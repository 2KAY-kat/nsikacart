<?php
ob_start();

// Disable error display
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../../logs/debug.log');

session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth_required.php';

// Clear any previous output
ob_clean();

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['product_id'])) {
        throw new Exception('Product ID is required');
    }

    if (!isset($current_user_id)) {
        throw new Exception('User not authenticated');
    }

    $product_id = (int)$input['product_id'];

    // Get product name before deletion
    $name_stmt = $pdo->prepare("
        SELECT p.name FROM saved_items si 
        JOIN products p ON si.product_id = p.id 
        WHERE si.user_id = ? AND si.product_id = ?
    ");
    $name_stmt->execute([$current_user_id, $product_id]);
    $product = $name_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new Exception('Product not found in saved list');
    }

    // Delete item
    $stmt = $pdo->prepare("
        DELETE FROM saved_items 
        WHERE user_id = ? AND product_id = ?
    ");
    
    $result = $stmt->execute([$current_user_id, $product_id]);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Item removed from saved list',
            'product_name' => $product['name']
        ]);
    } else {
        throw new Exception('Failed to remove item');
    }

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