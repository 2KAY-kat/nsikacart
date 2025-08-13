<?php
require_once '../middleware/auth_required.php';
require_once '../middleware/activity_logger.php';

// Only allow admin access
if ($current_user_role !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'rotate':
        // Rotate log files when they get too large
        $log_files = [
            '../../logs/activity.log',
            '../../logs/audit.log',
            '../../logs/debug.log',
            '../../logs/upload_errors.log'
        ];
        
        foreach ($log_files as $log_file) {
            if (file_exists($log_file) && filesize($log_file) > 10 * 1024 * 1024) { // 10MB
                $backup_file = $log_file . '.' . date('Y-m-d-H-i-s') . '.bak';
                rename($log_file, $backup_file);
                file_put_contents($log_file, ''); // Create new empty log
                
                ActivityLogger::logAudit('LOG_ROTATED', "Log file rotated: $log_file", 'INFO');
            }
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Log rotation completed'
        ]);
        break;
        
    case 'export':
        $log_type = $_GET['type'] ?? 'activity';
        $days = (int)($_GET['days'] ?? 7);
        
        $log_file = "../../logs/{$log_type}.log";
        
        if (!file_exists($log_file)) {
            echo json_encode([
                'success' => false, 
                'message' => 'Log file not found'
            ]);
            exit;
        }
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $log_type . '_export_' . date('Y-m-d') . '.log"');
        readfile($log_file);
        
        ActivityLogger::logAudit('LOG_EXPORTED', "Log exported: $log_type", 'INFO');
        exit;
        
    default:
        echo json_encode([   
            'success' => false, 
            'message' => 'Invalid action'
        ]);
}