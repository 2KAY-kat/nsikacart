<?php
// cleanisng the buffer 
if (ob_get_level()) {
    ob_end_clean();
}

// Include required files FIRST and load environment 
require_once '../../helpers/env.php';
loadEnv('../../.env');

require_once '../config/db.php';

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

    // NEW: Send notification email to admin about new registration (since Resend is sandboxed)
    $baseUrl = env('APP_URL', 'http://localhost/nsikacart');
    $dashboardLink = rtrim($baseUrl, '/') . "/public/dashboard/index.html#admin/users";
    
    // Get admin email from env or use your verified Resend email
    $adminEmail = env('ADMIN_EMAIL', 'onboarding@resend.dev'); // Replace with your email

    // Prepare admin notification email
    $subject = 'New User Registration - ' . env('APP_NAME', 'Nsikacart');
    $htmlBody = "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>New User Registration</title>
        <style>
            body { margin: 0; padding: 0; font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f8f9fa; }
            .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
            .header { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: white; padding: 30px 20px; text-align: center; }
            .content { padding: 40px 30px; }
            .info-box { background-color: #f8f9fa; border-left: 4px solid #007bff; padding: 15px; margin: 20px 0; }
            .info-box strong { color: #007bff; }
            .btn { display: inline-block; background: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #dee2e6; }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='header'>
                <h1>🔔 New User Registration</h1>
            </div>
            <div class='content'>
                <p><strong>A new user has registered and requires email verification.</strong></p>
                
                <div class='info-box'>
                    <p><strong>Name:</strong> {$name}</p>
                    <p><strong>Email:</strong> {$email}</p>
                    <p><strong>Phone:</strong> {$phone}</p>
                    <p><strong>Registered:</strong> " . date('Y-m-d H:i:s') . "</p>
                </div>
                
                <p>Please log in to the admin dashboard to manually verify this user's email address.</p>
                
                <div style='text-align: center;'>
                    <a href='{$dashboardLink}' class='btn'>Go to Dashboard</a>
                </div>
                
                <p style='color: #666; font-size: 14px; margin-top: 30px;'>
                    <strong>Note:</strong> Due to Resend's sandbox mode, automatic email verification is disabled. 
                    Users must be manually verified by an admin.
                </p>
            </div>
            <div class='footer'>
                <p style='color: #666; font-size: 12px;'>© " . date('Y') . " " . env('APP_NAME', 'Nsikacart') . ". All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $textBody = "
    New User Registration - " . env('APP_NAME', 'Nsikacart') . "
    
    A new user has registered and requires email verification.
    
    Name: {$name}
    Email: {$email}
    Phone: {$phone}
    Registered: " . date('Y-m-d H:i:s') . "
    
    Please log in to the admin dashboard to manually verify this user.
    Dashboard: {$dashboardLink}
    ";

    // Send notification via Resend API to admin
    $emailPayload = [
        "from" => "Nsikacart <onboarding@resend.dev>",
        "to" => [$adminEmail], // Admin email
        "subject" => $subject,
        "html" => $htmlBody,
        "text" => $textBody
    ];

    // Send request via cURL
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

    // Log if email sending failed
    if ($httpCode < 200 || $httpCode >= 300) {
        error_log("Failed to send admin notification email: " . $response);
    }

    // feedback on registration success
    echo json_encode([
        "success" => true,
        "message" => "Registration successful! Your account is pending verification. An administrator will verify your email shortly."
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