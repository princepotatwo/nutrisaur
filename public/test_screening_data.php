<?php
/**
 * Test Screening Data - See exactly what the screening responses API returns
 */

echo "🧪 Testing Screening Responses API Data\n";
echo "=====================================\n\n";

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
    
    echo "✅ Database connection successful\n\n";
    
    // Check user_preferences table data
    echo "🔍 USER_PREFERENCES TABLE DATA:\n";
    $stmt = $pdo->query("SELECT * FROM user_preferences LIMIT 5");
    $userPrefs = $stmt->fetchAll();
    
    if (empty($userPrefs)) {
        echo "❌ No data in user_preferences table\n";
    } else {
        echo "✅ Found " . count($userPrefs) . " records:\n";
        foreach ($userPrefs as $i => $record) {
            echo "  Record " . ($i + 1) . ":\n";
            echo "    - Email: " . $record['user_email'] . "\n";
            echo "    - Age: " . $record['age'] . "\n";
            echo "    - Gender: " . $record['gender'] . "\n";
            echo "    - Barangay: " . $record['barangay'] . "\n";
            echo "    - Risk Score: " . $record['risk_score'] . "\n";
            echo "    - Malnutrition Risk: " . $record['malnutrition_risk'] . "\n";
            echo "    - Created: " . $record['created_at'] . "\n\n";
        }
    }
    
    // Check users table data
    echo "🔍 USERS TABLE DATA:\n";
    $stmt = $pdo->query("SELECT * FROM users LIMIT 5");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "❌ No data in users table\n";
    } else {
        echo "✅ Found " . count($users) . " users:\n";
        foreach ($users as $i => $user) {
            echo "  User " . ($i + 1) . ":\n";
            echo "    - Email: " . $user['email'] . "\n";
            echo "    - Username: " . $user['username'] . "\n";
            echo "    - Barangay: " . $user['barangay'] . "\n";
            echo "    - Created: " . $user['created_at'] . "\n\n";
        }
    }
    
    // Test the exact query that screening_responses endpoint uses
    echo "🔍 TESTING SCREENING RESPONSES QUERY:\n";
    $stmt = $pdo->query("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as count
        FROM user_preferences 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ");
    $screeningData = $stmt->fetchAll();
    
    if (empty($screeningData)) {
        echo "❌ No screening data found for last 30 days\n";
    } else {
        echo "✅ Found " . count($screeningData) . " screening records:\n";
        foreach ($screeningData as $record) {
            echo "  - Date: " . $record['date'] . ", Count: " . $record['count'] . "\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n✅ Test complete!\n";
?>
