<?php
/**
 * Quick Populate Script
 * Fast way to add sample data and test the dashboard
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
    <title>Quick Populate Sample Data</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background-color: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
        .test-result { margin: 10px 0; padding: 10px; border-left: 4px solid #007bff; }
    </style>
</head>
<body>
    <h1>⚡ Quick Populate Sample Data</h1>
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
    
    // Quick sample data - just 3 records with proper risk scores
    $sampleData = [
        [
            'user_email' => 'test1@example.com',
            'age' => 25,
            'gender' => 'male',
            'barangay' => 'Bagac',
            'weight_kg' => '70.00',
            'height_cm' => '175.00',
            'bmi' => '22.86',
            'risk_score' => 35,
            'malnutrition_risk' => 'moderate',
            'name' => 'Test User 1',
            'income' => 'medium'
        ],
        [
            'user_email' => 'test2@example.com',
            'age' => 30,
            'gender' => 'female',
            'barangay' => 'Bagac',
            'weight_kg' => '55.00',
            'height_cm' => '160.00',
            'bmi' => '21.48',
            'risk_score' => 75,
            'malnutrition_risk' => 'high',
            'name' => 'Test User 2',
            'income' => 'low'
        ],
        [
            'user_email' => 'test3@example.com',
            'age' => 40,
            'gender' => 'male',
            'barangay' => 'Bagac',
            'weight_kg' => '90.00',
            'height_cm' => '170.00',
            'bmi' => '31.14',
            'risk_score' => 90,
            'malnutrition_risk' => 'severe',
            'name' => 'Test User 3',
            'income' => 'high'
        ]
    ];
    
    echo "<div class='info'>
        <h3>📝 Adding Quick Sample Data</h3>
        <p>Adding " . count($sampleData) . " test records...</p>
    </div>";
    
    $insertedCount = 0;
    
    foreach ($sampleData as $data) {
        try {
            // Check if user already exists
            $stmt = $pdo->prepare("SELECT id FROM user_preferences WHERE user_email = :email");
            $stmt->bindParam(':email', $data['user_email']);
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                // Insert new record
                $stmt = $pdo->prepare("INSERT INTO user_preferences (
                    user_email, age, gender, barangay, weight_kg, height_cm, bmi,
                    risk_score, malnutrition_risk, name, income, created_at, updated_at
                ) VALUES (
                    :email, :age, :gender, :barangay, :weight_kg, :height_cm, :bmi,
                    :risk_score, :malnutrition_risk, :name, :income, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                )");
                
                $stmt->bindParam(':email', $data['user_email']);
                $stmt->bindParam(':age', $data['age']);
                $stmt->bindParam(':gender', $data['gender']);
                $stmt->bindParam(':barangay', $data['barangay']);
                $stmt->bindParam(':weight_kg', $data['weight_kg']);
                $stmt->bindParam(':height_cm', $data['height_cm']);
                $stmt->bindParam(':bmi', $data['bmi']);
                $stmt->bindParam(':risk_score', $data['risk_score']);
                $stmt->bindParam(':malnutrition_risk', $data['malnutrition_risk']);
                $stmt->bindParam(':name', $data['name']);
                $stmt->bindParam(':income', $data['income']);
                
                $stmt->execute();
                $insertedCount++;
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>
                <h3>❌ Error inserting data for {$data['user_email']}</h3>
                <p>Error: " . $e->getMessage() . "</p>
            </div>";
        }
    }
    
    echo "<div class='success'>
        <h3>✅ Quick Sample Data Added!</h3>
        <p><strong>Inserted:</strong> $insertedCount new records</p>
    </div>";
    
    // Test API endpoints
    echo "<div class='info'>
        <h3>🧪 Testing API Endpoints</h3>
    </div>";
    
    // Test 1: Community Metrics
    echo "<div class='test-result'>
        <h4>Test 1: Community Metrics</h4>";
    try {
        $metrics = $db->getCommunityMetrics();
        echo "<p><strong>Result:</strong></p>";
        echo "<pre>" . json_encode($metrics, JSON_PRETTY_PRINT) . "</pre>";
        
        if ($metrics['total_users'] > 0) {
            echo "<p style='color: green;'>✅ SUCCESS: Total users = {$metrics['total_users']}</p>";
        } else {
            echo "<p style='color: red;'>❌ FAILED: No users found</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ ERROR: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    
    // Test 2: Risk Distribution
    echo "<div class='test-result'>
        <h4>Test 2: Risk Distribution</h4>";
    try {
        $risks = $db->getRiskDistribution();
        echo "<p><strong>Result:</strong></p>";
        echo "<pre>" . json_encode($risks, JSON_PRETTY_PRINT) . "</pre>";
        
        $totalRisk = $risks['low'] + $risks['moderate'] + $risks['high'] + $risks['severe'];
        if ($totalRisk > 0) {
            echo "<p style='color: green;'>✅ SUCCESS: Total risk cases = $totalRisk</p>";
        } else {
            echo "<p style='color: red;'>❌ FAILED: No risk data found</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ ERROR: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    
    // Test 3: Geographic Distribution
    echo "<div class='test-result'>
        <h4>Test 3: Geographic Distribution</h4>";
    try {
        $geo = $db->getGeographicDistribution();
        echo "<p><strong>Result:</strong></p>";
        echo "<pre>" . json_encode($geo, JSON_PRETTY_PRINT) . "</pre>";
        
        if (count($geo) > 0) {
            echo "<p style='color: green;'>✅ SUCCESS: Found " . count($geo) . " barangays</p>";
        } else {
            echo "<p style='color: red;'>❌ FAILED: No geographic data found</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ ERROR: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    
    // Test 4: API Endpoint Test
    echo "<div class='test-result'>
        <h4>Test 4: Live API Endpoint Test</h4>";
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://nutrisaur-production.up.railway.app/api/DatabaseAPI.php?action=community_metrics');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            echo "<p style='color: red;'>❌ cURL Error: $error</p>";
        } else {
            echo "<p><strong>HTTP Status:</strong> $httpCode</p>";
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                if ($data && isset($data['success'])) {
                    echo "<p style='color: green;'>✅ SUCCESS: API endpoint working</p>";
                    echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
                } else {
                    echo "<p style='color: red;'>❌ FAILED: Invalid JSON response</p>";
                    echo "<pre>$response</pre>";
                }
            } else {
                echo "<p style='color: red;'>❌ FAILED: HTTP $httpCode</p>";
                echo "<pre>$response</pre>";
            }
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ ERROR: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    
    echo "<div class='success'>
        <h3>🎉 Quick Test Complete!</h3>
        <p><strong>Next Steps:</strong></p>
        <ul>
            <li>Check the dashboard: <a href='https://nutrisaur-production.up.railway.app/dash.php' target='_blank'>Dashboard</a></li>
            <li>Test the API: <a href='https://nutrisaur-production.up.railway.app/test_database_api.php' target='_blank'>API Test</a></li>
            <li>Run full population: <a href='https://nutrisaur-production.up.railway.app/populate_sample_data.php' target='_blank'>Full Sample Data</a></li>
        </ul>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='error'>
        <h3>❌ Quick Test Failed</h3>
        <p><strong>Error:</strong> " . $e->getMessage() . "</p>
        <p><strong>File:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>
    </div>";
}

echo "</body></html>";
?>
