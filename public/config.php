<?php
/**
 * Nutrisaur Configuration File
 * Railway Production Environment
 */

// Database Configuration
$host = 'mainline.proxy.rlwy.net';
$port = '26063';
$dbname = 'railway';
$dbUsername = 'root';
$dbPassword = 'nZhQwfTnAJfFieCpIclAMtOQbBxcjwgy';

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
    global $host, $port, $dbname, $dbUsername, $dbPassword;
    
    try {
        $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $dbUsername, $dbPassword);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
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
?>
