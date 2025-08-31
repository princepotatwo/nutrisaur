<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

echo json_encode([
    'test' => 'FCM Registration Test',
    'method' => $_SERVER['REQUEST_METHOD'],
    'timestamp' => date('Y-m-d H:i:s')
]);

// Test what data the Android app would send
$input = file_get_contents('php://input');
$postData = $_POST;

echo "\n" . json_encode([
    'raw_input' => $input,
    'post_data' => $postData,
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'NOT_SET'
]);

// Test database connection
try {
    // Railway database credentials
    $mysql_host = 'mainline.proxy.rlwy.net';
    $mysql_port = 26063;
    $mysql_user = 'root';
    $mysql_password = 'nZhQwfTnAJfFieCpIclAMtOQbBxcjwgy';
    $mysql_database = 'railway';
    
    // If MYSQL_PUBLIC_URL is set, use it
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
    
    $dsn = "mysql:host={$mysql_host};port={$mysql_port};dbname={$mysql_database};charset=utf8mb4";
    $pdoOptions = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10
    ];
    
    $conn = new PDO($dsn, $mysql_user, $mysql_password, $pdoOptions);
    
    echo "\n" . json_encode([
        'database' => 'CONNECTED'
    ]);
    
    // Test FCM tokens table
    $stmt = $conn->query("SHOW TABLES LIKE 'fcm_tokens'");
    $fcmTableExists = $stmt->rowCount() > 0;
    
    echo "\n" . json_encode([
        'fcm_tokens_table_exists' => $fcmTableExists
    ]);
    
    if ($fcmTableExists) {
        // Check table structure
        $stmt = $conn->query("DESCRIBE fcm_tokens");
        $columns = $stmt->fetchAll();
        
        echo "\n" . json_encode([
            'fcm_tokens_structure' => $columns
        ]);
        
        // Check if bbbb@bbb.bb exists
        $stmt = $conn->prepare("SELECT * FROM fcm_tokens WHERE user_email = ?");
        $stmt->execute(['bbbb@bbb.bb']);
        $bbbbUser = $stmt->fetch();
        
        echo "\n" . json_encode([
            'bbbb_user_in_fcm_tokens' => $bbbbUser ? 'YES' : 'NO',
            'bbbb_user_data' => $bbbbUser
        ]);
    }
    
} catch (Exception $e) {
    echo "\n" . json_encode([
        'database_error' => $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
}

// Test if we can write to the database
if (isset($conn) && $conn) {
    try {
        // Try to insert a test record
        $testToken = 'test_token_' . time();
        $stmt = $conn->prepare("INSERT INTO fcm_tokens (user_email, fcm_token, created_at) VALUES (?, ?, NOW())");
        $stmt->execute(['test@test.com', $testToken]);
        
        echo "\n" . json_encode([
            'write_test' => 'SUCCESS',
            'test_token_inserted' => $testToken
        ]);
        
        // Clean up test record
        $stmt = $conn->prepare("DELETE FROM fcm_tokens WHERE user_email = ?");
        $stmt->execute(['test@test.com']);
        
        echo "\n" . json_encode([
            'cleanup_test' => 'SUCCESS'
        ]);
        
    } catch (Exception $e) {
        echo "\n" . json_encode([
            'write_test_error' => $e->getMessage()
        ]);
    }
}
?>
