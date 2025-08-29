<?php

// in actuality the add user thing/module/feature whatever you might call it is ther for internal managers and devs to add other inhouse users like monitors automated monitors emplyeess and any other mamber of stuff ...
# its explicitly not intended to add users at a mass scale or normal users 
# dont misuse it in any other way than its intended one ....
# __2KAY__

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../config/db.php';
require_once '../auth/check-session.php';

require_once '../config/db.php';
require_once '../../helpers/PHPMailer-master/src/Exception.php';
require_once '../../helpers/PHPMailer-master/src/PHPMailer.php';
require_once '../../helpers/PHPMailer-master/src/SMTP.php';

// stage PHPMailer variables and then the headers
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json; charset=utf-8');

// capture any accidental output
if (ob_get_length()) ob_clean();
try {
// 1) method check
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// 2) auth check
$role = $_SESSION['user']['role'] ?? '';
if (! in_array($role, ['admin','monitor'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

// 3) parse JSON
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON payload'
    ]);
    exit;
}

// 4) validate fields
$req = ['name','email','phone','password','confirm_password','role'];
foreach ($req as $f) {
    if (empty($data[$f])) {
        echo json_encode([
            'success' => false,
            'message' => 'All fields are required'
        ]);
        exit;
    }
}

// Correct sanitization
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

if ($password !== $confirm_password) {
    echo json_encode([
        "success" => false,
        "message" => "Passwords do not match"
    ]);
    exit;
}

// Fix role handling
$user_role = htmlspecialchars(trim($data['role']));
$allowed_roles = ['user', 'monitor', 'admin'];
if (!in_array($user_role, $allowed_roles)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid role selected"
    ]);
    exit;
}

// checking the availabilty of a potential user similarity in db

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

    // run the randomizer algorith and runa random verification code and amke sure it stayes for 5 mins befire it expires
    $verificationToken = bin2hex(random_bytes(32));
    $tokenExpiry = date("Y-m-d H:i:s", strtotime('+15 minutes')); // 15 minute expiry


// we proceed tp hashing the password since weve checked for the existance of the email if we pass that then we proceed

$hashpassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, email_verified, verification_token, verification_expires_at, role) VALUES(?, ?, ?, ?, FALSE, ?, ?, ?)");
$success = $stmt->execute([$name, $email, $phone, $hashpassword, $verificationToken, $tokenExpiry, $user_role]);

// error hundling and mesage feedback
    if (!$success) {
        echo json_encode([
            "success" => false,
            "message" => "Failed to add new user"
        ]);
        exit;
    }
    // send verification email with PHPMailer
    $baseUrl = env('APP_URL', 'http://localhost/nsikacart');
    $verificationLink = rtrim($baseUrl, '/') . "/auth/verify-email.html?token=" . urlencode($verificationToken);

    $mail = new PHPMailer(true);

    // SMTP configuration from .env
    $mail->isSMTP();
    $mail->Host = env('SMTP_HOST');
    $mail->SMTPAuth = true;
    $mail->Username = env('SMTP_USERNAME');
    $mail->Password = env('SMTP_PASSWORD');
    $mail->SMTPSecure = env('SMTP_ENCRYPTION') === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = env('SMTP_PORT');

    $mail->setFrom(env('SMTP_USERNAME'), env('SMTP_FROM_NAME', 'Nsikacart'));
    $mail->addAddress($email, $name);

    // Email content for admin-added user
    $mail->isHTML(true);
    $mail->Subject = 'You have been added to ' . env('APP_NAME', 'Nsikacart') . ' - Please Verify Your Email';
    $mail->Body = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Email Verification</title>
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    background-color: #f8f9fa;
                }
                .email-container {
                    max-width: 600px;
                    margin: 0 auto;
                    background-color: #ffffff;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                }
                .header {
                    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
                    color: white;
                    padding: 30px 20px;
                    text-align: center;
                }
                .logo {
                    width: 60px;
                    height: 60px;
                    background-color: rgba(255, 255, 255, 0.1);
                    border-radius: 50%;
                    margin: 0 auto 15px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 24px;
                    font-weight: bold;
                }
                .header h1 {
                    margin: 0;
                    font-size: 28px;
                    font-weight: 300;
                }
                .content {
                    padding: 40px 30px;
                }
                .welcome-text {
                    font-size: 18px;
                    color: #007bff;
                    margin-bottom: 20px;
                    font-weight: 600;
                }
                .main-text {
                    font-size: 16px;
                    margin-bottom: 30px;
                    color: #555;
                }
                .verify-btn {
                    display: inline-block;
                    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
                    color: white;
                    padding: 15px 35px;
                    text-decoration: none;
                    border-radius: 25px;
                    font-weight: 600;
                    font-size: 16px;
                    text-align: center;
                    box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
                    transition: transform 0.2s ease;
                }
                .verify-btn:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
                }
                .btn-container {
                    text-align: center;
                    margin: 30px 0;
                }
                .alt-link {
                    background-color: #f8f9fa;
                    padding: 20px;
                    border-radius: 8px;
                    margin: 30px 0;
                    border-left: 4px solid #007bff;
                }
                .alt-link p {
                    margin: 0 0 10px 0;
                    font-size: 14px;
                    color: #666;
                }
                .alt-link a {
                    color: #007bff;
                    word-break: break-all;
                    text-decoration: none;
                }
                .warning-box {
                    background-color: #fff3cd;
                    border: 1px solid #ffeaa7;
                    border-radius: 8px;
                    padding: 15px;
                    margin: 20px 0;
                    display: flex;
                    align-items: center;
                }
                .warning-icon {
                    color: #856404;
                    font-size: 20px;
                    margin-right: 10px;
                }
                .warning-text {
                    color: #856404;
                    font-size: 14px;
                    margin: 0;
                    font-weight: 600;
                }
                .footer {
                    background-color: #f8f9fa;
                    padding: 30px;
                    text-align: center;
                    border-top: 1px solid #dee2e6;
                }
                .footer-text {
                    color: #6c757d;
                    font-size: 14px;
                    margin: 0 0 15px 0;
                }
                .social-links {
                    margin-top: 20px;
                }
                .social-links a {
                    display: inline-block;
                    margin: 0 10px;
                    color: #007bff;
                    text-decoration: none;
                    font-size: 14px;
                }
                .brand-name {
                    color: #007bff;
                    font-weight: 700;
                }
                .features {
                    display: flex;
                    justify-content: space-around;
                    margin: 30px 0;
                    padding: 20px 0;
                    border-top: 1px solid #eee;
                    border-bottom: 1px solid #eee;
                }
                .feature {
                    text-align: center;
                    flex: 1;
                }
                .feature-icon {
                    font-size: 24px;
                    color: #007bff;
                    margin-bottom: 10px;
                }
                .feature-text {
                    font-size: 12px;
                    color: #666;
                    margin: 0;
                }
                @media (max-width: 600px) {
                    .content {
                        padding: 30px 20px;
                    }
                    .features {
                        flex-direction: column;
                        gap: 15px;
                    }
                    .verify-btn {
                        padding: 12px 25px;
                        font-size: 14px;
                    }
                }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='header'>
                    <div class='logo'>N</div>
                    <h1>Welcome to <span class='brand-name'>" . env('APP_NAME', 'Nsikacart') . "</span></h1>
                </div>
                
                <div class='content'>
                    <p class='welcome-text'>Hello {$name}! 👋</p>
                    
                    <p class='main-text'>
                        You have been added as a new user on <strong>" . env('APP_NAME', 'Nsikacart') . "</strong> by an administrator.<br>
                        To activate your account and start using our platform, please verify your email address by clicking the button below.
                    </p>
                    
                    <div class='btn-container'>
                        <a href='{$verificationLink}' class='verify-btn'>
                            ✓ Verify My Email Address
                        </a>
                    </div>
                    
                    <div class='warning-box'>
                        <span class='warning-icon'>⏰</span>
                        <p class='warning-text'>This verification link will expire in 15 minutes.</p>
                    </div>
                    
                    <div class='features'>
                        <div class='feature'>
                            <div class='feature-icon'>🛒</div>
                            <p class='feature-text'>Shop Quality Products</p>
                        </div>
                        <div class='feature'>
                            <div class='feature-icon'>🔒</div>
                            <p class='feature-text'>Secure Marketplace</p>
                        </div>
                    </div>
                    
                    <div class='alt-link'>
                        <p><strong>Having trouble with the button?</strong></p>
                        <p>Copy and paste this link into your browser to verify your email:</p>
                        <a href='{$verificationLink}'>{$verificationLink}</a>
                    </div>
                    
                    <p style='color: #666; font-size: 14px; margin-top: 30px;'>
                        If you were not expecting this email or did not request to be added, you can safely ignore this message.
                    </p>
                </div>
                
                <div class='footer'>
                    <p class='footer-text'>
                        This email was sent from <strong class='brand-name'>" . env('APP_NAME', 'Nsikacart') . "</strong><br>
                        Your trusted online shopping destination
                    </p>
                    
                    <div class='social-links'>
                        <a href='#'>Privacy Policy</a> | 
                        <a href='#'>Terms of Service</a> | 
                        <a href='#'>Contact Support</a>
                    </div>
                    
                    <p style='color: #999; font-size: 12px; margin-top: 20px;'>
                        © " . date('Y') . " " . env('APP_NAME', 'Nsikacart') . ". All rights reserved.
                    </p>
                </div>
            </div>
        </body>
        </html>";

    $mail->AltBody = "
You have been added as a user on " . env('APP_NAME', 'Nsikacart') . " by an administrator.

Hello {$name},

To activate your account, please verify your email address by clicking the link below or copying it into your browser:

{$verificationLink}

⚠️ This verification link will expire in 15 minutes.

If you were not expecting this email, you can safely ignore it.

Welcome to " . env('APP_NAME', 'Nsikacart') . "!

---
This email was sent from " . env('APP_NAME', 'Nsikacart') . "
© " . date('Y') . " " . env('APP_NAME', 'Nsikacart') . ". All rights reserved.
    ";

    $mail->send();

if ($success) {
    $newId   = $pdo->lastInsertId();
    $response = [
      'success' => true,
      'message' => 'User added successfully! Please check your email to verify and activate your account. The verification link will expire in 15 minutes.',
      'user'    => [
        'id'   => (int)$newId,
        'name' => $name,
        'role' => $user_role
      ]
    ];
    if (ob_get_length()) { 
        ob_clean(); 
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to add new user"
    ]);
    exit;
}

} catch (Exception $e) {
    if (ob_get_length()) { ob_clean(); }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}