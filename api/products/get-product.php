<?php
// Start output buffering to catch any unwanted output
ob_start();

// Disable error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(0);

session_start();
header('Content-Type: application/json');

// Clear any output that might have been generated
ob_clean();

require_once '../config/db.php';
require_once '../middleware/auth_required.php';

try {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('Invalid product ID');
    }
    
    $product_id = (int)$_GET['id'];
    $user_id = $current_user_id;
    
    // Ensure user can only edit their own products
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND user_id = ?");
    $stmt->execute([$product_id, $user_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception('Product not found or access denied');
    }
    
    // Decode images JSON
    $product['images'] = json_decode($product['images'], true) ?: [];
    
    // Clear output buffer and send clean JSON
    ob_clean();
    echo json_encode([
        'success' => true,
        'product' => $product
    ]);
    
} catch (Exception $e) {
    // Clear output buffer and send clean error JSON
    ob_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// End output buffering
ob_end_flush();
?>