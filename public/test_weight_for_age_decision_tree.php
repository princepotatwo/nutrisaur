<?php
require_once 'who_growth_standards.php';

echo "<h2>Testing Weight-for-Age Classifications</h2>";

$who = new WHOGrowthStandards();

// Test cases for different weights at age 0 months (male)
$testCases = [
    ['weight' => 1.5, 'age' => 0, 'sex' => 'Male', 'expected' => 'Severely Underweight'],
    ['weight' => 2.0, 'age' => 0, 'sex' => 'Male', 'expected' => 'Severely Underweight'],
    ['weight' => 2.2, 'age' => 0, 'sex' => 'Male', 'expected' => 'Underweight'],
    ['weight' => 2.5, 'age' => 0, 'sex' => 'Male', 'expected' => 'Normal'],
    ['weight' => 4.0, 'age' => 0, 'sex' => 'Male', 'expected' => 'Normal'],
    ['weight' => 4.5, 'age' => 0, 'sex' => 'Male', 'expected' => 'Overweight'],
];

foreach ($testCases as $test) {
    $result = $who->calculateWeightForAge($test['weight'], $test['age'], $test['sex']);
    $actual = $result['classification'];
    $expected = $test['expected'];
    $status = ($actual === $expected) ? '✅' : '❌';
    
    echo "<p>{$status} Weight: {$test['weight']}kg, Age: {$test['age']}m, Sex: {$test['sex']} | Expected: {$expected}, Got: {$actual}</p>";
}

echo "<h3>Testing Comprehensive Assessment</h3>";

// Test comprehensive assessment
$assessment = $who->getComprehensiveAssessment(2.0, 50, '2024-01-15', 'Male', '2024-01-15');
if ($assessment['success']) {
    $wfa = $assessment['results']['weight_for_age'];
    echo "<p>Comprehensive Assessment - Weight-for-Age: {$wfa['classification']} (z-score: {$wfa['z_score']})</p>";
} else {
    echo "<p>Assessment failed: " . implode(', ', $assessment['errors']) . "</p>";
}
?>
