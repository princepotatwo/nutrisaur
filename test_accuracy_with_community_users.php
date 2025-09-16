<?php
require_once 'who_growth_standards.php';
require_once 'config.php';

echo "<h2>Testing WHO Growth Standards Accuracy with Community Users</h2>";

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Get sample community users data
    $sql = "SELECT id, name, sex, birthday, weight, height, screening_date 
            FROM community_users 
            WHERE weight IS NOT NULL AND height IS NOT NULL 
            ORDER BY id LIMIT 20";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "<p>No community users found with weight and height data.</p>";
        exit;
    }
    
    $who = new WHOGrowthStandards();
    
    echo "<h3>Testing " . count($users) . " Community Users</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Name</th><th>Sex</th><th>Age (months)</th><th>Weight (kg)</th><th>Height (cm)</th><th>WFA Classification</th><th>Z-Score</th><th>Method</th></tr>";
    
    foreach ($users as $user) {
        // Calculate age in months
        $birthDate = new DateTime($user['birthday']);
        $screeningDate = new DateTime($user['screening_date']);
        $age = $birthDate->diff($screeningDate);
        $ageInMonths = ($age->y * 12) + $age->m;
        if ($age->d >= 15) {
            $ageInMonths += 1;
        }
        
        // Get Weight-for-Age assessment
        $wfa = $who->calculateWeightForAge($user['weight'], $ageInMonths, $user['sex']);
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user['id']) . "</td>";
        echo "<td>" . htmlspecialchars($user['name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['sex']) . "</td>";
        echo "<td>" . $ageInMonths . "</td>";
        echo "<td>" . $user['weight'] . "</td>";
        echo "<td>" . $user['height'] . "</td>";
        echo "<td>" . htmlspecialchars($wfa['classification']) . "</td>";
        echo "<td>" . ($wfa['z_score'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($wfa['method'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Test specific cases from the WHO table
    echo "<h3>Testing Specific WHO Table Cases</h3>";
    
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
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Age (months)</th><th>Weight (kg)</th><th>Expected</th><th>Actual</th><th>Match</th><th>Z-Score</th></tr>";
    
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
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
