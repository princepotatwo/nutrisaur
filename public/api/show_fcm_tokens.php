<?php
// Simple script to show FCM tokens
header('Content-Type: application/json');

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
    
    // Get FCM tokens
    $stmt = $conn->prepare("SELECT id, fcm_token, user_email, user_barangay, is_active, created_at FROM fcm_tokens WHERE is_active = TRUE ORDER BY created_at DESC");
    $stmt->execute();
    $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mask the tokens for security (show first 20 chars)
    foreach ($tokens as &$token) {
        $token['fcm_token_preview'] = substr($token['fcm_token'], 0, 20) . '...';
        unset($token['fcm_token']); // Remove full token for security
    }
    
    echo json_encode([
        'success' => true,
        'total_tokens' => count($tokens),
        'tokens' => $tokens
    ], JSON_PRETTY_PRINT);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
