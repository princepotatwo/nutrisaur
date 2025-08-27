<?php
/**
 * Main Router for Nutrisaur Application
 * Routes requests to appropriate files in the sss directory
 */

// Get the requested path
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = trim($path, '/');

// Remove query string from path
if (strpos($path, '?') !== false) {
    $path = substr($path, 0, strpos($path, '?'));
}

// Route to appropriate file
switch ($path) {
    case '':
    case 'home':
        // Route to home page
        include_once '../sss/home.php';
        break;
        
    case 'dash':
    case 'dashboard':
        // Route to dashboard
        include_once '../sss/test_dash.php';
        break;
        
    case 'settings':
        // Route to settings
        include_once '../sss/settings.php';
        break;
        
    case 'ai':
        // Route to AI page
        include_once '../sss/AI.php';
        break;
        
    case 'event':
    case 'events':
        // Route to events page
        include_once '../sss/event.php';
        break;
        
    case 'logout':
        // Route to logout
        include_once '../sss/logout.php';
        break;
        
    case 'NR':
        // Route to NR page
        include_once '../sss/NR.php';
        break;
        
    case 'FPM':
        // Route to FPM page
        include_once '../sss/FPM.php';
        break;
        
    default:
        // Check if it's an API request
        if (strpos($path, 'api/') === 0 || strpos($path, 'unified_api') === 0) {
            // Handle API requests
            if (strpos($path, 'unified_api') === 0) {
                include_once 'unified_api.php';
            } else {
                // Route to api directory
                $api_path = str_replace('api/', '', $path);
                if (file_exists("../sss/api/$api_path.php")) {
                    include_once "../sss/api/$api_path.php";
                } else {
                    http_response_code(404);
                    echo "API endpoint not found: $api_path";
                }
            }
        } else {
            // Check if file exists in sss directory
            if (file_exists("../sss/$path.php")) {
                include_once "../sss/$path.php";
            } else {
                // Default to home page
                include_once '../sss/home.php';
            }
        }
        break;
}
?>
