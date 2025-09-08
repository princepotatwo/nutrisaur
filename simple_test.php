<?php
// Simple test to check if community_users table exists
require_once 'public/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $db = DatabaseAPI::getInstance();
    
    // Test if community_users table exists
    $result = $db->select('community_users', [], '*', 5, 0, 'screening_date DESC');
    
    $response = [
        'success' => true,
        'message' => 'Database connection successful!',
        'table_exists' => true,
        'count' => count($result),
        'sample_data' => $result
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'table_exists' => false
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
}
?>
