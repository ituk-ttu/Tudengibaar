<?php
include('db.connection.php');

try {

    $stmt = $db->query("
        SELECT 
            p.id,
            p.name, 
            ph.timestamp, 
            ph.price
        FROM prices p
        LEFT JOIN price_history ph ON p.id = ph.drink_id
        WHERE p.show_in_chart = 1
    ");

    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $drinkId = $row['id'];

        if (!isset($data[$drinkId])) {
            $data[$drinkId] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'price_history' => []
            ];
        }

        if ($row['timestamp'] && $row['price']) {
            $data[$drinkId]['price_history'][] = [
                'timestamp' => $row['timestamp'],
                'price' => $row['price']
            ];
        }
    }

    $output = array_values($data);

    header('Content-Type: application/json');
    echo json_encode($output);

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    header('Content-Type: application/json', true, 500);
    echo json_encode(['error' => 'An error occurred. Please try again later.']);
}
