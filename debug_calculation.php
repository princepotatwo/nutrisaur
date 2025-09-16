<?php
require_once 'who_growth_standards.php';

echo "<h2>Debug Calculation Issues</h2>";

$who = new WHOGrowthStandards();

// Test cases that should work correctly
$testCases = [
    // 0-month-old boys (should be 0 months old on 2024-09-15)
    ['name' => 'Boy 0mo SU', 'weight' => 2.0, 'height' => 50, 'birthday' => '2024-09-15', 'sex' => 'Male', 'screening_date' => '2024-09-15 10:30:00', 'expected' => 'Severely Underweight'],
    ['name' => 'Boy 0mo UW', 'weight' => 2.3, 'height' => 50, 'birthday' => '2024-09-15', 'sex' => 'Male', 'screening_date' => '2024-09-15 10:30:00', 'expected' => 'Underweight'],
    ['name' => 'Boy 0mo N', 'weight' => 3.5, 'height' => 50, 'birthday' => '2024-09-15', 'sex' => 'Male', 'screening_date' => '2024-09-15 10:30:00', 'expected' => 'Normal'],
    ['name' => 'Boy 0mo OW', 'weight' => 4.6, 'height' => 50, 'birthday' => '2024-09-15', 'sex' => 'Male', 'screening_date' => '2024-09-15 10:30:00', 'expected' => 'Overweight'],
    
    // 12-month-old boys
    ['name' => 'Boy 12mo SU', 'weight' => 6.8, 'height' => 75, 'birthday' => '2023-09-15', 'sex' => 'Male', 'screening_date' => '2024-09-15 10:30:00', 'expected' => 'Severely Underweight'],
    ['name' => 'Boy 12mo UW', 'weight' => 7.3, 'height' => 75, 'birthday' => '2023-09-15', 'sex' => 'Male', 'screening_date' => '2024-09-15 10:30:00', 'expected' => 'Underweight'],
    ['name' => 'Boy 12mo N', 'weight' => 9.0, 'height' => 75, 'birthday' => '2023-09-15', 'sex' => 'Male', 'screening_date' => '2024-09-15 10:30:00', 'expected' => 'Normal'],
    ['name' => 'Boy 12mo OW', 'weight' => 12.5, 'height' => 75, 'birthday' => '2023-09-15', 'sex' => 'Male', 'screening_date' => '2024-09-15 10:30:00', 'expected' => 'Overweight'],
];

foreach ($testCases as $test) {
    echo "<h3>{$test['name']} (Expected: {$test['expected']})</h3>";
    echo "<p>Weight: {$test['weight']} kg, Height: {$test['height']} cm, Birthday: {$test['birthday']}, Sex: {$test['sex']}, Screening: {$test['screening_date']}</p>";
    
    // Calculate age manually using screening date
    $birthDate = new DateTime($test['birthday']);
    $screeningDate = new DateTime($test['screening_date']);
    $age = $birthDate->diff($screeningDate);
    $ageInMonths = ($age->y * 12) + $age->m;
    if ($age->d >= 15) {
        $ageInMonths += 1;
    }
    
    echo "<p><strong>Calculated Age:</strong> {$age->y}y {$age->m}m ({$ageInMonths} months)</p>";
    
    // Test lookup table
    if ($test['sex'] === 'Male') {
        $lookup = $who->getWeightForAgeBoysLookup();
        echo "<p><strong>Available ages in lookup:</strong> " . implode(', ', array_keys($lookup)) . "</p>";
        
        $closestAge = $who->findClosestAge($lookup, $ageInMonths);
        echo "<p><strong>Closest age found:</strong> " . ($closestAge ?? 'null') . "</p>";
        
        if ($closestAge !== null) {
            $ranges = $lookup[$closestAge];
            echo "<p><strong>Ranges for age {$closestAge}:</strong></p>";
            foreach ($ranges as $category => $range) {
                $inRange = ($test['weight'] >= $range['min'] && $test['weight'] <= $range['max']);
                echo "<p>&nbsp;&nbsp;{$category}: {$range['min']}-{$range['max']} | Weight {$test['weight']} in range: " . ($inRange ? 'YES' : 'NO') . "</p>";
            }
        }
    }
    
    // Test the actual calculation
    try {
        $result = $who->calculateWeightForAge($test['weight'], $ageInMonths, $test['sex']);
        echo "<p><strong>Weight-for-Age Result:</strong></p>";
        echo "<pre>" . print_r($result, true) . "</pre>";
        
        // Check if result matches expected
        $actual = $result['classification'] ?? 'Unknown';
        $matches = ($actual === $test['expected']);
        echo "<p><strong>Result Match:</strong> " . ($matches ? '✅ CORRECT' : '❌ WRONG') . " (Expected: {$test['expected']}, Got: {$actual})</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
}
?>
