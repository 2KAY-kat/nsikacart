<?php
ob_start();

// Disable error display
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../../logs/debug.log');

session_start();
require_once __DIR__ . '../../config/db.php';
require_once __DIR__ . '../../middleware/auth_required.php';

// Clear any previous output
ob_clean();

header('Content-Type: application/json');

try {
    if (!isset($current_user_id)) {
        throw new Exception('User not authenticated');
    }

    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM saved_items 
        WHERE user_id = ?
    ");
    
    $stmt->execute([$current_user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'count' => (int)$result['count']
    ]);

} catch (PDOException $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

ob_end_flush();