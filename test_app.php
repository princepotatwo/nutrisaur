<?php
// Test the application functionality
echo "<h2>Testing Nutrisaur Application</h2>";

// Test 1: Check if WHO class loads without errors
echo "<h3>Test 1: WHO Growth Standards Class</h3>";
try {
    require_once 'who_growth_standards.php';
    $who = new WHOGrowthStandards();
    echo "<p style='color: green;'>✓ WHO Growth Standards class loaded successfully</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error loading WHO class: " . $e->getMessage() . "</p>";
}

// Test 2: Test specific WHO table cases
echo "<h3>Test 2: WHO Table Accuracy</h3>";
$testCases = [
    ['age' => 0, 'weight' => 2.0, 'expected' => 'Severely Underweight'],
    ['age' => 0, 'weight' => 2.3, 'expected' => 'Underweight'],
    ['age' => 0, 'weight' => 3.5, 'expected' => 'Normal'],
    ['age' => 0, 'weight' => 4.6, 'expected' => 'Overweight'],
    ['age' => 12, 'weight' => 6.5, 'expected' => 'Severely Underweight'],
    ['age' => 12, 'weight' => 7.3, 'expected' => 'Underweight'],
    ['age' => 12, 'weight' => 9.5, 'expected' => 'Normal'],
    ['age' => 12, 'weight' => 12.5, 'expected' => 'Overweight'],
    ['age' => 24, 'weight' => 8.0, 'expected' => 'Severely Underweight'],
    ['age' => 24, 'weight' => 9.0, 'expected' => 'Underweight'],
    ['age' => 24, 'weight' => 12.0, 'expected' => 'Normal'],
    ['age' => 24, 'weight' => 16.0, 'expected' => 'Overweight']
];

$correct = 0;
$total = count($testCases);

echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Age</th><th>Weight</th><th>Expected</th><th>Actual</th><th>Match</th><th>Z-Score</th></tr>";

foreach ($testCases as $test) {
    try {
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
        echo "</tr>";
    } catch (Exception $e) {
        echo "<tr><td colspan='6' style='color: red;'>Error: " . $e->getMessage() . "</td></tr>";
    }
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

// Test 3: Test database connection
echo "<h3>Test 3: Database Connection</h3>";
try {
    require_once 'config.php';
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Test query
    $sql = "SELECT COUNT(*) as count FROM community_users";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Community users in database: " . $result['count'] . "</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
}

echo "<h3>Test Complete!</h3>";
echo "<p>If all tests pass, the application should be working correctly.</p>";
?>
