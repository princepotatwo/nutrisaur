<?php
// Simple test file to verify API routing
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'API routing test successful',
    'timestamp' => date('Y-m-d H:i:s'),
    'file_location' => __FILE__
]);
?>
