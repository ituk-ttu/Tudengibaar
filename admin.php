<?php

include('db.connection.php');
require_once 'vendor/autoload.php';

use Twig\Loader\FilesystemLoader;
use Twig\Environment;


$loader = new FilesystemLoader('templates');
$twig = new Environment($loader);

try {
    $stmt = $db->query("SELECT id, name FROM prices");
    $drinks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo $twig->render('admin.twig', ['drinks' => $drinks]);
} catch (PDOException $e) {
    echo $twig->render('error.twig', ['message' => $e->getMessage()]);
}
?>
