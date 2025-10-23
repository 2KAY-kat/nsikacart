<?php
// Start output buffering
ob_start();

// Logging setup
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../../logs/debug.log');

session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth_required.php';

// Clear any buffered output
ob_clean();
header('Content-Type: application/json');

try {
    if (!isset($pdo)) {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection not established'
        ]);
    }

    if (!isset($current_user_id)) {
        echo json_encode([
            'success' => false,
            'message' => 'User not authenticated'
        ]);
    }

    $stmt = $pdo->prepare("
        SELECT 
            si.id AS saved_item_id,
            si.product_id,
            si.quantity,
            p.name,
            p.price AS dollar,
            p.description,
            p.main_image,
            p.created_at AS posted_date,
            p.location,
            u.name AS seller_name
        FROM saved_items si
        JOIN products p ON si.product_id = p.id
        JOIN users u ON p.user_id = u.id
        WHERE si.user_id = ?
        ORDER BY si.created_at DESC
    ");

    $stmt->execute([$current_user_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as &$item) {
        $image = $item['main_image'];

        // Handling both Cloudinary URLs and local uploads
        if (!empty($image)) {
            if (filter_var($image, FILTER_VALIDATE_URL)) {
                // Cloudinary or external image
                $item['image'] = $image;
            } else {
                // my old fall back off my uploads folder config
                $item['image'] = './dashboard/uploads/' . ltrim($image, '/');
            }
        } else {
            // Default placeholder image if missing
            $item['image'] = './assets/no-image.png';
        }
    }

    echo json_encode([
        'success' => true,
        'saved_items' => $items,
        'user_id' => $current_user_id
    ]);

} catch (PDOException $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] DB error in get-saved-items.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'debug' => [
            'error' => $e->getMessage(),
            'code' => $e->getCode()
        ]
    ]);
} catch (Exception $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] General error in get-saved-items.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

ob_end_flush();