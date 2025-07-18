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
    // Use $pdo directly instead of getDbConnection()
    // $pdo is already created in db.php
    
    // Get total users count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get total products count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
    $totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get monthly sales (placeholder - adjust based on your sales table structure)
    $monthlySales = 0; // You'll need to implement this based on your sales/orders table
    
    // Get active sessions (simplified count)
    $activeSessions = 1; // You'll need to implement proper session tracking
    
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