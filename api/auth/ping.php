<?php
require_once '../config/db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['session_token']) || !isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized: No session token or user found"
    ]);
    exit;
}

$sessionToken = $_SESSION['session_token'];
$userId = $_SESSION['user']['id'];

try {
    // Update both session_token and user_id
    $stmt = $pdo->prepare("UPDATE sessions SET last_active = NOW(), user_id = :user_id WHERE session_token = :token");
    $stmt->execute([
        ':token' => $sessionToken,
        ':user_id' => $userId
    ]);

    echo json_encode([
        "success" => true,
        "message" => "session activity updated"
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: failed to update session Activity"
    ]);
}

?>