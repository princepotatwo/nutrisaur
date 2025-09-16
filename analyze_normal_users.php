<?php
// Analyze the 22 Normal users to understand the classification issue
require_once 'public/api/DatabaseAPI.php';

echo "=== ANALYZING 22 NORMAL USERS ===\n";

$api = new DatabaseAPI();

// Get the WHO classifications data
$result = $api->getWHOClassifications('weight-for-age', '1d', '');

if ($result['success'] && isset($result['data']['debug_info'])) {
    $debugInfo = $result['data']['debug_info'];
    $normalUsers = [];
    
    echo "Total users processed: " . count($debugInfo) . "\n";
    
    // Find all Normal users
    foreach ($debugInfo as $i => $user) {
        if (isset($user['weight_for_age_result']) && 
            $user['weight_for_age_result']['classification'] === 'Normal') {
            $normalUsers[] = $user;
        }
    }
    
    echo "Users classified as Normal: " . count($normalUsers) . "\n\n";
    
    // Analyze each Normal user
    foreach ($normalUsers as $i => $user) {
        echo "=== NORMAL USER " . ($i + 1) . " ===\n";
        echo "Name: " . ($user['name'] ?? 'Unknown') . "\n";
        echo "Age: " . ($user['age_months'] ?? 'Unknown') . " months\n";
        echo "Weight: " . ($user['weight'] ?? 'Unknown') . " kg\n";
        echo "Sex: " . ($user['sex'] ?? 'Unknown') . "\n";
        
        if (isset($user['weight_for_age_result'])) {
            $result = $user['weight_for_age_result'];
            echo "Classification: " . ($result['classification'] ?? 'Unknown') . "\n";
            echo "Age Used: " . ($result['age_used'] ?? 'Unknown') . "\n";
            echo "Weight Range: " . ($result['weight_range'] ?? 'Unknown') . "\n";
            echo "Z-Score: " . ($result['z_score'] ?? 'Unknown') . "\n";
        }
        
        echo "\n";
    }
    
    // Check for patterns
    echo "=== PATTERN ANALYSIS ===\n";
    $ageGroups = [];
    $weightGroups = [];
    $sexGroups = [];
    
    foreach ($normalUsers as $user) {
        $age = $user['age_months'] ?? 'Unknown';
        $weight = $user['weight'] ?? 'Unknown';
        $sex = $user['sex'] ?? 'Unknown';
        
        $ageGroups[$age] = ($ageGroups[$age] ?? 0) + 1;
        $weightGroups[$weight] = ($weightGroups[$weight] ?? 0) + 1;
        $sexGroups[$sex] = ($sexGroups[$sex] ?? 0) + 1;
    }
    
    echo "Age distribution:\n";
    foreach ($ageGroups as $age => $count) {
        echo "  Age $age: $count users\n";
    }
    
    echo "\nWeight distribution:\n";
    foreach ($weightGroups as $weight => $count) {
        echo "  Weight $weight: $count users\n";
    }
    
    echo "\nSex distribution:\n";
    foreach ($sexGroups as $sex => $count) {
        echo "  $sex: $count users\n";
    }
    
} else {
    echo "No debug info available or API call failed\n";
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
}
?>
