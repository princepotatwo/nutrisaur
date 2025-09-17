<?php
require_once 'who_growth_standards.php';

$who = new WHOGrowthStandards();

// Test the exact cases from the table
$testCases = [
    ['weight' => 1.9, 'ageInMonths' => 0, 'sex' => 'Female', 'expected' => 'Severely Underweight'],
    ['weight' => 2.2, 'ageInMonths' => 0, 'sex' => 'Female', 'expected' => 'Underweight'],
    ['weight' => 6.3, 'ageInMonths' => 0, 'sex' => 'Female', 'expected' => 'Overweight'],
];

echo "Testing Female Weight-for-Age Classification:\n";
echo "============================================\n\n";

foreach ($testCases as $i => $test) {
    echo "Test " . ($i + 1) . ":\n";
    echo "Weight: {$test['weight']}kg, Age: {$test['ageInMonths']} months, Sex: {$test['sex']}\n";
    echo "Expected: {$test['expected']}\n";
    
    $result = $who->calculateWeightForAge($test['weight'], $test['ageInMonths'], $test['sex']);
    
    echo "Actual Result:\n";
    print_r($result);
    echo "Classification: " . ($result['classification'] ?? 'NULL') . "\n";
    echo "Z-Score: " . ($result['z_score'] ?? 'NULL') . "\n";
    echo "Method: " . ($result['method'] ?? 'NULL') . "\n";
    echo "Match Expected: " . (($result['classification'] ?? '') === $test['expected'] ? 'YES' : 'NO') . "\n";
    echo "---\n\n";
}
?>
