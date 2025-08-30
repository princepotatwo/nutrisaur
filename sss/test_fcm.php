<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once __DIR__ . '/../public/config.php';

// Get database connection
$conn = getDatabaseConnection();

// Function to safely execute database queries
function safeDbQuery($conn, $query, $params = []) {
    try {
        if ($conn instanceof PDO) {
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } else {
            // Fallback for mysqli
            $stmt = $conn->prepare($query);
            if (!empty($params)) {
                $stmt->bind_param(str_repeat('s', count($params)), ...$params);
            }
            $stmt->execute();
            return $stmt;
        }
    } catch (Exception $e) {
        error_log("Database query error: " . $e->getMessage());
        return false;
    }
}

// Function to send FCM notification
function sendFCMNotification($tokens, $notificationData) {
    try {
        // Try to get Firebase credentials from multiple sources
        $firebaseCredentials = null;
        
        // Method 1: Try to find Firebase Admin SDK JSON file
        $possiblePaths = [
            __DIR__ . '/nutrisaur-ebf29-firebase-adminsdk-fbsvc-152a242b3b.json',
            __DIR__ . '/../public/api/nutrisaur-ebf29-firebase-adminsdk-fbsvc-152a242b3b.json',
            '/var/www/html/sss/nutrisaur-ebf29-firebase-adminsdk-fbsvc-152a242b3b.json',
            '/var/www/html/public/api/nutrisaur-ebf29-firebase-adminsdk-fbsvc-152a242b3b.json'
        ];
        
        // Also try to find any firebase admin SDK file dynamically
        $dynamicPaths = [];
        $searchDirs = [__DIR__, __DIR__ . '/../public/api', '/var/www/html/sss', '/var/www/html/public/api'];
        foreach ($searchDirs as $dir) {
            if (is_dir($dir)) {
                $files = glob($dir . '/*firebase*admin*.json');
                $dynamicPaths = array_merge($dynamicPaths, $files);
            }
        }
        
        $allPaths = array_merge($possiblePaths, $dynamicPaths);
        error_log("Searching for Firebase Admin SDK in directories: " . implode(', ', $searchDirs));
        error_log("Dynamic Firebase files found: " . implode(', ', $dynamicPaths));
        
        // Try to find existing file
        foreach ($allPaths as $path) {
            if (file_exists($path)) {
                $firebaseCredentials = json_decode(file_get_contents($path), true);
                error_log("Firebase Admin SDK file found at: $path");
                break;
            }
        }
        
        // Method 2: Try to get from environment variables (Railway)
        if (!$firebaseCredentials && isset($_ENV['FIREBASE_PROJECT_ID'])) {
            error_log("Using Firebase credentials from Railway environment variables");
            
            // Fix the private key format - convert \n back to actual newlines
            $privateKey = $_ENV['FIREBASE_PRIVATE_KEY'] ?? '';
            $privateKey = str_replace('\\n', "\n", $privateKey);
            
            // Ensure the private key has proper formatting
            if (!str_contains($privateKey, '-----BEGIN PRIVATE KEY-----')) {
                error_log("Private key format issue - missing BEGIN marker");
                $privateKey = "-----BEGIN PRIVATE KEY-----\n" . $privateKey . "\n-----END PRIVATE KEY-----";
            }
            
            error_log("Private key length: " . strlen($privateKey));
            error_log("Private key starts with: " . substr($privateKey, 0, 50));
            error_log("Private key ends with: " . substr($privateKey, -50));
            
            $firebaseCredentials = [
                'type' => 'service_account',
                'project_id' => $_ENV['FIREBASE_PROJECT_ID'],
                'private_key_id' => $_ENV['FIREBASE_PRIVATE_KEY_ID'],
                'private_key' => $privateKey,
                'client_email' => $_ENV['FIREBASE_CLIENT_EMAIL'],
                'client_id' => $_ENV['FIREBASE_CLIENT_ID'],
                'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
                'token_uri' => 'https://oauth2.googleapis.com/token',
                'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
                'client_x509_cert_url' => $_ENV['FIREBASE_CLIENT_CERT_URL'] ?? ''
            ];
        }
        
        if ($firebaseCredentials) {
            error_log("Firebase credentials found, attempting to send FCM notification");
            try {
                $result = sendFCMWithCredentials($tokens, $notificationData, $firebaseCredentials);
                error_log("FCM sendFCMWithCredentials result: " . ($result ? 'true' : 'false'));
                return $result;
            } catch (Exception $e) {
                error_log("Exception in sendFCMWithCredentials: " . $e->getMessage());
                error_log("Exception trace: " . $e->getTraceAsString());
                return false;
            } catch (Error $e) {
                error_log("Error in sendFCMWithCredentials: " . $e->getMessage());
                error_log("Error trace: " . $e->getTraceAsString());
                return false;
            }
        } else {
            error_log("Firebase credentials not found in any location");
            error_log("Current directory: " . __DIR__);
            error_log("Tried paths: " . implode(', ', $allPaths));
            error_log("Environment variables checked: FIREBASE_PROJECT_ID, FIREBASE_PRIVATE_KEY_ID, FIREBASE_PRIVATE_KEY, FIREBASE_CLIENT_EMAIL, FIREBASE_CLIENT_ID");
            return false;
        }
    } catch (Exception $e) {
        error_log("Error in sendFCMNotification: " . $e->getMessage());
        return false;
    }
}

// Function to send FCM using Firebase credentials (from file or environment)
function sendFCMWithCredentials($tokens, $notificationData, $firebaseCredentials) {
    error_log("sendFCMWithCredentials called with " . count($tokens) . " tokens");
    error_log("Firebase credentials keys: " . implode(', ', array_keys($firebaseCredentials)));
    
    try {
        // Use the provided Firebase credentials
        $serviceAccount = $firebaseCredentials;
        if (!$serviceAccount || !isset($serviceAccount['project_id'])) {
            error_log("Invalid Firebase credentials");
            return false;
        }
        
        // Generate access token using service account credentials
        error_log("Attempting to generate Firebase access token");
        $accessToken = generateAccessToken($serviceAccount);
        if (!$accessToken) {
            error_log("Failed to generate access token");
            return false;
        }
        error_log("Firebase access token generated successfully");
        
        $successCount = 0;
        $failureCount = 0;
        
        foreach ($tokens as $token) {
            // Prepare the FCM message payload for HTTP v1 API
            $fcmPayload = [
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
                            'default_sound' => true,
                            'default_vibrate_timings' => true,
                            'default_light_settings' => true,
                            'icon' => 'ic_launcher',
                            'color' => '#4CAF50',
                            'channel_id' => 'nutrisaur_events'
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
            
            // Use Firebase HTTP v1 API
            $projectId = $serviceAccount['project_id'];
            $fcmUrl = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
            
            // Send FCM message using cURL with Admin SDK
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $fcmUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmPayload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                error_log("cURL error for token: $curlError");
                $failureCount++;
                continue;
            }
            
            if ($httpCode === 200) {
                $responseData = json_decode($response, true);
                if (isset($responseData['name'])) {
                    $successCount++;
                    error_log("FCM success for token: " . $responseData['name']);
                } else {
                    error_log("FCM response missing 'name'. Response: " . substr($response, 0, 200));
                    $failureCount++;
                }
            } else {
                error_log("FCM HTTP error $httpCode. Response: " . substr($response, 0, 200));
                $failureCount++;
            }
        }
        
        if ($successCount > 0) {
            error_log("FCM notification sent successfully to $successCount out of " . count($tokens) . " devices");
            return true;
        } else {
            $errorMsg = "FCM notification failed to send to any devices. Success: $successCount, Failures: $failureCount";
            error_log($errorMsg);
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Error in sendFCMWithAdminSDK: " . $e->getMessage());
        return false;
    }
}

// Function to generate access token from service account
function generateAccessToken($serviceAccount) {
    try {
        // Check if we have a cached token
        $cacheFile = __DIR__ . '/fcm_token_cache.json';
        if (file_exists($cacheFile)) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if ($cached && isset($cached['expires']) && $cached['expires'] > time()) {
                return $cached['token'];
            }
        }
        
        // Generate new token
        $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        
        $time = time();
        $payload = base64_encode(json_encode([
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $time + 3600,
            'iat' => $time
        ]));
        
        $signature = '';
        if (openssl_sign($header . '.' . $payload, $signature, $serviceAccount['private_key'], 'SHA256')) {
            $signature = base64_encode($signature);
        } else {
            throw new Exception('Failed to sign JWT');
        }
        
        $jwt = $header . '.' . $payload . '.' . $signature;
        
        // Exchange JWT for access token
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $tokenData = json_decode($response, true);
            if (isset($tokenData['access_token'])) {
                // Cache the token
                $cacheData = [
                    'token' => $tokenData['access_token'],
                    'expires' => $time + 3500 // Cache for slightly less than 1 hour
                ];
                file_put_contents($cacheFile, json_encode($cacheData));
                
                return $tokenData['access_token'];
            }
        }
        
        throw new Exception('Failed to get access token. HTTP: ' . $httpCode . ', Response: ' . $response);
        
    } catch (Exception $e) {
        error_log("Error generating access token: " . $e->getMessage());
        return false;
    }
}

// Handle test notification request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'test_fcm') {
    try {
        // Get all active FCM tokens
        $stmt = $conn->prepare("
            SELECT fcm_token, user_email, user_barangay 
            FROM fcm_tokens 
            WHERE is_active = TRUE 
            AND fcm_token IS NOT NULL 
            AND fcm_token != ''
            LIMIT 5
        ");
        $stmt->execute();
        $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($tokens)) {
            throw new Exception('No active FCM tokens found');
        }
        
        $fcmTokens = array_column($tokens, 'fcm_token');
        
        // Send test notification
        error_log("Attempting to send FCM notification to " . count($fcmTokens) . " tokens");
        $notificationSent = sendFCMNotification($fcmTokens, [
            'title' => '🧪 FCM Test Notification',
            'body' => 'This is a test notification from Nutrisaur FCM system! 🚀',
            'data' => [
                'notification_type' => 'test',
                'timestamp' => date('Y-m-d H:i:s'),
                'test_id' => uniqid()
            ]
        ]);
        
        if ($notificationSent) {
            $result = [
                'success' => true,
                'message' => 'Test FCM notification sent successfully!',
                'tokens_sent' => count($fcmTokens),
                'timestamp' => date('Y-m-d H:i:s')
            ];
        } else {
            error_log("FCM notification failed - sendFCMNotification returned false");
            // Get the last few error log entries to help debug
            $errorLogs = [];
            if (function_exists('error_get_last')) {
                $errorLogs[] = error_get_last();
            }
            throw new Exception('Failed to send FCM notification - check error logs for details. Error logs: ' . json_encode($errorLogs));
        }
        
    } catch (Exception $e) {
        $result = [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// Get FCM status information
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] === 'status') {
    try {
        $debugInfo = [];
        
        // Check database connection
        $debugInfo['database_connected'] = $conn ? true : false;
        
        // Check if fcm_tokens table exists
        $stmt = safeDbQuery($conn, "SHOW TABLES LIKE 'fcm_tokens'");
        $debugInfo['fcm_tokens_table_exists'] = $stmt ? $stmt->rowCount() > 0 : false;
        
        // Get FCM token count
        if ($debugInfo['fcm_tokens_table_exists']) {
            $stmt = safeDbQuery($conn, "SELECT COUNT(*) as count FROM fcm_tokens WHERE is_active = TRUE");
            $debugInfo['active_fcm_tokens'] = $stmt ? $stmt->fetchColumn() : 'Database error';
        }
        
        // Check Firebase credentials from multiple sources
        $firebaseCredentials = null;
        $credentialSource = 'none';
        
        // Method 1: Check for Firebase Admin SDK JSON file
        $possiblePaths = [
            __DIR__ . '/nutrisaur-ebf29-firebase-adminsdk-fbsvc-152a242b3b.json',
            __DIR__ . '/../public/api/nutrisaur-ebf29-firebase-adminsdk-fbsvc-152a242b3b.json',
            '/var/www/html/sss/nutrisaur-ebf29-firebase-adminsdk-fbsvc-152a242b3b.json',
            '/var/www/html/public/api/nutrisaur-ebf29-firebase-adminsdk-fbsvc-152a242b3b.json'
        ];
        
        // Also try to find any firebase admin SDK file dynamically
        $dynamicPaths = [];
        $searchDirs = [__DIR__, __DIR__ . '/../public/api', '/var/www/html/sss', '/var/www/html/public/api'];
        foreach ($searchDirs as $dir) {
            if (is_dir($dir)) {
                $files = glob($dir . '/*firebase*admin*.json');
                $dynamicPaths = array_merge($dynamicPaths, $files);
            }
        }
        
        $allPaths = array_merge($possiblePaths, $dynamicPaths);
        
        // Check for existing file
        foreach ($allPaths as $path) {
            if (file_exists($path)) {
                $firebaseCredentials = json_decode(file_get_contents($path), true);
                $credentialSource = 'file';
                break;
            }
        }
        
        // Method 2: Check for Railway environment variables
        if (!$firebaseCredentials && isset($_ENV['FIREBASE_PROJECT_ID'])) {
            $credentialSource = 'environment';
            $firebaseCredentials = [
                'project_id' => $_ENV['FIREBASE_PROJECT_ID'],
                'client_email' => $_ENV['FIREBASE_CLIENT_EMAIL'] ?? 'not_set'
            ];
        }
        
        $debugInfo['firebase_credentials_available'] = $firebaseCredentials !== null;
        $debugInfo['firebase_credential_source'] = $credentialSource;
        $debugInfo['firebase_project_id'] = $firebaseCredentials['project_id'] ?? 'not_found';
        $debugInfo['firebase_client_email'] = $firebaseCredentials['client_email'] ?? 'not_found';
        $debugInfo['all_search_directories'] = $searchDirs;
        $debugInfo['dynamic_firebase_files_found'] = $dynamicPaths;
        $debugInfo['firebase_admin_sdk_paths_tried'] = $allPaths;
        $debugInfo['current_working_directory'] = getcwd();
        $debugInfo['script_directory'] = __DIR__;
        $debugInfo['environment_variables_checked'] = ['FIREBASE_PROJECT_ID', 'FIREBASE_PRIVATE_KEY_ID', 'FIREBASE_PRIVATE_KEY', 'FIREBASE_CLIENT_EMAIL', 'FIREBASE_CLIENT_ID'];
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'debug_info' => $debugInfo,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error getting status: ' . $e->getMessage()
        ]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FCM Test - Nutrisaur</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        .status-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .test-section {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .btn {
            background: #4CAF50;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #45a049;
        }
        .btn:disabled {
            background: #cccccc;
            cursor: not-allowed;
        }
        .result {
            margin-top: 15px;
            padding: 15px;
            border-radius: 6px;
            font-family: monospace;
            white-space: pre-wrap;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 FCM Test Dashboard</h1>
            <p>Test and verify Firebase Cloud Messaging functionality</p>
        </div>
        
        <div class="status-section">
            <h2>📊 FCM System Status</h2>
            <button class="btn" onclick="checkStatus()">Check Status</button>
            <div id="statusResult" class="result" style="display: none;"></div>
        </div>
        
        <div class="test-section">
            <h2>🚀 Test FCM Notification</h2>
            <p>Send a test notification to all registered devices</p>
            <button class="btn" onclick="testFCM()">Send Test Notification</button>
            <div id="testResult" class="result" style="display: none;"></div>
        </div>
    </div>

    <script>
        async function checkStatus() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'Checking...';
            
            try {
                const response = await fetch('?action=status');
                const data = await response.json();
                
                const resultDiv = document.getElementById('statusResult');
                resultDiv.style.display = 'block';
                
                if (data.success) {
                    resultDiv.className = 'result success';
                    resultDiv.textContent = JSON.stringify(data.debug_info, null, 2);
                } else {
                    resultDiv.className = 'result error';
                    resultDiv.textContent = 'Error: ' + data.message;
                }
            } catch (error) {
                const resultDiv = document.getElementById('statusResult');
                resultDiv.style.display = 'block';
                resultDiv.className = 'result error';
                resultDiv.textContent = 'Network error: ' + error.message;
            } finally {
                btn.disabled = false;
                btn.textContent = 'Check Status';
            }
        }
        
        async function testFCM() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'Sending...';
            
            try {
                const formData = new FormData();
                formData.append('action', 'test_fcm');
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                const resultDiv = document.getElementById('testResult');
                resultDiv.style.display = 'block';
                
                if (data.success) {
                    resultDiv.className = 'result success';
                    resultDiv.textContent = JSON.stringify(data, null, 2);
                } else {
                    resultDiv.className = 'result error';
                    resultDiv.textContent = 'Error: ' + data.message;
                }
            } catch (error) {
                const resultDiv = document.getElementById('testResult');
                resultDiv.style.display = 'block';
                resultDiv.className = 'result error';
                resultDiv.textContent = 'Network error: ' + error.message;
            } finally {
                btn.disabled = false;
                btn.textContent = 'Send Test Notification';
            }
        }
    </script>
</body>
</html>
