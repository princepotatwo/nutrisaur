<?php
require_once 'public/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $db = DatabaseAPI::getInstance();
    
    // Test database connection
    $users = $db->select('community_users', [], '*', 10, 0, 'screening_date DESC');
    
    $response = [
        'success' => true,
        'message' => 'Database connection successful',
        'count' => count($users),
        'data' => $users
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
