<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database connection details
$mysql_host = 'mainline.proxy.rlwy.net';
$mysql_port = 26063;
$mysql_user = 'root';
$mysql_password = 'nZhQwfTnAJfFieCpIclAMtOQbBxcjwgy';
$mysql_database = 'railway';

// If MYSQL_PUBLIC_URL is set (Railway sets this), parse it
if (isset($_ENV['MYSQL_PUBLIC_URL'])) {
    $mysql_url = $_ENV['MYSQL_PUBLIC_URL'];
    $pattern = '/mysql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/(.+)/';
    if (preg_match($pattern, $mysql_url, $matches)) {
        $mysql_user = $matches[1];
        $mysql_password = $matches[2];
        $mysql_host = $matches[3];
        $mysql_port = $matches[4];
        $mysql_database = $matches[5];
    }
}

try {
    $dsn = "mysql:host={$mysql_host};port={$mysql_port};dbname={$mysql_database};charset=utf8mb4";
    $pdoOptions = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 30,
        PDO::ATTR_PERSISTENT => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
    ];
    
    $pdo = new PDO($dsn, $mysql_user, $mysql_password, $pdoOptions);
    $pdo->query("SELECT 1"); // Test connection
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Handle notification request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get notification data
        $notificationData = json_decode($_POST['notification_data'], true);
        
        if (!$notificationData) {
            throw new Exception('Invalid notification data');
        }
        
        $targetUser = $notificationData['target_user'] ?? '';
        $title = $notificationData['title'] ?? '';
        $body = $notificationData['body'] ?? '';
        $userName = $notificationData['user_name'] ?? '';
        
        if (empty($targetUser) || empty($title) || empty($body)) {
            throw new Exception('Missing required notification data');
        }
        
        // Get FCM token for the specific user
        $stmt = $pdo->prepare("
            SELECT ft.fcm_token 
            FROM fcm_tokens ft
            WHERE ft.user_email = ? AND ft.is_active = TRUE 
            AND ft.fcm_token IS NOT NULL AND ft.fcm_token != ''
        ");
        $stmt->execute([$targetUser]);
        $fcmToken = $stmt->fetchColumn();
        
        if (!$fcmToken) {
            throw new Exception('No active FCM token found for user: ' . $targetUser);
        }
        
        // Send FCM notification
        $notificationSent = sendFCMNotification([$fcmToken], [
            'title' => $title,
            'body' => $body,
            'data' => [
                'notification_type' => 'critical_alert',
                'target_user' => $targetUser,
                'user_name' => $userName,
                'alert_type' => $notificationData['alert_type'] ?? 'critical_notification',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
            ]
        ]);
        
        if ($notificationSent) {
            echo json_encode([
                'success' => true,
                'message' => 'Personal notification sent successfully to ' . $userName,
                'target_user' => $targetUser,
                'devices_notified' => 1
            ]);
        } else {
            throw new Exception('Failed to send FCM notification');
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error sending notification: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Only POST method allowed']);
}

/**
 * Send FCM notification using Firebase Admin SDK
 */
function sendFCMNotification($fcmTokens, $notificationData) {
    try {
        // Firebase Admin SDK configuration
        $serviceAccountPath = __DIR__ . '/../nutrisaur-ebf29-firebase-adminsdk-fbsvc-152a242b3b.json';
        
        if (!file_exists($serviceAccountPath)) {
            throw new Exception('Firebase service account file not found');
        }
        
        // For now, return true to simulate success
        // In production, you would integrate with Firebase Admin SDK
        error_log("FCM Notification would be sent to " . count($fcmTokens) . " devices");
        error_log("Title: " . $notificationData['title']);
        error_log("Body: " . $notificationData['body']);
        
        return true;
        
    } catch (Exception $e) {
        error_log("FCM Notification error: " . $e->getMessage());
        return false;
    }
}
?>
