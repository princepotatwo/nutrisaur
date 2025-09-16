<?php
// Test the specific users mentioned by the user
require_once 'public/api/DatabaseAPI.php';

echo "=== TESTING SPECIFIC USERS ===\n\n";

$api = new DatabaseAPI();

// Test the specific users mentioned
$testUsers = [
    ['name' => 'Overweight 36mo Boy', 'weight' => 18.4, 'birthday' => '2021-09-15', 'sex' => 'Male', 'screening_date' => '2024-09-15 10:00:00'],
    ['name' => 'Overweight 71mo Boy', 'weight' => 24.1, 'birthday' => '2018-10-15', 'sex' => 'Male', 'screening_date' => '2024-09-15 10:00:00']
];

foreach ($testUsers as $user) {
    echo "--- Testing: {$user['name']} ---\n";
    echo "Weight: {$user['weight']} kg\n";
    echo "Birthday: {$user['birthday']}\n";
    echo "Sex: {$user['sex']}\n";
    echo "Screening Date: {$user['screening_date']}\n";
    
    // Calculate age manually
    require_once 'who_growth_standards.php';
    $who = new WHOGrowthStandards();
    $ageInMonths = $who->calculateAgeInMonths($user['birthday'], $user['screening_date']);
    echo "Calculated Age: $ageInMonths months\n";
    
    // Test classification
    $result = $who->calculateWeightForAge($user['weight'], $ageInMonths, $user['sex']);
    echo "Classification Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    
    // Check boys' lookup table for this age
    $boysTable = $who->getWeightForAgeBoysLookupTable();
    if (isset($boysTable[$ageInMonths])) {
        $ranges = $boysTable[$ageInMonths];
        echo "WHO Ranges for age $ageInMonths:\n";
        echo "  Severely Underweight: <= " . $ranges['severely_underweight']['max'] . " kg\n";
        echo "  Underweight: <= " . $ranges['underweight']['max'] . " kg\n";
        echo "  Normal: <= " . $ranges['normal']['max'] . " kg\n";
        echo "  Overweight: >= " . $ranges['overweight']['min'] . " kg\n";
        
        // Manual classification
        $weight = $user['weight'];
        if ($weight <= $ranges['severely_underweight']['max']) {
            $manualClass = 'Severely Underweight';
        } elseif ($weight <= $ranges['underweight']['max']) {
            $manualClass = 'Underweight';
        } elseif ($weight <= $ranges['normal']['max']) {
            $manualClass = 'Normal';
        } else {
            $manualClass = 'Overweight';
        }
        echo "Manual Classification: $manualClass\n";
    } else {
        echo "Age $ageInMonths not found in boys' lookup table\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
}

// Also test the API directly
echo "=== TESTING API DIRECTLY ===\n";
$result = $api->getWHOClassifications('weight-for-age', '1d', '');
echo "API Result:\n";
echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
?>
