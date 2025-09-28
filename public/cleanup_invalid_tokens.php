<?php
/**
 * Script to clean up invalid FCM tokens
 * This script removes FCM tokens that are causing UNREGISTERED errors
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once __DIR__ . '/api/DatabaseAPI.php';

echo "<h1>FCM Token Cleanup</h1>";

try {
    $db = DatabaseAPI::getInstance();
    
    // Get all FCM tokens
    $stmt = $db->getPDO()->query("SELECT email, fcm_token FROM community_users WHERE fcm_token IS NOT NULL AND fcm_token != ''");
    $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($tokens) . " FCM tokens to check</p>";
    
    $invalidTokens = [];
    $validTokens = [];
    
    foreach ($tokens as $tokenData) {
        $fcmToken = $tokenData['fcm_token'];
        $email = $tokenData['email'];
        
        // Test the token by sending a test notification
        $testResult = sendFCMNotificationToToken($fcmToken, 'Test', 'Token validation test');
        
        if ($testResult['success']) {
            $validTokens[] = $tokenData;
            echo "<p style='color: green;'>✅ Valid token for $email</p>";
        } else {
            $invalidTokens[] = $tokenData;
            echo "<p style='color: red;'>❌ Invalid token for $email: " . $testResult['error'] . "</p>";
        }
    }
    
    echo "<h2>Summary</h2>";
    echo "<p>Valid tokens: " . count($validTokens) . "</p>";
    echo "<p>Invalid tokens: " . count($invalidTokens) . "</p>";
    
    if (count($invalidTokens) > 0) {
        echo "<h3>Cleaning up invalid tokens...</h3>";
        
        foreach ($invalidTokens as $invalidToken) {
            $email = $invalidToken['email'];
            $fcmToken = $invalidToken['fcm_token'];
            
            // Remove the invalid token
            $stmt = $db->getPDO()->prepare("UPDATE community_users SET fcm_token = NULL WHERE email = :email AND fcm_token = :fcm_token");
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':fcm_token', $fcmToken);
            $result = $stmt->execute();
            
            if ($result) {
                echo "<p style='color: blue;'>🧹 Removed invalid token for $email</p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to remove token for $email</p>";
            }
        }
        
        echo "<p style='color: green;'>✅ Cleanup completed</p>";
    } else {
        echo "<p style='color: green;'>✅ All tokens are valid</p>";
    }
    
} catch (Exception $e) {
    echo "<h2>Cleanup Failed</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
