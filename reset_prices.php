<?php

include('db.connection.php');

header('Content-Type: application/json'); // Ensure the response is in JSON format

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $stmt = $db->prepare("UPDATE prices SET current_price = base_price, difference = 0.00");
        $stmt->execute();

        $stmt = $db->query("SELECT id, current_price FROM prices");
        $prices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $insertStmt = $db->prepare("INSERT INTO price_history (drink_id, price, timestamp) VALUES (:drink_id, :price, :timestamp)");
        $currentTimestamp = date('Y-m-d H:i:s');

        echo json_encode(['success' => true, 'message' => 'All prices reset successfully']);
        exit;
    }
    echo json_encode(['error' => 'Invalid request method']);
    exit;
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
