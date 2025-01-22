<?php

include('db.connection.php');
require 'vendor/autoload.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;

date_default_timezone_set('Europe/Tallinn');

// Fetch all drinks for bartender dashboard
$stmt = $db->query('SELECT id, name, base_price, current_price, min_price, sales_count, difference, is_shot, is_cocktail FROM prices');
$drinks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to round values to two decimal places
function roundToTwoDecimals($value)
{
    return round($value, 2);
}

// Batch update prices for drinks
function updateDrinkPrices($db, $drinkIds, $quantities)
{
    $updates = [];
    $history = [];

    foreach ($drinkIds as $index => $drinkId) {
        $quantity = (int)$quantities[$index];

        $stmt = $db->prepare('SELECT id, name, base_price, current_price, min_price, max_price, difference, is_shot, is_cocktail FROM prices WHERE id = :id');
        $stmt->execute([':id' => $drinkId]);
        $drink = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$drink) {
            continue;
        }

        // Handle drinks without min and max prices separately
        if (is_null($drink['min_price']) && is_null($drink['max_price'])) {
            $stmt = $db->prepare('UPDATE prices SET sales_count = sales_count + :quantity WHERE id = :id');
            $stmt->execute([':quantity' => $quantity, ':id' => $drinkId]);
            continue;
        }

        // Calculate price increase based on base_price
        $totalIncrease = $drink['is_shot'] ? 0.9 : 0.14;
        $priceIncrease = round($totalIncrease * $quantity, 2);
        $newPrice = round($drink['current_price'] + $priceIncrease, 2);
        $basePrice = $drink['base_price'];

        $newDifference = $drink['difference'] + $priceIncrease;
        $maxDifference = round(($basePrice - $drink['min_price']), 2);

        if ($newPrice > $drink['max_price']) {
            $newPrice = $drink['max_price'];
            $newDifference = $maxDifference;
        }

        $updates[] = [
            ':id' => $drinkId,
            ':current_price' => $newPrice,
            ':difference' => $newDifference,
            ':quantity' => $quantity
        ];

        $history[] = [
            ':drink_id' => $drinkId,
            ':price' => $newPrice,
            ':timestamp' => (new DateTime())->format('Y-m-d H:i:s')
        ];

        for ($i = 0; $i < $quantity; $i++) {
            $randomCount = ($drink['is_shot']) ? mt_rand(1, 2) : mt_rand(1, 3);
            $categoryCondition = ($drink['is_shot']) ? 'is_shot = 1' : 'is_cocktail = 1';

            $stmt = $db->prepare("SELECT id, current_price, max_price FROM prices WHERE id NOT IN (:excludedDrinkIds) AND $categoryCondition AND current_price >= max_price * 0.9 ORDER BY RAND() LIMIT :priorityCount");
            $stmt->bindValue(':priorityCount', $randomCount, PDO::PARAM_INT);
            $stmt->bindValue(':excludedDrinkIds', implode(',', $drinkIds), PDO::PARAM_STR);
            $stmt->execute();
            $priorityDrinkIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (count($priorityDrinkIds) < $randomCount) {
                $remainingCount = $randomCount - count($priorityDrinkIds);
                $stmt = $db->prepare("SELECT id FROM prices WHERE id NOT IN (:excludedDrinkIds) AND $categoryCondition ORDER BY RAND() LIMIT :remainingCount");
                $stmt->bindValue(':remainingCount', $remainingCount, PDO::PARAM_INT);
                $stmt->bindValue(':excludedDrinkIds', implode(',', $drinkIds), PDO::PARAM_STR);
                $stmt->execute();
                $priorityDrinkIds = array_merge($priorityDrinkIds, $stmt->fetchAll(PDO::FETCH_COLUMN));
            }

            $reductions = [];
            $remainingReduction = $totalIncrease;

            for ($j = 0; $j < $randomCount; $j++) {
                if ($j === $randomCount - 1) {
                    $reductions[] = roundToTwoDecimals($remainingReduction);
                } else {
                    $reduction = roundToTwoDecimals(mt_rand(0, (int)($remainingReduction * 100)) / 100);
                    $reductions[] = $reduction;
                    $remainingReduction -= $reduction;
                }
            }

            foreach ($priorityDrinkIds as $index => $priorityDrinkId) {
                $stmt = $db->prepare('SELECT current_price, difference, min_price, max_price, base_price FROM prices WHERE id = :id');
                $stmt->execute([':id' => $priorityDrinkId]);
                $selectedDrink = $stmt->fetch(PDO::FETCH_ASSOC);

                // Apply reduction
                $reduction = $reductions[$index];  // Get the reduction for this drink
                $newPrice = round($selectedDrink['current_price'] - $reduction, 2);

                $basePrice = $selectedDrink['base_price'];
                $newDifference = $selectedDrink['difference'] - $reduction;

                $minDifference = round($basePrice - ($selectedDrink['max_price']), 2);

                if ($newPrice < $selectedDrink['min_price']) {
                    $newPrice = $selectedDrink['min_price'];
                    $newDifference = $minDifference;
                }

                $stmt = $db->prepare('UPDATE prices SET current_price = :current_price, difference = :difference WHERE id = :id');
                $stmt->execute([
                    ':current_price' => $newPrice,
                    ':difference' => $newDifference,
                    ':id' => $priorityDrinkId,
                ]);

                // Insert the price history for the price reduction
                $stmt = $db->prepare('INSERT INTO price_history (drink_id, price, timestamp) VALUES (:drink_id, :price, :timestamp)');
                $stmt->execute([
                    ':drink_id' => $priorityDrinkId,
                    ':price' => $newPrice,
                    ':timestamp' => (new DateTime())->format('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    // Perform all updates in one go
    if ($updates) {
        $stmt = $db->prepare('UPDATE prices SET current_price = :current_price, difference = :difference, sales_count = sales_count + :quantity WHERE id = :id');
        foreach ($updates as $update) {
            $stmt->execute($update);
        }
    }

    // Batch insert price history
    if ($history) {
        $stmt = $db->prepare('INSERT INTO price_history (drink_id, price, timestamp) VALUES (:drink_id, :price, :timestamp)');
        foreach ($history as $record) {
            $stmt->execute($record);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['drink_ids']) && isset($input['quantities'])) {
        $selectedDrinkIds = array_map('intval', $input['drink_ids']);
        $quantities = array_map('intval', $input['quantities']);

        updateDrinkPrices($db, $selectedDrinkIds, $quantities);

        http_response_code(200);
        echo json_encode(['success' => true]);
        exit;
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'No drink IDs or quantities provided']);
        exit;
    }
}

$loader = new FilesystemLoader('templates');
$twig = new Environment($loader);

try {
    echo $twig->render('bartender.twig', [
        'drinks' => $drinks,
    ]);
} catch (LoaderError $e) {
    error_log($e->getMessage());
} catch (RuntimeError $e) {
    error_log($e->getMessage());
} catch (SyntaxError $e) {
    error_log($e->getMessage());
}
