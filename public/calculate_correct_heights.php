<?php
require_once '../public/api/who_growth_standards.php';

echo "<h2>Correct Heights for Height-for-Age Classifications</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Age (months)</th><th>Sex</th><th>Expected Height (cm)</th><th>Severely Stunted</th><th>Stunted</th><th>Normal</th><th>Tall</th></tr>";

$who = new WHOGrowthStandards();

// Test different ages and sexes
$testCases = [
    ['age' => 0, 'sex' => 'Male'],
    ['age' => 6, 'sex' => 'Male'],
    ['age' => 12, 'sex' => 'Male'],
    ['age' => 18, 'sex' => 'Male'],
    ['age' => 24, 'sex' => 'Male'],
    ['age' => 36, 'sex' => 'Male'],
    ['age' => 48, 'sex' => 'Male'],
    ['age' => 60, 'sex' => 'Male'],
];

foreach ($testCases as $case) {
    $age = $case['age'];
    $sex = $case['sex'];
    
    // Get WHO standards for this age/sex
    $standards = ($sex === 'Male') ? $who->getHeightForAgeBoys() : $who->getHeightForAgeGirls();
    
    if (isset($standards[$age])) {
        $median = $standards[$age]['median'];
        $sd = $standards[$age]['sd'];
        
        // Calculate height ranges for different classifications
        $severelyStunted = $median - (3 * $sd);  // < -3 SD
        $stunted = $median - (2 * $sd);          // -3 to -2 SD
        $normal = $median;                       // -2 to +2 SD
        $tall = $median + (2 * $sd);            // > +2 SD
        
        echo "<tr>";
        echo "<td>$age</td>";
        echo "<td>$sex</td>";
        echo "<td><strong>$median</strong></td>";
        echo "<td>&lt; " . round($severelyStunted, 1) . " cm</td>";
        echo "<td>" . round($stunted, 1) . " - " . round($severelyStunted, 1) . " cm</td>";
        echo "<td><strong>" . round($median - (2 * $sd), 1) . " - " . round($median + (2 * $sd), 1) . " cm</strong></td>";
        echo "<td>&gt; " . round($tall, 1) . " cm</td>";
        echo "</tr>";
    }
}

echo "</table>";

echo "<h3>Current Test Data Heights vs Required Heights:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>User</th><th>Age</th><th>Current Height</th><th>Required for Normal</th><th>Status</th></tr>";

$currentData = [
    ['user' => 'severe36@test.com', 'age' => 36, 'current_height' => 96],
    ['user' => 'severe9@test.com', 'age' => 9, 'current_height' => 73],
    ['user' => 'severe8@test.com', 'age' => 8, 'current_height' => 71],
    ['user' => 'severe7@test.com', 'age' => 7, 'current_height' => 69],
    ['user' => 'normal36@test.com', 'age' => 36, 'current_height' => 96],
    ['user' => 'normal9@test.com', 'age' => 9, 'current_height' => 73],
    ['user' => 'over36@test.com', 'age' => 36, 'current_height' => 96],
];

foreach ($currentData as $data) {
    $age = $data['age'];
    $currentHeight = $data['current_height'];
    
    $standards = $who->getHeightForAgeBoys();
    if (isset($standards[$age])) {
        $median = $standards[$age]['median'];
        $sd = $standards[$age]['sd'];
        $normalMin = $median - (2 * $sd);
        $normalMax = $median + (2 * $sd);
        
        $status = ($currentHeight >= $normalMin && $currentHeight <= $normalMax) ? 
            '✅ Normal' : '❌ Too Low';
        
        echo "<tr>";
        echo "<td>" . $data['user'] . "</td>";
        echo "<td>$age months</td>";
        echo "<td>$currentHeight cm</td>";
        echo "<td>$normalMin - $normalMax cm</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
}

echo "</table>";
?>
