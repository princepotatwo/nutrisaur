<?php
require_once __DIR__ . "/api/DatabaseAPI.php";

// Create a comprehensive test account
$testUser = [
    'email' => 'test@nutrisaur.com',
    'username' => 'TestUser',
    'password' => 'test123',
    'is_admin' => 0
];

// Comprehensive user preferences data
$userPreferences = [
    'user_email' => 'test@nutrisaur.com',
    'age' => 25,
    'gender' => 'Female',
    'barangay' => 'BAGAC',
    'municipality' => 'BAGAC',
    'height_cm' => 165,
    'weight_kg' => 55,
    'risk_score' => 85, // High risk to test SAM cases
    'muac_cm' => 11.0, // Critical MUAC to test alerts
    'whz_score' => -3.2, // Severe malnutrition
    'dietary_diversity_score' => 2, // Low dietary diversity
    'income_level' => 'Low',
    'education_level' => 'High School',
    'occupation' => 'Student',
    'marital_status' => 'Single',
    'household_size' => 4,
    'children_count' => 0,
    'elderly_count' => 1,
    'pregnant' => 0,
    'lactating' => 0,
    'chronic_illness' => 1,
    'medication_use' => 1,
    'smoking' => 0,
    'alcohol_use' => 0,
    'physical_activity' => 'Low',
    'stress_level' => 'High',
    'sleep_hours' => 6,
    'water_intake' => 'Low',
    'food_security' => 'Insecure',
    'access_to_healthcare' => 'Limited',
    'nutrition_education' => 'None',
    'supplement_use' => 0,
    'allergies' => 'None',
    'food_preferences' => 'Traditional',
    'cooking_facilities' => 'Basic',
    'refrigeration' => 'Yes',
    'transportation' => 'Limited',
    'community_support' => 'Low',
    'emergency_contacts' => 'Family',
    'insurance_coverage' => 'None',
    'disability' => 0,
    'mental_health_concerns' => 1,
    'substance_abuse_history' => 0,
    'family_history_diabetes' => 1,
    'family_history_heart_disease' => 0,
    'family_history_cancer' => 0,
    'family_history_obesity' => 1,
    'recent_weight_loss' => 1,
    'appetite_changes' => 1,
    'swelling_edema' => 1,
    'fatigue' => 1,
    'weakness' => 1,
    'dizziness' => 1,
    'shortness_of_breath' => 0,
    'chest_pain' => 0,
    'abdominal_pain' => 1,
    'nausea' => 1,
    'vomiting' => 0,
    'diarrhea' => 1,
    'constipation' => 0,
    'fever' => 0,
    'cough' => 0,
    'sore_throat' => 0,
    'headache' => 1,
    'vision_problems' => 0,
    'hearing_problems' => 0,
    'dental_problems' => 1,
    'skin_problems' => 1,
    'hair_loss' => 1,
    'nail_changes' => 1,
    'bone_pain' => 1,
    'joint_pain' => 1,
    'muscle_cramps' => 1,
    'numbness' => 0,
    'tingling' => 0,
    'seizures' => 0,
    'memory_problems' => 1,
    'concentration_issues' => 1,
    'mood_changes' => 1,
    'anxiety' => 1,
    'depression' => 1,
    'irritability' => 1,
    'sleep_problems' => 1,
    'nightmares' => 0,
    'night_sweats' => 0,
    'hot_flashes' => 0,
    'irregular_menstruation' => 1,
    'heavy_bleeding' => 0,
    'painful_periods' => 1,
    'breast_changes' => 0,
    'urinary_problems' => 0,
    'bowel_changes' => 1,
    'blood_in_stool' => 0,
    'blood_in_urine' => 0,
    'unusual_discharge' => 0,
    'lumps_swellings' => 0,
    'moles_changes' => 0,
    'wounds_not_healing' => 1,
    'easy_bruising' => 1,
    'excessive_bleeding' => 0,
    'frequent_infections' => 1,
    'slow_healing' => 1,
    'temperature_intolerance' => 1,
    'excessive_thirst' => 1,
    'excessive_hunger' => 1,
    'frequent_urination' => 1,
    'nocturia' => 1,
    'incontinence' => 0,
    'bedwetting' => 0,
    'constipation_chronic' => 0,
    'diarrhea_chronic' => 1,
    'bloating' => 1,
    'gas' => 1,
    'heartburn' => 1,
    'acid_reflux' => 1,
    'food_intolerance' => 1,
    'food_allergies' => 0,
    'gluten_sensitivity' => 0,
    'lactose_intolerance' => 1,
    'other_intolerances' => 'None',
    'eating_disorder_history' => 0,
    'binge_eating' => 0,
    'restrictive_eating' => 1,
    'purging' => 0,
    'body_image_concerns' => 1,
    'weight_concerns' => 1,
    'dieting_history' => 1,
    'yo_yo_dieting' => 1,
    'fad_diets' => 1,
    'supplement_abuse' => 0,
    'laxative_abuse' => 0,
    'diuretic_abuse' => 0,
    'appetite_suppressants' => 0,
    'steroid_use' => 0,
    'other_medications' => 'None',
    'vitamin_d_supplement' => 0,
    'iron_supplement' => 1,
    'calcium_supplement' => 0,
    'omega_3_supplement' => 0,
    'probiotic_supplement' => 0,
    'multivitamin_supplement' => 0,
    'other_supplements' => 'None',
    'herbal_medicines' => 0,
    'traditional_medicines' => 1,
    'home_remedies' => 1,
    'alternative_therapies' => 0,
    'acupuncture' => 0,
    'massage_therapy' => 0,
    'chiropractic' => 0,
    'naturopathy' => 0,
    'homeopathy' => 0,
    'ayurveda' => 0,
    'traditional_chinese_medicine' => 0,
    'other_alternative' => 'None',
    'regular_checkups' => 0,
    'dental_checkups' => 0,
    'eye_checkups' => 0,
    'gynecological_checkups' => 0,
    'prostate_checkups' => 0,
    'colonoscopy' => 0,
    'mammogram' => 0,
    'pap_smear' => 0,
    'prostate_specific_antigen' => 0,
    'bone_density_scan' => 0,
    'other_screenings' => 'None',
    'vaccinations_up_to_date' => 0,
    'flu_vaccine' => 0,
    'pneumonia_vaccine' => 0,
    'covid_vaccine' => 0,
    'other_vaccines' => 'None',
    'emergency_plan' => 0,
    'first_aid_kit' => 0,
    'emergency_contacts_saved' => 1,
    'medical_alert_bracelet' => 0,
    'advance_directive' => 0,
    'living_will' => 0,
    'healthcare_proxy' => 0,
    'organ_donor' => 0,
    'blood_donor' => 0,
    'bone_marrow_donor' => 0,
    'other_donations' => 'None',
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s')
];

try {
    $db = new DatabaseAPI();
    $pdo = $db->getPDO();
    
    echo "<h1>Creating Comprehensive Test Account</h1>";
    
    // Check if test user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$testUser['email']]);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        echo "<p>✅ Test user already exists: {$testUser['email']}</p>";
    } else {
        // Create test user
        $stmt = $pdo->prepare("INSERT INTO users (email, username, password, is_admin, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$testUser['email'], $testUser['username'], password_hash($testUser['password'], PASSWORD_DEFAULT), $testUser['is_admin']]);
        echo "<p>✅ Test user created: {$testUser['email']}</p>";
    }
    
    // Check if user preferences already exist
    $stmt = $pdo->prepare("SELECT id FROM user_preferences WHERE user_email = ?");
    $stmt->execute([$userPreferences['user_email']]);
    $existingPrefs = $stmt->fetch();
    
    if ($existingPrefs) {
        echo "<p>✅ User preferences already exist for: {$userPreferences['user_email']}</p>";
    } else {
        // Create comprehensive user preferences
        $columns = implode(', ', array_keys($userPreferences));
        $placeholders = ':' . implode(', :', array_keys($userPreferences));
        
        $stmt = $pdo->prepare("INSERT INTO user_preferences ($columns) VALUES ($placeholders)");
        $stmt->execute($userPreferences);
        echo "<p>✅ Comprehensive user preferences created for: {$userPreferences['user_email']}</p>";
    }
    
    // Test API endpoints
    echo "<h2>Testing API Endpoints</h2>";
    
    // Test community metrics
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM user_preferences");
    $stmt->execute();
    $total = $stmt->fetch()['total'];
    echo "<p>📊 Total users in database: $total</p>";
    
    // Test risk distribution
    $stmt = $pdo->prepare("SELECT 
        SUM(CASE WHEN risk_score < 20 THEN 1 ELSE 0 END) as low,
        SUM(CASE WHEN risk_score >= 20 AND risk_score < 50 THEN 1 ELSE 0 END) as moderate,
        SUM(CASE WHEN risk_score >= 50 AND risk_score < 80 THEN 1 ELSE 0 END) as high,
        SUM(CASE WHEN risk_score >= 80 THEN 1 ELSE 0 END) as severe
        FROM user_preferences");
    $stmt->execute();
    $riskData = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>📈 Risk Distribution: Low={$riskData['low']}, Moderate={$riskData['moderate']}, High={$riskData['high']}, Severe={$riskData['severe']}</p>";
    
    // Test critical alerts
    $stmt = $pdo->prepare("SELECT COUNT(*) as critical FROM user_preferences WHERE risk_score >= 30");
    $stmt->execute();
    $critical = $stmt->fetch()['critical'];
    echo "<p>🚨 Critical alerts: $critical</p>";
    
    // Test geographic distribution
    $stmt = $pdo->prepare("SELECT barangay, COUNT(*) as count FROM user_preferences GROUP BY barangay ORDER BY count DESC LIMIT 5");
    $stmt->execute();
    $geoData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>🗺️ Top 5 barangays:</p><ul>";
    foreach ($geoData as $geo) {
        echo "<li>{$geo['barangay']}: {$geo['count']} users</li>";
    }
    echo "</ul>";
    
    echo "<h2>Test Account Details</h2>";
    echo "<p><strong>Email:</strong> {$testUser['email']}</p>";
    echo "<p><strong>Password:</strong> {$testUser['password']}</p>";
    echo "<p><strong>Risk Score:</strong> {$userPreferences['risk_score']} (High Risk)</p>";
    echo "<p><strong>MUAC:</strong> {$userPreferences['muac_cm']} cm (Critical)</p>";
    echo "<p><strong>WHZ Score:</strong> {$userPreferences['whz_score']} (Severe Malnutrition)</p>";
    echo "<p><strong>Barangay:</strong> {$userPreferences['barangay']}</p>";
    
    echo "<h2>Dashboard Test Results</h2>";
    echo "<p>✅ This test account should populate:</p>";
    echo "<ul>";
    echo "<li>Total Screened (increased by 1)</li>";
    echo "<li>High Risk Cases (increased by 1)</li>";
    echo "<li>SAM Cases (increased by 1)</li>";
    echo "<li>Critical Alerts (increased by 1)</li>";
    echo "<li>Geographic Distribution (BAGAC increased by 1)</li>";
    echo "<li>Risk Distribution Chart (Severe category increased by 1)</li>";
    echo "<li>All screening response sections</li>";
    echo "<li>Analysis data sections</li>";
    echo "</ul>";
    
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>Refresh your dashboard to see the updated numbers</li>";
    echo "<li>Check all UI sections for new data</li>";
    echo "<li>Test barangay filtering by selecting 'BAGAC'</li>";
    echo "<li>Verify that all charts and metrics are populated</li>";
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
