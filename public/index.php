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
$public_path = __DIR__ . '/';
$public_api_path = __DIR__ . '/api/';

// Debug logging for development
if (isset($_GET['debug'])) {
    error_log("Requested path: $path");
    error_log("SSS path: $sss_path");
    error_log("Public API path: $public_api_path");
}

// Check if it's an API request first
if (strpos($path, 'api/') === 0) {
    // Handle API requests
    $api_path = str_replace('api/', '', $path);
    
    // Remove .php extension if present
    $api_path = str_replace('.php', '', $api_path);
    
    // Check if file exists in public/api directory
    $public_api_file = $public_api_path . "$api_path.php";
    
    // Debug logging for API requests
    error_log("API Request - Path: $path, API Path: $api_path, File: $public_api_file");
    
    if (file_exists($public_api_file)) {
        try {
            // Set JSON content type for all API responses
            header('Content-Type: application/json');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type');
            
            // Handle preflight OPTIONS request
            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                exit(0);
            }
            
            include_once $public_api_file;
            exit;
        } catch (Exception $e) {
            error_log("API Error in $public_api_file: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error', 'message' => $e->getMessage()]);
            exit;
        }
    } else {
        error_log("API endpoint not found: $public_api_file");
        http_response_code(404);
        echo json_encode(['error' => 'API endpoint not found', 'path' => $api_path]);
        exit;
    }
}

// Route to appropriate file
switch ($path) {
    case '':
    case 'home':
        // Route to home page
        include_once $public_path . 'home.php';
        break;
        
    case 'dash':
    case 'dashboard':
        // Route to dashboard
        include_once $public_path . 'dash.php';
        break;
        
    case 'settings':
        // Route to settings
        include_once $public_path . 'settings.php';
        break;
        
    case 'ai':
        // Route to AI page
        include_once $public_path . 'AI.php';
        break;
        
    case 'event':
    case 'events':
        // Route to events page
        include_once $public_path . 'event.php';
        break;
        
    case 'logout':
        // Route to logout
        include_once $public_path . 'logout.php';
        break;
        
    case 'NR':
        // Route to NR page
        include_once $public_path . 'NR.php';
        break;
        
    case 'FPM':
        // Route to FPM page
        include_once $public_path . 'FPM.php';
        break;
        
    case 'health':
    case 'health.php':
        // Route to health check
        include_once $public_path . 'health.php';
        break;
        
    case 'fcm_diagnostic':
    case 'fcm_diagnostic.php':
        // Route to FCM diagnostic endpoint
        include_once 'fcm_diagnostic.php';
        break;
        

        
    case 'logo.png':
        // Serve logo as static file
        $logoPath = __DIR__ . '/logo.png';
        if (file_exists($logoPath)) {
            header('Content-Type: image/png');
            header('Cache-Control: public, max-age=31536000');
            readfile($logoPath);
            exit;
        } else {
            http_response_code(404);
            echo 'Logo not found';
            exit;
        }
        break;
        
            default:
            if (strpos($path, 'unified_api') === 0) {
                // Handle unified API requests
                include_once 'unified_api.php';
            } else {
                // Check if file exists in public directory
                $file_path = $public_path . "$path.php";
                if (file_exists($file_path)) {
                    include_once $file_path;
                } else {
                    // Default to home page
                    include_once $public_path . 'home.php';
                }
            }
            break;
}
?>
