<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../config/db.php';
header('Content-Type: application/json');

try {
    // Check if user is authenticated and has admin privileges
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required'
        ]);
        exit;
    }

    // Check if user has admin role (only admin can change roles)
    $userRole = $_SESSION['user']['role'] ?? '';
    if ($userRole !== 'admin') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Access denied. Only administrators can change user roles.'
        ]);
        exit;
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['user_id'] ?? null;
    $newRole = $input['new_role'] ?? null;
    $currentRole = $input['current_role'] ?? null;

    if (!$userId || !$newRole) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'User ID and new role are required'
        ]);
        exit;
    }

    // Validate new role
    $allowedRoles = ['user', 'monitor', 'admin'];
    if (!in_array($newRole, $allowedRoles)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid role specified'
        ]);
        exit;
    }

    // Prevent admin from changing their own role
    if ($userId == $_SESSION['user']['id']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'You cannot change your own role'
        ]);
        exit;
    }

    // Check if user exists and get their current role
    $stmt = $pdo->prepare("SELECT role, name, email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit;
    }

    // Check if there's actually a change
    if ($user['role'] === $newRole) {
        echo json_encode([
            'success' => true,
            'message' => 'No changes needed - user already has this role'
        ]);
        exit;
    }

    // Update user role
    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$newRole, $userId]);

    if ($stmt->rowCount() > 0) {
        // Log the role change for audit purposes
        error_log("Admin {$_SESSION['user']['name']} (ID: {$_SESSION['user']['id']}) changed user {$user['name']} (ID: $userId) role from {$user['role']} to $newRole");
        
        echo json_encode([
            'success' => true,
            'message' => 'User role updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update user role. Please try again'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}