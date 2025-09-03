<?php
// Start session
session_start();

echo "<h2>Session Test</h2>";

// Check if session is working
echo "<h3>Session ID: " . session_id() . "</h3>";
echo "<h3>Session Name: " . session_name() . "</h3>";

// Check current session data
echo "<h3>Current Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Test setting session data
if (isset($_GET['set'])) {
    $_SESSION['test_data'] = 'test_value_' . time();
    $_SESSION['test_time'] = time();
    echo "<h3>Set test session data</h3>";
    echo "<p>Test data: " . $_SESSION['test_data'] . "</p>";
    echo "<p>Test time: " . $_SESSION['test_time'] . "</p>";
}

// Test clearing session
if (isset($_GET['clear'])) {
    session_destroy();
    session_start();
    echo "<h3>Session cleared and restarted</h3>";
}

// Check session status
echo "<h3>Session Status:</h3>";
echo "<p>Session Status: " . session_status() . "</p>";
echo "<p>Session Save Path: " . session_save_path() . "</p>";

// Test links
echo "<h3>Test Actions:</h3>";
echo "<p><a href='?set=1'>Set Test Session Data</a></p>";
echo "<p><a href='?clear=1'>Clear Session</a></p>";
echo "<p><a href='test_session.php'>Refresh</a></p>";

// Test login simulation
if (isset($_GET['simulate_login'])) {
    $_SESSION['user_id'] = 123;
    $_SESSION['username'] = 'testuser';
    $_SESSION['email'] = 'test@example.com';
    echo "<h3>Simulated Login - Session Data Set</h3>";
}

echo "<p><a href='?simulate_login=1'>Simulate Login</a></p>";

// Test redirect
if (isset($_SESSION['user_id'])) {
    echo "<h3>User is logged in!</h3>";
    echo "<p><a href='dash.php'>Go to Dashboard</a></p>";
} else {
    echo "<h3>User is NOT logged in</h3>";
    echo "<p><a href='home.php'>Go to Home</a></p>";
}
?>
