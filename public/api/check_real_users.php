<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database connection details - Updated for Railway
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
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

try {
    // Check what tables exist
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $result = [
        'success' => true,
        'tables' => $tables,
        'data' => []
    ];
    
    // Check FCM tokens table
    if (in_array('fcm_tokens', $tables)) {
        $stmt = $conn->query("SELECT * FROM fcm_tokens ORDER BY created_at DESC LIMIT 10");
        $fcmTokens = $stmt->fetchAll();
        $result['data']['fcm_tokens'] = $fcmTokens;
    }
    
    // Check users table (if it exists)
    if (in_array('users', $tables)) {
        $stmt = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 10");
        $users = $stmt->fetchAll();
        $result['data']['users'] = $users;
    }
    
    // Check screening_responses table
    if (in_array('screening_responses', $tables)) {
        $stmt = $conn->query("SELECT user_email, user_name, barangay, created_at FROM screening_responses ORDER BY created_at DESC LIMIT 10");
        $screenings = $stmt->fetchAll();
        $result['data']['screening_responses'] = $screenings;
    }
    
    // Check if bbbb@bbb.bb exists and has FCM token
    if (in_array('fcm_tokens', $tables)) {
        $stmt = $conn->prepare("SELECT * FROM fcm_tokens WHERE user_email = ?");
        $stmt->execute(['bbbb@bbb.bb']);
        $bbbbUser = $stmt->fetch();
        $result['data']['bbbb_user_check'] = $bbbbUser;
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Query failed: ' . $e->getMessage()
    ]);
}
?>
