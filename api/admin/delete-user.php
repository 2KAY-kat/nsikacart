<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

// requiresa and headers/json
require_once '../config/db.php';
header('Content-Type: application/json');

try {
    // check if user is authenticated and has admin privileges only admins are allowed to delete users
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Authentication required"
        ]);
        exit;
    }

    // check if user has admin role for the above comment 
    $userRole = $_SESSION['user']['role'] ?? '';
    if ($userRole !== 'admin') {
        http_response_code(403);
        echo json_encode([
            "success" => false,
            "message" => "Access denied. Only administrators can delete users."
        ]);
        exit;
    }

    // all data is required to be in json format this check makes sure of that
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['user_id'] ?? null;

    if (!$userId) { // check is the user's id is rendered for delete
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "User ID is required"
        ]);
        exit;
    }

    // no one deletes themselves
    if ($userId == $_SESSION['user']['id']) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "You cannot delete your own account"
        ]);
        exit;
    }

    // begginns the processign delete 
    $pdo->beginTransaction();

    try {
        // gets user details for logging before deletion
        $stmt = $pdo->prepare("SELECT name, email, role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userToDelete = $stmt->fetch(PDO::FETCH_ASSOC);

        // of we get bad or inexistance details we rollback and giev feedback
        if (!$userToDelete) {
            $pdo->rollback();
            echo json_encode([
                "success" => false,
                "message" => "User not found"
            ]);
            exit;
        }

        // seleting the user's products first using foreign key constraints refences
        $stmt = $pdo->prepare("DELETE FROM products WHERE user_id = ?");
        $stmt->execute([$userId]);

        // then we remove the user its esy since ni users haev avatars or profile pics
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);

        if ($stmt->rowCount() > 0) { // returns the number of rows affected by the last SQL statement thid ensures that we havent affect unintended rows
            $pdo->commit(); // finishings
            
            // log the deletion for audit purposes futre wise
            $currentUserName = $_SESSION['user']['name'] ?? 'Unknown';
            error_log("Admin {$currentUserName} (ID: {$_SESSION['user']['id']}) deleted user {$userToDelete['name']} (ID: $userId, Role: {$userToDelete['role']})");
            
            // feed back
            echo json_encode([
                "success" => true,
                "message" => "User deleted successfully"
            ]);
        } else { // but if its failed we rolback
            $pdo->rollback();
            echo json_encode([
                "success" => false,
                "message" => "Failed to delete user"
            ]);
        }
    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Delete User Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Internal server error"
    ]);
}
?>