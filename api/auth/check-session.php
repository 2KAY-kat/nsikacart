<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'No active session'
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'user' => [
        'id' => $_SESSION['user']['id'],
        'name' => $_SESSION['user']['name'],
        'role' => $_SESSION['user']['role']
    ]
]);