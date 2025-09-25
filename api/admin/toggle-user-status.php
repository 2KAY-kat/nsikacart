<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../config/db.php';
require_once '../../helpers/PHPMailer-master/src/Exception.php';
require_once '../../helpers/PHPMailer-master/src/PHPMailer.php';
require_once '../../helpers/PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
header('Content-Type: application/json');

try {
    // Check if user is authenticated and has admin or monitor privileges
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required'
        ]);
        exit;
    }

    // Check if user has admin or monitor role
    $userRole = $_SESSION['user']['role'] ?? '';
    if ($userRole !== 'admin' && $userRole !== 'monitor') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Access denied. Admin or Monitor privileges required.'
        ]);
        exit;
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['user_id'] ?? null;
    $currentStatus = $input['current_status'] ?? null;

    if (!$userId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'User ID is required'
        ]);
        exit;
    }

    // Prevent user from changing their own status
    if ($userId == $_SESSION['user']['id']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'You cannot change your own status'
        ]);
        exit;
    }

    // Get user details for logging
    $stmt = $pdo->prepare("SELECT name, email, role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$targetUser) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit;
    }

    // Additional check: Monitors cannot change status of admins
    if ($userRole === 'monitor' && $targetUser['role'] === 'admin') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Monitors cannot change the status of administrators'
        ]);
        exit;
    }

    // Toggle status
$newStatus = ($currentStatus === 'active') ? 'suspended' : 'active';

$stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
$stmt->execute([$newStatus, $userId]);

if ($stmt->rowCount() > 0) {
    // Prepare email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = env('SMTP_HOST'); 
        $mail->SMTPAuth   = true;
        $mail->Username   = env('SMTP_USERNAME');
        $mail->Password   = env('SMTP_PASSWORD');
        $mail->SMTPSecure = env('SMTP_ENCRYPTION') === 'tls' ? 
            PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = env('SMTP_PORT');

        $mail->setFrom(env('SMTP_USERNAME'), env('SMTP_FROM_NAME') ?: 'Nsikacart');
        $mail->addAddress($targetUser['email'], $targetUser['name']);

        $mail->isHTML(true);
        $mail->Subject = "User Status Change Notification - " . (env('APP_NAME') ?: 'Nsikacart');

        $actionMessage = $newStatus === 'active'
            ? '<p>Your ' . (env('APP_NAME') ?: 'Nsikacart') . ' account is no longer suspended after our team made further review on your activities.</p> 
                <p>You can now be able to login and access your dashboard.</p>'
            : '<p>Your account has been suspended due to some suspiciouse activities that we have observed. so you cannot access your dashboard.</p> 
                <p>If you have complaint of unfair suspension please contact the adminstrator or got to our customer support service to place your complaint.</p>';

        $mail->Body = "
            <h2>Status Notification</h2>
            <p>Hello {$targetUser['name']},</p>
            <p>{$actionMessage}</p> 
            <p style='color: #666; font-size: 14px; margin-top: 30px;'> Best regards,<br> The " . env('APP_NAME', 'Nsikacart') . " Team </p>
        ";

        $mail->send();

        // Log action
        $currentUserName = $_SESSION['user']['name'] ?? 'Unknown';
        error_log("{$userRole} {$currentUserName} (ID: {$_SESSION['user']['id']}) {$newStatus} user {$targetUser['name']} (ID: $userId)");

        echo json_encode([
            'success' => true,
            'message' => "User successfully {$newStatus} and notification email sent"
        ]);

    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);

        echo json_encode([
            'success' => true,
            'message' => "User successfully {$newStatus}, but email could not be sent"
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'User not found or no status change applied'
    ]);
}


} catch (Exception $e) {
    error_log("Toggle User Status Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
}
?>