<?php
require_once '../config/db.php';
header('Content-Type: application/json');

try { // joining the users table and the products table lets as refernce the sellers details and their uploaded products i the sche,a its doen with FKs
    // a trick i leanrned in some book
    $stmt = $pdo->query("
        SELECT 
            p.id, 
            p.name, 
            p.price, 
            p.description, 
            p.category, 
            p.location, 
            p.images, 
            p.main_image,
            u.phone as seller_phone,
            u.name as seller_name
        FROM products p 
        LEFT JOIN users u ON p.user_id = u.id 
        WHERE p.status = 'active' 
        ORDER BY p.created_at DESC
    ");

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process image data and ensure proper format
    foreach ($products as &$product) {
        $product['images'] = json_decode($product['images'], true) ?: [];
        
        // Convert image filenames to web URLs
        if (!empty($product['images'])) {
            $product['images'] = array_map(function($imageName) {
                return '/nsikacart/public/dashboard/uploads/' . $imageName;
            }, $product['images']);
        }
        
        // Handle main_image conversion
        if (!empty($product['main_image'])) {
            // If main_image doesn't start with /, it's just a filename
            if (strpos($product['main_image'], '/') !== 0) {
                $product['main_image'] = '/nsikacart/public/dashboard/uploads/' . $product['main_image'];
            }
        } else if (!empty($product['images'])) {
            // Use first image as main_image if main_image is empty
            $product['main_image'] = $product['images'][0];
        } else {
            // Fallback to placeholder
            $product['main_image'] = '/nsikacart/public/assets/placeholder.png';
        }
        
        // Convert price to float
        $product['price'] = floatval($product['price']);
        
        // the phone number formated for whats chani chnai
        if (!empty($product['seller_phone'])) {
            $product['seller_phone'] = preg_replace('/[^0-9]/', '', $product['seller_phone']);
        }
    }

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
?>
