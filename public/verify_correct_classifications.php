<?php
require_once '../who_growth_standards.php';

echo "<h2>Verification of Correct Classifications Based on Official WHO Table</h2>";

$who = new WHOGrowthStandards();

// Test cases with correct expected classifications based on official WHO table
$testCases = [
    // Age 0 months: Severely ≤ 2.1, Underweight 2.2-2.4, Normal 2.5-4.4, Overweight ≥ 4.5
    ['name' => 'Severely Underweight 0mo Boy', 'weight' => 2.0, 'height' => 50.0, 'birthday' => '2024-09-15', 'sex' => 'Male', 'expected' => 'Severely Underweight'],
    ['name' => 'Underweight 0mo Boy', 'weight' => 2.3, 'height' => 50.0, 'birthday' => '2024-09-15', 'sex' => 'Male', 'expected' => 'Underweight'],
    ['name' => 'Normal 0mo Boy', 'weight' => 3.0, 'height' => 50.0, 'birthday' => '2024-09-15', 'sex' => 'Male', 'expected' => 'Normal'],
    ['name' => 'Overweight 0mo Boy', 'weight' => 4.5, 'height' => 50.0, 'birthday' => '2024-09-15', 'sex' => 'Male', 'expected' => 'Overweight'],
    
    // Age 6 months: Severely ≤ 5.7, Underweight 5.8-6.3, Normal 6.4-9.8, Overweight ≥ 9.9
    ['name' => 'Severely Underweight 6mo Boy', 'weight' => 5.5, 'height' => 72.0, 'birthday' => '2024-03-15', 'sex' => 'Male', 'expected' => 'Severely Underweight'],
    ['name' => 'Underweight 6mo Boy', 'weight' => 6.0, 'height' => 72.0, 'birthday' => '2024-03-15', 'sex' => 'Male', 'expected' => 'Underweight'],
    ['name' => 'Normal 6mo Boy', 'weight' => 8.0, 'height' => 72.0, 'birthday' => '2024-03-15', 'sex' => 'Male', 'expected' => 'Normal'],
    ['name' => 'Overweight 6mo Boy', 'weight' => 9.9, 'height' => 72.0, 'birthday' => '2024-03-15', 'sex' => 'Male', 'expected' => 'Overweight'],
    
    // Age 24 months: Severely ≤ 8.6, Underweight 8.7-9.6, Normal 9.7-15.3, Overweight ≥ 15.4
    ['name' => 'Severely Underweight 24mo Boy', 'weight' => 8.5, 'height' => 85.0, 'birthday' => '2022-09-15', 'sex' => 'Male', 'expected' => 'Severely Underweight'],
    ['name' => 'Underweight 24mo Boy', 'weight' => 9.0, 'height' => 85.0, 'birthday' => '2022-09-15', 'sex' => 'Male', 'expected' => 'Underweight'],
    ['name' => 'Normal 24mo Boy', 'weight' => 12.0, 'height' => 85.0, 'birthday' => '2022-09-15', 'sex' => 'Male', 'expected' => 'Normal'],
    ['name' => 'Overweight 24mo Boy', 'weight' => 15.4, 'height' => 85.0, 'birthday' => '2022-09-15', 'sex' => 'Male', 'expected' => 'Overweight'],
    
    // Age 71 months: Severely ≤ 13.9, Underweight 14.0-15.6, Normal 15.7-24.0, Overweight ≥ 24.1
    ['name' => 'Severely Underweight 71mo Boy', 'weight' => 13.5, 'height' => 115.0, 'birthday' => '2018-10-15', 'sex' => 'Male', 'expected' => 'Severely Underweight'],
    ['name' => 'Underweight 71mo Boy', 'weight' => 15.0, 'height' => 115.0, 'birthday' => '2018-10-15', 'sex' => 'Male', 'expected' => 'Underweight'],
    ['name' => 'Normal 71mo Boy', 'weight' => 20.0, 'height' => 115.0, 'birthday' => '2018-10-15', 'sex' => 'Male', 'expected' => 'Normal'],
    ['name' => 'Overweight 71mo Boy', 'weight' => 24.1, 'height' => 115.0, 'birthday' => '2018-10-15', 'sex' => 'Male', 'expected' => 'Overweight'],
];

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Name</th><th>Age</th><th>Weight</th><th>Height</th><th>Expected</th><th>Actual</th><th>Match</th><th>Z-Score</th></tr>";

$correctCount = 0;
$totalCount = count($testCases);

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
            
            if ($actual === $case['expected']) {
                $correctCount++;
            }
            
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

echo "<h3>Summary</h3>";
echo "Correct classifications: $correctCount / $totalCount (" . round(($correctCount / $totalCount) * 100, 1) . "%)";
?>
