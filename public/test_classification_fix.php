<?php
require_once '../config.php';
require_once '../public/api/who_growth_standards.php';

// Test with a specific user from community_users
$pdo = new PDO($dsn, $username, $password, $options);
$stmt = $pdo->query("SELECT * FROM community_users WHERE email = 'severe8@test.com' LIMIT 1");
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "<h2>Classification Fix Test for: " . $user['email'] . "</h2>";
    
    // Test both methods
    $birthDate = new DateTime($user['birthday']);
    $screeningDate = new DateTime($user['screening_date']);
    $today = new DateTime();
    
    $ageScreening = $birthDate->diff($screeningDate);
    $ageToday = $birthDate->diff($today);
    
    $ageInMonthsScreening = ($ageScreening->y * 12) + $ageScreening->m;
    $ageInMonthsToday = ($ageToday->y * 12) + $ageToday->m;
    
    echo "<p><strong>User Data:</strong></p>";
    echo "<ul>";
    echo "<li>Birthday: " . $user['birthday'] . "</li>";
    echo "<li>Screening Date: " . $user['screening_date'] . "</li>";
    echo "<li>Weight: " . $user['weight'] . " kg</li>";
    echo "<li>Height: " . $user['height'] . " cm</li>";
    echo "<li>Sex: " . $user['sex'] . "</li>";
    echo "</ul>";
    
    echo "<p><strong>Age Calculations:</strong></p>";
    echo "<ul>";
    echo "<li>Age at screening: " . $ageInMonthsScreening . " months</li>";
    echo "<li>Age today: " . $ageInMonthsToday . " months</li>";
    echo "<li>Difference: " . ($ageInMonthsToday - $ageInMonthsScreening) . " months</li>";
    echo "</ul>";
    
    // Test WHO classifications with both methods
    $who = new WHOGrowthStandards();
    
    // Method 1: Using screening date (CORRECT)
    $assessment1 = $who->getComprehensiveAssessment(
        floatval($user['weight']), 
        floatval($user['height']), 
        $user['birthday'], 
        $user['sex'],
        $user['screening_date']
    );
    
    // Method 2: Using current date (OLD METHOD)
    $assessment2 = $who->getComprehensiveAssessment(
        floatval($user['weight']), 
        floatval($user['height']), 
        $user['birthday'], 
        $user['sex']
    );
    
    echo "<p><strong>WHO Classifications:</strong></p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Method</th><th>Weight-for-Age</th><th>Height-for-Age</th><th>Weight-for-Height</th></tr>";
    
    if ($assessment1['success']) {
        $wfa1 = $assessment1['growth_standards']['weight_for_age']['classification'] ?? 'N/A';
        $hfa1 = $assessment1['growth_standards']['height_for_age']['classification'] ?? 'N/A';
        $wfh1 = $assessment1['growth_standards']['weight_for_height']['classification'] ?? 'N/A';
        echo "<tr><td><strong>Screening Date (CORRECT)</strong></td><td>" . $wfa1 . "</td><td>" . $hfa1 . "</td><td>" . $wfh1 . "</td></tr>";
    }
    
    if ($assessment2['success']) {
        $wfa2 = $assessment2['growth_standards']['weight_for_age']['classification'] ?? 'N/A';
        $hfa2 = $assessment2['growth_standards']['height_for_age']['classification'] ?? 'N/A';
        $wfh2 = $assessment2['growth_standards']['weight_for_height']['classification'] ?? 'N/A';
        echo "<tr><td><strong>Current Date (OLD)</strong></td><td>" . $wfa2 . "</td><td>" . $hfa2 . "</td><td>" . $wfh2 . "</td></tr>";
    }
    
    echo "</table>";
    
    echo "<p><strong>Result:</strong> " . ($ageInMonthsScreening === $ageInMonthsToday ? "No difference (screening was recent)" : "Different ages = Different classifications") . "</p>";
    
} else {
    echo "<p>No test user found. Please import the test data first.</p>";
}
?>
