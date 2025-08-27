<?php
/**
 * Simple Database Connection Test
 * This will test the database connection using the working approach from debug_config.php
 */

echo "🧪 Simple Database Connection Test\n";
echo "================================\n\n";

// Step 1: Check environment variables
echo "1️⃣ Checking environment variables...\n";
echo "📍 MYSQL_PUBLIC_URL: " . ($_ENV['MYSQL_PUBLIC_URL'] ?? 'NOT SET') . "\n";
echo "📍 MYSQLHOST: " . ($_ENV['MYSQLHOST'] ?? 'NOT SET') . "\n";
echo "📍 MYSQLPORT: " . ($_ENV['MYSQLPORT'] ?? 'NOT SET') . "\n\n";

// Step 2: Parse MYSQL_PUBLIC_URL and test connection
if (isset($_ENV['MYSQL_PUBLIC_URL'])) {
    $mysql_url = $_ENV['MYSQL_PUBLIC_URL'];
    echo "2️⃣ Parsing MYSQL_PUBLIC_URL: $mysql_url\n";
    
    $pattern = '/mysql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/(.+)/';
    if (preg_match($pattern, $mysql_url, $matches)) {
        echo "✅ Parsed successfully:\n";
        echo "   👤 User: " . $matches[1] . "\n";
        echo "   🔑 Password: " . substr($matches[2], 0, 10) . "...\n";
        echo "   📍 Host: " . $matches[3] . "\n";
        echo "   🚪 Port: " . $matches[4] . "\n";
        echo "   🗄️ Database: " . $matches[5] . "\n\n";
        
        // Step 3: Test database connection
        echo "3️⃣ Testing database connection...\n";
        try {
            $dsn = "mysql:host={$matches[3]};port={$matches[4]};dbname={$matches[5]};charset=utf8mb4";
            echo "🔗 DSN: $dsn\n";
            
            $pdo = new PDO($dsn, $matches[1], $matches[2], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 10
            ]);
            
            echo "✅ PDO connection successful!\n";
            
            // Test basic query
            $stmt = $pdo->query("SELECT VERSION() as version");
            $result = $stmt->fetch();
            echo "📊 MySQL Version: " . $result['version'] . "\n";
            
            // Test if our tables exist
            echo "\n4️⃣ Checking database tables...\n";
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (count($tables) > 0) {
                echo "✅ Found " . count($tables) . " tables:\n";
                foreach ($tables as $table) {
                    echo "   - $table\n";
                }
            } else {
                echo "❌ No tables found in database\n";
            }
            
            // Test a specific table if it exists
            if (in_array('users', $tables)) {
                echo "\n5️⃣ Testing users table...\n";
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
                $result = $stmt->fetch();
                echo "✅ Users table has " . $result['count'] . " records\n";
            }
            
        } catch (PDOException $e) {
            echo "💥 Database connection failed!\n";
            echo "❌ Error: " . $e->getMessage() . "\n";
            echo "🔍 Error Code: " . $e->getCode() . "\n";
        }
        
    } else {
        echo "❌ Failed to parse MYSQL_PUBLIC_URL\n";
    }
} else {
    echo "❌ MYSQL_PUBLIC_URL not set!\n";
}

echo "\n🎯 Test complete!\n";
?>
