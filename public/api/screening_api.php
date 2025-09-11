<?php
/**
 * Dedicated Screening API
 * Simple, focused API for saving user screening data
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database configuration
$host = 'autorack.proxy.rlwy.net';
$port = '47913';
$dbname = 'railway';
$username = 'root';
$password = 'YOloxCacdHZJHdtFkGJKwKOJuATIZelm';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get JSON data
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'No data provided']);
            exit;
        }
        
        $email = $data['email'] ?? '';
        $name = $data['name'] ?? '';
        $municipality = $data['municipality'] ?? '';
        $barangay = $data['barangay'] ?? '';
        $sex = $data['sex'] ?? '';
        $birthday = $data['birthday'] ?? '';
        $is_pregnant = $data['is_pregnant'] ?? 'No';
        $weight = $data['weight'] ?? '';
        $height = $data['height'] ?? '';
        $muac = $data['muac'] ?? '';
        
        if (empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Email is required']);
            exit;
        }
        
        // Check if user exists
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM community_users WHERE email = ?");
        $checkStmt->execute([$email]);
        $userExists = $checkStmt->fetchColumn() > 0;
        
        if ($userExists) {
            // Update existing user
            $updateSql = "UPDATE community_users SET 
                            municipality = ?, 
                            barangay = ?, 
                            sex = ?, 
                            birthday = ?, 
                            is_pregnant = ?, 
                            weight = ?, 
                            height = ?, 
                            muac = ?, 
                            screening_date = NOW()
                          WHERE email = ?";
            
            $updateStmt = $pdo->prepare($updateSql);
            $result = $updateStmt->execute([
                $municipality, $barangay, $sex, $birthday, $is_pregnant, 
                $weight, $height, $muac, $email
            ]);
            
            if ($result) {
                // Verify the update worked by fetching the data
                $verifyStmt = $pdo->prepare("SELECT weight, height, muac FROM community_users WHERE email = ?");
                $verifyStmt->execute([$email]);
                $savedData = $verifyStmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Screening data updated successfully',
                    'action' => 'updated',
                    'saved_data' => $savedData,
                    'email' => $email
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update screening data']);
            }
        } else {
            // Create new user
            $insertSql = "INSERT INTO community_users 
                         (name, email, municipality, barangay, sex, birthday, is_pregnant, weight, height, muac, screening_date) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $insertStmt = $pdo->prepare($insertSql);
            $result = $insertStmt->execute([
                $name, $email, $municipality, $barangay, $sex, $birthday, 
                $is_pregnant, $weight, $height, $muac
            ]);
            
            if ($result) {
                // Verify the insert worked by fetching the data
                $verifyStmt = $pdo->prepare("SELECT weight, height, muac FROM community_users WHERE email = ?");
                $verifyStmt->execute([$email]);
                $savedData = $verifyStmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'User created and screening data saved successfully',
                    'action' => 'created',
                    'saved_data' => $savedData,
                    'email' => $email
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create user']);
            }
        }
        
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get user data
        $email = $_GET['email'] ?? '';
        
        if (empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Email is required']);
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT * FROM community_users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo json_encode([
                'success' => true,
                'message' => 'User data retrieved successfully',
                'user' => [
                    'name' => $user['name'] ?? '',
                    'email' => $user['email'] ?? '',
                    'municipality' => $user['municipality'] ?? '',
                    'barangay' => $user['barangay'] ?? '',
                    'sex' => $user['sex'] ?? '',
                    'birthday' => $user['birthday'] ?? '',
                    'is_pregnant' => $user['is_pregnant'] ?? '',
                    'weight_kg' => $user['weight'] ?? '', // Map weight to weight_kg
                    'height_cm' => $user['height'] ?? '', // Map height to height_cm
                    'muac_cm' => $user['muac'] ?? '',     // Map muac to muac_cm
                    'screening_date' => $user['screening_date'] ?? ''
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
