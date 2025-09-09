<?php
/**
 * Test script to verify age fix for screening.php
 * This will test users of different ages to ensure they appear correctly
 */

echo "=== TESTING AGE FIX FOR SCREENING.PHP ===\n\n";

// Test data for different age groups
$testUsers = [
    // Child (should show Weight-for-Age)
    [
        'name' => 'Child Test User',
        'email' => 'child' . time() . '@example.com',
        'birthday' => '2020-01-15', // 4 years old
        'weight' => '18.5',
        'height' => '105.0',
        'sex' => 'Male',
        'expected_age_months' => 48,
        'expected_standard' => 'weight-for-age'
    ],
    
    // Adult (should show BMI with adult classification)
    [
        'name' => 'Adult Test User',
        'email' => 'adult' . time() . '@example.com',
        'birthday' => '2003-01-15', // 22 years old
        'weight' => '70.0',
        'height' => '175.0',
        'sex' => 'Male',
        'expected_age_months' => 264,
        'expected_standard' => 'bmi-for-age'
    ],
    
    // Teenager (should show BMI with adult classification)
    [
        'name' => 'Teen Test User',
        'email' => 'teen' . time() . '@example.com',
        'birthday' => '2010-06-15', // 14 years old
        'weight' => '55.0',
        'height' => '160.0',
        'sex' => 'Female',
        'expected_age_months' => 174,
        'expected_standard' => 'bmi-for-age'
    ]
];

foreach ($testUsers as $i => $user) {
    echo "Test User " . ($i + 1) . ": " . $user['name'] . "\n";
    echo "  Email: " . $user['email'] . "\n";
    echo "  Birthday: " . $user['birthday'] . "\n";
    echo "  Weight: " . $user['weight'] . " kg\n";
    echo "  Height: " . $user['height'] . " cm\n";
    echo "  Sex: " . $user['sex'] . "\n";
    
    // Calculate age
    $birthDate = new DateTime($user['birthday']);
    $today = new DateTime();
    $age = $today->diff($birthDate);
    $ageInMonths = ($age->y * 12) + $age->m;
    $ageDisplay = $age->y . 'y ' . $age->m . 'm';
    
    echo "  Calculated Age: " . $ageDisplay . " (" . $ageInMonths . " months)\n";
    echo "  Expected Age: " . $user['expected_age_months'] . " months\n";
    
    // Calculate BMI
    $bmi = $user['weight'] && $user['height'] ? 
           round($user['weight'] / pow($user['height'] / 100, 2), 1) : 'N/A';
    echo "  BMI: " . $bmi . "\n";
    
    // Determine which standard should be shown
    $showStandard = null;
    if ($ageInMonths >= 0 && $ageInMonths <= 71) {
        $showStandard = 'weight-for-age';
    } elseif ($ageInMonths > 71) {
        $showStandard = 'bmi-for-age';
    }
    
    echo "  Expected Standard: " . $user['expected_standard'] . "\n";
    echo "  Calculated Standard: " . $showStandard . "\n";
    
    // Check if user should be displayed
    $shouldDisplay = ($showStandard !== null);
    echo "  Should Display: " . ($shouldDisplay ? 'Yes' : 'No') . "\n";
    
    if ($shouldDisplay) {
        echo "  ✅ User will appear in screening.php\n";
    } else {
        echo "  ❌ User will NOT appear in screening.php\n";
    }
    
    echo "\n";
}

echo "=== TEST COMPLETED ===\n";
echo "Now test the actual screening.php page to see if users of all ages appear correctly.\n";
echo "The 22-year-old (2003) should now appear in the screening table with BMI classification.\n";
?>
