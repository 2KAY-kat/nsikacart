<?php
session_start();

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

function outputJson($data) {
    echo json_encode($data);
    exit;
}

try {
    if (!isset($_SESSION['user']['id']) || empty($_SESSION['user']['id'])) {
        http_response_code(401);
        outputJson([
            'success' => false,
            'message' => 'Authentication required. Please log in first.',
            'redirect' => '../../auth/login.html'
        ]);
    }

    if (!file_exists('../config/db.php')) {
        outputJson(['success' => false, 'message' => 'Database configuration not found']);
    }

    require_once '../config/db.php';
    require_once __DIR__ . '/../../helpers/cloudinary.php';

    if (!isset($pdo) || !($pdo instanceof PDO)) {
        outputJson(['success' => false, 'message' => 'Database connection failed']);
    }

    $input_raw = file_get_contents('php://input');
    if (empty($input_raw)) outputJson(['success' => false, 'message' => 'No input data received']);

    $input = json_decode($input_raw, true);
    if (json_last_error() !== JSON_ERROR_NONE)
        outputJson(['success' => false, 'message' => 'Invalid JSON: ' . json_last_error_msg()]);

    if (empty($input['product_id'])) outputJson(['success' => false, 'message' => 'Product ID required']);

    $product_id = (int)$input['product_id'];
    $user_id = (int)$_SESSION['user']['id'];

    // Check product ownership
    $stmt = $pdo->prepare("SELECT id, main_image, images, user_id FROM products WHERE id = ? AND user_id = ?");
    $stmt->execute([$product_id, $user_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        outputJson(['success' => false, 'message' => 'Product not found or not owned by you']);
    }

    // Begin transaction
    $pdo->beginTransaction();

    // Delete from database
    $delete_stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND user_id = ?");
    $delete_stmt->execute([$product_id, $user_id]);

    if ($delete_stmt->rowCount() === 0) {
        throw new Exception('Failed to delete product record.');
    }

    // Delete from Cloudinary
    $cloud_deleted = [];

    try {
        // Delete main image (if available)
        if (!empty($product['main_image'])) {
            $mainImagePublicId = cloudinary_extract_public_id($product['main_image']);
            if ($mainImagePublicId) {
                $result = cloudinary_delete($mainImagePublicId);
                $cloud_deleted[] = ['main_image' => $result];
            }
        }

        // Delete multiple images (if available)
        if (!empty($product['images'])) {
            $images = json_decode($product['images'], true);
            if (is_array($images)) {
                foreach ($images as $imgUrl) {
                    $pid = cloudinary_extract_public_id($imgUrl);
                    if ($pid) {
                        $result = cloudinary_delete($pid);
                        $cloud_deleted[] = ['extra_image' => $result];
                    }
                }
            }
        }

    } catch (Exception $e) {
        cloudinary_log("Failed deleting Cloudinary assets: " . $e->getMessage());
    }

    $pdo->commit();

    outputJson([
        'success' => true,
        'message' => 'Product deleted successfully',
        'cloudinary_deleted' => $cloud_deleted
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Delete error: " . $e->getMessage());
    outputJson(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} catch (Error $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Fatal error: " . $e->getMessage());
    outputJson(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

outputJson(['success' => false, 'message' => 'Unknown error occurred']);