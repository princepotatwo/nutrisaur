<?php
/**
 * Test Database Connection Script
 * This will test if we can connect to your Railway MySQL database
 */

require_once 'config.php';

echo "🧪 Testing Database Connection...\n\n";

// Show connection details (without password)
echo "🔍 Connection Details:\n";
echo "📍 Host: mainline.proxy.rlwy.net\n";
echo "🚪 Port: 26063\n";
echo "🗄️ Database: railway\n";
echo "👤 Username: root\n";
echo "🔑 Password: [hidden]\n\n";

// Test 1: Basic socket connection
echo "1️⃣ Testing basic socket connection...\n";
$socket = @fsockopen('mainline.proxy.rlwy.net', 26063, $errno, $errstr, 10);
if ($socket) {
    echo "✅ Socket connection successful!\n";
    fclose($socket);
} else {
    echo "❌ Socket connection failed: $errstr ($errno)\n";
}

// Test 2: PDO connection with detailed error
echo "\n2️⃣ Testing PDO connection...\n";
try {
    $dsn = "mysql:host=mainline.proxy.rlwy.net;port=26063;dbname=railway;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', 'nZhQwfTnAJfFieCpIclAMtOQbBxcjwgy', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "✅ PDO connection successful!\n";
    
    // Test basic query
    try {
        $result = $pdo->query("SELECT VERSION() as version")->fetch();
        echo "📊 MySQL Version: " . $result['version'] . "\n";
    } catch (PDOException $e) {
        echo "❌ Query test failed: " . $e->getMessage() . "\n";
    }
} catch (PDOException $e) {
    echo "❌ PDO connection failed: " . $e->getMessage() . "\n";
    echo "🔍 Error Code: " . $e->getCode() . "\n";
}

// Test 3: MySQLi connection
echo "\n3️⃣ Testing MySQLi connection...\n";
try {
    $mysqli = new mysqli('mainline.proxy.rlwy.net', 'root', 'nZhQwfTnAJfFieCpIclAMtOQbBxcjwgy', 'railway', 26063);
    
    if ($mysqli->connect_error) {
        echo "❌ MySQLi connection failed: " . $mysqli->connect_error . "\n";
        echo "🔍 Error Code: " . $mysqli->connect_errno . "\n";
    } else {
        echo "✅ MySQLi connection successful!\n";
        echo "📊 Server info: " . $mysqli->server_info . "\n";
        $mysqli->close();
    }
} catch (Exception $e) {
    echo "❌ MySQLi connection failed: " . $e->getMessage() . "\n";
}

// Test 4: Try without database name first
echo "\n4️⃣ Testing connection without database name...\n";
try {
    $dsn = "mysql:host=mainline.proxy.rlwy.net;port=26063;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', 'nZhQwfTnAJfFieCpIclAMtOQbBxcjwgy', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10
    ]);
    echo "✅ Connection to MySQL server successful (without database)\n";
    
    // List available databases
    $databases = $pdo->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
    echo "📋 Available databases: " . implode(', ', $databases) . "\n";
    
} catch (PDOException $e) {
    echo "❌ Connection to MySQL server failed: " . $e->getMessage() . "\n";
}

echo "\n🎯 Connection test complete!\n";
?>
