<?php
// Simple health check for API
echo json_encode([
    'success' => true,
    'message' => 'API is healthy',
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'path' => $_SERVER['REQUEST_URI']
]);
?>
