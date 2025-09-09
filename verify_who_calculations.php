<?php
require_once 'config.php';
require_once 'who_growth_standards.php';

// Get database connection
$pdo = getDatabaseConnection();

echo "<h2>WHO Growth Standards Verification and Fix</h2>\n";

// Get all users from the database
$stmt = $pdo->query("SELECT name, email, weight, height, birthday, sex FROM community_users ORDER BY name");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Current Database Users</h3>\n";
echo "<table border='1' cellpadding='5'>\n";
echo "<tr><th>Name</th><th>Email</th><th>Age</th><th>Weight</th><th>Height</th><th>BMI</th><th>WFA Z-Score</th><th>WFA Classification</th></tr>\n";

$who = new WHOGrowthStandards();

foreach ($users as $user) {
    // Calculate age in months
    $birthDate = new DateTime($user['birthday']);
    $today = new DateTime();
    $age = $today->diff($birthDate);
    $ageInMonths = ($age->y * 12) + $age->m;
    $ageDisplay = $age->y . 'y ' . $age->m . 'm';
    
    // Calculate BMI
    $bmi = round($user['weight'] / pow($user['height'] / 100, 2), 1);
    
    // Get WHO Growth Standards calculation
    $wfaResult = $who->calculateWeightForAge($user['weight'], $ageInMonths, $user['sex']);
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($user['name']) . "</td>";
    echo "<td>" . htmlspecialchars($user['email']) . "</td>";
    echo "<td>" . $ageDisplay . " (" . $ageInMonths . "m)</td>";
    echo "<td>" . $user['weight'] . " kg</td>";
    echo "<td>" . $user['height'] . " cm</td>";
    echo "<td>" . $bmi . "</td>";
    echo "<td>" . ($wfaResult['z_score'] !== null ? number_format($wfaResult['z_score'], 2) : 'N/A') . "</td>";
    echo "<td>" . $wfaResult['classification'] . "</td>";
    echo "</tr>\n";
}

echo "</table>\n";

// Now update the database with correct WHO Growth Standards calculations
echo "<h3>Updating Database with Correct WHO Growth Standards</h3>\n";

$updateCount = 0;
foreach ($users as $user) {
    try {
        // Calculate age in months
        $birthDate = new DateTime($user['birthday']);
        $today = new DateTime();
        $age = $today->diff($birthDate);
        $ageInMonths = ($age->y * 12) + $age->m;
        
        // Get comprehensive WHO Growth Standards assessment
        $assessment = $who->getComprehensiveAssessment(
            floatval($user['weight']),
            floatval($user['height']),
            $user['birthday'],
            $user['sex']
        );
        
        if ($assessment['success']) {
            $results = $assessment['results'];
            
            // Update the database with correct WHO Growth Standards data
            $updateStmt = $pdo->prepare("
                UPDATE community_users SET 
                    `bmi-for-age` = :bmi_for_age_z,
                    `weight-for-height` = :weight_for_height_z,
                    `weight-for-age` = :weight_for_age_z,
                    `weight-for-length` = :weight_for_length_z,
                    `height-for-age` = :height_for_age_z,
                    bmi = :bmi,
                    bmi_category = :bmi_category
                WHERE email = :email
            ");
            
            $updateStmt->execute([
                ':bmi_for_age_z' => $results['bmi_for_age']['z_score'],
                ':weight_for_height_z' => $results['weight_for_height']['z_score'],
                ':weight_for_age_z' => $results['weight_for_age']['z_score'],
                ':weight_for_length_z' => $results['weight_for_length']['z_score'],
                ':height_for_age_z' => $results['height_for_age']['z_score'],
                ':bmi' => $results['bmi'],
                ':bmi_category' => $results['bmi_for_age']['classification'],
                ':email' => $user['email']
            ]);
            
            $updateCount++;
            echo "<p>✓ Updated " . htmlspecialchars($user['name']) . " - WFA Z: " . number_format($results['weight_for_age']['z_score'], 2) . " (" . $results['weight_for_age']['classification'] . ")</p>\n";
        } else {
            echo "<p>✗ Failed to update " . htmlspecialchars($user['name']) . " - " . $assessment['error'] . "</p>\n";
        }
    } catch (Exception $e) {
        echo "<p>✗ Error updating " . htmlspecialchars($user['name']) . " - " . $e->getMessage() . "</p>\n";
    }
}

echo "<h3>Update Complete</h3>\n";
echo "<p>Updated " . $updateCount . " users with correct WHO Growth Standards calculations.</p>\n";
echo "<p><a href='public/screening.php'>View Updated Screening Table</a></p>\n";
?>
