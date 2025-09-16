<?php
require_once '../who_growth_standards.php';

echo "<h2>CSV Classification Verification</h2>";

$who = new WHOGrowthStandards();

// Test cases from the CSV
$testCases = [
    ['name' => 'Severely Underweight 0mo Boy', 'weight' => 2.0, 'height' => 50.0, 'birthday' => '2024-09-15', 'sex' => 'Male', 'expected' => 'Severely Underweight'],
    ['name' => 'Underweight 0mo Boy', 'weight' => 2.5, 'height' => 50.0, 'birthday' => '2024-09-15', 'sex' => 'Male', 'expected' => 'Underweight'],
    ['name' => 'Normal 0mo Boy', 'weight' => 3.0, 'height' => 50.0, 'birthday' => '2024-09-15', 'sex' => 'Male', 'expected' => 'Normal'],
    ['name' => 'Overweight 0mo Boy', 'weight' => 3.5, 'height' => 50.0, 'birthday' => '2024-09-15', 'sex' => 'Male', 'expected' => 'Overweight'],
    ['name' => 'Severely Underweight 6mo Boy', 'weight' => 6.0, 'height' => 72.0, 'birthday' => '2024-03-15', 'sex' => 'Male', 'expected' => 'Severely Underweight'],
    ['name' => 'Underweight 6mo Boy', 'weight' => 7.0, 'height' => 72.0, 'birthday' => '2024-03-15', 'sex' => 'Male', 'expected' => 'Underweight'],
    ['name' => 'Normal 6mo Boy', 'weight' => 8.0, 'height' => 72.0, 'birthday' => '2024-03-15', 'sex' => 'Male', 'expected' => 'Normal'],
    ['name' => 'Overweight 6mo Boy', 'weight' => 8.8, 'height' => 72.0, 'birthday' => '2024-03-15', 'sex' => 'Male', 'expected' => 'Overweight'],
];

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Name</th><th>Age</th><th>Weight</th><th>Height</th><th>Expected</th><th>Actual</th><th>Match</th><th>Z-Score</th></tr>";

foreach ($testCases as $case) {
    try {
        $result = $who->getComprehensiveAssessment(
            $case['weight'], 
            $case['height'], 
            $case['birthday'], 
            $case['sex'],
            '2024-09-15 10:00:00'
        );
        
        if ($result['success']) {
            $actual = $result['growth_standards']['weight_for_age']['classification'] ?? 'N/A';
            $zScore = $result['growth_standards']['weight_for_age']['z_score'] ?? 'N/A';
            $age = $result['age_months'] ?? 'N/A';
            $match = ($actual === $case['expected']) ? '✅' : '❌';
            
            echo "<tr>";
            echo "<td>{$case['name']}</td>";
            echo "<td>{$age}mo</td>";
            echo "<td>{$case['weight']}kg</td>";
            echo "<td>{$case['height']}cm</td>";
            echo "<td>{$case['expected']}</td>";
            echo "<td>{$actual}</td>";
            echo "<td>{$match}</td>";
            echo "<td>{$zScore}</td>";
            echo "</tr>";
        } else {
            echo "<tr>";
            echo "<td>{$case['name']}</td>";
            echo "<td>Error</td>";
            echo "<td>{$case['weight']}kg</td>";
            echo "<td>{$case['height']}cm</td>";
            echo "<td>{$case['expected']}</td>";
            echo "<td>ERROR: " . ($result['error'] ?? 'Unknown') . "</td>";
            echo "<td>❌</td>";
            echo "<td>N/A</td>";
            echo "</tr>";
        }
    } catch (Exception $e) {
        echo "<tr>";
        echo "<td>{$case['name']}</td>";
        echo "<td>Exception</td>";
        echo "<td>{$case['weight']}kg</td>";
        echo "<td>{$case['height']}cm</td>";
        echo "<td>{$case['expected']}</td>";
        echo "<td>EXCEPTION: " . $e->getMessage() . "</td>";
        echo "<td>❌</td>";
        echo "<td>N/A</td>";
        echo "</tr>";
    }
}

echo "</table>";
?>
