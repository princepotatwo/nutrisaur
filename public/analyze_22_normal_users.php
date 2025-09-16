<?php
// Focused analysis of the 22 Normal users to find the accuracy problem
require_once 'public/api/DatabaseAPI.php';

echo "=== ANALYZING 22 NORMAL USERS FOR ACCURACY ISSUES ===\n\n";

$api = new DatabaseAPI();
$result = $api->getWHOClassifications('weight-for-age', '1d', '');

if ($result['success'] && isset($result['data']['debug_info'])) {
    $debugInfo = $result['data']['debug_info'];
    $normalUsers = [];
    
    // Find all Normal users
    foreach ($debugInfo as $user) {
        if (isset($user['weight_for_age_result']) && 
            $user['weight_for_age_result']['classification'] === 'Normal') {
            $normalUsers[] = $user;
        }
    }
    
    echo "Found " . count($normalUsers) . " Normal users out of " . count($debugInfo) . " total users\n\n";
    
    // Analyze each Normal user for potential misclassification
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
            
            // Check if this looks like a misclassification
            $weight = floatval($user['weight']);
            $age = intval($user['age_months']);
            $sex = $user['sex'];
            
            echo "\n--- MANUAL VERIFICATION ---\n";
            
            // Load WHO standards to manually verify
            require_once 'who_growth_standards.php';
            $who = new WHOGrowthStandards();
            
            // Get the lookup table for manual verification
            if ($sex === 'Male') {
                $ranges = $who->getWeightForAgeBoysLookupTable();
            } else {
                $ranges = $who->getWeightForAgeGirlsLookupTable();
            }
            
            // Find closest age
            $closestAge = null;
            $minDiff = PHP_INT_MAX;
            foreach (array_keys($ranges) as $ageKey) {
                $diff = abs($age - $ageKey);
                if ($diff < $minDiff) {
                    $minDiff = $diff;
                    $closestAge = $ageKey;
                }
            }
            
            if ($closestAge !== null && isset($ranges[$closestAge])) {
                $ageRanges = $ranges[$closestAge];
                echo "Manual lookup for age $closestAge:\n";
                echo "  Severely Underweight: <= " . $ageRanges['severely_underweight']['max'] . " kg\n";
                echo "  Underweight: <= " . $ageRanges['underweight']['max'] . " kg\n";
                echo "  Normal: <= " . $ageRanges['normal']['max'] . " kg\n";
                echo "  Overweight: >= " . $ageRanges['overweight']['min'] . " kg\n";
                
                echo "\nUser weight: $weight kg\n";
                
                // Manual classification
                if ($weight <= $ageRanges['severely_underweight']['max']) {
                    $manualClass = 'Severely Underweight';
                } elseif ($weight <= $ageRanges['underweight']['max']) {
                    $manualClass = 'Underweight';
                } elseif ($weight <= $ageRanges['normal']['max']) {
                    $manualClass = 'Normal';
                } else {
                    $manualClass = 'Overweight';
                }
                
                echo "Manual classification: $manualClass\n";
                
                if ($manualClass !== 'Normal') {
                    echo "⚠️  POTENTIAL MISCLASSIFICATION! Should be $manualClass, not Normal\n";
                } else {
                    echo "✅ Classification appears correct\n";
                }
            }
        }
        
        echo "\n" . str_repeat("-", 50) . "\n\n";
    }
    
    // Summary analysis
    echo "=== SUMMARY ANALYSIS ===\n";
    $misclassifications = 0;
    $ageGroups = [];
    $weightGroups = [];
    
    foreach ($normalUsers as $user) {
        $age = $user['age_months'] ?? 'Unknown';
        $weight = $user['weight'] ?? 'Unknown';
        
        $ageGroups[$age] = ($ageGroups[$age] ?? 0) + 1;
        $weightGroups[$weight] = ($weightGroups[$weight] ?? 0) + 1;
    }
    
    echo "Age distribution of Normal users:\n";
    foreach ($ageGroups as $age => $count) {
        echo "  Age $age: $count users\n";
    }
    
    echo "\nWeight distribution of Normal users:\n";
    foreach ($weightGroups as $weight => $count) {
        echo "  Weight $weight: $count users\n";
    }
    
} else {
    echo "No debug info available or API call failed\n";
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
}
?>
