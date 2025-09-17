<?php
require_once 'who_growth_standards.php';

$who = new WHOGrowthStandards();

// Test data - 4 year old child
$weight = 15.0; // kg
$height = 100.0; // cm
$birthDate = '2020-01-01'; // 4 years old
$sex = 'Male';
$screeningDate = '2024-01-01';

echo "=== DEBUGGING WHO GROWTH STANDARDS ===\n\n";

// Test comprehensive assessment
echo "1. Testing Comprehensive Assessment:\n";
$assessment = $who->getComprehensiveAssessment($weight, $height, $birthDate, $sex, $screeningDate);

if ($assessment['success']) {
    echo "✅ Assessment successful\n";
    echo "Results:\n";
    print_r($assessment['results']);
} else {
    echo "❌ Assessment failed: " . ($assessment['error'] ?? 'Unknown error') . "\n";
}

echo "\n2. Testing Individual Functions:\n";

// Test weight-for-age
echo "Weight-for-Age: ";
$wfa = $who->calculateWeightForAge($weight, 48, $sex);
echo $wfa['classification'] . " (z-score: " . ($wfa['z_score'] ?? 'N/A') . ")\n";

// Test height-for-age
echo "Height-for-Age: ";
$hfa = $who->calculateHeightForAge($height, 48, $sex);
echo $hfa['classification'] . " (z-score: " . ($hfa['z_score'] ?? 'N/A') . ")\n";

// Test weight-for-height
echo "Weight-for-Height: ";
$wfh = $who->calculateWeightForHeight($weight, $height, $sex);
echo $wfh['classification'] . " (z-score: " . ($wfh['z_score'] ?? 'N/A') . ")\n";

// Test weight-for-length
echo "Weight-for-Length: ";
$wfl = $who->calculateWeightForLength($weight, $height, $sex);
echo $wfl['classification'] . " (z-score: " . ($wfl['z_score'] ?? 'N/A') . ")\n";

// Test BMI-for-age
echo "BMI-for-Age: ";
$bmi = $who->calculateBMIForAge($weight, $height, $birthDate, $sex, $screeningDate);
echo $bmi['classification'] . " (z-score: " . ($bmi['z_score'] ?? 'N/A') . ")\n";

echo "\n3. Testing with different ages:\n";

// Test with 2 year old (24 months)
echo "2 year old (24 months):\n";
$assessment2 = $who->getComprehensiveAssessment(12.0, 85.0, '2022-01-01', 'Male', '2024-01-01');
if ($assessment2['success']) {
    echo "Weight-for-Age: " . $assessment2['results']['weight_for_age']['classification'] . "\n";
    echo "Height-for-Age: " . $assessment2['results']['height_for_age']['classification'] . "\n";
    echo "Weight-for-Height: " . $assessment2['results']['weight_for_height']['classification'] . "\n";
    echo "BMI-for-Age: " . $assessment2['results']['bmi_for_age']['classification'] . "\n";
} else {
    echo "Assessment failed: " . ($assessment2['error'] ?? 'Unknown error') . "\n";
}

// Test with 6 month old
echo "\n6 month old:\n";
$assessment3 = $who->getComprehensiveAssessment(7.0, 65.0, '2023-07-01', 'Male', '2024-01-01');
if ($assessment3['success']) {
    echo "Weight-for-Age: " . $assessment3['results']['weight_for_age']['classification'] . "\n";
    echo "Height-for-Age: " . $assessment3['results']['height_for_age']['classification'] . "\n";
    echo "Weight-for-Height: " . $assessment3['results']['weight_for_height']['classification'] . "\n";
    echo "BMI-for-Age: " . $assessment3['results']['bmi_for_age']['classification'] . "\n";
} else {
    echo "Assessment failed: " . ($assessment3['error'] ?? 'Unknown error') . "\n";
}
?>
