<?php
session_start();

header('Content-Type: application/json');

echo json_encode([
    'session_id' => session_id(),
    'user_id' => $_SESSION['user']['id'] ?? null, // Fixed to match actual structure
    'user_name' => $_SESSION['user']['name'] ?? null,
    'user_role' => $_SESSION['user']['role'] ?? null,
    'is_logged_in' => isset($_SESSION['user']['id']) && !empty($_SESSION['user']['id']),
    'session_data' => $_SESSION,
    'cookie_data' => $_COOKIE
]);
?>