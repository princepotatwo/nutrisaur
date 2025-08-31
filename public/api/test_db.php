<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'status' => 'Testing database connection...',
    'timestamp' => date('Y-m-d H:i:s')
]);

// Test Railway environment variables
$env_vars = [
    'MYSQL_PUBLIC_URL' => $_ENV['MYSQL_PUBLIC_URL'] ?? 'NOT_SET',
    'RAILWAY_ENVIRONMENT' => $_ENV['RAILWAY_ENVIRONMENT'] ?? 'NOT_SET',
    'RAILWAY_SERVICE_NAME' => $_ENV['RAILWAY_SERVICE_NAME'] ?? 'NOT_SET'
];

echo "\n" . json_encode([
    'environment' => $env_vars
]);

// Test database connection
try {
    // Railway database credentials
    $mysql_host = 'mainline.proxy.rlwy.net';
    $mysql_port = 26063;
    $mysql_user = 'root';
    $mysql_password = 'nZhQwfTnAJfFieCpIclAMtOQbBxcjwgy';
    $mysql_database = 'railway';
    
    // If MYSQL_PUBLIC_URL is set, use it
    if (isset($_ENV['MYSQL_PUBLIC_URL'])) {
        $mysql_url = $_ENV['MYSQL_PUBLIC_URL'];
        $pattern = '/mysql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/(.+)/';
        if (preg_match($pattern, $mysql_url, $matches)) {
            $mysql_user = $matches[1];
            $mysql_password = $matches[2];
            $mysql_host = $matches[3];
            $mysql_port = $matches[4];
            $mysql_database = $matches[5];
        }
    }
    
    echo "\n" . json_encode([
        'connection_details' => [
            'host' => $mysql_host,
            'port' => $mysql_port,
            'user' => $mysql_user,
            'database' => $mysql_database,
            'password_length' => strlen($mysql_password)
        ]
    ]);
    
    $dsn = "mysql:host={$mysql_host};port={$mysql_port};dbname={$mysql_database};charset=utf8mb4";
    $pdoOptions = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10
    ];
    
    $conn = new PDO($dsn, $mysql_user, $mysql_password, $pdoOptions);
    
    echo "\n" . json_encode([
        'database_status' => 'CONNECTED_SUCCESSFULLY',
        'connection_info' => $conn->getAttribute(PDO::ATTR_CONNECTION_STATUS)
    ]);
    
    // Test a simple query
    $stmt = $conn->query("SELECT 1 as test");
    $result = $stmt->fetch();
    
    echo "\n" . json_encode([
        'query_test' => 'SUCCESS',
        'result' => $result
    ]);
    
    // Check what tables exist
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "\n" . json_encode([
        'tables_found' => count($tables),
        'table_list' => $tables
    ]);
    
} catch (Exception $e) {
    echo "\n" . json_encode([
        'database_status' => 'CONNECTION_FAILED',
        'error' => $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
}
?>
