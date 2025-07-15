<?php
session_start();
header("Content-Type: application/json");
require_once '../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

// decoding the data in inputs from the json formartt
if (!isset(
    $data['email'],
    $data['password']
)) {
    echo json_encode([
        "success" => false, 
        "message" => "Email/Username and password are required"
    ]);
    exit;
}

$login_input = trim($data['email']);
$password = trim($data['password']);
$remember = isset($data['remember']) && $data['remember'] ? true : false;

if (empty($login_input) || empty($password)) {
    echo json_encode([
        "success" => false, 
        "message" => "Email/Username and password are required"
    ]);
    exit;
}

// check if input is an email or username
if (filter_var($login_input, FILTER_VALIDATE_EMAIL)) {
    $query = "SELECT id, name, email, password, role FROM users WHERE email = ?";
    $params = [$login_input];
} else {
    $query = "SELECT id, name, email, password, role FROM users WHERE name = ?";
    $params = [$login_input];
}

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

// setting session variables becouse not all users are normal users others are monitors and an admin the most high
$_SESSION['user'] = [
    'id' => $user['id'],
    'name' => $user['name'],
    'role' => $user['role']
];

// Remember me logic
if ($remember) {
    // Generate a random token using the bin2hex(random_bytes()) algorithm 
    $token = bin2hex(random_bytes(32));
    $expires = time() + (86400 * 30); // 30 days is better to aavoid overlsoding the db

    // DO NOT remove old tokens for this user, allow multiple tokens (one per device/browser)
    // $pdo->prepare("DELETE FROM user_remember_tokens WHERE user_id = ?")->execute([$user['id']]);

    // Store new token (each login gets its own row) this ensures the user is not logged out of all devices they are in 
    $stmtToken = $pdo->prepare("INSERT INTO user_remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmtToken->execute([$user['id'], hash('sha256', $token), $expires]);

    // set cookie thus checks if your loggin token is not expired everytime you are logged in
    setcookie('remember_token', $token, $expires, '/', '', false, true);
}

echo json_encode(
    [
        "success" => true,
        "message" => "You have successfully logged in",
        "user" => [
            "id" => $user['id'],
            "name" => $user['name'],
            "role" => $user['role']
        ]
    ]
);
