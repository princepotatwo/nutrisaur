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

// Define the correct paths
$sss_path = __DIR__ . '/../sss/';
$public_api_path = __DIR__ . '/api/';

// Debug logging for development
if (isset($_GET['debug'])) {
    error_log("Requested path: $path");
    error_log("SSS path: $sss_path");
    error_log("Public API path: $public_api_path");
}

// Route to appropriate file
switch ($path) {
    case '':
    case 'home':
        // Route to home page
        include_once $sss_path . 'home.php';
        break;
        
    case 'dash':
    case 'dashboard':
        // Route to dashboard
        include_once $sss_path . 'dash.php';
        break;
        
    case 'settings':
        // Route to settings
        include_once $sss_path . 'settings.php';
        break;
        
    case 'ai':
        // Route to AI page
        include_once $sss_path . 'AI.php';
        break;
        
    case 'event':
    case 'events':
        // Route to events page
        include_once $sss_path . 'event.php';
        break;
        
    case 'logout':
        // Route to logout
        include_once $sss_path . 'logout.php';
        break;
        
    case 'NR':
        // Route to NR page
        include_once $sss_path . 'NR.php';
        break;
        
    case 'FPM':
        // Route to FPM page
        include_once $sss_path . 'FPM.php';
        break;
        
    default:
        // Check if it's an API request
        if (strpos($path, 'api/') === 0) {
            // Handle API requests
            $api_path = str_replace('api/', '', $path);
            
            // First check if file exists in public/api directory
            $public_api_file = $public_api_path . "$api_path.php";
            $sss_api_file = $sss_path . "api/$api_path.php";
            
            if (isset($_GET['debug'])) {
                error_log("API path: $api_path");
                error_log("Public API file: $public_api_file");
                error_log("SSS API file: $sss_api_file");
                error_log("Public file exists: " . (file_exists($public_api_file) ? 'yes' : 'no'));
                error_log("SSS file exists: " . (file_exists($sss_api_file) ? 'yes' : 'no'));
            }
            
            if (file_exists($public_api_file)) {
                include_once $public_api_file;
            } elseif (file_exists($sss_api_file)) {
                include_once $sss_api_file;
            } else {
                http_response_code(404);
                echo "API endpoint not found: $api_path";
            }
        } elseif (strpos($path, 'unified_api') === 0) {
            // Handle unified API requests
            include_once 'unified_api.php';
        } else {
            // Check if file exists in sss directory
            $file_path = $sss_path . "$path.php";
            if (file_exists($file_path)) {
                include_once $file_path;
            } else {
                // Default to home page
                include_once $sss_path . 'home.php';
            }
        }
        break;
}
?>
