<?php
header('Content-Type: application/json');

try {
    // Include the centralized Database API
    require_once __DIR__ . "/DatabaseAPI.php";

    // Initialize the database API
    $db = new DatabaseAPI();
    
    // Debug: Check if database connection is available
    $pdo = $db->getPDO();
    $testConnection = $db->testConnection();
    
    echo json_encode([
        'success' => false, 
        'message' => 'Database connection test',
        'debug' => [
            'pdo_available' => $pdo ? 'yes' : 'no',
            'test_connection' => $testConnection ? 'success' : 'failed'
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error occurred',
        'debug' => $e->getMessage()
    ]);
}
?>
