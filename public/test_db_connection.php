<?php
/**
 * Test Database Connection Script
 * This will test if we can connect to your Railway MySQL database
 */

require_once 'config.php';

echo "🧪 Testing Database Connection...\n\n";

// Test PDO connection
echo "1️⃣ Testing PDO connection...\n";
$pdo = getDatabaseConnection();
if ($pdo) {
    echo "✅ PDO connection successful!\n";
    
    // Test basic query
    try {
        $result = $pdo->query("SELECT VERSION() as version")->fetch();
        echo "📊 MySQL Version: " . $result['version'] . "\n";
    } catch (PDOException $e) {
        echo "❌ Query test failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ PDO connection failed!\n";
}

echo "\n2️⃣ Testing MySQLi connection...\n";
$mysqli = getMysqliConnection();
if ($mysqli) {
    echo "✅ MySQLi connection successful!\n";
    echo "📊 Server info: " . $mysqli->server_info . "\n";
    $mysqli->close();
} else {
    echo "❌ MySQLi connection failed!\n";
}

echo "\n3️⃣ Testing database connection function...\n";
if (testDatabaseConnection()) {
    echo "✅ Database connection test passed!\n";
} else {
    echo "❌ Database connection test failed!\n";
}

echo "\n🎯 Connection test complete!\n";
?>
