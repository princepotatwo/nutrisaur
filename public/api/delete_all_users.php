<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Start session to check authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is authenticated
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate confirmation
if (!isset($input['confirm']) || $input['confirm'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Confirmation required']);
    exit();
}

try {
    // Use centralized DatabaseAPI
    require_once __DIR__ . '/DatabaseHelper.php';
    
    // Get database helper instance
    $db = DatabaseHelper::getInstance();
    
    // Check if database is available
    if (!$db->isAvailable()) {
        echo json_encode(['success' => false, 'message' => 'Database connection not available']);
        exit();
    }
    
    // Get count of users before deletion
    $countResult = $db->select('community_users', 'COUNT(*) as count');
    $userCount = $countResult['success'] ? $countResult['data'][0]['count'] : 0;
    
    if ($userCount === 0) {
        echo json_encode(['success' => true, 'message' => 'No users to delete', 'deleted_count' => 0]);
        exit();
    }
    
    // Delete all users from community_users table
    $deleteResult = $db->delete('community_users', '1=1'); // Delete all records
    
    if ($deleteResult['success']) {
        // Log the action
        error_log("All users deleted by admin: " . ($_SESSION['username'] ?? 'Unknown'));
        
        echo json_encode([
            'success' => true, 
            'message' => "Successfully deleted all {$userCount} users",
            'deleted_count' => $userCount
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to delete users: ' . ($deleteResult['error'] ?? 'Unknown error')
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error deleting all users: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
