<?php
session_start();

header("Content-Type: application/json");

require_once 'activity_logger.php';

/**
 * authentication middleware to check if a user is logged in and some session storage abd authorisation shitt
 */

// ensure session is started (only if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// check if user is authenticated 
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    // logs every loggin activity for unauthorised access into the site
    ActivityLogger::logAudit('UNAUTHORIZED_ACCESS', 'Attempt to access protected resource without authentication', 'WARNING');
    
    header('Content-Type: application/json');
    http_response_code(401); // Unauthorized
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

// every successful access to protected resource is also logged for security reasons amd site improvement on commn pitfalls
ActivityLogger::logActivity('page_access', [
    'endpoint' => $_SERVER['REQUEST_URI'] ?? 'unknown',
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
]);

// set common variables for convenience in other scripts
$current_user_id = $_SESSION['user']['id'];
$current_user_name = $_SESSION['user']['name'];
$current_user_role = $_SESSION['user']['role'];

// continue to the main script if authenticated