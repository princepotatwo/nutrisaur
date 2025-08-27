<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['email'] = 'admin@example.com';
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
$email = $_SESSION['email'];

// Database connection - Use the same working approach as dash.php
$mysql_host = 'mainline.proxy.rlwy.net';
$mysql_port = 26063;
$mysql_user = 'root';
$mysql_password = 'nZhQwfTnAJfFieCpIclAMtOQbBxcjwgy';
$mysql_database = 'railway';

// Create database connection
try {
    $dsn = "mysql:host={$mysql_host};port={$mysql_port};dbname={$mysql_database};charset=utf8mb4";
    $conn = new PDO($dsn, $mysql_user, $mysql_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ]);
    
    $dbConnected = true;
    $dbMessage = "Database connected successfully!";
} catch (PDOException $e) {
    $conn = null;
    $dbConnected = false;
    $dbMessage = "Database connection failed: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings Test - NutriSaur</title>
    <style>
        body { font-family: Arial, sans-serif; background: #1A211A; color: #E8F0D6; padding: 20px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: rgba(161, 180, 84, 0.2); color: #A1B454; border: 1px solid #A1B454; }
        .error { background: rgba(207, 134, 134, 0.2); color: #CF8686; border: 1px solid #CF8686; }
        .info { background: rgba(224, 201, 137, 0.2); color: #E0C989; border: 1px solid #E0C989; }
        .navbar { background: #2A3326; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .navbar a { color: #E8F0D6; text-decoration: none; margin-right: 20px; }
        .navbar a:hover { color: #A1B454; }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="dash">Dashboard</a>
        <a href="event">Events</a>
        <a href="ai">AI</a>
        <a href="settings">Settings</a>
        <a href="logout">Logout</a>
    </div>
    
    <h1>Settings Test Page</h1>
    
    <div class="status <?php echo $dbConnected ? 'success' : 'error'; ?>">
        <strong>Database Status:</strong> <?php echo $dbMessage; ?>
    </div>
    
    <div class="status info">
        <strong>User Info:</strong><br>
        User ID: <?php echo htmlspecialchars($userId); ?><br>
        Username: <?php echo htmlspecialchars($username); ?><br>
        Email: <?php echo htmlspecialchars($email); ?>
    </div>
    
    <div class="status info">
        <strong>Database Details:</strong><br>
        Host: <?php echo htmlspecialchars($mysql_host); ?><br>
        Port: <?php echo htmlspecialchars($mysql_port); ?><br>
        Database: <?php echo htmlspecialchars($mysql_database); ?><br>
        User: <?php echo htmlspecialchars($mysql_user); ?>
    </div>
    
    <?php if ($dbConnected && $conn): ?>
        <div class="status success">
            <strong>Database Query Test:</strong><br>
            <?php
            try {
                $stmt = $conn->query("SELECT COUNT(*) as user_count FROM user_preferences");
                $result = $stmt->fetch();
                echo "Total users in database: " . $result['user_count'];
            } catch (PDOException $e) {
                echo "Query failed: " . $e->getMessage();
            }
            ?>
        </div>
    <?php endif; ?>
    
    <div class="status info">
        <strong>Page Status:</strong> Settings test page loaded successfully!
    </div>
</body>
</html>
