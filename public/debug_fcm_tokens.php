<?php
/**
 * Debug script to check FCM token issues
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once __DIR__ . '/api/DatabaseAPI.php';

echo "<h1>FCM Token Debug Report</h1>";

try {
    $db = DatabaseAPI::getInstance();
    
    // Check for duplicate emails
    echo "<h2>1. Duplicate Email Check</h2>";
    $stmt = $db->getPDO()->query("
        SELECT email, COUNT(*) as count 
        FROM community_users 
        GROUP BY email 
        HAVING COUNT(*) > 1 
        ORDER BY count DESC
    ");
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($duplicates) > 0) {
        echo "<p style='color: red;'>❌ Found " . count($duplicates) . " duplicate emails:</p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Email</th><th>Count</th></tr>";
        foreach ($duplicates as $dup) {
            echo "<tr><td>" . htmlspecialchars($dup['email']) . "</td><td>" . $dup['count'] . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: green;'>✅ No duplicate emails found</p>";
    }
    
    // Check FCM token distribution
    echo "<h2>2. FCM Token Distribution</h2>";
    $stmt = $db->getPDO()->query("
        SELECT 
            COUNT(*) as total_users,
            COUNT(fcm_token) as users_with_tokens,
            COUNT(CASE WHEN fcm_token IS NOT NULL AND fcm_token != '' THEN 1 END) as valid_tokens
        FROM community_users
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Metric</th><th>Count</th></tr>";
    echo "<tr><td>Total Users</td><td>" . $stats['total_users'] . "</td></tr>";
    echo "<tr><td>Users with FCM Tokens</td><td>" . $stats['users_with_tokens'] . "</td></tr>";
    echo "<tr><td>Valid FCM Tokens</td><td>" . $stats['valid_tokens'] . "</td></tr>";
    echo "</table>";
    
    // Check recent FCM token registrations
    echo "<h2>3. Recent FCM Token Activity</h2>";
    $stmt = $db->getPDO()->query("
        SELECT email, fcm_token, created_at, updated_at 
        FROM community_users 
        WHERE fcm_token IS NOT NULL AND fcm_token != ''
        ORDER BY updated_at DESC 
        LIMIT 10
    ");
    $recentTokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($recentTokens) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Email</th><th>FCM Token (first 30 chars)</th><th>Created</th><th>Updated</th></tr>";
        foreach ($recentTokens as $token) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($token['email']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($token['fcm_token'], 0, 30)) . "...</td>";
            echo "<td>" . $token['created_at'] . "</td>";
            echo "<td>" . $token['updated_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ No FCM tokens found!</p>";
    }
    
    // Check for users without FCM tokens
    echo "<h2>4. Users Without FCM Tokens</h2>";
    $stmt = $db->getPDO()->query("
        SELECT email, created_at, updated_at 
        FROM community_users 
        WHERE fcm_token IS NULL OR fcm_token = ''
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $usersWithoutTokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($usersWithoutTokens) > 0) {
        echo "<p>Found " . count($usersWithoutTokens) . " users without FCM tokens:</p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Email</th><th>Created</th><th>Updated</th></tr>";
        foreach ($usersWithoutTokens as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . $user['created_at'] . "</td>";
            echo "<td>" . $user['updated_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: green;'>✅ All users have FCM tokens</p>";
    }
    
    // Check for potential issues
    echo "<h2>5. Potential Issues</h2>";
    
    // Check for empty emails
    $stmt = $db->getPDO()->query("SELECT COUNT(*) as count FROM community_users WHERE email IS NULL OR email = ''");
    $emptyEmails = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($emptyEmails > 0) {
        echo "<p style='color: red;'>❌ Found $emptyEmails users with empty emails</p>";
    }
    
    // Check for app_user emails (default emails)
    $stmt = $db->getPDO()->query("SELECT COUNT(*) as count FROM community_users WHERE email LIKE 'app_user_%'");
    $appUserEmails = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($appUserEmails > 0) {
        echo "<p style='color: orange;'>⚠️ Found $appUserEmails users with default app_user emails</p>";
    }
    
    echo "<h2>6. Recommendations</h2>";
    if (count($duplicates) > 0) {
        echo "<p style='color: red;'>🔧 Fix duplicate emails first - this is likely causing token clearing issues</p>";
    }
    if ($stats['valid_tokens'] < $stats['total_users'] * 0.5) {
        echo "<p style='color: orange;'>🔧 Many users missing FCM tokens - check registration process</p>";
    }
    if ($appUserEmails > 0) {
        echo "<p style='color: orange;'>🔧 Consider cleaning up default app_user emails</p>";
    }
    
} catch (Exception $e) {
    echo "<h2>Debug Failed</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
