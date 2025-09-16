<?php
require_once '../who_growth_standards.php';

// Test the specific 71-month case
$who = new WHOGrowthStandards();

$birthDate = '2018-10-15';
$screeningDate = '2024-09-15 10:00:00';
$weight = 24.1;
$height = 115.0;
$sex = 'Male';

echo "<h1>71-Month Overweight Test Debug</h1>";
echo "<p><strong>Birth Date:</strong> $birthDate</p>";
echo "<p><strong>Screening Date:</strong> $screeningDate</p>";
echo "<p><strong>Weight:</strong> $weight kg</p>";
echo "<p><strong>Height:</strong> $height cm</p>";
echo "<p><strong>Sex:</strong> $sex</p>";
echo "<p><strong>Expected:</strong> Overweight</p>";

// Age calculation
$ageInMonths = $who->calculateAgeInMonths($birthDate, $screeningDate);
echo "<h2>Age Calculation</h2>";
echo "<p><strong>Calculated Age:</strong> $ageInMonths months</p>";

// Manual verification
$birth = new DateTime($birthDate);
$screening = new DateTime($screeningDate);
$age = $birth->diff($screening);
$manualAge = ($age->y * 12) + $age->m;
if ($age->d >= 15) {
    $manualAge += 1;
}
echo "<p><strong>Manual Age:</strong> $manualAge months (Years: {$age->y}, Months: {$age->m}, Days: {$age->d})</p>";

// Weight-for-Age calculation
echo "<h2>Weight-for-Age Classification</h2>";
$result = $who->calculateWeightForAge($weight, $ageInMonths, $sex);
echo "<p><strong>Classification:</strong> " . ($result['classification'] ?? 'NULL') . "</p>";
echo "<p><strong>Z-Score:</strong> " . ($result['z_score'] ?? 'NULL') . "</p>";
echo "<p><strong>Age Used:</strong> " . ($result['age_used'] ?? 'NULL') . "</p>";
echo "<p><strong>Method:</strong> " . ($result['method'] ?? 'NULL') . "</p>";
echo "<p><strong>Weight Range:</strong> " . ($result['weight_range'] ?? 'NULL') . "</p>";

// Manual classification check
echo "<h2>Manual Classification Check (71-month boys)</h2>";
echo "<ul>";
echo "<li>Severely Underweight: 0 - 13.4 kg</li>";
echo "<li>Underweight: 13.5 - 15.1 kg</li>";
echo "<li>Normal: 15.2 - 22.6 kg</li>";
echo "<li>Overweight: 22.7+ kg</li>";
echo "</ul>";

if ($weight <= 13.4) {
    echo "<p><strong>Manual Result:</strong> Severely Underweight</p>";
} elseif ($weight >= 13.5 && $weight <= 15.1) {
    echo "<p><strong>Manual Result:</strong> Underweight</p>";
} elseif ($weight >= 15.2 && $weight <= 22.6) {
    echo "<p><strong>Manual Result:</strong> Normal</p>";
} elseif ($weight >= 22.7) {
    echo "<p><strong>Manual Result:</strong> Overweight ✓</p>";
} else {
    echo "<p><strong>Manual Result:</strong> Unknown range</p>";
}

// Comprehensive assessment
echo "<h2>Comprehensive Assessment</h2>";
$assessment = $who->getComprehensiveAssessment($weight, $height, $birthDate, $sex, $screeningDate);
if ($assessment['success']) {
    $results = $assessment['results'];
    echo "<p><strong>Weight-for-Age:</strong> " . ($results['weight_for_age']['classification'] ?? 'NULL') . "</p>";
    echo "<p><strong>Height-for-Age:</strong> " . ($results['height_for_age']['classification'] ?? 'NULL') . "</p>";
    echo "<p><strong>Weight-for-Height:</strong> " . ($results['weight_for_height']['classification'] ?? 'NULL') . "</p>";
    echo "<p><strong>BMI-for-Age:</strong> " . ($results['bmi_for_age']['classification'] ?? 'NULL') . "</p>";
    echo "<p><strong>Age in Months:</strong> " . ($results['age_months'] ?? 'NULL') . "</p>";
    echo "<p><strong>BMI:</strong> " . ($results['bmi'] ?? 'NULL') . "</p>";
} else {
    echo "<p><strong>Assessment failed:</strong> " . ($assessment['error'] ?? 'Unknown error') . "</p>";
}

echo "<h2>Conclusion</h2>";
echo "<p>With weight $weight kg at $ageInMonths months, this should be classified as <strong>Overweight</strong> since $weight ≥ 22.7 kg.</p>";
?>
