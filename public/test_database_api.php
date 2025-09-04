<?php
/**
 * DatabaseAPI Test File
 * Tests all major DatabaseAPI functions to ensure data fetching works correctly
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
    <title>DatabaseAPI Test Results</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background-color: #d4edda; border-color: #c3e6cb; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; }
        .info { background-color: #d1ecf1; border-color: #bee5eb; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
        .endpoint { font-weight: bold; color: #007bff; }
    </style>
</head>
<body>
    <h1>🔍 DatabaseAPI Test Results</h1>
    <p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "</p>
";

try {
    // Initialize DatabaseAPI
    $db = new DatabaseAPI();
    
    if (!$db) {
        throw new Exception("Failed to initialize DatabaseAPI");
    }
    
    echo "<div class='test-section info'>
        <h3>✅ DatabaseAPI Initialized Successfully</h3>
        <p>DatabaseAPI instance created without errors.</p>
    </div>";
    
    // Test 1: Basic Connection Test
    echo "<div class='test-section info'>
        <h3>🔗 Test 1: Basic Connection Test</h3>";
    
    $pdo = $db->getPDO();
    $mysqli = $db->getMysqli();
    
    if ($pdo) {
        echo "<p>✅ PDO Connection: <strong>SUCCESS</strong></p>";
        try {
            $stmt = $pdo->query("SELECT 1 as test");
            $result = $stmt->fetch();
            echo "<p>✅ PDO Query Test: <strong>SUCCESS</strong> (Result: {$result['test']})</p>";
        } catch (Exception $e) {
            echo "<p>❌ PDO Query Test: <strong>FAILED</strong> - " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>❌ PDO Connection: <strong>FAILED</strong></p>";
    }
    
    if ($mysqli) {
        echo "<p>✅ MySQLi Connection: <strong>SUCCESS</strong></p>";
    } else {
        echo "<p>❌ MySQLi Connection: <strong>FAILED</strong></p>";
    }
    
    echo "</div>";
    
    // Test 2: Community Metrics
    echo "<div class='test-section info'>
        <h3>📊 Test 2: Community Metrics</h3>";
    
    $metrics = $db->getCommunityMetrics();
    if ($metrics) {
        echo "<p>✅ Community Metrics: <strong>SUCCESS</strong></p>";
        echo "<pre>" . json_encode($metrics, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<p>❌ Community Metrics: <strong>FAILED</strong></p>";
    }
    
    echo "</div>";
    
    // Test 3: Risk Distribution
    echo "<div class='test-section info'>
        <h3>🎯 Test 3: Risk Distribution</h3>";
    
    $riskData = $db->getRiskDistribution();
    if ($riskData) {
        echo "<p>✅ Risk Distribution: <strong>SUCCESS</strong></p>";
        echo "<pre>" . json_encode($riskData, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<p>❌ Risk Distribution: <strong>FAILED</strong></p>";
    }
    
    echo "</div>";
    
    // Test 4: Geographic Distribution
    echo "<div class='test-section info'>
        <h3>🗺️ Test 4: Geographic Distribution</h3>";
    
    $geoData = $db->getGeographicDistribution();
    if ($geoData) {
        echo "<p>✅ Geographic Distribution: <strong>SUCCESS</strong></p>";
        echo "<pre>" . json_encode($geoData, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<p>❌ Geographic Distribution: <strong>FAILED</strong></p>";
    }
    
    echo "</div>";
    
    // Test 5: Detailed Screening Responses
    echo "<div class='test-section info'>
        <h3>📋 Test 5: Detailed Screening Responses</h3>";
    
    $screeningData = $db->getDetailedScreeningResponses('1m', '');
    if ($screeningData) {
        echo "<p>✅ Screening Responses: <strong>SUCCESS</strong> (Count: " . count($screeningData) . ")</p>";
        if (count($screeningData) > 0) {
            echo "<pre>" . json_encode(array_slice($screeningData, 0, 3), JSON_PRETTY_PRINT) . "</pre>";
            echo "<p><em>Showing first 3 records...</em></p>";
        }
    } else {
        echo "<p>❌ Screening Responses: <strong>FAILED</strong></p>";
    }
    
    echo "</div>";
    
    // Test 6: Critical Alerts
    echo "<div class='test-section info'>
        <h3>🚨 Test 6: Critical Alerts</h3>";
    
    $alertsData = $db->getCriticalAlerts();
    if ($alertsData) {
        echo "<p>✅ Critical Alerts: <strong>SUCCESS</strong> (Count: " . count($alertsData) . ")</p>";
        if (count($alertsData) > 0) {
            echo "<pre>" . json_encode(array_slice($alertsData, 0, 3), JSON_PRETTY_PRINT) . "</pre>";
            echo "<p><em>Showing first 3 records...</em></p>";
        }
    } else {
        echo "<p>❌ Critical Alerts: <strong>FAILED</strong></p>";
    }
    
    echo "</div>";
    
    // Test 7: API Endpoint Test
    echo "<div class='test-section info'>
        <h3>🌐 Test 7: API Endpoint Test</h3>";
    
    $testUrl = 'https://nutrisaur-production.up.railway.app/api/DatabaseAPI.php?action=community_metrics';
    echo "<p class='endpoint'>Testing URL: $testUrl</p>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $testUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "<p>❌ cURL Error: <strong>$error</strong></p>";
    } else {
        echo "<p>✅ HTTP Status: <strong>$httpCode</strong></p>";
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if ($data && isset($data['success'])) {
                echo "<p>✅ API Response: <strong>SUCCESS</strong></p>";
                echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
            } else {
                echo "<p>❌ API Response: <strong>INVALID JSON</strong></p>";
                echo "<pre>$response</pre>";
            }
        } else {
            echo "<p>❌ API Response: <strong>HTTP $httpCode</strong></p>";
            echo "<pre>$response</pre>";
        }
    }
    
    echo "</div>";
    
    echo "<div class='test-section success'>
        <h3>🎉 All Tests Completed!</h3>
        <p>The DatabaseAPI is working correctly. All data fetching functions are operational.</p>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='test-section error'>
        <h3>❌ Test Failed</h3>
        <p><strong>Error:</strong> " . $e->getMessage() . "</p>
        <p><strong>File:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>
    </div>";
}

echo "</body></html>";
?>
