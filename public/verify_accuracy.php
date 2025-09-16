<?php
require_once 'who_growth_standards.php';

echo "<h2>WHO Growth Standards Accuracy Verification</h2>";

$who = new WHOGrowthStandards();

// Test specific cases from the WHO table
$testCases = [
    // Age 0 months
    ['age' => 0, 'weight' => 2.0, 'expected' => 'Severely Underweight'],
    ['age' => 0, 'weight' => 2.3, 'expected' => 'Underweight'],
    ['age' => 0, 'weight' => 3.5, 'expected' => 'Normal'],
    ['age' => 0, 'weight' => 4.6, 'expected' => 'Overweight'],
    
    // Age 12 months
    ['age' => 12, 'weight' => 6.5, 'expected' => 'Severely Underweight'],
    ['age' => 12, 'weight' => 7.3, 'expected' => 'Underweight'],
    ['age' => 12, 'weight' => 9.5, 'expected' => 'Normal'],
    ['age' => 12, 'weight' => 12.5, 'expected' => 'Overweight'],
    
    // Age 24 months
    ['age' => 24, 'weight' => 8.0, 'expected' => 'Severely Underweight'],
    ['age' => 24, 'weight' => 9.0, 'expected' => 'Underweight'],
    ['age' => 24, 'weight' => 12.0, 'expected' => 'Normal'],
    ['age' => 24, 'weight' => 16.0, 'expected' => 'Overweight'],
    
    // Age 36 months
    ['age' => 36, 'weight' => 9.5, 'expected' => 'Severely Underweight'],
    ['age' => 36, 'weight' => 10.5, 'expected' => 'Underweight'],
    ['age' => 36, 'weight' => 14.5, 'expected' => 'Normal'],
    ['age' => 36, 'weight' => 18.5, 'expected' => 'Overweight'],
    
    // Age 60 months
    ['age' => 60, 'weight' => 12.0, 'expected' => 'Severely Underweight'],
    ['age' => 60, 'weight' => 13.0, 'expected' => 'Underweight'],
    ['age' => 60, 'weight' => 17.5, 'expected' => 'Normal'],
    ['age' => 60, 'weight' => 21.5, 'expected' => 'Overweight']
];

echo "<h3>Testing Specific WHO Table Cases</h3>";

echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Age (months)</th><th>Weight (kg)</th><th>Expected</th><th>Actual</th><th>Match</th><th>Z-Score</th><th>Method</th></tr>";

$correct = 0;
$total = count($testCases);

foreach ($testCases as $test) {
    $wfa = $who->calculateWeightForAge($test['weight'], $test['age'], 'Male');
    $actual = $wfa['classification'];
    $match = ($actual === $test['expected']) ? '✓' : '✗';
    
    if ($match === '✓') {
        $correct++;
    }
    
    echo "<tr>";
    echo "<td>" . $test['age'] . "</td>";
    echo "<td>" . $test['weight'] . "</td>";
    echo "<td>" . $test['expected'] . "</td>";
    echo "<td>" . $actual . "</td>";
    echo "<td style='color: " . ($match === '✓' ? 'green' : 'red') . "'>" . $match . "</td>";
    echo "<td>" . ($wfa['z_score'] ?? 'N/A') . "</td>";
    echo "<td>" . ($wfa['method'] ?? 'N/A') . "</td>";
    echo "</tr>";
}

echo "</table>";

$accuracy = round(($correct / $total) * 100, 1);
echo "<h3>Accuracy: {$correct}/{$total} ({$accuracy}%)</h3>";

if ($accuracy >= 95) {
    echo "<p style='color: green; font-weight: bold;'>✓ EXCELLENT: Decision tree is highly accurate!</p>";
} elseif ($accuracy >= 90) {
    echo "<p style='color: orange; font-weight: bold;'>⚠ GOOD: Decision tree is mostly accurate but needs minor adjustments.</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>✗ POOR: Decision tree needs significant improvements.</p>";
}

// Test edge cases
echo "<h3>Edge Case Testing</h3>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Age</th><th>Weight</th><th>Classification</th><th>Z-Score</th></tr>";

$edgeCases = [
    ['age' => 0, 'weight' => 2.1], // Exactly at severely underweight boundary
    ['age' => 0, 'weight' => 2.2], // Exactly at underweight boundary
    ['age' => 0, 'weight' => 2.4], // Exactly at underweight boundary
    ['age' => 0, 'weight' => 2.5], // Exactly at normal boundary
    ['age' => 0, 'weight' => 4.4], // Exactly at normal boundary
    ['age' => 0, 'weight' => 4.5], // Exactly at overweight boundary
    ['age' => 12, 'weight' => 6.9], // Exactly at severely underweight boundary
    ['age' => 12, 'weight' => 7.0], // Exactly at underweight boundary
    ['age' => 12, 'weight' => 7.6], // Exactly at underweight boundary
    ['age' => 12, 'weight' => 7.7], // Exactly at normal boundary
    ['age' => 12, 'weight' => 12.0], // Exactly at normal boundary
    ['age' => 12, 'weight' => 12.1], // Exactly at overweight boundary
];

foreach ($edgeCases as $test) {
    $wfa = $who->calculateWeightForAge($test['weight'], $test['age'], 'Male');
    echo "<tr>";
    echo "<td>" . $test['age'] . "</td>";
    echo "<td>" . $test['weight'] . "</td>";
    echo "<td>" . $wfa['classification'] . "</td>";
    echo "<td>" . ($wfa['z_score'] ?? 'N/A') . "</td>";
    echo "</tr>";
}

echo "</table>";
?>
