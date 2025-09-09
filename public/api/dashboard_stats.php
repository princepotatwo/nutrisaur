<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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
    $db = DatabaseHelper::getInstance();
    
    if (!$db->isAvailable()) {
        echo json_encode(['success' => false, 'message' => 'Database connection not available']);
        exit();
    }
    
    $stats = [];
    
    // Get total community users
    $totalUsersResult = $db->select('community_users', 'COUNT(*) as count');
    $stats['total_users'] = $totalUsersResult['success'] ? $totalUsersResult['data'][0]['count'] : 0;
    
    // Get new users today
    $today = date('Y-m-d');
    $newTodayResult = $db->select('community_users', 'COUNT(*) as count', 'DATE(screening_date) = ?', [$today]);
    $stats['new_today'] = $newTodayResult['success'] ? $newTodayResult['data'][0]['count'] : 0;
    
    // Get new users this week
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $newWeekResult = $db->select('community_users', 'COUNT(*) as count', 'DATE(screening_date) >= ?', [$weekStart]);
    $stats['new_this_week'] = $newWeekResult['success'] ? $newWeekResult['data'][0]['count'] : 0;
    
    // Get municipality breakdown
    $municipalityResult = $db->select('community_users', 'municipality, COUNT(*) as count', '', [], 'GROUP BY municipality ORDER BY count DESC');
    $stats['municipalities'] = $municipalityResult['success'] ? $municipalityResult['data'] : [];
    
    // Get gender breakdown
    $genderResult = $db->select('community_users', 'sex, COUNT(*) as count', '', [], 'GROUP BY sex');
    $stats['gender_breakdown'] = $genderResult['success'] ? $genderResult['data'] : [];
    
    // Get recent users (last 10)
    $recentUsersResult = $db->select('community_users', 'name, email, municipality, barangay, screening_date', '', [], 'ORDER BY screening_date DESC LIMIT 10');
    $stats['recent_users'] = $recentUsersResult['success'] ? $recentUsersResult['data'] : [];
    
    // Get screening data by date (last 30 days)
    $thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));
    $screeningByDateResult = $db->select('community_users', 'DATE(screening_date) as date, COUNT(*) as count', 'DATE(screening_date) >= ?', [$thirtyDaysAgo], 'GROUP BY DATE(screening_date) ORDER BY date DESC');
    $stats['screening_by_date'] = $screeningByDateResult['success'] ? $screeningByDateResult['data'] : [];
    
    // Add timestamp for change detection
    $stats['last_updated'] = time();
    $stats['timestamp'] = date('Y-m-d H:i:s');
    
    echo json_encode([
        'success' => true, 
        'stats' => $stats,
        'message' => 'Dashboard stats updated successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
