<?php
// cleansed buffer output as intended 
if (ob_get_level()) {
    ob_end_clean();
}

// requires aand  loadenv and starts the sessions
require_once '../../helpers/env.php';
loadEnv('../../.env');
require_once '../config/db.php';

session_start();
header("Content-Type: application/json");
header("Cache-Control: no-cache, must-revalidate");

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        echo json_encode([
            "success" => false,
            "message" => "Only POST requests allowed"
        ]);
        exit;
    }
    /** 
     * i think i need a centrised function for every post required block of code fpr most of the files this is redundant 
     * i cant keep thos on
     *  **/

    // refomarting our data for proper jsons and decoded as well clean shit 
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid JSON data, please try again"
        ]);
        exit;
    }

    // making sure no empty fiels are sent fir securoty and consistancy
    if (empty($data['token']) || empty($data['password'])) {
        echo json_encode([
            "success" => false,
            "message" => "Token and password are required"
        ]);
        exit;
    }

    // adding some salts as ussual
    $token = trim($data['token']);
    $password = trim($data['password']);

    if (strlen($password) < 6) { // vaidation of teh passsword that allows only passwords with character s above 6 be required or  auhourosed for processin g for eligibility
        echo json_encode([
            "success" => false,
            "message" => "Password must be at least 6 characters long"
        ]);
        exit;
    }

    // Verify token and check expiry DATE AND TIME OF THEH TOKEN into db
    $stmt = $pdo->prepare("SELECT id, email, reset_expires_at FROM users WHERE reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid reset token. Please request a new password reset."
        ]);
        exit;
    }

    if (strtotime($user['reset_expires_at']) < time()) {
        echo json_encode([
            "success" => false,
            "message" => "Reset token has expired. Please request a new password reset."
        ]);
        exit;
    }

    // we then update password and clear reset token out of the tables for cleaner nice users table
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $updateStmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires_at = NULL WHERE id = ?");
    $success = $updateStmt->execute([$hashedPassword, $user['id']]);

    // if any failures is to occur on pass weord update we send feedback 
    if (!$success) {
        echo json_encode([
            "success" => false,
            "message" => "Failed to update password"
        ]);
        exit;
    }

    // and if we are successful on updating the password the feedback again .. usual stuff
    echo json_encode([
        "success" => true,
        "message" => "Password reset successfully!"
    ]);

    // amd some objects catches and stuff

} catch (Exception $e) {
    error_log("Reset password error: " . $e->getMessage());
    
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>