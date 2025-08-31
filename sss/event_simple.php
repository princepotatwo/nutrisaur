<?php
// Simple event.php for testing notifications
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set test user session
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'kevinpingol';
$_SESSION['email'] = 'kevinpingol@example.com';

// Database connection
$mysql_host = 'mainline.proxy.rlwy.net';
$mysql_port = 26063;
$mysql_user = 'root';
$mysql_password = 'nZhQwfTnAJfFieCpIclAMtOQbBxcjwgy';
$mysql_database = 'railway';

try {
    $dsn = "mysql:host={$mysql_host};port={$mysql_port};dbname={$mysql_database};charset=utf8mb4";
    $conn = new PDO($dsn, $mysql_user, $mysql_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ]);
    $dbConnected = true;
} catch(Exception $e) {
    $dbConnected = false;
    $errorMessage = "Database connection failed: " . $e->getMessage();
}

// Handle event creation
if ($_POST['action'] === 'create_event') {
    $eventTitle = $_POST['eventTitle'] ?? '';
    $eventDescription = $_POST['eventDescription'] ?? '';
    $eventLocation = $_POST['eventLocation'] ?? 'All Locations';
    
    if ($eventTitle && $eventDescription) {
        // Send notification to all users
        $result = sendNotificationToAllUsers($eventTitle, $eventDescription, $eventLocation);
        $successMessage = "Event created and notifications sent! Result: " . json_encode($result);
    } else {
        $errorMessage = "Please fill in all required fields.";
    }
}

// Function to send notification to all users
function sendNotificationToAllUsers($title, $body, $location) {
    global $conn;
    
    try {
        // Get all FCM tokens
        $stmt = $conn->prepare("SELECT user_email, user_barangay FROM fcm_tokens WHERE is_active = TRUE AND fcm_token IS NOT NULL");
        $stmt->execute();
        $users = $stmt->fetchAll();
        
        $successCount = 0;
        $failCount = 0;
        
        foreach ($users as $user) {
            $notificationData = [
                'title' => "🎯 Event: " . $title,
                'body' => $body . " - Location: " . $location,
                'target_user' => $user['user_email'],
                'user_name' => $user['user_email'],
                'alert_type' => 'event_notification'
            ];
            
            $result = sendNotificationViaAPI($notificationData);
            if ($result['success']) {
                $successCount++;
            } else {
                $failCount++;
            }
        }
        
        return [
            'success' => true,
            'total_users' => count($users),
            'notifications_sent' => $successCount,
            'notifications_failed' => $failCount
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Function to send notification via API
function sendNotificationViaAPI($notificationData) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://nutrisaur-production.up.railway.app/api/send_notification.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'notification_data' => json_encode($notificationData)
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => $error];
    }
    
    $responseData = json_decode($response, true);
    return $responseData ?: ['success' => false, 'error' => 'Invalid response'];
}

// Get user count for display
$userCount = 0;
if ($dbConnected) {
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM fcm_tokens WHERE is_active = TRUE AND fcm_token IS NOT NULL");
        $stmt->execute();
        $result = $stmt->fetch();
        $userCount = $result['count'] ?? 0;
    } catch (Exception $e) {
        $userCount = 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Test - NutriSaur</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { background: #4CAF50; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background: #45a049; }
        .success { color: green; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Event Test - NutriSaur</h1>
            <p>Test event creation and notification system</p>
            <div class="info">
                <strong>Logged in as:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo htmlspecialchars($_SESSION['email']); ?>)<br>
                <strong>Users with FCM tokens:</strong> <?php echo $userCount; ?>
            </div>
        </div>

        <?php if (isset($successMessage)): ?>
            <div class="success"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>

        <?php if (isset($errorMessage)): ?>
            <div class="error"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="action" value="create_event">
            
            <div class="form-group">
                <label for="eventTitle">Event Title:</label>
                <input type="text" id="eventTitle" name="eventTitle" placeholder="e.g., Nutrition Workshop" required>
            </div>

            <div class="form-group">
                <label for="eventDescription">Event Description:</label>
                <textarea id="eventDescription" name="eventDescription" rows="4" placeholder="Describe the event..." required></textarea>
            </div>

            <div class="form-group">
                <label for="eventLocation">Target Location:</label>
                <select id="eventLocation" name="eventLocation">
                    <option value="All Locations">All Locations (All Users)</option>
                    <option value="Specific Barangay">Specific Barangay</option>
                    <option value="Specific Municipality">Specific Municipality</option>
                </select>
            </div>

            <button type="submit">🚀 Create Event & Send Notifications</button>
        </form>

        <div class="info" style="margin-top: 30px;">
            <h3>How to Test:</h3>
            <ol>
                <li>Fill in the event details above</li>
                <li>Click "Create Event & Send Notifications"</li>
                <li>Check your device for push notifications</li>
                <li>The system will send notifications to all users with FCM tokens</li>
            </ol>
        </div>
    </div>
</body>
</html>
