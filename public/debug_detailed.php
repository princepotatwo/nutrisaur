<?php
require_once '../who_growth_standards.php';

$who = new WHOGrowthStandards();

echo "<h2>Detailed Debug - 0 months, 2.3kg (should be Underweight)</h2>";

// Test the specific failing case
$weight = 2.3;
$age = 0;
$sex = 'Male';

echo "<p><strong>Input:</strong> Age = $age months, Weight = $weight kg, Sex = $sex</p>";

// Get the lookup table directly (we'll need to make it public temporarily)
// For now, let's manually check the values
echo "<p><strong>Expected WHO Table Values for 0 months:</strong></p>";
echo "<ul>";
echo "<li>Severely Underweight: ≤2.1kg</li>";
echo "<li>Underweight: 2.2-2.4kg</li>";
echo "<li>Normal: 2.5-4.4kg</li>";
echo "<li>Overweight: ≥4.5kg</li>";
echo "</ul>";

echo "<p><strong>Logic Check:</strong></p>";
echo "<ul>";
echo "<li>Is 2.3 ≤ 2.1? " . (2.3 <= 2.1 ? "YES (Severely Underweight)" : "NO") . "</li>";
echo "<li>Is 2.3 ≥ 2.2 AND 2.3 ≤ 2.4? " . ((2.3 >= 2.2 && 2.3 <= 2.4) ? "YES (Underweight)" : "NO") . "</li>";
echo "</ul>";

// Call the actual method
$result = $who->calculateWeightForAge($weight, $age, $sex);

echo "<p><strong>Actual Result:</strong></p>";
echo "<pre>";
print_r($result);
echo "</pre>";

// Test a few more cases
echo "<h2>Testing More Cases</h2>";

$testCases = [
    ['age' => 0, 'weight' => 2.0, 'expected' => 'Severely Underweight'],
    ['age' => 0, 'weight' => 2.1, 'expected' => 'Severely Underweight'],
    ['age' => 0, 'weight' => 2.2, 'expected' => 'Underweight'],
    ['age' => 0, 'weight' => 2.3, 'expected' => 'Underweight'],
    ['age' => 0, 'weight' => 2.4, 'expected' => 'Underweight'],
    ['age' => 0, 'weight' => 2.5, 'expected' => 'Normal'],
    ['age' => 0, 'weight' => 4.4, 'expected' => 'Normal'],
    ['age' => 0, 'weight' => 4.5, 'expected' => 'Overweight']
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Age</th><th>Weight</th><th>Expected</th><th>Actual</th><th>Method</th><th>Age Used</th><th>Result</th></tr>";

foreach ($testCases as $test) {
    $result = $who->calculateWeightForAge($test['weight'], $test['age'], 'Male');
    
    $actual = $result['classification'] ?? 'NULL';
    $method = $result['method'] ?? 'NULL';
    $ageUsed = $result['age_used'] ?? 'NULL';
    $isCorrect = ($actual === $test['expected']);
    
    echo "<tr>";
    echo "<td>{$test['age']} months</td>";
    echo "<td>{$test['weight']}kg</td>";
    echo "<td>{$test['expected']}</td>";
    echo "<td>$actual</td>";
    echo "<td>$method</td>";
    echo "<td>$ageUsed</td>";
    echo "<td style='color: " . ($isCorrect ? "green" : "red") . "'>" . ($isCorrect ? "✅ CORRECT" : "❌ WRONG") . "</td>";
    echo "</tr>";
}

echo "</table>";
?>
