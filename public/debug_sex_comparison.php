<?php
require_once '../who_growth_standards.php';

$who = new WHOGrowthStandards();

echo "<h1>Debug Sex Comparison</h1>";

// Test different sex values
$sexValues = ['Male', 'male', 'M', 'm', 'MALE', 'Male '];

foreach ($sexValues as $sex) {
    echo "<h2>Testing sex: '$sex'</h2>";
    echo "<p>Sex === 'Male': " . ($sex === 'Male' ? 'TRUE' : 'FALSE') . "</p>";
    
    $result = $who->calculateWeightForAge(24.1, 71, $sex);
    echo "<p>Classification: " . ($result['classification'] ?? 'NULL') . "</p>";
    echo "<p>Weight Range: " . ($result['weight_range'] ?? 'NULL') . "</p>";
    echo "<hr>";
}

// Test the actual user data
echo "<h2>Actual User Data Test</h2>";
$result = $who->calculateWeightForAge(24.1, 71, 'Male');
echo "<pre>" . print_r($result, true) . "</pre>";

// Test with comprehensive assessment
echo "<h2>Comprehensive Assessment Test</h2>";
$assessment = $who->getComprehensiveAssessment(24.1, 115.0, '2018-10-15', 'Male', '2024-09-15 10:00:00');
if ($assessment['success']) {
    $results = $assessment['results'];
    echo "<p>Weight-for-Age: " . ($results['weight_for_age']['classification'] ?? 'NULL') . "</p>";
    echo "<p>Weight Range: " . ($results['weight_for_age']['weight_range'] ?? 'NULL') . "</p>";
} else {
    echo "<p>Assessment failed: " . ($assessment['error'] ?? 'Unknown error') . "</p>";
}
?>
