<?php
// Simple FCM debug test
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>FCM Debug Test</h1>";

// Test 1: Check if Firebase config file exists
$adminSdkPath = __DIR__ . '/nutrisaur-ebf29-firebase-adminsdk-fbsvc-152a242b3b.json';
echo "<h2>Test 1: Firebase Config File</h2>";
echo "Path: $adminSdkPath<br>";
echo "Exists: " . (file_exists($adminSdkPath) ? 'YES' : 'NO') . "<br>";

if (file_exists($adminSdkPath)) {
    $content = file_get_contents($adminSdkPath);
    $serviceAccount = json_decode($content, true);
    echo "Valid JSON: " . (json_last_error() === JSON_ERROR_NONE ? 'YES' : 'NO') . "<br>";
    if ($serviceAccount) {
        echo "Project ID: " . ($serviceAccount['project_id'] ?? 'MISSING') . "<br>";
        echo "Client Email: " . ($serviceAccount['client_email'] ?? 'MISSING') . "<br>";
        echo "Private Key: " . (isset($serviceAccount['private_key']) ? 'PRESENT' : 'MISSING') . "<br>";
    }
}

// Test 2: Test JWT generation
echo "<h2>Test 2: JWT Generation</h2>";
if (file_exists($adminSdkPath) && $serviceAccount) {
    try {
        $accessToken = generateAccessToken($serviceAccount);
        echo "Access Token Generated: " . ($accessToken ? 'YES' : 'NO') . "<br>";
        if ($accessToken) {
            echo "Token Length: " . strlen($accessToken) . "<br>";
            echo "Token Preview: " . substr($accessToken, 0, 50) . "...<br>";
        }
    } catch (Exception $e) {
        echo "JWT Error: " . $e->getMessage() . "<br>";
    }
}

// Test 3: Test FCM sending
echo "<h2>Test 3: FCM Send Test</h2>";
if (file_exists($adminSdkPath) && $serviceAccount) {
    try {
        // Test with dummy token
        $testTokens = [['fcm_token' => 'test_token_123', 'user_email' => 'test@test.com', 'user_barangay' => 'Test']];
        $testData = ['title' => 'Test Notification', 'body' => 'This is a test'];
        
        $result = sendFCMNotification($testTokens, $testData, 'Test Location');
        echo "FCM Test Result: " . ($result ? 'SUCCESS' : 'FAILED') . "<br>";
    } catch (Exception $e) {
        echo "FCM Test Error: " . $e->getMessage() . "<br>";
    }
}

// Include the functions from event.php
require_once 'event.php';

echo "<h2>Test Complete</h2>";
?>
