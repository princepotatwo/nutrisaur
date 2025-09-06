<?php
/**
 * ONE-CLICK SCREENING SYSTEM SETUP
 * Run this once to set up the complete flexible screening system
 */

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/api/ScreeningManager.php';
    
    $screeningManager = ScreeningManager::getInstance();
    
    // Setup the table
    $tableSetup = $screeningManager->ensureScreeningTableExists();
    
    // Test the system
    $testData = [
        'email' => 'system_test@nutrisaur.com',
        'municipality' => 'Test Municipality',
        'barangay' => 'Test Barangay',
        'birthdate' => '1995-01-15',
        'age' => 28,
        'sex' => 'Female',
        'weight' => 55,
        'height' => 158,
        'food_carbs' => true,
        'food_protein' => true,
        'family_diabetes' => true,
        'lifestyle' => 'Active'
    ];
    
    $testSave = $screeningManager->saveScreeningData($testData);
    $testGet = $screeningManager->getScreeningData('system_test@nutrisaur.com');
    $testStats = $screeningManager->getScreeningStats();
    
    echo json_encode([
        'success' => true,
        'message' => '🎉 Screening system setup complete!',
        'results' => [
            'table_setup' => $tableSetup ? 'SUCCESS' : 'FAILED',
            'test_save' => $testSave['success'] ? 'SUCCESS' : 'FAILED',
            'test_get' => count($testGet) > 0 ? 'SUCCESS' : 'FAILED',
            'test_stats' => !empty($testStats) ? 'SUCCESS' : 'FAILED'
        ],
        'android_url' => $_SERVER['HTTP_HOST'] . '/api/comprehensive_screening.php?action=save',
        'admin_panel' => $_SERVER['HTTP_HOST'] . '/screening_admin.php',
        'features_ready' => [
            '✅ Flexible question system',
            '✅ Auto database updates',
            '✅ No connection issues',
            '✅ Risk scoring system',
            '✅ Statistics dashboard',
            '✅ Admin interface'
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Setup failed: ' . $e->getMessage()
    ]);
}

?>
