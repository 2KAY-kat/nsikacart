<?php
// Enhanced debug version of delete endpoint
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Content-Type: application/json');

$debug_info = [];

try {
    $debug_info['step'] = 'Starting';
    $debug_info['session_check'] = isset($_SESSION['user']['id']) ? 'PASS' : 'FAIL';
    $debug_info['user_id'] = $_SESSION['user']['id'] ?? 'NOT_SET';
    
    // Check if user is logged in
    if (!isset($_SESSION['user']['id']) || empty($_SESSION['user']['id'])) {
        $debug_info['error'] = 'Authentication failed';
        echo json_encode($debug_info);
        exit;
    }

    $debug_info['step'] = 'Auth passed';
    
    // Try to include database config
    try {
        require_once '../config/db.php';
        $debug_info['database_include'] = 'SUCCESS';
    } catch (Exception $e) {
        $debug_info['database_include'] = 'FAILED: ' . $e->getMessage();
        echo json_encode($debug_info);
        exit;
    }

    $debug_info['step'] = 'Database included';
    $debug_info['pdo_exists'] = isset($pdo) ? 'YES' : 'NO';

    // Get input
    $input_raw = file_get_contents('php://input');
    $debug_info['input_raw'] = $input_raw;
    
    if (empty($input_raw)) {
        $debug_info['error'] = 'No input data';
        echo json_encode($debug_info);
        exit;
    }

    $input = json_decode($input_raw, true);
    $debug_info['input_decoded'] = $input;
    $debug_info['json_error'] = json_last_error_msg();
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        $debug_info['error'] = 'JSON decode failed';
        echo json_encode($debug_info);
        exit;
    }
    
    if (!isset($input['product_id'])) {
        $debug_info['error'] = 'Product ID missing';
        echo json_encode($debug_info);
        exit;
    }
    
    $product_id = (int)$input['product_id'];
    $user_id = (int)$_SESSION['user']['id'];
    
    $debug_info['product_id'] = $product_id;
    $debug_info['user_id_from_session'] = $user_id;
    $debug_info['step'] = 'Ready to query database';

    // Test database query
    try {
        $check_stmt = $pdo->prepare("SELECT id, main_image, images, user_id FROM products WHERE id = ? AND user_id = ?");
        $check_stmt->execute([$product_id, $user_id]);
        $product = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        $debug_info['product_found'] = $product ? 'YES' : 'NO';
        $debug_info['product_data'] = $product;
        
    } catch (Exception $e) {
        $debug_info['database_query_error'] = $e->getMessage();
        echo json_encode($debug_info);
        exit;
    }

    $debug_info['step'] = 'Query successful';
    $debug_info['status'] = 'All checks passed - ready for actual deletion';
    
    echo json_encode($debug_info);

} catch (Error $e) {
    $debug_info['fatal_error'] = $e->getMessage();
    $debug_info['fatal_error_file'] = $e->getFile();
    $debug_info['fatal_error_line'] = $e->getLine();
    echo json_encode($debug_info);
} catch (Exception $e) {
    $debug_info['exception'] = $e->getMessage();
    echo json_encode($debug_info);
}
?>