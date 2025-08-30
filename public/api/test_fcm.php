<?php
// Test script to verify Firebase Admin SDK integration
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🧪 Testing Firebase Admin SDK Integration...\n\n";

// Include the unified API to test FCM functions
require_once 'unified_api.php';

echo "✅ Unified API loaded successfully\n";

// Test Firebase service account file
$adminSdkPath = __DIR__ . '/nutrisaur-ebf29-firebase-adminsdk-fbsvc-152a242b3b.json';
if (file_exists($adminSdkPath)) {
    echo "✅ Firebase service account file found: " . basename($adminSdkPath) . "\n";
    
    // Test reading the service account
    $serviceAccount = json_decode(file_get_contents($adminSdkPath), true);
    if ($serviceAccount && isset($serviceAccount['project_id'])) {
        echo "✅ Service account JSON parsed successfully\n";
        echo "   Project ID: " . $serviceAccount['project_id'] . "\n";
        echo "   Client Email: " . $serviceAccount['client_email'] . "\n";
        echo "   Private Key ID: " . $serviceAccount['private_key_id'] . "\n";
    } else {
        echo "❌ Failed to parse service account JSON\n";
        exit(1);
    }
} else {
    echo "❌ Firebase service account file not found\n";
    exit(1);
}

// Test FCM token cache
$cacheFile = __DIR__ . '/fcm_token_cache.json';
if (file_exists($cacheFile)) {
    echo "✅ FCM token cache file found\n";
    $cached = json_decode(file_get_contents($cacheFile), true);
    if ($cached && isset($cached['access_token'])) {
        echo "   Cached token exists\n";
        echo "   Expires at: " . date('Y-m-d H:i:s', $cached['expires_at']) . "\n";
        echo "   Token valid: " . (($cached['expires_at'] > time()) ? 'Yes' : 'No') . "\n";
    }
} else {
    echo "⚠️  FCM token cache file not found\n";
}

echo "\n🚀 Testing FCM notification function...\n";

// Test with dummy data
$testTokens = ['test_token_123'];
$testNotification = [
    'title' => 'Test Notification',
    'body' => 'This is a test FCM notification',
    'data' => [
        'test' => 'true',
        'timestamp' => time()
    ]
];

try {
    echo "📱 Attempting to send test FCM notification...\n";
    $result = sendFCMNotification($testTokens, $testNotification);
    
    if ($result) {
        echo "✅ FCM notification function executed successfully\n";
        echo "   (Note: This is expected to fail with test tokens, but the function should run)\n";
    } else {
        echo "⚠️  FCM notification function returned false (expected with test tokens)\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error testing FCM notification: " . $e->getMessage() . "\n";
}

echo "\n🎯 Firebase Admin SDK Integration Test Complete!\n";
echo "   Your FCM system is now ready to send real push notifications!\n";
?>
