<?php
/**
 * Test Remote Database Connection
 * Use this script to test database connectivity from any laptop
 */

// Database connection details
$host = 'mainline.proxy.rlwy.net';
$port = '26063';
$dbname = 'railway';
$username = 'root';
$password = 'nZhQwfTnAJfFieCpIclAMtOQbBxcjwgy';

echo "Testing connection to Railway MySQL database...\n";
echo "Host: $host\n";
echo "Port: $port\n";
echo "Database: $dbname\n";
echo "Username: $username\n";
echo "Password: " . substr($password, 0, 3) . "***\n\n";

try {
    // Test PDO connection
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ]);
    
    echo "✅ PDO Connection: SUCCESS\n";
    
    // Test basic query
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "✅ Query Test: SUCCESS (result: " . $result['test'] . ")\n";
    
    // Show database info
    $stmt = $pdo->query("SELECT DATABASE() as current_db, USER() as current_user, VERSION() as mysql_version");
    $info = $stmt->fetch();
    echo "✅ Database Info:\n";
    echo "   Current Database: " . $info['current_db'] . "\n";
    echo "   Current User: " . $info['current_user'] . "\n";
    echo "   MySQL Version: " . $info['mysql_version'] . "\n";
    
    // Show tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "✅ Available Tables (" . count($tables) . "):\n";
    foreach ($tables as $table) {
        echo "   - $table\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Connection Failed: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Connection test completed.\n";
?>



