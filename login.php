<?php
session_start();

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;
include('db.connection.php');
require_once 'vendor/autoload.php';

date_default_timezone_set('Europe/Tallinn');


$loader = new FilesystemLoader('templates');
$twig = new Environment($loader);

$error = null;

function sanitizeInputVar($var){
    $var = stripslashes($var);
    $var = htmlentities($var);
    $var = strip_tags($var);
    return $var;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInputVar($_POST['username'] ?? '');
    $password = sanitizeInputVar($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        try {
            $stmt = $db->prepare('SELECT id, username, password FROM users WHERE username = :username');
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];

                    if ($user['username'] === 'admin') {
                        $_SESSION['admin_id'] = $user['id'];
                        header('Location: admin.php');
                    } else {
                        header('Location: index.php');
                    }
                    exit();
                } else {
                    $error = "Invalid username or password.";
                }
            } else {
                $error = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            error_log("Error fetching user: " . $e->getMessage());
            $error = "An error occurred while processing your login. Please try again later.";
        }
    } else {
        $error = "Please fill in both fields.";
    }
}

echo $twig->render('login.twig', [
    'error' => $error,
]);
