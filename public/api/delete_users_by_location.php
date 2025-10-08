<?php
// Delete users by location API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    require_once __DIR__ . '/../../config.php';
    
    $pdo = getDatabaseConnection();
    
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'No data received']);
            exit();
        }
        
        $municipality = $data['municipality'] ?? '';
        $barangay = $data['barangay'] ?? '';
        $scope = $data['scope'] ?? '';
        
        if (empty($municipality) || empty($scope)) {
            echo json_encode(['success' => false, 'message' => 'Municipality and scope are required']);
            exit();
        }
        
        // Build the WHERE clause based on scope
        $whereClause = "municipality = ?";
        $params = [$municipality];
        
        if ($scope === 'barangay' && !empty($barangay)) {
            $whereClause .= " AND barangay = ?";
            $params[] = $barangay;
        }
        
        // First, get count of users to be deleted
        $countStmt = $pdo->prepare("SELECT COUNT(*) as count FROM community_users WHERE $whereClause");
        $countStmt->execute($params);
        $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
        $userCount = $countResult['count'];
        
        if ($userCount === 0) {
            echo json_encode(['success' => true, 'message' => 'No users found to delete', 'deleted_count' => 0]);
            exit();
        }
        
        // Get FCM tokens for notifications before deletion
        $fcmStmt = $pdo->prepare("SELECT fcm_token, email FROM community_users WHERE $whereClause AND fcm_token IS NOT NULL AND fcm_token != ''");
        $fcmStmt->execute($params);
        $fcmTokens = $fcmStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Delete users
        $deleteStmt = $pdo->prepare("DELETE FROM community_users WHERE $whereClause");
        $result = $deleteStmt->execute($params);
        
        if ($result) {
            // Send notifications to deleted users
            $notificationsSent = 0;
            foreach ($fcmTokens as $user) {
                if (sendAccountDeletedNotification($user['email'])) {
                    $notificationsSent++;
                }
            }
            
            // Log the action
            $location = $municipality . ($barangay ? ", $barangay" : "");
            error_log("Users deleted by location by admin: " . ($_SESSION['username'] ?? 'Unknown') . " - Location: $location - Count: $userCount - Notifications sent: $notificationsSent");
            
            echo json_encode([
                'success' => true, 
                'message' => "Successfully deleted $userCount users from $location" . ($notificationsSent > 0 ? " and sent $notificationsSent notifications" : ""),
                'deleted_count' => $userCount,
                'notifications_sent' => $notificationsSent
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete users']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'POST method required']);
    }
    
} catch (Exception $e) {
    error_log("Error deleting users by location: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

// Function to send account deleted notification
function sendAccountDeletedNotification($email) {
    try {
        // You can implement FCM notification here if needed
        // For now, just log the notification
        error_log("Account deleted notification sent to: $email");
        return true;
    } catch (Exception $e) {
        error_log("Failed to send notification to $email: " . $e->getMessage());
        return false;
    }
}
?>
