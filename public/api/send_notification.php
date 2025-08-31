<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database connection details
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

try {
    $dsn = "mysql:host={$mysql_host};port={$mysql_port};dbname={$mysql_database};charset=utf8mb4";
    $pdoOptions = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 30,
        PDO::ATTR_PERSISTENT => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
    ];
    
    $pdo = new PDO($dsn, $mysql_user, $mysql_password, $pdoOptions);
    $pdo->query("SELECT 1"); // Test connection
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Handle notification request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get notification data
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
        
        // 🚨 NEW: Handle "all" users case for events
        if ($targetUser === 'all') {
            error_log("🚨 SENDING TO ALL USERS - Event notification");
            
            // Get ALL active FCM tokens
            $stmt = $pdo->prepare("
                SELECT ft.fcm_token, ft.user_email
                FROM fcm_tokens ft
                WHERE ft.is_active = TRUE 
                AND ft.fcm_token IS NOT NULL AND ft.fcm_token != ''
            ");
            $stmt->execute();
            $allTokens = $stmt->fetchAll();
            
            if (empty($allTokens)) {
                error_log("❌ No active FCM tokens found for any users");
                echo json_encode([
                    'success' => false,
                    'message' => 'No active FCM tokens found for any users',
                    'target_user' => 'all',
                    'reason' => 'no_fcm_tokens_found'
                ]);
                exit;
            }
            
            error_log("✅ Found " . count($allTokens) . " active FCM tokens for mass notification");
            
            // Extract just the tokens
            $fcmTokens = array_column($allTokens, 'fcm_token');
            
            // Send FCM notification to ALL users
            $notificationSent = sendFCMNotification($fcmTokens, [
                'title' => $title,
                'body' => $body,
                'data' => [
                    'notification_type' => 'event_notification',
                    'target_user' => 'all',
                    'alert_type' => $notificationData['alert_type'] ?? 'event_notification',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
                ]
            ]);
            
            if ($notificationSent) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Mass notification sent successfully to ' . count($fcmTokens) . ' users',
                    'target_user' => 'all',
                    'devices_notified' => count($fcmTokens)
                ]);
            } else {
                throw new Exception('Failed to send mass FCM notification');
            }
            
        } else if (strpos($targetUser, 'MUNICIPALITY_') === 0 || strpos($targetUser, 'ABUCAY') !== false || strpos($targetUser, 'BAGAC') !== false || strpos($targetUser, 'MARIVELES') !== false || strpos($targetUser, 'MORONG') !== false || strpos($targetUser, 'ORANI') !== false || strpos($targetUser, 'PILAR') !== false || strpos($targetUser, 'SAMAL') !== false) {
            // 🚨 NEW: Handle location-based targeting for events
            error_log("🚨 SENDING TO LOCATION: $targetUser - Event notification");
            
            // Extract municipality name from target
            $municipality = '';
            if (strpos($targetUser, 'MUNICIPALITY_') === 0) {
                $municipality = str_replace('MUNICIPALITY_', '', $targetUser);
            } else if (strpos($targetUser, 'ABUCAY') !== false) {
                $municipality = 'ABUCAY';
            } else if (strpos($targetUser, 'BAGAC') !== false) {
                $municipality = 'BAGAC';
            } else if (strpos($targetUser, 'MARIVELES') !== false) {
                $municipality = 'MARIVELES';
            } else if (strpos($targetUser, 'MORONG') !== false) {
                $municipality = 'MORONG';
            } else if (strpos($targetUser, 'ORANI') !== false) {
                $municipality = 'ORANI';
            } else if (strpos($targetUser, 'PILAR') !== false) {
                $municipality = 'PILAR';
            } else if (strpos($targetUser, 'SAMAL') !== false) {
                $municipality = 'SAMAL';
            }
            
            // Get FCM tokens for users in this municipality
            $stmt = $pdo->prepare("
                SELECT ft.fcm_token, ft.user_email, ft.user_barangay
                FROM fcm_tokens ft
                WHERE ft.is_active = TRUE 
                AND ft.fcm_token IS NOT NULL AND ft.fcm_token != ''
                AND ft.user_barangay IS NOT NULL 
                AND ft.user_barangay != ''
                AND (
                    ft.user_barangay LIKE ? 
                    OR ft.user_barangay LIKE ?
                    OR ft.user_barangay LIKE ?
                )
            ");
            $stmt->execute([
                "%$municipality%",
                "%$targetUser%",
                "%" . strtolower($municipality) . "%"
            ]);
            $locationTokens = $stmt->fetchAll();
            
            if (empty($locationTokens)) {
                error_log("❌ No active FCM tokens found for location: $targetUser");
                echo json_encode([
                    'success' => false,
                    'message' => "No active FCM tokens found for location: $targetUser",
                    'target_user' => $targetUser,
                    'reason' => 'no_fcm_tokens_for_location'
                ]);
                exit;
            }
            
            error_log("✅ Found " . count($locationTokens) . " active FCM tokens for location: $targetUser");
            
            // Extract just the tokens
            $fcmTokens = array_column($locationTokens, 'fcm_token');
            
            // Send FCM notification to users in this location
            $notificationSent = sendFCMNotification($fcmTokens, [
                'title' => $title,
                'body' => $body,
                'data' => [
                    'notification_type' => 'event_notification',
                    'target_user' => $targetUser,
                    'alert_type' => $notificationData['alert_type'] ?? 'event_notification',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
                ]
            ]);
            
            if ($notificationSent) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Location-based notification sent successfully to ' . count($fcmTokens) . ' users in ' . $targetUser,
                    'target_user' => $targetUser,
                    'devices_notified' => count($fcmTokens)
                ]);
            } else {
                throw new Exception('Failed to send location-based FCM notification');
            }
            
        } else {
            // 🚨 ORIGINAL LOGIC: Single user notification
            // Get FCM token for the specific user
            $stmt = $pdo->prepare("
                SELECT ft.fcm_token 
                FROM fcm_tokens ft
                WHERE ft.user_email = ? AND ft.is_active = TRUE 
                AND ft.fcm_token IS NOT NULL AND ft.fcm_token != ''
            ");
            $stmt->execute([$targetUser]);
            $fcmToken = $stmt->fetchColumn();
            
            // Check if user has a valid FCM token
            if (!$fcmToken) {
                error_log("No valid FCM token found for user: " . $targetUser . " - skipping notification");
                echo json_encode([
                    'success' => false,
                    'message' => 'No valid FCM token found for user: ' . $userName,
                    'target_user' => $targetUser,
                    'reason' => 'no_fcm_token'
                ]);
                exit;
            }
            
            // Validate FCM token format (should be 140+ characters, alphanumeric + :_-. and other valid characters)
            if (strlen($fcmToken) < 140 || !preg_match('/^[a-zA-Z0-9:_\-\.]+$/', $fcmToken)) {
                error_log("Invalid FCM token format for user: " . $targetUser . " - token: " . substr($fcmToken, 0, 50) . "...");
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid FCM token format for user: ' . $userName,
                    'target_user' => $targetUser,
                    'reason' => 'invalid_token_format'
                ]);
                exit;
            }
            
            error_log("Using valid FCM token for user: " . $targetUser . " - token: " . substr($fcmToken, 0, 20) . "...");
            
            // Send FCM notification
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
                echo json_encode([
                    'success' => true,
                    'message' => 'Personal notification sent successfully to ' . $userName,
                    'target_user' => $targetUser,
                    'devices_notified' => 1
                ]);
            } else {
                throw new Exception('Failed to send FCM notification');
            }
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error sending notification: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Only POST method allowed']);
}

/**
 * Send FCM notification using Firebase Admin SDK
 */
function sendFCMNotification($fcmTokens, $notificationData) {
    try {
        // Firebase Admin SDK configuration - Use your correct nutrisaur-ebf29 project
        $serviceAccountData = [
            "type" => "service_account",
            "project_id" => "nutrisaur-ebf29",
            "private_key_id" => "152a242b3b3d1fd2a41fc3f22f188a517377b1f6",
            "private_key" => "-----BEGIN PRIVATE KEY-----\nMIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQCYjO00aaKgKo9K\nlCIShWR27+fiQubXdeGpEl9a1VbkB9mA537FXiDwHcTVi5odcVpt1P8neoeTTCAZ\nY9LyzEmPrvN6KhgvscLg7bNftdjWowGy6BCxLDKMxjo9rvz0twZJPeyhfcgIg3a4\nOUBawhI3wJ9AJal2x2dovn7r2dH3rQx17lG4Lp8x/0ccARVDXgKTBBp8Wz6qV6dM\nyINFMRV21za0a9D74v99iMRylTR+BbHmBNSFpBnsEHRncxMrOAmBDUxDKgFP9SQQ\nWIuoLZdUcl4myFutYgxPNOLPrK09SqFGkllXsTOaXpPoMjkUT05q+o6zlFSMfdhf\nr3Pc9v+BAgMBAAECggEABjctvgY00lZRls4Q0lTdikwNlGY57hr3OhKN0I9jNjDM\nr6ge/e8vI+FuoO3KdnslHlcAm2zuY7XFFAvBb1OcBq7v7DEVYbIaONxug86OunYT\nTmUOsw1UNPCptFQyKc1gQ/EyOEU99nzWxDxO7zO7lspyIqS2Mij/QWELnlP2bmMu\nRXb9sEzw2YbddUgpV5G1A0z9ZKKqoR5DgqD9Fyt8Im4Jxm/tqFKjY/mS5vWtQCTG\nnj1AkiLfcHQY6gAs7DiM/WxqKQL/sgI3HE7GcKYiqZ6CUu6S07TOaTknat7gSTqC\nWXVRDOZV6wv0dYZLaGZ3ZGH9arqFpjExy9G4diRnbQKBgQDFvQPpht3zWBQS2BB3\nv5E6tJoC0JKyB6+kuvlTYOj6w834tIOj946kQd51xOURvjMXELkuDsv+v593PFDK\nQzk+uZnBzaYHO22nS3jEMgrcoypKQsRM7NWf6l005f9ZywQZoElgKpw/yy9hj4fA\nHEHyIWbxQ9YepKpN+JNtbsFWRQKBgQDFf3zV46WljQJuLnQK9CHbyQjusrRRsfth\ntUH+6QtE0aZ/yyiFMb59Qake6E5BTS6j1/fmqSJNnlUy8Vzq17MhKVhOL+XXbAtS\noacGDHRVbZqg07QfylatJu1tuYWGP4HkACX6wt4VZkP553c2P3bc11VdqUWFPg2w\nB+hlQqAGDQKBgQCDznOeJXUrMSnoSbfoanx/GkWS3L78Bt2Qu8VYS7/g78YLIyCg\nmnKtkO6dqApdYmAh3tbhGaHnBIpia4Ua3eZ5pjQUmGU0auuz2T394bGV0vlsmMbK\n1A+t0gYhLbKhgw8PmeVvQdf3OhQyPv9pEizvHk7FQcenk3GmGa0EBBDB1QKBgQCq\nLu5KfSzrGDRE74CNQ8u9UWGCFrmtQBonGwLwfq54MdQwgMa8552G38K04Gc2fCS5\niMuUlp/5lHtEN5niv9YauD7guQNseyzSmnuLicXhK144QdUQI9JGyKmFiH0Xrrfl\n2X7gs3Sdqg7fVGZ0d4GcUW29FLTUWSGAU4AdaajtLQKBgCXK629zzUE/7op7Zl9p\nEp/s+xn5lhrpo8ZXho0D/ajfQkjrDbMmpWdUMIwtAK75WMn16TRA/+RIynAV5Z5B\nte0wbxwkXAXtAcp2qH+1fe/9w7hTQLP1808HP/VnG9fLnWECIxRxrsP33am3hUHo\n2q8Bsx0vioPXrYNHzr3K1hRH\n-----END PRIVATE KEY-----\n",
            "client_email" => "firebase-adminsdk-fbsvc@nutrisaur-ebf29.iam.gserviceaccount.com",
            "client_id" => "115564910409083368011",
            "auth_uri" => "https://accounts.google.com/o/oauth2/auth",
            "token_uri" => "https://oauth2.googleapis.com/token",
            "auth_provider_x509_cert_url" => "https://www.googleapis.com/oauth2/v1/certs",
            "client_x509_cert_url" => "https://www.googleapis.com/robot/v1/metadata/x509/firebase-adminsdk-fbsvc%40nutrisaur-ebf29.iam.gserviceaccount.com",
            "universe_domain" => "googleapis.com"
        ];
        
        // Try Firebase Admin SDK first, fallback to legacy API
        error_log("FCM Debug: Trying Firebase Admin SDK first");
        $result = sendFCMViaEnhancedCurl($fcmTokens, $notificationData, $serviceAccountData);
        if (!$result) {
            error_log("FCM Debug: Firebase Admin SDK failed, trying legacy FCM API");
            return sendFCMViaCurl($fcmTokens, $notificationData);
        }
        return $result;
        

        
    } catch (Exception $e) {
        error_log("FCM Notification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send FCM notification via enhanced cURL with Firebase authentication
 */
function sendFCMViaEnhancedCurl($fcmTokens, $notificationData, $serviceAccountData) {
    try {
        // Use service account data directly
        $serviceAccount = $serviceAccountData;
        if (!$serviceAccount) {
            throw new Exception('Invalid service account data');
        }
        
        // Get project ID and private key
        $projectId = $serviceAccount['project_id'];
        $privateKey = $serviceAccount['private_key'];
        $clientEmail = $serviceAccount['client_email'];
        
        // Get OAuth2 access token for Firebase authentication
        $accessToken = getFirebaseAccessToken($serviceAccount);
        if (!$accessToken) {
            throw new Exception('Failed to get OAuth2 access token');
        }
        
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        
        foreach ($fcmTokens as $token) {
            $data = [
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
                            'sound' => 'default'
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
            
            $headers = [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                error_log("Enhanced cURL Error: " . $error);
                continue;
            }
            
            if ($httpCode === 200) {
                $responseData = json_decode($response, true);
                if (isset($responseData['name'])) {
                    error_log("FCM Notification sent successfully via Firebase Admin SDK to token: " . substr($token, 0, 20) . "...");
                } else {
                    error_log("FCM Response: " . $response);
                }
            } else {
                error_log("FCM HTTP Error: " . $httpCode . " - " . $response);
            }
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Enhanced FCM Error: " . $e->getMessage());
        // Fallback to basic cURL
        return sendFCMViaCurl($fcmTokens, $notificationData);
    }
}

/**
 * Send FCM notification via basic cURL (fallback method)
 */
function sendFCMViaCurl($fcmTokens, $notificationData) {
    try {
        // FCM Server Key from Firebase Console (Project Settings > Cloud Messaging)
        // You need to get this from Firebase Console > Project Settings > Cloud Messaging > Server key
        $serverKey = 'AIzaSyBGArwSy8j6_pQwR4ozFudKFcM5jHHXwTA'; // This might be the API key, not server key
        
        $url = 'https://fcm.googleapis.com/fcm/send';
        
        $successCount = 0;
        $totalTokens = count($fcmTokens);
        
        foreach ($fcmTokens as $token) {
            $data = [
                'to' => $token,
                'notification' => [
                    'title' => $notificationData['title'],
                    'body' => $notificationData['body'],
                    'sound' => 'default',
                    'badge' => '1'
                ],
                'data' => $notificationData['data'] ?? [],
                'priority' => 'high',
                'content_available' => true
            ];
            
            $headers = [
                'Authorization: key=' . $serverKey,
                'Content-Type: application/json'
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                error_log("Basic cURL Error: " . $error);
                continue;
            }
            
            if ($httpCode === 200) {
                $responseData = json_decode($response, true);
                if (isset($responseData['success']) && $responseData['success'] == 1) {
                    error_log("FCM Notification sent successfully via basic cURL to token: " . substr($token, 0, 20) . "...");
                    $successCount++;
                } else {
                    $errorMsg = $responseData['results'][0]['error'] ?? 'Unknown error';
                    error_log("FCM Error: " . $errorMsg);
                    error_log("FCM Response: " . $response);
                }
            } else {
                error_log("FCM HTTP Error: " . $httpCode . " - " . $response);
            }
        }
        
        // Only return true if at least one notification was sent successfully
        if ($successCount > 0) {
            error_log("FCM Summary: {$successCount}/{$totalTokens} notifications sent successfully");
            return true;
        } else {
            error_log("FCM Summary: 0/{$totalTokens} notifications sent successfully - returning false");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Basic FCM cURL Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate Firebase JWT token for authentication
 */
function generateFirebaseJWT($projectId, $privateKey, $clientEmail) {
    try {
        error_log("JWT Debug: Starting JWT generation");
        
        // JWT header
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT'
        ];
        
        // JWT payload
        $now = time();
        $payload = [
            'iss' => $clientEmail,
            'sub' => $clientEmail,
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600, // 1 hour
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging'
        ];
        
        error_log("JWT Debug: Header and payload created");
        
        // Create JWT
        $headerEncoded = base64url_encode(json_encode($header));
        $payloadEncoded = base64url_encode(json_encode($payload));
        
        error_log("JWT Debug: Header and payload encoded");
        
        // Sign the JWT
        $signature = '';
        if (openssl_sign($headerEncoded . '.' . $payloadEncoded, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            $signatureEncoded = base64url_encode($signature);
            $jwt = $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
            error_log("JWT Debug: JWT generated successfully, length: " . strlen($jwt));
            return $jwt;
        } else {
            error_log("JWT Error: Failed to sign JWT with openssl_sign");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("JWT Error: JWT Generation Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get Firebase OAuth2 access token using JWT
 */
function getFirebaseAccessToken($serviceAccountData) {
    try {
        error_log("OAuth Debug: Starting OAuth2 token request");
        
        $jwtToken = generateFirebaseJWT(
            $serviceAccountData['project_id'],
            $serviceAccountData['private_key'],
            $serviceAccountData['client_email']
        );
        
        if (!$jwtToken) {
            throw new Exception("Failed to generate JWT token");
        }
        
        $tokenUrl = 'https://oauth2.googleapis.com/token';
        $postData = [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwtToken
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        error_log("OAuth Debug: HTTP Code: " . $httpCode);
        error_log("OAuth Debug: Response: " . $response);
        
        if ($httpCode !== 200) {
            throw new Exception("OAuth2 token request failed: HTTP $httpCode - $response");
        }
        
        $tokenData = json_decode($response, true);
        if (!isset($tokenData['access_token'])) {
            throw new Exception("No access token in OAuth2 response: " . $response);
        }
        
        error_log("OAuth Debug: Access token obtained successfully");
        return $tokenData['access_token'];
        
    } catch (Exception $e) {
        error_log("OAuth Debug Error: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Base64URL encode function
 */
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
?>
