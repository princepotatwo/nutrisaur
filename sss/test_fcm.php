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
        // Use Firebase Admin SDK JSON file (working approach from localevent.php)
        $adminSdkPath = __DIR__ . '/nutrisaur-ebf29-firebase-adminsdk-fbsvc-8dc50fb07f.json';
        
        if (file_exists($adminSdkPath)) {
            return sendFCMWithAdminSDK($tokens, $notificationData, $adminSdkPath);
        } else {
            error_log("Firebase Admin SDK JSON file not found at: $adminSdkPath");
            return false;
        }
    } catch (Exception $e) {
        error_log("Error in sendFCMNotification: " . $e->getMessage());
        return false;
    }
}

// Function to send FCM using Firebase Admin SDK (working implementation from localevent.php)
function sendFCMWithAdminSDK($tokens, $notificationData, $adminSdkPath) {
    try {
        error_log("sendFCMWithAdminSDK called with " . count($tokens) . " tokens");
        
        // Read the service account JSON file
        $serviceAccount = json_decode(file_get_contents($adminSdkPath), true);
        if (!$serviceAccount || !isset($serviceAccount['project_id'])) {
            error_log("Invalid Firebase service account JSON file");
            return false;
        }
        
        error_log("Service account loaded, project_id: " . $serviceAccount['project_id']);
        
        // Generate access token using service account credentials
        error_log("Generating access token...");
        $accessToken = generateAccessToken($serviceAccount);
        if (!$accessToken) {
            error_log("Failed to generate access token");
            return false;
        }
        
        error_log("Access token generated successfully, length: " . strlen($accessToken));
        
        $successCount = 0;
        $failureCount = 0;
        
        foreach ($tokens as $token) {
            error_log("Processing token: " . substr($token, 0, 20) . "...");
            
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
            
            error_log("Sending FCM to URL: " . $fcmUrl);
            
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
            
            error_log("FCM response - HTTP Code: $httpCode, cURL Error: " . ($curlError ?: 'none'));
            if ($response) {
                error_log("FCM response body: " . substr($response, 0, 200));
            }
            
            if ($curlError) {
                error_log("cURL error: $curlError");
                $failureCount++;
                continue;
            }
            
            if ($httpCode == 200) {
                $responseData = json_decode($response, true);
                if (isset($responseData['name'])) {
                    $successCount++;
                    error_log("FCM success for token, response name: " . $responseData['name']);
                } else {
                    $failureCount++;
                    error_log("FCM response missing 'name' field");
                }
            } else {
                $failureCount++;
                error_log("FCM HTTP error $httpCode");
            }
        }
        
        error_log("FCM processing complete - Success: $successCount, Failures: $failureCount");
        return $successCount > 0;
        
    } catch (Exception $e) {
        error_log("Exception in sendFCMWithAdminSDK: " . $e->getMessage());
        error_log("Exception trace: " . $e->getTraceAsString());
        return false;
    } catch (Error $e) {
        error_log("Error in sendFCMWithAdminSDK: " . $e->getMessage());
        error_log("Error trace: " . $e->getTraceAsString());
        return false;
    }
}

// Function to generate access token using service account (working from localevent.php)
function generateAccessToken($serviceAccount) {
    try {
        // Check if we have a cached token that's still valid
        $cacheFile = __DIR__ . '/fcm_token_cache.json';
        if (file_exists($cacheFile)) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if ($cached && isset($cached['expires_at']) && $cached['expires_at'] > time()) {
                return $cached['access_token'];
            }
        }
        
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT'
        ];
        
        $time = time();
        $payload = [
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $time + 3600,
            'iat' => $time
        ];
        
        $headerEncoded = base64url_encode(json_encode($header));
        $payloadEncoded = base64url_encode(json_encode($payload));
        
        $signature = '';
        openssl_sign(
            $headerEncoded . '.' . $payloadEncoded,
            $signature,
            $serviceAccount['private_key'],
            'SHA256'
        );
        
        $signatureEncoded = base64url_encode($signature);
        $jwt = $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
        
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            error_log("cURL error in generateAccessToken: " . $curlError);
            return false;
        }
        
        if ($httpCode == 200) {
            $tokenData = json_decode($response, true);
            if (isset($tokenData['access_token'])) {
                // Cache the token for reuse
                $cacheData = [
                    'access_token' => $tokenData['access_token'],
                    'expires_at' => time() + 3500, // Cache for 58 minutes (token valid for 1 hour)
                    'created_at' => time()
                ];
                file_put_contents($cacheFile, json_encode($cacheData));
                
                return $tokenData['access_token'];
            }
        }
        
        error_log("Failed to generate access token. HTTP Code: " . $httpCode . " Response: " . $response);
        return false;
        
    } catch (Exception $e) {
        error_log("Error generating access token: " . $e->getMessage());
        return false;
    }
}

// Helper function for base64url encoding (from localevent.php)
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
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
                $lastError = error_get_last();
                if ($lastError !== null) {
                    $errorLogs[] = $lastError;
                }
            }
            
            // Get more detailed error information
            $errorDetails = [
                'sendFCMNotification_returned' => false,
                'last_error' => $errorLogs,
                'current_time' => date('Y-m-d H:i:s'),
                'memory_usage' => memory_get_usage(true),
                'peak_memory' => memory_get_peak_usage(true)
            ];
            
            throw new Exception('Failed to send FCM notification - check error logs for details. Error details: ' . json_encode($errorDetails));
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

// Diagnostic action to help debug FCM issues
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] === 'diagnose') {
    try {
        $diagnosticInfo = [];
        
        // Check environment variables
        $diagnosticInfo['environment'] = [
            'FIREBASE_PROJECT_ID' => $_ENV['FIREBASE_PROJECT_ID'] ?? 'not_set',
            'FIREBASE_CLIENT_EMAIL' => $_ENV['FIREBASE_CLIENT_EMAIL'] ?? 'not_set',
            'FIREBASE_PRIVATE_KEY_ID' => $_ENV['FIREBASE_PRIVATE_KEY_ID'] ?? 'not_set',
            'FIREBASE_CLIENT_ID' => $_ENV['FIREBASE_CLIENT_ID'] ?? 'not_set',
            'FIREBASE_PRIVATE_KEY_length' => isset($_ENV['FIREBASE_PRIVATE_KEY']) ? strlen($_ENV['FIREBASE_PRIVATE_KEY']) : 'not_set',
            'FIREBASE_PRIVATE_KEY_starts_with' => isset($_ENV['FIREBASE_PRIVATE_KEY']) ? substr($_ENV['FIREBASE_PRIVATE_KEY'], 0, 50) : 'not_set',
            'FIREBASE_PRIVATE_KEY_ends_with' => isset($_ENV['FIREBASE_PRIVATE_KEY']) ? substr($_ENV['FIREBASE_PRIVATE_KEY'], -50) : 'not_set'
        ];
        
        // Check file system
        $diagnosticInfo['file_system'] = [
            'current_directory' => getcwd(),
            'script_directory' => __DIR__,
            'script_exists' => file_exists(__FILE__),
            'config_exists' => file_exists(__DIR__ . '/../public/config.php')
        ];
        
        // Check Firebase Admin SDK files
        $firebaseFiles = [];
        $searchDirs = [__DIR__, __DIR__ . '/../public/api', '/var/www/html/sss', '/var/www/html/public/api'];
        foreach ($searchDirs as $dir) {
            if (is_dir($dir)) {
                $files = glob($dir . '/*firebase*admin*.json');
                foreach ($files as $file) {
                    $firebaseFiles[] = [
                        'path' => $file,
                        'exists' => file_exists($file),
                        'readable' => is_readable($file),
                        'size' => file_exists($file) ? filesize($file) : 'N/A'
                    ];
                }
            }
        }
        $diagnosticInfo['firebase_files'] = $firebaseFiles;
        
        // Check database connection
        $diagnosticInfo['database'] = [
            'connected' => $conn ? true : false,
            'connection_type' => $conn ? get_class($conn) : 'none'
        ];
        
        // Check FCM tokens
        if ($conn) {
            try {
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_fcm_tokens WHERE fcm_token IS NOT NULL AND fcm_token != ''");
                $stmt->execute();
                $diagnosticInfo['fcm_tokens'] = [
                    'active_count' => $stmt->fetchColumn(),
                    'table_exists' => true,
                    'table_name' => 'user_fcm_tokens'
                ];
            } catch (Exception $e) {
                $diagnosticInfo['fcm_tokens'] = [
                    'error' => $e->getMessage(),
                    'table_exists' => false,
                    'table_name' => 'user_fcm_tokens'
                ];
            }
        }
        
        // Check PHP extensions
        $diagnosticInfo['php_extensions'] = [
            'curl' => extension_loaded('curl'),
            'openssl' => extension_loaded('openssl'),
            'json' => extension_loaded('json'),
            'pdo' => extension_loaded('pdo'),
            'pdo_mysql' => extension_loaded('pdo_mysql')
        ];
        
        // Check PHP version and settings
        $diagnosticInfo['php_info'] = [
            'version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'error_reporting' => ini_get('error_reporting')
        ];
        
        // Test JWT generation and OAuth token exchange
        $diagnosticInfo['jwt_test'] = [];
        
        try {
            // Test JWT generation
            $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $time = time();
            $payload = base64_encode(json_encode([
                'iss' => $_ENV['FIREBASE_CLIENT_EMAIL'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'exp' => $time + 3600,
                'iat' => $time
            ]));
            
            $privateKey = str_replace('\\n', "\n", $_ENV['FIREBASE_PRIVATE_KEY']);
            if (!str_contains($privateKey, '-----BEGIN PRIVATE KEY-----')) {
                $privateKey = "-----BEGIN PRIVATE KEY-----\n" . $privateKey . "\n-----END PRIVATE KEY-----";
            }
            
            $signature = '';
            if (openssl_sign($header . '.' . $payload, $signature, $privateKey, 'SHA256')) {
                $signature = base64_encode($signature);
                $jwt = $header . '.' . $payload . '.' . $signature;
                
                $diagnosticInfo['jwt_test']['jwt_generation'] = [
                    'success' => true,
                    'jwt_length' => strlen($jwt),
                    'jwt_starts_with' => substr($jwt, 0, 50) . '...'
                ];
                
                // Test OAuth token exchange
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt
                ]));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);
                
                if ($curlError) {
                    $diagnosticInfo['jwt_test']['oauth_exchange'] = [
                        'success' => false,
                        'error' => 'cURL error: ' . $curlError
                    ];
                } else {
                    $diagnosticInfo['jwt_test']['oauth_exchange'] = [
                        'success' => $httpCode === 200,
                        'http_code' => $httpCode,
                        'response_length' => strlen($response),
                        'response_preview' => substr($response, 0, 200)
                    ];
                    
                    if ($httpCode === 200) {
                        $tokenData = json_decode($response, true);
                        if (isset($tokenData['access_token'])) {
                            $diagnosticInfo['jwt_test']['access_token'] = [
                                'received' => true,
                                'token_length' => strlen($tokenData['access_token']),
                                'token_starts_with' => substr($tokenData['access_token'], 0, 20) . '...',
                                'expires_in' => $tokenData['expires_in'] ?? 'not_set',
                                'token_type' => $tokenData['token_type'] ?? 'not_set'
                            ];
                        } else {
                            $diagnosticInfo['jwt_test']['access_token'] = [
                                'received' => false,
                                'response_keys' => array_keys($tokenData)
                            ];
                        }
                    }
                }
                
            } else {
                $diagnosticInfo['jwt_test']['jwt_generation'] = [
                    'success' => false,
                    'error' => 'Failed to sign JWT with OpenSSL'
                ];
                
                // Get OpenSSL error details
                $opensslErrors = [];
                while ($error = openssl_error_string()) {
                    $opensslErrors[] = $error;
                }
                $diagnosticInfo['jwt_test']['openssl_errors'] = $opensslErrors;
            }
        } catch (Exception $e) {
            $diagnosticInfo['jwt_test']['error'] = $e->getMessage();
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'diagnostic_info' => $diagnosticInfo,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error during diagnosis: ' . $e->getMessage()
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
        .diagnostic-section {
            background: #fff3cd;
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
        
        <div class="diagnostic-section">
            <h2>🔍 FCM Diagnostic</h2>
            <p>Detailed diagnostic information for troubleshooting FCM issues</p>
            <button class="btn" onclick="runDiagnostic()">Run Diagnostic</button>
            <div id="diagnosticResult" class="result" style="display: none;"></div>
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
        
        async function runDiagnostic() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'Running...';
            
            try {
                const response = await fetch('?action=diagnose');
                const data = await response.json();
                
                const resultDiv = document.getElementById('diagnosticResult');
                resultDiv.style.display = 'block';
                
                if (data.success) {
                    resultDiv.className = 'result info';
                    resultDiv.textContent = JSON.stringify(data.diagnostic_info, null, 2);
                } else {
                    resultDiv.className = 'result error';
                    resultDiv.textContent = 'Error: ' + data.message;
                }
            } catch (error) {
                const resultDiv = document.getElementById('diagnosticResult');
                resultDiv.style.display = 'block';
                resultDiv.className = 'result error';
                resultDiv.textContent = 'Network error: ' + error.message;
            } finally {
                btn.disabled = false;
                btn.textContent = 'Run Diagnostic';
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
