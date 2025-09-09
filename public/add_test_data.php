<?php
// Script to add diverse nutritional assessment test data via web interface
require_once __DIR__ . '/../config.php';

try {
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        throw new Exception("Database connection failed");
    }

    // Clear existing test data
    $pdo->exec("DELETE FROM community_users WHERE email LIKE '%@test.com'");

    // Add diverse test data to demonstrate all nutritional statuses
    $testData = [
        // Children with different nutritional statuses
        [
            'name' => 'Child SAM',
            'email' => 'child_sam@test.com',
            'password' => '$2y$10$example_hash',
            'municipality' => 'CITY OF BALANGA',
            'barangay' => 'Central',
            'sex' => 'Male',
            'birthday' => '2020-01-15', // 4 years old
            'is_pregnant' => null,
            'weight' => '12.0', // Very low weight
            'height' => '95.0', // Low height
            'muac' => '10.5' // Very low MUAC
        ],
        [
            'name' => 'Child MAM',
            'email' => 'child_mam@test.com',
            'password' => '$2y$10$example_hash',
            'municipality' => 'DINALUPIHAN',
            'barangay' => 'Bonifacio (Pob.)',
            'sex' => 'Female',
            'birthday' => '2019-06-10', // 5 years old
            'is_pregnant' => null,
            'weight' => '14.0',
            'height' => '100.0',
            'muac' => '12.0' // Moderate MUAC
        ],
        [
            'name' => 'Child Normal',
            'email' => 'child_normal@test.com',
            'password' => '$2y$10$example_hash',
            'municipality' => 'ORANI',
            'barangay' => 'Centro I (Pob.)',
            'sex' => 'Male',
            'birthday' => '2018-03-20', // 6 years old
            'is_pregnant' => null,
            'weight' => '20.0',
            'height' => '110.0',
            'muac' => '14.0' // Normal MUAC
        ],
        
        // Pregnant women
        [
            'name' => 'Pregnant At-risk',
            'email' => 'pregnant_atrisk@test.com',
            'password' => '$2y$10$example_hash',
            'municipality' => 'MARIVELES',
            'barangay' => 'Poblacion',
            'sex' => 'Female',
            'birthday' => '1995-08-12', // 29 years old
            'is_pregnant' => 'Yes',
            'weight' => '45.0',
            'height' => '160.0',
            'muac' => '22.0' // Low MUAC for pregnant
        ],
        [
            'name' => 'Pregnant Normal',
            'email' => 'pregnant_normal@test.com',
            'password' => '$2y$10$example_hash',
            'municipality' => 'HERMOSA',
            'barangay' => 'A. Rivera (Pob.)',
            'sex' => 'Female',
            'birthday' => '1992-11-05', // 32 years old
            'is_pregnant' => 'Yes',
            'weight' => '58.0',
            'height' => '165.0',
            'muac' => '26.0' // Normal MUAC for pregnant
        ],
        
        // Adults with different BMI categories
        [
            'name' => 'Adult Severe Underweight',
            'email' => 'adult_severe@test.com',
            'password' => '$2y$10$example_hash',
            'municipality' => 'BAGAC',
            'barangay' => 'Bagumbayan (Pob.)',
            'sex' => 'Male',
            'birthday' => '1988-04-18', // 36 years old
            'is_pregnant' => null,
            'weight' => '45.0', // Very low weight
            'height' => '170.0',
            'muac' => '20.0'
        ],
        [
            'name' => 'Adult Moderate Underweight',
            'email' => 'adult_moderate@test.com',
            'password' => '$2y$10$example_hash',
            'municipality' => 'LIMAY',
            'barangay' => 'Poblacion',
            'sex' => 'Female',
            'birthday' => '1990-07-22', // 34 years old
            'is_pregnant' => null,
            'weight' => '50.0',
            'height' => '170.0',
            'muac' => '22.0'
        ],
        [
            'name' => 'Adult Mild Underweight',
            'email' => 'adult_mild@test.com',
            'password' => '$2y$10$example_hash',
            'municipality' => 'MORONG',
            'barangay' => 'Poblacion',
            'sex' => 'Male',
            'birthday' => '1985-12-03', // 39 years old
            'is_pregnant' => null,
            'weight' => '55.0',
            'height' => '175.0',
            'muac' => '24.0'
        ],
        [
            'name' => 'Adult Normal',
            'email' => 'adult_normal@test.com',
            'password' => '$2y$10$example_hash',
            'municipality' => 'ORION',
            'barangay' => 'Arellano (Pob.)',
            'sex' => 'Female',
            'birthday' => '1987-09-14', // 37 years old
            'is_pregnant' => null,
            'weight' => '65.0',
            'height' => '165.0',
            'muac' => '25.0'
        ],
        [
            'name' => 'Adult Overweight',
            'email' => 'adult_overweight@test.com',
            'password' => '$2y$10$example_hash',
            'municipality' => 'PILAR',
            'barangay' => 'Poblacion',
            'sex' => 'Male',
            'birthday' => '1983-02-28', // 41 years old
            'is_pregnant' => null,
            'weight' => '85.0',
            'height' => '175.0',
            'muac' => '28.0'
        ],
        [
            'name' => 'Adult Obesity Class I',
            'email' => 'adult_obesity1@test.com',
            'password' => '$2y$10$example_hash',
            'municipality' => 'SAMAL',
            'barangay' => 'East Calaguiman (Pob.)',
            'sex' => 'Female',
            'birthday' => '1980-06-15', // 44 years old
            'is_pregnant' => null,
            'weight' => '95.0',
            'height' => '165.0',
            'muac' => '30.0'
        ],
        [
            'name' => 'Adult Obesity Class II',
            'email' => 'adult_obesity2@test.com',
            'password' => '$2y$10$example_hash',
            'municipality' => 'ABUCAY',
            'barangay' => 'Bangkal',
            'sex' => 'Male',
            'birthday' => '1978-10-08', // 46 years old
            'is_pregnant' => null,
            'weight' => '110.0',
            'height' => '170.0',
            'muac' => '32.0'
        ],
        [
            'name' => 'Adult Obesity Class III',
            'email' => 'adult_obesity3@test.com',
            'password' => '$2y$10$example_hash',
            'municipality' => 'CITY OF BALANGA',
            'barangay' => 'Bagumbayan',
            'sex' => 'Female',
            'birthday' => '1975-03-25', // 49 years old
            'is_pregnant' => null,
            'weight' => '130.0',
            'height' => '160.0',
            'muac' => '35.0'
        ]
    ];

    $stmt = $pdo->prepare("
        INSERT INTO community_users 
        (name, email, password, municipality, barangay, sex, birthday, is_pregnant, weight, height, muac, screening_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    foreach ($testData as $data) {
        $stmt->execute([
            $data['name'],
            $data['email'],
            $data['password'],
            $data['municipality'],
            $data['barangay'],
            $data['sex'],
            $data['birthday'],
            $data['is_pregnant'],
            $data['weight'],
            $data['height'],
            $data['muac']
        ]);
    }

    echo "<h2>✅ Successfully added " . count($testData) . " diverse test records!</h2>";
    echo "<p>Now the screening page should show various nutritional statuses:</p>";
    echo "<ul>";
    echo "<li>Severe Acute Malnutrition (SAM)</li>";
    echo "<li>Moderate Acute Malnutrition (MAM)</li>";
    echo "<li>Maternal Undernutrition (At-risk)</li>";
    echo "<li>Severe Underweight</li>";
    echo "<li>Moderate Underweight</li>";
    echo "<li>Mild Underweight</li>";
    echo "<li>Normal</li>";
    echo "<li>Overweight</li>";
    echo "<li>Obesity Class I</li>";
    echo "<li>Obesity Class II</li>";
    echo "<li>Obesity Class III (Severe)</li>";
    echo "</ul>";
    echo "<p><a href='screening.php'>Go to Screening Page</a></p>";

} catch (Exception $e) {
    echo "<h2>❌ Error: " . $e->getMessage() . "</h2>";
}
?>
