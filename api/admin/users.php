<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../config/db.php';
header('Content-Type: application/json');

try {
    // Check if user is authenticated and has admin privileges
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required'
        ]);
        exit;
    }

    // Check if user has admin or monitor role
    $userRole = $_SESSION['user']['role'] ?? '';
    if ($userRole !== 'admin' && $userRole !== 'monitor') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Access denied. Admin privileges required.'
        ]);
        exit;
    }

    // Optional filters
    $role = $_GET['role'] ?? null;
    $status = $_GET['status'] ?? null;

    // Build query dynamically
    $query = "SELECT id, name as username, email, role, status, created_at FROM users WHERE 1=1";
    $params = [];

    if ($role) {
        $query .= " AND role = ?";
        $params[] = $role;
    }
    if ($status) {
        $query .= " AND status = ?";
        $params[] = $status;
    }

    $query .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $users
    ]);

} catch (Exception $e) {
    error_log("Users API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
}
?>
