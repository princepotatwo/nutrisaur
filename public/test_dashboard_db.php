<?php
/**
 * Test Dashboard Database Connection
 * This script tests the database connection using the same approach as dash.php
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    echo "❌ Not logged in. Please login first.\n";
    exit;
}

echo "✅ User is logged in\n";
echo "User ID: " . ($_SESSION['user_id'] ?? $_SESSION['admin_id']) . "\n";
echo "Username: " . ($_SESSION['username'] ?? 'Unknown') . "\n";

// Database connection - Use the same working approach as dash.php
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

echo "🔍 Database connection details:\n";
echo "Host: $mysql_host\n";
echo "Port: $mysql_port\n";
echo "User: $mysql_user\n";
echo "Database: $mysql_database\n";

// Create database connection
try {
    $dsn = "mysql:host={$mysql_host};port={$mysql_port};dbname={$mysql_database};charset=utf8mb4";
    echo "🔗 Attempting to connect with DSN: $dsn\n";
    
    $conn = new PDO($dsn, $mysql_user, $mysql_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ]);
    
    echo "✅ Database connection successful!\n";
    
    // Test a simple query
    $stmt = $conn->query("SELECT COUNT(*) as total_users FROM users");
    $result = $stmt->fetch();
    echo "📊 Total users in database: " . $result['total_users'] . "\n";
    
    // Test user_preferences table
    $stmt = $conn->query("SELECT COUNT(*) as total_screenings FROM user_preferences");
    $result = $stmt->fetch();
    echo "📋 Total screenings in database: " . $result['total_screenings'] . "\n";
    
    // Test if current user exists
    $userId = $_SESSION['user_id'] ?? $_SESSION['admin_id'];
    $stmt = $conn->prepare("SELECT username, email FROM users WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();
        echo "👤 Current user found: " . $user['username'] . " (" . $user['email'] . ")\n";
    } else {
        echo "❌ Current user not found in database\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
}
?>
