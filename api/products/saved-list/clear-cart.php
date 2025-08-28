<?php
ob_start();
session_start();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth_required.php';

header('Content-Type: application/json');

try {
    if (!isset($current_user_id)) {
        throw new Exception('User not authenticated');
    }

    $stmt = $pdo->prepare("DELETE FROM saved_items WHERE user_id = ?");
    $result = $stmt->execute([$current_user_id]);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Cart cleared successfully'
        ]);
    } else {
        throw new Exception('Failed to clear cart');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

ob_end_flush();