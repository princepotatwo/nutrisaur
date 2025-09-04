<?php
/**
 * Populate Sample Data Script
 * Adds realistic sample data to user_preferences table for dashboard testing
 */

// Include the unified DatabaseAPI
require_once __DIR__ . "/api/DatabaseAPI.php";

// Start session if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set content type for better output
header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Populate Sample Data</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background-color: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>📊 Populate Sample Data for Dashboard Testing</h1>
    <p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "</p>
";

try {
    // Initialize DatabaseAPI
    $db = new DatabaseAPI();
    
    if (!$db) {
        throw new Exception("Failed to initialize DatabaseAPI");
    }
    
    $pdo = $db->getPDO();
    if (!$pdo) {
        throw new Exception("Failed to get PDO connection");
    }
    
    echo "<div class='info'>
        <h3>✅ DatabaseAPI Initialized Successfully</h3>
        <p>DatabaseAPI instance created and PDO connection established.</p>
    </div>";
    
    // Sample data for user_preferences
    $sampleData = [
        [
            'user_email' => 'maria.santos@test.com',
            'age' => 28,
            'gender' => 'female',
            'barangay' => 'Bagac',
            'municipality' => 'Bagac',
            'province' => 'Bataan',
            'weight_kg' => '55.20',
            'height_cm' => '160.00',
            'bmi' => '21.56',
            'risk_score' => 45,
            'malnutrition_risk' => 'moderate',
            'screening_date' => '2025-09-01',
            'name' => 'Maria Santos',
            'birthday' => '1997-05-15',
            'income' => 'medium',
            'muac' => '22.50',
            'screening_answers' => json_encode(['question1' => 'yes', 'question2' => 'no']),
            'allergies' => 'peanuts',
            'diet_prefs' => 'vegetarian',
            'avoid_foods' => 'shellfish',
            'swelling' => 0,
            'weight_loss' => 1,
            'feeding_behavior' => 'normal',
            'physical_signs' => 'none',
            'dietary_diversity' => 'moderate',
            'clinical_risk_factors' => 'diabetes',
            'whz_score' => -1.2,
            'income_level' => 'medium'
        ],
        [
            'user_email' => 'juan.delacruz@test.com',
            'age' => 35,
            'gender' => 'male',
            'barangay' => 'Bagac',
            'municipality' => 'Bagac',
            'province' => 'Bataan',
            'weight_kg' => '78.50',
            'height_cm' => '175.00',
            'bmi' => '25.63',
            'risk_score' => 65,
            'malnutrition_risk' => 'high',
            'screening_date' => '2025-09-02',
            'name' => 'Juan Dela Cruz',
            'birthday' => '1990-08-22',
            'income' => 'low',
            'muac' => '24.00',
            'screening_answers' => json_encode(['question1' => 'yes', 'question2' => 'yes']),
            'allergies' => 'none',
            'diet_prefs' => 'regular',
            'avoid_foods' => 'none',
            'swelling' => 1,
            'weight_loss' => 1,
            'feeding_behavior' => 'poor',
            'physical_signs' => 'fatigue',
            'dietary_diversity' => 'low',
            'clinical_risk_factors' => 'hypertension',
            'whz_score' => -2.1,
            'income_level' => 'low'
        ],
        [
            'user_email' => 'ana.garcia@test.com',
            'age' => 22,
            'gender' => 'female',
            'barangay' => 'Bagac',
            'municipality' => 'Bagac',
            'province' => 'Bataan',
            'weight_kg' => '48.30',
            'height_cm' => '155.00',
            'bmi' => '20.10',
            'risk_score' => 25,
            'malnutrition_risk' => 'low',
            'screening_date' => '2025-09-03',
            'name' => 'Ana Garcia',
            'birthday' => '2003-12-10',
            'income' => 'high',
            'muac' => '21.00',
            'screening_answers' => json_encode(['question1' => 'no', 'question2' => 'no']),
            'allergies' => 'none',
            'diet_prefs' => 'regular',
            'avoid_foods' => 'none',
            'swelling' => 0,
            'weight_loss' => 0,
            'feeding_behavior' => 'excellent',
            'physical_signs' => 'none',
            'dietary_diversity' => 'high',
            'clinical_risk_factors' => 'none',
            'whz_score' => 0.5,
            'income_level' => 'high'
        ],
        [
            'user_email' => 'pedro.martinez@test.com',
            'age' => 45,
            'gender' => 'male',
            'barangay' => 'Bagac',
            'municipality' => 'Bagac',
            'province' => 'Bataan',
            'weight_kg' => '85.20',
            'height_cm' => '170.00',
            'bmi' => '29.48',
            'risk_score' => 85,
            'malnutrition_risk' => 'severe',
            'screening_date' => '2025-09-04',
            'name' => 'Pedro Martinez',
            'birthday' => '1980-03-18',
            'income' => 'medium',
            'muac' => '26.50',
            'screening_answers' => json_encode(['question1' => 'yes', 'question2' => 'yes']),
            'allergies' => 'none',
            'diet_prefs' => 'regular',
            'avoid_foods' => 'none',
            'swelling' => 1,
            'weight_loss' => 1,
            'feeding_behavior' => 'poor',
            'physical_signs' => 'edema',
            'dietary_diversity' => 'very_low',
            'clinical_risk_factors' => 'diabetes,hypertension',
            'whz_score' => -3.2,
            'income_level' => 'medium'
        ],
        [
            'user_email' => 'lucia.reyes@test.com',
            'age' => 19,
            'gender' => 'female',
            'barangay' => 'Bagac',
            'municipality' => 'Bagac',
            'province' => 'Bataan',
            'weight_kg' => '52.10',
            'height_cm' => '158.00',
            'bmi' => '20.86',
            'risk_score' => 30,
            'malnutrition_risk' => 'low',
            'screening_date' => '2025-09-05',
            'name' => 'Lucia Reyes',
            'birthday' => '2006-07-25',
            'income' => 'low',
            'muac' => '20.50',
            'screening_answers' => json_encode(['question1' => 'no', 'question2' => 'yes']),
            'allergies' => 'none',
            'diet_prefs' => 'regular',
            'avoid_foods' => 'none',
            'swelling' => 0,
            'weight_loss' => 0,
            'feeding_behavior' => 'normal',
            'physical_signs' => 'none',
            'dietary_diversity' => 'moderate',
            'clinical_risk_factors' => 'none',
            'whz_score' => -0.8,
            'income_level' => 'low'
        ]
    ];
    
    echo "<div class='info'>
        <h3>📝 Adding Sample Data</h3>
        <p>Adding " . count($sampleData) . " sample records to user_preferences table...</p>
    </div>";
    
    $insertedCount = 0;
    $updatedCount = 0;
    
    foreach ($sampleData as $data) {
        try {
            // Check if user already exists
            $stmt = $pdo->prepare("SELECT id FROM user_preferences WHERE user_email = :email");
            $stmt->bindParam(':email', $data['user_email']);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                // Update existing record
                $stmt = $pdo->prepare("UPDATE user_preferences SET 
                    age = :age, gender = :gender, barangay = :barangay, municipality = :municipality, 
                    province = :province, weight_kg = :weight_kg, height_cm = :height_cm, bmi = :bmi,
                    risk_score = :risk_score, malnutrition_risk = :malnutrition_risk, 
                    screening_date = :screening_date, name = :name, birthday = :birthday, income = :income,
                    muac = :muac, screening_answers = :screening_answers, allergies = :allergies,
                    diet_prefs = :diet_prefs, avoid_foods = :avoid_foods, swelling = :swelling,
                    weight_loss = :weight_loss, feeding_behavior = :feeding_behavior, 
                    physical_signs = :physical_signs, dietary_diversity = :dietary_diversity,
                    clinical_risk_factors = :clinical_risk_factors, whz_score = :whz_score,
                    income_level = :income_level, updated_at = CURRENT_TIMESTAMP
                    WHERE user_email = :email");
                $updatedCount++;
            } else {
                // Insert new record
                $stmt = $pdo->prepare("INSERT INTO user_preferences (
                    user_email, age, gender, barangay, municipality, province, weight_kg, height_cm, bmi,
                    risk_score, malnutrition_risk, screening_date, name, birthday, income, muac,
                    screening_answers, allergies, diet_prefs, avoid_foods, swelling, weight_loss,
                    feeding_behavior, physical_signs, dietary_diversity, clinical_risk_factors,
                    whz_score, income_level, created_at, updated_at
                ) VALUES (
                    :email, :age, :gender, :barangay, :municipality, :province, :weight_kg, :height_cm, :bmi,
                    :risk_score, :malnutrition_risk, :screening_date, :name, :birthday, :income, :muac,
                    :screening_answers, :allergies, :diet_prefs, :avoid_foods, :swelling, :weight_loss,
                    :feeding_behavior, :physical_signs, :dietary_diversity, :clinical_risk_factors,
                    :whz_score, :income_level, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                )");
                $insertedCount++;
            }
            
            // Bind all parameters
            foreach ($data as $key => $value) {
                if ($key === 'user_email') {
                    $stmt->bindParam(':email', $value);
                } else {
                    $stmt->bindParam(':' . $key, $value);
                }
            }
            
            $stmt->execute();
            
        } catch (Exception $e) {
            echo "<div class='error'>
                <h3>❌ Error inserting data for {$data['user_email']}</h3>
                <p>Error: " . $e->getMessage() . "</p>
            </div>";
        }
    }
    
    echo "<div class='success'>
        <h3>✅ Sample Data Population Complete!</h3>
        <p><strong>Inserted:</strong> $insertedCount new records</p>
        <p><strong>Updated:</strong> $updatedCount existing records</p>
    </div>";
    
    // Verify the data
    echo "<div class='info'>
        <h3>🔍 Verifying Data</h3>";
    
    try {
        // Count total records
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM user_preferences");
        $stmt->execute();
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Count records with non-null risk_score
        $stmt = $pdo->prepare("SELECT COUNT(*) as with_risk FROM user_preferences WHERE risk_score IS NOT NULL");
        $stmt->execute();
        $withRisk = $stmt->fetch(PDO::FETCH_ASSOC)['with_risk'];
        
        // Get risk distribution
        $stmt = $pdo->prepare("SELECT 
            CASE 
                WHEN risk_score < 20 THEN 'low'
                WHEN risk_score < 50 THEN 'moderate'
                WHEN risk_score < 80 THEN 'high'
                ELSE 'severe'
            END as risk_level,
            COUNT(*) as count
            FROM user_preferences 
            WHERE risk_score IS NOT NULL 
            GROUP BY risk_level");
        $stmt->execute();
        $riskDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get barangay distribution
        $stmt = $pdo->prepare("SELECT barangay, COUNT(*) as count FROM user_preferences GROUP BY barangay");
        $stmt->execute();
        $barangayDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Total Records:</strong> $total</p>";
        echo "<p><strong>Records with Risk Score:</strong> $withRisk</p>";
        
        echo "<p><strong>Risk Distribution:</strong></p>";
        echo "<ul>";
        foreach ($riskDistribution as $risk) {
            echo "<li>{$risk['risk_level']}: {$risk['count']}</li>";
        }
        echo "</ul>";
        
        echo "<p><strong>Barangay Distribution:</strong></p>";
        echo "<ul>";
        foreach ($barangayDistribution as $barangay) {
            echo "<li>{$barangay['barangay']}: {$barangay['count']}</li>";
        }
        echo "</ul>";
        
    } catch (Exception $e) {
        echo "<p>❌ Error verifying data: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
    
    // Test the dashboard API endpoints
    echo "<div class='info'>
        <h3>🧪 Testing Dashboard API Endpoints</h3>";
    
    try {
        // Test community metrics
        $metrics = $db->getCommunityMetrics();
        echo "<p><strong>Community Metrics:</strong></p>";
        echo "<pre>" . json_encode($metrics, JSON_PRETTY_PRINT) . "</pre>";
        
        // Test risk distribution
        $risks = $db->getRiskDistribution();
        echo "<p><strong>Risk Distribution:</strong></p>";
        echo "<pre>" . json_encode($risks, JSON_PRETTY_PRINT) . "</pre>";
        
        // Test geographic distribution
        $geo = $db->getGeographicDistribution();
        echo "<p><strong>Geographic Distribution:</strong></p>";
        echo "<pre>" . json_encode($geo, JSON_PRETTY_PRINT) . "</pre>";
        
    } catch (Exception $e) {
        echo "<p>❌ Error testing API: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
    
    echo "<div class='success'>
        <h3>🎉 Sample Data Population Complete!</h3>
        <p>The user_preferences table now has realistic data for dashboard testing.</p>
        <p><strong>Next Steps:</strong></p>
        <ul>
            <li>Check the dashboard at: <a href='https://nutrisaur-production.up.railway.app/dash.php' target='_blank'>Dashboard</a></li>
            <li>Test the API endpoints: <a href='https://nutrisaur-production.up.railway.app/test_database_api.php' target='_blank'>API Test</a></li>
        </ul>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='error'>
        <h3>❌ Sample Data Population Failed</h3>
        <p><strong>Error:</strong> " . $e->getMessage() . "</p>
        <p><strong>File:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>
    </div>";
}

echo "</body></html>";
?>
