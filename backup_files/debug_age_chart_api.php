<?php
// Debug script to test Age Classification Chart API directly
require_once 'public/api/DatabaseAPI.php';

echo "🔍 Debugging Age Classification Chart API\n";
echo "==========================================\n\n";

try {
    $db = DatabaseAPI::getInstance();
    
    // Test the Age Classification Chart API
    $_GET['action'] = 'get_age_classification_chart';
    $_GET['barangay'] = '';
    $_GET['time_frame'] = '1d';
    
    echo "📊 Testing Age Classification Chart API...\n";
    ob_start();
    include 'public/api/DatabaseAPI.php';
    $apiResponse = ob_get_clean();
    
    echo "API Response:\n";
    echo $apiResponse . "\n\n";
    
    // Test the WHO Classifications Bulk API (same as donut chart)
    echo "📊 Testing WHO Classifications Bulk API (donut chart)...\n";
    $_GET['action'] = 'get_all_who_classifications_bulk';
    $_GET['who_standard'] = 'weight-for-age';
    
    ob_start();
    include 'public/api/DatabaseAPI.php';
    $whoResponse = ob_get_clean();
    
    echo "WHO API Response:\n";
    echo $whoResponse . "\n\n";
    
    // Compare the data
    $ageData = json_decode($apiResponse, true);
    $whoData = json_decode($whoResponse, true);
    
    echo "🔍 Comparison:\n";
    echo "- Age Chart users: " . (isset($ageData['data']['rawData']) ? count($ageData['data']['rawData']) : 'unknown') . "\n";
    echo "- WHO Chart users: " . (isset($whoData['total_users']) ? $whoData['total_users'] : 'unknown') . "\n";
    
    if (isset($ageData['data']['chartData']['Normal'])) {
        $normalCount = array_sum($ageData['data']['chartData']['Normal']);
        echo "- Age Chart Normal count: " . $normalCount . "\n";
    }
    
    if (isset($whoData['data']['weight_for_age']['Severely Underweight'])) {
        echo "- WHO Chart Severely Underweight: " . $whoData['data']['weight_for_age']['Severely Underweight'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
