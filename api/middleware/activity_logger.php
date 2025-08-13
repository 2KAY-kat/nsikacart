<?php

class ActivityLogger {
    private static $log_file = __DIR__ . '/../../logs/activity.log';
    private static $audit_file = __DIR__ . '/../../logs/audit.log';
    
    /**
     * Log general activity for analytics
     */
    public static function logActivity($action, $details = [], $user_id = null, $ip = null) {
        $timestamp = date('Y-m-d H:i:s');
        $user_id = $user_id ?? $_SESSION['user']['id'] ?? 'anonymous';
        $ip = $ip ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $log_entry = [
            'timestamp' => $timestamp,
            'user_id' => $user_id,
            'action' => $action,
            'ip_address' => $ip,
            'user_agent' => $user_agent,
            'details' => $details,
            'session_id' => session_id()
        ];
        
        $log_line = $timestamp . " | " . json_encode($log_entry) . "\n";
        error_log($log_line, 3, self::$log_file);
    }
    
    /**
     * Log security-critical events for auditing
     */
    public static function logAudit($event_type, $description, $severity = 'INFO', $user_id = null) {
        $timestamp = date('Y-m-d H:i:s');
        $user_id = $user_id ?? $_SESSION['user']['id'] ?? null;
        $user_name = $_SESSION['user']['name'] ?? 'anonymous';
        $user_role = $_SESSION['user']['role'] ?? 'none';
        
        $audit_entry = [
            'timestamp' => $timestamp,
            'event_type' => $event_type,
            'severity' => $severity,
            'user_id' => $user_id,
            'user_name' => $user_name,
            'user_role' => $user_role,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ];
        
        $log_line = $timestamp . " | " . $severity . " | " . json_encode($audit_entry) . "\n";
        error_log($log_line, 3, self::$audit_file);
    }
    
    /**
     * Log database operations
     */
    public static function logDatabaseActivity($operation, $table, $record_id = null, $changes = []) {
        self::logActivity('database_operation', [
            'operation' => $operation,
            'table' => $table,
            'record_id' => $record_id,
            'changes' => $changes
        ]);
    }
    
    /**
     * Log file operations
     */
    public static function logFileActivity($operation, $file_path, $file_size = null) {
        self::logActivity('file_operation', [
            'operation' => $operation,
            'file_path' => $file_path,
            'file_size' => $file_size
        ]);
    }
}