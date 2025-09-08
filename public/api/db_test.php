<?php
// Simple database test for community_users table
require_once '../config.php';

try {
    $db = DatabaseAPI::getInstance();
    
    // Test if community_users table exists
    $users = $db->select('community_users', [], '*', 5, 0, 'screening_date DESC');
    
    echo json_encode([
        'success' => true,
        'message' => 'Community users table is working!',
        'table_exists' => true,
        'count' => count($users),
        'sample_data' => $users
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'table_exists' => false
    ], JSON_PRETTY_PRINT);
}
?>
