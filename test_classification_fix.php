<?php
require_once 'who_growth_standards.php';

$who = new WHOGrowthStandards();

echo "=== Testing Fixed Classifications ===\n\n";

// Test the specific cases from the table
$testCases = [
    ['name' => 'Severely Underweight 9mo Boy', 'age' => 9, 'weight' => 6.4, 'sex' => 'Male', 'expected' => 'Severely Underweight'],
    ['name' => 'Underweight 9mo Boy', 'age' => 9, 'weight' => 7.0, 'sex' => 'Male', 'expected' => 'Underweight'],
    ['name' => 'Normal 9mo Boy', 'age' => 9, 'weight' => 9.0, 'sex' => 'Male', 'expected' => 'Normal'],
    ['name' => 'Overweight 71mo Boy', 'age' => 71, 'weight' => 24.1, 'sex' => 'Male', 'expected' => 'Overweight'],
    ['name' => 'Normal 71mo Boy', 'age' => 71, 'weight' => 20.0, 'sex' => 'Male', 'expected' => 'Normal'],
];

foreach ($testCases as $case) {
    $result = $who->calculateWeightForAge($case['weight'], $case['age'], $case['sex']);
    $status = ($result['classification'] === $case['expected']) ? '✅ CORRECT' : '❌ WRONG';
    echo "{$case['name']}: {$result['classification']} (Expected: {$case['expected']}) $status\n";
    echo "  Range: {$result['weight_range']}\n\n";
}
?>
