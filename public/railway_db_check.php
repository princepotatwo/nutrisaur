<?php
echo "=== Railway Database Environment Check ===\n\n";

// Check all possible Railway database environment variables
$railwayVars = [
    'MYSQL_HOST', 'MYSQL_PORT', 'MYSQL_DATABASE', 'MYSQL_USER', 'MYSQL_PASSWORD',
    'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD',
    'DATABASE_HOST', 'DATABASE_PORT', 'DATABASE_NAME', 'DATABASE_USER', 'DATABASE_PASSWORD',
    'RAILWAY_MYSQL_HOST', 'RAILWAY_MYSQL_PORT', 'RAILWAY_MYSQL_DATABASE', 'RAILWAY_MYSQL_USER', 'RAILWAY_MYSQL_PASSWORD'
];

echo "Checking Railway Database Environment Variables:\n";
$foundVars = [];
foreach ($railwayVars as $var) {
    $value = $_ENV[$var] ?? 'NOT_SET';
    if ($value !== 'NOT_SET') {
        if (strpos($var, 'PASSWORD') !== false) {
            echo "✅ $var: [HIDDEN]\n";
        } else {
            echo "✅ $var: $value\n";
        }
        $foundVars[] = $var;
    } else {
        echo "❌ $var: NOT_SET\n";
    }
}

echo "\n";

if (empty($foundVars)) {
    echo "❌ NO DATABASE ENVIRONMENT VARIABLES FOUND!\n";
    echo "This means Railway hasn't linked a database service to your app.\n\n";
    
    echo "SOLUTION:\n";
    echo "1. Go to your Railway project dashboard\n";
    echo "2. Add a MySQL database service\n";
    echo "3. Link it to your nutrisaur service\n";
    echo "4. Railway will automatically set the environment variables\n\n";
    
    echo "Current fallback values being used:\n";
    echo "- Host: mainline.proxy.rlwy.net\n";
    echo "- Port: 26063\n";
    echo "- Database: railway\n";
    echo "- User: root\n";
    echo "- Password: nZhQwfTnAJfFieCpIclAMtOQbBxcjwgy\n\n";
    
    echo "Testing connection with fallback values...\n";
    
    try {
        $dsn = "mysql:host=mainline.proxy.rlwy.net;port=26063;dbname=railway;charset=utf8mb4";
        $pdo = new PDO($dsn, 'root', 'nZhQwfTnAJfFieCpIclAMtOQbBxcjwgy', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10
        ]);
        echo "✅ Connection successful with fallback values\n";
        
        // Test if we can query
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        echo "✅ Query test: " . $result['test'] . "\n";
        
        // Check if users table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() > 0) {
            echo "✅ users table exists\n";
        } else {
            echo "❌ users table does not exist\n";
        }
        
        // Check if user_preferences table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'user_preferences'");
        if ($stmt->rowCount() > 0) {
            echo "✅ user_preferences table exists\n";
        } else {
            echo "❌ user_preferences table does not exist\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Connection failed: " . $e->getMessage() . "\n";
        echo "\nThis suggests the fallback database is not accessible.\n";
        echo "You need to add a proper MySQL database service in Railway.\n";
    }
    
} else {
    echo "✅ Found " . count($foundVars) . " database environment variables\n";
    echo "Variables found: " . implode(', ', $foundVars) . "\n\n";
    
    echo "Testing connection with Railway variables...\n";
    require_once __DIR__ . "/config.php";
    
    try {
        $pdo = getDatabaseConnection();
        if ($pdo) {
            echo "✅ Connection successful\n";
            
            // Test if we can query
            $stmt = $pdo->query("SELECT 1 as test");
            $result = $stmt->fetch();
            echo "✅ Query test: " . $result['test'] . "\n";
            
        } else {
            echo "❌ Connection failed\n";
        }
    } catch (Exception $e) {
        echo "❌ Connection error: " . $e->getMessage() . "\n";
    }
}

echo "\n=== End Railway Database Check ===\n";
?>
