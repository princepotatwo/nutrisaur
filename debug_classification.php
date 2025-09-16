<?php
require_once 'who_growth_standards.php';

$who = new WHOGrowthStandards();

echo "=== DEBUGGING CLASSIFICATION ISSUE ===\n\n";

// Test specific cases from the CSV
$testCases = [
    ['name' => 'Severely Underweight 9mo Boy', 'age' => 9, 'weight' => 6.4, 'sex' => 'Male'],
    ['name' => 'Underweight 9mo Boy', 'age' => 9, 'weight' => 7.0, 'sex' => 'Male'],
    ['name' => 'Normal 9mo Boy', 'age' => 9, 'weight' => 9.0, 'sex' => 'Male'],
    ['name' => 'Overweight 71mo Boy', 'age' => 71, 'weight' => 24.1, 'sex' => 'Male'],
    ['name' => 'Severely Underweight 8mo Boy', 'age' => 8, 'weight' => 6.2, 'sex' => 'Male'],
];

foreach ($testCases as $case) {
    echo "=== {$case['name']} ===\n";
    echo "Age: {$case['age']} months, Weight: {$case['weight']}kg, Sex: {$case['sex']}\n";
    
    $result = $who->calculateWeightForAge($case['weight'], $case['age'], $case['sex']);
    
    echo "Classification: {$result['classification']}\n";
    echo "Weight Range: {$result['weight_range']}\n";
    echo "Age Used: {$result['age_used']}\n";
    echo "Method: {$result['method']}\n";
    
    if (isset($result['error'])) {
        echo "ERROR: {$result['error']}\n";
    }
    
    echo "\n";
}

// Let's also check what lookup table data we're actually using
echo "=== CHECKING LOOKUP TABLE DATA ===\n";
$boysTable = $who->getWeightForAgeBoysLookupTable();

echo "Age 9 months data:\n";
if (isset($boysTable[9])) {
    print_r($boysTable[9]);
} else {
    echo "Age 9 not found in boys table!\n";
}

echo "\nAge 71 months data:\n";
if (isset($boysTable[71])) {
    print_r($boysTable[71]);
} else {
    echo "Age 71 not found in boys table!\n";
}
?>
