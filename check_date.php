<?php
echo "<h2>Current Date Check</h2>";
echo "<p><strong>Current Date:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Timezone:</strong> " . date_default_timezone_get() . "</p>";

// Test with the screening date from CSV
$screeningDate = '2024-09-15 10:30:00';
echo "<p><strong>Screening Date from CSV:</strong> {$screeningDate}</p>";

// Calculate age using screening date instead of current date
$birthday = '2024-09-15';
$birth = new DateTime($birthday);
$screening = new DateTime($screeningDate);
$age = $birth->diff($screening);
$ageInMonths = ($age->y * 12) + $age->m;
if ($age->d >= 15) {
    $ageInMonths += 1;
}

echo "<p><strong>Age calculation using screening date:</strong></p>";
echo "<p>Birth: {$birthday}</p>";
echo "<p>Screening: {$screeningDate}</p>";
echo "<p>Age: {$age->y}y {$age->m}m {$age->d}d ({$ageInMonths} months)</p>";

// Test with different birth dates
$testDates = [
    '2024-09-15', // Should be 0 months
    '2023-09-15', // Should be 12 months
    '2021-10-15', // Should be 35 months
    '2018-10-15'  // Should be 71 months
];

echo "<h3>Age calculations using screening date:</h3>";
foreach ($testDates as $date) {
    $birth = new DateTime($date);
    $age = $birth->diff($screening);
    $ageInMonths = ($age->y * 12) + $age->m;
    if ($age->d >= 15) {
        $ageInMonths += 1;
    }
    echo "<p>Birth: {$date} -> Age: {$age->y}y {$age->m}m ({$ageInMonths} months)</p>";
}
?>
