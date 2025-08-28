<?php
ob_start();

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
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Verify request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method is allowed');
    }

    if (!isset($pdo)) {
        throw new Exception('Database connection failed');
    }

    if (!isset($current_user_id)) {
        throw new Exception('User not authenticated');
    }

    // Get and validate JSON input
    $rawInput = file_get_contents('php://input');
    if (!$rawInput) {
        throw new Exception('No input data provided');
    }

    $input = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data provided');
    }
    
    if (!isset($input['product_id']) || !is_numeric($input['product_id'])) {
        throw new Exception('Valid product ID is required');
    }

    $product_id = (int)$input['product_id'];
    $quantity = isset($input['quantity']) && is_numeric($input['quantity']) 
        ? max(1, min(99, (int)$input['quantity'])) 
        : 1;

    // Transaction to ensure data consistency
    $pdo->beginTransaction();

    try {
        // Check if product exists and is active
        $check_product = $pdo->prepare("
            SELECT id, name, status 
            FROM products 
            WHERE id = ? AND status = 'active'
        ");
        $check_product->execute([$product_id]);
        $product = $check_product->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new Exception('Product not found or is not available');
        }

        // Check if already in saved items
        $check_saved = $pdo->prepare("
            SELECT id 
            FROM saved_items 
            WHERE user_id = ? AND product_id = ?
        ");
        $check_saved->execute([$current_user_id, $product_id]);
        
        if ($check_saved->rowCount() > 0) {
            throw new Exception('Product already in saved list');
        }

        // Add to saved items
        $stmt = $pdo->prepare("
            INSERT INTO saved_items (user_id, product_id, quantity) 
            VALUES (?, ?, ?)
        ");
        
        if (!$stmt->execute([$current_user_id, $product_id, $quantity])) {
            throw new Exception('Failed to add item to saved list');
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Product added to saved list',
            'product_name' => $product['name'],
            'saved_item' => [
                'product_id' => $product_id,
                'quantity' => $quantity
            ]
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (PDOException $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'A database error occurred'
    ]);
} catch (Exception $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] General error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

ob_end_flush();