<?php
// Main entry point for Nutrisaur Web Application
session_start();

// Set headers for CORS and security
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: text/html; charset=UTF-8');

// Check if it's a preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include configuration
if (file_exists('config.php')) {
    require_once 'config.php';
}

// Simple routing
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = trim($path, '/');

// Remove 'public' from path if present
if (strpos($path, 'public/') === 0) {
    $path = substr($path, 7);
}

// Route to appropriate file
switch ($path) {
    case '':
    case 'index':
        include 'sss/settings_verified_mho.php';
        break;
    case 'api':
        include 'api.php';
        break;
    case 'dashboard':
        include 'sss/dash.php';
        break;
    case 'events':
        include 'sss/event.php';
        break;
    case 'home':
        include 'sss/home.php';
        break;
    case 'health':
        echo json_encode(['status' => 'healthy', 'timestamp' => date('Y-m-d H:i:s')]);
        break;
    default:
        // Try to find the file in sss directory
        if (file_exists("sss/$path.php")) {
            include "sss/$path.php";
        } else {
            http_response_code(404);
            echo '<h1>404 - Page Not Found</h1>';
            echo '<p>The requested page could not be found.</p>';
            echo '<p><a href="/">Go to Home</a></p>';
        }
        break;
}
?>
