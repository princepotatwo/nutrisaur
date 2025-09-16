<?php
require_once 'who_growth_standards.php';

$who = new WHOGrowthStandards();

// Test case: Overweight 71mo Boy
$birthDate = '2018-10-15';
$screeningDate = '2024-09-15 10:00:00';
$weight = 24.1;
$height = 115.0;
$sex = 'Male';

echo "=== Testing Age Calculation ===\n";
$ageInMonths = $who->calculateAgeInMonths($birthDate, $screeningDate);
echo "Birth Date: $birthDate\n";
echo "Screening Date: $screeningDate\n";
echo "Age in Months: $ageInMonths\n";

echo "\n=== Testing Weight-for-Age Classification ===\n";
$result = $who->calculateWeightForAge($weight, $ageInMonths, $sex);
echo "Weight: $weight kg\n";
echo "Height: $height cm\n";
echo "Sex: $sex\n";
echo "Classification: " . ($result['classification'] ?? 'NULL') . "\n";
echo "Z-Score: " . ($result['z_score'] ?? 'NULL') . "\n";

echo "\n=== Testing Comprehensive Assessment ===\n";
$assessment = $who->getComprehensiveAssessment($weight, $height, $birthDate, $sex, $screeningDate);
if ($assessment['success']) {
    $results = $assessment['results'];
    echo "Weight-for-Age: " . ($results['weight_for_age']['classification'] ?? 'NULL') . "\n";
    echo "Height-for-Age: " . ($results['height_for_age']['classification'] ?? 'NULL') . "\n";
    echo "Weight-for-Height: " . ($results['weight_for_height']['classification'] ?? 'NULL') . "\n";
    echo "BMI-for-Age: " . ($results['bmi_for_age']['classification'] ?? 'NULL') . "\n";
} else {
    echo "Assessment failed: " . ($assessment['error'] ?? 'Unknown error') . "\n";
}

echo "\n=== Manual Age Calculation ===\n";
$birth = new DateTime($birthDate);
$screening = new DateTime($screeningDate);
$age = $birth->diff($screening);
$manualAge = ($age->y * 12) + $age->m;
if ($age->d >= 15) {
    $manualAge += 1;
}
echo "Manual calculation: $manualAge months\n";
echo "Years: {$age->y}, Months: {$age->m}, Days: {$age->d}\n";
?>