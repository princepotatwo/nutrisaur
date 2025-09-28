<?php
/**
 * Settings API - Uses Universal DatabaseAPI
 * Handles all settings page AJAX requests
 */

// Start the session
session_start();

// Use Universal DatabaseAPI
require_once __DIR__ . '/DatabaseHelper.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Get database helper instance
$db = DatabaseHelper::getInstance();

if (!$db->isAvailable()) {
    echo json_encode(['success' => false, 'error' => 'Database not available']);
    exit;
}

// Get action
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_users':
            // Get all user preferences using Universal DatabaseAPI
            $result = $db->select(
                'user_preferences',
                'id, user_email, age, gender, barangay, municipality, province, weight_kg, height_cm, bmi, risk_score, malnutrition_risk, screening_date, created_at, name, birthday, income, muac, screening_answers, allergies, diet_prefs, avoid_foods, swelling, weight_loss, feeding_behavior, physical_signs, dietary_diversity, clinical_risk_factors, whz_score, income_level',
                '',
                [],
                'created_at DESC',
                '100'
            );
            
            if ($result['success']) {
                // Format the data for the frontend
                $formattedUsers = [];
                foreach ($result['data'] as $user) {
                    $formattedUsers[] = [
                        'id' => $user['id'],
                        'username' => $user['username'] ?? 'N/A',
                        'email' => $user['user_email'],
                        'name' => $user['name'] ?? 'N/A',
                        'age' => $user['age'] ?? 'N/A',
                        'gender' => $user['gender'] ?? 'N/A',
                        'height_cm' => $user['height_cm'] ?? 'N/A',
                        'weight_kg' => $user['weight_kg'] ?? 'N/A',
                        'bmi' => $user['bmi'] ?? 'N/A',
                        'barangay' => $user['barangay'] ?? 'N/A',
                        'municipality' => $user['municipality'] ?? 'N/A',
                        'risk_score' => $user['risk_score'] ?? 'N/A',
                        'malnutrition_risk' => $user['malnutrition_risk'] ?? 'N/A',
                        'created_at' => $user['created_at'] ?? 'N/A'
                    ];
                }
                
                echo json_encode([
                    'success' => true,
                    'data' => $formattedUsers,
                    'count' => count($formattedUsers)
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => $result['message']]);
            }
            break;
            
        case 'add_user':
            // Add new user using Universal DatabaseAPI
            $requiredFields = ['user_email', 'username', 'name'];
            foreach ($requiredFields as $field) {
                if (empty($_POST[$field])) {
                    echo json_encode(['success' => false, 'error' => "Field '$field' is required"]);
                    exit;
                }
            }
            
            $userData = [
                'user_email' => $_POST['user_email'],
                'username' => $_POST['username'],
                'name' => $_POST['name'],
                'barangay' => $_POST['barangay'] ?? '',
                'income' => $_POST['income'] ?? '',
                'age' => $_POST['age'] ?? null,
                'gender' => $_POST['gender'] ?? '',
                'height' => $_POST['height'] ?? null,
                'weight' => $_POST['weight'] ?? null,
                'risk_score' => $_POST['risk_score'] ?? 0,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Check if user already exists
            $exists = $db->exists('user_preferences', 'user_email = ?', [$userData['user_email']]);
            
            if ($exists) {
                // Update existing user
                unset($userData['created_at']); // Don't update created_at
                $result = $db->update('user_preferences', $userData, 'user_email = ?', [$userData['user_email']]);
            } else {
                // Insert new user
                $result = $db->insert('user_preferences', $userData);
            }
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => 'User saved successfully',
                    'data' => ['user_email' => $userData['user_email']]
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => $result['message']]);
            }
            break;
            
        case 'update_user':
            // Update user using Universal DatabaseAPI
            if (empty($_POST['user_email'])) {
                echo json_encode(['success' => false, 'error' => 'User email is required']);
                exit;
            }
            
            $userData = [
                'username' => $_POST['username'] ?? '',
                'name' => $_POST['name'] ?? '',
                'barangay' => $_POST['barangay'] ?? '',
                'income' => $_POST['income'] ?? '',
                'age' => $_POST['age'] ?? null,
                'gender' => $_POST['gender'] ?? '',
                'height' => $_POST['height'] ?? null,
                'weight' => $_POST['weight'] ?? null,
                'risk_score' => $_POST['risk_score'] ?? 0
            ];
            
            $result = $db->update('user_preferences', $userData, 'user_email = ?', [$_POST['user_email']]);
            
            if ($result['success']) {
                echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => $result['message']]);
            }
            break;
            
        case 'delete_user':
            // Delete user using Universal DatabaseAPI
            if (empty($_POST['user_id'])) {
                echo json_encode(['success' => false, 'error' => 'User ID is required']);
                exit;
            }
            
            $result = $db->delete('user_preferences', 'id = ?', [$_POST['user_id']]);
            
            if ($result['success'] && $result['affected_rows'] > 0) {
                echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'User not found or deletion failed']);
            }
            break;
            
        case 'delete_users_by_location':
            // Delete users by location using Universal DatabaseAPI
            $barangay = $_POST['barangay'] ?? null;
            $municipality = $_POST['municipality'] ?? null;
            
            if (empty($barangay) && empty($municipality)) {
                echo json_encode(['success' => false, 'error' => 'Barangay or municipality is required']);
                exit;
            }
            
            $whereConditions = [];
            $params = [];
            
            if (!empty($barangay)) {
                $whereConditions[] = "barangay = ?";
                $params[] = $barangay;
            }
            
            if (!empty($municipality)) {
                $whereConditions[] = "municipality = ?";
                $params[] = $municipality;
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            $result = $db->delete('user_preferences', $whereClause, $params);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => "Deleted {$result['affected_rows']} users from the specified location",
                    'data' => ['deleted_count' => $result['affected_rows']]
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => $result['message']]);
            }
            break;
            
        case 'delete_all_users':
            // Delete all users using Universal DatabaseAPI
            $confirm = $_POST['confirm'] ?? false;
            if (!$confirm) {
                echo json_encode(['success' => false, 'error' => 'Confirmation required for deleting all users']);
                exit;
            }
            
            $result = $db->query("DELETE FROM user_preferences", []);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => "Deleted all {$result['affected_rows']} users",
                    'data' => ['deleted_count' => $result['affected_rows']]
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => $result['message']]);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action]);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>
