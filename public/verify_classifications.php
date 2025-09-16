<?php
require_once '../config.php';
require_once '../public/api/who_growth_standards.php';

// Get community_users data
$pdo = new PDO($dsn, $username, $password, $options);
$stmt = $pdo->query("SELECT * FROM community_users ORDER BY email LIMIT 10");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Classification Verification: Screening.php vs Dash.php</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Email</th><th>Age</th><th>Weight</th><th>Height</th><th>Sex</th><th>Screening Date</th><th>Method</th><th>Weight-for-Age</th><th>Height-for-Age</th><th>Weight-for-Height</th></tr>";

foreach ($users as $user) {
    // Method 1: Screening.php method (using screening_date)
    $birthDate = new DateTime($user['birthday']);
    $screeningDate = new DateTime($user['screening_date'] ?? date('Y-m-d H:i:s'));
    $age = $birthDate->diff($screeningDate);
    $ageInMonthsScreening = ($age->y * 12) + $age->m;
    
    $who1 = new WHOGrowthStandards();
    $assessment1 = $who1->getComprehensiveAssessment(
        floatval($user['weight']), 
        floatval($user['height']), 
        $user['birthday'], 
        $user['sex'],
        $user['screening_date'] ?? null
    );
    
    // Method 2: Dash.php method (using current date)
    $birthDate2 = new DateTime($user['birthday']);
    $today = new DateTime();
    $age2 = $today->diff($birthDate2);
    $ageInMonthsDash = ($age2->y * 12) + $age2->m;
    
    $who2 = new WHOGrowthStandards();
    $assessment2 = $who2->getComprehensiveAssessment(
        floatval($user['weight']), 
        floatval($user['height']), 
        $user['birthday'], 
        $user['sex']
    );
    
    // Display results
    echo "<tr>";
    echo "<td>" . htmlspecialchars($user['email']) . "</td>";
    echo "<td>" . $ageInMonthsScreening . "mo (screening)<br>" . $ageInMonthsDash . "mo (dash)</td>";
    echo "<td>" . $user['weight'] . "kg</td>";
    echo "<td>" . $user['height'] . "cm</td>";
    echo "<td>" . $user['sex'] . "</td>";
    echo "<td>" . $user['screening_date'] . "</td>";
    
    // Screening method results
    echo "<td><strong>Screening.php</strong></td>";
    if ($assessment1['success']) {
        $wfa1 = $assessment1['growth_standards']['weight_for_age']['classification'] ?? 'N/A';
        $hfa1 = $assessment1['growth_standards']['height_for_age']['classification'] ?? 'N/A';
        $wfh1 = $assessment1['growth_standards']['weight_for_height']['classification'] ?? 'N/A';
        echo "<td>" . $wfa1 . "</td>";
        echo "<td>" . $hfa1 . "</td>";
        echo "<td>" . $wfh1 . "</td>";
    } else {
        echo "<td colspan='3'>Error: " . ($assessment1['error'] ?? 'Unknown') . "</td>";
    }
    
    echo "</tr><tr>";
    echo "<td colspan='6'></td>";
    
    // Dash method results
    echo "<td><strong>Dash.php</strong></td>";
    if ($assessment2['success']) {
        $wfa2 = $assessment2['growth_standards']['weight_for_age']['classification'] ?? 'N/A';
        $hfa2 = $assessment2['growth_standards']['height_for_age']['classification'] ?? 'N/A';
        $wfh2 = $assessment2['growth_standards']['weight_for_height']['classification'] ?? 'N/A';
        echo "<td>" . $wfa2 . "</td>";
        echo "<td>" . $hfa2 . "</td>";
        echo "<td>" . $wfh2 . "</td>";
    } else {
        echo "<td colspan='3'>Error: " . ($assessment2['error'] ?? 'Unknown') . "</td>";
    }
    
    echo "</tr>";
    echo "<tr><td colspan='10' style='border-top: 2px solid #ccc;'></td></tr>";
}

echo "</table>";

// Summary
echo "<h3>Key Differences Found:</h3>";
echo "<ul>";
echo "<li><strong>Age Calculation:</strong> Screening uses screening_date, Dash uses current date</li>";
echo "<li><strong>WHO Assessment:</strong> Screening passes screening_date, Dash doesn't</li>";
echo "<li><strong>Result:</strong> Different ages lead to different WHO classifications</li>";
echo "</ul>";

echo "<h3>Recommendation:</h3>";
echo "<p>Dash.php should use the same age calculation method as screening.php for consistency.</p>";
?>
