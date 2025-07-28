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
header('Content-Type: application/json; charset=utf-8');

// capture any accidental output
if (ob_get_length()) ob_clean();

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

// we proceed tp hashing the password since weve checked for the existance of the email if we pass that then we proceed

$hashpassword = password_hash($password, PASSWORD_DEFAULT);


$stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role) VALUES(?, ?, ?, ?, ?)");
$success = $stmt->execute([$name, $email, $phone, $hashpassword, $user_role]);

if ($success) {
    $newId   = $pdo->lastInsertId();
    $response = [
      'success' => true,
      'message' => 'You have successfully added a new internal user.',
      'user'    => [
        'id'   => (int)$newId,
        'name' => $name,
        'role' => $user_role
      ]
    ];
    // clean out any buffered output, emit one JSON blob, then stop
    if (ob_get_length()) { ob_clean(); }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
} else {
    echo json_encode([
        "success" => false,
        "message" => "Something went wrong, please try again later"
    ]);
}
