<?php
// Simple health check endpoint
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Add some debugging info
$debug_info = [
    'status' => 'healthy',
    'timestamp' => date('Y-m-d H:i:s'),
    'version' => '1.0.0',
    'environment' => 'production',
    'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
    'php_version' => PHP_VERSION,
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
    'script_name' => __FILE__
];

echo json_encode($debug_info, JSON_PRETTY_PRINT);
?>
