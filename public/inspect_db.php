<?php
// Inspect the actual Railway database structure
session_start();

// Database connection - same as screening.php
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

echo "<h1>Railway Database Inspector</h1>";
echo "<h2>Database: $mysql_database</h2>";

try {
    $dsn = "mysql:host={$mysql_host};port={$mysql_port};dbname={$mysql_database};charset=utf8mb4";
    
    $pdoOptions = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 30,
        PDO::ATTR_PERSISTENT => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
    ];
    
    $conn = new PDO($dsn, $mysql_user, $mysql_password, $pdoOptions);
    
    echo "<h3>✅ Database Connection Successful!</h3>";
    
    // List all tables
    echo "<h3>📋 All Tables in Database:</h3>";
    $stmt = $conn->prepare("SHOW TABLES");
    $stmt->execute();
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li><strong>$table</strong></li>";
    }
    echo "</ul>";
    
    // Check if user_preferences exists
    if (in_array('user_preferences', $tables)) {
        echo "<h3>✅ user_preferences table EXISTS!</h3>";
        
        // Get column details
        echo "<h4>📊 Columns in user_preferences table:</h4>";
        $stmt = $conn->prepare("DESCRIBE user_preferences");
        $stmt->execute();
        $columns = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th>";
        echo "</tr>";
        
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td><strong>" . $col['Field'] . "</strong></td>";
            echo "<td>" . $col['Type'] . "</td>";
            echo "<td>" . $col['Null'] . "</td>";
            echo "<td>" . $col['Key'] . "</td>";
            echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . $col['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check if screening_assessments exists
        if (in_array('screening_assessments', $tables)) {
            echo "<h3>⚠️ screening_assessments table ALSO EXISTS!</h3>";
        } else {
            echo "<h3>❌ screening_assessments table does NOT exist</h3>";
        }
        
        // Show sample data
        echo "<h4>📝 Sample data from user_preferences (first 3 rows):</h4>";
        $stmt = $conn->prepare("SELECT * FROM user_preferences LIMIT 3");
        $stmt->execute();
        $data = $stmt->fetchAll();
        
        if (!empty($data)) {
            echo "<pre style='background-color: #f5f5f5; padding: 10px; overflow-x: auto;'>";
            print_r($data);
            echo "</pre>";
        } else {
            echo "<p>No data found in user_preferences table.</p>";
        }
        
        // Count total records
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM user_preferences");
        $stmt->execute();
        $count = $stmt->fetch();
        echo "<p><strong>Total records in user_preferences:</strong> " . $count['total'] . "</p>";
        
    } else {
        echo "<h3>❌ user_preferences table does NOT exist!</h3>";
    }
    
} catch (PDOException $e) {
    echo "<h3>❌ Database connection failed:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><em>Generated at: " . date('Y-m-d H:i:s') . "</em></p>";
?>
