<?php
/**
 * Nutrisaur Configuration File
 * Railway Production Environment
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Database Configuration - HARDCODED with working values from MySQL Workbench
$mysql_host = 'mainline.proxy.rlwy.net';
$mysql_port = 26063;
$mysql_user = 'root';
$mysql_password = 'nZhQwfTnAJfFieCpIclAMtOQbBxcjwgy';
$mysql_database = 'railway';

// Application Configuration
define('APP_NAME', 'Nutrisaur');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'production');

// Base URL for production
$base_url = 'https://nutrisaur-production.up.railway.app/';

// Database connection function with detailed logging
function getDatabaseConnection() {
    global $mysql_host, $mysql_port, $mysql_user, $mysql_password, $mysql_database;
    
    echo "🔍 Attempting database connection...\n";
    echo "📍 Host: $mysql_host\n";
    echo "🚪 Port: $mysql_port\n";
    echo "👤 User: $mysql_user\n";
    echo "🗄️ Database: $mysql_database\n";
    echo "🔑 Password: " . substr($mysql_password, 0, 10) . "...\n\n";
    
    // Test socket connection first
    echo "🔌 Testing socket connection...\n";
    $socket = @fsockopen($mysql_host, $mysql_port, $errno, $errstr, 10);
    if ($socket) {
        echo "✅ Socket connection successful!\n";
        fclose($socket);
    } else {
        echo "❌ Socket connection failed: $errstr ($errno)\n";
    }
    echo "\n";
    
    try {
        echo "🚀 Creating PDO connection...\n";
        $dsn = "mysql:host={$mysql_host};port={$mysql_port};dbname={$mysql_database};charset=utf8mb4";
        echo "🔗 DSN: $dsn\n";
        
        $pdo = new PDO($dsn, $mysql_user, $mysql_password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 10
        ]);
        
        echo "✅ PDO connection successful!\n";
        return $pdo;
        
    } catch (PDOException $e) {
        echo "💥 PDO connection failed!\n";
        echo "❌ Error: " . $e->getMessage() . "\n";
        echo "🔍 Error Code: " . $e->getCode() . "\n";
        echo "📍 File: " . $e->getFile() . "\n";
        echo "🚪 Line: " . $e->getLine() . "\n";
        
        // Log error
        error_log("Database connection failed: " . $e->getMessage());
        return null;
    }
}

// Legacy mysqli connection for backward compatibility
function getMysqliConnection() {
    global $mysql_host, $mysql_port, $mysql_user, $mysql_password, $mysql_database;
    
    echo "🔍 Attempting MySQLi connection...\n";
    
    try {
        $mysqli = new mysqli(
            $mysql_host, 
            $mysql_user, 
            $mysql_password, 
            $mysql_database, 
            $mysql_port
        );
        
        if ($mysqli->connect_error) {
            echo "❌ MySQLi connection failed: " . $mysqli->connect_error . "\n";
            echo "🔍 Error Code: " . $mysqli->connect_errno . "\n";
            throw new Exception("Connection failed: " . $mysqli->connect_error);
        }
        
        echo "✅ MySQLi connection successful!\n";
        echo "📊 Server info: " . $mysqli->server_info . "\n";
        
        $mysqli->set_charset("utf8mb4");
        return $mysqli;
        
    } catch (Exception $e) {
        echo "💥 MySQLi connection failed: " . $e->getMessage() . "\n";
        error_log("MySQLi connection failed: " . $e->getMessage());
        return null;
    }
}

// Test database connection
function testDatabaseConnection() {
    echo "🧪 Testing database connection...\n";
    $pdo = getDatabaseConnection();
    if ($pdo) {
        try {
            echo "🔍 Testing basic query...\n";
            $result = $pdo->query("SELECT 1 as test");
            $row = $result->fetch();
            echo "✅ Query successful: " . $row['test'] . "\n";
            return true;
        } catch (PDOException $e) {
            echo "❌ Query test failed: " . $e->getMessage() . "\n";
            return false;
        }
    }
    return false;
}

// Debug function to show current config
function showDatabaseConfig() {
    global $mysql_host, $mysql_port, $mysql_user, $mysql_database;
    return [
        'host' => $mysql_host,
        'port' => $mysql_port,
        'username' => $mysql_user,
        'database' => $mysql_database,
        'method' => 'HARDCODED - Working values from MySQL Workbench'
    ];
}
?>
