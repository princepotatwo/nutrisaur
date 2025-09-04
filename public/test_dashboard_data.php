<?php
// Test Dashboard Data Processing
// Access this at: /test_dashboard_data

header('Content-Type: application/json');

require_once __DIR__ . "/config.php";
require_once __DIR__ . "/api/DatabaseAPI.php";

$db = DatabaseAPI::getInstance();

// Test the exact same data the dashboard receives
$test_data = [
    'community_metrics' => $db->getCommunityMetrics(),
    'risk_distribution' => $db->getRiskDistribution(),
    'geographic_distribution' => $db->getGeographicDistribution(),
    'critical_alerts' => $db->getCriticalAlerts(),
    'detailed_screening_responses' => $db->getDetailedScreeningResponses()
];

echo json_encode([
    'success' => true,
    'message' => 'Dashboard data test',
    'timestamp' => date('Y-m-d H:i:s'),
    'data' => $test_data
], JSON_PRETTY_PRINT);
?>
