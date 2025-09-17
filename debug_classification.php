<?php
require_once 'who_growth_standards.php';

echo "<h2>Debug Weight-for-Age Classifications</h2>";

$who = new WHOGrowthStandards();

// Test the exact weights from our CSV
$testCases = [
    // Male 0 months
    ['weight' => 1.0, 'age' => 0, 'sex' => 'Male', 'expected' => 'Severely Underweight'],
    ['weight' => 2.2, 'age' => 0, 'sex' => 'Male', 'expected' => 'Underweight'],
    ['weight' => 2.5, 'age' => 0, 'sex' => 'Male', 'expected' => 'Normal'],
    ['weight' => 4.5, 'age' => 0, 'sex' => 'Male', 'expected' => 'Overweight'],
    
    // Female 0 months
    ['weight' => 1.0, 'age' => 0, 'sex' => 'Female', 'expected' => 'Severely Underweight'],
    ['weight' => 2.1, 'age' => 0, 'sex' => 'Female', 'expected' => 'Underweight'],
    ['weight' => 2.4, 'age' => 0, 'sex' => 'Female', 'expected' => 'Normal'],
    ['weight' => 4.3, 'age' => 0, 'sex' => 'Female', 'expected' => 'Overweight'],
];

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Weight</th><th>Age</th><th>Sex</th><th>Expected</th><th>Actual</th><th>Match</th></tr>";

foreach ($testCases as $test) {
    $result = $who->calculateWeightForAge($test['weight'], $test['age'], $test['sex']);
    $actual = $result['classification'];
    $expected = $test['expected'];
    $match = ($actual === $expected) ? '✅' : '❌';
    
    echo "<tr>";
    echo "<td>{$test['weight']}kg</td>";
    echo "<td>{$test['age']}m</td>";
    echo "<td>{$test['sex']}</td>";
    echo "<td>{$expected}</td>";
    echo "<td>{$actual}</td>";
    echo "<td>{$match}</td>";
    echo "</tr>";
}

echo "</table>";

// Test comprehensive assessment
echo "<h3>Comprehensive Assessment Test</h3>";
$assessment = $who->getComprehensiveAssessment(2.5, 50, '2024-01-15', 'Male', '2024-01-15');
if ($assessment['success']) {
    $wfa = $assessment['results']['weight_for_age'];
    echo "<p>Weight: 2.5kg, Male, 0 months</p>";
    echo "<p>Classification: {$wfa['classification']}</p>";
    echo "<p>Z-score: {$wfa['z_score']}</p>";
} else {
    echo "<p>Assessment failed: " . implode(', ', $assessment['errors']) . "</p>";
}
?>
