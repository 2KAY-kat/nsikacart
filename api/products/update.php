<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
require_once '../config/db.php';
require_once '../middleware/auth_required.php';

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
    
    $upload_dir = __DIR__ . '/../../public/dashboard/uploads/';
    
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
    
    if (isset($_POST['delete_images'])) {
        $images_to_delete = json_decode($_POST['delete_images'], true);
        if (is_array($images_to_delete)) {
            foreach ($images_to_delete as $image_to_delete) {
                // Remove from filesystem
                $image_path = $upload_dir . basename($image_to_delete);
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
                
                // Remove from existing images array
                $existing_images = array_filter($existing_images, function($img) use ($image_to_delete) {
                    return $img !== $image_to_delete;
                });
                
                // Check if main image was deleted
                if ($current_main_image === $image_to_delete) {
                    $current_main_image = null;
                }
            }
            $has_changes = true;
        }
    }
    
    // Handle new image uploads
    $new_images = [];
    $new_main_image = $current_main_image;
    
    if (isset($_FILES['images']) && is_array($_FILES['images']['tmp_name'])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                // Validate image type
                $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                if (!in_array($_FILES['images']['type'][$key], $allowed_types)) {
                    throw new Exception("Invalid file type. Only JPG, PNG, WEBP and GIF allowed");
                }
                
                $image_name = uniqid() . '_' . basename($_FILES['images']['name'][$key]);
                $image_path = $upload_dir . $image_name;
                
                if (move_uploaded_file($tmp_name, $image_path)) {
                    $new_images[] = $image_name;
                    
                    // First uploaded image becomes main image if no main image exists
                    if (!$new_main_image && count($new_images) === 1) {
                        $new_main_image = $image_name;
                    }
                    
                    $has_changes = true;
                }
            }
        }
    }
    
    // Combine existing and new images
    $all_images = array_merge($existing_images, $new_images);
    
    // Limit to 10 images total
    if (count($all_images) > 10) {
        $all_images = array_slice($all_images, 0, 10);
    }
    
    // Update main image if it was deleted and we have images
    if (!$new_main_image && !empty($all_images)) {
        $new_main_image = $all_images[0];
        $has_changes = true;
    }
    
    // Update database with new image data
    if ($has_changes && (!empty($all_images) || !empty($new_main_image))) {
        if ($new_main_image !== $existing_product['main_image']) {
            $update_fields[] = "main_image = ?";
            $update_values[] = $new_main_image;
        }
        
        if (json_encode($all_images) !== $existing_product['images']) {
            $update_fields[] = "images = ?";
            $update_values[] = json_encode($all_images);
        }
    }
    
    // Only proceed if there are fields to update
    if (empty($update_fields)) {
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
        'message' => 'Product updated successfully'
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