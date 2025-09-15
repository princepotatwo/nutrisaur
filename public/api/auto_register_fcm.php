<?php
/**
 * Auto Register FCM Token
 * Simplified version that uses community_users table
 */

// Include the config file for database connection functions
require_once __DIR__ . '/../config.php';

// Function to automatically register/update FCM token
function autoRegisterFCMToken($conn, $fcmToken, $deviceInfo) {
    try {
        // Check if token already exists in community_users
        $checkStmt = $conn->prepare("SELECT community_user_id FROM community_users WHERE fcm_token = ?");
        $checkStmt->execute([$fcmToken]);
        $existingToken = $checkStmt->fetch();
        
        if ($existingToken) {
            // Update existing token with new device info
            $stmt = $conn->prepare("
                UPDATE community_users 
                SET fcm_token = ? 
                WHERE fcm_token = ?
            ");
            $stmt->execute([
                $fcmToken,
                $fcmToken
            ]);
            
            return [
                'success' => true,
                'action' => 'updated',
                'message' => 'FCM token updated successfully',
                'token_id' => $existingToken['community_user_id']
            ];
        } else {
            // Check if user exists by email
            if (!empty($deviceInfo['user_email']) && $deviceInfo['user_email'] !== 'unknown@example.com') {
                $userCheckStmt = $conn->prepare("SELECT community_user_id FROM community_users WHERE email = ?");
                $userCheckStmt->execute([$deviceInfo['user_email']]);
                $existingUser = $userCheckStmt->fetch();
                
                if ($existingUser) {
                    // Update existing user with FCM token
                    $stmt = $conn->prepare("
                        UPDATE community_users 
                        SET fcm_token = ? 
                        WHERE email = ?
                    ");
                    $stmt->execute([
                        $fcmToken,
                        $deviceInfo['user_email']
                    ]);
                    
                    return [
                        'success' => true,
                        'action' => 'updated_user',
                        'message' => 'FCM token added to existing user',
                        'token_id' => $existingUser['community_user_id']
                    ];
                }
            }
            
            // Insert new user with FCM token (minimal data)
            $stmt = $conn->prepare("
                INSERT INTO community_users (email, fcm_token, barangay, screening_date) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([
                $deviceInfo['user_email'] ?? 'unknown@example.com',
                $fcmToken,
                $deviceInfo['user_barangay'] ?? 'Unknown'
            ]);
            
            $tokenId = $conn->lastInsertId();
            
            return [
                'success' => true,
                'action' => 'registered',
                'message' => 'FCM token registered successfully',
                'token_id' => $tokenId
            ];
        }
    } catch(PDOException $e) {
        error_log("Auto register FCM token error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ];
    }
}

// Main execution
try {
    $conn = getDatabaseConnection();
    
    if (!$conn) {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed'
        ]);
        exit;
    }
    
    // Handle POST request for token registration
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid JSON data'
            ]);
            exit;
        }
        
        $fcmToken = $data['fcm_token'] ?? '';
        $deviceInfo = [
            'device_name' => $data['device_name'] ?? 'Unknown Device',
            'user_email' => $data['user_email'] ?? 'unknown@example.com',
            'user_barangay' => $data['user_barangay'] ?? 'Unknown'
        ];
        
        if (empty($fcmToken)) {
            echo json_encode([
                'success' => false,
                'message' => 'FCM token is required'
            ]);
            exit;
        }
        
        $result = autoRegisterFCMToken($conn, $fcmToken, $deviceInfo);
        echo json_encode($result);
        
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Handle GET request for token status check
        try {
            if (isset($_GET['fcm_token'])) {
                $fcmToken = trim($_GET['fcm_token']);
                
                $stmt = $conn->prepare("SELECT * FROM community_users WHERE fcm_token = ?");
                $stmt->execute([$fcmToken]);
                $token = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($token) {
                    echo json_encode([
                        'success' => true,
                        'token_exists' => true,
                        'user_email' => $token['email'],
                        'barangay' => $token['barangay'],
                        'created_at' => $token['screening_date']
                    ]);
                } else {
                    echo json_encode([
                        'success' => true,
                        'token_exists' => false,
                        'message' => 'Token not found'
                    ]);
                }
            } else {
                // Return token count
                $stmt = $conn->query("SELECT COUNT(*) as count FROM community_users WHERE fcm_token IS NOT NULL AND fcm_token != ''");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'total_tokens' => $result['count'],
                    'message' => 'FCM token statistics'
                ]);
            }
        } catch(PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
    }
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>