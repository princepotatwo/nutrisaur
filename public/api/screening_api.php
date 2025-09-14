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

// Include the main configuration file
require_once __DIR__ . '/../config.php';

try {
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        throw new Exception("Failed to establish database connection");
    }
    
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
        $weight = floatval($data['weight'] ?? 0);
        $height = floatval($data['height'] ?? 0);
        $muac = floatval($data['muac'] ?? 0);
        
        // Calculate age from birthday
        $age = 0;
        if (!empty($birthday)) {
            $birthDate = new DateTime($birthday);
            $today = new DateTime();
            $age = $today->diff($birthDate)->y;
        }
        
        // CRITICAL: Validate all input data
        $validation_errors = [];
        
        if (empty($email)) {
            $validation_errors[] = 'Email is required';
        }
        
        if (empty($name)) {
            $validation_errors[] = 'Name is required';
        }
        
        if (empty($municipality)) {
            $validation_errors[] = 'Municipality is required';
        }
        
        if (empty($barangay)) {
            $validation_errors[] = 'Barangay is required';
        }
        
        if (empty($sex) || !in_array($sex, ['Male', 'Female', 'Other'])) {
            $validation_errors[] = 'Valid sex is required (Male, Female, or Other)';
        }
        
        // Validate age
        if ($age < 0 || $age > 150) {
            $validation_errors[] = "Invalid age: {$age} years. Age must be between 0-150 years.";
        }
        
        // Validate weight
        if ($weight <= 0) {
            $validation_errors[] = "Invalid weight: {$weight} kg. Weight must be greater than 0.";
        } elseif ($weight < 0.5) {
            $validation_errors[] = "Invalid weight: {$weight} kg. Weight is too low (minimum 0.5 kg).";
        } elseif ($weight > 1000) {
            $validation_errors[] = "Invalid weight: {$weight} kg. Weight is too high (maximum 1000 kg).";
        }
        
        // Validate height
        if ($height <= 0) {
            $validation_errors[] = "Invalid height: {$height} cm. Height must be greater than 0.";
        } elseif ($height < 20) {
            $validation_errors[] = "Invalid height: {$height} cm. Height is too low (minimum 20 cm).";
        } elseif ($height > 300) {
            $validation_errors[] = "Invalid height: {$height} cm. Height is too high (maximum 300 cm).";
        }
        
        // Validate MUAC
        if ($muac < 0) {
            $validation_errors[] = "Invalid MUAC: {$muac} cm. MUAC cannot be negative.";
        } elseif ($muac > 50) {
            $validation_errors[] = "Invalid MUAC: {$muac} cm. MUAC is too high (maximum 50 cm).";
        }
        
        // Calculate and validate BMI
        if ($weight > 0 && $height > 0) {
            $height_m = $height / 100;
            $bmi = $weight / ($height_m * $height_m);
            
            if ($bmi < 5 || $bmi > 100) {
                $validation_errors[] = "Invalid BMI: " . round($bmi, 1) . ". BMI should be between 5-100.";
            }
            
            // Check for impossible combinations
            if ($age == 1 && $weight > 1000 && $height > 1000) {
                $validation_errors[] = "Impossible measurements detected: Age {$age}, Weight {$weight} kg, Height {$height} cm. These values are not medically possible for a 1-year-old.";
            }
        }
        
        // Age-specific validation
        if ($age >= 0 && $age < 18) {
            // Child-specific validation
            if ($age < 2 && $weight > 20) {
                $validation_errors[] = "Weight {$weight} kg is too high for age {$age}. Expected range: 3-15 kg.";
            }
            if ($age < 2 && $height > 100) {
                $validation_errors[] = "Height {$height} cm is too high for age {$age}. Expected range: 50-90 cm.";
            }
        }
        
        // Return validation errors if any
        if (!empty($validation_errors)) {
            echo json_encode([
                'success' => false, 
                'message' => 'Data validation failed',
                'errors' => $validation_errors,
                'recommendation' => 'Please check your measurements and enter realistic values.'
            ]);
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
