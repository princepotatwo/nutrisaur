<?php
require_once 'who_growth_standards.php';

echo "<h2>Testing Fixed WHO Growth Standards Calculations</h2>";

$who = new WHOGrowthStandards();

// Test cases from the CSV file
$testCases = [
    // Boys Weight-for-Age tests
    ['name' => 'Boy WFA SU 0mo', 'weight' => 2.0, 'height' => 50, 'birthday' => '2024-09-15', 'sex' => 'Male'],
    ['name' => 'Boy WFA UW 0mo', 'weight' => 2.3, 'height' => 50, 'birthday' => '2024-09-15', 'sex' => 'Male'],
    ['name' => 'Boy WFA N 0mo', 'weight' => 3.5, 'height' => 50, 'birthday' => '2024-09-15', 'sex' => 'Male'],
    ['name' => 'Boy WFA OW 0mo', 'weight' => 4.6, 'height' => 50, 'birthday' => '2024-09-15', 'sex' => 'Male'],
    
    ['name' => 'Boy WFA SU 12mo', 'weight' => 6.8, 'height' => 75, 'birthday' => '2023-09-15', 'sex' => 'Male'],
    ['name' => 'Boy WFA UW 12mo', 'weight' => 7.3, 'height' => 75, 'birthday' => '2023-09-15', 'sex' => 'Male'],
    ['name' => 'Boy WFA N 12mo', 'weight' => 9.0, 'height' => 75, 'birthday' => '2023-09-15', 'sex' => 'Male'],
    ['name' => 'Boy WFA OW 12mo', 'weight' => 12.5, 'height' => 75, 'birthday' => '2023-09-15', 'sex' => 'Male'],
    
    // Girls Weight-for-Height tests
    ['name' => 'Girl WFH SW 65cm', 'weight' => 5.4, 'height' => 65, 'birthday' => '2022-09-15', 'sex' => 'Female'],
    ['name' => 'Girl WFH W 65cm', 'weight' => 5.8, 'height' => 65, 'birthday' => '2022-09-15', 'sex' => 'Female'],
    ['name' => 'Girl WFH N 65cm', 'weight' => 7.0, 'height' => 65, 'birthday' => '2022-09-15', 'sex' => 'Female'],
    ['name' => 'Girl WFH OW 65cm', 'weight' => 9.0, 'height' => 65, 'birthday' => '2022-09-15', 'sex' => 'Female'],
    ['name' => 'Girl WFH O 65cm', 'weight' => 10.0, 'height' => 65, 'birthday' => '2022-09-15', 'sex' => 'Female'],
];

foreach ($testCases as $test) {
    echo "<h3>{$test['name']}</h3>";
    echo "<p>Weight: {$test['weight']} kg, Height: {$test['height']} cm, Birthday: {$test['birthday']}, Sex: {$test['sex']}</p>";
    
    try {
        $assessment = $who->getComprehensiveAssessment(
            $test['weight'],
            $test['height'],
            $test['birthday'],
            $test['sex']
        );
        
        if ($assessment['success']) {
            $results = $assessment['results'];
            
            // Calculate age for display
            $birthDate = new DateTime($test['birthday']);
            $today = new DateTime();
            $age = $today->diff($birthDate);
            $ageInMonths = ($age->y * 12) + $age->m;
            $ageDisplay = $age->y . 'y ' . $age->m . 'm';
            
            echo "<p><strong>Age:</strong> {$ageDisplay} ({$ageInMonths} months)</p>";
            
            // Show Weight-for-Age results
            if (isset($results['weight_for_age'])) {
                $wfa = $results['weight_for_age'];
                echo "<p><strong>Weight-for-Age:</strong> Z-score: " . ($wfa['z_score'] ?? 'N/A') . ", Classification: " . ($wfa['classification'] ?? 'N/A') . "</p>";
            }
            
            // Show Weight-for-Height results
            if (isset($results['weight_for_height'])) {
                $wfh = $results['weight_for_height'];
                echo "<p><strong>Weight-for-Height:</strong> Z-score: " . ($wfh['z_score'] ?? 'N/A') . ", Classification: " . ($wfh['classification'] ?? 'N/A') . "</p>";
            }
            
            // Show BMI
            $bmi = round($test['weight'] / pow($test['height'] / 100, 2), 1);
            echo "<p><strong>BMI:</strong> {$bmi}</p>";
            
        } else {
            echo "<p style='color: red;'><strong>Error:</strong> " . ($assessment['error'] ?? 'Unknown error') . "</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'><strong>Exception:</strong> " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
}
?>
