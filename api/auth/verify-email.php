<?php
// Clean any previous output
if (ob_get_level()) {
    ob_end_clean();
}

require_once '../config/db.php';

header("Content-Type: application/json");
header("Cache-Control: no-cache, must-revalidate");

try {
    // Only process POST requests
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Only POST requests allowed");
    }

    // Get JSON input
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON data");
    }

    // Validate required fields
    if (empty($data['token'])) {
        throw new Exception("Verification token is required");
    }

    $token = trim($data['token']);

    // Find user with this verification token
    $stmt = $pdo->prepare("SELECT id, name, email, email_verified, verification_expires_at FROM users WHERE verification_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid verification token. Please check your email or request a new verification link."
        ]);
        exit;
    }

    // Check if email is already verified
    if ($user['email_verified']) {
        echo json_encode([
            "success" => false,
            "message" => "Email address is already verified. You can log in to your account."
        ]);
        exit;
    }

    // Check if token has expired
    if (strtotime($user['verification_expires_at']) < time()) {
        echo json_encode([
            "success" => false,
            "message" => "Verification link has expired (15 minutes). Please request a new verification email."
        ]);
        exit;
    }

    // Verify the email
    $updateStmt = $pdo->prepare("UPDATE users SET email_verified = TRUE, verification_token = NULL, verification_expires_at = NULL WHERE id = ?");
    $success = $updateStmt->execute([$user['id']]);

    if ($success) {
        echo json_encode([
            "success" => true,
            "message" => "Email verified successfully! You can now log in to your account."
        ]);
    } else {
        throw new Exception("Failed to verify email");
    }

} catch (Exception $e) {
    error_log("Email verification error: " . $e->getMessage());
    
    echo json_encode([
        "success" => false,
        "message" => "Email verification failed. Please try again later."
    ]);
}
?>