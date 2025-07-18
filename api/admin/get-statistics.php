<?php
require_once '../config/db.php';
require_once '../middleware/auth_required.php';

// Check if user is admin or monitor
session_start();
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] !== 'admin' && $_SESSION['user']['role'] !== 'monitor')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

try {
    
    // Get total users count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get total products count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
    $totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get monthly sales (placeholder - adjust based on your sales table structure)
    $monthlySales = 0; // You'll need to implement this based on your sales/orders table
    
    // Get active sessions (simplified count)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM sessions WHERE last_active > NOW() - INTERVAL 15 MINUTE");
    $activeSessions = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'totalUsers' => $totalUsers,
            'totalProducts' => $totalProducts,
            'monthlySales' => $monthlySales,
            'activeSessions' => $activeSessions
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>