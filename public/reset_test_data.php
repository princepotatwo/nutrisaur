<?php
require_once __DIR__ . "/api/DatabaseAPI.php";

try {
    $db = new DatabaseAPI();
    $pdo = $db->getPDO();
    
    echo "<h1>Reset and Populate Test Data</h1>";
    
    // Delete all existing user_preferences
    echo "<h2>Step 1: Deleting all existing user_preferences...</h2>";
    $stmt = $pdo->prepare("DELETE FROM user_preferences");
    $stmt->execute();
    $deletedCount = $stmt->rowCount();
    echo "<p>✅ Deleted $deletedCount existing records</p>";
    
    // Create 10 test records with different risk levels
    echo "<h2>Step 2: Adding 10 test records...</h2>";
    
    $testData = [
        // High Risk Cases
        [
            'user_email' => 'test1@community.com',
            'age' => 25,
            'gender' => 'Female',
            'barangay' => 'BAGAC',
            'municipality' => 'BAGAC',
            'province' => 'BATAAN',
            'weight_kg' => 45,
            'height_cm' => 160,
            'bmi' => 17.6,
            'risk_score' => 85,
            'malnutrition_risk' => 'High',
            'screening_date' => date('Y-m-d'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'name' => 'Maria Santos',
            'birthday' => '1998-05-15',
            'income' => 'Low',
            'muac' => 11.0,
            'screening_answers' => '{"fatigue": true, "weight_loss": true, "poor_appetite": true}',
            'allergies' => 'None',
            'diet_prefs' => 'Traditional',
            'avoid_foods' => 'None',
            'swelling' => 1,
            'weight_loss' => 1,
            'feeding_behavior' => 'Poor',
            'physical_signs' => 'Fatigue, Weakness',
            'dietary_diversity' => 2,
            'clinical_risk_factors' => 'Chronic illness',
            'whz_score' => -3.2,
            'income_level' => 'Low'
        ],
        [
            'user_email' => 'test2@community.com',
            'age' => 30,
            'gender' => 'Male',
            'barangay' => 'BAGAC',
            'municipality' => 'BAGAC',
            'province' => 'BATAAN',
            'weight_kg' => 55,
            'height_cm' => 170,
            'bmi' => 19.0,
            'risk_score' => 75,
            'malnutrition_risk' => 'High',
            'screening_date' => date('Y-m-d'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'name' => 'Juan Dela Cruz',
            'birthday' => '1993-08-22',
            'income' => 'Medium',
            'muac' => 12.5,
            'screening_answers' => '{"fatigue": true, "weight_loss": false, "poor_appetite": true}',
            'allergies' => 'None',
            'diet_prefs' => 'Mixed',
            'avoid_foods' => 'None',
            'swelling' => 0,
            'weight_loss' => 0,
            'feeding_behavior' => 'Poor',
            'physical_signs' => 'Fatigue',
            'dietary_diversity' => 3,
            'clinical_risk_factors' => 'None',
            'whz_score' => -2.8,
            'income_level' => 'Medium'
        ],
        // Moderate Risk Cases
        [
            'user_email' => 'test3@community.com',
            'age' => 35,
            'gender' => 'Female',
            'barangay' => 'ABUCAY',
            'municipality' => 'ABUCAY',
            'province' => 'BATAAN',
            'weight_kg' => 60,
            'height_cm' => 165,
            'bmi' => 22.0,
            'risk_score' => 45,
            'malnutrition_risk' => 'Moderate',
            'screening_date' => date('Y-m-d'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'name' => 'Ana Garcia',
            'birthday' => '1988-12-10',
            'income' => 'Medium',
            'muac' => 14.0,
            'screening_answers' => '{"fatigue": false, "weight_loss": true, "poor_appetite": false}',
            'allergies' => 'None',
            'diet_prefs' => 'Traditional',
            'avoid_foods' => 'None',
            'swelling' => 0,
            'weight_loss' => 1,
            'feeding_behavior' => 'Normal',
            'physical_signs' => 'Fatigue, Weakness',
            'dietary_diversity' => 4,
            'clinical_risk_factors' => 'Chronic Illness',
            'whz_score' => -1.5,
            'income_level' => 'Medium'
        ],
        [
            'user_email' => 'test4@community.com',
            'age' => 28,
            'gender' => 'Male',
            'barangay' => 'ABUCAY',
            'municipality' => 'ABUCAY',
            'province' => 'BATAAN',
            'weight_kg' => 70,
            'height_cm' => 175,
            'bmi' => 22.9,
            'risk_score' => 35,
            'malnutrition_risk' => 'Moderate',
            'screening_date' => date('Y-m-d'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'name' => 'Pedro Martinez',
            'birthday' => '1995-03-18',
            'income' => 'High',
            'muac' => 15.5,
            'screening_answers' => '{"fatigue": false, "weight_loss": false, "poor_appetite": false}',
            'allergies' => 'None',
            'diet_prefs' => 'Modern',
            'avoid_foods' => 'None',
            'swelling' => 0,
            'weight_loss' => 0,
            'feeding_behavior' => 'Normal',
            'physical_signs' => 'Fatigue',
            'dietary_diversity' => 5,
            'clinical_risk_factors' => 'Medication Use',
            'whz_score' => -0.8,
            'income_level' => 'High'
        ],
        // Low Risk Cases
        [
            'user_email' => 'test5@community.com',
            'age' => 22,
            'gender' => 'Female',
            'barangay' => 'BAGAC',
            'municipality' => 'BAGAC',
            'province' => 'BATAAN',
            'weight_kg' => 55,
            'height_cm' => 160,
            'bmi' => 21.5,
            'risk_score' => 15,
            'malnutrition_risk' => 'Low',
            'screening_date' => date('Y-m-d'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'name' => 'Sofia Reyes',
            'birthday' => '2001-07-25',
            'income' => 'High',
            'muac' => 16.0,
            'screening_answers' => '{"fatigue": false, "weight_loss": false, "poor_appetite": false}',
            'allergies' => 'None',
            'diet_prefs' => 'Modern',
            'avoid_foods' => 'None',
            'swelling' => 0,
            'weight_loss' => 0,
            'feeding_behavior' => 'Normal',
            'physical_signs' => 'Dizziness',
            'dietary_diversity' => 6,
            'clinical_risk_factors' => 'Mental Health',
            'whz_score' => 0.2,
            'income_level' => 'High'
        ],
        [
            'user_email' => 'test6@community.com',
            'age' => 40,
            'gender' => 'Male',
            'barangay' => 'ABUCAY',
            'municipality' => 'ABUCAY',
            'province' => 'BATAAN',
            'weight_kg' => 75,
            'height_cm' => 170,
            'bmi' => 26.0,
            'risk_score' => 10,
            'malnutrition_risk' => 'Low',
            'screening_date' => date('Y-m-d'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'name' => 'Carlos Lopez',
            'birthday' => '1983-11-05',
            'income' => 'High',
            'muac' => 17.5,
            'screening_answers' => '{"fatigue": false, "weight_loss": false, "poor_appetite": false}',
            'allergies' => 'None',
            'diet_prefs' => 'Modern',
            'avoid_foods' => 'None',
            'swelling' => 0,
            'weight_loss' => 0,
            'feeding_behavior' => 'Normal',
            'physical_signs' => 'Headache',
            'dietary_diversity' => 7,
            'clinical_risk_factors' => 'Diabetes History',
            'whz_score' => 0.8,
            'income_level' => 'High'
        ],
        // Children Cases
        [
            'user_email' => 'test7@community.com',
            'age' => 4,
            'gender' => 'Female',
            'barangay' => 'BAGAC',
            'municipality' => 'BAGAC',
            'province' => 'BATAAN',
            'weight_kg' => 15,
            'height_cm' => 100,
            'bmi' => 15.0,
            'risk_score' => 90,
            'malnutrition_risk' => 'High',
            'screening_date' => date('Y-m-d'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'name' => 'Luna Santos',
            'birthday' => '2019-02-14',
            'income' => 'Low',
            'muac' => 10.5,
            'screening_answers' => '{"fatigue": true, "weight_loss": true, "poor_appetite": true}',
            'allergies' => 'None',
            'diet_prefs' => 'Traditional',
            'avoid_foods' => 'None',
            'swelling' => 1,
            'weight_loss' => 1,
            'feeding_behavior' => 'Poor',
            'physical_signs' => 'Fatigue, Weakness',
            'dietary_diversity' => 1,
            'clinical_risk_factors' => 'None',
            'whz_score' => -3.5,
            'income_level' => 'Low'
        ],
        [
            'user_email' => 'test8@community.com',
            'age' => 8,
            'gender' => 'Male',
            'barangay' => 'ABUCAY',
            'municipality' => 'ABUCAY',
            'province' => 'BATAAN',
            'weight_kg' => 25,
            'height_cm' => 120,
            'bmi' => 17.4,
            'risk_score' => 65,
            'malnutrition_risk' => 'High',
            'screening_date' => date('Y-m-d'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'name' => 'Miguel Garcia',
            'birthday' => '2015-09-30',
            'income' => 'Medium',
            'muac' => 12.0,
            'screening_answers' => '{"fatigue": true, "weight_loss": false, "poor_appetite": true}',
            'allergies' => 'None',
            'diet_prefs' => 'Traditional',
            'avoid_foods' => 'None',
            'swelling' => 0,
            'weight_loss' => 0,
            'feeding_behavior' => 'Poor',
            'physical_signs' => 'Fatigue',
            'dietary_diversity' => 2,
            'clinical_risk_factors' => 'None',
            'whz_score' => -2.1,
            'income_level' => 'Medium'
        ],
        // Elderly Cases
        [
            'user_email' => 'test9@community.com',
            'age' => 70,
            'gender' => 'Female',
            'barangay' => 'BAGAC',
            'municipality' => 'BAGAC',
            'province' => 'BATAAN',
            'weight_kg' => 50,
            'height_cm' => 155,
            'bmi' => 20.8,
            'risk_score' => 55,
            'malnutrition_risk' => 'Moderate',
            'screening_date' => date('Y-m-d'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'name' => 'Lola Rosa',
            'birthday' => '1953-04-12',
            'income' => 'Low',
            'muac' => 13.5,
            'screening_answers' => '{"fatigue": true, "weight_loss": true, "poor_appetite": false}',
            'allergies' => 'None',
            'diet_prefs' => 'Traditional',
            'avoid_foods' => 'None',
            'swelling' => 0,
            'weight_loss' => 1,
            'feeding_behavior' => 'Normal',
            'physical_signs' => 'Fatigue',
            'dietary_diversity' => 3,
            'clinical_risk_factors' => 'Chronic illness',
            'whz_score' => -1.8,
            'income_level' => 'Low'
        ],
        [
            'user_email' => 'test10@community.com',
            'age' => 65,
            'gender' => 'Male',
            'barangay' => 'ABUCAY',
            'municipality' => 'ABUCAY',
            'province' => 'BATAAN',
            'weight_kg' => 65,
            'height_cm' => 165,
            'bmi' => 23.9,
            'risk_score' => 25,
            'malnutrition_risk' => 'Low',
            'screening_date' => date('Y-m-d'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'name' => 'Lolo Jose',
            'birthday' => '1958-12-25',
            'income' => 'Medium',
            'muac' => 16.0,
            'screening_answers' => '{"fatigue": false, "weight_loss": false, "poor_appetite": false}',
            'allergies' => 'None',
            'diet_prefs' => 'Traditional',
            'avoid_foods' => 'None',
            'swelling' => 0,
            'weight_loss' => 0,
            'feeding_behavior' => 'Normal',
            'physical_signs' => 'None',
            'dietary_diversity' => 5,
            'clinical_risk_factors' => 'None',
            'whz_score' => -0.5,
            'income_level' => 'Medium'
        ]
    ];
    
    $insertedCount = 0;
    foreach ($testData as $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $stmt = $pdo->prepare("INSERT INTO user_preferences ($columns) VALUES ($placeholders)");
        $stmt->execute($data);
        $insertedCount++;
        
        echo "<p>✅ Added: {$data['name']} - {$data['age']} years old, {$data['gender']}, Risk Score: {$data['risk_score']}</p>";
    }
    
    echo "<h2>Step 3: Verification</h2>";
    
    // Check total count
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM user_preferences");
    $stmt->execute();
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "<p>📊 Total records: $total</p>";
    
    // Check risk distribution
    $stmt = $pdo->prepare("SELECT 
        SUM(CASE WHEN risk_score < 20 THEN 1 ELSE 0 END) as low,
        SUM(CASE WHEN risk_score >= 20 AND risk_score < 50 THEN 1 ELSE 0 END) as moderate,
        SUM(CASE WHEN risk_score >= 50 AND risk_score < 80 THEN 1 ELSE 0 END) as high,
        SUM(CASE WHEN risk_score >= 80 THEN 1 ELSE 0 END) as severe
        FROM user_preferences");
    $stmt->execute();
    $riskData = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>📈 Risk Distribution: Low={$riskData['low']}, Moderate={$riskData['moderate']}, High={$riskData['high']}, Severe={$riskData['severe']}</p>";
    
    // Check age distribution
    $stmt = $pdo->prepare("SELECT 
        SUM(CASE WHEN age < 5 THEN 1 ELSE 0 END) as under_5,
        SUM(CASE WHEN age >= 5 AND age < 18 THEN 1 ELSE 0 END) as youth,
        SUM(CASE WHEN age >= 18 AND age < 65 THEN 1 ELSE 0 END) as adult,
        SUM(CASE WHEN age >= 65 THEN 1 ELSE 0 END) as elderly
        FROM user_preferences");
    $stmt->execute();
    $ageData = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>👥 Age Distribution: Under 5={$ageData['under_5']}, Youth={$ageData['youth']}, Adult={$ageData['adult']}, Elderly={$ageData['elderly']}</p>";
    
    // Check barangay distribution
    $stmt = $pdo->prepare("SELECT barangay, COUNT(*) as count FROM user_preferences GROUP BY barangay");
    $stmt->execute();
    $barangayData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>🗺️ Barangay Distribution:</p><ul>";
    foreach ($barangayData as $barangay) {
        echo "<li>{$barangay['barangay']}: {$barangay['count']} people</li>";
    }
    echo "</ul>";
    
    echo "<h2>✅ Test Data Ready!</h2>";
    echo "<p>Your dashboard should now show:</p>";
    echo "<ul>";
    echo "<li>📊 Total Screened: $total</li>";
    echo "<li>🚨 Critical Alerts: " . ($riskData['high'] + $riskData['severe']) . "</li>";
    echo "<li>📈 Risk Distribution: All levels represented</li>";
    echo "<li>👥 Age Groups: All age ranges covered</li>";
    echo "<li>🗺️ Geographic: Both BAGAC and ABUCAY</li>";
    echo "</ul>";
    
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>Refresh your dashboard</li>";
    echo "<li>Check all sections are populated</li>";
    echo "<li>Test barangay filtering</li>";
    echo "<li>Verify critical alerts show community info</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2 { color: #2c5530; }
p { margin: 10px 0; }
ul, ol { margin: 10px 0; padding-left: 20px; }
li { margin: 5px 0; }
</style>
