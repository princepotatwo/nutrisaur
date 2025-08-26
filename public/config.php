<?php
/**
 * Nutrisaur Configuration File
 * Railway Production Environment
 */

// Database Configuration - Use Railway environment variables
$mysql_url = $_ENV['MYSQL_URL'] ?? 'mysql://root:nZhQwfTnAJfFieCpIclAMtOQbBxcjwgy@mainline.proxy.rlwy.net:26063/railway';

// Parse the MySQL URL
function parseMySQLUrl($url) {
    $parsed = parse_url($url);
    return [
        'host' => $parsed['host'] ?? 'mainline.proxy.rlwy.net',
        'port' => $parsed['port'] ?? 26063,
        'username' => $parsed['user'] ?? 'root',
        'password' => $parsed['pass'] ?? 'nZhQwfTnAJfFieCpIclAMtOQbBxcjwgy',
        'database' => ltrim($parsed['path'] ?? 'railway', '/')
    ];
}

$db_config = parseMySQLUrl($mysql_url);

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

// Database connection function
function getDatabaseConnection() {
    global $db_config;
    
    try {
        $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['database']};charset=utf8mb4";
        $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 10
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
    global $db_config;
    
    try {
        $mysqli = new mysqli(
            $db_config['host'], 
            $db_config['username'], 
            $db_config['password'], 
            $db_config['database'], 
            $db_config['port']
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

// Debug function to show current config
function showDatabaseConfig() {
    global $db_config;
    return [
        'host' => $db_config['host'],
        'port' => $db_config['port'],
        'username' => $db_config['username'],
        'database' => $db_config['database'],
        'mysql_url' => $_ENV['MYSQL_URL'] ?? 'Not set'
    ];
}
?>
