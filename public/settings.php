<?php
if (session_status() === PHP_SESSION_NONE) {
session_start();
}

if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    header('Location: /home');
    exit;
}

// Use centralized DatabaseAPI - NO MORE HARDCODED CONNECTIONS!
require_once __DIR__ . '/api/DatabaseHelper.php';

// Get database helper instance
$db = DatabaseHelper::getInstance();

// Check if database is available
$dbError = null;
if (!$db->isAvailable()) {
    $dbError = "Database connection not available";
    error_log($dbError);
}

// Get user info
$username = $_SESSION['username'] ?? 'Unknown User';
$user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;

// Email sending function using SendGrid API (consistent with home.php)
function sendVerificationEmail($email, $username, $verificationCode, $subject = 'Email Verification') {
    // SendGrid API configuration
    $apiKey = $_ENV['SENDGRID_API_KEY'] ?? 'YOUR_SENDGRID_API_KEY_HERE';
    $apiUrl = 'https://api.sendgrid.com/v3/mail/send';
    
    $emailData = [
        'personalizations' => [
            [
                'to' => [
                    ['email' => $email, 'name' => $username]
                ],
                'subject' => 'NUTRISAUR - ' . $subject
            ]
        ],
        'from' => [
            'email' => 'noreply.nutrisaur@gmail.com',
            'name' => 'NUTRISAUR'
        ],
        'content' => [
            [
                'type' => 'text/plain',
                'value' => "Hello " . htmlspecialchars($username) . ",\n\nYour verification code is: " . $verificationCode . "\n\nThis code will expire in 10 minutes.\n\nIf you did not request this verification code, please ignore this email.\n\nBest regards,\nNUTRISAUR Team"
            ],
            [
                'type' => 'text/html',
                'value' => "
                <html>
                <head>
                    <title>NUTRISAUR - $subject</title>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                </head>
                <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4;'>
                    <div style='max-width: 600px; margin: 20px auto; background-color: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                        <div style='text-align: center; background-color: #2A3326; color: #A1B454; padding: 20px; border-radius: 8px 8px 0 0; margin: -20px -20px 20px -20px;'>
                            <h1 style='margin: 0; font-size: 24px;'>NUTRISAUR</h1>
                        </div>
                        <div style='padding: 20px 0;'>
                            <p>Hello " . htmlspecialchars($username) . ",</p>
                            <p>Your verification code is:</p>
                            <div style='background-color: #f8f9fa; border: 2px solid #2A3326; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px;'>
                                <span style='font-size: 28px; font-weight: bold; color: #2A3326; letter-spacing: 4px;'>" . $verificationCode . "</span>
                            </div>
                            <p><strong>This code will expire in 10 minutes.</strong></p>
                            <p>If you did not request this verification code, please ignore this email.</p>
                        </div>
                        <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 14px;'>
                            <p>Best regards,<br>NUTRISAUR Team</p>
                        </div>
                    </div>
                </body>
                </html>
                "
            ]
        ]
    ];
    
    // Send email via SendGrid API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Enhanced error logging
    error_log("SendGrid API Response: HTTP $httpCode, Response: $response, Error: " . ($curlError ?: 'None'));
    
    if ($curlError) {
        error_log("SendGrid cURL error: " . $curlError);
        return false;
    }
    
    if ($httpCode >= 200 && $httpCode < 300) {
        error_log("Email sent successfully via SendGrid API");
        return true;
    }
    
    error_log("SendGrid API failed. HTTP Code: $httpCode, Response: $response");
    return false;
}

/**
 * Send password setup email using the same method as verification emails
 */
function sendPasswordSetupEmail($email, $username, $resetToken) {
    // Create password setup link
    $setupLink = "https://" . $_SERVER['HTTP_HOST'] . "/home.php?setup_password=" . $resetToken;
    
    // Use the same email method as verification emails
    return sendVerificationEmail($email, $username, $setupLink, 'Admin Account Setup - Password Required');
}

// Municipalities and Barangays data
$municipalities = [
    'ABUCAY' => ['Bangkal', 'Calaylayan (Pob.)', 'Capitangan', 'Gabon', 'Laon (Pob.)', 'Mabatang', 'Omboy', 'Salian', 'Wawa (Pob.)'],
    'BAGAC' => ['Bagumbayan (Pob.)', 'Banawang', 'Binuangan', 'Binukawan', 'Ibaba', 'Ibis', 'Pag-asa (Wawa-Sibacan)', 'Parang', 'Paysawan', 'Quinawan', 'San Antonio', 'Saysain', 'Tabing-Ilog (Pob.)', 'Atilano L. Ricardo'],
    'CITY OF BALANGA (Capital)' => ['Bagumbayan', 'Cabog-Cabog', 'Munting Batangas (Cadre)', 'Cataning', 'Central', 'Cupang Proper', 'Cupang West', 'Dangcol (Bernabe)', 'Ibayo', 'Malabia', 'Poblacion', 'Pto. Rivas Ibaba', 'Pto. Rivas Itaas', 'San Jose', 'Sibacan', 'Camacho', 'Talisay', 'Tanato', 'Tenejero', 'Tortugas', 'Tuyo', 'Bagong Silang', 'Cupang North', 'Doña Francisca', 'Lote'],
    'DINALUPIHAN' => ['Bangal', 'Bonifacio (Pob.)', 'Burgos (Pob.)', 'Colo', 'Daang Bago', 'Dalao', 'Del Pilar (Pob.)', 'Gen. Luna (Pob.)', 'Gomez (Pob.)', 'Happy Valley', 'Kataasan', 'Layac', 'Luacan', 'Mabini Proper (Pob.)', 'Mabini Ext. (Pob.)', 'Magsaysay', 'Naparing', 'New San Jose', 'Old San Jose', 'Padre Dandan (Pob.)', 'Pag-asa', 'Pagalanggang', 'Pinulot', 'Pita', 'Rizal (Pob.)', 'Roosevelt', 'Roxas (Pob.)', 'Saguing', 'San Benito', 'San Isidro (Pob.)', 'San Pablo (Bulate)', 'San Ramon', 'San Simon', 'Santo Niño', 'Sapang Balas', 'Santa Isabel (Tabacan)', 'Torres Bugauen (Pob.)', 'Tucop', 'Zamora (Pob.)', 'Aquino', 'Bayan-bayanan', 'Maligaya', 'Payangan', 'Pentor', 'Tubo-tubo', 'Jose C. Payumo, Jr.'],
    'HERMOSA' => ['A. Rivera (Pob.)', 'Almacen', 'Bacong', 'Balsic', 'Bamban', 'Burgos-Soliman (Pob.)', 'Cataning (Pob.)', 'Culis', 'Daungan (Pob.)', 'Mabiga', 'Mabuco', 'Maite', 'Mambog - Mandama', 'Palihan', 'Pandatung', 'Pulo', 'Saba', 'San Pedro (Pob.)', 'Santo Cristo (Pob.)', 'Sumalo', 'Tipo', 'Judge Roman Cruz Sr. (Mandama)', 'Sacrifice Valley'],
    'LIMAY' => ['Alangan', 'Kitang I', 'Kitang 2 & Luz', 'Lamao', 'Landing', 'Poblacion', 'Reformista', 'Townsite', 'Wawa', 'Duale', 'San Francisco de Asis', 'St. Francis II'],
    'MARIVELES' => ['Alas-asin', 'Alion', 'Batangas II', 'Cabcaben', 'Lucanin', 'Baseco Country (Nassco)', 'Poblacion', 'San Carlos', 'San Isidro', 'Sisiman', 'Balon-Anito', 'Biaan', 'Camaya', 'Ipag', 'Malaya', 'Maligaya', 'Mt. View', 'Townsite'],
    'MORONG' => ['Binaritan', 'Mabayo', 'Nagbalayong', 'Poblacion', 'Sabang'],
    'ORANI' => ['Bagong Paraiso (Pob.)', 'Balut (Pob.)', 'Bayan (Pob.)', 'Calero (Pob.)', 'Paking-Carbonero (Pob.)', 'Centro II (Pob.)', 'Dona', 'Kaparangan', 'Masantol', 'Mulawin', 'Pag-asa', 'Palihan (Pob.)', 'Pantalan Bago (Pob.)', 'Pantalan Luma (Pob.)', 'Parang Parang (Pob.)', 'Centro I (Pob.)', 'Sibul', 'Silahis', 'Tala', 'Talimundoc', 'Tapulao', 'Tenejero (Pob.)', 'Tugatog', 'Wawa (Pob.)', 'Apollo', 'Kabalutan', 'Maria Fe', 'Puksuan', 'Tagumpay'],
    'ORION' => ['Arellano (Pob.)', 'Bagumbayan (Pob.)', 'Balagtas (Pob.)', 'Balut (Pob.)', 'Bantan', 'Bilolo', 'Calungusan', 'Camachile', 'Daang Bago (Pob.)', 'Daang Bilolo (Pob.)', 'Daang Pare', 'General Lim (Kaput)', 'Kapunitan', 'Lati (Pob.)', 'Lusungan (Pob.)', 'Puting Buhangin', 'Sabatan', 'San Vicente (Pob.)', 'Santo Domingo', 'Villa Angeles (Pob.)', 'Wakas (Pob.)', 'Wawa (Pob.)', 'Santa Elena'],
    'PILAR' => ['Ala-uli', 'Bagumbayan', 'Balut I', 'Balut II', 'Bantan Munti', 'Burgos', 'Del Rosario (Pob.)', 'Diwa', 'Landing', 'Liyang', 'Nagwaling', 'Panilao', 'Pantingan', 'Poblacion', 'Rizal (Pob.)', 'Santa Rosa', 'Wakas North', 'Wakas South', 'Wawa'],
    'SAMAL' => ['East Calaguiman (Pob.)', 'East Daang Bago (Pob.)', 'Ibaba (Pob.)', 'Imelda', 'Lalawigan', 'Palili', 'San Juan (Pob.)', 'San Roque (Pob.)', 'Santa Lucia', 'Sapa', 'Tabing Ilog', 'Gugo', 'West Calaguiman (Pob.)', 'West Daang Bago (Pob.)']
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $screening_data = [
            'municipality' => $_POST['municipality'] ?? '',
            'barangay' => $_POST['barangay'] ?? '',
            'sex' => $_POST['sex'] ?? '',
            'birthday' => $_POST['birthday'] ?? '',
            'is_pregnant' => $_POST['is_pregnant'] ?? '',
            'weight' => $_POST['weight'] ?? '',
            'height' => $_POST['height'] ?? '',
            'muac' => $_POST['muac'] ?? '',
            'screening_date' => date('Y-m-d H:i:s')
        ];

        // Get user info from session
        $user_email = $_SESSION['user_email'] ?? 'user@example.com';
        $name = $_SESSION['name'] ?? 'User';
        $password = $_SESSION['password'] ?? 'default_password';

        // Insert into community_users table using DatabaseHelper
        $insertData = [
                    'name' => $name,
            'email' => $user_email,
            'password' => $password,
            'municipality' => $screening_data['municipality'],
            'barangay' => $screening_data['barangay'],
            'sex' => $screening_data['sex'],
            'birthday' => $screening_data['birthday'],
            'is_pregnant' => $screening_data['is_pregnant'],
            'weight' => $screening_data['weight'],
            'height' => $screening_data['height'],
            'muac' => $screening_data['muac'],
            'screening_date' => $screening_data['screening_date'],
            'fcm_token' => $_POST['fcm_token'] ?? null
        ];
        
        // Insert using DatabaseHelper
        $result = $db->insert('community_users', $insertData);
                
                if ($result['success']) {
            $success_message = "Screening assessment saved successfully!";
                } else {
            $error_message = "Error saving screening assessment: " . ($result['error'] ?? 'Unknown error');
        }
        
    } catch (Exception $e) {
        $error_message = "Error saving screening assessment: " . $e->getMessage();
    }
}

// Handle AJAX requests for table management
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && !isset($_POST['municipality'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    
    switch ($action) {
        case 'update_profile':
            try {
                $name = $_POST['name'] ?? '';
                $email = $_POST['email'] ?? '';
                $currentUserId = $_SESSION['user_id'] ?? null;
                
                if (!$currentUserId) {
                    echo json_encode(['success' => false, 'error' => 'User not logged in']);
                    break;
                }
                
                require_once __DIR__ . "/../config.php";
                $pdo = getDatabaseConnection();
                if (!$pdo) {
                    echo json_encode(['success' => false, 'error' => 'Database connection not available']);
                    break;
                }
                
                // Update user profile in database
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE user_id = ?");
                $result = $stmt->execute([$name, $email, $currentUserId]);
                
                if ($result) {
                    // Update session data
                    $_SESSION['username'] = $name;
                    $_SESSION['email'] = $email;
                    
                    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to update profile']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
            }
            break;
            
        case 'send_verification_code':
            try {
                $email = $_SESSION['email'] ?? '';
                if (!$email) {
                    echo json_encode(['success' => false, 'error' => 'No email found in session']);
                    break;
                }
                
                // Generate 4-digit verification code
                $verificationCode = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                
                // Store verification code in session (expires in 10 minutes)
                $_SESSION['verification_code'] = $verificationCode;
                $_SESSION['verification_expires'] = time() + 600; // 10 minutes
                
                // Send email using Resend API
                if (sendVerificationEmail($email, $username, $verificationCode, 'Password Change Verification Code')) {
                    echo json_encode(['success' => true, 'message' => 'Verification code sent']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to send email']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Error sending verification code: ' . $e->getMessage()]);
            }
            break;
            
        case 'change_password':
            try {
                $currentPassword = $_POST['currentPassword'] ?? '';
                $newPassword = $_POST['newPassword'] ?? '';
                $verificationCode = $_POST['verificationCode'] ?? '';
                $useVerification = $_POST['useVerification'] === 'true';
                $currentUserId = $_SESSION['user_id'] ?? null;
                
                if (!$currentUserId) {
                    echo json_encode(['success' => false, 'error' => 'User not logged in']);
                    break;
                }
                
                require_once __DIR__ . "/../config.php";
                $pdo = getDatabaseConnection();
                if (!$pdo) {
                    echo json_encode(['success' => false, 'error' => 'Database connection not available']);
                    break;
                }
                
                // Get current user data
                $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
                $stmt->execute([$currentUserId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$user) {
                    echo json_encode(['success' => false, 'error' => 'User not found']);
                    break;
                }
                
                if ($useVerification) {
                    // Verify verification code
                    if (!isset($_SESSION['verification_code']) || !isset($_SESSION['verification_expires'])) {
                        echo json_encode(['success' => false, 'error' => 'No verification code found. Please request a new one.']);
                        break;
                    }
                    
                    if (time() > $_SESSION['verification_expires']) {
                        echo json_encode(['success' => false, 'error' => 'Verification code has expired. Please request a new one.']);
                        break;
                    }
                    
                    if ($_SESSION['verification_code'] !== $verificationCode) {
                        echo json_encode(['success' => false, 'error' => 'Invalid verification code']);
                        break;
                    }
                } else {
                    // Verify current password
                    if (!password_verify($currentPassword, $user['password'])) {
                        echo json_encode(['success' => false, 'error' => 'Current password is incorrect']);
                        break;
                    }
                }
                
                // Update password
                $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $result = $stmt->execute([$hashedNewPassword, $currentUserId]);
                
                if ($result) {
                    // Clear verification code if used
                    if ($useVerification) {
                        unset($_SESSION['verification_code']);
                        unset($_SESSION['verification_expires']);
                    }
                    
                    echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to update password']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
            }
            break;
            
        case 'send_email_verification_code':
            try {
                $newEmail = $_POST['newEmail'] ?? '';
                if (!$newEmail) {
                    echo json_encode(['success' => false, 'error' => 'No email provided']);
                    break;
                }
                
                // Generate 4-digit verification code
                $verificationCode = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                
                // Store verification code in session (expires in 10 minutes)
                $_SESSION['email_verification_code'] = $verificationCode;
                $_SESSION['email_verification_expires'] = time() + 600; // 10 minutes
                $_SESSION['pending_email'] = $newEmail;
                
                // Send email using Resend API
                if (sendVerificationEmail($newEmail, $username, $verificationCode, 'Email Change Verification Code')) {
                    echo json_encode(['success' => true, 'message' => 'Verification code sent to new email']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to send email']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Error sending verification code: ' . $e->getMessage()]);
            }
            break;
            
        case 'change_email':
            try {
                $newEmail = $_POST['newEmail'] ?? '';
                $verificationCode = $_POST['verificationCode'] ?? '';
                $currentUserId = $_SESSION['user_id'] ?? null;
                
                if (!$currentUserId) {
                    echo json_encode(['success' => false, 'error' => 'User not logged in']);
                    break;
                }
                
                // Verify verification code
                if (!isset($_SESSION['email_verification_code']) || !isset($_SESSION['email_verification_expires'])) {
                    echo json_encode(['success' => false, 'error' => 'No verification code found. Please request a new one.']);
                    break;
                }
                
                if (time() > $_SESSION['email_verification_expires']) {
                    echo json_encode(['success' => false, 'error' => 'Verification code has expired. Please request a new one.']);
                    break;
                }
                
                if ($_SESSION['email_verification_code'] !== $verificationCode) {
                    echo json_encode(['success' => false, 'error' => 'Invalid verification code']);
                    break;
                }
                
                if ($_SESSION['pending_email'] !== $newEmail) {
                    echo json_encode(['success' => false, 'error' => 'Email does not match the one verification was sent to']);
                    break;
                }
                
                require_once __DIR__ . "/../config.php";
                $pdo = getDatabaseConnection();
                if (!$pdo) {
                    echo json_encode(['success' => false, 'error' => 'Database connection not available']);
                    break;
                }
                
                // Update email
                $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE user_id = ?");
                $result = $stmt->execute([$newEmail, $currentUserId]);
                
                if ($result) {
                    // Update session
                    $_SESSION['email'] = $newEmail;
                    
                    // Clear verification data
                    unset($_SESSION['email_verification_code']);
                    unset($_SESSION['email_verification_expires']);
                    unset($_SESSION['pending_email']);
                    
                    echo json_encode(['success' => true, 'message' => 'Email changed successfully']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to update email']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
            }
            break;
            
        case 'get_users_table':
            try {
                require_once __DIR__ . "/../config.php";
                $pdo = getDatabaseConnection();
                if (!$pdo) {
                    echo json_encode(['success' => false, 'error' => 'Database connection not available']);
                    break;
                }
                
                $stmt = $pdo->prepare("SELECT user_id, username, email, email_verified, created_at, last_login, is_active FROM users ORDER BY created_at DESC");
                $stmt->execute();
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'users' => $users]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;
            
            
        case 'update_user_field':
            try {
                $user_id = $_POST['user_id'] ?? '';
                $field = $_POST['field'] ?? '';
                $value = $_POST['value'] ?? '';
                
                if (empty($user_id) || empty($field) || !in_array($field, ['username', 'email'])) {
                    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
                    break;
                }
                
                require_once __DIR__ . "/../config.php";
                $pdo = getDatabaseConnection();
                if (!$pdo) {
                    echo json_encode(['success' => false, 'error' => 'Database connection not available']);
                    break;
                }
                
                // Validate email format if field is email
                if ($field === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
                    break;
                }
                
                // Check if username/email already exists (excluding current user)
                $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE $field = ? AND user_id != ?");
                $checkStmt->execute([$value, $user_id]);
                if ($checkStmt->fetch()) {
                    echo json_encode(['success' => false, 'error' => "$field already exists"]);
                    break;
                }
                
                $updateStmt = $pdo->prepare("UPDATE users SET $field = ? WHERE user_id = ?");
                $result = $updateStmt->execute([$value, $user_id]);
                
                if ($result) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to update user']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;
            
       case 'add_user':
           // Debug: Log everything
           error_log("=== ADD USER DEBUG START - VERSION 2.0 ===");
           error_log("POST data: " . print_r($_POST, true));
           error_log("Session data: " . print_r($_SESSION, true));
           
           // Simple admin user creation
           $email = trim($_POST['email'] ?? '');
           $municipality = trim($_POST['municipality'] ?? '');
           
           error_log("Email: '$email', Municipality: '$municipality'");
           
           // Basic validation
           if (empty($email) || empty($municipality)) {
               error_log("Validation failed: empty fields");
               echo json_encode(['success' => false, 'error' => 'Email and municipality are required']);
               exit;
           }
           
           if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
               error_log("Validation failed: invalid email format");
               echo json_encode(['success' => false, 'error' => 'Invalid email format']);
               exit;
           }
           
           error_log("Validation passed, connecting to database...");
           
           // Database connection
           require_once __DIR__ . "/../config.php";
           $pdo = getDatabaseConnection();
           if (!$pdo) {
               error_log("Database connection failed");
               echo json_encode(['success' => false, 'error' => 'Database connection failed']);
               exit;
           }
           
           error_log("Database connection successful");
           
           try {
               error_log("Starting database operations...");
               
               // Check if municipality column exists, create if missing
               $checkColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'municipality'");
               if (!$checkColumn->fetch()) {
                   error_log("Municipality column missing, creating it...");
                   $pdo->exec("ALTER TABLE users ADD COLUMN municipality VARCHAR(100) NULL");
                   error_log("Municipality column created");
               } else {
                   error_log("Municipality column exists");
               }
               
               // Check if email already exists
               error_log("Checking if email exists...");
               $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
               $check->execute([$email]);
               if ($check->fetch()) {
                   error_log("Email already exists");
                   echo json_encode(['success' => false, 'error' => 'Email already exists']);
                   exit;
               }
               error_log("Email is unique");
               
               // Generate username from email
               $username = explode('@', $email)[0];
               $username = preg_replace('/[^a-zA-Z0-9_]/', '', $username);
               error_log("Generated username: $username");
               
               // Make username unique
               $originalUsername = $username;
               $counter = 1;
               while ($counter < 100) {
                   $check = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
                   $check->execute([$username]);
                   if (!$check->fetch()) break;
                   $username = $originalUsername . $counter;
                   $counter++;
               }
               error_log("Final username: $username");
               
               // Create user
               $password = 'mho123';
               $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
               $resetToken = bin2hex(random_bytes(32));
               $resetExpires = date('Y-m-d H:i:s', strtotime('+7 days'));
               
               error_log("About to insert user with data:");
               error_log("Username: $username");
               error_log("Email: $email");
               error_log("Municipality: $municipality");
               error_log("Reset token: $resetToken");
               
               $stmt = $pdo->prepare("INSERT INTO users (username, email, password, municipality, password_reset_code, password_reset_expires, email_verified, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, 1, NOW())");
               $result = $stmt->execute([$username, $email, $hashedPassword, $municipality, $resetToken, $resetExpires]);
               
               error_log("Insert result: " . ($result ? 'SUCCESS' : 'FAILED'));
               
               if ($result) {
                   error_log("User created successfully, sending email...");
                   // Send email
                   $setupLink = "https://" . $_SERVER['HTTP_HOST'] . "/home.php?setup_password=" . $resetToken;
                   $emailSent = sendVerificationEmail($email, $username, $setupLink, 'Admin Account Setup');
                   
                   error_log("Email sent: " . ($emailSent ? 'SUCCESS' : 'FAILED'));
                   error_log("=== ADD USER DEBUG END - SUCCESS ===");
                   
                   echo json_encode([
                       'success' => true, 
                       'message' => 'Admin user created successfully! Password setup email sent.',
                       'email_sent' => $emailSent
                   ]);
               } else {
                   error_log("Insert failed. PDO error: " . print_r($stmt->errorInfo(), true));
                   error_log("=== ADD USER DEBUG END - INSERT FAILED ===");
                   echo json_encode(['success' => false, 'error' => 'Failed to create user']);
               }
               
           } catch (Exception $e) {
               error_log("Exception caught: " . $e->getMessage());
               error_log("Stack trace: " . $e->getTraceAsString());
               error_log("=== ADD USER DEBUG END - EXCEPTION ===");
               echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
           }
           break;
           
       case 'delete_user':
           try {
               $user_id = $_POST['user_id'] ?? '';
               
               if (empty($user_id)) {
                   echo json_encode(['success' => false, 'error' => 'User ID is required']);
                   break;
               }
               
               require_once __DIR__ . "/../config.php";
               $pdo = getDatabaseConnection();
               if (!$pdo) {
                   echo json_encode(['success' => false, 'error' => 'Database connection not available']);
                   break;
               }
               
               $deleteStmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
               $result = $deleteStmt->execute([$user_id]);
               
               if ($result && $deleteStmt->rowCount() > 0) {
                   echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
               } else {
                   echo json_encode(['success' => false, 'error' => 'User not found or could not be deleted']);
               }
           } catch (Exception $e) {
               echo json_encode(['success' => false, 'error' => $e->getMessage()]);
           }
           break;
           
       case 'delete_all_admin_users':
           try {
               $confirm = $_POST['confirm'] ?? false;
               
               if (!$confirm) {
                   echo json_encode(['success' => false, 'error' => 'Confirmation required']);
                   break;
               }
               
               require_once __DIR__ . "/../config.php";
               $pdo = getDatabaseConnection();
               if (!$pdo) {
                   echo json_encode(['success' => false, 'error' => 'Database connection not available']);
                   break;
               }
               
               // Delete all users from the users table (admin users)
               $deleteStmt = $pdo->prepare("DELETE FROM users");
               $result = $deleteStmt->execute();
               
               if ($result) {
                   $deletedCount = $deleteStmt->rowCount();
                   echo json_encode(['success' => true, 'message' => "Successfully deleted {$deletedCount} admin users"]);
               } else {
                   echo json_encode(['success' => false, 'error' => 'Failed to delete admin users']);
               }
           } catch (Exception $e) {
               echo json_encode(['success' => false, 'error' => $e->getMessage()]);
           }
           break;
           
       case 'update_user':
           try {
               $user_id = $_POST['user_id'] ?? '';
               $username = $_POST['username'] ?? '';
               $email = $_POST['email'] ?? '';
               $password = $_POST['password'] ?? '';
               
               if (empty($user_id) || empty($username) || empty($email)) {
                   echo json_encode(['success' => false, 'error' => 'User ID, username, and email are required']);
                   break;
               }
               
               if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                   echo json_encode(['success' => false, 'error' => 'Invalid email format']);
                   break;
               }
               
               require_once __DIR__ . "/../config.php";
               $pdo = getDatabaseConnection();
               if (!$pdo) {
                   echo json_encode(['success' => false, 'error' => 'Database connection not available']);
                   break;
               }
               
               // Check if email already exists for another user
               $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
               $checkStmt->execute([$email, $user_id]);
               if ($checkStmt->fetch()) {
                   echo json_encode(['success' => false, 'error' => 'Email already exists for another user']);
                   break;
               }
               
               // Check if username already exists for another user
               $checkStmt2 = $pdo->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
               $checkStmt2->execute([$username, $user_id]);
               if ($checkStmt2->fetch()) {
                   echo json_encode(['success' => false, 'error' => 'Username already exists for another user']);
                   break;
               }
               
               // Prepare update query
               if (!empty($password)) {
                   // Update with new password
                   if (strlen($password) < 6) {
                       echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters']);
                       break;
                   }
                   $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                   $updateStmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE user_id = ?");
                   $result = $updateStmt->execute([$username, $email, $hashedPassword, $user_id]);
               } else {
                   // Update without password
                   $updateStmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE user_id = ?");
                   $result = $updateStmt->execute([$username, $email, $user_id]);
               }
               
               if ($result) {
                   echo json_encode(['success' => true, 'message' => 'User updated successfully']);
               } else {
                   echo json_encode(['success' => false, 'error' => 'Failed to update user']);
               }
           } catch (Exception $e) {
               echo json_encode(['success' => false, 'error' => $e->getMessage()]);
           }
           break;
           
       case 'archive_user':
           try {
               $user_id = $_POST['user_id'] ?? '';
               $action = $_POST['archive_action'] ?? 'archive'; // 'archive' or 'unarchive'
               
               if (empty($user_id)) {
                   echo json_encode(['success' => false, 'error' => 'User ID is required']);
                   break;
               }
               
               require_once __DIR__ . "/../config.php";
               $pdo = getDatabaseConnection();
               if (!$pdo) {
                   echo json_encode(['success' => false, 'error' => 'Database connection not available']);
                   break;
               }
               
               // Toggle the is_active status
               $newStatus = ($action === 'archive') ? 0 : 1;
               $updateStmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE user_id = ?");
               $result = $updateStmt->execute([$newStatus, $user_id]);
               
               if ($result && $updateStmt->rowCount() > 0) {
                   $message = ($action === 'archive') ? 'User archived successfully' : 'User unarchived successfully';
                   echo json_encode(['success' => true, 'message' => $message]);
               } else {
                   echo json_encode(['success' => false, 'error' => 'User not found or could not be updated']);
               }
           } catch (Exception $e) {
               echo json_encode(['success' => false, 'error' => $e->getMessage()]);
           }
           break;
           
       case 'archive_community_user':
           try {
               $user_email = $_POST['user_email'] ?? '';
               $action = $_POST['archive_action'] ?? 'archive'; // 'archive' or 'unarchive'
               
               if (empty($user_email)) {
                   echo json_encode(['success' => false, 'error' => 'User email is required']);
                   break;
               }
               
               require_once __DIR__ . "/../config.php";
               $pdo = getDatabaseConnection();
               if (!$pdo) {
                   echo json_encode(['success' => false, 'error' => 'Database connection not available']);
                   break;
               }
               
               // Toggle the status for community users (1 = active, 0 = archived)
               $newStatus = ($action === 'archive') ? 0 : 1;
               $updateStmt = $pdo->prepare("UPDATE community_users SET status = ? WHERE email = ?");
               $result = $updateStmt->execute([$newStatus, $user_email]);
               
               if ($result && $updateStmt->rowCount() > 0) {
                   $message = ($action === 'archive') ? 'Community user archived successfully' : 'Community user unarchived successfully';
                   echo json_encode(['success' => true, 'message' => $message]);
               } else {
                   echo json_encode(['success' => false, 'error' => 'Community user not found or could not be updated']);
               }
           } catch (Exception $e) {
               echo json_encode(['success' => false, 'error' => $e->getMessage()]);
           }
           break;
           
       case 'verify_password':
           try {
               $password = $_POST['password'] ?? '';
               
               if (empty($password)) {
                   echo json_encode(['success' => false, 'error' => 'Password is required']);
                   break;
               }
               
               // Check if user is logged in (admin user)
               if (!isset($_SESSION['user_id'])) {
                   echo json_encode(['success' => false, 'error' => 'Admin user not logged in']);
                   break;
               }
               
               require_once __DIR__ . "/../config.php";
               $pdo = getDatabaseConnection();
               if (!$pdo) {
                   echo json_encode(['success' => false, 'error' => 'Database connection not available']);
                   break;
               }
               
               // Get current admin user's password hash from users table
               // Only admin users (from users table) can delete users
               $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
               $result = $stmt->execute([$_SESSION['user_id']]);
               
               if (!$result) {
                   echo json_encode(['success' => false, 'error' => 'Failed to verify admin user']);
                   break;
               }
               
               $user = $stmt->fetch(PDO::FETCH_ASSOC);
               if (!$user) {
                   echo json_encode(['success' => false, 'error' => 'Admin user not found']);
                   break;
               }
               
               // Verify password
               if (password_verify($password, $user['password'])) {
                   echo json_encode(['success' => true, 'message' => 'Admin password verified']);
               } else {
                   echo json_encode(['success' => false, 'error' => 'Incorrect admin password']);
               }
               
           } catch (Exception $e) {
               echo json_encode(['success' => false, 'error' => $e->getMessage()]);
           }
           break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
            break;
    }
    exit;
}

// Get existing screening assessments using DatabaseHelper
$screening_assessments = [];
if ($db->isAvailable()) {
    try {
        $user_email = $_SESSION['user_email'] ?? 'user@example.com';
        $result = $db->select(
            'community_users', 
            '*', 
            'email = ?', 
            [$user_email], 
            'screening_date DESC'
        );
        $screening_assessments = $result['success'] ? $result['data'] : [];
    } catch (Exception $e) {
        error_log("Error fetching screening assessments: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings and Admin</title>
</head>
<style>
/* Dark Theme - Default */
:root {
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
    --color-border: rgba(161, 180, 84, 0.2);
    --color-shadow: rgba(0, 0, 0, 0.1);
    --color-hover: rgba(161, 180, 84, 0.08);
    --color-active: rgba(161, 180, 84, 0.15);
}

/* Light Theme - Light Greenish Colors */
.light-theme {
    --color-bg: #F0F7F0;
    --color-card: #FFFFFF;
    --color-highlight: #66BB6A;
    --color-text: #1B3A1B;
    --color-accent1: #81C784;
    --color-accent2: #4CAF50;
    --color-accent3: #2E7D32;
    --color-accent4: #A5D6A7;
    --color-danger: #E57373;
    --color-warning: #FFB74D;
    --color-border: #C8E6C9;
    --color-shadow: rgba(76, 175, 80, 0.1);
    --color-hover: rgba(76, 175, 80, 0.08);
    --color-active: rgba(76, 175, 80, 0.15);
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
    overflow: hidden;
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
    transform: translateX(2px);
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
    background: linear-gradient(135deg, transparent 0%, rgba(161, 180, 84, 0.03) 100%);
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

/* ===== BODY PADDING FOR NAVBAR ===== */
body {
    padding-left: 40px; /* Space for minimized navbar */
    transition: padding-left 0.4s ease;
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

/* Base body styles */
body {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    min-height: 100vh;
    background-color: var(--color-bg);
    color: var(--color-text);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    padding-left: 320px;
    line-height: 1.6;
    letter-spacing: 0.2px;
}

/* Dashboard container */
.dashboard {
    max-width: calc(100% - 60px);
    width: 100%;
    margin: 0 auto;
    padding: 20px;
}

/* Header styles */
header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    background-color: var(--color-card);
    padding: 15px 25px;
    border-radius: 12px;
    box-shadow: 0 4px 15px var(--color-shadow);
    border: 1px solid var(--color-border);
    transition: all 0.3s ease;
}

/* Dashboard Header Styles */
.dashboard-header {
    /* Removed card styling - no background, padding, border-radius, or box-shadow */
    padding: 0;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.dashboard-header h1 {
    margin: 0;
    color: var(--color-text);
    font-size: 36px;
    font-weight: 700;
    line-height: 1.2;
}

.light-theme .dashboard-header h1 {
    color: #1B3A1B;
    font-size: 36px;
    font-weight: 700;
}

.dashboard-header .user-info {
    display: flex;
    align-items: center;
}

/* User info styles */
.user-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

/* New Theme toggle button - Orange for moon, Black for sun */
.new-theme-toggle-btn {
    background: #FF9800; /* Default orange for moon icon */
    border: none;
    color: #333; /* Dark color for moon icon */
    padding: 10px 15px;
    border-radius: 25px;
    cursor: pointer;
    transition: all 0.3s ease;
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
    color: #FFFFFF;
}

/* Ensure consistent table styling for both users and community users tables */
.user-table th,
.user-table td,
.assessment-table th,
.assessment-table td {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    box-sizing: border-box;
}

/* Force consistent styling for dynamically loaded content */
.user-table tbody tr td {
    font-size: 14px !important;
    line-height: 1.4 !important;
    vertical-align: middle !important;
}

.user-table thead tr th {
    font-size: 14px !important;
    line-height: 1.4 !important;
    vertical-align: middle !important;
}

/* Ensure editable fields maintain consistent styling */
.user-table .editable {
    font-size: inherit !important;
    font-family: inherit !important;
    line-height: inherit !important;
}

/* Verification status icons styling */
.verified-status {
    text-align: center;
    vertical-align: middle;
}

.verified-icon, .not-verified-icon {
    display: inline-block;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    line-height: 20px;
    text-align: center;
    font-weight: bold;
    font-style: normal;
}

.verified-icon {
    background-color: rgba(40, 167, 69, 0.1);
    color: #28a745;
    border: 1px solid rgba(40, 167, 69, 0.3);
}

.not-verified-icon {
    background-color: rgba(220, 53, 69, 0.1);
    color: #dc3545;
    border: 1px solid rgba(220, 53, 69, 0.3);
}

/* Card Deck Fan Component Styles */
.card-deck-container {
    background: var(--color-card);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--color-border);
    width: 100%;
}

.deck-header {
    margin-bottom: 20px;
}

.search-filter-container {
    display: flex;
    flex-direction: column;
    gap: 15px;
    align-items: center;
}

.search-box {
    display: flex;
    align-items: center;
    background: var(--color-card);
    border: 1px solid var(--color-border);
    border-radius: 25px;
    padding: 8px 15px;
    width: 100%;
    max-width: 400px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.search-input {
    flex: 1;
    border: none;
    background: transparent;
    color: var(--color-text);
    font-size: 14px;
    padding: 8px 0;
    outline: none;
}

.search-input::placeholder {
    color: var(--color-text);
    opacity: 0.6;
}

.search-btn {
    background: none;
    border: none;
    color: var(--color-highlight);
    font-size: 16px;
    cursor: pointer;
    padding: 5px;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.search-btn:hover {
    background: rgba(161, 180, 84, 0.1);
}

.filter-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: center;
}

.filter-btn {
    background: var(--color-card);
    border: 1px solid var(--color-border);
    color: var(--color-text);
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.filter-btn:hover {
    background: rgba(161, 180, 84, 0.1);
    border-color: var(--color-highlight);
}

.filter-btn.active {
    background: var(--color-highlight);
    color: white;
    border-color: var(--color-highlight);
}

/* New Control Grid Layout */
.control-grid {
    background: linear-gradient(135deg, var(--color-card) 0%, rgba(161, 180, 84, 0.1) 100%);
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 15px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    border: 2px solid rgba(161, 180, 84, 0.3);
    display: grid;
    grid-template-rows: auto auto;
    gap: 15px;
}

/* Row 1: Action Buttons and Search */
.control-row-1 {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.action-section {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

.btn-add, .btn-secondary, .btn-delete-all {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    white-space: nowrap;
}

.btn-add {
    background: linear-gradient(135deg, var(--color-highlight) 0%, rgba(161, 180, 84, 0.8) 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(161, 180, 84, 0.3);
}

.btn-add:hover {
    background: linear-gradient(135deg, rgba(161, 180, 84, 0.9) 0%, var(--color-highlight) 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(161, 180, 84, 0.4);
}

.btn-secondary {
    background: linear-gradient(135deg, var(--color-accent1) 0%, rgba(161, 180, 84, 0.8) 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(161, 180, 84, 0.3);
}

.btn-secondary:hover {
    background: linear-gradient(135deg, rgba(161, 180, 84, 0.9) 0%, var(--color-accent1) 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(161, 180, 84, 0.4);
}

.btn-delete-all {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(231, 76, 60, 0.3);
}

.btn-delete-all:hover {
    background: linear-gradient(135deg, #c0392b 0%, #e74c3c 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(231, 76, 60, 0.4);
}

.btn-delete-location {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(231, 76, 60, 0.3);
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-delete-location:hover {
    background: linear-gradient(135deg, #c0392b 0%, #e74c3c 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(231, 76, 60, 0.4);
}

.btn-icon {
    font-size: 16px;
}

.btn-text {
    font-weight: 500;
}

.warning-box {
    background: #fee;
    border: 1px solid #fcc;
    border-radius: 8px;
    padding: 15px;
    margin: 15px 0;
    color: #c33;
}

[data-theme="dark"] .warning-box {
    background: #2d1b1b;
    border-color: #4a2c2c;
    color: #ff6b6b;
}

.btn-danger {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 12px 24px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-danger:hover {
    background: linear-gradient(135deg, #c0392b, #e74c3c);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(231, 76, 60, 0.4);
}

/* Search container styles applied to div containing search input */
.control-row-1 > div:last-child {
    display: flex;
    align-items: center;
    background: var(--color-card);
    border: 1px solid var(--color-border);
    border-radius: 8px;
    padding: 4px;
    min-width: 300px;
    max-width: 400px;
}

.search-input {
    flex: 1;
    border: none;
    background: transparent;
    color: var(--color-text);
    padding: 8px 12px;
    font-size: 14px;
    outline: none;
}

.search-input::placeholder {
    color: var(--color-text-secondary);
}

.search-btn {
    background: var(--color-highlight);
    border: none;
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 16px;
}

.search-btn:hover {
    background: rgba(161, 180, 84, 0.8);
    transform: scale(1.05);
}

/* Row 2: Filter Controls */
.control-row-2 {
    background: linear-gradient(135deg, rgba(161, 180, 84, 0.15) 0%, rgba(161, 180, 84, 0.05) 100%);
    border-radius: 8px;
    padding: 12px;
    border: 1px solid rgba(161, 180, 84, 0.2);
}

.filter-section {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    align-items: end;
}

.filter-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.filter-item label {
    font-size: 11px;
    font-weight: 600;
    color: var(--color-text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 2px;
}

.filter-item select, .filter-item input {
    padding: 8px 12px;
    border: 1px solid var(--color-border);
    border-radius: 6px;
    background: var(--color-card);
    color: var(--color-text);
    font-size: 13px;
    transition: all 0.3s ease;
    outline: none;
}

.filter-item select:focus, .filter-item input:focus {
    border-color: var(--color-highlight);
    box-shadow: 0 0 0 2px rgba(161, 180, 84, 0.2);
}

/* Date input group styling */
.date-input-group {
    display: flex;
    align-items: center;
    gap: 8px;
}

.date-input {
    flex: 1;
    padding: 8px 12px;
    border: 1px solid var(--color-border);
    border-radius: 6px;
    background: var(--color-bg);
    color: var(--color-text);
    font-size: 14px;
    transition: border-color 0.3s ease;
}

.date-input:focus {
    outline: none;
    border-color: var(--color-highlight);
    box-shadow: 0 0 0 2px rgba(161, 180, 84, 0.2);
}

.date-separator {
    color: var(--color-text-secondary);
    font-weight: 500;
    font-size: 14px;
    white-space: nowrap;
}

/* Light theme adjustments */
.light-theme .control-grid {
    background: linear-gradient(135deg, var(--color-card) 0%, rgba(102, 187, 106, 0.1) 100%);
    border-color: rgba(102, 187, 106, 0.3);
}

.light-theme .control-row-2 {
    background: linear-gradient(135deg, rgba(102, 187, 106, 0.15) 0%, rgba(102, 187, 106, 0.05) 100%);
    border-color: rgba(102, 187, 106, 0.2);
}

.light-theme .filter-item label {
    color: var(--color-text);
}

/* Responsive Design */
@media (max-width: 1200px) {
    .filter-section {
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
    }
}

@media (max-width: 768px) {
    .control-grid {
        gap: 12px;
    }

    .control-row-1 {
        flex-direction: column;
        gap: 12px;
    }

    .control-row-2 {
        padding: 10px;
    }

    .filter-section {
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }

    .action-section {
        justify-content: center;
        flex-wrap: wrap;
    }

    .btn-add, .btn-secondary, .btn-delete-all {
        flex: 0 0 auto;
        min-width: 120px;
    }

    .control-row-1 > div:last-child {
        min-width: 250px;
        max-width: 100%;
    }
}

@media (max-width: 480px) {
    .control-grid {
        gap: 10px;
        padding: 12px;
    }

    .control-row-2 {
        padding: 8px;
    }

    .filter-section {
        grid-template-columns: repeat(2, 1fr);
        gap: 6px;
    }

    .action-section {
        flex-direction: column;
        gap: 8px;
    }

    .btn-add, .btn-secondary, .btn-delete-all {
        width: 100%;
        justify-content: center;
    }

    .control-row-1 > div:last-child {
        min-width: 200px;
    }
}

.deck-card.hidden {
    display: none !important;
}

.deck-card {
    transition: all 0.3s ease;
    opacity: 1;
}

.deck-card.hidden {
    opacity: 0;
    transform: scale(0.95);
    pointer-events: none;
}

.no-results-message {
    grid-column: 1 / -1;
    text-align: center;
    padding: 40px;
    color: var(--color-text);
    font-style: italic;
    background: var(--color-card);
    border-radius: 12px;
    border: 1px dashed var(--color-border);
}

.deck-wrapper {
    position: relative;
    overflow: hidden;
}

.deck-container {
    position: relative;
    height: 400px;
    border-radius: 24px;
    border: 1px solid var(--color-border);
    background: linear-gradient(135deg, var(--color-card) 0%, rgba(161, 180, 84, 0.05) 100%);
    backdrop-filter: blur(10px);
    overflow: hidden;
    width: 100%;
    margin-bottom: 12px;
}

.deck-cards {
    display: flex;
    gap: 15px;
    padding: 24px;
    height: 100%;
    overflow-x: auto;
    overflow-y: hidden;
    scrollbar-width: none;
    -ms-overflow-style: none;
    scroll-behavior: smooth;
    max-width: 100%;
    align-items: center;
}

.deck-header-section {
    padding: 12px 24px 8px 24px;
    border-bottom: 1px solid var(--color-border);
}

.section-title {
    color: var(--color-highlight);
    font-size: 16px;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.deck-cards::-webkit-scrollbar {
    display: none;
}

/* Responsive design for card deck */
@media (max-width: 1400px) {
    .screening-container {
        max-width: 1200px;
        padding: 25px;
    }
    
    .deck-cards {
        gap: 12px;
        padding: 20px;
    }
    
    .deck-card {
        width: 200px;
        height: 280px;
        min-width: 200px;
    }
}

@media (max-width: 1200px) {
    .screening-container {
        max-width: 1000px;
        padding: 20px;
    }
    
    .deck-cards {
        gap: 10px;
        padding: 18px;
    }
    
    .deck-card {
        height: 260px;
    }
    
    .deck-container {
        height: 350px;
    }
}

@media (max-width: 768px) {
    .screening-container {
        max-width: 100%;
        padding: 15px;
    }
    
    .deck-cards {
        gap: 8px;
        padding: 15px;
    }
    
    .deck-card {
        height: 240px;
    }
    
    .deck-container {
        height: 300px;
    }
}

@media (max-width: 480px) {
    .screening-container {
        padding: 10px;
    }
    
    .deck-cards {
        gap: 6px;
        padding: 12px;
    }
    
    .deck-card {
        height: 200px;
    }
    
    .deck-container {
        height: 250px;
    }
}

.deck-card {
    position: relative;
    width: 220px;
    height: 320px;
    min-width: 220px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    transform: translateX(0px) translateY(0px) scale(1);
    flex-shrink: 0;
}

.deck-card:hover {
    transform: translateY(-10px);
}

.card-main {
    width: 100%;
    height: 100%;
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    background: linear-gradient(135deg, var(--color-card) 0%, rgba(161, 180, 84, 0.1) 100%);
    backdrop-filter: blur(10px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    padding: 16px;
    box-sizing: border-box;
    transition: all 0.3s ease;
}

.card-main:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.2);
    border-color: var(--color-highlight);
}
    padding: 20px;
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
    position: relative;
    z-index: 10;
    overflow: hidden;
}



.card-header {
    text-align: center;
    margin-bottom: 12px;
}

.card-header h4 {
    color: var(--color-highlight);
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 6px;
    line-height: 1.2;
}

.card-location {
    color: var(--color-text);
    font-size: 12px;
    opacity: 0.7;
    line-height: 1.2;
}

.card-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
    overflow: hidden;
}

.card-stat {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
    border-bottom: 1px solid var(--color-border);
}

.card-stat:last-child {
    border-bottom: none;
}

.stat-label {
    color: var(--color-text);
    font-size: 12px;
    font-weight: 500;
    opacity: 0.8;
}

.stat-value {
    color: var(--color-highlight);
    font-size: 13px;
    font-weight: 600;
}

.bmi-normal {
    color: #4CAF50 !important;
    background: rgba(76, 175, 80, 0.1);
    padding: 1px 4px;
    border-radius: 6px;
    font-weight: 600;
}

.bmi-overweight {
    color: #FF9800 !important;
    background: rgba(255, 152, 0, 0.1);
    padding: 1px 4px;
    border-radius: 6px;
    font-weight: 600;
}

.bmi-underweight {
    color: #F44336 !important;
    background: rgba(244, 67, 54, 0.1);
    padding: 1px 4px;
    border-radius: 6px;
    font-weight: 600;
}

.bmi-obese {
    color: #D32F2F !important;
    background: rgba(211, 47, 47, 0.1);
    padding: 1px 4px;
    border-radius: 6px;
    font-weight: 600;
}

.diet-balanced {
    color: #4CAF50 !important;
}

.diet-at-risk {
    color: #FF9800 !important;
}

.lifestyle-active {
    color: #4CAF50 !important;
}

.lifestyle-sedentary {
    color: #FF9800 !important;
}

.risk-low-risk {
    color: #4CAF50 !important;
    background: rgba(76, 175, 80, 0.1);
    padding: 2px 6px;
    border-radius: 8px;
    font-weight: 700;
}

.risk-medium-risk {
    color: #FF9800 !important;
    background: rgba(255, 152, 0, 0.1);
    padding: 2px 6px;
    border-radius: 8px;
    font-weight: 700;
}

.risk-high-risk {
    color: #F44336 !important;
    background: rgba(244, 67, 54, 0.1);
    padding: 2px 6px;
    border-radius: 8px;
    font-weight: 700;
}

.pregnancy-yes {
    color: #E91E63 !important;
    background: rgba(233, 30, 99, 0.1);
    padding: 2px 6px;
    border-radius: 8px;
    font-weight: 700;
}

.pregnancy-no {
    color: #4CAF50 !important;
    background: rgba(76, 175, 80, 0.1);
    padding: 2px 6px;
    border-radius: 8px;
    font-weight: 700;
}



/* Light theme adjustments */
.light-theme .deck-container {
    background: linear-gradient(135deg, var(--color-card) 0%, rgba(102, 187, 106, 0.05) 100%);
}

.light-theme .card-main {
    background: linear-gradient(135deg, var(--color-card) 0%, rgba(102, 187, 106, 0.1) 100%);
}

.light-theme .fan-card {
    background: linear-gradient(135deg, var(--color-card) 0%, rgba(102, 187, 106, 0.15) 100%);
    box-shadow: 0 12px 35px rgba(102, 187, 106, 0.2);
}

.light-theme .fan-label {
    background: linear-gradient(135deg, var(--color-highlight) 0%, var(--color-accent1) 100%);
    box-shadow: 0 4px 12px rgba(102, 187, 106, 0.3);
}

.light-theme .fan-label:hover {
    box-shadow: 0 6px 16px rgba(102, 187, 106, 0.4);
}
        .screening-container {
    width: 100%;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .screening-form {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .form-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: var(--bg-color);
        }

        .section-title {
            font-size: 1.5em;
            font-weight: bold;
            margin-bottom: 20px;
            color: var(--text-color);
            border-bottom: 2px solid var(--accent-color);
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-color);
        }

        .form-input, .form-select {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--input-bg);
            color: var(--text-color);
    font-size: 16px;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(var(--accent-color-rgb), 0.1);
        }

        .radio-group {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .radio-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .radio-item input[type="radio"] {
            width: 18px;
            height: 18px;
        }

        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }

        .age-inputs {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .age-inputs .form-group {
            flex: 1;
        }

        .bmi-display {
            background: var(--accent-color);
    color: white;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            text-align: center;
            font-weight: bold;
        }

        .submit-btn {
            background: var(--accent-color);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            background: var(--accent-color-dark);
            transform: translateY(-2px);
        }

        .screening-history {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .history-table th,
        .history-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .history-table th {
            background: var(--accent-color);
            color: white;
            font-weight: bold;
        }

        .history-table tr:hover {
            background: var(--hover-bg);
        }

        .alert {
        padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .conditional-field {
            display: none;
        }

        .conditional-field.show {
            display: block;
        }

        .error-message {
            color: #dc3545;
            font-size: 14px;
            margin-top: 5px;
        }

        .success-message {
            color: #28a745;
            font-size: 14px;
            margin-top: 5px;
        }

        /* Assessment Results Styles */
        .assessment-results {
            margin-bottom: 30px;
        }

        .results-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .results-header h2 {
            font-size: 2.5em;
            margin-bottom: 10px;
            color: var(--text-color);
        }

        .results-header p {
            font-size: 1.1em;
            color: var(--text-color-secondary);
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s ease;
        }

        .summary-card:hover {
            transform: translateY(-5px);
        }

        .card-icon {
            font-size: 3em;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--accent-color);
            border-radius: 50%;
            color: white;
        }

        .card-content h3 {
            font-size: 1.1em;
            margin-bottom: 8px;
            color: var(--text-color);
        }

        .card-value {
            font-size: 2.5em;
            font-weight: bold;
            color: var(--accent-color);
        }

        .assessment-table-container {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .table-header h3 {
            font-size: 1.5em;
            color: var(--text-color);
            margin: 0;
        }

        .table-controls {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .search-input {
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--input-bg);
            color: var(--text-color);
            min-width: 200px;
        }

        .filter-select {
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--input-bg);
            color: var(--text-color);
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
        }

        .no-data-icon {
            font-size: 4em;
            margin-bottom: 20px;
        }

        .no-data h3 {
            font-size: 1.8em;
            margin-bottom: 10px;
            color: var(--text-color);
        }

        .no-data p {
            font-size: 1.1em;
            color: var(--text-color-secondary);
            margin-bottom: 30px;
        }

        .mobile-app-info {
            background: var(--bg-color);
            border-radius: 10px;
            padding: 25px;
            max-width: 600px;
            margin: 0 auto;
        }

        .mobile-app-info h4 {
            font-size: 1.3em;
            margin-bottom: 15px;
            color: var(--text-color);
        }

        .mobile-app-info ul {
            list-style: none;
            padding: 0;
        }

        .mobile-app-info li {
            padding: 8px 0;
            color: var(--text-color);
            position: relative;
            padding-left: 25px;
        }

        .mobile-app-info li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: var(--accent-color);
            font-weight: bold;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .assessment-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .assessment-table th,
        .assessment-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .assessment-table th {
            background: var(--accent-color);
            color: white;
            font-weight: bold;
            position: sticky;
            top: 0;
        }

        .assessment-table tr:hover {
            background: var(--hover-bg);
        }

        .bmi-value {
            font-weight: bold;
            color: var(--text-color);
        }

        .bmi-category {
    display: block;
            font-size: 0.9em;
            color: var(--text-color-secondary);
            margin-top: 5px;
        }

        .risk-score {
            font-weight: bold;
            font-size: 1.1em;
        }

        .risk-level {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: bold;
            text-transform: uppercase;
        }

        .risk-level.low {
            background: #d4edda;
            color: #155724;
        }

        .risk-level.medium {
            background: #fff3cd;
            color: #856404;
        }

        .risk-level.high {
            background: #f8d7da;
            color: #721c24;
        }

        .btn-view {
            background: var(--accent-color);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background 0.3s ease;
        }

        .btn-view:hover {
            background: var(--accent-color-dark);
        }

        /* Assessment Details Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background: var(--card-bg);
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
    display: flex;
            justify-content: space-between;
    align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 15px;
        }

        .modal-header h3 {
            margin: 0;
            color: var(--text-color);
        }

        /* Edit User Modal Styles */
        .modal-body {
            padding: 30px 0;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-color);
            font-size: 14px;
            letter-spacing: 0.5px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--color-border);
            border-radius: 8px;
            font-size: 14px;
            background-color: var(--color-card);
            color: var(--text-color);
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--color-highlight);
            box-shadow: 0 0 0 3px rgba(161, 180, 84, 0.15);
            transform: translateY(-1px);
        }

        .form-group input[readonly] {
            background-color: #f8f9fa;
            color: #6c757d;
            cursor: not-allowed;
        }

        .form-group small {
            display: block;
            margin-top: 6px;
            font-size: 12px;
            color: #6c757d;
        }

        .form-group small.error {
            color: #dc3545;
        }

        /* Form grid layout for better organization */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .form-row .form-group {
            margin-bottom: 0;
        }

        /* Enhanced modal styling */
        .modal-content {
            background: var(--card-bg);
            margin: 3% auto;
            padding: 0;
            border-radius: 16px;
            width: 90%;
            max-width: 900px;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            border: 1px solid var(--color-border);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--color-highlight), #8FAF5A);
            color: white;
            padding: 25px 30px;
            margin: 0;
            border-bottom: none;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }

        .modal-header .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: opacity 0.3s ease;
        }

        .modal-header .close:hover {
            opacity: 0.8;
        }

        .modal-body {
            padding: 30px;
            background: var(--color-card);
        }

        .modal-footer {
            display: flex;
            gap: 15px;
            padding: 25px 30px;
            border-top: 1px solid var(--color-border);
            background: var(--color-card);
            position: sticky;
            bottom: 0;
        }

        .btn {
            flex: 1;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--color-highlight), #8FAF5A);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #8FAF5A, var(--color-highlight));
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(161, 180, 84, 0.3);
        }

        /* Ensure edit modal footer is visible */
        #editUserModal .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 20px 0 0 0;
            border-top: 1px solid var(--color-border);
            margin-top: 20px;
            position: relative;
            background-color: var(--color-card);
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--color-highlight);
            color: white;
        }

        .btn-primary:hover {
            background-color: #8a9a5c;
        }

        .btn-primary:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }

        .close:hover {
            color: var(--text-color);
        }

        /* Additional modal centering and visibility fixes */
        #editUserModal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            overflow-y: auto;
        }

        #editUserModal .modal-content {
            position: fixed !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            z-index: 1001 !important;
            margin: 0 !important;
            max-height: 90vh !important;
            overflow-y: auto !important;
        }

        #editUserModal .modal-footer {
            position: relative !important;
            bottom: auto !important;
            margin-top: 20px !important;
            padding: 20px 0 0 0 !important;
            border-top: 1px solid var(--color-border) !important;
            background-color: var(--color-card) !important;
        }

        #editUserModal .btn-primary {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        .close {
            color: var(--text-color);
            font-size: 28px;
    font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: var(--accent-color);
        }

        .assessment-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .detail-section {
            background: var(--bg-color);
            padding: 20px;
    border-radius: 10px;
        }

        .detail-section h4 {
            margin-bottom: 15px;
            color: var(--text-color);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 8px;
        }

        .detail-item {
            margin-bottom: 10px;
        }

        .detail-label {
            font-weight: bold;
            color: var(--text-color);
        }

        .detail-value {
            color: var(--text-color-secondary);
            margin-left: 10px;
        }

        /* MHO Description Styles */
        .mho-description {
            margin-bottom: 30px;
        }

        .description-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border-left: 4px solid var(--accent-color);
        }

        .description-card h3 {
            color: var(--accent-color);
            font-size: 1.5em;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .description-card p {
            font-size: 1.1em;
            line-height: 1.6;
            margin-bottom: 25px;
            color: var(--text-color);
        }

        .assessment-features, .assessment-process {
            margin-bottom: 25px;
        }

        .assessment-features h4, .assessment-process h4 {
            color: var(--text-color);
            font-size: 1.2em;
            margin-bottom: 15px;
            border-bottom: 2px solid var(--accent-color);
            padding-bottom: 8px;
        }

        .assessment-features ul, .assessment-process ol {
            padding-left: 20px;
        }

        .assessment-features li, .assessment-process li {
            margin-bottom: 10px;
            line-height: 1.5;
            color: var(--text-color);
        }

        .assessment-features strong {
            color: var(--accent-color);
        }

        .assessment-process ol {
            counter-reset: step-counter;
        }

        .assessment-process li {
            counter-increment: step-counter;
            position: relative;
            padding-left: 30px;
        }

        .assessment-process li::before {
            content: counter(step-counter);
            position: absolute;
            left: 0;
            top: 0;
            background: var(--accent-color);
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8em;
            font-weight: bold;
        }

        /* User Management Styles - Same as settings.php */
        .user-management-container {
            background-color: var(--color-card);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
            margin-bottom: 30px;
            border: 1px solid var(--color-border);
            position: relative;
            overflow: hidden;
            width: 100%;
            max-width: calc(100% - 60px);
            margin-left: 0;
            margin-right: 0;
        }

        /* Dark theme specific styles */
        .dark-theme .user-management-container {
            background-color: var(--color-card);
            border: 1px solid var(--color-border);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }

        .light-theme .user-management-container {
            background: var(--color-card);
            border: 1px solid var(--color-border);
            box-shadow: 0 6px 20px var(--color-shadow);
        }

.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    background-color: var(--color-card);
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--color-border);
    position: relative;
    z-index: 1;
}

/* Dark theme table header styles */
.dark-theme .table-header {
    background-color: var(--color-card);
    border-color: var(--color-border);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

/* Light theme table header styles */
.light-theme .table-header {
    background-color: var(--color-card);
    border-color: var(--color-border);
    box-shadow: 0 4px 15px var(--color-shadow);
}

.table-header h2 {
    color: var(--color-highlight);
    font-size: 24px;
    margin: 0;
    font-weight: 500;
    letter-spacing: 0.5px;
}

.header-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

        .header-controls {
            display: flex;
            flex-direction: column;
            gap: 20px;
            width: 100%;
        }

        .search-row {
            display: flex;
            gap: 15px;
            align-items: center;
            width: 100%;
            flex-wrap: wrap;
        }

        .action-row {
            display: flex;
            gap: 12px;
            align-items: center;
            width: 100%;
            flex-wrap: wrap;
            justify-content: center;
        }

.btn-add {
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

.btn-secondary:hover {
    background-color: var(--color-accent2);
    transform: translateY(-1px);
}

.action-buttons {
    display: flex;
    gap: 8px;
    justify-content: center;
    flex-wrap: wrap;
    align-items: center;
}

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

.btn-edit, .btn-suspend, .btn-delete {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 12px;
    font-weight: 600;
            margin: 0 4px;
    transition: all 0.3s ease;
    cursor: pointer !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    border: none;
            min-width: 60px;
            max-width: 80px;
    display: inline-block !important;
    text-align: center;
    line-height: 1.2;
    position: relative;
    z-index: 10;
}

.btn-edit {
    background-color: rgba(161, 180, 84, 0.15);
    color: var(--color-highlight);
    border: 2px solid rgba(161, 180, 84, 0.4);
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(161, 180, 84, 0.2);
}

.btn-suspend {
    background-color: rgba(224, 201, 137, 0.15);
    color: var(--color-warning);
    border: 2px solid rgba(224, 201, 137, 0.4);
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(224, 201, 137, 0.2);
}

.btn-delete {
    background-color: rgba(207, 134, 134, 0.15);
    color: var(--color-danger);
    border: 2px solid rgba(207, 134, 134, 0.4);
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(207, 134, 134, 0.2);
}

.light-theme .btn-edit {
    background-color: rgba(102, 187, 106, 0.15);
    color: var(--color-highlight);
    border: 2px solid rgba(102, 187, 106, 0.4);
    font-weight: 600;
}

.light-theme .btn-suspend {
    background-color: rgba(255, 183, 77, 0.15);
    color: var(--color-warning);
    border: 2px solid rgba(255, 183, 77, 0.4);
    font-weight: 600;
}

.light-theme .btn-delete {
    background-color: rgba(229, 115, 115, 0.15);
    color: var(--color-danger);
    border: 2px solid rgba(229, 115, 115, 0.4);
    font-weight: 600;
}

.btn-edit:hover, .btn-suspend:hover, .btn-delete:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    filter: brightness(1.1);
    transform: translateY(-2px) scale(1.05);
}

.btn-edit:active, .btn-suspend:active, .btn-delete:active {
    transform: translateY(0) scale(0.98);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.btn-edit:focus, .btn-suspend:focus, .btn-delete:focus {
    outline: 2px solid var(--color-highlight);
    outline-offset: 2px;
    transform: translateY(-1px);
}

/* Ensure buttons look clickable */
.btn-edit, .btn-suspend, .btn-delete {
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    position: relative;
    overflow: hidden;
    pointer-events: auto !important;
    touch-action: manipulation;
}

/* Add a subtle background pattern to make buttons more visible */
.btn-edit::before, .btn-suspend::before, .btn-delete::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    transition: left 0.5s ease;
    pointer-events: none;
}

.btn-edit:hover::before, .btn-suspend:hover::before, .btn-delete:hover::before {
    left: 100%;
}

/* Ensure buttons are always clickable */
.btn-edit, .btn-suspend, .btn-delete, .btn-archive, .btn-unarchive {
    pointer-events: auto !important;
    cursor: pointer !important;
    position: relative;
    z-index: 100;
    /* Make buttons clearly look clickable */
    background-image: linear-gradient(145deg, rgba(255,255,255,0.1), transparent);
    border-style: solid;
    border-width: 2px;
    text-decoration: none;
    /* Prevent text selection */
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
}

.btn-add:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.btn-edit {
    background-color: var(--color-highlight);
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    border: none;
    font-size: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-right: 4px;
}

.btn-delete {
    background-color: #e74c3c;
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    border: none;
    font-size: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.btn-edit:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(161, 180, 84, 0.3);
    background-color: var(--color-primary);
}

.btn-delete:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
    background-color: #c0392b;
}

        .table-responsive {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid var(--color-border);
            width: 100%;
            max-width: 100%;
        }

        .user-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 10px;
            table-layout: auto;
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid var(--color-border);
            box-shadow: 0 4px 20px var(--color-shadow);
            min-width: 100%;
        }

        /* Auto-fit column distribution - let table adjust automatically */
        .user-table th,
        .user-table td {
            width: auto;
            min-width: 80px;
        }
        
        /* Specific minimum widths for different columns */
        .user-table th:nth-child(1),
        .user-table td:nth-child(1) {
            min-width: 120px; /* NAME */
        }
        
        .user-table th:nth-child(2),
        .user-table td:nth-child(2) {
            min-width: 150px; /* EMAIL */
        }
        
        .user-table th:nth-child(3),
        .user-table td:nth-child(3) {
            min-width: 120px; /* MUNICIPALITY */
        }
        
        .user-table th:nth-child(4),
        .user-table td:nth-child(4) {
            min-width: 100px; /* BARANGAY */
        }
        
        .user-table th:nth-child(5),
        .user-table td:nth-child(5) {
            min-width: 60px; /* SEX */
        }
        
        .user-table th:nth-child(6),
        .user-table td:nth-child(6) {
            min-width: 100px; /* BIRTHDAY */
        }
        
        .user-table th:nth-child(7),
        .user-table td:nth-child(7) {
            min-width: 280px; /* ACTIONS - enough for three buttons (Edit, Archive/Unarchive, Delete) */
        }

        .user-table thead { 
    background-color: var(--color-card);
        }

        .user-table tbody tr:nth-child(odd) {
            background-color: rgba(84, 96, 72, 0.3);
        }

        .user-table tbody tr:nth-child(even) {
            background-color: rgba(84, 96, 72, 0.1);
        }

        /* Dark theme table styles */
        .dark-theme .user-table {
    border: 1px solid var(--color-border);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
}

        .dark-theme .user-table thead {
    background-color: var(--color-card);
        }

        .dark-theme .user-table tbody tr:nth-child(odd) {
            background-color: rgba(161, 180, 84, 0.1);
        }

        .dark-theme .user-table tbody tr:nth-child(even) {
            background-color: rgba(161, 180, 84, 0.05);
        }

        /* Light theme table styles */
        .light-theme .user-table {
    border: 1px solid var(--color-border);
            box-shadow: 0 4px 20px var(--color-shadow);
        }

        .light-theme .user-table thead {
            background-color: var(--color-card);
        }

        .light-theme .user-table tbody tr:nth-child(odd) {
    background-color: rgba(102, 187, 106, 0.1);
        }

        .light-theme .user-table tbody tr:nth-child(even) {
            background-color: rgba(102, 187, 106, 0.05);
        }

        .user-table tbody tr {
    transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .user-table tbody tr:hover {
            border-left-color: var(--color-highlight);
            background-color: rgba(161, 180, 84, 0.2);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(161, 180, 84, 0.15);
        }

        /* Professional row styling */
        .user-table tbody tr {
            transition: all 0.2s ease;
        }

        .user-table tbody tr:hover td {
            color: var(--color-text);
        }

        /* Enhanced cell hover effects */
        .user-table td:hover {
            background-color: rgba(161, 180, 84, 0.05);
        }

        /* Professional table borders */
        .user-table th:not(:last-child),
        .user-table td:not(:last-child) {
            border-right: 1px solid rgba(161, 180, 84, 0.1);
        }

        .user-table th,
        .user-table td {
            padding: 12px 8px;
            text-align: left;
            border-bottom: 1px solid rgba(161, 180, 84, 0.2);
            font-size: 14px;
            font-weight: 500;
            vertical-align: middle;
        }

        /* Specific styling for screening date column to reduce width */
        .user-table th:nth-child(7),
        .user-table td:nth-child(7) {
            width: 120px;
            max-width: 120px;
            min-width: 100px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Profile Card Styles - Match user-management-container exactly */
        .profile-card {
            background-color: var(--color-card);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
            margin-bottom: 30px;
            border: 1px solid var(--color-border);
            position: relative;
            overflow: hidden;
            width: 100%;
            max-width: calc(100% - 60px);
            margin-left: 0;
            margin-right: 0;
        }

        /* Dark theme specific styles for profile card */
        .dark-theme .profile-card {
            background-color: var(--color-card);
            border: 1px solid var(--color-border);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }

        /* Light theme specific styles for profile card */
        .light-theme .profile-card {
            background: var(--color-card);
            border: 1px solid var(--color-border);
            box-shadow: 0 6px 20px var(--color-shadow);
        }

        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--color-border);
        }

        .profile-header h3 {
            color: var(--color-highlight);
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        }

        .btn-edit-profile {
            background: var(--color-highlight);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-edit-profile:hover {
            background: #66BB6A;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(161, 180, 84, 0.3);
        }

        .profile-content {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .profile-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .profile-field {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .profile-field label {
            font-weight: 600;
            color: var(--color-text-secondary);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .profile-field span {
            color: var(--color-text);
            font-size: 16px;
            padding: 8px 12px;
            background: var(--color-bg);
            border-radius: 6px;
            border: 1px solid var(--color-border);
        }

        .profile-edit {
            background: var(--color-bg);
            border-radius: 10px;
            padding: 20px;
            border: 1px solid var(--color-border);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--color-text);
            margin-bottom: 5px;
        }

        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid var(--color-border);
            border-radius: 6px;
            background: var(--color-card);
            color: var(--color-text);
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--color-highlight);
            box-shadow: 0 0 0 3px rgba(161, 180, 84, 0.2);
        }

        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .btn-cancel, .btn-save {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-cancel {
            background: var(--color-border);
            color: var(--color-text);
        }

        .btn-cancel:hover {
            background: #e0e0e0;
        }

        .btn-save {
            background: var(--color-highlight);
            color: white;
        }

        .btn-save:hover {
            background: #66BB6A;
            transform: translateY(-2px);
        }

        .profile-actions {
            display: flex;
            justify-content: center;
            padding-top: 15px;
            border-top: 1px solid var(--color-border);
        }

        .btn-change-password {
            background: #FF9800;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-change-password:hover {
            background: #F57C00;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
        }

        .btn-send-code {
            background: var(--color-highlight);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 8px 16px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .btn-send-code:hover {
            background: #66BB6A;
            transform: translateY(-1px);
        }

        .btn-change-email {
            background: #2196F3;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 8px 16px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .btn-change-email:hover {
            background: #1976D2;
            transform: translateY(-1px);
        }

        .btn-forgot-password {
            background: #FF5722;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 8px 16px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .btn-forgot-password:hover {
            background: #E64A19;
            transform: translateY(-1px);
        }
            vertical-align: middle;
            position: relative;
            line-height: 1.4;
            overflow: hidden;
            text-overflow: ellipsis;
            font-family: inherit;
        }

        /* Specific text handling for different columns */
        .user-table td:nth-child(1),
        .user-table td:nth-child(2) {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-table td:nth-child(3),
        .user-table td:nth-child(4) {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-table td:nth-child(5) {
            text-align: center;
            white-space: nowrap;
        }

        .user-table td:nth-child(6) {
            white-space: nowrap;
            text-align: center;
        }

        /* Ensure actions column is always visible */
        .user-table th:last-child,
        .user-table td:last-child {
            white-space: nowrap;
            overflow: visible;
            text-overflow: clip;
            text-align: center;
            padding: 8px 6px;
            box-sizing: border-box;
            min-width: 280px;
            vertical-align: middle;
        }



        /* Responsive table wrapper */
        .table-responsive {
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow-x: auto;
            overflow-y: visible;
            width: 100%;
            max-width: 100%;
        }

        /* Responsive improvements */
        @media (max-width: 1200px) {
            .user-table th,
            .user-table td {
                padding: 10px 6px;
                font-size: 13px;
            }
            
            .action-buttons .btn-edit,
            .action-buttons .btn-delete,
            .action-buttons .btn-archive,
            .action-buttons .btn-unarchive {
                padding: 5px 10px;
                font-size: 10px;
                min-width: 45px;
                height: 26px;
            }
        }

        @media (max-width: 768px) {
            .user-table th,
            .user-table td {
                padding: 8px 4px;
                font-size: 12px;
            }
            
            .action-buttons {
                gap: 3px;
                flex-direction: column;
            }
            
            .action-buttons .btn-edit,
            .action-buttons .btn-delete,
            .action-buttons .btn-archive,
            .action-buttons .btn-unarchive {
                padding: 4px 8px;
                font-size: 9px;
                min-width: 40px;
                height: 24px;
            }
        }

        .user-table td:last-child {
            text-align: center;
        }

        /* Action buttons styling */
        .action-buttons {
            display: flex;
            gap: 6px;
            justify-content: center;
            align-items: center;
            padding: 4px;
            flex-wrap: nowrap;
            width: 100%;
            box-sizing: border-box;
            vertical-align: middle;
        }

        .action-buttons .btn-edit,
        .action-buttons .btn-delete {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            min-width: 55px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            white-space: nowrap;
            flex-shrink: 0;
        }

        .action-buttons .btn-edit {
            background-color: var(--color-highlight) !important;
            color: white !important;
            border: none !important;
            padding: 6px 12px !important;
            border-radius: 6px !important;
            font-size: 11px !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
            white-space: nowrap !important;
            flex-shrink: 0 !important;
            min-width: 55px !important;
            height: 28px !important;
            margin-right: 4px !important;
        }

        .action-buttons .btn-edit:hover {
            background-color: #8CA86E !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 8px rgba(161, 180, 84, 0.3) !important;
        }

        .action-buttons .btn-delete {
            background-color: #e74c3c !important;
            color: white !important;
            border: none !important;
            padding: 6px 12px !important;
            border-radius: 6px !important;
            font-size: 11px !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
            white-space: nowrap !important;
            flex-shrink: 0 !important;
            min-width: 55px !important;
            height: 28px !important;
        }

        .action-buttons .btn-delete:hover {
            background-color: #c0392b !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 8px rgba(231, 76, 60, 0.3) !important;
        }

        .action-buttons .btn-archive {
            background-color: #ff8c00 !important;
            color: white !important;
            border: none !important;
            padding: 6px 12px !important;
            border-radius: 6px !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
            text-decoration: none !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-width: 60px !important;
            height: 28px !important;
            margin-right: 4px !important;
        }

        .action-buttons .btn-archive:hover {
            background-color: #e67e00 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 8px rgba(255, 140, 0, 0.3) !important;
        }

        .action-buttons .btn-unarchive {
            background-color: #32cd32 !important;
            color: white !important;
            border: none !important;
            padding: 6px 12px !important;
            border-radius: 6px !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
            text-decoration: none !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-width: 60px !important;
            height: 28px !important;
            margin-right: 4px !important;
        }

        .action-buttons .btn-unarchive:hover {
            background-color: #228b22 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 8px rgba(50, 205, 50, 0.3) !important;
        }

        /* Additional clickability rules for archive buttons */
        .btn-archive, .btn-unarchive {
            pointer-events: auto !important;
            cursor: pointer !important;
            position: relative !important;
            z-index: 1002 !important;
            user-select: none !important;
        }

        /* Password Confirmation Modal Styling */
        #passwordConfirmModal {
            z-index: 1002 !important;
        }
        
        #passwordConfirmModal .modal-content {
            max-width: 500px;
            border: 2px solid var(--color-danger);
            box-shadow: 0 20px 40px rgba(231, 76, 60, 0.3);
        }

        #passwordConfirmModal .modal-header {
            background: linear-gradient(135deg, rgba(231, 76, 60, 0.1) 0%, rgba(231, 76, 60, 0.05) 100%);
            border-bottom: 2px solid rgba(231, 76, 60, 0.2);
            padding: 20px;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        #passwordConfirmModal .modal-header h2 {
            color: var(--color-danger);
            margin: 0;
            font-size: 18px;
            font-weight: 700;
        }

        #passwordConfirmModal .modal-body {
            padding: 20px;
        }

        #passwordConfirmModal .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid var(--color-border);
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            background-color: rgba(0, 0, 0, 0.02);
            border-radius: 0 0 10px 10px;
        }

        #passwordConfirmModal .input-group {
            margin-bottom: 15px;
        }

        #passwordConfirmModal .input-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--color-text);
        }

        #passwordConfirmModal .input-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--color-border);
            border-radius: 8px;
            font-size: 14px;
            background-color: var(--color-card);
            color: var(--color-text);
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        #passwordConfirmModal .input-group input:focus {
            outline: none;
            border-color: var(--color-danger);
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
        }

        #passwordConfirmModal .btn-danger {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        #passwordConfirmModal .btn-danger:hover {
            background: linear-gradient(135deg, #c0392b 0%, #e74c3c 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
        }

        #passwordConfirmModal .btn-danger:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        #passwordConfirmModal .btn-secondary {
            background-color: var(--color-border);
            color: var(--color-text);
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        #passwordConfirmModal .btn-secondary:hover {
            background-color: var(--color-hover);
            transform: translateY(-1px);
        }

        /* Delete All Users Button Styling */
        .btn-delete-all {
            background-color: #e74c3c !important;
            color: white !important;
            border: none !important;
            padding: 8px 16px !important;
            border-radius: 6px !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
            white-space: nowrap !important;
            flex-shrink: 0 !important;
            min-width: 140px !important;
            height: 36px !important;
        }

        .btn-delete-all:hover {
            background-color: #c0392b !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 8px rgba(231, 76, 60, 0.3) !important;
        }

        .btn-delete-all:active {
            transform: translateY(0) !important;
            box-shadow: 0 2px 4px rgba(231, 76, 60, 0.2) !important;
        }

        .action-buttons .btn-edit:disabled,
        .action-buttons .btn-delete:disabled,
        .action-buttons .btn-archive:disabled,
        .action-buttons .btn-unarchive:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Light theme action buttons */
        .light-theme .action-buttons .btn-edit {
            background-color: rgba(102, 187, 106, 0.15);
            color: var(--color-highlight);
            border: 2px solid rgba(102, 187, 106, 0.4);
        }

        .light-theme .action-buttons .btn-edit:hover {
            background-color: rgba(102, 187, 106, 0.25);
        }

        .light-theme .action-buttons .btn-delete {
            background-color: rgba(229, 115, 115, 0.15);
            color: var(--color-danger);
            border: 2px solid rgba(229, 115, 115, 0.4);
        }

        .light-theme .action-buttons .btn-delete:hover {
            background-color: rgba(229, 115, 115, 0.25);
        }

        .light-theme .action-buttons .btn-archive {
            background-color: rgba(255, 140, 0, 0.15);
            color: #ff8c00;
            border: 2px solid rgba(255, 140, 0, 0.4);
        }

        .light-theme .action-buttons .btn-archive:hover {
            background-color: rgba(255, 140, 0, 0.25);
        }

        .light-theme .action-buttons .btn-unarchive {
            background-color: rgba(50, 205, 50, 0.15);
            color: #32cd32;
            border: 2px solid rgba(50, 205, 50, 0.4);
        }

        .light-theme .action-buttons .btn-unarchive:hover {
            background-color: rgba(50, 205, 50, 0.25);
        }

        /* Editable fields styling */
        .editable {
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
            transition: all 0.3s ease;
            display: inline-block;
            min-width: 100px;
            border: 1px solid transparent;
        }

        .editable:hover {
            background-color: rgba(161, 180, 84, 0.1);
            border-color: rgba(161, 180, 84, 0.3);
            transform: translateY(-1px);
        }

        .editable:active {
            background-color: rgba(161, 180, 84, 0.2);
            transform: translateY(0);
        }

        /* User modal styling */
        .modal-content .input-group {
            margin-bottom: 20px;
        }

        .modal-content .input-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--color-text);
            font-weight: 500;
        }

        .modal-content .input-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(161, 180, 84, 0.3);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.05);
            color: var(--color-text);
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .modal-content .input-group input:focus {
            outline: none;
            border-color: var(--color-highlight);
            box-shadow: 0 0 0 3px rgba(161, 180, 84, 0.1);
            background: rgba(255, 255, 255, 0.08);
        }

        .modal-content .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .modal-content .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .modal-content .btn-submit {
            background-color: var(--color-highlight);
            color: white;
        }

        .modal-content .btn-submit:hover {
            background-color: var(--color-accent1);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(161, 180, 84, 0.3);
        }

        .modal-content .btn-cancel {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--color-text);
            border: 1px solid rgba(161, 180, 84, 0.3);
        }

        .modal-content .btn-cancel:hover {
            background-color: rgba(161, 180, 84, 0.1);
            border-color: var(--color-highlight);
        }

        /* Auto-fit columns - automatically distributes space equally */
        /* All columns will automatically get equal width distribution */
        /* No need for specific nth-child rules - table will auto-adjust */

        .user-table th {
            color: var(--color-highlight);
            font-weight: 700;
            font-size: 14px;
            position: sticky;
            top: 0;
            background-color: var(--color-card);
            z-index: 10;
            border-bottom: 2px solid rgba(161, 180, 84, 0.4);
            padding: 14px 8px;
            text-align: center;
            vertical-align: middle;
            font-family: inherit;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            backdrop-filter: blur(10px);
        }

        /* Header alignment for specific columns */
        .user-table th:nth-child(1),
        .user-table th:nth-child(2),
        .user-table th:nth-child(3),
        .user-table th:nth-child(4) {
            text-align: left;
        }

        .user-table th:nth-child(5),
        .user-table th:nth-child(6),
        .user-table th:nth-child(7) {
            text-align: center;
        }

        .tooltip {
    position: relative;
            cursor: pointer;
        }

        .tooltiptext {
            visibility: hidden;
            width: 200px;
            background: var(--color-card);
    color: var(--color-text);
            text-align: center;
            border-radius: 8px;
            padding: 8px;
            position: absolute;
            z-index: 1000;
            bottom: 125%;
            left: 50%;
            margin-left: -100px;
            opacity: 0;
            transition: opacity 0.3s;
            border: 1px solid var(--color-border);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    font-size: 12px;
            line-height: 1.4;
        }

        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }

        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }

        .status-active {
            background-color: var(--color-highlight);
        }

        .status-suspended {
            background-color: var(--color-warning);
        }

        .status-inactive {
            background-color: var(--color-danger);
        }

        .risk-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
    font-weight: 600;
            display: inline-block;
            text-align: center;
            min-width: 50px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
        }

        .risk-badge.good {
            background-color: rgba(161, 180, 84, 0.15);
            color: #A1B454;
        }

        .risk-badge.at,
        .risk-badge.risk {
            background-color: rgba(224, 201, 137, 0.15);
            color: #E0C989;
        }

        .risk-badge.malnourished {
            background-color: rgba(207, 134, 134, 0.15);
            color: #CF8686;
        }

        /* Light theme risk badge styles */
        .light-theme .risk-badge.good {
            background-color: rgba(102, 187, 106, 0.15);
    color: var(--color-highlight);
            border: 1px solid rgba(102, 187, 106, 0.3);
        }

        .light-theme .risk-badge.at,
        .light-theme .risk-badge.risk {
            background-color: rgba(255, 183, 77, 0.15);
            color: var(--color-warning);
            border: 1px solid rgba(255, 183, 77, 0.3);
        }

        .light-theme .risk-badge.malnourished {
            background-color: rgba(229, 115, 115, 0.15);
    color: var(--color-danger);
            border: 1px solid rgba(229, 115, 115, 0.3);
        }

        /* Add hover effects for risk badges */
        .risk-badge:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .risk-badge.low:hover {
            background-color: rgba(161, 180, 84, 0.25);
            transform: scale(1.05) translateY(-1px);
        }

        .risk-badge.medium:hover {
            background-color: rgba(224, 201, 137, 0.25);
            transform: scale(1.05) translateY(-1px);
        }

        .risk-badge.high:hover {
            background-color: rgba(207, 134, 134, 0.25);
            transform: scale(1.05) translateY(-1px);
        }

        .light-theme .risk-badge.low:hover {
            background-color: rgba(102, 187, 106, 0.25);
        }

        .light-theme .risk-badge.medium:hover {
            background-color: rgba(255, 183, 77, 0.25);
        }

        .light-theme .risk-badge.high:hover {
            background-color: rgba(229, 115, 115, 0.25);
        }

        /* Add hover effects for table rows */
    .user-table tbody tr {
            transition: all 0.3s ease;
        position: relative;
    }

    .user-table tbody tr::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(90deg, transparent, rgba(161, 180, 84, 0.05), transparent);
        opacity: 0;
            transition: opacity 0.3s ease;
    }

    .user-table tbody tr:hover::after {
        opacity: 1;
    }

        .user-table tbody tr:hover {
            border-left-color: var(--color-highlight);
            background-color: rgba(161, 180, 84, 0.2);
        transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(161, 180, 84, 0.15);
        }

        /* Add hover effects for search container */
        .search-container {
    display: flex;
    align-items: center;
    background: var(--color-card);
    border-radius: 8px;
    padding: 8px 12px;
    border: 1px solid rgba(161, 180, 84, 0.3);
    transition: all 0.2s ease;
    flex: 1;
    min-width: 0;
    max-width: 300px;
}

.search-container:focus-within {
    border-color: var(--color-highlight);
    box-shadow: 0 0 0 2px rgba(161, 180, 84, 0.2);
            transform: translateY(-1px);
}

.search-input {
    border: none;
    background: transparent;
    color: var(--color-text);
    padding: 6px 8px;
    font-size: 14px;
    outline: none;
    width: 100%;
    font-weight: 500;
}

.search-input::placeholder {
    color: rgba(255, 255, 255, 0.5);
    font-weight: 400;
}

.search-input::placeholder {
    color: rgba(255, 255, 255, 0.6);
}

/* Dark theme search input styles */
        .dark-theme .search-input {
            background: var(--color-card);
            color: var(--color-text);
            border: 2px solid rgba(161, 180, 84, 0.3);
            border-radius: 8px;
            padding: 10px 12px;
            transition: all 0.3s ease;
            outline: none; /* Remove default outline */
            box-shadow: none; /* Remove any box-shadow that might create inside outline */
        }

        .dark-theme .search-input:focus {
            border-color: var(--color-highlight);
            box-shadow: none !important; /* Remove focus box-shadow to eliminate inside outline */
            outline: none !important; /* Ensure no outline on focus */
            transform: translateY(-2px);
        }

.dark-theme .search-input:hover {
    border-color: var(--color-highlight);
}

.dark-theme .search-input::placeholder {
    color: rgba(232, 240, 214, 0.6);
}

/* Light theme search input styles */
        .light-theme .search-input {
            background: var(--color-card);
            color: var(--color-text);
            border: 2px solid rgba(161, 180, 84, 0.3);
            border-radius: 8px;
            padding: 10px 12px;
            transition: all 0.3s ease;
            outline: none; /* Remove default outline */
            box-shadow: none; /* Remove any box-shadow that might create inside outline */
        }

        .light-theme .search-input:focus {
            border-color: var(--color-highlight);
            box-shadow: none !important; /* Remove focus box-shadow to eliminate inside outline */
            outline: none !important; /* Ensure no outline on focus */
            transform: translateY(-2px);
        }

.light-theme .search-input:hover {
    border-color: var(--color-highlight);
}

.light-theme .search-input::placeholder {
    color: rgba(65, 89, 57, 0.6);
}

.search-btn {
    background: var(--color-highlight);
    border: none;
    color: var(--color-bg);
    padding: 6px 8px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.2s ease;
    font-weight: 600;
    min-width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.search-btn:hover {
    background: var(--color-accent1);
    transform: scale(1.02);
}

/* Location filter styles */
.location-filter-container {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
    max-width: 250px;
}

.location-select {
    padding: 8px 12px;
    border-radius: 8px;
    border: 1px solid var(--color-border);
    background-color: var(--color-card);
    color: var(--color-text);
    font-size: 14px;
    width: 100%;
    cursor: pointer;
    transition: all 0.2s ease;
    font-weight: 500;
}

/* Dark theme location select styles */
.dark-theme .location-select {
    background-color: var(--color-card);
    color: var(--color-text);
    border-color: var(--color-border);
}

.light-theme .location-select {
    background-color: var(--color-card);
    border: 1px solid var(--color-border);
    color: var(--color-text);
}

.location-select:focus {
    outline: none;
    border-color: var(--color-highlight);
    box-shadow: 0 0 0 2px rgba(161, 180, 84, 0.2);
            transform: translateY(-1px);
}

.light-theme .location-select:focus {
    border-color: var(--color-highlight);
    box-shadow: 0 0 0 2px var(--color-shadow);
}

        .no-data-message {
            text-align: center;
            padding: 30px;
            color: var(--color-text);
            opacity: 0.7;
            font-style: italic;
        }

        /* CSV Upload Styles */
        .csv-upload-area {
            background: linear-gradient(135deg, rgba(161, 180, 84, 0.1), rgba(161, 180, 84, 0.05));
            border: 2px dashed rgba(161, 180, 84, 0.4);
            border-radius: 15px;
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
    margin-bottom: 20px;
        }

        .csv-upload-area:hover {
            background: linear-gradient(135deg, rgba(161, 180, 84, 0.15), rgba(161, 180, 84, 0.1));
            border-color: var(--color-highlight);
            transform: translateY(-2px);
        }

        .csv-upload-area.dragover {
            background: linear-gradient(135deg, rgba(161, 180, 84, 0.2), rgba(161, 180, 84, 0.15));
            border-color: var(--color-highlight);
            transform: scale(1.02);
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
        }

        .csv-import-modal-content {
            position: fixed !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            margin: 0 !important;
            height: 85vh !important;
            width: 90% !important;
            max-width: 800px !important;
            border-radius: 15px !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3) !important;
            overflow: hidden !important;
            z-index: 1001 !important;
        }

        .csv-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
    margin-top: 20px;
}

.csv-preview-table {
    width: 100%;
    border-collapse: collapse;
            margin-top: 15px;
            font-size: 12px;
}

.csv-preview-table th,
.csv-preview-table td {
            padding: 8px;
    text-align: left;
            border: 1px solid var(--color-border);
}

.csv-preview-table th {
    background-color: var(--color-highlight);
    color: white;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
    width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: var(--color-card);
            margin: 2% auto;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            max-height: 95vh;
            overflow-y: auto;
            position: relative;
            top: 50%;
            transform: translateY(-50%);
        }

        /* Specific styling for edit user modal */
        #editUserModal .modal-content {
            background-color: var(--color-card);
            margin: 0 auto;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            max-height: 95vh;
            overflow-y: auto;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1001;
        }

        .close {
    color: var(--color-text);
            float: right;
            font-size: 28px;
    font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
    color: var(--color-danger);
        }

        .btn-submit {
            background-color: var(--color-highlight);
            color: white;
            padding: 10px 20px;
            border: none;
    border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-submit:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .btn-cancel {
            background-color: var(--color-danger);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
    font-size: 14px;
}

        .csv-import-info {
    background-color: rgba(161, 180, 84, 0.1);
    border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .csv-import-info h4 {
            color: var(--color-highlight);
            margin-bottom: 10px;
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
</head>
<body class="light-theme">
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
            <div style="margin-top: 10px;">Logged in as: <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></div>
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
                <a href="event" class="mobile-nav-icon" title="Events">
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
                <a href="settings" class="mobile-nav-icon active" title="Settings">
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

    <div class="dashboard">
        <header>
            <div class="dashboard-header">
                <h1>Settings and Admin</h1>
            </div>
            <div class="user-info">
                <button id="new-theme-toggle" class="new-theme-toggle-btn" title="Toggle theme">
                    <span class="new-theme-icon">🌙</span>
                </button>
            </div>
        </header>

        <div class="screening-container">
            <div class="user-management-container">
                <!-- New Organized Grid Layout -->
                <div class="control-grid">
                    <!-- Row 1: Action Buttons and Search -->
                    <div class="control-row-1">
                        <div class="action-section">
                            <button class="btn-add" id="tableToggleBtn" onclick="downloadCSVTemplate()">
                                <span class="btn-text">Switch to Admin</span>
                            </button>
                            <button class="btn-secondary" onclick="showAddUserModal()">
                                <span class="btn-icon">➕</span>
                                <span class="btn-text">Add User</span>
                            </button>
                            <button class="btn-delete-all" onclick="deleteAllUsers()">
                                <span class="btn-icon">🗑️</span>
                                <span class="btn-text">Delete All Users</span>
                            </button>
                            <button class="btn-delete-location" onclick="showDeleteByLocationModal()" id="deleteByLocationBtn" style="display: none;">
                                <span class="btn-text">Delete by Location</span>
                            </button>
                        </div>
                        <div>
                            <input type="text" id="searchInput" placeholder="Search by name, email, location, or gender..." class="search-input">
                            <button type="button" onclick="searchAssessments()" class="search-btn">🔍</button>
                        </div>
                    </div>

                    <!-- Row 2: Filter Controls -->
                    <div class="control-row-2">
                        <div class="filter-section">
                            <div class="filter-item">
                                <label>MUNICIPALITY</label>
                                <select id="municipalityFilter" onchange="filterByMunicipality()">
                                    <option value="">All</option>
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

                            <div class="filter-item">
                                <label>BARANGAY</label>
                                <select id="barangayFilter" onchange="filterByBarangay()">
                                    <option value="">All</option>
                                </select>
                            </div>

                            <div class="filter-item">
                                <label>SCREENING DATE RANGE</label>
                                <div class="date-input-group">
                                    <input type="date" id="fromDate" class="date-input" onchange="filterByDateRange()">
                                    <span class="date-separator">to</span>
                                    <input type="date" id="toDate" class="date-input" onchange="filterByDateRange()">
                                </div>
                            </div>

                            <div class="filter-item">
                                <label>GENDER</label>
                                <select id="sexFilter" onchange="filterBySex()">
                                    <option value="">All</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            
            <div id="no-users-message" style="display:none;" class="no-data-message">
                No users found in the database. Add your first user!
            </div>
            
            <div class="table-responsive">
                <table class="user-table">
                <thead>
                    <tr>
                        <th>NAME</th>
                        <th>EMAIL</th>
                        <th>MUNICIPALITY</th>
                        <th>BARANGAY</th>
                        <th>SEX</th>
                        <th>BIRTHDAY</th>
                        <th>SCREENING DATE</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <?php
                        // Get community_users data directly from database
                    if ($db->isAvailable()) {
                        try {
                            // Use Universal DatabaseAPI to get users for HTML display
                            // First get all community users
                            $result = $db->select(
                                    'community_users',
                                    '*',
                                '',
                                [],
                                    'name ASC'
                            );
                            
                            $users = $result['success'] ? $result['data'] : [];
                            
                            if (!empty($users)) {
                                foreach ($users as $user) {
                                    // Use email as the identifier since it's the primary key
                                    $userIdentifier = htmlspecialchars($user['email'] ?? '');
                                    
                                    // Get screening date directly from community_users table
                                    $screeningDate = 'Not Available';
                                    if (isset($user['screening_date']) && $user['screening_date'] && $user['screening_date'] !== 'N/A') {
                                        try {
                                            $date = new DateTime($user['screening_date']);
                                            $screeningDate = $date->format('Y-m-d');
                                        } catch (Exception $e) {
                                            $screeningDate = 'Invalid Date';
                                        }
                                    }
                                    
                                    echo '<tr data-user-email="' . $userIdentifier . '">';
                                    echo '<td>' . htmlspecialchars($user['name'] ?? 'N/A') . '</td>';
                                    echo '<td>' . $userIdentifier . '</td>';
                                    echo '<td>' . htmlspecialchars($user['municipality'] ?? 'N/A') . '</td>';
                                    echo '<td>' . htmlspecialchars($user['barangay'] ?? 'N/A') . '</td>';
                                    echo '<td>' . htmlspecialchars($user['sex'] ?? 'N/A') . '</td>';
                                    echo '<td>' . htmlspecialchars($user['birthday'] ?? 'N/A') . '</td>';
                                    echo '<td>' . htmlspecialchars($screeningDate) . '</td>';
                                    echo '<td class="action-buttons">';
                                    echo '<button class="btn-edit" onclick="editUser(\'' . $userIdentifier . '\')" title="Edit User">';
                                    echo 'Edit';
                                    echo '</button>';
                                    
                                    // Add archive/unarchive button based on status
                                    $userStatus = $user['status'] ?? 'active';
                                    if ($userStatus === 'active') {
                                        echo '<button class="btn-archive" onclick="archiveUser(\'' . $userIdentifier . '\', \'archive\')" title="Archive User">';
                                        echo 'Archive';
                                        echo '</button>';
                                    } else {
                                        echo '<button class="btn-unarchive" onclick="archiveUser(\'' . $userIdentifier . '\', \'unarchive\')" title="Unarchive User">';
                                        echo 'Unarchive';
                                        echo '</button>';
                                    }
                                    
                                    echo '<button class="btn-delete" onclick="deleteUser(\'' . $userIdentifier . '\')" title="Delete User">';
                                    echo 'Delete';
                                    echo '</button>';
                                    echo '</td>';
                                    echo '</tr>';
                                }
                            } else {
                                    // Let JavaScript handle empty database with sample data
                                    echo '<!-- No users in database - JavaScript will show sample data -->';
                            }
                        } catch (Exception $e) {
                                echo '<tr><td colspan="7" class="no-data-message">Error loading users: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                        }
                    } else {
                            echo '<tr><td colspan="7" class="no-data-message">Database connection failed.</td></tr>';
                    }
                    ?>
                </tbody>
                </table>
                    </div>
                </div>
            </div>
            
            <!-- Current User Profile Card -->
            <div class="profile-card">
            <div class="profile-header">
                <h3>My Profile</h3>
                <button class="btn-edit-profile" onclick="toggleProfileEdit()">
                    <span class="btn-icon">✏️</span>
                    <span class="btn-text">Edit Profile</span>
                </button>
            </div>
            
            <div class="profile-content">
                <div class="profile-info" id="profileInfo">
                    <div class="profile-field">
                        <label>Name:</label>
                        <span id="profileName"><?php echo htmlspecialchars($_SESSION['username'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="profile-field">
                        <label>Email:</label>
                        <span id="profileEmail"><?php echo htmlspecialchars($_SESSION['email'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="profile-field">
                        <label>Role:</label>
                        <span id="profileRole"><?php echo htmlspecialchars($_SESSION['role'] ?? 'Admin'); ?></span>
                    </div>
                </div>

                <div class="profile-edit" id="profileEdit" style="display: none;">
                    <form id="profileForm">
                        <div class="form-group">
                            <label for="editName">Name:</label>
                            <input type="text" id="editName" name="name" value="<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="editEmail">Email:</label>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="email" id="editEmail" name="email" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" required style="flex: 1;">
                                <button type="button" onclick="showChangeEmailModal()" class="btn-change-email">Change Email</button>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn-cancel" onclick="cancelProfileEdit()">Cancel</button>
                            <button type="submit" class="btn-save">Save Changes</button>
                            <button type="button" class="btn-change-password" onclick="showChangePasswordModal()">
                                <span class="btn-text">Change Password</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        </div>
        
        <!-- CSV Import Modal -->
        <div id="csvImportModal" class="modal">
            <div class="modal-content csv-import-modal-content">
                <span class="close" onclick="closeCSVImportModal()">&times;</span>
            <h2>Import Assessments from CSV</h2>
                <div style="height: calc(85vh - 120px); overflow-y: auto; padding-right: 10px;">
                
                <!-- Status Message Area -->
                <div id="csvStatusMessage" style="display: none; margin-bottom: 20px; padding: 15px; border-radius: 8px; font-weight: 600;"></div>
                
                <div class="csv-import-info">
                    <div style="background-color: rgba(233, 141, 124, 0.2); border: 2px solid var(--color-danger); border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <h4 style="color: var(--color-danger); margin: 0 0 10px 0;">⚠️ CRITICAL: EXACT FORMAT REQUIRED</h4>
                                <p style="margin: 0; color: var(--color-danger); font-weight: 600;">CSV data MUST use EXACTLY the same answer options as the mobile app. Any deviation will cause validation errors and prevent import.</p>
                            </div>
                    </div>
                </div>
                
                <h4>📋 CSV Import Instructions</h4>
                                        <p><strong>1.</strong> Download template with exact mobile app formats</p>
                                        <p><strong>2.</strong> Fill data using ONLY specified answer options</p>
                <p><strong>3.</strong> Upload your completed CSV file</p>
                <p><strong>4.</strong> Review and confirm import</p>
                                    </div>
            
                <form id="csvImportForm">
                    <div class="csv-upload-area" id="uploadArea" onclick="document.getElementById('csvFile').click()" style="cursor: pointer;" 
                         ondragover="handleDragOver(event)" 
                         ondrop="handleDrop(event)" 
                         ondragleave="handleDragLeave(event)">
                    <input type="file" id="csvFile" accept=".csv" style="display: none;" onchange="handleFileSelect(event)">
                        <div class="upload-text">
                            <h4>Upload CSV File</h4>
                        <p>Click here or drag and drop your CSV file</p>
                        <small class="csv-format">Supported format: .csv</small>
                        </div>
                    </div>
                    
                <div id="csvPreview" style="display: none;"></div>
                    
                    <div class="csv-actions">
                        <button type="button" class="btn btn-submit" id="importCSVBtn" disabled onclick="processCSVImport()">📥 Import CSV</button>
                        <button type="button" class="btn btn-cancel" id="cancelBtn" style="display: none;" onclick="cancelUpload()">❌ Cancel Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- User Import Modal (Admin Users) -->
    <div id="userImportModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeUserImportModal()">&times;</span>
            <h2>Add New Admin User</h2>
            <form id="userForm">
                <div class="input-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="input-group">
                    <label for="municipality">Municipality *</label>
                    <select id="municipality" name="municipality" required>
                        <option value="">Select Municipality</option>
                        <?php
                        foreach ($municipalities as $municipality => $barangays) {
                            echo '<option value="' . htmlspecialchars($municipality) . '">' . htmlspecialchars($municipality) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-submit" onclick="addNewUser()">Add Admin User</button>
                    <button type="button" class="btn btn-cancel" onclick="closeUserImportModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Community User Modal -->
    <div id="addCommunityUserModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Community User</h2>
                <span class="close" onclick="closeAddCommunityUserModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="addCommunityUserForm">
                    <div class="form-group">
                        <label for="addName">Full Name *</label>
                        <input type="text" id="addName" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="addEmail">Email *</label>
                        <input type="email" id="addEmail" name="email" required onblur="validateAddEmail()">
                        <small id="addEmailError" style="color: red; font-size: 12px;"></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="addPassword">Password *</label>
                        <input type="password" id="addPassword" name="password" required>
                        <small style="color: #666; font-size: 12px;">Default password: password123</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="addMunicipality">Municipality *</label>
                        <select id="addMunicipality" name="municipality" required onchange="updateAddBarangayOptions()">
                            <option value="">Select Municipality</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="addBarangay">Barangay *</label>
                        <select id="addBarangay" name="barangay" required>
                            <option value="">Select Barangay</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="addSex">Sex *</label>
                        <select id="addSex" name="sex" required onchange="toggleAddPregnancyField()">
                            <option value="">Select Sex</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="addBirthday">Birthday *</label>
                        <input type="date" id="addBirthday" name="birthday" required onchange="calculateAddAge()">
                        <small id="addAgeDisplay" style="color: #666; font-size: 12px;"></small>
                    </div>
                    
                    <div class="form-group" id="addPregnancyGroup" style="display: none;">
                        <label for="addPregnancy">Are you pregnant? *</label>
                        <select id="addPregnancy" name="is_pregnant">
                            <option value="No">No</option>
                            <option value="Yes">Yes</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="addWeight">Weight (kg) *</label>
                        <input type="number" id="addWeight" name="weight" step="0.1" min="0.1" max="1000" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="addHeight">Height (cm) *</label>
                        <input type="number" id="addHeight" name="height" step="0.1" min="1" max="300" required>
                    </div>
                    
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="addCommunityUser()">Add Community User</button>
                <button type="button" class="btn btn-secondary" onclick="closeAddCommunityUserModal()">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Password Confirmation Modal for Delete Operations -->
    <div id="passwordConfirmModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>🔒 Confirm Deletion</h2>
                <span class="close" onclick="closePasswordConfirmModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div style="background-color: rgba(231, 76, 60, 0.1); border: 2px solid var(--color-danger); border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 24px;">⚠️</span>
                        <div>
                            <h4 style="color: var(--color-danger); margin: 0 0 5px 0;">Security Verification Required</h4>
                            <p style="margin: 0; color: var(--color-danger); font-weight: 600;" id="deleteConfirmMessage">Please enter your admin password to confirm this deletion.</p>
                        </div>
                    </div>
                </div>
                
                <form id="passwordConfirmForm">
                    <div class="input-group">
                        <label for="confirmPassword">Your Password</label>
                        <input type="password" id="confirmPassword" name="confirmPassword" required placeholder="Enter your current password" autocomplete="current-password">
                    </div>
                    <div id="passwordError" style="color: var(--color-danger); font-size: 14px; margin-top: 5px; display: none;"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closePasswordConfirmModal()">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmPasswordAndDelete()" id="confirmDeleteBtn">
                    <span class="btn-icon">🗑️</span>
                    <span class="btn-text">Confirm Delete</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        // Municipalities and Barangays data
        const municipalities = <?php echo json_encode($municipalities); ?>;

        // Function to extract community users data from existing table
        function extractCommunityUsersData() {
            const tableBody = document.getElementById('usersTableBody');
            const rows = tableBody.querySelectorAll('tr');
            const users = [];
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length >= 7) { // Make sure we have enough columns including screening date
                    const user = {
                        name: cells[0].textContent.trim(),
                        email: cells[1].textContent.trim(),
                        municipality: cells[2].textContent.trim(),
                        barangay: cells[3].textContent.trim(),
                        sex: cells[4].textContent.trim(),
                        birthday: cells[5].textContent.trim(),
                        screening_date: cells[6].textContent.trim() // Add screening date
                    };
                    users.push(user);
                }
            });
            
            console.log('Extracted community users data:', users.length, 'users');
            return users;
        }

        // Initialize screening page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing screening page...');
            
            // Extract and store the original community users data
            originalCommunityUsersData = extractCommunityUsersData();
            initializeTableFunctionality();
        });



        function viewAssessmentDetails(id) {
            // Fetch assessment details via AJAX
                            fetch(`/api/DatabaseAPI.php?action=comprehensive_screening&screening_id=${id}`)
                .then(response => response.json())
                .then(data => {
                        if (data.error) {
                        alert('Error loading assessment details: ' + data.error);
                            return;
                        }
                    showAssessmentModal(data);
            })
            .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading assessment details');
                });
        }

        function showAssessmentModal(assessment) {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>MHO Nutritional Assessment - ${assessment.municipality}, ${assessment.barangay}</h3>
                        <span class="close" onclick="this.closest('.modal').remove()">&times;</span>
                    </div>
                    <div class="assessment-details">
                        <div class="detail-section">
                            <h4>📊 Basic Information</h4>
                            <div class="detail-item">
                                <span class="detail-label">Age:</span>
                                <span class="detail-value">${assessment.age} years${assessment.age_months ? `, ${assessment.age_months} months` : ''}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Sex:</span>
                                <span class="detail-value">${assessment.sex}</span>
                            </div>
                            ${assessment.pregnant ? `
                            <div class="detail-item">
                                <span class="detail-label">Pregnant:</span>
                                <span class="detail-value">${assessment.pregnant}</span>
                            </div>
                            ` : ''}
                        </div>
                        
                        <div class="detail-section">
                            <h4>📏 Anthropometric Data</h4>
                            <div class="detail-item">
                                <span class="detail-label">Weight:</span>
                                <span class="detail-value">${assessment.weight} kg</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Height:</span>
                                <span class="detail-value">${assessment.height} cm</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">BMI:</span>
                                <span class="detail-value">${assessment.bmi} (${getBMICategory(assessment.bmi)})</span>
                            </div>
                        </div>
                        
                        <div class="detail-section">
                            <h4>⚠️ Decision Tree Assessment</h4>
                            <div class="detail-item">
                                <span class="detail-label">Decision Tree Score:</span>
                                <span class="detail-value">${assessment.risk_score}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Nutritional Risk Level:</span>
                                <span class="detail-value">${getRiskLevel(assessment.risk_score)}</span>
                            </div>
                        </div>
                        
                        ${assessment.meal_recall ? `
                        <div class="detail-section">
                            <h4>🍽️ Meal Assessment</h4>
                            <div class="detail-item">
                                <span class="detail-label">24-Hour Recall:</span>
                                <span class="detail-value">${assessment.meal_recall}</span>
                            </div>
                        </div>
                        ` : ''}
                        
                        ${assessment.family_history ? `
                        <div class="detail-section">
                            <h4>👨‍👩‍👧‍👦 Family History</h4>
                            <div class="detail-item">
                                <span class="detail-label">Conditions:</span>
                                <span class="detail-value">${JSON.parse(assessment.family_history).join(', ')}</span>
                            </div>
                        </div>
                        ` : ''}
                        
                        <div class="detail-section">
                            <h4>🏃‍♀️ Lifestyle</h4>
                            <div class="detail-item">
                                <span class="detail-label">Activity Level:</span>
                                <span class="detail-value">${assessment.lifestyle}${assessment.lifestyle_other ? ` - ${assessment.lifestyle_other}` : ''}</span>
                            </div>
                        </div>
                        
                        ${assessment.assessment_summary ? `
                        <div class="detail-section" style="grid-column: 1 / -1;">
                            <h4>📋 Assessment Summary</h4>
                            <div class="detail-item">
                                <span class="detail-value">${assessment.assessment_summary}</span>
                            </div>
                        </div>
                        ` : ''}
                        
                        ${assessment.recommendations ? `
                        <div class="detail-section" style="grid-column: 1 / -1;">
                            <h4>💡 Recommendations</h4>
                            <div class="detail-item">
                                <span class="detail-value">${assessment.recommendations}</span>
                </div>
            </div>
                        ` : ''}
            </div>
            </div>
        `;
            
            document.body.appendChild(modal);
            modal.style.display = 'block';
            
            // Close modal when clicking outside
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }

        function getBMICategory(bmi) {
            if (bmi < 18.5) return 'Underweight';
            if (bmi < 25) return 'Normal';
            if (bmi < 30) return 'Overweight';
            return 'Obese';
        }

        function getRiskLevel(score) {
            if (score <= 10) return 'Low';
            if (score <= 20) return 'Medium';
            return 'High';
        }

        // Search and filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchAssessments');
            const filterSelect = document.getElementById('filterRisk');
            
            if (searchInput) {
                searchInput.addEventListener('input', filterAssessments);
            }
            
            if (filterSelect) {
                filterSelect.addEventListener('change', filterAssessments);
            }
        });

        function filterAssessments() {
            const searchTerm = document.getElementById('searchAssessments').value.toLowerCase();
            const riskFilter = document.getElementById('filterRisk').value;
            const rows = document.querySelectorAll('.assessment-row');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const riskScore = parseInt(row.dataset.risk);
                
                let showRow = true;
                
                // Search filter
                if (searchTerm && !text.includes(searchTerm)) {
                    showRow = false;
                }
                
                // Risk filter
                if (riskFilter) {
                    if (riskFilter === 'low' && riskScore > 10) showRow = false;
                    if (riskFilter === 'medium' && (riskScore <= 10 || riskScore > 20)) showRow = false;
                    if (riskFilter === 'high' && riskScore <= 20) showRow = false;
                }
                
                row.style.display = showRow ? '' : 'none';
            });
        }

        // Theme persistence and toggle
    document.addEventListener('DOMContentLoaded', function() {
            // Load saved theme from localStorage
            const savedTheme = localStorage.getItem('theme');
            const body = document.body;
            const icon = document.querySelector('.new-theme-icon');
            
            if (savedTheme === 'dark') {
                body.classList.remove('light-theme');
                body.classList.add('dark-theme');
                icon.textContent = '🌙';
            } else {
                body.classList.remove('dark-theme');
                body.classList.add('light-theme');
                icon.textContent = '☀️';
            }
        });

        // Theme toggle
        document.getElementById('new-theme-toggle').addEventListener('click', function() {
            const body = document.body;
            const icon = this.querySelector('.new-theme-icon');
            
            if (body.classList.contains('dark-theme')) {
                body.classList.remove('dark-theme');
                body.classList.add('light-theme');
                icon.textContent = '☀️';
                localStorage.setItem('theme', 'light');
            } else {
                body.classList.remove('light-theme');
                body.classList.add('dark-theme');
                icon.textContent = '🌙';
                localStorage.setItem('theme', 'dark');
            }
        });

        // Enhanced MHO Assessment Table JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing MHO assessment table...');
            initializeTableFunctionality();
        });

        function initializeTableFunctionality() {
            // Add row hover effects
        const tableRows = document.querySelectorAll('.user-table tbody tr');
        tableRows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.01)';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        });
        }

        function searchAssessments() {
            const searchInput = document.getElementById('searchInput');
            const searchTerm = searchInput.value.toLowerCase().trim();
            const tableRows = document.querySelectorAll('.user-table tbody tr');
            
            let visibleCount = 0;
            
            tableRows.forEach(row => {
                const name = row.cells[0].textContent.toLowerCase();
                const email = row.cells[1].textContent.toLowerCase();
                const municipality = row.cells[2].textContent.toLowerCase();
                const barangay = row.cells[3].textContent.toLowerCase();
                const sex = row.cells[4].textContent.toLowerCase();
                
                const matchesSearch = name.includes(searchTerm) || 
                                   email.includes(searchTerm) || 
                                   municipality.includes(searchTerm) || 
                                   barangay.includes(searchTerm) || 
                                   sex.includes(searchTerm);
                
                if (matchesSearch) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            updateNoDataMessage(visibleCount);
        }

        function filterBySex() {
            const sexFilter = document.getElementById('sexFilter').value;
            const tableRows = document.querySelectorAll('.user-table tbody tr');
            
            let visibleCount = 0;
            
            tableRows.forEach(row => {
                const sex = row.cells[4].textContent.trim();
                
                if (!sexFilter || sex === sexFilter) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            updateNoDataMessage(visibleCount);
        }

        function filterByLocation() {
        const locationFilter = document.getElementById('locationFilter').value;
            const tableRows = document.querySelectorAll('.user-table tbody tr');
            
            let visibleCount = 0;
            
            tableRows.forEach(row => {
                const location = row.cells[3].textContent.toLowerCase();
                
                if (!locationFilter || location.includes(locationFilter.toLowerCase())) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            updateNoDataMessage(visibleCount);
        }

        function updateNoDataMessage(visibleCount) {
            const noDataMessage = document.querySelector('.no-data-message');
            const tbody = document.querySelector('.user-table tbody');
            
                if (visibleCount === 0) {
                if (!noDataMessage) {
                    const message = document.createElement('tr');
                    message.className = 'no-data-message';
                    message.innerHTML = '<td colspan="7"><div>No assessments found matching your criteria.</div></td>';
                    tbody.appendChild(message);
                }
            } else if (noDataMessage) {
                noDataMessage.remove();
            }
        }

        function viewAssessment(id) {
            // Get assessment data (in real implementation, this would fetch from database)
            const assessmentData = getAssessmentData(id);
            showAssessmentModal(assessmentData);
        }

        function editAssessment(id) {
            // In real implementation, this would redirect to edit form
            alert(`Edit assessment ${id} - Redirecting to edit form...`);
        }

        function deleteAssessment(id) {
            if (confirm('Are you sure you want to delete this assessment?')) {
                // In real implementation, this would delete from database
                const row = document.querySelector(`tr[data-id="${id}"]`);
                if (row) {
                    row.remove();
                    alert('Assessment deleted successfully!');
                }
            }
        }

        function getAssessmentData(id) {
            // Mock data - in real implementation, this would fetch from database
            return {
                id: id,
                name: 'Sample User',
                age: '25',
                sex: 'Female',
                municipality: 'Balanga',
                barangay: 'Bagumbayan',
                height: '160',
                weight: '55',
                bmi: '21.5',
                risk_level: 'Low Risk',
                created_at: '2024-01-15',
                meal_assessment: 'Balanced',
                lifestyle: 'Active',
                family_history: ['None'],
                immunization_status: 'Complete',
                risk_factors: ['None'],
                recommendation: 'Maintain current healthy lifestyle',
                intervention: 'Regular monitoring'
            };
        }

        function showAssessmentModal(assessment) {
            const modal = document.createElement('div');
            modal.className = 'assessment-modal';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                backdrop-filter: blur(5px);
            `;
            
            const modalContent = document.createElement('div');
            modalContent.style.cssText = `
                background: var(--color-card);
                border-radius: 16px;
                padding: 30px;
                max-width: 700px;
                width: 90%;
                max-height: 80vh;
                overflow-y: auto;
                border: 1px solid var(--color-highlight);
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
                position: relative;
            `;
            
            const closeBtn = document.createElement('button');
            closeBtn.innerHTML = '✕';
            closeBtn.style.cssText = `
                position: absolute;
                top: 15px;
                right: 20px;
                background: none;
                border: none;
                color: var(--color-highlight);
                font-size: 24px;
                cursor: pointer;
                padding: 5px;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.3s ease;
            `;
            
            closeBtn.addEventListener('mouseenter', function() {
                this.style.background = 'var(--color-highlight)';
                this.style.color = 'var(--color-bg)';
            });
            
            closeBtn.addEventListener('mouseleave', function() {
                this.style.background = 'none';
                this.style.color = 'var(--color-highlight)';
            });
            
            closeBtn.addEventListener('click', function() {
                modal.remove();
            });
            
            modalContent.innerHTML = `
                <h2 style="color: var(--color-highlight); margin-bottom: 25px; text-align: center; font-size: 28px;">
                    MHO Assessment Details - ${assessment.name}
                </h2>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div style="background: rgba(161, 180, 84, 0.1); padding: 15px; border-radius: 12px; border: 1px solid var(--color-border);">
                        <h3 style="color: var(--color-highlight); margin-bottom: 12px; font-size: 16px;">📋 Basic Information</h3>
                        <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>Name:</strong> ${assessment.name}</p>
                        <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>Age:</strong> ${assessment.age} years</p>
                        <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>Sex:</strong> ${assessment.sex}</p>
                        <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>Location:</strong> ${assessment.barangay}, ${assessment.municipality}</p>
                    </div>
                    
                    <div style="background: rgba(161, 180, 84, 0.1); padding: 15px; border-radius: 12px; border: 1px solid var(--color-border);">
                        <h3 style="color: var(--color-highlight); margin-bottom: 12px; font-size: 16px;">📏 Anthropometric Data</h3>
                        <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>Height:</strong> ${assessment.height} cm</p>
                        <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>Weight:</strong> ${assessment.weight} kg</p>
                        <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>BMI:</strong> ${assessment.bmi}</p>
                        <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>Risk Level:</strong> <span style="color: ${assessment.risk_level === 'Low Risk' ? '#4CAF50' : assessment.risk_level === 'Medium Risk' ? '#FF9800' : '#F44336'}">${assessment.risk_level}</span></p>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div style="background: rgba(161, 180, 84, 0.1); padding: 15px; border-radius: 12px; border: 1px solid var(--color-border);">
                        <h3 style="color: var(--color-highlight); margin-bottom: 12px; font-size: 16px;">🍽️ Meal Assessment</h3>
                        <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>24-Hour Recall:</strong> <span style="color: ${assessment.meal_assessment === 'Balanced' ? '#4CAF50' : '#FF9800'}">${assessment.meal_assessment}</span></p>
                    </div>
                    
                    <div style="background: rgba(161, 180, 84, 0.1); padding: 15px; border-radius: 12px; border: 1px solid var(--color-border);">
                        <h3 style="color: var(--color-highlight); margin-bottom: 12px; font-size: 16px;">🏃 Lifestyle</h3>
                        <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>Activity Level:</strong> <span style="color: ${assessment.lifestyle === 'Active' ? '#4CAF50' : '#FF9800'}">${assessment.lifestyle}</span></p>
                    </div>
                </div>
                
                <div style="background: rgba(161, 180, 84, 0.1); padding: 15px; border-radius: 12px; border: 1px solid var(--color-border); margin-bottom: 20px;">
                    <h3 style="color: var(--color-highlight); margin-bottom: 12px; font-size: 16px;">👨‍👩‍👧‍👦 Family History</h3>
                    <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>Conditions:</strong> ${assessment.family_history.join(', ')}</p>
                </div>
                
                <div style="background: rgba(161, 180, 84, 0.15); padding: 15px; border-radius: 12px; border: 1px solid var(--color-highlight);">
                    <h3 style="color: var(--color-highlight); margin-bottom: 12px; font-size: 16px;">💡 Recommendations</h3>
                    <p style="color: var(--color-text); margin-bottom: 8px; font-size: 14px;"><strong>Assessment:</strong> ${assessment.recommendation}</p>
                    <p style="color: var(--color-text); margin-bottom: 0; font-size: 14px;"><strong>Intervention:</strong> ${assessment.intervention}</p>
                </div>
            `;
            
            modalContent.appendChild(closeBtn);
            modal.appendChild(modalContent);
            document.body.appendChild(modal);
            
            // Close modal when clicking outside
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }







        // CSV Functions
        // Global variable to track current table type
        let currentTableType = 'community_users'; // 'community_users' for community users table, 'users' for users table
        let originalCommunityUsersData = null; // Store original community users data

        // Profile management functions
        function toggleProfileEdit() {
            const profileInfo = document.getElementById('profileInfo');
            const profileEdit = document.getElementById('profileEdit');
            const editBtn = document.querySelector('.btn-edit-profile');
            
            if (profileEdit.style.display === 'none') {
                profileInfo.style.display = 'none';
                profileEdit.style.display = 'block';
                editBtn.innerHTML = '<span class="btn-icon">❌</span><span class="btn-text">Cancel Edit</span>';
            } else {
                profileInfo.style.display = 'grid';
                profileEdit.style.display = 'none';
                editBtn.innerHTML = '<span class="btn-icon">✏️</span><span class="btn-text">Edit Profile</span>';
            }
        }

        function cancelProfileEdit() {
            toggleProfileEdit();
        }

        // Handle profile form submission
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const name = formData.get('name');
            const email = formData.get('email');
            
            // Update profile via API
            fetch('/settings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_profile&name=${encodeURIComponent(name)}&email=${encodeURIComponent(email)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the display
                    document.getElementById('profileName').textContent = name;
                    document.getElementById('profileEmail').textContent = email;
                    
                    // Update session data (if available)
                    if (typeof updateSessionData === 'function') {
                        updateSessionData({ username: name, email: email });
                    }
                    
                    showMessage('Profile updated successfully!', 'success');
                    toggleProfileEdit();
                } else {
                    showMessage('Failed to update profile: ' + data.error, 'error');
                }
            })
            .catch(error => {
                console.error('Error updating profile:', error);
                showMessage('Error updating profile. Please try again.', 'error');
            });
        });

        function showChangePasswordModal() {
            // Create password change modal
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.id = 'changePasswordModal';
            modal.innerHTML = `
                <div class="modal-content">
                    <span class="close" onclick="closeChangePasswordModal()">&times;</span>
                    <h2>Change Password</h2>
                    <form id="changePasswordForm">
                        <div class="form-group">
                            <label for="currentPassword">Current Password:</label>
                            <input type="password" id="currentPassword" name="currentPassword" required>
                        </div>
                        <div class="form-group">
                            <label for="newPassword">New Password:</label>
                            <input type="password" id="newPassword" name="newPassword" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label for="confirmPassword">Confirm New Password:</label>
                            <input type="password" id="confirmPassword" name="confirmPassword" required minlength="6">
                        </div>
                        <div class="form-group" id="verificationGroup" style="display: none;">
                            <label for="verificationCode">Verification Code (if you forgot current password):</label>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="text" id="verificationCode" name="verificationCode" style="flex: 1;">
                                <button type="button" onclick="sendVerificationCode()" class="btn-send-code">Send Code</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <button type="button" onclick="toggleForgotPassword()" class="btn-forgot-password">I forgot my current password</button>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn-cancel" onclick="closeChangePasswordModal()">Cancel</button>
                            <button type="submit" class="btn-save">Change Password</button>
                        </div>
                    </form>
                </div>
            `;
            
            document.body.appendChild(modal);
            modal.style.display = 'block';
        }

        function toggleForgotPassword() {
            const verificationGroup = document.getElementById('verificationGroup');
            const currentPasswordField = document.getElementById('currentPassword');
            const forgotBtn = document.querySelector('.btn-forgot-password');
            
            if (verificationGroup.style.display === 'none') {
                verificationGroup.style.display = 'block';
                currentPasswordField.required = false;
                currentPasswordField.placeholder = 'Leave blank if using verification code';
                forgotBtn.textContent = 'I know my current password';
            } else {
                verificationGroup.style.display = 'none';
                currentPasswordField.required = true;
                currentPasswordField.placeholder = '';
                forgotBtn.textContent = 'I forgot my current password';
            }
        }

        function closeChangePasswordModal() {
            const modal = document.getElementById('changePasswordModal');
            if (modal) {
                modal.remove();
            }
        }

        function showChangeEmailModal() {
            // Create email change modal
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.id = 'changeEmailModal';
            modal.innerHTML = `
                <div class="modal-content">
                    <span class="close" onclick="closeChangeEmailModal()">&times;</span>
                    <h2>Change Email Address</h2>
                    <form id="changeEmailForm">
                        <div class="form-group">
                            <label for="newEmail">New Email Address:</label>
                            <input type="email" id="newEmail" name="newEmail" required>
                        </div>
                        <div class="form-group">
                            <label for="emailVerificationCode">Verification Code:</label>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="text" id="emailVerificationCode" name="emailVerificationCode" required style="flex: 1;">
                                <button type="button" onclick="sendEmailVerificationCode()" class="btn-send-code">Send Code</button>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn-cancel" onclick="closeChangeEmailModal()">Cancel</button>
                            <button type="submit" class="btn-save">Change Email</button>
                        </div>
                    </form>
                </div>
            `;
            
            document.body.appendChild(modal);
            modal.style.display = 'block';
        }

        function closeChangeEmailModal() {
            const modal = document.getElementById('changeEmailModal');
            if (modal) {
                modal.remove();
            }
        }

        function sendEmailVerificationCode() {
            const newEmail = document.getElementById('newEmail').value;
            if (!newEmail) {
                showMessage('Please enter a new email address first!', 'error');
                return;
            }
            
            fetch('/settings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=send_email_verification_code&newEmail=${encodeURIComponent(newEmail)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Verification code sent to your new email address!', 'success');
                } else {
                    showMessage('Failed to send verification code: ' + data.error, 'error');
                }
            })
            .catch(error => {
                console.error('Error sending verification code:', error);
                showMessage('Error sending verification code. Please try again.', 'error');
            });
        }

        function sendVerificationCode() {
            fetch('/settings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=send_verification_code'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Verification code sent to your email!', 'success');
                } else {
                    showMessage('Failed to send verification code: ' + data.error, 'error');
                }
            })
            .catch(error => {
                console.error('Error sending verification code:', error);
                showMessage('Error sending verification code. Please try again.', 'error');
            });
        }

        // Handle email change form submission
        document.addEventListener('submit', function(e) {
            if (e.target.id === 'changeEmailForm') {
                e.preventDefault();
                
                const formData = new FormData(e.target);
                const newEmail = formData.get('newEmail');
                const verificationCode = formData.get('emailVerificationCode');
                
                if (!newEmail || !verificationCode) {
                    showMessage('Please fill in all fields!', 'error');
                    return;
                }
                
                // Update email via API
                fetch('/settings.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=change_email&newEmail=${encodeURIComponent(newEmail)}&verificationCode=${encodeURIComponent(verificationCode)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the display
                        document.getElementById('profileEmail').textContent = newEmail;
                        document.getElementById('editEmail').value = newEmail;
                        
                        // Update session data
                        if (typeof updateSessionData === 'function') {
                            updateSessionData({ email: newEmail });
                        }
                        
                        showMessage('Email changed successfully!', 'success');
                        closeChangeEmailModal();
                    } else {
                        showMessage('Failed to change email: ' + data.error, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error changing email:', error);
                    showMessage('Error changing email. Please try again.', 'error');
                });
            }
        });

        // Handle password change form submission
        document.addEventListener('submit', function(e) {
            if (e.target.id === 'changePasswordForm') {
                e.preventDefault();
                
                const formData = new FormData(e.target);
                const currentPassword = formData.get('currentPassword');
                const newPassword = formData.get('newPassword');
                const confirmPassword = formData.get('confirmPassword');
                const verificationCode = formData.get('verificationCode');
                const verificationGroup = document.getElementById('verificationGroup');
                const isUsingVerification = verificationGroup.style.display !== 'none';
                
                if (newPassword !== confirmPassword) {
                    showMessage('New passwords do not match!', 'error');
                    return;
                }
                
                if (newPassword.length < 6) {
                    showMessage('New password must be at least 6 characters long!', 'error');
                    return;
                }
                
                // Validate either current password or verification code is provided
                if (!isUsingVerification && !currentPassword) {
                    showMessage('Please enter your current password or use verification code!', 'error');
                    return;
                }
                
                if (isUsingVerification && !verificationCode) {
                    showMessage('Please enter the verification code!', 'error');
                    return;
                }
                
                // Update password via API
                fetch('/settings.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=change_password&currentPassword=${encodeURIComponent(currentPassword)}&newPassword=${encodeURIComponent(newPassword)}&verificationCode=${encodeURIComponent(verificationCode)}&useVerification=${isUsingVerification}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage('Password changed successfully!', 'success');
                        closeChangePasswordModal();
                    } else {
                        showMessage('Failed to change password: ' + data.error, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error changing password:', error);
                    showMessage('Error changing password. Please try again.', 'error');
                });
            }
        });

        function downloadCSVTemplate() {
            // Toggle between community_users table and users table
            if (currentTableType === 'community_users') {
                // Switch to users table
                currentTableType = 'users';
                updateTableToggleButton(); // Update button text immediately
                loadUsersTable();
            } else {
                // Switch back to community_users table
                currentTableType = 'community_users';
                updateTableToggleButton(); // Update button text immediately
                loadCommunityUsersTable();
            }
        }

        function showCSVImportModal() {
            const modal = document.getElementById('csvImportModal');
            if (modal) {
                modal.style.display = 'block';
                resetCSVForm();
            }
        }

        function showUserImportModal() {
            // Show modal for adding new user
            const modal = document.getElementById('userImportModal');
            if (modal) {
                modal.style.display = 'block';
                resetUserForm();
            }
        }

        function updateTableToggleButton() {
            const btn = document.getElementById('tableToggleBtn');
            if (btn) {
                if (currentTableType === 'users') {
                    btn.querySelector('.btn-text').textContent = 'Switch to Community';
                } else {
                    btn.querySelector('.btn-text').textContent = 'Switch to Admin';
                }
            }
            
            // Update Delete All Users button text based on current table
            const deleteAllBtn = document.querySelector('.btn-delete-all .btn-text');
            if (deleteAllBtn) {
                if (currentTableType === 'users') {
                    deleteAllBtn.textContent = 'Delete Admin Users';
                } else {
                    deleteAllBtn.textContent = 'Delete Community Users';
                }
            }
            
            // Show/hide Delete by Location button (only for community users)
            const deleteByLocationBtn = document.getElementById('deleteByLocationBtn');
            if (deleteByLocationBtn) {
                deleteByLocationBtn.style.display = currentTableType === 'community_users' ? 'flex' : 'none';
            }
        }

        function loadUsersTable() {
            // Hide the filter controls (control-row-2) since they're not needed for users table
            const controlRow2 = document.querySelector('.control-row-2');
            if (controlRow2) {
                controlRow2.style.display = 'none';
            }
            
            // Fetch users from database and update table
            fetch('/settings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_users_table'
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
            .then(text => {
                console.log('Raw response:', text);
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        updateTableStructure('users', data.users);
                        updateTableToggleButton();
                        currentTableType = 'users';
                    } else {
                        console.error('Failed to load users:', data.error);
                        showMessage('Failed to load users: ' + data.error, 'error');
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response text:', text);
                    showMessage('Error parsing server response', 'error');
                }
            })
            .catch(error => {
                console.error('Error loading users:', error);
                showMessage('Error loading users: ' + error.message, 'error');
            });
        }

        function loadCommunityUsersTable() {
            // Show the filter controls (control-row-2) since they're needed for community users table
            const controlRow2 = document.querySelector('.control-row-2');
            if (controlRow2) {
                controlRow2.style.display = 'block';
            }
            
            // Restore the original table structure and data
            console.log('Restoring original community users table');
            
            const tableBody = document.getElementById('usersTableBody');
            const tableHead = document.querySelector('.user-table thead tr');
            
            // Restore original headers
            tableHead.innerHTML = `
                <th>NAME</th>
                <th>EMAIL</th>
                <th>MUNICIPALITY</th>
                <th>BARANGAY</th>
                <th>SEX</th>
                <th>BIRTHDAY</th>
                <th>SCREENING DATE</th>
                <th>ACTIONS</th>
            `;
            
            // Clear the table body first
            tableBody.innerHTML = '';
            
            // Restore original community users data
            if (originalCommunityUsersData && originalCommunityUsersData.length > 0) {
                originalCommunityUsersData.forEach(user => {
                    const row = document.createElement('tr');
                    row.setAttribute('data-user-email', user.email); // Add the missing data attribute
                    row.innerHTML = `
                        <td>${user.name || 'N/A'}</td>
                        <td>${user.email}</td>
                        <td>${user.municipality || 'N/A'}</td>
                        <td>${user.barangay || 'N/A'}</td>
                        <td>${user.sex || 'N/A'}</td>
                        <td>${user.birthday || 'N/A'}</td>
                        <td>${user.screening_date || 'Not Available'}</td>
                        <td class="action-buttons">
                            <button class="btn-edit" onclick="editUser('${user.email}')" title="Edit User">
                                Edit
                            </button>
                            ${(user.status == 1 || !user.status) ? 
                                `<button class="btn-archive" onclick="archiveUser('${user.email}', 'archive')" title="Archive User">Archive</button>` :
                                `<button class="btn-unarchive" onclick="archiveUser('${user.email}', 'unarchive')" title="Unarchive User">Unarchive</button>`
                            }
                            <button class="btn-delete" onclick="deleteUser('${user.email}')" title="Delete User">
                                Delete
                            </button>
                        </td>
                    `;
                    tableBody.appendChild(row);
                });
                console.log('Restored', originalCommunityUsersData.length, 'community users');
            } else {
                // If no stored data, show empty table with message
                console.log('No stored community users data, showing empty table');
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td colspan="7" style="text-align: center; padding: 20px; color: #666;">
                        No community users found. All users may have been deleted.
                        <br><br>
                        <button onclick="loadUsers()" style="background: #007bff; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">
                            Refresh Data
                        </button>
                    </td>
                `;
                tableBody.appendChild(row);
            }
            
            // Update table toggle button and current table type
            updateTableToggleButton();
            currentTableType = 'community_users';
        }

        function updateTableStructure(tableType, users) {
            const tableBody = document.getElementById('usersTableBody');
            const tableHead = document.querySelector('.user-table thead tr');
            
            console.log('updateTableStructure called with:', tableType, users.length, 'users');
            console.log('tableBody:', tableBody);
            console.log('tableHead:', tableHead);
            
            if (!tableBody || !tableHead) {
                console.error('Table elements not found. Looking for alternative selectors...');
                // Try alternative selectors
                const altTableBody = document.querySelector('tbody');
                const altTableHead = document.querySelector('thead tr');
                
                if (!altTableBody || !altTableHead) {
                    console.error('No table elements found at all');
                    showMessage('Table elements not found. Please refresh the page.', 'error');
                    return;
                }
                
                // Use alternative elements
                altTableBody.innerHTML = '';
                altTableHead.innerHTML = '';
                
                if (tableType === 'users') {
                    // Update table headers for users table
                    altTableHead.innerHTML = `
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Email Verified</th>
                        <th>Created At</th>
                        <th>Last Login</th>
                        <th>ACTIONS</th>
                    `;
                    
                    // Update table body with users data
                    altTableBody.innerHTML = users.map(user => `
                        <tr data-user-id="${user.user_id}">
                            <td>${user.user_id}</td>
                            <td><span class="editable" data-field="username" data-id="${user.user_id}">${user.username}</span></td>
                            <td><span class="editable" data-field="email" data-id="${user.user_id}">${user.email}</span></td>
                            <td>${user.email_verified ? '✅' : '❌'}</td>
                            <td>${new Date(user.created_at).toLocaleDateString()}</td>
                            <td>${user.last_login ? new Date(user.last_login).toLocaleDateString() : 'Never'}</td>
                            <td class="action-buttons">
                                <button class="btn-edit" onclick="editUser(${user.user_id})" title="Edit User">
                                    Edit
                                </button>
                                ${user.is_active == 1 ? 
                                    `<button class="btn-archive" onclick="archiveUser(${user.user_id}, 'archive')" title="Archive User">Archive</button>` :
                                    `<button class="btn-unarchive" onclick="archiveUser(${user.user_id}, 'unarchive')" title="Unarchive User">Unarchive</button>`
                                }
                                <button class="btn-delete" onclick="deleteUser(${user.user_id})" title="Delete User">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    `).join('');
                } else {
                    // Update table headers for community_users table (existing structure)
                    altTableHead.innerHTML = `
                        <th>NAME</th>
                        <th>EMAIL</th>
                        <th>MUNICIPALITY</th>
                        <th>BARANGAY</th>
                        <th>SEX</th>
                        <th>BIRTHDAY</th>
                        <th>SCREENING DATE</th>
                        <th>ACTIONS</th>
                    `;
                    
                    // Update table body with community users data
                    altTableBody.innerHTML = users.map(user => `
                        <tr data-user-email="${user.email}">
                            <td>${user.name || 'N/A'}</td>
                            <td>${user.email}</td>
                            <td>${user.municipality || 'N/A'}</td>
                            <td>${user.barangay || 'N/A'}</td>
                            <td>${user.sex || 'N/A'}</td>
                            <td>${user.birthday || 'N/A'}</td>
                            <td>${user.screening_date && user.screening_date !== 'N/A' && user.screening_date !== '' ? 
                                (() => {
                                    try {
                                        const date = new Date(user.screening_date);
                                        return date.toISOString().split('T')[0];
                                    } catch (e) {
                                        return 'Invalid Date';
                                    }
                                })() : 'Not Available'}</td>
                            <td class="action-buttons">
                                <button class="btn-edit" onclick="editUser('${user.email}')" title="Edit User">
                                    Edit
                                </button>
                                ${(user.status === 'active' || !user.status) ? 
                                    `<button class="btn-archive" onclick="archiveUser('${user.email}', 'archive')" title="Archive User">Archive</button>` :
                                    `<button class="btn-unarchive" onclick="archiveUser('${user.email}', 'unarchive')" title="Unarchive User">Unarchive</button>`
                                }
                                <button class="btn-delete" onclick="deleteUser('${user.email}')" title="Delete User">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    `).join('');
                }
                
                // Add click handlers for editable fields in users table
                if (tableType === 'users') {
                    addEditableHandlers();
                }
                return;
            }
            
            if (tableType === 'users') {
           // Update table headers for users table
           tableHead.innerHTML = `
               <th>ID</th>
               <th>Username</th>
               <th>Email</th>
               <th>Verified</th>
               <th>Created At</th>
               <th>Last Login</th>
               <th>ACTIONS</th>
           `;
                
                // Update table body with users data
                tableBody.innerHTML = users.map(user => `
                    <tr data-user-id="${user.user_id}">
                        <td>${user.user_id}</td>
                        <td><span class="editable" data-field="username" data-id="${user.user_id}">${user.username}</span></td>
                        <td><span class="editable" data-field="email" data-id="${user.user_id}">${user.email}</span></td>
                        <td class="verified-status">
                            ${user.email_verified ? 
                                '<i class="verified-icon" style="color: #28a745; font-size: 16px;">✓</i>' : 
                                '<i class="not-verified-icon" style="color: #dc3545; font-size: 16px;">✗</i>'
                            }
                        </td>
                        <td>${new Date(user.created_at).toLocaleDateString()}</td>
                        <td>${user.last_login ? new Date(user.last_login).toLocaleDateString() : 'Never'}</td>
                        <td class="action-buttons">
                            <button class="btn-edit" onclick="editUser(${user.user_id})" title="Edit User">
                                Edit
                            </button>
                            ${user.is_active == 1 ? 
                                `<button class="btn-archive" onclick="archiveUser(${user.user_id}, 'archive')" title="Archive User">Archive</button>` :
                                `<button class="btn-unarchive" onclick="archiveUser(${user.user_id}, 'unarchive')" title="Unarchive User">Unarchive</button>`
                            }
                            <button class="btn-delete" onclick="deleteUser(${user.user_id})" title="Delete User">
                                Delete
                            </button>
                        </td>
                    </tr>
                `).join('');
            } else {
                // Update table headers for community_users table (existing structure)
                tableHead.innerHTML = `
                    <th>NAME</th>
                    <th>EMAIL</th>
                    <th>MUNICIPALITY</th>
                    <th>BARANGAY</th>
                    <th>SEX</th>
                    <th>BIRTHDAY</th>
                    <th>SCREENING DATE</th>
                    <th>ACTIONS</th>
                `;
                
                // Update table body with community users data
                tableBody.innerHTML = users.map(user => `
                    <tr data-user-email="${user.email}">
                        <td>${user.name || 'N/A'}</td>
                        <td>${user.email}</td>
                        <td>${user.municipality || 'N/A'}</td>
                        <td>${user.barangay || 'N/A'}</td>
                        <td>${user.sex || 'N/A'}</td>
                        <td>${user.birthday || 'N/A'}</td>
                        <td>${user.screening_date && user.screening_date !== 'N/A' && user.screening_date !== '' ? 
                            (() => {
                                try {
                                    const date = new Date(user.screening_date);
                                    return date.toISOString().split('T')[0];
                                } catch (e) {
                                    return 'Invalid Date';
                                }
                            })() : 'Not Available'}</td>
                        <td class="action-buttons">
                            <button class="btn-edit" onclick="editUser('${user.email}')" title="Edit User">
                                Edit
                            </button>
                            ${(user.status == 1 || !user.status) ? 
                                `<button class="btn-archive" onclick="archiveUser('${user.email}', 'archive')" title="Archive User">Archive</button>` :
                                `<button class="btn-unarchive" onclick="archiveUser('${user.email}', 'unarchive')" title="Unarchive User">Unarchive</button>`
                            }
                            <button class="btn-delete" onclick="deleteUser('${user.email}')" title="Delete User">
                                Delete
                            </button>
                        </td>
                    </tr>
                `).join('');
            }
            
            // Add click handlers for editable fields in users table
            if (tableType === 'users') {
                addEditableHandlers();
            }
        }

        function addEditableHandlers() {
            document.querySelectorAll('.editable').forEach(element => {
                element.addEventListener('click', function() {
                    const field = this.dataset.field;
                    const id = this.dataset.id;
                    const currentValue = this.textContent;
                    
                    if (field === 'username' || field === 'email') {
                        const newValue = prompt(`Edit ${field}:`, currentValue);
                        if (newValue && newValue !== currentValue) {
                            updateUserField(id, field, newValue);
                        }
                    }
                });
            });
        }

        function updateUserField(userId, field, newValue) {
            fetch('/settings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_user_field&user_id=${userId}&field=${field}&value=${encodeURIComponent(newValue)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the display
                    const element = document.querySelector(`[data-field="${field}"][data-id="${userId}"]`);
                    if (element) {
                        element.textContent = newValue;
                    }
                    showMessage('User updated successfully!', 'success');
                } else {
                    showMessage('Failed to update user: ' + data.error, 'error');
                }
            })
            .catch(error => {
                console.error('Error updating user:', error);
                showMessage('Error updating user', 'error');
            });
        }

        function resetUserForm() {
            document.getElementById('userForm').reset();
        }

        function showMessage(message, type) {
            // Create or find message container
            let messageContainer = document.getElementById('messageContainer');
            if (!messageContainer) {
                messageContainer = document.createElement('div');
                messageContainer.id = 'messageContainer';
                messageContainer.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 10000;
                    max-width: 400px;
                `;
                document.body.appendChild(messageContainer);
            }
            
            const messageDiv = document.createElement('div');
            messageDiv.style.cssText = `
                padding: 15px;
                margin-bottom: 10px;
                border-radius: 8px;
                font-weight: 500;
                animation: slideInRight 0.3s ease-out;
            `;
            
            if (type === 'error') {
                messageDiv.style.backgroundColor = 'rgba(207, 134, 134, 0.1)';
                messageDiv.style.color = 'var(--color-danger)';
                messageDiv.style.border = '1px solid rgba(207, 134, 134, 0.3)';
            } else if (type === 'success') {
                messageDiv.style.backgroundColor = 'rgba(161, 180, 84, 0.1)';
                messageDiv.style.color = 'var(--color-highlight)';
                messageDiv.style.border = '1px solid rgba(161, 180, 84, 0.3)';
            } else {
                messageDiv.style.backgroundColor = 'rgba(66, 133, 244, 0.1)';
                messageDiv.style.color = '#4285F4';
                messageDiv.style.border = '1px solid rgba(66, 133, 244, 0.3)';
            }
            
            messageDiv.textContent = message;
            messageContainer.appendChild(messageDiv);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.parentNode.removeChild(messageDiv);
                }
            }, 5000);
        }

        function closeUserImportModal() {
            const modal = document.getElementById('userImportModal');
            if (modal) {
                modal.style.display = 'none';
                resetUserForm();
            }
        }

        // Show appropriate add user modal based on current table type
        function showAddUserModal() {
            if (currentTableType === 'users') {
                showUserImportModal();
            } else {
                showAddCommunityUserModal();
            }
        }

        // Add Community User Modal Functions
        function showAddCommunityUserModal() {
            console.log('Showing Add Community User Modal');
            
            const modal = document.getElementById('addCommunityUserModal');
            if (!modal) {
                console.error('addCommunityUserModal not found');
                alert('Add Community User modal not found. Please refresh the page.');
                return;
            }
            
            // Reset form
            document.getElementById('addCommunityUserForm').reset();
            
            // Set default password
            document.getElementById('addPassword').value = 'password123';
            
            // Clear any previous error messages
            const emailErrorElement = document.getElementById('addEmailError');
            if (emailErrorElement) {
                emailErrorElement.textContent = '';
            }
            
            // Initialize municipality dropdown
            initializeAddMunicipalityDropdown();
            
            // Hide pregnancy field initially
            document.getElementById('addPregnancyGroup').style.display = 'none';
            
            // Show modal
            modal.style.display = 'block';
        }

        function closeAddCommunityUserModal() {
            const modal = document.getElementById('addCommunityUserModal');
            if (modal) {
                modal.style.display = 'none';
                document.getElementById('addCommunityUserForm').reset();
            }
        }

        function initializeAddMunicipalityDropdown() {
            const municipalitySelect = document.getElementById('addMunicipality');
            if (!municipalitySelect) return;
            
            // Clear existing options except the first one
            municipalitySelect.innerHTML = '<option value="">Select Municipality</option>';
            
            // Add municipality options
            Object.keys(municipalities).forEach(municipality => {
                const option = document.createElement('option');
                option.value = municipality;
                option.textContent = municipality;
                municipalitySelect.appendChild(option);
            });
        }

        function updateAddBarangayOptions() {
            const municipalitySelect = document.getElementById('addMunicipality');
            const barangaySelect = document.getElementById('addBarangay');
            
            if (!municipalitySelect || !barangaySelect) return;
            
            const selectedMunicipality = municipalitySelect.value;
            
            // Clear existing options except the first one
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            
            if (selectedMunicipality && municipalities[selectedMunicipality]) {
                municipalities[selectedMunicipality].forEach(barangay => {
                    const option = document.createElement('option');
                    option.value = barangay;
                    option.textContent = barangay;
                    barangaySelect.appendChild(option);
                });
            }
        }

        function toggleAddPregnancyField() {
            const sexSelect = document.getElementById('addSex');
            const pregnancyGroup = document.getElementById('addPregnancyGroup');
            
            if (!sexSelect || !pregnancyGroup) return;
            
            if (sexSelect.value === 'Female') {
                pregnancyGroup.style.display = 'block';
            } else {
                pregnancyGroup.style.display = 'none';
                document.getElementById('addPregnancy').value = 'No';
            }
        }

        function calculateAddAge() {
            const birthdayInput = document.getElementById('addBirthday');
            const ageDisplay = document.getElementById('addAgeDisplay');
            
            if (!birthdayInput || !ageDisplay) return;
            
            const birthday = new Date(birthdayInput.value);
            const today = new Date();
            const age = today.getFullYear() - birthday.getFullYear();
            const monthDiff = today.getMonth() - birthday.getMonth();
            
            let calculatedAge = age;
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthday.getDate())) {
                calculatedAge--;
            }
            
            if (birthdayInput.value && calculatedAge >= 0) {
                ageDisplay.textContent = `Age: ${calculatedAge} years`;
            } else {
                ageDisplay.textContent = '';
            }
        }

        function validateAddEmail() {
            const email = document.getElementById('addEmail').value.trim();
            const emailError = document.getElementById('addEmailError');
            const emailInput = document.getElementById('addEmail');
            
            // Clear previous error
            if (emailError) {
                emailError.textContent = '';
            }
            if (emailInput) {
                emailInput.style.borderColor = '';
            }
            
            if (!email) {
                if (emailError) {
                    emailError.textContent = 'Email is required';
                }
                if (emailInput) {
                    emailInput.style.borderColor = 'red';
                }
                return false;
            }
            
            // Basic email format validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                if (emailError) {
                    emailError.textContent = 'Please enter a valid email address';
                }
                if (emailInput) {
                    emailInput.style.borderColor = 'red';
                }
                return false;
            }
            
            return true;
        }

        function addCommunityUser() {
            console.log('Adding community user...');
            
            // Validate form
            const name = document.getElementById('addName').value.trim();
            const email = document.getElementById('addEmail').value.trim();
            const password = document.getElementById('addPassword').value.trim();
            const municipality = document.getElementById('addMunicipality').value;
            const barangay = document.getElementById('addBarangay').value;
            const sex = document.getElementById('addSex').value;
            const birthday = document.getElementById('addBirthday').value;
            const weight = document.getElementById('addWeight').value;
            const height = document.getElementById('addHeight').value;
            const isPregnant = document.getElementById('addPregnancy').value;
            
            // Basic validation
            if (!name) {
                alert('Please enter a name');
                return;
            }
            
            if (!email) {
                alert('Please enter an email');
                return;
            }
            
            if (!validateAddEmail()) {
                return;
            }
            
            if (!password) {
                alert('Please enter a password');
                return;
            }
            
            if (password.length < 6) {
                alert('Password must be at least 6 characters long');
                return;
            }
            
            if (!municipality) {
                alert('Please select a municipality');
                return;
            }
            
            if (!barangay) {
                alert('Please select a barangay');
                return;
            }
            
            if (!sex) {
                alert('Please select sex');
                return;
            }
            
            if (!birthday) {
                alert('Please select a birthday');
                return;
            }
            
            if (!weight || isNaN(weight) || parseFloat(weight) <= 0 || parseFloat(weight) > 1000) {
                alert('Please enter a valid weight between 0.1 and 1000 kg');
                return;
            }
            
            if (!height || isNaN(height) || parseFloat(height) <= 0 || parseFloat(height) > 300) {
                alert('Please enter a valid height between 1 and 300 cm');
                return;
            }
            
            // Prepare data
            const userData = {
                name: name,
                email: email,
                password: password,
                municipality: municipality,
                barangay: barangay,
                sex: sex,
                birthday: birthday,
                weight: parseFloat(weight),
                height: parseFloat(height),
                is_pregnant: isPregnant === 'Yes' ? 1 : 0
            };
            
            console.log('Submitting community user data:', userData);
            
            // Submit to API
            fetch('api/DatabaseAPI.php?action=add_community_user', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(userData)
            })
            .then(response => response.json())
            .then(data => {
                console.log('Add community user response:', data);
                if (data.success) {
                    showMessage(`Community user "${name}" (${email}) added successfully!`, 'success');
                    closeAddCommunityUserModal();
                    // Reload the community users table
                    loadCommunityUsersTable();
                } else {
                    showMessage('Failed to add community user: ' + (data.message || data.error), 'error');
                }
            })
            .catch(error => {
                console.error('Error adding community user:', error);
                showMessage('Error adding community user. Please try again.', 'error');
            });
        }

        function addNewUser() {
            const email = document.getElementById('email').value.trim();
            const municipality = document.getElementById('municipality').value.trim();
            
            if (!email || !municipality) {
                showMessage('Please fill in all fields', 'error');
                return;
            }
            
            // Show loading
            showMessage('Creating admin user...', 'info');
            
            fetch('/settings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add_user&email=${encodeURIComponent(email)}&municipality=${encodeURIComponent(municipality)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message || 'Admin user created successfully!', 'success');
                    closeUserImportModal();
                    // Reload the appropriate table
                    if (currentTableType === 'users') {
                        loadUsersTable();
                    } else {
                        loadCommunityUsersTable();
                    }
                } else {
                    showMessage('Failed to create admin user: ' + data.error, 'error');
                }
            })
            .catch(error => {
                console.error('Error adding user:', error);
                showMessage('Error adding user', 'error');
            });
        }

        function closeCSVImportModal() {
            const modal = document.getElementById('csvImportModal');
            if (modal) {
                modal.style.display = 'none';
                resetCSVForm();
            }
        }

        function resetCSVForm() {
            document.getElementById('csvFile').value = '';
            document.getElementById('csvPreview').style.display = 'none';
            document.getElementById('importCSVBtn').disabled = true;
            document.getElementById('cancelBtn').style.display = 'none';
            hideCSVStatus();
        }

        function handleDragOver(e) {
            e.preventDefault();
            e.stopPropagation();
            document.getElementById('uploadArea').classList.add('dragover');
        }

        function handleDragLeave(e) {
            e.preventDefault();
            e.stopPropagation();
            document.getElementById('uploadArea').classList.remove('dragover');
        }

        function handleDrop(e) {
            e.preventDefault();
            e.stopPropagation();
            document.getElementById('uploadArea').classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const file = files[0];
                if (file.type === 'text/csv' || file.name.endsWith('.csv')) {
                    document.getElementById('csvFile').files = files;
                    handleFileSelect(document.getElementById('csvFile'));
                } else {
                    showCSVStatus('error', 'Please upload a CSV file.');
                }
            }
        }

        function handleFileSelect(input) {
            const file = input.files[0];
            if (file && (file.type === 'text/csv' || file.name.endsWith('.csv'))) {
                previewCSV(file);
            } else {
                showCSVStatus('error', 'Please select a valid CSV file.');
            }
        }

        function previewCSV(file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const csv = e.target.result;
                const lines = csv.split('\n').filter(line => line.trim());
                
                    if (lines.length < 2) {
                    showCSVStatus('error', 'CSV file must contain at least a header row and one data row.');
                        return;
                    }

                const headers = lines[0].split(',').map(h => h.replace(/"/g, '').trim());
                const previewRows = lines.slice(1, 6);
                
                let tableHTML = '<h4>📋 Preview (First 5 rows)</h4>';
                tableHTML += '<div style="overflow-x: auto;"><table class="csv-preview-table">';
            tableHTML += '<thead><tr>';
            headers.forEach(header => {
                tableHTML += `<th>${header}</th>`;
            });
                tableHTML += '</tr></thead><tbody>';
                
                previewRows.forEach(row => {
                    const cells = row.split(',').map(cell => cell.replace(/"/g, '').trim());
                    tableHTML += '<tr>';
                    cells.forEach(cell => {
                        tableHTML += `<td>${cell}</td>`;
                    });
                    tableHTML += '</tr>';
                });
                
                tableHTML += '</tbody></table></div>';
                
                document.getElementById('csvPreview').innerHTML = tableHTML;
                document.getElementById('csvPreview').style.display = 'block';
                document.getElementById('importCSVBtn').disabled = false;
                document.getElementById('cancelBtn').style.display = 'inline-block';
                
                showCSVStatus('success', `CSV loaded successfully! ${lines.length - 1} rows ready for import.`);
            };
            
            reader.readAsText(file);
        }

        function processCSVImport() {
            showCSVStatus('info', 'CSV import functionality will be implemented in the backend.');
        }

        function showCSVStatus(type, message) {
            const statusDiv = document.getElementById('csvStatusMessage');
                statusDiv.style.display = 'block';
            statusDiv.className = `csv-status ${type}`;
            statusDiv.textContent = message;
                
                if (type === 'success') {
                    statusDiv.style.backgroundColor = 'rgba(161, 180, 84, 0.2)';
                    statusDiv.style.color = 'var(--color-highlight)';
                statusDiv.style.border = '1px solid var(--color-highlight)';
            } else if (type === 'info') {
                statusDiv.style.backgroundColor = 'rgba(102, 187, 106, 0.2)';
                statusDiv.style.color = 'var(--color-highlight)';
                statusDiv.style.border = '1px solid var(--color-highlight)';
                } else {
                statusDiv.style.backgroundColor = 'rgba(233, 141, 124, 0.2)';
                statusDiv.style.color = 'var(--color-danger)';
                statusDiv.style.border = '1px solid var(--color-danger)';
            }
        }

        function hideCSVStatus() {
            const statusDiv = document.getElementById('csvStatusMessage');
                        statusDiv.style.display = 'none';
        }

        function cancelUpload() {
            resetCSVForm();
        }

        // User management functions
        function editUser(identifier) {
            console.log('editUser called with identifier:', identifier, 'currentTableType:', currentTableType);
            
            if (!identifier || identifier === '') {
                alert('Invalid user identifier');
                return;
            }
            
            if (currentTableType === 'users') {
                // For users table, identifier is user_id
                const userRow = document.querySelector(`tr[data-user-id="${identifier}"]`);
                console.log('Found user row:', userRow);
                if (!userRow) {
                    alert('User data not found');
                    return;
                }
                
                // Extract user data from table cells for users table
                const userData = {
                    user_id: identifier,
                    username: userRow.cells[1].textContent.trim(),
                    email: userRow.cells[2].textContent.trim()
                };
                console.log('Extracted user data:', userData);
                
                // Show edit modal for users table
                showEditUsersTableModal(userData);
            } else {
                // For community_users table, identifier is email
                const userRow = document.querySelector(`tr[data-user-email="${identifier}"]`);
                console.log('Found community user row:', userRow);
                if (!userRow) {
                    alert('User data not found');
                    return;
                }
                
                // Extract user data from table cells for community_users table
                const userData = {
                    email: identifier,
                    name: userRow.cells[0].textContent.trim(),
                    municipality: userRow.cells[2].textContent.trim(),
                    barangay: userRow.cells[3].textContent.trim(),
                    sex: userRow.cells[4].textContent.trim(),
                    birthday: userRow.cells[5].textContent.trim()
                };
                console.log('Extracted community user data:', userData);
                
                // Show edit modal for community_users table with complete data
                showEditUserModal(userData);
            }
        }

        function deleteUser(identifier) {
            if (!identifier || identifier === '') {
                alert('Invalid user identifier');
                return;
            }

            // Show password confirmation modal instead of simple confirm
            const userType = currentTableType === 'users' ? 'admin user' : 'community user';
            const confirmMessage = `You are about to permanently delete this ${userType}. This action cannot be undone. Please enter your admin password to confirm.`;
            
            showPasswordConfirmModal(performDeleteUser, identifier, confirmMessage);
        }

        function performDeleteUser(identifier) {
            console.log('Performing delete for user:', identifier);

            // Prepare request data based on table type
            let requestData = {};
            let endpoint = '';
            
            if (currentTableType === 'users') {
                // For users table, use user_id
                requestData = { user_id: identifier };
                endpoint = '/settings.php';
            } else {
                // For community_users table, use email
                requestData = { user_email: identifier };
                endpoint = '/api/delete_user.php';
            }

            // Send delete request to server
            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': currentTableType === 'users' ? 'application/x-www-form-urlencoded' : 'application/json',
                },
                body: currentTableType === 'users' ? 
                    `action=delete_user&${Object.keys(requestData).map(key => `${key}=${encodeURIComponent(requestData[key])}`).join('&')}` :
                    JSON.stringify(requestData)
            })
            .then(response => currentTableType === 'users' ? response.text().then(text => {
                try { return JSON.parse(text); } catch { return {success: false, error: 'Invalid response'}; }
            }) : response.json())
            .then(data => {
                if (data.success) {
                    // Remove the row from the table
                    let row;
                    if (currentTableType === 'users') {
                        row = document.querySelector(`tr[data-user-id="${identifier}"]`);
                    } else {
                        row = document.querySelector(`tr[data-user-email="${identifier}"]`);
                    }
                    
                    if (row) {
                        row.remove();
                        showNotification('User deleted successfully!', 'success');
                    }
                } else {
                    showNotification('Error deleting user: ' + (data.message || data.error || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error deleting user: ' + error.message, 'error');
            });
        }

        function archiveUser(identifier, action = 'archive') {
            console.log('archiveUser called with:', identifier, action);
            if (!identifier || identifier === '') {
                alert('Invalid user identifier');
                return;
            }

            // Confirm action
            const actionText = action === 'archive' ? 'archive' : 'unarchive';
            const confirmText = action === 'archive' ? 
                'Are you sure you want to archive this user? They will be disabled but not deleted.' :
                'Are you sure you want to unarchive this user? They will be enabled again.';
            
            if (!confirm(confirmText)) {
                return;
            }

            // Show loading state
            const archiveBtn = event.target;
            const originalText = archiveBtn.innerHTML;
            archiveBtn.innerHTML = '⏳';
            archiveBtn.disabled = true;

            // Prepare request data based on table type
            let requestData = {};
            let endpoint = '';
            
            if (currentTableType === 'users') {
                // For admin users table
                requestData = {
                    action: 'archive_user',
                    user_id: identifier,
                    archive_action: action
                };
                endpoint = '/settings.php';
            } else {
                // For community users table
                requestData = {
                    action: 'archive_community_user',
                    user_email: identifier,
                    archive_action: action
                };
                endpoint = '/settings.php';
            }

            // Send archive request to server
            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: Object.keys(requestData).map(key => `${key}=${encodeURIComponent(requestData[key])}`).join('&')
            })
            .then(response => response.text().then(text => {
                try { return JSON.parse(text); } catch { return {success: false, error: 'Invalid response'}; }
            }))
            .then(data => {
                if (data.success) {
                    // Update the button in the row
                    let row;
                    if (currentTableType === 'users') {
                        row = document.querySelector(`tr[data-user-id="${identifier}"]`);
                    } else {
                        row = document.querySelector(`tr[data-user-email="${identifier}"]`);
                    }
                    
                    if (row) {
                        const actionCell = row.querySelector('.action-buttons');
                        if (actionCell) {
                            // Update the archive button based on new status
                            if (action === 'archive') {
                                // Replace archive button with unarchive button
                                const archiveBtn = actionCell.querySelector('.btn-archive');
                                if (archiveBtn) {
                                    archiveBtn.className = 'btn-unarchive';
                                    archiveBtn.innerHTML = 'Unarchive';
                                    archiveBtn.setAttribute('onclick', `archiveUser('${identifier}', 'unarchive')`);
                                    archiveBtn.setAttribute('title', 'Unarchive User');
                                }
                            } else {
                                // Replace unarchive button with archive button
                                const unarchiveBtn = actionCell.querySelector('.btn-unarchive');
                                if (unarchiveBtn) {
                                    unarchiveBtn.className = 'btn-archive';
                                    unarchiveBtn.innerHTML = 'Archive';
                                    unarchiveBtn.setAttribute('onclick', `archiveUser('${identifier}', 'archive')`);
                                    unarchiveBtn.setAttribute('title', 'Archive User');
                                }
                            }
                        }
                    }
                    
                    const successMessage = action === 'archive' ? 'User archived successfully!' : 'User unarchived successfully!';
                    showNotification(successMessage, 'success');
                } else {
                    showNotification('Error: ' + (data.message || data.error || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error: ' + error.message, 'error');
            })
            .finally(() => {
                // Restore button state
                archiveBtn.innerHTML = originalText;
                archiveBtn.disabled = false;
            });
        }

        // Password confirmation modal functions
        let pendingDeleteAction = null;
        let pendingDeleteData = null;

        function showPasswordConfirmModal(deleteAction, deleteData, confirmMessage) {
            console.log('Setting up password modal with:', {deleteAction, deleteData, confirmMessage});
            pendingDeleteAction = deleteAction;
            pendingDeleteData = deleteData;
            
            console.log('Pending actions set:', {pendingDeleteAction, pendingDeleteData});
            
            document.getElementById('deleteConfirmMessage').textContent = confirmMessage || 'Please enter your admin password to confirm this deletion.';
            document.getElementById('confirmPassword').value = '';
            document.getElementById('passwordError').style.display = 'none';
            document.getElementById('passwordConfirmModal').style.display = 'block';
            
            // Focus on password input
            setTimeout(() => {
                document.getElementById('confirmPassword').focus();
            }, 100);
        }

        function closePasswordConfirmModal() {
            document.getElementById('passwordConfirmModal').style.display = 'none';
            pendingDeleteAction = null;
            pendingDeleteData = null;
            document.getElementById('confirmPassword').value = '';
            document.getElementById('passwordError').style.display = 'none';
        }

        function confirmPasswordAndDelete() {
            const password = document.getElementById('confirmPassword').value;
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            const passwordError = document.getElementById('passwordError');
            
            if (!password) {
                passwordError.textContent = 'Please enter your password';
                passwordError.style.display = 'block';
                return;
            }
            
            // Show loading state
            const originalText = confirmBtn.innerHTML;
            confirmBtn.innerHTML = '<span class="btn-icon">⏳</span><span class="btn-text">Verifying...</span>';
            confirmBtn.disabled = true;
            passwordError.style.display = 'none';
            
            // Verify password first
            fetch('/settings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=verify_password&password=${encodeURIComponent(password)}`
            })
            .then(response => response.text().then(text => {
                try { return JSON.parse(text); } catch { return {success: false, error: 'Invalid response'}; }
            }))
            .then(data => {
                console.log('Password verification response:', data);
                if (data.success) {
                    // Password verified, proceed with deletion
                    console.log('Password verified! Calling delete function:', pendingDeleteAction, 'with data:', pendingDeleteData);
                    
                    // Store the action and data before closing modal
                    const actionToExecute = pendingDeleteAction;
                    const dataToPass = pendingDeleteData;
                    
                    closePasswordConfirmModal();
                    
                    if (actionToExecute && dataToPass) {
                        console.log('Executing delete action...');
                        actionToExecute(dataToPass);
                    } else {
                        console.error('Missing delete action or data:', {actionToExecute, dataToPass});
                    }
                } else {
                    // Password verification failed
                    console.log('Password verification failed:', data.error);
                    passwordError.textContent = data.error || 'Incorrect password';
                    passwordError.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Password verification error:', error);
                passwordError.textContent = 'Error verifying password. Please try again.';
                passwordError.style.display = 'block';
            })
            .finally(() => {
                // Restore button state
                confirmBtn.innerHTML = originalText;
                confirmBtn.disabled = false;
            });
        }

        // Handle Enter key in password field
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('confirmPassword');
            if (passwordInput) {
                passwordInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        confirmPasswordAndDelete();
                    }
                });
            }
        });

        function deleteAllUsers() {
            // Get total number of users
            const userRows = document.querySelectorAll('.user-table tbody tr');
            const userCount = userRows.length;
            
            if (userCount === 0) {
                showNotification('No users to delete!', 'info');
                return;
            }

            // Show password confirmation modal instead of simple confirm
            const tableTypeName = currentTableType === 'users' ? 'admin users' : 'community users';
            const confirmMessage = `You are about to permanently delete ALL ${userCount} ${tableTypeName} from the database. This action cannot be undone and will remove all their data. Please enter your admin password to confirm this critical operation.`;
            
            showPasswordConfirmModal(performDeleteAllUsers, userCount, confirmMessage);
        }

        function performDeleteAllUsers(userCount) {
            const tableTypeName = currentTableType === 'users' ? 'admin users' : 'community users';
            console.log('Performing delete all for', userCount, tableTypeName);

            // Determine endpoint and request data based on current table type
            let endpoint, requestData, headers;
            
            if (currentTableType === 'users') {
                // Delete all admin users
                endpoint = '/settings.php';
                requestData = 'action=delete_all_admin_users&confirm=true';
                headers = {
                    'Content-Type': 'application/x-www-form-urlencoded',
                };
            } else {
                // Delete all community users
                endpoint = '/api/delete_all_users.php';
                requestData = JSON.stringify({
                    confirm: true,
                    table_type: 'community_users'
                });
                headers = {
                    'Content-Type': 'application/json',
                };
            }

            // Send delete all request to server
            fetch(endpoint, {
                method: 'POST',
                headers: headers,
                body: requestData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove all rows from the table
                    const tbody = document.querySelector('.user-table tbody');
                    tbody.innerHTML = '';
                    
                    // Show no users message
                    const noUsersMessage = document.getElementById('no-users-message');
                    if (noUsersMessage) {
                        noUsersMessage.style.display = 'block';
                    }
                    
                    showNotification(`Successfully deleted all ${data.deleted_count || userCount} users!`, 'success');
                } else {
                    showNotification('Error deleting all users: ' + (data.message || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error deleting all users: ' + error.message, 'error');
            });
        }

        function showNotification(message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 8px;
                color: white;
                font-weight: 600;
                z-index: 10000;
                max-width: 300px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
                transform: translateX(100%);
                transition: transform 0.3s ease;
            `;

            // Set background color based on type
            if (type === 'success') {
                notification.style.backgroundColor = '#4CAF50';
            } else if (type === 'error') {
                notification.style.backgroundColor = '#F44336';
            } else {
                notification.style.backgroundColor = '#2196F3';
            }

            notification.textContent = message;
            document.body.appendChild(notification);

            // Animate in
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);

            // Auto remove after 3 seconds
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }

        // Simple Edit User Modal Function (for community users)
        function showEditUserModalSimple(userData) {
            console.log('showEditUserModalSimple called with userData:', userData);
            
            // Check if modal exists
            const modal = document.getElementById('editUserModal');
            if (!modal) {
                console.error('editUserModal not found');
                alert('Edit modal not found. Please refresh the page.');
                return;
            }
            
            // Populate form with the basic user data from table
            document.getElementById('modalEditName').value = userData.name || '';
            document.getElementById('modalEditEmail').value = userData.email || '';
            document.getElementById('editSex').value = userData.sex || '';
            document.getElementById('editBirthday').value = userData.birthday || '';
            
            // Set default values for weight and height (will be fetched from database on save)
            document.getElementById('editWeight').value = '';
            document.getElementById('editHeight').value = '';
            
            // Store original email for comparison
            document.getElementById('modalEditEmail').setAttribute('data-original-email', userData.email);
            
            // Initialize municipality and barangay dropdowns
            initializeMunicipalityDropdown();
            document.getElementById('editMunicipality').value = userData.municipality || '';
            updateBarangayOptions();
            
            // Set barangay after options are loaded
            setTimeout(() => {
                document.getElementById('editBarangay').value = userData.barangay || '';
            }, 100);
            
            // Calculate and display age
            calculateAge();
            
            // Toggle pregnancy field based on sex
            togglePregnancyField();
            
            // Set pregnancy status to default
            document.getElementById('editPregnancy').value = 'No';
            
            // Show the modal
            console.log('Showing edit modal for user:', userData);
            modal.style.display = 'block';
            
            // Ensure modal is visible and centered
            setTimeout(() => {
                const modal = document.getElementById('editUserModal');
                const modalContent = modal.querySelector('.modal-content');
                const saveButton = modal.querySelector('.btn-primary');
                
                if (modal.style.display === 'block') {
                    console.log('Modal is visible');
                    console.log('Modal content:', modalContent);
                    console.log('Save button:', saveButton);
                    
                    // Force center the modal
                    if (modalContent) {
                        modalContent.style.position = 'fixed';
                        modalContent.style.top = '50%';
                        modalContent.style.left = '50%';
                        modalContent.style.transform = 'translate(-50%, -50%)';
                        modalContent.style.zIndex = '1001';
                    }
                    
                    // Ensure save button is visible
                    if (saveButton) {
                        console.log('Save button found and visible');
                        saveButton.style.display = 'block';
                        saveButton.style.visibility = 'visible';
                    } else {
                        console.log('Save button not found!');
                    }
                } else {
                    console.log('Modal display issue');
                }
            }, 100);
        }

        // Edit User Modal Functions
        function showEditUserModal(userData) {
            console.log('showEditUserModal called with userData:', userData);
            
            // Check if modal exists
            const modal = document.getElementById('editUserModal');
            if (!modal) {
                console.error('editUserModal not found');
                alert('Edit modal not found. Please refresh the page.');
                return;
            }
            
            // First, fetch complete user data from database
            fetchUserData(userData.email, (completeUserData) => {
                console.log('fetchUserData callback received:', completeUserData);
                
                // Use completeUserData if available, otherwise fall back to userData
                const finalUserData = Object.keys(completeUserData).length > 0 ? completeUserData : userData;
                console.log('Using final user data:', finalUserData);
                
                // Populate form with complete user data
                document.getElementById('modalEditName').value = finalUserData.name || '';
                document.getElementById('modalEditEmail').value = finalUserData.email || '';
                document.getElementById('editSex').value = finalUserData.sex || '';
                document.getElementById('editBirthday').value = finalUserData.birthday || '';
                document.getElementById('editWeight').value = finalUserData.weight || '';
                document.getElementById('editHeight').value = finalUserData.height || '';
                
                // Set pregnancy status if available
                if (finalUserData.is_pregnant) {
                    document.getElementById('editPregnancy').value = finalUserData.is_pregnant;
                }
                
                // Store original email for comparison
                const originalEmail = finalUserData.email || userData.email;
                document.getElementById('modalEditEmail').setAttribute('data-original-email', originalEmail);
                
                // Initialize municipality and barangay dropdowns
                initializeMunicipalityDropdown();
                document.getElementById('editMunicipality').value = finalUserData.municipality || '';
                updateBarangayOptions();
                
                // Set barangay after options are loaded
                setTimeout(() => {
                    document.getElementById('editBarangay').value = finalUserData.barangay || '';
                }, 100);
                
                // Calculate and display age
                calculateAge();
                
                // Toggle pregnancy field based on sex
                togglePregnancyField();
                
                // Set pregnancy status if available
                if (finalUserData.is_pregnant !== undefined) {
                    document.getElementById('editPregnancy').value = finalUserData.is_pregnant ? 'Yes' : 'No';
                }
                
                // Clear any previous error messages
                const emailErrorElement = document.getElementById('modalEmailError');
                if (emailErrorElement) {
                    emailErrorElement.textContent = '';
                }
                const editEmailElement = document.getElementById('modalEditEmail');
                if (editEmailElement) {
                    editEmailElement.style.borderColor = '';
                }
                
                // Show modal
                console.log('Showing edit modal for user:', finalUserData);
                modal.style.display = 'block';
                
                // Ensure modal is visible and centered
                setTimeout(() => {
                    const modal = document.getElementById('editUserModal');
                    const modalContent = modal.querySelector('.modal-content');
                    const saveButton = modal.querySelector('.btn-primary');
                    
                    if (modal.style.display === 'block') {
                        console.log('Modal is visible');
                        console.log('Modal content:', modalContent);
                        console.log('Save button:', saveButton);
                        
                        // Force center the modal
                        if (modalContent) {
                            modalContent.style.position = 'fixed';
                            modalContent.style.top = '50%';
                            modalContent.style.left = '50%';
                            modalContent.style.transform = 'translate(-50%, -50%)';
                            modalContent.style.zIndex = '1001';
                        }
                        
                        // Ensure save button is visible
                        if (saveButton) {
                            console.log('Save button found and visible');
                            saveButton.style.display = 'block';
                            saveButton.style.visibility = 'visible';
                        } else {
                            console.log('Save button not found!');
                        }
                    } else {
                        console.log('Modal display issue');
                    }
                }, 100);
            });
        }

        function fetchUserData(email, callback) {
            console.log('fetchUserData called with email:', email);
            
            fetch('api/DatabaseAPI.php?action=get_community_user_data', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ email: email })
            })
            .then(response => {
                console.log('fetchUserData response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('fetchUserData response data:', data);
                if (data.success && data.user) {
                    callback(data.user);
                } else {
                    console.warn('API returned unsuccessful response, using fallback data');
                    // Fallback to basic data if API fails
                    callback({});
                }
            })
            .catch(error => {
                console.error('Error fetching user data:', error);
                // Fallback to basic data if API fails
                callback({});
            });
        }

        function closeEditUserModal() {
            document.getElementById('editUserModal').style.display = 'none';
            document.getElementById('editUserForm').reset();
        }
        
        // Debug function to test modal display
        function testEditModal() {
            console.log('Testing edit modal...');
            const testData = {
                email: 'test@example.com',
                name: 'Test User',
                municipality: 'CITY OF BALANGA',
                barangay: 'San Jose',
                sex: 'Female',
                birthday: '1990-01-01'
            };
            showEditUserModal(testData);
        }
        
        // Debug function to test barangay functionality
        function testBarangayOptions() {
            console.log('Testing barangay options...');
            const municipalitySelect = document.getElementById('editMunicipality');
            const barangaySelect = document.getElementById('editBarangay');
            
            console.log('Municipality select:', municipalitySelect);
            console.log('Barangay select:', barangaySelect);
            
            if (municipalitySelect && barangaySelect) {
                // Set a test municipality
                municipalitySelect.value = 'CITY OF BALANGA';
                updateBarangayOptions();
                console.log('Barangay options after update:', barangaySelect.options.length);
            } else {
                console.error('Required elements not found');
            }
        }

        // Functions for Users Table Edit Modal
        function showEditUsersTableModal(userData) {
            console.log('showEditUsersTableModal called with:', userData);
            
            // Check if modal exists
            const modal = document.getElementById('editUsersTableModal');
            if (!modal) {
                console.error('editUsersTableModal not found');
                alert('Edit modal not found. Please refresh the page.');
                return;
            }
            
            // Populate the form with user data
            document.getElementById('editUserId').value = userData.user_id;
            document.getElementById('editUsername').value = userData.username;
            document.getElementById('editUserEmail').value = userData.email;
            document.getElementById('editPassword').value = ''; // Clear password field
            
            // Clear any previous error messages
            document.getElementById('userEmailError').textContent = '';
            
            // Show the modal
            modal.style.display = 'block';
            console.log('Modal should be visible now');
        }

        function closeEditUsersTableModal() {
            document.getElementById('editUsersTableModal').style.display = 'none';
            document.getElementById('editUsersTableForm').reset();
            document.getElementById('userEmailError').textContent = '';
        }

        // Delete by Location Modal Functions
        function showDeleteByLocationModal() {
            console.log('Showing Delete by Location Modal');
            
            // Initialize municipality dropdown
            initializeDeleteMunicipalityDropdown();
            
            const modal = document.getElementById('deleteByLocationModal');
            if (modal) {
                modal.style.display = 'block';
            }
        }

        function closeDeleteByLocationModal() {
            const modal = document.getElementById('deleteByLocationModal');
            if (modal) {
                modal.style.display = 'none';
                document.getElementById('deleteByLocationForm').reset();
            }
        }

        function initializeDeleteMunicipalityDropdown() {
            const municipalities = [
                "ABUCAY", "BAGAC", "CITY OF BALANGA", "DINALUPIHAN", "HERMOSA", 
                "LIMAY", "MARIVELES", "MORONG", "ORANI", "ORION", "PILAR", "SAMAL"
            ];
            
            const select = document.getElementById('deleteMunicipality');
            select.innerHTML = '<option value="">Select Municipality</option>';
            
            municipalities.forEach(municipality => {
                const option = document.createElement('option');
                option.value = municipality;
                option.textContent = municipality;
                select.appendChild(option);
            });
        }

        function updateDeleteBarangayOptions() {
            const municipality = document.getElementById('deleteMunicipality').value;
            const barangaySelect = document.getElementById('deleteBarangay');
            
            // Clear existing options
            barangaySelect.innerHTML = '<option value="">All Barangays</option>';
            
            if (!municipality) {
                return;
            }
            
            // Use the exact same barangay data as screening.php
            const barangayMap = {
                'ABUCAY': ['Bangkal', 'Calaylayan (Pob.)', 'Capitangan', 'Gabon', 'Laon (Pob.)', 'Mabatang', 'Poblacion', 'Saguing', 'Salapungan', 'Tala'],
                'BAGAC': ['Bagumbayan (Pob.)', 'Banawang', 'Binuangan', 'Binukawan', 'Ibaba', 'Ibayo', 'Paysawan', 'Quinaoayanan', 'San Antonio', 'Saysain', 'Sibucao', 'Tabing-Ilog', 'Tipo', 'Tugatog', 'Wawa'],
                'CITY OF BALANGA': ['Bagumbayan', 'Cabog-Cabog', 'Munting Batangas (Cadre)', 'Cataning', 'Central', 'Cupang Proper', 'Cupang West', 'Dangcol (Bernabe)', 'Ibayo', 'Malabia', 'Poblacion', 'Pto. Rivas Ibaba', 'Pto. Rivas Itaas', 'San Jose', 'Sibacan', 'Camacho', 'Talisay', 'Tanato', 'Tenejero', 'Tortugas', 'Tuyo', 'Bagong Silang', 'Cupang North', 'Doña Francisca', 'Lote'],
                'DINALUPIHAN': ['Bangal', 'Bonifacio (Pob.)', 'Burgos (Pob.)', 'Colo', 'Daang Bago', 'Dalao', 'Del Pilar', 'General Luna', 'Governor Generoso', 'Hacienda', 'Jose Abad Santos (Pob.)', 'Kataasan', 'Layac', 'Lourdes', 'Mabini', 'Maligaya', 'Naparing', 'Paco', 'Pag-asa', 'Pagalanggang', 'Panggalan', 'Pinulot', 'Poblacion', 'Rizal', 'Saguing', 'San Benito', 'San Isidro', 'San Ramon', 'Santo Cristo', 'Sapang Balas', 'Sumalo', 'Tipo', 'Tuklasan', 'Turac', 'Zamora'],
                'HERMOSA': ['A. Rivera (Pob.)', 'Almacen', 'Bacong', 'Balsic', 'Bamban', 'Burgos-Soliman (Pob.)', 'Cataning (Pob.)', 'Culong', 'Daungan (Pob.)', 'Judicial (Pob.)', 'Mabiga', 'Mabuco', 'Maite', 'Palihan', 'Pandatung', 'Pulong Gubat', 'San Pedro (Pob.)', 'Santo Cristo (Pob.)', 'Sumalo', 'Tipo'],
                'LIMAY': ['Alangan', 'Kitang I', 'Kitang 2 & Luz', 'Lamao', 'Landing', 'Poblacion', 'Reforma', 'San Francisco de Asis', 'Townsite'],
                'MARIVELES': ['Alas-asin', 'Alion', 'Batangas II', 'Cabcaben', 'Lucanin', 'Mabayo', 'Malaya', 'Maligaya', 'Mountain View', 'Poblacion', 'San Carlos', 'San Isidro', 'San Nicolas', 'San Pedro', 'Saysain', 'Sisiman', 'Tukuran'],
                'MORONG': ['Binaritan', 'Mabayo', 'Nagbalayong', 'Poblacion', 'Sabang', 'San Pedro', 'Sitio Liyang'],
                'ORANI': ['Apolinario (Pob.)', 'Bagong Paraiso', 'Balut', 'Bayan (Pob.)', 'Calero (Pob.)', 'Calutit', 'Camachile', 'Del Pilar', 'Kaparangan', 'Mabatang', 'Maria Fe', 'Pagtakhan', 'Paking-Carbonero (Pob.)', 'Pantalan Bago (Pob.)', 'Pantalan Luma (Pob.)', 'Parang', 'Poblacion', 'Rizal (Pob.)', 'Sagrada', 'San Jose', 'Sibul', 'Sili', 'Sulong', 'Tagumpay', 'Tala', 'Talimundoc', 'Tugatog', 'Wawa'],
                'ORION': ['Arellano (Pob.)', 'Bagumbayan (Pob.)', 'Balagtas (Pob.)', 'Balut (Pob.)', 'Bantan', 'Bilolo', 'Calungusan', 'Camachile', 'Daang Bago', 'Daan Bago', 'Daan Bilolo', 'Daan Pare', 'General Lim (Kaput)', 'Kaput', 'Lati', 'Lusung', 'Puting Buhangin', 'Sabatan', 'San Vicente', 'Santa Elena', 'Santo Domingo', 'Villa Angeles', 'Wakas'],
                'PILAR': ['Ala-uli', 'Bagumbayan', 'Balut I', 'Balut II', 'Bantan Munti', 'Bantan', 'Burgos', 'Del Rosario', 'Diwa', 'Landing', 'Liwa', 'Nueva Vida', 'Panghulo', 'Pantingan', 'Poblacion', 'Rizal', 'Sagrada', 'San Nicolas', 'San Pedro', 'Santo Niño', 'Wakas'],
                'SAMAL': ['East Calaguiman (Pob.)', 'East Daang Bago (Pob.)', 'Ibaba (Pob.)', 'Imelda', 'Lalawigan', 'Palili', 'San Juan', 'San Roque', 'Santa Lucia', 'Santo Niño', 'West Calaguiman (Pob.)', 'West Daang Bago (Pob.)']
            };
            
            const barangays = barangayMap[municipality] || [];
            barangays.forEach(barangay => {
                const option = document.createElement('option');
                option.value = barangay;
                option.textContent = barangay;
                barangaySelect.appendChild(option);
            });
        }

        function confirmDeleteByLocation() {
            const municipality = document.getElementById('deleteMunicipality').value;
            const barangay = document.getElementById('deleteBarangay').value;
            
            if (!municipality) {
                alert('Please select municipality');
                return;
            }
            
            // Show password confirmation modal
            const confirmMessage = `You are about to delete all community users from ${municipality}${barangay ? `, ${barangay}` : ''}. This action cannot be undone. Please enter your admin password to confirm.`;
            
            showPasswordConfirmModal(performDeleteByLocation, {municipality, barangay}, confirmMessage);
        }

        function performDeleteByLocation(deleteData) {
            console.log('Performing delete by location:', deleteData);
            
            // Send delete request to server
            console.log('Sending request to /api/delete_users_by_location.php with data:', JSON.stringify(deleteData));
            fetch('/api/delete_users_by_location.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(deleteData)
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                // Get response text first to debug
                return response.text();
            })
            .then(text => {
                console.log('Raw response text:', text);
                
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed JSON data:', data);
                    
                    if (data.success) {
                        showNotification(`Successfully deleted ${data.deleted_count} users from ${deleteData.municipality}${deleteData.barangay ? `, ${deleteData.barangay}` : ''}`, 'success');
                        closeDeleteByLocationModal();
                        // Refresh the page to ensure all data is updated
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500); // Small delay to show the success message
                    } else {
                        showNotification('Error deleting users: ' + data.message, 'error');
                    }
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    console.error('Response text that failed to parse:', text);
                    showNotification('Error parsing server response: ' + parseError.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error deleting users: ' + error.message, 'error');
            });
        }

        function saveUsersTableChanges() {
            const userId = document.getElementById('editUserId').value;
            const username = document.getElementById('editUsername').value.trim();
            const email = document.getElementById('editUserEmail').value.trim();
            const password = document.getElementById('editPassword').value.trim();
            
            // Validate required fields
            if (!username || !email) {
                alert('Username and email are required');
                return;
            }
            
            // Validate email format
            if (!isValidEmail(email)) {
                document.getElementById('userEmailError').textContent = 'Please enter a valid email address';
                return;
            }
            
            // Prepare form data
            const formData = new FormData();
            formData.append('action', 'update_user');
            formData.append('user_id', userId);
            formData.append('username', username);
            formData.append('email', email);
            if (password) {
                formData.append('password', password);
            }
            
            // Show loading state
            const saveButton = document.querySelector('#editUsersTableModal .btn-primary');
            const originalText = saveButton.textContent;
            saveButton.textContent = 'Saving...';
            saveButton.disabled = true;
            
            // Send update request
            fetch('/settings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        showMessage('User updated successfully', 'success');
                        closeEditUsersTableModal();
                        // Reload the users table to show updated data
                        loadUsersTable();
                    } else {
                        showMessage('Error updating user: ' + data.error, 'error');
                        if (data.error.includes('email')) {
                            document.getElementById('userEmailError').textContent = data.error;
                        }
                    }
                } catch (e) {
                    showMessage('Error parsing server response', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Error updating user: ' + error.message, 'error');
            })
            .finally(() => {
                // Restore button state
                saveButton.textContent = originalText;
                saveButton.disabled = false;
            });
        }

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const editUserModal = document.getElementById('editUserModal');
            const editUsersTableModal = document.getElementById('editUsersTableModal');
            
            if (event.target === editUserModal) {
                closeEditUserModal();
            } else if (event.target === editUsersTableModal) {
                closeEditUsersTableModal();
            }
        }

        function initializeMunicipalityDropdown() {
            const municipalities = [
                "ABUCAY", "BAGAC", "CITY OF BALANGA", "DINALUPIHAN", "HERMOSA", 
                "LIMAY", "MARIVELES", "MORONG", "ORANI", "ORION", "PILAR", "SAMAL"
            ];
            
            const select = document.getElementById('editMunicipality');
            select.innerHTML = '<option value="">Select Municipality</option>';
            
            municipalities.forEach(municipality => {
                const option = document.createElement('option');
                option.value = municipality;
                option.textContent = municipality;
                select.appendChild(option);
            });
        }

        function updateBarangayOptions() {
            console.log('updateBarangayOptions called');
            const municipality = document.getElementById('editMunicipality').value;
            const barangaySelect = document.getElementById('editBarangay');
            
            console.log('Selected municipality:', municipality);
            console.log('Barangay select element:', barangaySelect);
            
            // Clear existing options
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            
            if (!municipality) {
                console.log('No municipality selected, returning');
                return;
            }
            
            // Barangay data (exact match with screening.php)
            const barangayMap = {
                'ABUCAY': ['Bangkal', 'Calaylayan (Pob.)', 'Capitangan', 'Gabon', 'Laon (Pob.)', 'Mabatang', 'Poblacion', 'Saguing', 'Salapungan', 'Tala'],
                'BAGAC': ['Bagumbayan (Pob.)', 'Banawang', 'Binuangan', 'Binukawan', 'Ibaba', 'Ibayo', 'Paysawan', 'Quinaoayanan', 'San Antonio', 'Saysain', 'Sibucao', 'Tabing-Ilog', 'Tipo', 'Tugatog', 'Wawa'],
                'CITY OF BALANGA': ['Bagumbayan', 'Cabog-Cabog', 'Munting Batangas (Cadre)', 'Cataning', 'Central', 'Cupang Proper', 'Cupang West', 'Dangcol (Bernabe)', 'Ibayo', 'Malabia', 'Poblacion', 'Pto. Rivas Ibaba', 'Pto. Rivas Itaas', 'San Jose', 'Sibacan', 'Camacho', 'Talisay', 'Tanato', 'Tenejero', 'Tortugas', 'Tuyo', 'Bagong Silang', 'Cupang North', 'Doña Francisca', 'Lote'],
                'DINALUPIHAN': ['Bangal', 'Bonifacio (Pob.)', 'Burgos (Pob.)', 'Colo', 'Daang Bago', 'Dalao', 'Del Pilar', 'General Luna', 'Governor Generoso', 'Hacienda', 'Jose Abad Santos (Pob.)', 'Kataasan', 'Layac', 'Lourdes', 'Mabini', 'Maligaya', 'Naparing', 'Paco', 'Pag-asa', 'Pagalanggang', 'Panggalan', 'Pinulot', 'Poblacion', 'Rizal', 'Saguing', 'San Benito', 'San Isidro', 'San Ramon', 'Santo Cristo', 'Sapang Balas', 'Sumalo', 'Tipo', 'Tuklasan', 'Turac', 'Zamora'],
                'HERMOSA': ['A. Rivera (Pob.)', 'Almacen', 'Bacong', 'Balsic', 'Bamban', 'Burgos-Soliman (Pob.)', 'Cataning (Pob.)', 'Culong', 'Daungan (Pob.)', 'Judicial (Pob.)', 'Mabiga', 'Mabuco', 'Maite', 'Palihan', 'Pandatung', 'Pulong Gubat', 'San Pedro (Pob.)', 'Santo Cristo (Pob.)', 'Sumalo', 'Tipo'],
                'LIMAY': ['Alangan', 'Kitang I', 'Kitang 2 & Luz', 'Lamao', 'Landing', 'Poblacion', 'Reforma', 'San Francisco de Asis', 'Townsite'],
                'MARIVELES': ['Alas-asin', 'Alion', 'Batangas II', 'Cabcaben', 'Lucanin', 'Mabayo', 'Malaya', 'Maligaya', 'Mountain View', 'Poblacion', 'San Carlos', 'San Isidro', 'San Nicolas', 'San Pedro', 'Saysain', 'Sisiman', 'Tukuran'],
                'MORONG': ['Binaritan', 'Mabayo', 'Nagbalayong', 'Poblacion', 'Sabang', 'San Pedro', 'Sitio Liyang'],
                'ORANI': ['Apolinario (Pob.)', 'Bagong Paraiso', 'Balut', 'Bayan (Pob.)', 'Calero (Pob.)', 'Calutit', 'Camachile', 'Del Pilar', 'Kaparangan', 'Mabatang', 'Maria Fe', 'Pagtakhan', 'Paking-Carbonero (Pob.)', 'Pantalan Bago (Pob.)', 'Pantalan Luma (Pob.)', 'Parang', 'Poblacion', 'Rizal (Pob.)', 'Sagrada', 'San Jose', 'Sibul', 'Sili', 'Sulong', 'Tagumpay', 'Tala', 'Talimundoc', 'Tugatog', 'Wawa'],
                'ORION': ['Arellano (Pob.)', 'Bagumbayan (Pob.)', 'Balagtas (Pob.)', 'Balut (Pob.)', 'Bantan', 'Bilolo', 'Calungusan', 'Camachile', 'Daang Bago', 'Daan Bago', 'Daan Bilolo', 'Daan Pare', 'General Lim (Kaput)', 'Kaput', 'Lati', 'Lusung', 'Puting Buhangin', 'Sabatan', 'San Vicente', 'Santa Elena', 'Santo Domingo', 'Villa Angeles', 'Wakas'],
                'PILAR': ['Ala-uli', 'Bagumbayan', 'Balut I', 'Balut II', 'Bantan Munti', 'Bantan', 'Burgos', 'Del Rosario', 'Diwa', 'Landing', 'Liwa', 'Nueva Vida', 'Panghulo', 'Pantingan', 'Poblacion', 'Rizal', 'Sagrada', 'San Nicolas', 'San Pedro', 'Santo Niño', 'Wakas'],
                'SAMAL': ['East Calaguiman (Pob.)', 'East Daang Bago (Pob.)', 'Ibaba (Pob.)', 'Imelda', 'Lalawigan', 'Palili', 'San Juan', 'San Roque', 'Santa Lucia', 'Santo Niño', 'West Calaguiman (Pob.)', 'West Daang Bago (Pob.)']
            };
            
            const barangays = barangayMap[municipality] || [];
            console.log('Found barangays for', municipality, ':', barangays.length, 'barangays');
            
            barangays.forEach(barangay => {
                const option = document.createElement('option');
                option.value = barangay;
                option.textContent = barangay;
                barangaySelect.appendChild(option);
            });
            
            console.log('Added', barangays.length, 'barangay options to select');
        }

        function togglePregnancyField() {
            const sex = document.getElementById('editSex').value;
            const pregnancyGroup = document.getElementById('pregnancyGroup');
            const birthday = document.getElementById('editBirthday').value;
            
            if (sex === 'Female' && birthday) {
                const age = calculateAgeFromBirthday(birthday);
                if (age >= 18 && age <= 50) {
                    pregnancyGroup.style.display = 'block';
                    document.getElementById('editPregnancy').required = true;
                } else {
                    pregnancyGroup.style.display = 'none';
                    document.getElementById('editPregnancy').required = false;
                }
            } else {
                pregnancyGroup.style.display = 'none';
                document.getElementById('editPregnancy').required = false;
            }
        }

        function calculateAge() {
            const birthday = document.getElementById('editBirthday').value;
            if (birthday) {
                const age = calculateAgeFromBirthday(birthday);
                document.getElementById('ageDisplay').textContent = `Age: ${age} years`;
                
                // Also toggle pregnancy field
                togglePregnancyField();
            } else {
                document.getElementById('ageDisplay').textContent = '';
            }
        }

        function calculateAgeFromBirthday(birthday) {
            const today = new Date();
            const birthDate = new Date(birthday);
            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            
            return age;
        }

        async function saveUserChanges() {
            // Validate form
            if (!validateEditForm()) {
                return;
            }
            
            // Get form data
            const formData = new FormData(document.getElementById('editUserForm'));
            // Get original email for finding the user
            const originalEmail = document.getElementById('modalEditEmail').getAttribute('data-original-email');
            
            const userData = {
                original_email: originalEmail,  // Use this to find the user
                email: formData.get('email'),   // New email to update to
                name: formData.get('name'),
                municipality: formData.get('municipality'),
                barangay: formData.get('barangay'),
                sex: formData.get('sex'),
                birthday: formData.get('birthday'),
                is_pregnant: formData.get('is_pregnant') || 'No',
                weight: formData.get('weight'),
                height: formData.get('height'),
                muac: formData.get('muac') || '0'
            };
            
            // Show loading
            const saveButton = document.querySelector('#editUserModal .btn-primary');
            const originalText = saveButton.textContent;
            saveButton.textContent = 'Saving...';
            saveButton.disabled = true;
            
            try {
                // Check if email already exists (only if email changed)
                const originalEmail = document.getElementById('editEmail').getAttribute('data-original-email');
                if (userData.email !== originalEmail) {
                    const emailExists = await new Promise((resolve) => {
                        checkEmailExists(userData.email, resolve);
                    });
                    
                    if (emailExists) {
                        alert('This email already exists. Please use a different email.');
                        saveButton.textContent = originalText;
                        saveButton.disabled = false;
                        return;
                    }
                }
                
                // Make API request to update user
                const response = await fetch('api/DatabaseAPI.php?action=update_community_user', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(userData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('User updated successfully!');
                    closeEditUserModal();
                    // Refresh the page to show updated data
                    location.reload();
                } else {
                    alert('Error updating user: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error updating user: ' + error.message);
            } finally {
                saveButton.textContent = originalText;
                saveButton.disabled = false;
            }
        }

        function validateEmail() {
            const email = document.getElementById('modalEditEmail').value.trim();
            const emailError = document.getElementById('modalEmailError');
            const emailInput = document.getElementById('modalEditEmail');
            
            // Clear previous error
            if (emailError) {
                emailError.textContent = '';
            }
            if (emailInput) {
                emailInput.style.borderColor = '';
            }
            
            if (!email) {
                if (emailError) {
                    emailError.textContent = 'Email is required';
                }
                if (emailInput) {
                    emailInput.style.borderColor = 'red';
                }
                return false;
            }
            
            // Basic email format validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                if (emailError) {
                    emailError.textContent = 'Please enter a valid email address';
                }
                if (emailInput) {
                    emailInput.style.borderColor = 'red';
                }
                return false;
            }
            
            return true;
        }

        function checkEmailExists(email, callback) {
            fetch('api/DatabaseAPI.php?action=check_email_exists', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ email: email })
            })
            .then(response => response.json())
            .then(data => {
                callback(data.exists || false);
            })
            .catch(error => {
                console.error('Error checking email:', error);
                callback(false);
            });
        }

        function validateEditForm() {
            const name = document.getElementById('modalEditName').value.trim();
            const email = document.getElementById('modalEditEmail').value.trim();
            const municipality = document.getElementById('editMunicipality').value;
            const barangay = document.getElementById('editBarangay').value;
            const sex = document.getElementById('editSex').value;
            const birthday = document.getElementById('editBirthday').value;
            const weight = document.getElementById('editWeight').value;
            const height = document.getElementById('editHeight').value;
            
            // Basic validation
            if (!name) {
                alert('Please enter a name');
                return false;
            }
            
            if (!email) {
                alert('Please enter an email');
                return false;
            }
            
            // Email format validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Please enter a valid email address');
                return false;
            }
            
            if (!municipality) {
                alert('Please select a municipality');
                return false;
            }
            
            if (!barangay) {
                alert('Please select a barangay');
                return false;
            }
            
            if (!sex) {
                alert('Please select sex');
                return false;
            }
            
            if (!birthday) {
                alert('Please select a birthday');
                return false;
            }
            
            if (!weight || isNaN(weight) || parseFloat(weight) <= 0 || parseFloat(weight) > 1000) {
                alert('Please enter a valid weight between 0.1 and 1000 kg');
                return false;
            }
            
            if (!height || isNaN(height) || parseFloat(height) <= 0 || parseFloat(height) > 300) {
                alert('Please enter a valid height between 1 and 300 cm');
                return false;
            }
            
            // Age-based validation (same as in NutritionalScreeningActivity.java)
            const age = calculateAgeFromBirthday(birthday);
            const weightNum = parseFloat(weight);
            const heightNum = parseFloat(height);
            
            // Weight validation based on age
            if (age < 2 && weightNum < 3) {
                alert('Weight seems impossible for this age. Please check your input.');
                return false;
            } else if (age >= 2 && age < 5 && weightNum < 8) {
                alert('Weight seems impossible for this age. Please check your input.');
                return false;
            } else if (age >= 5 && age < 10 && weightNum < 15) {
                alert('Weight seems impossible for this age. Please check your input.');
                return false;
            } else if (age >= 10 && age < 15 && weightNum < 25) {
                alert('Weight seems impossible for this age. Please check your input.');
                return false;
            } else if (age >= 15 && weightNum < 30) {
                alert('Weight seems impossible for this age. Please check your input.');
                return false;
            }
            
            // Height validation based on age
            if (age < 2 && heightNum < 30) {
                alert('Height seems impossible for this age. Please check your input.');
                return false;
            } else if (age >= 2 && age < 5 && heightNum < 50) {
                alert('Height seems impossible for this age. Please check your input.');
                return false;
            } else if (age >= 5 && age < 10 && heightNum < 80) {
                alert('Height seems impossible for this age. Please check your input.');
                return false;
            } else if (age >= 10 && age < 15 && heightNum < 120) {
                alert('Height seems impossible for this age. Please check your input.');
                return false;
            } else if (age >= 15 && heightNum < 140) {
                alert('Height seems impossible for this age. Please check your input.');
                return false;
            }
            
            return true;
        }

        // New filter functions for the control grid
        function filterByMunicipality() {
            applyAllFilters();
        }

        function filterByBarangay() {
            applyAllFilters();
        }

        function filterByDateRange() {
            applyAllFilters();
        }


        function applyAllFilters() {
            const municipalityFilter = document.getElementById('municipalityFilter').value;
            const barangayFilter = document.getElementById('barangayFilter').value;
            const fromDate = document.getElementById('fromDate').value;
            const toDate = document.getElementById('toDate').value;
            const sexFilter = document.getElementById('sexFilter').value;
            const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
            
            const tableRows = document.querySelectorAll('.user-table tbody tr');
            let visibleCount = 0;
            
            tableRows.forEach(row => {
                let showRow = true;
                
                // Search filter
                if (searchTerm) {
                    const name = row.cells[0].textContent.toLowerCase();
                    const email = row.cells[1].textContent.toLowerCase();
                    const municipality = row.cells[2].textContent.toLowerCase();
                    const barangay = row.cells[3].textContent.toLowerCase();
                    const sex = row.cells[4].textContent.toLowerCase();
                    
                    if (!name.includes(searchTerm) && 
                        !email.includes(searchTerm) && 
                        !municipality.includes(searchTerm) && 
                        !barangay.includes(searchTerm) && 
                        !sex.includes(searchTerm)) {
                        showRow = false;
                    }
                }
                
                // Municipality filter
                if (municipalityFilter && row.cells[2].textContent !== municipalityFilter) {
                    showRow = false;
                }
                
                // Barangay filter
                if (barangayFilter && row.cells[3].textContent !== barangayFilter) {
                    showRow = false;
                }
                
                // Date range filter (screening date)
                if (fromDate || toDate) {
                    // Get the screening date text content, trim whitespace
                    const screeningDateText = row.cells[6].textContent.trim();
                    console.log('Date filter - screeningDateText:', screeningDateText, 'fromDate:', fromDate, 'toDate:', toDate);
                    
                    // Check if we have a valid date (not N/A, not empty, not just whitespace)
                    if (screeningDateText && screeningDateText !== 'N/A' && screeningDateText !== '') {
                        const screeningDate = new Date(screeningDateText);
                        console.log('Date filter - parsed screeningDate:', screeningDate);
                        
                        if (!isNaN(screeningDate.getTime())) {
                            if (fromDate) {
                                const fromDateObj = new Date(fromDate);
                                console.log('Date filter - fromDateObj:', fromDateObj, 'screeningDate < fromDateObj:', screeningDate < fromDateObj);
                                if (screeningDate < fromDateObj) {
                                    showRow = false;
                                }
                            }
                            if (toDate) {
                                const toDateObj = new Date(toDate);
                                toDateObj.setHours(23, 59, 59, 999); // Include the entire day
                                console.log('Date filter - toDateObj:', toDateObj, 'screeningDate > toDateObj:', screeningDate > toDateObj);
                                if (screeningDate > toDateObj) {
                                    showRow = false;
                                }
                            }
                        } else {
                            console.log('Date filter - invalid date, hiding row');
                            showRow = false;
                        }
                    } else {
                        // If screening date is N/A or empty, hide the row when date filters are active
                        if (fromDate || toDate) {
                            console.log('Date filter - hiding row with N/A or empty screening date');
                            showRow = false;
                        }
                    }
                }
                
                // Sex filter
                if (sexFilter && row.cells[4].textContent !== sexFilter) {
                    showRow = false;
                }
                
                if (showRow) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Update visible count display
            updateVisibleCount(visibleCount);
        }

        function updateVisibleCount(count) {
            // You can add a count display element if needed
            console.log(`Showing ${count} users`);
        }

        // Update barangay options when municipality changes (for filters)
        function updateFilterBarangayOptions() {
            const municipalityFilter = document.getElementById('municipalityFilter');
            const barangayFilter = document.getElementById('barangayFilter');
            
            // Clear existing options
            barangayFilter.innerHTML = '<option value="">All</option>';
            
            // Use the exact same barangay data as screening.php
            const barangays = {
                'ABUCAY': ['Bangkal', 'Calaylayan (Pob.)', 'Capitangan', 'Gabon', 'Laon (Pob.)', 'Mabatang', 'Poblacion', 'Saguing', 'Salapungan', 'Tala'],
                'BAGAC': ['Bagumbayan (Pob.)', 'Banawang', 'Binuangan', 'Binukawan', 'Ibaba', 'Ibayo', 'Paysawan', 'Quinaoayanan', 'San Antonio', 'Saysain', 'Sibucao', 'Tabing-Ilog', 'Tipo', 'Tugatog', 'Wawa'],
                'CITY OF BALANGA': ['Bagumbayan', 'Cabog-Cabog', 'Munting Batangas (Cadre)', 'Cataning', 'Central', 'Cupang Proper', 'Cupang West', 'Dangcol (Bernabe)', 'Ibayo', 'Malabia', 'Poblacion', 'Pto. Rivas Ibaba', 'Pto. Rivas Itaas', 'San Jose', 'Sibacan', 'Camacho', 'Talisay', 'Tanato', 'Tenejero', 'Tortugas', 'Tuyo', 'Bagong Silang', 'Cupang North', 'Doña Francisca', 'Lote'],
                'DINALUPIHAN': ['Bangal', 'Bonifacio (Pob.)', 'Burgos (Pob.)', 'Colo', 'Daang Bago', 'Dalao', 'Del Pilar', 'General Luna', 'Governor Generoso', 'Hacienda', 'Jose Abad Santos (Pob.)', 'Kataasan', 'Layac', 'Lourdes', 'Mabini', 'Maligaya', 'Naparing', 'Paco', 'Pag-asa', 'Pagalanggang', 'Panggalan', 'Pinulot', 'Poblacion', 'Rizal', 'Saguing', 'San Benito', 'San Isidro', 'San Ramon', 'Santo Cristo', 'Sapang Balas', 'Sumalo', 'Tipo', 'Tuklasan', 'Turac', 'Zamora'],
                'HERMOSA': ['A. Rivera (Pob.)', 'Almacen', 'Bacong', 'Balsic', 'Bamban', 'Burgos-Soliman (Pob.)', 'Cataning (Pob.)', 'Culong', 'Daungan (Pob.)', 'Judicial (Pob.)', 'Mabiga', 'Mabuco', 'Maite', 'Palihan', 'Pandatung', 'Pulong Gubat', 'San Pedro (Pob.)', 'Santo Cristo (Pob.)', 'Sumalo', 'Tipo'],
                'LIMAY': ['Alangan', 'Kitang I', 'Kitang 2 & Luz', 'Lamao', 'Landing', 'Poblacion', 'Reforma', 'San Francisco de Asis', 'Townsite'],
                'MARIVELES': ['Alas-asin', 'Alion', 'Batangas II', 'Cabcaben', 'Lucanin', 'Mabayo', 'Malaya', 'Maligaya', 'Mountain View', 'Poblacion', 'San Carlos', 'San Isidro', 'San Nicolas', 'San Pedro', 'Saysain', 'Sisiman', 'Tukuran'],
                'MORONG': ['Binaritan', 'Mabayo', 'Nagbalayong', 'Poblacion', 'Sabang', 'San Pedro', 'Sitio Liyang'],
                'ORANI': ['Apolinario (Pob.)', 'Bagong Paraiso', 'Balut', 'Bayan (Pob.)', 'Calero (Pob.)', 'Calutit', 'Camachile', 'Del Pilar', 'Kaparangan', 'Mabatang', 'Maria Fe', 'Pagtakhan', 'Paking-Carbonero (Pob.)', 'Pantalan Bago (Pob.)', 'Pantalan Luma (Pob.)', 'Parang', 'Poblacion', 'Rizal (Pob.)', 'Sagrada', 'San Jose', 'Sibul', 'Sili', 'Sulong', 'Tagumpay', 'Tala', 'Talimundoc', 'Tugatog', 'Wawa'],
                'ORION': ['Arellano (Pob.)', 'Bagumbayan (Pob.)', 'Balagtas (Pob.)', 'Balut (Pob.)', 'Bantan', 'Bilolo', 'Calungusan', 'Camachile', 'Daang Bago', 'Daan Bago', 'Daan Bilolo', 'Daan Pare', 'General Lim (Kaput)', 'Kaput', 'Lati', 'Lusung', 'Puting Buhangin', 'Sabatan', 'San Vicente', 'Santa Elena', 'Santo Domingo', 'Villa Angeles', 'Wakas'],
                'PILAR': ['Ala-uli', 'Bagumbayan', 'Balut I', 'Balut II', 'Bantan Munti', 'Bantan', 'Burgos', 'Del Rosario', 'Diwa', 'Landing', 'Liwa', 'Nueva Vida', 'Panghulo', 'Pantingan', 'Poblacion', 'Rizal', 'Sagrada', 'San Nicolas', 'San Pedro', 'Santo Niño', 'Wakas'],
                'SAMAL': ['East Calaguiman (Pob.)', 'East Daang Bago (Pob.)', 'Ibaba (Pob.)', 'Imelda', 'Lalawigan', 'Palili', 'San Juan', 'San Roque', 'Santa Lucia', 'Santo Niño', 'West Calaguiman (Pob.)', 'West Daang Bago (Pob.)']
            };
            
            const selectedMunicipality = municipalityFilter.value;
            if (selectedMunicipality && barangays[selectedMunicipality]) {
                barangays[selectedMunicipality].forEach(barangay => {
                    const option = document.createElement('option');
                    option.value = barangay;
                    option.textContent = barangay;
                    barangayFilter.appendChild(option);
                });
            }
        }

        // Initialize barangay options on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateFilterBarangayOptions();
            
            // Load community users table by default
            loadCommunityUsersTable();
            
            // Add event listener for municipality changes
            const municipalityFilter = document.getElementById('municipalityFilter');
            if (municipalityFilter) {
                municipalityFilter.addEventListener('change', function() {
                    updateFilterBarangayOptions();
                    applyAllFilters();
                });
            }
            
            // Add event listener for search input
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('input', applyAllFilters);
            }
            
            // Ensure button text is set correctly after everything loads
            setTimeout(() => {
                updateTableToggleButton();
            }, 100);
        });

    </script>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit User Information</h2>
                <span class="close" onclick="closeEditUserModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    <!-- Personal Information Section -->
                    <div class="form-group">
                        <label for="modalEditName">Full Name *</label>
                        <input type="text" id="modalEditName" name="name" required placeholder="Enter full name">
                    </div>
                    
                    <div class="form-group">
                        <label for="modalEditEmail">Email Address *</label>
                        <input type="email" id="modalEditEmail" name="email" required onblur="validateEmail()" placeholder="Enter email address">
                        <small id="modalEmailError" class="error"></small>
                    </div>
                    
                    <!-- Location Information -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editMunicipality">Municipality *</label>
                            <select id="editMunicipality" name="municipality" required onchange="updateBarangayOptions()">
                                <option value="">Select Municipality</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="editBarangay">Barangay *</label>
                            <select id="editBarangay" name="barangay" required>
                                <option value="">Select Barangay</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Personal Details -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editSex">Sex *</label>
                            <select id="editSex" name="sex" required onchange="togglePregnancyField()">
                                <option value="">Select Sex</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="editBirthday">Birthday *</label>
                            <input type="date" id="editBirthday" name="birthday" required onchange="calculateAge()">
                            <small id="ageDisplay"></small>
                        </div>
                    </div>
                    
                    <!-- Pregnancy Field (conditional) -->
                    <div class="form-group" id="pregnancyGroup" style="display: none;">
                        <label for="editPregnancy">Are you pregnant? *</label>
                        <select id="editPregnancy" name="is_pregnant">
                            <option value="No">No</option>
                            <option value="Yes">Yes</option>
                        </select>
                        <small>This field is only required for female users</small>
                    </div>
                    
                    <!-- Physical Measurements -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editWeight">Weight (kg) *</label>
                            <input type="number" id="editWeight" name="weight" step="0.1" min="0.1" max="1000" required placeholder="Enter weight in kg">
                            <small>Enter weight in kilograms</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="editHeight">Height (cm) *</label>
                            <input type="number" id="editHeight" name="height" step="0.1" min="1" max="300" required placeholder="Enter height in cm">
                            <small>Enter height in centimeters</small>
                        </div>
                    </div>
                    
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditUserModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveUserChanges()">Save Changes</button>
            </div>
        </div>
    </div>

    <!-- Edit Users Table Modal (for users table only) -->
    <div id="editUsersTableModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit User Account</h2>
                <span class="close" onclick="closeEditUsersTableModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editUsersTableForm">
                    <div class="form-group">
                        <label for="editUsername">Username *</label>
                        <input type="text" id="editUsername" name="username" required placeholder="Enter username">
                    </div>
                    
                    <div class="form-group">
                        <label for="editUserEmail">Email Address *</label>
                        <input type="email" id="editUserEmail" name="email" required placeholder="Enter email address">
                        <small id="userEmailError" class="error"></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="editPassword">Password</label>
                        <input type="password" id="editPassword" name="password" placeholder="Leave blank to keep current password">
                        <small>Leave blank to keep current password</small>
                    </div>
                    
                    <input type="hidden" id="editUserId" name="user_id">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditUsersTableModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveUsersTableChanges()">Save Changes</button>
            </div>
        </div>
    </div>

    <!-- Delete by Location Modal -->
    <div id="deleteByLocationModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Delete Users by Location</h2>
                <span class="close" onclick="closeDeleteByLocationModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="deleteByLocationForm">
                    <div class="form-group">
                        <label for="deleteMunicipality">Select Municipality *</label>
                        <select id="deleteMunicipality" name="municipality" required onchange="updateDeleteBarangayOptions()">
                            <option value="">Select Municipality</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="deleteBarangay">Select Barangay</label>
                        <select id="deleteBarangay" name="barangay">
                            <option value="">All Barangays</option>
                        </select>
                        <small>Select a specific barangay or leave as "All Barangays" to delete all users from the municipality</small>
                    </div>
                    
                    <div class="form-group">
                        <div class="warning-box">
                            <strong>⚠️ Warning:</strong> This action will permanently delete all community users from the selected location. This cannot be undone.
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteByLocationModal()">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmDeleteByLocation()">Delete Users</button>
            </div>
        </div>
    </div>

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
                    body.style.paddingLeft = '320px'; // Expanded navbar width
                } else {
                    body.style.paddingLeft = '40px'; // Minimized navbar width
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
                                                                        