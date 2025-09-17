<?php
require_once 'who_growth_standards.php';

echo "<h2>Testing Specific Cases from CSV</h2>";

$who = new WHOGrowthStandards();

// Test the exact cases from our CSV that should be Normal but are showing as Obese
$testCases = [
    // These should be Normal but are showing as Obese
    ['weight' => 2.5, 'height' => 50, 'birthday' => '2024-01-15', 'sex' => 'Male', 'screening_date' => '2024-01-15'],
    ['weight' => 3.0, 'height' => 50, 'birthday' => '2024-01-15', 'sex' => 'Male', 'screening_date' => '2024-01-15'],
    ['weight' => 2.4, 'height' => 50, 'birthday' => '2024-01-15', 'sex' => 'Female', 'screening_date' => '2024-01-15'],
    ['weight' => 2.8, 'height' => 50, 'birthday' => '2024-01-15', 'sex' => 'Female', 'screening_date' => '2024-01-15'],
];

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Weight</th><th>Height</th><th>Sex</th><th>Age</th><th>WFA Classification</th><th>HFA Classification</th><th>WFH Classification</th><th>BMI Classification</th></tr>";

foreach ($testCases as $test) {
    $assessment = $who->getComprehensiveAssessment(
        $test['weight'],
        $test['height'],
        $test['birthday'],
        $test['sex'],
        $test['screening_date']
    );
    
    if ($assessment['success']) {
        $results = $assessment['results'];
        $ageInMonths = $who->calculateAgeInMonths($test['birthday'], $test['screening_date']);
        
        echo "<tr>";
        echo "<td>{$test['weight']}kg</td>";
        echo "<td>{$test['height']}cm</td>";
        echo "<td>{$test['sex']}</td>";
        echo "<td>{$ageInMonths}m</td>";
        echo "<td>" . ($results['weight_for_age']['classification'] ?? 'N/A') . "</td>";
        echo "<td>" . ($results['height_for_age']['classification'] ?? 'N/A') . "</td>";
        echo "<td>" . ($results['weight_for_height']['classification'] ?? 'N/A') . "</td>";
        echo "<td>" . ($results['bmi_for_age']['classification'] ?? 'N/A') . "</td>";
        echo "</tr>";
    } else {
        echo "<tr><td colspan='8'>Assessment failed: " . implode(', ', $assessment['errors']) . "</td></tr>";
    }
}

echo "</table>";

// Test if the issue is with age calculation
echo "<h3>Age Calculation Test</h3>";
$birthday = '2024-01-15';
$screening_date = '2024-01-15';
$ageInMonths = $who->calculateAgeInMonths($birthday, $screening_date);
echo "<p>Birthday: $birthday</p>";
echo "<p>Screening Date: $screening_date</p>";
echo "<p>Age in Months: $ageInMonths</p>";
?>
