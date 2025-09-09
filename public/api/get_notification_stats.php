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
require_once __DIR__ . '/../config.php';

try {
    // Get FCM token statistics
    $tokenStats = [];
    
    // Total tokens
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM community_users WHERE fcm_token IS NOT NULL AND fcm_token != ''");
    $stmt->execute();
    $tokenStats['total_tokens'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Active tokens
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM community_users WHERE fcm_token IS NOT NULL AND fcm_token != '' AND status = 'active'");
    $stmt->execute();
    $tokenStats['active_tokens'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Tokens with location info
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM community_users WHERE fcm_token IS NOT NULL AND fcm_token != '' AND barangay IS NOT NULL AND barangay != ''");
    $stmt->execute();
    $tokenStats['tokens_with_location'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Tokens by platform (not available in community_users, set to empty)
    $tokenStats['tokens_by_platform'] = [];
    
    // Get notification statistics
    $notificationStats = [];
    
    // Total notifications sent
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notification_logs");
    $stmt->execute();
    $notificationStats['total_notifications'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Successful notifications
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notification_logs WHERE success = TRUE");
    $stmt->execute();
    $notificationStats['successful_notifications'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Failed notifications
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notification_logs WHERE success = FALSE");
    $stmt->execute();
    $notificationStats['failed_notifications'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Calculate success rate
    if ($notificationStats['total_notifications'] > 0) {
        $notificationStats['success_rate'] = round(
            ($notificationStats['successful_notifications'] / $notificationStats['total_notifications']) * 100, 
            1
        );
    } else {
        $notificationStats['success_rate'] = 0;
    }
    
    // Notifications by type
    $stmt = $conn->prepare("SELECT notification_type, COUNT(*) as count FROM notification_logs GROUP BY notification_type");
    $stmt->execute();
    $notificationStats['by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Notifications by target type
    $stmt = $conn->prepare("SELECT target_type, COUNT(*) as count FROM notification_logs GROUP BY target_type");
    $stmt->execute();
    $notificationStats['by_target'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Total tokens sent
    $stmt = $conn->prepare("SELECT SUM(tokens_sent) as total FROM notification_logs");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $notificationStats['total_tokens_sent'] = $result['total'] ?: 0;
    
    // Recent activity (last 24 hours)
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notification_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stmt->execute();
    $notificationStats['notifications_24h'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Combine all statistics
    $stats = [
        'fcm_tokens' => $tokenStats,
        'notifications' => $notificationStats,
        'summary' => [
            'total_tokens' => $tokenStats['total_tokens'],
            'active_tokens' => $tokenStats['active_tokens'],
            'total_notifications' => $notificationStats['total_notifications'],
            'success_rate' => $notificationStats['success_rate'],
            'tokens_with_location' => $tokenStats['tokens_with_location'],
            'notifications_24h' => $notificationStats['notifications_24h']
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Error getting notification stats: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'error_code' => 'STATS_ERROR'
    ]);
} catch (PDOException $e) {
    error_log("Database error getting notification stats: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error_code' => 'DATABASE_ERROR'
    ]);
}
?>
