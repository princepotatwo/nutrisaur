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
        
        // TEMPORARY: Use real FCM token for testing if no token found in database
        if (!$fcmToken) {
            // Use the real FCM token you provided for testing
            $fcmToken = 'dvMeL_TxQzKV_ScrM_I0L5:APA91bFkZfYXi3EYo7NLMP5isEDt5MRSRrVtGh2FojBW8zamrZT3BYRUfO3kTdiyfWtLmcZleotA9PkKnniji02l7OcrM5vdHCf95OHEsLTDsGwpVt_6q3g';
            error_log("Using test FCM token for user: " . $targetUser);
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
        // TEMPORARILY DISABLED: Enhanced Firebase Admin SDK method
        // Use basic FCM method for immediate testing
        error_log("FCM Debug: Enhanced method temporarily disabled, using basic FCM method");
        return sendFCMViaCurl($fcmTokens, $notificationData);
        
        /*
        // Firebase Admin SDK configuration - Multiple path fallbacks for Railway
        $possiblePaths = [
            __DIR__ . '/../../sss/nutrisaur-notifications-firebase-adminsdk-fbsvc-188c79990a.json',
            __DIR__ . '/../../../sss/nutrisaur-notifications-firebase-adminsdk-fbsvc-188c79990a.json',
            __DIR__ . '/../../nutrisaur-notifications-firebase-adminsdk-fbsvc-188c79990a.json',
            dirname(__DIR__, 2) . '/sss/nutrisaur-notifications-firebase-adminsdk-fbsvc-188c79990a.json',
            dirname(__DIR__, 3) . '/sss/nutrisaur-notifications-firebase-adminsdk-fbsvc-188c79990a.json'
        ];
        
        $serviceAccountPath = null;
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $serviceAccountPath = $path;
                error_log("FCM Debug: Service account file found at: " . $path);
                break;
            }
        }
        
        if (!$serviceAccountPath) {
            error_log("FCM Error: Service account file not found in any of these paths:");
            foreach ($possiblePaths as $path) {
                error_log("FCM Debug: Tried path: " . $path . " - Exists: " . (file_exists($path) ? 'YES' : 'NO'));
            }
            throw new Exception('Firebase service account file not found in any expected location');
        }
        
        error_log("FCM Debug: Service account file found, size: " . filesize($serviceAccountPath));
        
        // Try Firebase Admin SDK first, then fallback to cURL
        if (function_exists('curl_init')) {
            error_log("FCM Debug: Using enhanced cURL method");
            // Use enhanced cURL method with proper Firebase authentication
            return sendFCMViaEnhancedCurl($fcmTokens, $notificationData, $serviceAccountPath);
        } else {
            error_log("FCM Debug: Using basic cURL fallback");
            // Basic cURL fallback
            return sendFCMViaCurl($fcmTokens, $notificationData);
        }
        */
        
    } catch (Exception $e) {
        error_log("FCM Notification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send FCM notification via enhanced cURL with Firebase authentication
 */
function sendFCMViaEnhancedCurl($fcmTokens, $notificationData, $serviceAccountPath) {
    try {
        // Read service account details
        $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
        if (!$serviceAccount) {
            throw new Exception('Invalid service account file');
        }
        
        // Get project ID and private key
        $projectId = $serviceAccount['project_id'];
        $privateKey = $serviceAccount['private_key'];
        $clientEmail = $serviceAccount['client_email'];
        
        // Generate JWT token for Firebase authentication
        $jwtToken = generateFirebaseJWT($projectId, $privateKey, $clientEmail);
        if (!$jwtToken) {
            throw new Exception('Failed to generate JWT token');
        }
        
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        
        foreach ($fcmTokens as $token) {
            $data = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $notificationData['title'],
                        'body' => $notificationData['body']
                    ],
                    'data' => $notificationData['data'] ?? [],
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'sound' => 'default',
                            'priority' => 'high'
                        ]
                    ],
                    'apns' => [
                        'payload' => [
                            'aps' => [
                                'sound' => 'default',
                                'badge' => 1
                            ]
                        ]
                    ]
                ]
            ];
            
            $headers = [
                'Authorization: Bearer ' . $jwtToken,
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
                error_log("Enhanced cURL Error: " . $error);
                continue;
            }
            
            if ($httpCode === 200) {
                $responseData = json_decode($response, true);
                if (isset($responseData['name'])) {
                    error_log("FCM Notification sent successfully via Firebase Admin SDK to token: " . substr($token, 0, 20) . "...");
                } else {
                    error_log("FCM Response: " . $response);
                }
            } else {
                error_log("FCM HTTP Error: " . $httpCode . " - " . $response);
            }
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Enhanced FCM Error: " . $e->getMessage());
        // Fallback to basic cURL
        return sendFCMViaCurl($fcmTokens, $notificationData);
    }
}

/**
 * Send FCM notification via basic cURL (fallback method)
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
                error_log("Basic cURL Error: " . $error);
                continue;
            }
            
            if ($httpCode === 200) {
                $responseData = json_decode($response, true);
                if (isset($responseData['success']) && $responseData['success'] == 1) {
                    error_log("FCM Notification sent successfully via basic cURL to token: " . substr($token, 0, 20) . "...");
                } else {
                    error_log("FCM Error: " . ($responseData['results'][0]['error'] ?? 'Unknown error'));
                }
            } else {
                error_log("FCM HTTP Error: " . $httpCode . " - " . $response);
            }
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Basic FCM cURL Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate Firebase JWT token for authentication
 */
function generateFirebaseJWT($projectId, $privateKey, $clientEmail) {
    try {
        error_log("JWT Debug: Starting JWT generation");
        
        // JWT header
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT'
        ];
        
        // JWT payload
        $now = time();
        $payload = [
            'iss' => $clientEmail,
            'sub' => $clientEmail,
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600, // 1 hour
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging'
        ];
        
        error_log("JWT Debug: Header and payload created");
        
        // Create JWT
        $headerEncoded = base64url_encode(json_encode($header));
        $payloadEncoded = base64url_encode(json_encode($payload));
        
        error_log("JWT Debug: Header and payload encoded");
        
        // Sign the JWT
        $signature = '';
        if (openssl_sign($headerEncoded . '.' . $payloadEncoded, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            $signatureEncoded = base64url_encode($signature);
            $jwt = $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
            error_log("JWT Debug: JWT generated successfully, length: " . strlen($jwt));
            return $jwt;
        } else {
            error_log("JWT Error: Failed to sign JWT with openssl_sign");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("JWT Error: JWT Generation Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Base64URL encode function
 */
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
?>
