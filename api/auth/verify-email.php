<?php
// Clean any previous output
if (ob_get_level()) {
    ob_end_clean();
}

require_once '../config/db.php';

// headers and ...
header("Content-Type: application/json");
header("Cache-Control: no-cache, must-revalidate");

try {
    // alloweing posts requests only to be processed
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Only POST requests allowed");
    }

    // we make sure all data sent across and get the site is in JSON input
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    // if not json format data
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            "success" => false,
            "message" => "Your input is not in a valid JSON data fomart"
        ]);
        exit;
    }

    // validation of required fields for the tokens since its required
    if (empty($data['token'])) {
        echo json_encode([
            "success" => false,
            "message" => "Sorry, Verification token is required"
        ]);
        exit;
    }

    // secure and trim the data for any unwaned spaces and fomartings
    $token = trim($data['token']);

    // loop in to the db to find user with the verification token sent omn eamil
    $stmt = $pdo->prepare("SELECT id, name, email, email_verified, verification_expires_at FROM users WHERE verification_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    // if the user is present in the db .... 
    if (!$user) { // notify the user to look into thier email inbox
        echo json_encode([
            "success" => false,
            "message" => "Invalid verification token. Please check your email or request a new verification link."
        ]);
        exit;
    }

    // and if the user is after checking the db email is already verified .. ptompt them to proceed to login
    if ($user['email_verified']) {
        echo json_encode([
            "success" => false,
            "message" => "Email address is already verified. You can log in to your account."
        ]);
        exit;
    }

    // akamachedwa poke and check the db table if the token is still usable according to time and  check if token has expired
    if (strtotime($user['verification_expires_at']) < time()) {
        echo json_encode([ // if its expired ....  notify the user to redo the thing again
            "success" => false,
            "message" => "Verification link has expired (15 minutes). Please request a new verification email."
        ]);
        exit;
    }

    // if its still useble (theh rtoken) goeas into the db and verifys the email
    $updateStmt = $pdo->prepare("UPDATE users SET email_verified = TRUE, verification_token = NULL, verification_expires_at = NULL WHERE id = ?");
    $success = $updateStmt->execute([$user['id']]);

    // on seuucesfull veirfication we send feedback to the user about it and thir proceed 
    if ($success) {
        echo json_encode([
            "success" => true,
            "message" => "Email verified successfully! You can now log in to your account."
        ]);
    } else { // on failure to verify the email we give feed back for a try againnattempt onn thier end
        echo json_encode([
            "success" => false,
            "message" => "Failed to verify email"
        ]);
        exit;
    }

} catch (Exception $e) {
    error_log("Email verification error: " . $e->getMessage());
    
    echo json_encode([
        "success" => false,
        "message" => "Email verification failed. Please try again later."
    ]);
}
?>