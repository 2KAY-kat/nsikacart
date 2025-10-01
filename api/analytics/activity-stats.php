<?php
// Clean output buffer and prevent any HTML output
ob_start();
ob_clean();

// Turn off error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    require_once '../middleware/auth_required.php';

    // Only allow admin/monitor access
    if ($current_user_role !== 'admin' && $current_user_role !== 'monitor') {
        ob_clean();
        http_response_code(403);
        echo json_encode([
            'success' => false, 
            'message' => 'Access denied'
        ]);
        exit;
    }

    function parseActivityLogs($days = 30) {
        $log_file = dirname(__DIR__, 2) . '/logs/activity.log';
        
        if (!file_exists($log_file)) {
            return [];
        }
        
        try {
            $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines === false) {
                return [];
            }
            
            $activities = [];
            $cutoff_date = date('Y-m-d H:i:s', strtotime("-$days days"));
            
            foreach ($lines as $line) {
                $parts = explode(' | ', $line, 2);
                if (count($parts) === 2) {
                    $timestamp = trim($parts[0]);
                    if ($timestamp >= $cutoff_date) {
                        $data = json_decode(trim($parts[1]), true);
                        if ($data && json_last_error() === JSON_ERROR_NONE) {
                            $activities[] = $data;
                        }
                    }
                }
            }
            
            return array_reverse($activities); // Most recent first
        } catch (Exception $e) {
            error_log('Error parsing activity logs: ' . $e->getMessage());
            return [];
        }
    }

    function generateStats($activities) {
        $stats = [
            'total_activities' => count($activities),
            'unique_users' => count(array_unique(array_column($activities, 'user_id'))),
            'actions_breakdown' => [],
            'hourly_distribution' => array_fill(0, 24, 0),
            'daily_distribution' => [],
            'top_users' => [],
            'recent_activities' => array_slice($activities, 0, 20),
            'user_agents' => [],
            'endpoints' => []
        ];
        
        foreach ($activities as $activity) {
            try {
                // Actions breakdown
                $action = $activity['action'] ?? 'unknown';
                $stats['actions_breakdown'][$action] = ($stats['actions_breakdown'][$action] ?? 0) + 1;
                
                // Hourly distribution
                if (isset($activity['timestamp'])) {
                    $hour = (int)date('H', strtotime($activity['timestamp']));
                    if ($hour >= 0 && $hour < 24) {
                        $stats['hourly_distribution'][$hour]++;
                    }
                }
                
                // Daily distribution
                if (isset($activity['timestamp'])) {
                    $date = date('Y-m-d', strtotime($activity['timestamp']));
                    $stats['daily_distribution'][$date] = ($stats['daily_distribution'][$date] ?? 0) + 1;
                }
                
                // Top users
                $user_id = $activity['user_id'] ?? 'anonymous';
                $stats['top_users'][$user_id] = ($stats['top_users'][$user_id] ?? 0) + 1;
                
                // User agents (browsers)
                $user_agent = $activity['user_agent'] ?? 'unknown';
                // Extract browser name
                if (strpos($user_agent, 'Chrome') !== false) $browser = 'Chrome';
                elseif (strpos($user_agent, 'Firefox') !== false) $browser = 'Firefox';
                elseif (strpos($user_agent, 'Safari') !== false && strpos($user_agent, 'Chrome') === false) $browser = 'Safari';
                elseif (strpos($user_agent, 'Edge') !== false || strpos($user_agent, 'Edg') !== false) $browser = 'Edge';
                else $browser = 'Other';
                
                $stats['user_agents'][$browser] = ($stats['user_agents'][$browser] ?? 0) + 1;
                
                // Popular endpoints
                if (isset($activity['details']['endpoint'])) {
                    $endpoint = $activity['details']['endpoint'];
                    // Clean up endpoint for better readability
                    $endpoint = preg_replace('/\?.*/', '', $endpoint); // Remove query parameters
                    $stats['endpoints'][$endpoint] = ($stats['endpoints'][$endpoint] ?? 0) + 1;
                }
            } catch (Exception $e) {
                error_log('Error processing activity: ' . $e->getMessage());
                continue;
            }
        }
        
        // Sort data
        arsort($stats['top_users']);
        arsort($stats['actions_breakdown']);
        arsort($stats['user_agents']);
        arsort($stats['endpoints']);
        
        // Limit results
        $stats['top_users'] = array_slice($stats['top_users'], 0, 10, true);
        $stats['endpoints'] = array_slice($stats['endpoints'], 0, 10, true);
        
        return $stats;
    }

    $days = (int)($_GET['days'] ?? 30);
    if ($days < 1) $days = 30;
    if ($days > 365) $days = 365;
    
    $activities = parseActivityLogs($days);
    $stats = generateStats($activities);
    
    // Clean any output before sending JSON
    ob_clean();
    
    echo json_encode([
        'success' => true,
        'data' => $stats,
        'period_days' => $days
    ]);

} catch (Exception $e) {
    // Log the error but don't expose it to the client
    error_log('Analytics error: ' . $e->getMessage());
    
    // Clean output and send error response
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Internal server error'
    ]);
}

ob_end_flush();
?>