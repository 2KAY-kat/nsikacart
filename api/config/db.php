<?php
require_once '../../helpers/env.php';

// Load environment variables
loadEnv('../../.env');

$host = env('DB_HOST');
$dbname = env('DB_NAME');
$username = env('DB_USER');
$password = env('DB_PASS');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    throw new Exception("Database connection failed");
}
?>