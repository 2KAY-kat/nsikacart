<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
require_once '../config/db.php';
require_once '../middleware/auth_required.php';
require_once __DIR__ . '/../../helpers/cloudinary.php'; // added for Cloudinary

header('Content-Type: application/json');

try {
    if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
        throw new Exception('Invalid product ID');
    }
    
    $product_id = (int)$_POST['product_id'];
    $user_id = $current_user_id;
    
    // First, verify the product belongs to the current user
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND user_id = ?");
    $stmt->execute([$product_id, $user_id]);
    $existing_product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existing_product) {
        throw new Exception('Product not found or access denied');
    }
    
    $upload_dir = __DIR__ . '/../../public/dashboard/uploads/'; // local backup path, optional

    // Prepare update fields
    $update_fields = [];
    $update_values = [];
    $has_changes = false;
    
    // Check each field for changes before adding to update
    if (isset($_POST['name']) && !empty($_POST['name']) && $_POST['name'] !== $existing_product['name']) {
        $update_fields[] = "name = ?";
        $update_values[] = trim($_POST['name']);
        $has_changes = true;
    }
    
    if (isset($_POST['description']) && !empty($_POST['description']) && $_POST['description'] !== $existing_product['description']) {
        $update_fields[] = "description = ?";
        $update_values[] = trim($_POST['description']);
        $has_changes = true;
    }
    
    if (isset($_POST['price']) && is_numeric($_POST['price']) && (float)$_POST['price'] !== (float)$existing_product['price']) {
        $update_fields[] = "price = ?";
        $update_values[] = (float)$_POST['price'];
        $has_changes = true;
    }
    
    if (isset($_POST['category']) && !empty($_POST['category']) && $_POST['category'] !== $existing_product['category']) {
        $update_fields[] = "category = ?";
        $update_values[] = trim($_POST['category']);
        $has_changes = true;
    }
    
    if (isset($_POST['location']) && !empty($_POST['location']) && $_POST['location'] !== $existing_product['location']) {
        $update_fields[] = "location = ?";
        $update_values[] = trim($_POST['location']);
        $has_changes = true;
    }
    
    if (isset($_POST['status']) && in_array($_POST['status'], ['active', 'disabled']) && $_POST['status'] !== $existing_product['status']) {
        $update_fields[] = "status = ?";
        $update_values[] = $_POST['status'];
        $has_changes = true;
    }
    
    // Handle image deletions first
    $existing_images = json_decode($existing_product['images'], true) ?: [];
    $current_main_image = $existing_product['main_image'];
    $existing_public_ids = json_decode($existing_product['images_public_ids'] ?? '[]', true) ?: [];

    // handle image deletions (if client sends delete_images as array of public_ids)
    $deleted_public_ids = $_POST['delete_images'] ? json_decode($_POST['delete_images'], true) : [];
    $folder = getenv('CLOUDINARY_UPLOAD_FOLDER') ?: 'nsikacart_products';
    if (!empty($deleted_public_ids)) {
        foreach ($deleted_public_ids as $pubId) {
            try {
                $cloudinary = getCloudinaryInstance();
                $cloudinary->uploadApi()->destroy($pubId, ['resource_type' => 'image']);
            } catch (Exception $e) {
                cloudinary_log('Failed to delete image ' . $pubId . ': ' . $e->getMessage());
                // do not abort; log and continue
            }
        }
        // remove deleted entries from $existing_images and $existing_public_ids
        foreach ($deleted_public_ids as $pubId) {
            $idx = array_search($pubId, $existing_public_ids);
            if ($idx !== false) {
                unset($existing_public_ids[$idx]);
                unset($existing_images[$idx]);
            }
        }
        // reindex arrays
        $existing_images = array_values($existing_images);
        $existing_public_ids = array_values($existing_public_ids);
        $has_changes = true;
    }

    // handle new uploads in $_FILES['images'] (multi) and optional new main image
    $new_public_ids = [];
    $new_images = [];
    if (isset($_FILES['images'])) {
        $files = restructure_files_array($_FILES['images']);
        foreach ($files as $f) {
            if ($f['error'] !== UPLOAD_ERR_OK) continue;
            try {
                $uploadResp = cloudinary_upload($f['tmp_name'], ['folder' => $folder]);
                if (!empty($uploadResp['secure_url'])) {
                    $new_images[] = $uploadResp['secure_url'];
                    $new_public_ids[] = $uploadResp['public_id'] ?? null;
                }
            } catch (Exception $e) {
                cloudinary_log('Update: failed to upload extra image: ' . $e->getMessage());
            }
        }
    }

    // handle new main image (if provided)
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
        try {
            $main_resp = cloudinary_upload($_FILES['main_image']['tmp_name'], ['folder' => $folder]);
            if (!empty($main_resp['secure_url'])) {
                $new_main_image = $main_resp['secure_url'];
                $new_main_public_id = $main_resp['public_id'] ?? null;
                // optionally delete old main image public_id if you have it
                if (!empty($existing_product['main_image_public_id'])) {
                    try {
                        $cloudinary = getCloudinaryInstance();
                        $cloudinary->uploadApi()->destroy($existing_product['main_image_public_id'], ['resource_type' => 'image']);
                    } catch (Exception $e) {
                        cloudinary_log('Failed to delete old main image: ' . $e->getMessage());
                    }
                }
                $has_changes = true;
            }
        } catch (Exception $e) {
            cloudinary_log('Main image update failed: ' . $e->getMessage());
            // respond with error or continue depending on desired behavior
        }
    }

    // merge existing + new images/public_ids and update DB inside transaction
    $final_images = array_values(array_merge($existing_images, $new_images));
    $final_public_ids = array_values(array_merge($existing_public_ids, $new_public_ids));

    // Only proceed if there are fields to update
    if (empty($update_fields) && empty($final_images) && empty($final_public_ids)) {
        echo json_encode([
            'success' => true,
            'message' => 'No changes detected'
        ]);
        exit;
    }

    // Add updated_at timestamp
    $update_fields[] = "updated_at = NOW()";

    // Build and execute update query
    $update_values[] = $product_id;
    $sql = "UPDATE products SET " . implode(', ', $update_fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($update_values);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Product updated successfully',
            'main_image_url' => $new_main_image,
            'images' => $all_images
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update product'
        ]);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>