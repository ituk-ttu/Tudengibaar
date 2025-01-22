<?php
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;

require_once 'vendor/autoload.php';
include('db.connection.php');

date_default_timezone_set('Europe/Tallinn');

// Database configuration using environment variables for added security
function checkIfPriceHistoryEmpty($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM price_history");
    return $stmt->fetchColumn() == 0; // Return true if empty
}

function insertBasePrices($pdo) {
    $stmt = $pdo->query("SELECT id, current_price FROM prices");
    $prices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $insertStmt = $pdo->prepare("INSERT INTO price_history (drink_id, price, timestamp) VALUES (:drink_id, :price, :timestamp)");
    $currentTimestamp = date('Y-m-d H:i:s');

    foreach ($prices as $price) {
        $insertStmt->execute([
            ':drink_id' => $price['id'],
            ':price' => $price['current_price'],
            ':timestamp' => $currentTimestamp,
        ]);
    }
}

// Insert base prices if price_history is empty
if (checkIfPriceHistoryEmpty($db)) {
    insertBasePrices($db);
}

// Fetch price history data with drink names
function getPriceHistory($pdo) {
    $stmt = $pdo->prepare("SELECT ph.drink_id, ph.price, ph.timestamp, p.name 
                           FROM price_history ph
                           JOIN prices p ON ph.drink_id = p.id
                           ORDER BY ph.drink_id");
    $stmt->execute();

    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $drinkId = $row['drink_id'];
        $drinkName = htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); // Sanitize for safety

        $timeOnly = date('Y-m-d H:i:s', strtotime($row['timestamp'])); // Keep full date and time
        $timestampMillis = strtotime($timeOnly) * 1000; // Convert to milliseconds for Highcharts

        // Initialize data array for each drink if not already set
        if (!isset($data[$drinkId])) {
            $data[$drinkId] = [
                'name' => $drinkName,
                'data' => []
            ];
        }

        // Append price data with the timestamp
        $data[$drinkId]['data'][] = [
            $timestampMillis, // Converted timestamp in milliseconds
            (float)$row['price'] // Ensure price is a float
        ];
    }

    return $data;
}


// Fetch current prices for drinks
function getPrice($pdo) {
    $stmt = $pdo->prepare("SELECT name, current_price, difference FROM prices");
    $stmt->execute();
    $priceData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Sanitize data
    foreach ($priceData as &$price) {
        $price['name'] = htmlspecialchars($price['name'], ENT_QUOTES, 'UTF-8');
    }

    return $priceData;
}

// Prepare data for Highcharts
$priceHistory = getPriceHistory($db);
$prices = getPrice($db);
$seriesData = [];
$priceData = [];

foreach ($priceHistory as $drinkId => $drinkData) {
    $seriesData[] = [
        'name' => $drinkData['name'],
        'data' => $drinkData['data']
    ];
}

foreach ($prices as $price) {
    $priceData[] = [
        'name' => $price['name'],
        'current_price' => $price['current_price'],
        'difference' => $price['difference']
    ];
}

// Initialize Twig
$loader = new FilesystemLoader('templates');
$twig = new Environment($loader);

try {
    // Render the Twig template with series data
    echo $twig->render('prices.twig', [
        'seriesData' => $seriesData, // Pass directly for Twig HTML list
        'seriesDataJson' => json_encode($seriesData), // JSON encode for JavaScript
        'priceData' => $priceData,
    ]);
} catch (LoaderError $e) {
    error_log('Error loading template: ' . $e->getMessage()); // Log the error
    echo 'An error occurred. Please try again later.'; // Generic message for users
} catch (RuntimeError $e) {
    error_log('Runtime error: ' . $e->getMessage()); // Log the error
    echo 'An error occurred. Please try again later.'; // Generic message for users
} catch (SyntaxError $e) {
    error_log('Syntax error: ' . $e->getMessage()); // Log the error
    echo 'An error occurred. Please try again later.'; // Generic message for users
}