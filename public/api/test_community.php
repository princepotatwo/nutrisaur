<?php
// Simple test for community users table
echo json_encode([
    'success' => true,
    'message' => 'Test endpoint working',
    'timestamp' => date('Y-m-d H:i:s'),
    'get_params' => $_GET
], JSON_PRETTY_PRINT);
?>