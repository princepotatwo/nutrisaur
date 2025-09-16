<?php
require_once '../config.php';
require_once '../public/api/who_growth_standards.php';

echo "<h2>Weight-for-Age Classification Test</h2>";

// Test specific users
$testUsers = [
    ['email' => 'severe8@test.com', 'expected' => 'Severely Underweight'],
    ['email' => 'under8@test.com', 'expected' => 'Underweight'],
    ['email' => 'normal8@test.com', 'expected' => 'Normal'],
    ['email' => 'over8@test.com', 'expected' => 'Overweight'],
];

$pdo = new PDO($dsn, $username, $password, $options);
$who = new WHOGrowthStandards();

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Email</th><th>Age</th><th>Weight</th><th>Expected</th><th>Actual</th><th>Z-Score</th><th>Status</th></tr>";

foreach ($testUsers as $testUser) {
    $stmt = $pdo->prepare("SELECT * FROM community_users WHERE email = ?");
    $stmt->execute([$testUser['email']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Calculate age
        $birthDate = new DateTime($user['birthday']);
        $screeningDate = new DateTime($user['screening_date']);
        $age = $birthDate->diff($screeningDate);
        $ageInMonths = ($age->y * 12) + $age->m;
        
        // Get Weight-for-Age assessment
        $assessment = $who->getComprehensiveAssessment(
            floatval($user['weight']), 
            floatval($user['height']), 
            $user['birthday'], 
            $user['sex'],
            $user['screening_date']
        );
        
        $actualClassification = 'Error';
        $zScore = 'N/A';
        if ($assessment['success'] && isset($assessment['growth_standards']['weight_for_age'])) {
            $actualClassification = $assessment['growth_standards']['weight_for_age']['classification'];
            $zScore = $assessment['growth_standards']['weight_for_age']['z_score'];
        }
        
        $status = ($actualClassification === $testUser['expected']) ? '✅' : '❌';
        
        echo "<tr>";
        echo "<td>" . $user['email'] . "</td>";
        echo "<td>$ageInMonths mo</td>";
        echo "<td>" . $user['weight'] . " kg</td>";
        echo "<td><strong>" . $testUser['expected'] . "</strong></td>";
        echo "<td>$actualClassification</td>";
        echo "<td>$zScore</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
}

echo "</table>";

echo "<h3>Key Points:</h3>";
echo "<ul>";
echo "<li><strong>severe8@test.com</strong> should be 'Severely Underweight' (z-score < -3)</li>";
echo "<li><strong>under8@test.com</strong> should be 'Underweight' (z-score -3 to -2)</li>";
echo "<li><strong>normal8@test.com</strong> should be 'Normal' (z-score -2 to +2)</li>";
echo "<li><strong>over8@test.com</strong> should be 'Overweight' (z-score > +2)</li>";
echo "</ul>";
?>
