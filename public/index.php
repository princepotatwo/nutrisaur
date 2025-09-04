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
    
    if (isset($_GET['debug'])) {
        error_log("API path: $api_path");
        error_log("Public API file: $public_api_file");
        error_log("Public file exists: " . (file_exists($public_api_file) ? 'yes' : 'no'));
    }
    
    if (file_exists($public_api_file)) {
        include_once $public_api_file;
        exit;
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'API endpoint not found']);
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
        include_once $sss_path . 'health.php';
        break;
        
    case 'fcm_diagnostic':
    case 'fcm_diagnostic.php':
        // Route to FCM diagnostic endpoint
        include_once 'fcm_diagnostic.php';
        break;
        
    case 'check_verification_fields':
    case 'check_verification_fields.php':
        // Route to verification fields checker
        include_once 'check_verification_fields.php';
        break;
        
    case 'test_email':
    case 'test_email.php':
        // Route to email test page
        include_once 'test_email.php';
        break;
        
    case 'test_phpmailer':
    case 'test_phpmailer.php':
        // Route to PHPMailer test
        include_once 'test_phpmailer.php';
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
