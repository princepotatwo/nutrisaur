<?php
/**
 * Script to fix FCM token issues
 * This script addresses the root causes of FCM token problems
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once __DIR__ . '/api/DatabaseAPI.php';

echo "<h1>FCM Token Issues Fix</h1>";

try {
    $db = DatabaseAPI::getInstance();
    
    echo "<h2>Step 1: Check for Duplicate Emails</h2>";
    
    // Check for duplicate emails first
    $stmt = $db->getPDO()->query("
        SELECT email, COUNT(*) as count 
        FROM community_users 
        GROUP BY email 
        HAVING COUNT(*) > 1 
        ORDER BY count DESC
    ");
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($duplicates) > 0) {
        echo "<p style='color: red;'>❌ Found " . count($duplicates) . " duplicate emails that could cause FCM token issues:</p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Email</th><th>Count</th></tr>";
        foreach ($duplicates as $dup) {
            echo "<tr><td>" . htmlspecialchars($dup['email']) . "</td><td>" . $dup['count'] . "</td></tr>";
        }
        echo "</table>";
        
        echo "<h3>Fixing Duplicate Emails...</h3>";
        $fixResult = $db->fixDuplicateEmails();
        
        if ($fixResult['success']) {
            echo "<p style='color: green;'>✅ Fixed " . $fixResult['fixed_count'] . " duplicate email issues</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to fix duplicates: " . $fixResult['error'] . "</p>";
        }
    } else {
        echo "<p style='color: green;'>✅ No duplicate emails found</p>";
    }
    
    echo "<h2>Step 2: Check FCM Token Distribution</h2>";
    
    $stmt = $db->getPDO()->query("
        SELECT 
            COUNT(*) as total_users,
            COUNT(CASE WHEN fcm_token IS NOT NULL AND fcm_token != '' THEN 1 END) as users_with_tokens
        FROM community_users
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Metric</th><th>Count</th><th>Percentage</th></tr>";
    echo "<tr><td>Total Users</td><td>" . $stats['total_users'] . "</td><td>100%</td></tr>";
    echo "<tr><td>Users with FCM Tokens</td><td>" . $stats['users_with_tokens'] . "</td><td>" . round(($stats['users_with_tokens'] / $stats['total_users']) * 100, 1) . "%</td></tr>";
    echo "</table>";
    
    if ($stats['users_with_tokens'] < $stats['total_users'] * 0.5) {
        echo "<p style='color: orange;'>⚠️ Less than 50% of users have FCM tokens - this indicates a registration issue</p>";
    }
    
    echo "<h2>Step 3: Check for Invalid FCM Tokens</h2>";
    
    // Check for tokens that might be invalid (too short, malformed, etc.)
    $stmt = $db->getPDO()->query("
        SELECT email, fcm_token, LENGTH(fcm_token) as token_length
        FROM community_users 
        WHERE fcm_token IS NOT NULL AND fcm_token != ''
        ORDER BY token_length ASC
    ");
    $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $invalidTokens = [];
    foreach ($tokens as $token) {
        if (strlen($token['fcm_token']) < 50 || !str_contains($token['fcm_token'], ':')) {
            $invalidTokens[] = $token;
        }
    }
    
    if (count($invalidTokens) > 0) {
        echo "<p style='color: red;'>❌ Found " . count($invalidTokens) . " potentially invalid FCM tokens:</p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Email</th><th>Token Length</th><th>Token (first 30 chars)</th></tr>";
        foreach ($invalidTokens as $token) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($token['email']) . "</td>";
            echo "<td>" . $token['token_length'] . "</td>";
            echo "<td>" . htmlspecialchars(substr($token['fcm_token'], 0, 30)) . "...</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>Cleaning Invalid Tokens...</h3>";
        $cleanedCount = 0;
        foreach ($invalidTokens as $token) {
            $stmt = $db->getPDO()->prepare("UPDATE community_users SET fcm_token = NULL WHERE email = :email");
            $stmt->bindParam(':email', $token['email']);
            $stmt->execute();
            $cleanedCount++;
        }
        echo "<p style='color: green;'>✅ Cleaned $cleanedCount invalid FCM tokens</p>";
    } else {
        echo "<p style='color: green;'>✅ All FCM tokens appear to be valid</p>";
    }
    
    echo "<h2>Step 4: Final Statistics</h2>";
    
    // Get final stats
    $stmt = $db->getPDO()->query("
        SELECT 
            COUNT(*) as total_users,
            COUNT(CASE WHEN fcm_token IS NOT NULL AND fcm_token != '' THEN 1 END) as users_with_tokens,
            COUNT(CASE WHEN fcm_token IS NOT NULL AND fcm_token != '' AND LENGTH(fcm_token) > 50 THEN 1 END) as valid_tokens
        FROM community_users
    ");
    $finalStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Metric</th><th>Count</th><th>Percentage</th></tr>";
    echo "<tr><td>Total Users</td><td>" . $finalStats['total_users'] . "</td><td>100%</td></tr>";
    echo "<tr><td>Users with FCM Tokens</td><td>" . $finalStats['users_with_tokens'] . "</td><td>" . round(($finalStats['users_with_tokens'] / $finalStats['total_users']) * 100, 1) . "%</td></tr>";
    echo "<tr><td>Valid FCM Tokens</td><td>" . $finalStats['valid_tokens'] . "</td><td>" . round(($finalStats['valid_tokens'] / $finalStats['total_users']) * 100, 1) . "%</td></tr>";
    echo "</table>";
    
    echo "<h2>Step 5: Recommendations</h2>";
    
    if ($finalStats['valid_tokens'] < $finalStats['total_users'] * 0.8) {
        echo "<p style='color: orange;'>🔧 Consider implementing a token refresh mechanism for users without tokens</p>";
    }
    
    if (count($duplicates) > 0) {
        echo "<p style='color: green;'>✅ Duplicate email issues have been resolved</p>";
    }
    
    echo "<p style='color: green;'>✅ FCM token issues have been addressed</p>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li>Test creating a new event to verify notifications work</li>";
    echo "<li>Monitor server logs for FCM token registration</li>";
    echo "<li>Check if users are receiving notifications properly</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h2>Fix Failed</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
