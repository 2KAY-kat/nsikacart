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

    // Check if user has admin role (only admin can delete users)
    $userRole = $_SESSION['user']['role'] ?? '';
    if ($userRole !== 'admin') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Access denied. Only administrators can delete users.'
        ]);
        exit;
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['user_id'] ?? null;

    if (!$userId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'User ID is required'
        ]);
        exit;
    }

    // Prevent admin from deleting themselves
    if ($userId == $_SESSION['user']['id']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'You cannot delete your own account'
        ]);
        exit;
    }

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Get user details for logging before deletion
        $stmt = $pdo->prepare("SELECT name, email, role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userToDelete = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$userToDelete) {
            $pdo->rollback();
            echo json_encode([
                'success' => false,
                'message' => 'User not found'
            ]);
            exit;
        }

        // Delete user's products first (if you have foreign key constraints)
        $stmt = $pdo->prepare("DELETE FROM products WHERE user_id = ?");
        $stmt->execute([$userId]);

        // Delete the user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);

        if ($stmt->rowCount() > 0) {
            $pdo->commit();
            
            // Log the deletion for audit purposes
            $currentUserName = $_SESSION['user']['name'] ?? 'Unknown';
            error_log("Admin {$currentUserName} (ID: {$_SESSION['user']['id']}) deleted user {$userToDelete['name']} (ID: $userId, Role: {$userToDelete['role']})");
            
            echo json_encode([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } else {
            $pdo->rollback();
            echo json_encode([
                'success' => false,
                'message' => 'Failed to delete user'
            ]);
        }
    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Delete User Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
}
?>