<?php
// Prevent any output before JSON
ob_start();

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Use centralized session management
require_once __DIR__ . "/DatabaseAPI.php";
require_once __DIR__ . "/DatabaseHelper.php";
require_once __DIR__ . "/../../who_growth_standards.php";

// Define the functions we need directly here to avoid HTML output
function getNutritionalStatistics($db, $barangay = null) {
    try {
        // Get users data
        $users = getScreeningResponsesByTimeFrame($db, $barangay);
        
        $stats = [
            'municipality_distribution' => [],
            'barangay_distribution' => [],
            'total_users' => count($users)
        ];
        
        // Process users for statistics
        foreach ($users as $user) {
            $municipality = $user['municipality'] ?? 'Unknown';
            $barangayName = $user['barangay'] ?? 'Unknown';
            
            if (!isset($stats['municipality_distribution'][$municipality])) {
                $stats['municipality_distribution'][$municipality] = 0;
            }
            $stats['municipality_distribution'][$municipality]++;
            
            if (!isset($stats['barangay_distribution'][$barangayName])) {
                $stats['barangay_distribution'][$barangayName] = 0;
            }
            $stats['barangay_distribution'][$barangayName]++;
        }
        
        return $stats;
    } catch (Exception $e) {
        error_log("Error in getNutritionalStatistics: " . $e->getMessage());
        return [
            'municipality_distribution' => [],
            'barangay_distribution' => [],
            'total_users' => 0
        ];
    }
}

function getScreeningResponsesByTimeFrame($db, $barangay = null) {
    try {
        $sql = "SELECT * FROM screening_responses";
        $params = [];
        
        if ($barangay) {
            $sql .= " WHERE barangay = ?";
            $params[] = $barangay;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error in getScreeningResponsesByTimeFrame: " . $e->getMessage());
        return [];
    }
}

function getTimeFrameData($db, $barangay = null, $dbAPI = null) {
    // Return basic time frame data
    return [
        'total_screened' => 0,
        'recent_registrations' => 0,
        'time_periods' => []
    ];
}

function getDetailedScreeningResponsesForDash($db, $barangay = null) {
    return getScreeningResponsesByTimeFrame($db, $barangay);
}

function getWHOClassificationData($db, $barangay = null, $whoStandard = 'weight-for-age') {
    try {
        // Get users data
        $users = getScreeningResponsesByTimeFrame($db, $barangay);
        
        $classifications = [
            'Severely Underweight' => 0,
            'Underweight' => 0,
            'Normal' => 0,
            'Overweight' => 0,
            'Obese' => 0,
            'No Data' => 0
        ];
        
        $totalUsers = count($users);
        $validClassifications = 0;
        
        foreach ($users as $user) {
            // Simple classification logic - in real implementation, this would use WHO standards
            $age = calculateAge($user['birthday']);
            $weight = floatval($user['weight']);
            $height = floatval($user['height']);
            
            if ($weight > 0 && $height > 0) {
                $bmi = $weight / (($height / 100) * ($height / 100));
                
                if ($bmi < 18.5) {
                    $classifications['Underweight']++;
                } elseif ($bmi < 25) {
                    $classifications['Normal']++;
                } elseif ($bmi < 30) {
                    $classifications['Overweight']++;
                } else {
                    $classifications['Obese']++;
                }
                $validClassifications++;
            } else {
                $classifications['No Data']++;
            }
        }
        
        return [
            'success' => true,
            'data' => [
                'classifications' => $classifications,
                'total' => $validClassifications,
                'total_users' => $totalUsers
            ]
        ];
    } catch (Exception $e) {
        error_log("Error in getWHOClassificationData: " . $e->getMessage());
        return [
            'success' => false,
            'data' => [
                'classifications' => [],
                'total' => 0,
                'total_users' => 0
            ]
        ];
    }
}

function calculateAge($birthday) {
    $birthDate = new DateTime($birthday);
    $today = new DateTime();
    $age = $today->diff($birthDate);
    return $age->y + ($age->m / 12) + ($age->d / 365);
}

// Clear any output that might have been generated
ob_clean();

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
