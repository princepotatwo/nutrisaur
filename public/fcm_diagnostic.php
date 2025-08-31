<?php
// Standalone FCM Diagnostic Endpoint
// Access: https://your-domain.railway.app/fcm_diagnostic.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🔍 FCM Diagnostic Starting...\n\n";

try {
    $diagnosticInfo = [];
    
    // Check PHP environment
    $diagnosticInfo['php_environment'] = [
        'version' => PHP_VERSION,
        'extensions' => [
            'curl' => extension_loaded('curl'),
            'openssl' => extension_loaded('openssl'),
            'json' => extension_loaded('json'),
            'pdo' => extension_loaded('pdo'),
            'pdo_mysql' => extension_loaded('pdo_mysql')
        ],
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'error_reporting' => ini_get('error_reporting')
    ];
    
    // Check environment variables
    $diagnosticInfo['environment_variables'] = [
        'FIREBASE_PROJECT_ID' => $_ENV['FIREBASE_PROJECT_ID'] ?? 'not_set',
        'FIREBASE_CLIENT_EMAIL' => $_ENV['FIREBASE_CLIENT_EMAIL'] ?? 'not_set',
        'FIREBASE_PRIVATE_KEY_ID' => $_ENV['FIREBASE_PRIVATE_KEY_ID'] ?? 'not_set',
        'FIREBASE_CLIENT_ID' => $_ENV['FIREBASE_CLIENT_ID'] ?? 'not_set',
        'FIREBASE_PRIVATE_KEY' => isset($_ENV['FIREBASE_PRIVATE_KEY']) ? 'set(' . strlen($_ENV['FIREBASE_PRIVATE_KEY']) . ' chars)' : 'not_set'
    ];
    
    // Check Railway environment
    $diagnosticInfo['railway_environment'] = [
        'RAILWAY_ENVIRONMENT' => $_ENV['RAILWAY_ENVIRONMENT'] ?? 'not_set',
        'RAILWAY_SERVICE_NAME' => $_ENV['RAILWAY_SERVICE_NAME'] ?? 'not_set',
        'RAILWAY_PROJECT_NAME' => $_ENV['RAILWAY_PROJECT_NAME'] ?? 'not_set',
        'PORT' => $_ENV['PORT'] ?? 'not_set'
    ];
    
    // Check file system
    $diagnosticInfo['file_system'] = [
        'current_directory' => getcwd(),
        'script_directory' => __DIR__,
        'script_exists' => file_exists(__FILE__),
        'config_exists' => file_exists(__DIR__ . '/config.php')
    ];
    
    // Check for Firebase Admin SDK files
    $firebaseFiles = [];
    $searchDirs = [__DIR__ . '/../sss', __DIR__ . '/api', '/var/www/html/sss', '/var/www/html/public/api'];
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
    
    // Check FCM token cache
    $fcmCachePath = __DIR__ . '/api/fcm_token_cache.json';
    $diagnosticInfo['fcm_cache'] = [
        'cache_file_exists' => file_exists($fcmCachePath),
        'cache_file_readable' => is_readable($fcmCachePath),
        'cache_file_size' => file_exists($fcmCachePath) ? filesize($fcmCachePath) : 'N/A'
    ];
    
    if (file_exists($fcmCachePath)) {
        try {
            $cacheData = json_decode(file_get_contents($fcmCachePath), true);
            if ($cacheData) {
                $diagnosticInfo['fcm_cache']['data'] = [
                    'has_access_token' => isset($cacheData['access_token']),
                    'has_expires_at' => isset($cacheData['expires_at']),
                    'expires_at_timestamp' => $cacheData['expires_at'] ?? 'not_set',
                    'current_timestamp' => time(),
                    'is_expired' => isset($cacheData['expires_at']) ? (time() > $cacheData['expires_at']) : 'unknown'
                ];
            }
        } catch (Exception $e) {
            $diagnosticInfo['fcm_cache']['error'] = $e->getMessage();
        }
    }
    
    // Try to test FCM functionality
    $diagnosticInfo['fcm_test'] = [];
    
    // Check if we have Firebase credentials
    $hasCredentials = false;
    if (isset($_ENV['FIREBASE_PROJECT_ID']) && 
        isset($_ENV['FIREBASE_CLIENT_EMAIL']) && 
        isset($_ENV['FIREBASE_PRIVATE_KEY_ID']) && 
        isset($_ENV['FIREBASE_CLIENT_ID']) && 
        isset($_ENV['FIREBASE_PRIVATE_KEY'])) {
        $hasCredentials = true;
    }
    
    $diagnosticInfo['fcm_test']['has_environment_credentials'] = $hasCredentials;
    
    // Test JWT generation if we have credentials
    if ($hasCredentials) {
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
                
                $diagnosticInfo['fcm_test']['jwt_generation'] = [
                    'success' => true,
                    'jwt_length' => strlen($jwt),
                    'jwt_starts_with' => substr($jwt, 0, 50) . '...'
                ];
            } else {
                $diagnosticInfo['fcm_test']['jwt_generation'] = [
                    'success' => false,
                    'error' => 'Failed to sign JWT with OpenSSL'
                ];
            }
        } catch (Exception $e) {
            $diagnosticInfo['fcm_test']['jwt_generation'] = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Check database connection if config exists
    if (file_exists(__DIR__ . '/config.php')) {
        try {
            require_once __DIR__ . '/config.php';
            if (function_exists('getDatabaseConnection')) {
                $conn = getDatabaseConnection();
                if ($conn) {
                    $diagnosticInfo['database'] = [
                        'connected' => true,
                        'connection_type' => get_class($conn)
                    ];
                    
                    // Check FCM tokens table
                    try {
                        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM fcm_tokens WHERE is_active = TRUE");
                        $stmt->execute();
                        $activeTokens = $stmt->fetchColumn();
                        $diagnosticInfo['database']['fcm_tokens'] = [
                            'active_count' => $activeTokens,
                            'table_exists' => true
                        ];
                    } catch (Exception $e) {
                        $diagnosticInfo['database']['fcm_tokens'] = [
                            'error' => $e->getMessage(),
                            'table_exists' => false
                        ];
                    }
                } else {
                    $diagnosticInfo['database'] = [
                        'connected' => false,
                        'error' => 'getDatabaseConnection returned null'
                    ];
                }
            } else {
                $diagnosticInfo['database'] = [
                    'connected' => false,
                    'error' => 'getDatabaseConnection function not found'
                ];
            }
        } catch (Exception $e) {
            $diagnosticInfo['database'] = [
                'connected' => false,
                'error' => $e->getMessage()
            ];
        }
    } else {
        $diagnosticInfo['database'] = [
            'connected' => false,
            'error' => 'config.php not found'
        ];
    }
    
    // Summary and recommendations
    $diagnosticInfo['summary'] = [];
    
    // Check for critical issues
    $criticalIssues = [];
    if (!$hasCredentials) {
        $criticalIssues[] = 'Missing Firebase environment variables';
    }
    
    if (!extension_loaded('curl')) {
        $criticalIssues[] = 'cURL extension not loaded';
    }
    
    if (!extension_loaded('openssl')) {
        $criticalIssues[] = 'OpenSSL extension not loaded';
    }
    
    if (!extension_loaded('json')) {
        $criticalIssues[] = 'JSON extension not loaded';
    }
    
    $diagnosticInfo['summary']['critical_issues'] = $criticalIssues;
    $diagnosticInfo['summary']['critical_issues_count'] = count($criticalIssues);
    
    // Recommendations
    $recommendations = [];
    if (!$hasCredentials) {
        $recommendations[] = 'Set all required Firebase environment variables in Railway';
        $recommendations[] = 'Verify Firebase project ID and service account details';
    }
    
    if (empty($firebaseFiles)) {
        $recommendations[] = 'Consider uploading Firebase Admin SDK JSON file as alternative';
    }
    
    if (!extension_loaded('curl') || !extension_loaded('openssl')) {
        $recommendations[] = 'Ensure PHP extensions (curl, openssl) are enabled';
    }
    
    $diagnosticInfo['summary']['recommendations'] = $recommendations;
    
    // Final status
    if (count($criticalIssues) === 0) {
        $diagnosticInfo['status'] = 'healthy';
        $diagnosticInfo['message'] = 'FCM system appears to be properly configured';
    } elseif (count($criticalIssues) <= 2) {
        $diagnosticInfo['status'] = 'degraded';
        $diagnosticInfo['message'] = 'FCM system has some issues but may still function';
    } else {
        $diagnosticInfo['status'] = 'critical';
        $diagnosticInfo['message'] = 'FCM system has critical issues and will not function';
    }
    
    $diagnosticInfo['timestamp'] = date('Y-m-d H:i:s');
    $diagnosticInfo['server_timezone'] = date_default_timezone_get();
    
    // Output as JSON
    echo json_encode($diagnosticInfo, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Diagnostic failed: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
?>
