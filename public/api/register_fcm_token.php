<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database connection details - Updated for Railway
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
    
    $conn = new PDO($dsn, $mysql_user, $mysql_password, $pdoOptions);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

// Function to log FCM operations
function logFCMOperation($message, $data = null) {
    $logEntry = date('Y-m-d H:i:s') . " - " . $message;
    if ($data) {
        $logEntry .= " - Data: " . json_encode($data);
    }
    error_log("[FCM] " . $logEntry);
}

// Function to validate FCM token format
function isValidFCMToken($token) {
    // FCM tokens are typically 140+ characters long and can contain alphanumeric characters, dots, and other valid characters
    return strlen($token) >= 140 && preg_match('/^[a-zA-Z0-9:_\-\.]+$/', $token);
}

// Function to clean and validate barangay name
function cleanBarangayName($barangay) {
    if (empty($barangay)) {
        return null;
    }
    
    // Remove extra spaces and normalize
    $cleaned = trim($barangay);
    
    // Map common variations to standard names
    $barangayMap = [
        'CITY OF BALANGA (Capital)' => 'CITY OF BALANGA',
        'CITY OF BALANGA' => 'CITY OF BALANGA',
        'ABUCAY' => 'MUNICIPALITY_ABUCAY',
        'BAGAC' => 'MUNICIPALITY_BAGAC',
        'BALANGA' => 'CITY OF BALANGA',
        'DINALUPIHAN' => 'MUNICIPALITY_DINALUPIHAN',
        'HERMOSA' => 'MUNICIPALITY_HERMOSA',
        'LIMAY' => 'MUNICIPALITY_LIMAY',
        'MARIVELES' => 'MUNICIPALITY_MARIVELES',
        'MORONG' => 'MUNICIPALITY_MORONG',
        'ORANI' => 'MUNICIPALITY_ORANI',
        'ORION' => 'MUNICIPALITY_ORION',
        'PILAR' => 'MUNICIPALITY_PILAR',
        'SAMAL' => 'MUNICIPALITY_SAMAL'
    ];
    
    // Check if this is a municipality name that should be mapped
    foreach ($barangayMap as $key => $value) {
        if (stripos($cleaned, $key) !== false) {
            return $value;
        }
    }
    
    return $cleaned;
}

try {
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method is allowed');
    }
    
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        // Try form data as fallback
        $data = $_POST;
    }
    
    if (!$data) {
        throw new Exception('No data received');
    }
    
    logFCMOperation("FCM token registration request received", $data);
    
    // Validate required fields
    $requiredFields = ['fcm_token'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    $fcmToken = trim($data['fcm_token']);
    $deviceName = isset($data['device_name']) ? trim($data['device_name']) : 'Unknown Device';
    $userEmail = isset($data['user_email']) ? trim($data['user_email']) : null;
    $userBarangay = isset($data['user_barangay']) ? cleanBarangayName($data['user_barangay']) : null;
    $appVersion = isset($data['app_version']) ? trim($data['app_version']) : '1.0';
    $platform = isset($data['platform']) ? trim($data['platform']) : 'android';
    
    // Validate FCM token format
    if (!isValidFCMToken($fcmToken)) {
        throw new Exception('Invalid FCM token format');
    }
    
    // Check if this exact token already exists
    $checkStmt = $conn->prepare("SELECT id, user_email, user_barangay FROM fcm_tokens WHERE fcm_token = ?");
    $checkStmt->execute([$fcmToken]);
    $existingToken = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingToken) {
        // Token exists, check if we need to update anything
        $needsUpdate = false;
        $updateFields = [];
        $updateParams = [];
        
        // Check if user_email needs updating
        if ($userEmail && $userEmail !== $existingToken['user_email']) {
            $updateFields[] = "user_email = ?";
            $updateParams[] = $userEmail;
            $needsUpdate = true;
        }
        
        // Check if user_barangay needs updating
        if ($userBarangay && $userBarangay !== $existingToken['user_barangay']) {
            $updateFields[] = "user_barangay = ?";
            $updateParams[] = $userBarangay;
            $needsUpdate = true;
        }
        
        // Always update device_name and timestamp for tracking
        $updateFields[] = "device_name = ?";
        $updateParams[] = $deviceName;
        $updateFields[] = "updated_at = NOW()";
        
        if ($needsUpdate) {
            // Update with new information
            $updateParams[] = $fcmToken; // For WHERE clause
            $updateSQL = "UPDATE fcm_tokens SET " . implode(", ", $updateFields) . " WHERE fcm_token = ?";
            $updateStmt = $conn->prepare($updateSQL);
            $updateStmt->execute($updateParams);
            
            logFCMOperation("FCM token updated with new user information", [
                'token_id' => $existingToken['id'],
                'user_email' => $userEmail,
                'user_barangay' => $userBarangay,
                'action' => 'updated'
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'FCM token updated successfully',
                'action' => 'updated',
                'token_id' => $existingToken['id']
            ]);
        } else {
            // No meaningful changes, just acknowledge
            logFCMOperation("FCM token already registered, no changes needed", [
                'token_id' => $existingToken['id'],
                'action' => 'no_change'
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'FCM token already registered, no changes needed',
                'action' => 'no_change',
                'token_id' => $existingToken['id']
            ]);
        }
    } else {
        // Check if this device already has a token for this user email
        if ($userEmail) {
            $deviceCheckStmt = $conn->prepare("SELECT id, fcm_token FROM fcm_tokens WHERE user_email = ? AND device_name = ?");
            $deviceCheckStmt->execute([$userEmail, $deviceName]);
            $existingDeviceToken = $deviceCheckStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingDeviceToken) {
                // This device already has a token for this user, update it
                $updateStmt = $conn->prepare("
                    UPDATE fcm_tokens 
                    SET fcm_token = ?, user_barangay = ?, app_version = ?, platform = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                
                $updateStmt->execute([
                    $fcmToken,
                    $userBarangay,
                    $appVersion,
                    $platform,
                    $existingDeviceToken['id']
                ]);
                
                logFCMOperation("FCM token updated for existing device user", [
                    'token_id' => $existingDeviceToken['id'],
                    'user_email' => $userEmail,
                    'user_barangay' => $userBarangay,
                    'device' => $deviceName,
                    'action' => 'updated_device'
                ]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'FCM token updated for existing device user',
                    'action' => 'updated_device',
                    'token_id' => $existingDeviceToken['id']
                ]);
                return;
            }
        }
        // New token, insert it
        $insertStmt = $conn->prepare("
            INSERT INTO fcm_tokens (fcm_token, device_name, user_email, user_barangay, app_version, platform, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $insertStmt->execute([
            $fcmToken,
            $deviceName,
            $userEmail,
            $userBarangay,
            $appVersion,
            $platform
        ]);
        
        $tokenId = $conn->lastInsertId();
        
        logFCMOperation("New FCM token registered", [
            'token_id' => $tokenId,
            'user_email' => $userEmail,
            'user_barangay' => $userBarangay,
            'device' => $deviceName
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'FCM token registered successfully',
            'action' => 'registered',
            'token_id' => $tokenId
        ]);
    }
    
    // Log successful registration
    logFCMOperation("FCM token registration completed successfully");
    
} catch (Exception $e) {
    logFCMOperation("FCM token registration failed: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'error_code' => 'REGISTRATION_FAILED'
    ]);
} catch (PDOException $e) {
    logFCMOperation("Database error during FCM registration: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error_code' => 'DATABASE_ERROR'
    ]);
}
?>
