<?php
require_once 'who_growth_standards.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>WHO Growth Standards Verification - All 4 Tables</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .table-container { margin: 30px 0; }
        .table-title { background: #2c3e50; color: white; padding: 15px; font-size: 18px; font-weight: bold; }
        .table-content { border: 1px solid #ddd; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background: #f8f9fa; font-weight: bold; }
        .severely-underweight { background: #ffebee; }
        .underweight { background: #fff3e0; }
        .normal { background: #e8f5e8; }
        .overweight { background: #fff8e1; }
        .obese { background: #fce4ec; }
        .severely-wasted { background: #ffebee; }
        .wasted { background: #fff3e0; }
        .section { margin: 40px 0; }
        .section h2 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
    </style>
</head>
<body>";

echo "<h1>WHO Growth Standards Verification - All 4 Tables</h1>";
echo "<p><strong>This page shows the complete decision tree logic for all 4 WHO standards based on your provided images.</strong></p>";

$who = new WHOGrowthStandards();

// 1. WEIGHT-FOR-AGE BOYS (0-71 months)
echo "<div class='section'>";
echo "<h2>1. Weight-for-Age Boys (0-71 months)</h2>";
echo "<p>Classification: Severely Underweight, Underweight, Normal, Overweight</p>";

$boysLookup = $who->getWeightForAgeBoysLookupTable();
echo "<div class='table-container'>";
echo "<div class='table-title'>Weight-for-Age Boys Decision Tree</div>";
echo "<div class='table-content'>";
echo "<table>";
echo "<tr><th>Age (months)</th><th>Severely Underweight (≤kg)</th><th>Underweight (kg)</th><th>Normal (kg)</th><th>Overweight (≥kg)</th></tr>";

foreach ($boysLookup as $age => $ranges) {
    $severelyMax = $ranges['severely_underweight']['max'];
    $underweightMin = $ranges['underweight']['min'];
    $underweightMax = $ranges['underweight']['max'];
    $normalMin = $ranges['normal']['min'];
    $normalMax = $ranges['normal']['max'];
    $overweightMin = $ranges['overweight']['min'];
    
    echo "<tr>";
    echo "<td><strong>$age</strong></td>";
    echo "<td class='severely-underweight'>≤ $severelyMax</td>";
    echo "<td class='underweight'>$underweightMin - $underweightMax</td>";
    echo "<td class='normal'>$normalMin - $normalMax</td>";
    echo "<td class='overweight'>≥ $overweightMin</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div></div>";
echo "</div>";

// 2. WEIGHT-FOR-AGE GIRLS (0-71 months)
echo "<div class='section'>";
echo "<h2>2. Weight-for-Age Girls (0-71 months)</h2>";
echo "<p>Classification: Severely Underweight, Underweight, Normal, Overweight</p>";

$girlsLookup = $who->getWeightForAgeGirlsLookupTable();
echo "<div class='table-container'>";
echo "<div class='table-title'>Weight-for-Age Girls Decision Tree</div>";
echo "<div class='table-content'>";
echo "<table>";
echo "<tr><th>Age (months)</th><th>Severely Underweight (≤kg)</th><th>Underweight (kg)</th><th>Normal (kg)</th><th>Overweight (≥kg)</th></tr>";

foreach ($girlsLookup as $age => $ranges) {
    $severelyMax = $ranges['severely_underweight']['max'];
    $underweightMin = $ranges['underweight']['min'];
    $underweightMax = $ranges['underweight']['max'];
    $normalMin = $ranges['normal']['min'];
    $normalMax = $ranges['normal']['max'];
    $overweightMin = $ranges['overweight']['min'];
    
    echo "<tr>";
    echo "<td><strong>$age</strong></td>";
    echo "<td class='severely-underweight'>≤ $severelyMax</td>";
    echo "<td class='underweight'>$underweightMin - $underweightMax</td>";
    echo "<td class='normal'>$normalMin - $normalMax</td>";
    echo "<td class='overweight'>≥ $overweightMin</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div></div>";
echo "</div>";

// 3. WEIGHT-FOR-HEIGHT BOYS (24-60 months)
echo "<div class='section'>";
echo "<h2>3. Weight-for-Height Boys (24-60 months)</h2>";
echo "<p>Classification: Severely Wasted, Wasted, Normal, Overweight, Obese</p>";

$boysHeightLookup = $who->getWeightForHeightBoysLookup();
echo "<div class='table-container'>";
echo "<div class='table-title'>Weight-for-Height Boys Decision Tree</div>";
echo "<div class='table-content'>";
echo "<table>";
echo "<tr><th>Height (cm)</th><th>Severely Wasted (≤kg)</th><th>Wasted (kg)</th><th>Normal (kg)</th><th>Overweight (kg)</th><th>Obese (≥kg)</th></tr>";

foreach ($boysHeightLookup as $height => $ranges) {
    $severelyMax = $ranges['severely_wasted']['max'];
    $wastedMin = $ranges['wasted']['min'];
    $wastedMax = $ranges['wasted']['max'];
    $normalMin = $ranges['normal']['min'];
    $normalMax = $ranges['normal']['max'];
    $overweightMin = $ranges['overweight']['min'];
    $overweightMax = $ranges['overweight']['max'];
    $obeseMin = $ranges['obese']['min'];
    
    echo "<tr>";
    echo "<td><strong>$height</strong></td>";
    echo "<td class='severely-wasted'>≤ $severelyMax</td>";
    echo "<td class='wasted'>$wastedMin - $wastedMax</td>";
    echo "<td class='normal'>$normalMin - $normalMax</td>";
    echo "<td class='overweight'>$overweightMin - $overweightMax</td>";
    echo "<td class='obese'>≥ $obeseMin</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div></div>";
echo "</div>";

// 4. WEIGHT-FOR-HEIGHT GIRLS (24-60 months)
echo "<div class='section'>";
echo "<h2>4. Weight-for-Height Girls (24-60 months)</h2>";
echo "<p>Classification: Severely Wasted, Wasted, Normal, Overweight, Obese</p>";

$girlsHeightLookup = $who->getWeightForHeightGirlsLookup();
echo "<div class='table-container'>";
echo "<div class='table-title'>Weight-for-Height Girls Decision Tree</div>";
echo "<div class='table-content'>";
echo "<table>";
echo "<tr><th>Height (cm)</th><th>Severely Wasted (≤kg)</th><th>Wasted (kg)</th><th>Normal (kg)</th><th>Overweight (kg)</th><th>Obese (≥kg)</th></tr>";

foreach ($girlsHeightLookup as $height => $ranges) {
    $severelyMax = $ranges['severely_wasted']['max'];
    $wastedMin = $ranges['wasted']['min'];
    $wastedMax = $ranges['wasted']['max'];
    $normalMin = $ranges['normal']['min'];
    $normalMax = $ranges['normal']['max'];
    $overweightMin = $ranges['overweight']['min'];
    $overweightMax = $ranges['overweight']['max'];
    $obeseMin = $ranges['obese']['min'];
    
    echo "<tr>";
    echo "<td><strong>$height</strong></td>";
    echo "<td class='severely-wasted'>≤ $severelyMax</td>";
    echo "<td class='wasted'>$wastedMin - $wastedMax</td>";
    echo "<td class='normal'>$normalMin - $normalMax</td>";
    echo "<td class='overweight'>$overweightMin - $overweightMax</td>";
    echo "<td class='obese'>≥ $obeseMin</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div></div>";
echo "</div>";

// TEST CASES VERIFICATION
echo "<div class='section'>";
echo "<h2>5. Test Cases Verification</h2>";
echo "<p>Testing specific cases to verify accuracy against your images:</p>";

$testCases = [
    // Weight-for-Age Boys
    ['type' => 'Weight-for-Age Boys', 'age' => 0, 'weight' => 2.1, 'sex' => 'Male', 'expected' => 'Severely Underweight'],
    ['type' => 'Weight-for-Age Boys', 'age' => 0, 'weight' => 2.3, 'sex' => 'Male', 'expected' => 'Underweight'],
    ['type' => 'Weight-for-Age Boys', 'age' => 0, 'weight' => 3.5, 'sex' => 'Male', 'expected' => 'Normal'],
    ['type' => 'Weight-for-Age Boys', 'age' => 0, 'weight' => 4.5, 'sex' => 'Male', 'expected' => 'Overweight'],
    
    // Weight-for-Age Girls
    ['type' => 'Weight-for-Age Girls', 'age' => 0, 'weight' => 2.0, 'sex' => 'Female', 'expected' => 'Severely Underweight'],
    ['type' => 'Weight-for-Age Girls', 'age' => 0, 'weight' => 2.3, 'sex' => 'Female', 'expected' => 'Underweight'],
    ['type' => 'Weight-for-Age Girls', 'age' => 0, 'weight' => 3.2, 'sex' => 'Female', 'expected' => 'Normal'],
    ['type' => 'Weight-for-Age Girls', 'age' => 0, 'weight' => 4.2, 'sex' => 'Female', 'expected' => 'Overweight'],
    
    // Weight-for-Height Boys
    ['type' => 'Weight-for-Height Boys', 'age' => 24, 'weight' => 5.7, 'height' => 65, 'sex' => 'Male', 'expected' => 'Severely Wasted'],
    ['type' => 'Weight-for-Height Boys', 'age' => 24, 'weight' => 6.0, 'height' => 65, 'sex' => 'Male', 'expected' => 'Wasted'],
    ['type' => 'Weight-for-Height Boys', 'age' => 24, 'weight' => 7.5, 'height' => 65, 'sex' => 'Male', 'expected' => 'Normal'],
    ['type' => 'Weight-for-Height Boys', 'age' => 24, 'weight' => 9.2, 'height' => 65, 'sex' => 'Male', 'expected' => 'Overweight'],
    ['type' => 'Weight-for-Height Boys', 'age' => 24, 'weight' => 9.8, 'height' => 65, 'sex' => 'Male', 'expected' => 'Obese'],
    
    // Weight-for-Height Girls
    ['type' => 'Weight-for-Height Girls', 'age' => 24, 'weight' => 5.4, 'height' => 65, 'sex' => 'Female', 'expected' => 'Severely Wasted'],
    ['type' => 'Weight-for-Height Girls', 'age' => 24, 'weight' => 5.7, 'height' => 65, 'sex' => 'Female', 'expected' => 'Wasted'],
    ['type' => 'Weight-for-Height Girls', 'age' => 24, 'weight' => 7.2, 'height' => 65, 'sex' => 'Female', 'expected' => 'Normal'],
    ['type' => 'Weight-for-Height Girls', 'age' => 24, 'weight' => 9.2, 'height' => 65, 'sex' => 'Female', 'expected' => 'Overweight'],
    ['type' => 'Weight-for-Height Girls', 'age' => 24, 'weight' => 9.8, 'height' => 65, 'sex' => 'Female', 'expected' => 'Obese'],
];

echo "<div class='table-container'>";
echo "<div class='table-title'>Test Cases Results</div>";
echo "<div class='table-content'>";
echo "<table>";
echo "<tr><th>Test Case</th><th>Input</th><th>Expected</th><th>Actual</th><th>Status</th></tr>";

foreach ($testCases as $i => $test) {
    $birthDate = date('Y-m-d', strtotime('-' . $test['age'] . ' months'));
    $screeningDate = date('Y-m-d');
    
    if (isset($test['height'])) {
        // Weight-for-Height test
        $result = $who->calculateWeightForHeight($test['weight'], $test['height'], $test['sex']);
        $actual = $result['classification'];
    } else {
        // Weight-for-Age test
        $result = $who->calculateWeightForAge($test['weight'], $test['age'], $test['sex'], $birthDate, $screeningDate);
        $actual = $result['classification'];
    }
    
    $status = ($actual === $test['expected']) ? '✅ PASS' : '❌ FAIL';
    $statusClass = ($actual === $test['expected']) ? 'normal' : 'obese';
    
    $input = isset($test['height']) 
        ? "Age: {$test['age']}mo, Weight: {$test['weight']}kg, Height: {$test['height']}cm, Sex: {$test['sex']}"
        : "Age: {$test['age']}mo, Weight: {$test['weight']}kg, Sex: {$test['sex']}";
    
    echo "<tr>";
    echo "<td><strong>Test " . ($i + 1) . "</strong></td>";
    echo "<td>$input</td>";
    echo "<td>{$test['expected']}</td>";
    echo "<td>$actual</td>";
    echo "<td class='$statusClass'>$status</td>";
    echo "</tr>";
}

echo "</table>";
echo "</div></div>";
echo "</div>";

echo "<div class='section'>";
echo "<h2>6. Summary</h2>";
echo "<p><strong>All 4 WHO Growth Standards are now implemented with hardcoded lookup tables based on your provided images:</strong></p>";
echo "<ul>";
echo "<li>✅ <strong>Weight-for-Age Boys (0-71 months)</strong> - 4 categories: Severely Underweight, Underweight, Normal, Overweight</li>";
echo "<li>✅ <strong>Weight-for-Age Girls (0-71 months)</strong> - 4 categories: Severely Underweight, Underweight, Normal, Overweight</li>";
echo "<li>✅ <strong>Weight-for-Height Boys (24-60 months)</strong> - 5 categories: Severely Wasted, Wasted, Normal, Overweight, Obese</li>";
echo "<li>✅ <strong>Weight-for-Height Girls (24-60 months)</strong> - 5 categories: Severely Wasted, Wasted, Normal, Overweight, Obese</li>";
echo "</ul>";
echo "<p><strong>All decision trees use exact values from WHO official tables, not formula-based calculations.</strong></p>";
echo "</div>";

echo "</body></html>";
?>
