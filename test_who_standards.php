<?php
require_once 'who_growth_standards.php';

$who = new WHOGrowthStandards();

// Test data
$weight = 15.0; // kg
$height = 100.0; // cm
$birthDate = '2020-01-01'; // 4 years old
$sex = 'Male';
$screeningDate = '2024-01-01';

echo "Testing WHO Growth Standards...\n\n";

// Test individual functions
echo "1. Weight-for-Age:\n";
$wfa = $who->calculateWeightForAge($weight, 48, $sex);
print_r($wfa);

echo "\n2. Height-for-Age:\n";
$hfa = $who->calculateHeightForAge($height, 48, $sex);
print_r($hfa);

echo "\n3. Weight-for-Height:\n";
$wfh = $who->calculateWeightForHeight($weight, $height, $sex);
print_r($wfh);

echo "\n4. Weight-for-Length:\n";
$wfl = $who->calculateWeightForLength($weight, $height, $sex);
print_r($wfl);

echo "\n5. BMI-for-Age:\n";
$bmi = $who->calculateBMIForAge($weight, $height, $birthDate, $sex, $screeningDate);
print_r($bmi);

echo "\n6. Comprehensive Assessment:\n";
$assessment = $who->getComprehensiveAssessment($weight, $height, $birthDate, $sex, $screeningDate);
print_r($assessment);
?>
