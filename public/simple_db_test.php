<?php
/**
 * Simple Database Connection Test
 * This will test the database connection using the updated config
 */

require_once 'config.php';

echo "🧪 Simple Database Connection Test\n";
echo "================================\n\n";

// Show current configuration
echo "🔍 Current Configuration:\n";
showDatabaseConfig();

// Test 1: Socket connection
echo "1️⃣ Testing socket connection...\n";
$socket = @fsockopen($mysql_host, $mysql_port, $errno, $errstr, 5);
if ($socket) {
    echo "✅ Socket connection successful!\n";
    fclose($socket);
} else {
    echo "❌ Socket connection failed: $errstr ($errno)\n";
}

// Test 2: MySQL connection without database
echo "\n2️⃣ Testing MySQL connection without database...\n";
try {
    $mysqli = new mysqli($mysql_host, $mysql_user, $mysql_password, '', $mysql_port);
    if ($mysqli->connect_error) {
        echo "❌ MySQL connection failed: " . $mysqli->connect_error . "\n";
        echo "🔍 Error Code: " . $mysqli->connect_errno . "\n";
    } else {
        echo "✅ MySQL connection successful!\n";
        
        // List databases
        $result = $mysqli->query("SHOW DATABASES");
        if ($result) {
            echo "📋 Available databases:\n";
            while ($row = $result->fetch_array()) {
                echo "   - " . $row[0] . "\n";
            }
        }
        
        $mysqli->close();
    }
} catch (Exception $e) {
    echo "❌ MySQL connection failed: " . $e->getMessage() . "\n";
}

// Test 3: Database connection using config functions
echo "\n3️⃣ Testing database connection using config functions...\n";
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

// Test 4: MySQLi connection using config functions
echo "\n4️⃣ Testing MySQLi connection using config functions...\n";
$mysqli = getMysqliConnection();
if ($mysqli) {
    echo "✅ MySQLi connection successful!\n";
    echo "📊 Server info: " . $mysqli->server_info . "\n";
    
    // Test a simple query
    $result = $mysqli->query("SELECT 1 as test");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "✅ Test query successful: " . $row['test'] . "\n";
    }
    
    $mysqli->close();
} else {
    echo "❌ MySQLi connection failed!\n";
}

echo "\n🎯 Test complete!\n";
?>
