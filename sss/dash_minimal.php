<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Test</title>
    <style>
        body { font-family: Arial; background: #1A211A; color: #E8F0D6; padding: 20px; }
        .nav { background: #2A3326; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .nav a { color: #E8F0D6; text-decoration: none; margin-right: 20px; }
        .card { background: #2A3326; padding: 20px; border-radius: 8px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="nav">
        <a href="dash">Dashboard</a>
        <a href="event">Events</a>
        <a href="ai">AI</a>
        <a href="settings">Settings</a>
        <a href="logout">Logout</a>
    </div>
    
    <h1>Dashboard Test Page</h1>
    
    <div class="card">
        <h2>Database Connection Test</h2>
        <?php
        try {
            $host = 'mainline.proxy.rlwy.net';
            $port = 26063;
            $user = 'root';
            $pass = 'nZhQwfTnAJfFieCpIclAMtOQbBxcjwgy';
            $db = 'railway';
            
            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
            $conn = new PDO($dsn, $user, $pass);
            
            echo "<p style='color: #A1B454;'>✅ Database connected successfully!</p>";
            
            $stmt = $conn->query("SELECT COUNT(*) as count FROM user_preferences");
            $result = $stmt->fetch();
            echo "<p>Total users: " . $result['count'] . "</p>";
            
        } catch (Exception $e) {
            echo "<p style='color: #CF8686;'>❌ Database error: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <div class="card">
        <h2>Page Status</h2>
        <p>✅ Dashboard test page loaded successfully!</p>
        <p>User ID: <?php echo $_SESSION['user_id']; ?></p>
        <p>Username: <?php echo $_SESSION['username']; ?></p>
    </div>
</body>
</html>
