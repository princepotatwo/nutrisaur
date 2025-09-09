<?php
// Super Simple FCM Token Registration - No Validation
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config.php';

try {
    $conn = getDatabaseConnection();
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON');
    }
    
    $fcmToken = $data['fcm_token'] ?? '';
    $userEmail = $data['user_email'] ?? 'app_user_' . time() . '@nutrisaur.app';
    $userBarangay = $data['user_barangay'] ?? 'Unknown';
    
    if (empty($fcmToken)) {
        throw new Exception('FCM token required');
    }
    
    // Try to update existing user first
    $stmt = $conn->prepare("UPDATE community_users SET fcm_token = ?, updated_at = NOW() WHERE email = ?");
    $stmt->execute([$fcmToken, $userEmail]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'FCM token updated', 'action' => 'updated']);
        exit;
    }
    
    // If no update, create new user
    $stmt = $conn->prepare("INSERT INTO community_users (email, fcm_token, barangay, screening_date) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$userEmail, $fcmToken, $userBarangay]);
    
    echo json_encode(['success' => true, 'message' => 'FCM token registered', 'action' => 'created']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
