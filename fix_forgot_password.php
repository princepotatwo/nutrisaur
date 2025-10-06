<?php
/**
 * Fix Forgot Password Database Issue
 * This script adds the missing password reset columns to the community_users table
 */

require_once 'public/config.php';

// Set content type
header('Content-Type: application/json');

try {
    // Get database connection
    $pdo = getDatabaseConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    echo "Starting forgot password database fix...\n";
    
    // Check if password_reset_code column exists
    $checkCodeColumn = $pdo->query("SHOW COLUMNS FROM community_users LIKE 'password_reset_code'");
    $codeColumnExists = $checkCodeColumn->rowCount() > 0;
    
    // Check if password_reset_expires column exists
    $checkExpiresColumn = $pdo->query("SHOW COLUMNS FROM community_users LIKE 'password_reset_expires'");
    $expiresColumnExists = $checkExpiresColumn->rowCount() > 0;
    
    echo "Current status:\n";
    echo "- password_reset_code column exists: " . ($codeColumnExists ? "YES" : "NO") . "\n";
    echo "- password_reset_expires column exists: " . ($expiresColumnExists ? "YES" : "NO") . "\n";
    
    // Add the password reset columns if they don't exist
    if (!$codeColumnExists) {
        echo "Adding password_reset_code column...\n";
        $sql1 = "ALTER TABLE community_users 
                ADD COLUMN password_reset_code VARCHAR(4) DEFAULT NULL COMMENT '4-digit password reset code'";
        $pdo->exec($sql1);
        echo "✓ password_reset_code column added successfully\n";
    } else {
        echo "✓ password_reset_code column already exists\n";
    }
    
    if (!$expiresColumnExists) {
        echo "Adding password_reset_expires column...\n";
        $sql2 = "ALTER TABLE community_users 
                ADD COLUMN password_reset_expires DATETIME DEFAULT NULL COMMENT 'Password reset code expiration time'";
        $pdo->exec($sql2);
        echo "✓ password_reset_expires column added successfully\n";
    } else {
        echo "✓ password_reset_expires column already exists\n";
    }
    
    // Add indexes for faster lookups (ignore errors if they already exist)
    try {
        $pdo->exec("CREATE INDEX idx_community_users_reset_code ON community_users(password_reset_code)");
        echo "✓ Index on password_reset_code created\n";
    } catch (Exception $e) {
        echo "ℹ Index on password_reset_code already exists or error: " . $e->getMessage() . "\n";
    }
    
    try {
        $pdo->exec("CREATE INDEX idx_community_users_reset_expires ON community_users(password_reset_expires)");
        echo "✓ Index on password_reset_expires created\n";
    } catch (Exception $e) {
        echo "ℹ Index on password_reset_expires already exists or error: " . $e->getMessage() . "\n";
    }
    
    // Verify the changes
    echo "\nVerifying changes...\n";
    $result = $pdo->query("DESCRIBE community_users");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    
    $hasResetCode = false;
    $hasResetExpires = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'password_reset_code') {
            $hasResetCode = true;
            echo "✓ password_reset_code: " . $column['Type'] . " " . $column['Null'] . " " . $column['Default'] . "\n";
        }
        if ($column['Field'] === 'password_reset_expires') {
            $hasResetExpires = true;
            echo "✓ password_reset_expires: " . $column['Type'] . " " . $column['Null'] . " " . $column['Default'] . "\n";
        }
    }
    
    if ($hasResetCode && $hasResetExpires) {
        echo "\n🎉 SUCCESS: Forgot password functionality should now work!\n";
        echo "The community_users table now has the required columns for password reset.\n";
        
        // Test the API endpoint
        echo "\nTesting forgot password API...\n";
        $testEmail = "test@example.com";
        $testData = [
            'email' => $testEmail,
            'action' => 'forgot_password_community'
        ];
        
        // Simulate the API call
        $_POST = $testData;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        // Capture output
        ob_start();
        include 'public/api/DatabaseAPI.php';
        $output = ob_get_clean();
        
        echo "API test result: " . $output . "\n";
        
    } else {
        echo "\n❌ ERROR: Some columns are still missing!\n";
        echo "password_reset_code exists: " . ($hasResetCode ? "YES" : "NO") . "\n";
        echo "password_reset_expires exists: " . ($hasResetExpires ? "YES" : "NO") . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
