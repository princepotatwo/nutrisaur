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

// Debug information
$debug_info = [
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'server_port' => $_SERVER['SERVER_PORT'] ?? 'Unknown',
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
    'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
];

// Simple routing
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = trim($path, '/');

// Route to appropriate file
switch ($path) {
    case '':
    case 'index':
        // Show main dashboard or welcome page
        echo '<!DOCTYPE html>';
        echo '<html><head><title>Nutrisaur - Railway Deployment</title></head>';
        echo '<body style="font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5;">';
        echo '<div style="max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">';
        echo '<h1>🚀 Nutrisaur is Running on Railway!</h1>';
        echo '<p><strong>Status:</strong> <span style="color: #28a745;">✅ Active</span></p>';
        echo '<p><strong>PHP Version:</strong> ' . $debug_info['php_version'] . '</p>';
        echo '<p><strong>Server:</strong> ' . $debug_info['server_software'] . '</p>';
        echo '<p><strong>Port:</strong> ' . $debug_info['server_port'] . '</p>';
        echo '<hr>';
        echo '<h2>🔗 Available Endpoints:</h2>';
        echo '<ul>';
        echo '<li><a href="/health">Health Check</a> - Verify system status</li>';
        echo '<li><a href="/test">Test Page</a> - Detailed system information</li>';
        echo '<li><a href="/sss/settings_verified_mho.php">MHO Settings</a> - Main application</li>';
        echo '</ul>';
        echo '</div></body></html>';
        break;
        
    case 'health':
        include 'health.php';
        break;
        
    case 'test':
        include 'test.php';
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
