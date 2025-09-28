<?php
session_start();

// Clear FCM token if user is logged in
if (isset($_SESSION['user_email'])) {
    $userEmail = $_SESSION['user_email'];
    
    // Clear FCM token from database
    try {
        require_once __DIR__ . "/../config.php";
        $pdo = getDatabaseConnection();
        
        if ($pdo) {
            $stmt = $pdo->prepare("UPDATE community_users SET fcm_token = NULL WHERE email = ?");
            $stmt->execute([$userEmail]);
            error_log("FCM token cleared for user: " . $userEmail);
        }
    } catch (Exception $e) {
        error_log("Error clearing FCM token: " . $e->getMessage());
    }
}

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to home page
header('Location: home.php');
exit();
?>
