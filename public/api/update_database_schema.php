<?php
/**
 * Database Schema Update Script
 * Adds parent contact information columns to community_users table
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    require_once __DIR__ . '/../../config.php';
    $pdo = getDatabaseConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Check if columns already exist
    $checkColumns = $pdo->query("SHOW COLUMNS FROM community_users LIKE 'parent_name'");
    if ($checkColumns->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Parent contact columns already exist',
            'columns_exist' => true
        ]);
        exit;
    }
    
    // Add parent contact information columns
    $sql = "ALTER TABLE community_users 
            ADD COLUMN parent_name VARCHAR(255) DEFAULT NULL,
            ADD COLUMN parent_phone VARCHAR(20) DEFAULT NULL,
            ADD COLUMN parent_email VARCHAR(255) DEFAULT NULL";
    
    $pdo->exec($sql);
    
    // Add indexes for better performance
    $pdo->exec("CREATE INDEX idx_parent_name ON community_users(parent_name)");
    $pdo->exec("CREATE INDEX idx_parent_phone ON community_users(parent_phone)");
    $pdo->exec("CREATE INDEX idx_parent_email ON community_users(parent_email)");
    
    echo json_encode([
        'success' => true,
        'message' => 'Parent contact columns added successfully',
        'columns_added' => ['parent_name', 'parent_phone', 'parent_email']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error updating database schema: ' . $e->getMessage()
    ]);
}
?>
