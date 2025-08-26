<?php
/**
 * Database Import Script for Nutrisaur on Railway
 * This script will import the nutrisaur_db.sql file into your Railway MySQL database
 */

// Database connection details from Railway
$host = 'mainline.proxy.rlwy.net';
$port = '26063';
$username = 'root';
$password = 'nZhQwfTnAJfFieCpIclAMtOQbBxcjwgy';
$database = 'railway';

echo "🚀 Starting Database Import for Nutrisaur...\n\n";

try {
    // Create connection
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to Railway MySQL database successfully!\n";
    echo "📍 Host: $host:$port\n";
    echo "🗄️ Database: $database\n\n";
    
    // Read the SQL file
    $sqlFile = 'app/nutrisaur_db (14).sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    echo "📖 Reading SQL file: $sqlFile\n";
    $sql = file_get_contents($sqlFile);
    
    if (empty($sql)) {
        throw new Exception("SQL file is empty");
    }
    
    echo "📊 SQL file loaded successfully (" . strlen($sql) . " characters)\n\n";
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    echo "🔄 Starting import process...\n";
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue; // Skip comments and empty statements
        }
        
        try {
            $pdo->exec($statement);
            $successCount++;
            echo "✅ Statement executed successfully\n";
        } catch (PDOException $e) {
            $errorCount++;
            echo "❌ Error executing statement: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n🎉 Import completed!\n";
    echo "✅ Successful statements: $successCount\n";
    echo "❌ Failed statements: $errorCount\n";
    
    // Test some basic queries to verify import
    echo "\n🔍 Verifying import...\n";
    
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "📋 Tables found: " . implode(', ', $tables) . "\n";
    
    // Check user count
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "👥 Users imported: $userCount\n";
    
    // Check programs count
    $programCount = $pdo->query("SELECT COUNT(*) FROM programs")->fetchColumn();
    echo "📅 Programs imported: $programCount\n";
    
    echo "\n🎯 Database import verification complete!\n";
    
} catch (Exception $e) {
    echo "💥 Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
