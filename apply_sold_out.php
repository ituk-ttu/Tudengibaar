<?php
include('db.connection.php');

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $drink_id = isset($_POST['id']) ? intval($_POST['id']) : null;

        if ($drink_id === null) {
            echo json_encode(['error' => 'Invalid request']);
            exit;
        }

        $stmt = $db->prepare("SELECT is_soldout FROM prices WHERE id = :drink_id");
        $stmt->execute(['drink_id' => $drink_id]);
        $drink = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($drink) {
            $newStatus = ($drink['is_soldout'] == 1) ? 0 : 1;

            $stmt = $db->prepare("UPDATE prices SET is_soldout = :newStatus WHERE id = :drink_id");
            $stmt->execute(['newStatus' => $newStatus, 'drink_id' => $drink_id]);

            $message = ($newStatus == 1) ? 'Drink marked as sold out' : 'Drink marked as available';
            echo json_encode(['success' => true, 'message' => $message]);
        } else {
            echo json_encode(['error' => 'Drink not found']);
        }
        exit;
    }
    echo json_encode(['error' => 'Invalid request']);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
