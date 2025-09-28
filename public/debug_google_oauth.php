<?php
// Debug Google OAuth API
header('Content-Type: application/json');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Test if DatabaseAPI.php can be included
    if (file_exists('./DatabaseAPI.php')) {
        require_once './DatabaseAPI.php';
        $db = new DatabaseAPI();
        $dbAvailable = $db->isAvailable();
    } else {
        $dbAvailable = false;
    }
    
    // Test database connection
    $dbConnection = false;
    if ($dbAvailable) {
        try {
            $testQuery = $db->select('community_users', 'COUNT(*) as count', '1=1', []);
            $dbConnection = true;
        } catch (Exception $e) {
            $dbConnection = false;
            $dbError = $e->getMessage();
        }
    }
    
    // Test Google OAuth token verification
    $testToken = 'test_token';
    $googleResponse = @file_get_contents("https://oauth2.googleapis.com/tokeninfo?id_token=$testToken");
    $googleApiWorking = $googleResponse !== false;
    
    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'database_file_exists' => file_exists('./DatabaseAPI.php'),
        'database_available' => $dbAvailable,
        'database_connection' => $dbConnection,
        'database_error' => $dbError ?? null,
        'google_api_working' => $googleApiWorking,
        'current_directory' => getcwd(),
        'script_location' => __FILE__,
        'php_version' => phpversion(),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
