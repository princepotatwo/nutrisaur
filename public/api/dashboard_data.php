<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Use centralized session management
require_once __DIR__ . "/DatabaseAPI.php";
require_once __DIR__ . "/DatabaseHelper.php";
require_once __DIR__ . "/../../who_growth_standards.php";

// Include the functions from dash.php
require_once __DIR__ . "/../dash.php";

try {
    // Use DatabaseAPI for authentication and DatabaseHelper for data operations
    $dbAPI = DatabaseAPI::getInstance();
    $db = DatabaseHelper::getInstance();

    // Get barangay filter from request
    $barangay = $_GET['barangay'] ?? null;
    if ($barangay === 'all' || $barangay === '') {
        $barangay = null;
    }

    // Get all the dashboard data
    $nutritionalStatistics = getNutritionalStatistics($db, $barangay);
    $timeFrameData = getTimeFrameData($db, $barangay, $dbAPI);
    $detailedResponses = getDetailedScreeningResponsesForDash($db, $barangay);
    
    // Get WHO classification data for all standards
    $wfaData = getWHOClassificationData($db, $barangay, 'weight-for-age');
    $hfaData = getWHOClassificationData($db, $barangay, 'height-for-age');
    $wfhData = getWHOClassificationData($db, $barangay, 'weight-for-height');

    // Calculate dashboard metrics (same logic as in dash.php)
    $totalScreened = count($detailedResponses);
    $highRiskCases = 0;
    $samCases = 0;
    $severelyWasted = 0;

    // Count severe cases from WHO classifications
    if (isset($wfaData['data']['classifications'])) {
        foreach ($wfaData['data']['classifications'] as $classification => $count) {
            if (stripos($classification, 'severely underweight') !== false) {
                $highRiskCases += $count;
            }
        }
    }

    if (isset($hfaData['data']['classifications'])) {
        foreach ($hfaData['data']['classifications'] as $classification => $count) {
            if (stripos($classification, 'severely stunted') !== false) {
                $samCases += $count;
            }
        }
    }

    if (isset($wfhData['data']['classifications'])) {
        foreach ($wfhData['data']['classifications'] as $classification => $count) {
            if (stripos($classification, 'severely wasted') !== false) {
                $severelyWasted += $count;
            }
        }
    }

    // Prepare response data
    $dashboardData = [
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'data' => [
            'metrics' => [
                'total_screened' => $totalScreened,
                'high_risk_cases' => $highRiskCases,
                'sam_cases' => $samCases,
                'severely_wasted' => $severelyWasted
            ],
            'nutritional_statistics' => $nutritionalStatistics,
            'time_frame_data' => $timeFrameData,
            'detailed_responses' => $detailedResponses,
            'who_classifications' => [
                'weight_for_age' => $wfaData,
                'height_for_age' => $hfaData,
                'weight_for_height' => $wfhData
            ]
        ]
    ];

    echo json_encode($dashboardData);

} catch (Exception $e) {
    error_log("Dashboard API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch dashboard data',
        'message' => $e->getMessage()
    ]);
}
?>
