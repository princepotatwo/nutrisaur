<?php
/**
 * Debug Config - Test config.php step by step
 */

echo "🧪 Debug Config - Step by Step Test\n";
echo "==================================\n\n";

// Step 1: Check if we can read environment variables
echo "1️⃣ Checking environment variables...\n";
echo "📍 MYSQL_PUBLIC_URL: " . ($_ENV['MYSQL_PUBLIC_URL'] ?? 'NOT SET') . "\n";
echo "📍 MYSQLHOST: " . ($_ENV['MYSQLHOST'] ?? 'NOT SET') . "\n";
echo "📍 MYSQLPORT: " . ($_ENV['MYSQLPORT'] ?? 'NOT SET') . "\n\n";

// Step 2: Test the regex parsing
if (isset($_ENV['MYSQL_PUBLIC_URL'])) {
    $mysql_url = $_ENV['MYSQL_PUBLIC_URL'];
    echo "2️⃣ Testing regex parsing of: $mysql_url\n";
    
    $pattern = '/mysql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/(.+)/';
    if (preg_match($pattern, $mysql_url, $matches)) {
        echo "✅ Regex match successful!\n";
        echo "   👤 User: " . $matches[1] . "\n";
        echo "   🔑 Password: " . substr($matches[2], 0, 10) . "...\n";
        echo "   📍 Host: " . $matches[3] . "\n";
        echo "   🚪 Port: " . $matches[4] . "\n";
        echo "   🗄️ Database: " . $matches[5] . "\n\n";
        
        // Step 3: Test socket connection with parsed values
        echo "3️⃣ Testing socket connection...\n";
        $host = $matches[3];
        $port = $matches[4];
        
        $socket = @fsockopen($host, $port, $errno, $errstr, 10);
        if ($socket) {
            echo "✅ Socket connection successful!\n";
            fclose($socket);
        } else {
            echo "❌ Socket connection failed: $errstr ($errno)\n";
        }
        
        // Step 4: Test PDO connection
        echo "\n4️⃣ Testing PDO connection...\n";
        try {
            $dsn = "mysql:host={$host};port={$port};dbname=" . $matches[5] . ";charset=utf8mb4";
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
            echo "🔍 Error Code: " . $e->getCode() . "\n";
        }
        
    } else {
        echo "❌ Regex match failed!\n";
        echo "🔍 Pattern: $pattern\n";
        echo "🔍 URL: $mysql_url\n";
    }
} else {
    echo "❌ MYSQL_PUBLIC_URL not set!\n";
}

echo "\n🎯 Debug complete!\n";
?>
