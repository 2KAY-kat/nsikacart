<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../config/db.php';
header('Content-Type: application/json');

try {
    // Check if user is authenticated and has admin or monitor privileges
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required'
        ]);
        exit;
    }

    // Check if user has admin or monitor role
    $userRole = $_SESSION['user']['role'] ?? '';
    if ($userRole !== 'admin' && $userRole !== 'monitor') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Access denied. Admin or Monitor privileges required.'
        ]);
        exit;
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['user_id'] ?? null;
    $currentStatus = $input['current_status'] ?? null;

    if (!$userId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'User ID is required'
        ]);
        exit;
    }

    // Prevent user from changing their own status
    if ($userId == $_SESSION['user']['id']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'You cannot change your own status'
        ]);
        exit;
    }

    // Get user details for logging
    $stmt = $pdo->prepare("SELECT name, email, role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$targetUser) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit;
    }

    // Additional check: Monitors cannot change status of admins
    if ($userRole === 'monitor' && $targetUser['role'] === 'admin') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Monitors cannot change the status of administrators'
        ]);
        exit;
    }

    // Toggle status
    $newStatus = ($currentStatus === 'active') ? 'suspended' : 'active';

    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $userId]);

    if ($stmt->rowCount() > 0) {
        // Log the action for audit purposes
        $currentUserName = $_SESSION['user']['name'] ?? 'Unknown';
        $actionType = $newStatus === 'suspended' ? 'suspended' : 'activated';
        error_log("{$userRole} {$currentUserName} (ID: {$_SESSION['user']['id']}) {$actionType} user {$targetUser['name']} (ID: $userId)");
        
        echo json_encode([
            'success' => true,
            'message' => "User {$actionType} successfully"
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'User not found or no changes made'
        ]);
    }

} catch (Exception $e) {
    error_log("Toggle User Status Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
}
?>