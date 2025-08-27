<?php
/**
 * Test Tables - Check what tables exist in Railway database
 */

echo "🧪 Testing Railway Database Tables\n";
echo "==================================\n\n";

// Database connection - Use the same working approach
$mysql_host = 'mainline.proxy.rlwy.net';
$mysql_port = 26063;
$mysql_user = 'root';
$mysql_password = 'nZhQwfTnAJfFieCpIclAMtOQbBxcjwgy';
$mysql_database = 'railway';

// If MYSQL_PUBLIC_URL is set (Railway sets this), parse it
if (isset($_ENV['MYSQL_PUBLIC_URL'])) {
    $mysql_url = $_ENV['MYSQL_PUBLIC_URL'];
    $pattern = '/mysql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/(.+)/';
    if (preg_match($pattern, $mysql_url, $matches)) {
        $mysql_user = $matches[1];
        $mysql_password = $matches[2];
        $mysql_host = $matches[3];
        $mysql_port = $matches[4];
        $mysql_database = $matches[5];
    }
}

try {
    // Create database connection
    $dsn = "mysql:host={$mysql_host};port={$mysql_port};dbname={$mysql_database};charset=utf8mb4";
    $pdo = new PDO($dsn, $mysql_user, $mysql_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ]);
    
    echo "✅ Database connection successful\n";
    echo "📊 Database: $mysql_database\n";
    echo "🌐 Host: $mysql_host:$mysql_port\n\n";
    
    // Show all tables
    echo "📋 EXISTING TABLES:\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "❌ No tables found in database\n";
    } else {
        foreach ($tables as $table) {
            echo "  ✅ $table\n";
        }
    }
    
    echo "\n";
    
    // Check specific tables that the API needs
    $requiredTables = [
        'users',
        'user_preferences', 
        'admin',
        'ai_food_recommendations',
        'fcm_tokens',
        'notification_logs'
    ];
    
    echo "🔍 CHECKING REQUIRED TABLES:\n";
    foreach ($requiredTables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "  ✅ $table - EXISTS\n";
            
            // Show table structure
            $stmt2 = $pdo->query("DESCRIBE $table");
            $columns = $stmt2->fetchAll();
            echo "     Columns: " . count($columns) . "\n";
            
            // Show row count
            $stmt3 = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt3->fetch()['count'];
            echo "     Rows: $count\n";
        } else {
            echo "  ❌ $table - MISSING\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n✅ Test complete!\n";
?>
