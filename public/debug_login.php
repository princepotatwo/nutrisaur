<?php
session_start();

echo "<h2>Debug Login Test</h2>";

// Check current session
echo "<h3>Current Session:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['admin_id']);
echo "<h3>Is Logged In: " . ($isLoggedIn ? 'YES' : 'NO') . "</h3>";

// Test database connection
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/api/DatabaseAPI.php";
$db = new DatabaseAPI();

echo "<h3>Database Status:</h3>";
$status = $db->getDatabaseStatus();
echo "<pre>";
print_r($status);
echo "</pre>";

// Test authentication
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    echo "<h3>POST Data:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        echo "<h3>Testing Authentication:</h3>";
        $result = $db->authenticateUser($username, $password);
        echo "<pre>";
        print_r($result);
        echo "</pre>";
        
        if ($result['success']) {
            echo "<h3>Authentication Successful!</h3>";
            echo "<p>Setting session variables...</p>";
            
            session_regenerate_id(true);
            
            if ($result['user_type'] === 'user') {
                $_SESSION['user_id'] = $result['data']['user_id'];
                $_SESSION['username'] = $result['data']['username'];
                $_SESSION['email'] = $result['data']['email'];
                $_SESSION['is_admin'] = $result['data']['is_admin'];
                
                if ($result['data']['is_admin']) {
                    $_SESSION['admin_id'] = $result['data']['admin_data']['admin_id'];
                    $_SESSION['role'] = $result['data']['admin_data']['role'];
                }
            } else {
                $_SESSION['admin_id'] = $result['data']['admin_id'];
                $_SESSION['username'] = $result['data']['username'];
                $_SESSION['email'] = $result['data']['email'];
                $_SESSION['is_admin'] = true;
                $_SESSION['role'] = $result['data']['role'];
            }
            
            echo "<h3>Session After Login:</h3>";
            echo "<pre>";
            print_r($_SESSION);
            echo "</pre>";
            
            echo "<h3>Redirect Test:</h3>";
            echo "<p>Would redirect to: dash.php</p>";
            echo "<a href='dash.php'>Test Dashboard Access</a>";
        } else {
            echo "<h3>Authentication Failed:</h3>";
            echo "<p>Error: " . $result['message'] . "</p>";
        }
    }
}

// Simple login form
echo "<h3>Test Login Form:</h3>";
echo "<form method='POST'>";
echo "<p>Username/Email: <input type='text' name='username' required></p>";
echo "<p>Password: <input type='password' name='password' required></p>";
echo "<p><input type='submit' value='Test Login'></p>";
echo "</form>";

echo "<h3>Links:</h3>";
echo "<p><a href='home.php'>Go to Home</a></p>";
echo "<p><a href='dash.php'>Go to Dashboard</a></p>";
echo "<p><a href='logout.php'>Logout</a></p>";
?>
