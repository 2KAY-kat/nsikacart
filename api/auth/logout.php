<?php 
session_start();
header('Content-Type: application/json');

require_once '../config/db.php';

// Clean up persistent login if it exists
if (isset($_COOKIE['remember_token'])) {
    $token = hash('sha256', $_COOKIE['remember_token']);
    $stmt = $pdo->prepare("DELETE FROM user_remember_tokens WHERE token = ?");
    $stmt->execute([$token]);
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
}

// Kill session in DB if token is tracked
if (isset($_SESSION['session_token'])) {
    $token = $_SESSION['session_token'];
    $stmt = $pdo->prepare("UPDATE sessions SET is_active = 0, expires_at = NOW() WHERE session_token = ?");
    $stmt->execute([$token]);
}

// Destroy PHP session
$_SESSION = [];
session_unset();
session_destroy();

echo json_encode([
    "success" => true,
    "message" => "You have been logged out successfully."
]);