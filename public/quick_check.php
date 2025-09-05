<?php
// Quick database check
$mysql_host = 'mainline.proxy.rlwy.net';
$mysql_port = 26063;
$mysql_user = 'root';
$mysql_password = 'nZhQwfTnAJfFieCpIclAMtOQbBxcjwgy';
$mysql_database = 'railway';

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
    $dsn = "mysql:host={$mysql_host};port={$mysql_port};dbname={$mysql_database};charset=utf8mb4";
    $conn = new PDO($dsn, $mysql_user, $mysql_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "=== RAILWAY DATABASE CHECK ===\n";
    echo "Database: $mysql_database\n\n";
    
    // Check tables
    $stmt = $conn->prepare("SHOW TABLES");
    $stmt->execute();
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(', ', $tables) . "\n\n";
    
    // Check user_preferences columns
    if (in_array('user_preferences', $tables)) {
        $stmt = $conn->prepare("DESCRIBE user_preferences");
        $stmt->execute();
        $columns = $stmt->fetchAll();
        
        echo "user_preferences columns:\n";
        foreach ($columns as $col) {
            echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
        }
        
        // Check if username exists
        $hasUsername = false;
        foreach ($columns as $col) {
            if ($col['Field'] === 'username') {
                $hasUsername = true;
                break;
            }
        }
        
        echo "\nUsername column exists: " . ($hasUsername ? "YES" : "NO") . "\n";
        
        // Count records
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_preferences");
        $stmt->execute();
        $count = $stmt->fetch();
        echo "Total records: " . $count['count'] . "\n";
        
    } else {
        echo "user_preferences table does NOT exist!\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
