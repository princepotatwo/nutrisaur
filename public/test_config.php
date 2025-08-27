<?php
/**
 * Test Config - See exactly what config.php is doing
 */

echo "🧪 Test Config - What's happening in config.php?\n";
echo "==============================================\n\n";

// Step 1: Check if we can include config.php
echo "1️⃣ Including config.php...\n";
try {
    require_once 'config.php';
    echo "✅ config.php loaded successfully\n\n";
} catch (Exception $e) {
    echo "❌ Failed to load config.php: " . $e->getMessage() . "\n";
    exit;
}

// Step 2: Check what variables are set
echo "2️⃣ Checking database variables...\n";
echo "📍 mysql_host: " . (isset($mysql_host) ? $mysql_host : 'NOT SET') . "\n";
echo "🚪 mysql_port: " . (isset($mysql_port) ? $mysql_port : 'NOT SET') . "\n";
echo "👤 mysql_user: " . (isset($mysql_user) ? $mysql_user : 'NOT SET') . "\n";
echo "🗄️ mysql_database: " . (isset($mysql_database) ? $mysql_database : 'NOT SET') . "\n";
echo "🔑 mysql_password: " . (isset($mysql_password) ? substr($mysql_password, 0, 10) . "..." : 'NOT SET') . "\n\n";

// Step 3: Check if MYSQL_PUBLIC_URL is being parsed
echo "3️⃣ Checking MYSQL_PUBLIC_URL parsing...\n";
echo "📍 MYSQL_PUBLIC_URL: " . ($_ENV['MYSQL_PUBLIC_URL'] ?? 'NOT SET') . "\n";

if (isset($_ENV['MYSQL_PUBLIC_URL'])) {
    $mysql_url = $_ENV['MYSQL_PUBLIC_URL'];
    $pattern = '/mysql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/(.+)/';
    if (preg_match($pattern, $mysql_url, $matches)) {
        echo "✅ Parsed successfully:\n";
        echo "   👤 User: " . $matches[1] . "\n";
        echo "   🔑 Password: " . substr($matches[2], 0, 10) . "...\n";
        echo "   📍 Host: " . $matches[3] . "\n";
        echo "   🚪 Port: " . $matches[4] . "\n";
        echo "   🗄️ Database: " . $matches[5] . "\n\n";
        
        // Step 4: Test connection with parsed values
        echo "4️⃣ Testing connection with parsed values...\n";
        try {
            $dsn = "mysql:host={$matches[3]};port={$matches[4]};dbname={$matches[5]};charset=utf8mb4";
            echo "🔗 DSN: $dsn\n";
            
            $pdo = new PDO($dsn, $matches[1], $matches[2], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 10
            ]);
            
            echo "✅ PDO connection successful!\n";
            
            // Test a simple query
            $stmt = $pdo->query("SELECT 1 as test");
            $result = $stmt->fetch();
            echo "✅ Query test: SUCCESS - Result: " . $result['test'] . "\n";
            
        } catch (PDOException $e) {
            echo "💥 PDO connection failed!\n";
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "❌ Failed to parse MYSQL_PUBLIC_URL\n";
    }
} else {
    echo "❌ MYSQL_PUBLIC_URL not set!\n";
}

// Step 5: Test the config.php functions
echo "\n5️⃣ Testing config.php functions...\n";
if (function_exists('getDatabaseConnection')) {
    echo "✅ getDatabaseConnection function exists\n";
    $conn = getDatabaseConnection();
    if ($conn) {
        echo "✅ getDatabaseConnection returned a connection\n";
    } else {
        echo "❌ getDatabaseConnection returned null\n";
    }
} else {
    echo "❌ getDatabaseConnection function does not exist\n";
}

echo "\n🎯 Test complete!\n";
?>
