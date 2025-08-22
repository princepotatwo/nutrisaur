<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database configuration
require_once __DIR__ . '/config.php';

try {
    // Get limit parameter (default to 10)
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $limit = min(max($limit, 1), 100); // Ensure limit is between 1 and 100
    
    // Get recent notification logs
    $stmt = $conn->prepare("
        SELECT 
            id,
            event_id,
            notification_type,
            target_type,
            target_value,
            tokens_sent,
            success,
            error_message,
            created_at
        FROM notification_logs 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    
    $stmt->execute([$limit]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the logs for display
    $formattedLogs = [];
    foreach ($logs as $log) {
        $formattedLogs[] = [
            'id' => $log['id'],
            'event_id' => $log['event_id'],
            'notification_type' => ucfirst(str_replace('_', ' ', $log['notification_type'])),
            'target_type' => ucfirst($log['target_type']),
            'target_value' => $log['target_value'],
            'tokens_sent' => (int)$log['tokens_sent'],
            'success' => (bool)$log['success'],
            'error_message' => $log['error_message'],
            'created_at' => $log['created_at'],
            'created_at_formatted' => date('M j, Y g:i A', strtotime($log['created_at']))
        ];
    }
    
    echo json_encode([
        'success' => true,
        'logs' => $formattedLogs,
        'total' => count($formattedLogs),
        'limit' => $limit,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Error getting recent notification logs: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'error_code' => 'LOGS_ERROR'
    ]);
} catch (PDOException $e) {
    error_log("Database error getting recent notification logs: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error_code' => 'DATABASE_ERROR'
    ]);
}
?>
