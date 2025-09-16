<?php
// Test classification accuracy with known test cases
require_once 'who_growth_standards.php';

echo "=== TESTING CLASSIFICATION ACCURACY ===\n\n";

$who = new WHOGrowthStandards();

// Test cases that should help us identify the problem
$testCases = [
    // Known problematic case: 71-month boy with 24.1kg should be Overweight
    ['weight' => 24.1, 'age' => 71, 'sex' => 'Male', 'expected' => 'Overweight'],
    
    // Test some edge cases around the Normal/Overweight boundary
    ['weight' => 15.0, 'age' => 36, 'sex' => 'Male', 'expected' => 'Normal'],
    ['weight' => 20.0, 'age' => 60, 'sex' => 'Male', 'expected' => 'Normal'],
    ['weight' => 25.0, 'age' => 60, 'sex' => 'Male', 'expected' => 'Overweight'],
    
    // Test some younger ages
    ['weight' => 8.0, 'age' => 12, 'sex' => 'Male', 'expected' => 'Normal'],
    ['weight' => 12.0, 'age' => 12, 'sex' => 'Male', 'expected' => 'Overweight'],
    
    // Test some older ages
    ['weight' => 18.0, 'age' => 48, 'sex' => 'Male', 'expected' => 'Normal'],
    ['weight' => 22.0, 'age' => 48, 'sex' => 'Male', 'expected' => 'Overweight'],
];

foreach ($testCases as $i => $case) {
    echo "--- TEST CASE " . ($i + 1) . " ---\n";
    echo "Weight: {$case['weight']} kg, Age: {$case['age']} months, Sex: {$case['sex']}\n";
    echo "Expected: {$case['expected']}\n";
    
    $result = $who->calculateWeightForAge($case['weight'], $case['age'], $case['sex']);
    
    if ($result && isset($result['classification'])) {
        echo "Actual: {$result['classification']}\n";
        echo "Age Used: {$result['age_used']}\n";
        echo "Weight Range: {$result['weight_range']}\n";
        echo "Z-Score: {$result['z_score']}\n";
        
        if ($result['classification'] === $case['expected']) {
            echo "✅ CORRECT\n";
        } else {
            echo "❌ INCORRECT - Expected {$case['expected']}, got {$result['classification']}\n";
        }
    } else {
        echo "❌ ERROR - No classification returned\n";
    }
    
    echo "\n";
}

// Test the boys' lookup table directly
echo "=== TESTING BOYS' LOOKUP TABLE ===\n";
$boysTable = $who->getWeightForAgeBoysLookupTable();

// Check a few specific ages
$testAges = [12, 36, 60, 71];
foreach ($testAges as $age) {
    if (isset($boysTable[$age])) {
        echo "Age $age months:\n";
        $ranges = $boysTable[$age];
        echo "  Severely Underweight: <= " . $ranges['severely_underweight']['max'] . " kg\n";
        echo "  Underweight: <= " . $ranges['underweight']['max'] . " kg\n";
        echo "  Normal: <= " . $ranges['normal']['max'] . " kg\n";
        echo "  Overweight: >= " . $ranges['overweight']['min'] . " kg\n";
        echo "\n";
    } else {
        echo "Age $age months: NOT FOUND in lookup table\n\n";
    }
}
?>
