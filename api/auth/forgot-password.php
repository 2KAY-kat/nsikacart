<?php
// Clean any previous output
if (ob_get_level()) {
    ob_end_clean();
}

// Include required files FIRST and load environment
require_once '../../helpers/env.php';
loadEnv('../../.env');

require_once '../config/db.php';
require_once '../../helpers/PHPMailer-master/src/Exception.php';
require_once '../../helpers/PHPMailer-master/src/PHPMailer.php';
require_once '../../helpers/PHPMailer-master/src/SMTP.php';

// USE statements must come AFTER the require statements
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Now start the session and set headers
session_start();
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
    if (empty($data['email'])) {
        throw new Exception("Email address is required");
    }

    $email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, email, name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        // Don't reveal if email exists or not for security
        echo json_encode([
            "success" => true,
            "message" => "If an account with that email exists, a password reset link has been sent."
        ]);
        exit;
    }

    // Generate reset token
    $token = bin2hex(random_bytes(32));
    $tokenExpiry = date("Y-m-d H:i:s", strtotime('+1 hour'));

    // Update user with reset token
    $updateStmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires_at = ? WHERE email = ?");
    $success = $updateStmt->execute([$token, $tokenExpiry, $email]);

    if (!$success) {
        throw new Exception("Failed to generate reset token");
    }

    // Create reset link
    $appUrl = env('APP_URL', 'http://localhost/nsikacart');
    $resetLink = $appUrl . "/auth/reset-password.html?token=" . urlencode($token);

    // Send email using PHPMailer
    $mail = new PHPMailer(true);
    
    $mail->isSMTP();
    $mail->Host = env('SMTP_HOST');
    $mail->SMTPAuth = true;
    $mail->Username = env('SMTP_USERNAME');
    $mail->Password = env('SMTP_PASSWORD');
    $mail->SMTPSecure = env('SMTP_ENCRYPTION') === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = env('SMTP_PORT');

    $mail->setFrom(env('SMTP_USERNAME'), env('SMTP_FROM_NAME', 'Nsikacart'));
    $mail->addAddress($email, $user['name']);

    $mail->isHTML(true);
    $mail->Subject = 'Password Reset Request - ' . env('APP_NAME', 'Nsikacart');
    $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #333;'>Password Reset Request</h2>
            <p>Hello {$user['name']},</p>
            <p>You requested a password reset for your " . env('APP_NAME', 'Nsikacart') . " account. Click the button below to reset your password:</p>
            <div style='text-align: center; margin: 30px 0;'>
                <a href='{$resetLink}' style='background-color: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>Reset Password</a>
            </div>
            <p>Or copy and paste this link into your browser:</p>
            <p style='word-break: break-all; color: #007bff;'>{$resetLink}</p>
            <p><strong>This link will expire in 1 hour.</strong></p>
            <p>If you didn't request this password reset, please ignore this email.</p>
            <hr style='margin-top: 30px; border: none; border-top: 1px solid #eee;'>
            <p style='color: #666; font-size: 12px;'>This email was sent from " . env('APP_NAME', 'Nsikacart') . ". Please do not reply to this email.</p>
        </div>";
    
    $mail->AltBody = "Hello {$user['name']},\n\nYou requested a password reset for your " . env('APP_NAME', 'Nsikacart') . " account.\n\nClick this link to reset your password: {$resetLink}\n\nThis link will expire in 1 hour.\n\nIf you didn't request this, please ignore this email.";

    $mail->send();

    echo json_encode([
        "success" => true,
        "message" => "Password reset link sent successfully! Please check your email."
    ]);

} catch (Exception $e) {
    error_log("Forgot password error: " . $e->getMessage());
    
    echo json_encode([
        "success" => false,
        "message" => "Failed to send reset email. Please try again later."
    ]);
} catch (Error $e) {
    error_log("Forgot password fatal error: " . $e->getMessage());
    
    echo json_encode([
        "success" => false,
        "message" => "An unexpected error occurred. Please try again later."
    ]);
}
?>