<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database configuration
require_once __DIR__ . '/../config.php';

// Function to automatically register/update FCM token
function autoRegisterFCMToken($conn, $fcmToken, $deviceInfo) {
    try {
        // Check if token already exists
        $checkStmt = $conn->prepare("SELECT id FROM fcm_tokens WHERE fcm_token = ?");
        $checkStmt->execute([$fcmToken]);
        $existingToken = $checkStmt->fetch();
        
        if ($existingToken) {
            // Update existing token with new device info
            $stmt = $conn->prepare("
                UPDATE fcm_tokens 
                SET device_name = ?, user_email = ?, updated_at = NOW() 
                WHERE fcm_token = ?
            ");
            $stmt->execute([
                $deviceInfo['device_name'] ?? 'Unknown Device',
                $deviceInfo['user_email'] ?? 'unknown@example.com',
                $fcmToken
            ]);
            
            return [
                'success' => true,
                'action' => 'updated',
                'message' => 'FCM token updated successfully',
                'token_id' => $existingToken['id']
            ];
        } else {
            // Check if this device already has a token for this user email
            if (!empty($deviceInfo['user_email']) && $deviceInfo['user_email'] !== 'unknown@example.com') {
                $deviceCheckStmt = $conn->prepare("SELECT id, fcm_token FROM fcm_tokens WHERE user_email = ? AND device_name = ?");
                $deviceCheckStmt->execute([$deviceInfo['user_email'], $deviceInfo['device_name'] ?? 'Unknown Device']);
                $existingDeviceToken = $deviceCheckStmt->fetch();
                
                if ($existingDeviceToken) {
                    // This device already has a token for this user, update it
                    $stmt = $conn->prepare("
                        UPDATE fcm_tokens 
                        SET fcm_token = ?, updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $fcmToken,
                        $existingDeviceToken['id']
                    ]);
                    
                    return [
                        'success' => true,
                        'action' => 'updated_device',
                        'message' => 'FCM token updated for existing device user',
                        'token_id' => $existingDeviceToken['id']
                    ];
                }
            }
            // Insert new token
            $stmt = $conn->prepare("
                INSERT INTO fcm_tokens (fcm_token, device_name, user_email, created_at, updated_at) 
                VALUES (?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                $fcmToken,
                $deviceInfo['device_name'] ?? 'Unknown Device',
                $deviceInfo['user_email'] ?? 'unknown@example.com'
            ]);
            
            $tokenId = $conn->lastInsertId();
            
            return [
                'success' => true,
                'action' => 'registered',
                'message' => 'FCM token registered successfully',
                'token_id' => $tokenId
            ];
        }
    } catch(PDOException $e) {
        error_log("Database error in autoRegisterFCMToken: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ];
    }
}

// Handle POST request for token registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            // Try to get from POST data if JSON fails
            $input = $_POST;
        }
        
        // Validate required fields
        if (empty($input['fcm_token'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'FCM token is required'
            ]);
            exit;
        }
        
        $fcmToken = trim($input['fcm_token']);
        $deviceInfo = [
            'device_name' => $input['device_name'] ?? 'Android Device',
            'user_email' => $input['user_email'] ?? 'user@nutrisaur.com',
            'app_version' => $input['app_version'] ?? '1.0.0',
            'android_version' => $input['android_version'] ?? 'Unknown',
            'device_model' => $input['device_model'] ?? 'Unknown'
        ];
        
        // Register/update the token
        $result = autoRegisterFCMToken($conn, $fcmToken, $deviceInfo);
        
        if ($result['success']) {
            // Log successful registration
            error_log("FCM token auto-registered: " . $result['action'] . " for device: " . $deviceInfo['device_name']);
            
            echo json_encode($result);
        } else {
            http_response_code(500);
            echo json_encode($result);
        }
        
    } catch (Exception $e) {
        error_log("Error in auto FCM registration: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Server error: ' . $e->getMessage()
        ]);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Handle GET request for token status check
    try {
        if (isset($_GET['fcm_token'])) {
            $fcmToken = trim($_GET['fcm_token']);
            
            $stmt = $conn->prepare("SELECT * FROM fcm_tokens WHERE fcm_token = ?");
            $stmt->execute([$fcmToken]);
            $token = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($token) {
                echo json_encode([
                    'success' => true,
                    'token_exists' => true,
                    'token_info' => $token
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'token_exists' => false,
                    'message' => 'Token not found'
                ]);
            }
        } else {
            // Return token count
            $stmt = $conn->query("SELECT COUNT(*) as count FROM fcm_tokens");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'total_tokens' => $result['count'],
                'message' => 'FCM token status check'
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Error checking FCM token status: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Server error: ' . $e->getMessage()
        ]);
    }
    
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use POST to register tokens or GET to check status.'
    ]);
}
?>
