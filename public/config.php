<?php
/**
 * Nutrisaur Configuration File
 * Railway Production Environment
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Database Configuration - Parse from Railway's MYSQL_PUBLIC_URL
$mysql_host = 'mainline.proxy.rlwy.net';
$mysql_port = 26063;
$mysql_user = 'root';
$mysql_password = 'nZhQwfTnAJfFieCpIclAMtOQbBxcjwgy';
$mysql_database = 'railway';

// If MYSQL_PUBLIC_URL is set (Railway sets this), parse it
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

// Application Configuration
define('APP_NAME', 'Nutrisaur');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'production');

// Base URL for production
$base_url = 'https://nutrisaur-production.up.railway.app/';

// Database connection function with detailed logging
function getDatabaseConnection() {
    global $mysql_host, $mysql_port, $mysql_user, $mysql_password, $mysql_database;
    
    try {
        $dsn = "mysql:host={$mysql_host};port={$mysql_port};dbname={$mysql_database};charset=utf8mb4";
        $pdo = new PDO($dsn, $mysql_user, $mysql_password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 10
        ]);
        
        return $pdo;
        
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return null;
    }
}

// Legacy mysqli connection for backward compatibility
function getMysqliConnection() {
    global $mysql_host, $mysql_port, $mysql_user, $mysql_password, $mysql_database;
    
    try {
        $mysqli = new mysqli(
            $mysql_host, 
            $mysql_user, 
            $mysql_password, 
            $mysql_database, 
            $mysql_port
        );
        
        if ($mysqli->connect_error) {
            throw new Exception("Connection failed: " . $mysqli->connect_error);
        }
        
        $mysqli->set_charset("utf8mb4");
        return $mysqli;
        
    } catch (Exception $e) {
        error_log("MySQLi connection failed: " . $e->getMessage());
        return null;
    }
}

// Test database connection function
function testDatabaseConnection() {
    $pdo = getDatabaseConnection();
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT 1 as test");
            $result = $stmt->fetch();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    return false;
}

// Show database configuration (for debugging)
function showDatabaseConfig() {
    global $mysql_host, $mysql_port, $mysql_user, $mysql_database;
    
    echo "🔧 Database Configuration:\n";
    echo "📍 Host: $mysql_host\n";
    echo "🚪 Port: $mysql_port\n";
    echo "👤 User: $mysql_user\n";
    echo "🗄️ Database: $mysql_database\n";
    echo "🔑 Password: " . str_repeat('*', 10) . "\n\n";
    
    echo "🌍 Environment Variables:\n";
    echo "📍 MYSQLHOST: " . ($_ENV['MYSQLHOST'] ?? 'NOT SET') . "\n";
    echo "🚪 MYSQLPORT: " . ($_ENV['MYSQLPORT'] ?? 'NOT SET') . "\n";
    echo "👤 MYSQLUSER: " . ($_ENV['MYSQLUSER'] ?? 'NOT SET') . "\n";
    echo "🗄️ MYSQLDATABASE: " . ($_ENV['MYSQLDATABASE'] ?? 'NOT SET') . "\n";
    echo "🔗 MYSQL_URL: " . ($_ENV['MYSQL_URL'] ?? 'NOT SET') . "\n";
    echo "🔗 MYSQL_PUBLIC_URL: " . ($_ENV['MYSQL_PUBLIC_URL'] ?? 'NOT SET') . "\n\n";
}

// Note: $conn variable is not created automatically
// Use getDatabaseConnection() function when you need a database connection
?>
