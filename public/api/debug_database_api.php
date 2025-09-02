<?php
header('Content-Type: application/json');

try {
    echo json_encode([
        'step' => '1',
        'message' => 'Starting DatabaseAPI test',
        'current_dir' => __DIR__,
        'config_path' => __DIR__ . "/../config.php",
        'config_exists' => file_exists(__DIR__ . "/../config.php")
    ]);
    
    // Test including config.php directly
    require_once __DIR__ . "/../config.php";
    
    echo json_encode([
        'step' => '2',
        'message' => 'Config.php included successfully',
        'pdo_connection' => getDatabaseConnection() ? 'success' : 'failed'
    ]);
    
    // Test DatabaseAPI class
    require_once __DIR__ . "/DatabaseAPI.php";
    $db = new DatabaseAPI();
    
    echo json_encode([
        'step' => '3',
        'message' => 'DatabaseAPI instantiated',
        'pdo_available' => $db->getPDO() ? 'yes' : 'no',
        'test_connection' => $db->testConnection() ? 'success' : 'failed'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
