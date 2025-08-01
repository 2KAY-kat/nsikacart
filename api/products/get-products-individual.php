<?php
session_start();
// add error reporting for debugging
ini_set('display_errors', 0); 
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once '../config/db.php';
require_once '../middleware/auth_required.php';

try {
    $user_id = $current_user_id;
    
    // Pagination parameters
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(5, min(50, intval($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;
    
    // Get total count
    $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM products WHERE user_id = ?");
    $countStmt->execute([$user_id]);
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalRecords / $limit);
    
    // Get paginated products
    $stmt = $pdo->prepare("SELECT * FROM products WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([$user_id, $limit, $offset]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as &$product) {
        // add error handling for JSON decoding
        $images = json_decode($product['images'], true);
        $product['images'] = ($images !== null) ? $images : [];
    }
    
    echo json_encode([
        "success" => true,
        "products" => $products,
        "pagination" => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => (int)$totalRecords,
            'limit' => $limit,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error loading products: " . $e->getMessage()
    ]);
}