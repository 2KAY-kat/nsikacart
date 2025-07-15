<?php
session_start();

header("Content-Type: application/json");

/**
 * authentication middleware to check if a user is logged in
 */

// ensure session is started (only if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// check if user is authenticated 
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header('Content-Type: application/json');
    http_response_code(401); // Unauthorized
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

// set common variables for convenience in other scripts
$current_user_id = $_SESSION['user']['id'];
$current_user_name = $_SESSION['user']['name'];
$current_user_role = $_SESSION['user']['role'];

// continue to the main script if authenticated