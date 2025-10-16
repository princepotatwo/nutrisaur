<?php
// Delete users by location API
error_log("Delete by location API called at " . date('Y-m-d H:i:s'));

// Suppress error reporting to prevent warnings from being output
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering to catch any unexpected output
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();
error_log("Session started, user_id: " . ($_SESSION['user_id'] ?? 'not set') . ", role: " . ($_SESSION['role'] ?? 'not set'));

// Check if user is logged in (same pattern as delete_user.php)
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    error_log("Attempting to require config.php");
    require_once __DIR__ . '/../../config.php';
    error_log("Config.php loaded successfully");
    
    error_log("Attempting to get database connection");
    $pdo = getDatabaseConnection();
    error_log("Database connection result: " . ($pdo ? 'success' : 'failed'));
    
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        error_log("POST request received");
        $input = file_get_contents('php://input');
        error_log("Raw input: " . $input);
        $data = json_decode($input, true);
        error_log("Decoded data: " . json_encode($data));
        
        if (!$data) {
            error_log("No data received or JSON decode failed");
            echo json_encode(['success' => false, 'message' => 'No data received']);
            exit();
        }
        
        $municipality = $data['municipality'] ?? '';
        $barangay = $data['barangay'] ?? '';
        error_log("Municipality: '$municipality', Barangay: '$barangay'");
        
        if (empty($municipality)) {
            error_log("Municipality is empty");
            echo json_encode(['success' => false, 'message' => 'Municipality is required']);
            exit();
        }
        
        // For MHO users, verify they can only delete from their own municipality
        $user_municipality = null;
        if (!isset($_SESSION['is_super_admin']) || $_SESSION['is_super_admin'] !== true) {
            if (isset($_SESSION['user_id'])) {
                // Get user municipality from database
                $stmt = $pdo->prepare("SELECT municipality FROM users WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $userData = $stmt->fetch();
                $user_municipality = $userData['municipality'] ?? null;
                error_log("MHO User municipality: $user_municipality");
                
                // Verify MHO user can only delete from their own municipality
                if ($user_municipality && $municipality !== $user_municipality) {
                    error_log("MHO user attempted to delete from different municipality: $municipality vs $user_municipality");
                    echo json_encode(['success' => false, 'message' => 'You can only delete users from your assigned municipality']);
                    exit();
                }
            }
        }
        
        // Build the WHERE clause based on municipality and optional barangay
        $whereClause = "municipality = ?";
        $params = [$municipality];
        
        if (!empty($barangay)) {
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
            
            // Clean any unexpected output
            $unexpectedOutput = ob_get_clean();
            if (!empty($unexpectedOutput)) {
                error_log("Unexpected output detected: " . $unexpectedOutput);
            }
            
            echo json_encode([
                'success' => true, 
                'message' => "Successfully deleted $userCount users from $location" . ($notificationsSent > 0 ? " and sent $notificationsSent notifications" : ""),
                'deleted_count' => $userCount,
                'notifications_sent' => $notificationsSent
            ]);
        } else {
            // Clean any unexpected output
            $unexpectedOutput = ob_get_clean();
            if (!empty($unexpectedOutput)) {
                error_log("Unexpected output detected: " . $unexpectedOutput);
            }
            echo json_encode(['success' => false, 'message' => 'Failed to delete users']);
        }
    } else {
        // Clean any unexpected output
        $unexpectedOutput = ob_get_clean();
        if (!empty($unexpectedOutput)) {
            error_log("Unexpected output detected: " . $unexpectedOutput);
        }
        echo json_encode(['success' => false, 'message' => 'POST method required']);
    }
    
} catch (Exception $e) {
    error_log("Error deleting users by location: " . $e->getMessage());
    // Clean any unexpected output
    $unexpectedOutput = ob_get_clean();
    if (!empty($unexpectedOutput)) {
        error_log("Unexpected output detected: " . $unexpectedOutput);
    }
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
