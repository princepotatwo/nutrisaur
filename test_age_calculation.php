<?php
echo "<h2>Age Calculation Test</h2>";

// Test the age calculation logic
$birthDate = new DateTime('2024-09-15');
$today = new DateTime();
$age = $today->diff($birthDate);

echo "<p>Birth Date: " . $birthDate->format('Y-m-d') . "</p>";
echo "<p>Today: " . $today->format('Y-m-d') . "</p>";
echo "<p>Age: {$age->y} years, {$age->m} months, {$age->d} days</p>";

$ageInMonths = ($age->y * 12) + $age->m;
if ($age->d >= 15) {
    $ageInMonths += 1;
}

echo "<p>Age in months: {$ageInMonths}</p>";
echo "<p>Age display: {$age->y}y {$age->m}m</p>";

// Test with different birth dates
$testDates = [
    '2024-09-15', // 0 months
    '2023-09-15', // 12 months  
    '2021-10-15', // 35 months
    '2018-10-15'  // 71 months
];

foreach ($testDates as $date) {
    $birth = new DateTime($date);
    $age = $today->diff($birth);
    $ageInMonths = ($age->y * 12) + $age->m;
    if ($age->d >= 15) {
        $ageInMonths += 1;
    }
    echo "<p>Birth: {$date} -> Age: {$age->y}y {$age->m}m ({$ageInMonths} months)</p>";
}
?>
