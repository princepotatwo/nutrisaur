<?php
/**
 * Nutrisaur Configuration File
 * Railway Production Environment
 */

// Database Configuration - Parse Railway MYSQL_PUBLIC_URL or use fallbacks
function parseRailwayDatabaseUrl() {
    $mysqlUrl = $_ENV['MYSQL_PUBLIC_URL'] ?? null;
    
    if ($mysqlUrl) {
        // Parse: mysql://root:password@host:port/database
        $parsed = parse_url($mysqlUrl);
        
        // Debug logging
        error_log("Parsing MYSQL_PUBLIC_URL: " . $mysqlUrl);
        error_log("Parsed result: " . json_encode($parsed));
        
        if ($parsed && isset($parsed['host']) && isset($parsed['user']) && isset($parsed['pass'])) {
            $config = [
                'host' => $parsed['host'],
                'port' => $parsed['port'] ?? '3306',
                'database' => ltrim($parsed['path'] ?? '', '/'),
                'username' => $parsed['user'],
                'password' => urldecode($parsed['pass']) // Decode URL-encoded characters
            ];
            
            error_log("Using parsed config: " . json_encode(array_merge($config, ['password' => substr($config['password'], 0, 3) . '***'])));
            return $config;
        }
    }
    
    // Fallback values (these are working as shown in the diagnostic)
    error_log("Using fallback config");
    return [
        'host' => 'mainline.proxy.rlwy.net',
        'port' => '26063',
        'database' => 'railway',
        'username' => 'root',
        'password' => 'nZhQwfTnAJfFieCpIclAMtOQbBxcjwgy'
    ];
}

$dbConfig = parseRailwayDatabaseUrl();
$host = $dbConfig['host'];
$port = $dbConfig['port'];
$dbname = $dbConfig['database'];
$dbUsername = $dbConfig['username'];
$dbPassword = $dbConfig['password'];

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
    
    // Validate connection parameters
    if (empty($host) || empty($port) || empty($dbname) || empty($dbUsername) || empty($dbPassword)) {
        error_log("Database connection failed: Missing required parameters - Host: $host, Port: $port, DB: $dbname, User: $dbUsername");
        return null;
    }
    
    try {
        // Force TCP connection by explicitly specifying the port
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        
        error_log("Attempting database connection with DSN: mysql:host=$host;port=$port;dbname=$dbname");
        
        // Add additional options to force TCP connection
        $pdo = new PDO($dsn, $dbUsername, $dbPassword, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 10,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
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
        'mysql_public_url' => $_ENV['MYSQL_PUBLIC_URL'] ?? 'not_set',
        'connection_method' => $_ENV['MYSQL_PUBLIC_URL'] ? 'parsed_from_url' : 'fallback_values'
    ];
}
?>
