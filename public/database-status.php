<?php
/**
 * Database Status Endpoint
 * Helps debug database connection issues in Railway deployment
 */

header('Content-Type: application/json');

// Include the Database API
require_once __DIR__ . "/api/DatabaseAPI.php";

try {
    $db = new DatabaseAPI();
    
    $status = [
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
        $status['status'] = 'healthy';
        $status['message'] = 'Database connection successful';
    } else {
        $status['status'] = 'unhealthy';
        $status['message'] = 'Database connection failed';
        $status['recommendations'] = [
            'Check if MySQL database is provisioned in Railway',
            'Verify database connection variables are set',
            'Ensure database is accessible from the application'
        ];
    }
    
    echo json_encode($status, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to initialize Database API',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
?>
