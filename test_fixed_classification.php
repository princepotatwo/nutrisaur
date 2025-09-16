<?php
require_once 'who_growth_standards.php';

$who = new WHOGrowthStandards();

echo "=== Testing Fixed Classification Logic ===\n\n";

// Test cases from the table
$testCases = [
    ['name' => 'Severely Underweight 8mo Boy', 'age' => 8, 'weight' => 6.2, 'sex' => 'Male'],
    ['name' => 'Severely Underweight 7mo Boy', 'age' => 7, 'weight' => 5.9, 'sex' => 'Male'],
    ['name' => 'Underweight 9mo Boy', 'age' => 9, 'weight' => 7.0, 'sex' => 'Male'],
    ['name' => 'Underweight 60mo Boy', 'age' => 60, 'weight' => 14.0, 'sex' => 'Male'],
    ['name' => 'Normal 36mo Boy', 'age' => 36, 'weight' => 14.2, 'sex' => 'Male'],
    ['name' => 'Overweight 71mo Boy', 'age' => 71, 'weight' => 24.1, 'sex' => 'Male'],
];

foreach ($testCases as $case) {
    $result = $who->calculateWeightForAge($case['weight'], $case['age'], $case['sex']);
    echo "{$case['name']}: {$result['classification']} (Range: {$result['weight_range']})\n";
}

echo "\n=== Testing Edge Cases ===\n";
// Test edge cases that were falling through gaps
$edgeCases = [
    ['name' => 'Edge Case 6.0kg 9mo', 'age' => 9, 'weight' => 6.0, 'sex' => 'Male'],
    ['name' => 'Edge Case 6.1kg 9mo', 'age' => 9, 'weight' => 6.1, 'sex' => 'Male'],
    ['name' => 'Edge Case 6.9kg 9mo', 'age' => 9, 'weight' => 6.9, 'sex' => 'Male'],
    ['name' => 'Edge Case 7.0kg 9mo', 'age' => 9, 'weight' => 7.0, 'sex' => 'Male'],
];

foreach ($edgeCases as $case) {
    $result = $who->calculateWeightForAge($case['weight'], $case['age'], $case['sex']);
    echo "{$case['name']}: {$result['classification']} (Range: {$result['weight_range']})\n";
}
?>
