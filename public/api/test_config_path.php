<?php
header('Content-Type: application/json');

echo json_encode([
    'step' => '1',
    'current_dir' => __DIR__,
    'config_path' => __DIR__ . "/../config.php",
    'config_exists' => file_exists(__DIR__ . "/../config.php")
]);

try {
    require_once __DIR__ . "/../config.php";
    
    echo json_encode([
        'step' => '2',
        'message' => 'Config.php included successfully',
        'pdo_connection' => getDatabaseConnection() ? 'success' : 'failed'
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
