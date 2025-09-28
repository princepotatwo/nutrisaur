<?php
// Test database connection
header('Content-Type: application/json');

try {
    // Try different paths for DatabaseAPI.php
    $possiblePaths = [
        './api/DatabaseAPI.php',
        './DatabaseAPI.php',
        __DIR__ . '/api/DatabaseAPI.php',
        __DIR__ . '/DatabaseAPI.php'
    ];
    
    $dbPath = null;
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $dbPath = $path;
            break;
        }
    }
    
    if (!$dbPath) {
        throw new Exception('DatabaseAPI.php not found in any expected location');
    }
    
    require_once $dbPath;
    $db = new DatabaseAPI();
    
    if (!$db->isAvailable()) {
        throw new Exception('Database not available');
    }
    
    // Test database connection
    $testQuery = $db->select('community_users', 'COUNT(*) as count', '1=1', []);
    
    echo json_encode([
        'success' => true,
        'database_path' => $dbPath,
        'database_available' => true,
        'test_query_result' => $testQuery,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'possible_paths' => $possiblePaths ?? [],
        'current_directory' => getcwd(),
        'script_directory' => __DIR__
    ]);
}
?>
