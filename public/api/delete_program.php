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

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Include database helper
require_once __DIR__ . '/DatabaseHelper.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['program_id'])) {
        echo json_encode(['success' => false, 'message' => 'Program ID is required']);
        exit();
    }
    
    $program_id = trim($input['program_id']);
    
    if (empty($program_id) || !is_numeric($program_id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid program ID']);
        exit();
    }
    
    // Get database instance
    $db = DatabaseHelper::getInstance();
    
    if (!$db->isAvailable()) {
        echo json_encode(['success' => false, 'message' => 'Database connection not available']);
        exit();
    }
    
    // Check if program exists
    $result = $db->select('programs', 'program_id', 'program_id = ?', [$program_id]);
    
    if (!$result['success'] || empty($result['data'])) {
        echo json_encode(['success' => false, 'message' => 'Program not found']);
        exit();
    }
    
    // Delete the program using program_id as the primary key
    $deleteResult = $db->delete('programs', 'program_id = ?', [$program_id]);
    
    if ($deleteResult['success']) {
        echo json_encode(['success' => true, 'message' => 'Program deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete program: ' . ($deleteResult['error'] ?? 'Unknown error')]);
    }
    
} catch (Exception $e) {
    error_log("Delete program error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
