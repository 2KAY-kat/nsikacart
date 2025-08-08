<?php
// buffer cleanup and session start
ob_start();
ob_clean();

session_start();
header("Content-Type: application/json");
header("Cache-Control: no-cache, must-revalidate");

require_once '../config/db.php';

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response_code(405);
        echo json_encode([
            "success" => false,
            "message" => "This method is not allowed"
        ]);
        exit;
    }

    // raw input without the clutter and stuff
    $raw_input = file_get_contents("php://input");
    
    // check if input is empty helps us prevet security edge cases and yes no data can be dangourous as bad data
    if (empty(trim($raw_input))) { // ad ofcouser the trim to avoid unncessary and spaced out data
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "there was no data sent to the server, please try again"
        ]);
        exit;
    }
    
    // since our data is being ecoded over and over ... we got to decode it so that its readable for the php engine to process it
    $data = json_decode($raw_input, true);

    // check for JSON decode errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid JSON data format: " . json_last_error_msg()
        ]);
        exit;
    }
    
    // check if data is array on sending
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid data format, try again"
        ]);
        exit;
    }

    // making sure the fields are validated if are required fields
    if (!isset($data['email']) || !isset($data['password'])) {
        echo json_encode([
            "success" => false,
            "message" => "Email/Username and password are required"
        ]);
        exit;
    }

    $login_input = trim($data['email']);
    $password = trim($data['password']);
    $remember = isset($data['remember']) && $data['remember'] ? true : false; // if the remember me check bos is ever ticked store it into the db

    // you jsut cant sent an empty password or field itno the processing engine
    if (empty($login_input) || empty($password)) {
        echo json_encode([
            "success" => false,
            "message" => "Email/Username and password cannot be empty"
        ]);
        exit;
    }

    // check if input is an email or username
    /* since the first input besides the password is either email or username ... we check if the inputs is either of those by by checking it against the their other details.
    */
    if (filter_var($login_input, FILTER_VALIDATE_EMAIL)) {
        $query = "SELECT id, name, email, password, role, email_verified FROM users WHERE email = ?";
        $params = [$login_input];
    } else {
        $query = "SELECT id, name, email, password, role, email_verified FROM users WHERE name = ?";
        $params = [$login_input];
    }
    
    // actual prepared statement to avoid sql injections and yeah to be safe so afar...
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid username/email or password"
        ]);
        exit;
    }

    // check if email is verified/ this helps us to identify if the currently logging in user is actaually verified and if not and email is sent actual genius...
    if (!$user['email_verified']) {
        echo json_encode([
            "success" => false,
            "message" => "Please verify your email address before logging in. Check your inbox for the verification link.",
            "email_not_verified" => true
        ]);
        exit;
    }

    // generate session token for tracking
    $session_token = bin2hex(random_bytes(32));
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $expires_at = date('Y-m-d H:i:s', time() + (86400 * 30)); // 30 days

    // Insert session into database
    $stmt_session = $pdo->prepare("INSERT INTO sessions (user_id, session_token, ip_address, user_agent, expires_at, last_active) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt_session->execute([$user['id'], $session_token, $ip_address, $user_agent, $expires_at]);

    // Set session variables
    $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'role' => $user['role']
    ];
    $_SESSION['session_token'] = $session_token;

    // Remember me logic
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $expires = time() + (86400 * 30); // expires after 30 days 

        $stmtToken = $pdo->prepare("INSERT INTO user_remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmtToken->execute([$user['id'], hash('sha256', $token), $expires]);

        setcookie('remember_token', $token, $expires, '/', '', false, true);
    }

    echo json_encode([
        "success" => true,
        "message" => "Login successful!",
        "user" => [
            "id" => $user['id'],
            "name" => $user['name'],
            "role" => $user['role']
        ]
    ]);

} catch (Exception $e) {
    // Log the error for debugging
    error_log("Login error: " . $e->getMessage());
    
    // Clear output buffering and send error response
    ob_clean();
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

ob_end_flush();
?>
