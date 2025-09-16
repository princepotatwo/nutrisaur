<?php
require_once '../who_growth_standards.php';

$who = new WHOGrowthStandards();

// Test data from the problematic case
$birthDate = '2018-10-15';
$screeningDate = '2024-09-15 10:00:00';
$weight = 24.1;
$height = 115.0;
$sex = 'Male';
$expected = 'Overweight';

echo "<h1>🔍 Complete WHO Classification Debug</h1>";
echo "<p><strong>Test Case:</strong> 71-month boy with 24.1kg (should be Overweight)</p>";

// 1. Basic Data
echo "<h2>📊 Test Data</h2>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><td><strong>Birth Date</strong></td><td>$birthDate</td></tr>";
echo "<tr><td><strong>Screening Date</strong></td><td>$screeningDate</td></tr>";
echo "<tr><td><strong>Weight</strong></td><td>$weight kg</td></tr>";
echo "<tr><td><strong>Height</strong></td><td>$height cm</td></tr>";
echo "<tr><td><strong>Sex</strong></td><td>$sex</td></tr>";
echo "<tr><td><strong>Expected</strong></td><td>$expected</td></tr>";
echo "</table>";

// 2. Age Calculation
echo "<h2>📅 Age Calculation</h2>";
$ageInMonths = $who->calculateAgeInMonths($birthDate, $screeningDate);
echo "<p><strong>Calculated Age:</strong> $ageInMonths months</p>";

// Manual age calculation
$birth = new DateTime($birthDate);
$screening = new DateTime($screeningDate);
$age = $birth->diff($screening);
$manualAge = ($age->y * 12) + $age->m;
if ($age->d >= 15) {
    $manualAge += 1;
}
echo "<p><strong>Manual Age:</strong> $manualAge months (Years: {$age->y}, Months: {$age->m}, Days: {$age->d})</p>";

// 3. Lookup Table Data
echo "<h2>📋 Lookup Table Data</h2>";

echo "<h3>Boys Lookup Table - Age 71</h3>";
$boysRanges = $who->getWeightForAgeBoysLookupTable();
if (isset($boysRanges[71])) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Classification</th><th>Range</th></tr>";
    foreach ($boysRanges[71] as $class => $range) {
        echo "<tr><td>$class</td><td>{$range['min']} - {$range['max']} kg</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ Age 71 not found in boys lookup table</p>";
    echo "<p>Available ages: " . implode(', ', array_keys($boysRanges)) . "</p>";
}

echo "<h3>Girls Lookup Table - Age 71</h3>";
$girlsRanges = $who->getWeightForAgeGirlsLookupTable();
if (isset($girlsRanges[71])) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Classification</th><th>Range</th></tr>";
    foreach ($girlsRanges[71] as $class => $range) {
        echo "<tr><td>$class</td><td>{$range['min']} - {$range['max']} kg</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ Age 71 not found in girls lookup table</p>";
    echo "<p>Available ages: " . implode(', ', array_keys($girlsRanges)) . "</p>";
}

// 4. Sex Comparison Test
echo "<h2>👤 Sex Comparison Test</h2>";
$sexValues = ['Male', 'male', 'M', 'm', 'MALE', 'Male '];
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Sex Value</th><th>=== 'Male'</th><th>Classification</th><th>Weight Range</th></tr>";
foreach ($sexValues as $testSex) {
    $isMale = ($testSex === 'Male') ? 'TRUE' : 'FALSE';
    $result = $who->calculateWeightForAge($weight, $ageInMonths, $testSex);
    $classification = $result['classification'] ?? 'NULL';
    $weightRange = $result['weight_range'] ?? 'NULL';
    echo "<tr><td>'$testSex'</td><td>$isMale</td><td>$classification</td><td>$weightRange</td></tr>";
}
echo "</table>";

// 5. Weight-for-Age Calculation
echo "<h2>⚖️ Weight-for-Age Calculation</h2>";
$result = $who->calculateWeightForAge($weight, $ageInMonths, $sex);
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Property</th><th>Value</th></tr>";
foreach ($result as $key => $value) {
    echo "<tr><td>$key</td><td>" . (is_array($value) ? json_encode($value) : $value) . "</td></tr>";
}
echo "</table>";

// 6. Manual Classification Check
echo "<h2>✅ Manual Classification Check</h2>";
echo "<p><strong>WHO Standards for 71-month boys:</strong></p>";
echo "<ul>";
echo "<li>Severely Underweight: 0 - 13.4 kg</li>";
echo "<li>Underweight: 13.5 - 15.1 kg</li>";
echo "<li>Normal: 15.2 - 22.6 kg</li>";
echo "<li>Overweight: 22.7+ kg</li>";
echo "</ul>";

$manualResult = '';
if ($weight <= 13.4) {
    $manualResult = 'Severely Underweight';
} elseif ($weight >= 13.5 && $weight <= 15.1) {
    $manualResult = 'Underweight';
} elseif ($weight >= 15.2 && $weight <= 22.6) {
    $manualResult = 'Normal';
} elseif ($weight >= 22.7) {
    $manualResult = 'Overweight';
} else {
    $manualResult = 'Unknown range';
}

echo "<p><strong>Manual Result:</strong> $manualResult</p>";
echo "<p><strong>Weight $weight kg should be:</strong> " . ($weight >= 22.7 ? "✅ Overweight (≥ 22.7 kg)" : "❌ Not overweight") . "</p>";

// 7. Comprehensive Assessment
echo "<h2>🔬 Comprehensive Assessment</h2>";
$assessment = $who->getComprehensiveAssessment($weight, $height, $birthDate, $sex, $screeningDate);
if ($assessment['success']) {
    $results = $assessment['results'];
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Standard</th><th>Classification</th><th>Z-Score</th></tr>";
    echo "<tr><td>Weight-for-Age</td><td>" . ($results['weight_for_age']['classification'] ?? 'NULL') . "</td><td>" . ($results['weight_for_age']['z_score'] ?? 'NULL') . "</td></tr>";
    echo "<tr><td>Height-for-Age</td><td>" . ($results['height_for_age']['classification'] ?? 'NULL') . "</td><td>" . ($results['height_for_age']['z_score'] ?? 'NULL') . "</td></tr>";
    echo "<tr><td>Weight-for-Height</td><td>" . ($results['weight_for_height']['classification'] ?? 'NULL') . "</td><td>" . ($results['weight_for_height']['z_score'] ?? 'NULL') . "</td></tr>";
    echo "<tr><td>BMI-for-Age</td><td>" . ($results['bmi_for_age']['classification'] ?? 'NULL') . "</td><td>" . ($results['bmi_for_age']['z_score'] ?? 'NULL') . "</td></tr>";
    echo "</table>";
    echo "<p><strong>Age in Months:</strong> " . ($results['age_months'] ?? 'NULL') . "</p>";
    echo "<p><strong>BMI:</strong> " . ($results['bmi'] ?? 'NULL') . "</p>";
} else {
    echo "<p>❌ Assessment failed: " . ($assessment['error'] ?? 'Unknown error') . "</p>";
}

// 8. Database User Data (if available)
echo "<h2>🗄️ Database User Data</h2>";
try {
    require_once '../config.php';
    $pdo = getDatabaseConnection();
    
    $stmt = $pdo->prepare("SELECT * FROM community_users WHERE email = ?");
    $stmt->execute(['over71@test.com']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<p><strong>User found in database:</strong></p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        foreach ($user as $key => $value) {
            echo "<tr><td>$key</td><td>$value</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ User not found in database</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

// 9. Summary
echo "<h2>📝 Summary</h2>";
echo "<div style='background: #f0f0f0; padding: 15px; border-radius: 5px;'>";
echo "<p><strong>Issue:</strong> 71-month boy with 24.1kg is classified as 'Normal' instead of 'Overweight'</p>";
echo "<p><strong>Expected:</strong> Overweight (weight ≥ 22.7kg for boys)</p>";
echo "<p><strong>Actual:</strong> " . ($result['classification'] ?? 'NULL') . " (using range: " . ($result['weight_range'] ?? 'NULL') . ")</p>";

if (isset($result['weight_range']) && strpos($result['weight_range'], '15.7-26.8') !== false) {
    echo "<p><strong>🚨 PROBLEM IDENTIFIED:</strong> System is using GIRLS' lookup table for a MALE child!</p>";
    echo "<p>Range 15.7-26.8kg is from girls' standards, not boys' standards (15.2-22.6kg)</p>";
} else {
    echo "<p><strong>Status:</strong> Need to investigate further</p>";
}
echo "</div>";

echo "<h2>🔧 Next Steps</h2>";
echo "<p>1. Check if the issue is in the lookup table selection logic</p>";
echo "<p>2. Verify the sex comparison is working correctly</p>";
echo "<p>3. Fix the bug and test again</p>";
?>
