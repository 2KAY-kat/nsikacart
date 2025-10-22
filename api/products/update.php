<?php
session_start();

// Always return JSON
header('Content-Type: application/json; charset=utf-8');

// Do not echo PHP errors to client
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/php_errors.log');

set_exception_handler(function($e){
    error_log("Uncaught exception in update.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
    exit;
});

register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log("Shutdown fatal error in update.php: " . print_r($err, true));
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Fatal server error']);
        exit;
    }
});

require_once '../config/db.php';
require_once '../middleware/auth_required.php';
require_once __DIR__ . '/../../helpers/cloudinary.php'; // expects cloudinary_upload() & cloudinary_log()
require_once '../middleware/activity_logger.php';

function restructure_files_array(array $file_post) {
    $files = [];
    $file_count = count($file_post['name']);
    $file_keys = array_keys($file_post);
    for ($i = 0; $i < $file_count; $i++) {
        foreach ($file_keys as $key) {
            $files[$i][$key] = $file_post[$key][$i];
        }
    }
    return $files;
}

try {
    // Basic auth/session check (auth_required.php should set $current_user_id or use session)
    if (!isset($_SESSION['user']['id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }
    $user_id = $_SESSION['user']['id'];

    if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'product_id required']);
        exit;
    }
    $product_id = (int)$_POST['product_id'];

    // fetch product and verify ownership
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND user_id = ?");
    $stmt->execute([$product_id, $user_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found or not owned by user']);
        exit;
    }

    // Prepare updated fields (only simple example; adapt to your form fields)
    $name = isset($_POST['name']) ? trim($_POST['name']) : $product['name'];
    $price = isset($_POST['price']) ? $_POST['price'] : $product['price'];
    $description = isset($_POST['description']) ? trim($_POST['description']) : $product['description'];
    $category = isset($_POST['category']) ? trim($_POST['category']) : $product['category'];
    $location = isset($_POST['location']) ? trim($_POST['location']) : $product['location'];
    $status = isset($_POST['status']) ? $_POST['status'] : $product['status'];

    // load existing images and public ids (expect JSON in DB or fallbacks)
    $existing_images = json_decode($product['images'] ?? '[]', true) ?: [];
    $existing_public_ids = json_decode($product['images_public_ids'] ?? '[]', true) ?: [];

    // Handle deletions: expect delete_images as JSON array of public_ids
    $delete_public_ids = [];
    if (!empty($_POST['delete_images'])) {
        $delete_public_ids = json_decode($_POST['delete_images'], true) ?: [];
    }

    $folder = getenv('CLOUDINARY_UPLOAD_FOLDER') ?: 'nsikacart_products';
    $cloudinary = null; // lazy init for deletes

    // Start transaction
    $pdo->beginTransaction();

    // Delete requested images from Cloudinary and remove from arrays
    if (!empty($delete_public_ids)) {
        try {
            $cloudinary = getCloudinaryInstance();
        } catch (Exception $e) {
            // Log but continue; failing to init Cloudinary is critical only if we need to upload/delete
            cloudinary_log('Cloudinary init failed during update delete: ' . $e->getMessage());
            throw $e;
        }

        foreach ($delete_public_ids as $pubId) {
            if (empty($pubId)) continue;
            try {
                $cloudinary->uploadApi()->destroy($pubId, ['resource_type' => 'image']);
            } catch (Exception $e) {
                // log and continue - allow DB update to proceed (avoid half-failures)
                cloudinary_log("Failed to destroy public_id {$pubId}: " . $e->getMessage());
            }
            // remove from existing arrays
            $idx = array_search($pubId, $existing_public_ids);
            if ($idx !== false) {
                unset($existing_public_ids[$idx]);
                unset($existing_images[$idx]);
            }
        }
        // reindex
        $existing_images = array_values($existing_images);
        $existing_public_ids = array_values($existing_public_ids);
    }

    // Handle new extra images (multi file input named 'images')
    $new_images = [];
    $new_public_ids = [];
    $allowed_types = ['image/jpeg','image/png','image/webp','image/gif'];
    if (isset($_FILES['images'])) {
        $files = restructure_files_array($_FILES['images']);
        foreach ($files as $f) {
            if ($f['error'] !== UPLOAD_ERR_OK) continue;
            if (!in_array($f['type'], $allowed_types)) continue;
            // upload
            try {
                $resp = cloudinary_upload($f['tmp_name'], ['folder' => $folder]);
                $url = $resp['secure_url'] ?? $resp['url'] ?? null;
                $pub = $resp['public_id'] ?? null;
                if ($url) {
                    $new_images[] = $url;
                    $new_public_ids[] = $pub;
                }
            } catch (Exception $e) {
                cloudinary_log("Failed to upload extra image in update: " . $e->getMessage());
                // continue with remaining files
            }
        }
    }

    // Handle new main image (optional file input 'main_image')
    $new_main_image = null;
    $new_main_public_id = null;
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
        $main = $_FILES['main_image'];
        if (in_array($main['type'], $allowed_types)) {
            try {
                $resp = cloudinary_upload($main['tmp_name'], ['folder' => $folder]);
                $new_main_image = $resp['secure_url'] ?? $resp['url'] ?? null;
                $new_main_public_id = $resp['public_id'] ?? null;
                // Optionally delete old main public id if stored
                if (!empty($product['main_image_public_id'])) {
                    try {
                        if (!$cloudinary) $cloudinary = getCloudinaryInstance();
                        $cloudinary->uploadApi()->destroy($product['main_image_public_id'], ['resource_type' => 'image']);
                    } catch (Exception $e) {
                        cloudinary_log('Failed to delete old main image during update: ' . $e->getMessage());
                    }
                }
            } catch (Exception $e) {
                cloudinary_log('Failed to upload new main image: ' . $e->getMessage());
                throw $e;
            }
        } else {
            throw new Exception('Main image must be a valid image type');
        }
    }

    // Merge image lists
    $final_images = array_values(array_merge($existing_images, $new_images));
    $final_public_ids = array_values(array_merge($existing_public_ids, $new_public_ids));
    $final_main_image = $new_main_image ?? $product['main_image'];
    $final_main_public_id = $new_main_public_id ?? ($product['main_image_public_id'] ?? null);

    // Validate numeric price
    if (!is_numeric($price) || $price <= 0) {
        throw new Exception('Invalid price');
    }

    // Update DB - adapt columns to your schema
    $updateStmt = $pdo->prepare("
        UPDATE products SET
            name = :name,
            price = :price,
            description = :description,
            category = :category,
            location = :location,
            status = :status,
            main_image = :main_image,
            main_image_public_id = :main_image_public_id,
            images = :images,
            images_public_ids = :images_public_ids,
            updated_at = NOW()
        WHERE id = :id AND user_id = :user_id
    ");

    $images_json = json_encode($final_images);
    $public_ids_json = json_encode($final_public_ids);

    $ok = $updateStmt->execute([
        ':name' => $name,
        ':price' => $price,
        ':description' => $description,
        ':category' => $category,
        ':location' => $location,
        ':status' => $status,
        ':main_image' => $final_main_image,
        ':main_image_public_id' => $final_main_public_id,
        ':images' => $images_json,
        ':images_public_ids' => $public_ids_json,
        ':id' => $product_id,
        ':user_id' => $user_id
    ]);

    if (!$ok) {
        $pdo->rollBack();
        throw new Exception('Failed to update product in database');
    }

    $pdo->commit();

    ActivityLogger::logActivity('product_update', ['product_id'=>$product_id, 'user_id'=>$user_id]);

    echo json_encode(['success' => true, 'message' => 'Product updated', 'product_id' => $product_id]);
    exit;

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    ActivityLogger::logAudit('PRODUCT_UPDATE_FAILED', $e->getMessage(), 'ERROR');
    cloudinary_log('Update error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Update failed', 'error' => $e->getMessage()]);
    exit;
}
?>