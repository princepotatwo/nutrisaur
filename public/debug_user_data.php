<?php
require_once '../config.php';

// Get user data for over71@test.com
try {
    $pdo = getDatabaseConnection();
    
    echo "<h1>Debug User Data: over71@test.com</h1>";
    
    $stmt = $pdo->prepare("SELECT * FROM community_users WHERE email = ?");
    $stmt->execute(['over71@test.com']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<h2>Database Data</h2>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        foreach ($user as $key => $value) {
            echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
        }
        echo "</table>";
        
        echo "<h2>Relevant Fields</h2>";
        echo "<p><strong>Email:</strong> " . ($user['email'] ?? 'NULL') . "</p>";
        echo "<p><strong>Name:</strong> " . ($user['name'] ?? 'NULL') . "</p>";
        echo "<p><strong>Birthday:</strong> " . ($user['birthday'] ?? 'NULL') . "</p>";
        echo "<p><strong>Weight:</strong> " . ($user['weight'] ?? 'NULL') . " kg</p>";
        echo "<p><strong>Height:</strong> " . ($user['height'] ?? 'NULL') . " cm</p>";
        echo "<p><strong>Sex:</strong> " . ($user['sex'] ?? 'NULL') . "</p>";
        echo "<p><strong>Screening Date:</strong> " . ($user['screening_date'] ?? 'NULL') . "</p>";
        
        // Now test with WHO class
        require_once '../who_growth_standards.php';
        $who = new WHOGrowthStandards();
        
        echo "<h2>WHO Classification Test</h2>";
        $assessment = $who->getComprehensiveAssessment(
            floatval($user['weight']),
            floatval($user['height']),
            $user['birthday'],
            $user['sex'],
            $user['screening_date'] ?? null
        );
        
        if ($assessment['success']) {
            $results = $assessment['results'];
            echo "<p><strong>Age in Months:</strong> " . ($results['age_months'] ?? 'NULL') . "</p>";
            echo "<p><strong>BMI:</strong> " . ($results['bmi'] ?? 'NULL') . "</p>";
            echo "<p><strong>Weight-for-Age:</strong> " . ($results['weight_for_age']['classification'] ?? 'NULL') . "</p>";
            echo "<p><strong>Height-for-Age:</strong> " . ($results['height_for_age']['classification'] ?? 'NULL') . "</p>";
            echo "<p><strong>Weight-for-Height:</strong> " . ($results['weight_for_height']['classification'] ?? 'NULL') . "</p>";
            echo "<p><strong>BMI-for-Age:</strong> " . ($results['bmi_for_age']['classification'] ?? 'NULL') . "</p>";
        } else {
            echo "<p><strong>Assessment failed:</strong> " . json_encode($assessment) . "</p>";
        }
        
        // Age calculation
        $ageInMonths = $who->calculateAgeInMonths($user['birthday'], $user['screening_date']);
        echo "<h2>Age Calculation</h2>";
        echo "<p><strong>Calculated Age:</strong> $ageInMonths months</p>";
        
        // Weight-for-Age specific test
        $weightForAge = $who->calculateWeightForAge(floatval($user['weight']), $ageInMonths, $user['sex']);
        echo "<h2>Weight-for-Age Specific Test</h2>";
        echo "<pre>" . print_r($weightForAge, true) . "</pre>";
        
    } else {
        echo "<p>User not found in database.</p>";
    }
    
} catch (Exception $e) {
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
}
?>
