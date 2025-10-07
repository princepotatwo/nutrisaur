<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug logging
error_log("🚀 EVENT.PHP LOADED - URI: " . $_SERVER['REQUEST_URI']);
error_log("🚀 GET PARAMS: " . json_encode($_GET));

// Include DatabaseAPI for FCM functionality
require_once __DIR__ . '/api/DatabaseAPI.php';

// Use DatabaseAPI for FCM notifications instead of custom implementation
function sendEventFCMNotificationToToken($fcmToken, $title, $body, $dataPayload = null) {
    try {
        // Use the working FCM implementation from DatabaseAPI
        // Call the global function from DatabaseAPI.php with proper data payload
        error_log("🔔 sendEventFCMNotificationToToken called with token: " . substr($fcmToken, 0, 20) . "...");
        $result = \sendFCMNotificationToToken($fcmToken, $title, $body, $dataPayload);
        error_log("🔔 FCM result: " . json_encode($result));
        return $result;
    } catch (Exception $e) {
        error_log("❌ Error in sendEventFCMNotificationToToken: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Function to get Firebase access token using service account (kept for compatibility)
function getEventFirebaseAccessToken($serviceAccountKey) {
    try {
        $jwt = createEventJWT($serviceAccountKey);
        
        $url = 'https://oauth2.googleapis.com/token';
        $data = [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            $response = json_decode($result, true);
            return $response['access_token'] ?? null;
        }
        
        return null;
    } catch (Exception $e) {
        return null;
    }
}

// Function to create JWT for Firebase authentication
function createEventJWT($serviceAccountKey) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'RS256']);
    $now = time();
    $payload = json_encode([
        'iss' => $serviceAccountKey['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => $serviceAccountKey['token_uri'],
        'exp' => $now + 3600,
        'iat' => $now
    ]);
    
    $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    $signature = '';
    $privateKey = $serviceAccountKey['private_key'];
    openssl_sign($base64Header . '.' . $base64Payload, $signature, $privateKey, 'SHA256');
    $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64Header . '.' . $base64Payload . '.' . $base64Signature;
}

// 🚨 TEST AJAX ENDPOINT - Add this first to debug the issue
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'test_ajax') {
    error_log("=== TEST AJAX ENDPOINT CALLED ===");
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'AJAX is working!']);
    exit;
}

// 🚨 DEBUG FCM TOKENS ENDPOINT
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'debug_fcm_tokens') {
    error_log("=== DEBUG FCM TOKENS CALLED ===");
    header('Content-Type: application/json');
    
    $location = $_POST['location'] ?? 'Bangkal';
    $tokens = getFCMTokensByLocation($location);
    
    echo json_encode([
        'success' => true,
        'location' => $location,
        'tokens_found' => count($tokens),
        'tokens' => $tokens
    ]);
    exit;
}

// 🚨 DEBUG NOTIFICATION ENDPOINT
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'debug_notification') {
    error_log("=== DEBUG NOTIFICATION CALLED ===");
    header('Content-Type: application/json');
    
    try {
        // Test notification using direct FCM sending (no cURL to avoid duplicates)
        $testResult = sendEventFCMNotificationToToken('test_token', '🎯 Event: Test Event', 'New event: Test Event at Bangkal on Sep 15, 2025 6:30 PM');
        
        if ($testResult['success']) {
            $response = json_encode(['success' => true, 'message' => 'Test notification sent successfully']);
        } else {
            $response = json_encode(['success' => false, 'message' => 'Test notification failed: ' . $testResult['error']]);
        }
        
        echo $response;
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// 🚨 NEW ACTION: SAVE EVENT ONLY (no notifications, no redirects)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'save_event_only') {
    error_log("=== SAVE EVENT ONLY CALLED ===");
    error_log("POST data received: " . print_r($_POST, true));
    error_log("HTTP_X_REQUESTED_WITH: " . ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? 'NOT SET'));
    
    // Check if it's an AJAX request
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
        error_log("❌ Not an AJAX request");
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }
    
    error_log("✅ AJAX request validated");
    
    try {
        // Get form data
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $date_time = $_POST['date_time'] ?? '';
        $location = $_POST['location'] ?? '';
        $organizer = $_POST['organizer'] ?? '';
        
        // Auto-set type to "Event" since we removed the type field
        $type = 'Event';
        
        // Validate required fields
        if (empty($title) || empty($description) || empty($date_time) || empty($organizer)) {
            echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
            exit;
        }
        
        // Insert into programs table using DatabaseAPI
        $db = DatabaseAPI::getInstance();
        
        // Get next available program_id
        $maxIdResult = $db->universalQuery("SELECT MAX(program_id) as max_id FROM programs");
        $nextId = ($maxIdResult['success'] && !empty($maxIdResult['data'])) ? $maxIdResult['data'][0]['max_id'] + 1 : 1;
        
        $result = $db->universalInsert('programs', [
            'program_id' => $nextId,
            'title' => $title,
            'type' => $type,
            'description' => $description,
            'date_time' => $date_time,
            'location' => $location,
            'organizer' => $organizer,
        ]);
        
        if ($result['success']) {
            $eventId = $nextId; // Use the program_id we calculated, not insert_id
            
            // 🚨 SIMPLIFIED NOTIFICATION SYSTEM - NO COMPLEX FUNCTIONS
            $notificationMessage = 'Event created successfully';
            error_log("✅ Event saved successfully with ID: $eventId");
            
            // Try to send notifications using the simple DatabaseAPI method
            try {
                error_log("📱 Attempting to send notifications via DatabaseAPI...");
                
                // Use the DatabaseAPI directly instead of complex functions
                $db = DatabaseAPI::getInstance();
                
                // Get FCM tokens for the location (no limit to process all users)
                $locationQuery = $location === 'All Locations' || $location === 'all' ? '' : "AND barangay = :location";
                $sql = "SELECT fcm_token, email FROM community_users WHERE fcm_token IS NOT NULL AND fcm_token != '' $locationQuery";
                
                if ($location === 'All Locations' || $location === 'all') {
                    $stmt = $db->getPDO()->prepare($sql);
                } else {
                    $stmt = $db->getPDO()->prepare($sql);
                    $stmt->bindParam(':location', $location);
                }
                
                $stmt->execute();
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                error_log("📊 Found " . count($users) . " users to notify for location: $location");
                
                $notificationCount = 0;
                $errorCount = 0;
                
                foreach ($users as $user) {
                    if (!empty($user['fcm_token'])) {
                        try {
                            $notificationResult = \sendFCMNotificationToToken(
                                $user['fcm_token'],
                                "🎯 New Event: $title",
                                "New event at $location on " . date('M j, Y g:i A', strtotime($date_time))
                            );
                            
                            if ($notificationResult['success']) {
                                $notificationCount++;
                            } else {
                                $errorCount++;
                                error_log("⚠️ Failed to send notification to " . $user['email'] . ": " . ($notificationResult['error'] ?? 'Unknown error'));
                            }
                        } catch (Exception $e) {
                            $errorCount++;
                            error_log("❌ Exception sending notification to " . $user['email'] . ": " . $e->getMessage());
                        }
                    }
                }
                
                if ($notificationCount > 0) {
                    $totalUsers = count($users);
                    $notificationMessage = "Event created and notifications sent to $notificationCount of $totalUsers users";
                    if ($errorCount > 0) {
                        $notificationMessage .= " ($errorCount failed)";
                    }
                    error_log("✅ Notifications sent to $notificationCount of $totalUsers users ($errorCount failed)");
                } else {
                    $notificationMessage = "Event created (no users found for notifications)";
                    error_log("⚠️ No notifications sent - no users found");
                }
                
            } catch (Exception $e) {
                error_log("❌ Notification error (non-blocking): " . $e->getMessage());
                $notificationMessage = "Event created successfully (notification error: " . $e->getMessage() . ")";
                // Don't let notification errors break event creation
            }
            
        } else {
            throw new Exception('Failed to insert event: ' . $result['message']);
        }
        
        // Return success response
        error_log("🚀 About to return JSON response for event ID: $eventId");
        header('Content-Type: application/json');
        $response = [
            'success' => true,
            'message' => $notificationMessage ?: 'Event saved successfully!',
            'event_id' => $eventId
        ];
        error_log("🚀 JSON response: " . json_encode($response));
        echo json_encode($response);
        error_log("🚀 JSON response sent successfully");
        exit;
        
    } catch (Exception $e) {
        error_log("❌ Error saving event: " . $e->getMessage());
        error_log("❌ Error stack trace: " . $e->getTraceAsString());
        http_response_code(500);
        header('Content-Type: application/json');
        $errorResponse = ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        error_log("❌ Error response: " . json_encode($errorResponse));
        echo json_encode($errorResponse);
        exit;
    }
}

// Handle get events request for mobile app FIRST (before authentication check)
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] === 'get_events') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    
    try {
        // Use DatabaseHelper for database operations
        require_once __DIR__ . '/api/DatabaseHelper.php';
        $db = DatabaseHelper::getInstance();
        
        if (!$db->isAvailable()) {
            echo json_encode([
                'success' => false,
                'message' => 'Database not available'
            ]);
            exit;
        }
        
        // Get all events from programs table
        $result = $db->select('programs', '*', '', [], 'date_time ASC');
        
        if (!$result['success']) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to fetch events: ' . ($result['message'] ?? 'Unknown error')
            ]);
            exit;
        }
        
        $events = $result['data'];
        
        // Format events for mobile app
        $formattedEvents = [];
        foreach ($events as $event) {
            $formattedEvents[] = [
                'id' => $event['program_id'],
                'title' => $event['title'],
                'type' => $event['type'],
                'description' => $event['description'],
                'date_time' => $event['date_time'],
                'location' => $event['location'],
                'organizer' => $event['organizer'],
                'created_at' => (!empty($event['created_at']) && $event['created_at'] !== '0000-00-00 00:00:00') ? strtotime($event['created_at']) : time()
            ];
        }
        
        echo json_encode([
            'success' => true,
            'events' => $formattedEvents
        ]);
        exit;
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Start the session only if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get user info from session
$userId = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? null;
$email = $_SESSION['email'] ?? null;

// Check if user is authenticated (skip for API calls)
$isApiCall = isset($_GET['action']) || (isset($_POST['action']) && in_array($_POST['action'], ['create_new_event', 'save_event_only', 'test_ajax', 'debug_fcm_tokens', 'debug_notification']));
if (!$isApiCall && (!$userId || !$username || !$email)) {
    // Redirect to login if not authenticated (only for web interface)
    header("Location: /login");
    exit;
}

// Use centralized DatabaseAPI - NO MORE HARDCODED CONNECTIONS!
require_once __DIR__ . '/api/DatabaseHelper.php';

// Get database helper instance
$db = DatabaseHelper::getInstance();

// Initialize variables
$dbConnected = $db->isAvailable();
$errorMessage = null;
$programs = [];

if ($dbConnected) {
    try {
        // Fetch programs from database using centralized API
        $result = $db->select('programs', '*', '', [], 'date_time DESC');
        if ($result['success']) {
            $programs = $result['data'];
        } else {
            $programs = [];
            $errorMessage = "Failed to fetch programs: " . ($result['message'] ?? 'Unknown error');
        }
    } catch (Exception $e) {
        $dbConnected = false;
        $errorMessage = "Database query failed: " . $e->getMessage();
        $programs = [];
    }
} else {
    $errorMessage = "Database connection not available";
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

// Handle get events request for mobile app
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] === 'get_events') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    
    try {
        // Get all events from programs table
        $db = DatabaseAPI::getInstance();
        $result = $db->universalQuery("SELECT * FROM programs ORDER BY date_time ASC");
        $events = $result['success'] ? $result['data'] : [];
        
        // Format events for mobile app
        $formattedEvents = [];
        foreach ($events as $event) {
            $formattedEvents[] = [
                'id' => $event['program_id'],
                'title' => $event['title'],
                'type' => $event['type'],
                'description' => $event['description'],
                'date_time' => $event['date_time'],
                'location' => $event['location'],
                'organizer' => $event['organizer'],
                'created_at' => (!empty($event['created_at']) && $event['created_at'] !== '0000-00-00 00:00:00') ? strtotime($event['created_at']) : time()
            ];
        }
        
        echo json_encode([
            'success' => true,
            'events' => $formattedEvents
        ]);
        exit;
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching events: ' . $e->getMessage()
        ]);
        exit;
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
            $stmt = $db->getPDO()->query("SHOW TABLES LIKE 'notification_logs'");
            $debugInfo['notification_logs_table_exists'] = $stmt ? $stmt->rowCount() > 0 : false;
            
            // Check if community_users table has fcm_token column
            $stmt = $db->getPDO()->query("SHOW COLUMNS FROM community_users LIKE 'fcm_token'");
            $debugInfo['community_users_fcm_column_exists'] = $stmt ? $stmt->rowCount() > 0 : false;
            
            // Check if user_preferences table exists
            $stmt = $db->getPDO()->query("SHOW TABLES LIKE 'user_preferences'");
            $debugInfo['user_preferences_table_exists'] = $stmt ? $stmt->rowCount() > 0 : false;
            
            // Get FCM token count from community_users table
            if ($debugInfo['community_users_fcm_column_exists']) {
                $stmt = $db->getPDO()->query("SELECT COUNT(*) as count FROM community_users WHERE fcm_token IS NOT NULL AND fcm_token != ''");
                $debugInfo['active_fcm_tokens'] = $stmt ? $stmt->fetchColumn() : 'Database error';
            }
            
            // Get user preferences count
            if ($debugInfo['user_preferences_table_exists']) {
                $stmt = $db->getPDO()->query("SELECT COUNT(*) as count FROM user_preferences WHERE barangay IS NOT NULL AND barangay != ''");
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
            
            // Get FCM token for the specific user from community_users table
            $stmt = $db->getPDO()->prepare("
                SELECT cu.fcm_token 
                FROM community_users cu
                WHERE cu.email = ? 
                AND cu.fcm_token IS NOT NULL AND cu.fcm_token != ''
            ");
            $stmt->execute([$targetUser]);
            $fcmToken = $stmt->fetchColumn();
            
            if (!$fcmToken) {
                throw new Exception('No active FCM token found for user: ' . $targetUser);
            }
            
            // Send FCM notification to the specific user
            $notificationSent = sendEventFCMNotification([$fcmToken], [
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



// 🚨 COMPLETELY REWRITTEN EVENT CREATION LOGIC - NO REDIRECTS
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_event'])) {
    error_log("=== NEW EVENT CREATION LOGIC STARTED ===");
    
    // Get form data
    $title = $_POST['eventTitle'] ?? '';
    $type = $_POST['eventType'] ?? '';
    $description = $_POST['eventDescription'] ?? '';
    $date_time = $_POST['eventDate'] ?? '';
    $location = $_POST['eventLocation'] ?? 'all';
    $organizer = $_POST['eventOrganizer'] ?? '';
    
    error_log("Form data received: Title=$title, Type=$type, Location=$location, Organizer=$organizer");
    
    // Validate required fields
    if (empty($title) || empty($type) || empty($description) || empty($date_time) || empty($organizer)) {
        $errorMessage = "Please fill in all required fields.";
        error_log("Validation failed: Missing required fields");
    } else {
        try {
            // Insert event into database using DatabaseAPI
            $db = DatabaseAPI::getInstance();
            $result = $db->universalInsert('programs', [
                'title' => $title,
                'type' => $type,
                'description' => $description,
                'date_time' => $date_time,
                'location' => $location,
                'organizer' => $organizer,
            ]);
            
            if ($result['success']) {
                $eventId = $result['insert_id'];
            } else {
                throw new Exception('Failed to insert event: ' . $result['message']);
            }
            
            error_log("✅ Event created successfully with ID: $eventId");
            
            // Notifications are handled by the save_event_only handler
            $successMessage = "🎉 Event '$title' created successfully!";
            
            error_log("✅ Event creation completed successfully");
            
        } catch (Exception $e) {
            $errorMessage = "Error creating event: " . $e->getMessage();
            error_log("❌ Event creation failed: " . $e->getMessage());
        }
    }
    
    // IMPORTANT: NO REDIRECTS, NO EXIT - Just set messages and continue
    error_log("Event creation logic completed - staying on same page");
}

// 🚨 COMPLETELY NEW AJAX EVENT CREATION HANDLER - NO REDIRECTS, NO DASHBOARD
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'create_new_event') {
    error_log("=== NEW AJAX EVENT CREATION STARTED ===");
    error_log("POST data received: " . print_r($_POST, true));
    
    // Check if it's an AJAX request
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
        error_log("❌ Not an AJAX request - HTTP_X_REQUESTED_WITH: " . ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? 'NOT SET'));
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }
    
    error_log("✅ AJAX request validated successfully");
    
    // Get form data
    $title = $_POST['title'] ?? '';
    $type = $_POST['type'] ?? '';
    $description = $_POST['description'] ?? '';
    $date_time = $_POST['date_time'] ?? '';
    $location = $_POST['location'] ?? 'all';
    $organizer = $_POST['organizer'] ?? '';
    $notificationType = $_POST['notificationType'] ?? 'push';
    $recipientGroup = $_POST['recipientGroup'] ?? 'All Users';
    
    error_log("AJAX Event data: Title=$title, Type=$type, Location=$location, Organizer=$organizer");
    
    // Validate required fields
    if (empty($title) || empty($type) || empty($description) || empty($date_time) || empty($organizer)) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }
    
    try {
        // Insert event into database using DatabaseAPI
        $db = DatabaseAPI::getInstance();
        
        // Get next available program_id
        $maxIdResult = $db->universalQuery("SELECT MAX(program_id) as max_id FROM programs");
        $nextId = ($maxIdResult['success'] && !empty($maxIdResult['data'])) ? $maxIdResult['data'][0]['max_id'] + 1 : 1;
        
        $result = $db->universalInsert('programs', [
            'program_id' => $nextId,
            'title' => $title,
            'type' => $type,
            'description' => $description,
            'date_time' => $date_time,
            'location' => $location,
            'organizer' => $organizer,
        ]);
        
        if ($result['success']) {
            $eventId = $nextId; // Use the program_id we calculated, not insert_id
        } else {
            throw new Exception('Failed to insert event: ' . $result['message']);
        }
        
        error_log("✅ AJAX Event created successfully with ID: $eventId");
        
        // Send notifications using the sendEventNotifications function
        $notificationMessage = '';
        if ($notificationType !== 'none') {
            try {
                error_log("📱 AJAX: Sending notifications for event: $title at $location");
                
                // Use the centralized sendEventNotifications function
                $notificationResult = sendEventNotifications($eventId, $title, $type, $description, $date_time, $location, $organizer);
                
                if ($notificationResult['success']) {
                    $notificationMessage = $notificationResult['message'];
                    error_log("✅ AJAX Notification sent successfully: " . $notificationMessage);
                } else {
                    $notificationMessage = $notificationResult['message'];
                    error_log("⚠️ AJAX Notification warning: " . $notificationMessage);
                }
                
            } catch (Exception $e) {
                error_log("❌ AJAX Notification error: " . $e->getMessage());
                $notificationMessage = 'Event created but notification failed: ' . $e->getMessage();
            }
        } else {
            $notificationMessage = 'Event created without notifications';
        }
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => $notificationMessage,
            'event_id' => $eventId
        ]);
        
        error_log("✅ AJAX Event creation completed successfully");
        
    } catch (Exception $e) {
        error_log("❌ AJAX Event creation failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error creating event: ' . $e->getMessage()]);
    }
    
    // IMPORTANT: EXIT HERE FOR AJAX - NO REDIRECTS, NO CONTINUATION
    exit;
}

// 🚨 NEW SIMPLIFIED NOTIFICATION FUNCTION
function sendEventNotifications($eventId, $title, $type, $description, $date_time, $location, $organizer) {
    $db = DatabaseAPI::getInstance();
    
    try {
        error_log("🚨 sendEventNotifications called for event: $title");
        
        // Create unique lock file name based on event details (same as CSV import)
        $eventKey = md5($title . $location . date('Y-m-d H:i:s', strtotime($date_time)));
        $lockFile = "/tmp/notification_" . $eventKey . ".lock";
        
        // Check if notification already sent for this exact event
        if (file_exists($lockFile)) {
            error_log("⚠️ Manual Event: Notification already sent for event: $title at $location - skipping duplicate");
            return [
                'success' => true,
                'message' => 'Event created but notification already sent for this event',
                'devices_notified' => 0
            ];
        }
        
        // Create lock file to prevent duplicates
        file_put_contents($lockFile, time());
        error_log("🔔 Manual Event: Sending notification for event: $title at $location");
        
        // Get FCM tokens based on location
        $fcmTokenData = getFCMTokensByLocation($location);
        $fcmTokens = array_column($fcmTokenData, 'fcm_token');
        
        error_log("📱 FCM tokens found: " . count($fcmTokens) . " for location: '$location'");
        
        if (empty($fcmTokens)) {
            error_log("⚠️ No FCM tokens found for location: $location");
            return [
                'success' => false,
                'message' => 'No users found for this location. Event created but no notifications sent.',
                'devices_notified' => 0
            ];
        }
        
        // Create notification body
        $locationText = ($location === 'all' || empty($location)) ? 'All Locations' : $location;
        $notificationBody = "New event: $title at $locationText on " . date('M j, Y g:i A', strtotime($date_time));
        
        // Send notifications using the working API
        $successCount = 0;
        $failureCount = 0;
        
        foreach ($fcmTokenData as $tokenData) {
            $fcmToken = $tokenData['fcm_token'];
            $userEmail = $tokenData['user_email'];
            
            if (empty($fcmToken) || empty($userEmail)) {
                $failureCount++;
                continue;
            }
            
            // Use the SAME working notification API that dash.php uses for critical risk
            $notificationPayload = [
                'title' => "🎯 Event: " . $title,
                'body' => $notificationBody,
                'target_user' => $userEmail,
                'user_name' => $userEmail,
                'alert_type' => 'event_notification',
                'event_id' => $eventId,
                'event_type' => $type,
                'event_location' => $location,
                'event_date' => date('M j, Y g:i A', strtotime($date_time))
            ];
            
            // Send FCM notification directly (no cURL to avoid duplicate notifications)
            $fcmResult = sendEventFCMNotificationToToken($fcmToken, $notificationPayload['title'], $notificationPayload['body']);
            
            if ($fcmResult['success']) {
                    error_log("Event notification sent successfully to $userEmail: {$notificationPayload['title']}");
                    $successCount++;
                } else {
                error_log("FCM notification failed for user $userEmail: " . ($fcmResult['error'] ?? 'Unknown error'));
                $failureCount++;
            }
        }
        
        error_log("📤 Notifications sent: $successCount successful, $failureCount failed");
        
        // Log the attempt
        logNotificationAttempt($eventId, 'new_event', 'location', $location, $successCount, $successCount > 0);
        
        if ($successCount > 0) {
            return [
                'success' => true,
                'message' => "Notification sent to $successCount users in $locationText",
                'devices_notified' => $successCount
            ];
        } else {
            return [
                'success' => false,
                'message' => "Event created but failed to send notifications",
                'devices_notified' => 0
            ];
        }
        
    } catch (Exception $e) {
        error_log("❌ Error in sendEventNotifications: " . $e->getMessage());
        error_log("❌ Error stack trace: " . $e->getTraceAsString());
        return [
            'success' => false,
            'message' => "Error sending notifications: " . $e->getMessage(),
            'devices_notified' => 0
        ];
    }
}

// Helper function to send notification via API
// Include DatabaseAPI class
require_once __DIR__ . '/api/DatabaseAPI.php';

function sendNotificationViaAPI($notificationData) {
    try {
        // Use DatabaseAPI class directly instead of HTTP calls
        $db = DatabaseAPI::getInstance();
        
        // Extract notification data
        $title = $notificationData['title'] ?? '';
        $body = $notificationData['body'] ?? '';
        $targetUser = $notificationData['target_user'] ?? '';
        $alertType = $notificationData['alert_type'] ?? '';
        $userName = $notificationData['user_name'] ?? '';
        
        if (empty($title) || empty($body)) {
            return ['success' => false, 'error' => 'Title and body are required'];
        }
        
        // Get FCM tokens for the target user from community_users table
        $fcmTokens = [];
        if (!empty($targetUser)) {
            $stmt = $db->getPDO()->prepare("SELECT fcm_token FROM community_users WHERE email = :email AND status = 'active' AND fcm_token IS NOT NULL AND fcm_token != ''");
            $stmt->bindParam(':email', $targetUser);
            $stmt->execute();
            $fcmTokens = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } else {
            // Send to all active tokens if no specific user
            $stmt = $db->getPDO()->prepare("SELECT fcm_token FROM community_users WHERE status = 'active' AND fcm_token IS NOT NULL AND fcm_token != ''");
            $stmt->execute();
            $fcmTokens = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        
        if (empty($fcmTokens)) {
            return ['success' => false, 'error' => 'No active FCM tokens found'];
        }
        
        // Send notification to each token
        $successCount = 0;
        $failCount = 0;
        
        foreach ($fcmTokens as $fcmToken) {
            try {
                // Send actual FCM notification using Firebase Admin SDK
                $fcmResult = sendEventFCMNotificationToToken($fcmToken, $title, $body);
                
                if ($fcmResult['success']) {
                    // Log successful notification
                    $db->logNotification(
                        null, // event_id
                        $fcmToken,
                        $title,
                        $body,
                        'success',
                        $fcmResult['response']
                    );
                    $successCount++;
                } else {
                    // Log failed notification
                    $db->logNotification(
                        null, // event_id
                        $fcmToken,
                        $title,
                        $body,
                        'failed',
                        $fcmResult['error']
                    );
                    $failCount++;
                }
            } catch (Exception $e) {
                error_log("Failed to send notification to token: " . $e->getMessage());
                $failCount++;
            }
        }
        
        return [
            'success' => true,
            'message' => "Notification sent successfully to {$successCount} devices",
            'success_count' => $successCount,
            'fail_count' => $failCount
        ];
        
    } catch (Exception $e) {
        error_log("Error in sendNotificationViaAPI: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Use DatabaseAPI class directly for FCM notifications
function sendEventFCMNotification($tokens, $notificationData, $targetLocation = null) {
    try {
        error_log("sendFCMNotification called with " . count($tokens) . " tokens for location: $targetLocation");
        
        if (empty($tokens)) {
            error_log("No FCM tokens provided for notification");
            return false;
        }
        
        // Use DatabaseAPI class directly
        $db = DatabaseAPI::getInstance();
        
        $successCount = 0;
        $failureCount = 0;
        
        foreach ($tokens as $tokenData) {
            $fcmToken = $tokenData['fcm_token'];
            $userEmail = $tokenData['user_email'];
            $userBarangay = $tokenData['user_barangay'] ?? 'Unknown';
            
            if (empty($fcmToken) || empty($userEmail)) {
                error_log("Empty FCM token or user email for notification");
                continue;
            }
            
            try {
                // Send actual FCM notification
                $fcmResult = sendEventFCMNotificationToToken($fcmToken, $notificationData['title'], $notificationData['body']);
                
                if ($fcmResult['success']) {
                    // Log successful notification
                    $db->logNotification(
                        null, // event_id
                        $fcmToken,
                        $notificationData['title'],
                        $notificationData['body'],
                        'success',
                        $fcmResult['response']
                    );
                    
                    error_log("FCM notification sent successfully to $userEmail ($userBarangay): {$notificationData['title']}");
                    $successCount++;
                } else {
                    // Log failed notification
                    $db->logNotification(
                        null, // event_id
                        $fcmToken,
                        $notificationData['title'],
                        $notificationData['body'],
                        'failed',
                        $fcmResult['error']
                    );
                    
                    error_log("FCM notification failed for $userEmail ($userBarangay): {$fcmResult['error']}");
                    $failureCount++;
                }
                
            } catch (Exception $e) {
                error_log("Failed to send notification to user $userEmail: " . $e->getMessage());
                $failureCount++;
            }
        }
        
        error_log("Event notification summary: $successCount successful, $failureCount failed for location: $targetLocation");
        return $successCount > 0;
        
    } catch (Exception $e) {
        error_log("Error in sendFCMNotification: " . $e->getMessage());
        return false;
    }
}

// These functions are no longer needed - using the working API instead

// Function to get FCM tokens based on location targeting using DatabaseAPI methods
function getFCMTokensByLocation($targetLocation = null) {
    try {
        // Use DatabaseAPI class directly
        $db = DatabaseAPI::getInstance();
        
        // Debug logging
        error_log("getFCMTokensByLocation called with targetLocation: '$targetLocation' (type: " . gettype($targetLocation) . ", length: " . strlen($targetLocation ?? '') . ")");
        
        if (empty($targetLocation) || $targetLocation === 'all' || $targetLocation === '' || $targetLocation === 'All Locations') {
            error_log("Processing 'all locations' case - getting all FCM tokens");
            // Get all FCM tokens (no status column check)
            $stmt = $db->getPDO()->prepare("
                SELECT fcm_token, email as user_email, barangay as user_barangay, municipality
                FROM community_users
                WHERE fcm_token IS NOT NULL 
                AND fcm_token != ''
            ");
            $stmt->execute();
            $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Check if it's a municipality (starts with MUNICIPALITY_)
            if (strpos($targetLocation, 'MUNICIPALITY_') === 0) {
                error_log("Processing municipality case: $targetLocation");
                // Get tokens for all users in the municipality
                $municipalityName = str_replace('MUNICIPALITY_', '', $targetLocation);
                // Convert underscores back to spaces
                $municipalityName = str_replace('_', ' ', $municipalityName);
                
                // Handle special case for BALANGA -> CITY OF BALANGA
                if ($municipalityName === 'BALANGA') {
                    $municipalityName = 'CITY OF BALANGA';
                }
                
                error_log("Looking for municipality: '$municipalityName'");
                
                $stmt = $db->getPDO()->prepare("
                    SELECT fcm_token, email as user_email, barangay as user_barangay, municipality
                    FROM community_users
                    WHERE fcm_token IS NOT NULL 
                    AND fcm_token != ''
                    AND municipality = :municipality
                ");
                $stmt->bindParam(':municipality', $municipalityName);
                $stmt->execute();
                $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                error_log("🔍 Found " . count($tokens) . " users in municipality '$municipalityName'");
                
                // Debug: Show what municipalities actually exist
                $debugStmt = $db->getPDO()->prepare("SELECT DISTINCT municipality, COUNT(*) as count FROM community_users WHERE fcm_token IS NOT NULL AND fcm_token != '' GROUP BY municipality");
                $debugStmt->execute();
                $municipalities = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("🔍 Available municipalities with FCM tokens: " . json_encode($municipalities));
            } else {
                error_log("Processing barangay case: $targetLocation - getting tokens for specific barangay");
                // Get FCM tokens by barangay
                $stmt = $db->getPDO()->prepare("
                    SELECT fcm_token, email as user_email, barangay as user_barangay, municipality
                    FROM community_users
                    WHERE fcm_token IS NOT NULL 
                    AND fcm_token != ''
                    AND barangay = :targetLocation
                ");
                $stmt->bindParam(':targetLocation', $targetLocation);
                $stmt->execute();
                $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
        
        // Log the targeting results
        $targetType = empty($targetLocation) ? 'all' : (strpos($targetLocation, 'MUNICIPALITY_') === 0 ? 'municipality' : 'barangay');
        error_log("FCM targeting using DatabaseAPI: $targetType '$targetLocation' - Found " . count($tokens) . " tokens");
        
        // Additional debug info for empty results
        if (count($tokens) === 0) {
            error_log("No FCM tokens found. Checking if there are any FCM tokens with barangay data...");
            
            // Check if there are any FCM tokens with barangay data from community_users table
            $checkStmt = $db->getPDO()->prepare("SELECT COUNT(*) as total FROM community_users WHERE barangay IS NOT NULL AND barangay != '' AND fcm_token IS NOT NULL AND fcm_token != ''");
            $checkStmt->execute();
            $tokenWithBarangayCount = $checkStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Check if there are any FCM tokens from community_users table
            $tokenStmt = $db->getPDO()->prepare("SELECT COUNT(*) as total FROM community_users WHERE fcm_token IS NOT NULL AND fcm_token != ''");
            $tokenStmt->execute();
            $tokenCount = $tokenStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            error_log("Total FCM tokens with barangay data: $tokenWithBarangayCount, Total active FCM tokens: $tokenCount");
            
            // Debug: Show what municipalities and barangays actually exist in the database
            if (strpos($targetLocation, 'MUNICIPALITY_') === 0) {
                $debugStmt = $db->getPDO()->prepare("SELECT DISTINCT municipality, COUNT(*) as count FROM community_users WHERE fcm_token IS NOT NULL AND fcm_token != '' GROUP BY municipality");
                $debugStmt->execute();
                $municipalities = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("Available municipalities in database: " . json_encode($municipalities));
                
                $municipalityName = str_replace('MUNICIPALITY_', '', $targetLocation);
                // Convert underscores back to spaces
                $municipalityName = str_replace('_', ' ', $municipalityName);
                error_log("Looking for municipality: '$municipalityName'");
            }
        }
        
        return $tokens;
        
    } catch (Exception $e) {
        error_log("Error getting FCM tokens by location: " . $e->getMessage());
        return [];
    }
}

// Function to log notification attempts using DatabaseAPI
function logNotificationAttempt($eventId, $notificationType, $targetType, $targetValue, $tokensSent, $success, $errorMessage = null) {
    try {
        // Use DatabaseAPI class directly
        $db = DatabaseAPI::getInstance();
        
        $stmt = $db->getPDO()->prepare("
            INSERT INTO notification_logs (event_id, notification_type, target_type, target_value, tokens_sent, success, error_message, created_at) 
            VALUES (:event_id, :notification_type, :target_type, :target_value, :tokens_sent, :success, :error_message, NOW())
        ");
        
        $successInt = $success ? 1 : 0;
        $stmt->bindParam(':event_id', $eventId);
        $stmt->bindParam(':notification_type', $notificationType);
        $stmt->bindParam(':target_type', $targetType);
        $stmt->bindParam(':target_value', $targetValue);
        $stmt->bindParam(':tokens_sent', $tokensSent);
        $stmt->bindParam(':success', $successInt);
        $stmt->bindParam(':error_message', $errorMessage);
        $stmt->execute();
        
    } catch (Exception $e) {
        error_log("Error logging notification attempt: " . $e->getMessage());
    }
}



// Function to get user location statistics using DatabaseAPI
function getUserLocationStats() {
    try {
        // Use DatabaseAPI class directly
        $db = DatabaseAPI::getInstance();
        
        $stats = [];
        
        // Count total users with barangay in community_users table using DatabaseAPI
        $stmt = $db->getPDO()->prepare("
            SELECT COUNT(*) as total_users_with_barangay
            FROM community_users
            WHERE barangay IS NOT NULL AND barangay != ''
            AND status = 'active'
        ");
        $stmt->execute();
        $stats['total_users_with_barangay'] = $stmt->fetchColumn();
        
        // Count total active FCM tokens from community_users table
        $stmt = $db->getPDO()->prepare("
            SELECT COUNT(*) as total_fcm_tokens
            FROM community_users 
            WHERE status = 'active' AND fcm_token IS NOT NULL AND fcm_token != ''
        ");
        $stmt->execute();
        $stats['total_fcm_tokens'] = $stmt->fetchColumn();
        
        // Count FCM tokens with barangay from community_users table
        $stmt = $db->getPDO()->prepare("
            SELECT COUNT(*) as fcm_tokens_with_barangay
            FROM community_users
            WHERE status = 'active' 
            AND fcm_token IS NOT NULL 
            AND fcm_token != ''
            AND barangay IS NOT NULL 
            AND barangay != ''
        ");
        $stmt->execute();
        $stats['fcm_tokens_with_barangay'] = $stmt->fetchColumn();
        
        // Count FCM tokens without barangay from community_users table
        $stmt = $db->getPDO()->prepare("
            SELECT COUNT(*) as fcm_tokens_without_barangay
            FROM community_users 
            WHERE status = 'active' 
            AND fcm_token IS NOT NULL 
            AND fcm_token != ''
            AND (barangay IS NULL OR barangay = '')
        ");
        $stmt->execute();
        $stats['fcm_tokens_without_barangay'] = $stmt->fetchColumn();
        
        return $stats;
        
    } catch (Exception $e) {
        error_log("Error getting user location stats: " . $e->getMessage());
        return [];
    }
}



// Function to get detailed user list for a specific location (for debugging) - using direct lookup like dash.php
function getUsersForLocation($targetLocation) {
    $db = DatabaseAPI::getInstance();
    
    try {
        if (empty($targetLocation) || $targetLocation === 'all') {
            $result = $db->universalQuery("
                SELECT cu.email as user_email, cu.barangay, cu.fcm_token, cu.status as is_active
                FROM community_users cu
                WHERE cu.barangay IS NOT NULL AND cu.barangay != ''
                AND cu.status = 'active' AND cu.fcm_token IS NOT NULL AND cu.fcm_token != ''
                ORDER BY cu.barangay, cu.email
            ");
            $users = $result['success'] ? $result['data'] : [];
        } else {
            // Check if it's a municipality
            if (strpos($targetLocation, 'MUNICIPALITY_') === 0) {
                $municipalityName = str_replace('MUNICIPALITY_', '', $targetLocation);
                // Convert underscores back to spaces
                $municipalityName = str_replace('_', ' ', $municipalityName);
                $result = $db->universalQuery("
                    SELECT cu.email as user_email, cu.barangay, cu.fcm_token, cu.status as is_active
                    FROM community_users cu
                    WHERE cu.barangay IS NOT NULL AND cu.barangay != ''
                    AND cu.status = 'active' AND cu.fcm_token IS NOT NULL AND cu.fcm_token != ''
                    AND (cu.barangay = ? OR cu.barangay LIKE ?)
                    ORDER BY cu.barangay, cu.email
                ", [$targetLocation, $municipalityName . '%']);
                $users = $result['success'] ? $result['data'] : [];
            } else {
                $result = $db->universalQuery("
                    SELECT cu.email as user_email, cu.barangay, cu.fcm_token, cu.status as is_active
                    FROM community_users cu
                    WHERE cu.barangay = ?
                    AND cu.status = 'active' AND cu.fcm_token IS NOT NULL AND cu.fcm_token != ''
                    ORDER BY cu.email
                ", [$targetLocation]);
                $users = $result['success'] ? $result['data'] : [];
            }
        }
        
        return $users;
        
    } catch (Exception $e) {
        error_log("Error getting users for location: " . $e->getMessage());
        return [];
    }
}

// Handle individual event deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $programId = (int)$_GET['delete'];
    
    try {
        // Get database connection
        $db = DatabaseAPI::getInstance();
        
        // Get program details before deletion to clean up lock files
        $programResult = $db->universalSelect('programs', 'title, location, date_time', 'program_id = ?', '', '', [$programId]);
        $program = $programResult['success'] && !empty($programResult['data']) ? $programResult['data'][0] : null;
        
        // Delete the event from database
        $result = $db->universalDelete('programs', 'program_id = ?', [$programId]);
        
        if ($result['success']) {
            // Clean up corresponding lock file if program details are available
            if ($program) {
                $eventKey = md5($program['title'] . $program['location'] . date('Y-m-d H:i:s', strtotime($program['date_time'])));
                $lockFile = "/tmp/notification_" . $eventKey . ".lock";
                
                if (file_exists($lockFile)) {
                    unlink($lockFile);
                    error_log("🧹 Deleted lock file for event: " . $program['title'] . " at " . $program['location']);
                }
            }
            
            // Redirect back with success message
            header("Location: event.php?deleted=1&deleted_id=" . $programId);
            exit;
        } else {
            // Redirect back with error message
            header("Location: event.php?error=1&message=" . urlencode($result['message']));
            exit;
        }
    } catch (Exception $e) {
        // Redirect back with error message
        header("Location: event.php?error=1&message=" . urlencode("Error deleting event: " . $e->getMessage()));
        exit;
    }
}

// 🚨 CLEAN CSV IMPORT METHOD - NO COMPLEX LOGIC
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['import_csv'])) {
    error_log("=== CLEAN CSV IMPORT STARTED ===");
    
    if (isset($_FILES['csvFile']) && $_FILES['csvFile']['error'] == 0) {
        $file = $_FILES['csvFile'];
        
        // Simple validation
        if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'csv') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Please upload a CSV file only.']);
            exit;
        }
        
        if ($file['size'] > 5000000) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'File size too large. Max 5MB.']);
            exit;
        }
        
        try {
            // Simple database connection
            require_once __DIR__ . '/../config.php';
            $pdo = getDatabaseConnection();
            
            if (!$pdo) {
                throw new Exception("Database connection failed");
            }
            
            // Debug: Check database connection
            error_log("🔍 CSV: Database connection established successfully");
            
                    $importedCount = 0;
                    $errors = [];
            $row = 0;
                    
            // Process CSV
            if (($handle = fopen($file['tmp_name'], "r")) !== FALSE) {
                error_log("🔍 CSV: File opened successfully, starting to process rows...");
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        $row++;
                    error_log("🔍 CSV: Processing row $row with " . count($data) . " columns: " . implode('|', $data));
                    if ($row == 1) {
                        error_log("🔍 CSV: Skipping header row");
                        continue; // Skip header
                    }
                        
                    // Validate columns - accept both 5 and 6 columns for backward compatibility
                        if (count($data) < 5) {
                        $errors[] = "Row $row: Need at least 5 columns (title, date_time, location, organizer, description)";
                            continue;
                        }
                        
                    // Handle backward compatibility: if 5 columns, treat as old format without barangay
                    if (count($data) == 5) {
                        // Old format: title, date_time, location, organizer, description
                        $barangay = ''; // Empty barangay = target all barangays in municipality
                    } else {
                        // New format: title, date_time, location, barangay, organizer, description
                        $barangay = trim($data[3]);
                    }
                        
                    // Extract data based on format
                        $title = trim($data[0]);
                        $date_time = trim($data[1]);
                        $location = trim($data[2]);
                        
                    if (count($data) == 5) {
                        // Old format: title, date_time, location, organizer, description
                        $organizer = trim($data[3]);
                        $description = trim($data[4]);
                    } else {
                        // New format: title, date_time, location, barangay, organizer, description
                        $organizer = trim($data[4]);
                        $description = trim($data[5]);
                    }
                        
                        // Validate required fields
                    if (empty($title) || empty($date_time) || empty($location) || empty($organizer)) {
                        $errors[] = "Row $row: Missing required fields (title, date_time, location, organizer are required)";
                            continue;
                        }
                        
                    // Simple date validation
                    $dateObj = DateTime::createFromFormat('Y-m-d H:i:s', $date_time);
                    if (!$dateObj) {
                        $dateObj = DateTime::createFromFormat('Y-m-d H:i', $date_time);
                    }
                        if (!$dateObj) {
                        $errors[] = "Row $row: Invalid date format";
                            continue;
                        }
                        
                    // Get next ID
                    $stmt = $pdo->query("SELECT MAX(program_id) as max_id FROM programs");
                    $result = $stmt->fetch();
                    $nextId = ($result['max_id'] ?? 0) + 1;
                    
                    // Insert into database - using community_users.barangay for targeting
                    error_log("🔍 CSV: Attempting to insert - ID: $nextId, Title: $title, Location: $location, Target Barangay: $barangay");
                    $stmt = $pdo->prepare("
                        INSERT INTO programs (program_id, title, type, description, date_time, location, organizer) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $success = $stmt->execute([
                        $nextId,
                        $title,
                        'Event',
                        $description,
                        $dateObj->format('Y-m-d H:i:s'),
                        $location,
                        $organizer
                    ]);
                    
                    error_log("🔍 CSV: Insert result - Success: " . ($success ? 'YES' : 'NO'));
                    
                    if ($success) {
                            $importedCount++;
                        error_log("✅ CSV: Row $row imported - ID: $nextId");
                        
                        // Send notification with duplicate prevention using file lock
                        try {
                            // Create unique lock file name based on event details
                            $eventKey = md5($title . $location . $dateObj->format('Y-m-d H:i:s'));
                            $lockFile = "/tmp/notification_" . $eventKey . ".lock";
                            
                            // Check if notification already sent for this exact event
                            if (!file_exists($lockFile)) {
                                error_log("🔔 CSV: Sending notification for event: $title at $location");
                                
                                // Create lock file to prevent duplicates
                                file_put_contents($lockFile, time());
                                
                                $notificationTitle = "🎯 Event: $title";
                                $notificationBody = "New event: $title at $location" . (!empty($barangay) ? " - $barangay" : "") . " on " . date('M j, Y g:i A', strtotime($dateObj->format('Y-m-d H:i:s')));
                                
                                // Target users based on barangay field
                                if (empty($barangay)) {
                                    // If barangay is empty, target all users in the municipality
                                $tokenStmt = $pdo->prepare("
                                    SELECT fcm_token, email FROM community_users 
                                    WHERE fcm_token IS NOT NULL AND fcm_token != ''
                                        AND municipality = ?
                                    ");
                                    $tokenStmt->execute([$location]);
                                    error_log("🔔 CSV: Targeting all users in municipality: $location");
                                } else {
                                    // If barangay has value, target users in that specific barangay
                                    $tokenStmt = $pdo->prepare("
                                        SELECT fcm_token, email FROM community_users 
                                        WHERE fcm_token IS NOT NULL AND fcm_token != ''
                                        AND barangay = ?
                                    ");
                                    $tokenStmt->execute([$barangay]);
                                    error_log("🔔 CSV: Targeting users in specific barangay: $barangay");
                                }
                                $tokens = $tokenStmt->fetchAll();
                                
                                error_log("🔔 CSV: Found " . count($tokens) . " FCM tokens for location: $location");
                                
                                $successCount = 0;
                                foreach ($tokens as $token) {
                                    $fcmResult = sendEventFCMNotificationToToken($token['fcm_token'], $notificationTitle, $notificationBody);
                                    if ($fcmResult['success']) {
                                        $successCount++;
                                        error_log("✅ CSV: FCM sent to " . $token['email']);
                                    } else {
                                        error_log("❌ CSV: FCM failed for " . $token['email'] . ": " . ($fcmResult['error'] ?? 'Unknown error'));
                                    }
                                }
                                
                                error_log("📱 CSV: Notification sent to $successCount users for event: $title");
                                
                            } else {
                                error_log("⚠️ CSV: Notification already sent for event: $title at $location - skipping duplicate");
                            }
                            
                        } catch (Exception $e) {
                            error_log("⚠️ CSV: Notification failed: " . $e->getMessage());
                            
                            // Fallback: Send to all users if location-specific targeting fails
                            try {
                                $allTokensResult = getFCMTokensByLocation("All Locations");
                                if (!empty($allTokensResult)) {
                                    $successCount = 0;
                                    foreach ($allTokensResult as $tokenData) {
                                        $fcmToken = $tokenData["fcm_token"];
                                        $userEmail = $tokenData["user_email"];
                                        
                                        error_log("🔍 CSV: Fallback - Attempting to send FCM notification to user: $userEmail");
                                        $fcmResult = sendEventFCMNotificationToToken($fcmToken, $notificationTitle, $notificationBody);
                                        if ($fcmResult["success"]) {
                                            $successCount++;
                                            error_log("✅ CSV: Fallback FCM notification sent successfully to $userEmail");
                                        }
                                    }
                                    
                                    error_log("📱 CSV: Fallback notification sent to $successCount users for event: $title");
                                }
                            } catch (Exception $fallbackError) {
                                error_log("⚠️ CSV: Fallback notification also failed: " . $fallbackError->getMessage());
                            }
                                }
                                
                            } else {
                        $errors[] = "Row $row: Database insert failed";
                        }
                    }
                    fclose($handle);
            }
            
            // Return response
            header('Content-Type: application/json');
            if ($importedCount > 0) {
                echo json_encode([
                    'success' => true,
                    'imported_count' => $importedCount,
                    'message' => "Successfully imported $importedCount events!",
                    'errors' => $errors
                ]);
                                } else {
                echo json_encode([
                    'success' => false,
                    'imported_count' => 0,
                    'message' => 'No events imported. ' . implode('; ', $errors),
                    'errors' => $errors
                ]);
            }
            exit;
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'imported_count' => 0,
                'message' => 'CSV import failed: ' . $e->getMessage(),
                'errors' => []
            ]);
            exit;
        }
        
                        } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'imported_count' => 0,
            'message' => 'File upload failed',
            'errors' => []
        ]);
        exit;
        }
}

// Note: Deletion is now handled via AJAX calls to /api/delete_program.php and /api/delete_all_programs.php
// This provides real-time UI updates and better error handling

// Handle program editing
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_event'])) {
    $programId = $_POST['program_id'];
    $title = $_POST['eventTitle'];
    $description = $_POST['eventDescription'];
    $date_time = $_POST['eventDate'];
    $location = $_POST['eventLocation'];
    $organizer = $_POST['eventOrganizer'];
    
    try {
        // Get database connection
        $db = DatabaseAPI::getInstance();
        
        // Update the event in the database using universalUpdate
        $updateData = [
            'title' => $title,
            'description' => $description,
            'date_time' => $date_time,
            'location' => $location,
            'organizer' => $organizer
        ];
        
        $result = $db->universalUpdate('programs', $updateData, 'program_id = ?', [$programId]);
        
        if (!$result['success']) {
            throw new Exception("Failed to update event: " . $result['message']);
        }
        
        // Redirect to refresh the page and show success message
        header("Location: event.php?updated=1&updated_id=" . $programId);
        exit();
    } catch(PDOException $e) {
        $errorMessage = "Error updating program: " . $e->getMessage();
    }
}

// Get event data for editing - Now fetches from unified_api.php
$editEvent = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editId = $_GET['edit'];
    try {
        // Get database connection
        $db = DatabaseAPI::getInstance();
        
        // First try to get from local database
        $result = $db->universalSelect('programs', '*', 'program_id = ?', '', '', [$editId]);
        $editEvent = $result['success'] && !empty($result['data']) ? $result['data'][0] : null;
        
        // If not found locally, try to get from unified_api.php
        if (!$editEvent) {
            $apiUrl = 'https://nutrisaur-production.up.railway.app/api/DatabaseAPI.php';
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
    <title>NutriSaur Event Form</title>
    
</head>
<style>
/* Dark Theme (Default) - Softer colors */
.dark-theme {
    --color-bg: #1A211A;
    --color-card: #2A3326;
    --color-highlight: #A1B454;
    --color-text: #E8F0D6;
    --color-accent1: #8CA86E;
    --color-accent2: #B5C88D;
    --color-accent3: #546048;
    --color-accent4: #C9D8AA;
    --color-danger: #CF8686;
    --color-warning: #E0C989;
}

/* Light Theme - Softer colors */
.light-theme {
    --color-bg: #F5F8F0;
    --color-card: #FFFFFF;
    --color-highlight: #76BB6E;
    --color-text: #1B3A1B;
    --color-accent1: #F9B97F;
    --color-accent2: #E9957C;
    --color-accent3: #76BB6E;
    --color-accent4: #D7E3A0;
    --color-danger: #E98D7C;
    --color-warning: #F9C87F;
    --color-border: rgba(118, 187, 110, 0.2);
    --color-shadow: rgba(118, 187, 110, 0.1);
    --color-hover: rgba(118, 187, 110, 0.08);
    --color-active: rgba(118, 187, 110, 0.15);
}

.light-theme body {
    background: linear-gradient(135deg, #DCE8C0, #C5DBA1);
    background-size: 400% 400%;
    animation: gradientBackground 15s ease infinite;
}

@keyframes gradientBackground {
    0% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    transition: background-color 0.4s ease, color 0.4s ease, border-color 0.4s ease, box-shadow 0.4s ease;
}

body {
    min-height: 200vh;
    background-color: var(--color-bg);
    color: var(--color-text);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    padding: 20px;
    padding-left: 60px; /* Space for minimized navbar + margin */
    line-height: 1.6;
    letter-spacing: 0.2px;
    transition: padding-left 0.4s ease;
}

.light-theme body {
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100" height="100" opacity="0.1"><path d="M10,10 Q50,20 90,10 Q80,50 90,90 Q50,80 10,90 Q20,50 10,10 Z" fill="%2376BB43"/></svg>');
    background-size: 300px;
}

.dashboard {
    max-width: 100%;
    width: 100%;
    margin: 0 auto;
    transition: max-width 0.4s ease;
}



.feature-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 25px;
    margin-bottom: 30px;
}

.feature-card {
    background-color: var(--color-card);
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
    position: relative;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    cursor: pointer;
    display: flex;
    flex-direction: column;
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
}

.light-theme .feature-card {
    border: none;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
}

.feature-icon {
    width: 60px;
    height: 60px;
    background-color: rgba(161, 180, 84, 0.2);
    border-radius: 15px;
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 20px;
    font-size: 30px;
}

.light-theme .feature-icon {
    background-color: rgba(142, 185, 110, 0.2);
}

.feature-card h3 {
    font-size: 20px;
    margin-bottom: 15px;
    color: var(--color-highlight);
}

.feature-card p {
    font-size: 15px;
    opacity: 0.9;
    margin-bottom: 20px;
    flex-grow: 1;
}

.feature-action {
    display: flex;
    align-items: center;
    font-weight: 500;
    color: var(--color-highlight);
    margin-top: auto;
}

.feature-action span {
    margin-left: 8px;
    font-size: 18px;
}

/* Chat logs container */
.chat-logs-container {
    background-color: var(--color-card);
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
    margin-bottom: 30px;
}

.light-theme .chat-logs-container {
    border: none;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
}

.chat-logs-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.chat-logs-title {
    font-size: 20px;
    color: var(--color-highlight);
}

.chat-logs-controls {
    display: flex;
    gap: 15px;
    align-items: center;
}

.chat-filter {
    display: flex;
    align-items: center;
    gap: 8px;
    background-color: rgba(161, 180, 84, 0.1);
    padding: 8px 15px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.chat-filter:hover {
    background-color: rgba(161, 180, 84, 0.2);
}

.chat-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.chat-item {
    background-color: rgba(42, 51, 38, 0.7);
    border-radius: 15px;
    padding: 0;
    overflow: hidden;
}

.light-theme .chat-item {
    background-color: rgba(234, 240, 220, 0.7);
}

.chat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background-color: rgba(0, 0, 0, 0.1);
    cursor: pointer;
}

.chat-user {
    display: flex;
    align-items: center;
    gap: 10px;
}

.chat-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background-color: var(--color-accent3);
    display: flex;
    justify-content: center;
    align-items: center;
    color: var(--color-text);
    font-weight: bold;
    font-size: 14px;
}

.light-theme .chat-avatar {
    color: white;
}

.chat-meta {
    font-size: 12px;
    opacity: 0.7;
}

.chat-rating {
    display: flex;
    align-items: center;
    gap: 5px;
}

.rating-high {
    color: var(--color-accent3);
}

.rating-medium {
    color: var(--color-warning);
}

.rating-low {
    color: var(--color-danger);
}

.chat-content {
    display: none;
    padding: 15px 20px;
    max-height: 400px;
    overflow-y: auto;
}

.chat-content.active {
    display: block;
}

.chat-message {
    display: flex;
    margin-bottom: 15px;
}

.chat-message.user {
    flex-direction: row-reverse;
}

.message-bubble {
    max-width: 80%;
    padding: 12px;
    border-radius: 10px;
    position: relative;
}

.user .message-bubble {
    background-color: rgba(161, 180, 84, 0.3);
    border-top-right-radius: 0;
}

.ai .message-bubble {
    background-color: rgba(42, 51, 38, 0.6);
    border-top-left-radius: 0;
}

.light-theme .ai .message-bubble {
    background-color: rgba(142, 185, 110, 0.2);
}

.message-time {
    font-size: 10px;
    opacity: 0.7;
    margin-top: 5px;
    text-align: right;
}

.chat-action-buttons {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 0 20px 15px;
}

.chat-action-btn {
    font-size: 12px;
    padding: 6px 12px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    background-color: rgba(161, 180, 84, 0.2);
    color: var(--color-text);
    transition: all 0.3s ease;
}

.chat-action-btn:hover {
    background-color: rgba(161, 180, 84, 0.4);
}

/* Analytics container */
.analytics-container {
    background-color: var(--color-card);
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
    margin-bottom: 30px;
}

.light-theme .analytics-container {
    border: none;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
    background: linear-gradient(135deg, rgba(234, 240, 220, 0.8), rgba(234, 240, 220, 0.9));
}

.analytics-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.analytics-header h2 {
    color: var(--color-highlight);
    font-size: 24px;
}

.analytics-controls select {
    padding: 8px 15px;
    border-radius: 8px;
    background-color: var(--color-bg);
    color: var(--color-text);
    border: 1px solid var(--color-accent3);
    cursor: pointer;
}

.analytics-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.analytics-card {
    background-color: rgba(42, 51, 38, 0.7);
    border-radius: 15px;
    padding: 20px;
    height: 300px;
}

.analytics-card-header {
    margin-bottom: 15px;
}

.analytics-card-header h3 {
    color: var(--color-highlight);
    font-size: 18px;
}

.analytics-content {
    height: calc(100% - 40px);
    overflow-y: auto;
}

.topic-list, .questions-list, .feedback-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.topic-item, .question-item, .feedback-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    background-color: rgba(161, 180, 84, 0.1);
    border-radius: 8px;
    transition: all 0.3s ease;
}

.topic-item:hover, .question-item:hover, .feedback-item:hover {
    background-color: rgba(161, 180, 84, 0.2);
    transform: translateX(5px);
}

.topic-name, .question-text, .feedback-text {
    font-weight: 500;
}

.topic-count, .question-count {
    color: var(--color-accent1);
    font-size: 14px;
}

.engagement-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    padding: 10px;
}

.stat-item {
    text-align: center;
    padding: 15px;
    background-color: rgba(161, 180, 84, 0.1);
    border-radius: 10px;
}

.stat-value {
    display: block;
    font-size: 24px;
    font-weight: 600;
    color: var(--color-highlight);
    margin-bottom: 5px;
}

.stat-label {
    font-size: 14px;
    opacity: 0.8;
}

.feedback-item.positive {
    border-left: 4px solid var(--color-highlight);
}

.feedback-item.negative {
    border-left: 4px solid var(--color-warning);
}

.feedback-rating {
    color: var(--color-warning);
    font-size: 14px;
}

@media (max-width: 768px) {
    .analytics-grid {
        grid-template-columns: 1fr;
    }
    
    .engagement-stats {
        grid-template-columns: 1fr;
    }
    
    .create-event-btn {
        padding: 14px 24px;
        font-size: 15px;
        min-width: 180px;
        gap: 10px;
    }
    
    .create-event-btn .btn-icon {
        font-size: 18px;
    }
    
    .create-event-btn .btn-arrow {
        font-size: 16px;
    }
}

@media (max-width: 480px) {
    .create-event-btn {
        padding: 12px 20px;
        font-size: 14px;
        min-width: 160px;
        gap: 8px;
        flex-direction: column;
        min-height: 60px;
    }
    
    .create-event-btn .btn-icon {
        font-size: 16px;
    }
    
    .create-event-btn .btn-text {
        font-size: 13px;
    }
    
    .create-event-btn .btn-arrow {
        font-size: 14px;
    }
    
    .form-actions {
        justify-content: center;
    }
}

/* Training data container */
.training-container {
    background-color: var(--color-card);
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
    margin-bottom: 30px;
}

.light-theme .training-container {
    border: none;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
}

.training-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.training-title {
    font-size: 20px;
    color: var(--color-highlight);
}

.training-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.training-tab {
    padding: 8px 15px;
    border-radius: 8px;
    background-color: rgba(161, 180, 84, 0.1);
    cursor: pointer;
    transition: all 0.3s ease;
}

.training-tab.active {
    background-color: var(--color-highlight);
    color: white;
}

.light-theme .training-tab.active {
    color: var(--color-text);
}

.training-tab:hover:not(.active) {
    background-color: rgba(161, 180, 84, 0.2);
}

.training-content {
    background-color: rgba(42, 51, 38, 0.7);
    border-radius: 15px;
    padding: 20px;
}

.light-theme .training-content {
    background-color: rgba(234, 240, 220, 0.7);
}

.training-group {
    margin-bottom: 20px;
}

.training-group-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    cursor: pointer;
}

.training-group-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 500;
}

.training-group-icon {
    font-size: 18px;
    transition: transform 0.3s ease;
}

.training-group.open .training-group-icon {
    transform: rotate(90deg);
}

.training-group-content {
    display: none;
    border-left: 2px solid var(--color-highlight);
    padding-left: 15px;
    margin-left: 5px;
}

.training-group.open .training-group-content {
    display: block;
}

.training-item {
    margin-bottom: 10px;
    padding: 10px;
    border-radius: 8px;
    background-color: rgba(161, 180, 84, 0.05);
}

.training-item-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
}

.training-item-title {
    font-weight: 500;
}

.training-item-content {
    font-size: 14px;
    opacity: 0.9;
}

.training-item-responses {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid rgba(161, 180, 84, 0.2);
}

.training-response {
    font-size: 14px;
    margin-bottom: 8px;
    padding-left: 10px;
    position: relative;
}

.training-response::before {
    content: '•';
    position: absolute;
    left: 0;
    color: var(--color-highlight);
}

.training-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
}

.training-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 15px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s ease;
}



/* Enhanced Create Event Button - Updated to match btn-add styling */
.create-event-btn {
    background-color: var(--color-highlight);
    color: white;
    padding: 10px 16px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s ease;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-width: 200px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.create-event-btn:hover {
    background-color: var(--color-accent1);
    transform: translateY(-1px);
}

.create-event-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.create-event-btn:focus {
    outline: 2px solid var(--color-highlight);
    outline-offset: 2px;
    transform: translateY(-1px);
}

.create-event-btn .btn-text {
    font-size: 14px;
    font-weight: 600;
    color: white;
}

/* Light theme button styles */
.light-theme .create-event-btn {
    background-color: var(--color-highlight);
    color: white !important;
}

.light-theme .create-event-btn:hover {
    background-color: var(--color-accent3);
    color: white !important;
}



.training-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

/* Improved navigation bar */
.navbar {
    position: fixed;
    top: 0;
    left: 0;
    width: 320px;
    height: 100vh;
    background-color: var(--color-card);
    box-shadow: 3px 0 15px rgba(0, 0, 0, 0.1);
    padding: 0;
    box-sizing: border-box;
    overflow-y: auto;
    z-index: 1000;
    display: flex;
    flex-direction: column;
    backdrop-filter: blur(10px);
}

.navbar-header {
    padding: 35px 25px;
    display: flex;
    align-items: center;
    border-bottom: 2px solid rgba(164, 188, 46, 0.15);
    background: linear-gradient(135deg, rgba(161, 180, 84, 0.05) 0%, transparent 100%);
    position: relative;
    overflow: hidden;
}

.navbar-header::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(161, 180, 84, 0.3), transparent);
}

.light-theme .navbar-header {
    background: linear-gradient(135deg, rgba(142, 185, 110, 0.05) 0%, transparent 100%);
}

.light-theme .navbar-header::after {
    background: linear-gradient(90deg, transparent, rgba(142, 185, 110, 0.3), transparent);
}

.navbar-logo {
    display: flex;
    align-items: center;
    transition: transform 0.3s ease;
}

.navbar-logo:hover {
    transform: scale(1.05);
}

.navbar-logo-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    margin-right: 20px;
    display: flex;
    justify-content: center;
    align-items: center;
    color: var(--color-text);
    font-weight: bold;
    font-size: 20px;
    background: linear-gradient(135deg, rgba(161, 180, 84, 0.1), rgba(161, 180, 84, 0.05));
    border: 1px solid rgba(161, 180, 84, 0.2);
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(161, 180, 84, 0.1);
}

.navbar-logo:hover .navbar-logo-icon {
    background: linear-gradient(135deg, rgba(161, 180, 84, 0.15), rgba(161, 180, 84, 0.08));
    border-color: rgba(161, 180, 84, 0.3);
    box-shadow: 0 4px 15px rgba(161, 180, 84, 0.2);
}

.light-theme .navbar-logo-icon {
    background: linear-gradient(135deg, rgba(142, 185, 110, 0.1), rgba(142, 185, 110, 0.05));
    border-color: rgba(142, 185, 110, 0.2);
    box-shadow: 0 2px 8px rgba(142, 185, 110, 0.1);
}

.light-theme .navbar-logo:hover .navbar-logo-icon {
    background: linear-gradient(135deg, rgba(142, 185, 110, 0.15), rgba(142, 185, 110, 0.08));
    border-color: rgba(142, 185, 110, 0.3);
    box-shadow: 0 4px 15px rgba(142, 185, 110, 0.2);
}

.navbar-logo-text {
    font-size: 24px;
    font-weight: 600;
    color: var(--color-text);
}

.navbar-menu {
    flex: 1;
    padding: 30px 0;
}

.navbar ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.navbar li {
    margin-bottom: 2px;
    position: relative;
    transition: all 0.3s ease;
}

.navbar li:hover {
    transform: translateX(5px);
}

.navbar li:not(:last-child) {
    border-bottom: 1px solid rgba(161, 180, 84, 0.08);
}

.light-theme .navbar li:not(:last-child) {
    border-bottom: 1px solid rgba(142, 185, 110, 0.08);
}

.navbar a {
    text-decoration: none;
    color: var(--color-text);
    font-size: 17px;
    padding: 18px 25px;
    display: flex;
    align-items: center;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    opacity: 0.9;
    border-radius: 0 12px 12px 0;
    margin-right: 10px;
    overflow: hidden;
    background: linear-gradient(90deg, transparent 0%, transparent 100%);
}

.navbar a::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(161, 180, 84, 0.1), transparent);
    transition: left 0.5s ease;
}

.light-theme .navbar a::before {
    background: linear-gradient(90deg, transparent, rgba(142, 185, 110, 0.1), transparent);
}

.navbar a:hover {
    background: linear-gradient(90deg, rgba(161, 180, 84, 0.08) 0%, rgba(161, 180, 84, 0.04) 100%);
    color: var(--color-highlight);
    opacity: 1;
    transform: translateX(3px);
    box-shadow: 0 4px 15px rgba(161, 180, 84, 0.15);
}

.navbar a:hover::before {
    left: 100%;
}

.navbar a.active {
    background: linear-gradient(90deg, rgba(161, 180, 84, 0.15) 0%, rgba(161, 180, 84, 0.08) 100%);
    color: var(--color-highlight);
    opacity: 1;
    font-weight: 600;
    border-left: 4px solid var(--color-highlight);
    box-shadow: 0 6px 20px rgba(161, 180, 84, 0.2);
    transform: translateX(2px);
}

.light-theme .navbar a:hover {
    background: linear-gradient(90deg, rgba(142, 185, 110, 0.08) 0%, rgba(142, 185, 110, 0.04) 100%);
    box-shadow: 0 4px 15px rgba(142, 185, 110, 0.15);
}

.light-theme .navbar a.active {
    background: linear-gradient(90deg, rgba(142, 185, 110, 0.15) 0%, rgba(142, 185, 110, 0.08) 100%);
    border-left-color: var(--color-accent3);
    box-shadow: 0 6px 20px rgba(142, 185, 110, 0.2);
}

.navbar-icon {
    margin-right: 15px;
    width: 24px;
    font-size: 20px;
}

.navbar-footer {
    padding: 25px 20px;
    border-top: 2px solid rgba(164, 188, 46, 0.15);
    font-size: 12px;
    opacity: 0.7;
    text-align: center;
    background: linear-gradient(135deg, transparent 0%, rgba(161, 180, 84, 0.03) 100%);
    position: relative;
}

.navbar-footer::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(161, 180, 84, 0.2), transparent);
}

.light-theme .navbar-footer {
    background: linear-gradient(135deg, transparent 0%, rgba(142, 185, 110, 0.03) 100%);
}

.light-theme .navbar-footer::before {
    background: linear-gradient(90deg, transparent, rgba(142, 185, 110, 0.2), transparent);
}

.navbar-footer div:first-child {
    font-weight: 600;
    color: var(--color-highlight);
    margin-bottom: 8px;
}

.light-theme .navbar-footer div:first-child {
    color: var(--color-accent3);
}

/* Main content */
.main {
    padding: 20px;
}

@media (max-width: 768px) {
    .navbar {
        width: 80px;
        transform: translateX(0);
        transition: transform 0.3s ease, width 0.3s ease;
    }
    
    .navbar:hover {
        width: 320px;
    }
    
    .navbar-logo-text, .navbar span:not(.navbar-icon) {
        opacity: 0;
        transition: opacity 0.2s ease;
    }
    
    .navbar:hover .navbar-logo-text, 
    .navbar:hover span:not(.navbar-icon) {
        opacity: 1;
    }
    
    body {
        padding-left: 100px;
    }
}

.light-theme .navbar {
    background-color: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    box-shadow: 3px 0 20px rgba(118, 187, 110, 0.1);
}

/* Light theme comprehensive styling */
.light-theme * {
    color: inherit;
}

.light-theme h1, .light-theme h2, .light-theme h3, .light-theme h4, .light-theme h5, .light-theme h6,
.light-theme p, .light-theme span, .light-theme div, .light-theme label, .light-theme strong, .light-theme b {
    color: #1B3A1B !important;
}

.light-theme .logo h1 {
    color: #1B3A1B !important;
}

.light-theme .user-info span {
    color: #1B3A1B !important;
}

.light-theme .event-header h2 {
    color: #1B3A1B !important;
}

.light-theme .csv-upload-header h3 {
    color: #1B3A1B !important;
}

.light-theme .csv-upload-header p {
    color: #1B3A1B !important;
}

.light-theme .upload-text h4 {
    color: #1B3A1B !important;
}

.light-theme .upload-text p {
    color: #1B3A1B !important;
}

.light-theme .csv-format {
    color: #1B3A1B !important;
}

.light-theme .csv-preview h4 {
    color: #1B3A1B !important;
}

.light-theme .table-header h2 {
    color: #1B3A1B !important;
}

.light-theme .form-group label {
    color: #1B3A1B !important;
}

.light-theme .form-group input,
.light-theme .form-group select,
.light-theme .form-group textarea {
    color: #1B3A1B !important;
}

.light-theme .form-group input::placeholder,
.light-theme .form-group textarea::placeholder {
    color: #666 !important;
}

.light-theme .table-controls select {
    color: #1B3A1B !important;
}

.light-theme .events-table th {
    color: #1B3A1B !important;
}

.light-theme .events-table td {
    color: #1B3A1B !important;
}

.light-theme .status-badge {
    color: #1B3A1B !important;
}

.light-theme .action-btn {
    color: #1B3A1B !important;
}

.light-theme .delete-all-btn {
    color: #1B3A1B !important;
}

.light-theme .modal-header h3 {
    color: #1B3A1B !important;
}

.light-theme .modal .form-group label {
    color: #1B3A1B !important;
}

.light-theme .modal .form-group input,
.light-theme .modal .form-group select,
.light-theme .modal .form-group textarea {
    color: #1B3A1B !important;
}

.light-theme .close {
    color: #1B3A1B !important;
}

.light-theme .select-header {
    color: #1B3A1B !important;
}

.light-theme .option-header {
    color: #1B3A1B !important;
}

.light-theme .option-item {
    color: #1B3A1B !important;
}

.light-theme .search-container input {
    color: #1B3A1B !important;
}

.light-theme .search-container input::placeholder {
    color: #666 !important;
}

.light-theme .preview-table th {
    color: #1B3A1B !important;
}

.light-theme .preview-table td {
    color: #1B3A1B !important;
}

.light-theme .navbar-logo-text {
    color: #1B3A1B !important;
}

.light-theme .navbar a {
    color: #1B3A1B !important;
}

.light-theme .navbar-footer div {
    color: #1B3A1B !important;
}

.light-theme .navbar-footer div:first-child {
    color: #76BB6E !important;
}

/* Additional light theme styling for better consistency */
.light-theme .event-container {
    background: #FFFFFF !important;
    border: 1px solid rgba(118, 187, 110, 0.2);
    box-shadow: 0 6px 20px rgba(118, 187, 110, 0.08);
}

.light-theme .events-table-container,
.light-theme .csv-upload-section {
    background: #FFFFFF !important;
    border: 1px solid rgba(118, 187, 110, 0.2);
    box-shadow: 0 6px 20px rgba(118, 187, 110, 0.08);
}

.light-theme .form-group input,
.light-theme .form-group select,
.light-theme .form-group textarea {
    background-color: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(118, 187, 110, 0.3);
}

.light-theme .form-group input:focus,
.light-theme .form-group select:focus,
.light-theme .form-group textarea:focus {
    border-color: #76BB6E;
    box-shadow: 0 0 0 2px rgba(118, 187, 110, 0.2);
}

.light-theme .table-controls select {
    background-color: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(118, 187, 110, 0.3);
}

.light-theme .events-table tr:hover {
    background-color: rgba(118, 187, 110, 0.05);
}

.light-theme .csv-upload-area {
    background: rgba(255, 255, 255, 0.9);
    border: 2px dashed rgba(118, 187, 110, 0.4);
}

.light-theme .csv-upload-area:hover {
    background: linear-gradient(135deg, rgba(118, 187, 110, 0.08), rgba(118, 187, 110, 0.05));
    border-color: #76BB6E;
}

.light-theme .csv-preview {
    background-color: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(118, 187, 110, 0.2);
}

.light-theme .preview-table th {
    background-color: rgba(118, 187, 110, 0.1);
}

.light-theme .modal-content {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(245, 248, 240, 0.95));
    border: 1px solid rgba(118, 187, 110, 0.3);
}

.light-theme .select-header {
    background-color: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(118, 187, 110, 0.3);
}

.light-theme .dropdown-content {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(245, 248, 240, 0.95));
    border: 1px solid rgba(118, 187, 110, 0.3);
}

.light-theme .option-header {
    background-color: rgba(118, 187, 110, 0.1);
}

.light-theme .option-item:hover {
    background-color: rgba(118, 187, 110, 0.1);
}

.light-theme .search-container input {
    background-color: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(118, 187, 110, 0.3);
}

.light-theme .search-container input:focus {
    border-color: #76BB6E;
    box-shadow: 0 0 0 2px rgba(118, 187, 110, 0.2);
}



.light-theme .alert-success {
    background-color: rgba(118, 187, 110, 0.15);
    color: #1B3A1B;
    border-left: 4px solid #76BB6E;
}

.light-theme .alert-danger {
    background-color: rgba(233, 141, 124, 0.15);
    color: #1B3A1B;
    border-left: 4px solid #E98D7C;
}

/* Make event container pure white in light theme */
.light-theme .event-container {
    background: #FFFFFF !important;
    border: 1px solid rgba(118, 187, 110, 0.2);
    box-shadow: 0 6px 20px rgba(118, 187, 110, 0.08);
}

/* Add this media query for responsive adjustments */
@media (max-width: 768px) {
    .navbar a {
        padding: 12px 25px;  /* Slightly reduce vertical padding for mobile */
    }
    
    .navbar li {
        margin-bottom: 2px;  /* Further reduce spacing on mobile */
    }
}

/* Custom scrollbar - Add this to match USM.html */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: var(--background-color);
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, var(--secondary-color) 0%, #7cb342 100%);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #7cb342 0%, #689f38 100%);
}

/* Chat interface styles */
.chat-container {
    background-color: var(--color-card);
    border-radius: 20px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
    margin-bottom: 30px;
    display: flex;
    flex-direction: column;
    height: calc(100vh - 200px);
    min-height: 600px;
    overflow: hidden;
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 30px;
    display: flex;
    flex-direction: column;
}

.welcome-message {
    text-align: center;
    padding: 40px 20px;
    margin-bottom: 30px;
}

.welcome-message h2 {
    color: var(--color-highlight);
    font-size: 28px;
    margin-bottom: 15px;
}

.welcome-message p {
    color: var(--color-text);
    font-size: 16px;
    opacity: 0.9;
}

.message {
    display: flex;
    margin-bottom: 20px;
    animation: messageAppear 0.3s ease;
}

@keyframes messageAppear {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.message.user-message {
    justify-content: flex-end;
}

.message-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    color: var(--color-text);
    font-weight: bold;
    margin-right: 12px;
}

.message.user-message .message-avatar {
    background-color: var(--color-accent3);
    order: 2;
    margin-right: 0;
    margin-left: 12px;
}

.message.ai-message .message-avatar {
    background-color: var(--color-highlight);
}

.light-theme .message-avatar {
    color: white;
}

.message-content {
    max-width: 80%;
    padding: 15px;
    border-radius: 18px;
    position: relative;
}

.message.user-message .message-content {
    background-color: var(--color-highlight);
    color: var(--color-text);
    border-top-right-radius: 4px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.message.ai-message .message-content {
    background-color: rgba(42, 51, 38, 0.6);
    color: var(--color-text);
    border-top-left-radius: 4px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(161, 180, 84, 0.2);
}

.light-theme .message.ai-message .message-content {
    background-color: rgba(142, 185, 110, 0.2);
    border: 1px solid rgba(142, 185, 110, 0.3);
}

.message-time {
    font-size: 11px;
    opacity: 0.7;
    margin-top: 6px;
    text-align: right;
    color: var(--color-text);
}

.message-typing {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 0 5px;
    background-color: rgba(42, 51, 38, 0.6);
    border-radius: 18px;
    border: 1px solid rgba(161, 180, 84, 0.2);
}

.light-theme .message-typing {
    background-color: rgba(142, 185, 110, 0.2);
    border: 1px solid rgba(142, 185, 110, 0.3);
}

.chat-input-container {
    padding: 20px;
    border-top: 1px solid rgba(164, 188, 46, 0.2);
    display: flex;
    gap: 10px;
    align-items: center;
}

#chat-input {
    flex: 1;
    padding: 15px;
    border-radius: 25px;
    border: 1px solid rgba(164, 188, 46, 0.3);
    background-color: rgba(42, 51, 38, 0.3);
    color: var(--color-text);
    font-size: 15px;
    resize: none;
    outline: none;
    font-family: inherit;
    transition: all 0.3s ease;
    max-height: 150px;
}

.light-theme #chat-input {
    background-color: rgba(234, 240, 220, 0.7);
    color: var(--color-text);
}

#chat-input:focus {
    border-color: var(--color-highlight);
    box-shadow: 0 0 0 2px rgba(161, 180, 84, 0.2);
}

#send-button {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background-color: var(--color-highlight);
    color: var(--color-text);
    border: none;
    cursor: pointer;
    display: flex;
    justify-content: center;
    align-items: center;
    transition: all 0.3s ease;
}

.light-theme #send-button {
    color: white;
}

#send-button:hover {
    background-color: var(--color-accent3);
    transform: scale(1.05);
}

#send-button svg {
    width: 20px;
    height: 20px;
}

.light-theme .analytics-card {
    background: linear-gradient(135deg, rgba(234, 240, 220, 0.9), rgba(234, 240, 220, 0.7));
    border: 1px solid rgba(142, 185, 110, 0.2);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
}

.light-theme .topic-item,
.light-theme .question-item,
.light-theme .feedback-item {
    background: rgba(234, 240, 220, 0.8);
    border: 1px solid rgba(142, 185, 110, 0.1);
}

.light-theme .topic-item:hover,
.light-theme .question-item:hover,
.light-theme .feedback-item:hover {
    background: rgba(234, 240, 220, 0.95);
    border-color: var(--color-accent3);
    transform: translateX(5px);
}

.light-theme .stat-item {
    background: rgba(234, 240, 220, 0.8);
    border: 1px solid rgba(142, 185, 110, 0.1);
}

.light-theme .stat-value {
    color: var(--color-accent3);
}

.light-theme .feedback-item.positive {
    border-left: 4px solid var(--color-accent3);
}

.light-theme .feedback-item.negative {
    border-left: 4px solid var(--color-warning);
}

.light-theme .analytics-card-header h3 {
    color: var(--color-accent3);
}

.light-theme .topic-count,
.light-theme .question-count {
    color: var(--color-accent3);
}

.light-theme .feedback-rating {
    color: var(--color-accent3);
}

.light-theme .analytics-controls select {
    background-color: rgba(234, 240, 220, 0.9);
    border: 1px solid rgba(142, 185, 110, 0.3);
    color: var(--color-text);
}

.light-theme .analytics-controls select:focus {
    border-color: var(--color-accent3);
    box-shadow: 0 0 0 2px rgba(142, 185, 110, 0.2);
}

.light-theme .topic-name,
.light-theme .question-text,
.light-theme .feedback-text {
    color: var(--color-text);
}

.light-theme .stat-label {
    color: var(--color-text);
    opacity: 0.8;
}

.light-theme .analytics-header h2 {
    color: var(--color-accent3);
}

/* Event Container Styles */
.event-container {
    background-color: var(--color-card);
    border-radius: 20px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
}

.event-header h2 {
    color: var(--color-highlight);
    margin-bottom: 20px;
}

.event-form {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group label {
    color: var(--color-text);
    font-weight: 500;
}

.form-group input,
.form-group select,
.form-group textarea {
    padding: 12px;
    border-radius: 8px;
    border: 1px solid rgba(161, 180, 84, 0.2);
    background-color: rgba(42, 51, 38, 0.3);
    color: var(--color-text);
    font-size: 14px;
}

.form-group textarea {
    min-height: 100px;
    resize: vertical;
}

.form-actions {
    grid-column: 1 / -1;
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    margin-top: 20px;
}

/* Events Table Styles */
.events-table-container {
    background-color: var(--color-card);
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
}

.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.table-header h2 {
    color: var(--color-highlight);
}

.table-controls {
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
}

.table-controls select {
    padding: 8px 15px;
    border-radius: 8px;
    background-color: rgba(42, 51, 38, 0.3);
    color: var(--color-text);
    border: 1px solid rgba(161, 180, 84, 0.2);
}

.events-table {
    width: 100%;
    border-collapse: collapse;
}

.events-table th,
.events-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid rgba(161, 180, 84, 0.1);
}

.events-table th {
    color: var(--color-highlight);
    font-weight: 500;
}

.events-table tr:hover {
    background-color: rgba(161, 180, 84, 0.05);
}

.status-badge {
    padding: 6px 12px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 500;
}

.status-upcoming {
    background-color: rgba(161, 180, 84, 0.2);
    color: var(--color-highlight);
}

.status-past {
    background-color: rgba(207, 134, 134, 0.2);
    color: var(--color-danger);
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.action-btn {
    background-color: var(--color-highlight);
    color: white;
    padding: 10px 16px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s ease;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin: 0 4px;
    min-width: 60px;
    max-width: 80px;
    position: relative;
    z-index: 10;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.edit-btn {
    background-color: var(--color-highlight);
    color: white;
}

.delete-btn {
    background-color: var(--color-danger);
    color: white;
}

.action-btn:hover {
    background-color: var(--color-accent1);
    transform: translateY(-1px);
}

.action-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.action-btn:focus {
    outline: 2px solid var(--color-highlight);
    outline-offset: 2px;
    transform: translateY(-1px);
}

/* Light theme button styles */
.light-theme .edit-btn {
    background-color: var(--color-highlight);
    color: white !important;
}

.light-theme .delete-btn {
    background-color: var(--color-danger);
    color: white !important;
}

.light-theme .action-btn:hover {
    background-color: var(--color-accent3);
    color: white !important;
}

/* Button Styles - Matching settings.php .btn-add styling */
.btn {
    padding: 14px 28px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    font-weight: 700;
    font-size: 16px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.btn-add {
    background-color: var(--color-highlight);
    color: white !important;
    padding: 10px 16px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s ease;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-icon {
    font-size: 16px;
    line-height: 1;
}

.btn-text {
    font-size: 14px;
    font-weight: 600;
}

.btn-secondary {
    background-color: var(--color-accent3);
    color: white !important;
    padding: 10px 16px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s ease;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-secondary:hover {
    background-color: var(--color-accent2);
    transform: translateY(-1px);
}

.light-theme .btn-secondary {
    background-color: var(--color-accent3);
    color: white !important;
}

.light-theme .btn-secondary:hover {
    background-color: var(--color-accent2);
    color: white !important;
}

.btn-danger {
    background-color: var(--color-danger);
    color: white;
    padding: 10px 16px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s ease;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-danger:hover {
    background-color: var(--color-accent1);
    transform: translateY(-1px);
}

.light-theme .btn-danger {
    background-color: var(--color-danger);
    color: white !important;
}

.light-theme .btn-danger:hover {
    background-color: var(--color-accent3);
    color: white !important;
}

/* Light theme button styles */
.light-theme .btn-add {
    background-color: var(--color-highlight);
    color: white !important;
}

.btn-add:hover {
    background-color: var(--color-accent1);
    transform: translateY(-1px);
}

.light-theme .btn-add:hover {
    background-color: var(--color-accent3);
    color: white !important;
}

/* Delete All Button Styles - Updated to match .btn-add */
.delete-all-btn {
    background-color: var(--color-highlight);
    color: white;
    padding: 10px 16px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s ease;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-left: 15px;
    min-width: 140px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.delete-all-btn:hover {
    background-color: var(--color-accent1);
    transform: translateY(-1px);
}

.light-theme .delete-all-btn {
    background-color: var(--color-highlight);
}

.light-theme .delete-all-btn:hover {
    background-color: var(--color-accent3);
}

/* Light Theme Styles */
.light-theme .event-container,
.light-theme .events-table-container {
    background: linear-gradient(135deg, rgba(234, 240, 220, 0.8), rgba(234, 240, 220, 0.9));
    border: 1px solid rgba(142, 185, 110, 0.2);
}

.light-theme .form-group input,
.light-theme .form-group select,
.light-theme .form-group textarea {
    background-color: rgba(234, 240, 220, 0.7);
    border: 1px solid rgba(142, 185, 110, 0.3);
    color: var(--color-text);
}

.light-theme .table-controls select {
    background-color: rgba(234, 240, 220, 0.7);
    border: 1px solid rgba(142, 185, 110, 0.3);
}

.light-theme .events-table tr:hover {
    background-color: rgba(142, 185, 110, 0.1);
}

/* Alert styles */
.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 10px;
    font-weight: 500;
}

.alert-success {
    background-color: rgba(161, 180, 84, 0.2);
    color: var(--color-highlight);
    border-left: 4px solid var(--color-highlight);
}

.alert-danger {
    background-color: rgba(207, 134, 134, 0.2);
    color: var(--color-danger);
    border-left: 4px solid var(--color-danger);
}

.light-theme .alert-success {
    background-color: rgba(142, 185, 110, 0.2);
    color: var(--color-accent3);
    border-left: 4px solid var(--color-accent3);
}

.light-theme .alert-danger {
    background-color: rgba(233, 141, 124, 0.2);
    color: var(--color-danger);
    border-left: 4px solid var(--color-danger);
}

/* CSV Upload Styles */
.csv-upload-section {
    background-color: var(--color-card);
    border-radius: 20px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
    border: 2px dashed rgba(161, 180, 84, 0.3);
    transition: all 0.3s ease;
}

.csv-upload-section:hover {
    border-color: var(--color-highlight);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.csv-upload-header {
    text-align: center;
    margin-bottom: 25px;
}

.csv-upload-header h3 {
    color: var(--color-highlight);
    font-size: 24px;
    margin-bottom: 8px;
}

.csv-upload-header p {
    color: var(--color-text);
    opacity: 0.8;
    font-size: 16px;
}

.csv-upload-container {
    max-width: 600px;
    margin: 0 auto;
}

.csv-upload-area {
    background: linear-gradient(135deg, rgba(161, 180, 84, 0.1), rgba(161, 180, 84, 0.05));
    border: 2px dashed rgba(161, 180, 84, 0.4);
    border-radius: 15px;
    padding: 40px 20px;
    text-align: center;
    cursor: pointer !important;
    transition: all 0.3s ease;
    margin-bottom: 20px;
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    outline: none;
    pointer-events: auto;
}

.csv-upload-area:hover {
    background: linear-gradient(135deg, rgba(161, 180, 84, 0.15), rgba(161, 180, 84, 0.1));
    border-color: var(--color-highlight);
    transform: translateY(-2px);
}

.csv-upload-area:focus {
    outline: none;
    box-shadow: none;
}

.upload-icon {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.8;
}

.upload-text h4 {
    color: var(--color-highlight);
    font-size: 20px;
    margin-bottom: 10px;
}

.upload-text p {
    color: var(--color-text);
    margin-bottom: 8px;
    opacity: 0.9;
}

.csv-format {
    font-size: 12px;
    opacity: 0.7;
    font-style: italic;
    margin-top: 10px;
}

.csv-preview {
    background-color: rgba(42, 51, 38, 0.7);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}

.light-theme .csv-preview {
    background-color: rgba(234, 240, 220, 0.8);
    border: 1px solid rgba(142, 185, 110, 0.2);
}

.csv-preview h4 {
    color: var(--color-highlight);
    margin-bottom: 15px;
    font-size: 16px;
}

.preview-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}

.preview-table th,
.preview-table td {
    padding: 8px;
    text-align: left;
    border-bottom: 1px solid rgba(161, 180, 84, 0.2);
}

.preview-table th {
    background-color: rgba(161, 180, 84, 0.1);
    color: var(--color-highlight);
    font-weight: 500;
}

.csv-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}





.light-theme .csv-upload-section {
    background: linear-gradient(135deg, rgba(234, 240, 220, 0.8), rgba(234, 240, 220, 0.9));
    border: 2px dashed rgba(142, 185, 110, 0.4);
}

.light-theme .csv-upload-area {
    background: linear-gradient(135deg, rgba(142, 185, 110, 0.1), rgba(142, 185, 110, 0.05));
    border: 2px dashed rgba(142, 185, 110, 0.4);
}

.light-theme .csv-upload-area:hover {
    background: linear-gradient(135deg, rgba(142, 185, 110, 0.15), rgba(142, 185, 110, 0.1));
    border-color: var(--color-accent3);
}



/* Drag and drop styles */
.csv-upload-area.dragover {
    background: linear-gradient(135deg, rgba(161, 180, 84, 0.2), rgba(161, 180, 84, 0.15));
    border-color: var(--color-highlight);
    transform: scale(1.02);
}

.light-theme .csv-upload-area.dragover {
    background: linear-gradient(135deg, rgba(142, 185, 110, 0.2), rgba(142, 185, 110, 0.15));
    border-color: var(--color-accent3);
}

/* Header Styles - Both Light and Dark Themes */
header {
    background-color: var(--color-card);
    border-radius: 18px;
    padding: 22px 28px;
    margin-bottom: 30px;
    box-shadow: 0 5px 18px rgba(0, 0, 0, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    border: 1px solid rgba(161, 180, 84, 0.2);
    transition: all 0.3s ease;
}

header:hover {
    box-shadow: 0 7px 22px rgba(0, 0, 0, 0.15);
    transform: translateY(-1px);
}

.logo h1 {
    color: var(--color-text);
    font-size: 28px;
    font-weight: 700;
    margin: 0;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 18px;
}

.user-info .user-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background-color: var(--color-accent3);
    color: var(--color-text);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 19px;
}

.user-info span {
    color: var(--color-text);
    font-weight: 500;
    font-size: 17px;
}

.theme-toggle-btn {
    background: rgba(161, 180, 84, 0.1);
    border: 2px solid var(--color-highlight);
    color: var(--color-highlight);
    padding: 8px 12px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 16px;
    min-width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.theme-toggle-btn:hover {
    background: var(--color-highlight);
    color: var(--color-bg);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(161, 180, 84, 0.3);
}

.theme-toggle-btn .theme-icon {
    font-size: 16px;
    transition: transform 0.3s ease;
}

/* Light Theme Header Overrides */
.light-theme header {
    background: #FFFFFF;
    border: 1px solid rgba(142, 185, 110, 0.3);
    box-shadow: 0 5px 18px rgba(118, 187, 110, 0.1);
}

.light-theme .logo h1 {
    color: var(--color-text);
}

.light-theme .user-info .user-avatar {
    background-color: var(--color-accent3);
    color: var(--color-text);
}

.light-theme .user-info span {
    color: var(--color-text);
}

/* New Theme toggle button - Orange for moon, Black for sun */
.new-theme-toggle-btn {
    background: #FF9800; /* Default orange for moon icon */
    border: none;
    color: #333; /* Dark color for moon icon */
    padding: 10px 15px;
    border-radius: 25px;
    cursor: pointer;
    transition: background-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
    font-size: 18px;
    min-width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 15px rgba(255, 152, 0, 0.3);
    font-weight: bold;
}

.new-theme-toggle-btn:hover {
    background: #F57C00; /* Darker orange on hover */
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 152, 0, 0.4);
}

.new-theme-toggle-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 10px rgba(255, 152, 0, 0.3);
}

.new-theme-toggle-btn .new-theme-icon {
    font-size: 20px;
    transition: transform 0.3s ease;
}

/* Dark theme: Orange background for moon icon */
.dark-theme .new-theme-toggle-btn {
    background: #FF9800;
    color: #333; /* Dark color for moon icon */
    box-shadow: 0 4px 15px rgba(255, 152, 0, 0.3);
}

.dark-theme .new-theme-toggle-btn:hover {
    background: #F57C00;
    box-shadow: 0 6px 20px rgba(255, 152, 0, 0.4);
}

/* Dark theme: Orange moon icon */
.dark-theme .new-theme-toggle-btn .new-theme-icon {
    color: #333;
}

/* Light theme: Black background for sun icon */
.light-theme .new-theme-toggle-btn {
    background: #000000;
    color: #FFFFFF; /* White color for sun icon */
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
}

.light-theme .new-theme-toggle-btn:hover {
    background: #333333;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.5);
}

/* Light theme: White sun icon */
.light-theme .new-theme-toggle-btn .new-theme-icon {
    color: #FFFFFF !important;
}

/* Ensure sun icon is white when light theme is active */
.light-theme #new-theme-toggle .new-theme-icon {
    color: #FFFFFF !important;
}



.light-theme .user-info span {
    color: var(--color-accent3);
}

.light-theme .theme-toggle-btn {
    background-color: var(--color-accent3);
}

.light-theme .theme-toggle-btn:hover {
    background-color: var(--color-highlight);
}



/* Location Dropdown Styles */
.custom-select-container {
    position: relative;
    width: 100%;
}

.select-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    border: 1px solid rgba(161, 180, 84, 0.2);
    border-radius: 8px;
    background-color: rgba(42, 51, 38, 0.3);
    color: var(--color-text);
    cursor: pointer;
    transition: all 0.3s ease;
}

.select-header:hover {
    border-color: var(--color-highlight);
    background-color: rgba(42, 51, 38, 0.4);
}

.dropdown-arrow {
    transition: transform 0.3s ease;
    font-size: 12px;
    opacity: 0.7;
}

.dropdown-arrow.active {
    transform: rotate(180deg);
}

.dropdown-content {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background-color: var(--color-card);
    border: 1px solid rgba(161, 180, 84, 0.2);
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    z-index: 1000;
    max-height: 300px;
    overflow-y: auto;
    display: none;
    margin-top: 5px;
}

.dropdown-content.active {
    display: block;
}

.search-container {
    padding: 16px;
    border-bottom: 1px solid rgba(161, 180, 84, 0.2);
}

.search-container input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid rgba(161, 180, 84, 0.2);
    border-radius: 6px;
    background-color: rgba(42, 51, 38, 0.3);
    color: var(--color-text);
    font-size: 14px;
}

.search-container input:focus {
    outline: none;
    border-color: var(--color-highlight);
}

.options-container {
    max-height: 300px;
    overflow-y: auto;
}

.option-group {
    border-bottom: 1px solid rgba(161, 180, 84, 0.2);
}

.option-header {
    padding: 12px 16px 8px;
    font-weight: 600;
    color: var(--color-highlight);
    background-color: rgba(161, 180, 84, 0.1);
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.option-item {
    padding: 10px 16px;
    cursor: pointer;
    transition: background-color 0.2s ease;
    font-size: 14px;
}

.option-item:hover {
    background-color: rgba(161, 180, 84, 0.1);
}

.option-item.selected {
    background-color: var(--color-highlight);
    color: var(--color-bg);
}

.light-theme .select-header {
    background-color: rgba(234, 240, 220, 0.7);
    border: 1px solid rgba(142, 185, 110, 0.3);
    color: var(--color-text);
}

.light-theme .dropdown-content {
    background: linear-gradient(135deg, rgba(234, 240, 220, 0.95), rgba(234, 240, 220, 0.9));
    border: 1px solid rgba(142, 185, 110, 0.3);
}

.light-theme .search-container input {
    background-color: rgba(234, 240, 220, 0.7);
    border: 1px solid rgba(142, 185, 110, 0.3);
}

.light-theme .option-header {
    background-color: rgba(142, 185, 110, 0.1);
}

.light-theme .option-item:hover {
    background-color: rgba(142, 185, 110, 0.1);
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(5px);
}

.modal-content {
    background-color: var(--color-card);
    margin: 5% auto;
    padding: 0;
    border-radius: 20px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 25px 30px;
    border-bottom: 1px solid rgba(161, 180, 84, 0.2);
}

.modal-header h3 {
    color: var(--color-highlight);
    font-size: 24px;
    margin: 0;
}

.close {
    color: var(--color-text);
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.3s ease;
}

.close:hover {
    color: var(--color-highlight);
}

.modal form {
    padding: 30px;
}

.modal .form-group {
    margin-bottom: 20px;
}

.modal .form-group label {
    display: block;
    color: var(--color-text);
    font-weight: 500;
    margin-bottom: 8px;
}

.modal .form-group input,
.modal .form-group select,
.modal .form-group textarea {
    width: 100%;
    padding: 12px;
    border-radius: 8px;
    border: 1px solid rgba(161, 180, 84, 0.2);
    background-color: rgba(42, 51, 38, 0.3);
    color: var(--color-text);
    font-size: 14px;
    transition: all 0.3s ease;
}

.modal .form-group input:focus,
.modal .form-group select:focus,
.modal .form-group textarea:focus {
    border-color: var(--color-highlight);
    box-shadow: 0 0 0 2px rgba(161, 180, 84, 0.2);
    outline: none;
}

.modal .form-group textarea {
    min-height: 100px;
    resize: vertical;
}

.modal .form-actions {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid rgba(161, 180, 84, 0.2);
}

.light-theme .modal-content {
    background: linear-gradient(135deg, rgba(234, 240, 220, 0.95), rgba(234, 240, 220, 0.9));
    border: 1px solid rgba(142, 185, 110, 0.3);
}

.light-theme .modal .form-group input,
.light-theme .modal .form-group select,
.light-theme .modal .form-group textarea {
    background-color: rgba(234, 240, 220, 0.7);
    border: 1px solid rgba(142, 185, 110, 0.3);
}

.light-theme .modal .form-group input:focus,
.light-theme .modal .form-group select:focus,
.light-theme .modal .form-group textarea:focus {
    border-color: var(--color-accent3);
    box-shadow: 0 0 0 2px rgba(142, 185, 110, 0.2);
}

/* ===== NAVBAR STYLES ===== */
.navbar {
    position: fixed;
    top: 0;
    left: 0;
    width: 320px;
    height: 100vh;
    background-color: var(--color-card);
    box-shadow: 3px 0 15px rgba(0, 0, 0, 0.1);
    padding: 0;
    box-sizing: border-box;
    overflow-y: auto;
    z-index: 1000;
    display: flex;
    flex-direction: column;
    backdrop-filter: blur(10px);
    transition: transform 0.3s ease-in-out;
    transform: translateX(-280px); /* Show only 40px */
}

/* Dark theme navbar styles */
.dark-theme .navbar {
    background-color: var(--color-card);
    box-shadow: 3px 0 15px rgba(0, 0, 0, 0.2);
}

/* Light theme navbar styles */
.light-theme .navbar {
    background-color: var(--color-card);
    box-shadow: 3px 0 15px var(--color-shadow);
}

.navbar-header {
    padding: 35px 25px;
    display: flex;
    align-items: center;
    border-bottom: 2px solid rgba(164, 188, 46, 0.15);
    background: linear-gradient(135deg, rgba(161, 180, 84, 0.05) 0%, transparent 100%);
    position: relative;
}

/* Dark theme navbar header styles */
.dark-theme .navbar-header {
    background: linear-gradient(135deg, rgba(161, 180, 84, 0.05) 0%, transparent 100%);
    border-bottom-color: rgba(164, 188, 46, 0.15);
}

/* Light theme navbar header styles */
.light-theme .navbar-header {
    background: linear-gradient(135deg, rgba(102, 187, 106, 0.05) 0%, transparent 100%);
    border-bottom-color: rgba(102, 187, 106, 0.15);
}

.navbar-header::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(161, 180, 84, 0.3), transparent);
}

/* Dark theme navbar header after styles */
.dark-theme .navbar-header::after {
    background: linear-gradient(90deg, transparent, rgba(161, 180, 84, 0.3), transparent);
}

/* Light theme navbar header after styles */
.light-theme .navbar-header::after {
    background: linear-gradient(90deg, transparent, rgba(102, 187, 106, 0.3), transparent);
}

.navbar-logo {
    display: flex;
    align-items: center;
    transition: transform 0.3s ease;
}

.navbar-logo:hover {
    transform: scale(1.05);
}

.navbar-logo-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    margin-right: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, rgba(161, 180, 84, 0.1), rgba(161, 180, 84, 0.05));
    border: 2px solid rgba(161, 180, 84, 0.2);
    box-shadow: 0 2px 8px rgba(161, 180, 84, 0.1);
    transition: all 0.3s ease;
}

.navbar-logo:hover .navbar-logo-icon {
    background: linear-gradient(135deg, rgba(161, 180, 84, 0.15), rgba(161, 180, 84, 0.08));
    border-color: rgba(161, 180, 84, 0.3);
    box-shadow: 0 4px 15px rgba(161, 180, 84, 0.2);
}

.light-theme .navbar-logo-icon {
    background: linear-gradient(135deg, rgba(102, 187, 106, 0.1), rgba(102, 187, 106, 0.05));
    border-color: var(--color-border);
    box-shadow: 0 2px 8px var(--color-shadow);
}

.light-theme .navbar-logo:hover .navbar-logo-icon {
    background: linear-gradient(135deg, rgba(102, 187, 106, 0.15), rgba(102, 187, 106, 0.08));
    border-color: var(--color-border);
    box-shadow: 0 4px 15px var(--color-shadow);
}

.navbar-logo-text {
    font-size: 24px;
    font-weight: 600;
    color: var(--color-text);
}

.navbar-menu {
    flex: 1;
    padding: 30px 0;
}

.navbar ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.navbar li {
    margin-bottom: 2px;
    position: relative;
    transition: all 0.3s ease;
}

.navbar li:hover {
    transform: translateX(5px);
}

.navbar li:not(:last-child) {
    border-bottom: 1px solid rgba(161, 180, 84, 0.08);
}

.light-theme .navbar li:not(:last-child) {
    border-bottom: 1px solid rgba(102, 187, 106, 0.08);
}

.navbar a {
    text-decoration: none;
    color: var(--color-text);
    font-size: 17px;
    padding: 18px 25px;
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.navbar a::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(161, 180, 84, 0.1), transparent);
    transition: left 0.5s ease;
}

.light-theme .navbar a::before {
    background: linear-gradient(90deg, transparent, rgba(102, 187, 106, 0.1), transparent);
}

.navbar a:hover {
    background: linear-gradient(90deg, rgba(161, 180, 84, 0.08) 0%, rgba(161, 180, 84, 0.04) 100%);
    color: var(--color-highlight);
    opacity: 1;
    transform: translateX(3px);
    box-shadow: 0 4px 15px rgba(161, 180, 84, 0.15);
}

.navbar a:hover::before {
    left: 100%;
}

.navbar a.active {
    background: linear-gradient(90deg, rgba(161, 180, 84, 0.15) 0%, rgba(161, 180, 84, 0.08) 100%);
    color: var(--color-highlight);
    opacity: 1;
    font-weight: 600;
    border-left: 4px solid var(--color-highlight);
    box-shadow: 0 6px 20px rgba(161, 180, 84, 0.2);
}

.light-theme .navbar a:hover {
    background: linear-gradient(90deg, var(--color-hover) 0%, rgba(102, 187, 106, 0.04) 100%);
    color: #1B3A1B;
    box-shadow: 0 4px 15px var(--color-shadow);
}

.light-theme .navbar a.active {
    background: linear-gradient(90deg, var(--color-active) 0%, rgba(102, 187, 106, 0.08) 100%);
    border-left-color: var(--color-highlight);
    box-shadow: 0 6px 20px var(--color-shadow);
}

.navbar-icon {
    margin-right: 15px;
    width: 24px;
    font-size: 20px;
}

.navbar-footer {
    padding: 25px 20px;
    border-top: 2px solid rgba(164, 188, 46, 0.15);
    font-size: 12px;
    opacity: 0.7;
    text-align: center;
    position: relative;
}

/* Dark theme navbar footer styles */
.dark-theme .navbar-footer {
    border-top-color: rgba(164, 188, 46, 0.15);
    background: linear-gradient(135deg, transparent 0%, rgba(161, 180, 84, 0.03) 100%);
}

/* Light theme navbar footer styles */
.light-theme .navbar-footer {
    border-top-color: rgba(102, 187, 106, 0.15);
    background: linear-gradient(135deg, transparent 0%, rgba(102, 187, 106, 0.03) 100%);
}

.navbar-footer::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(161, 180, 84, 0.2), transparent);
}

/* Dark theme navbar footer before styles */
.dark-theme .navbar-footer::before {
    background: linear-gradient(90deg, transparent, rgba(161, 180, 84, 0.2), transparent);
}

/* Light theme navbar footer before styles */
.light-theme .navbar-footer::before {
    background: linear-gradient(90deg, transparent, rgba(102, 187, 106, 0.2), transparent);
}

.navbar-footer div:first-child {
    font-weight: 600;
    color: var(--color-highlight);
    margin-bottom: 8px;
}

/* Dark theme navbar footer text styles */
.dark-theme .navbar-footer div:first-child {
    color: var(--color-highlight);
}

.light-theme .navbar-footer div:first-child {
    color: #1B3A1B;
}

/* ===== MODERN NAVBAR HOVER SYSTEM ===== */
.navbar:hover {
    transform: translateX(0); /* Show full navbar */
    box-shadow: 5px 0 25px rgba(0, 0, 0, 0.2);
    backdrop-filter: blur(15px);
}

.navbar-logo-text,
.navbar span:not(.navbar-icon),
.navbar-footer {
    opacity: 0;
    transition: opacity 0.3s ease, transform 0.3s ease;
    transform: translateX(-10px);
    white-space: nowrap;
}

.navbar:hover .navbar-logo-text,
.navbar:hover span:not(.navbar-icon),
.navbar:hover .navbar-footer {
    opacity: 1;
    transform: translateX(0);
}

/* Minimized state */
.navbar {
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    align-items: center;
    padding-top: 20px;
}

.navbar:hover .navbar-icon {
    transform: scale(1.05);
    color: var(--color-highlight);
}

/* Expanded state */
.navbar:hover {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: stretch;
    padding-top: 0;
}


/* ===== MOBILE TOP NAVIGATION ===== */
.mobile-top-nav {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 60px;
    background: var(--color-card);
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    backdrop-filter: blur(15px);
    border-bottom: 1px solid var(--color-border);
}

.mobile-nav-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 100%;
    padding: 0 20px;
    max-width: 100%;
}

.mobile-nav-logo {
    display: flex;
    align-items: center;
    gap: 12px;
}

.mobile-logo-img {
    width: 32px;
    height: 32px;
    border-radius: 8px;
}

.mobile-logo-text {
    font-size: 18px;
    font-weight: 600;
    color: var(--color-text);
}

.mobile-nav-icons {
    display: flex;
    align-items: center;
    gap: 15px;
}

.mobile-nav-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: rgba(161, 180, 84, 0.1);
    color: var(--color-text);
    text-decoration: none;
    transition: all 0.3s ease;
    border: 1px solid rgba(161, 180, 84, 0.2);
}

.mobile-nav-icon:hover,
.mobile-nav-icon.active {
    background: var(--color-highlight);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(161, 180, 84, 0.3);
}

.mobile-nav-icon svg {
    width: 18px;
    height: 18px;
}

/* ===== RESPONSIVE DESIGN ===== */
@media (min-width: 769px) {
    .mobile-top-nav, .mobile-nav-overlay, .mobile-nav-sidebar, .mobile-nav-close, .nav-overlay {
        display: none !important;
    }
    .navbar:hover {
        width: 320px !important;
    }
}

@media (max-width: 768px) {
    .navbar {
        display: none !important;
    }
    .mobile-top-nav {
        display: block !important;
    }
    body {
        padding-left: 0 !important;
        padding-top: 60px !important;
        width: 100vw !important;
        max-width: 100vw !important;
        overflow-x: hidden !important;
        min-height: 100vh !important;
    }
    .dashboard {
        margin-left: 0 !important;
        padding: 15px !important;
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
    }
}
    </style>
<body class="dark-theme">
    <div class="dashboard">
        <header>
            <div class="logo">
                <h1>Nutrition Event Notifications</h1>
            </div>
            <div class="user-info">
                <button id="new-theme-toggle" class="new-theme-toggle-btn" title="Toggle theme">
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
                                <p class="csv-format">Format: Event Title, Date & Time, Location, Barangay, Organizer, Description</p>
                                <p class="csv-format-note">💡 Leave Barangay empty to target all barangays in the municipality</p>
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
            
            <?php if(isset($successMessage)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($successMessage); ?>
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
            

            
                <!-- 🚨 COMPLETELY NEW CREATE EVENT FORM - NO REDIRECTS, NO DASHBOARD - DEPLOYMENT TRIGGER -->
    <form class="event-form" id="newCreateEventForm">
                <div class="form-group">
                    <label for="eventTitle">Event Title</label>
                    <input type="text" id="eventTitle" name="eventTitle" placeholder="e.g., Nutrition Seminar in Barangay Hall" value="<?php echo htmlspecialchars($recommended_program); ?>" required>
                </div>
                
                
                <div class="form-group">
                    <label for="eventDate">Date & Time</label>
                    <input type="datetime-local" id="eventDate" name="eventDate" required>
                </div>
                
                <div class="form-group">
                    <label for="eventMunicipality">Municipality</label>
                    <select id="eventMunicipality" name="eventMunicipality" onchange="updateBarangayOptions()" required>
                        <option value="">Select Municipality</option>
                        <option value="all">All Municipalities</option>
                        <option value="ABUCAY">ABUCAY</option>
                        <option value="BAGAC">BAGAC</option>
                        <option value="CITY OF BALANGA">CITY OF BALANGA</option>
                        <option value="DINALUPIHAN">DINALUPIHAN</option>
                        <option value="HERMOSA">HERMOSA</option>
                        <option value="LIMAY">LIMAY</option>
                        <option value="MARIVELES">MARIVELES</option>
                        <option value="MORONG">MORONG</option>
                        <option value="ORANI">ORANI</option>
                        <option value="ORION">ORION</option>
                        <option value="PILAR">PILAR</option>
                        <option value="SAMAL">SAMAL</option>
                    </select>
                                </div>
                                
                <div class="form-group">
                    <label for="eventBarangay">Barangay (Optional)</label>
                    <select id="eventBarangay" name="eventBarangay">
                        <option value="">All Barangays in Municipality</option>
                        <!-- Barangay options will be populated by JavaScript -->
                    </select>
                    <small class="form-help">Leave empty to target all barangays in the selected municipality</small>
                </div>
                
                <div class="form-group">
                    <label for="eventOrganizer">Person in Charge</label>
                    <input type="text" id="eventOrganizer" name="eventOrganizer" value="<?php echo htmlspecialchars($username ?? $email ?? 'Unknown User'); ?>" readonly>
                </div>
                
                <div class="form-row">
                <div class="form-group">
                    <label for="eventDescription">Event Description</label>
                        <textarea id="eventDescription" name="eventDescription" placeholder="Describe the event details..." rows="3"></textarea>
                                </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" onclick="handleNewEventCreation()" class="btn btn-primary">Create Event</button>
                    <button type="button" class="btn btn-secondary" onclick="closeCreateEventModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
        </div>
        
<!-- Events Table and Main Content -->
        <div class="events-table-container">
            <div class="table-header">
                <h2>Upcoming Events</h2>
                <div class="table-controls">
                    <select id="eventFilter" onchange="filterEvents(this.value)">
                        <option value="all">All Events</option>
                        <option value="upcoming">Upcoming</option>
                        <option value="past">Past Events</option>
                    </select>
                    <button onclick="confirmDeleteAll()" class="btn btn-danger">Delete All Events</button>
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
            
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    Error: <?php echo htmlspecialchars($_GET['message'] ?? 'Unknown error occurred'); ?>
                </div>
            <?php endif; ?>
            
            <table class="events-table">
                <thead>
                    <tr>
                        <th>Event Title</th>
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
    
<!-- Navigation -->
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
                <li><a href="screening"><span class="navbar-icon"></span><span>MHO Assessment</span></a></li>
                <li><a href="event"><span class="navbar-icon"></span><span>Nutrition Event Notifications</span></a></li>
                <li><a href="ai"><span class="navbar-icon"></span><span>Chatbot & AI Logs</span></a></li>
                <li><a href="settings"><span class="navbar-icon"></span><span>Settings & Admin</span></a></li>
                <li><a href="logout" style="color: #ff5252;"><span class="navbar-icon"></span><span>Logout</span></a></li>
            </ul>
        </div>
        <div class="navbar-footer">
            <div>NutriSaur v2.0 • © 2025</div>
            <div style="margin-top: 10px;">Logged in as: <?php echo htmlspecialchars($username); ?></div>
        </div>
    </div>

    <!-- Mobile Top Navigation -->
    <div class="mobile-top-nav">
        <div class="mobile-nav-container">
            <div class="mobile-nav-logo">
                <img src="/logo.png" alt="Logo" class="mobile-logo-img">
                <span class="mobile-logo-text">NutriSaur</span>
            </div>
            <div class="mobile-nav-icons">
                <a href="dash" class="mobile-nav-icon" title="Dashboard">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                </a>
                <a href="screening" class="mobile-nav-icon" title="MHO Assessment">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </a>
                <a href="event" class="mobile-nav-icon active" title="Events">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </a>
                <a href="ai" class="mobile-nav-icon" title="AI Chatbot">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                </a>
                <a href="settings" class="mobile-nav-icon" title="Settings">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                </a>
                <a href="logout" class="mobile-nav-icon" title="Logout" style="color: #ff5252;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16,17 21,12 16,7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <!-- Edit Event Modal -->
<div id="editEventModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Event</h3>
            <span class="close" onclick="closeEditEventModal()">&times;</span>
            </div>
        <div class="modal-body">
            <form class="event-form" id="editEventForm">
                <input type="hidden" id="editEventId" name="editEventId">
                
                <div class="form-group">
                    <label for="editEventTitle">Event Title</label>
                    <input type="text" id="editEventTitle" name="editEventTitle" required>
                </div>
                
                <div class="form-group">
                    <label for="editEventDate">Date & Time</label>
                    <input type="datetime-local" id="editEventDate" name="editEventDate" required>
                </div>
                
                <div class="form-group">
                    <label for="editEventMunicipality">Municipality</label>
                    <select id="editEventMunicipality" name="editEventMunicipality" onchange="updateEditBarangayOptions()" required>
                        <option value="">Select Municipality</option>
                        <option value="all">All Municipalities</option>
                        <option value="ABUCAY">ABUCAY</option>
                        <option value="BAGAC">BAGAC</option>
                        <option value="CITY OF BALANGA">CITY OF BALANGA</option>
                        <option value="DINALUPIHAN">DINALUPIHAN</option>
                        <option value="HERMOSA">HERMOSA</option>
                        <option value="LIMAY">LIMAY</option>
                        <option value="MARIVELES">MARIVELES</option>
                        <option value="MORONG">MORONG</option>
                        <option value="ORANI">ORANI</option>
                        <option value="ORION">ORION</option>
                        <option value="PILAR">PILAR</option>
                        <option value="SAMAL">SAMAL</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="editEventBarangay">Barangay (Optional)</label>
                    <select id="editEventBarangay" name="editEventBarangay">
                        <option value="">All Barangays in Municipality</option>
                        <!-- Barangay options will be populated by JavaScript -->
                    </select>
                    <small class="form-help">Leave empty to target all barangays in the selected municipality</small>
                </div>
                
                <div class="form-group">
                    <label for="editEventOrganizer">Person in Charge</label>
                    <input type="text" id="editEventOrganizer" name="editEventOrganizer" readonly>
                </div>
                
                <div class="form-group">
                    <label for="editEventDescription">Event Description</label>
                    <textarea id="editEventDescription" name="editEventDescription" rows="3"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Event</button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditEventModal()">Cancel</button>
                </div>
            </form>
            </div>
        </div>
    </div>

<script>
// Municipality to Barangay mapping
const municipalityBarangays = {
    'ABUCAY': ['Bangkal', 'Calaylayan (Pob.)', 'Capitangan', 'Gabon', 'Laon (Pob.)', 'Mabatang', 'Omboy', 'Salian', 'Wawa (Pob.)'],
    'BAGAC': ['Bagumbayan (Pob.)', 'Banawang', 'Binuangan', 'Binukawan', 'Ibaba', 'Ibabang Wasian', 'Ilog', 'Lawa', 'Palihan', 'Parang', 'Poblacion', 'San Antonio', 'Saysain', 'Sibacan', 'Tabing-Ilog', 'Tipo', 'Wawa'],
    'CITY OF BALANGA': ['Bagumbayan', 'Cabog-Cabog', 'Munting Batangas (Cadre)', 'Cataning', 'Central', 'Cupang Proper', 'Cupang West', 'Dangcol (Bernabe)', 'Ibayo', 'Malabia', 'Poblacion', 'Pto. Rivas Ibaba', 'Pto. Rivas Itaas', 'San Jose', 'Sibacan', 'Camacho', 'Talisay', 'Tanato', 'Tenejero', 'Tortugas', 'Tuyo', 'Bagong Silang', 'Cupang North', 'Doña Francisca', 'Lote'],
    'DINALUPIHAN': ['Bayan-bayanan', 'Bonifacio', 'Burgos', 'Daungan', 'Del Pilar', 'General Lim', 'Imelda', 'Lourdes', 'Mabatang', 'Maligaya', 'Poblacion', 'San Juan', 'San Roque', 'Santo Niño', 'Sulong'],
    'HERMOSA': ['A. Rivera (Pob.)', 'Almacen', 'Bacong', 'Balsic', 'Bamban', 'Burgos-Soliman (Pob.)', 'Cataning (Pob.)', 'Culis', 'Daungan (Pob.)', 'Mabiga', 'Mabuco', 'Maite', 'Mambog - Mandama', 'Palihan', 'Pandatung', 'Pulo', 'Saba', 'San Pedro', 'Sumalo', 'Tipo'],
    'LIMAY': ['Alangan', 'Kitang I', 'Kitang 2 & Luz', 'Lamao', 'Landing', 'Poblacion', 'Reformista', 'Townsite', 'Wawa', 'Duale', 'San Francisco de Asis', 'St. Francis II'],
    'MARIVELES': ['Alas-asin', 'Alion', 'Batangas II', 'Cabcaben', 'Lucanin', 'Baseco Country (Nassco)', 'Poblacion', 'San Carlos', 'San Isidro', 'Sisiman', 'Balon-Anito', 'Biaan', 'Camaya', 'Ipag', 'Malaya', 'Maligaya', 'Mt. View', 'Townsite'],
    'MORONG': ['Binaritan', 'Mabayo', 'Nagbalayong', 'Poblacion', 'Sabang', 'San Jose'],
    'ORANI': ['Bagong Paraiso', 'Balut', 'Bayorbor', 'Calungusan', 'Camacho', 'Daang Bago', 'Dona', 'Kaparangan', 'Mabayo', 'Masagana', 'Mulawin', 'Paglalaban', 'Palawe', 'Pantalan Bago', 'Poblacion', 'Saguing', 'Tagumpay', 'Tala', 'Tapulao', 'Tenejero', 'Wawa'],
    'ORION': ['Balagtas', 'Balut', 'Bantan', 'Bilolo', 'Calungusan', 'Camachile', 'Daang Bago', 'Daang Pare', 'Del Pilar', 'General Lim', 'Imelda', 'Lourdes', 'Mabatang', 'Maligaya', 'Poblacion', 'San Juan', 'San Roque', 'Santo Niño', 'Sulong'],
    'PILAR': ['Alas-asin', 'Balanak', 'Balut', 'Bantan', 'Bilolo', 'Calungusan', 'Camachile', 'Daang Bago', 'Daang Pare', 'Del Pilar', 'General Lim', 'Imelda', 'Lourdes', 'Mabatang', 'Maligaya', 'Poblacion', 'San Juan', 'San Roque', 'Santo Niño', 'Sulong'],
    'SAMAL': ['East Daang Bago', 'West Daang Bago', 'East Poblacion', 'West Poblacion', 'San Juan', 'San Roque', 'Santo Niño', 'Sulong']
};

// Function to update barangay options based on selected municipality
function updateBarangayOptions() {
    const municipalitySelect = document.getElementById('eventMunicipality');
    const barangaySelect = document.getElementById('eventBarangay');
    const selectedMunicipality = municipalitySelect.value;
    
    // Clear existing options
    barangaySelect.innerHTML = '<option value="">All Barangays in Municipality</option>';
    
    if (selectedMunicipality && selectedMunicipality !== 'all' && municipalityBarangays[selectedMunicipality]) {
        municipalityBarangays[selectedMunicipality].forEach(barangay => {
            const option = document.createElement('option');
            option.value = barangay;
            option.textContent = barangay;
            barangaySelect.appendChild(option);
        });
    }
}

// Function to update edit barangay options
function updateEditBarangayOptions() {
    const municipalitySelect = document.getElementById('editEventMunicipality');
    const barangaySelect = document.getElementById('editEventBarangay');
    const selectedMunicipality = municipalitySelect.value;
    
    // Clear existing options
    barangaySelect.innerHTML = '<option value="">All Barangays in Municipality</option>';
    
    if (selectedMunicipality && selectedMunicipality !== 'all' && municipalityBarangays[selectedMunicipality]) {
        municipalityBarangays[selectedMunicipality].forEach(barangay => {
            const option = document.createElement('option');
            option.value = barangay;
            option.textContent = barangay;
            barangaySelect.appendChild(option);
        });
    }
}

// Function to get the target location for notifications
function getTargetLocation() {
    const municipality = document.getElementById('eventMunicipality').value;
    const barangay = document.getElementById('eventBarangay').value;
    
    if (municipality === 'all') {
        return 'all';
    } else if (barangay) {
        return barangay; // Target specific barangay
    } else {
        return 'MUNICIPALITY_' + municipality.replace(/ /g, '_'); // Target all barangays in municipality
    }
}

// Function to get the target location for edit form
function getEditTargetLocation() {
    const municipality = document.getElementById('editEventMunicipality').value;
    const barangay = document.getElementById('editEventBarangay').value;
    
    if (municipality === 'all') {
        return 'all';
    } else if (barangay) {
        return barangay; // Target specific barangay
    } else {
        return 'MUNICIPALITY_' + municipality.replace(/ /g, '_'); // Target all barangays in municipality
    }
}

// Function to close create event modal
function closeCreateEventModal() {
    document.getElementById('createEventModal').style.display = 'none';
    document.getElementById('newCreateEventForm').reset();
}

// Function to close edit event modal  
function closeEditEventModal() {
    document.getElementById('editEventModal').style.display = 'none';
    document.getElementById('editEventForm').reset();
}

// Function to close create event modal
function closeCreateEventModal() {
    document.getElementById('createEventModal').style.display = 'none';
    document.getElementById('newCreateEventForm').reset();
}

        // 🚨 GLOBAL EVENT CREATION HANDLER - Available immediately
        window.handleNewEventCreation = async function() {
            console.log('🚨 NEW EVENT CREATION STARTED - NO REDIRECTS');
            
            // Get form data from the form element
            const form = document.getElementById('newCreateEventForm');
            if (!form) {
                console.error('Form not found!');
                return;
            }
            
            const formData = new FormData(form);
    const municipality = formData.get('eventMunicipality');
    const barangay = formData.get('eventBarangay');
    
    // Determine target location based on municipality and barangay selection
    let targetLocation;
    if (municipality === 'all') {
        targetLocation = 'all';
    } else if (barangay) {
        targetLocation = barangay; // Target specific barangay
    } else {
        targetLocation = 'MUNICIPALITY_' + municipality.replace(/ /g, '_'); // Target all barangays in municipality
    }
    
            const eventData = {
                title: formData.get('eventTitle'),
                description: formData.get('eventDescription'),
                date_time: formData.get('eventDate'),
        location: targetLocation,
                organizer: formData.get('eventOrganizer')
            };
            
            console.log('Event data:', eventData);
            console.log('Form element found:', !!form);
            console.log('FormData entries:');
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }
            
            // Validate required fields
            if (!eventData.title || !eventData.description || !eventData.date_time || !eventData.organizer) {
                console.error('Missing required fields:', {
                    title: eventData.title,
                    description: eventData.description,
                    date_time: eventData.date_time,
                    organizer: eventData.organizer
                });
                alert('Please fill in all required fields');
                return;
            }
            
            try {
                console.log('✅ Validation passed, starting event creation process');
            
            // Show loading state
        const submitBtn = document.querySelector('#newCreateEventForm .btn-primary');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="btn-text">Creating Event...</span>';
            submitBtn.disabled = true;
            
                console.log('🔄 Calling save_event_only API...');
                console.log('📤 Sending event data to programs table:', {
                    title: eventData.title,
                    description: eventData.description,
                    date_time: eventData.date_time,
                    location: eventData.location,
                    organizer: eventData.organizer
                });
                
                // 🚨 STEP 1: SAVE EVENT TO DATABASE FIRST (using the working PHP logic)
                const saveResponse = await fetch('event.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        'action': 'save_event_only',
                        'title': eventData.title,
                        'description': eventData.description,
                        'date_time': eventData.date_time,
                        'location': eventData.location,
                        'organizer': eventData.organizer
                    })
                });
                
                const saveResult = await saveResponse.json();
                console.log('📊 Save API response:', saveResult);
                
                if (!saveResult.success) {
                    console.error('❌ Save API failed:', saveResult.message);
                    throw new Error(`Failed to save event: ${saveResult.message}`);
                }
                
                console.log('✅ Event saved successfully!');
                
                // Notifications are now handled automatically in PHP after saving
                
                // Fetch and display current programs table
                console.log('🔍 Fetching current programs table to verify...');
                try {
                    const programsResponse = await fetch('/api/DatabaseAPI.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: new URLSearchParams({
                            'action': 'query',
                            'sql': 'SELECT * FROM programs ORDER BY program_id DESC LIMIT 5'
                        })
                    });
                    const programsResult = await programsResponse.json();
                    console.log('📊 Current programs table (last 5 events):', programsResult);
                } catch (error) {
                    console.error('❌ Error fetching programs table:', error);
                }
                
                    // Reset form
                    form.reset();
                    
                    // Show success message
                alert(`🎉 Event "${eventData.title}" created successfully!`);
                
                // Refresh the page to show the new event
                setTimeout(() => {
                    location.reload();
                }, 2000); // Wait 2 seconds to show the success message
                
            } catch (error) {
                console.error('Error creating event:', error);
                alert('Error creating event. Please try again.');
            } finally {
                // Restore button state
            const submitBtn = document.querySelector('#newCreateEventForm .btn-primary');
                if (submitBtn) {
                    submitBtn.innerHTML = '<span class="btn-text">Create Event</span>';
                submitBtn.disabled = false;
            }
        }
        };
        
// Function to confirm delete all events
        function confirmDeleteAll() {
            // Get total number of events
            const eventRows = document.querySelectorAll('.event-row');
            const eventCount = eventRows.length;
            
            if (eventCount === 0) {
        alert('No events to delete!');
                return;
            }

            // Double confirmation for delete all
            const confirmMessage = `⚠️ WARNING: This will delete ALL ${eventCount} events from the database!\n\nThis action cannot be undone. Are you absolutely sure?`;
            
            if (!confirm(confirmMessage)) {
                return;
            }

            // Second confirmation
            if (!confirm('FINAL CONFIRMATION: Delete ALL events? This will permanently remove all event data.')) {
                return;
            }

            // Show loading state
            const deleteAllBtn = event.target;
            const originalText = deleteAllBtn.innerHTML;
            deleteAllBtn.innerHTML = '⏳ Deleting...';
            deleteAllBtn.disabled = true;

            // Send delete all request to server
            fetch('/api/delete_all_programs.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    confirm: true
                })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                    // Remove all event rows from the page
                    eventRows.forEach(row => row.remove());
                    
        alert(`Successfully deleted all ${data.deleted_count || eventCount} events!`);
                    
                    // Reload page to refresh event list
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                        } else {
        alert('Error deleting all events: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
    alert('Error deleting all events: ' + error.message);
            })
            .finally(() => {
                // Restore button state
                if (deleteAllBtn) {
                    deleteAllBtn.innerHTML = originalText;
                    deleteAllBtn.disabled = false;
                }
            });
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
                const locationInput = document.getElementById('eventLocation');
                const descriptionInput = document.getElementById('eventDescription');
                const dateInput = document.getElementById('eventDate');
                const organizerInput = document.getElementById('eventOrganizer');
                
                // Log which elements were found
                console.log('Form elements found:', {
                    titleInput: !!titleInput,
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
            const descriptionInput = document.getElementById('eventDescription');
            const dateInput = document.getElementById('eventDate');
            const locationInput = document.getElementById('eventLocation');
            const organizerInput = document.getElementById('eventOrganizer');
            
            console.log('Form validation - Form values:', {
                title: titleInput?.value,
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
            
            const csvContent = `title,date_time,location,barangay,organizer,description
Nutrition Workshop,${formatDate(future1)},CITY OF BALANGA,Bagumbayan,Dr. Maria Santos,Free nutrition screening and health consultation
Health Seminar,${formatDate(future2)},ABUCAY,,Dr. Juan Cruz,Community health education and awareness (all barangays)
Medical Mission,${formatDate(future3)},LIMAY,Poblacion,Dr. Ana Reyes,Free medical checkup and consultation`;
            
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
            console.log('🔍 selectEventLocation called with value:', value, 'text:', text);
            console.log('Value type:', typeof value, 'Value length:', value ? value.length : 0);
            
            const selectedOption = document.getElementById('selected-event-location');
            const dropdownContent = document.getElementById('event-location-dropdown');
            const dropdownArrow = document.querySelector('.select-header .dropdown-arrow');
            const hiddenInput = document.getElementById('eventLocation');
            
            console.log('🔍 Found elements:', {
                selectedOption: !!selectedOption,
                dropdownContent: !!dropdownContent,
                dropdownArrow: !!dropdownArrow,
                hiddenInput: !!hiddenInput
            });
            
            if (selectedOption && dropdownContent && dropdownArrow && hiddenInput) {
                selectedOption.textContent = text;
                hiddenInput.value = value;
                
                console.log('✅ Set hidden input value to:', hiddenInput.value);
                console.log('✅ Hidden input value after setting:', document.getElementById('eventLocation').value);
                
                dropdownContent.classList.remove('active');
                dropdownArrow.classList.remove('active');
                
                // Update selected state
                document.querySelectorAll('#event-location-dropdown .option-item').forEach(item => {
                    item.classList.remove('selected');
                });
                
                const clickedItem = document.querySelector(`#event-location-dropdown [data-value="${value}"]`);
                if (clickedItem) {
                    clickedItem.classList.add('selected');
                    console.log('✅ Updated selected state for item:', clickedItem);
                }
                
                // Show location preview
                console.log('🔄 Calling showLocationPreview with:', value, text);
                showLocationPreview(value, text);
            } else {
                console.error('❌ Some required elements not found for location selection');
                console.error('Missing elements:', {
                    selectedOption: !selectedOption ? 'MISSING' : 'FOUND',
                    dropdownContent: !dropdownContent ? 'MISSING' : 'FOUND',
                    dropdownArrow: !dropdownArrow ? 'MISSING' : 'FOUND',
                    hiddenInput: !hiddenInput ? 'MISSING' : 'FOUND'
                });
            }
        }
        
        // Function to show location preview
        function showLocationPreview(locationValue, locationText) {
            console.log('🔍 showLocationPreview called with:', locationValue, locationText);
            
            const previewDiv = document.getElementById('locationPreview');
            const contentDiv = document.getElementById('locationPreviewContent');
            const notificationPreviewDiv = document.getElementById('notificationPreview');
            const notificationContentDiv = document.getElementById('notificationPreviewContent');
            
            console.log('🔍 Preview elements found:', {
                previewDiv: !!previewDiv,
                contentDiv: !!contentDiv,
                notificationPreviewDiv: !!notificationPreviewDiv,
                notificationContentDiv: !!notificationContentDiv
            });
            
            if (!previewDiv || !contentDiv) {
                console.error('❌ Required preview elements not found');
                return;
            }
            
            // Show loading state
            previewDiv.style.display = 'block';
            contentDiv.innerHTML = '<div style="text-align: center; opacity: 0.7;">🔄 Loading location preview...</div>';
            
            // Show notification preview loading state
            if (notificationPreviewDiv && notificationContentDiv) {
                notificationPreviewDiv.style.display = 'block';
                notificationContentDiv.innerHTML = '<div style="text-align: center; opacity: 0.7;">🔄 Loading notification preview...</div>';
            }
            
            // Fetch users for this location
            console.log('🔄 Fetching location preview for:', locationValue);
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
                        displayNotificationPreview(result.users, locationText);
                    } else {
                        contentDiv.innerHTML = '<div style="text-align: center; color: var(--color-danger);">❌ Error loading preview: ' + (result.message || 'Unknown error') + '</div>';
                        if (notificationContentDiv) {
                            notificationContentDiv.innerHTML = '<div style="text-align: center; color: var(--color-danger);">❌ Error loading preview</div>';
                        }
                    }
                } catch (e) {
                    contentDiv.innerHTML = '<div style="text-align: center; color: var(--color-danger);">❌ Error parsing response</div>';
                    if (notificationContentDiv) {
                        notificationContentDiv.innerHTML = '<div style="text-align: center; color: var(--color-danger);">❌ Error parsing response</div>';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                contentDiv.innerHTML = '<div style="text-align: center; color: var(--color-danger);">❌ Error loading preview</div>';
                if (notificationContentDiv) {
                    notificationContentDiv.innerHTML = '<div style="text-align: center; color: var(--color-danger);">❌ Error loading preview</div>';
                }
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
        
        // Function to display notification preview
        function displayNotificationPreview(users, locationText) {
            const notificationContentDiv = document.getElementById('notificationPreviewContent');
            
            if (!notificationContentDiv) return;
            
            if (!users || users.length === 0) {
                notificationContentDiv.innerHTML = `
                    <div style="text-align: center; opacity: 0.7;">
                        <div style="margin-bottom: 10px;"><strong>📱 Notification Preview</strong></div>
                        <div>No users to notify in this location</div>
                        <div style="font-size: 12px; margin-top: 5px;">Users need to complete screening to receive notifications</div>
                    </div>
                `;
                return;
            }
            
            const eventTitle = document.getElementById('eventTitle')?.value || 'Event Title';
            const eventDate = document.getElementById('eventDate')?.value || 'Event Date';
            const notificationType = document.getElementById('notificationType')?.value || 'push';
            
            let html = `
                <div style="margin-bottom: 15px;">
                    <div style="font-weight: bold; margin-bottom: 5px;"><strong>📱 Notification Preview</strong></div>
                    <div style="font-size: 14px; opacity: 0.8;">${users.length} user(s) will receive ${notificationType} notifications</div>
                </div>
                <div style="background: rgba(161, 180, 84, 0.1); padding: 12px; border-radius: 8px; margin-bottom: 15px;">
                    <div style="font-weight: bold; margin-bottom: 8px;">📋 Sample Notification:</div>
                    <div style="font-size: 13px; line-height: 1.4;">
                        <div><strong>Title:</strong> New Event: ${eventTitle}</div>
                        <div><strong>Message:</strong> ${eventTitle} at ${locationText} on ${eventDate}</div>
                        <div><strong>Type:</strong> ${notificationType === 'push' ? 'Push Notification' : notificationType === 'both' ? 'Push + Email' : 'Email Only'}</div>
                    </div>
                </div>
                <div style="max-height: 150px; overflow-y: auto;">
            `;
            
            users.forEach(user => {
                const hasToken = user.fcm_token && user.is_active;
                const statusIcon = hasToken ? '📱' : '📧';
                const statusText = hasToken ? 'Push Ready' : 'Email Only';
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
                            ${user.barangay} | ${statusText}
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            notificationContentDiv.innerHTML = html;
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
            formData.append('import_csv', '1'); // Add the required POST parameter
            
            // Show loading state
            const importBtn = document.getElementById('importBtn');
            const importStatus = document.getElementById('importStatus');
            const originalText = importBtn.innerHTML;
            
            importBtn.disabled = true;
            importBtn.innerHTML = '🔄 Uploading...';
            importStatus.style.display = 'block';
            
            fetch('event.php', {
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
            
            fetch('https://nutrisaur-production.up.railway.app/api/DatabaseAPI.php', {
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
            
            fetch('https://nutrisaur-production.up.railway.app/api/DatabaseAPI.php', {
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
        
        // Add Event Modal Functions
        function openAddEventModal() {
            // Reset form values
            document.getElementById('add_eventTitle').value = '';
            document.getElementById('add_eventDate').value = '';
            document.getElementById('add_eventLocation').value = '';
            document.getElementById('add_eventOrganizer').value = '<?php echo htmlspecialchars($username ?? $email ?? 'Unknown User'); ?>';
            document.getElementById('add_eventDescription').value = '';
            
            // Set default date to tomorrow
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            tomorrow.setHours(9, 0, 0, 0); // 9:00 AM
            const formattedDate = tomorrow.toISOString().slice(0, 16);
            document.getElementById('add_eventDate').value = formattedDate;
            
            // Show modal
            document.getElementById('addEventModal').style.display = 'block';
        }
        
        function closeAddEventModal() {
            document.getElementById('addEventModal').style.display = 'none';
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
            const editModal = document.getElementById('editModal');
            const addModal = document.getElementById('addEventModal');
            const duplicateModal = document.getElementById('duplicateWarningModal');
            
            if (event.target === editModal) {
                closeEditModal();
            } else if (event.target === addModal) {
                closeAddEventModal();
            } else if (event.target === duplicateModal) {
                closeDuplicateWarningModal();
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
                const locationInput = document.getElementById('eventLocation');
                const descriptionInput = document.getElementById('eventDescription');
                const dateInput = document.getElementById('eventDate');
                const organizerInput = document.getElementById('eventOrganizer');
                
                // Log which elements were found
                console.log('Form elements found:', {
                    titleInput: !!titleInput,
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
            const descriptionInput = document.getElementById('eventDescription');
            const dateInput = document.getElementById('eventDate');
            const locationInput = document.getElementById('eventLocation');
            const organizerInput = document.getElementById('eventOrganizer');
            
            console.log('Form validation - Form values:', {
                title: titleInput?.value,
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

        // Theme toggle function
        function newToggleTheme() {
            console.log('🎨 Theme toggle function called');
            const body = document.body;
            const icon = document.querySelector('.new-theme-icon');
            
            console.log('🎨 Current body classes:', body.className);
            console.log('🎨 Icon element:', icon);
            
            if (body.classList.contains('dark-theme')) {
                body.classList.remove('dark-theme');
                body.classList.add('light-theme');
                if (icon) icon.textContent = '☀️';
                localStorage.setItem('theme', 'light');
                console.log('✅ Switched to light theme');
            } else {
                body.classList.remove('light-theme');
                body.classList.add('dark-theme');
                if (icon) icon.textContent = '🌙';
                localStorage.setItem('theme', 'dark');
                console.log('✅ Switched to dark theme');
            }
            
            console.log('🎨 New body classes:', body.className);
        }

        // Initialize theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🎨 DOM Content Loaded - Initializing theme...');
            const savedTheme = localStorage.getItem('theme') || 'dark';
            const body = document.body;
            const icon = document.querySelector('.new-theme-icon');
            
            console.log('🎨 Saved theme from localStorage:', savedTheme);
            console.log('🎨 Body element:', body);
            console.log('🎨 Icon element:', icon);
            
            if (savedTheme === 'light') {
                body.classList.remove('dark-theme');
                body.classList.add('light-theme');
                if (icon) icon.textContent = '☀️';
                console.log('✅ Applied light theme on page load');
            } else {
                body.classList.remove('light-theme');
                body.classList.add('dark-theme');
                if (icon) icon.textContent = '🌙';
                console.log('✅ Applied dark theme on page load');
            }

            // Add theme toggle event listener
            const themeToggleBtn = document.getElementById('new-theme-toggle');
            console.log('🎨 Theme toggle button:', themeToggleBtn);
            if (themeToggleBtn) {
                themeToggleBtn.addEventListener('click', newToggleTheme);
                console.log('✅ Theme toggle event listener added');
            } else {
                console.error('❌ Theme toggle button not found!');
            }
        });
    </script>

    <script>
        // ===== MODERN 2025 NAVIGATION SYSTEM =====
        let navState = {
            isMobile: window.innerWidth <= 768,
            isHovered: false
        };
        const navbar = document.querySelector('.navbar');
        const mobileTopNav = document.querySelector('.mobile-top-nav');
        const body = document.body;

        function initNavigation() {
            console.log('🚀 Initializing Navigation System...');
            
            if (!navbar || !mobileTopNav) {
                console.error('❌ Navigation elements not found');
                return;
            }
            
            console.log('📱 Mobile mode:', navState.isMobile);
            
            setupEventListeners();
            updateNavbarState();
            updateBodyPadding();
            
            console.log('✅ Navigation system initialized');
        }

        function setupEventListeners() {
            // Desktop navbar hover events
            if (navbar) {
                navbar.addEventListener('mouseenter', () => {
                    if (!navState.isMobile) {
                        navState.isHovered = true;
                        updateNavbarState();
                        updateBodyPadding();
                    }
                });

                navbar.addEventListener('mouseleave', () => {
                    if (!navState.isMobile) {
                        navState.isHovered = false;
                        updateNavbarState();
                        updateBodyPadding();
                    }
                });
            }

            // Window resize handler
            window.addEventListener('resize', handleResize);
        }

        function updateNavbarState() {
            if (!navbar) return;

            if (navState.isHovered && !navState.isMobile) {
                navbar.classList.add('expanded');
                navbar.classList.remove('collapsed');
            } else {
                navbar.classList.add('collapsed');
                navbar.classList.remove('expanded');
            }
        }

        function updateBodyPadding() {
            if (!navState.isMobile) {
                if (navState.isHovered) {
                    body.style.paddingLeft = '340px'; // Expanded navbar width + margin
                } else {
                    body.style.paddingLeft = '60px'; // Minimized navbar width + margin
                }
            }
        }

        function handleResize() {
            const wasMobile = navState.isMobile;
            navState.isMobile = window.innerWidth <= 768;
            
            if (wasMobile !== navState.isMobile) {
                updateNavbarState();
                updateBodyPadding();
            }
        }

        // Initialize navigation system
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initNavigation);
        } else {
            initNavigation();
        }
    </script>
</body>
</html>