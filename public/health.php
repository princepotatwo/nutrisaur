<?php
// Health check endpoint
header('Content-Type: application/json');
echo json_encode([
    'status' => 'healthy',
    'timestamp' => date('Y-m-d H:i:s'),
    'version' => '1.0.0'
]);
?>
