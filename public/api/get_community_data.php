<?php
// Simple API endpoint to get community data
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once '../config.php';
    $pdo = getDatabaseConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Get all community users
    $stmt = $pdo->prepare("SELECT * FROM community_users ORDER BY screening_date DESC");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $totalUsers = count($users);
    $municipalities = array_unique(array_column($users, 'municipality'));
    $maleUsers = count(array_filter($users, function($user) { return strtolower($user['sex']) === 'male'; }));
    $femaleUsers = count(array_filter($users, function($user) { return strtolower($user['sex']) === 'female'; }));
    
    $response = [
        'success' => true,
        'data' => [
            'users' => $users,
            'stats' => [
                'total_users' => $totalUsers,
                'total_municipalities' => count($municipalities),
                'male_users' => $maleUsers,
                'female_users' => $femaleUsers
            ]
        ]
    ];
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
