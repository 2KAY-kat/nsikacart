<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1); 
require_once '../config/db.php';
require_once '../middleware/auth_required.php';
require_once '../middleware/activity_logger.php';
require_once __DIR__ . '/../../helpers/cloudinary.php';

header('Content-Type: application/json');

// increase upload limits if needed
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '10M');

// create log function aaaaaaaaaaaaaaaaaaaahh
function logError($message) {
    error_log(date('Y-m-d H:i:s') . ": " . $message . "\n", 3, "../../logs/upload_errors.log");
}

function logDebug($data) {
    error_log(date('Y-m-d H:i:s') . ": " . print_r($data, true) . "\n", 3, "../../logs/debug.log");
}

try {
    // starts writting into the activity log file for the current uploading activity
    ActivityLogger::logActivity('product_upload_start', [
        'product_name' => $name ?? 'unknown',
        'category' => $category ?? 'unknown',
        'file_count' => count($_FILES['images']['name'] ?? [])
    ]);

    // check if request is multipart/form-data
    if (!isset($_SERVER['CONTENT_TYPE']) || strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') === false) {
        throw new Exception('Invalid content type. Must be multipart/form-data');
    }

    // check if the user is authenticated
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'User not authenticated'
        ]);
        exit;
    }

    $user_id = $_SESSION['user']['id'];

    // validate required fields
    $required_fields = ['name', 'price', 'description', 'category', 'location', 'status'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Missing required field: {$field}");
        }
    }

    // set validated field lengths
    $max_lengths = [
        'name' => 255,
        'category' => 100,
        'location' => 255
    ];

    foreach ($max_lengths as $field => $max_length) {
        if (strlen($_POST[$field]) > $max_length) {
            throw new Exception("$field cannot be longer than $max_length characters");
        }
    }

    // validate file upload
    if (!isset($_FILES['main_image'])) {
        throw new Exception("No main image uploaded");
    }

    if ($_FILES['main_image']['error'] !== UPLOAD_ERR_OK) {
        $upload_errors = array(
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
        );
        $error_message = isset($upload_errors[$_FILES['main_image']['error']]) 
            ? $upload_errors[$_FILES['main_image']['error']] 
            : 'Unknown upload error';
        throw new Exception($error_message);
    }

    // Cloudinary instance
    $cloudinary = getCloudinaryInstance();

    // process main image
    $main_image = $_FILES['main_image'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($main_image['type'], $allowed_types)) {
        throw new Exception("Invalid file type. Only JPG, PNG, WEBP and GIF allowed");
    }

    // upload main image to Cloudinary
    $main_upload = $cloudinary->uploadApi()->upload($main_image['tmp_name'], [
        'folder' => 'nsikacart_products',
        'use_filename' => true,
        'unique_filename' => true
    ]);
    $main_image_url = $main_upload['secure_url']; // store this in DB instead of local path

    // process additional images
    $other_images_urls = [];
    if (isset($_FILES['images'])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK && is_uploaded_file($tmp_name)) {
                if (!in_array($_FILES['images']['type'][$key], $allowed_types)) {
                    continue; // skip invalid type
                }
                $upload = $cloudinary->uploadApi()->upload($tmp_name, [
                    'folder' => 'nsikacart_products',
                    'use_filename' => true,
                    'unique_filename' => true
                ]);
                $other_images_urls[] = $upload['secure_url'];
            }
        }
    }

    // validate price format
    if (!is_numeric($_POST['price']) || $_POST['price'] <= 0) {
        throw new Exception("Price must be a positive number");
    }

    // validate product status
    $allowed_statuses = ['active', 'disabled'];
    if (!in_array($_POST['status'], $allowed_statuses)) {
        throw new Exception("Invalid status value");
    }

    // prepare image data for database (store Cloudinary URLs)
    $images_json = json_encode(array_merge([$main_image_url], $other_images_urls));

    // insert into database
    $stmt = $pdo->prepare("
        INSERT INTO products (
            name, description, price, category, 
            location, status, main_image, 
            images, user_id
        ) VALUES (
            ?, ?, ?, ?, 
            ?, ?, ?, 
            ?, ?
        )
    ");

    if ($stmt->execute([
        trim($_POST['name']),
        trim($_POST['description']),
        (float)$_POST['price'],
        trim($_POST['category']),
        trim($_POST['location']),
        $_POST['status'],
        $main_image_url,
        $images_json,
        $user_id
    ])) {
        $product_id = $pdo->lastInsertId();

        ActivityLogger::logDatabaseActivity('INSERT', 'products', $product_id, [
            'name' => $_POST['name'] ?? '',
            'category' => $_POST['category'] ?? '',
            'price' => $_POST['price'] ?? '',
            'location' => $_POST['location'] ?? ''
        ]);

        ActivityLogger::logAudit('PRODUCT_CREATED', "Product '{$_POST['name']}' created successfully", 'INFO');

        logDebug([
            'session' => $_SESSION,
            'user_id' => $user_id ?? 'not set',
            'post_data' => $_POST
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Product uploaded successfully',
            'product_id' => $product_id,
            'main_image_url' => $main_image_url,
            'images' => array_merge([$main_image_url], $other_images_urls)
        ]);
    }
} catch (Exception $e) {
    ActivityLogger::logAudit('PRODUCT_UPLOAD_FAILED', $e->getMessage(), 'ERROR');
    logError($e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Upload failed: ' . $e->getMessage()
    ]);
}