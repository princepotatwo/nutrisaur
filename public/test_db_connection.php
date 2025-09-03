<?php
require_once __DIR__ . "/config.php";

echo "=== Database Connection Test ===\n\n";

// Test PDO connection
echo "Testing PDO connection...\n";
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
echo "Testing MySQLi connection...\n";
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

// Show current configuration
echo "Current database configuration:\n";
$config = getDatabaseConfig();
foreach ($config as $key => $value) {
    if ($key !== 'password') {
        echo "- $key: $value\n";
    }
}

echo "\n=== End of Database Test ===\n";
?>
