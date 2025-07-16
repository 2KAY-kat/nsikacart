<?php
session_start();
header('Content-Type: application/json');

// Send back session info (be careful not to expose sensitive data in production)
echo json_encode([
    'session_active' => isset($_SESSION['user_id']),
    'session_id' => session_id(),
    'session_status' => session_status()
]);
?>