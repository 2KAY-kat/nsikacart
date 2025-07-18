<?php
// Temporary debug file to test the delete endpoint
session_start();

header('Content-Type: text/plain');

// Basic debug info
echo "=== DEBUG INFO ===\n";
echo "Session ID: " . session_id() . "\n";
echo "User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET') . "\n";
echo "Request Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'NOT SET') . "\n";
echo "PHP Session Save Path: " . session_save_path() . "\n";
echo "Session Cookie Name: " . session_name() . "\n";

// Show all session data
echo "\n=== SESSION DATA ===\n";
if (empty($_SESSION)) {
    echo "Session is empty\n";
} else {
    print_r($_SESSION);
}

// Show cookies
echo "\n=== COOKIES ===\n";
if (empty($_COOKIE)) {
    echo "No cookies found\n";
} else {
    print_r($_COOKIE);
}

// Test database connection
echo "\n=== DATABASE TEST ===\n";
try {
    require_once '../config/db.php';
    echo "Database connection: OK\n";
    
    if (isset($pdo)) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM products");
        echo "Products count: " . $stmt->fetchColumn() . "\n";
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        echo "Users count: " . $stmt->fetchColumn() . "\n";
    }
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}

// If POST request, show input data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "\n=== POST DATA ===\n";
    $input = file_get_contents('php://input');
    echo "Raw Input: " . $input . "\n";
    
    if ($input) {
        $decoded = json_decode($input, true);
        echo "Decoded JSON: " . print_r($decoded, true) . "\n";
        echo "JSON Error: " . json_last_error_msg() . "\n";
    }
}

// Check if the request method is DELETE
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    echo "Request Method is DELETE.\n";
    // Handle the delete operation here
    // For example, you can access the data sent with the DELETE request
    parse_str(file_get_contents("php://input"), $_DELETE);
    // Debug the received data
    echo "Received DELETE data:\n";
    print_r($_DELETE);
} else {
    echo "Request Method is not DELETE. It is: " . $_SERVER['REQUEST_METHOD'] . "\n";
}
?>