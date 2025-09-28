<?php
/**
 * Test script to verify notification system fixes
 * This script tests the notification system without creating actual events
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once __DIR__ . '/api/DatabaseAPI.php';

echo "<h1>Notification System Test</h1>";

try {
    // Test 1: Check if notification_logs table exists and has proper structure
    echo "<h2>Test 1: Database Structure</h2>";
    
    $db = DatabaseAPI::getInstance();
    
    // Check if notification_logs table exists
    $stmt = $db->getPDO()->query("SHOW TABLES LIKE 'notification_logs'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "✅ notification_logs table exists<br>";
        
        // Check table structure
        $stmt = $db->getPDO()->query("DESCRIBE notification_logs");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . $column['Field'] . "</td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "<td>" . $column['Default'] . "</td>";
            echo "<td>" . $column['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ notification_logs table does not exist<br>";
        echo "<p>Creating table...</p>";
        
        // Create the table
        $createTableSQL = "
        CREATE TABLE IF NOT EXISTS notification_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id INT NOT NULL,
            notification_type VARCHAR(255) NOT NULL,
            target_type VARCHAR(50) NOT NULL,
            target_value TEXT,
            tokens_sent INT DEFAULT 1,
            success BOOLEAN DEFAULT FALSE,
            error_message TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_event_id (event_id),
            INDEX idx_created_at (created_at),
            INDEX idx_success (success)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->getPDO()->exec($createTableSQL);
        echo "✅ notification_logs table created<br>";
    }
    
    // Test 2: Check FCM tokens
    echo "<h2>Test 2: FCM Tokens</h2>";
    
    $stmt = $db->getPDO()->query("SELECT COUNT(*) as total FROM community_users WHERE fcm_token IS NOT NULL AND fcm_token != ''");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalTokens = $result['total'];
    
    echo "Total FCM tokens in database: $totalTokens<br>";
    
    if ($totalTokens > 0) {
        // Get sample tokens
        $stmt = $db->getPDO()->query("SELECT email, fcm_token FROM community_users WHERE fcm_token IS NOT NULL AND fcm_token != '' LIMIT 3");
        $sampleTokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Sample FCM Tokens:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Email</th><th>FCM Token (first 30 chars)</th></tr>";
        foreach ($sampleTokens as $token) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($token['email']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($token['fcm_token'], 0, 30)) . "...</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "⚠️ No FCM tokens found in database<br>";
    }
    
    // Test 3: Test notification logging
    echo "<h2>Test 3: Notification Logging</h2>";
    
    try {
        $testResult = $db->logNotification(
            999, // test event ID
            'test_token_123',
            'Test Notification',
            'This is a test notification',
            'success',
            'Test response'
        );
        
        if ($testResult) {
            echo "✅ Notification logging test successful<br>";
        } else {
            echo "❌ Notification logging test failed<br>";
        }
    } catch (Exception $e) {
        echo "❌ Notification logging test error: " . $e->getMessage() . "<br>";
    }
    
    // Test 4: Check recent notification logs
    echo "<h2>Test 4: Recent Notification Logs</h2>";
    
    try {
        $stmt = $db->getPDO()->query("SELECT * FROM notification_logs ORDER BY created_at DESC LIMIT 5");
        $recentLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($recentLogs) > 0) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Event ID</th><th>Type</th><th>Success</th><th>Created</th></tr>";
            foreach ($recentLogs as $log) {
                echo "<tr>";
                echo "<td>" . $log['id'] . "</td>";
                echo "<td>" . $log['event_id'] . "</td>";
                echo "<td>" . htmlspecialchars($log['notification_type']) . "</td>";
                echo "<td>" . ($log['success'] ? 'Yes' : 'No') . "</td>";
                echo "<td>" . $log['created_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "No recent notification logs found<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error fetching recent logs: " . $e->getMessage() . "<br>";
    }
    
    echo "<h2>Test Summary</h2>";
    echo "<p>✅ Database structure test completed</p>";
    echo "<p>✅ FCM tokens check completed</p>";
    echo "<p>✅ Notification logging test completed</p>";
    echo "<p>✅ Recent logs check completed</p>";
    
    echo "<h2>Next Steps</h2>";
    echo "<p>1. Try creating a new event to test the full notification flow</p>";
    echo "<p>2. Check the browser console for any JavaScript errors</p>";
    echo "<p>3. Monitor the server logs for notification sending results</p>";
    
} catch (Exception $e) {
    echo "<h2>Test Failed</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
