<?php
header('Content-Type: application/json');

echo json_encode([
    'step' => '1',
    'message' => 'Starting login debug test',
    'current_dir' => __DIR__
]);

try {
    echo json_encode([
        'step' => '2',
        'message' => 'Including DatabaseAPI.php',
        'file_exists' => file_exists(__DIR__ . "/DatabaseAPI.php")
    ]);
    
    require_once __DIR__ . "/DatabaseAPI.php";
    
    echo json_encode([
        'step' => '3',
        'message' => 'Creating DatabaseAPI instance'
    ]);
    
    $db = new DatabaseAPI();
    
    echo json_encode([
        'step' => '4',
        'message' => 'DatabaseAPI created successfully',
        'pdo_available' => $db->getPDO() ? 'yes' : 'no'
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
