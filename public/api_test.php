<?php
// Simple test to verify API routing
header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'Direct API test successful',
    'timestamp' => date('Y-m-d H:i:s'),
    'server_info' => [
        'request_uri' => $_SERVER['REQUEST_URI'],
        'script_name' => $_SERVER['SCRIPT_NAME'],
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
        'current_dir' => __DIR__
    ]
]);
?>
