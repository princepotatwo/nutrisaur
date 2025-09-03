<?php
echo "=== Railway Database Environment Check ===\n\n";

// Check Railway database environment variables
$railwayVars = [
    'MYSQL_PUBLIC_URL'
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
    echo "❌ MYSQL_PUBLIC_URL NOT FOUND!\n";
    echo "Railway should provide MYSQL_PUBLIC_URL when database is linked.\n\n";
    
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
    echo "✅ Found MYSQL_PUBLIC_URL\n";
    echo "Railway database is properly linked!\n\n";
    
    echo "Testing connection with parsed MYSQL_PUBLIC_URL...\n";
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
