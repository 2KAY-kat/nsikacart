<?php
if (ob_get_level()) {
    ob_end_clean();
}

require_once '../../helpers/env.php';
loadEnv('../../.env');

require_once '../config/db.php';
require_once '../../helpers/PHPMailer-master/src/Exception.php';
require_once '../../helpers/PHPMailer-master/src/PHPMailer.php';
require_once '../../helpers/PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header("Content-Type: application/json");
header("Cache-Control: no-cache, must-revalidate");

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Method not allowed");
    }

    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    if (!isset($data['email'])) {
        throw new Exception("Email is required");
    }

    $email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    // Check if user exists and needs verification
    $stmt = $pdo->prepare("SELECT id, name, email_verified FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception("No account found with this email address");
    }

    if ($user['email_verified']) {
        throw new Exception("This email is already verified. You can login to your account.");
    }

    // Generate new verification token
    $verificationToken = bin2hex(random_bytes(32));
    $tokenExpiry = date("Y-m-d H:i:s", strtotime('+15 minutes'));

    // Update user with new token
    $updateStmt = $pdo->prepare("UPDATE users SET verification_token = ?, verification_expires_at = ? WHERE id = ?");
    $updateSuccess = $updateStmt->execute([$verificationToken, $tokenExpiry, $user['id']]);

    if (!$updateSuccess) {
        throw new Exception("Failed to generate new verification token");
    }

    // Send new verification email
    $baseUrl = env('APP_URL', 'http://localhost/nsikacart');
    $verificationLink = rtrim($baseUrl, '/') . "/auth/verify-email.html?token=" . urlencode($verificationToken);

    $mail = new PHPMailer(true);
    
    // SMTP configuration
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
    $mail->Subject = 'New Verification Link - ' . env('APP_NAME', 'Nsikacart');
    $mail->Body = "
    <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
        <h2>New Verification Link</h2>
        <p>Hello {$user['name']},</p>
        <p>You requested a new verification link. Please click the button below to verify your email address:</p>
        <p style='margin: 25px 0;'>
            <a href='{$verificationLink}' 
               style='background-color: #007bff; color: white; padding: 12px 25px; text-decoration: none; border-radius: 4px; display: inline-block;'>
                Verify Email Address
            </a> 
        </p>
        <p style='color: #666;'><small>This link will expire in 15 minutes.</small></p>
        <p style='color: #666;'><small>If you didn't request this, you can safely ignore this email.</small></p>
    </div>";

    $mail->send();

    echo json_encode([
        "success" => true,
        "message" => "A new verification link has been sent to your email address. Please check your inbox."
    ]);

} catch (Exception $e) {
    error_log("Resend verification error: " . $e->getMessage());
    
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>