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
    
    // Prepare update fields - only update fields that are provided
    $update_fields = [];
    $update_values = [];
    
    // Only proceed if there are actual changes or files to upload
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
    
    // Handle main image update
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
        $main_image = $_FILES['main_image'];
        $main_image_name = uniqid() . '_' . basename($main_image['name']);
        $main_image_path = $upload_dir . $main_image_name;
        
        // Validate image type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($main_image['type'], $allowed_types)) {
            throw new Exception("Invalid file type. Only JPG, PNG and GIF allowed");
        }
        
        if (move_uploaded_file($main_image['tmp_name'], $main_image_path)) {
            $update_fields[] = "main_image = ?";
            $update_values[] = $main_image_name;
            
            // Delete old main image
            if ($existing_product['main_image'] && file_exists($upload_dir . $existing_product['main_image'])) {
                unlink($upload_dir . $existing_product['main_image']);
            }
            
            $has_changes = true;
        }
    }
    
    // Handle additional images update
    if (isset($_FILES['images']) && is_array($_FILES['images']['tmp_name'])) {
        $other_images = [];
        $existing_images = json_decode($existing_product['images'], true) ?: [];
        
        // Keep existing images that aren't being replaced
        $other_images = $existing_images;
        
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $image_name = uniqid() . '_' . basename($_FILES['images']['name'][$key]);
                $image_path = $upload_dir . $image_name;
                
                if (move_uploaded_file($tmp_name, $image_path)) {
                    $other_images[] = $image_name;
                    $has_changes = true;
                }
            }
        }
        
        if (!empty($other_images)) {
            $update_fields[] = "images = ?";
            $update_values[] = json_encode($other_images);
        }
    }
    
    // Only proceed if there are fields to update
    if (empty($update_fields)) {
        throw new Exception('No fields to update');
    }
    
    // Add updated_at timestamp
    $update_fields[] = "updated_at = NOW()";
    
    // Build and execute update query
    $update_values[] = $product_id;
    $sql = "UPDATE products SET " . implode(', ', $update_fields) . " WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($update_values);
    
    if (!$result) {
        throw new Exception('Failed to update product');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Product updated successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>