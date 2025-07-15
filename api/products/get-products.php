<?php
session_start();
require_once '../config/db.php';
require_once '../middleware/auth_required.php';

header('Content-Type: application/json');

try {
    // get the user_id from the session
    $user_id = $_SESSION['user']['id'];
    
    $stmt = $pdo->prepare("
        SELECT p.*, u.name 
        FROM products p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.user_id = ?
        ORDER BY p.created_at DESC
    ");
    
    $stmt->execute([$user_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'products' => $products
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch products: ' . $e->getMessage()
    ]);
}