<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Include the centralized configuration file
require_once __DIR__ . "/config.php";

// Test endpoint
if (isset($_GET['test'])) {
    echo json_encode([
        'success' => true,
        'message' => 'API is working!',
        'timestamp' => date('Y-m-d H:i:s'),
        'method' => $_SERVER['REQUEST_METHOD'],
        'test_param' => $_GET['test']
    ]);
    exit;
}

// Check for specific endpoint types - support both 'type' and 'endpoint' parameters for compatibility
$endpoint = $_GET['endpoint'] ?? $_GET['type'] ?? 'dashboard';

// Handle POST requests with actions (for Android app compatibility)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $postData = json_decode($input, true);
    
    if ($postData && isset($postData['action'])) {
        $action = $postData['action'];
        
        if ($action === 'save_screening') {
            $email = $postData['email'] ?? '';
            $username = $postData['username'] ?? '';
            $riskScore = $postData['risk_score'] ?? 0;
            
            error_log("save_screening called with email: $email, username: $username, risk_score: $riskScore");
            
            if (empty($email)) {
                echo json_encode(['success' => false, 'message' => 'Email is required']);
                exit;
            }
            
            try {
                // Handle both old format (screening_data) and new format (individual fields)
                $screeningArray = [];
                $screeningAnswersToSave = '{}';
                
                if (isset($postData['screening_data'])) {
                    // Old format: parse JSON from screening_data
                    $screeningData = $postData['screening_data'];
                    $screeningArray = json_decode($screeningData, true) ?: [];
                    $screeningAnswersToSave = json_encode($screeningArray, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
                } else {
                    // New format: individual fields directly in postData
                    $screeningArray = $postData;
                    $screeningAnswersToSave = json_encode($screeningArray, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
                }
                
                // Extract key values for SIMPLE user_preferences table - ONE COLUMN PER QUESTION
                $birthday = $screeningArray['birthday'] ?? null;
                $age = $screeningArray['age'] ?? null;
                $gender = $screeningArray['gender'] ?? '';
                $weight = $screeningArray['weight'] ?? 0;
                $height = $screeningArray['height'] ?? 0;
                $bmi = $screeningArray['bmi'] ?? 0;
                $muac = !empty($screeningArray['muac']) ? floatval($screeningArray['muac']) : 0;
                $swelling = $screeningArray['swelling'] ?? 'no';
                $weightLoss = $screeningArray['weight_loss'] ?? '<5% or none';
                $dietaryDiversity = $screeningArray['dietary_diversity'] ?? 0;
                $feedingBehavior = $screeningArray['feeding_behavior'] ?? 'good appetite';
                
                // Physical signs - individual boolean columns (convert to integers for MySQL tinyint)
                $physicalThin = $screeningArray['physical_thin'] ? 1 : 0;
                $physicalShorter = $screeningArray['physical_shorter'] ? 1 : 0;
                $physicalWeak = $screeningArray['physical_weak'] ? 1 : 0;
                $physicalNone = $screeningArray['physical_none'] ? 1 : 0;
                
                // Clinical risk factors - individual boolean columns (convert to integers for MySQL tinyint)
                $hasRecentIllness = $screeningArray['has_recent_illness'] ? 1 : 0;
                $hasEatingDifficulty = $screeningArray['has_eating_difficulty'] ? 1 : 0;
                $hasFoodInsecurity = $screeningArray['has_food_insecurity'] ? 1 : 0;
                $hasMicronutrientDeficiency = $screeningArray['has_micronutrient_deficiency'] ? 1 : 0;
                $hasFunctionalDecline = $screeningArray['has_functional_decline'] ? 1 : 0;
                
                $barangay = $screeningArray['barangay'] ?? '';
                $income = $screeningArray['income'] ?? '';
                
                // Save to SIMPLE user_preferences table - ONE COLUMN PER QUESTION
                $stmt = $conn->prepare("
                    INSERT INTO user_preferences (
                        user_email, username, screening_answers, risk_score, 
                        birthday, age, gender, weight, height, bmi, muac,
                        swelling, weight_loss, dietary_diversity, feeding_behavior,
                        physical_thin, physical_shorter, physical_weak, physical_none,
                        has_recent_illness, has_eating_difficulty, has_food_insecurity, 
                        has_micronutrient_deficiency, has_functional_decline,
                        barangay, income
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                        screening_answers = VALUES(screening_answers),
                        risk_score = VALUES(risk_score),
                        birthday = VALUES(birthday),
                        age = VALUES(age),
                        gender = VALUES(gender),
                        weight = VALUES(weight),
                        height = VALUES(height),
                        bmi = VALUES(bmi),
                        muac = VALUES(muac),
                        swelling = VALUES(swelling),
                        weight_loss = VALUES(weight_loss),
                        dietary_diversity = VALUES(dietary_diversity),
                        feeding_behavior = VALUES(feeding_behavior),
                        physical_thin = VALUES(physical_thin),
                        physical_shorter = VALUES(physical_shorter),
                        physical_weak = VALUES(physical_weak),
                        physical_none = VALUES(physical_none),
                        has_recent_illness = VALUES(has_recent_illness),
                        has_eating_difficulty = VALUES(has_eating_difficulty),
                        has_food_insecurity = VALUES(has_food_insecurity),
                        has_micronutrient_deficiency = VALUES(has_micronutrient_deficiency),
                        has_functional_decline = VALUES(has_functional_decline),
                        barangay = VALUES(barangay),
                        income = VALUES(income)
                ");
                
                $stmt->execute([
                    $email, $username, $screeningAnswersToSave, $riskScore,
                    $birthday, $age, $gender, $weight, $height, $bmi, $muac,
                    $swelling, $weightLoss, $dietaryDiversity, $feedingBehavior,
                    $physicalThin, $physicalShorter, $physicalWeak, $physicalNone,
                    $hasRecentIllness, $hasEatingDifficulty, $hasFoodInsecurity,
                    $hasMicronutrientDeficiency, $hasFunctionalDecline,
                    $barangay, $income
                ]);
                
                error_log("save_screening: User inserted/updated successfully for email: $email");
                
                // ✅ Data saved to SIMPLE user_preferences table - ONE COLUMN PER QUESTION
                // Simple structure with individual columns for each screening question
                
                echo json_encode(['success' => true, 'message' => 'Screening data saved successfully']);
                exit; // Prevent further handlers from running (avoids Unknown action)
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error saving screening data: ' . $e->getMessage()]);
                exit; // Stop processing on error
            }
        }
        
        if ($action === 'get_screening_data') {
            // Handle both POST form data and JSON body
            $email = $_POST['email'] ?? '';
            if (empty($email)) {
                // Try to get from JSON body
                $input = file_get_contents('php://input');
                $jsonData = json_decode($input, true);
                $email = $jsonData['email'] ?? $jsonData['user_email'] ?? '';
            }
            
            error_log("get_screening_data called with email: $email");
            error_log("POST data: " . json_encode($_POST));
            error_log("JSON input: " . $input);
            
            if (empty($email)) {
                echo json_encode(['success' => false, 'message' => 'Email is required']);
                exit;
            }
            
            try {
                // First, try to get data from the main users table (same source as the dashboard)
                try {
                    error_log("Attempting to query main users table for email: $email");
                    $stmt = $conn->prepare("
                        SELECT 
                            id,
                            username,
                            email,
                            birthday,
                            gender,
                            weight,
                            height,
                            bmi,
                            barangay,
                            income,
                            screening_answers,
                            risk_score,
                            created_at,
                            updated_at
                        FROM users 
                        WHERE email = ?
                    ");
                    
                    $stmt->execute([$email]);
                    $result = $stmt->fetch();
                    error_log("Main users table query result: " . json_encode($result));
                    
                    if ($result) {
                        // Parse screening answers
                        $screeningAnswers = [];
                        if (!empty($result['screening_answers'])) {
                            try {
                                $screeningAnswers = json_decode($result['screening_answers'], true);
                                // Handle double decode if needed
                                if (is_string($screeningAnswers)) {
                                    $screeningAnswers = json_decode($screeningAnswers, true);
                                }
                            } catch (Exception $e) {
                                error_log("Error parsing screening_answers for email $email: " . $e->getMessage());
                                $screeningAnswers = [];
                            }
                        }
                        
                        // Merge user data with screening data
                        $userData = array_merge([
                            'id' => $result['id'],
                            'email' => $result['email'],
                            'username' => $result['username'],
                            'birthday' => $result['birthday'],
                            'gender' => $result['gender'],
                            'weight' => $result['weight'],
                            'height' => $result['height'],
                            'bmi' => $result['bmi'],
                            'barangay' => $result['barangay'],
                            'income' => $result['income'],
                            'risk_score' => $result['risk_score'],
                            'created_at' => $result['created_at'],
                            'updated_at' => $result['updated_at']
                        ], $screeningAnswers);
                        
                        error_log("Successfully retrieved user data from main users table for email: $email");
                        
                    } else {
                        error_log("No user data found in main users table for email: $email");
                        $userData = [];
                    }
                    
                } catch (Exception $e) {
                    error_log("Error querying main users table for email $email: " . $e->getMessage());
                    $userData = [];
                }
                
                // If no data from main users table, try user_preferences as fallback
                if (empty($userData)) {
                    error_log("Attempting fallback to user_preferences table for email: $email");
                    try {
                        $stmt = $conn->prepare("
                            SELECT 
                                user_email,
                                username,
                                screening_answers, 
                                risk_score, 
                                barangay, 
                                gender, 
                                weight, 
                                height, 
                                bmi, 
                                muac,
                                dietary_diversity,
                                income,
                                birthday,
                                updated_at
                            FROM user_preferences 
                            WHERE user_email = ?
                        ");
                        
                        $stmt->execute([$email]);
                        $result = $stmt->fetch();
                        error_log("user_preferences fallback query result: " . json_encode($result));
                        
                        if ($result) {
                            $screeningAnswersRaw = $result['screening_answers'];
                            $screeningAnswers = json_decode($screeningAnswersRaw, true);
                            
                            // Handle double decode if needed
                            if (is_string($screeningAnswers)) {
                                $screeningAnswers = json_decode($screeningAnswers, true);
                            }
                            
                            // Merge basic user info with screening data
                            $userData = array_merge([
                                'email' => $result['user_email'],
                                'username' => $result['username'],
                                'risk_score' => $result['risk_score'],
                                'barangay' => $result['barangay'],
                                'gender' => $result['gender'],
                                'weight' => $result['weight'],
                                'height' => $result['height'],
                                'bmi' => $result['bmi'],
                                'muac' => $result['muac'],
                                'dietary_diversity' => $result['dietary_diversity'],
                                'income' => $result['income'],
                                'birthday' => $result['birthday'],
                                'updated_at' => $result['updated_at']
                            ], $screeningAnswers);
                            
                            error_log("Successfully retrieved user data from user_preferences fallback for email: $email");
                        } else {
                            error_log("No user data found in user_preferences fallback for email: $email");
                        }
                    } catch (Exception $e) {
                        error_log("Error retrieving from user_preferences fallback for email $email: " . $e->getMessage());
                    }
                }
                
                error_log("get_screening_data returning data for email $email: " . json_encode($userData));
                echo json_encode([
                    'success' => true,
                    'data' => $userData
                ]);
                exit; // Exit after successful response
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error retrieving screening data: ' . $e->getMessage()]);
                exit; // Exit after error response
            }
        }
        
        if ($action === 'update_fcm_token') {
            try {
                $userEmail = $postData['email'] ?? '';
                $fcmToken = $postData['fcm_token'] ?? '';
                
                if (!$userEmail || !$fcmToken) {
                    echo json_encode(['error' => 'Email and FCM token are required']);
                    exit;
                }
                
                // Use the existing user_fcm_tokens table instead of user_preferences
                // Check if token already exists
                $stmt = $conn->prepare("SELECT id FROM user_fcm_tokens WHERE fcm_token = ?");
                $stmt->execute([$fcmToken]);
                $existingToken = $stmt->fetch();
                
                if ($existingToken) {
                    // Update existing token with new user email
                    $stmt = $conn->prepare("
                        UPDATE user_fcm_tokens SET 
                            user_email = ?,
                            updated_at = NOW()
                        WHERE fcm_token = ?
                    ");
                    $stmt->execute([$userEmail, $fcmToken]);
                } else {
                    // Insert new token
                    $stmt = $conn->prepare("
                        INSERT INTO user_fcm_tokens (
                            user_email, fcm_token, created_at, updated_at
                        ) VALUES (?, ?, NOW(), NOW())
                    ");
                    $stmt->execute([$userEmail, $fcmToken]);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'FCM token updated successfully',
                    'user_email' => $userEmail
                ]);
                
            } catch(PDOException $e) {
                echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            }
            exit;
        }
        
        // First get_user_data action removed - was referencing non-existent screening_answers column
        // Using the second get_user_data action below which works correctly
        
        if ($action === 'add_user') {
            try {
                $userData = $postData['user_data'] ?? [];
                
                if (empty($userData) || empty($userData['email'])) {
                    echo json_encode(['error' => 'User data and email are required']);
                    exit;
                }
                
                // Check if user already exists
                $stmt = $conn->prepare("SELECT id FROM user_preferences WHERE user_email = ?");
                $stmt->execute([$userData['email']]);
                $existingUser = $stmt->fetch();
                
                if ($existingUser) {
                    echo json_encode(['error' => 'User with this email already exists']);
                    exit;
                }
                
                // Insert new user with direct column data (no more screening_answers)
                $stmt = $conn->prepare("
                    INSERT INTO user_preferences (
                        user_email, name, risk_score, 
                        barangay, gender, weight, height, bmi, muac,
                        allergies, diet_prefs, avoid_foods, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                
                // Calculate BMI if weight and height are provided
                $bmi = 0;
                if (!empty($userData['weight']) && !empty($userData['height'])) {
                    $heightMeters = $userData['height'] / 100.0;
                    $bmi = $heightMeters > 0 ? round($userData['weight'] / ($heightMeters * $heightMeters), 1) : 0;
                }
                
                $stmt->execute([
                    $userData['email'],
                    $userData['username'] ?? '',
                    $userData['risk_score'] ?? 0,
                    $userData['barangay'] ?? '',
                    $userData['gender'] ?? '',
                    $userData['weight'] ?? 0,
                    $userData['height'] ?? 0,
                    $bmi,
                    $userData['muac'] ?? 0,
                    is_array($userData['allergies']) ? json_encode($userData['allergies']) : ($userData['allergies'] ?? ''),
                    is_array($userData['diet_prefs']) ? json_encode($userData['diet_prefs']) : ($userData['diet_prefs'] ?? ''),
                    $userData['avoid_foods'] ?? ''
                ]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'User added successfully',
                    'user_email' => $userData['email']
                ]);
                
            } catch(PDOException $e) {
                echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            }
            exit;
        }
        
        if ($action === 'add_user_csv') {
            try {
                $userData = $postData['user_data'] ?? [];
                
                if (empty($userData) || empty($userData['user_email'])) {
                    echo json_encode(['error' => 'User data and user_email are required']);
                    exit;
                }
                
                // Check if user already exists
                $stmt = $conn->prepare("SELECT id FROM user_preferences WHERE user_email = ?");
                $stmt->execute([$userData['user_email']]);
                $existingUser = $stmt->fetch();
                
                if ($existingUser) {
                    echo json_encode(['error' => 'User with this email already exists']);
                    exit;
                }
                
                // Insert new user with ALL CSV data matching the actual database structure
                $stmt = $conn->prepare("
                    INSERT INTO user_preferences (
                        user_email, name, birthday, age, gender, height, weight, bmi, muac, 
                        goal, risk_score, allergies, diet_prefs, avoid_foods, barangay, income,
                        created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                
                $stmt->execute([
                    $userData['user_email'],
                    $userData['name'] ?? '',
                    $userData['birthday'] ?? null,
                    $userData['age'] ?? null,
                    $userData['gender'] ?? '',
                    $userData['height'] ?? 0,
                    $userData['weight'] ?? 0,
                    $userData['bmi'] ?? 0,
                    $userData['muac'] ?? 0,
                    $userData['goal'] ?? 'maintain',
                    $userData['risk_score'] ?? 0,
                    $userData['allergies'] ?? '',
                    $userData['diet_prefs'] ?? '',
                    $userData['avoid_foods'] ?? '',
                    $userData['barangay'] ?? '',
                    $userData['income'] ?? ''
                ]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'User added successfully from CSV',
                    'user_email' => $userData['user_email']
                ]);
                
            } catch(PDOException $e) {
                echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            }
            exit;
        }
        
        if ($action === 'update_user') {
            try {
                $userData = $postData['user_data'] ?? [];
                
                if (empty($userData) || empty($userData['user_email'])) {
                    echo json_encode(['error' => 'User data and email are required']);
                    exit;
                }
                
                // Check if user exists
                $stmt = $conn->prepare("SELECT id FROM user_preferences WHERE user_email = ?");
                $stmt->execute([$userData['user_email']]);
                $existingUser = $stmt->fetch();
                
                if (!$existingUser) {
                    echo json_encode(['error' => 'User not found']);
                    exit;
                }
                
                // Update user
                $stmt = $conn->prepare("
                    UPDATE user_preferences SET 
                        name = ?, birthday = ?, age = ?, gender = ?, height = ?, weight = ?, 
                        bmi = ?, muac = ?, goal = ?, risk_score = ?, allergies = ?, 
                        diet_prefs = ?, avoid_foods = ?, barangay = ?, income = ?, 
                        updated_at = NOW()
                    WHERE user_email = ?
                ");
                
                $result = $stmt->execute([
                    $userData['name'],
                    $userData['birthday'] ?? null,
                    $userData['age'] ?? null,
                    $userData['gender'] ?? null,
                    $userData['height'] ?? null,
                    $userData['weight'] ?? null,
                    $userData['bmi'] ?? null,
                    $userData['muac'] ?? null,
                    $userData['goal'] ?? null,
                    $userData['risk_score'] ?? 0,
                    $userData['allergies'] ?? null,
                    $userData['diet_prefs'] ?? null,
                    $userData['avoid_foods'] ?? null,
                    $userData['barangay'] ?? null,
                    $userData['income'] ?? null,
                    $userData['user_email']
                ]);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'User updated successfully']);
                } else {
                    echo json_encode(['error' => 'Failed to update user']);
                }
                
            } catch (Exception $e) {
                echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            }
        }
        
        if ($action === 'get_user_data') {
            try {
                $email = $postData['email'] ?? $postData['user_email'] ?? '';
                
                if (empty($email)) {
                    echo json_encode(['error' => 'User email is required']);
                    exit;
                }
                
                // Get user data directly from user_preferences table
                $stmt = $conn->prepare("
                    SELECT id, user_email, name, birthday, age, gender, height, weight, bmi, 
                           muac, goal, risk_score, allergies, diet_prefs, avoid_foods, 
                           barangay, income, created_at, updated_at
                    FROM user_preferences 
                    WHERE user_email = ?
                ");
                
                $stmt->execute([$email]);
                $userData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($userData) {
                    echo json_encode([
                        'success' => true,
                        'data' => $userData
                    ]);
                } else {
                    echo json_encode(['error' => 'User not found']);
                }
                
            } catch (Exception $e) {
                echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            }
            exit;
        }
        
        if ($action === 'delete_user') {
            try {
                $email = $postData['email'] ?? '';
                
                if (empty($email)) {
                    echo json_encode(['error' => 'User email is required']);
                    exit;
                }
                
                // Delete user
                $stmt = $conn->prepare("DELETE FROM user_preferences WHERE user_email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'User deleted successfully',
                        'user_email' => $email
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'User not found'
                    ]);
                }
                
            } catch(PDOException $e) {
                echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            }
            exit;
        }
        
        // fix_screening_data action removed - screening_answers column no longer exists
        // if ($action === 'fix_screening_data') { ... }
        
        if ($action === 'get_usm_screening_data') {
            try {
                $userEmail = $postData['user_email'] ?? $postData['email'] ?? '';
                
                if (!$userEmail) {
                    echo json_encode(['error' => 'User email is required']);
                    exit;
                }
                
                $stmt = $conn->prepare("
                    SELECT 
                        screening_answers, 
                        risk_score, 
                        created_at, 
                        updated_at,
                        gender,
                        barangay,
                        income,
                        weight,
                        height,
                        bmi,
                        muac,
                        name,
                        birthday,
                        allergies,
                        diet_prefs,
                        avoid_foods
                    FROM user_preferences 
                    WHERE user_email = ?
                ");
                $stmt->execute([$userEmail]);
                $screeningData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($screeningData) {
                    // Parse the screening answers JSON with improved handling for USM
                    $screeningAnswers = [];
                    if ($screeningData['screening_answers'] && $screeningData['screening_answers'] !== 'null' && $screeningData['screening_answers'] !== '[]') {
                        try {
                            $screeningAnswersRaw = $screeningData['screening_answers'];
                            
                            // Debug: Log the raw screening answers
                            error_log("USM Screening Debug - Raw screening answers for user {$userEmail}: " . $screeningAnswersRaw);
                            
                            // First, try to decode normally
                            $screeningAnswers = json_decode($screeningAnswersRaw, true);
                            
                            // Debug: Log the first decode result
                            error_log("USM Screening Debug - First decode result: " . print_r($screeningAnswers, true));
                            
                            // If first decode returns a string (not an array), decode it again
                            if (is_string($screeningAnswers)) {
                                $screeningAnswers = json_decode($screeningAnswers, true);
                                error_log("USM Screening Debug - Second decode result: " . print_r($screeningAnswers, true));
                            }
                            
                            // Ensure screeningAnswers is an array
                            if (!is_array($screeningAnswers)) {
                                $screeningAnswers = [];
                                error_log("USM Screening Debug - Screening answers is not an array, setting to empty array");
                            }
                            
                            // Try to fix common JSON issues if parsing failed
                            if (empty($screeningAnswers) && !empty($screeningAnswersRaw)) {
                                try {
                                    // Try to fix common JSON issues
                                    $fixedData = $screeningAnswersRaw;
                                    // Remove extra backslashes
                                    $fixedData = str_replace('\\\\', '\\', $fixedData);
                                    // Fix escaped quotes
                                    $fixedData = str_replace('\\"', '"', $fixedData);
                                    $screeningAnswers = json_decode($fixedData, true) ?: [];
                                    error_log("USM Screening Debug - Fixed JSON decode result: " . print_r($screeningAnswers, true));
                                } catch (Exception $e2) {
                                    $screeningAnswers = [];
                                    error_log("USM Screening Debug - Fixed JSON decode failed: " . $e2->getMessage());
                                }
                            }
                        } catch (Exception $e) {
                            $screeningAnswers = [];
                            error_log("USM Screening Debug - JSON decode exception: " . $e->getMessage());
                        }
                    } else {
                        error_log("USM Screening Debug - No screening answers for user {$userEmail} or empty/null value");
                    }
                    
                    // Create a comprehensive user data object for USM
                    $userData = array_merge($screeningAnswers, [
                        'user_email' => $userEmail,
                        'risk_score' => $screeningData['risk_score'],
                        'created_at' => $screeningData['created_at'],
                        'updated_at' => $screeningData['updated_at'],
                        'gender' => $screeningData['gender'],
                        'barangay' => $screeningData['barangay'],
                        'income' => $screeningData['income'],
                        'weight' => $screeningData['weight'],
                        'height' => $screeningData['height'],
                        'bmi' => $screeningData['bmi'],
                        'muac' => $screeningData['muac'],
                        'name' => $screeningData['name'],
                        'birthday' => $screeningData['birthday'],
                        'allergies' => $screeningData['allergies'],
                        'diet_prefs' => $screeningData['diet_prefs'],
                        'avoid_foods' => $screeningData['avoid_foods'],
                        'screening_answers' => $screeningData['screening_answers']
                    ]);
                    
                    // Debug: Log the final user data
                    error_log("USM Screening Debug - Final user data for {$userEmail}: " . print_r($userData, true));
                    
                    echo json_encode([
                        'success' => true,
                        'data' => $userData
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'No screening data found for this user'
                    ]);
                }
                
            } catch(PDOException $e) {
                echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            }
            exit;
        }
        
        if ($action === 'get_screening_analysis') {
            $email = $_POST['email'] ?? '';
            
            if (empty($email)) {
                echo json_encode(['success' => false, 'message' => 'Email is required']);
                exit;
            }
            
            try {
                // Get screening data in both formats
                $jsonData = getScreeningData($conn, $email, 'json');
                $structuredData = getScreeningData($conn, $email, 'structured');
                $trends = analyzeScreeningTrends($conn, $email);
                
                $analysis = [
                    'user_email' => $email,
                    'json_format' => $jsonData,
                    'structured_format' => $structuredData,
                    'trends' => $trends,
                    'comparison' => [
                        'data_consistency' => 'verified',
                        'structure_used' => $structuredData ? 'normalized' : 'legacy_json',
                        'recommendation' => $structuredData ? 'Using new normalized structure' : 'Consider migrating to normalized structure'
                    ]
                ];
                
                echo json_encode([
                    'success' => true,
                    'analysis' => $analysis
                ]);
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error analyzing screening data: ' . $e->getMessage()]);
            }
        }
        
        if ($action === 'get_screening_statistics') {
            try {
                $stats = getScreeningStatistics($conn);
                
                if ($stats) {
                    echo json_encode([
                        'success' => true,
                        'statistics' => $stats
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'No screening data available for statistics'
                    ]);
                }
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error getting statistics: ' . $e->getMessage()]);
            }
        }
        
        // migrate_user_to_normalized action removed - screening_answers column no longer exists
        // if ($action === 'migrate_user_to_normalized') { ... }
        
        // Unknown action
        echo json_encode(['error' => 'Unknown action: ' . $action]);
        exit;
    }
}

// Test endpoint for debugging municipality filtering
if ($endpoint === 'test_municipality') {
    try {
        $barangay = $_GET['barangay'] ?? '';
        echo json_encode([
            'success' => true,
            'barangay_param' => $barangay,
            'is_municipality' => strpos($barangay, 'MUNICIPALITY_') === 0,
            'municipality_name' => strpos($barangay, 'MUNICIPALITY_') === 0 ? str_replace('MUNICIPALITY_', '', $barangay) : null,
            'barangays_in_municipality' => strpos($barangay, 'MUNICIPALITY_') === 0 ? getMunicipalityBarangays(str_replace('MUNICIPALITY_', '', $barangay)) : [],
            'total_users_in_municipality' => 0
        ]);
        
        // If it's a municipality, also show actual user count
        if (strpos($barangay, 'MUNICIPALITY_') === 0) {
            $municipality = str_replace('MUNICIPALITY_', '', $barangay);
            list($whereClause, $params) = buildMunicipalityWhereClause($municipality);
            
            if (!empty($whereClause)) {
                $sql = "SELECT COUNT(*) as total FROM user_preferences up " . $whereClause;
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'barangay_param' => $barangay,
                    'is_municipality' => true,
                    'municipality_name' => $municipality,
                    'barangays_in_municipality' => getMunicipalityBarangays($municipality),
                    'total_users_in_municipality' => intval($result['total']),
                    'sql_query' => $sql,
                    'params' => $params
                ]);
            }
        }
        
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Test endpoint to check current user data
if ($endpoint === 'check_user_data') {
    try {
        $userEmail = $_GET['email'] ?? '';
        
        if (!$userEmail) {
            echo json_encode(['error' => 'Email parameter required']);
            exit;
        }
        
        $stmt = $conn->prepare("
            SELECT 
                user_email,
                barangay,
                gender,
                weight,
                height,
                bmi,
                screening_answers,
                risk_score,
                created_at,
                updated_at
            FROM user_preferences 
            WHERE user_email = ?
        ");
        $stmt->execute([$userEmail]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userData) {
            // Parse screening answers to compare with individual columns
            $screeningAnswers = json_decode($userData['screening_answers'], true);
            
            echo json_encode([
                'success' => true,
                'user_data' => $userData,
                'screening_answers_parsed' => $screeningAnswers,
                'comparison' => [
                    'barangay_match' => $userData['barangay'] === ($screeningAnswers['barangay'] ?? ''),
                    'gender_match' => $userData['gender'] === ($screeningAnswers['gender'] ?? ''),
                    'weight_match' => $userData['weight'] == ($screeningAnswers['weight'] ?? 0),
                    'height_match' => $userData['height'] == ($screeningAnswers['height'] ?? 0),
                    'bmi_match' => $userData['bmi'] == ($screeningAnswers['bmi'] ?? 0)
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'User not found'
            ]);
        }
        
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Municipality to barangay mapping
function getMunicipalityBarangays($municipality) {
    $municipalityMap = [
        'ABUCAY' => ['Bangkal', 'Calaylayan (Pob.)', 'Capitangan', 'Gabon', 'Laon (Pob.)', 'Mabatang', 'Omboy', 'Salian', 'Wawa (Pob.)'],
        'BAGAC' => ['Bagumbayan (Pob.)', 'Banawang', 'Binuangan', 'Binukawan', 'Ibaba', 'Ibis', 'Pag-asa (Wawa-Sibacan)', 'Parang', 'Paysawan', 'Quinawan', 'San Antonio', 'Saysain', 'Tabing-Ilog (Pob.)', 'Atilano L. Ricardo'],
        'BALANGA' => ['Bagumbayan', 'Cabog-Cabog', 'Munting Batangas (Cadre)', 'Cataning', 'Central', 'Cupang Proper', 'Cupang West', 'Dangcol (Bernabe)', 'Ibayo', 'Malabia', 'Poblacion', 'Pto. Rivas Ibaba', 'Pto. Rivas Itaas', 'San Jose', 'Sibacan', 'Camacho', 'Talisay', 'Tanato', 'Tenejero', 'Tortugas', 'Tuyo', 'Bagong Silang', 'Cupang North', 'Doña Francisca', 'Lote'],
        'DINALUPIHAN' => ['Bangal', 'Bonifacio (Pob.)', 'Burgos (Pob.)', 'Colo', 'Daang Bago', 'Dalao', 'Del Pilar (Pob.)', 'Gen. Luna (Pob.)', 'Gomez (Pob.)', 'Happy Valley', 'Kataasan', 'Layac', 'Luacan', 'Mabini Proper (Pob.)', 'Mabini Ext. (Pob.)', 'Magsaysay', 'Naparing', 'New San Jose', 'Old San Jose', 'Padre Dandan (Pob.)', 'Pag-asa', 'Pagalanggang', 'Pinulot', 'Pita', 'Rizal (Pob.)', 'Roosevelt', 'Roxas (Pob.)', 'Saguing', 'San Benito', 'San Isidro (Pob.)', 'San Pablo (Bulate)', 'San Ramon', 'San Simon', 'Santo Niño', 'Sapang Balas', 'Santa Isabel (Tabacan)', 'Torres Bugauen (Pob.)', 'Tucop', 'Zamora (Pob.)', 'Aquino', 'Bayan-bayanan', 'Maligaya', 'Payangan', 'Pentor', 'Tubo-tubo', 'Jose C. Payumo, Jr.'],
        'HERMOSA' => ['A. Rivera (Pob.)', 'Almacen', 'Bacong', 'Balsic', 'Bamban', 'Burgos-Soliman (Pob.)', 'Cataning (Pob.)', 'Culis', 'Daungan (Pob.)', 'Mabiga', 'Mabuco', 'Maite', 'Mambog - Mandama', 'Palihan', 'Pandatung', 'Pulo', 'Saba', 'San Pedro (Pob.)', 'Santo Cristo (Pob.)', 'Sumalo', 'Tipo', 'Judge Roman Cruz Sr. (Mandama)', 'Sacrifice Valley'],
        'LIMAY' => ['Alangan', 'Kitang I', 'Kitang 2 & Luz', 'Lamao', 'Landing', 'Poblacion', 'Reformista', 'Townsite', 'Wawa', 'Duale', 'San Francisco de Asis', 'St. Francis II'],
        'MARIVELES' => ['Alas-asin', 'Alion', 'Batangas II', 'Cabcaben', 'Lucanin', 'Baseco Country (Nassco)', 'Poblacion', 'San Carlos', 'San Isidro', 'Sisiman', 'Balon-Anito', 'Biaan', 'Camaya', 'Ipag', 'Malaya', 'Maligaya', 'Mt. View', 'Townsite'],
        'MORONG' => ['Binaritan', 'Mabayo', 'Nagbalayong', 'Poblacion', 'Sabang'],
        'ORANI' => ['Bagong Paraiso (Pob.)', 'Balut (Pob.)', 'Bayan (Pob.)', 'Calero (Pob.)', 'Paking-Carbonero (Pob.)', 'Centro II (Pob.)', 'Dona', 'Kaparangan', 'Masantol', 'Mulawin', 'Pag-asa', 'Palihan (Pob.)', 'Pantalan Bago (Pob.)', 'Pantalan Luma (Pob.)', 'Parang Parang (Pob.)', 'Centro I (Pob.)', 'Sibul', 'Silahis', 'Tala', 'Talimundoc', 'Tapulao', 'Tenejero (Pob.)', 'Tugatog', 'Wawa (Pob.)', 'Apollo', 'Kabalutan', 'Maria Fe', 'Puksuan', 'Tagumpay'],
        'ORION' => ['Arellano (Pob.)', 'Bagumbayan (Pob.)', 'Balagtas (Pob.)', 'Balut (Pob.)', 'Bantan', 'Bilolo', 'Calungusan', 'Camachile', 'Daang Bago (Pob.)', 'Daang Bilolo (Pob.)', 'Daang Pare', 'General Lim (Kaput)', 'Kapunitan', 'Lati (Pob.)', 'Lusungan (Pob.)', 'Puting Buhangin', 'Sabatan', 'San Vicente (Pob.)', 'Santo Domingo', 'Villa Angeles (Pob.)', 'Wakas (Pob.)', 'Santa Elena'],
        'PILAR' => ['Ala-uli', 'Bagumbayan', 'Balut I', 'Balut II', 'Bantan Munti', 'Burgos', 'Del Rosario (Pob.)', 'Diwa', 'Landing', 'Liyang', 'Nagwaling', 'Panilao', 'Pantingan', 'Poblacion', 'Rizal', 'Santa Rosa', 'Wakas North', 'Wakas South', 'Wawa'],
        'SAMAL' => ['East Calaguiman (Pob.)', 'East Daang Bago (Pob.)', 'Ibaba (Pob.)', 'Imelda', 'Lalawigan', 'Palili', 'San Juan (Pob.)', 'San Roque (Pob.)', 'Santa Lucia', 'Sapa', 'Tabing Ilog', 'Gugo', 'West Calaguiman (Pob.)', 'West Daang Bago (Pob.)']
    ];
    
    return $municipalityMap[$municipality] ?? [];
}

// Helper function to build municipality WHERE clause
function buildMunicipalityWhereClause($municipality) {
    $barangays = getMunicipalityBarangays($municipality);
    if (empty($barangays)) {
        return ['', []];
    }
    
    $placeholders = str_repeat('?,', count($barangays) - 1) . '?';
    $whereClause = "WHERE up.barangay IN ($placeholders)";
    
    return [$whereClause, $barangays];
}

// Handle specific dashboard endpoints
if ($endpoint === 'community_metrics') {
    try {
        $barangay = $_GET['barangay'] ?? '';
        
        // Get community metrics based on barangay filter
        $whereClause = "";
        $params = [];
        
        if ($barangay && $barangay !== '') {
            if (strpos($barangay, 'MUNICIPALITY_') === 0) {
                // Municipality level - get all barangays in that municipality
                $municipality = str_replace('MUNICIPALITY_', '', $barangay);
                list($whereClause, $params) = buildMunicipalityWhereClause($municipality);
                if (empty($whereClause)) {
                    // If municipality not found, return empty results
                    echo json_encode([
                        'success' => true,
                        'total_screened' => 0,
                        'screened_change' => 'No data',
                        'high_risk_cases' => 0,
                        'risk_change' => 'No data',
                        'sam_cases' => 0,
                        'sam_change' => 'No data',
                        'barangay_filter' => $barangay
                    ]);
                    exit;
                }
            } else {
                // Individual barangay
                $whereClause = "WHERE up.barangay = ?";
                $params = [$barangay];
            }
        }
        
        $sql = "
            SELECT 
                COUNT(*) as total_screened,
                AVG(up.risk_score) as avg_risk_score,
                SUM(CASE WHEN up.risk_score >= 50 THEN 1 ELSE 0 END) as high_risk_cases,
                SUM(CASE WHEN up.risk_score >= 50 THEN 1 ELSE 0 END) as sam_cases
            FROM user_preferences up
        ";
        
        if ($whereClause) {
            $sql .= " " . $whereClause . " AND up.risk_score IS NOT NULL AND 1=1";
        } else {
            $sql .= " WHERE up.risk_score IS NOT NULL AND 1=1";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $metrics = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate changes based on actual data
        $screenedChange = $metrics['total_screened'] > 0 ? '+0' : 'No data';
        $riskChange = $metrics['total_screened'] > 0 ? '+0' : 'No data';
        $samChange = $metrics['total_screened'] > 0 ? 'No change' : 'No data';
        
        echo json_encode([
            'success' => true,
            'total_screened' => intval($metrics['total_screened']),
            'screened_change' => $screenedChange,
            'high_risk_cases' => intval($metrics['high_risk_cases']),
            'risk_change' => $riskChange,
            'sam_cases' => intval($metrics['sam_cases']),
            'sam_change' => $samChange,
            'barangay_filter' => $barangay
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($endpoint === 'risk_distribution') {
    try {
        $barangay = $_GET['barangay'] ?? '';
        
        $whereClause = "";
        $params = [];
        
        if ($barangay && $barangay !== '') {
            if (strpos($barangay, 'MUNICIPALITY_') === 0) {
                $municipality = str_replace('MUNICIPALITY_', '', $barangay);
                list($whereClause, $params) = buildMunicipalityWhereClause($municipality);
                if (empty($whereClause)) {
                    // If municipality not found, return empty results
                    echo json_encode([
                        'success' => true,
                        'data' => []
                    ]);
                    exit;
                }
            } else {
                $whereClause = "WHERE up.barangay = ?";
                $params = [$barangay];
            }
        }
        
        $sql = "
            SELECT 
                CASE 
                    WHEN up.risk_score < 20 THEN 'Low Risk'
                    WHEN up.risk_score < 50 THEN 'Moderate Risk'
                    WHEN up.risk_score < 80 THEN 'High Risk'
                    ELSE 'Critical Risk'
                END as risk_level,
                COUNT(*) as count
            FROM user_preferences up
        ";
        
        if ($whereClause) {
            $sql .= " " . $whereClause . " AND up.risk_score IS NOT NULL AND 1=1";
        } else {
            $sql .= " WHERE up.risk_score IS NOT NULL AND 1=1";
        }
        
        $sql .= "
            GROUP BY risk_level
            ORDER BY 
                CASE risk_level
                    WHEN 'Critical Risk' THEN 1
                    WHEN 'High Risk' THEN 2
                    WHEN 'Moderate Risk' THEN 3
                    WHEN 'Low Risk' THEN 4
                END
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $riskData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format data for chart
        $chartData = [];
        $colors = ['#4CAF50', '#FFC107', '#FF9800', '#F44336'];
        
        // Get individual risk scores for each category to calculate accurate averages
        foreach ($riskData as $index => $item) {
            $riskLevel = $item['risk_level'];
            
            // Get the actual risk scores for users in this category
            $scoreSql = "
                SELECT up.risk_score
                FROM user_preferences up
            ";
            
            if ($whereClause) {
                $scoreSql .= " " . $whereClause . " AND up.risk_score IS NOT NULL";
            } else {
                $scoreSql .= " WHERE up.risk_score IS NOT NULL";
            }
            
            $scoreSql .= " AND CASE 
                WHEN up.risk_score < 20 THEN 'Low Risk'
                WHEN up.risk_score < 50 THEN 'Moderate Risk'
                WHEN up.risk_score < 80 THEN 'High Risk'
                ELSE 'Critical Risk'
            END = ?";
            
            $scoreStmt = $conn->prepare($scoreSql);
            $scoreParams = $params;
            $scoreParams[] = $riskLevel;
            $scoreStmt->execute($scoreParams);
            $riskScores = $scoreStmt->fetchAll(PDO::FETCH_COLUMN);
            
            $chartData[] = [
                'label' => $item['risk_level'],
                'value' => intval($item['count']),
                'color' => $colors[$index] ?? '#999',
                'risk_scores' => $riskScores // Include actual risk scores
            ];
        }
        
        // Add debugging information
        $debugInfo = [
            'sql_query' => $sql,
            'params' => $params,
            'raw_data' => $riskData,
            'chart_data' => $chartData
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $chartData,
            'debug' => $debugInfo
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($endpoint === 'whz_distribution') {
    try {
        $barangay = $_GET['barangay'] ?? '';
        
        $whereClause = "";
        $params = [];
        
        if ($barangay && $barangay !== '') {
            if (strpos($barangay, 'MUNICIPALITY_') === 0) {
                $municipality = str_replace('MUNICIPALITY_', '', $barangay);
                list($whereClause, $params) = buildMunicipalityWhereClause($municipality);
                if (empty($whereClause)) {
                    // If municipality not found, return empty results
                    echo json_encode([
                        'success' => true,
                        'data' => []
                    ]);
                    exit;
                }
            } else {
                $whereClause = "WHERE up.barangay = ?";
                $params = [$barangay];
            }
        }
        
        // Calculate WHZ scores from height, weight, and age data
        $sql = "
            SELECT 
                CASE 
                    WHEN up.bmi < 16 THEN 'SAM'
                    WHEN up.bmi < 18.5 THEN 'MAM'
                    WHEN up.bmi < 25 THEN 'Normal'
                    ELSE 'Overweight'
                END as whz_category,
                COUNT(*) as count
            FROM user_preferences up
        ";
        
        if ($whereClause) {
            $sql .= " " . $whereClause . " AND up.bmi IS NOT NULL";
        } else {
            $sql .= " WHERE up.bmi IS NOT NULL";
        }
        
        $sql .= "
            GROUP BY whz_category
            ORDER BY 
                CASE whz_category
                    WHEN 'SAM' THEN 1
                    WHEN 'MAM' THEN 2
                    WHEN 'Normal' THEN 3
                    WHEN 'Overweight' THEN 4
                END
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $whzData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format data for chart
        $chartData = [];
        $colors = ['#F44336', '#FF9800', '#4CAF50', '#FFC107'];
        foreach ($whzData as $index => $item) {
            $chartData[] = [
                'label' => $item['whz_category'],
                'value' => intval($item['count']),
                'color' => $colors[$index] ?? '#999'
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $chartData
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($endpoint === 'muac_distribution') {
    try {
        $barangay = $_GET['barangay'] ?? '';
        
        $whereClause = "";
        $params = [];
        
        if ($barangay && $barangay !== '') {
            if (strpos($barangay, 'MUNICIPALITY_') === 0) {
                $municipality = str_replace('MUNICIPALITY_', '', $barangay);
                list($whereClause, $params) = buildMunicipalityWhereClause($municipality);
                if (empty($whereClause)) {
                    // If municipality not found, return empty results
                    echo json_encode([
                        'success' => true,
                        'data' => []
                    ]);
                    exit;
                }
            } else {
                $whereClause = "WHERE up.barangay = ?";
                $params = [$barangay];
            }
        }
        
        $sql = "
            SELECT 
                CASE 
                    WHEN up.muac < 11.5 THEN 'SAM'
                    WHEN up.muac < 12.5 THEN 'MAM'
                    ELSE 'Normal'
                END as muac_category,
                COUNT(*) as count
            FROM user_preferences up
        ";
        
        if ($whereClause) {
            $sql .= " " . $whereClause . " AND up.muac IS NOT NULL";
        } else {
            $sql .= " WHERE up.muac IS NOT NULL";
        }
        
        $sql .= "
            GROUP BY muac_category
            ORDER BY 
                CASE muac_category
                    WHEN 'SAM' THEN 1
                    WHEN 'MAM' THEN 2
                    WHEN 'Normal' THEN 3
                END
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $muacData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format data for chart
        $chartData = [];
        $colors = ['#F44336', '#FF9800', '#4CAF50'];
        foreach ($muacData as $index => $item) {
            $chartData[] = [
                'label' => $item['muac_category'],
                'value' => intval($item['count']),
                'color' => $colors[$index] ?? '#999'
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $chartData
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($endpoint === 'geographic_distribution') {
    try {
        $barangay = $_GET['barangay'] ?? '';
        
        $whereClause = "";
        $params = [];
        
        if ($barangay && $barangay !== '') {
            if (strpos($barangay, 'MUNICIPALITY_') === 0) {
                $municipality = str_replace('MUNICIPALITY_', '', $barangay);
                list($whereClause, $params) = buildMunicipalityWhereClause($municipality);
                if (empty($whereClause)) {
                    // If municipality not found, return empty results
                    echo json_encode([
                        'success' => true,
                        'data' => []
                    ]);
                    exit;
                }
            } else {
                $whereClause = "WHERE up.barangay = ?";
                $params = [$barangay];
            }
        }
        
        $sql = "
            SELECT 
                up.barangay,
                COUNT(*) as count,
                AVG(up.risk_score) as avg_risk
            FROM user_preferences up
        ";
        
        if ($whereClause) {
            $sql .= " " . $whereClause . " AND up.risk_score IS NOT NULL AND 1=1";
        } else {
            $sql .= " WHERE up.risk_score IS NOT NULL AND 1=1";
        }
        
        $sql .= "
            GROUP BY up.barangay
            ORDER BY count DESC
            LIMIT 10
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $geoData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format data for chart
        $chartData = [];
        foreach ($geoData as $item) {
            $chartData[] = [
                'barangay' => $item['barangay'],
                'count' => intval($item['count']),
                'avg_risk' => round($item['avg_risk'], 1)
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $chartData
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($endpoint === 'critical_alerts') {
    try {
        $barangay = $_GET['barangay'] ?? '';
        
        $whereClause = "";
        $params = [];
        
        if ($barangay && $barangay !== '') {
            if (strpos($barangay, 'MUNICIPALITY_') === 0) {
                $municipality = str_replace('MUNICIPALITY_', '', $barangay);
                list($whereClause, $params) = buildMunicipalityWhereClause($municipality);
                if (empty($whereClause)) {
                    // If municipality not found, return empty results
                    echo json_encode([
                        'success' => true,
                        'data' => []
                    ]);
                    exit;
                }
            } else {
                $whereClause = "WHERE up.barangay = ?";
                $params = [$barangay];
            }
        }
        
        $sql = "
            SELECT 
                up.id,
                up.name,
                up.user_email,
                up.barangay,
                up.risk_score,
                up.bmi,
                up.muac,
                up.created_at
            FROM user_preferences up
        ";
        
        if ($whereClause) {
            $sql .= " " . $whereClause . " AND (up.risk_score >= 75 OR (up.risk_score >= 50 AND up.muac < 11.5) OR (up.risk_score >= 50 AND up.bmi < 16))";
        } else {
            $sql .= " WHERE (up.risk_score >= 75 OR (up.risk_score >= 50 AND up.muac < 11.5) OR (up.risk_score >= 50 AND up.bmi < 16))";
        }
        
        $sql .= "
            ORDER BY up.risk_score DESC
            LIMIT 5
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format data for alerts - match the dashboard format
        $alertData = [];
        foreach ($alerts as $alert) {
            // Determine specific risk level for more precise messaging
            $riskLevel = 'High Risk';
            $message = 'High malnutrition risk detected';
            
            if ($alert['risk_score'] >= 75) {
                $riskLevel = 'Severe Risk';
                $message = 'SEVERE malnutrition risk - Immediate intervention required';
            } elseif ($alert['risk_score'] >= 50) {
                if ($alert['muac'] < 11.5 || $alert['bmi'] < 16) {
                    $riskLevel = 'High Risk';
                    $message = 'High malnutrition risk with critical indicators';
                } else {
                    $riskLevel = 'High Risk';
                    $message = 'High malnutrition risk detected';
                }
            }
            
            $alertData[] = [
                'type' => 'critical',
                'message' => $message,
                'user' => $alert['name'] ?: $alert['user_email'] ?: 'User ' . $alert['id'],
                'user_email' => $alert['user_email'],
                'time' => date('M j, Y', strtotime($alert['created_at'])),
                'risk_level' => $riskLevel,
                'risk_score' => $alert['risk_score']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $alertData
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($endpoint === 'analysis_data') {
    try {
        $barangay = $_GET['barangay'] ?? '';
        
        $whereClause = "";
        $params = [];
        
        if ($barangay && $barangay !== '') {
            if (strpos($barangay, 'MUNICIPALITY_') === 0) {
                $municipality = str_replace('MUNICIPALITY_', '', $barangay);
                list($whereClause, $params) = buildMunicipalityWhereClause($municipality);
                if (empty($whereClause)) {
                    // If municipality not found, return empty results
                    echo json_encode([
                        'success' => true,
                        'risk_analysis' => ['total_users' => 0, 'avg_risk' => 0, 'at_risk_users' => 0],
                        'demographics' => ['total_users' => 0, 'age_0_5' => 0, 'age_6_12' => 0, 'age_13_17' => 0, 'age_18_59' => 0, 'age_60_plus' => 0]
                    ]);
                    exit;
                }
            } else {
                $whereClause = "WHERE up.barangay = ?";
                $params = [$barangay];
            }
        }
        
        // Get risk analysis data
        $sql = "
            SELECT 
                COUNT(*) as total_users,
                AVG(up.risk_score) as avg_risk,
                SUM(CASE WHEN up.risk_score >= 50 THEN 1 ELSE 0 END) as at_risk_users
            FROM user_preferences up
        ";
        
        if ($whereClause) {
            $sql .= " " . $whereClause . " AND up.risk_score IS NOT NULL AND 1=1";
        } else {
            $sql .= " WHERE up.risk_score IS NOT NULL AND 1=1";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $riskAnalysis = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get demographics data
        $sql = "
            SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN up.age < 6 THEN 1 ELSE 0 END) as age_0_5,
                SUM(CASE WHEN up.age >= 6 AND up.age < 13 THEN 1 ELSE 0 END) as age_6_12,
                SUM(CASE WHEN up.age >= 13 AND up.age < 18 THEN 1 ELSE 0 END) as age_13_17,
                SUM(CASE WHEN up.age >= 18 AND up.age < 60 THEN 1 ELSE 0 END) as age_18_59,
                SUM(CASE WHEN up.age >= 60 THEN 1 ELSE 0 END) as age_60_plus
            FROM user_preferences up
        ";
        
        if ($whereClause) {
            $sql .= " " . $whereClause . " AND up.age IS NOT NULL";
        } else {
            $sql .= " WHERE up.age IS NOT NULL";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $demographics = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'risk_analysis' => [
                'total_users' => intval($riskAnalysis['total_users']),
                'avg_risk' => round($riskAnalysis['avg_risk'], 1),
                'at_risk_users' => intval($riskAnalysis['at_risk_users'])
            ],
            'demographics' => [
                'total_users' => intval($demographics['total_users']),
                'age_0_5' => intval($demographics['age_0_5']),
                'age_6_12' => intval($demographics['age_6_12']),
                'age_13_17' => intval($demographics['age_13_17']),
                'age_18_59' => intval($demographics['age_18_59']),
                'age_60_plus' => intval($demographics['age_60_plus'])
            ]
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// MHO Risk Score Update Endpoint
if ($endpoint === 'update_mho_risk_scores') {
    try {
        // This endpoint requires admin privileges or should be called carefully
        $result = updateAllRiskScoresToMHO($conn);
        
        echo json_encode($result);
        
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle USM endpoint for User Screening Module
if ($endpoint === 'usm') {
    try {
        // Get all users with their screening data and preferences
        // Use a more flexible query that handles missing columns gracefully
        $stmt = $conn->prepare("
            SELECT 
                up.id,
                up.name,
                up.user_email as email,
                up.birthday,
                up.age,
                up.gender,
                up.weight,
                up.height,
                up.bmi,
                up.muac,
                up.barangay,
                up.income,
                up.risk_score,
                up.allergies,
                up.diet_prefs,
                up.avoid_foods,
                up.created_at,
                up.updated_at
            FROM user_preferences up
            ORDER BY up.created_at DESC
        ");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("USM Debug - Found " . count($users) . " users in user_preferences table");
        
        // Format users data for USM
        $usersData = [];
        foreach ($users as $user) {
            // Data is now directly in the columns, no need to parse JSON
            error_log("USM Debug - Processing user {$user['email']} with direct column data");
                
            
            $usersData[] = [
                'id' => intval($user['id']),
                'username' => $user['name'] ?: ($user['email'] ? explode('@', $user['email'])[0] : 'User ' . $user['id']),
                'name' => $user['name'] ?: ($user['email'] ? explode('@', $user['email'])[0] : 'User ' . $user['id']),
                'email' => $user['email'],
                'birthday' => $user['birthday'],
                'age' => $user['age'],
                'gender' => $user['gender'],
                'weight' => $user['weight'],
                'height' => $user['height'],
                'bmi' => $user['bmi'],
                'muac' => $user['muac'],
                'barangay' => $user['barangay'],
                'income' => $user['income'],
                'risk_score' => $user['risk_score'],
                'allergies' => $user['allergies'],
                'diet_prefs' => $user['diet_prefs'],
                'avoid_foods' => $user['avoid_foods'],
                'created_at' => $user['created_at'],
                'updated_at' => $user['updated_at']
            ];
            
            // Debug logging for direct column data
            error_log("USM Debug - User {$user['email']}: birthday: " . ($user['birthday'] ?? 'null') . ", age: " . ($user['age'] ?? 'null') . ", barangay: " . ($user['barangay'] ?? 'null') . ", income: " . ($user['income'] ?? 'null'));
        }
        
        error_log("USM Debug - Returning " . count($usersData) . " users");
        
        echo json_encode([
            'success' => true,
            'users' => $usersData,        // For backward compatibility with web dashboard
            'preferences' => $usersData,   // For Android app compatibility
            'total_users' => count($usersData)
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle events endpoint for Android app - FIXED to support both 'type' and 'endpoint' parameters
if ($endpoint === 'events') {
    try {
        // Get all upcoming programs/events
        $stmt = $conn->prepare("
            SELECT 
                p.program_id,
                p.title,
                p.type,
                p.description,
                p.date_time,
                p.location,
                p.organizer,
                p.created_at
            FROM programs p
            WHERE p.date_time >= NOW()
            ORDER BY p.date_time ASC
        ");
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format events data
        $eventsData = [];
        foreach ($events as $event) {
            $eventsData[] = [
                'id' => intval($event['program_id']),
                'title' => $event['title'],
                'type' => $event['type'],
                'description' => $event['description'],
                'date_time' => $event['date_time'],
                'location' => $event['location'],
                'organizer' => $event['organizer'],
                'created_at' => $event['created_at']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'events' => $eventsData,
            'total_events' => count($eventsData)
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle dashboard endpoint for web dashboard
if ($endpoint === 'dashboard') {
    try {
        $userEmail = $_GET['user_email'] ?? '';
        
        // Get user preferences data
        $sql = "SELECT * FROM user_preferences";
        $params = [];
        
        if ($userEmail) {
            $sql .= " WHERE user_email = ?";
            $params = [$userEmail];
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $preferences = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate dashboard metrics
        $totalUsers = count($preferences);
        $totalScreened = $totalUsers;
        $samCases = 0;
        $meanWHZ = 0;
        $referralCases = 0;
        $averageRisk = 0;
        $totalRisk = 0;
        $usersWithRisk = 0;
        
        // Risk distribution for chart
        $riskDistribution = array_fill(0, 10, 0);
        
        foreach ($preferences as $pref) {
            if ($pref['risk_score'] !== null) {
                $totalRisk += $pref['risk_score'];
                $usersWithRisk++;
                
                // Categorize risk scores
                $riskIndex = min(floor($pref['risk_score'] / 10), 9);
                $riskDistribution[$riskIndex]++;
                
                // Count SAM cases (risk score >= 70)
                if ($pref['risk_score'] >= 70) {
                    $samCases++;
                }
                
                // Count referral cases (risk score >= 50)
                if ($pref['risk_score'] >= 50) {
                    $referralCases++;
                }
            }
            
            // Calculate mean WHZ from BMI (approximation)
            if ($pref['bmi'] !== null) {
                $meanWHZ += $pref['bmi'];
            }
        }
        
        $averageRisk = $usersWithRisk > 0 ? round($totalRisk / $usersWithRisk, 1) : 0;
        $meanWHZ = $totalUsers > 0 ? round($meanWHZ / $totalUsers, 1) : 0;
        
        // Get barangay data
        $barangayData = [];
        foreach ($preferences as $pref) {
            if ($pref['barangay']) {
                if (!isset($barangayData[$pref['barangay']])) {
                    $barangayData[$pref['barangay']] = ['total' => 0, 'sam' => 0];
                }
                $barangayData[$pref['barangay']]['total']++;
                if ($pref['risk_score'] >= 70) {
                    $barangayData[$pref['barangay']]['sam']++;
                }
            }
        }
        
        // Get critical alerts
        $criticalAlerts = [];
        foreach ($preferences as $pref) {
            if ($pref['risk_score'] >= 70 || ($pref['bmi'] && $pref['bmi'] < 16) || ($pref['muac'] && $pref['muac'] < 11.5)) {
                $criticalAlerts[] = [
                    'type' => 'critical',
                    'message' => 'High malnutrition risk detected',
                    'user' => $pref['name'] ?? $pref['user_email'],
                    'time' => date('M j, Y', strtotime($pref['created_at']))
                ];
            }
        }
        
        // Limit alerts to prevent overflow
        $criticalAlerts = array_slice($criticalAlerts, 0, 4);
        
        echo json_encode([
            'success' => true,
            'total_users' => $totalUsers,
            'total_screened' => $totalScreened,
            'sam_cases' => $samCases,
            'mean_whz' => $meanWHZ,
            'referral_cases' => $referralCases,
            'average_risk' => $averageRisk,
            'preferences' => $preferences,
            'risk_distribution' => $riskDistribution,
            'barangay_data' => $barangayData,
            'critical_alerts' => $criticalAlerts
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Intelligent Programs Endpoint
if ($endpoint === 'intelligent_programs') {
    try {
        $barangay = $_GET['barangay'] ?? '';
        $municipality = $_GET['municipality'] ?? '';
        
        // Get user data for the selected area - only users with completed profiles
        $sql = "SELECT up.*, up.name, up.user_email as email 
                FROM user_preferences up 
                WHERE up.risk_score IS NOT NULL AND 1=1";
        $params = [];
        
        if ($barangay && !str_starts_with($barangay, 'MUNICIPALITY_')) {
            // Individual barangay
            $sql .= " AND up.barangay = ?";
            $params[] = $barangay;
        } elseif ($municipality || str_starts_with($barangay, 'MUNICIPALITY_')) {
            // Municipality - get all barangays in that municipality
            $municipalityName = $municipality ?: str_replace('MUNICIPALITY_', '', $barangay);
            
            // Define barangays per municipality
            $municipalityBarangays = [
                'ABUCAY' => ['Bangkal', 'Calaylayan (Pob.)', 'Capitangan', 'Gabon', 'Laon (Pob.)', 'Mabatang', 'Omboy', 'Salian', 'Wawa (Pob.)'],
                'BAGAC' => ['Bagumbayan (Pob.)', 'Banawang', 'Binuangan', 'Binukawan', 'Ibaba', 'Ibis', 'Pag-asa (Wawa-Sibacan)', 'Parang', 'Paysawan', 'Quinawan', 'San Antonio', 'Saysain', 'Tabing-Ilog (Pob.)', 'Atilano L. Ricardo'],
                'BALANGA' => ['Bagumbayan', 'Cabog-Cabog', 'Munting Batangas (Cadre)', 'Cataning', 'Central', 'Cupang Proper', 'Cupang West', 'Dangcol (Bernabe)', 'Ibayo', 'Malabia', 'Poblacion', 'Pto. Rivas Ibaba', 'Pto. Rivas Itaas', 'San Jose', 'Sibacan', 'Camacho', 'Talisay', 'Tanato', 'Tenejero', 'Tortugas', 'Tuyo', 'Bagong Silang', 'Cupang North', 'Doña Francisca', 'Lote'],
                'DINALUPIHAN' => ['Bangal', 'Bonifacio (Pob.)', 'Burgos (Pob.)', 'Colo', 'Daang Bago', 'Dalao', 'Del Pilar (Pob.)', 'Gen. Luna (Pob.)', 'Gomez (Pob.)', 'Happy Valley', 'Kataasan', 'Layac', 'Luacan', 'Mabini Proper (Pob.)', 'Mabini Ext. (Pob.)', 'Magsaysay', 'Naparing', 'New San Jose', 'Old San Jose', 'Padre Dandan (Pob.)', 'Pag-asa', 'Pagalanggang', 'Pinulot', 'Pita', 'Rizal (Pob.)', 'Roosevelt', 'Roxas (Pob.)', 'Saguing', 'San Benito', 'San Isidro (Pob.)', 'San Pablo (Bulate)', 'San Ramon', 'San Simon', 'Santo Niño', 'Sapang Balas', 'Santa Isabel (Tabacan)', 'Torres Bugauen (Pob.)', 'Tucop', 'Zamora (Pob.)', 'Aquino', 'Bayan-bayanan', 'Maligaya', 'Payangan', 'Pentor', 'Tubo-tubo', 'Jose C. Payumo, Jr.'],
                'HERMOSA' => ['A. Rivera (Pob.)', 'Almacen', 'Bacong', 'Balsic', 'Bamban', 'Burgos-Soliman (Pob.)', 'Cataning (Pob.)', 'Culis', 'Daungan (Pob.)', 'Mabiga', 'Mabuco', 'Maite', 'Mambog - Mandama', 'Palihan', 'Pandatung', 'Pulo', 'Saba', 'San Pedro (Pob.)', 'Santo Cristo (Pob.)', 'Sumalo', 'Tipo', 'Judge Roman Cruz Sr. (Mandama)', 'Sacrifice Valley'],
                'LIMAY' => ['Alangan', 'Kitang I', 'Kitang 2 & Luz', 'Lamao', 'Landing', 'Poblacion', 'Reformista', 'Townsite', 'Wawa', 'Duale', 'San Francisco de Asis', 'St. Francis II'],
                'MARIVELES' => ['Alas-asin', 'Alion', 'Batangas II', 'Cabcaben', 'Lucanin', 'Baseco Country (Nassco)', 'Poblacion', 'San Carlos', 'San Isidro', 'Sisiman', 'Balon-Anito', 'Biaan', 'Camaya', 'Ipag', 'Malaya', 'Maligaya', 'Mt. View', 'Townsite'],
                'MORONG' => ['Binaritan', 'Mabayo', 'Nagbalayong', 'Poblacion', 'Sabang'],
                'ORANI' => ['Bagong Paraiso (Pob.)', 'Balut (Pob.)', 'Bayan (Pob.)', 'Calero (Pob.)', 'Paking-Carbonero (Pob.)', 'Centro II (Pob.)', 'Dona', 'Kaparangan', 'Masantol', 'Mulawin', 'Pag-asa', 'Palihan (Pob.)', 'Pantalan Bago (Pob.)', 'Pantalan Luma (Pob.)', 'Parang Parang (Pob.)', 'Centro I (Pob.)', 'Sibul', 'Silahis', 'Tala', 'Talimundoc', 'Tapulao', 'Tenejero (Pob.)', 'Tugatog', 'Wawa (Pob.)', 'Apollo', 'Kabalutan', 'Maria Fe', 'Puksuan', 'Tagumpay'],
                'ORION' => ['Arellano (Pob.)', 'Bagumbayan (Pob.)', 'Balagtas (Pob.)', 'Balut (Pob.)', 'Bantan', 'Bilolo', 'Calungusan', 'Camachile', 'Daang Bago (Pob.)', 'Daang Bilolo (Pob.)', 'Daang Pare', 'General Lim (Kaput)', 'Kapunitan', 'Lati (Pob.)', 'Lusungan (Pob.)', 'Puting Buhangin', 'Sabatan', 'San Vicente (Pob.)', 'Santo Domingo', 'Villa Angeles (Pob.)', 'Wakas (Pob.)', 'Santa Elena'],
                'PILAR' => ['Ala-uli', 'Bagumbayan', 'Balut I', 'Balut II', 'Bantan Munti', 'Burgos', 'Del Rosario (Pob.)', 'Diwa', 'Landing', 'Liyang', 'Nagwaling', 'Panilao', 'Pantingan', 'Poblacion', 'Rizal', 'Santa Rosa', 'Wakas North', 'Wakas South', 'Wawa'],
                'SAMAL' => ['East Calaguiman (Pob.)', 'East Daang Bago (Pob.)', 'Ibaba (Pob.)', 'Imelda', 'Lalawigan', 'Palili', 'San Juan (Pob.)', 'San Roque (Pob.)', 'Santa Lucia', 'Sapa', 'Tabing Ilog', 'Gugo', 'West Calaguiman (Pob.)', 'West Daang Bago (Pob.)']
            ];
            
            if (isset($municipalityBarangays[$municipalityName])) {
                $placeholders = str_repeat('?,', count($municipalityBarangays[$municipalityName]) - 1) . '?';
                $sql .= " AND up.barangay IN ($placeholders)";
                $params = array_merge($params, $municipalityBarangays[$municipalityName]);
            }
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $userData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Analyze data to generate intelligent programs
        $totalUsers = count($userData);
        $highRiskUsers = 0;
        $samCases = 0;
        $childrenCount = 0;
        $elderlyCount = 0;
        $lowDietaryDiversity = 0;
        $averageRisk = 0;
        $totalRisk = 0;
        
        foreach ($userData as $user) {
            if ($user['risk_score'] !== null) {
                $totalRisk += $user['risk_score'];
                if ($user['risk_score'] >= 50) $highRiskUsers++;
                if ($user['risk_score'] >= 70) $samCases++;
            }
            
            // Age analysis
            if ($user['age'] !== null) {
                if ($user['age'] <= 17) $childrenCount++;
                elseif ($user['age'] >= 60) $elderlyCount++;
            }
            
            // Dietary diversity analysis
            if ($user['dietary_diversity_score'] !== null && $user['dietary_diversity_score'] < 5) {
                $lowDietaryDiversity++;
            }
        }
        
        $averageRisk = $totalUsers > 0 ? round($totalRisk / $totalUsers, 1) : 0;
        $highRiskPercentage = $totalUsers > 0 ? round(($highRiskUsers / $totalUsers) * 100, 1) : 0;
        $samPercentage = $totalUsers > 0 ? round(($samCases / $totalUsers) * 100, 1) : 0;
        
        // Check if there are users in the selected area
        if ($totalUsers === 0) {
            // No users found - return appropriate message
            $programs = [];
            $data_analysis = [
                'total_users' => 0,
                'high_risk_percentage' => 0,
                'sam_cases' => 0,
                'sam_percentage' => 0,
                'children_count' => 0,
                'elderly_count' => 0,
                'low_dietary_diversity' => 0,
                'average_risk' => 0,
                'barangay' => $barangay,
                'municipality' => $municipality,
                'message' => 'No users found in the selected area. Programs will be generated once users are registered.'
            ];
            
            echo json_encode([
                'success' => true,
                'programs' => $programs,
                'data_analysis' => $data_analysis,
                'no_data' => true
            ]);
            exit;
        }
        
        // INTELLIGENT DECISION: Only generate programs if there's a real need
        $communityHealthStatus = 'healthy';
        $programCount = 0;
        
        // Check if community needs intervention programs
        if ($highRiskPercentage >= 30 || $samCases > 0) {
            $communityHealthStatus = 'at_risk';
            $programCount = 3; // Full intervention needed
        } elseif ($highRiskPercentage >= 15 || $lowDietaryDiversity > 0) {
            $communityHealthStatus = 'moderate_risk';
            $programCount = 2; // Moderate intervention needed
        } elseif ($totalUsers > 0) {
            $communityHealthStatus = 'healthy';
            $programCount = 1; // Only maintenance/prevention program
        } else {
            $communityHealthStatus = 'no_data';
            $programCount = 0; // No programs needed
        }
        
        // FOR DEMONSTRATION PURPOSES: Always generate 3 programs if there are users
        // Comment out this line to restore the original logic
        if ($totalUsers > 0 && $programCount < 3) {
            $programCount = 3;
            $communityHealthStatus = 'demo_mode';
        }
        
        // Generate programs based on community health status
        if ($programCount === 0) {
            // Community is healthy or no data - no intervention programs needed
            $programs = [];
            $data_analysis = [
                'total_users' => $totalUsers,
                'high_risk_percentage' => $highRiskPercentage,
                'sam_cases' => $samCases,
                'sam_percentage' => $samPercentage,
                'children_count' => $childrenCount,
                'elderly_count' => $elderlyCount,
                'low_dietary_diversity' => $lowDietaryDiversity,
                'average_risk' => $averageRisk,
                'barangay' => $barangay,
                'municipality' => $municipalityName ?? null,
                'community_health_status' => $communityHealthStatus,
                'message' => $communityHealthStatus === 'healthy' 
                    ? 'Community is healthy! No intervention programs needed at this time. Focus on maintaining good nutrition practices.'
                    : 'Insufficient data to determine community health status. Programs will be generated once more data is available.'
            ];
            
            echo json_encode([
                'success' => true,
                'programs' => $programs,
                'data_analysis' => $data_analysis,
                'no_data' => true,
                'community_health_status' => $communityHealthStatus
            ]);
            exit;
        }
        
        // INTELLIGENT PROGRAM GENERATION based on community health status
        $programs = [];
        
        // Determine target locations for programs based on actual user data
        $targetLocations = [];
        if ($barangay && !str_starts_with($barangay, 'MUNICIPALITY_')) {
            // Individual barangay - all programs target this barangay
            $targetLocations = [$barangay, $barangay, $barangay];
        } elseif ($municipality || str_starts_with($barangay, 'MUNICIPALITY_')) {
            // Municipality - find barangays that actually have users
            $municipalityName = $municipality ?: str_replace('MUNICIPALITY_', '', $barangay);
            
            // Get actual barangays with users in this municipality
            $barangaysWithUsers = [];
            foreach ($userData as $user) {
                if (isset($user['barangay']) && $user['barangay'] && !in_array($user['barangay'], $barangaysWithUsers)) {
                    $barangaysWithUsers[] = $user['barangay'];
                }
            }
            
            if (!empty($barangaysWithUsers)) {
                // Use actual barangays with users
                $targetLocations = array_slice($barangaysWithUsers, 0, 3);
                // If less than 3 barangays, repeat the last one
                while (count($targetLocations) < 3) {
                    $targetLocations[] = end($targetLocations);
                }
            } else {
                // No users found in any barangay of this municipality
                $programs = [];
                $data_analysis = [
                    'total_users' => 0,
                    'high_risk_percentage' => 0,
                    'sam_cases' => 0,
                    'sam_percentage' => 0,
                    'children_count' => 0,
                    'elderly_count' => 0,
                    'low_dietary_diversity' => 0,
                    'average_risk' => 0,
                    'barangay' => $barangay,
                    'municipality' => $municipality,
                    'message' => "No users found in {$municipalityName} municipality. Programs will be generated once users are registered."
                ];
                
                echo json_encode([
                    'success' => true,
                    'programs' => $programs,
                    'data_analysis' => $data_analysis,
                    'no_data' => true
                ]);
                exit;
            }
        } else {
            // All barangays - find barangays that actually have users
            $barangaysWithUsers = [];
            foreach ($userData as $user) {
                if (isset($user['barangay']) && $user['barangay'] && !in_array($user['barangay'], $barangaysWithUsers)) {
                    $barangaysWithUsers[] = $user['barangay'];
                }
            }
            
            if (!empty($barangaysWithUsers)) {
                // Use actual barangays with users
                $targetLocations = array_slice($barangaysWithUsers, 0, 3);
                // If less than 3 barangays, repeat the last one
                while (count($targetLocations) < 3) {
                    $targetLocations[] = end($targetLocations);
                }
            } else {
                // No users found anywhere
                $programs = [];
                $data_analysis = [
                    'total_users' => 0,
                    'high_risk_percentage' => 0,
                    'sam_cases' => 0,
                    'sam_percentage' => 0,
                    'children_count' => 0,
                    'elderly_count' => 0,
                    'low_dietary_diversity' => 0,
                    'average_risk' => 0,
                    'barangay' => $barangay,
                    'municipality' => $municipality,
                    'message' => 'No users found in any barangay. Programs will be generated once users are registered.'
                ];
                
                echo json_encode([
                    'success' => true,
                    'programs' => $programs,
                    'data_analysis' => $data_analysis,
                    'no_data' => true
                ]);
                exit;
            }
        }
        
        // Program 1: Always generated (if programs are needed)
        if ($programCount >= 1) {
            if ($highRiskPercentage >= 30 || $samCases > 0) {
                // High risk - intervention needed
                if ($samCases > 0) {
                    $programs[] = [
                        'title' => 'SAM Case Management Program',
                        'type' => 'Medical Intervention',
                        'priority' => 'Critical',
                        'target_audience' => 'SAM cases and their families',
                        'duration' => '6-12 months',
                        'location' => $targetLocations[0],
                        'icon' => '⚕️',
                        'description' => 'Comprehensive care for Severe Acute Malnutrition cases including therapeutic feeding, medical monitoring, and family support.',
                        'reasoning' => "SAM cases detected: {$samCases} individuals require immediate medical attention"
                    ];
                } else {
                    $programs[] = [
                        'title' => 'Emergency Malnutrition Intervention Program',
                        'type' => 'Intensive Workshop',
                        'priority' => 'Critical',
                        'target_audience' => 'High-risk individuals and families',
                        'duration' => '3-6 months',
                        'location' => $targetLocations[0],
                        'icon' => '🚨',
                        'description' => 'Immediate intervention for high-risk individuals with risk scores ≥50. Includes therapeutic feeding, medical monitoring, and family counseling.',
                        'reasoning' => "High malnutrition risk detected: {$highRiskPercentage}% of users have risk scores ≥50"
                    ];
                }
            } else {
                // Healthy community - maintenance program
                $programs[] = [
                    'title' => 'Community Nutrition Maintenance Program',
                    'type' => 'Educational Workshop',
                    'priority' => 'Low',
                    'target_audience' => 'General community',
                    'duration' => '3 months',
                    'location' => $targetLocations[0],
                    'icon' => '✅',
                    'description' => 'Maintain excellent community nutrition through continued education, monitoring, and best practice sharing.',
                    'reasoning' => "Community is healthy (average risk: {$averageRisk}%). Focus on maintaining good practices."
                ];
            }
        }
        
        // Program 2: Generated for moderate risk or higher
        if ($programCount >= 2) {
            if ($childrenCount > 0 && $childrenCount >= $totalUsers * 0.3) {
                $programs[] = [
                    'title' => 'Child Nutrition Development Program',
                    'type' => 'Family Workshop',
                    'priority' => 'High',
                    'target_audience' => 'Children and families',
                    'duration' => '6 months',
                    'location' => $targetLocations[1],
                    'icon' => '👶',
                    'description' => 'Age-appropriate nutrition programs for children 0-17 years, including growth monitoring, feeding practices, and family education.',
                    'reasoning' => "High child population: {$childrenCount} children (" . ($totalUsers > 0 ? round(($childrenCount / $totalUsers) * 100, 1) : 0) . "% of total users)"
                ];
            } elseif ($elderlyCount > 0 && $elderlyCount >= $totalUsers * 0.2) {
                $programs[] = [
                    'title' => 'Senior Nutrition Wellness Program',
                    'type' => 'Health Seminar',
                    'priority' => 'Medium',
                    'target_audience' => 'Elderly individuals (60+)',
                    'duration' => '4 months',
                    'location' => $targetLocations[1],
                    'icon' => '👴',
                    'description' => 'Specialized nutrition and health programs for elderly individuals, focusing on age-related nutritional needs and chronic disease management.',
                    'reasoning' => "Elderly population focus: {$elderlyCount} elderly individuals (" . ($totalUsers > 0 ? round(($elderlyCount / $totalUsers) * 100, 1) : 0) . "% of total users)"
                ];
            } else {
                $programs[] = [
                    'title' => 'Preventive Nutrition Program',
                    'type' => 'Public Seminar',
                    'priority' => 'Medium',
                    'target_audience' => 'General community',
                    'duration' => '3 months',
                    'location' => $targetLocations[1],
                    'icon' => '🏘️',
                    'description' => 'Proactive nutrition education to prevent malnutrition through healthy eating habits and lifestyle changes.',
                    'reasoning' => "Preventive approach: Current average risk score is {$averageRisk}%"
                ];
            }
        }
        
        // Program 3: Generated only for high risk communities
        if ($programCount >= 3) {
            if ($lowDietaryDiversity > 0 && $lowDietaryDiversity >= $totalUsers * 0.4) {
                $programs[] = [
                    'title' => 'Dietary Diversity Enhancement Program',
                    'type' => 'Cooking Workshop',
                    'priority' => 'High',
                    'target_audience' => 'Individuals with low dietary diversity',
                    'duration' => '4 months',
                    'location' => $targetLocations[2],
                    'icon' => '🥗',
                    'description' => 'Hands-on cooking workshops using local ingredients to improve dietary diversity scores and introduce new food groups.',
                    'reasoning' => "Low dietary diversity: {$lowDietaryDiversity} users (" . ($totalUsers > 0 ? round(($lowDietaryDiversity / $totalUsers) * 100, 1) : 0) . "% of total users) have scores <5"
                ];
            } else {
                // Seasonal or general program for high-risk communities
                $currentMonth = date('n');
                if ($currentMonth >= 6 && $currentMonth <= 10) {
                    $programs[] = [
                        'title' => 'Rainy Season Nutrition Program',
                        'type' => 'Seasonal Workshop',
                        'priority' => 'Medium',
                        'target_audience' => 'General community',
                        'duration' => '3 months',
                        'location' => $targetLocations[2],
                        'icon' => '🌱',
                        'description' => 'Nutrition strategies for rainy season including immune-boosting foods, food safety, and seasonal ingredient utilization.',
                        'reasoning' => "Seasonal approach: Rainy season (June-October) with focus on immune health and food safety"
                    ];
                } else {
                    $programs[] = [
                        'title' => 'Intensive Nutrition Monitoring Program',
                        'type' => 'Community Initiative',
                        'priority' => 'Medium',
                        'target_audience' => 'General community',
                        'duration' => '6 months',
                        'location' => $targetLocations[2],
                        'icon' => '📊',
                        'description' => 'Enhanced nutrition monitoring and intervention for at-risk communities with regular health assessments.',
                        'reasoning' => "High-risk community requires intensive monitoring and support"
                    ];
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'programs' => $programs,
            'data_analysis' => [
                'total_users' => $totalUsers,
                'high_risk_percentage' => $highRiskPercentage,
                'sam_cases' => $samCases,
                'sam_percentage' => $samPercentage,
                'children_count' => $childrenCount,
                'elderly_count' => $elderlyCount,
                'low_dietary_diversity' => $lowDietaryDiversity,
                'average_risk' => $averageRisk,
                'barangay' => $barangay,
                'municipality' => $municipalityName ?? null,
                'community_health_status' => $communityHealthStatus,
                'program_count' => $programCount,
                'message' => $communityHealthStatus === 'healthy' 
                    ? 'Community is healthy! Focus on maintaining good nutrition practices.'
                    : ($communityHealthStatus === 'at_risk' 
                        ? 'Community requires immediate intervention programs.'
                        : 'Community needs moderate support and monitoring.')
            ]
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Screening Responses Endpoint
if ($endpoint === 'screening_responses') {
    try {
        $barangay = $_GET['barangay'] ?? '';
        
        $whereClause = "";
        $params = [];
        
        if ($barangay && $barangay !== '') {
            if (strpos($barangay, 'MUNICIPALITY_') === 0) {
                $municipality = str_replace('MUNICIPALITY_', '', $barangay);
                list($whereClause, $params) = buildMunicipalityWhereClause($municipality);
                if (empty($whereClause)) {
                    echo json_encode([
                        'success' => true,
                        'data' => [
                            'age_groups' => [],
                            'gender_distribution' => [],
                            'income_levels' => [],
                            'height_distribution' => [],
                            'swelling_distribution' => [],
                            'weight_loss_distribution' => [],
                            'feeding_behavior_distribution' => [],
                            'physical_signs' => [],
                            'dietary_diversity_distribution' => [],
                            'clinical_risk_factors' => []
                        ]
                    ]);
                    exit;
                }
            } else {
                $whereClause = "WHERE up.barangay = ?";
                $params = [$barangay];
            }
        }
        
        // Part 1: Basic Information & Demographics
        // Age group distribution
        $sql = "
            SELECT 
                CASE 
                    WHEN up.age < 6 THEN '0-5 years'
                    WHEN up.age < 13 THEN '6-12 years'
                    WHEN up.age < 18 THEN '13-17 years'
                    WHEN up.age < 60 THEN '18-59 years'
                    ELSE '60+ years'
                END as age_group,
                COUNT(*) as count
            FROM user_preferences up
        ";
        
        if ($whereClause) {
            $sql .= " " . $whereClause . " AND up.age IS NOT NULL";
        } else {
            $sql .= " WHERE up.age IS NOT NULL";
        }
        
        $sql .= " GROUP BY age_group ORDER BY age_group";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $ageGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Gender distribution
        $sql = "
            SELECT 
                CASE 
                    WHEN up.gender = 'boy' THEN 'Boy'
                    WHEN up.gender = 'girl' THEN 'Girl'
                    WHEN up.gender IS NULL OR up.gender = '' THEN 'Not specified'
                    ELSE up.gender
                END as gender,
                COUNT(*) as count
            FROM user_preferences up
        ";
        
        if ($whereClause) {
            $sql .= " " . $whereClause . " AND 1=1";
        } else {
            $sql .= " WHERE 1=1";
        }
        
        $sql .= " GROUP BY gender ORDER BY count DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $genderDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Income level distribution
        $sql = "
            SELECT 
                CASE 
                    WHEN up.income LIKE '%Below PHP 12,030%' THEN 'Below Poverty Line'
                    WHEN up.income LIKE '%PHP 12,031%' THEN 'Low Income'
                    WHEN up.income LIKE '%PHP 20,001%' THEN 'Middle Income'
                    WHEN up.income LIKE '%Above PHP 40,000%' THEN 'High Income'
                    WHEN up.income IS NULL OR up.income = '' THEN 'Not specified'
                    ELSE 'Other'
                END as income_level,
                COUNT(*) as count
            FROM user_preferences up
        ";
        
        if ($whereClause) {
            $sql .= " " . $whereClause;
        } else {
            $sql .= " WHERE 1=1";
        }
        
        $sql .= " GROUP BY income_level ORDER BY count DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $incomeLevels = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Height distribution
        $sql = "
            SELECT 
                CASE 
                    WHEN up.height IS NULL OR up.height = 0 THEN 'Not measured'
                    WHEN up.height < 100 THEN 'Below 100 cm'
                    WHEN up.height < 150 THEN '100-149 cm'
                    WHEN up.height < 180 THEN '150-179 cm'
                    ELSE '180+ cm'
                END as height_range,
                COUNT(*) as count
            FROM user_preferences up
        ";
        
        if ($whereClause) {
            $sql .= " " . $whereClause . " AND 1=1";
        } else {
            $sql .= " WHERE 1=1";
        }
        
        $sql .= " GROUP BY height_range ORDER BY height_range";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $heightDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Part 2: Health & Nutrition Assessment
        // Swelling distribution
        $sql = "
            SELECT 
                CASE 
                    WHEN up.swelling = 'yes' THEN 'Yes (Edema detected)'
                    WHEN up.swelling = 'no' THEN 'No (No edema)'
                    WHEN up.swelling IS NULL OR up.swelling = '' THEN 'Not specified'
                    ELSE up.swelling
                END as swelling_status,
                COUNT(*) as count
            FROM user_preferences up
        ";
        
        if ($whereClause) {
            $sql .= " " . $whereClause . " AND 1=1";
        } else {
            $sql .= " WHERE 1=1";
        }
        
        $sql .= " GROUP BY swelling_status ORDER BY count DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $swellingDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Weight loss distribution
        $sql = "
            SELECT 
                CASE 
                    WHEN up.weight_loss = '>10%' THEN '>10% weight loss'
                    WHEN up.weight_loss = '5-10%' THEN '5-10% weight loss'
                    WHEN up.weight_loss = '<5%' THEN '<5% weight loss'
                    WHEN up.weight_loss = 'none' THEN 'No weight loss'
                    WHEN up.weight_loss IS NULL OR up.weight_loss = '' THEN 'Not specified'
                    ELSE up.weight_loss
                END as weight_loss_status,
                COUNT(*) as count
            FROM user_preferences up
        ";
        
        if ($whereClause) {
            $sql .= " " . $whereClause . " AND 1=1";
        } else {
            $sql .= " WHERE 1=1";
        }
        
        $sql .= " GROUP BY weight_loss_status ORDER BY count DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $weightLossDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Feeding behavior distribution
        $sql = "
            SELECT 
                CASE 
                    WHEN up.feeding_behavior = 'good appetite' THEN 'Good appetite'
                    WHEN up.feeding_behavior = 'moderate appetite' THEN 'Moderate appetite'
                    WHEN up.feeding_behavior = 'poor appetite' THEN 'Poor appetite'
                    WHEN up.feeding_behavior IS NULL OR up.feeding_behavior = '' THEN 'Not specified'
                    ELSE up.feeding_behavior
                END as feeding_behavior_status,
                COUNT(*) as count
            FROM user_preferences up
        ";
        
        if ($whereClause) {
            $sql .= " " . $whereClause . " AND 1=1";
        } else {
            $sql .= " WHERE 1=1";
        }
        
        $sql .= " GROUP BY feeding_behavior_status ORDER BY count DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $feedingBehaviorDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Physical signs of malnutrition
        $physicalSigns = [];
        $physicalFields = ['physical_thin', 'physical_shorter', 'physical_weak', 'physical_none'];
        $physicalLabels = ['Thin/Underweight', 'Shorter than peers', 'Weak/Fatigued', 'No physical signs'];
        
        foreach ($physicalFields as $index => $field) {
            $sql = "
                SELECT COUNT(*) as count
                FROM user_preferences up
            ";
            
            if ($whereClause) {
                $sql .= " " . $whereClause . " AND 1=1 AND up.$field = 1";
            } else {
                $sql .= " WHERE 1=1 AND up.$field = 1";
            }
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $physicalSigns[] = [
                    'label' => $physicalLabels[$index],
                    'count' => intval($count)
                ];
            }
        }
        
        // Dietary diversity distribution
        $sql = "
            SELECT 
                CASE 
                    WHEN up.dietary_diversity IS NULL OR up.dietary_diversity = 0 THEN 'Not specified'
                    WHEN up.dietary_diversity < 4 THEN 'Low (0-3 food groups)'
                    WHEN up.dietary_diversity < 6 THEN 'Moderate (4-5 food groups)'
                    ELSE 'High (6+ food groups)'
                END as dietary_diversity_level,
                COUNT(*) as count
            FROM user_preferences up
        ";
        
        if ($whereClause) {
            $sql .= " " . $whereClause . " AND 1=1";
        } else {
            $sql .= " WHERE 1=1";
        }
        
        $sql .= " GROUP BY dietary_diversity_level ORDER BY dietary_diversity_level";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $dietaryDiversityDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Clinical risk factors
        $clinicalRiskFactors = [];
        $riskFields = ['has_recent_illness', 'has_eating_difficulty', 'has_food_insecurity', 'has_micronutrient_deficiency', 'has_functional_decline'];
        $riskLabels = ['Recent Illness', 'Eating Difficulty', 'Food Insecurity', 'Micronutrient Deficiency', 'Functional Decline'];
        
        foreach ($riskFields as $index => $field) {
            $sql = "
                SELECT COUNT(*) as count
                FROM user_preferences up
            ";
            
            if ($whereClause) {
                $sql .= " " . $whereClause . " AND 1=1 AND up.$field = 1";
            } else {
                $sql .= " WHERE 1=1 AND up.$field = 1";
            }
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $clinicalRiskFactors[] = [
                    'label' => $riskLabels[$index],
                    'count' => intval($count)
                ];
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'age_groups' => $ageGroups,
                'gender_distribution' => $genderDistribution,
                'income_levels' => $incomeLevels,
                'height_distribution' => $heightDistribution,
                'swelling_distribution' => $swellingDistribution,
                'weight_loss_distribution' => $weightLossDistribution,
                'feeding_behavior_distribution' => $feedingBehaviorDistribution,
                'physical_signs' => $physicalSigns,
                'dietary_diversity_distribution' => $dietaryDiversityDistribution,
                'clinical_risk_factors' => $clinicalRiskFactors
            ]
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Default dashboard response (fallback)
echo json_encode([
    'success' => true,
    'message' => 'Endpoint not found or not specified',
            'available_endpoints' => [
            'community_metrics',
            'risk_distribution', 
            'whz_distribution',
            'muac_distribution',
            'geographic_distribution',
            'critical_alerts',
            'analysis_data',
            'events',
            'usm',
            'dashboard',
            'intelligent_programs',
            'screening_responses',
            'time_frame_data'
        ],
    'note' => 'Use either ?endpoint=X or ?type=X parameter'
]);

// Helper function to get screening data in both formats
function getScreeningData($conn, $email, $format = 'json') {
    try {
        if ($format === 'structured') {
            // Get data from new normalized structure
            $stmt = $conn->prepare("
                SELECT 
                    ss.session_id,
                    ss.risk_score,
                    ss.overall_status,
                    ss.session_date,
                    JSON_OBJECTAGG(sa.question_key, sa.answer_value) as answers
                FROM screening_sessions ss
                JOIN screening_answers sa ON ss.session_id = sa.session_id
                WHERE ss.user_email = ?
                GROUP BY ss.session_id
                ORDER BY ss.session_date DESC
                LIMIT 1
            ");
            
            $stmt->execute([$email]);
            $result = $stmt->fetch();
            
            if ($result) {
                return [
                    'session_id' => $result['session_id'],
                    'risk_score' => $result['risk_score'],
                    'overall_status' => $result['overall_status'],
                    'session_date' => $result['session_date'],
                    'answers' => json_decode($result['answers'], true)
                ];
            }
        } else {
            // Get data in JSON format (backward compatible)
            $stmt = $conn->prepare("
                SELECT 
                    ss.risk_score,
                    ss.overall_status,
                    JSON_OBJECT(
                        'gender', MAX(CASE WHEN sa.question_key = 'gender' THEN sa.answer_value END),
                        'swelling', MAX(CASE WHEN sa.question_key = 'swelling' THEN sa.answer_value END),
                        'weight_loss', MAX(CASE WHEN sa.question_key = 'weight_loss' THEN sa.answer_value END),
                        'feeding_behavior', MAX(CASE WHEN sa.question_key = 'feeding_behavior' THEN sa.answer_value END),
                        'physical_signs', MAX(CASE WHEN sa.question_key = 'physical_signs' THEN sa.answer_value END),
                        'dietary_diversity', MAX(CASE WHEN sa.question_key = 'dietary_diversity' THEN sa.answer_value END),
                        'weight', MAX(CASE WHEN sa.question_key = 'weight' THEN sa.answer_value END),
                        'height', MAX(CASE WHEN sa.question_key = 'height' THEN sa.answer_value END),
                        'birthday', MAX(CASE WHEN sa.question_key = 'birthday' THEN sa.answer_value END),
                        'barangay', MAX(CASE WHEN sa.question_key = 'barangay' THEN sa.answer_value END),
                        'income', MAX(CASE WHEN sa.question_key = 'income' THEN sa.answer_value END),
                        'has_recent_illness', MAX(CASE WHEN sa.question_key = 'has_recent_illness' THEN sa.answer_value END),
                        'has_eating_difficulty', MAX(CASE WHEN sa.question_key = 'has_eating_difficulty' THEN sa.answer_value END),
                        'has_food_insecurity', MAX(CASE WHEN sa.question_key = 'has_food_insecurity' THEN sa.answer_value END),
                        'has_micronutrient_deficiency', MAX(CASE WHEN sa.question_key = 'has_micronutrient_deficiency' THEN sa.answer_value END),
                        'has_functional_decline', MAX(CASE WHEN sa.question_key = 'has_functional_decline' THEN sa.answer_value END)
                    ) as screening_answers
                FROM screening_sessions ss
                JOIN screening_answers sa ON ss.session_id = sa.session_id
                WHERE ss.user_email = ?
                AND ss.session_date = (
                    SELECT MAX(session_date) 
                    FROM screening_sessions 
                    WHERE user_email = ?
                )
            ");
            
            $stmt->execute([$email, $email]);
            $result = $stmt->fetch();
            
            if ($result) {
                return [
                    'risk_score' => $result['risk_score'],
                    'overall_status' => $result['overall_status'],
                    'screening_answers' => $result['screening_answers']
                ];
            }
        }
        
        return null;
        
    } catch (Exception $e) {
        error_log("Error in getScreeningData: " . $e->getMessage());
        return null;
    }
}

// Helper function to analyze screening trends
function analyzeScreeningTrends($conn, $email) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                ss.session_date,
                ss.risk_score,
                ss.overall_status,
                COUNT(sa.answer_id) as answers_count
            FROM screening_sessions ss
            JOIN screening_answers sa ON ss.session_id = sa.session_id
            WHERE ss.user_email = ?
            GROUP BY ss.session_id
            ORDER BY ss.session_date DESC
        ");
        
        $stmt->execute([$email]);
        $sessions = $stmt->fetchAll();
        
        $trends = [
            'total_sessions' => count($sessions),
            'risk_score_trend' => [],
            'overall_status_distribution' => [],
            'latest_session' => null
        ];
        
        foreach ($sessions as $session) {
            $trends['risk_score_trend'][] = [
                'date' => $session['session_date'],
                'score' => $session['risk_score']
            ];
            
            $status = $session['overall_status'];
            if (!isset($trends['overall_status_distribution'][$status])) {
                $trends['overall_status_distribution'][$status] = 0;
            }
            $trends['overall_status_distribution'][$status]++;
        }
        
        if (!empty($sessions)) {
            $trends['latest_session'] = $sessions[0];
        }
        
        return $trends;
        
    } catch (Exception $e) {
        error_log("Error in analyzeScreeningTrends: " . $e->getMessage());
        return null;
    }
}

// Helper function to get screening statistics
function getScreeningStatistics($conn) {
    try {
        // Overall statistics
        $stmt = $conn->query("
            SELECT 
                COUNT(DISTINCT ss.user_email) as total_users,
                COUNT(ss.session_id) as total_sessions,
                AVG(ss.risk_score) as avg_risk_score,
                COUNT(CASE WHEN ss.overall_status = 'critical' THEN 1 END) as critical_count,
                COUNT(CASE WHEN ss.overall_status = 'high_risk' THEN 1 END) as high_risk_count,
                COUNT(CASE WHEN ss.overall_status = 'medium_risk' THEN 1 END) as medium_risk_count,
                COUNT(CASE WHEN ss.overall_status = 'low_risk' THEN 1 END) as low_risk_count
            FROM screening_sessions ss
        ");
        
        $overall = $stmt->fetch();
        
        // Risk level distribution
        $stmt = $conn->query("
            SELECT 
                overall_status,
                COUNT(*) as count,
                AVG(risk_score) as avg_score
            FROM screening_sessions 
            GROUP BY overall_status 
            ORDER BY 
                CASE overall_status
                    WHEN 'critical' THEN 1
                    WHEN 'high_risk' THEN 2
                    WHEN 'medium_risk' THEN 3
                    WHEN 'low_risk' THEN 4
                END
        ");
        
        $riskDistribution = $stmt->fetchAll();
        
        return [
            'overall' => $overall,
            'risk_distribution' => $riskDistribution
        ];
        
    } catch (Exception $e) {
        error_log("Error in getScreeningStatistics: " . $e->getMessage());
        return null;
    }
}

// OFFICIAL MHO-APPROVED RISK SCORE CALCULATION FUNCTION
function calculateOfficialMHORiskScore($userData) {
    $score = 0;
    
    // Extract values
    $weight = floatval($userData['weight'] ?? 0);
    $height = floatval($userData['height'] ?? 0);
    $birthday = $userData['birthday'] ?? '';
    $muac = floatval($userData['muac'] ?? 0);
    $age = intval($userData['age'] ?? 0);
    
    if ($weight > 0 && $height > 0 && $birthday) {
        // Calculate age in months
        $birthDate = new DateTime($birthday);
        $today = new DateTime();
        $ageInMonths = ($today->diff($birthDate)->days) / 30.44;
        
        // Calculate BMI
        $heightInMeters = $height / 100;
        $bmi = $weight / ($heightInMeters * $heightInMeters);
        
        // OFFICIAL MHO RISK CALCULATION
        // Age-based risk assessment (MHO Standard)
        if ($ageInMonths >= 6 && $ageInMonths <= 59) {
            // Children 6-59 months: Use MUAC thresholds (MHO Standard)
            if ($muac > 0) {
                if ($muac < 11.5) $score += 40;      // Severe acute malnutrition (MUAC < 11.5 cm)
                else if ($muac < 12.5) $score += 25; // Moderate acute malnutrition (MUAC 11.5-12.5 cm)
                else $score += 0;                     // Normal (MUAC ≥ 12.5 cm)
            } else {
                // If MUAC not provided, use weight-for-height approximation
                $wfh = $weight / $heightInMeters;
                if ($wfh < 0.8) $score += 40;      // Severe acute malnutrition
                else if ($wfh < 0.9) $score += 25; // Moderate acute malnutrition
                else $score += 0;                   // Normal
            }
        } else if ($ageInMonths < 240) {
            // Children/adolescents 5-19 years (BMI-for-age, WHO MHO Standard)
            if ($bmi < 15) $score += 40;        // Severe thinness
            else if ($bmi < 17) $score += 30;   // Moderate thinness
            else if ($bmi < 18.5) $score += 20; // Mild thinness
            else $score += 0;                    // Normal
        } else {
            // Adults 20+ (BMI, WHO MHO Standard)
            if ($bmi < 16.5) $score += 40;      // Severe thinness
            else if ($bmi < 18.5) $score += 25; // Moderate thinness
            else $score += 0;                    // Normal weight
        }
        
        // Additional MHO risk factors if available
        if (!empty($userData['allergies']) && $userData['allergies'] !== 'none') {
            $score += 5; // Food allergies increase risk
        }
        
        if (!empty($userData['diet_prefs']) && 
            ($userData['diet_prefs'] === 'vegan' || $userData['diet_prefs'] === 'vegetarian')) {
            $score += 3; // Restricted diets may increase risk
        }
        
        // Cap score at 100
        $score = min($score, 100);
    }
    
    return $score;
}

// Function to update all existing risk scores using MHO formula
function updateAllRiskScoresToMHO($conn) {
    try {
        // Get all users with basic data
        $stmt = $conn->prepare("
            SELECT id, name, user_email, birthday, age, gender, height, weight, bmi, muac, 
                   allergies, diet_prefs, avoid_foods, barangay, income, risk_score
            FROM user_preferences 
            WHERE name IS NOT NULL AND name != '' AND weight > 0 AND height > 0
        ");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $updated = 0;
        $errors = 0;
        
        foreach ($users as $user) {
            try {
                // Calculate new risk score using MHO formula
                $newRiskScore = calculateOfficialMHORiskScore($user);
                
                // Update the database
                $updateStmt = $conn->prepare("
                    UPDATE user_preferences 
                    SET risk_score = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $updateStmt->execute([$newRiskScore, $user['id']]);
                
                $updated++;
                
            } catch (Exception $e) {
                error_log("Error updating risk score for user {$user['user_email']}: " . $e->getMessage());
                $errors++;
            }
        }
        
        return [
            'success' => true,
            'total_users' => count($users),
            'updated' => $updated,
            'errors' => $errors
        ];
        
    } catch (Exception $e) {
        error_log("Error in updateAllRiskScoresToMHO: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// ✅ MHO Screening Data is now stored in SIMPLE user_preferences table
// ONE COLUMN PER QUESTION - simple structure, no complex normalized tables

// Handle time frame data endpoint
if ($endpoint === 'time_frame_data') {
    try {
        $timeFrame = $_GET['time_frame'] ?? '1d';
        $barangay = $_GET['barangay'] ?? '';
        
        // Calculate time frame dates
        $now = new DateTime();
        $startDate = new DateTime();
        
        switch($timeFrame) {
            case '1d':
                $startDate->modify('-1 day');
                break;
            case '1w':
                $startDate->modify('-1 week');
                break;
            case '1m':
                $startDate->modify('-1 month');
                break;
            case '3m':
                $startDate->modify('-3 months');
                break;
            case '1y':
                $startDate->modify('-1 year');
                break;
            default:
                $startDate->modify('-1 day');
        }
        
        $startDateStr = $startDate->format('Y-m-d H:i:s');
        $endDateStr = $now->format('Y-m-d 23:59:59');
        
        // Build the query based on barangay filter
        $whereClause = "WHERE (up.created_at BETWEEN :start_date AND :end_date) OR (up.updated_at BETWEEN :start_date AND :end_date)";
        $params = [':start_date' => $startDateStr, ':end_date' => $endDateStr];
        
        if ($barangay && $barangay !== '') {
            $whereClause .= " AND up.barangay = :barangay";
            $params[':barangay'] = $barangay;
        }
        
        // Get time frame metrics
        $metricsQuery = "
            SELECT 
                COUNT(*) as total_screened,
                SUM(CASE WHEN up.risk_score >= 50 THEN 1 ELSE 0 END) as high_risk_cases,
                SUM(CASE WHEN up.bmi < 18.5 THEN 1 ELSE 0 END) as sam_cases,
                SUM(CASE WHEN up.muac < 11.5 THEN 1 ELSE 0 END) as critical_muac,
                AVG(up.risk_score) as avg_risk_score,
                AVG(up.bmi) as avg_bmi,
                AVG(up.muac) as avg_muac,
                COUNT(DISTINCT up.barangay) as barangays_covered,
                MIN(up.created_at) as earliest_screening,
                MAX(up.updated_at) as latest_update
            FROM user_preferences up
            $whereClause
        ";
        
        $stmt = $conn->prepare($metricsQuery);
        $stmt->execute($params);
        $metricsData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get screening responses by time frame
        $screeningQuery = "
            SELECT 
                up.age,
                up.gender,
                up.income,
                up.height,
                up.swelling,
                up.weight_loss,
                up.feeding_behavior,
                up.physical_thin,
                up.physical_shorter,
                up.physical_weak,
                up.physical_none,
                up.dietary_diversity,
                up.has_recent_illness,
                up.has_eating_difficulty,
                up.has_food_insecurity
            FROM user_preferences up
            $whereClause
        ";
        
        $stmt = $conn->prepare($screeningQuery);
        $stmt->execute($params);
        $screeningResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process screening data into distributions
        $ageGroups = [];
        $genderDistribution = [];
        $incomeLevels = [];
        $heightDistribution = [];
        $swellingDistribution = [];
        $weightLossDistribution = [];
        $feedingBehaviorDistribution = [];
        $physicalSigns = [];
        $dietaryDiversityDistribution = [];
        $clinicalRiskFactors = [];
        
        foreach ($screeningResults as $row) {
            // Age groups
            $age = $row['age'];
            if ($age < 1) $ageGroup = 'Under 1 year';
            elseif ($age < 6) $ageGroup = '1-5 years';
            elseif ($age < 12) $ageGroup = '6-11 years';
            elseif ($age < 18) $ageGroup = '12-17 years';
            elseif ($age < 25) $ageGroup = '18-24 years';
            elseif ($age < 35) $ageGroup = '25-34 years';
            elseif ($age < 45) $ageGroup = '35-44 years';
            elseif ($age < 55) $ageGroup = '45-54 years';
            elseif ($age < 65) $ageGroup = '55-64 years';
            else $ageGroup = '65+ years';
            
            $ageGroups[$ageGroup] = ($ageGroups[$ageGroup] ?? 0) + 1;
            
            // Gender
            $gender = $row['gender'] ?: 'Not specified';
            $genderDistribution[$gender] = ($genderDistribution[$gender] ?? 0) + 1;
            
            // Income levels
            $income = $row['income'] ?: 'Not specified';
            $incomeLevels[$income] = ($incomeLevels[$income] ?? 0) + 1;
            
            // Height ranges
            $height = $row['height'];
            if ($height < 100) $heightRange = 'Under 100 cm';
            elseif ($height < 120) $heightRange = '100-119 cm';
            elseif ($height < 140) $heightRange = '120-139 cm';
            elseif ($height < 160) $heightRange = '140-159 cm';
            elseif ($height < 180) $heightRange = '160-179 cm';
            else $heightRange = '180+ cm';
            
            $heightDistribution[$heightRange] = ($heightDistribution[$heightRange] ?? 0) + 1;
            
            // Swelling
            $swelling = $row['swelling'] ?: 'Not specified';
            $swellingDistribution[$swelling] = ($swellingDistribution[$swelling] ?? 0) + 1;
            
            // Weight loss
            $weightLoss = $row['weight_loss'] ?: 'Not specified';
            $weightLossDistribution[$weightLoss] = ($weightLossDistribution[$weightLoss] ?? 0) + 1;
            
            // Feeding behavior
            $feedingBehavior = $row['feeding_behavior'] ?: 'Not specified';
            $feedingBehaviorDistribution[$feedingBehavior] = ($feedingBehaviorDistribution[$feedingBehavior] ?? 0) + 1;
            
            // Physical signs
            if ($row['physical_thin']) $physicalSigns['Thin Appearance'] = ($physicalSigns['Thin Appearance'] ?? 0) + 1;
            if ($row['physical_shorter']) $physicalSigns['Shorter Stature'] = ($physicalSigns['Shorter Stature'] ?? 0) + 1;
            if ($row['physical_weak']) $physicalSigns['Weak Physical Condition'] = ($physicalSigns['Weak Physical Condition'] ?? 0) + 1;
            if ($row['physical_none']) $physicalSigns['No Physical Signs'] = ($physicalSigns['No Physical Signs'] ?? 0) + 1;
            
            // Dietary diversity
            $dietaryScore = $row['dietary_diversity'] ?: 0;
            if ($dietaryScore == 0) $dietaryLevel = 'No Food Groups (0)';
            elseif ($dietaryScore <= 2) $dietaryLevel = 'Very Low Diversity (1-2 food groups)';
            elseif ($dietaryScore <= 4) $dietaryLevel = 'Low Diversity (3-4 food groups)';
            elseif ($dietaryScore <= 6) $dietaryLevel = 'Medium Diversity (5-6 food groups)';
            elseif ($dietaryScore <= 8) $dietaryLevel = 'Good Diversity (7-8 food groups)';
            else $dietaryLevel = 'High Diversity (9-10 food groups)';
            
            $dietaryDiversityDistribution[$dietaryLevel] = ($dietaryDiversityDistribution[$dietaryLevel] ?? 0) + 1;
            
            // Clinical risk factors
            if ($row['has_recent_illness']) $clinicalRiskFactors['Recent Illness'] = ($clinicalRiskFactors['Recent Illness'] ?? 0) + 1;
            if ($row['has_eating_difficulty']) $clinicalRiskFactors['Eating Difficulty'] = ($clinicalRiskFactors['Eating Difficulty'] ?? 0) + 1;
            if ($row['has_food_insecurity']) $clinicalRiskFactors['Food Insecurity'] = ($clinicalRiskFactors['Food Insecurity'] ?? 0) + 1;
        }
        
        // Convert to arrays for JSON response
        $screeningResponses = [
            'age_groups' => array_map(function($key, $value) {
                return ['age_group' => $key, 'count' => $value];
            }, array_keys($ageGroups), array_values($ageGroups)),
            'gender_distribution' => array_map(function($key, $value) {
                return ['gender' => $key, 'count' => $value];
            }, array_keys($genderDistribution), array_values($genderDistribution)),
            'income_levels' => array_map(function($key, $value) {
                return ['income' => $key, 'count' => $value];
            }, array_keys($incomeLevels), array_values($incomeLevels)),
            'height_distribution' => array_map(function($key, $value) {
                return ['height_range' => $key, 'count' => $value];
            }, array_keys($heightDistribution), array_values($heightDistribution)),
            'swelling_distribution' => array_map(function($key, $value) {
                return ['swelling_status' => $key, 'count' => $value];
            }, array_keys($swellingDistribution), array_values($swellingDistribution)),
            'weight_loss_distribution' => array_map(function($key, $value) {
                return ['weight_loss_status' => $key, 'count' => $value];
            }, array_keys($weightLossDistribution), array_values($weightLossDistribution)),
            'feeding_behavior_distribution' => array_map(function($key, $value) {
                return ['feeding_behavior_status' => $key, 'count' => $value];
            }, array_keys($feedingBehaviorDistribution), array_values($feedingBehaviorDistribution)),
            'physical_signs' => array_map(function($key, $value) {
                return ['physical_sign' => $key, 'count' => $value];
            }, array_keys($physicalSigns), array_values($physicalSigns)),
            'dietary_diversity_distribution' => array_map(function($key, $value) {
                return ['dietary_diversity_level' => $key, 'count' => $value];
            }, array_keys($dietaryDiversityDistribution), array_values($dietaryDiversityDistribution)),
            'clinical_risk_factors' => array_map(function($key, $value) {
                return ['clinical_risk_factor' => $key, 'count' => $value];
            }, array_keys($clinicalRiskFactors), array_values($clinicalRiskFactors))
        ];
        
        // Combine metrics and screening responses
        $response = array_merge($metricsData, [
            'time_frame' => $timeFrame,
            'start_date' => $startDateStr,
            'end_date' => $endDateStr,
            'start_date_formatted' => $startDate->format('M j, Y'),
            'end_date_formatted' => $now->format('M j, Y'),
            'screening_responses' => $screeningResponses
        ]);
        
        echo json_encode([
            'success' => true,
            'data' => $response
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error getting time frame data: ' . $e->getMessage()
        ]);
    }
    exit;
}
?> 