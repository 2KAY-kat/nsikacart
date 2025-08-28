<?php
session_start();
require_once '../../config/db.php';
require_once '../../middleware/auth_required.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['product_id'])) {
        throw new Exception('Product ID is required');
    }

    $product_id = (int)$input['product_id'];
    $user_id = $current_user_id;

    // Get product name before deletion
    $name_stmt = $pdo->prepare("
        SELECT p.name FROM saved_items si 
        JOIN products p ON si.product_id = p.id 
        WHERE si.user_id = ? AND si.product_id = ?
    ");
    $name_stmt->execute([$user_id, $product_id]);
    $product = $name_stmt->fetch(PDO::FETCH_ASSOC);

    // Delete item
    $stmt = $pdo->prepare("
        DELETE FROM saved_items 
        WHERE user_id = ? AND product_id = ?
    ");
    
    $result = $stmt->execute([$user_id, $product_id]);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Item removed from saved list',
            'product_name' => $product ? $product['name'] : null
        ]);
    } else {
        throw new Exception('Failed to remove item');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}