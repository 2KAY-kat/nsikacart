<?php
// cleanisng the buffer 
if (ob_get_level()) {
    ob_end_clean();
}

// Include required files FIRST and load environment 
require_once '../../helpers/env.php';
loadEnv('../../.env');

require_once '../config/db.php';
// require_once '../../helpers/PHPMailer-master/src/Exception.php';
// require_once '../../helpers/PHPMailer-master/src/PHPMailer.php';
// require_once '../../helpers/PHPMailer-master/src/SMTP.php';

// stage PHPMailer variables and then the headers
// use PHPMailer\PHPMailer\PHPMailer;
// use PHPMailer\PHPMailer\Exception;

header("Content-Type: application/json");
header("Cache-Control: no-cache, must-revalidate");

try {
    // as ussual only process POST requests
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
        echo json_encode([
            "success" => false,
            "message" => "Method not allowed"
        ]);
        exit;
    }

    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    // validate all the register filed inputs
    if (!isset($data['name'], $data['email'], $data['password'], $data['confirm_password'])) {
        echo json_encode([
            "success" => false, 
            "message" => "All fields are required"
        ]);
        exit;
    }

    // trims and filter and sanotize the inputs 
    $name = htmlspecialchars(trim($data['name']));
    $email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
    
    // and errror hundling for all any if needed to after soem failrure
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

    // checking the password if it marches 
    /* NOTE:
    on signup we already front end check for the password matchability done in javascript frontend validation system so this is a seecond stage of securitt
    */
    if ($password !== $confirm_password) {
        echo json_encode([
            "success" => false, 
            "message" => "Passwords do not match"
        ]);
        exit;
    }

    // the leng of the password is also checked on the front but since this shti is kinda future proof i bult it for other projects future proof
    if (strlen($password) < 6) {
        echo json_encode([
            "success" => false, 
            "message" => "Password must be at least 6 characters long"
        ]);
        exit;
    }

    // check if user already exists especialy the email aad the name 
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? OR name = ?");
    $stmt->execute([$email, $name]);
    $exists = $stmt->fetchColumn();

    /// and ofcourse error hundling
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

    // obvious
    $hashpassword = password_hash($password, PASSWORD_DEFAULT);

    // insert user with email_verified = FALSE and sends the verify wnmail token awaiting verification
    $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, email_verified, verification_token, verification_expires_at) VALUES(?, ?, ?, ?, FALSE, ?, ?)");
    $success = $stmt->execute([$name, $email, $phone, $hashpassword, $verificationToken, $tokenExpiry]);

    // error hundling and mesage feedback
    if (!$success) {
        echo json_encode([
            "success" => false,
            "message" => "Failed to create user account"
        ]);
        exit;
    }

    // send verification email and fcourse with Resend API instead of PHPMailer
$baseUrl = env('APP_URL', 'http://localhost/nsikacart');
$verificationLink = rtrim($baseUrl, '/') . "/auth/verify-email.html?token=" . urlencode($verificationToken);
// sends verification links with a token that leads us to the verify page 

// prepare email subject and HTML body (same as before)
$subject = 'Welcome to ' . env('APP_NAME', 'Nsikacart') . ' - Verify Your Email';
$htmlBody = "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Email Verification</title>
    <style>
        /* (same CSS from your previous email template — untouched) */
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
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: white; padding: 30px 20px; text-align: center; }
        .logo { width: 60px; height: 60px; background-color: rgba(255, 255, 255, 0.1); border-radius: 50%; margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: bold; }
        .header h1 { margin: 0; font-size: 28px; font-weight: 300; }
        .content { padding: 40px 30px; }
        .welcome-text { font-size: 18px; color: #007bff; margin-bottom: 20px; font-weight: 600; }
        .main-text { font-size: 16px; margin-bottom: 30px; color: #555; }
        .verify-btn { display: inline-block; background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: white; padding: 15px 35px; text-decoration: none; border-radius: 25px; font-weight: 600; font-size: 16px; text-align: center; box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3); transition: transform 0.2s ease; }
        .verify-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4); }
        .btn-container { text-align: center; margin: 30px 0; }
        .alt-link { background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 30px 0; border-left: 4px solid #007bff; }
        .alt-link p { margin: 0 0 10px 0; font-size: 14px; color: #666; }
        .alt-link a { color: #007bff; word-break: break-all; text-decoration: none; }
        .warning-box { background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 15px; margin: 20px 0; display: flex; align-items: center; }
        .warning-icon { color: #856404; font-size: 20px; margin-right: 10px; }
        .warning-text { color: #856404; font-size: 14px; margin: 0; font-weight: 600; }
        .footer { background-color: #f8f9fa; padding: 30px; text-align: center; border-top: 1px solid #dee2e6; }
        .footer-text { color: #6c757d; font-size: 14px; margin: 0 0 15px 0; }
        .social-links { margin-top: 20px; }
        .social-links a { display: inline-block; margin: 0 10px; color: #007bff; text-decoration: none; font-size: 14px; }
        .brand-name { color: #007bff; font-weight: 700; }
        .features { display: flex; justify-content: space-around; margin: 30px 0; padding: 20px 0; border-top: 1px solid #eee; border-bottom: 1px solid #eee; }
        .feature { text-align: center; flex: 1; }
        .feature-icon { font-size: 24px; color: #007bff; margin-bottom: 10px; }
        .feature-text { font-size: 12px; color: #666; margin: 0; }
        @media (max-width: 600px) {
            .content { padding: 30px 20px; }
            .features { flex-direction: column; gap: 15px; }
            .verify-btn { padding: 12px 25px; font-size: 14px; }
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
                Thank you for joining <strong>" . env('APP_NAME', 'Nsikacart') . "</strong>! 
                To start shopping, please verify your email address below.
            </p>
            <div class='btn-container'>
                <a href='{$verificationLink}' class='verify-btn'>✓ Verify My Email Address</a>
            </div>
            <div class='warning-box'>
                <span class='warning-icon'>⏰</span>
                <p class='warning-text'>This verification link will expire in 15 minutes.</p>
            </div>
        </div>
        <div class='footer'>
            <p class='footer-text'>© " . date('Y') . " " . env('APP_NAME', 'Nsikacart') . ". All rights reserved.</p>
        </div>
    </div>
</body>
</html>
";

$textBody = "
Welcome to " . env('APP_NAME', 'Nsikacart') . "!

Hello {$name},

Thank you for joining " . env('APP_NAME', 'Nsikacart') . "! Please verify your email address:

{$verificationLink}

⚠️ Link expires in 15 minutes.
";

// prepare payload for Resend API
$emailPayload = [
    "from" => "Nsikacart <onboarding@resend.dev>",
    "to" => [$email],
    "subject" => $subject,
    "html" => $htmlBody,
    "text" => $textBody
];

// send request via cURL
$ch = curl_init('https://api.resend.com/emails');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . env('RESEND_API_KEY'),
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailPayload));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// feedback on sent email with the verify link email
if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode([
        "success" => true,
        "message" => "Registration successful! Please check your email to verify your account. The verification link will expire in 15 minutes."
    ]);
} else {
    error_log("Resend API failed: " . $response);
    echo json_encode([
        "success" => true,
        "message" => "Registration successful, but failed to send verification email. Please contact support."
    ]);
}

//     // feedback on sent email with teh verify link email
//     echo json_encode([
//         "success" => true, 
//         "message" => "Registration successful! Please check your email to verify your account. The verification link will expire in 15 minutes."
//     ]);

} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    
//     echo json_encode([
//         "success" => false,
//         "message" => "Registration failed. Please try again later."
//     ]);
} catch (Error $e) {
    error_log("Registration fatal error: " . $e->getMessage());
    
    echo json_encode([
        "success" => false,
        "message" => "An unexpected error occurred. Please try again later."
    ]);
}
?>
