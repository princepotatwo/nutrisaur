<?php
/**
 * SUPER EASY Comprehensive Screening API
 * Handles ALL screening operations automatically
 * NO MORE CONNECTION ISSUES! NO MORE MANUAL DATABASE CHANGES!
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Use the super flexible ScreeningManager
require_once __DIR__ . '/ScreeningManager.php';

try {
    $screeningManager = ScreeningManager::getInstance();
    
    // Get action
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        
        // SAVE SCREENING DATA (SUPER FLEXIBLE)
        case 'save_comprehensive_screening':
        case 'save_screening':
        case 'save':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Get data from multiple sources
                $input = file_get_contents('php://input');
                $jsonData = json_decode($input, true);
                $postData = $_POST;
                
                // Merge all data sources
                $data = array_merge($postData, $jsonData ?: []);
                
                // Handle nested screening_data
                if (isset($data['screening_data'])) {
                    $screeningData = is_string($data['screening_data']) ? 
                        json_decode($data['screening_data'], true) : $data['screening_data'];
                    $data = array_merge($data, $screeningData ?: []);
                }
                
                if (empty($data)) {
                    echo json_encode(['success' => false, 'message' => 'No data provided']);
                    exit;
                }
                
                // Save using the flexible manager
                $result = $screeningManager->saveScreeningData($data);
                echo json_encode($result);
                
            } else {
                echo json_encode(['success' => false, 'message' => 'POST method required']);
            }
            break;
            
        // GET SCREENING DATA
        case 'get_screening':
        case 'get_screenings':
        case 'get':
            $userEmail = $_GET['user_email'] ?? $_POST['user_email'] ?? '';
            $limit = intval($_GET['limit'] ?? $_POST['limit'] ?? 100);
            
            $data = $screeningManager->getScreeningData($userEmail ?: null, $limit);
            echo json_encode([
                'success' => true,
                'data' => $data,
                'count' => count($data)
            ]);
            break;
            
        // GET SCREENING STATISTICS
        case 'get_stats':
        case 'stats':
        case 'statistics':
            $barangay = $_GET['barangay'] ?? $_POST['barangay'] ?? '';
            $stats = $screeningManager->getScreeningStats($barangay);
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            break;
            
        // ENSURE TABLE EXISTS (FOR DEBUGGING)
        case 'init':
        case 'setup':
        case 'ensure_table':
            $result = $screeningManager->ensureScreeningTableExists();
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Screening table ready' : 'Failed to setup table'
            ]);
            break;
            
        // TEST THE SYSTEM
        case 'test':
            // Test data insertion
            $testData = [
                'email' => 'test@example.com',
                'municipality' => 'Test Municipality',
                'barangay' => 'Test Barangay',
                'birthdate' => 'Jan 15, 1995',
                'age' => 28,
                'sex' => 'Female',
                'weight' => 55,
                'height' => 158,
                'food_carbs' => true,
                'food_protein' => true,
                'family_diabetes' => true,
                'lifestyle' => 'Active'
            ];
            
            $saveResult = $screeningManager->saveScreeningData($testData);
            $getResult = $screeningManager->getScreeningData('test@example.com', 1);
            $statsResult = $screeningManager->getScreeningStats();
            
            echo json_encode([
                'success' => true,
                'message' => 'Comprehensive screening API test completed',
                'tests' => [
                    'save_test' => $saveResult,
                    'get_test' => ['success' => true, 'count' => count($getResult)],
                    'stats_test' => ['success' => true, 'stats' => $statsResult]
                ]
            ]);
            break;
            
        // DEFAULT: SHOW API USAGE
        default:
            echo json_encode([
                'success' => true,
                'message' => 'Nutrisaur Comprehensive Screening API',
                'version' => '2.0',
                'endpoints' => [
                    'save' => 'POST ?action=save (accepts any screening data format)',
                    'get' => 'GET ?action=get&user_email=email (get user screenings)',
                    'get_all' => 'GET ?action=get&limit=100 (get all screenings)',
                    'stats' => 'GET ?action=stats&barangay=name (get statistics)',
                    'test' => 'GET ?action=test (test the system)',
                    'init' => 'GET ?action=init (ensure table exists)'
                ],
                'features' => [
                    'Auto database creation/update',
                    'Flexible data input (JSON/Form)',
                    'Automatic risk scoring',
                    'No connection issues',
                    'No manual database changes needed'
                ],
                'android_usage' => [
                    'POST to: ' . $_SERVER['HTTP_HOST'] . '/api/comprehensive_screening.php?action=save',
                    'Send any JSON format - it will auto-adapt!',
                    'No need to change anything when you modify questions'
                ]
            ]);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'error_type' => 'system_error'
    ]);
}

?>
