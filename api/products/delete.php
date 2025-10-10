<?php
// Start session first
session_start();

// Disable error reporting to prevent HTML output
error_reporting(0);
ini_set('display_errors', 0);

// Set headers immediately
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// Function to safely output JSON and exit
function outputJson($data)
{
    echo json_encode($data);
    exit;
}

try {
    // Check if user is logged in FIRST - using correct session structure
    if (!isset($_SESSION['user']['id']) || empty($_SESSION['user']['id'])) {
        http_response_code(401);
        outputJson([
            'success' => false,
            'message' => 'Authentication required. Please log in first.',
            'redirect' => '../../auth/login.html'
        ]);
    }

    // Include database config after auth check
    if (!file_exists('../config/db.php')) {
        outputJson([
            'success' => false,
            'message' => 'Database configuration not found'
        ]);
    }

    require_once '../config/db.php';

    // Check if PDO was created successfully
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        outputJson([
            'success' => false,
            'message' => 'Database connection failed'
        ]);
    }

    // Get JSON input
    $input_raw = file_get_contents('php://input');
    if (empty($input_raw)) {
        outputJson([
            'success' => false,
            'message' => 'No input data received'
        ]);
    }

    $input = json_decode($input_raw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        outputJson([
            'success' => false,
            'message' => 'Invalid JSON input: ' . json_last_error_msg()
        ]);
    }

    if (!isset($input['product_id']) || empty($input['product_id'])) {
        outputJson([
            'success' => false,
            'message' => 'Product ID is required'
        ]);
    }

    $product_id = (int)$input['product_id'];
    $user_id = (int)$_SESSION['user']['id'];

    if ($product_id <= 0) {
        outputJson([
            'success' => false,
            'message' => 'Invalid product ID'
        ]);
    }

    // First, verify the product belongs to the current user
    $check_stmt = $pdo->prepare("SELECT id, main_image, images, user_id FROM products WHERE id = ? AND user_id = ?");
    if (!$check_stmt) {
        outputJson([
            'success' => false,
            'message' => 'Database query preparation failed'
        ]);
    }

    $check_result = $check_stmt->execute([$product_id, $user_id]);
    if (!$check_result) {
        outputJson([
            'success' => false,
            'message' => 'Database query execution failed'
        ]);
    }

    $product = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        outputJson([
            'success' => false,
            'message' => 'Product not found or you do not have permission to delete this product'
        ]);
    }

    // Begin transaction for safe deletion
    if (!$pdo->beginTransaction()) {
        outputJson([
            'success' => false,
            'message' => 'Failed to start database transaction'
        ]);
    }

    try {
        // Delete the product from database first
        $delete_stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND user_id = ?");
        if (!$delete_stmt) {
            throw new Exception('Failed to prepare delete statement');
        }

        $delete_result = $delete_stmt->execute([$product_id, $user_id]);

        if (!$delete_result || $delete_stmt->rowCount() === 0) {
            throw new Exception('Failed to delete product from database');
        }

        // Delete associated image files (after successful DB deletion)
        $upload_dir = __DIR__ . '/../../public/dashboard/uploads/';

        // Ensure upload directory exists and is accessible
        if (!is_dir($upload_dir)) {
            error_log("Upload directory not found: " . $upload_dir);
        } else {
            // Delete main image
            if (!empty($product['main_image'])) {
                $main_image_path = $upload_dir . basename($product['main_image']);
                if (file_exists($main_image_path) && is_file($main_image_path)) {
                    @unlink($main_image_path);
                }
            }

            // Delete other images
            if (!empty($product['images'])) {
                $images = json_decode($product['images'], true);
                if (is_array($images)) {
                    foreach ($images as $image) {
                        if (!empty($image)) {
                            $image_path = $upload_dir . basename($image);
                            if (file_exists($image_path) && is_file($image_path)) {
                                @unlink($image_path);
                            }
                        }
                    }
                }
            }
        }

        // Commit transaction
        if (!$pdo->commit()) {
            throw new Exception('Failed to commit transaction');
        }

        outputJson([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollback();
        error_log("Product deletion DB error: " . $e->getMessage());
        outputJson([
            'success' => false,
            'message' => 'Failed to delete product: ' . $e->getMessage()
        ]);
    }
} catch (Exception $e) {
    error_log("Product deletion error: " . $e->getMessage());
    outputJson([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
} catch (Error $e) {
    error_log("Product deletion fatal error: " . $e->getMessage() . " in " . $e->getFile() . " line " . $e->getLine());
    outputJson([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

// Fallback - should never reach here
outputJson([
    'success' => false,
    'message' => 'Unknown error occurred'
]);
