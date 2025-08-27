<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../config/db.php';
header('Content-Type: application/json');

try {
    // Check if user is authenticated and has admin/monitor privileges
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Authentication required"
        ]);
        exit;
    }

    // Check if user has admin or monitor role
    $userRole = $_SESSION['user']['role'] ?? '';
    if ($userRole !== 'admin' && $userRole !== 'monitor') {
        http_response_code(403);
        echo json_encode([
            "success" => false,
            "message" => "Access denied. Admin privileges required."
        ]);
        exit;
    }

    // Get total users count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get total products count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
    $totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get active sessions - sessions active in last 15 minutes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM sessions WHERE last_active > (NOW() - INTERVAL 15 MINUTE) AND is_active = 1");
    $activeSessions = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get unique active users - distinct users with active sessions
    $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) as total FROM sessions WHERE last_active > (NOW() - INTERVAL 15 MINUTE) AND is_active = 1");
    $activeUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    echo json_encode([
        "success" => true,
        "stats" => [
            "totalUsers" => $totalUsers,
            "totalProducts" => $totalProducts,
            "activeSessions" => $activeSessions,
            "activeUsers" => $activeUsers
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Statistics API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "message" => "Internal server error"
    ]);
}
?>