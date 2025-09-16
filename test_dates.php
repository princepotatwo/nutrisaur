<?php
echo "<h2>Date and Age Calculation Test</h2>";

$today = new DateTime();
echo "<p><strong>Current Date:</strong> " . $today->format('Y-m-d H:i:s') . "</p>";

// Test different birth dates
$testDates = [
    '2024-09-15', // Should be 0 months if today is 2024-09-15
    '2023-09-15', // Should be 12 months
    '2021-10-15', // Should be 35 months
    '2018-10-15'  // Should be 71 months
];

foreach ($testDates as $date) {
    $birth = new DateTime($date);
    $age = $today->diff($birth);
    $ageInMonths = ($age->y * 12) + $age->m;
    if ($age->d >= 15) {
        $ageInMonths += 1;
    }
    echo "<p>Birth: {$date} -> Age: {$age->y}y {$age->m}m {$age->d}d ({$ageInMonths} months)</p>";
}

// Test with a specific date to see what happens
echo "<h3>Testing with specific screening date (2024-09-15):</h3>";
$screeningDate = new DateTime('2024-09-15');

foreach ($testDates as $date) {
    $birth = new DateTime($date);
    $age = $birth->diff($screeningDate);
    $ageInMonths = ($age->y * 12) + $age->m;
    if ($age->d >= 15) {
        $ageInMonths += 1;
    }
    echo "<p>Birth: {$date} -> Age on 2024-09-15: {$age->y}y {$age->m}m {$age->d}d ({$ageInMonths} months)</p>";
}
?>
