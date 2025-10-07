<?php
require_once __DIR__ . '/../../helpers/env.php';

// Load local .env if exists
loadEnv(__DIR__ . '/../../.env');

try {
    $host = env('DB_HOST', 'localhost');
    $port = env('DB_PORT', 3306);
    $db   = env('DB_NAME', 'nsikacart');
    $user = env('DB_USER', 'root');
    $pass = env('DB_PASS', '');

    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    throw new Exception("Database connection failed");
}
?>
