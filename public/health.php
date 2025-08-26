<?php
// Set headers for Railway health check
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Get server information
$server_info = [
    'status' => 'healthy',
    'timestamp' => date('Y-m-d H:i:s'),
    'version' => '1.0.0',
    'message' => 'Nutrisaur is running successfully!',
    'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'port' => $_SERVER['SERVER_PORT'] ?? 'Unknown',
    'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown'
];

// Output JSON response
echo json_encode($server_info, JSON_PRETTY_PRINT);
?>
