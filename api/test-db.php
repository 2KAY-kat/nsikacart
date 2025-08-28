<?php
require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json');

try {
    $pdo->query('SELECT 1');
    echo json_encode([
        'success' => true, 
        'message' => 'Database connection successful',
        'config' => [
            'host' => env('DB_HOST'),
            'database' => env('DB_NAME')
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
}