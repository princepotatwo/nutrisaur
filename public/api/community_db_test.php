<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Headers are now set by the router
session_start();

try {
    // Use the same working approach as other API files
    require_once __DIR__ . "/../../config.php";
    $pdo = getDatabaseConnection();
    
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    // Test if community_users table exists
    $stmt = $pdo->prepare("SELECT * FROM community_users ORDER BY screening_date DESC LIMIT 5");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
