<?php
require_once '../config/db.php';
require_once '../middleware/auth_required.php';

// Check if user is admin or monitor
session_start();
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] !== 'admin' && $_SESSION['user']['role'] !== 'monitor')) {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'message' => 'Access denied'
    ]);
    exit;
}

try {
    
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
        'success' => true,
        'stats' => [
            'totalUsers' => $totalUsers,
            'totalProducts' => $totalProducts,
            'activeSessions' => $activeSessions,
            'activeUsers' => $activeUsers
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database error'
    ]);
}
?>