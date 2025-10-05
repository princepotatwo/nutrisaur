<?php
// DatabaseAPI_with_events.php - Modified version with event publishing
require_once 'config.php';
require_once 'DatabaseAPI.php';

// Include event publishing functions
require_once '../events_api.php';

// Modified save_screening function with event publishing
function saveScreeningWithEvents($data) {
    global $db;
    
    $email = $data['email'] ?? '';
    $name = $data['name'] ?? '';
    $password = $data['password'] ?? '';
    $municipality = $data['municipality'] ?? '';
    $barangay = $data['barangay'] ?? '';
    $sex = $data['sex'] ?? '';
    $birthday = $data['birthday'] ?? '';
    $is_pregnant = parsePregnantStatus($data['is_pregnant'] ?? 'No');
    $weight = $data['weight'] ?? '0';
    $height = $data['height'] ?? '0';
    
    if (empty($email)) {
        return ['success' => false, 'message' => 'Email is required'];
    }
    
    // Check if user exists
    $checkResult = $db->universalSelect('community_users', 'email', 'email = ?', '', '', [$email]);
    
    if ($checkResult['success'] && !empty($checkResult['data'])) {
        // User exists, update their screening data
        $updateData = [
            'municipality' => $municipality,
            'barangay' => $barangay,
            'sex' => $sex,
            'birthday' => $birthday,
            'is_pregnant' => $is_pregnant,
            'weight' => $weight,
            'height' => $height,
            'screening_date' => date('Y-m-d H:i:s')
        ];
        
        try {
            $pdo = $db->getPDO();
            $updateSql = "UPDATE community_users SET 
                            municipality = ?, 
                            barangay = ?, 
                            sex = ?, 
                            birthday = ?, 
                            is_pregnant = ?, 
                            weight = ?, 
                            height = ?, 
                            screening_date = NOW()
                          WHERE email = ?";
            
            $updateStmt = $pdo->prepare($updateSql);
            $result = $updateStmt->execute([
                $municipality, $barangay, $sex, $birthday, $is_pregnant, 
                $weight, $height, $email
            ]);
            
            if ($result) {
                // Publish event for dashboard update
                publishCommunityEvent(
                    'screening_data_updated',
                    [
                        'email' => $email,
                        'name' => $name,
                        'municipality' => $municipality,
                        'barangay' => $barangay,
                        'sex' => $sex,
                        'birthday' => $birthday,
                        'is_pregnant' => $is_pregnant,
                        'weight' => $weight,
                        'height' => $height,
                        'screening_date' => date('Y-m-d H:i:s'),
                        'action' => 'updated',
                        'timestamp' => time()
                    ],
                    $barangay
                );
                
                return [
                    'success' => true, 
                    'message' => 'Screening data updated successfully',
                    'data' => $updateData
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to update screening data'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    } else {
        // User doesn't exist, create new user with screening data
        if (empty($name)) {
            return ['success' => false, 'message' => 'Name is required for new users'];
        }
        
        // Generate a default password if none provided
        if (empty($password)) {
            $password = 'screening_user_' . time() . '_' . rand(1000, 9999);
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $insertData = [
            'name' => $name,
            'email' => $email,
            'password' => $hashedPassword,
            'municipality' => $municipality,
            'barangay' => $barangay,
            'sex' => $sex,
            'birthday' => $birthday,
            'is_pregnant' => $is_pregnant,
            'weight' => $weight,
            'height' => $height,
            'screening_date' => date('Y-m-d H:i:s')
        ];
        
        $result = $db->universalInsert('community_users', $insertData);
        
        if ($result['success']) {
            // Publish event for dashboard update
            publishCommunityEvent(
                'new_user_registered',
                [
                    'email' => $email,
                    'name' => $name,
                    'municipality' => $municipality,
                    'barangay' => $barangay,
                    'sex' => $sex,
                    'birthday' => $birthday,
                    'is_pregnant' => $is_pregnant,
                    'weight' => $weight,
                    'height' => $height,
                    'screening_date' => date('Y-m-d H:i:s'),
                    'action' => 'registered',
                    'timestamp' => time()
                ],
                $barangay
            );
            
            return [
                'success' => true, 
                'message' => 'User created and screening data saved successfully',
                'data' => $insertData
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to create user'];
        }
    }
}

// Add new API endpoint for event-enabled screening
if ($_GET['action'] === 'save_screening_with_events') {
    try {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Try to get JSON data first
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            // If JSON parsing fails, try form data
            if (!$data) {
                $data = $_POST;
            }
            
            if (!$data) {
                echo json_encode(['success' => false, 'message' => 'No data provided']);
                exit;
            }
            
            $result = saveScreeningWithEvents($data);
            echo json_encode($result);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>
