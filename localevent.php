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

// Include database configuration
require_once 'api/config.php';

// Initialize variables
$dbConnected = false;
$errorMessage = null;
$programs = [];

try {
    // Use the existing connection from config.php
    $dbConnected = true;
    
    // Fetch programs from database
    $stmt = $conn->prepare("SELECT * FROM programs ORDER BY date_time DESC");
    $stmt->execute();
    $programs = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $dbConnected = false;
    $errorMessage = "Database connection failed: " . $e->getMessage();
}

// Check if program recommendation was passed from community hub
$recommended_program = isset($_GET['program']) ? $_GET['program'] : '';

// Handle form submission for creating new program
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_event'])) {
    $title = $_POST['eventTitle'];
    $type = $_POST['eventType'];
    $description = $_POST['eventDescription'];
    $date_time = $_POST['eventDate'];
    $location = $_POST['eventLocation'];
    $organizer = $_POST['eventOrganizer'];
    
    try {
        // First, insert the event directly into the database
        $stmt = $conn->prepare("
            INSERT INTO programs (title, type, description, date_time, location, organizer, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([$title, $type, $description, $date_time, $location, $organizer]);
        $eventId = $conn->lastInsertId();
        
        // Now send FCM notification to all registered devices
        $notificationSent = false;
        
        try {
            // Get all registered FCM tokens from the database
            $tokenStmt = $conn->prepare("SELECT fcm_token FROM user_fcm_tokens WHERE fcm_token IS NOT NULL AND fcm_token != ''");
            $tokenStmt->execute();
            $fcmTokens = $tokenStmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($fcmTokens)) {
                // Send FCM notification using Firebase Admin SDK
                $notificationSent = sendFCMNotification($fcmTokens, [
                    'title' => $title,
                    'body' => "New event: $title at $location on " . date('M j, Y g:i A', strtotime($date_time)),
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
                ]);
            } else {
                $notificationSent = false;
            }
            
        } catch (Exception $notificationError) {
            error_log("Error sending FCM notification: " . $notificationError->getMessage());
            $notificationSent = false;
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
                'devices_notified' => count($fcmTokens ?? [])
            ]);
            exit;
        } else {
            // Redirect with success message
            $redirectUrl = "event.php?success=1&event_id=" . $eventId;
            if ($notificationSent) {
                $redirectUrl .= "&notification=1&devices=" . count($fcmTokens ?? []);
            }
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

// Function to send FCM notification
function sendFCMNotification($tokens, $notificationData) {
    try {
        // Use Firebase Admin SDK JSON file (recommended approach)
        $adminSdkPath = __DIR__ . '/nutrisaur-ebf29-firebase-adminsdk-fbsvc-8dc50fb07f.json';
        
        if (file_exists($adminSdkPath)) {
            return sendFCMWithAdminSDK($tokens, $notificationData, $adminSdkPath);
        } else {
            error_log("Firebase Admin SDK JSON file not found at: $adminSdkPath");
            return false;
        }
    } catch (Exception $e) {
        error_log("Error in sendFCMNotification: " . $e->getMessage());
        return false;
    }
}

// Function to send FCM using Firebase Admin SDK (simplified and fixed)
function sendFCMWithAdminSDK($tokens, $notificationData, $adminSdkPath) {
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
                $failureCount++;
                continue;
            }
            
            if ($httpCode == 200) {
                $responseData = json_decode($response, true);
                if (isset($responseData['name'])) {
                    $successCount++;
                } else {
                    $failureCount++;
                }
            } else {
                $failureCount++;
            }
        }
        
        return $successCount > 0;
        
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
                    
                    // Send real-time notifications for imported events
                    if (!empty($importedEvents)) {
                        try {
                            // Get all registered FCM tokens
                            $tokenStmt = $conn->prepare("SELECT fcm_token FROM user_fcm_tokens WHERE fcm_token IS NOT NULL AND fcm_token != ''");
                            $tokenStmt->execute();
                            $fcmTokens = $tokenStmt->fetchAll(PDO::FETCH_COLUMN);
                            
                            if (!empty($fcmTokens)) {
                                foreach ($importedEvents as $event) {
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
                                    ]);
                                }
                            }
                            
                        } catch(Exception $e) {
                            // Silent fail for notifications
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
        
        // Redirect to refresh page
        header("Location: event.php?deleted=1");
        exit;
    } catch(PDOException $e) {
        $errorMessage = "Error deleting program: " . $e->getMessage();
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
        
        // Send notification about the updated event
        try {
            // Get all registered FCM tokens
            $tokenStmt = $conn->prepare("SELECT fcm_token FROM user_fcm_tokens WHERE fcm_token IS NOT NULL AND fcm_token != ''");
            $tokenStmt->execute();
            $fcmTokens = $tokenStmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($fcmTokens)) {
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
                ]);
            }
            
        } catch(Exception $e) {
            // Silent fail for notifications
        }
        
        // Redirect to refresh page
        header("Location: event.php?updated=1");
        exit;
    } catch(PDOException $e) {
        $errorMessage = "Error updating program: " . $e->getMessage();
    }
}

// Get event data for editing
$editEvent = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editId = $_GET['edit'];
    try {
        $stmt = $conn->prepare("SELECT * FROM programs WHERE program_id = :id");
        $stmt->bindParam(':id', $editId);
        $stmt->execute();
        $editEvent = $stmt->fetch();
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
    --color-bg: #a0ca3f;
    --color-card: #EAF0DC;
    --color-highlight: #8EB96E;
    --color-text: #415939;
    --color-accent1: #F9B97F;
    --color-accent2: #E9957C;
    --color-accent3: #76BB6E;
    --color-accent4: #D7E3A0;
    --color-danger: #E98D7C;
    --color-warning: #F9C87F;
}

.light-theme body {
    background: linear-gradient(135deg, #DCE8C0, #C5DBA1);
    background-size: 400% 400%;
    animation: gradientBackground 15s ease infinite;
    background-image: none;
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
    padding-left: 340px;
    line-height: 1.6;
    letter-spacing: 0.2px;
}

.light-theme body {
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100" height="100" opacity="0.1"><path d="M10,10 Q50,20 90,10 Q80,50 90,90 Q50,80 10,90 Q20,50 10,10 Z" fill="%2376BB43"/></svg>');
    background-size: 300px;
}

.dashboard {
    max-width: calc(100% - 60px);
    width: 100%;
    margin: 0 auto;
}

header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.light-theme header {
    background-color: var(--color-card);
    padding: 15px 25px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.logo {
    display: flex;
    align-items: center;
}

.logo-icon {
    width: 40px;
    height: 40px;
    background-color: var(--color-highlight);
    border-radius: 8px;
    margin-right: 12px;
    display: flex;
    justify-content: center;
    align-items: center;
    color: var(--color-text);
    font-weight: bold;
}

.light-theme .logo-icon {
    color: white;
}

h1 {
    font-size: 24px;
    font-weight: 600;
    color: var(--color-text);
}

.light-theme h1 {
    color: var(--color-highlight);
}

.user-info {
    display: flex;
    align-items: center;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: var(--color-accent3);
    margin-right: 10px;
    display: flex;
    justify-content: center;
    align-items: center;
    color: var(--color-text);
}

.light-theme .user-avatar {
    background-color: var(--color-accent1);
    color: white;
    font-weight: bold;
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

.primary-btn {
    background-color: var(--color-highlight);
    color: var(--color-text);
}

.light-theme .primary-btn {
    color: white;
}

.secondary-btn {
    background-color: rgba(161, 180, 84, 0.2);
    color: var(--color-text);
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
    padding: 30px 25px;
    display: flex;
    align-items: center;
    border-bottom: 1px solid rgba(164, 188, 46, 0.2);
}

.navbar-logo {
    display: flex;
    align-items: center;
}

.navbar-logo-icon {
    width: 45px;
    height: 45px;
    background-color: var(--color-highlight);
    border-radius: 8px;
    margin-right: 15px;
    display: flex;
    justify-content: center;
    align-items: center;
    color: var(--color-text);
    font-weight: bold;
    font-size: 20px;
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
    margin-bottom: 5px;
}

.navbar a {
    text-decoration: none;
    color: var(--color-text);
    font-size: 17px;
    padding: 15px 25px;
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
    position: relative;
    opacity: 0.9;
    border-radius: 0 8px 8px 0;
    margin-right: 10px;
}

.navbar a:hover {
    background-color: rgba(161, 180, 84, 0.08);
    color: var(--color-highlight);
    opacity: 1;
}

.navbar a.active {
    background-color: rgba(161, 180, 84, 0.12);
    color: var(--color-highlight);
    opacity: 1;
    font-weight: 500;
    border-left: 3px solid var(--color-highlight);
}

.navbar-icon {
    margin-right: 15px;
    width: 24px;
    font-size: 20px;
}

.navbar-footer {
    padding: 20px;
    border-top: 1px solid rgba(164, 188, 46, 0.2);
    font-size: 12px;
    opacity: 0.6;
    text-align: center;
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
    background-color: rgba(234, 240, 220, 0.85);
    backdrop-filter: blur(10px);
    box-shadow: 3px 0 20px rgba(0, 0, 0, 0.06);
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
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.edit-btn {
    background-color: rgba(161, 180, 84, 0.2);
    color: var(--color-highlight);
}

.delete-btn {
    background-color: rgba(207, 134, 134, 0.2);
    color: var(--color-danger);
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

.btn-import,
.btn-download-template {
    padding: 12px 24px;
    border-radius: 10px;
    border: none;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-import {
    background: linear-gradient(135deg, var(--color-highlight), var(--color-accent3));
    color: white;
    min-width: 180px;
}

.btn-import:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(161, 180, 84, 0.3);
}

.btn-import:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

.btn-download-template {
    background-color: rgba(161, 180, 84, 0.2);
    color: var(--color-text);
    border: 1px solid rgba(161, 180, 84, 0.3);
}

.btn-download-template:hover {
    background-color: rgba(161, 180, 84, 0.3);
    transform: translateY(-2px);
}

.btn-cancel {
    padding: 12px 24px;
    border-radius: 10px;
    border: none;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    background-color: rgba(207, 134, 134, 0.2);
    color: var(--color-danger);
    border: 1px solid rgba(207, 134, 134, 0.3);
}

.btn-cancel:hover {
    background-color: rgba(207, 134, 134, 0.3);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(207, 134, 134, 0.2);
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

.light-theme .btn-import {
    background: linear-gradient(135deg, var(--color-accent3), var(--color-highlight));
}

.light-theme .btn-download-template {
    background-color: rgba(142, 185, 110, 0.2);
    border: 1px solid rgba(142, 185, 110, 0.3);
}

.light-theme .btn-download-template:hover {
    background-color: rgba(142, 185, 110, 0.3);
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
    </style>
</head>
<body class="dark-theme">
    <div class="dashboard">
        <header>
            <div class="logo">
                <h1>Nutrition Event Notifications</h1>
            </div>
            <div class="user-info">
                <div class="user-avatar"><?php echo htmlspecialchars(substr($username, 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($username); ?></span>
            </div>
        </header>

        <div class="event-container">
            <div class="event-header">
                <h2>Create New Event Notification</h2>
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
                            <button type="button" class="btn-import" id="importBtn" disabled onclick="uploadCSVWithAjax()">
                                📥 Import Events
                            </button>
                            <button type="submit" name="import_csv" class="btn-import" id="importBtnFallback" style="display: none;">
                                📥 Import Events (Fallback)
                            </button>
                            <button type="button" class="btn-cancel" id="cancelBtn" style="display: none;" onclick="cancelUpload()">
                                ❌ Cancel Upload
                            </button>
                            <button type="button" class="btn-download-template" onclick="downloadTemplate()">
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
                    Event created successfully! Sent to <?php echo isset($_GET['devices']) ? htmlspecialchars($_GET['devices']) : 'all'; ?> users.
                </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['imported'])): ?>
                <div class="alert alert-success">
                    ✅ Successfully imported <?php echo htmlspecialchars($_GET['imported']); ?> events!
                    <?php if(isset($_GET['errors']) && $_GET['errors'] > 0): ?>
                        <br>⚠️ <?php echo htmlspecialchars($_GET['errors']); ?> rows had errors and were skipped.
                    <?php endif; ?>
                    <br><small>You can now view the imported events in the table below.</small>
                </div>
            <?php endif; ?>
            
            <form class="event-form" method="POST" action="event.php">
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
                    <input type="text" id="eventLocation" name="eventLocation" placeholder="e.g., Barangay Hall, Lamao" required>
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
                    <button type="submit" name="create_event" class="primary-btn">Create Event</button>
                </div>
                

            </form>
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
                </div>
            </div>
            
            <?php if(isset($_GET['deleted'])): ?>
                <div class="alert alert-success">
                    Event deleted successfully!
                </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['updated'])): ?>
                <div class="alert alert-success">
                    ✅ Event updated successfully!
                    <br>📱 Update notification sent to all users!
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
                                        <button onclick="openEditModal(<?php echo $program['program_id']; ?>, '<?php echo htmlspecialchars($program['title']); ?>', '<?php echo htmlspecialchars($program['type']); ?>', '<?php echo htmlspecialchars($program['description']); ?>', '<?php echo $program['date_time']; ?>', '<?php echo htmlspecialchars($program['location']); ?>', '<?php echo htmlspecialchars($program['organizer']); ?>')" class="action-btn edit-btn">Edit</button>
                                        <a href="event.php?delete=<?php echo $program['program_id']; ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this event?')">Delete</a>
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
                    <img src="logo.png" alt="Logo" style="width: 40px; height: 40px;">
                </div>
                <div class="navbar-logo-text">NutriSaur</div>
            </div>
        </div>
        <div class="navbar-menu">
            <ul>
                <li><a href="dash.php"><span class="navbar-icon">📊</span><span>Dashboard</span></a></li>
                <li><a href="community_hub.php"><span class="navbar-icon">🏘️</span><span>Community Nutrition Hub</span></a></li>
                <li><a href="event.php" class="active"><span class="navbar-icon">⚠️</span><span>Nutrition Event Notifications</span></a></li>
                <li><a href="USM.php"><span class="navbar-icon">👥</span><span>User Management</span></a></li>
                <li><a href="AI.php"><span class="navbar-icon">🤖</span><span>Chatbot & AI Logs</span></a></li>
                <li><a href="settings.php"><span class="navbar-icon">⚙️</span><span>Settings & Admin</span></a></li>
                <li><a href="logout.php" style="color: #ff5252;"><span class="navbar-icon">🚪</span><span>Logout</span></a></li>
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
                    <input type="text" id="edit_eventLocation" name="eventLocation" required>
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
                    <button type="submit" class="primary-btn">Update Event</button>
                    <button type="button" class="secondary-btn" onclick="closeEditModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Remove the old theme toggle code and replace with this
        function loadSavedTheme() {
            const savedTheme = localStorage.getItem('nutrisaur-theme');
            if (savedTheme === 'light') {
                document.body.classList.remove('dark-theme');
                document.body.classList.add('light-theme');
            } else {
                document.body.classList.add('dark-theme');
                document.body.classList.remove('light-theme');
            }
        }



        // Load theme on page load
        window.addEventListener('DOMContentLoaded', () => {
            loadSavedTheme();
        });



        // Toggle training groups (only if they exist)
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
                            <a href="edit_program.php?id=${event.program_id}" class="action-btn edit-btn">Edit</a>
                            <a href="event.php?delete=${event.program_id}" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this event?')">Delete</a>
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
            
            const csvContent = `Event Title,Type,Date & Time,Location,Organizer,Description,Notification Type,Recipient Group
Sample Event,Workshop,${formatDate(future1)},Sample Location,Sample Organizer,Sample description,email,All Users`;
            
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
            formData.append('import_csv', '1');
            
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
            .then(response => response.text())
            .then(data => {
                console.log('Upload response received');
                // Check if the response contains success indicators
                if (data.includes('imported=') || data.includes('success')) {
                    // Reload the page to show the imported data
                    window.location.reload();
                } else {
                    // Show error message
                    alert('Upload failed. Please check the file format and try again.');
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
            
            fetch('http://localhost/thesis355/unified_api.php', {
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
            
            fetch('http://localhost/thesis355/unified_api.php', {
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
                                📍 ${notification.location} | 📅 ${new Date(notification.date_time).toLocaleDateString()}
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
        });
        

        
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
            document.getElementById('edit_eventLocation').value = location;
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
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>
</html>