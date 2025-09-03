<?php
/**
 * Database Status Endpoint
 * Enhanced diagnostics for Railway database connection issues
 */

header('Content-Type: application/json');

try {
    // Include the enhanced configuration
    require_once __DIR__ . "/config.php";
    
    $status = [
        'timestamp' => date('Y-m-d H:i:s'),
        'environment' => $_ENV['RAILWAY_ENVIRONMENT'] ?? 'unknown',
        'service' => $_ENV['RAILWAY_SERVICE_NAME'] ?? 'unknown',
        'environment_variables' => [
            'MYSQL_PUBLIC_URL' => isset($_ENV['MYSQL_PUBLIC_URL']) ? 'set' : 'not_set',
            'DATABASE_URL' => isset($_ENV['DATABASE_URL']) ? 'set' : 'not_set',
            'MYSQLHOST' => isset($_ENV['MYSQLHOST']) ? 'set' : 'not_set',
            'MYSQLPORT' => isset($_ENV['MYSQLPORT']) ? 'set' : 'not_set',
            'MYSQLUSER' => isset($_ENV['MYSQLUSER']) ? 'set' : 'not_set',
            'MYSQLPASSWORD' => isset($_ENV['MYSQLPASSWORD']) ? 'set' : 'not_set',
            'MYSQLDATABASE' => isset($_ENV['MYSQLDATABASE']) ? 'set' : 'not_set'
        ]
    ];
    
    // Test database connection using the enhanced config
    if (function_exists('getDatabaseConnection')) {
        $pdo = getDatabaseConnection();
        if ($pdo) {
            try {
                $stmt = $pdo->query("SELECT 1 as test");
                $result = $stmt->fetch();
                
                $status['database_status'] = [
                    'connected' => true,
                    'connection_type' => 'PDO',
                    'test_query' => 'success'
                ];
                
                // Get database info
                $stmt = $pdo->query("SELECT VERSION() as version");
                $version = $stmt->fetch();
                $status['database_info'] = [
                    'version' => $version['version'] ?? 'unknown',
                    'charset' => $pdo->query("SHOW VARIABLES LIKE 'character_set_database'")->fetch()['Value'] ?? 'unknown'
                ];
                
                // Check if basic tables exist
                $tables = [];
                $stmt = $pdo->query("SHOW TABLES");
                while ($row = $stmt->fetch()) {
                    $tables[] = $row[array_keys($row)[0]];
                }
                $status['database_tables'] = $tables;
                
            } catch (Exception $e) {
                $status['database_status'] = [
                    'connected' => true,
                    'connection_type' => 'PDO',
                    'test_query' => 'failed',
                    'error' => $e->getMessage()
                ];
            }
        } else {
            $status['database_status'] = [
                'connected' => false,
                'error' => 'getDatabaseConnection returned null'
            ];
        }
    } else {
        $status['database_status'] = [
            'connected' => false,
            'error' => 'getDatabaseConnection function not available'
        ];
    }
    
    // Check if database is available
    if (isset($status['database_status']['connected']) && $status['database_status']['connected']) {
        $status['status'] = 'healthy';
        $status['message'] = 'Database connection successful';
    } else {
        $status['status'] = 'unhealthy';
        $status['message'] = 'Database connection failed';
        $status['recommendations'] = [
            'Add a MySQL database service to your Railway project',
            'Set up database connection variables in Railway environment',
            'Ensure the database is accessible from your application',
            'Check Railway dashboard for database service status',
            'Wait 2-3 minutes after adding database for provisioning'
        ];
    }
    
    echo json_encode($status, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to initialize Database API',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s'),
        'recommendations' => [
            'Check if all required files are present',
            'Verify PHP configuration',
            'Check Railway deployment logs',
            'Ensure config.php is properly loaded'
        ]
    ], JSON_PRETTY_PRINT);
}
?>
