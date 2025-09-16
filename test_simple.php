<?php
require_once 'who_growth_standards.php';

echo "<h2>Simple Test - Debug the Issue</h2>";

$who = new WHOGrowthStandards();

// Test a simple case that should be Normal
$weight = 3.5; // Should be Normal for 0 months
$height = 50;
$birthday = '2024-09-15';
$sex = 'Male';

echo "<h3>Test Case: Weight {$weight} kg, Height {$height} cm, Birthday {$birthday}, Sex {$sex}</h3>";

// Calculate age manually
$birthDate = new DateTime($birthday);
$today = new DateTime();
$age = $today->diff($birthDate);
$ageInMonths = ($age->y * 12) + $age->m;
if ($age->d >= 15) {
    $ageInMonths += 1;
}

echo "<p><strong>Current Date:</strong> " . $today->format('Y-m-d') . "</p>";
echo "<p><strong>Calculated Age:</strong> {$age->y}y {$age->m}m ({$ageInMonths} months)</p>";

// Test the lookup table directly
$lookup = $who->getWeightForAgeBoysLookup();
echo "<p><strong>Available ages in lookup:</strong> " . implode(', ', array_keys($lookup)) . "</p>";

$closestAge = $who->findClosestAge($lookup, $ageInMonths);
echo "<p><strong>Closest age found:</strong> " . ($closestAge ?? 'null') . "</p>";

if ($closestAge !== null) {
    $ranges = $lookup[$closestAge];
    echo "<p><strong>Ranges for age {$closestAge}:</strong></p>";
    foreach ($ranges as $category => $range) {
        $inRange = ($weight >= $range['min'] && $weight <= $range['max']);
        echo "<p>&nbsp;&nbsp;{$category}: {$range['min']}-{$range['max']} | Weight {$weight} in range: " . ($inRange ? 'YES' : 'NO') . "</p>";
    }
}

// Test the actual calculation
echo "<h3>Actual Calculation Result:</h3>";
$result = $who->calculateWeightForAge($weight, $ageInMonths, $sex);
echo "<pre>" . print_r($result, true) . "</pre>";

// Test comprehensive assessment
echo "<h3>Comprehensive Assessment Result:</h3>";
$assessment = $who->getComprehensiveAssessment($weight, $height, $birthday, $sex);
echo "<pre>" . print_r($assessment, true) . "</pre>";
?>
