<?php
include('db.connection.php');

try {
    $stmt = $db->prepare("
        UPDATE prices 
        SET current_price = last_price, 
            difference = last_difference, 
            last_price = NULL, 
            price_expiry = NULL,  
            last_difference = NULL 
        WHERE price_expiry <= NOW() AND last_price IS NOT NULL
    ");
    $stmt->execute();

    $stmt = $db->query("SELECT id, name, current_price, difference, is_shot, is_cocktail, is_soldout FROM prices");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($data);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
