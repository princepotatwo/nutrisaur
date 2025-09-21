<?php
// Simple test to debug the Age Classification Chart API
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🔍 Testing Age Classification Chart API\n";
echo "=====================================\n\n";

try {
    require_once 'public/api/DatabaseAPI.php';
    
    $db = DatabaseAPI::getInstance();
    
    // Test 1: Get users
    echo "📊 Test 1: Getting users...\n";
    $users = $db->getDetailedScreeningResponses('1d', '');
    echo "✅ Users found: " . count($users) . "\n\n";
    
    // Test 2: Filter users by age eligibility
    echo "📊 Test 2: Filtering users by age eligibility...\n";
    $filteredUsers = $db->filterUsersByAgeEligibility($users, 'weight-for-age');
    echo "✅ Filtered users: " . count($filteredUsers) . "\n\n";
    
    // Test 3: Test WHO Growth Standards
    echo "📊 Test 3: Testing WHO Growth Standards...\n";
    require_once 'who_growth_standards.php';
    $who = new WHOGrowthStandards();
    echo "✅ WHO Growth Standards loaded\n\n";
    
    // Test 4: Test comprehensive assessment on first user
    if (!empty($filteredUsers)) {
        echo "📊 Test 4: Testing comprehensive assessment...\n";
        $user = $filteredUsers[0];
        echo "User: " . $user['name'] . " (Age: " . $user['birthday'] . ")\n";
        
        $assessment = $who->getComprehensiveAssessment(
            floatval($user['weight_kg']), 
            floatval($user['height_cm']), 
            $user['birthday'], 
            $user['sex'],
            $user['screening_date'] ?? null
        );
        
        if ($assessment['success']) {
            echo "✅ Assessment successful\n";
            echo "Results: " . json_encode($assessment['results'], JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "❌ Assessment failed: " . json_encode($assessment) . "\n";
        }
    }
    
    echo "\n✅ All tests passed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
