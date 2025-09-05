<?php
// Debug session information
session_start();

echo "<h1>Session Debug Information</h1>";
echo "<h2>Session Status:</h2>";
echo "<p>Session Status: " . session_status() . "</p>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session Name: " . session_name() . "</p>";

echo "<h2>Session Data:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Cookies:</h2>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

echo "<h2>Headers:</h2>";
echo "<pre>";
print_r(getallheaders());
echo "</pre>";

echo "<h2>Request Info:</h2>";
echo "<p>Request Method: " . $_SERVER['REQUEST_METHOD'] . "</p>";
echo "<p>User Agent: " . $_SERVER['HTTP_USER_AGENT'] . "</p>";
echo "<p>X-Requested-With: " . ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? 'Not set') . "</p>";

// Test AJAX detection
$isAjax = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) || 
          ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) ||
          (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');

echo "<h2>AJAX Detection:</h2>";
echo "<p>Is AJAX: " . ($isAjax ? 'Yes' : 'No') . "</p>";

if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'AJAX request detected',
        'session_data' => $_SESSION,
        'is_logged_in' => isset($_SESSION['user_id'])
    ]);
} else {
    echo "<p>This is a regular page request.</p>";
}
?>
