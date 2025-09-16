<?php
require_once '../config.php';
require_once '../public/api/who_growth_standards.php';

// Get community_users data
$pdo = new PDO($dsn, $username, $password, $options);
$stmt = $pdo->query("SELECT * FROM community_users ORDER BY email LIMIT 10");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Weight-for-Age Classification Test</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Email</th><th>Age (mo)</th><th>Weight (kg)</th><th>Height (cm)</th><th>Sex</th><th>Expected Classification</th><th>Actual Classification</th><th>Z-Score</th><th>Status</th></tr>";

$who = new WHOGrowthStandards();

foreach ($users as $user) {
    // Calculate age in months using screening date
    $birthDate = new DateTime($user['birthday']);
    $screeningDate = new DateTime($user['screening_date'] ?? date('Y-m-d H:i:s'));
    $age = $birthDate->diff($screeningDate);
    $ageInMonths = ($age->y * 12) + $age->m;
    
    // Get Weight-for-Age assessment
    $assessment = $who->getComprehensiveAssessment(
        floatval($user['weight']), 
        floatval($user['height']), 
        $user['birthday'], 
        $user['sex'],
        $user['screening_date'] ?? null
    );
    
    // Determine expected classification based on email prefix
    $expectedClassification = 'Unknown';
    if (strpos($user['email'], 'severe') === 0) {
        $expectedClassification = 'Severely Underweight';
    } elseif (strpos($user['email'], 'under') === 0) {
        $expectedClassification = 'Underweight';
    } elseif (strpos($user['email'], 'normal') === 0) {
        $expectedClassification = 'Normal';
    } elseif (strpos($user['email'], 'over') === 0) {
        $expectedClassification = 'Overweight';
    }
    
    // Get actual classification
    $actualClassification = 'Error';
    $zScore = 'N/A';
    if ($assessment['success'] && isset($assessment['growth_standards']['weight_for_age'])) {
        $actualClassification = $assessment['growth_standards']['weight_for_age']['classification'];
        $zScore = $assessment['growth_standards']['weight_for_age']['z_score'];
    }
    
    // Check if classification matches expectation
    $status = ($actualClassification === $expectedClassification) ? '✅ Correct' : '❌ Wrong';
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($user['email']) . "</td>";
    echo "<td>$ageInMonths</td>";
    echo "<td>" . $user['weight'] . "</td>";
    echo "<td>" . $user['height'] . "</td>";
    echo "<td>" . $user['sex'] . "</td>";
    echo "<td><strong>$expectedClassification</strong></td>";
    echo "<td>$actualClassification</td>";
    echo "<td>$zScore</td>";
    echo "<td>$status</td>";
    echo "</tr>";
}

echo "</table>";

// Summary
$correct = 0;
$total = count($users);
foreach ($users as $user) {
    $expectedClassification = 'Unknown';
    if (strpos($user['email'], 'severe') === 0) {
        $expectedClassification = 'Severely Underweight';
    } elseif (strpos($user['email'], 'under') === 0) {
        $expectedClassification = 'Underweight';
    } elseif (strpos($user['email'], 'normal') === 0) {
        $expectedClassification = 'Normal';
    } elseif (strpos($user['email'], 'over') === 0) {
        $expectedClassification = 'Overweight';
    }
    
    $assessment = $who->getComprehensiveAssessment(
        floatval($user['weight']), 
        floatval($user['height']), 
        $user['birthday'], 
        $user['sex'],
        $user['screening_date'] ?? null
    );
    
    $actualClassification = 'Error';
    if ($assessment['success'] && isset($assessment['growth_standards']['weight_for_age'])) {
        $actualClassification = $assessment['growth_standards']['weight_for_age']['classification'];
    }
    
    if ($actualClassification === $expectedClassification) {
        $correct++;
    }
}

echo "<h3>Summary:</h3>";
echo "<p><strong>Accuracy: $correct/$total (" . round(($correct/$total)*100, 1) . "%)</strong></p>";

if ($correct < $total) {
    echo "<p style='color: red;'><strong>ISSUE FOUND:</strong> Weight-for-Age classifications are not matching expected values based on user names.</p>";
} else {
    echo "<p style='color: green;'><strong>SUCCESS:</strong> All Weight-for-Age classifications are correct!</p>";
}
?>
