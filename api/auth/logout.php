<?php 
session_start();
header('Content-Type: application/json');

require_once '../config/db.php';

// before destroying teh current loogged un session remove remember_token from DB if its cookie still exists
if (isset($_COOKIE['remember_token'])) {
    $token = hash('sha256', $_COOKIE['remember_token']);
    $stmt = $pdo->prepare("DELETE FROM user_remember_tokens WHERE token = ?");
    $stmt->execute([$token]);
    // then clear the cookie
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
}

// Destroy session
$_SESSION = [];
session_unset();
session_destroy();

echo json_encode([
    "success" => true,
    "message" => "You have been logged out successfully."
]);