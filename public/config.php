<?php
/**
 * Nutrisaur Configuration File
 * Railway Production Environment
 */

// Database Configuration - Original working configuration
$host = $_ENV['MYSQL_HOST'] ?? $_ENV['DB_HOST'] ?? $_ENV['DATABASE_HOST'] ?? 'mainline.proxy.rlwy.net';
$port = $_ENV['MYSQL_PORT'] ?? $_ENV['DB_PORT'] ?? $_ENV['DATABASE_PORT'] ?? '26063';
$dbname = $_ENV['MYSQL_DATABASE'] ?? $_ENV['DB_NAME'] ?? $_ENV['DATABASE_NAME'] ?? 'railway';
$dbUsername = $_ENV['MYSQL_USER'] ?? $_ENV['DB_USER'] ?? $_ENV['DATABASE_USER'] ?? 'root';
$dbPassword = $_ENV['MYSQL_PASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? $_ENV['DATABASE_PASSWORD'] ?? 'nZhQwfTnAJfFieCpIclAMtOQbBxcjwgy';

// Application Configuration
define('APP_NAME', 'Nutrisaur');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'production');

// Base URL for production
$base_url = 'https://nutrisaur-production.up.railway.app/';

// Error reporting for production
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Session configuration for Railway
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', 3600); // 1 hour
ini_set('session.cookie_lifetime', 3600); // 1 hour

// Database connection function
function getDatabaseConnection() {
    global $host, $port, $dbname, $dbUsername, $dbPassword;
    
    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUsername, $dbPassword, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 10,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]);
        return $pdo;
    } catch (PDOException $e) {
        // Log error but don't expose details to user
        error_log("Database connection failed: " . $e->getMessage());
        return null;
    }
}

// Legacy mysqli connection for backward compatibility
function getMysqliConnection() {
    global $host, $port, $dbname, $dbUsername, $dbPassword;
    
    try {
        $mysqli = new mysqli($host, $dbUsername, $dbPassword, $dbname, $port);
        
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

// Test database connection
function testDatabaseConnection() {
    $pdo = getDatabaseConnection();
    if ($pdo) {
        try {
            $pdo->query("SELECT 1");
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    return false;
}

// Debug function to show current database configuration
function getDatabaseConfig() {
    global $host, $port, $dbname, $dbUsername, $dbPassword;
    return [
        'host' => $host,
        'port' => $port,
        'database' => $dbname,
        'username' => $dbUsername,
        'password' => substr($dbPassword, 0, 3) . '***',
        'env_vars' => [
            'MYSQL_HOST' => $_ENV['MYSQL_HOST'] ?? 'not_set',
            'MYSQL_PORT' => $_ENV['MYSQL_PORT'] ?? 'not_set',
            'MYSQL_DATABASE' => $_ENV['MYSQL_DATABASE'] ?? 'not_set',
            'MYSQL_USER' => $_ENV['MYSQL_USER'] ?? 'not_set',
            'MYSQL_PASSWORD' => $_ENV['MYSQL_PASSWORD'] ? 'set' : 'not_set',
            'DATABASE_HOST' => $_ENV['DATABASE_HOST'] ?? 'not_set',
            'DATABASE_PORT' => $_ENV['DATABASE_PORT'] ?? 'not_set',
            'DATABASE_NAME' => $_ENV['DATABASE_NAME'] ?? 'not_set',
            'DATABASE_USER' => $_ENV['DATABASE_USER'] ?? 'not_set',
            'DATABASE_PASSWORD' => $_ENV['DATABASE_PASSWORD'] ? 'set' : 'not_set'
        ]
    ];
}
?>
