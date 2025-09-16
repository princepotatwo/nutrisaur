<?php
// Test the classification with debugging
require_once 'who_growth_standards.php';

echo "=== TESTING CLASSIFICATION WITH DEBUGGING ===\n";

$who = new WHOGrowthStandards();

// Test the 71-month boy case
$weight = 24.1;
$ageInMonths = 71;
$sex = 'Male';

echo "Testing: Weight=$weight, Age=$ageInMonths, Sex=$sex\n";

$result = $who->calculateWeightForAge($weight, $ageInMonths, $sex);

echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";

// Test a few more cases
$testCases = [
    ['weight' => 15.0, 'age' => 36, 'sex' => 'Male'],
    ['weight' => 20.0, 'age' => 60, 'sex' => 'Male'],
    ['weight' => 10.0, 'age' => 24, 'sex' => 'Male']
];

foreach ($testCases as $case) {
    echo "\n--- Testing: Weight={$case['weight']}, Age={$case['age']}, Sex={$case['sex']} ---\n";
    $result = $who->calculateWeightForAge($case['weight'], $case['age'], $case['sex']);
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
}
?>
