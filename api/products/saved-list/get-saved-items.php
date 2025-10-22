<?php
// Start output buffering
ob_start();

// Enable error logging but disable display
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
    // First check if database connection exists
    if (!isset($pdo)) {
        throw new Exception('Database connection not established');
    }

    // Check if user is authenticated
    if (!isset($current_user_id)) {
        throw new Exception('User not authenticated');
    }

    // Get saved items with error logging
    try {
        // Modified query to get location from products table instead of users
        $stmt = $pdo->prepare("
            SELECT 
                si.id as saved_item_id,
                si.product_id,
                si.quantity,
                p.name,
                p.price as dollar,
                p.description,
                p.main_image as image,
                p.created_at as posted_date,
                p.location,           
                u.name as seller_name
            FROM saved_items si
            JOIN products p ON si.product_id = p.id
            JOIN users u ON p.user_id = u.id
            WHERE si.user_id = ?
            ORDER BY si.created_at DESC
        ");

        $stmt->execute([$current_user_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Process image paths
        foreach ($items as &$item) {
            if (!empty($item['image'])) {
                // If image doesn't start with /, it's just a filename
                if (strpos($item['image'], '/') !== 0) {
                    $item['image'] = './dashboard/uploads/' . $item['image'];
                }
            } else {
                // message placeholder
                echo json_encode([
                    "success" => false,
                    "message" => "no image"
                ]);
            }
        }

        echo json_encode([
            'success' => true,
            'saved_items' => $items,
            'user_id' => $current_user_id
        ]);

    } catch (PDOException $e) {
        error_log("[" . date('Y-m-d H:i:s') . "] Query error: " . $e->getMessage());
        throw new Exception('Failed to fetch saved items');
    }

} catch (PDOException $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] Database error in get-saved-items.php: " . $e->getMessage());
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

// Ensure all output is flushed
ob_end_flush();