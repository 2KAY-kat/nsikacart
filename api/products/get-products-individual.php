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
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as &$product) {
        // add error handling for JSON decoding
        $images = json_decode($product['images'], true);
        $product['images'] = ($images !== null) ? $images : [];
    }
    
    echo json_encode([
        "success" => true,
        "products" => $products
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error loading products: " . $e->getMessage()
    ]);
}