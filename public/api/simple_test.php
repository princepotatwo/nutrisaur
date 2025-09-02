<?php
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'API file accessed directly',
    'timestamp' => date('Y-m-d H:i:s'),
    'server' => $_SERVER['SERVER_NAME'],
    'path' => __FILE__
]);
?>
