<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    // Include the centralized configuration
    require_once __DIR__ . "/../../config.php";
    
    // Get database configuration
    $config = getDatabaseConfig();
    
    // Test database connection
    $pdo = getDatabaseConnection();
    $mysqli = getMysqliConnection();
    
    $result = [
        'success' => true,
        'message' => 'Database configuration test',
        'config' => $config,
        'pdo_connection' => $pdo ? 'success' : 'failed',
        'mysqli_connection' => $mysqli ? 'success' : 'failed',
        'test_query' => null
    ];
    
    // Test a simple query if PDO connection works
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT 1 as test");
            $result['test_query'] = $stmt->fetch()['test'];
        } catch (Exception $e) {
            $result['test_query'] = 'failed: ' . $e->getMessage();
        }
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Configuration test failed',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
