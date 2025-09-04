<?php
/**
 * Debug API Endpoint
 * Helps identify issues with API routing and responses
 */

// Set headers first
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Capture all output to prevent any HTML from being sent
ob_start();

try {
    // Basic server information
    $serverInfo = [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'request_uri' => $_SERVER['REQUEST_URI'],
        'script_name' => $_SERVER['SCRIPT_NAME'],
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
        'current_dir' => __DIR__,
        'file_exists' => file_exists(__FILE__),
        'headers_sent' => headers_sent(),
        'content_type_set' => false
    ];

    // Check if Content-Type header was set
    foreach (headers_list() as $header) {
        if (stripos($header, 'Content-Type: application/json') !== false) {
            $serverInfo['content_type_set'] = true;
            break;
        }
    }

    // Test file includes
    $includeTests = [];
    
    // Test config.php
    $configPath = __DIR__ . "/../config.php";
    $includeTests['config.php'] = [
        'path' => $configPath,
        'exists' => file_exists($configPath),
        'readable' => is_readable($configPath)
    ];

    // Test DatabaseAPI.php
    $dbApiPath = __DIR__ . "/DatabaseAPI.php";
    $includeTests['DatabaseAPI.php'] = [
        'path' => $dbApiPath,
        'exists' => file_exists($dbApiPath),
        'readable' => is_readable($dbApiPath)
    ];

    // Test EmailService.php
    $emailPath = __DIR__ . "/EmailService.php";
    $includeTests['EmailService.php'] = [
        'path' => $emailPath,
        'exists' => file_exists($emailPath),
        'readable' => is_readable($emailPath)
    ];

    // Test email_config.php
    $emailConfigPath = __DIR__ . "/../../email_config.php";
    $includeTests['email_config.php'] = [
        'path' => $emailConfigPath,
        'exists' => file_exists($emailConfigPath),
        'readable' => is_readable($emailConfigPath)
    ];

    // Test PHPMailer
    $phpmailerPath = __DIR__ . "/../../vendor/phpmailer/phpmailer/src/PHPMailer.php";
    $includeTests['PHPMailer'] = [
        'path' => $phpmailerPath,
        'exists' => file_exists($phpmailerPath),
        'readable' => is_readable($phpmailerPath)
    ];

    // Test database connection
    $dbConnection = null;
    $dbError = null;
    
    if (file_exists($configPath)) {
        try {
            require_once $configPath;
            $dbConnection = getDatabaseConnection();
            $dbError = null;
        } catch (Exception $e) {
            $dbError = $e->getMessage();
        }
    }

    // Clear any output buffer
    $output = ob_get_clean();

    // Prepare response
    $response = [
        'success' => true,
        'message' => 'Debug API is working',
        'timestamp' => date('Y-m-d H:i:s'),
        'server_info' => $serverInfo,
        'include_tests' => $includeTests,
        'database_connection' => $dbConnection ? 'success' : 'failed',
        'database_error' => $dbError,
        'output_buffer' => $output,
        'request_data' => [
            'get' => $_GET,
            'post' => $_POST,
            'raw_input' => file_get_contents('php://input')
        ]
    ];

    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    // Clear any output buffer
    ob_end_clean();
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
