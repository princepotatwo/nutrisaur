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
    
    // Get count of programs before deletion
    $countResult = $db->select('programs', 'COUNT(*) as count');
    $programCount = $countResult['success'] ? $countResult['data'][0]['count'] : 0;
    
    if ($programCount === 0) {
        echo json_encode(['success' => true, 'message' => 'No programs to delete', 'deleted_count' => 0]);
        exit();
    }
    
    // Get all program details before deletion to clean up lock files
    $programsResult = $db->select('programs', 'title, location, date_time');
    $programs = $programsResult['success'] ? $programsResult['data'] : [];
    
    // Delete all programs from programs table
    $deleteResult = $db->delete('programs', '1=1'); // Delete all records
    
    if ($deleteResult['success']) {
        // Clean up all corresponding lock files
        $deletedLockFiles = 0;
        foreach ($programs as $program) {
            $eventKey = md5($program['title'] . $program['location'] . date('Y-m-d H:i:s', strtotime($program['date_time'])));
            $lockFile = "/tmp/notification_" . $eventKey . ".lock";
            
            if (file_exists($lockFile)) {
                unlink($lockFile);
                $deletedLockFiles++;
            }
        }
        
        // Log the action
        error_log("All programs deleted by admin: " . ($_SESSION['username'] ?? 'Unknown') . " - Cleaned up {$deletedLockFiles} lock files");
        
        echo json_encode([
            'success' => true, 
            'message' => "Successfully deleted all {$programCount} programs and {$deletedLockFiles} lock files",
            'deleted_count' => $programCount
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to delete programs: ' . ($deleteResult['error'] ?? 'Unknown error')
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error deleting all programs: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
