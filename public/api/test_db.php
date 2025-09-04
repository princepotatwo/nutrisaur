<?php
// Test database connection
error_log("=== DATABASE TEST START ===");

try {
    require_once __DIR__ . "/../config.php";
    error_log("Config included successfully");
    
    $pdo = getDatabaseConnection();
    error_log("Database connection attempt completed");
    
    if ($pdo) {
        // Test a simple query
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'message' => 'Database connection successful',
            'test_result' => $result,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
} catch (Exception $e) {
    error_log("Database test error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

error_log("=== DATABASE TEST END ===");
?>
