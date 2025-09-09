<?php
// Simple FCM Token Registration for Android App
// Uses community_users table directly
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database connection
require_once __DIR__ . '/../../config.php';

function logFCMOperation($message, $data = null) {
    $logEntry = date('Y-m-d H:i:s') . " - " . $message;
    if ($data) {
        $logEntry .= " - Data: " . json_encode($data);
    }
    error_log("[FCM_SIMPLE] " . $logEntry);
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method is allowed');
    }
    
    // Get database connection
    $conn = getDatabaseConnection();
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    logFCMOperation("Raw input received", $input);
    logFCMOperation("Decoded JSON data", $data);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    
    // Extract data (remove action if present)
    $fcmToken = $data['fcm_token'] ?? '';
    $deviceName = $data['device_name'] ?? 'Unknown Device';
    $userEmail = $data['user_email'] ?? '';
    $userBarangay = $data['user_barangay'] ?? '';
    $appVersion = $data['app_version'] ?? '1.0';
    $platform = $data['platform'] ?? 'android';
    
    logFCMOperation("Extracted data", [
        'fcm_token' => substr($fcmToken, 0, 20) . '...',
        'device_name' => $deviceName,
        'user_email' => $userEmail,
        'user_barangay' => $userBarangay,
        'app_version' => $appVersion,
        'platform' => $platform
    ]);
    
    if (empty($fcmToken)) {
        throw new Exception('FCM token is required');
    }
    
    // Check if user exists by email
    if (!empty($userEmail)) {
        logFCMOperation("Checking if user exists", ['email' => $userEmail]);
        
        $stmt = $conn->prepare("SELECT community_user_id FROM community_users WHERE email = ?");
        $stmt->execute([$userEmail]);
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingUser) {
            logFCMOperation("User found, updating FCM token", ['user_id' => $existingUser['community_user_id']]);
            
            // Update existing user with FCM token
            $stmt = $conn->prepare("UPDATE community_users SET fcm_token = ?, updated_at = NOW() WHERE email = ?");
            $stmt->execute([$fcmToken, $userEmail]);
            
            logFCMOperation("FCM token updated successfully for existing user");
            
            echo json_encode([
                'success' => true,
                'message' => 'FCM token updated successfully',
                'action' => 'updated_existing_user'
            ]);
            exit;
        }
    }
    
    // Check if FCM token already exists
    logFCMOperation("Checking if FCM token already exists");
    
    $stmt = $conn->prepare("SELECT community_user_id, email FROM community_users WHERE fcm_token = ?");
    $stmt->execute([$fcmToken]);
    $existingToken = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingToken) {
        logFCMOperation("FCM token already exists", ['user_id' => $existingToken['community_user_id'], 'email' => $existingToken['email']]);
        
        // Update the existing record with new user info if provided
        if (!empty($userEmail) && $userEmail !== $existingToken['email']) {
            $stmt = $conn->prepare("UPDATE community_users SET email = ?, barangay = ?, updated_at = NOW() WHERE fcm_token = ?");
            $stmt->execute([$userEmail, $userBarangay, $fcmToken]);
            logFCMOperation("Updated existing FCM token record with new user info");
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'FCM token already registered',
            'action' => 'token_exists'
        ]);
        exit;
    }
    
    // Create new user record with FCM token
    logFCMOperation("Creating new user with FCM token");
    
    $stmt = $conn->prepare("INSERT INTO community_users (email, fcm_token, barangay, screening_date, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW(), NOW())");
    $stmt->execute([
        $userEmail ?: 'app_user_' . time() . '@nutrisaur.app',
        $fcmToken,
        $userBarangay ?: 'Unknown'
    ]);
    
    $userId = $conn->lastInsertId();
    
    logFCMOperation("New user created with FCM token", ['user_id' => $userId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'FCM token registered successfully',
        'action' => 'created_new_user',
        'user_id' => $userId
    ]);
    
} catch (Exception $e) {
    logFCMOperation("FCM token registration error", ['error' => $e->getMessage()]);
    
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'error_code' => 'REGISTRATION_FAILED'
    ]);
}
?>