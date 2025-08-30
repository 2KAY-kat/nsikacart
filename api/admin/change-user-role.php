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
    // Check if user is authenticated and has admin privileges
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required'
        ]);
        exit;
    }

    // Check if user has admin role (only admin can change roles)
    $userRole = $_SESSION['user']['role'] ?? '';
    if ($userRole !== 'admin') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Access denied. Only administrators can change user roles.'
        ]);
        exit;
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['user_id'] ?? null;
    $newRole = $input['new_role'] ?? null;
    $currentRole = $input['current_role'] ?? null;

    if (!$userId || !$newRole) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'User ID and new role are required'
        ]);
        exit;
    }

    // Validate new role
    $allowedRoles = ['user', 'monitor', 'admin'];
    if (!in_array($newRole, $allowedRoles)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid role specified'
        ]);
        exit;
    }

    // Prevent admin from changing their own role
    if ($userId == $_SESSION['user']['id']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'You cannot change your own role'
        ]);
        exit;
    }

    // Check if user exists and get their current role
    $stmt = $pdo->prepare("SELECT role, name, email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit;
    }

    // Check if there's actually a change
    if ($user['role'] === $newRole) {
        echo json_encode([
            'success' => true,
            'message' => 'No changes needed - user already has this role'
        ]);
        exit;
    }

    // Update user role
    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$newRole, $userId]);

    if ($stmt->rowCount() > 0) {
        // Log the role change for audit purposes
        $currentUserName = $_SESSION['user']['name'] ?? 'Unknown';
        error_log("Admin {$currentUserName} (ID: {$_SESSION['user']['id']}) changed user {$user['name']} (ID: $userId) role from {$user['role']} to $newRole");

        // Send role change notification email
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
        $mail->addAddress($user['email'], $user['name']);
        
        $mail->isHTML(true);
        $mail->Subject = 'Role Update Notification - ' . env('APP_NAME', 'Nsikacart');
        
        // Role-specific messages
        $roleDescription = '';
        switch($newRole) {
            case 'admin':
                $roleDescription = 'As an administrator, you now have full access to manage users, monitor system activities, and perform administrative tasks.';
                break;
            case 'monitor':
                $roleDescription = 'As a monitor, you can now oversee user activities, generate reports, and help maintain platform quality.';
                break;
            case 'user':
                $roleDescription = 'You now have standard user privileges on our platform.';
                break;
        }

        $mail->Body = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <title>Role Update Notification</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9;'>
                <div style='background-color: #ffffff; padding: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
                    <h2 style='color: #007bff; margin-bottom: 20px;'>Role Update Notification</h2>
                    
                    <p>Hello {$user['name']},</p>
                    
                    <p>Your role on " . env('APP_NAME', 'Nsikacart') . " has been updated from <strong>{$user['role']}</strong> to <strong>{$newRole}</strong>.</p>
                    
                    <p>{$roleDescription}</p>

                    <div style='margin: 25px 0; padding: 15px; background-color: #f8f9fa; border-left: 4px solid #007bff;'>
                        <p style='margin: 0;'><strong>New Role:</strong> " . ucfirst($newRole) . "</p>
                    </div>

                    <p>If you have any questions about your new role or responsibilities, please contact the administration team.</p>
                    
                    <p style='color: #666; font-size: 14px; margin-top: 30px;'>
                        Best regards,<br>
                        The " . env('APP_NAME', 'Nsikacart') . " Team
                    </p>
                </div>
            </div>
        </body>
        </html>";

        try {
            $mail->send();
            echo json_encode([
                'success' => true,
                'message' => "User role successfully changed and notification email sent"
            ]);
        } catch (Exception $e) {
            error_log("Failed to send role change email: " . $e->getMessage());
            echo json_encode([
                'success' => true,
                'message' => "User role changed but failed to send notification email"
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update user role'
        ]);
    }

} catch (Exception $e) {
    error_log("Change User Role Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
}
?>