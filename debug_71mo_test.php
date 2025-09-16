<?php
// Simple test to debug the 71-month overweight case
echo "=== 71-Month Overweight Test Debug ===\n";

// Test data from CSV
$birthDate = '2018-10-15';
$screeningDate = '2024-09-15 10:00:00';
$weight = 24.1;
$height = 115.0;
$sex = 'Male';
$expected = 'Overweight';

echo "Birth Date: $birthDate\n";
echo "Screening Date: $screeningDate\n";
echo "Weight: $weight kg\n";
echo "Height: $height cm\n";
echo "Sex: $sex\n";
echo "Expected: $expected\n\n";

// Manual age calculation
$birth = new DateTime($birthDate);
$screening = new DateTime($screeningDate);
$age = $birth->diff($screening);
$ageInMonths = ($age->y * 12) + $age->m;
if ($age->d >= 15) {
    $ageInMonths += 1;
}

echo "Manual Age Calculation:\n";
echo "Years: {$age->y}, Months: {$age->m}, Days: {$age->d}\n";
echo "Age in months: $ageInMonths\n\n";

// Check WHO standards for 71-month boys
echo "WHO Standards for 71-month boys:\n";
echo "Severely Underweight: 0 - 13.4 kg\n";
echo "Underweight: 13.5 - 15.1 kg\n";
echo "Normal: 15.2 - 22.6 kg\n";
echo "Overweight: 22.7+ kg\n\n";

echo "Weight classification check:\n";
if ($weight <= 13.4) {
    echo "Result: Severely Underweight (weight ≤ 13.4)\n";
} elseif ($weight >= 13.5 && $weight <= 15.1) {
    echo "Result: Underweight (13.5 ≤ weight ≤ 15.1)\n";
} elseif ($weight >= 15.2 && $weight <= 22.6) {
    echo "Result: Normal (15.2 ≤ weight ≤ 22.6)\n";
} elseif ($weight >= 22.7) {
    echo "Result: Overweight (weight ≥ 22.7)\n";
} else {
    echo "Result: Unknown range\n";
}

echo "\nManual verification: Weight $weight kg should be Overweight (≥ 22.7 kg)\n";

// Now test with actual WHO class
require_once 'who_growth_standards.php';
$who = new WHOGrowthStandards();

echo "\n=== Testing with WHO Class ===\n";
$calculatedAge = $who->calculateAgeInMonths($birthDate, $screeningDate);
echo "WHO calculated age: $calculatedAge months\n";

$result = $who->calculateWeightForAge($weight, $calculatedAge, $sex);
echo "WHO classification: " . ($result['classification'] ?? 'NULL') . "\n";
echo "Z-Score: " . ($result['z_score'] ?? 'NULL') . "\n";
echo "Age used: " . ($result['age_used'] ?? 'NULL') . "\n";
echo "Method: " . ($result['method'] ?? 'NULL') . "\n";
echo "Weight range: " . ($result['weight_range'] ?? 'NULL') . "\n";

// Test comprehensive assessment
echo "\n=== Testing Comprehensive Assessment ===\n";
$assessment = $who->getComprehensiveAssessment($weight, $height, $birthDate, $sex, $screeningDate);
if ($assessment['success']) {
    $results = $assessment['results'];
    echo "Weight-for-Age: " . ($results['weight_for_age']['classification'] ?? 'NULL') . "\n";
    echo "Success: This is the result that will be used in the dashboard\n";
} else {
    echo "Assessment failed: " . ($assessment['error'] ?? 'Unknown error') . "\n";
}
?>
