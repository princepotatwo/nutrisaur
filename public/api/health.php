<?php
// Simple health check for API
$response = [
    'success' => true,
    'message' => 'API is healthy',
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'path' => $_SERVER['REQUEST_URI'],
    'get_params' => $_GET,
    'test_db_param' => $_GET['test_db'] ?? 'not_set',
    'action_param' => $_GET['action'] ?? 'not_set'
];

// Add database test if requested
if (isset($_GET['test_db']) && $_GET['test_db'] === 'community_users') {
    try {
        require_once __DIR__ . "/../../config.php";
        $pdo = getDatabaseConnection();
        
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT * FROM community_users ORDER BY screening_date DESC LIMIT 5");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response['database_test'] = [
                'success' => true,
                'message' => 'Community users table is working!',
                'table_exists' => true,
                'count' => count($users),
                'sample_data' => $users
            ];
        } else {
            $response['database_test'] = [
                'success' => false,
                'message' => 'Database connection failed'
            ];
        }
    } catch (Exception $e) {
        $response['database_test'] = [
            'success' => false,
            'error' => $e->getMessage(),
            'table_exists' => false
        ];
    }
}

// Add full data access if requested
if (isset($_GET['action']) && $_GET['action'] === 'community_data') {
    try {
        require_once __DIR__ . "/../../config.php";
        $pdo = getDatabaseConnection();
        
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT * FROM community_users ORDER BY screening_date DESC");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response = [
                'success' => true,
                'count' => count($users),
                'data' => $users
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Database connection failed'
            ];
        }
    } catch (Exception $e) {
        $response = [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
