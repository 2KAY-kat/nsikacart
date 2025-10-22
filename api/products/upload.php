<?php
session_start();

// send JSON header early
header('Content-Type: application/json; charset=utf-8');

// error handling: do not leak HTML to client, log instead
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/php_errors.log');

set_exception_handler(function($e){
    error_log("Uncaught exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: '.$e->getMessage()]);
    exit;
});

register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log("Shutdown fatal error: " . print_r($err, true));
        http_response_code(500);
        // do not include internal details in production
        echo json_encode(['success' => false, 'message' => 'Fatal server error']);
    }
});

require_once '../config/db.php';
require_once '../middleware/auth_required.php';
require_once '../middleware/activity_logger.php';
require_once __DIR__ . '/../../helpers/cloudinary.php';

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

    // process main image
    $main_image = $_FILES['main_image'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($main_image['type'], $allowed_types)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Main image must be a valid image (jpg, png, webp, gif).']);
        exit;
    }

    // upload main image to Cloudinary
    try {
        $folder = getenv('CLOUDINARY_UPLOAD_FOLDER') ?: 'nsikacart_products';
        $main_upload = cloudinary_upload($main_image['tmp_name'], ['folder' => $folder]);
        $main_image_url = $main_upload['secure_url'] ?? $main_upload['url'] ?? null;
        $main_public_id = $main_upload['public_id'] ?? null;
        if (!$main_image_url) throw new Exception('No secure_url returned from Cloudinary for main image.');
    } catch (Exception $e) {
        cloudinary_log('Main image upload failed: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to upload main image.']);
        exit;
    }

    // process additional images (optional)
    $other_images_urls = [];
    $other_public_ids = [];
    if (isset($_FILES['images'])) {
        $files = restructure_files_array($_FILES['images']);
        foreach ($files as $f) {
            if ($f['error'] !== UPLOAD_ERR_OK) continue;
            if (!in_array($f['type'], $allowed_types)) continue;
            try {
                $other_upload = cloudinary_upload($f['tmp_name'], ['folder' => $folder]);
                if (!empty($other_upload['secure_url'])) {
                    $other_images_urls[] = $other_upload['secure_url'];
                    $other_public_ids[] = $other_upload['public_id'] ?? null;
                }
            } catch (Exception $e) {
                cloudinary_log('Other image upload failed: ' . $e->getMessage());
                // continue uploading remaining images; don't fail entire request for one image
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
            location, status, main_image, main_image_public_id,
            images, images_public_ids, user_id
        ) VALUES (
            ?, ?, ?, ?, 
            ?, ?, ?, ?,
            ?, ?, ?
        )
    ");

    // Prepare the public_ids JSON
    $other_public_ids_json = json_encode($other_public_ids);

    if ($stmt->execute([
        trim($_POST['name']),
        trim($_POST['description']),
        (float)$_POST['price'],
        trim($_POST['category']),
        trim($_POST['location']),
        $_POST['status'],
        $main_image_url,
        $main_public_id,
        $images_json,
        $other_public_ids_json,
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

// helper used above - convert $_FILES multi to easier array
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


// CREATE TABLE products (
//     id INT AUTO_INCREMENT  PRIMARY KEY,
//     name VARCHAR(255) NOT NULL,
//     price DECIMAL(10,2) NOT NULL,
//     description TEXT NOT NULL,

//     status ENUM('active', 'disabled') DEFAULT 'active',
//     main_image VARCHAR(255) NOT NULL,
//     images TEXT,

//     category VARCHAR(100) NOT NULL,
//     location VARCHAR(255) NOT NULL,

//     user_id INT NOT NULL,
//     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
//     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
//     FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
// );


// -- Verify your products table has these columns
// ALTER TABLE products
// ADD COLUMN main_image_public_id VARCHAR(255) NULL AFTER main_image,
// ADD COLUMN images_public_ids TEXT NULL AFTER images,
// ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;