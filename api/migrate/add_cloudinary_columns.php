<?php
// migration key for altering tables 
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/php_errors.log');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Require migration key in env
$migKey = getenv('MIGRATION_KEY');
if (!$migKey) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'MIGRATION_KEY not configured on server']);
    exit;
}

// Accept key via POST or header
$provided = $_POST['key'] ?? ($_SERVER['HTTP_X_MIGRATE_KEY'] ?? null);
if (!$provided || !hash_equals($migKey, $provided)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/db.php'; // expects $pdo

$logFile = __DIR__ . '/../../logs/migration.log';
function migLog($msg) {
    global $logFile;
    @file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $msg . PHP_EOL, FILE_APPEND);
}

try {
    migLog('Migration request started');

    // check existence of columns
    $cols = [
        'main_image_public_id' => "VARCHAR(255) NULL AFTER main_image",
        'images_public_ids' => "TEXT NULL AFTER images"
    ];

    $results = [];
    foreach ($cols as $col => $definition) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as cnt
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'products'
              AND COLUMN_NAME = :col
        ");
        $stmt->execute([':col' => $col]);
        $exists = (int)$stmt->fetchColumn() > 0;
        if ($exists) {
            $results[$col] = 'exists';
            migLog("Column {$col} already exists");
            continue;
        }

        // perform ALTER
        $sql = "ALTER TABLE products ADD COLUMN {$col} {$definition}";
        $pdo->exec($sql);
        $results[$col] = 'added';
        migLog("Column {$col} added");
    }

    migLog('Migration completed');
    echo json_encode(['success' => true, 'results' => $results]);
    exit;
} catch (Exception $e) {
    migLog('Migration failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Migration failed', 'error' => $e->getMessage()]);
    exit;
}
?>