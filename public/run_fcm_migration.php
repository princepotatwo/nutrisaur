<?php
// FCM Token Migration Script
// Run this once to add FCM token column to your Railway database

require_once __DIR__ . '/api/DatabaseHelper.php';

try {
    $db = DatabaseHelper::getInstance();
    
    if (!$db->isAvailable()) {
        die("Database connection not available");
    }
    
    echo "<h2>FCM Token Migration</h2>";
    echo "<p>Adding FCM token column to community_users table...</p>";
    
    // Add FCM token column
    $sql1 = "ALTER TABLE `community_users` 
             ADD COLUMN `fcm_token` VARCHAR(255) DEFAULT NULL 
             COMMENT 'Firebase Cloud Messaging token for push notifications' 
             AFTER `screening_date`";
    
    $result1 = $db->query($sql1);
    
    if ($result1['success']) {
        echo "<p style='color: green;'>✅ FCM token column added successfully!</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ FCM token column might already exist: " . ($result1['error'] ?? 'Unknown error') . "</p>";
    }
    
    // Add index for FCM token
    $sql2 = "CREATE INDEX `idx_fcm_token` ON `community_users` (`fcm_token`)";
    
    $result2 = $db->query($sql2);
    
    if ($result2['success']) {
        echo "<p style='color: green;'>✅ FCM token index created successfully!</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ FCM token index might already exist: " . ($result2['error'] ?? 'Unknown error') . "</p>";
    }
    
    // Show table structure
    $describe = $db->query("DESCRIBE `community_users`");
    
    if ($describe['success']) {
        echo "<h3>Updated Table Structure:</h3>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        foreach ($describe['data'] as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<p style='color: blue;'><strong>Migration completed! You can now delete this file for security.</strong></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
