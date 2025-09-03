<?php
/**
 * Nutrisaur Configuration File
 * Railway Production Environment - Enhanced for Railway Compatibility
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Railway Database Configuration - Multiple connection methods
class RailwayDatabaseConfig {
    private $connectionAttempts = 0;
    private $maxAttempts = 3;
    private $retryDelay = 2; // seconds
    
    public function getConnectionDetails() {
        $configs = [];
        
        // Method 1: MYSQL_PUBLIC_URL (Railway's preferred method)
        if (isset($_ENV['MYSQL_PUBLIC_URL'])) {
            $configs[] = $this->parseRailwayUrl($_ENV['MYSQL_PUBLIC_URL']);
        }
        
        // Method 2: Individual Railway environment variables
        if (isset($_ENV['MYSQLHOST']) && isset($_ENV['MYSQLUSER'])) {
            $configs[] = [
                'host' => $_ENV['MYSQLHOST'],
                'port' => $_ENV['MYSQLPORT'] ?? 3306,
                'user' => $_ENV['MYSQLUSER'],
                'password' => $_ENV['MYSQLPASSWORD'] ?? '',
                'database' => $_ENV['MYSQLDATABASE'] ?? 'railway',
                'method' => 'railway_env'
            ];
        }
        
        // Method 3: DATABASE_URL (alternative Railway format)
        if (isset($_ENV['DATABASE_URL'])) {
            $configs[] = $this->parseRailwayUrl($_ENV['DATABASE_URL']);
        }
        
        // Method 4: MYSQL_URL (another Railway format)
        if (isset($_ENV['MYSQL_URL'])) {
            $configs[] = $this->parseRailwayUrl($_ENV['MYSQL_URL']);
        }
        
        // Method 5: Fallback to hardcoded values (for local development)
        $configs[] = [
            'host' => 'mainline.proxy.rlwy.net',
            'port' => 26063,
            'user' => 'root',
            'password' => 'nZhQwfTnAJfFieCpIclAMtOQbBxcjwgy',
            'database' => 'railway',
            'method' => 'fallback'
        ];
        
        return $configs;
    }
    
    private function parseRailwayUrl($url) {
        $pattern = '/mysql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/(.+)/';
        if (preg_match($pattern, $url, $matches)) {
            return [
                'host' => $matches[3],
                'port' => $matches[4],
                'user' => $matches[1],
                'password' => $matches[2],
                'database' => $matches[5],
                'method' => 'url_parsed'
            ];
        }
        
        // Try alternative format without port
        $pattern2 = '/mysql:\/\/([^:]+):([^@]+)@([^\/]+)\/(.+)/';
        if (preg_match($pattern2, $url, $matches)) {
            return [
                'host' => $matches[3],
                'port' => 3306,
                'user' => $matches[1],
                'password' => $matches[2],
                'database' => $matches[4],
                'method' => 'url_parsed_no_port'
            ];
        }
        
        return null;
    }
    
    public function getPDOConnection() {
        $configs = $this->getConnectionDetails();
        
        foreach ($configs as $config) {
            if (!$config) continue;
            
            $this->connectionAttempts++;
            error_log("Attempting database connection #{$this->connectionAttempts} using method: {$config['method']}");
            
            try {
                $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 15,
                    PDO::ATTR_PERSISTENT => false, // Railway doesn't like persistent connections
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                    PDO::MYSQL_ATTR_SSL_CA => false, // Disable SSL verification for Railway
                    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
                ];
                
                $pdo = new PDO($dsn, $config['user'], $config['password'], $options);
                
                // Test the connection
                $pdo->query("SELECT 1");
                
                error_log("✅ Database connection successful using method: {$config['method']}");
                return $pdo;
                
            } catch (PDOException $e) {
                error_log("❌ Database connection failed (attempt #{$this->connectionAttempts}): " . $e->getMessage());
                
                if ($this->connectionAttempts < $this->maxAttempts) {
                    error_log("🔄 Retrying in {$this->retryDelay} seconds...");
                    sleep($this->retryDelay);
                    $this->retryDelay *= 2; // Exponential backoff
                }
            }
        }
        
        error_log("❌ All database connection attempts failed");
        return null;
    }
    
    public function getMysqliConnection() {
        $configs = $this->getConnectionDetails();
        
        foreach ($configs as $config) {
            if (!$config) continue;
            
            try {
                $mysqli = new mysqli(
                    $config['host'], 
                    $config['user'], 
                    $config['password'], 
                    $config['database'], 
                    $config['port']
                );
                
                if ($mysqli->connect_error) {
                    throw new Exception("Connection failed: " . $mysqli->connect_error);
                }
                
                $mysqli->set_charset("utf8mb4");
                error_log("✅ MySQLi connection successful using method: {$config['method']}");
                return $mysqli;
                
            } catch (Exception $e) {
                error_log("❌ MySQLi connection failed: " . $e->getMessage());
            }
        }
        
        return null;
    }
}

// Initialize Railway database config
$railwayDB = new RailwayDatabaseConfig();

// Database connection function with Railway optimization
function getDatabaseConnection() {
    global $railwayDB;
    return $railwayDB->getPDOConnection();
}

// Legacy mysqli connection for backward compatibility
function getMysqliConnection() {
    global $railwayDB;
    return $railwayDB->getMysqliConnection();
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
            error_log("Database test query failed: " . $e->getMessage());
            return false;
        }
    }
    return false;
}

// Enhanced database configuration display
function showDatabaseConfig() {
    global $railwayDB;
    
    echo "🔧 Railway Database Configuration:\n";
    echo "================================\n\n";
    
    $configs = $railwayDB->getConnectionDetails();
    foreach ($configs as $index => $config) {
        if (!$config) continue;
        
        echo "Method " . ($index + 1) . " ({$config['method']}):\n";
        echo "📍 Host: {$config['host']}\n";
        echo "🚪 Port: {$config['port']}\n";
        echo "👤 User: {$config['user']}\n";
        echo "🗄️ Database: {$config['database']}\n";
        echo "🔑 Password: " . str_repeat('*', 10) . "\n\n";
    }
    
    echo "🌍 Environment Variables:\n";
    echo "========================\n";
    echo "📍 MYSQLHOST: " . ($_ENV['MYSQLHOST'] ?? 'NOT SET') . "\n";
    echo "🚪 MYSQLPORT: " . ($_ENV['MYSQLPORT'] ?? 'NOT SET') . "\n";
    echo "👤 MYSQLUSER: " . ($_ENV['MYSQLUSER'] ?? 'NOT SET') . "\n";
    echo "🗄️ MYSQLDATABASE: " . ($_ENV['MYSQLDATABASE'] ?? 'NOT SET') . "\n";
    echo "🔗 MYSQL_URL: " . ($_ENV['MYSQL_URL'] ?? 'NOT SET') . "\n";
    echo "🔗 MYSQL_PUBLIC_URL: " . ($_ENV['MYSQL_PUBLIC_URL'] ?? 'NOT SET') . "\n";
    echo "🔗 DATABASE_URL: " . ($_ENV['DATABASE_URL'] ?? 'NOT SET') . "\n\n";
    
    echo "🔍 Connection Test:\n";
    echo "==================\n";
    if (testDatabaseConnection()) {
        echo "✅ Database connection successful!\n";
    } else {
        echo "❌ Database connection failed!\n";
    }
}

// Application Configuration
define('APP_NAME', 'Nutrisaur');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'production');

// Base URL for production
$base_url = 'https://nutrisaur-production.up.railway.app/';

// Note: $conn variable is not created automatically
// Use getDatabaseConnection() function when you need a database connection
?>
