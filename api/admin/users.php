<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

    // Pagination parameters
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(5, min(50, intval($_GET['limit'] ?? 10))); 
    $offset = ($page - 1) * $limit;

    // Optional filters
    $role = $_GET['role'] ?? null;
    $status = $_GET['status'] ?? null;
    // NEW: Add email verification filter
    $emailVerified = $_GET['email_verified'] ?? null;

    // Build base query for counting total records
    $countQuery = "SELECT COUNT(*) as total FROM users WHERE 1=1";
    $countParams = [];

    if ($role) {
        $countQuery .= " AND role = ?";
        $countParams[] = $role;
    }
    if ($status) {
        $countQuery .= " AND status = ?";
        $countParams[] = $status;
    }
    // NEW: Filter by email verification status
    if ($emailVerified !== null) {
        $countQuery .= " AND email_verified = ?";
        $countParams[] = $emailVerified === 'true' ? 1 : 0;
    }

    // Get total count
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($countParams);
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalRecords / $limit);

    // Build query for actual data with pagination
    // UPDATED: Include email_verified and verified_at columns
    $query = "SELECT id, name, /*email,*/ role, status, email_verified, admin_verified_at, created_at FROM users WHERE 1=1";
    $params = [];

    if ($role) {
        $query .= " AND role = ?";
        $params[] = $role;
    }
    if ($status) {
        $query .= " AND status = ?";
        $params[] = $status;
    }
    // NEW: Filter by email verification status
    if ($emailVerified !== null) {
        $query .= " AND email_verified = ?";
        $params[] = $emailVerified === 'true' ? 1 : 0;
    }

    // Add ORDER BY and LIMIT/OFFSET directly to the query
    $query .= " ORDER BY name DESC LIMIT " . intval($limit) . " OFFSET " . intval($offset);

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $users,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => (int)$totalRecords,
            'limit' => $limit,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ]
    ]);

} catch (PDOException $e) {
    error_log("Database Error in users.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General Error in users.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>