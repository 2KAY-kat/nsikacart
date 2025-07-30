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

header("Content-Type: application/json");
header("Cache-Control: no-cache, must-revalidate");

try {
    // Only process POST requests
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Only POST requests allowed");
    }

    $data = json_decode(file_get_contents("php://input"), true);

    // Validate inputs
    if (!isset($data['name'], $data['email'], $data['password'], $data['confirm_password'])) {
        echo json_encode(["success" => false, "message" => "All fields are required"]);
        exit;
    }

    $name = htmlspecialchars(trim($data['name']));
    $email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            "success" => false, 
            "message" => "Invalid email format"
        ]);
        exit;
    }

    $phone = htmlspecialchars(trim($data['phone']));
    $password = trim($data['password']);
    $confirm_password = trim($data['confirm_password']);

    // Password match check
    if ($password !== $confirm_password) {
        echo json_encode([
            "success" => false, 
            "message" => "Passwords do not match"
        ]);
        exit;
    }

    // Password length check
    if (strlen($password) < 6) {
        echo json_encode([
            "success" => false, 
            "message" => "Password must be at least 6 characters long"
        ]);
        exit;
    }

    // Check if user already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? OR name = ?");
    $stmt->execute([$email, $name]);
    $exists = $stmt->fetchColumn();

    if ($exists > 0) {
        echo json_encode([
            "success" => false, 
            "message" => "User with this email or name already exists"
        ]);
        exit;
    }

    // Generate verification token
    $verificationToken = bin2hex(random_bytes(32));
    $tokenExpiry = date("Y-m-d H:i:s", strtotime('+15 minutes')); // 15 minute expiry

    // Hash password
    $hashpassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert user with email_verified = FALSE
    $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, email_verified, verification_token, verification_expires_at) VALUES(?, ?, ?, ?, FALSE, ?, ?)");
    $success = $stmt->execute([$name, $email, $phone, $hashpassword, $verificationToken, $tokenExpiry]);

    if (!$success) {
        throw new Exception("Failed to create user account");
    }

    // Send verification email
    $appUrl = env('APP_URL', 'http://localhost/nsikacart');
    $verificationLink = $appUrl . "/auth/verify-email.html?token=" . urlencode($verificationToken);

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
    $mail->addAddress($email, $name);

    $mail->isHTML(true);
    $mail->Subject = 'Email Verification - ' . env('APP_NAME', 'Nsikacart');
    $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #333;'>Welcome to " . env('APP_NAME', 'Nsikacart') . "!</h2>
            <p>Hello {$name},</p>
            <p>Thank you for registering with " . env('APP_NAME', 'Nsikacart') . ". To complete your registration, please verify your email address by clicking the button below:</p>
            <div style='text-align: center; margin: 30px 0;'>
                <a href='{$verificationLink}' style='background-color: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>Verify Email Address</a>
            </div>
            <p>Or copy and paste this link into your browser:</p>
            <p style='word-break: break-all; color: #007bff;'>{$verificationLink}</p>
            <p><strong>This link will expire in 15 minutes.</strong></p>
            <p>If you didn't create this account, please ignore this email.</p>
            <hr style='margin-top: 30px; border: none; border-top: 1px solid #eee;'>
            <p style='color: #666; font-size: 12px;'>This email was sent from " . env('APP_NAME', 'Nsikacart') . ". Please do not reply to this email.</p>
        </div>";

    $mail->AltBody = "Hello {$name},\n\nThank you for registering with " . env('APP_NAME', 'Nsikacart') . ". Please verify your email address by clicking this link: {$verificationLink}\n\nThis link will expire in 15 minutes.\n\nIf you didn't create this account, please ignore this email.";

    $mail->send();

    echo json_encode([
        "success" => true, 
        "message" => "Registration successful! Please check your email to verify your account. The verification link will expire in 15 minutes."
    ]);

} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    
    echo json_encode([
        "success" => false,
        "message" => "Registration failed. Please try again later."
    ]);
} catch (Error $e) {
    error_log("Registration fatal error: " . $e->getMessage());
    
    echo json_encode([
        "success" => false,
        "message" => "An unexpected error occurred. Please try again later."
    ]);
}
?>
