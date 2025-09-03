<?php
/**
 * Database Setup Script for Railway Deployment
 * This script helps set up the database and provides diagnostic information
 */

header('Content-Type: application/json');

// Include the Database API
require_once __DIR__ . "/api/DatabaseAPI.php";

try {
    $db = new DatabaseAPI();
    
    $setupInfo = [
        'timestamp' => date('Y-m-d H:i:s'),
        'environment' => $_ENV['RAILWAY_ENVIRONMENT'] ?? 'unknown',
        'service' => $_ENV['RAILWAY_SERVICE_NAME'] ?? 'unknown',
        'database_status' => $db->getDatabaseStatus(),
        'environment_variables' => [
            'MYSQL_PUBLIC_URL' => isset($_ENV['MYSQL_PUBLIC_URL']) ? 'set' : 'not_set',
            'DATABASE_URL' => isset($_ENV['DATABASE_URL']) ? 'set' : 'not_set',
            'DB_HOST' => isset($_ENV['DB_HOST']) ? 'set' : 'not_set',
            'DB_USER' => isset($_ENV['DB_USER']) ? 'set' : 'not_set',
            'DB_PASS' => isset($_ENV['DB_PASS']) ? 'set' : 'not_set',
            'DB_NAME' => isset($_ENV['DB_NAME']) ? 'set' : 'not_set'
        ]
    ];
    
    // Check if database is available
    if ($db->isDatabaseAvailable()) {
        $setupInfo['status'] = 'success';
        $setupInfo['message'] = 'Database connection successful';
        
        // Try to create basic tables if they don't exist
        $pdo = $db->getPDO();
        if ($pdo) {
            try {
                // Create users table if it doesn't exist
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS users (
                        user_id INT AUTO_INCREMENT PRIMARY KEY,
                        username VARCHAR(50) UNIQUE NOT NULL,
                        email VARCHAR(100) UNIQUE NOT NULL,
                        password VARCHAR(255) NOT NULL,
                        is_admin BOOLEAN DEFAULT FALSE,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        last_login TIMESTAMP NULL
                    )
                ");
                
                // Create admin table if it doesn't exist
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS admin (
                        admin_id INT AUTO_INCREMENT PRIMARY KEY,
                        username VARCHAR(50) UNIQUE NOT NULL,
                        email VARCHAR(100) UNIQUE NOT NULL,
                        password VARCHAR(255) NOT NULL,
                        role VARCHAR(50) DEFAULT 'admin',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )
                ");
                
                // Create fcm_tokens table if it doesn't exist
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS fcm_tokens (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        fcm_token TEXT NOT NULL,
                        device_name VARCHAR(100),
                        user_email VARCHAR(100),
                        is_active BOOLEAN DEFAULT TRUE,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )
                ");
                
                $setupInfo['tables_created'] = true;
                $setupInfo['message'] = 'Database connection successful and basic tables created';
                
            } catch (Exception $e) {
                $setupInfo['table_creation_error'] = $e->getMessage();
                $setupInfo['message'] = 'Database connected but table creation failed';
            }
        }
        
    } else {
        $setupInfo['status'] = 'error';
        $setupInfo['message'] = 'Database connection failed';
        $setupInfo['recommendations'] = [
            'Add a MySQL database service to your Railway project',
            'Set up database connection variables in Railway environment',
            'Ensure the database is accessible from your application',
            'Check Railway dashboard for database service status'
        ];
        
        // Show current config values (without sensitive data)
        $setupInfo['current_config'] = [
            'host' => $_ENV['MYSQL_HOST'] ?? 'not_set',
            'port' => $_ENV['MYSQL_PORT'] ?? 'not_set',
            'database' => $_ENV['MYSQL_DATABASE'] ?? 'not_set',
            'user_set' => isset($_ENV['MYSQL_USER']) ? 'yes' : 'no',
            'password_set' => isset($_ENV['MYSQL_PASSWORD']) ? 'yes' : 'no'
        ];
    }
    
    echo json_encode($setupInfo, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to initialize Database API',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s'),
        'recommendations' => [
            'Check if all required files are present',
            'Verify PHP configuration',
            'Check Railway deployment logs'
        ]
    ], JSON_PRETTY_PRINT);
}
?>
