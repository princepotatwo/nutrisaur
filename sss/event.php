<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session only if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // For development/testing, set default values
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['email'] = 'admin@example.com';
    
    // Uncomment the following lines for production:
    // header("Location: home.php");
    // exit;
}

// Get user info from session
$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
$email = $_SESSION['email'];

// Create a safe database wrapper that won't crash the page
function safeDbQuery($conn, $sql, $params = []) {
    if (!$conn) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare($sql);
        if ($params) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        return $stmt;
    } catch (Exception $e) {
        error_log("Database query failed: " . $e->getMessage());
        return null;
    }
}

    // Use direct database connection like the working API
    $mysql_host = 'mainline.proxy.rlwy.net';
    $mysql_port = 26063;
    $mysql_user = 'root';
    $mysql_password = 'nZhQwfTnAJfFieCpIclAMtOQbBxcjwgy';
    $mysql_database = 'railway';

    // If MYSQL_PUBLIC_URL is set (Railway sets this), parse it
    if (isset($_ENV['MYSQL_PUBLIC_URL'])) {
        $mysql_url = $_ENV['MYSQL_PUBLIC_URL'];
        $pattern = '/mysql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/(.+)/';
        if (preg_match($pattern, $mysql_url, $matches)) {
            $mysql_user = $matches[1];
            $mysql_password = $matches[2];
            $mysql_host = $matches[3];
            $mysql_port = $matches[4];
            $mysql_database = $matches[5];
        }
    }

// Initialize variables
$dbConnected = false;
$errorMessage = null;
$programs = [];

try {
    // Create database connection directly like the working API
    $dsn = "mysql:host={$mysql_host};port={$mysql_port};dbname={$mysql_database};charset=utf8mb4";
    $conn = new PDO($dsn, $mysql_user, $mysql_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ]);
    
    if ($conn) {
        $dbConnected = true;
        
        // Fetch programs from database using safe wrapper
        $stmt = safeDbQuery($conn, "SELECT * FROM programs ORDER BY date_time DESC");
        if ($stmt) {
            $programs = $stmt->fetchAll();
        } else {
            $programs = [];
        }
    } else {
        $dbConnected = false;
        $errorMessage = "Database connection failed: Could not establish connection";
        $programs = [];
    }
    
} catch(Exception $e) {
    $dbConnected = false;
    $errorMessage = "Database connection failed: " . $e->getMessage();
    $programs = [];
}

// Check if program recommendation was passed from community hub
$recommended_program = isset($_GET['program']) ? $_GET['program'] : '';
        $recommended_location = isset($_GET['location']) ? $_GET['location'] : '';
        
        // Debug logging
        if ($recommended_program || $recommended_location) {
            error_log("Program recommendation received - Program: $recommended_program, Location: $recommended_location");
        }
        
        // Add JavaScript debug logging for location parameter
        if ($recommended_location) {
            echo "<script>console.log('PHP: Location parameter received:', '" . addslashes($recommended_location) . "');</script>";
        }

// Handle test notification request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_test_notification'])) {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    try {
        $result = sendTestNotification('Test notification from NutriSaur! Testing push notifications...');
        
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Test notification sent successfully' : 'Failed to send test notification'
            ]);
            exit;
        } else {
            $redirectUrl = "event.php?test=" . ($result ? "1" : "0");
            header("Location: " . $redirectUrl);
            exit;
        }
        
    } catch (Exception $e) {
        $errorMessage = "Error sending test notification: " . $e->getMessage();
        error_log($errorMessage);
        
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $errorMessage]);
            exit;
        } else {
            $redirectUrl = "event.php?test=0&error=" . urlencode($errorMessage);
            header("Location: " . $redirectUrl);
            exit;
        }
    }
}

// Handle location preview request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'get_location_preview') {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    if ($isAjax) {
        try {
            $location = $_POST['location'] ?? '';
            $users = getUsersForLocation($location);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'users' => $users,
                'location' => $location
            ]);
            exit;
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error getting location preview: ' . $e->getMessage()
            ]);
            exit;
        }
    }
}

// Handle get duplicates request
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] === 'get_duplicates') {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    if ($isAjax) {
        try {
            $duplicates = $_SESSION['import_duplicates'] ?? [];
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'duplicates' => $duplicates
            ]);
            exit;
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error getting duplicate data: ' . $e->getMessage()
            ]);
            exit;
        }
    }
}

// Handle debug request for FCM status
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'debug_fcm') {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    if ($isAjax) {
        try {
            $debugInfo = [];
            
            // Check if notification_logs table exists
            $stmt = safeDbQuery($conn, "SHOW TABLES LIKE 'notification_logs'");
            $debugInfo['notification_logs_table_exists'] = $stmt ? $stmt->rowCount() > 0 : false;
            if (!$stmt) {
                $debugInfo['notification_logs_error'] = 'Database connection failed';
            }
            
            // Check if fcm_tokens table exists
            $stmt = safeDbQuery($conn, "SHOW TABLES LIKE 'fcm_tokens'");
            $debugInfo['fcm_tokens_table_exists'] = $stmt ? $stmt->rowCount() > 0 : false;
            if (!$stmt) {
                $debugInfo['fcm_tokens_error'] = 'Database connection failed';
            }
            
            // Check if user_preferences table exists
            $stmt = safeDbQuery($conn, "SHOW TABLES LIKE 'user_preferences'");
            $debugInfo['user_preferences_table_exists'] = $stmt ? $stmt->rowCount() > 0 : false;
            if (!$stmt) {
                $debugInfo['user_preferences_error'] = 'Database connection failed';
            }
            
            // Get FCM token count
            if ($debugInfo['fcm_tokens_table_exists']) {
                $stmt = safeDbQuery($conn, "SELECT COUNT(*) as count FROM fcm_tokens WHERE is_active = TRUE");
                $debugInfo['active_fcm_tokens'] = $stmt ? $stmt->fetchColumn() : 'Database error';
            }
            
            // Get user preferences count
            if ($debugInfo['user_preferences_table_exists']) {
                $stmt = safeDbQuery($conn, "SELECT COUNT(*) as count FROM user_preferences WHERE barangay IS NOT NULL AND barangay != ''");
                $debugInfo['users_with_barangay'] = $stmt ? $stmt->fetchColumn() : 'Database error';
            }
            
            // Check Firebase Admin SDK file
            $adminSdkPath = __DIR__ . '/nutrisaur-ebf29-firebase-adminsdk-fbsvc-152a242b3b.json';
            $debugInfo['firebase_admin_sdk_exists'] = file_exists($adminSdkPath);
            $debugInfo['firebase_admin_sdk_path'] = $adminSdkPath;
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'debug_info' => $debugInfo,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            exit;
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error getting debug info: ' . $e->getMessage()
            ]);
            exit;
        }
    }
}

// Handle personal notification request for critical alerts
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'send_personal_notification') {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    if ($isAjax) {
        try {
            // Get form data
            $notificationData = json_decode($_POST['notification_data'], true);
            
            if (!$notificationData) {
                throw new Exception('Invalid notification data');
            }
            $targetUser = $notificationData['target_user'] ?? '';
            $title = $notificationData['title'] ?? '';
            $body = $notificationData['body'] ?? '';
            $userName = $notificationData['user_name'] ?? '';
            
            if (empty($targetUser) || empty($title) || empty($body)) {
                throw new Exception('Missing required notification data');
            }
            
            // Get FCM token for the specific user
            $stmt = $conn->prepare("
                SELECT ft.fcm_token 
                FROM fcm_tokens ft
                WHERE ft.user_email = ? AND ft.is_active = TRUE 
                AND ft.fcm_token IS NOT NULL AND ft.fcm_token != ''
            ");
            $stmt->execute([$targetUser]);
            $fcmToken = $stmt->fetchColumn();
            
            if (!$fcmToken) {
                throw new Exception('No active FCM token found for user: ' . $targetUser);
            }
            
            // Send FCM notification to the specific user
            $notificationSent = sendFCMNotification([$fcmToken], [
                'title' => $title,
                'body' => $body,
                'data' => [
                    'notification_type' => 'critical_alert',
                    'target_user' => $targetUser,
                    'user_name' => $userName,
                    'alert_type' => $notificationData['alert_type'] ?? 'critical_notification',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
                ]
            ]);
            
            if ($notificationSent) {
                // Log the notification attempt
                logNotificationAttempt(0, 'critical_alert', 'user', $targetUser, 1, true);
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Personal notification sent successfully to ' . $userName,
                    'target_user' => $targetUser,
                    'devices_notified' => 1
                ]);
            } else {
                throw new Exception('Failed to send FCM notification');
            }
            
        } catch (Exception $e) {
            error_log("Error sending personal notification: " . $e->getMessage());
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error sending personal notification: ' . $e->getMessage()
            ]);
        }
        exit;
    }
}

// Handle location-based notification requests
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'send_location_notification') {
    try {
        $location = $_POST['location'] ?? '';
        $title = $_POST['title'] ?? 'Location Notification';
        $message = $_POST['message'] ?? '';
        
        if (empty($location)) {
            throw new Exception('Location is required');
        }
        
        if (empty($message)) {
            throw new Exception('Message is required');
        }
        
        // Get FCM tokens for the specified location
        $fcmTokenData = getFCMTokensByLocation($location);
        
        if (empty($fcmTokenData)) {
            throw new Exception("No users found with FCM tokens for location: $location");
        }
        
        // Send notification to all users in the location
        $notificationData = [
            'title' => $title,
            'body' => $message,
            'data' => [
                'notification_type' => 'location_notification',
                'target_location' => $location,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
            ]
        ];
        
        $notificationSent = sendFCMNotification($fcmTokenData, $notificationData, $location);
        
        if ($notificationSent) {
            $usersNotified = count($fcmTokenData);
            
            // Log the notification attempt
            logNotificationAttempt(0, 'location_notification', 'location', $location, $usersNotified, true);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => "Location notification sent successfully to $usersNotified users in $location",
                'location' => $location,
                'users_notified' => $usersNotified,
                'target_location' => $location
            ]);
        } else {
            throw new Exception('Failed to send location notification');
        }
        
    } catch (Exception $e) {
        error_log("Error sending location notification: " . $e->getMessage());
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error sending location notification: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Handle form submission for creating new program
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_event'])) {
    // Debug logging for all POST data
    error_log("=== EVENT CREATION DEBUG ===");
    error_log("POST data received: " . print_r($_POST, true));
    error_log("Request method: " . $_SERVER["REQUEST_METHOD"]);
    error_log("Content-Type: " . ($_SERVER["CONTENT_TYPE"] ?? 'not set'));
    
    $title = $_POST['eventTitle'];
    $type = $_POST['eventType'];
    $description = $_POST['eventDescription'];
    $date_time = $_POST['eventDate'];
    $location = $_POST['eventLocation'];
    $organizer = $_POST['eventOrganizer'];
    
    // Debug logging for location
    error_log("Creating event with location: '$location' (length: " . strlen($location) . ")");
    error_log("Location value type: " . gettype($location));
    error_log("Location is empty: " . (empty($location) ? 'YES' : 'NO'));
    error_log("Location === '': " . ($location === '' ? 'YES' : 'NO'));
    error_log("Location === null: " . ($location === null ? 'YES' : 'NO'));
    
    try {
        // First, insert the event directly into the database
        $stmt = $conn->prepare("
            INSERT INTO programs (title, type, description, date_time, location, organizer, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([$title, $type, $description, $date_time, $location, $organizer]);
        $eventId = $conn->lastInsertId();
        
        error_log("Event created successfully with ID: $eventId");
        
        // Now send FCM notification with location-based targeting
        $notificationSent = false;
        $notificationMessage = '';
        $devicesNotified = 0;
        
        try {
            // Get FCM tokens based on event location
            $fcmTokenData = getFCMTokensByLocation($location);
            $fcmTokens = array_column($fcmTokenData, 'fcm_token');
            
            error_log("FCM tokens found: " . count($fcmTokens) . " for location: '$location'");
            
            if (!empty($fcmTokens)) {
                $devicesNotified = count($fcmTokens);
                
                // Determine target type for logging
                $targetType = 'all';
                $targetValue = 'all';
                if (!empty($location)) {
                    if (strpos($location, 'MUNICIPALITY_') === 0) {
                        $targetType = 'municipality';
                        $targetValue = $location;
                    } else {
                        $targetType = 'barangay';
                        $targetValue = $location;
                    }
                }
                
                // Create notification body with proper location handling
                $locationText = empty($location) ? 'All Locations' : $location;
                $notificationBody = "New event: $title at $locationText on " . date('M j, Y g:i A', strtotime($date_time));
                
                // Send FCM notification using Firebase Admin SDK
                $notificationSent = sendFCMNotification($fcmTokens, [
                    'title' => $title,
                    'body' => $notificationBody,
                    'data' => [
                        'event_id' => $eventId,
                        'event_title' => $title,
                        'event_type' => $type,
                        'event_description' => $description,
                        'event_date' => $date_time,
                        'event_location' => $location,
                        'event_organizer' => $organizer,
                        'notification_type' => 'new_event',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
                    ]
                ], $location);
                
                // Log the notification attempt
                logNotificationAttempt($eventId, 'new_event', $targetType, $targetValue, $devicesNotified, $notificationSent);
                
                if ($notificationSent) {
                    $notificationMessage = "Event created and notification sent to $devicesNotified devices in " . 
                        ($targetType === 'municipality' ? 'municipality' : ($targetType === 'barangay' ? 'barangay' : 'all areas')) . 
                        " ($locationText)!";
                } else {
                    $notificationMessage = "Event created but notification failed to send to $devicesNotified devices.";
                }
            } else {
                $notificationSent = false;
                $locationText = empty($location) ? 'All Locations' : $location;
                $notificationMessage = "Event created but no FCM tokens found for location '$locationText'. No notifications sent.";
                
                // Log the attempt with no tokens found
                logNotificationAttempt($eventId, 'new_event', 'all', 'all', 0, false, 'No FCM tokens found for location');
            }
            
        } catch (Exception $notificationError) {
            error_log("Error sending FCM notification: " . $notificationError->getMessage());
            $notificationSent = false;
            $notificationMessage = "❌ Event created but notification error: " . $notificationError->getMessage();
            
            // Log the error
            logNotificationAttempt($eventId, 'new_event', 'all', 'all', 0, false, $notificationError->getMessage());
        }
        
        // Check if this is an AJAX request
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        
        if ($isAjax) {
            // Return success for AJAX request
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => 'Event created successfully',
                'event_id' => $eventId,
                'notification_sent' => $notificationSent,
                'devices_notified' => $devicesNotified,
                'notification_message' => $notificationMessage
            ]);
            exit;
        } else {
            // Redirect with success message
            $redirectUrl = "event.php?success=1&event_id=" . $eventId;
            if ($notificationSent) {
                $redirectUrl .= "&notification=1&devices=" . $devicesNotified;
            }
            $redirectUrl .= "&message=" . urlencode($notificationMessage);
            header("Location: " . $redirectUrl);
            exit;
        }
        
    } catch(PDOException $e) {
        $errorMessage = "Database error: " . $e->getMessage();
        error_log("Database error creating event: " . $e->getMessage());
        
        if (isset($isAjax) && $isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $errorMessage]);
            exit;
        }
    } catch(Exception $e) {
        $errorMessage = "Error creating event: " . $e->getMessage();
        error_log("General error creating event: " . $e->getMessage());
        
        if (isset($isAjax) && $isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $errorMessage]);
            exit;
        }
    }
}

// Function to send FCM notification with location targeting using the working API
function sendFCMNotification($tokens, $notificationData, $targetLocation = null) {
    try {
        error_log("sendFCMNotification called with " . count($tokens) . " tokens for location: $targetLocation");
        
        if (empty($tokens)) {
            error_log("No FCM tokens provided for notification");
            return false;
        }
        
        $successCount = 0;
        $failureCount = 0;
        
        foreach ($tokens as $tokenData) {
            $fcmToken = $tokenData['fcm_token'];
            $userEmail = $tokenData['user_email'];
            $userBarangay = $tokenData['user_barangay'] ?? 'Unknown';
            
            if (empty($fcmToken)) {
                error_log("Empty FCM token for user: $userEmail");
                continue;
            }
            
            // Prepare notification data for the working API
            $notificationPayload = [
                'title' => $notificationData['title'],
                'body' => $notificationData['body'],
                'target_user' => $userEmail,
                'alert_type' => 'location_notification',
                'user_name' => $userEmail,
                'data' => array_merge($notificationData['data'] ?? [], [
                    'location' => $targetLocation,
                    'user_barangay' => $userBarangay
                ])
            ];
            
            // Send to the working notification API
            $apiUrl = 'https://nutrisaur-production.up.railway.app/api/send_notification.php';
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded',
                'X-Requested-With: XMLHttpRequest'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'notification_data' => json_encode($notificationPayload)
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                error_log("cURL error for user $userEmail: $error");
                $failureCount++;
                continue;
            }
            
            if ($httpCode === 200) {
                $result = json_decode($response, true);
                if ($result && $result['success']) {
                    error_log("Location notification sent successfully to $userEmail ($userBarangay)");
                    $successCount++;
                } else {
                    error_log("Location notification failed for $userEmail: " . ($result['message'] ?? 'Unknown error'));
                    $failureCount++;
                }
            } else {
                error_log("HTTP error $httpCode for user $userEmail: $response");
                $failureCount++;
            }
        }
        
        error_log("Location notification summary: $successCount successful, $failureCount failed for location: $targetLocation");
        return $successCount > 0;
        
    } catch (Exception $e) {
        error_log("Error in sendFCMNotification: " . $e->getMessage());
        return false;
    }
}

// Function to get FCM tokens based on location targeting using user_preferences table
function getFCMTokensByLocation($targetLocation = null) {
    global $conn;
    
    try {
        // Debug logging
        error_log("getFCMTokensByLocation called with targetLocation: '$targetLocation' (type: " . gettype($targetLocation) . ", length: " . strlen($targetLocation ?? '') . ")");
        
        if (empty($targetLocation) || $targetLocation === 'all' || $targetLocation === '') {
            error_log("Processing 'all locations' case - getting all FCM tokens");
            // Get all active FCM tokens with user barangay from user_preferences
            $stmt = $conn->prepare("
                SELECT ft.fcm_token, ft.user_email, up.barangay as user_barangay
                FROM fcm_tokens ft
                INNER JOIN user_preferences up ON ft.user_email = up.user_email
                WHERE ft.is_active = TRUE 
                AND ft.fcm_token IS NOT NULL 
                AND ft.fcm_token != ''
                AND up.barangay IS NOT NULL 
                AND up.barangay != ''
            ");
            $stmt->execute();
        } else {
            // Check if it's a municipality (starts with MUNICIPALITY_)
            if (strpos($targetLocation, 'MUNICIPALITY_') === 0) {
                error_log("Processing municipality case: $targetLocation");
                // Get tokens for all barangays in the municipality
                $stmt = $conn->prepare("
                    SELECT ft.fcm_token, ft.user_email, up.barangay as user_barangay
                    FROM fcm_tokens ft
                    INNER JOIN user_preferences up ON ft.user_email = up.user_email
                    WHERE ft.is_active = TRUE 
                    AND ft.fcm_token IS NOT NULL 
                    AND ft.fcm_token != ''
                    AND up.barangay IS NOT NULL 
                    AND up.barangay != ''
                    AND (up.barangay = ? OR up.barangay LIKE ?)
                ");
                $municipalityName = str_replace('MUNICIPALITY_', '', $targetLocation);
                $stmt->execute([$targetLocation, $municipalityName . '%']);
            } else {
                error_log("Processing barangay case: $targetLocation");
                // Get tokens for specific barangay
                $stmt = $conn->prepare("
                    SELECT ft.fcm_token, ft.user_email, up.barangay as user_barangay
                    FROM fcm_tokens ft
                    INNER JOIN user_preferences up ON ft.user_email = up.user_email
                    WHERE ft.is_active = TRUE 
                    AND ft.fcm_token IS NOT NULL 
                    AND ft.fcm_token != ''
                    AND up.barangay IS NOT NULL 
                    AND up.barangay != ''
                    AND up.barangay = ?
                ");
                $stmt->execute([$targetLocation]);
            }
        }
        
        $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Log the targeting results
        $targetType = empty($targetLocation) ? 'all' : (strpos($targetLocation, 'MUNICIPALITY_') === 0 ? 'municipality' : 'barangay');
        error_log("FCM targeting using user_preferences: $targetType '$targetLocation' - Found " . count($tokens) . " tokens");
        
        // Additional debug info for empty results
        if (count($tokens) === 0) {
            error_log("No FCM tokens found. Checking if there are any users with preferences...");
            
            // Check if there are any users with preferences
            $checkStmt = $conn->prepare("SELECT COUNT(*) as total FROM user_preferences WHERE barangay IS NOT NULL AND barangay != ''");
            $checkStmt->execute();
            $userCount = $checkStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Check if there are any active FCM tokens
            $tokenStmt = $conn->prepare("SELECT COUNT(*) as total FROM fcm_tokens WHERE is_active = TRUE AND fcm_token IS NOT NULL AND fcm_token != ''");
            $tokenStmt->execute();
            $tokenCount = $tokenStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            error_log("Total users with barangay preferences: $userCount, Total active FCM tokens: $tokenCount");
        }
        
        return $tokens;
        
    } catch (Exception $e) {
        error_log("Error getting FCM tokens by location: " . $e->getMessage());
        return [];
    }
}

// Function to log notification attempts
function logNotificationAttempt($eventId, $notificationType, $targetType, $targetValue, $tokensSent, $success, $errorMessage = null) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO notification_logs (event_id, notification_type, target_type, target_value, tokens_sent, success, error_message, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $eventId,
            $notificationType,
            $targetType,
            $targetValue,
            $tokensSent,
            $success ? 1 : 0,
            $errorMessage
        ]);
        
    } catch (Exception $e) {
        error_log("Error logging notification attempt: " . $e->getMessage());
    }
}

// Function to send FCM using Firebase Admin SDK (simplified and fixed)
function sendFCMWithAdminSDK($tokens, $notificationData, $adminSdkPath, $targetLocation = null) {
    try {
        // Read the service account JSON file
        $serviceAccount = json_decode(file_get_contents($adminSdkPath), true);
        if (!$serviceAccount || !isset($serviceAccount['project_id'])) {
            error_log("Invalid Firebase service account JSON file");
            return false;
        }
        
        // Generate access token using service account credentials
        $accessToken = generateAccessToken($serviceAccount);
        if (!$accessToken) {
            error_log("Failed to generate access token");
            return false;
        }
        
        $successCount = 0;
        $failureCount = 0;
        
        foreach ($tokens as $token) {
            // Log the token being processed (first few characters for security)
            $tokenPreview = substr($token, 0, 20) . '...';
            error_log("Processing FCM token: $tokenPreview for location: $targetLocation");
            
            // Prepare the FCM message payload for HTTP v1 API
            $fcmPayload = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $notificationData['title'],
                        'body' => $notificationData['body']
                    ],
                    'data' => $notificationData['data'] ?? [],
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'sound' => 'default',
                            'default_sound' => true,
                            'default_vibrate_timings' => true,
                            'default_light_settings' => true,
                            'icon' => 'ic_launcher',
                            'color' => '#4CAF50',
                            'channel_id' => 'nutrisaur_events'
                        ]
                    ],
                    'apns' => [
                        'payload' => [
                            'aps' => [
                                'sound' => 'default',
                                'badge' => 1
                            ]
                        ]
                    ]
                ]
            ];
            
            // Use Firebase HTTP v1 API
            $projectId = $serviceAccount['project_id'];
            $fcmUrl = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
            
            // Send FCM message using cURL with Admin SDK
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $fcmUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmPayload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                error_log("cURL error for token $tokenPreview: $curlError");
                $failureCount++;
                continue;
            }
            
            if ($httpCode == 200) {
                $responseData = json_decode($response, true);
                if (isset($responseData['name'])) {
                    error_log("FCM success for token $tokenPreview: " . $responseData['name']);
                    $successCount++;
                } else {
                    error_log("FCM response missing 'name' for token $tokenPreview. Response: " . substr($response, 0, 200));
                    $failureCount++;
                }
            } else {
                error_log("FCM HTTP error $httpCode for token $tokenPreview. Response: " . substr($response, 0, 200));
                $failureCount++;
            }
        }
        
        if ($successCount > 0) {
            error_log("FCM notification sent successfully to $successCount out of " . count($tokens) . " devices");
            return true;
        } else {
            $errorMsg = "FCM notification failed to send to any devices. Success: $successCount, Failures: $failureCount";
            error_log($errorMsg);
            throw new Exception($errorMsg);
        }
        
    } catch (Exception $e) {
        error_log("Error in sendFCMWithAdminSDK: " . $e->getMessage());
        return false;
    }
}

// Function to generate access token using service account
function generateAccessToken($serviceAccount) {
    try {
        // Check if we have a cached token that's still valid
        $cacheFile = __DIR__ . '/fcm_token_cache.json';
        if (file_exists($cacheFile)) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if ($cached && isset($cached['expires_at']) && $cached['expires_at'] > time()) {
                return $cached['access_token'];
            }
        }
        
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT'
        ];
        
        $time = time();
        $payload = [
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $time + 3600,
            'iat' => $time
        ];
        
        $headerEncoded = base64url_encode(json_encode($header));
        $payloadEncoded = base64url_encode(json_encode($payload));
        
        $signature = '';
        openssl_sign(
            $headerEncoded . '.' . $payloadEncoded,
            $signature,
            $serviceAccount['private_key'],
            'SHA256'
        );
        
        $signatureEncoded = base64url_encode($signature);
        $jwt = $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
        
        // Exchange JWT for access token
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            error_log("cURL error in generateAccessToken: " . $curlError);
            return false;
        }
        
        if ($httpCode == 200) {
            $tokenData = json_decode($response, true);
            if (isset($tokenData['access_token'])) {
                // Cache the token for reuse
                $cacheData = [
                    'access_token' => $tokenData['access_token'],
                    'expires_at' => time() + 3500, // Cache for 58 minutes (token valid for 1 hour)
                    'created_at' => time()
                ];
                file_put_contents($cacheFile, json_encode($cacheData));
                
                return $tokenData['access_token'];
            }
        }
        
        error_log("Failed to generate access token. HTTP Code: " . $httpCode . " Response: " . $response);
        return false;
        
    } catch (Exception $e) {
        error_log("Error generating access token: " . $e->getMessage());
        return false;
    }
}

// Helper function for base64url encoding
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// Function to get user location statistics for debugging
function getUserLocationStats() {
    global $conn;
    
    try {
        $stats = [];
        
        // Count total users with barangay in user_preferences
        $stmt = $conn->prepare("
            SELECT COUNT(*) as total_users_with_barangay
            FROM user_preferences 
            WHERE barangay IS NOT NULL AND barangay != ''
        ");
        $stmt->execute();
        $stats['total_users_with_barangay'] = $stmt->fetchColumn();
        
        // Count total active FCM tokens
        $stmt = $conn->prepare("
            SELECT COUNT(*) as total_fcm_tokens
            FROM fcm_tokens 
            WHERE is_active = TRUE AND fcm_token IS NOT NULL AND fcm_token != ''
        ");
        $stmt->execute();
        $stats['total_fcm_tokens'] = $stmt->fetchColumn();
        
        // Count FCM tokens with barangay from user_preferences
        $stmt = $conn->prepare("
            SELECT COUNT(*) as fcm_tokens_with_barangay
            FROM fcm_tokens ft
            INNER JOIN user_preferences up ON ft.user_email = up.user_email
            WHERE ft.is_active = TRUE 
            AND ft.fcm_token IS NOT NULL 
            AND ft.fcm_token != ''
            AND up.barangay IS NOT NULL 
            AND up.barangay != ''
        ");
        $stmt->execute();
        $stats['fcm_tokens_with_barangay'] = $stmt->fetchColumn();
        
        // Count FCM tokens without barangay (using old method)
        $stmt = $conn->prepare("
            SELECT COUNT(*) as fcm_tokens_without_barangay
            FROM fcm_tokens 
            WHERE is_active = TRUE 
            AND fcm_token IS NOT NULL 
            AND fcm_token != ''
            AND (user_barangay IS NULL OR user_barangay = '')
        ");
        $stmt->execute();
        $stats['fcm_tokens_without_barangay'] = $stmt->fetchColumn();
        
        return $stats;
        
    } catch (Exception $e) {
        error_log("Error getting user location stats: " . $e->getMessage());
        return [];
    }
}

// Simple test notification function for debugging
function sendTestNotification($message = 'Test notification from NutriSaur!') {
    global $conn;
    
    try {
        // Get all active FCM tokens with barangay from user_preferences
        $stmt = $conn->prepare("
            SELECT ft.fcm_token 
            FROM fcm_tokens ft
            INNER JOIN user_preferences up ON ft.user_email = up.user_email
            WHERE ft.is_active = TRUE 
            AND ft.fcm_token IS NOT NULL 
            AND ft.fcm_token != ''
            AND up.barangay IS NOT NULL 
            AND up.barangay != ''
        ");
        $stmt->execute();
        $tokens = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($tokens)) {
            error_log("No FCM tokens with barangay found for test notification");
            return false;
        }
        
        error_log("Sending test notification to " . count($tokens) . " tokens with barangay data");
        
        $notificationData = [
            'title' => 'Test Notification',
            'body' => $message,
            'data' => [
                'notification_type' => 'test',
                'test_message' => $message,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ];
        
        $result = sendFCMNotification($tokens, $notificationData);
        
        // Log the test attempt
        logNotificationAttempt(0, 'test', 'all', 'all', count($tokens), $result);
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Error sending test notification: " . $e->getMessage());
        return false;
    }
}

// Function to get detailed user list for a specific location (for debugging)
function getUsersForLocation($targetLocation) {
    global $conn;
    
    try {
        if (empty($targetLocation) || $targetLocation === 'all') {
            $stmt = $conn->prepare("
                SELECT up.user_email, up.barangay, ft.fcm_token, ft.is_active
                FROM user_preferences up
                LEFT JOIN fcm_tokens ft ON up.user_email = ft.user_email
                WHERE up.barangay IS NOT NULL AND up.barangay != ''
                ORDER BY up.barangay, up.user_email
            ");
            $stmt->execute();
        } else {
            // Check if it's a municipality
            if (strpos($targetLocation, 'MUNICIPALITY_') === 0) {
                $stmt = $conn->prepare("
                    SELECT up.user_email, up.barangay, ft.fcm_token, ft.is_active
                    FROM user_preferences up
                    LEFT JOIN fcm_tokens ft ON up.user_email = ft.user_email
                    WHERE up.barangay IS NOT NULL AND up.barangay != ''
                    AND (up.barangay = ? OR up.barangay LIKE ?)
                    ORDER BY up.barangay, up.user_email
                ");
                $municipalityName = str_replace('MUNICIPALITY_', '', $targetLocation);
                $stmt->execute([$targetLocation, $municipalityName . '%']);
            } else {
                $stmt = $conn->prepare("
                    SELECT up.user_email, up.barangay, ft.fcm_token, ft.is_active
                    FROM user_preferences up
                    LEFT JOIN fcm_tokens ft ON up.user_email = ft.user_email
                    WHERE up.barangay = ?
                    ORDER BY up.user_email
                ");
                $stmt->execute([$targetLocation]);
            }
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error getting users for location: " . $e->getMessage());
        return [];
    }
}

// Handle CSV file upload and import
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['import_csv'])) {
    
    
    if (isset($_FILES['csvFile']) && $_FILES['csvFile']['error'] == 0) {
        $file = $_FILES['csvFile'];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Check if file is CSV
        if ($fileExt != 'csv') {
            $errorMessage = "Please upload a CSV file only.";
        } elseif ($fileSize > 5000000) { // 5MB limit
            $errorMessage = "File size too large. Please upload a file smaller than 5MB.";
        } else {
            try {
                // Open the CSV file
                if (($handle = fopen($fileTmpName, "r")) !== FALSE) {
                    $row = 0;
                    $importedCount = 0;
                    $errors = [];
                    $importedEvents = []; // Track imported events for notifications
                    
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        $row++;
                        
                        // Skip header row
                        if ($row == 1) continue;
                        
                        // Check if we have enough columns
                        if (count($data) < 8) {
                            $errors[] = "Row $row: Insufficient data columns";
                            continue;
                        }
                        
                        // Extract data from CSV
                        $title = trim($data[0]);
                        $type = trim($data[1]);
                        $date_time = trim($data[2]);
                        $location = trim($data[3]);
                        $organizer = trim($data[4]);
                        $description = trim($data[5]);
                        $notificationType = trim($data[6]);
                        $recipientGroup = trim($data[7]);
                        
                        // Clean up the date string - remove any extra spaces or quotes
                        $date_time = trim($date_time, " \t\n\r\0\x0B\"'");
                        
                        // Validate required fields
                        if (empty($title) || empty($date_time) || empty($location)) {
                            $errors[] = "Row $row: Missing required fields (title, date, or location)";
                            continue;
                        }
                        
                        // Validate date format - try multiple formats
                        $dateObj = null;
                        $dateFormats = [
                            'Y-m-d H:i',      // 2024-01-15 14:00
                            'Y-m-d H:i:s',    // 2024-01-15 14:00:00
                            'Y/m/d H:i',      // 2024/01/15 14:00
                            'Y/m/d H:i:s',    // 2024/01/15 14:00:00
                            'd-m-Y H:i',      // 15-01-2024 14:00
                            'd/m/Y H:i',      // 15/01/2024 14:00
                            'm-d-Y H:i',      // 01-15-2024 14:00
                            'm/d/Y H:i',      // 01/15/2024 14:00
                        ];
                        
                        foreach ($dateFormats as $format) {
                            $dateObj = DateTime::createFromFormat($format, $date_time);
                            if ($dateObj) {
                                break;
                            }
                        }
                        
                        if (!$dateObj) {
                            $errors[] = "Row $row: Invalid date format. Use YYYY-MM-DD HH:MM (e.g., 2024-01-15 14:00)";
                            continue;
                        }
                        
                        // Check for duplicate events before inserting
                        try {
                            $checkStmt = $conn->prepare("
                                SELECT program_id FROM programs 
                                WHERE title = :title 
                                AND date_time = :date_time 
                                AND location = :location
                            ");
                            $checkStmt->bindParam(':title', $title);
                            $checkStmt->bindParam(':date_time', $dateObj->format('Y-m-d H:i:s'));
                            $checkStmt->bindParam(':location', $location);
                            $checkStmt->execute();
                            
                            if ($checkStmt->fetch()) {
                                continue; // Skip duplicate
                            }
                            
                            // Insert into database (only if not duplicate)
                            $stmt = $conn->prepare("
                                INSERT INTO programs (title, type, description, date_time, location, organizer, created_at) 
                                VALUES (:title, :type, :description, :date_time, :location, :organizer, :created_at)
                            ");
                            
                            // Use current timestamp for real-time detection
                            $created_at = time() * 1000; // Current time in milliseconds
                            
                            $stmt->bindParam(':title', $title);
                            $stmt->bindParam(':type', $type);
                            $stmt->bindParam(':description', $description);
                            $stmt->bindParam(':date_time', $dateObj->format('Y-m-d H:i:s'));
                            $stmt->bindParam(':location', $location);
                            $stmt->bindParam(':organizer', $organizer);
                            $stmt->bindParam(':created_at', $created_at);
                            
                            $stmt->execute();
                            $eventId = $conn->lastInsertId();
                            $importedCount++;
                            
                            // Track imported event for notifications
                            $importedEvents[] = [
                                'id' => $eventId,
                                'title' => $title,
                                'type' => $type,
                                'date_time' => $dateObj->format('Y-m-d H:i:s'),
                                'location' => $location,
                                'organizer' => $organizer,
                                'description' => $description,
                                'notification_type' => $notificationType,
                                'recipient_group' => $recipientGroup
                            ];
                            
                        } catch(PDOException $e) {
                            $errors[] = "Row $row: Database error - " . $e->getMessage();
                        }
                    }
                    fclose($handle);
                    
                    // Send real-time notifications for imported events with location-based targeting
                    if (!empty($importedEvents)) {
                        try {
                            foreach ($importedEvents as $event) {
                                // Get FCM tokens based on event location
                                $fcmTokenData = getFCMTokensByLocation($event['location']);
                                $fcmTokens = array_column($fcmTokenData, 'fcm_token');
                            
                            if (!empty($fcmTokens)) {
                                    // Determine target type for logging
                                    $targetType = 'all';
                                    $targetValue = 'all';
                                    if (!empty($event['location'])) {
                                        if (strpos($event['location'], 'MUNICIPALITY_') === 0) {
                                            $targetType = 'municipality';
                                            $targetValue = $event['location'];
                                        } else {
                                            $targetType = 'barangay';
                                            $targetValue = $event['location'];
                                        }
                                    }
                                    
                                    $notificationSent = sendFCMNotification($fcmTokens, [
                                        'title' => $event['title'],
                                        'body' => "New event imported: $event[title] at $event[location] on " . date('M j, Y g:i A', strtotime($event['date_time'])),
                                        'data' => [
                                            'event_id' => $event['id'],
                                            'event_title' => $event['title'],
                                            'event_type' => $event['type'],
                                            'event_description' => $event['description'],
                                            'event_date' => $event['date_time'],
                                            'event_location' => $event['location'],
                                            'event_organizer' => $event['organizer'],
                                            'notification_type' => 'imported_event',
                                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
                                        ]
                                    ], $event['location']);
                                    
                                    // Log the notification attempt
                                    logNotificationAttempt($event['id'], 'imported_event', $targetType, $targetValue, count($fcmTokens), $notificationSent);
                                } else {
                                    // Log the attempt with no tokens found
                                    logNotificationAttempt($event['id'], 'imported_event', 'barangay', $event['location'], 0, false, 'No FCM tokens found for location');
                                }
                            }
                            
                        } catch(Exception $e) {
                            error_log("Error sending notifications for imported events: " . $e->getMessage());
                        }
                    }
                    
                                            // Show success/error message
                        if ($importedCount > 0) {
                            $successMessage = "Successfully imported $importedCount events!";
                            if (!empty($errors)) {
                                $successMessage .= " However, " . count($errors) . " rows had errors.";
                            }
                            
                            // Force refresh the programs list
                            $stmt = $conn->prepare("SELECT * FROM programs ORDER BY date_time DESC");
                            $stmt->execute();
                            $programs = $stmt->fetchAll();
                            
                            header("Location: event.php?imported=$importedCount&errors=" . count($errors));
                            exit;
                        } else {
                            $errorMessage = "No events were imported. " . implode("; ", $errors);
                        }
                } else {
                    $errorMessage = "Error reading CSV file.";
                }
            } catch(Exception $e) {
                $errorMessage = "Error processing CSV file: " . $e->getMessage();
            }
        }
            } else {
            if (isset($_FILES['csvFile'])) {
                $errorMessage = "File upload error: " . $_FILES['csvFile']['error'];
            } else {
                $errorMessage = "Please select a CSV file to upload.";
            }
        }
}

// Handle program deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $programId = $_GET['delete'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM programs WHERE program_id = :id");
        $stmt->bindParam(':id', $programId);
        $stmt->execute();
        
        // Redirect to refresh page with success message
        header("Location: event.php?deleted=1&deleted_id=" . $programId);
        exit;
    } catch(PDOException $e) {
        $errorMessage = "Error deleting program: " . $e->getMessage();
    }
}

// Handle delete all programs
if (isset($_GET['delete_all']) && $_GET['delete_all'] === '1') {
    try {
        $stmt = $conn->prepare("DELETE FROM programs");
        $stmt->execute();
        
        // Redirect to refresh page
        header("Location: event.php?deleted_all=1");
        exit;
    } catch(PDOException $e) {
        $errorMessage = "Error deleting all programs: " . $e->getMessage();
    }
}

// Handle program editing
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_event'])) {
    $programId = $_POST['program_id'];
    $title = $_POST['eventTitle'];
    $type = $_POST['eventType']; // Changed from notificationType to eventType
    $description = $_POST['eventDescription'];
    $date_time = $_POST['eventDate'];
    $location = $_POST['eventLocation'];
    $organizer = $_POST['eventOrganizer'];
    
    try {
        // First update the event in the database
        $stmt = $conn->prepare("UPDATE programs SET title = :title, type = :type, description = :description, 
                               date_time = :date_time, location = :location, organizer = :organizer 
                               WHERE program_id = :id");
        
        $stmt->bindParam(':id', $programId);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':date_time', $date_time);
        $stmt->bindParam(':location', $location);
        $stmt->bindParam(':organizer', $organizer);
        
        $stmt->execute();
        
        // Send notification about the updated event with location-based targeting
        try {
            // Get FCM tokens based on event location
            $fcmTokenData = getFCMTokensByLocation($location);
            $fcmTokens = array_column($fcmTokenData, 'fcm_token');
            
            if (!empty($fcmTokens)) {
                // Determine target type for logging
                $targetType = 'all';
                $targetValue = 'all';
                if (!empty($location)) {
                    if (strpos($location, 'MUNICIPALITY_') === 0) {
                        $targetType = 'municipality';
                        $targetValue = $location;
                    } else {
                        $targetType = 'barangay';
                        $targetValue = $location;
                    }
                }
                
                $notificationSent = sendFCMNotification($fcmTokens, [
                    'title' => "Event Updated: $title",
                    'body' => "Event details have been updated. Check the new information.",
                    'data' => [
                        'event_title' => $title,
                        'event_type' => $type,
                        'event_description' => $description,
                        'event_date' => $date_time,
                        'event_location' => $location,
                        'event_organizer' => $organizer,
                        'notification_type' => 'event_updated',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
                    ]
                ], $location);
                
                // Log the notification attempt
                logNotificationAttempt($programId, 'event_updated', $targetType, $targetValue, count($fcmTokens), $notificationSent);
            } else {
                // Log the attempt with no tokens found
                logNotificationAttempt($programId, 'event_updated', 'barangay', $location, 0, false, 'No FCM tokens found for location');
            }
            
        } catch(Exception $e) {
            error_log("Error sending event update notification: " . $e->getMessage());
            logNotificationAttempt($programId, 'event_updated', 'barangay', $location, 0, false, $e->getMessage());
        }
        
        // Redirect to refresh page
        header("Location: event.php?updated=1");
        exit;
    } catch(PDOException $e) {
        $errorMessage = "Error updating program: " . $e->getMessage();
    }
}

// Get event data for editing - Now fetches from unified_api.php
$editEvent = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editId = $_GET['edit'];
    try {
        // First try to get from local database
        $stmt = $conn->prepare("SELECT * FROM programs WHERE program_id = :id");
        $stmt->bindParam(':id', $editId);
        $stmt->execute();
        $editEvent = $stmt->fetch();
        
        // If not found locally, try to get from unified_api.php
        if (!$editEvent) {
            $apiUrl = 'https://nutrisaur-production.up.railway.app/unified_api.php';
            $postData = [
                'action' => 'get_event_data',
                'event_id' => $editId
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'X-Requested-With: XMLHttpRequest'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && $response) {
                $apiData = json_decode($response, true);
                if ($apiData && $apiData['success'] && $apiData['data']) {
                    $editEvent = $apiData['data'];
                }
            }
        }
        
    } catch(PDOException $e) {
        $errorMessage = "Error fetching event: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriSaur - Chatbot & AI Training Logs</title>
    
</head>
<body class="dark-theme">
    <div class="dashboard">
        <header>
            <div class="logo">
                <h1>Nutrition Event Notifications</h1>
            </div>
            <div class="user-info">
                <button id="new-theme-toggle" class="new-theme-toggle-btn" title="Toggle theme" onclick="newToggleTheme()">
                    <span class="new-theme-icon">🌙</span>
                </button>
            </div>
        </header>

        <div class="event-container">
            <div class="event-header">
                <!-- Header text removed -->
            </div>
            

            
            <!-- CSV Upload Section -->
            <div class="csv-upload-section">
                <div class="csv-upload-header">
                    <h3>📁 Bulk Import Events</h3>
                    <p>Upload a CSV file to import multiple events at once</p>
                </div>
                <div class="csv-upload-container">
                    <form id="csvUploadForm" enctype="multipart/form-data" method="POST" action="event.php">
                        <div class="csv-upload-area" id="uploadArea">
                            <div class="upload-icon">📄</div>
                            <div class="upload-text">
                                <h4>Upload CSV File</h4>
                                <p>Click to select or drag and drop your CSV file here</p>
                                <p class="csv-format">Format: Event Title, Type, Date & Time, Location, Organizer, Description, Notification Type, Recipient Group</p>
                            </div>
                            <input type="file" id="csvFile" name="csvFile" accept=".csv" style="position: absolute; left: -9999px; opacity: 0; pointer-events: none;" onchange="handleFileSelect(this)">
                        </div>
                        <div class="csv-preview" id="csvPreview" style="display: none;">
                            <h4>📋 Preview (First 5 rows)</h4>
                            <div id="previewContent"></div>
                        </div>
                        <div class="csv-actions">
                            <button type="button" class="btn btn-add" id="importBtn" disabled onclick="uploadCSVWithAjax()">
                                📥 Import Events
                            </button>
                            <button type="submit" name="import_csv" class="btn btn-add" id="importBtnFallback" style="display: none;">
                                📥 Import Events (Fallback)
                            </button>
                            <button type="button" class="btn btn-secondary" id="cancelBtn" style="display: none;" onclick="cancelUpload()">
                                ❌ Cancel Upload
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="downloadTemplate()">
                                📋 Download Template
                            </button>
                        </div>
                        <div id="importStatus" style="display: none; text-align: center; margin-top: 15px;">
                            <div style="color: var(--color-highlight); font-weight: 500;">🔄 Processing import...</div>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if(isset($errorMessage)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    Event created successfully!
                    <?php if(isset($_GET['message'])): ?>
                        <br><?php echo htmlspecialchars(urldecode($_GET['message'])); ?>
                    <?php endif; ?>
                    <?php if(isset($_GET['devices']) && $_GET['devices'] > 0): ?>
                        <br>Sent to <?php echo htmlspecialchars($_GET['devices']); ?> devices.
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['imported'])): ?>
                <div class="alert alert-success">
                    Successfully imported <?php echo htmlspecialchars($_GET['imported']); ?> events!
                    <?php if(isset($_GET['errors']) && $_GET['errors'] > 0): ?>
                        <br><?php echo htmlspecialchars($_GET['errors']); ?> rows had errors and were skipped.
                    <?php endif; ?>
                    <?php if(isset($_GET['duplicates']) && $_GET['duplicates'] > 0): ?>
                        <br><span style="color: var(--color-warning);">⚠️ <?php echo htmlspecialchars($_GET['duplicates']); ?> duplicate events were skipped.</span>
                        <br><button type="button" class="btn btn-secondary" style="margin-top: 10px; font-size: 12px;" onclick="showDuplicateWarningModalFromSession()">View Duplicate Details</button>
                    <?php endif; ?>
                    <br><small>You can now view the imported events in the table below.</small>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['deleted'])): ?>
                <div class="alert alert-success">
                    Event deleted successfully!
                    <?php if(isset($_GET['deleted_id'])): ?>
                        <br><small>Event ID: <?php echo htmlspecialchars($_GET['deleted_id']); ?> has been removed.</small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['deleted_all'])): ?>
                <div class="alert alert-success">
                    All events have been deleted successfully!
                </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['updated'])): ?>
                <div class="alert alert-success">
                    Event updated successfully!
                    <?php if(isset($_GET['updated_id'])): ?>
                        <br><small>Event ID: <?php echo htmlspecialchars($_GET['updated_id']); ?> has been modified.</small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['test'])): ?>
                <?php if($_GET['test'] == '1'): ?>
                    <div class="alert alert-success">
                        Test notification sent successfully! Check your phone for the push notification.
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        Test notification failed to send.
                        <?php if(isset($_GET['error'])): ?>
                            <br>Error: <?php echo htmlspecialchars(urldecode($_GET['error'])); ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            

            
            <form class="event-form" name="createEventForm" method="POST" action="event.php">
                <div class="form-group">
                    <label for="eventTitle">Event Title</label>
                    <input type="text" id="eventTitle" name="eventTitle" placeholder="e.g., Nutrition Seminar in Barangay Hall" value="<?php echo htmlspecialchars($recommended_program); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="eventType">Event Type</label>
                    <select id="eventType" name="eventType" required>
                        <option value="Workshop">Workshop</option>
                        <option value="Seminar">Seminar</option>
                        <option value="Webinar">Webinar</option>
                        <option value="Demo">Demo</option>
                        <option value="Training">Training</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="eventDate">Date & Time</label>
                    <input type="datetime-local" id="eventDate" name="eventDate" required>
                </div>
                
                <div class="form-group">
                    <label for="eventLocation">Location</label>
                    <div class="custom-select-container">
                        <div class="select-header" onclick="toggleEventLocationDropdown()">
                            <span id="selected-event-location">All Locations</span>
                            <span class="dropdown-arrow">▼</span>
                        </div>
                        <div class="dropdown-content" id="event-location-dropdown">
                            <div class="search-container">
                                <input type="text" id="event-location-search" placeholder="Search barangay or municipality..." onkeyup="filterEventLocationOptions()">
                            </div>
                            <div class="options-container">
                                <!-- Municipality Options -->
                                <div class="option-group">
                                    <div class="option-header">Municipalities</div>
                                    <div class="option-item" data-value="" onclick="selectEventLocation('', 'All Locations')">All Locations</div>
                                    <div class="option-item" data-value="MUNICIPALITY_ABUCAY" onclick="selectEventLocation('MUNICIPALITY_ABUCAY', 'ABUCAY (All Barangays)')">ABUCAY (All Barangays)</div>
                                    <div class="option-item" data-value="MUNICIPALITY_BAGAC" onclick="selectEventLocation('MUNICIPALITY_BAGAC', 'BAGAC (All Barangays)')">BAGAC (All Barangays)</div>
                                    <div class="option-item" data-value="MUNICIPALITY_BALANGA" onclick="selectEventLocation('MUNICIPALITY_BALANGA', 'CITY OF BALANGA (All Barangays)')">CITY OF BALANGA (All Barangays)</div>
                                    <div class="option-item" data-value="MUNICIPALITY_DINALUPIHAN" onclick="selectEventLocation('MUNICIPALITY_DINALUPIHAN', 'DINALUPIHAN (All Barangays)')">DINALUPIHAN (All Barangays)</div>
                                    <div class="option-item" data-value="MUNICIPALITY_HERMOSA" onclick="selectEventLocation('MUNICIPALITY_HERMOSA', 'HERMOSA (All Barangays)')">HERMOSA (All Barangays)</div>
                                    <div class="option-item" data-value="MUNICIPALITY_LIMAY" onclick="selectEventLocation('MUNICIPALITY_LIMAY', 'LIMAY (All Barangays)')">LIMAY (All Barangays)</div>
                                    <div class="option-item" data-value="MUNICIPALITY_MARIVELES" onclick="selectEventLocation('MUNICIPALITY_MARIVELES', 'MARIVELES (All Barangays)')">MARIVELES (All Barangays)</div>
                                    <div class="option-item" data-value="MUNICIPALITY_MORONG" onclick="selectEventLocation('MUNICIPALITY_MORONG', 'MORONG (All Barangays)')">MORONG (All Barangays)</div>
                                    <div class="option-item" data-value="MUNICIPALITY_ORANI" onclick="selectEventLocation('MUNICIPALITY_ORANI', 'ORANI (All Barangays)')">ORANI (All Barangays)</div>
                                    <div class="option-item" data-value="MUNICIPALITY_ORION" onclick="selectEventLocation('MUNICIPALITY_ORION', 'ORION (All Barangays)')">ORION (All Barangays)</div>
                                    <div class="option-item" data-value="MUNICIPALITY_PILAR" onclick="selectEventLocation('MUNICIPALITY_PILAR', 'PILAR (All Barangays)')">PILAR (All Barangays)</div>
                                    <div class="option-item" data-value="MUNICIPALITY_SAMAL" onclick="selectEventLocation('MUNICIPALITY_SAMAL', 'SAMAL (All Barangays)')">SAMAL (All Barangays)</div>
                                </div>
                                
                                <!-- Individual Barangays by Municipality -->
                                <div class="option-group">
                                    <div class="option-header">ABUCAY</div>
                                    <div class="option-item" data-value="Bangkal">Bangkal</div>
                                    <div class="option-item" data-value="Calaylayan (Pob.)">Calaylayan (Pob.)</div>
                                    <div class="option-item" data-value="Capitangan">Capitangan</div>
                                    <div class="option-item" data-value="Gabon">Gabon</div>
                                    <div class="option-item" data-value="Laon (Pob.)">Laon (Pob.)</div>
                                    <div class="option-item" data-value="Mabatang">Mabatang</div>
                                    <div class="option-item" data-value="Omboy">Omboy</div>
                                    <div class="option-item" data-value="Salian">Salian</div>
                                    <div class="option-item" data-value="Wawa (Pob.)">Wawa (Pob.)</div>
                                </div>
                                <div class="option-group">
                                    <div class="option-header">BAGAC</div>
                                    <div class="option-item" data-value="Bagumbayan (Pob.)">Bagumbayan (Pob.)</div>
                                    <div class="option-item" data-value="Banawang">Banawang</div>
                                    <div class="option-item" data-value="Binuangan">Binuangan</div>
                                    <div class="option-item" data-value="Binukawan">Binukawan</div>
                                    <div class="option-item" data-value="Ibaba">Ibaba</div>
                                    <div class="option-item" data-value="Ibis">Ibis</div>
                                    <div class="option-item" data-value="Pag-asa (Wawa-Sibacan)">Pag-asa (Wawa-Sibacan)</div>
                                    <div class="option-item" data-value="Parang">Parang</div>
                                    <div class="option-item" data-value="Paysawan">Paysawan</div>
                                    <div class="option-item" data-value="Quinawan">Quinawan</div>
                                    <div class="option-item" data-value="San Antonio">San Antonio</div>
                                    <div class="option-item" data-value="Saysain">Saysain</div>
                                    <div class="option-item" data-value="Tabing-Ilog (Pob.)">Tabing-Ilog (Pob.)</div>
                                    <div class="option-item" data-value="Atilano L. Ricardo">Atilano L. Ricardo</div>
                                </div>
                                <div class="option-group">
                                    <div class="option-header">CITY OF BALANGA</div>
                                    <div class="option-item" data-value="Bagumbayan">Bagumbayan</div>
                                    <div class="option-item" data-value="Cabog-Cabog">Cabog-Cabog</div>
                                    <div class="option-item" data-value="Munting Batangas (Cadre)">Munting Batangas (Cadre)</div>
                                    <div class="option-item" data-value="Cataning">Cataning</div>
                                    <div class="option-item" data-value="Central">Central</div>
                                    <div class="option-item" data-value="Cupang Proper">Cupang Proper</div>
                                    <div class="option-item" data-value="Cupang West">Cupang West</div>
                                    <div class="option-item" data-value="Dangcol (Bernabe)">Dangcol (Bernabe)</div>
                                    <div class="option-item" data-value="Ibayo">Ibayo</div>
                                    <div class="option-item" data-value="Malabia">Malabia</div>
                                    <div class="option-item" data-value="Poblacion">Poblacion</div>
                                    <div class="option-item" data-value="Pto. Rivas Ibaba">Pto. Rivas Ibaba</div>
                                    <div class="option-item" data-value="Pto. Rivas Itaas">Pto. Rivas Itaas</div>
                                    <div class="option-item" data-value="San Jose">San Jose</div>
                                    <div class="option-item" data-value="Sibacan">Sibacan</div>
                                    <div class="option-item" data-value="Camacho">Camacho</div>
                                    <div class="option-item" data-value="Talisay">Talisay</div>
                                    <div class="option-item" data-value="Tanato">Tanato</div>
                                    <div class="option-item" data-value="Tenejero">Tenejero</div>
                                    <div class="option-item" data-value="Tortugas">Tortugas</div>
                                    <div class="option-item" data-value="Tuyo">Tuyo</div>
                                    <div class="option-item" data-value="Bagong Silang">Bagong Silang</div>
                                    <div class="option-item" data-value="Cupang North">Cupang North</div>
                                    <div class="option-item" data-value="Doña Francisca">Doña Francisca</div>
                                    <div class="option-item" data-value="Lote">Lote</div>
                                </div>
                                <div class="option-group">
                                    <div class="option-header">DINALUPIHAN</div>
                                    <div class="option-item" data-value="Bangal">Bangal</div>
                                    <div class="option-item" data-value="Bonifacio (Pob.)">Bonifacio (Pob.)</div>
                                    <div class="option-item" data-value="Burgos (Pob.)">Burgos (Pob.)</div>
                                    <div class="option-item" data-value="Colo">Colo</div>
                                    <div class="option-item" data-value="Daang Bago">Daang Bago</div>
                                    <div class="option-item" data-value="Dalao">Dalao</div>
                                    <div class="option-item" data-value="Del Pilar (Pob.)">Del Pilar (Pob.)</div>
                                    <div class="option-item" data-value="Gen. Luna (Pob.)">Gen. Luna (Pob.)</div>
                                    <div class="option-item" data-value="Gomez (Pob.)">Gomez (Pob.)</div>
                                    <div class="option-item" data-value="Happy Valley">Happy Valley</div>
                                    <div class="option-item" data-value="Kataasan">Kataasan</div>
                                    <div class="option-item" data-value="Layac">Layac</div>
                                    <div class="option-item" data-value="Luacan">Luacan</div>
                                    <div class="option-item" data-value="Mabini Proper (Pob.)">Mabini Proper (Pob.)</div>
                                    <div class="option-item" data-value="Mabini Ext. (Pob.)">Mabini Ext. (Pob.)</div>
                                    <div class="option-item" data-value="Magsaysay">Magsaysay</div>
                                    <div class="option-item" data-value="Naparing">Naparing</div>
                                    <div class="option-item" data-value="New San Jose">New San Jose</div>
                                    <div class="option-item" data-value="Old San Jose">Old San Jose</div>
                                    <div class="option-item" data-value="Padre Dandan (Pob.)">Padre Dandan (Pob.)</div>
                                    <div class="option-item" data-value="Pag-asa">Pag-asa</div>
                                    <div class="option-item" data-value="Pagalanggang">Pagalanggang</div>
                                    <div class="option-item" data-value="Pinulot">Pinulot</div>
                                    <div class="option-item" data-value="Pita">Pita</div>
                                    <div class="option-item" data-value="Rizal (Pob.)">Rizal (Pob.)</div>
                                    <div class="option-item" data-value="Roosevelt">Roosevelt</div>
                                    <div class="option-item" data-value="Roxas (Pob.)">Roxas (Pob.)</div>
                                    <div class="option-item" data-value="Saguing">Saguing</div>
                                    <div class="option-item" data-value="San Benito">San Benito</div>
                                    <div class="option-item" data-value="San Isidro (Pob.)">San Isidro (Pob.)</div>
                                    <div class="option-item" data-value="San Pablo (Bulate)">San Pablo (Bulate)</div>
                                    <div class="option-item" data-value="San Ramon">San Ramon</div>
                                    <div class="option-item" data-value="San Simon">San Simon</div>
                                    <div class="option-item" data-value="Santo Niño">Santo Niño</div>
                                    <div class="option-item" data-value="Sapang Balas">Sapang Balas</div>
                                    <div class="option-item" data-value="Santa Isabel (Tabacan)">Santa Isabel (Tabacan)</div>
                                    <div class="option-item" data-value="Torres Bugauen (Pob.)">Torres Bugauen (Pob.)</div>
                                    <div class="option-item" data-value="Tucop">Tucop</div>
                                    <div class="option-item" data-value="Zamora (Pob.)">Zamora (Pob.)</div>
                                    <div class="option-item" data-value="Aquino">Aquino</div>
                                    <div class="option-item" data-value="Bayan-bayanan">Bayan-bayanan</div>
                                    <div class="option-item" data-value="Maligaya">Maligaya</div>
                                    <div class="option-item" data-value="Payangan">Payangan</div>
                                    <div class="option-item" data-value="Pentor">Pentor</div>
                                    <div class="option-item" data-value="Tubo-tubo">Tubo-tubo</div>
                                    <div class="option-item" data-value="Jose C. Payumo, Jr.">Jose C. Payumo, Jr.</div>
                                </div>
                                <div class="option-group">
                                    <div class="option-header">HERMOSA</div>
                                    <div class="option-item" data-value="A. Rivera (Pob.)" onclick="selectEventLocation('A. Rivera (Pob.)', 'A. Rivera (Pob.)')">A. Rivera (Pob.)</div>
                                    <div class="option-item" data-value="Almacen" onclick="selectEventLocation('Almacen', 'Almacen')">Almacen</div>
                                    <div class="option-item" data-value="Bacong" onclick="selectEventLocation('Bacong', 'Bacong')">Bacong</div>
                                    <div class="option-item" data-value="Balsic" onclick="selectEventLocation('Balsic', 'Balsic')">Balsic</div>
                                    <div class="option-item" data-value="Bamban" onclick="selectEventLocation('Bamban', 'Bamban')">Bamban</div>
                                    <div class="option-item" data-value="Burgos-Soliman (Pob.)" onclick="selectEventLocation('Burgos-Soliman (Pob.)', 'Burgos-Soliman (Pob.)')">Burgos-Soliman (Pob.)</div>
                                    <div class="option-item" data-value="Cataning (Pob.)" onclick="selectEventLocation('Cataning (Pob.)', 'Cataning (Pob.)')">Cataning (Pob.)</div>
                                    <div class="option-item" data-value="Culis" onclick="selectEventLocation('Culis', 'Culis')">Culis</div>
                                    <div class="option-item" data-value="Daungan (Pob.)" onclick="selectEventLocation('Daungan (Pob.)', 'Daungan (Pob.)')">Daungan (Pob.)</div>
                                    <div class="option-item" data-value="Mabiga" onclick="selectEventLocation('Mabiga', 'Mabiga')">Mabiga</div>
                                    <div class="option-item" data-value="Mabuco" onclick="selectEventLocation('Mabuco', 'Mabuco')">Mabuco</div>
                                    <div class="option-item" data-value="Maite" onclick="selectEventLocation('Maite', 'Maite')">Maite</div>
                                    <div class="option-item" data-value="Mambog - Mandama" onclick="selectEventLocation('Mambog - Mandama', 'Mambog - Mandama')">Mambog - Mandama</div>
                                    <div class="option-item" data-value="Palihan" onclick="selectEventLocation('Palihan', 'Palihan')">Palihan</div>
                                    <div class="option-item" data-value="Pandatung" onclick="selectEventLocation('Pandatung', 'Pandatung')">Pandatung</div>
                                    <div class="option-item" data-value="Pulo" onclick="selectEventLocation('Pulo', 'Pulo')">Pulo</div>
                                    <div class="option-item" data-value="Saba" onclick="selectEventLocation('Saba', 'Saba')">Saba</div>
                                    <div class="option-item" data-value="San Pedro" onclick="selectEventLocation('San Pedro', 'San Pedro')">San Pedro</div>
                                    <div class="option-item" data-value="Sumalo" onclick="selectEventLocation('Sumalo', 'Sumalo')">Sumalo</div>
                                    <div class="option-item" data-value="Tipo" onclick="selectEventLocation('Tipo', 'Tipo')">Tipo</div>
                                </div>
                                <div class="option-group">
                                    <div class="option-header">LIMAY</div>
                                    <div class="option-item" data-value="Alas-asin" onclick="selectEventLocation('Alas-asin', 'Alas-asin')">Alas-asin</div>
                                    <div class="option-item" data-value="Anonang" onclick="selectEventLocation('Anonang', 'Anonang')">Anonang</div>
                                    <div class="option-item" data-value="Bataan" onclick="selectEventLocation('Bataan', 'Bataan')">Bataan</div>
                                    <div class="option-item" data-value="Bayan-bayanan" onclick="selectEventLocation('Bayan-bayanan', 'Bayan-bayanan')">Bayan-bayanan</div>
                                    <div class="option-item" data-value="Binuangan" onclick="selectEventLocation('Binuangan', 'Binuangan')">Binuangan</div>
                                    <div class="option-item" data-value="Cacabasan" onclick="selectEventLocation('Cacabasan', 'Cacabasan')">Cacabasan</div>
                                    <div class="option-item" data-value="Duale" onclick="selectEventLocation('Duale', 'Duale')">Duale</div>
                                    <div class="option-item" data-value="Kitang 2" onclick="selectEventLocation('Kitang 2', 'Kitang 2')">Kitang 2</div>
                                    <div class="option-item" data-value="Kitang 2 & Luz" onclick="selectEventLocation('Kitang 2 & Luz', 'Kitang 2 & Luz')">Kitang 2 & Luz</div>
                                    <div class="option-item" data-value="Lamao" onclick="selectEventLocation('Lamao', 'Lamao')">Lamao</div>
                                    <div class="option-item" data-value="Luz" onclick="selectEventLocation('Luz', 'Luz')">Luz</div>
                                    <div class="option-item" data-value="Mabayo" onclick="selectEventLocation('Mabayo', 'Mabayo')">Mabayo</div>
                                    <div class="option-item" data-value="Malaya" onclick="selectEventLocation('Malaya', 'Malaya')">Malaya</div>
                                    <div class="option-item" data-value="Mountain View" onclick="selectEventLocation('Mountain View', 'Mountain View')">Mountain View</div>
                                    <div class="option-item" data-value="Poblacion" onclick="selectEventLocation('Poblacion', 'Poblacion')">Poblacion</div>
                                    <div class="option-item" data-value="Reformista" onclick="selectEventLocation('Reformista', 'Reformista')">Reformista</div>
                                    <div class="option-item" data-value="San Isidro" onclick="selectEventLocation('San Isidro', 'San Isidro')">San Isidro</div>
                                    <div class="option-item" data-value="Santiago" onclick="selectEventLocation('Santiago', 'Santiago')">Santiago</div>
                                    <div class="option-item" data-value="Tuyan" onclick="selectEventLocation('Tuyan', 'Tuyan')">Tuyan</div>
                                    <div class="option-item" data-value="Villa Angeles" onclick="selectEventLocation('Villa Angeles', 'Villa Angeles')">Villa Angeles</div>
                                </div>
                                <div class="option-group">
                                    <div class="option-header">MARIVELES</div>
                                    <div class="option-item" data-value="Alion">Alion</div>
                                    <div class="option-item" data-value="Balon-Anito">Balon-Anito</div>
                                    <div class="option-item" data-value="Baseco">Baseco</div>
                                    <div class="option-item" data-value="Batan">Batan</div>
                                    <div class="option-item" data-value="Biaan">Biaan</div>
                                    <div class="option-item" data-value="Cabcaben">Cabcaben</div>
                                    <div class="option-item" data-value="Camaya">Camaya</div>
                                    <div class="option-item" data-value="Iba">Iba</div>
                                    <div class="option-item" data-value="Lamao">Lamao</div>
                                    <div class="option-item" data-value="Lucanin">Lucanin</div>
                                    <div class="option-item" data-value="Mabayo">Mabayo</div>
                                    <div class="option-item" data-value="Malusak">Malusak</div>
                                    <div class="option-item" data-value="Poblacion">Poblacion</div>
                                    <div class="option-item" data-value="San Carlos">San Carlos</div>
                                    <div class="option-item" data-value="San Isidro">San Isidro</div>
                                    <div class="option-item" data-value="Sisiman">Sisiman</div>
                                    <div class="option-item" data-value="Townsite">Townsite</div>
                                </div>
                                <div class="option-group">
                                    <div class="option-header">MORONG</div>
                                    <div class="option-item" data-value="Binaritan">Binaritan</div>
                                    <div class="option-item" data-value="Mabayo">Mabayo</div>
                                    <div class="option-item" data-value="Nagbalayong">Nagbalayong</div>
                                    <div class="option-item" data-value="Poblacion">Poblacion</div>
                                    <div class="option-item" data-value="Sabang">Sabang</div>
                                    <div class="option-item" data-value="San Jose">San Jose</div>
                                </div>
                                <div class="option-group">
                                    <div class="option-header">ORANI</div>
                                    <div class="option-item" data-value="Bagong Paraiso">Bagong Paraiso</div>
                                    <div class="option-item" data-value="Balut">Balut</div>
                                    <div class="option-item" data-value="Bayorbor">Bayorbor</div>
                                    <div class="option-item" data-value="Calungusan">Calungusan</div>
                                    <div class="option-item" data-value="Camacho">Camacho</div>
                                    <div class="option-item" data-value="Daang Bago">Daang Bago</div>
                                    <div class="option-item" data-value="Dona">Dona</div>
                                    <div class="option-item" data-value="Kaparangan">Kaparangan</div>
                                    <div class="option-item" data-value="Mabayo">Mabayo</div>
                                    <div class="option-item" data-value="Masagana">Masagana</div>
                                    <div class="option-item" data-value="Mulawin">Mulawin</div>
                                    <div class="option-item" data-value="Paglalaban">Paglalaban</div>
                                    <div class="option-item" data-value="Palawe">Palawe</div>
                                    <div class="option-item" data-value="Pantalan Bago">Pantalan Bago</div>
                                    <div class="option-item" data-value="Poblacion">Poblacion</div>
                                    <div class="option-item" data-value="Saguing">Saguing</div>
                                    <div class="option-item" data-value="Tagumpay">Tagumpay</div>
                                    <div class="option-item" data-value="Tala">Tala</div>
                                    <div class="option-item" data-value="Tapulao">Tapulao</div>
                                    <div class="option-item" data-value="Tenejero">Tenejero</div>
                                    <div class="option-item" data-value="Wawa">Wawa</div>
                                </div>
                                <div class="option-group">
                                    <div class="option-header">ORION</div>
                                    <div class="option-item" data-value="Balut">Balut</div>
                                    <div class="option-item" data-value="Bantan">Bantan</div>
                                    <div class="option-item" data-value="Burgos">Burgos</div>
                                    <div class="option-item" data-value="Calungusan">Calungusan</div>
                                    <div class="option-item" data-value="Camacho">Camacho</div>
                                    <div class="option-item" data-value="Capunitan">Capunitan</div>
                                    <div class="option-item" data-value="Daan Bilolo">Daan Bilolo</div>
                                    <div class="option-item" data-value="Daan Pare">Daan Pare</div>
                                    <div class="option-item" data-value="General Lim">General Lim</div>
                                    <div class="option-item" data-value="Kapunitan">Kapunitan</div>
                                    <div class="option-item" data-value="Lati">Lati</div>
                                    <div class="option-item" data-value="Luyahan">Luyahan</div>
                                    <div class="option-item" data-value="Mabayo">Mabayo</div>
                                    <div class="option-item" data-value="Maligaya">Maligaya</div>
                                    <div class="option-item" data-value="Poblacion">Poblacion</div>
                                    <div class="option-item" data-value="Sabatan">Sabatan</div>
                                    <div class="option-item" data-value="San Vicente">San Vicente</div>
                                    <div class="option-item" data-value="Santo Domingo">Santo Domingo</div>
                                    <div class="option-item" data-value="Villa Angeles">Villa Angeles</div>
                                    <div class="option-item" data-value="Wawa">Wawa</div>
                                </div>
                                <div class="option-group">
                                    <div class="option-header">PILAR</div>
                                    <div class="option-item" data-value="Bagumbayan">Bagumbayan</div>
                                    <div class="option-item" data-value="Balanoy">Balanoy</div>
                                    <div class="option-item" data-value="Bantan Munti">Bantan Munti</div>
                                    <div class="option-item" data-value="Bantan Grande">Bantan Grande</div>
                                    <div class="option-item" data-value="Burgos">Burgos</div>
                                    <div class="option-item" data-value="Del Rosario">Del Rosario</div>
                                    <div class="option-item" data-value="Diwa">Diwa</div>
                                    <div class="option-item" data-value="Fatima">Fatima</div>
                                    <div class="option-item" data-value="Landing">Landing</div>
                                    <div class="option-item" data-value="Liwa-liwa">Liwa-liwa</div>
                                    <div class="option-item" data-value="Nagwaling">Nagwaling</div>
                                    <div class="option-item" data-value="Panilao">Panilao</div>
                                    <div class="option-item" data-value="Poblacion">Poblacion</div>
                                    <div class="option-item" data-value="Rizal">Rizal</div>
                                    <div class="option-item" data-value="Santo Niño">Santo Niño</div>
                                    <div class="option-item" data-value="Wawa">Wawa</div>
                                </div>
                                <div class="option-group">
                                    <div class="option-header">SAMAL</div>
                                    <div class="option-item" data-value="Bagong Silang">Bagong Silang</div>
                                    <div class="option-item" data-value="Bangkong">Bangkong</div>
                                    <div class="option-item" data-value="Burgos">Burgos</div>
                                    <div class="option-item" data-value="Calaguiman">Calaguiman</div>
                                    <div class="option-item" data-value="Calantas">Calantas</div>
                                    <div class="option-item" data-value="Daan Bilolo">Daan Bilolo</div>
                                    <div class="option-item" data-value="Daang Pare">Daang Pare</div>
                                    <div class="option-item" data-value="Del Pilar">Del Pilar</div>
                                    <div class="option-item" data-value="General Lim">General Lim</div>
                                    <div class="option-item" data-value="Imelda">Imelda</div>
                                    <div class="option-item" data-value="Lourdes">Lourdes</div>
                                    <div class="option-item" data-value="Mabatang">Mabatang</div>
                                    <div class="option-item" data-value="Maligaya">Maligaya</div>
                                    <div class="option-item" data-value="Poblacion">Poblacion</div>
                                    <div class="option-item" data-value="San Juan">San Juan</div>
                                    <div class="option-item" data-value="San Roque">San Roque</div>
                                    <div class="option-item" data-value="Santo Niño">Santo Niño</div>
                                    <div class="option-item" data-value="Sulong">Sulong</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="eventLocation" name="eventLocation" value="All Locations">
                </div>
                
                <div class="form-group">
                    <label for="eventOrganizer">Person in Charge</label>
                    <input type="text" id="eventOrganizer" name="eventOrganizer" placeholder="Name of organizer" required>
                </div>
                
                <div class="form-group">
                    <label for="eventDescription">Event Description</label>
                    <textarea id="eventDescription" name="eventDescription" placeholder="Brief description of the event..." required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="notificationType">Notification Type</label>
                    <select id="notificationType" name="notificationType">
                        <option value="email">Email</option>
                        <option value="sms">Text Message</option>
                        <option value="both">Both Email and Text</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="recipientGroup">Recipient Group</label>
                    <select id="recipientGroup" name="recipientGroup">
                        <option value="All Users">All Users</option>
                        <option value="Parents">Parents</option>
                        <option value="Health Workers">Health Workers</option>
                        <option value="Barangay Officials">Barangay Officials</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="create_event" class="btn btn-add">
                        <span class="btn-text">Create Event</span>
                    </button>
                </div>
                
                <!-- Location Preview Section -->
                <div id="locationPreview" style="margin-top: 20px; display: none;">
                    <div style="background: rgba(161, 180, 84, 0.05); padding: 15px; border-radius: 10px; border-left: 4px solid var(--color-highlight);">
                        <h4 style="margin: 0 0 10px 0; color: var(--color-highlight);">Location Preview</h4>
                        <div id="locationPreviewContent">
                            <div style="text-align: center; opacity: 0.7;">Select a location to see which users would receive notifications</div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Location Notification Section -->
        <div class="location-notification-container" style="margin-top: 30px;">
            <div class="section-header">
                <h2>📍 Send Location-Based Notifications</h2>
                <p>Send notifications to users in specific barangays or municipalities</p>
            </div>
            
            <div class="location-notification-form">
                <div class="form-group">
                    <label for="notificationLocation">Target Location</label>
                    <div class="custom-select-container">
                        <div class="select-header" onclick="toggleNotificationLocationDropdown()">
                            <span id="selected-notification-location">Select Location</span>
                            <span class="dropdown-arrow">▼</span>
                        </div>
                        <div class="dropdown-content" id="notification-location-dropdown">
                            <div class="search-container">
                                <input type="text" id="notification-location-search" placeholder="Search barangay or municipality..." onkeyup="filterNotificationLocationOptions()">
                            </div>
                            <div class="options-container">
                                <!-- Municipality Options -->
                                <div class="option-group">
                                    <div class="option-header">Municipalities</div>
                                    <div class="option-item" data-value="MUNICIPALITY_ABUCAY" onclick="selectNotificationLocation('MUNICIPALITY_ABUCAY', 'ABUCAY (All Barangays)')">ABUCAY (All Barangays)</div>
                                    <div class="option-item" data-value="MUNICIPALITY_BAGAC" onclick="selectNotificationLocation('MUNICIPALITY_BAGAC', 'BAGAC (All Barangays)')">BAGAC (All Barangays)</div>
                                    <div class="option-item" data-value="MUNICIPALITY_BALANGA" onclick="selectNotificationLocation('MUNICIPALITY_BALANGA', 'CITY OF BALANGA (All Barangays)')">CITY OF BALANGA (All Barangays)</div>
                                    <div class="option-item" data-value="MUNICIPALITY_DINALUPIHAN" onclick="selectNotificationLocation('MUNICIPALITY_DINALUPIHAN', 'DINALUPIHAN (All Barangays)')">DINALUPIHAN (All Barangays)</div>
                                    <div class="option-item" data-value="MUNICIPALITY_HERMOSA" onclick="selectNotificationLocation('MUNICIPALITY_HERMOSA', 'HERMOSA (All Barangays)')">HERMOSA (All Barangays)</div>
                                    <div class="option-item" data-value="MUNICIPALITY_LIMAY" onclick="selectNotificationLocation('MUNICIPALITY_LIMAY', 'LIMAY (All Barangays)')">LIMAY (All Barangays)</div>
                                    <div class="option-item" data-value="MUNICIPALITY_MARIVELES" onclick="selectNotificationLocation('MUNICIPALITY_MARIVELES', 'MARIVELES (All Barangays)')">MARIVELES (All Barangays)</div>
                                    <div class="option-item" data-value="MUNICIPALITY_MORONG" onclick="selectNotificationLocation('MUNICIPALITY_MORONG', 'MORONG (All Barangays)')">MORONG (All Barangays)</div>
                                    <div class="option-item" data-value="MUNICIPALITY_ORANI" onclick="selectNotificationLocation('MUNICIPALITY_ORANI', 'ORANI (All Barangays)')">ORANI (All Barangays)</div>
                                    <div class="option-item" data-value="MUNICIPALITY_ORION" onclick="selectNotificationLocation('MUNICIPALITY_ORION', 'ORION (All Barangays)')">ORION (All Barangays)</div>
                                    <div class="option-item" data-value="MUNICIPALITY_PILAR" onclick="selectNotificationLocation('MUNICIPALITY_PILAR', 'PILAR (All Barangays)')">PILAR (All Barangays)</div>
                                    <div class="option-item" data-value="MUNICIPALITY_SAMAL" onclick="selectNotificationLocation('MUNICIPALITY_SAMAL', 'SAMAL (All Barangays)')">SAMAL (All Barangays)</div>
                                </div>
                                
                                <!-- Individual Barangays -->
                                <div class="option-group">
                                    <div class="option-header">Popular Barangays</div>
                                    <div class="option-item" data-value="A. Rivera (Pob.)" onclick="selectNotificationLocation('A. Rivera (Pob.)', 'A. Rivera (Pob.)')">A. Rivera (Pob.)</div>
                                    <div class="option-item" data-value="Bagumbayan" onclick="selectNotificationLocation('Bagumbayan', 'Bagumbayan')">Bagumbayan</div>
                                    <div class="option-item" data-value="Central" onclick="selectNotificationLocation('Central', 'Central')">Central</div>
                                    <div class="option-item" data-value="Poblacion" onclick="selectNotificationLocation('Poblacion', 'Poblacion')">Poblacion</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notificationTitle">Notification Title</label>
                    <input type="text" id="notificationTitle" placeholder="e.g., Important Health Update" required>
                </div>
                
                <div class="form-group">
                    <label for="notificationMessage">Message</label>
                    <textarea id="notificationMessage" placeholder="Enter your notification message..." rows="4" required></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" onclick="sendLocationNotificationFromForm()" class="btn btn-add">
                        📱 Send Location Notification
                    </button>
                </div>
                
                <!-- Location Preview for Notifications -->
                <div id="notificationLocationPreview" style="margin-top: 20px; display: none;">
                    <div style="background: rgba(161, 180, 84, 0.05); padding: 15px; border-radius: 10px; border-left: 4px solid var(--color-highlight);">
                        <h4 style="margin: 0 0 10px 0; color: var(--color-highlight);">Location Preview</h4>
                        <div id="notificationLocationPreviewContent">
                            <div style="text-align: center; opacity: 0.7;">Select a location to see which users would receive notifications</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="events-table-container">
            <div class="table-header">
                <h2>Upcoming Events</h2>
                <div class="table-controls">
                    <select id="eventFilter" onchange="filterEvents(this.value)">
                        <option value="all">All Events</option>
                        <option value="upcoming">Upcoming</option>
                        <option value="past">Past Events</option>
                    </select>
                    <button onclick="confirmDeleteAllEvents()" class="btn btn-danger">Delete All Events</button>
                </div>
            </div>
            
            <?php if(isset($_GET['deleted'])): ?>
                <div class="alert alert-success">
                    Event deleted successfully!
                </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['deleted_all'])): ?>
                <div class="alert alert-success">
                    All events deleted successfully!
                </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['updated'])): ?>
                <div class="alert alert-success">
                    Event updated successfully!
                    <br>Update notification sent to all users!
                </div>
            <?php endif; ?>
            
            <table class="events-table">
                <thead>
                    <tr>
                        <th>Event Title</th>
                        <th>Type</th>
                        <th>Date & Time</th>
                        <th>Location</th>
                        <th>Organizer</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="eventsTableBody">
                    <?php if($dbConnected && count($programs) > 0): ?>
                        <?php foreach($programs as $program): ?>
                            <?php
                                // Determine if event is upcoming or past
                                $eventDate = strtotime($program['date_time']);
                                $currentDate = time();
                                $status = ($eventDate > $currentDate) ? 'upcoming' : 'past';
                            ?>
                            <tr class="event-row <?php echo $status; ?>">
                                <td><?php echo htmlspecialchars($program['title']); ?></td>
                                <td><?php echo htmlspecialchars($program['type']); ?></td>
                                <td><?php echo date('M j, Y, g:i A', strtotime($program['date_time'])); ?></td>
                                <td><?php echo htmlspecialchars($program['location']); ?></td>
                                <td><?php echo htmlspecialchars($program['organizer']); ?></td>
                                <td><span class="status-badge status-<?php echo $status; ?>"><?php echo ucfirst($status); ?></span></td>
                                <td>
                                    <div class="action-buttons">
                                        <button onclick="openEditModal(<?php echo $program['program_id']; ?>, '<?php echo htmlspecialchars($program['title']); ?>', '<?php echo htmlspecialchars($program['type']); ?>', '<?php echo htmlspecialchars($program['description']); ?>', '<?php echo $program['date_time']; ?>', '<?php echo htmlspecialchars($program['location']); ?>', '<?php echo htmlspecialchars($program['organizer']); ?>')" class="btn btn-add">Edit</button>
                                        <a href="event.php?delete=<?php echo $program['program_id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this event?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="no-events">No events found. Create your first event!</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="navbar">
        <div class="navbar-header">
            <div class="navbar-logo">
                <div class="navbar-logo-icon">
                    <img src="/logo.png" alt="Logo" style="width: 40px; height: 40px;">
                </div>
                <div class="navbar-logo-text">NutriSaur</div>
            </div>
        </div>
        <div class="navbar-menu">
            <ul>
                <li><a href="dash"><span class="navbar-icon"></span><span>Dashboard</span></a></li>

                <li><a href="event"><span class="navbar-icon"></span><span>Nutrition Event Notifications</span></a></li>
                <li><a href="ai"><span class="navbar-icon"></span><span>Chatbot & AI Logs</span></a></li>
                <li><a href="settings"><span class="navbar-icon"></span><span>Settings & Admin</span></a></li>
                <li><a href="logout" style="color: #ff5252;"><span class="navbar-icon"></span><span>Logout</span></a></li>
            </ul>
        </div>
        <div class="navbar-footer">
            <div>NutriSaur v1.0 • © 2023</div>
            <div style="margin-top: 10px;">Logged in as: <?php echo htmlspecialchars($username); ?></div>
        </div>
    </div>

    <!-- Edit Event Modal -->
    <div id="editModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Event</h3>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST" action="event.php">
                <input type="hidden" id="edit_program_id" name="program_id">
                <input type="hidden" name="edit_event" value="1">
                
                <div class="form-group">
                    <label for="edit_eventTitle">Event Title</label>
                    <input type="text" id="edit_eventTitle" name="eventTitle" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_eventType">Event Type</label>
                    <select id="edit_eventType" name="eventType" required>
                        <option value="Workshop">Workshop</option>
                        <option value="Seminar">Seminar</option>
                        <option value="Webinar">Webinar</option>
                        <option value="Demo">Demo</option>
                        <option value="Training">Training</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_eventDate">Date & Time</label>
                    <input type="datetime-local" id="edit_eventDate" name="eventDate" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_eventLocation">Location</label>
                    <div class="custom-select-container">
                        <div class="select-header" onclick="toggleEditEventLocationDropdown()">
                            <span id="selected-edit-event-location">All Locations</span>
                            <span class="dropdown-arrow">▼</span>
                        </div>
                        <div class="dropdown-content" id="edit-event-location-dropdown">
                            <div class="search-container">
                                <input type="text" id="edit-event-location-search" placeholder="Search barangay or municipality..." onkeyup="filterEditEventLocationOptions()">
                            </div>
                            <div class="options-container">
                                <div class="option-group">
                                    <div class="option-header">Municipalities</div>
                                    <div class="option-item" data-value="" onclick="selectEditEventLocation('', 'All Locations')">All Locations</div>
                                    <div class="option-item" data-value="MUNICIPALITY_ABUCAY" onclick="selectEditEventLocation('MUNICIPALITY_ABUCAY', 'ABUCAY (All Barangays)')">ABUCAY (All Barangays)</div>
                                    <div class="option-item" data-value="MUNICIPALITY_BAGAC" onclick="selectEditEventLocation('MUNICIPALITY_BAGAC', 'BAGAC (All Barangays)')">BAGAC (All Barangays)</div>
                                    <div class="option-item" data-value="MUNICIPALITY_BALANGA" onclick="selectEditEventLocation('MUNICIPALITY_BALANGA', 'CITY OF BALANGA (All Barangays)')">CITY OF BALANGA (All Barangays)</div>
                                    <div class="option-item" data-value="MUNICIPALITY_DINALUPIHAN" onclick="selectEditEventLocation('MUNICIPALITY_DINALUPIHAN', 'DINALUPIHAN (All Barangays)')">DINALUPIHAN (All Barangays)</div>
                                    <div class="option-item" data-value="MUNICIPALITY_HERMOSA" onclick="selectEditEventLocation('MUNICIPALITY_HERMOSA', 'HERMOSA (All Barangays)')">HERMOSA (All Barangays)</div>
                                    <div class="option-item" data-value="MUNICIPALITY_LIMAY" onclick="selectEditEventLocation('MUNICIPALITY_LIMAY', 'LIMAY (All Barangays)')">LIMAY (All Barangays)</div>
                                    <div class="option-item" data-value="MUNICIPALITY_MARIVELES" onclick="selectEditEventLocation('MUNICIPALITY_MARIVELES', 'MARIVELES (All Barangays)')">MARIVELES (All Barangays)</div>
                                    <div class="option-item" data-value="MUNICIPALITY_MORONG" onclick="selectEditEventLocation('MUNICIPALITY_MORONG', 'MORONG (All Barangays)')">MORONG (All Barangays)</div>
                                    <div class="option-item" data-value="MUNICIPALITY_ORANI" onclick="selectEditEventLocation('MUNICIPALITY_ORANI', 'ORANI (All Barangays)')">ORANI (All Barangays)</div>
                                    <div class="option-item" data-value="MUNICIPALITY_ORION" onclick="selectEditEventLocation('MUNICIPALITY_ORION', 'ORION (All Barangays)')">ORION (All Barangays)</div>
                                    <div class="option-item" data-value="MUNICIPALITY_PILAR" onclick="selectEditEventLocation('MUNICIPALITY_PILAR', 'PILAR (All Barangays)')">PILAR (All Barangays)</div>
                                    <div class="option-item" data-value="MUNICIPALITY_SAMAL" onclick="selectEditEventLocation('MUNICIPALITY_SAMAL', 'SAMAL (All Barangays)')">SAMAL (All Barangays)</div>
                                </div>
                                <div class="option-group">
                                    <div class="option-header">HERMOSA</div>
                                    <div class="option-item" data-value="A. Rivera (Pob.)" onclick="selectEditEventLocation('A. Rivera (Pob.)', 'A. Rivera (Pob.)')">A. Rivera (Pob.)</div>
                                    <div class="option-item" data-value="Almacen" onclick="selectEditEventLocation('Almacen', 'Almacen')">Almacen</div>
                                    <div class="option-item" data-value="Bacong" onclick="selectEditEventLocation('Bacong', 'Bacong')">Bacong</div>
                                    <div class="option-item" data-value="Balsic" onclick="selectEditEventLocation('Balsic', 'Balsic')">Balsic</div>
                                    <div class="option-item" data-value="Bamban" onclick="selectEditEventLocation('Bamban', 'Bamban')">Bamban</div>
                                    <div class="option-item" data-value="Burgos-Soliman (Pob.)" onclick="selectEditEventLocation('Burgos-Soliman (Pob.)', 'Burgos-Soliman (Pob.)')">Burgos-Soliman (Pob.)</div>
                                    <div class="option-item" data-value="Cataning (Pob.)" onclick="selectEditEventLocation('Cataning (Pob.)', 'Cataning (Pob.)')">Cataning (Pob.)</div>
                                    <div class="option-item" data-value="Culis" onclick="selectEditEventLocation('Culis', 'Culis')">Culis</div>
                                    <div class="option-item" data-value="Daungan (Pob.)" onclick="selectEditEventLocation('Daungan (Pob.)', 'Daungan (Pob.)')">Daungan (Pob.)</div>
                                    <div class="option-item" data-value="Mabiga" onclick="selectEditEventLocation('Mabiga', 'Mabiga')">Mabiga</div>
                                    <div class="option-item" data-value="Mabuco" onclick="selectEditEventLocation('Mabuco', 'Mabuco')">Mabuco</div>
                                    <div class="option-item" data-value="Maite" onclick="selectEditEventLocation('Maite', 'Maite')">Maite</div>
                                    <div class="option-item" data-value="Mambog - Mandama" onclick="selectEditEventLocation('Mambog - Mandama', 'Mambog - Mandama')">Mambog - Mandama</div>
                                    <div class="option-item" data-value="Palihan" onclick="selectEditEventLocation('Palihan', 'Palihan')">Palihan</div>
                                    <div class="option-item" data-value="Pandatung" onclick="selectEditEventLocation('Pandatung', 'Pandatung')">Pandatung</div>
                                    <div class="option-item" data-value="Pulo" onclick="selectEditEventLocation('Pulo', 'Pulo')">Pulo</div>
                                    <div class="option-item" data-value="Saba" onclick="selectEditEventLocation('Saba', 'Saba')">Saba</div>
                                    <div class="option-item" data-value="San Pedro" onclick="selectEditEventLocation('San Pedro', 'San Pedro')">San Pedro</div>
                                    <div class="option-item" data-value="Sumalo" onclick="selectEditEventLocation('Sumalo', 'Sumalo')">Sumalo</div>
                                    <div class="option-item" data-value="Tipo" onclick="selectEditEventLocation('Tipo', 'Tipo')">Tipo</div>
                                </div>
                                <div class="option-group">
                                    <div class="option-header">LIMAY</div>
                                    <div class="option-item" data-value="Alas-asin" onclick="selectEditEventLocation('Alas-asin', 'Alas-asin')">Alas-asin</div>
                                    <div class="option-item" data-value="Anonang" onclick="selectEditEventLocation('Anonang', 'Anonang')">Anonang</div>
                                    <div class="option-item" data-value="Bataan" onclick="selectEditEventLocation('Bataan', 'Bataan')">Bataan</div>
                                    <div class="option-item" data-value="Bayan-bayanan" onclick="selectEditEventLocation('Bayan-bayanan', 'Bayan-bayanan')">Bayan-bayanan</div>
                                    <div class="option-item" data-value="Binuangan" onclick="selectEditEventLocation('Binuangan', 'Binuangan')">Binuangan</div>
                                    <div class="option-item" data-value="Cacabasan" onclick="selectEditEventLocation('Cacabasan', 'Cacabasan')">Cacabasan</div>
                                    <div class="option-item" data-value="Duale" onclick="selectEditEventLocation('Duale', 'Duale')">Duale</div>
                                    <div class="option-item" data-value="Kitang 2" onclick="selectEditEventLocation('Kitang 2', 'Kitang 2')">Kitang 2</div>
                                    <div class="option-item" data-value="Kitang 2 & Luz" onclick="selectEditEventLocation('Kitang 2 & Luz', 'Kitang 2 & Luz')">Kitang 2 & Luz</div>
                                    <div class="option-item" data-value="Lamao" onclick="selectEditEventLocation('Lamao', 'Lamao')">Lamao</div>
                                    <div class="option-item" data-value="Luz" onclick="selectEditEventLocation('Luz', 'Luz')">Luz</div>
                                    <div class="option-item" data-value="Mabayo" onclick="selectEditEventLocation('Mabayo', 'Mabayo')">Mabayo</div>
                                    <div class="option-item" data-value="Malaya" onclick="selectEditEventLocation('Malaya', 'Malaya')">Malaya</div>
                                    <div class="option-item" data-value="Mountain View" onclick="selectEditEventLocation('Mountain View', 'Mountain View')">Mountain View</div>
                                    <div class="option-item" data-value="Poblacion" onclick="selectEditEventLocation('Poblacion', 'Poblacion')">Poblacion</div>
                                    <div class="option-item" data-value="Reformista" onclick="selectEditEventLocation('Reformista', 'Reformista')">Reformista</div>
                                    <div class="option-item" data-value="San Isidro" onclick="selectEditEventLocation('San Isidro', 'San Isidro')">San Isidro</div>
                                    <div class="option-item" data-value="Santiago" onclick="selectEditEventLocation('Santiago', 'Santiago')">Santiago</div>
                                    <div class="option-item" data-value="Tuyan" onclick="selectEditEventLocation('Tuyan', 'Tuyan')">Tuyan</div>
                                    <div class="option-item" data-value="Villa Angeles" onclick="selectEditEventLocation('Villa Angeles', 'Villa Angeles')">Villa Angeles</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="edit_eventLocation" name="eventLocation" value="">
                </div>
                
                <div class="form-group">
                    <label for="edit_eventOrganizer">Person in Charge</label>
                    <input type="text" id="edit_eventOrganizer" name="eventOrganizer" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_eventDescription">Event Description</label>
                    <textarea id="edit_eventDescription" name="eventDescription" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="edit_notificationType">Notification Type</label>
                    <select id="edit_notificationType" name="notificationType">
                        <option value="email">Email</option>
                        <option value="sms">Text Message</option>
                        <option value="both">Both Email and Text</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-add">Update Event</button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Duplicate Warning Modal -->
    <div id="duplicateWarningModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>⚠️ Duplicate Events Detected</h3>
                <span class="close" onclick="closeDuplicateWarningModal()">&times;</span>
            </div>
            <div style="padding: 30px;">
                <div style="margin-bottom: 20px;">
                    <p>The following events were not imported because they already exist in the system:</p>
                </div>
                <div id="duplicateList" style="max-height: 300px; overflow-y: auto; margin-bottom: 20px;">
                    <!-- Duplicate events will be populated here -->
                </div>
                <div style="text-align: center; color: var(--color-warning); font-size: 14px;">
                    <strong>Note:</strong> Only unique events were imported. You can review the existing events in the table below.
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeDuplicateWarningModal()">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // NEW SIMPLE THEME TOGGLE - Optimized to prevent flickering!
        function newToggleTheme() {
            console.log('=== NEW THEME TOGGLE FUNCTION CALLED ===');
            
            const body = document.body;
            const toggleBtn = document.getElementById('new-theme-toggle');
            const icon = toggleBtn.querySelector('.new-theme-icon');
            
            // Check current theme
            const isCurrentlyLight = body.classList.contains('light-theme');
            
            // Temporarily disable transitions to prevent flickering
            toggleBtn.style.transition = 'none';
            
            if (isCurrentlyLight) {
                // Switch to dark theme
                console.log('Switching from LIGHT to DARK theme');
                
                // Batch all changes together
                requestAnimationFrame(() => {
                    // Remove light theme, add dark theme
                    body.classList.remove('light-theme');
                    body.classList.add('dark-theme');
                    
                    // Update icon to moon (indicating you can switch to light)
                    icon.textContent = '🌙';
                    
                    // Apply dark theme colors directly
                    body.style.backgroundColor = '#1A211A';
                    body.style.color = '#E8F0D6';
                    
                    // Update button color
                    toggleBtn.style.backgroundColor = '#FF9800';
                    
                    // Re-enable transitions after a brief delay
                    setTimeout(() => {
                        toggleBtn.style.transition = '';
                    }, 50);
                });
                
                // Save preference
                localStorage.setItem('nutrisaur-theme', 'dark');
                
                console.log('✅ Dark theme applied successfully!');
                
            } else {
                // Switch to light theme
                console.log('Switching from DARK to LIGHT theme');
                
                // Batch all changes together
                requestAnimationFrame(() => {
                    // Remove dark theme, add light theme
                    body.classList.remove('dark-theme');
                    body.classList.add('light-theme');
                    
                    // Update icon to sun (indicating you can switch to dark)
                    icon.textContent = '☀';
                    
                    // Apply light theme colors directly
                    body.style.backgroundColor = '#F0F7F0';
                    body.style.color = '#1B3A1B';
                    
                    // Update button color
                    toggleBtn.style.backgroundColor = '#000000';
                    
                    // Ensure sun icon is white
                    icon.style.color = '#FFFFFF';
                    
                    // Re-enable transitions after a brief delay
                    setTimeout(() => {
                        toggleBtn.style.transition = '';
                    }, 50);
                });
                
                // Save preference
                localStorage.setItem('nutrisaur-theme', 'light');
                
                console.log('✅ Light theme applied successfully!');
            }
            
            console.log('Final body classes:', body.className);
            console.log('Final icon:', icon.textContent);
            console.log('Final background color:', body.style.backgroundColor);
            console.log('Final text color:', body.style.color);
        }

        // OLD THEME TOGGLE (keeping for compatibility)
        function toggleTheme() {
            console.log('=== toggleTheme function called ===');
            const body = document.body;
            const themeToggle = document.getElementById('theme-toggle');
            const themeIcon = themeToggle.querySelector('.theme-icon');
            
            console.log('Current theme classes:', body.className);
            console.log('Theme toggle element:', themeToggle);
            console.log('Theme icon element:', themeIcon);
            
            if (body.classList.contains('light-theme')) {
                console.log('Switching from light to dark theme');
                body.classList.remove('light-theme');
                body.classList.add('dark-theme');
                themeIcon.textContent = '🌙';
                localStorage.setItem('nutrisaur-theme', 'dark');
            } else {
                console.log('Switching from dark to light theme');
                body.classList.remove('dark-theme');
                body.classList.add('light-theme');
                themeIcon.textContent = '☀';
                localStorage.setItem('nutrisaur-theme', 'light');
            }
            
            console.log('New theme classes:', body.className);
            console.log('Theme saved to localStorage:', localStorage.getItem('nutrisaur-theme'));
            console.log('=== Theme toggle completed ===');
        }

        function loadSavedTheme() {
            const savedTheme = localStorage.getItem('nutrisaur-theme');
            const newToggleBtn = document.getElementById('new-theme-toggle');
            const newIcon = newToggleBtn.querySelector('.new-theme-icon');
            
            // Temporarily disable transitions during initialization
            newToggleBtn.style.transition = 'none';
            
            if (savedTheme === 'light') {
                document.body.classList.remove('dark-theme');
                document.body.classList.add('light-theme');
                newIcon.textContent = '☀';
                newToggleBtn.style.backgroundColor = '#000000';
                
                // Ensure sun icon is white
                newIcon.style.color = '#FFFFFF';
                
                // Apply light theme colors directly
                document.body.style.backgroundColor = '#F0F7F0';
                document.body.style.color = '#1B3A1B';
            } else {
                // Default to dark theme
                document.body.classList.remove('light-theme');
                document.body.classList.add('dark-theme');
                newIcon.textContent = '🌙';
                newToggleBtn.style.backgroundColor = '#FF9800';
                
                // Apply dark theme colors directly
                document.body.style.backgroundColor = '#1A211A';
                document.body.style.color = '#E8F0D6';
            }
            
            // Re-enable transitions after initialization
            setTimeout(() => {
                newToggleBtn.style.transition = '';
            }, 100);
        }

        // Load theme on page load
        window.addEventListener('DOMContentLoaded', () => {
            console.log('DOM loaded, loading saved theme...');
            loadSavedTheme();
            console.log('Theme loaded, current body classes:', document.body.className);
            
            // Add form submission handler for event creation
            const eventForm = document.querySelector('form[name="createEventForm"]');
            if (eventForm) {
                eventForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    console.log('Event form submitted, handling via AJAX...');
                    
                    const formData = new FormData(eventForm);
                    const eventData = {
                        title: formData.get('eventTitle'),
                        type: formData.get('eventType'),
                        description: formData.get('eventDescription'),
                        date_time: formData.get('eventDate'),
                        location: formData.get('eventLocation'),
                        organizer: formData.get('eventOrganizer')
                    };
                    
                    // Validate required fields
                    if (!eventData.title || !eventData.type || !eventData.description || !eventData.date_time || !eventData.location || !eventData.organizer) {
                        alert('Please fill in all required fields, including selecting a location from the dropdown.');
                        return;
                    }
                    
                    // Send AJAX request
                    fetch('https://nutrisaur-production.up.railway.app/unified_api.php?endpoint=create_event', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(eventData)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Event created successfully!');
                            location.reload(); // Refresh to show new event
                        } else {
                            alert('Error creating event: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error creating event. Please try again.');
                    });
                });
            }
        });



        // Toggle training groups (only if they exist)
        
        // NEW FUNCTION: Delete all events using AJAX
        function confirmDeleteAllEvents() {
            if (confirm('WARNING: This will delete ALL events permanently!\n\nAre you absolutely sure you want to continue?')) {
                if (confirm('FINAL WARNING: This action cannot be undone!\n\nClick OK to delete ALL events.')) {
                    // Use AJAX instead of redirecting
                    fetch('https://nutrisaur-production.up.railway.app/unified_api.php?endpoint=delete_all_events', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Successfully deleted ' + data.deleted_count + ' events!');
                            location.reload(); // Refresh to show empty table
                        } else {
                            alert('Error deleting events: ' + (data.error || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error deleting events. Please try again.');
                    });
                }
            }
        }
    </script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Event.php page loaded, checking for program recommendations...');
        
        // Check if program recommendation was passed in URL
        const urlParams = new URLSearchParams(window.location.search);
        const recommendedProgram = urlParams.get('program');
        const programType = urlParams.get('type');
        const programLocation = urlParams.get('location');
        const programDescription = urlParams.get('description');
        
        // Debug logging
        console.log('URL Parameters received:', {
            program: recommendedProgram,
            type: programType,
            location: programLocation,
            description: programDescription
        });
        
        // Additional location debugging
        if (programLocation) {
            console.log('Location parameter found:', programLocation);
            console.log('Location parameter type:', typeof programLocation);
            console.log('Location parameter length:', programLocation.length);
            console.log('Location parameter encoded:', encodeURIComponent(programLocation));
        } else {
            console.log('No location parameter found in URL');
        }
        
        // If program recommendation exists, pre-fill the form fields
        if (recommendedProgram) {
            console.log('Program recommendation detected:', recommendedProgram);
            
            // Add a small delay to ensure DOM is fully loaded
            setTimeout(() => {
                // Check if all required form elements exist
                const titleInput = document.getElementById('eventTitle');
                const typeInput = document.getElementById('eventType');
                const locationInput = document.getElementById('eventLocation');
                const descriptionInput = document.getElementById('eventDescription');
                const dateInput = document.getElementById('eventDate');
                const organizerInput = document.getElementById('eventOrganizer');
                
                // Log which elements were found
                console.log('Form elements found:', {
                    titleInput: !!titleInput,
                    typeInput: !!typeInput,
                    locationInput: !!locationInput,
                    descriptionInput: !!descriptionInput,
                    dateInput: !!dateInput,
                    organizerInput: !!organizerInput
                });
                
                // Pre-fill the form fields with the recommendation data
                if (titleInput) {
                    titleInput.value = recommendedProgram;
                    console.log('Set title to:', recommendedProgram);
                } else {
                    console.error('Title input element not found');
                }
                
                if (typeInput && programType) {
                    typeInput.value = programType;
                    console.log('Set type to:', programType);
                } else if (programType) {
                    console.error('Type input element not found or programType missing');
                }
                
                if (locationInput && programLocation) {
                    locationInput.value = programLocation;
                    console.log('Set location to:', programLocation);
                    
                    // Also update the dropdown display to show the selected location
                    const selectedLocationDisplay = document.getElementById('selected-event-location');
                    if (selectedLocationDisplay) {
                        // Find the exact match in the dropdown options
                        let foundMatch = false;
                        const optionItems = document.querySelectorAll('#event-location-dropdown .option-item');
                        
                        optionItems.forEach(item => {
                            if (item.getAttribute('data-value') === programLocation) {
                                // Found exact match - update display and mark as selected
                                selectedLocationDisplay.textContent = item.textContent;
                                console.log('Found exact match, updated dropdown display to:', item.textContent);
                                
                                // Mark this option as selected
                                optionItems.forEach(opt => opt.classList.remove('selected'));
                                item.classList.add('selected');
                                foundMatch = true;
                            }
                        });
                        
                        // If no exact match found, try to find a close match
                        if (!foundMatch) {
                            console.log('No exact match found, looking for close match...');
                            optionItems.forEach(item => {
                                const itemText = item.textContent.toLowerCase();
                                const programLocationLower = programLocation.toLowerCase();
                                
                                // Check if the program location is contained in the option text
                                if (itemText.includes(programLocationLower) || programLocationLower.includes(itemText)) {
                                    selectedLocationDisplay.textContent = item.textContent;
                                    console.log('Found close match, updated dropdown display to:', item.textContent);
                                    
                                    // Mark this option as selected
                                    optionItems.forEach(opt => opt.classList.remove('selected'));
                                    item.classList.add('selected');
                                    foundMatch = true;
                                }
                            });
                        }
                        
                        // If still no match, just use the program location as is
                        if (!foundMatch) {
                            selectedLocationDisplay.textContent = programLocation;
                            console.log('No match found, using program location as is:', programLocation);
                        }
                    }
                } else if (programLocation) {
                    console.error('Location input element not found or programLocation missing');
                }
                
                if (descriptionInput && programDescription) {
                    descriptionInput.value = programDescription;
                    console.log('Set description to:', programDescription);
                } else if (programDescription) {
                    console.error('Description input element not found or programDescription missing');
                }
                
                // Set a default date (7 days from now) if not specified
                if (dateInput) {
                    const futureDate = new Date();
                    futureDate.setDate(futureDate.getDate() + 7);
                    const formattedDate = futureDate.toISOString().slice(0, 16);
                    dateInput.value = formattedDate;
                    console.log('Set default date to:', formattedDate);
                } else {
                    console.error('Date input element not found');
                }
                
                // Set default organizer if not specified
                if (organizerInput && !organizerInput.value) {
                    organizerInput.value = '<?php echo htmlspecialchars($username); ?>';
                    console.log('Set default organizer to:', '<?php echo htmlspecialchars($username); ?>');
                } else if (!organizerInput) {
                    console.error('Organizer input element not found');
                }
                
                // Show success message
                try {
                    showAlert('success', `Program recommendation loaded: ${recommendedProgram}`);
                    console.log('Success alert shown');
                } catch (error) {
                    console.error('Error showing alert:', error);
                }
                
                // Scroll to the form
                const formContainer = document.querySelector('.event-container');
                if (formContainer) {
                    formContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    console.log('Scrolled to form container');
                } else {
                    console.error('Form container not found');
                }
            }, 100); // 100ms delay
        }
        
        // Initialize training groups if they exist
        const trainingHeaders = document.querySelectorAll('.training-group-header');
        if (trainingHeaders.length > 0) {
            trainingHeaders.forEach(header => {
                header.addEventListener('click', () => {
                    const group = header.parentElement;
                    group.classList.toggle('open');
                });
            });
        }
    });



        // Function to filter events
        function filterEvents(filter) {
            const rows = document.querySelectorAll('.event-row');
            
            rows.forEach(row => {
                if (filter === 'all') {
                    row.style.display = '';
                } else if (filter === 'upcoming' && row.classList.contains('upcoming')) {
                    row.style.display = '';
                } else if (filter === 'past' && row.classList.contains('past')) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Function to confirm delete all events
        function confirmDeleteAll() {
            if (confirm('WARNING: This will delete ALL events permanently!\n\nAre you absolutely sure you want to continue?')) {
                if (confirm('FINAL WARNING: This action cannot be undone!\n\nClick OK to delete ALL events.')) {
                    window.location.href = 'event.php?delete_all=1';
                }
            }
        }

        // Function to send notifications
        function sendNotifications(eventId) {
            const recipientGroup = document.getElementById('recipientGroup').value;
            const notificationType = document.getElementById('notificationType').value;
            
            const formData = new FormData();
            formData.append('action', 'send');
            formData.append('event_id', eventId);
            formData.append('recipient_group', recipientGroup);
            formData.append('notification_type', notificationType);
            
            fetch('api/manage_notifications.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Notifications sent successfully!');
                } else {
                    showAlert('danger', 'Error sending notifications: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'Error sending notifications. Please try again.');
            });
        }

        // Form validation function
        function validateEventForm() {
            const titleInput = document.getElementById('eventTitle');
            const typeInput = document.getElementById('eventType');
            const descriptionInput = document.getElementById('eventDescription');
            const dateInput = document.getElementById('eventDate');
            const locationInput = document.getElementById('eventLocation');
            const organizerInput = document.getElementById('eventOrganizer');
            
            console.log('Form validation - Form values:', {
                title: titleInput?.value,
                type: typeInput?.value,
                description: descriptionInput?.value,
                date: dateInput?.value,
                location: locationInput?.value,
                organizer: organizerInput?.value
            });
            
            // Check required fields (location is optional - empty means "All Locations")
            if (!titleInput.value.trim()) {
                showAlert('danger', 'Please enter an event title.');
                return false;
            }
            
            if (!typeInput.value.trim()) {
                showAlert('danger', 'Please select an event type.');
                return false;
            }
            
            if (!descriptionInput.value.trim()) {
                showAlert('danger', 'Please enter an event description.');
                return false;
            }
            
            if (!dateInput.value) {
                showAlert('danger', 'Please select an event date and time.');
                return false;
            }
            
            if (!organizerInput.value.trim()) {
                showAlert('danger', 'Please enter an event organizer.');
                return false;
            }
            
            // Location is optional - empty string means "All Locations"
            // No validation needed for location
            console.log('Form validation passed - Location value:', locationInput?.value);
            
            return true;
        }

        // Function to show alert message
        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.textContent = message;
            
            const container = document.querySelector('.event-container');
            const existingAlert = container.querySelector('.alert');
            if (existingAlert) {
                container.removeChild(existingAlert);
            }
            
            container.insertBefore(alertDiv, container.firstChild);
            
            // Auto-hide the alert after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 5000);
        }

        // Function to fetch events
        function fetchEvents() {
            fetch('api/get_events.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateEventsTable(data.data);
                } else {
                    showAlert('danger', 'Error fetching events: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'Error fetching events. Please try again.');
            });
        }

        // Function to update the events table
        function updateEventsTable(events = null) {
            if (!events) return;
            
            const tbody = document.getElementById('eventsTableBody');
            tbody.innerHTML = '';
            
            const filter = document.getElementById('eventFilter')?.value || 'all';
            
            if (events.length === 0) {
                const row = document.createElement('tr');
                row.innerHTML = '<td colspan="7" class="no-events">No events found. Create your first event!</td>';
                tbody.appendChild(row);
                return;
            }
            
            events.forEach(event => {
                if (filter !== 'all' && filter !== event.status) return;
                
                const row = document.createElement('tr');
                row.className = `event-row ${event.status}`;
                
                // Format date
                const date = new Date(event.date_time);
                const formattedDate = date.toLocaleDateString('en-US', { 
                    month: 'short', 
                    day: 'numeric', 
                    year: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });
                
                row.innerHTML = `
                    <td>${event.title}</td>
                    <td>${event.type}</td>
                    <td>${formattedDate}</td>
                    <td>${event.location}</td>
                    <td>${event.organizer}</td>
                    <td><span class="status-badge status-${event.status}">${event.status.charAt(0).toUpperCase() + event.status.slice(1)}</span></td>
                    <td>
                        <div class="action-buttons">
                            <a href="edit_program.php?id=${event.program_id}" class="btn btn-add">Edit</a>
                            <a href="event.php?delete=${event.program_id}" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this event?')">Delete</a>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }



        // CSV Upload Functions
        // Flag to prevent double file selection
        let isFileSelectionInProgress = false;
        
        function handleFileSelect(input) {
            if (isFileSelectionInProgress) {
                console.log('File selection already in progress, ignoring duplicate call');
                return;
            }
            
            isFileSelectionInProgress = true;
            
            const file = input.files[0];
            if (file) {
                console.log('File selected:', file.name);
                
                // Also set the file in the main file input for form submission
                const mainFileInput = document.getElementById('csvFile');
                if (mainFileInput && input !== mainFileInput) {
                    // Create a new FileList-like object
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    mainFileInput.files = dt.files;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        const csv = e.target.result;
                        const lines = csv.split('\n');
                        const headers = lines[0].split(',');
                        const previewData = lines.slice(1, 6); // First 5 data rows
                        
                        showCSVPreview(headers, previewData);
                        document.getElementById('importBtn').disabled = false;
                        document.getElementById('cancelBtn').style.display = 'flex';
                    } catch (error) {
                        console.error('Error processing CSV file:', error);
                        alert('Error processing CSV file. Please check the file format.');
                    }
                };
                reader.onerror = function() {
                    console.error('Error reading file');
                    alert('Error reading file. Please try again.');
                };
                reader.readAsText(file);
            }
            
            // Reset flag after a short delay
            setTimeout(() => {
                isFileSelectionInProgress = false;
            }, 100);
        }

        function cancelUpload() {
            try {
                // Clear the file input
                const fileInput = document.getElementById('csvFile');
                if (fileInput) fileInput.value = '';
                
                // Hide the preview
                const preview = document.getElementById('csvPreview');
                if (preview) preview.style.display = 'none';
                
                // Disable import button and hide cancel button
                const importBtn = document.getElementById('importBtn');
                const cancelBtn = document.getElementById('cancelBtn');
                if (importBtn) importBtn.disabled = true;
                if (cancelBtn) cancelBtn.style.display = 'none';
                
                // Clear preview content
                const previewContent = document.getElementById('previewContent');
                if (previewContent) previewContent.innerHTML = '';
                
                console.log('Upload cancelled');
            } catch (error) {
                console.error('Error cancelling upload:', error);
            }
        }

        function showCSVPreview(headers, data) {
            const previewDiv = document.getElementById('csvPreview');
            const contentDiv = document.getElementById('previewContent');
            
            let tableHTML = '<table class="preview-table"><thead><tr>';
            headers.forEach(header => {
                tableHTML += `<th>${header.trim()}</th>`;
            });
            tableHTML += '</tr></thead><tbody>';
            
            data.forEach(row => {
                if (row.trim()) {
                    tableHTML += '<tr>';
                    const cells = row.split(',');
                    cells.forEach(cell => {
                        tableHTML += `<td>${cell.trim()}</td>`;
                    });
                    tableHTML += '</tr>';
                }
            });
            
            tableHTML += '</tbody></table>';
            contentDiv.innerHTML = tableHTML;
            previewDiv.style.display = 'block';
        }

        function downloadTemplate() {
            // Get current date and add some future dates for examples
            const now = new Date();
            const future1 = new Date(now.getTime() + (7 * 24 * 60 * 60 * 1000)); // 7 days from now
            const future2 = new Date(now.getTime() + (14 * 24 * 60 * 60 * 1000)); // 14 days from now
            const future3 = new Date(now.getTime() + (21 * 24 * 60 * 60 * 1000)); // 21 days from now
            
            const formatDate = (date) => {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                const hours = String(date.getHours()).padStart(2, '0');
                const minutes = String(date.getMinutes()).padStart(2, '0');
                return `${year}-${month}-${day} ${hours}:${minutes}`;
            };
            
            const csvContent = `title,type,description,date_time,location,organizer
Sample Event,Workshop,Sample description,${formatDate(future1)},Sample Location,Sample Organizer`;
            
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'events_template.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }

        // Improved Drag and Drop functionality
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing CSV upload...');
            
            // Form submission handling
            const csvUploadForm = document.getElementById('csvUploadForm');
            if (csvUploadForm) {
                csvUploadForm.addEventListener('submit', function(e) {
                    console.log('Form submission started');
                    const fileInput = document.getElementById('csvFile');
                    if (!fileInput.files[0]) {
                        e.preventDefault();
                        alert('Please select a CSV file first.');
                        return false;
                    }
                    
                    document.getElementById('importStatus').style.display = 'block';
                    document.getElementById('importBtn').disabled = true;
                    document.getElementById('cancelBtn').style.display = 'none';
                });
            }
            
            const uploadArea = document.getElementById('uploadArea') || document.querySelector('.csv-upload-area');
            const fileInput = document.getElementById('csvFile');
            
            if (uploadArea && fileInput) {
                console.log('Upload area and file input found');
                
                // Prevent default drag behaviors
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    uploadArea.addEventListener(eventName, preventDefaults, false);
                    document.body.addEventListener(eventName, preventDefaults, false);
                });
                
                // Highlight drop area when item is dragged over it
                ['dragenter', 'dragover'].forEach(eventName => {
                    uploadArea.addEventListener(eventName, highlight, false);
                });
                
                ['dragleave', 'drop'].forEach(eventName => {
                    uploadArea.addEventListener(eventName, unhighlight, false);
                });
                
                // Handle dropped files
                uploadArea.addEventListener('drop', handleDrop, false);
                
                // Handle click on upload area to trigger file selection
                uploadArea.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Create a temporary visible input (this method works)
                    const tempInput = document.createElement('input');
                    tempInput.type = 'file';
                    tempInput.accept = '.csv';
                    tempInput.style.position = 'absolute';
                    tempInput.style.left = '-9999px';
                    document.body.appendChild(tempInput);
                    
                    tempInput.addEventListener('change', function(e) {
                        if (e.target.files[0]) {
                            // Copy the file to the main input
                            const dt = new DataTransfer();
                            dt.items.add(e.target.files[0]);
                            fileInput.files = dt.files;
                            handleFileSelect(fileInput);
                        }
                        document.body.removeChild(tempInput);
                    });
                    
                    tempInput.click();
                });
                
                // Handle file input change (only once)
                fileInput.addEventListener('change', function(e) {
                    console.log('File input changed');
                    handleFileSelect(this);
                });
                
                // Make sure upload area is not focusable
                uploadArea.setAttribute('tabindex', '-1');
                

            } else {
                console.error('Upload area or file input not found');
                console.log('Upload area:', uploadArea);
                console.log('File input:', fileInput);
            }
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            function highlight(e) {
                uploadArea.classList.add('dragover');
            }
            
            function unhighlight(e) {
                uploadArea.classList.remove('dragover');
            }
            
            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                
                if (files.length > 0) {
                    console.log('File dropped:', files[0].name);
                    fileInput.files = files;
                    handleFileSelect(fileInput);
                }
            }
        });



        // Location Dropdown Functions
        function toggleEventLocationDropdown() {
            const dropdown = document.getElementById('event-location-dropdown');
            const arrow = document.querySelector('.select-header .dropdown-arrow');
            
            if (dropdown && arrow) {
                dropdown.classList.toggle('active');
                arrow.classList.toggle('active');
            }
        }

        function selectEventLocation(value, text) {
            console.log('selectEventLocation called with value:', value, 'text:', text);
            console.log('Value type:', typeof value, 'Value length:', value ? value.length : 0);
            
            const selectedOption = document.getElementById('selected-event-location');
            const dropdownContent = document.getElementById('event-location-dropdown');
            const dropdownArrow = document.querySelector('.select-header .dropdown-arrow');
            const hiddenInput = document.getElementById('eventLocation');
            
            console.log('Found elements:', {
                selectedOption: !!selectedOption,
                dropdownContent: !!dropdownContent,
                dropdownArrow: !!dropdownArrow,
                hiddenInput: !!hiddenInput
            });
            
            if (selectedOption && dropdownContent && dropdownArrow && hiddenInput) {
                selectedOption.textContent = text;
                hiddenInput.value = value;
                
                console.log('Set hidden input value to:', hiddenInput.value);
                console.log('Hidden input value after setting:', document.getElementById('eventLocation').value);
                
                dropdownContent.classList.remove('active');
                dropdownArrow.classList.remove('active');
                
                // Update selected state
                document.querySelectorAll('#event-location-dropdown .option-item').forEach(item => {
                    item.classList.remove('selected');
                });
                
                const clickedItem = document.querySelector(`#event-location-dropdown [data-value="${value}"]`);
                if (clickedItem) {
                    clickedItem.classList.add('selected');
                }
                
                // Show location preview
                showLocationPreview(value, text);
            } else {
                console.error('Some required elements not found for location selection');
            }
        }
        
        // Function to show location preview
        function showLocationPreview(locationValue, locationText) {
            const previewDiv = document.getElementById('locationPreview');
            const contentDiv = document.getElementById('locationPreviewContent');
            
            if (!previewDiv || !contentDiv) return;
            
            // Show loading state
            previewDiv.style.display = 'block';
            contentDiv.innerHTML = '<div style="text-align: center; opacity: 0.7;">🔄 Loading location preview...</div>';
            
            // Fetch users for this location
            fetch('event.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'action=get_location_preview&location=' + encodeURIComponent(locationValue)
            })
            .then(response => response.text())
            .then(data => {
                try {
                    const result = JSON.parse(data);
                    if (result.success) {
                        displayLocationPreview(result.users, locationText);
                    } else {
                        contentDiv.innerHTML = '<div style="text-align: center; color: var(--color-danger);">❌ Error loading preview: ' + (result.message || 'Unknown error') + '</div>';
                    }
                } catch (e) {
                    contentDiv.innerHTML = '<div style="text-align: center; color: var(--color-danger);">❌ Error parsing response</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                contentDiv.innerHTML = '<div style="text-align: center; color: var(--color-danger);">❌ Error loading preview</div>';
            });
        }
        
        // Function to display location preview
        function displayLocationPreview(users, locationText) {
            const contentDiv = document.getElementById('locationPreviewContent');
            
            if (!users || users.length === 0) {
                contentDiv.innerHTML = `
                    <div style="text-align: center; opacity: 0.7;">
                        <div style="margin-bottom: 10px;"><strong>${locationText}</strong></div>
                        <div>No users found for this location</div>
                        <div style="font-size: 12px; margin-top: 5px;">Users need to complete screening to appear here</div>
                    </div>
                `;
                return;
            }
            
            let html = `
                <div style="margin-bottom: 15px;">
                    <div style="font-weight: bold; margin-bottom: 5px;"><strong>${locationText}</strong></div>
                    <div style="font-size: 14px; opacity: 0.8;">${users.length} user(s) would receive notifications</div>
                </div>
                <div style="max-height: 200px; overflow-y: auto;">
            `;
            
            users.forEach(user => {
                const hasToken = user.fcm_token && user.is_active;
                const statusIcon = hasToken ? '✓' : '!';
                const statusColor = hasToken ? 'var(--color-highlight)' : 'var(--color-warning)';
                
                html += `
                    <div style="
                        padding: 8px; 
                        margin-bottom: 8px; 
                        background: rgba(161, 180, 84, 0.05); 
                        border-radius: 6px; 
                        border-left: 3px solid ${statusColor};
                        font-size: 12px;
                    ">
                        <div style="font-weight: bold; margin-bottom: 4px;">
                            ${statusIcon} ${user.user_email}
                        </div>
                        <div style="opacity: 0.8;">
                            ${user.barangay} | ${hasToken ? 'FCM Token Active' : 'No FCM Token'}
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            contentDiv.innerHTML = html;
        }
        
        // Function to debug FCM status
        function debugFCMStatus() {
            const button = event.target;
            const originalText = button.innerHTML;
            const debugInfoDiv = document.getElementById('fcmDebugInfo');
            const debugContentDiv = debugInfoDiv.querySelector('div');
            
            // Show loading state
            button.disabled = true;
            button.innerHTML = '🔄 Loading...';
            debugInfoDiv.style.display = 'block';
            debugContentDiv.innerHTML = 'Loading debug information...';
            
            // Fetch debug info
            fetch('event.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'action=debug_fcm'
            })
            .then(response => response.text())
            .then(data => {
                try {
                    const result = JSON.parse(data);
                    if (result.success) {
                        const debugInfo = result.debug_info;
                        let debugText = '🔍 FCM Debug Information\n';
                        debugText += '=' .repeat(40) + '\n\n';
                        
                        debugText += `📊 Database Tables:\n`;
                                debugText += `  • notification_logs: ${debugInfo.notification_logs_table_exists ? 'EXISTS' : 'MISSING'}\n`;
        debugText += `  • fcm_tokens: ${debugInfo.fcm_tokens_table_exists ? 'EXISTS' : 'MISSING'}\n`;
        debugText += `  • user_preferences: ${debugInfo.user_preferences_table_exists ? 'EXISTS' : 'MISSING'}\n\n`;
                        
                        if (debugInfo.fcm_tokens_table_exists) {
                            debugText += `FCM Tokens:\n`;
                            debugText += `  • Active tokens: ${debugInfo.active_fcm_tokens}\n\n`;
                        }
                        
                        if (debugInfo.user_preferences_table_exists) {
                            debugText += `👥 User Preferences:\n`;
                            debugText += `  • Users with barangay: ${debugInfo.users_with_barangay}\n\n`;
                        }
                        
                        debugText += `🔥 Firebase Admin SDK:\n`;
                        debugText += `  • File exists: ${debugInfo.firebase_admin_sdk_exists ? 'YES' : 'NO'}\n`;
                        debugText += `  • Path: ${debugInfo.firebase_admin_sdk_path}\n\n`;
                        
                        debugText += `⏰ Timestamp: ${result.timestamp}\n`;
                        
                        debugContentDiv.innerHTML = debugText;
                    } else {
                        debugContentDiv.innerHTML = `❌ Error: ${result.message || 'Unknown error'}`;
                    }
                } catch (e) {
                    debugContentDiv.innerHTML = `❌ Error parsing response: ${data.substring(0, 200)}...`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                debugContentDiv.innerHTML = `❌ Error: ${error.message}`;
            })
            .finally(() => {
                // Reset button
                button.disabled = false;
                button.innerHTML = originalText;
            });
        }

        function filterEventLocationOptions() {
            const searchInput = document.getElementById('event-location-search');
            const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
            const optionItems = document.querySelectorAll('#event-location-dropdown .option-item');
            
            optionItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Edit Modal Location Dropdown Functions
        function toggleEditEventLocationDropdown() {
            const dropdown = document.getElementById('edit-event-location-dropdown');
            const arrow = document.querySelector('#edit-event-location-dropdown').closest('.custom-select-container').querySelector('.dropdown-arrow');
            
            if (dropdown && arrow) {
                dropdown.classList.toggle('active');
                arrow.classList.toggle('active');
            }
        }

        function selectEditEventLocation(value, text) {
            const selectedOption = document.getElementById('selected-edit-event-location');
            const dropdownContent = document.getElementById('edit-event-location-dropdown');
            const dropdownArrow = document.querySelector('#edit-event-location-dropdown').closest('.custom-select-container').querySelector('.dropdown-arrow');
            const hiddenInput = document.getElementById('edit_eventLocation');
            
            if (selectedOption && dropdownContent && dropdownArrow && hiddenInput) {
                selectedOption.textContent = text;
                hiddenInput.value = value;
                dropdownContent.classList.remove('active');
                dropdownArrow.classList.remove('active');
                
                // Update selected state
                document.querySelectorAll('#edit-event-location-dropdown .option-item').forEach(item => {
                    item.classList.remove('selected');
                });
                
                const clickedItem = document.querySelector(`#edit-event-location-dropdown [data-value="${value}"]`);
                if (clickedItem) {
                    clickedItem.classList.add('selected');
                }
            }
        }

        function filterEditEventLocationOptions() {
            const searchInput = document.getElementById('edit-event-location-search');
            const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
            const optionItems = document.querySelectorAll('#edit-event-location-dropdown .option-item');
            
            optionItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('event-location-dropdown');
            const selectHeader = document.querySelector('.select-header');
            
            if (dropdown && !selectHeader.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.classList.remove('active');
                const arrow = document.querySelector('.select-header .dropdown-arrow');
                if (arrow) arrow.classList.remove('active');
            }
        });

        // Function to handle AJAX CSV upload
        function uploadCSVWithAjax() {
            const fileInput = document.getElementById('csvFile');
            const file = fileInput.files[0];
            
            if (!file) {
                alert('Please select a CSV file first.');
                return;
            }
            
            const formData = new FormData();
            formData.append('csvFile', file);
            
            // Show loading state
            const importBtn = document.getElementById('importBtn');
            const importStatus = document.getElementById('importStatus');
            const originalText = importBtn.innerHTML;
            
            importBtn.disabled = true;
            importBtn.innerHTML = '🔄 Uploading...';
            importStatus.style.display = 'block';
            
            fetch('https://nutrisaur-production.up.railway.app/unified_api.php?endpoint=import_csv', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Upload response received:', data);
                if (data.success) {
                    alert('Successfully imported ' + data.imported_count + ' events!');
                    if (data.errors && data.errors.length > 0) {
                        console.log('Import errors:', data.errors);
                    }
                    location.reload(); // Refresh to show new events
                } else {
                    alert('Upload failed: ' + (data.error || 'Unknown error'));
                    importBtn.disabled = false;
                    importBtn.innerHTML = originalText;
                    importStatus.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Upload error:', error);
                alert('Upload failed. Please try again.');
                importBtn.disabled = false;
                importBtn.innerHTML = originalText;
                importStatus.style.display = 'none';
            });
        }
        
        // Real-time notification system
        let notificationCheckInterval;
        let lastNotificationCount = 0;
        
        function initializeNotifications() {
            // Check for notifications every 30 seconds
            notificationCheckInterval = setInterval(checkForNewNotifications, 30000);
            
            // Initial check
            checkForNewNotifications();
        }
        
        function checkForNewNotifications() {
            const currentUserEmail = '<?php echo htmlspecialchars($email); ?>';
            
            if (!currentUserEmail) {
                console.log('No user email available for notification check');
                return;
            }
            
            fetch('https://nutrisaur-production.up.railway.app/unified_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    action: 'get_user_notifications',
                    user_email: currentUserEmail,
                    limit: 5
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const unreadCount = data.unread_count || 0;
                    
                    // Show notification badge if there are unread notifications
                    updateNotificationBadge(unreadCount);
                    
                    // Show toast notification for new notifications
                    if (unreadCount > lastNotificationCount) {
                        showNotificationToast(data.notifications[0]);
                    }
                    
                    lastNotificationCount = unreadCount;
                }
            })
            .catch(error => {
                console.error('Error checking notifications:', error);
            });
        }
        
        function updateNotificationBadge(count) {
            // Create or update notification badge
            let badge = document.getElementById('notification-badge');
            
            if (!badge) {
                badge = document.createElement('div');
                badge.id = 'notification-badge';
                badge.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: #ff4444;
                    color: white;
                    border-radius: 50%;
                    width: 24px;
                    height: 24px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 12px;
                    font-weight: bold;
                    z-index: 1000;
                    cursor: pointer;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                `;
                document.body.appendChild(badge);
                
                badge.addEventListener('click', showNotificationPanel);
            }
            
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }
        
        function showNotificationToast(notification) {
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed;
                top: 80px;
                right: 20px;
                background: var(--color-card);
                color: var(--color-text);
                padding: 15px 20px;
                border-radius: 10px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                z-index: 1001;
                max-width: 300px;
                border-left: 4px solid var(--color-highlight);
                animation: slideInRight 0.3s ease;
            `;
            
            toast.innerHTML = `
                <div style="font-weight: bold; margin-bottom: 5px;">📢 New Event Notification</div>
                <div style="font-size: 14px;">${notification.title}</div>
                <div style="font-size: 12px; opacity: 0.8; margin-top: 5px;">
                    ${new Date(notification.date_time).toLocaleDateString()}
                </div>
            `;
            
            document.body.appendChild(toast);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.style.animation = 'slideOutRight 0.3s ease';
                    setTimeout(() => {
                        if (toast.parentNode) {
                            toast.parentNode.removeChild(toast);
                        }
                    }, 300);
                }
            }, 5000);
        }
        
        function showNotificationPanel() {
            const currentUserEmail = '<?php echo htmlspecialchars($email); ?>';
            
            fetch('https://nutrisaur-production.up.railway.app/unified_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    action: 'get_user_notifications',
                    user_email: currentUserEmail,
                    limit: 10
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayNotificationPanel(data.notifications);
                }
            })
            .catch(error => {
                console.error('Error fetching notifications:', error);
            });
        }
        
        function displayNotificationPanel(notifications) {
            // Remove existing panel
            const existingPanel = document.getElementById('notification-panel');
            if (existingPanel) {
                existingPanel.remove();
            }
            
            const panel = document.createElement('div');
            panel.id = 'notification-panel';
            panel.style.cssText = `
                position: fixed;
                top: 60px;
                right: 20px;
                background: var(--color-card);
                color: var(--color-text);
                border-radius: 10px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                z-index: 1002;
                max-width: 400px;
                max-height: 500px;
                overflow-y: auto;
                padding: 20px;
            `;
            
            let notificationsHtml = '<h3 style="margin-bottom: 15px; color: var(--color-highlight);">📢 Notifications</h3>';
            
            if (notifications.length === 0) {
                notificationsHtml += '<p style="opacity: 0.7;">No notifications</p>';
            } else {
                notifications.forEach(notification => {
                    const isUnread = notification.status === 'pending';
                    notificationsHtml += `
                        <div style="
                            padding: 10px;
                            margin-bottom: 10px;
                            border-radius: 8px;
                            background: ${isUnread ? 'rgba(161, 180, 84, 0.1)' : 'transparent'};
                            border-left: 3px solid ${isUnread ? 'var(--color-highlight)' : 'transparent'};
                        ">
                            <div style="font-weight: bold; margin-bottom: 5px;">${notification.title}</div>
                            <div style="font-size: 12px; opacity: 0.8; margin-bottom: 5px;">
                                ${notification.location} | ${new Date(notification.date_time).toLocaleDateString()}
                            </div>
                            <div style="font-size: 12px; opacity: 0.7;">
                                ${notification.type} • ${notification.recipient_group}
                            </div>
                            ${isUnread ? '<div style="font-size: 10px; color: var(--color-highlight); margin-top: 5px;">● New</div>' : ''}
                        </div>
                    `;
                });
            }
            
            panel.innerHTML = notificationsHtml;
            document.body.appendChild(panel);
            
            // Close panel when clicking outside
            setTimeout(() => {
                document.addEventListener('click', function closePanel(e) {
                    if (!panel.contains(e.target) && !document.getElementById('notification-badge').contains(e.target)) {
                        panel.remove();
                        document.removeEventListener('click', closePanel);
                    }
                });
            }, 100);
        }
        
        // Initialize notifications when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializeNotifications();
            
            // Check notification status on page load
            checkNotificationStatus();
            
            // Load notification statistics
            loadNotificationStats();
        });
        
        // Function to load notification statistics
        function loadNotificationStats() {
            // Check if stats elements exist before trying to load
            const statsElements = ['total-tokens', 'active-tokens', 'total-notifications', 'success-rate'];
            const elementsExist = statsElements.every(id => document.getElementById(id));
            
            if (!elementsExist) {
                console.log('Notification stats elements not found, skipping stats load');
                return;
            }
            
            fetch('api/get_notification_stats.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.text();
                })
                .then(data => {
                    try {
                        const jsonData = JSON.parse(data);
                        if (jsonData.success) {
                            // Update stats elements
                            const totalTokensEl = document.getElementById('total-tokens');
                            const activeTokensEl = document.getElementById('active-tokens');
                            const totalNotificationsEl = document.getElementById('total-notifications');
                            const successRateEl = document.getElementById('success-rate');
                            
                            if (totalTokensEl) totalTokensEl.textContent = jsonData.stats.total_tokens || 0;
                            if (activeTokensEl) activeTokensEl.textContent = jsonData.stats.active_tokens || 0;
                            if (totalNotificationsEl) totalNotificationsEl.textContent = jsonData.stats.total_notifications || 0;
                            if (successRateEl) successRateEl.textContent = (jsonData.stats.success_rate || 0) + '%';
                            
                            // Load recent logs
                            loadRecentLogs();
                        } else {
                            console.warn('Notification stats API returned success=false:', jsonData.message);
                            showStatsError('API Error: ' + jsonData.message);
                        }
                    } catch (parseError) {
                        console.error('Error parsing notification stats JSON:', parseError);
                        console.log('Raw response:', data.substring(0, 500));
                        showStatsError('JSON Parse Error');
                    }
                })
                .catch(error => {
                    console.error('Error loading notification stats:', error);
                    showStatsError('Network Error');
                });
        }
        
        // Function to show stats error
        function showStatsError(message) {
            const statsElements = ['total-tokens', 'active-tokens', 'total-notifications', 'success-rate'];
            statsElements.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.textContent = 'Error';
            });
            
            // Show error message in console
            console.error('Stats loading failed:', message);
        }
        
        // Function to refresh notification statistics
        function refreshNotificationStats() {
            document.getElementById('total-tokens').textContent = '...';
            document.getElementById('active-tokens').textContent = '...';
            document.getElementById('total-notifications').textContent = '...';
            document.getElementById('success-rate').textContent = '...';
            
            loadNotificationStats();
        }
        
        // Function to send test notification
        function sendTestNotification() {
            const button = event.target;
            const originalText = button.innerHTML;
            
            button.disabled = true;
            button.innerHTML = '🔄 Sending...';
            
            fetch('event.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'send_test_notification=1'
            })
            .then(response => response.text())
            .then(data => {
                try {
                    const result = JSON.parse(data);
                    if (result.success) {
                        showAlert('success', 'Test notification sent successfully! Check your phone.');
                    } else {
                        showAlert('danger', 'Test notification failed: ' + result.message);
                    }
                } catch (e) {
                    if (data.includes('success')) {
                        showAlert('success', 'Test notification sent successfully! Check your phone.');
                    } else {
                        showAlert('danger', 'Test notification failed. Check server logs for details.');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'Error sending test notification. Please try again.');
            })
            .finally(() => {
                button.disabled = false;
                button.innerHTML = originalText;
                
                // Refresh stats after test
                setTimeout(() => {
                    refreshNotificationStats();
                }, 2000);
            });
        }
        
        // Function to load recent notification logs
        function loadRecentLogs() {
            fetch('api/get_recent_notification_logs.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const logsContainer = document.getElementById('recent-logs');
                        let logsHtml = '';
                        
                        if (data.logs.length === 0) {
                            logsHtml = '<div style="text-align: center; opacity: 0.7;">No notification logs found</div>';
                        } else {
                            data.logs.forEach(log => {
                                const statusIcon = log.success ? '✓' : '✗';
                                const statusColor = log.success ? 'var(--color-highlight)' : 'var(--color-danger)';
                                
                                logsHtml += `
                                    <div style="padding: 8px; margin-bottom: 8px; background: rgba(161, 180, 84, 0.05); border-radius: 6px; border-left: 3px solid ${statusColor};">
                                        <div style="font-size: 12px; font-weight: bold; margin-bottom: 4px;">
                                            ${statusIcon} ${log.notification_type} (${log.target_type})
                                        </div>
                                        <div style="font-size: 11px; opacity: 0.8;">
                                            Target: ${log.target_value || 'all'} | Tokens: ${log.tokens_sent} | ${new Date(log.created_at).toLocaleString()}
                                        </div>
                                    </div>
                                `;
                            });
                        }
                        
                        logsContainer.innerHTML = logsHtml;
                    }
                })
                .catch(error => {
                    console.error('Error loading recent logs:', error);
                });
        }
        
        // Function to check notification status
        function checkNotificationStatus() {
            const statusCheck = document.querySelector('.notification-status-check');
            if (statusCheck) {
                // Add a refresh button
                const refreshBtn = document.createElement('button');
                refreshBtn.innerHTML = 'Refresh Status';
                refreshBtn.style.cssText = `
                    background: var(--color-highlight);
                    color: white;
                    border: none;
                    padding: 8px 15px;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 14px;
                    margin-top: 10px;
                `;
                refreshBtn.onclick = function() {
                    window.location.reload();
                };
                statusCheck.appendChild(refreshBtn);
            }
        }
        

        
        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
        
        // Edit Modal Functions
        function openEditModal(programId, title, type, description, dateTime, location, organizer) {
            // Set form values
            document.getElementById('edit_program_id').value = programId;
            document.getElementById('edit_eventTitle').value = title;
            document.getElementById('edit_eventType').value = type;
            document.getElementById('edit_eventDescription').value = description;
            
            // Set location dropdown value with improved matching
            const editLocationInput = document.getElementById('edit_eventLocation');
            const selectedEditLocation = document.getElementById('selected-edit-event-location');
            if (editLocationInput && selectedEditLocation && location) {
                editLocationInput.value = location;
                
                // Find the exact match in the edit dropdown options
                let foundMatch = false;
                const editOptionItems = document.querySelectorAll('#edit-event-location-dropdown .option-item');
                
                editOptionItems.forEach(item => {
                    if (item.getAttribute('data-value') === location) {
                        // Found exact match - update display and mark as selected
                        selectedEditLocation.textContent = item.textContent;
                        console.log('Edit modal: Found exact match, updated dropdown display to:', item.textContent);
                        
                        // Mark this option as selected
                        editOptionItems.forEach(opt => opt.classList.remove('selected'));
                        item.classList.add('selected');
                        foundMatch = true;
                    }
                });
                
                // If no exact match found, try to find a close match
                if (!foundMatch) {
                    console.log('Edit modal: No exact match found, looking for close match...');
                    editOptionItems.forEach(item => {
                        const itemText = item.textContent.toLowerCase();
                        const locationLower = location.toLowerCase();
                        
                        // Check if the location is contained in the option text
                        if (itemText.includes(locationLower) || locationLower.includes(itemText)) {
                            selectedEditLocation.textContent = item.textContent;
                            console.log('Edit modal: Found close match, updated dropdown display to:', item.textContent);
                            
                            // Mark this option as selected
                            editOptionItems.forEach(opt => opt.classList.remove('selected'));
                            item.classList.add('selected');
                            foundMatch = true;
                        }
                    });
                }
                
                // If still no match, just use the location as is
                if (!foundMatch) {
                    selectedEditLocation.textContent = location;
                    console.log('Edit modal: No match found, using location as is:', location);
                }
            } else if (selectedEditLocation) {
                selectedEditLocation.textContent = 'All Locations';
            }
            
            document.getElementById('edit_eventOrganizer').value = organizer;
            
            // Convert date format for datetime-local input
            const date = new Date(dateTime);
            const formattedDate = date.toISOString().slice(0, 16);
            document.getElementById('edit_eventDate').value = formattedDate;
            
            // Show modal
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        function closeDuplicateWarningModal() {
            document.getElementById('duplicateWarningModal').style.display = 'none';
        }
        
        function showDuplicateWarningModal(duplicates) {
            const modal = document.getElementById('duplicateWarningModal');
            const duplicateList = document.getElementById('duplicateList');
            
            if (modal && duplicateList) {
                let html = '';
                duplicates.forEach(duplicate => {
                    html += `
                        <div style="
                            padding: 12px; 
                            margin-bottom: 10px; 
                            background: rgba(233, 141, 124, 0.1); 
                            border-radius: 8px; 
                            border-left: 3px solid var(--color-warning);
                        ">
                            <div style="font-weight: bold; margin-bottom: 5px;">
                                Row ${duplicate.row}: ${duplicate.title}
                            </div>
                            <div style="font-size: 12px; opacity: 0.8;">
                                Date: ${duplicate.date_time} | Location: ${duplicate.location}
                            </div>
                        </div>
                    `;
                });
                
                duplicateList.innerHTML = html;
                modal.style.display = 'block';
            }
        }
        
        function showDuplicateWarningModalFromSession() {
            // Fetch duplicate data from PHP session via AJAX
            fetch('event.php?action=get_duplicates', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.duplicates) {
                    showDuplicateWarningModal(data.duplicates);
                } else {
                    alert('No duplicate information available.');
                }
            })
            .catch(error => {
                console.error('Error fetching duplicate data:', error);
                alert('Error loading duplicate information.');
            });
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                closeEditModal();
            }
        }
    </script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Event.php page loaded, checking for program recommendations...');
        
        // Check if program recommendation was passed in URL
        const urlParams = new URLSearchParams(window.location.search);
        const recommendedProgram = urlParams.get('program');
        const programType = urlParams.get('type');
        const programLocation = urlParams.get('location');
        const programDescription = urlParams.get('description');
        
        // Debug logging
        console.log('URL Parameters received:', {
            program: recommendedProgram,
            type: programType,
            location: programLocation,
            description: programDescription
        });
        
        // Additional location debugging
        if (programLocation) {
            console.log('Location parameter found:', programLocation);
            console.log('Location parameter type:', typeof programLocation);
            console.log('Location parameter length:', programLocation.length);
            console.log('Location parameter encoded:', encodeURIComponent(programLocation));
        } else {
            console.log('No location parameter found in URL');
        }
        
        // If program recommendation exists, pre-fill the form fields
        if (recommendedProgram) {
            console.log('Program recommendation detected:', recommendedProgram);
            
            // Add a small delay to ensure DOM is fully loaded
            setTimeout(() => {
                // Check if all required form elements exist
                const titleInput = document.getElementById('eventTitle');
                const typeInput = document.getElementById('eventType');
                const locationInput = document.getElementById('eventLocation');
                const descriptionInput = document.getElementById('eventDescription');
                const dateInput = document.getElementById('eventDate');
                const organizerInput = document.getElementById('eventOrganizer');
                
                // Log which elements were found
                console.log('Form elements found:', {
                    titleInput: !!titleInput,
                    typeInput: !!typeInput,
                    locationInput: !!locationInput,
                    descriptionInput: !!descriptionInput,
                    dateInput: !!dateInput,
                    organizerInput: !!organizerInput
                });
                
                // Pre-fill the form fields with the recommendation data
                if (titleInput) {
                    titleInput.value = recommendedProgram;
                    console.log('Set title to:', recommendedProgram);
                } else {
                    console.error('Title input element not found');
                }
                
                if (typeInput && programType) {
                    typeInput.value = programType;
                    console.log('Set type to:', programType);
                } else if (programType) {
                    console.error('Type input element not found or programType missing');
                }
                
                if (locationInput && programLocation) {
                    locationInput.value = programLocation;
                    console.log('Set location to:', programLocation);
                    
                    // Also update the dropdown display to show the selected location
                    const selectedLocationDisplay = document.getElementById('selected-event-location');
                    if (selectedLocationDisplay) {
                        // Find the exact match in the dropdown options
                        let foundMatch = false;
                        const optionItems = document.querySelectorAll('#event-location-dropdown .option-item');
                        
                        optionItems.forEach(item => {
                            if (item.getAttribute('data-value') === programLocation) {
                                // Found exact match - update display and mark as selected
                                selectedLocationDisplay.textContent = item.textContent;
                                console.log('Found exact match, updated dropdown display to:', item.textContent);
                                
                                // Mark this option as selected
                                optionItems.forEach(opt => opt.classList.remove('selected'));
                                item.classList.add('selected');
                                foundMatch = true;
                            }
                        });
                        
                        // If no exact match found, try to find a close match
                        if (!foundMatch) {
                            console.log('No exact match found, looking for close match...');
                            optionItems.forEach(item => {
                                const itemText = item.textContent.toLowerCase();
                                const programLocationLower = programLocation.toLowerCase();
                                
                                // Check if the program location is contained in the option text
                                if (itemText.includes(programLocationLower) || programLocationLower.includes(itemText)) {
                                    selectedLocationDisplay.textContent = item.textContent;
                                    console.log('Found close match, updated dropdown display to:', item.textContent);
                                    
                                    // Mark this option as selected
                                    optionItems.forEach(opt => opt.classList.remove('selected'));
                                    item.classList.add('selected');
                                    foundMatch = true;
                                }
                            });
                        }
                        
                        // If still no match, just use the program location as is
                        if (!foundMatch) {
                            selectedLocationDisplay.textContent = programLocation;
                            console.log('No match found, using program location as is:', programLocation);
                        }
                    }
                } else if (programLocation) {
                    console.error('Location input element not found or programLocation missing');
                }
                
                if (descriptionInput && programDescription) {
                    descriptionInput.value = programDescription;
                    console.log('Set description to:', programDescription);
                } else if (programDescription) {
                    console.error('Description input element not found or programDescription missing');
                }
                
                // Set a default date (7 days from now) if not specified
                if (dateInput) {
                    const futureDate = new Date();
                    futureDate.setDate(futureDate.getDate() + 7);
                    const formattedDate = futureDate.toISOString().slice(0, 16);
                    dateInput.value = formattedDate;
                    console.log('Set default date to:', formattedDate);
                } else {
                    console.error('Date input element not found');
                }
                
                // Set default organizer if not specified
                if (organizerInput && !organizerInput.value) {
                    organizerInput.value = '<?php echo htmlspecialchars($username); ?>';
                    console.log('Set default organizer to:', '<?php echo htmlspecialchars($username); ?>');
                } else if (!organizerInput) {
                    console.error('Organizer input element not found');
                }
                
                // Show success message
                try {
                    showAlert('success', `Program recommendation loaded: ${recommendedProgram}`);
                    console.log('Success alert shown');
                } catch (error) {
                    console.error('Error showing alert:', error);
                }
                
                // Scroll to the form
                const formContainer = document.querySelector('.event-container');
                if (formContainer) {
                    formContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    console.log('Scrolled to form container');
                } else {
                    console.error('Form container not found');
                }
            }, 100); // 100ms delay
        }
        
        // Initialize training groups if they exist
        const trainingHeaders = document.querySelectorAll('.training-group-header');
        if (trainingHeaders.length > 0) {
            trainingHeaders.forEach(header => {
                header.addEventListener('click', () => {
                    const group = header.parentElement;
                    group.classList.toggle('open');
                });
            });
        }
    });



        // Function to filter events
        function filterEvents(filter) {
            const rows = document.querySelectorAll('.event-row');
            
            rows.forEach(row => {
                if (filter === 'all') {
                    row.style.display = '';
                } else if (filter === 'upcoming' && row.classList.contains('upcoming')) {
                    row.style.display = '';
                } else if (filter === 'past' && row.classList.contains('past')) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Function to confirm delete all events
        function confirmDeleteAll() {
            if (confirm('WARNING: This will delete ALL events permanently!\n\nAre you absolutely sure you want to continue?')) {
                if (confirm('FINAL WARNING: This action cannot be undone!\n\nClick OK to delete ALL events.')) {
                    window.location.href = 'event.php?delete_all=1';
                }
            }
        }

        // Function to send notifications
        function sendNotifications(eventId) {
            const recipientGroup = document.getElementById('recipientGroup').value;
            const notificationType = document.getElementById('notificationType').value;
            
            const formData = new FormData();
            formData.append('action', 'send');
            formData.append('event_id', eventId);
            formData.append('recipient_group', recipientGroup);
            formData.append('notification_type', notificationType);
            
            fetch('api/manage_notifications.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Notifications sent successfully!');
                } else {
                    showAlert('danger', 'Error sending notifications: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'Error sending notifications. Please try again.');
            });
        }

        // Form validation function
        function validateEventForm() {
            const titleInput = document.getElementById('eventTitle');
            const typeInput = document.getElementById('eventType');
            const descriptionInput = document.getElementById('eventDescription');
            const dateInput = document.getElementById('eventDate');
            const locationInput = document.getElementById('eventLocation');
            const organizerInput = document.getElementById('eventOrganizer');
            
            console.log('Form validation - Form values:', {
                title: titleInput?.value,
                type: typeInput?.value,
                description: descriptionInput?.value,
                date: dateInput?.value,
                location: locationInput?.value,
                organizer: organizerInput?.value
            });
            
            // Check required fields (location is optional - empty means "All Locations")
            if (!titleInput.value.trim()) {
                showAlert('danger', 'Please enter an event title.');
                return false;
            }
            
            if (!typeInput.value.trim()) {
                showAlert('danger', 'Please select an event type.');
                return false;
            }
            
            if (!descriptionInput.value.trim()) {
                showAlert('danger', 'Please enter an event description.');
                return false;
            }
            
            if (!dateInput.value) {
                showAlert('danger', 'Please select an event date and time.');
                return false;
            }
            
            if (!organizerInput.value.trim()) {
                showAlert('danger', 'Please enter an event organizer.');
                return false;
            }
            
            // Location is optional - empty string means "All Locations"
            // No validation needed for location
            console.log('Form validation passed - Location value:', locationInput?.value);
            
            return true;
        }

        // Function to show alert message
        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.textContent = message;
            
            const container = document.querySelector('.event-container');
            const existingAlert = container.querySelector('.alert');
            if (existingAlert) {
                container.removeChild(existingAlert);
            }
            
            container.insertBefore(alertDiv, container.firstChild);
            
            // Auto-hide the alert after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 5000);
        }

        // Function to fetch events
        function fetchEvents() {
            fetch('api/get_events.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateEventsTable(data.data);
                } else {
                    showAlert('danger', 'Error fetching events: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'Error fetching events. Please try again.');
            });
        }

        // Function to update the events table
        function updateEventsTable(events = null) {
            if (!events) return;
            
            const tbody = document.getElementById('eventsTableBody');
            tbody.innerHTML = '';
            
            const filter = document.getElementById('eventFilter')?.value || 'all';
            
            if (events.length === 0) {
                const row = document.createElement('tr');
                row.innerHTML = '<td colspan="7" class="no-events">No events found. Create your first event!</td>';
                tbody.appendChild(row);
                return;
            }
            
            events.forEach(event => {
                if (filter !== 'all' && filter !== event.status) return;
                
                const row = document.createElement('tr');
                row.className = `event-row ${event.status}`;
                
                // Format date
                const date = new Date(event.date_time);
                const formattedDate = date.toLocaleDateString('en-US', { 
                    month: 'short', 
                    day: 'numeric', 
                    year: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });
                
                row.innerHTML = `
                    <td>${event.title}</td>
                    <td>${event.type}</td>
                    <td>${formattedDate}</td>
                    <td>${event.location}</td>
                    <td>${event.organizer}</td>
                    <td><span class="status-badge status-${event.status}">${event.status.charAt(0).toUpperCase() + event.status.slice(1)}</span></td>
                    <td>
                        <div class="action-buttons">
                            <a href="edit_program.php?id=${event.program_id}" class="btn btn-add">Edit</a>
                            <a href="event.php?delete=${event.program_id}" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this event?')">Delete</a>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }



        // CSV Upload Functions
        // Flag to prevent double file selection
        let isFileSelectionInProgress = false;
        
        function handleFileSelect(input) {
            if (isFileSelectionInProgress) {
                console.log('File selection already in progress, ignoring duplicate call');
                return;
            }
            
            isFileSelectionInProgress = true;
            
            const file = input.files[0];
            if (file) {
                console.log('File selected:', file.name);
                
                // Also set the file in the main file input for form submission
                const mainFileInput = document.getElementById('csvFile');
                if (mainFileInput && input !== mainFileInput) {
                    // Create a new FileList-like object
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    mainFileInput.files = dt.files;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        const csv = e.target.result;
                        const lines = csv.split('\n');
                        const headers = lines[0].split(',');
                        const previewData = lines.slice(1, 6); // First 5 data rows
                        
                        showCSVPreview(headers, previewData);
                        document.getElementById('importBtn').disabled = false;
                        document.getElementById('cancelBtn').style.display = 'flex';
                    } catch (error) {
                        console.error('Error processing CSV file:', error);
                        alert('Error processing CSV file. Please check the file format.');
                    }
                };
                reader.onerror = function() {
                    console.error('Error reading file');
                    alert('Error reading file. Please try again.');
                };
                reader.readAsText(file);
            }
            
            // Reset flag after a short delay
            setTimeout(() => {
                isFileSelectionInProgress = false;
            }, 100);
        }

        function cancelUpload() {
            try {
                // Clear the file input
                const fileInput = document.getElementById('csvFile');
                if (fileInput) fileInput.value = '';
                
                // Hide the preview
                const preview = document.getElementById('csvPreview');
                if (preview) preview.style.display = 'none';
                
                // Disable import button and hide cancel button
                const importBtn = document.getElementById('importBtn');
                const cancelBtn = document.getElementById('cancelBtn');
                if (importBtn) importBtn.disabled = true;
                if (cancelBtn) cancelBtn.style.display = 'none';
                
                // Clear preview content
                const previewContent = document.getElementById('previewContent');
                if (previewContent) previewContent.innerHTML = '';
                
                console.log('Upload cancelled');
            } catch (error) {
                console.error('Error cancelling upload:', error);
            }
        }

        function showCSVPreview(headers, data) {
            const previewDiv = document.getElementById('csvPreview');
            const contentDiv = document.getElementById('previewContent');
            
            let tableHTML = '<table class="preview-table"><thead><tr>';
            headers.forEach(header => {
                tableHTML += `<th>${header.trim()}</th>`;
            });
            tableHTML += '</tr></thead><tbody>';
            
            data.forEach(row => {
                if (row.trim()) {
                    tableHTML += '<tr>';
                    const cells = row.split(',');
                    cells.forEach(cell => {
                        tableHTML += `<td>${cell.trim()}</td>`;
                    });
                    tableHTML += '</tr>';
                }
            });
            
            tableHTML += '</tbody></table>';
            contentDiv.innerHTML = tableHTML;
            previewDiv.style.display = 'block';
        }

        function downloadTemplate() {
            // Get current date and add some future dates for examples
            const now = new Date();
            const future1 = new Date(now.getTime() + (7 * 24 * 60 * 60 * 1000)); // 7 days from now
            const future2 = new Date(now.getTime() + (14 * 24 * 60 * 60 * 1000)); // 14 days from now
            const future3 = new Date(now.getTime() + (21 * 24 * 60 * 60 * 1000)); // 21 days from now
            
            const formatDate = (date) => {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                const hours = String(date.getHours()).padStart(2, '0');
                const minutes = String(date.getMinutes()).padStart(2, '0');
                return `${year}-${month}-${day} ${hours}:${minutes}`;
            };
            
            const csvContent = `title,type,description,date_time,location,organizer
Sample Event,Workshop,Sample description,${formatDate(future1)},Sample Location,Sample Organizer`;
            
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'events_template.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }

        // Improved Drag and Drop functionality
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing CSV upload...');
            
            // Form submission handling
            const csvUploadForm = document.getElementById('csvUploadForm');
            if (csvUploadForm) {
                csvUploadForm.addEventListener('submit', function(e) {
                    console.log('Form submission started');
                    const fileInput = document.getElementById('csvFile');
                    if (!fileInput.files[0]) {
                        e.preventDefault();
                        alert('Please select a CSV file first.');
                        return false;
                    }
                    
                    document.getElementById('importStatus').style.display = 'block';
                    document.getElementById('importBtn').disabled = true;
                    document.getElementById('cancelBtn').style.display = 'none';
                });
            }
            
            const uploadArea = document.getElementById('uploadArea') || document.querySelector('.csv-upload-area');
            const fileInput = document.getElementById('csvFile');
            
            if (uploadArea && fileInput) {
                console.log('Upload area and file input found');
                
                // Prevent default drag behaviors
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    uploadArea.addEventListener(eventName, preventDefaults, false);
                    document.body.addEventListener(eventName, preventDefaults, false);
                });
                
                // Highlight drop area when item is dragged over it
                ['dragenter', 'dragover'].forEach(eventName => {
                    uploadArea.addEventListener(eventName, highlight, false);
                });
                
                ['dragleave', 'drop'].forEach(eventName => {
                    uploadArea.addEventListener(eventName, unhighlight, false);
                });
                
                // Handle dropped files
                uploadArea.addEventListener('drop', handleDrop, false);
                
                // Handle click on upload area to trigger file selection
                uploadArea.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Create a temporary visible input (this method works)
                    const tempInput = document.createElement('input');
                    tempInput.type = 'file';
                    tempInput.accept = '.csv';
                    tempInput.style.position = 'absolute';
                    tempInput.style.left = '-9999px';
                    document.body.appendChild(tempInput);
                    
                    tempInput.addEventListener('change', function(e) {
                        if (e.target.files[0]) {
                            // Copy the file to the main input
                            const dt = new DataTransfer();
                            dt.items.add(e.target.files[0]);
                            fileInput.files = dt.files;
                            handleFileSelect(fileInput);
                        }
                        document.body.removeChild(tempInput);
                    });
                    
                    tempInput.click();
                });
                
                // Handle file input change (only once)
                fileInput.addEventListener('change', function(e) {
                    console.log('File input changed');
                    handleFileSelect(this);
                });
                
                // Make sure upload area is not focusable
                uploadArea.setAttribute('tabindex', '-1');
                

            } else {
                console.error('Upload area or file input not found');
                console.log('Upload area:', uploadArea);
                console.log('File input:', fileInput);
            }
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
        
        // Global variables for location notification
        let selectedNotificationLocation = '';
        let selectedNotificationLocationText = '';
        
        // Function to toggle notification location dropdown
        function toggleNotificationLocationDropdown() {
            const dropdown = document.getElementById('notification-location-dropdown');
            const arrow = document.querySelector('#notification-location-dropdown').previousElementSibling.querySelector('.dropdown-arrow');
            
            if (dropdown.classList.contains('active')) {
                dropdown.classList.remove('active');
                arrow.classList.remove('active');
            } else {
                dropdown.classList.add('active');
                arrow.classList.add('active');
            }
        }
        
        // Function to select notification location
        function selectNotificationLocation(value, text) {
            selectedNotificationLocation = value;
            selectedNotificationLocationText = text;
            
            document.getElementById('selected-notification-location').textContent = text;
            
            // Close dropdown
            toggleNotificationLocationDropdown();
            
            // Show location preview
            showNotificationLocationPreview(value);
        }
        
        // Function to filter notification location options
        function filterNotificationLocationOptions() {
            const searchInput = document.getElementById('notification-location-search');
            const searchTerm = searchInput.value.toLowerCase();
            const optionItems = document.querySelectorAll('#notification-location-dropdown .option-item');
            
            optionItems.forEach((item) => {
                const text = item.textContent.toLowerCase();
                const matches = text.includes(searchTerm);
                item.style.display = matches ? 'block' : 'none';
            });
        }
        
        // Function to show notification location preview
        async function showNotificationLocationPreview(location) {
            const previewContainer = document.getElementById('notificationLocationPreview');
            const previewContent = document.getElementById('notificationLocationPreviewContent');
            
            if (!location) {
                previewContainer.style.display = 'none';
                return;
            }
            
            try {
                // Get location preview from server
                const response = await fetch('event.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        'action': 'get_location_preview',
                        'location': location
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const users = result.users || [];
                    const userCount = users.length;
                    
                    let previewHTML = '';
                    if (userCount > 0) {
                        previewHTML = `
                            <div style="margin-bottom: 10px;">
                                <strong>${userCount} users will receive this notification:</strong>
                            </div>
                            <div style="max-height: 150px; overflow-y: auto;">
                                ${users.map(user => `
                                    <div style="padding: 5px 0; border-bottom: 1px solid rgba(161, 180, 84, 0.1);">
                                        <strong>${user.user_name || user.user_email}</strong>
                                        <br><small style="opacity: 0.7;">${user.user_email}</small>
                                    </div>
                                `).join('')}
                            </div>
                        `;
                    } else {
                        previewHTML = '<div style="text-align: center; opacity: 0.7; color: #f44336;">No users found in this location</div>';
                    }
                    
                    previewContent.innerHTML = previewHTML;
                    previewContainer.style.display = 'block';
                } else {
                    previewContent.innerHTML = '<div style="text-align: center; opacity: 0.7; color: #f44336;">Error loading location preview</div>';
                    previewContainer.style.display = 'block';
                }
                
            } catch (error) {
                console.error('Error getting location preview:', error);
                previewContent.innerHTML = '<div style="text-align: center; opacity: 0.7; color: #f44336;">Error loading location preview</div>';
                previewContainer.style.display = 'block';
            }
        }
        
        // Function to send location notification from form
        function sendLocationNotificationFromForm() {
            const location = selectedNotificationLocation;
            const title = document.getElementById('notificationTitle').value.trim();
            const message = document.getElementById('notificationMessage').value.trim();
            
            if (!location) {
                alert('Please select a target location');
                return;
            }
            
            if (!title) {
                alert('Please enter a notification title');
                return;
            }
            
            if (!message) {
                alert('Please enter a notification message');
                return;
            }
            
            // Confirm before sending
            if (confirm(`Send notification to users in ${selectedNotificationLocationText}?\n\nTitle: ${title}\nMessage: ${message}`)) {
                sendLocationNotification(location, title, message);
                
                // Clear form
                document.getElementById('notificationTitle').value = '';
                document.getElementById('notificationMessage').value = '';
                selectedNotificationLocation = '';
                selectedNotificationLocationText = '';
                document.getElementById('selected-notification-location').textContent = 'Select Location';
                document.getElementById('notificationLocationPreview').style.display = 'none';
            }
        }
        
        // Function to send location-based notifications
        async function sendLocationNotification(location, title, message) {
            try {
                console.log('Sending location notification:', { location, title, message });
                
                // Get FCM tokens for the specified location
                const response = await fetch('event.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        'action': 'send_location_notification',
                        'location': location,
                        'title': title,
                        'message': message
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotificationSuccess(`Location notification sent successfully to ${result.users_notified} users in ${location}!`);
                } else {
                    showNotificationError(`Failed to send location notification: ${result.message}`);
                }
                
            } catch (error) {
                console.error('Error sending location notification:', error);
                showNotificationError('Error sending location notification. Please try again.');
            }
        }
        
        // Function to show notification success
        function showNotificationSuccess(message) {
            const successDiv = document.createElement('div');
            successDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #4CAF50, #45a049);
                color: white;
                padding: 15px 20px;
                border-radius: 10px;
                box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
                z-index: 1001;
                max-width: 300px;
                animation: slideInRight 0.3s ease;
            `;
            successDiv.innerHTML = `✅ ${message}`;
            
            document.body.appendChild(successDiv);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (successDiv.parentNode) {
                    successDiv.style.animation = 'slideOutRight 0.3s ease';
                    setTimeout(() => {
                        if (successDiv.parentNode) {
                            successDiv.parentNode.removeChild(successDiv);
                        }
                    }, 300);
                }
            }, 5000);
        }
        
        // Function to show notification error
        function showNotificationError(message) {
            const errorDiv = document.createElement('div');
            errorDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #f44336, #d32f2f);
                color: white;
                padding: 15px 20px;
                border-radius: 10px;
                box-shadow: 0 4px 15px rgba(244, 67, 54, 0.3);
                z-index: 1001;
                max-width: 300px;
                animation: slideInRight 0.3s ease;
            `;
            errorDiv.innerHTML = `❌ ${message}`;
            
            document.body.appendChild(errorDiv);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (errorDiv.parentNode) {
                    errorDiv.style.animation = 'slideOutRight 0.3s ease';
                    setTimeout(() => {
                        if (errorDiv.parentNode) {
                            errorDiv.parentNode.removeChild(errorDiv);
                        }
                    }, 300);
                }
            }, 5000);
        }