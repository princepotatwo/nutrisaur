<?php
// Simple AJAX test endpoint
session_start();

// Set JSON header
header('Content-Type: application/json');

// Check if this is an AJAX request
$isAjax = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) || 
          ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) ||
          (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');

if (!$isAjax) {
    echo json_encode(['success' => false, 'error' => 'Not an AJAX request']);
    exit;
}

// Get action
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'test':
        echo json_encode([
            'success' => true,
            'message' => 'AJAX test successful',
            'session_id' => session_id(),
            'user_id' => $_SESSION['user_id'] ?? 'not set',
            'username' => $_SESSION['username'] ?? 'not set',
            'is_logged_in' => isset($_SESSION['user_id'])
        ]);
        break;
        
    case 'get_users':
        // Test the Universal DatabaseAPI
        require_once __DIR__ . '/api/DatabaseHelper.php';
        $db = DatabaseHelper::getInstance();
        
        if (!$db->isAvailable()) {
            echo json_encode(['success' => false, 'error' => 'Database not available']);
            break;
        }
        
        $result = $db->select('user_preferences', '*', '', [], 'created_at DESC', '5');
        
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'data' => $result['data'],
                'count' => $result['count']
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => $result['message']]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action]);
        break;
}
?>