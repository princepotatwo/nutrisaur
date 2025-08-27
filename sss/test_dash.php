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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Dashboard - NutriSaur</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .navbar { background: #333; color: white; padding: 10px; }
        .navbar a { color: white; text-decoration: none; margin-right: 20px; }
        .content { margin-top: 20px; }
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
    
    <div class="content">
        <h1>Test Dashboard</h1>
        <p>This is a minimal test dashboard to check if routing works.</p>
        <p>User: <?php echo htmlspecialchars($username); ?></p>
        <p>Email: <?php echo htmlspecialchars($email); ?></p>
    </div>
</body>
</html>
