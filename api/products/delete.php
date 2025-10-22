<?php
session_start();
header('Content-Type: application/json');

require_once '../config/db.php';
require_once __DIR__ . '/../../helpers/cloudinary.php';

function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'message' => 'Invalid method'], 405);
}

if (empty($_SESSION['user']['id'])) {
    respond(['success' => false, 'message' => 'Not authenticated'], 401);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['product_id'])) {
    respond(['success' => false, 'message' => 'Missing product_id'], 400);
}

$product_id = (int)$input['product_id'];
$user_id = (int)$_SESSION['user']['id'];

// Get product info
$stmt = $pdo->prepare("SELECT id, main_image_public_id, images_public_ids, user_id FROM products WHERE id = ? AND user_id = ?");
$stmt->execute([$product_id, $user_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    respond(['success' => false, 'message' => 'Product not found or permission denied'], 404);
}

try {
    $pdo->beginTransaction();

    // Delete product record
    $delStmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND user_id = ?");
    $delStmt->execute([$product_id, $user_id]);

    if ($delStmt->rowCount() === 0) {
        throw new Exception('Failed to delete product');
    }

    $cloudinary_results = [];

    // Delete main image from Cloudinary
    if (!empty($product['main_image_public_id'])) {
        $cloudinary_results[] = [
            'main_image' => cloudinary_delete($product['main_image_public_id'])
        ];
    }

    // Delete other images (if any)
    if (!empty($product['images_public_ids'])) {
        $extra_ids = json_decode($product['images_public_ids'], true);
        if (is_array($extra_ids)) {
            foreach ($extra_ids as $pid) {
                if (!empty($pid)) {
                    $cloudinary_results[] = [
                        'extra_image' => cloudinary_delete($pid)
                    ];
                }
            }
        }
    }

    $pdo->commit();
    respond([
        'success' => true,
        'message' => 'Product deleted successfully',
        'cloudinary_deleted' => $cloudinary_results
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    cloudinary_log("Delete failed: " . $e->getMessage());
    respond(['success' => false, 'message' => 'Deletion failed: ' . $e->getMessage()]);
}