<?php
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/api/DatabaseAPI.php";

$db = new DatabaseAPI();

echo "=== Testing DatabaseAPI Endpoints ===\n\n";

// Test community_metrics
echo "Testing community_metrics...\n";
try {
    $result = $db->getCommunityMetrics();
    echo "✅ community_metrics: " . json_encode($result) . "\n";
} catch (Exception $e) {
    echo "❌ community_metrics error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test geographic_distribution
echo "Testing geographic_distribution...\n";
try {
    $result = $db->getGeographicDistribution();
    echo "✅ geographic_distribution: " . json_encode($result) . "\n";
} catch (Exception $e) {
    echo "❌ geographic_distribution error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test risk_distribution
echo "Testing risk_distribution...\n";
try {
    $result = $db->getRiskDistribution();
    echo "✅ risk_distribution: " . json_encode($result) . "\n";
} catch (Exception $e) {
    echo "❌ risk_distribution error: " . $e->getMessage() . "\n";
}

echo "\n=== End of Tests ===\n";
?>
