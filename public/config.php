<?php
/**
 * Nutrisaur Configuration File
 * Railway Production Environment
 */

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

// Error reporting for production
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Database connection function
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
        // Log error but don't expose details to user
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
