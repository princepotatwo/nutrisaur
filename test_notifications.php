<?php
// Test script to debug notification issues
require_once __DIR__ . '/public/api/DatabaseAPI.php';

echo "=== NOTIFICATION DEBUG TEST ===\n\n";

try {
    $db = DatabaseAPI::getInstance();
    
    // 1. Check if community_users table exists and has fcm_token column
    echo "1. Checking database structure...\n";
    $stmt = $db->getPDO()->query("SHOW TABLES LIKE 'community_users'");
    $tableExists = $stmt->rowCount() > 0;
    echo "   - community_users table exists: " . ($tableExists ? "YES" : "NO") . "\n";
    
    if ($tableExists) {
        $stmt = $db->getPDO()->query("SHOW COLUMNS FROM community_users LIKE 'fcm_token'");
        $fcmColumnExists = $stmt->rowCount() > 0;
        echo "   - fcm_token column exists: " . ($fcmColumnExists ? "YES" : "NO") . "\n";
        
        // 2. Check total users and FCM tokens
        $stmt = $db->getPDO()->query("SELECT COUNT(*) as total FROM community_users");
        $totalUsers = $stmt->fetchColumn();
        echo "   - Total users in community_users: $totalUsers\n";
        
        if ($fcmColumnExists) {
            $stmt = $db->getPDO()->query("SELECT COUNT(*) as count FROM community_users WHERE fcm_token IS NOT NULL AND fcm_token != ''");
            $activeTokens = $stmt->fetchColumn();
            echo "   - Users with FCM tokens: $activeTokens\n";
            
            // 3. Show sample FCM tokens
            $stmt = $db->getPDO()->query("SELECT email, fcm_token FROM community_users WHERE fcm_token IS NOT NULL AND fcm_token != '' LIMIT 3");
            $sampleTokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "   - Sample FCM tokens:\n";
            foreach ($sampleTokens as $token) {
                echo "     * " . $token['email'] . " -> " . substr($token['fcm_token'], 0, 20) . "...\n";
            }
        }
    }
    
    echo "\n2. Testing notification API...\n";
    
    // 4. Test the notification API endpoint
    $testData = [
        'action' => 'send_notification',
        'title' => 'Test Notification',
        'body' => 'This is a test notification from debug script',
        'target' => 'all'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://nutrisaur-production.up.railway.app/api/DatabaseAPI.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($testData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "   - HTTP Code: $httpCode\n";
    echo "   - Response: $response\n";
    if ($error) {
        echo "   - cURL Error: $error\n";
    }
    
    echo "\n3. Testing FCM function directly...\n";
    
    // 5. Test FCM function directly
    if ($activeTokens > 0) {
        $stmt = $db->getPDO()->query("SELECT fcm_token FROM community_users WHERE fcm_token IS NOT NULL AND fcm_token != '' LIMIT 1");
        $testToken = $stmt->fetchColumn();
        
        if ($testToken) {
            echo "   - Testing with token: " . substr($testToken, 0, 20) . "...\n";
            $fcmResult = sendFCMNotificationToToken($testToken, 'Direct Test', 'Testing FCM directly');
            echo "   - FCM Result: " . json_encode($fcmResult) . "\n";
        }
    }
    
    echo "\n=== DEBUG COMPLETE ===\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
