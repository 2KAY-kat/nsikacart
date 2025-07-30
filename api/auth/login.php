<?php
// Start output buffering and clear any previous output
ob_start();
ob_clean();

session_start();
header("Content-Type: application/json");
header("Cache-Control: no-cache, must-revalidate");

require_once '../config/db.php';

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Only POST requests allowed");
    }

    // Get raw input
    $raw_input = file_get_contents("php://input");
    
    // Log the raw input for debugging (remove in production)
    error_log("Raw login input: " . $raw_input);
    
    // Check if input is empty
    if (empty(trim($raw_input))) {
        throw new Exception("No data received");
    }
    
    // Decode JSON
    $data = json_decode($raw_input, true);

    // Check for JSON decode errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON data: " . json_last_error_msg());
    }
    
    // Check if data is array
    if (!is_array($data)) {
        throw new Exception("Invalid data format");
    }

    // Validate required fields
    if (!isset($data['email']) || !isset($data['password'])) {
        throw new Exception("Email/Username and password are required");
    }

    $login_input = trim($data['email']);
    $password = trim($data['password']);
    $remember = isset($data['remember']) && $data['remember'] ? true : false;

    if (empty($login_input) || empty($password)) {
        throw new Exception("Email/Username and password cannot be empty");
    }

    // Check if input is an email or username
    if (filter_var($login_input, FILTER_VALIDATE_EMAIL)) {
        $query = "SELECT id, name, email, password, role, email_verified FROM users WHERE email = ?";
        $params = [$login_input];
    } else {
        $query = "SELECT id, name, email, password, role, email_verified FROM users WHERE name = ?";
        $params = [$login_input];
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        throw new Exception("Invalid username/email or password");
    }

    // Check if email is verified
    if (!$user['email_verified']) {
        echo json_encode([
            "success" => false,
            "message" => "Please verify your email address before logging in. Check your inbox for the verification link.",
            "email_not_verified" => true
        ]);
        exit;
    }

    // Set session variables
    $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'role' => $user['role']
    ];

    // Remember me logic
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $expires = time() + (86400 * 30);

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
