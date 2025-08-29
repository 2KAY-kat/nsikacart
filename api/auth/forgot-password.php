<?php
// keeping the output buffer c=in checke and clean for any unwanted data 
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

// classes that define phpmailer and whtawver it does under the hood 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// as usual start the session and set headers
session_start();
header("Content-Type: application/json");
header("Cache-Control: no-cache, must-revalidate");

try {
    // a littel post security check for that forgot password request
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response_code(405);
        echo json_encode([
            "success" => false,
            "message" => "Method not allowed"
        ]);
        exit;
    }

    //  we make sure we get the data in inputs in  JSON 
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "the data is not i a proper JSON frmat"
        ]);
        exit;
    }

    // validation of the inputs  
    if (empty($data['email'])) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Email address is required, please try again"
        ]);
        exit;
    }

    // making sure that the eamil is well sanitized ad valideted itno correct format for ....
    $email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
    // ikakanika , incase ili content ya bhobho like @ and any otehr formatinggs we get
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "your email address is not valid, try again"
        ]);
        exit;
    }

    // i realy dotn have to explain this check if user exists
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

    // generating a reset toke uses the bin2hex advanced algorithm for extar security
    $token = bin2hex(random_bytes(32));
    $tokenExpiry = date("Y-m-d H:i:s", strtotime('+1 hour'));

    // upadate the user who requested the reset token 
    $updateStmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires_at = ? WHERE email = ?");
    $success = $updateStmt->execute([$token, $tokenExpiry, $email]);

    if (!$success) {
        echo json_encode ([
            "success" => false,
            "message" => "failed to update the the reset token, please try again later"
        ]);
        exit;
    }

    // now we jump into the phpmailing system and the email format and its markup for the rest link email
    $baseUrl = env('APP_URL', 'http://localhost/nsikacart');
    $resetLink = rtrim($baseUrl, '/') . "/auth/reset-password.html?token=" . urlencode($token);

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
    ]); // after successful email sending w eget this one fr the reset link confirmation
// errro hundling ... classic
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