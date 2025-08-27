<?php
/**
 * Railway Entry Point
 * This file handles all routes including API calls
 * Railway will use this instead of .htaccess
 */

// Start session
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

// Set working directory to public/ so relative paths work correctly
chdir(__DIR__);

// Get the full request URI
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = trim($path, '/');

// Debug output for API routes
$is_api_route = strpos($path, 'api/') === 0;
if ($is_api_route) {
    echo "🔍 API Route Debug:\n";
    echo "📍 REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
    echo "🎯 Parsed Path: '$path'\n";
    echo "🔗 Is API Route: " . ($is_api_route ? 'Yes' : 'No') . "\n\n";
}

// Handle API routes first
if ($is_api_route) {
    // Extract the API endpoint
    $api_path = substr($path, 4); // Remove 'api/' prefix
    
    switch ($api_path) {
        case 'unified_api':
            include 'api/unified_api.php';
            break;
            
        case 'login':
            include 'api/login.php';
            break;
            
        case 'check_session':
            include 'api/check_session.php';
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'API endpoint not found']);
            break;
    }
    exit;
}

// Handle regular routes
switch ($path) {
    case '':
    case 'index':
        // Suppress PHP notices for main website
        error_reporting(E_ERROR | E_PARSE);
        include '../sss/home.php';
        break;
        
    case 'home':
    case 'home.php':
        error_reporting(E_ERROR | E_PARSE);
        include '../sss/home.php';
        break;
        
    case 'dash':
    case 'dash.php':
        include '../sss/dash.php';
        break;
        
    case 'event':
    case 'events':
    case 'event.php':
        include '../sss/event.php';
        break;
        
    case 'settings':
    case 'settings.php':
        include '../sss/settings.php';
        break;
        
    case 'ai':
    case 'AI.php':
        include '../sss/AI.php';
        break;
        
    case 'fpm':
    case 'FPM.php':
        include '../sss/FPM.php';
        break;
        
    case 'nr':
    case 'NR.php':
        include '../sss/NR.php';
        break;
        
    case 'logout':
    case 'logout.php':
        include '../sss/logout.php';
        break;
        
    case 'test_db_connection':
        include 'test_db_connection.php';
        break;
        
    case 'import_database':
        include 'import_database.php';
        break;
        
    case 'simple_db_test':
        include 'simple_db_test.php';
        break;
        
    case 'minimal_test':
        include 'minimal_test.php';
        break;
        
    case 'debug_config':
        include 'debug_config.php';
        break;
        
    case 'test_config':
        include 'test_config.php';
        break;
        
    case 'test_dash':
        include 'test_dash.php';
        break;
        
    case 'test_dashboard':
        if (file_exists("../sss/dash.php")) {
            echo "✅ dash.php exists in sss directory\n";
            echo "📁 Current working directory: " . getcwd() . "\n";
            echo "🔍 Trying to include dash.php...\n";
            include "../sss/dash.php";
        } else {
            echo "❌ dash.php not found in sss directory\n";
        }
        break;
        
    case 'test_dashboard_db':
        include 'test_dashboard_db.php';
        break;
        
    case 'test_api_endpoint':
        include 'test_api_endpoint.php';
        break;
        
    case 'debug_routing':
        include 'debug_routing.php';
        break;
        
    case 'debug_env':
        include 'debug_env.php';
        break;
        
    case 'health':
        include 'health.php';
        break;
        
    default:
        // First check if it's a direct sss file
        if (file_exists("../sss/$path.php")) {
            include "../sss/$path.php";
        } elseif (file_exists("$path.php")) {
            include "$path.php";
        } else {
            http_response_code(404);
            echo '<h1>404 - Page Not Found</h1>';
            echo '<p>The requested page could not be found.</p>';
            echo '<p><a href="/">Go to Home</a></p>';
        }
        break;
}
?>
