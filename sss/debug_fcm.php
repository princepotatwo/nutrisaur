<?php
// Command-line FCM debug script
// Usage: php debug_fcm.php

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🔍 FCM Debug Script - Nutrisaur\n";
echo "================================\n\n";

// Include database configuration
require_once __DIR__ . '/../public/config.php';

echo "📊 Checking System Status...\n";
echo "----------------------------\n";

// Check PHP version and extensions
echo "PHP Version: " . PHP_VERSION . "\n";
echo "cURL Extension: " . (extension_loaded('curl') ? '✅ Loaded' : '❌ Not Loaded') . "\n";
echo "OpenSSL Extension: " . (extension_loaded('openssl') ? '✅ Loaded' : '❌ Not Loaded') . "\n";
echo "JSON Extension: " . (extension_loaded('json') ? '✅ Loaded' : '❌ Not Loaded') . "\n";
echo "PDO Extension: " . (extension_loaded('pdo') ? '✅ Loaded' : '❌ Not Loaded') . "\n";
echo "PDO MySQL Extension: " . (extension_loaded('pdo_mysql') ? '✅ Loaded' : '❌ Not Loaded') . "\n\n";

// Check environment variables
echo "🔐 Checking Environment Variables...\n";
echo "-----------------------------------\n";
$envVars = [
    'FIREBASE_PROJECT_ID',
    'FIREBASE_CLIENT_EMAIL', 
    'FIREBASE_PRIVATE_KEY_ID',
    'FIREBASE_CLIENT_ID',
    'FIREBASE_PRIVATE_KEY'
];

foreach ($envVars as $var) {
    $value = $_ENV[$var] ?? 'not_set';
    if ($var === 'FIREBASE_PRIVATE_KEY' && $value !== 'not_set') {
        echo "$var: " . substr($value, 0, 50) . "... (length: " . strlen($value) . ")\n";
    } else {
        echo "$var: $value\n";
    }
}
echo "\n";

// Check file system
echo "📁 Checking File System...\n";
echo "--------------------------\n";
echo "Current Directory: " . getcwd() . "\n";
echo "Script Directory: " . __DIR__ . "\n";
echo "Config File Exists: " . (file_exists(__DIR__ . '/../public/config.php') ? '✅ Yes' : '❌ No') . "\n";

// Check for Firebase Admin SDK files
$searchDirs = [__DIR__, __DIR__ . '/../public/api', '/var/www/html/sss', '/var/www/html/public/api'];
echo "\nSearching for Firebase Admin SDK files in:\n";
foreach ($searchDirs as $dir) {
    if (is_dir($dir)) {
        echo "  - $dir: ";
        $files = glob($dir . '/*firebase*admin*.json');
        if (!empty($files)) {
            foreach ($files as $file) {
                echo "✅ " . basename($file) . " (" . filesize($file) . " bytes)";
            }
        } else {
            echo "❌ No files found";
        }
        echo "\n";
    } else {
        echo "  - $dir: ❌ Directory not accessible\n";
    }
}
echo "\n";

// Check database connection
echo "🗄️ Checking Database Connection...\n";
echo "----------------------------------\n";
try {
    $conn = getDatabaseConnection();
    if ($conn) {
        echo "Database Connection: ✅ Connected\n";
        echo "Connection Type: " . get_class($conn) . "\n";
        
        // Check FCM tokens table
        try {
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM fcm_tokens WHERE is_active = TRUE");
            $stmt->execute();
            $activeTokens = $stmt->fetchColumn();
            echo "Active FCM Tokens: $activeTokens\n";
        } catch (Exception $e) {
            echo "FCM Tokens Table: ❌ Error - " . $e->getMessage() . "\n";
        }
    } else {
        echo "Database Connection: ❌ Failed\n";
    }
} catch (Exception $e) {
    echo "Database Connection: ❌ Error - " . $e->getMessage() . "\n";
}
echo "\n";

// Test FCM functionality
echo "🚀 Testing FCM Functionality...\n";
echo "-------------------------------\n";

// Include the FCM functions
require_once __DIR__ . '/test_fcm.php';

// Get some FCM tokens for testing
try {
    if ($conn) {
        $stmt = $conn->prepare("SELECT fcm_token, user_email FROM fcm_tokens WHERE is_active = TRUE AND fcm_token IS NOT NULL AND fcm_token != '' LIMIT 3");
        $stmt->execute();
        $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($tokens)) {
            echo "Found " . count($tokens) . " active FCM tokens for testing\n";
            
            // Test FCM notification
            $fcmTokens = array_column($tokens, 'fcm_token');
            echo "Attempting to send test FCM notification...\n";
            
            $result = sendFCMNotification($fcmTokens, [
                'title' => '🧪 FCM Debug Test',
                'body' => 'This is a debug test notification from command line',
                'data' => [
                    'test_type' => 'debug_script',
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
            
            if ($result) {
                echo "FCM Test: ✅ Success\n";
            } else {
                echo "FCM Test: ❌ Failed\n";
                echo "Check the error logs above for details\n";
            }
        } else {
            echo "No active FCM tokens found for testing\n";
        }
    }
} catch (Exception $e) {
    echo "FCM Test Error: " . $e->getMessage() . "\n";
}

echo "\n🔍 Debug Complete!\n";
echo "Check the output above for any issues.\n";
echo "If FCM is failing, look for:\n";
echo "  - Missing environment variables\n";
echo "  - Missing Firebase Admin SDK file\n";
echo "  - Database connection issues\n";
echo "  - PHP extension issues\n";
echo "  - FCM token availability\n";
?>
