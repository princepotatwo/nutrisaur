<?php
require_once __DIR__ . "/config.php";

echo "=== Database Connection Diagnostic ===\n\n";

// Show current configuration
echo "Current Database Configuration:\n";
$config = getDatabaseConfig();
foreach ($config as $key => $value) {
    if ($key !== 'password') {
        if (is_array($value)) {
            echo "- $key:\n";
            foreach ($value as $envKey => $envValue) {
                echo "  - $envKey: $envValue\n";
            }
        } else {
            echo "- $key: $value\n";
        }
    }
}
echo "- password: " . $config['password'] . "\n\n";

// Test PDO connection
echo "Testing PDO Connection:\n";
try {
    $pdo = getDatabaseConnection();
    if ($pdo) {
        echo "✅ PDO connection successful\n";
        
        // Test a simple query
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        echo "✅ PDO query test: " . $result['test'] . "\n";
        
        // Test if users table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() > 0) {
            echo "✅ users table exists\n";
        } else {
            echo "❌ users table does not exist\n";
        }
        
        // Test if user_preferences table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'user_preferences'");
        if ($stmt->rowCount() > 0) {
            echo "✅ user_preferences table exists\n";
        } else {
            echo "❌ user_preferences table does not exist\n";
        }
        
    } else {
        echo "❌ PDO connection failed\n";
    }
} catch (Exception $e) {
    echo "❌ PDO error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test MySQLi connection
echo "Testing MySQLi Connection:\n";
try {
    $mysqli = getMysqliConnection();
    if ($mysqli) {
        echo "✅ MySQLi connection successful\n";
    } else {
        echo "❌ MySQLi connection failed\n";
    }
} catch (Exception $e) {
    echo "❌ MySQLi error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test DatabaseAPI
echo "Testing DatabaseAPI:\n";
require_once __DIR__ . "/api/DatabaseAPI.php";
try {
    $db = new DatabaseAPI();
    if ($db->isDatabaseAvailable()) {
        echo "✅ DatabaseAPI reports database is available\n";
        
        // Test a few API methods
        $metrics = $db->getCommunityMetrics();
        echo "✅ Community metrics: " . json_encode($metrics) . "\n";
        
        $geo = $db->getGeographicDistribution();
        echo "✅ Geographic distribution: " . json_encode($geo) . "\n";
        
    } else {
        echo "❌ DatabaseAPI reports database is not available\n";
    }
} catch (Exception $e) {
    echo "❌ DatabaseAPI error: " . $e->getMessage() . "\n";
}

echo "\n";

// Check environment variables
echo "Environment Variables Check:\n";
$envVars = [
    'MYSQL_HOST', 'MYSQL_PORT', 'MYSQL_DATABASE', 'MYSQL_USER', 'MYSQL_PASSWORD',
    'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD',
    'DATABASE_HOST', 'DATABASE_PORT', 'DATABASE_NAME', 'DATABASE_USER', 'DATABASE_PASSWORD'
];

foreach ($envVars as $var) {
    $value = $_ENV[$var] ?? 'not_set';
    if ($value !== 'not_set' && $var !== 'MYSQL_PASSWORD' && $var !== 'DB_PASSWORD' && $var !== 'DATABASE_PASSWORD') {
        echo "✅ $var: $value\n";
    } elseif ($value !== 'not_set') {
        echo "✅ $var: [HIDDEN]\n";
    } else {
        echo "❌ $var: not_set\n";
    }
}

echo "\n";

// Check if we can connect without database name first
echo "Testing connection without database name:\n";
try {
    $testHost = $config['host'];
    $testPort = $config['port'];
    $testUser = $config['username'];
    $testPass = $dbPassword; // Use the actual password
    
    $dsn = "mysql:host=$testHost;port=$testPort;charset=utf8mb4";
    $pdo = new PDO($dsn, $testUser, $testPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10
    ]);
    echo "✅ Connection to MySQL server successful (without database)\n";
    
    // List available databases
    $stmt = $pdo->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Available databases: " . implode(', ', $databases) . "\n";
    
} catch (Exception $e) {
    echo "❌ Connection to MySQL server failed: " . $e->getMessage() . "\n";
}

echo "\n=== End Diagnostic ===\n";
?>
