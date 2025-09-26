<?php
/**
 * Setup Google OAuth for Community Users
 * This script adds the necessary database fields for Google OAuth integration
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
    
    // Check if google_oauth column already exists
    $checkColumn = $pdo->query("SHOW COLUMNS FROM community_users LIKE 'google_oauth'");
    $columnExists = $checkColumn->rowCount() > 0;
    
    if ($columnExists) {
        echo json_encode([
            'success' => true,
            'message' => 'Google OAuth field already exists in community_users table',
            'action' => 'no_change_needed'
        ]);
        exit;
    }
    
    // Add the google_oauth column
    $sql = "ALTER TABLE community_users 
            ADD COLUMN google_oauth TINYINT(1) DEFAULT 0 COMMENT 'Flag to indicate if user signed up via Google OAuth'";
    
    $pdo->exec($sql);
    
    // Add index for faster lookups
    $pdo->exec("CREATE INDEX idx_community_users_google_oauth ON community_users(google_oauth)");
    
    // Update table comment
    $pdo->exec("ALTER TABLE community_users COMMENT = 'Community users table with support for both traditional and Google OAuth authentication'");
    
    // Verify the changes
    $verify = $pdo->query("DESCRIBE community_users");
    $columns = $verify->fetchAll(PDO::FETCH_ASSOC);
    
    $googleOauthColumn = null;
    foreach ($columns as $column) {
        if ($column['Field'] === 'google_oauth') {
            $googleOauthColumn = $column;
            break;
        }
    }
    
    if ($googleOauthColumn) {
        echo json_encode([
            'success' => true,
            'message' => 'Google OAuth field successfully added to community_users table',
            'action' => 'column_added',
            'column_info' => $googleOauthColumn
        ]);
    } else {
        throw new Exception('Failed to verify google_oauth column was added');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
