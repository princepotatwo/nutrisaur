<?php
require_once 'who_growth_standards.php';

echo "<h2>Debug Z-Score Calculation</h2>";

$who = new WHOGrowthStandards();

// Test case: 12-month-old boy, weight 8.5 kg
$weight = 8.5;
$height = 75.0;
$birthDate = '2023-09-15';
$sex = 'Male';
$screeningDate = '2024-09-15'; // 12 months old

echo "<h3>Test Case: 12-month-old boy, weight 8.5 kg</h3>";

// Get the raw data
$boysData = $who->getWeightForAgeBoys();
echo "<p>Raw boys data for age 12: " . json_encode($boysData[12] ?? 'NOT FOUND') . "</p>";

// Test the calculation
$result = $who->getComprehensiveAssessment($weight, $height, $birthDate, $sex, $screeningDate);

echo "<p>Comprehensive Assessment Result:</p>";
echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";

// Test individual calculations
echo "<h3>Individual Calculations:</h3>";

// Weight-for-Age
$wfa = $who->calculateWeightForAge($weight, $birthDate, $sex, $screeningDate);
echo "<p>Weight-for-Age: " . json_encode($wfa, JSON_PRETTY_PRINT) . "</p>";

// Weight-for-Height
$wfh = $who->calculateWeightForHeight($weight, $height, $sex);
echo "<p>Weight-for-Height: " . json_encode($wfh, JSON_PRETTY_PRINT) . "</p>";

// Test with a different weight to see if Z-score changes
echo "<h3>Test with different weight (10.0 kg):</h3>";
$result2 = $who->getComprehensiveAssessment(10.0, $height, $birthDate, $sex, $screeningDate);
echo "<pre>" . json_encode($result2, JSON_PRETTY_PRINT) . "</pre>";

// Test with a much higher weight
echo "<h3>Test with higher weight (12.0 kg):</h3>";
$result3 = $who->getComprehensiveAssessment(12.0, $height, $birthDate, $sex, $screeningDate);
echo "<pre>" . json_encode($result3, JSON_PRETTY_PRINT) . "</pre>";
?>
