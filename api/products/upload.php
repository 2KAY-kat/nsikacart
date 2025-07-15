<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0); 
require_once '../config/db.php';
require_once '../middleware/auth_required.php';

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
    // check if request is multipart/form-data
    if (!isset($_SERVER['CONTENT_TYPE']) || strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') === false) {
        throw new Exception('Invalid content type. Must be multipart/form-data');
    }

    $upload_dir = __DIR__ . '/../../public/dashboard/uploads/';

    // ensure uploads directory exists and is writable
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    if (!is_writable($upload_dir)) {
        logError("Upload directory is not writable: " . $upload_dir);
        throw new Exception("Server configuration error");
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

    // get user_id from session
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
    $main_image_name = uniqid() . '_' . basename($main_image['name']);
    $main_image_path = $upload_dir . $main_image_name;

    // validate image type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($main_image['type'], $allowed_types)) {
        throw new Exception("Invalid file type. Only JPG, PNG and GIF allowed");
    }

    if (!move_uploaded_file($main_image['tmp_name'], $main_image_path)) {
        throw new Exception("Failed to upload main image");
    }

    // process additional images
    $other_images = [];
    if (isset($_FILES['images'])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $image_name = uniqid() . '_' . basename($_FILES['images']['name'][$key]);
                $image_path = $upload_dir . $image_name;
                
                if (move_uploaded_file($tmp_name, $image_path)) {
                    $other_images[] = $image_name;
                }
            }
        }
    }

    // validate price format though we have a function in js that does that i think... its been a while since i worked on it ... last year
    if (!is_numeric($_POST['price']) || $_POST['price'] <= 0) {
        throw new Exception("Price must be a positive number");
    }

    // validate product status
    $allowed_statuses = ['active', 'disabled'];
    if (!in_array($_POST['status'], $allowed_statuses)) {
        throw new Exception("Invalid status value");
    }

    // prepare image data for database
    $images_json = json_encode(array_merge([$main_image_name], $other_images));

    // insert into database with proper type casting
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

    $result = $stmt->execute([
        trim($_POST['name']),
        trim($_POST['description']),
        (float)$_POST['price'],
        trim($_POST['category']),
        trim($_POST['location']),
        $_POST['status'],
        $main_image_name,
        $images_json,
        $user_id
    ]);

    if (!$result) {
        $error = $stmt->errorInfo();
        logError("Database error: " . json_encode($error));
        throw new Exception("Failed to save product to database");
    }

    // get the newly inserted product ID
    $productId = $pdo->lastInsertId();

    // add debug logging
    logDebug([
        'session' => $_SESSION,
        'user_id' => $user_id ?? 'not set',
        'post_data' => $_POST
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Product uploaded successfully',
        'productId' => $productId
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}