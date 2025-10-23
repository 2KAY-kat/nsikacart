<?php
/**
 * API Endpoint: Manual User Verification
 * Purpose: Allow admins/monitors to manually verify user email addresses
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../config/db.php';

// Set JSON header
header('Content-Type: application/json');

try {
    // Authentication check - user must be logged in
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required'
        ]);
        exit;
    }

    // Authorization check - only admins and monitors can verify users
    $currentUserRole = $_SESSION['user']['role'] ?? '';
    if ($currentUserRole !== 'admin' && $currentUserRole !== 'monitor') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Access denied. Admin or Monitor privileges required.'
        ]);
        exit;
    }

    // Only accept POST requests
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        exit;
    }

    // Get and validate JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['user_id'] ?? null;

    // Validate required fields
    if (!$userId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'User ID is required'
        ]);
        exit;
    }

    // Prevent admin from verifying themselves (optional business rule)
    if ($userId == $_SESSION['user']['id']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'You cannot manually verify your own account'
        ]);
        exit;
    }

    // Get user details from database
    $stmt = $pdo->prepare("SELECT id, name, email, email_verified FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if user exists
    if (!$targetUser) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit;
    }

    // Check if already verified
    if ($targetUser['email_verified']) {
        echo json_encode([
            'success' => false,
            'message' => 'This user is already verified'
        ]);
        exit;
    }

    // Update user: set email_verified = TRUE, clear verification token, set verified_by and verified_at
    $updateStmt = $pdo->prepare("
        UPDATE users 
        SET email_verified = TRUE, 
            verification_token = NULL, 
            verification_expires_at = NULL,
            admin_verified_by = ?,
            admin_verified_at = NOW()
        WHERE id = ?
    ");
    
    $success = $updateStmt->execute([$_SESSION['user']['id'], $userId]);

    if ($success && $updateStmt->rowCount() > 0) {
        // Log the action for audit trail
        $adminName = $_SESSION['user']['name'] ?? 'Unknown Admin';
        error_log("ADMIN ACTION: {$adminName} (ID: {$_SESSION['user']['id']}) manually verified user {$targetUser['name']} (ID: {$userId})");

        echo json_encode([
            'success' => true,
            'message' => "User '{$targetUser['name']}' has been successfully verified"
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to verify user. Please try again.'
        ]);
    }

} catch (PDOException $e) {
    error_log("Database Error in manual-verify-user.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    error_log("General Error in manual-verify-user.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
}
?>