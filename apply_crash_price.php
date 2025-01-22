<?php
include('db.connection.php');

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate and sanitize inputs
        $drink_id = isset($_POST['id']) ? intval($_POST['id']) : null;
        $crashed_price = isset($_POST['crashed_price']) ? floatval($_POST['crashed_price']) : null;
        $duration = isset($_POST['duration']) ? intval($_POST['duration']) : null;

        // Check for missing parameters
        if (!$drink_id || !$crashed_price || !$duration) {
            echo json_encode(['error' => 'All fields are required.']);
            exit;
        }

        // Calculate expiry time
        $expiry_time = date('Y-m-d H:i:s', strtotime("+$duration minutes"));

        // Fetch the current price, difference, and base price for calculations
        $stmt = $db->prepare("SELECT current_price, difference, base_price FROM prices WHERE id = :drink_id");
        $stmt->execute(['drink_id' => $drink_id]);
        $drink = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$drink) {
            echo json_encode(['error' => 'Invalid drink ID.']);
            exit;
        }

        $current_price = $drink['current_price'];
        $last_difference = $drink['difference'];
        $base_price = $drink['base_price'];

        $stmt = $db->prepare("
            UPDATE prices 
              SET last_price = :current_price, 
                  current_price = :crashed_price, 
                  price_expiry = :expiry_time,
                  last_difference = :last_difference,
                  difference = :difference
              WHERE id = :drink_id
        ");
        $new_difference = round($crashed_price - $base_price, 2);
        $stmt->execute([
            'current_price' => $current_price,
            'crashed_price' => $crashed_price,
            'expiry_time' => $expiry_time,
            'last_difference' => $last_difference,
            'difference' => $new_difference,
            'drink_id' => $drink_id
        ]);

        // Return success response
        echo json_encode(['success' => true, 'message' => 'Price crashed successfully!']);
    } else {
        echo json_encode(['error' => 'Invalid request method.']);
    }
} catch (PDOException $e) {
    // Handle database errors
    echo json_encode(['error' => $e->getMessage()]);
}
