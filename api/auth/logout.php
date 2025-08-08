<?php 
session_start();
header('Content-Type: application/json');

require_once '../config/db.php';

// clean up persistent login if it exists helps for insession strorage in browser cache
if (isset($_COOKIE['remember_token'])) {
    $token = hash('sha256', $_COOKIE['remember_token']);
    $stmt = $pdo->prepare("DELETE FROM user_remember_tokens WHERE token = ?");
    $stmt->execute([$token]);
    setcookie('remember_token', '', time() - 3600, '/', '', false, true); 
} // removing the session stored in remember me table 

// kill session in DB if token is tracked in the sessions table 
if (isset($_SESSION['session_token'])) {
    $token = $_SESSION['session_token'];
    $stmt = $pdo->prepare("UPDATE sessions SET is_active = 0, expires_at = NOW() WHERE session_token = ?");
    $stmt->execute([$token]);
}

// destroy PHP session then give feedback on the logging in action in the end
$_SESSION = [];
session_unset();
session_destroy();

echo json_encode([
    "success" => true,
    "message" => "You have been logged out successfully."
]);