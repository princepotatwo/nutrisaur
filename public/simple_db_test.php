<?php
echo "🧪 Simple Database Connection Test\n";
echo "================================\n\n";

// Test 1: Try to connect using Railway's environment variable approach
echo "1️⃣ Testing with Railway environment variables...\n";
if (isset($_ENV['MYSQL_URL'])) {
    echo "✅ MYSQL_URL found: " . substr($_ENV['MYSQL_URL'], 0, 20) . "...\n";
} else {
    echo "❌ MYSQL_URL not found\n";
}

// Test 2: Try direct connection with your current details
echo "\n2️⃣ Testing direct connection...\n";
$host = 'mainline.proxy.rlwy.net';
$port = 26063;
$user = 'root';
$pass = 'nZhQwfTnAJfFieCpIclAMtOQbBxcjwgy';
$db = 'railway';

echo "📍 Host: $host\n";
echo "🚪 Port: $port\n";
echo "🗄️ Database: $db\n";
echo "👤 User: $user\n";

// Test 3: Try socket connection first
echo "\n3️⃣ Testing socket connection...\n";
$socket = @fsockopen($host, $port, $errno, $errstr, 5);
if ($socket) {
    echo "✅ Socket connection successful!\n";
    fclose($socket);
} else {
    echo "❌ Socket connection failed: $errstr ($errno)\n";
}

// Test 4: Try MySQL connection without database
echo "\n4️⃣ Testing MySQL connection without database...\n";
try {
    $mysqli = new mysqli($host, $user, $pass, '', $port);
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

// Test 5: Try with database name
echo "\n5️⃣ Testing MySQL connection with database...\n";
try {
    $mysqli = new mysqli($host, $user, $pass, $db, $port);
    if ($mysqli->connect_error) {
        echo "❌ Database connection failed: " . $mysqli->connect_error . "\n";
        echo "🔍 Error Code: " . $mysqli->connect_errno . "\n";
    } else {
        echo "✅ Database connection successful!\n";
        echo "📊 Server info: " . $mysqli->server_info . "\n";
        
        // Test a simple query
        $result = $mysqli->query("SELECT 1 as test");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "✅ Test query successful: " . $row['test'] . "\n";
        }
        
        $mysqli->close();
    }
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}

echo "\n🎯 Test complete!\n";
?>
