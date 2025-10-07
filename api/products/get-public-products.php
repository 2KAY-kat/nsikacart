<?php
require_once '../config/db.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("
        SELECT 
            p.id, p.name, p.price, p.description, p.category, p.location, p.images, p.main_image,
            u.phone as seller_phone, u.name as seller_name
        FROM products p 
        LEFT JOIN users u ON p.user_id = u.id 
        WHERE p.status = 'active' 
        ORDER BY p.created_at DESC
    ");

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Web base path for images (assumes public/ is webroot)
    $upload_web_base = '/dashboard/uploads/';

    foreach ($products as &$product) {
        $product['images'] = json_decode($product['images'], true) ?: [];

        // convert stored filenames to web URLs
        if (!empty($product['images'])) {
            $product['images'] = array_map(function($imageName) use ($upload_web_base) {
                // If the stored value is already a URL, keep it
                if (filter_var($imageName, FILTER_VALIDATE_URL)) return $imageName;
                return $upload_web_base . ltrim($imageName, '/');
            }, $product['images']);
        }

        if (!empty($product['main_image'])) {
            if (filter_var($product['main_image'], FILTER_VALIDATE_URL)) {
                // keep absolute URLs
            } else {
                $product['main_image'] = $upload_web_base . ltrim($product['main_image'], '/');
            }
        } else if (!empty($product['images'])) {
            $product['main_image'] = $product['images'][0];
        } else {
            $product['main_image'] = '/public/assets/placeholder.png';
        }

        $product['price'] = floatval($product['price']);
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
