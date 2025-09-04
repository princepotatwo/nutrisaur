<?php
/**
 * Database Connection Debug Script
 * This will help us understand why the database connection is failing
 */

echo "<h1>Database Connection Debug</h1>\n";
echo "<pre>\n";

// 1. Check all environment variables
echo "=== ENVIRONMENT VARIABLES ===\n";
$envVars = [
    'MYSQL_PUBLIC_URL',
    'MYSQL_HOST', 'MYSQL_PORT', 'MYSQL_DATABASE', 'MYSQL_USER', 'MYSQL_PASSWORD',
    'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD',
    'DATABASE_HOST', 'DATABASE_PORT', 'DATABASE_NAME', 'DATABASE_USER', 'DATABASE_PASSWORD',
    'RAILWAY_MYSQL_HOST', 'RAILWAY_MYSQL_PORT', 'RAILWAY_MYSQL_DATABASE', 'RAILWAY_MYSQL_USER', 'RAILWAY_MYSQL_PASSWORD'
];

foreach ($envVars as $var) {
    $value = $_ENV[$var] ?? 'NOT_SET';
    if ($value !== 'NOT_SET' && strpos($var, 'PASSWORD') !== false) {
        $value = substr($value, 0, 3) . '***';
    }
    echo "$var: $value\n";
}

echo "\n=== CURRENT CONFIG.PHP VALUES ===\n";
require_once __DIR__ . "/config.php";

// Get the current database config
$currentConfig = getDatabaseConfig();
echo "Host: " . $currentConfig['host'] . "\n";
echo "Port: " . $currentConfig['port'] . "\n";
echo "Database: " . $currentConfig['database'] . "\n";
echo "Username: " . $currentConfig['username'] . "\n";
echo "Password: " . $currentConfig['password'] . "\n";
echo "Connection Method: " . $currentConfig['connection_method'] . "\n";

echo "\n=== TESTING CONNECTIONS ===\n";

// Test 1: Direct PDO connection with current config
echo "Test 1: Direct PDO with current config\n";
try {
    $dsn = "mysql:host={$currentConfig['host']};port={$currentConfig['port']};dbname={$currentConfig['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $currentConfig['username'], $currentConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10
    ]);
    echo "✅ PDO Connection SUCCESS\n";
    
    // Test query
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "✅ Query test: " . $result['test'] . "\n";
    
} catch (Exception $e) {
    echo "❌ PDO Connection FAILED: " . $e->getMessage() . "\n";
}

// Test 2: Direct MySQLi connection
echo "\nTest 2: Direct MySQLi with current config\n";
try {
    $mysqli = new mysqli($currentConfig['host'], $currentConfig['username'], $currentConfig['password'], $currentConfig['database'], $currentConfig['port']);
    
    if ($mysqli->connect_error) {
        throw new Exception("Connection failed: " . $mysqli->connect_error);
    }
    
    echo "✅ MySQLi Connection SUCCESS\n";
    
    // Test query
    $result = $mysqli->query("SELECT 1 as test");
    $row = $result->fetch_assoc();
    echo "✅ Query test: " . $row['test'] . "\n";
    
} catch (Exception $e) {
    echo "❌ MySQLi Connection FAILED: " . $e->getMessage() . "\n";
}

// Test 3: Using DatabaseAPI
echo "\nTest 3: Using DatabaseAPI\n";
try {
    require_once __DIR__ . "/api/DatabaseAPI.php";
    $db = new DatabaseAPI();
    
    if ($db->testConnection()) {
        echo "✅ DatabaseAPI Connection SUCCESS\n";
        
        // Test query through DatabaseAPI
        $pdo = $db->getPDO();
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        echo "✅ Query test: " . $result['test'] . "\n";
    } else {
        echo "❌ DatabaseAPI Connection FAILED\n";
    }
    
} catch (Exception $e) {
    echo "❌ DatabaseAPI FAILED: " . $e->getMessage() . "\n";
}

// Test 4: Try different host formats
echo "\nTest 4: Testing different host formats\n";
$hostVariations = [
    $currentConfig['host'],
    'localhost',
    '127.0.0.1',
    'mysql',
    'db'
];

foreach ($hostVariations as $host) {
    try {
        $dsn = "mysql:host=$host;port={$currentConfig['port']};dbname={$currentConfig['database']};charset=utf8mb4";
        $pdo = new PDO($dsn, $currentConfig['username'], $currentConfig['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        echo "✅ Host '$host' works!\n";
        break;
    } catch (Exception $e) {
        echo "❌ Host '$host' failed: " . $e->getMessage() . "\n";
    }
}

echo "\n=== RECOMMENDATIONS ===\n";
if (!isset($_ENV['MYSQL_PUBLIC_URL'])) {
    echo "1. Railway is not providing MYSQL_PUBLIC_URL\n";
    echo "2. Check if database service is linked to your app\n";
    echo "3. Try adding a MySQL database service in Railway\n";
} else {
    echo "1. MYSQL_PUBLIC_URL is set: " . $_ENV['MYSQL_PUBLIC_URL'] . "\n";
    echo "2. Check if the URL format is correct\n";
}

echo "\n=== END DEBUG ===\n";
echo "</pre>\n";
?>
