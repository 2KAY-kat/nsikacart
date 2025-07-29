<?php
if (ob_get_level()) {
    ob_end_clean();
}

require_once '../../helpers/env.php';
loadEnv('../../.env');
require_once '../config/db.php';

session_start();
header("Content-Type: application/json");
header("Cache-Control: no-cache, must-revalidate");

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Only POST requests allowed");
    }

    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON data");
    }

    if (empty($data['token']) || empty($data['password'])) {
        throw new Exception("Token and password are required");
    }

    $token = trim($data['token']);
    $password = trim($data['password']);

    if (strlen($password) < 6) {
        throw new Exception("Password must be at least 6 characters long");
    }

    // Verify token and check expiry
    $stmt = $pdo->prepare("SELECT id, email FROM users WHERE reset_token = ? AND reset_expires_at > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception("Invalid or expired reset token");
    }

    // Update password and clear reset token
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $updateStmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires_at = NULL WHERE id = ?");
    $success = $updateStmt->execute([$hashedPassword, $user['id']]);

    if (!$success) {
        throw new Exception("Failed to update password");
    }

    echo json_encode([
        "success" => true,
        "message" => "Password reset successfully!"
    ]);

} catch (Exception $e) {
    error_log("Reset password error: " . $e->getMessage());
    
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>