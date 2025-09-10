<?php
/**
 * Test FCM Integration for Event.php
 * This script tests the FCM functionality after updating event.php to use DatabaseAPI
 */

// Include the event.php file to test FCM functions
require_once __DIR__ . '/event.php';

echo "<h2>FCM Integration Test</h2>\n";

// Test 1: Check if DatabaseAPI is properly included
echo "<h3>Test 1: DatabaseAPI Inclusion</h3>\n";
if (class_exists('DatabaseAPI')) {
    echo "✅ DatabaseAPI class is available<br>\n";
    
    try {
        $db = DatabaseAPI::getInstance();
        echo "✅ DatabaseAPI instance created successfully<br>\n";
    } catch (Exception $e) {
        echo "❌ Failed to create DatabaseAPI instance: " . $e->getMessage() . "<br>\n";
    }
} else {
    echo "❌ DatabaseAPI class not found<br>\n";
}

// Test 2: Check FCM token functions
echo "<h3>Test 2: FCM Token Functions</h3>\n";
try {
    $db = DatabaseAPI::getInstance();
    $activeTokens = $db->getActiveFCMTokens();
    echo "✅ getActiveFCMTokens() returned " . count($activeTokens) . " tokens<br>\n";
    
    if (count($activeTokens) > 0) {
        echo "Sample token data:<br>\n";
        echo "<pre>" . print_r($activeTokens[0], true) . "</pre>\n";
    }
} catch (Exception $e) {
    echo "❌ Error getting FCM tokens: " . $e->getMessage() . "<br>\n";
}

// Test 3: Test FCM notification function (without actually sending)
echo "<h3>Test 3: FCM Notification Function</h3>\n";
if (function_exists('sendFCMNotificationToToken')) {
    echo "✅ sendFCMNotificationToToken function is available<br>\n";
    
    // Test with a dummy token (won't actually send)
    $testResult = sendFCMNotificationToToken('test_token', 'Test Title', 'Test Body');
    echo "✅ sendFCMNotificationToToken function executed (result: " . ($testResult['success'] ? 'success' : 'failed') . ")<br>\n";
} else {
    echo "❌ sendFCMNotificationToToken function not found<br>\n";
}

// Test 4: Test FCM token retrieval by location
echo "<h3>Test 4: FCM Token Retrieval by Location</h3>\n";
if (function_exists('getFCMTokensByLocation')) {
    echo "✅ getFCMTokensByLocation function is available<br>\n";
    
    // Test with 'all' location
    $allTokens = getFCMTokensByLocation('all');
    echo "✅ getFCMTokensByLocation('all') returned " . count($allTokens) . " tokens<br>\n";
    
    // Test with empty location
    $emptyTokens = getFCMTokensByLocation('');
    echo "✅ getFCMTokensByLocation('') returned " . count($emptyTokens) . " tokens<br>\n";
} else {
    echo "❌ getFCMTokensByLocation function not found<br>\n";
}

// Test 5: Check if FCM functions from DatabaseAPI are available
echo "<h3>Test 5: DatabaseAPI FCM Functions</h3>\n";
if (function_exists('sendFCMNotificationToToken')) {
    echo "✅ Global sendFCMNotificationToToken function is available<br>\n";
} else {
    echo "❌ Global sendFCMNotificationToToken function not found<br>\n";
}

if (function_exists('sendFCMNotification')) {
    echo "✅ Global sendFCMNotification function is available<br>\n";
} else {
    echo "❌ Global sendFCMNotification function not found<br>\n";
}

echo "<h3>Test Summary</h3>\n";
echo "FCM integration test completed. Check the results above to verify functionality.<br>\n";
echo "<p><strong>Note:</strong> This test only verifies function availability and basic execution. Actual FCM sending requires valid tokens and proper Firebase configuration.</p>\n";
?>
