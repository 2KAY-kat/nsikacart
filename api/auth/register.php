<?php
header("Content-Type: application/json");
include "../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

// we start validating the iputs on data here 

if (!isset($data['name'], $data['email'], $data['password'], $data['confirm_password'])) {
    echo json_encode(["success" => false, "message" => "All fields are required"]);
    exit;
}

$name = trim($data['name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$email = trim($data['email'], FILTER_SANITIZE_EMAIL);
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

// Password match check
if ($password !== $confirm_password) {
    echo json_encode([
        "success" => false, 
        "message" => "Passwords do not match"
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

// upon successful hashing we proceed to inserting the data into the database we goooo

$stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password) VALUES(?, ?, ?, ?)");
$success = $stmt->execute([$name, $email, $phone, $hashpassword]);

if ($success) {
    echo json_encode([
        "success" => true, 
        "message" => "You Have Successfully Registered Into Storedambwe Marketplace"
    ]);
} else {
    echo json_encode([
        "success" => false, 
        "message" => "Something went wrong, please try again later"
    ]);
}
