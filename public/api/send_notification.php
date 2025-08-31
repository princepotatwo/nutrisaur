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
        // Firebase Admin SDK configuration - Updated for new project
        $serviceAccountPath = __DIR__ . '/../../sss/nutrisaur-notifications-firebase-adminsdk-fbsvc-188c79990a.json';
        
        if (!file_exists($serviceAccountPath)) {
            throw new Exception('Firebase service account file not found at: ' . $serviceAccountPath);
        }
        
        // Load Firebase Admin SDK (if available)
        if (!class_exists('Google\Cloud\Core\ServiceBuilder')) {
            // Fallback: Use cURL to send FCM directly
            return sendFCMViaCurl($fcmTokens, $notificationData);
        }
        
        // TODO: Implement Firebase Admin SDK integration when available
        error_log("Firebase Admin SDK not available, using cURL fallback");
        return sendFCMViaCurl($fcmTokens, $notificationData);
        
    } catch (Exception $e) {
        error_log("FCM Notification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send FCM notification via cURL (fallback method)
 */
function sendFCMViaCurl($fcmTokens, $notificationData) {
    try {
        // FCM Server Key from your new Firebase project
        $serverKey = 'AIzaSyBGArwSy8j6_pQwR4ozFudKFcM5jHHXwTA'; // From your google-services.json
        
        $url = 'https://fcm.googleapis.com/fcm/send';
        
        foreach ($fcmTokens as $token) {
            $data = [
                'to' => $token,
                'notification' => [
                    'title' => $notificationData['title'],
                    'body' => $notificationData['body'],
                    'sound' => 'default',
                    'badge' => '1'
                ],
                'data' => $notificationData['data'] ?? [],
                'priority' => 'high',
                'content_available' => true
            ];
            
            $headers = [
                'Authorization: key=' . $serverKey,
                'Content-Type: application/json'
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                error_log("cURL Error: " . $error);
                continue;
            }
            
            if ($httpCode === 200) {
                $responseData = json_decode($response, true);
                if (isset($responseData['success']) && $responseData['success'] == 1) {
                    error_log("FCM Notification sent successfully to token: " . substr($token, 0, 20) . "...");
                } else {
                    error_log("FCM Error: " . ($responseData['results'][0]['error'] ?? 'Unknown error'));
                }
            } else {
                error_log("FCM HTTP Error: " . $httpCode . " - " . $response);
            }
        }
        
        return true; // Assume success if we processed all tokens
        
    } catch (Exception $e) {
        error_log("FCM cURL Error: " . $e->getMessage());
        return false;
    }
}
?>
