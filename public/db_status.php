<?php
// Simple database status checker
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/api/DatabaseAPI.php";

header('Content-Type: application/json');

try {
    $db = DatabaseAPI::getInstance();
    $status = $db->getDatabaseStatus();
    
    echo json_encode([
        'success' => true,
        'database_status' => $status,
        'timestamp' => date('Y-m-d H:i:s'),
        'environment' => [
            'MYSQL_PUBLIC_URL_set' => isset($_ENV['MYSQL_PUBLIC_URL']),
            'MYSQL_PUBLIC_URL_value' => isset($_ENV['MYSQL_PUBLIC_URL']) ? substr($_ENV['MYSQL_PUBLIC_URL'], 0, 20) . '...' : 'NOT_SET'
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
?>
