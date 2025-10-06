<?php
/**
 * Setup Password Reset for Community Users
 * This script adds the necessary database fields for password reset functionality
 */

require_once 'config.php';

// Set content type
header('Content-Type: application/json');

try {
    // Get database connection
    $pdo = getDatabaseConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Check if password_reset_code column already exists
    $checkCodeColumn = $pdo->query("SHOW COLUMNS FROM community_users LIKE 'password_reset_code'");
    $codeColumnExists = $checkCodeColumn->rowCount() > 0;
    
    // Check if password_reset_expires column already exists
    $checkExpiresColumn = $pdo->query("SHOW COLUMNS FROM community_users LIKE 'password_reset_expires'");
    $expiresColumnExists = $checkExpiresColumn->rowCount() > 0;
    
    if ($codeColumnExists && $expiresColumnExists) {
        echo json_encode([
            'success' => true,
            'message' => 'Password reset fields already exist in community_users table',
            'action' => 'no_change_needed'
        ]);
        exit;
    }
    
    // Add the password reset columns only if they don't exist
    if (!$codeColumnExists) {
        $sql1 = "ALTER TABLE community_users 
                ADD COLUMN password_reset_code VARCHAR(4) DEFAULT NULL COMMENT '4-digit password reset code'";
        $pdo->exec($sql1);
    }
    
    if (!$expiresColumnExists) {
        $sql2 = "ALTER TABLE community_users 
                ADD COLUMN password_reset_expires DATETIME DEFAULT NULL COMMENT 'Password reset code expiration time'";
        $pdo->exec($sql2);
    }
    
    // Add indexes for faster lookups (if not exists)
    try {
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_community_users_reset_code ON community_users(password_reset_code)");
    } catch (Exception $e) {
        // Index might already exist, ignore error
    }
    
    try {
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_community_users_reset_expires ON community_users(password_reset_expires)");
    } catch (Exception $e) {
        // Index might already exist, ignore error
    }
    
    // Update table comment
    $pdo->exec("ALTER TABLE community_users COMMENT = 'Community users table with support for password reset functionality'");
    
    // Verify the changes
    $verify = $pdo->query("DESCRIBE community_users");
    $columns = $verify->fetchAll(PDO::FETCH_ASSOC);
    
    $resetCodeColumn = null;
    $resetExpiresColumn = null;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'password_reset_code') {
            $resetCodeColumn = $column;
        }
        if ($column['Field'] === 'password_reset_expires') {
            $resetExpiresColumn = $column;
        }
    }
    
    if ($resetCodeColumn && $resetExpiresColumn) {
        echo json_encode([
            'success' => true,
            'message' => 'Password reset fields added successfully to community_users table',
            'action' => 'columns_added',
            'columns' => [
                'password_reset_code' => $resetCodeColumn,
                'password_reset_expires' => $resetExpiresColumn
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to verify password reset columns were added',
            'action' => 'verification_failed'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'action' => 'error'
    ]);
}
?>
