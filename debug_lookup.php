<?php
require_once 'who_growth_standards.php';

echo "<h2>Debug Lookup Table Logic</h2>";

$who = new WHOGrowthStandards();

// Test a specific case that should be Normal
$weight = 3.5; // Should be Normal for 0 months
$ageInMonths = 0;
$sex = 'Male';

echo "<h3>Test Case: Weight {$weight} kg, Age {$ageInMonths} months, Sex {$sex}</h3>";

// Test the lookup table directly
$lookup = $who->getWeightForAgeBoysLookup();
echo "<h4>Lookup Table for Age 0:</h4>";
echo "<pre>" . print_r($lookup[0], true) . "</pre>";

// Test the findClosestAge method
$closestAge = $who->findClosestAge($lookup, $ageInMonths);
echo "<h4>Closest Age Found: " . ($closestAge ?? 'null') . "</h4>";

if ($closestAge !== null) {
    $ranges = $lookup[$closestAge];
    echo "<h4>Ranges for Age {$closestAge}:</h4>";
    echo "<pre>" . print_r($ranges, true) . "</pre>";
    
    // Test each range
    foreach ($ranges as $category => $range) {
        $inRange = ($weight >= $range['min'] && $weight <= $range['max']);
        echo "<p><strong>{$category}:</strong> {$range['min']} - {$range['max']} | Weight {$weight} in range: " . ($inRange ? 'YES' : 'NO') . "</p>";
    }
}

// Test the actual calculation
echo "<h4>Actual Calculation Result:</h4>";
$result = $who->calculateWeightForAge($weight, $ageInMonths, $sex);
echo "<pre>" . print_r($result, true) . "</pre>";
?>
