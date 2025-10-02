<?php
// Use centralized session management
require_once __DIR__ . "/api/DatabaseAPI.php";
require_once __DIR__ . "/api/DatabaseHelper.php";
require_once __DIR__ . "/../who_growth_standards.php";

// Function to get adult BMI classification (for children over 71 months)
// This is only used as fallback for children over 71 months when WHO standards don't apply
function getAdultBMIClassification($bmi) {
    if ($bmi < 18.5) return 'Underweight';
    if ($bmi < 25) return 'Normal weight';
    if ($bmi < 30) return 'Overweight';
    return 'Obese';
}

// Use DatabaseAPI for authentication and DatabaseHelper for data operations
$dbAPI = DatabaseAPI::getInstance();
$db = DatabaseHelper::getInstance();

// Helper function to calculate age
function calculateAge($birthday) {
    $birthDate = new DateTime($birthday);
    $today = new DateTime();
    $age = $today->diff($birthDate);
    return $age->y + ($age->m / 12) + ($age->d / 365);
}

// Helper function to get nutritional assessment using WHO Growth Standards
function getNutritionalAssessment($user) {
    try {
        $who = new WHOGrowthStandards();
        
        // Calculate age in months for WHO standards using screening date (same as screening.php)
        $birthDate = new DateTime($user['birthday']);
        $screeningDate = new DateTime($user['screening_date'] ?? date('Y-m-d H:i:s'));
        $age = $birthDate->diff($screeningDate);
        $ageInMonths = ($age->y * 12) + $age->m;
        
        // Get comprehensive WHO Growth Standards assessment
        $assessment = $who->getComprehensiveAssessment(
            floatval($user['weight']), 
            floatval($user['height']), 
            $user['birthday'], 
            $user['sex'],
            $user['screening_date'] ?? null
        );
        
        if ($assessment['success']) {
            // Convert WHO Growth Standards format to expected format
            return [
                'nutritional_status' => $assessment['nutritional_status'] ?? 'Normal',
                'risk_level' => $assessment['nutritional_risk'] ?? 'Low',
                'category' => $assessment['category'] ?? 'Normal',
                'bmi' => $assessment['bmi'] ?? 0,
                'age' => $ageInMonths / 12, // Convert to years
                'weight' => floatval($user['weight']),
                'height' => floatval($user['height']),
                'who_classifications' => $assessment['results'] ?? []
            ];
        } else {
            return [
                'nutritional_status' => 'Assessment Error',
                'risk_level' => 'Unknown',
                'category' => 'Error',
                'bmi' => 0,
                'age' => $ageInMonths / 12,
                'weight' => floatval($user['weight']),
                'height' => floatval($user['height']),
                'who_classifications' => []
            ];
        }
    } catch (Exception $e) {
        error_log("WHO Growth Standards error: " . $e->getMessage());
        return [
            'nutritional_status' => 'Assessment Error',
            'risk_level' => 'Unknown',
            'category' => 'Error',
            'bmi' => 0,
            'age' => 0,
            'weight' => floatval($user['weight']),
            'height' => floatval($user['height']),
            'who_classifications' => []
        ];
    }
}

// Function to get nutritional statistics based on actual data
function getNutritionalStatistics($db, $barangay = null) {
    try {
        error_log("🔍 Nutritional Statistics Debug - Starting");
        error_log("  - Barangay: " . ($barangay ?: 'null'));
        
        // Get users data using the same method as other functions
        $users = getScreeningResponsesByTimeFrame($db, $barangay);
        error_log("  - Total users found: " . count($users));
        
        $stats = [
            'municipality_distribution' => [],
            'barangay_distribution' => [],
            'total_users' => count($users)
        ];
        
            foreach ($users as $user) {
    
    // Municipality Distribution
    if (!empty($user['municipality'])) {
                if (!isset($stats['municipality_distribution'][$user['municipality']])) {
                    $stats['municipality_distribution'][$user['municipality']] = 0;
        }
                $stats['municipality_distribution'][$user['municipality']]++;
    }
    
    // Barangay Distribution
    if (!empty($user['barangay'])) {
                if (!isset($stats['barangay_distribution'][$user['barangay']])) {
                    $stats['barangay_distribution'][$user['barangay']] = 0;
                }
                $stats['barangay_distribution'][$user['barangay']]++;
            }
        }
        
        // Sort municipality distribution by count (descending)
        arsort($stats['municipality_distribution']);
        
        // Sort barangay distribution by count (descending)
        arsort($stats['barangay_distribution']);
        
        error_log("🔍 Nutritional Statistics generated successfully");
        return [
            'success' => true,
            'statistics' => $stats,
            'barangay' => $barangay
        ];
        
    } catch (Exception $e) {
        error_log("Error getting nutritional statistics: " . $e->getMessage());
        return [
            'success' => false,
            'statistics' => [],
            'total_users' => 0,
            'barangay' => $barangay
        ];
    }
}

// Helper functions for API-compatible WHO classification logic

function getDetailedScreeningResponsesForDash($db, $barangay = null) {
    // This function replicates the API's getDetailedScreeningResponses method
    try {
        $whereClause = "WHERE 1=1";
        $params = [];
        
        // Add barangay filter
        if (!empty($barangay)) {
            $whereClause .= " AND barangay = :barangay";
            $params[':barangay'] = $barangay;
        }
        
        $stmt = $db->pdo->prepare("
            SELECT 
                cu.*,
                cu.email as user_email,
                DATE_FORMAT(cu.screening_date, '%Y-%m-%d') as screening_date
            FROM community_users cu
            $whereClause
            ORDER BY cu.screening_date DESC
            LIMIT 100
        ");
        $stmt->execute($params);
        $rawData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $rawData;
    } catch (PDOException $e) {
        error_log("Detailed screening responses error: " . $e->getMessage());
        return [];
    }
}

function getInitialClassifications($whoStandard) {
    // Initialize classifications based on WHO standard (matching API logic)
    switch ($whoStandard) {
        case 'weight-for-age':
            return [
                'Severely Underweight' => 0,
                'Underweight' => 0,
                'Normal' => 0,
                'Overweight' => 0,
                'Obese' => 0
            ];
        case 'bmi-for-age':
            return [
                'Underweight' => 0,
                'Normal' => 0,
                'Overweight' => 0,
                'Obese' => 0
            ];
        case 'height-for-age':
            return [
                'Severely Stunted' => 0,
                'Stunted' => 0,
                'Normal' => 0,
                'Tall' => 0
            ];
        case 'weight-for-height':
            return [
                'Severely Wasted' => 0,
                'Wasted' => 0,
                'Normal' => 0,
                'Overweight' => 0,
                'Obese' => 0
            ];
        case 'bmi-adult':
            return [
                'Severely Underweight' => 0,
                'Underweight' => 0,
                'Normal' => 0,
                'Overweight' => 0,
                'Obese' => 0
            ];
        default:
            return [
                'Underweight' => 0,
                'Normal' => 0,
                'Overweight' => 0,
                'Obese' => 0
            ];
    }
}

function getAgeRestrictions($whoStandard) {
    // Age restrictions (matching API logic)
    switch ($whoStandard) {
        case 'weight-for-age':
        case 'height-for-age':
            return ['min' => 0, 'max' => 71]; // 0-71 months (0-5.9 years)
        case 'weight-for-height':
            return ['min' => 0, 'max' => 60]; // 0-60 months (0-5 years)
        case 'bmi-for-age':
            return ['min' => 60, 'max' => 228]; // 60-228 months (5-19 years)
        case 'bmi-adult':
            return ['min' => 228, 'max' => 999]; // 228+ months (19+ years)
        default:
            return ['min' => 0, 'max' => 999];
    }
}

function processWHOStandardForDash(&$classifications, $results, $whoStandard, $ageInMonths, $user, $ageRestrictions) {
    // Age restrictions (matching API logic)
    if ($ageInMonths < $ageRestrictions['min'] || $ageInMonths > $ageRestrictions['max']) {
        // Skip users outside age range - don't add to classifications
        return;
    }
    
    // Get classification from results (matching API logic)
    $classification = 'No Data';
    $standardKey = str_replace('-', '_', $whoStandard);
    
    if (isset($results[$standardKey])) {
        $classification = $results[$standardKey]['classification'] ?? 'No Data';
    }
    
    // Count classification (matching API logic) - only if valid classification
    if (isset($classifications[$classification]) && $classification !== 'No Data') {
        $classifications[$classification]++;
    }
    // Skip "No Data" classifications - don't add them
}

// UPDATED: Function to get WHO classification data using API logic
function getWHOClassificationData($db, $barangay = null, $whoStandard = 'weight-for-age') {
    try {
        error_log("🔍 WHO Classification Debug - Using API Logic");
        error_log("  - Barangay: " . ($barangay ?: 'null'));
        error_log("  - WHO Standard: $whoStandard");
        
        // Get users data using the same method as API (getDetailedScreeningResponses)
        $users = getDetailedScreeningResponsesForDash($db, $barangay);
        error_log("  - Total users found: " . count($users));
        
        // Initialize classifications based on WHO standard (matching API logic)
        $classifications = getInitialClassifications($whoStandard);
        
        // Age restrictions (matching API logic)
        $ageRestrictions = getAgeRestrictions($whoStandard);
        
        // Single WHO instance for all calculations (matching API logic)
        require_once __DIR__ . '/../who_growth_standards.php';
        $who = new WHOGrowthStandards();
        
        $totalProcessed = 0;
        $totalUsers = count($users);
        
        // Process all users (matching API logic)
        foreach ($users as $user) {
            try {
                // Calculate age once using WHO instance (matching API logic)
                $ageInMonths = $who->calculateAgeInMonths($user['birthday'], $user['screening_date'] ?? null);
                
                // Get comprehensive assessment once for all standards (matching API logic)
                $assessment = $who->getComprehensiveAssessment(
                    floatval($user['weight']),
                    floatval($user['height']),
                    $user['birthday'],
                    $user['sex'],
                    $user['screening_date'] ?? null
                );
                
                if ($assessment['success'] && isset($assessment['results'])) {
                    $results = $assessment['results'];
                    
                    // Process WHO standard with proper age filtering (matching API logic)
                    processWHOStandardForDash($classifications, $results, $whoStandard, $ageInMonths, $user, $ageRestrictions);
                    
                    $totalProcessed++;
                } else {
                    // Skip users with failed assessment - don't add to classifications
                }
            } catch (Exception $e) {
                error_log("WHO processing error for user {$user['email']}: " . $e->getMessage());
                // Skip users with processing errors - don't add to classifications
            }
        }
        
        error_log("📊 Final WHO Classification Results:");
        error_log("  - Underweight: " . $classifications['Underweight']);
        error_log("  - Normal: " . $classifications['Normal']);
        error_log("  - Overweight: " . $classifications['Overweight']);
        error_log("  - Obese: " . $classifications['Obese']);
        error_log("  - Total Users: " . count($users));
        
        return [
            'success' => true,
            'classifications' => $classifications,
            'total_users' => count($users),
            'who_standard' => $whoStandard
        ];
        
    } catch (Exception $e) {
        error_log("Error getting WHO classification data: " . $e->getMessage());
        return [
            'success' => false,
            'classifications' => [
                'Underweight' => 0,
                'Normal' => 0,
                'Overweight' => 0,
                'Obese' => 0
            ],
            'total_users' => 0,
            'who_standard' => $whoStandard
        ];
    }
}

// Check if user is logged in using centralized method
if (!$dbAPI->isUserLoggedIn()) {
    // Simple debug to see what's in the session
    $sessionData = $dbAPI->getCurrentUserSession();
    error_log("Dash.php - Session data: " . json_encode($sessionData));
    error_log("Dash.php - Session ID: " . session_id());
    header('Location: /home');
    exit;
}

$currentUser = $dbAPI->getCurrentUserSession();
if (!$currentUser) {
    error_log("Dash.php - Failed to get current user session");
    header("Location: /home");
    exit();
}

// PHP Date formatting function for user-friendly time display
function formatTimeAgoPHP($dateString) {
    $now = new DateTime();
    $date = new DateTime($dateString);
    $diff = $now->diff($date);
    
    if ($diff->y > 0) {
        return $diff->y == 1 ? '1 year ago' : $diff->y . ' years ago';
    } elseif ($diff->m > 0) {
        return $diff->m == 1 ? '1 month ago' : $diff->m . ' months ago';
    } elseif ($diff->d > 0) {
        if ($diff->d >= 7) {
            $weeks = floor($diff->d / 7);
            return $weeks == 1 ? '1 week ago' : $weeks . ' weeks ago';
        } else {
            return $diff->d == 1 ? '1 day ago' : $diff->d . ' days ago';
        }
    } elseif ($diff->h > 0) {
        return $diff->h == 1 ? '1 hour ago' : $diff->h . ' hours ago';
    } elseif ($diff->i > 0) {
        return $diff->i == 1 ? '1 minute ago' : $diff->i . ' minutes ago';
    } else {
        return 'Just now';
    }
}

// Enhanced PHP date formatting with more options
function formatDatePHP($dateString, $format = 'relative') {
    $date = new DateTime($dateString);
    
    switch ($format) {
        case 'relative':
            return formatTimeAgoPHP($dateString);
        case 'short':
            return $date->format('M j, Y');
        case 'long':
            return $date->format('l, F j, Y');
        case 'time':
            return $date->format('g:i A');
        case 'datetime':
            return $date->format('M j, Y g:i A');
        default:
            return $date->format('Y-m-d');
    }
}

// NEW: Function to get data from community_users table with nutritional assessments
function getTimeFrameData($db, $barangay = null, $dbAPI = null) {
    error_log("🔍 getTimeFrameData Debug - Starting");
    error_log("  - Barangay: " . ($barangay ?: 'null'));
    
    try {
        // Build the WHERE clause for DatabaseHelper - no time filtering
        $whereClause = "1=1"; // Get all users
        $params = [];
        
        if ($barangay && $barangay !== '') {
            $whereClause .= " AND barangay = ?";
            $params[] = $barangay;
        }
        
        // Use DatabaseHelper like screening.php
        error_log("🔍 getTimeFrameData Debug - Querying database");
        error_log("  - WHERE clause: $whereClause");
        error_log("  - Params: " . json_encode($params));
        
        $result = $db->select('community_users', '*', $whereClause, $params, 'screening_date DESC');
        
        error_log("  - Query result success: " . ($result['success'] ? 'YES' : 'NO'));
        error_log("  - Query result message: " . ($result['message'] ?? 'No message'));
        error_log("  - Number of users found: " . (isset($result['data']) ? count($result['data']) : 'NO DATA'));
        
        if (!$result['success']) {
            error_log("Error fetching community_users: " . ($result['message'] ?? 'Unknown error'));
            return [
                'total_screened' => 0,
                'high_risk_cases' => 0,
                'sam_cases' => 0,
                'critical_muac' => 0,
                'barangays_covered' => 0,
                'time_frame' => 'all',
                'start_date_formatted' => 'All Time',
                'end_date_formatted' => 'Present'
            ];
        }
        
        $users = $result['data'] ?? [];
        
        // Use the same logic as donut chart - call DatabaseAPI for each WHO standard
        $totalScreened = count($users);
        $highRiskCases = 0; // Count of Severely Underweight
        $samCases = 0; // Count of Severely Stunted
        $severelyWasted = 0; // Count of Severely Wasted
        $barangaysCovered = [];
        
        // Get Weight-for-Age classifications (for Severely Underweight)
        $wfaData = $dbAPI->getWHOClassifications('weight-for-age', '1d', $barangay);
        if ($wfaData['success'] && isset($wfaData['data']['classifications'])) {
            $highRiskCases = $wfaData['data']['classifications']['Severely Underweight'] ?? 0;
        }
        
        // Get Height-for-Age classifications (for Severely Stunted)
        $hfaData = $dbAPI->getWHOClassifications('height-for-age', '1d', $barangay);
        if ($hfaData['success'] && isset($hfaData['data']['classifications'])) {
            $samCases = $hfaData['data']['classifications']['Severely Stunted'] ?? 0;
        }
        
        // Get Weight-for-Height classifications (for Severely Wasted)
        $wfhData = $dbAPI->getWHOClassifications('weight-for-height', '1d', $barangay);
        if ($wfhData['success'] && isset($wfhData['data']['classifications'])) {
            $severelyWasted = $wfhData['data']['classifications']['Severely Wasted'] ?? 0;
            }
            
            // Track barangays
        foreach ($users as $user) {
            if ($user['barangay']) {
                $barangaysCovered[] = $user['barangay'];
            }
        }
        
        // Log to browser console for debugging
        echo "<script>console.log('🔍 Dashboard Metrics - Using donut chart logic:');</script>";
        echo "<script>console.log('  - Total Screened: $totalScreened');</script>";
        echo "<script>console.log('  - Severely Underweight (WFA): $highRiskCases');</script>";
        echo "<script>console.log('  - Severely Stunted (HFA): $samCases');</script>";
        echo "<script>console.log('  - Severely Wasted (WFH): $severelyWasted');</script>";
        
        // Log full classification objects
        if (isset($wfaData['data']['classifications'])) {
            echo "<script>console.log('🔍 WFA Classifications:', " . json_encode($wfaData['data']['classifications']) . ");</script>";
        }
        if (isset($hfaData['data']['classifications'])) {
            echo "<script>console.log('🔍 HFA Classifications:', " . json_encode($hfaData['data']['classifications']) . ");</script>";
        }
        if (isset($wfhData['data']['classifications'])) {
            echo "<script>console.log('🔍 WFH Classifications:', " . json_encode($wfhData['data']['classifications']) . ");</script>";
        }
        
        $data = [
            'total_screened' => $totalScreened,
            'high_risk_cases' => $highRiskCases,
            'sam_cases' => $samCases,
            'critical_muac' => $severelyWasted,
            'barangays_covered' => count(array_unique($barangaysCovered)),
            'earliest_screening' => $totalScreened > 0 ? $users[count($users)-1]['screening_date'] : null,
            'latest_update' => $totalScreened > 0 ? $users[0]['screening_date'] : null
        ];
        
        // Add time frame info (simplified since we're getting all users)
        $data['time_frame'] = 'all';
        $data['start_date'] = null;
        $data['end_date'] = null;
        $data['start_date_formatted'] = 'All Time';
        $data['end_date_formatted'] = 'Present';
        
        return $data;
        
    } catch (PDOException $e) {
        error_log("Error getting time frame data: " . $e->getMessage());
        return [
            'total_screened' => 0,
            'high_risk_cases' => 0,
            'sam_cases' => 0,
            'critical_muac' => 0,
            'barangays_covered' => 0,
            'time_frame' => 'all',
            'start_date_formatted' => 'All Time',
            'end_date_formatted' => 'Present'
        ];
    }
}

// NEW: Function to get screening responses by time frame using community_users with assessments
function getScreeningResponsesByTimeFrame($db, $barangay = null) {
    $now = new DateTime();
    $startDateStr = 'All Time';
    $endDateStr = $now->format('Y-m-d H:i:s');
    
    try {
        // Build the WHERE clause for DatabaseHelper - get all data
        $whereClause = "1=1";
        $params = [];
        
        if ($barangay && $barangay !== '') {
            $whereClause .= " AND barangay = ?";
            $params[] = $barangay;
        }
        
        // Use DatabaseHelper like screening.php
        $result = $db->select('community_users', '*', $whereClause, $params, 'screening_date DESC');
        
        if (!$result['success']) {
            error_log("Error fetching community_users: " . ($result['message'] ?? 'Unknown error'));
            return [];
        }
        
        $users = $result['data'] ?? [];
        
        // Calculate distributions using nutritional assessments
        $ageGroups = [
            'Under 1 year' => 0,
            '1-5 years' => 0,
            '6-12 years' => 0,
            '13-17 years' => 0,
            '18-59 years' => 0,
            '60+ years' => 0
        ];
        
        $riskLevels = [
            'Low' => 0,
            'Low-Medium' => 0,
            'Medium' => 0,
            'High' => 0,
            'Very High' => 0
        ];
        
        $nutritionalStatus = [
            'Normal' => 0,
            'Underweight' => 0,
            'Overweight' => 0,
            'Obesity' => 0,
            'Severe Acute Malnutrition' => 0,
            'Moderate Acute Malnutrition' => 0,
            'Stunting' => 0,
            'Maternal Undernutrition' => 0
        ];
        
        foreach ($users as $user) {
            $assessment = getNutritionalAssessment($user);
            $age = calculateAge($user['birthday']);
            
            // Age groups
            if ($age < 1) {
                $ageGroups['Under 1 year']++;
            } elseif ($age < 6) {
                $ageGroups['1-5 years']++;
            } elseif ($age < 13) {
                $ageGroups['6-12 years']++;
            } elseif ($age < 18) {
                $ageGroups['13-17 years']++;
            } elseif ($age < 60) {
                $ageGroups['18-59 years']++;
            } else {
                $ageGroups['60+ years']++;
            }
            
            // Risk levels
            $riskLevel = $assessment['risk_level'];
            if (isset($riskLevels[$riskLevel])) {
                $riskLevels[$riskLevel]++;
            }
            
            // Nutritional status
            $status = $assessment['nutritional_status'];
            if (strpos($status, 'Normal') !== false) {
                $nutritionalStatus['Normal']++;
            } elseif (strpos($status, 'Underweight') !== false) {
                $nutritionalStatus['Underweight']++;
            } elseif (strpos($status, 'Overweight') !== false) {
                $nutritionalStatus['Overweight']++;
            } elseif (strpos($status, 'Obesity') !== false) {
                $nutritionalStatus['Obesity']++;
            } elseif (strpos($status, 'Severe Acute Malnutrition') !== false) {
                $nutritionalStatus['Severe Acute Malnutrition']++;
            } elseif (strpos($status, 'Moderate Acute Malnutrition') !== false) {
                $nutritionalStatus['Moderate Acute Malnutrition']++;
            } elseif (strpos($status, 'Stunting') !== false) {
                $nutritionalStatus['Stunting']++;
            } elseif (strpos($status, 'Maternal Undernutrition') !== false) {
                $nutritionalStatus['Maternal Undernutrition']++;
            }
        }
        
        // Convert age groups to the expected format
        $ageGroupsFormatted = [];
        foreach ($ageGroups as $ageGroup => $count) {
            $ageGroupsFormatted[] = [
                'age_group' => $ageGroup,
                'count' => $count
            ];
        }
        
        // Convert risk levels to the expected format
        $riskLevelsFormatted = [];
        foreach ($riskLevels as $riskLevel => $count) {
            $riskLevelsFormatted[] = [
                'risk_level' => $riskLevel,
                'count' => $count
            ];
        }
        
        // Convert nutritional status to the expected format
        $nutritionalStatusFormatted = [];
        foreach ($nutritionalStatus as $status => $count) {
            $nutritionalStatusFormatted[] = [
                'status' => $status,
                'count' => $count
            ];
        }
        
        return [
            'age_groups' => $ageGroupsFormatted,
            'risk_levels' => $riskLevelsFormatted,
            'nutritional_status' => $nutritionalStatusFormatted,
            'start_date' => $startDateStr,
            'end_date' => $endDateStr
        ];
        
    } catch (PDOException $e) {
        error_log("Error getting screening responses: " . $e->getMessage());
        return [
            'age_groups' => [],
            'risk_levels' => [],
            'nutritional_status' => [],
            'start_date' => $startDateStr,
            'end_date' => $endDateStr
        ];
    }
}

$userId = $currentUser['user_id'] ?? 'unknown';
$username = $currentUser['username'] ?? 'User';
$email = $currentUser['email'] ?? 'user@example.com';
$isAdmin = isset($currentUser['is_admin']) && $currentUser['is_admin'] === true;
$role = isset($currentUser['role']) ? $currentUser['role'] : 'user';

$profile = null;
try {
    // Use DatabaseHelper to get user profile
    $result = $db->query("
        SELECT u.*, up.* 
        FROM users u 
        LEFT JOIN user_preferences up ON u.email = up.user_email 
        WHERE u.user_id = ?
    ", [$userId]);
    
    if ($result['success'] && !empty($result['data'])) {
        $profile = $result['data'][0];
    }
} catch (Exception $e) {
    $profile = null;
}
        
        // Use DatabaseHelper to get nutrition goals
        $goalsResult = $db->select('nutrition_goals', '*', 'user_id = ?', [$userId]);
        
        if ($goalsResult['success'] && !empty($goalsResult['data'])) {
            $goals = $goalsResult['data'][0];
        }
        
        $currentBarangay = '';
        $timeFrameData = getTimeFrameData($db, $currentBarangay, $dbAPI);
        $screeningResponsesData = getScreeningResponsesByTimeFrame($db, $currentBarangay);
        $nutritionalStatistics = getNutritionalStatistics($db, $currentBarangay);
        
        // Get barangay list for dropdown using DatabaseHelper
        $barangayResult = $db->select('community_users', 'DISTINCT barangay', "barangay IS NOT NULL AND barangay != ''", [], 'barangay');
        $barangays = $barangayResult['success'] ? array_column($barangayResult['data'], 'barangay') : [];
        
        // Municipalities and Barangays data - Same as settings.php
        $municipalities = [
            'ABUCAY' => ['Bangkal', 'Calaylayan (Pob.)', 'Capitangan', 'Gabon', 'Laon (Pob.)', 'Mabatang', 'Omboy', 'Salian', 'Wawa (Pob.)'],
            'BAGAC' => ['Bagumbayan (Pob.)', 'Banawang', 'Binuangan', 'Binukawan', 'Ibaba', 'Ibis', 'Pag-asa (Wawa-Sibacan)', 'Parang', 'Paysawan', 'Quinawan', 'San Antonio', 'Saysain', 'Tabing-Ilog (Pob.)', 'Atilano L. Ricardo'],
            'CITY OF BALANGA (Capital)' => ['Bagumbayan', 'Cabog-Cabog', 'Munting Batangas (Cadre)', 'Cataning', 'Central', 'Cupang Proper', 'Cupang West', 'Dangcol (Bernabe)', 'Ibayo', 'Malabia', 'Poblacion', 'Pto. Rivas Ibaba', 'Pto. Rivas Itaas', 'San Jose', 'Sibacan', 'Camacho', 'Talisay', 'Tanato', 'Tenejero', 'Tortugas', 'Tuyo', 'Bagong Silang', 'Cupang North', 'Doña Francisca', 'Lote'],
            'DINALUPIHAN' => ['Bangal', 'Bonifacio (Pob.)', 'Burgos (Pob.)', 'Colo', 'Daang Bago', 'Dalao', 'Del Pilar (Pob.)', 'Gen. Luna (Pob.)', 'Gomez (Pob.)', 'Happy Valley', 'Kataasan', 'Layac', 'Luacan', 'Mabini Proper (Pob.)', 'Mabini Ext. (Pob.)', 'Magsaysay', 'Naparing', 'New San Jose', 'Old San Jose', 'Padre Dandan (Pob.)', 'Pag-asa', 'Pagalanggang', 'Pinulot', 'Pita', 'Rizal (Pob.)', 'Roosevelt', 'Roxas (Pob.)', 'Saguing', 'San Benito', 'San Isidro (Pob.)', 'San Pablo (Bulate)', 'San Ramon', 'San Simon', 'Santo Niño', 'Sapang Balas', 'Santa Isabel (Tabacan)', 'Torres Bugauen (Pob.)', 'Tucop', 'Zamora (Pob.)', 'Aquino', 'Bayan-bayanan', 'Maligaya', 'Payangan', 'Pentor', 'Tubo-tubo', 'Jose C. Payumo, Jr.'],
            'HERMOSA' => ['A. Rivera (Pob.)', 'Almacen', 'Bacong', 'Balsic', 'Bamban', 'Burgos-Soliman (Pob.)', 'Cataning (Pob.)', 'Culis', 'Daungan (Pob.)', 'Mabiga', 'Mabuco', 'Maite', 'Mambog - Mandama', 'Palihan', 'Pandatung', 'Pulo', 'Saba', 'San Pedro (Pob.)', 'Santo Cristo (Pob.)', 'Sumalo', 'Tipo', 'Judge Roman Cruz Sr. (Mandama)', 'Sacrifice Valley'],
            'LIMAY' => ['Alangan', 'Kitang I', 'Kitang 2 & Luz', 'Lamao', 'Landing', 'Poblacion', 'Reformista', 'Townsite', 'Wawa', 'Duale', 'San Francisco de Asis', 'St. Francis II'],
            'MARIVELES' => ['Alas-asin', 'Alion', 'Batangas II', 'Cabcaben', 'Lucanin', 'Baseco Country (Nassco)', 'Poblacion', 'San Carlos', 'San Isidro', 'Sisiman', 'Balon-Anito', 'Biaan', 'Camaya', 'Ipag', 'Malaya', 'Maligaya', 'Mt. View', 'Townsite'],
            'MORONG' => ['Binaritan', 'Mabayo', 'Nagbalayong', 'Poblacion', 'Sabang'],
            'ORANI' => ['Bagong Paraiso (Pob.)', 'Balut (Pob.)', 'Bayan (Pob.)', 'Calero (Pob.)', 'Paking-Carbonero (Pob.)', 'Centro II (Pob.)', 'Dona', 'Kaparangan', 'Masantol', 'Mulawin', 'Pag-asa', 'Palihan (Pob.)', 'Pantalan Bago (Pob.)', 'Pantalan Luma (Pob.)', 'Parang Parang (Pob.)', 'Centro I (Pob.)', 'Sibul', 'Silahis', 'Tala', 'Talimundoc', 'Tapulao', 'Tenejero (Pob.)', 'Tugatog', 'Wawa (Pob.)', 'Apollo', 'Kabalutan', 'Maria Fe', 'Puksuan', 'Tagumpay'],
            'ORION' => ['Arellano (Pob.)', 'Bagumbayan (Pob.)', 'Balagtas (Pob.)', 'Balut (Pob.)', 'Bantan', 'Bilolo', 'Calungusan', 'Camachile', 'Daang Bago (Pob.)', 'Daang Bilolo (Pob.)', 'Daang Pare', 'General Lim (Kaput)', 'Kapunitan', 'Lati (Pob.)', 'Lusungan (Pob.)', 'Puting Buhangin', 'Sabatan', 'San Vicente (Pob.)', 'Santo Domingo', 'Villa Angeles (Pob.)', 'Wakas (Pob.)', 'Wawa (Pob.)', 'Santa Elena'],
            'PILAR' => ['Ala-uli', 'Bagumbayan', 'Balut I', 'Balut II', 'Bantan Munti', 'Burgos', 'Del Rosario (Pob.)', 'Diwa', 'Landing', 'Liyang', 'Nagwaling', 'Panilao', 'Pantingan', 'Poblacion', 'Rizal (Pob.)', 'Santa Rosa', 'Wakas North', 'Wakas South', 'Wawa'],
            'SAMAL' => ['East Calaguiman (Pob.)', 'East Daang Bago (Pob.)', 'Ibaba (Pob.)', 'Imelda', 'Lalawigan', 'Palili', 'San Juan (Pob.)', 'San Roque (Pob.)', 'Santa Lucia', 'Sapa', 'Tabing Ilog', 'Gugo', 'West Calaguiman (Pob.)', 'West Daang Bago (Pob.)']
        ];

        // Geographic distribution will be loaded via JavaScript API call
        $geographicDistributionData = [];

        // Function to get geographic distribution - show ALL barangays from database
        function getGeographicDistributionData($db, $municipalities) {
            try {
                // Get all users from database
                $result = $db->select('community_users', '*', '', [], 'screening_date DESC');
                $users = $result['success'] ? $result['data'] : [];
                
                error_log("🔍 Geographic Distribution Debug:");
                error_log("  - Total users found: " . count($users));
                
                // Count users per barangay
                $barangayCounts = [];
                foreach ($users as $user) {
                    $barangay = $user['barangay'] ?? 'Unknown';
                    $municipality = $user['municipality'] ?? 'Unknown';
                    if (!isset($barangayCounts[$barangay])) {
                        $barangayCounts[$barangay] = [
                            'count' => 0,
                            'municipality' => $municipality
                        ];
                    }
                    $barangayCounts[$barangay]['count']++;
                }
                
                error_log("  - Barangays with users: " . json_encode($barangayCounts));
                
                // Create distribution data with ALL barangays from database (not just hardcoded ones)
                $distribution = [];
                foreach ($barangayCounts as $barangay => $data) {
                    if ($data['count'] > 0) {
                        $distribution[] = [
                            'barangay' => $barangay,
                            'municipality' => $data['municipality'],
                            'count' => $data['count']
                        ];
                    }
                }
                
                error_log("  - Distribution entries created: " . count($distribution));
                
                // Sort by count descending
                usort($distribution, function($a, $b) {
                    return $b['count'] - $a['count'];
                });
                
                error_log("  - Final distribution: " . json_encode($distribution));
                
                return $distribution;
            } catch (Exception $e) {
                error_log("Geographic distribution error: " . $e->getMessage());
                return [];
            }
        }

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: /home");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>NutriSaur Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js?v=<?php echo time(); ?>"></script>
</head>
<style>
/* Dark Theme - Default */
:root {
    --color-bg: #1A211A;
    --color-card: #2A3326;
    --color-highlight: #A1B454;
    --color-text: #FFFFFF;
    --color-accent1: #8CA86E;
    --color-accent2: #B5C88D;
    --color-accent3: #546048;
    --color-accent4: #C9D8AA;
    --color-danger: #CF8686;
    --color-warning: #E0C989;
    --color-border: rgba(161, 180, 84, 0.2);
    --color-shadow: rgba(0, 0, 0, 0.1);
    --color-hover: rgba(161, 180, 84, 0.08);
    --color-active: rgba(161, 180, 84, 0.15);
    
    /* Additional variables for age range controls */
    --card-bg: var(--color-card);
    --border-color: var(--color-border);
    --text-color: var(--color-text);
    --text-color-secondary: var(--color-accent2);
    --primary-color: var(--color-highlight);
    --input-bg: var(--color-card);
}

/* Light Theme - Light Greenish Colors */
.light-theme {
    --color-bg: #F0F7F0;
    --color-card: #FFFFFF;
    --color-highlight: #66BB6A;
    --color-text: #1B3A1B;
    --color-accent1: #81C784;
    --color-accent2: #4CAF50;
    --color-accent3: #2E7D32;
    --color-accent4: #A5D6A7;
    --color-danger: #E57373;
    --color-warning: #FFB74D;
    --color-border: #C8E6C9;
    
    /* Additional variables for age range controls */
    --card-bg: var(--color-card);
    --border-color: var(--color-border);
    --text-color: var(--color-text);
    --text-color-secondary: var(--color-accent3);
    --primary-color: var(--color-highlight);
    --input-bg: var(--color-card);
    --color-shadow: rgba(76, 175, 80, 0.1);
    --color-hover: rgba(76, 175, 80, 0.08);
    --color-active: rgba(76, 175, 80, 0.15);
}

/* Base navbar styles */
.navbar {
    position: fixed;
    top: 0;
    left: 0;
    width: 320px;
    height: 100vh;
    background-color: var(--color-card);
    box-shadow: 3px 0 15px rgba(0, 0, 0, 0.1);
    padding: 0;
    box-sizing: border-box;
    overflow-y: auto;
    z-index: 1000;
    display: flex;
    flex-direction: column;
    backdrop-filter: blur(10px);
    transition: transform 0.3s ease-in-out;
    transform: translateX(-280px); /* Show only 40px */
}

/* Base body styles */
body {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    transition: background-color 0.2s ease, 
                color 0.2s ease, 
                border-color 0.2s ease, 
                box-shadow 0.2s ease;
    min-height: 100vh;
    background-color: var(--color-bg);
    color: var(--color-text);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    padding-left: 40px;
    line-height: 1.6;
    letter-spacing: 0.2px;
}

/* Dashboard container */
.dashboard {
    max-width: calc(100% - 60px);
    width: 100%;
    margin: 0 auto;
    padding: 20px;
}

/* Navbar header styles */
.navbar-header {
    padding: 35px 25px;
    display: flex;
    align-items: center;
    border-bottom: 2px solid rgba(164, 188, 46, 0.15);
    background: linear-gradient(135deg, rgba(161, 180, 84, 0.05) 0%, transparent 100%);
    position: relative;
    overflow: hidden;
}

.navbar-header::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(161, 180, 84, 0.3), transparent);
}

/* Navbar menu styles */
.navbar-menu {
    flex: 1;
    padding: 30px 0;
}

.navbar ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.navbar li {
    margin-bottom: 2px;
    position: relative;
    transition: all 0.3s ease;
}

.navbar li:hover {
    transform: translateX(5px);
}

.navbar li:not(:last-child) {
    border-bottom: 1px solid rgba(161, 180, 84, 0.08);
}

/* Navbar footer styles */
.navbar-footer {
    padding: 25px;
    text-align: center;
    border-top: 2px solid rgba(164, 188, 46, 0.15);
    background: linear-gradient(135deg, transparent 0%, rgba(161, 180, 84, 0.03) 100%);
    position: relative;
    overflow: hidden;
}

.navbar-footer::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(161, 180, 84, 0.3), transparent);
}

.navbar-footer div:first-child {
    font-weight: 600;
    color: var(--color-highlight);
    margin-bottom: 8px;
}

/* Header styles */
header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.dashboard-header {
    /* Removed card styling - no background, padding, border-radius, or box-shadow */
    padding: 0;
    margin-bottom: 20px;
}

.dashboard-header h1 {
    margin: 0;
    color: var(--color-text);
    font-size: 36px;
    font-weight: 700;
    line-height: 1.2;
}

/* User info styles */
.user-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: var(--color-accent3);
    display: flex;
    justify-content: center;
    align-items: center;
    color: var(--color-text);
    font-weight: bold;
}

/* Theme toggle button - OLD STYLES REMOVED */

/* Filter section styles */
.filter-section {
    background: var(--color-card);
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

/* Critical Alerts - Simple and Clean Design */

/* New theme toggle button design */
.new-theme-toggle-btn {
    background: #FF9800;
    border: none;
    color: #FFFFFF;
    padding: 10px 12px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 44px;
    height: 44px;
}

.new-theme-toggle-btn:hover {
    background: #F57C00;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
}

.new-theme-toggle-btn .new-theme-icon {
    font-size: 18px;
    transition: all 0.3s ease;
}

/* Light theme - black button with white icon */
.light-theme .new-theme-toggle-btn {
    background: #000000;
}

.light-theme .new-theme-toggle-btn:hover {
    background: #333333;
}

.light-theme .new-theme-toggle-btn .new-theme-icon {
    color: #FFFFFF !important;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.filter-group label {
    font-weight: 600;
    color: var(--color-text);
    min-width: 120px;
}

.custom-select-container {
    position: relative;
    min-width: 200px;
}

.custom-select-container.small-width {
    min-width: 180px;
    max-width: 200px;
}

.select-header {
    background-color: var(--color-card);
    border: 2px solid var(--color-border);
    border-radius: 8px;
    padding: 10px 15px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s ease;
    color: var(--color-text);
}

.select-header:hover {
    border-color: var(--color-highlight);
}

.dropdown-arrow {
    transition: transform 0.3s ease;
}

.dropdown-content {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: var(--color-card);
    border: 2px solid var(--color-border);
    border-radius: 8px;
    max-height: 300px;
    overflow-y: auto;
    z-index: 1000;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.filter-container .dropdown-content {
    z-index: 1002;
    position: absolute;
    max-width: 250px;
    max-height: 200px;
    overflow-y: auto;
    overflow-x: hidden;
}

.filter-container .custom-select-container {
    position: relative;
    overflow: visible;
}

.filter-container .custom-select-container .dropdown-content {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    max-width: 100%;
    max-height: 250px;
    overflow-y: auto;
    overflow-x: hidden;
    z-index: 1003;
    background-color: var(--color-card);
    border: 2px solid var(--color-border);
    border-radius: 8px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.filter-container .search-container {
    position: sticky;
    top: 0;
    background-color: var(--color-card);
    z-index: 1004;
}

.filter-container .options-container {
    max-height: 180px;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 8px 0;
}

.filter-container .option-group {
    margin-bottom: 8px;
}

.filter-container .option-item {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}

/* Stats grid styles */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: var(--color-card);
    padding: 24px;
    border-radius: 16px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    border: 1px solid var(--color-border);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.stat-card h3 {
    margin: 0 0 15px 0;
    color: var(--color-text);
    font-size: 18px;
    font-weight: 600;
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    color: var(--color-highlight);
    margin-bottom: 8px;
}

.stat-label {
    color: var(--color-text);
    opacity: 0.8;
    font-size: 14px;
}

/* Chart row styles */
.chart-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.chart-card {
    background: var(--color-card);
    padding: 24px;
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    border: 1px solid var(--color-border);
}

.chart-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
}

.chart-card h3 {
    margin: 0 0 20px 0;
    color: var(--color-text);
    font-size: 18px;
    font-weight: 600;
}

/* Make text smaller and fit better */
.dashboard {
    font-size: 14px;
}

.dashboard-header h1 {
    font-size: 36px !important;
    line-height: 1.2;
    font-weight: 700;
}

.stat-card h3 {
    font-size: 16px !important;
    line-height: 1.3;
}

.stat-value {
    font-size: 18px !important;
    line-height: 1.2;
}

.chart-title {
    font-size: 16px !important;
    line-height: 1.3;
}

.segment-label {
    font-size: 12px !important;
    line-height: 1.2;
}

.segment-percentage {
    font-size: 11px !important;
    line-height: 1.1;
}

.percentage-label {
    font-size: 11px !important;
    font-weight: 600;
}

.filter-section label {
    font-size: 13px !important;
}

.filter-section select,
.filter-section input {
    font-size: 13px !important;
}

/* Navbar link font size - consistent with other pages */
.navbar a {
    font-size: 17px !important;
}

/* Logo text font size - consistent with other pages */
.navbar-logo-text {
    font-size: 24px !important;
}

/* Ensure navbar styles are properly enforced */
.navbar a,
.navbar a:hover,
.navbar a.active {
    font-size: 17px !important;
}

.navbar-logo-text,
.navbar-logo-text:hover {
    font-size: 24px !important;
}

.light-theme .navbar-header {
    background: linear-gradient(135deg, rgba(142, 185, 110, 0.05) 0%, transparent 100%);
}

.light-theme .navbar-header::after {
    background: linear-gradient(90deg, transparent, rgba(142, 185, 110, 0.3), transparent);
}

.navbar-logo {
    display: flex;
    align-items: center;
    transition: transform 0.3s ease;
}

.navbar-logo:hover {
    transform: scale(1.05);
}

.navbar-logo-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    margin-right: 20px;
    display: flex;
    justify-content: center;
    align-items: center;
    color: var(--color-text);
    font-weight: bold;
    font-size: 20px;
    background: linear-gradient(135deg, rgba(161, 180, 84, 0.1), rgba(161, 180, 84, 0.05));
    border: 1px solid rgba(161, 180, 84, 0.2);
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(161, 180, 84, 0.1);
}

.navbar-logo:hover .navbar-logo-icon {
    background: linear-gradient(135deg, rgba(161, 180, 84, 0.15), rgba(161, 180, 84, 0.08));
    border-color: rgba(161, 180, 84, 0.3);
    box-shadow: 0 4px 15px rgba(161, 180, 84, 0.2);
}

.light-theme .navbar-logo-icon {
    background: linear-gradient(135deg, rgba(142, 185, 110, 0.1), rgba(142, 185, 110, 0.05));
    border-color: rgba(142, 185, 110, 0.2);
    box-shadow: 0 2px 8px rgba(142, 185, 110, 0.1);
}

.light-theme .navbar-logo:hover .navbar-logo-icon {
    background: linear-gradient(135deg, rgba(142, 185, 110, 0.15), rgba(142, 185, 110, 0.08));
    border-color: rgba(142, 185, 110, 0.3);
    box-shadow: 0 4px 15px rgba(142, 185, 110, 0.2);
}

.navbar-logo-text {
    font-size: 24px;
    font-weight: 600;
    color: var(--color-text);
}

.navbar-menu {
    flex: 1;
    padding: 30px 0;
}

.navbar ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.navbar li {
    margin-bottom: 2px;
    position: relative;
    transition: all 0.3s ease;
}

.navbar li:hover {
    transform: translateX(5px);
}

.navbar li:not(:last-child) {
    border-bottom: 1px solid rgba(161, 180, 84, 0.08);
}

.light-theme .navbar li:not(:last-child) {
    border-bottom: 1px solid rgba(142, 185, 110, 0.08);
}

.navbar a {
    text-decoration: none;
    color: var(--color-text);
    font-size: 17px;
    padding: 18px 25px;
    display: flex;
    align-items: center;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    opacity: 0.9;
    border-radius: 0 12px 12px 0;
    margin-right: 10px;
    overflow: hidden;
    background: linear-gradient(90deg, transparent 0%, transparent 100%);
}

.navbar a::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(161, 180, 84, 0.1), transparent);
    transition: left 0.5s ease;
}

.light-theme .navbar a::before {
    background: linear-gradient(90deg, transparent, rgba(142, 185, 110, 0.1), transparent);
}

.navbar a:hover {
    background: linear-gradient(90deg, rgba(161, 180, 84, 0.08) 0%, rgba(161, 180, 84, 0.04) 100%);
    color: var(--color-highlight);
    opacity: 1;
    transform: translateX(3px);
    box-shadow: 0 4px 15px rgba(161, 180, 84, 0.15);
}

.navbar a:hover::before {
    left: 100%;
}

.navbar a.active {
    background: linear-gradient(90deg, rgba(161, 180, 84, 0.15) 0%, rgba(161, 180, 84, 0.08) 100%);
    color: var(--color-highlight);
    opacity: 1;
    font-weight: 600;
    border-left: 4px solid var(--color-highlight);
    box-shadow: 0 6px 20px rgba(161, 180, 84, 0.2);
    transform: translateX(2px);
}

.light-theme .navbar a:hover {
    background: linear-gradient(90deg, rgba(142, 185, 110, 0.08) 0%, rgba(142, 185, 110, 0.04) 100%);
    box-shadow: 0 4px 15px rgba(142, 185, 110, 0.15);
}

.light-theme .navbar a.active {
    background: linear-gradient(90deg, rgba(142, 185, 110, 0.15) 0%, rgba(142, 185, 110, 0.08) 100%);
    border-left-color: var(--color-accent3);
    box-shadow: 0 6px 20px rgba(142, 185, 110, 0.2);
}

.navbar-icon {
    margin-right: 15px;
    width: 24px;
    font-size: 20px;
}

.navbar-footer {
    padding: 25px 20px;
    border-top: 2px solid rgba(164, 188, 46, 0.15);
    font-size: 12px;
    opacity: 0.7;
    text-align: center;
    background: linear-gradient(135deg, transparent 0%, rgba(161, 180, 84, 0.03) 100%);
    position: relative;
}

.navbar-footer::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(161, 180, 84, 0.2), transparent);
}

.light-theme .navbar-footer {
    background: linear-gradient(135deg, transparent 0%, rgba(142, 185, 110, 0.03) 100%);
}

.light-theme .navbar-footer::before {
    background: linear-gradient(90deg, transparent, rgba(142, 185, 110, 0.2), transparent);
}

.navbar-footer div:first-child {
    font-weight: 600;
    color: var(--color-highlight);
    margin-bottom: 8px;
}

.light-theme .navbar-footer div:first-child {
    color: var(--color-accent3);
}

.light-theme body {
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100" height="100" opacity="0.1"><path d="M10,10 Q50,20 90,10 Q80,50 90,90 Q50,80 10,90 Q20,50 10,10 Z" fill="%2376BB43"/></svg>');
    background-size: 300px;
}

.dashboard {
    max-width: calc(100% - 60px);
    width: 100%;
    margin: 0 auto;
}

header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    background-color: var(--color-card);
    padding: 15px 25px;
    border-radius: 12px;
    box-shadow: 0 4px 15px var(--color-shadow);
    border: 1px solid var(--color-border);
    transition: all 0.3s ease;
}

.logo {
    display: flex;
    align-items: center;
}

        .logo-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            margin-right: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--color-text);
            font-weight: bold;
        }

.light-theme .logo-icon {
    color: var(--color-highlight);
}

h1 {
    font-size: 24px;
    font-weight: 600;
    color: var(--color-text);
    margin: 0;
}

.light-theme h1 {
    color: #1B3A1B;
}

/* Dashboard header specific styling */
.dashboard-header h1 {
    color: var(--color-text);
    margin: 0;
    font-size: 24px;
    font-weight: 600;
}

.light-theme .dashboard-header h1 {
    color: #1B3A1B;
    font-size: 36px;
    font-weight: 700;
}

/* Header user info styles */
header .user-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Old theme toggle styles removed */

/* Old theme toggle styles removed */

.light-theme .stat-card {
    background-color: var(--color-card);
    border: 1px solid var(--color-border);
    box-shadow: 0 6px 20px var(--color-shadow);
}

.light-theme .chart-card {
    background-color: var(--color-card);
    border: 1px solid var(--color-border);
    box-shadow: 0 8px 24px var(--color-shadow);
}

.light-theme .stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 187, 106, 0.15);
}

.light-theme .chart-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 32px rgba(102, 187, 106, 0.15);
}

.light-theme .filter-section {
    background-color: var(--color-card);
    border: 1px solid var(--color-border);
    box-shadow: 0 4px 15px var(--color-shadow);
}

.light-theme .filter-group select {
    background-color: white;
    color: var(--color-text);
    border-color: var(--color-border);
}

.light-theme .filter-group select:focus {
    border-color: var(--color-highlight);
    box-shadow: 0 0 0 2px rgba(102, 187, 106, 0.1);
}


.light-theme .card {
    background-color: var(--color-card);
    border: 1px solid var(--color-border);
    box-shadow: 0 4px 15px var(--color-shadow);
}

.light-theme .card:hover {
    box-shadow: 0 8px 20px rgba(76, 175, 80, 0.2);
}

/* Old theme toggle styles removed */

/* User avatar styles removed */

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background-color: var(--color-card);
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
    position: relative;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    font-size: 32px;
    margin-bottom: 15px;
    text-align: center;
    opacity: 0.8;
    transition: transform 0.3s ease;
}

.stat-card:hover .stat-icon {
    transform: scale(1.1);
    opacity: 1;
}

.light-theme .stat-card {
    border: none;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
}

.stat-card h3 {
    font-size: 16px;
    margin-bottom: 10px;
    opacity: 0.95;
    color: var(--color-text);
    font-weight: 500;
}

.stat-value {
    font-size: 32px;
    font-weight: 600;
    margin-bottom: 5px;
    color: var(--color-text);
}

.light-theme .stat-value {
    color: #1B3A1B;
}

.stat-label {
    font-size: 14px;
    opacity: 0.8;
    color: var(--color-text);
}

.light-theme .stat-label {
    opacity: 0.85;
}

.chart-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(480px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

/* Improved chart styling */
.chart-card {
    background-color: var(--color-card);
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
    display: flex;
    flex-direction: column;
    min-height: 420px;
    max-height: none;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.chart-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.1);
}

.chart-card h3 {
    font-size: 18px;
    margin-bottom: 15px;
    color: var(--color-text);
}

.light-theme .chart-card h3 {
    color: var(--color-accent3);
}

.chart-description {
    font-size: 13px;
    color: var(--color-text);
    opacity: 0.8;
    margin-bottom: 20px;
    line-height: 1.4;
    font-style: italic;
}

.light-theme .chart-description {
    opacity: 0.7;
    color: var(--color-text);
}

/* Improved bar chart styling */
.bar {
    width: 8%;
    background-color: var(--color-accent2);
    border-radius: 8px 8px 0 0;
    position: relative;
    overflow: hidden;
    transition: height 1s cubic-bezier(0.25, 0.8, 0.25, 1), 
                background-color 0.3s ease,
                transform 0.2s ease;
    filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.05));
    cursor: pointer;
}

.bar:hover {
    transform: scale(1.05);
    filter: drop-shadow(0 6px 12px rgba(0, 0, 0, 0.1));
}

/* Improved line chart styling */
.line-path {
    fill: none;
    stroke: var(--color-highlight);
    stroke-width: 3;
    stroke-dasharray: 1000;
    stroke-dashoffset: 1000;
    transition: stroke-dashoffset 2.5s ease;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
    /* Debug: Ensure visibility */
    opacity: 1;
    visibility: visible;
}

/* Performance optimizations */
.chart-card * {
    will-change: transform, opacity;
}

/* Loading state */
.chart-card.loading {
    opacity: 0.7;
    pointer-events: none;
}

/* Chart Labels and Legends */
.chart-labels {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 16px;
    padding: 12px;
    background: var(--color-card);
    border-radius: 8px;
    border: 1px solid var(--color-accent3);
}

.label-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: var(--color-text);
}

.label-color {
    width: 12px;
    height: 12px;
    border-radius: 2px;
    display: inline-block;
}

/* Axis Labels */
.x-axis {
    display: flex;
    justify-content: space-between;
    margin-top: 8px;
    padding: 0 4px;
}

.axis-label {
    font-size: 11px;
    color: var(--color-text);
    opacity: 0.7;
    text-align: center;
    flex: 1;
}

.y-axis-label {
    position: absolute;
    left: -30px;
    top: 50%;
    transform: rotate(-90deg) translateX(50%);
    font-size: 12px;
    color: var(--color-text);
    opacity: 0.8;
    white-space: nowrap;
}

/* Bar Chart Labels */
.bar-chart-container {
    position: relative;
    padding-left: 40px;
    padding-bottom: 30px;
}

.bar-chart {
    position: relative;
    height: 200px;
    margin-bottom: 8px;
}

/* Line Chart Labels */
.line-chart-container {
    position: relative;
    padding-left: 30px;
    padding-bottom: 20px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.line-chart {
    width: 100%;
    height: 180px;
    margin-bottom: 5px;
    flex: 1;
    position: relative;
}

.y-axis-label {
    position: absolute;
    left: -25px;
    top: 50%;
    transform: rotate(-90deg) translateX(50%);
    font-size: 11px;
    color: var(--color-text);
    opacity: 0.8;
    white-space: nowrap;
}

.donut-chart-container {
    position: relative;
    height: 280px;
    display: flex;
    justify-content: center;
    align-items: center;
}



/* Percentage labels around the donut */
.percentage-labels {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    pointer-events: none;
    z-index: 5;
}

.percentage-label {
    position: absolute;
    background: rgba(0, 0, 0, 0.85);
    color: white;
    padding: 6px 10px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
    opacity: 1;
    transform: scale(1);
    transition: none;
    pointer-events: none;
    cursor: default;
    border: 1px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.4), 0 0 20px rgba(0, 0, 0, 0.2);
    backdrop-filter: blur(5px);
    z-index: 5;
}

/* Hover effects removed - percentage labels are now static */

/* Light theme percentage labels - white backgrounds with dark theme text colors */
.light-theme .percentage-label {
    background: rgba(255, 255, 255, 0.95) !important;
    color: #1B3A1B !important;
    border: 1px solid rgba(102, 187, 106, 0.3) !important;
    box-shadow: 0 3px 12px rgba(102, 187, 106, 0.2), 0 0 20px rgba(102, 187, 106, 0.1) !important;
    backdrop-filter: blur(5px) !important;
}

/* Ensure percentage labels maintain their colors in light theme */
.light-theme .percentage-label[data-risk-level="0"] {
    color: #4CAF50 !important; /* Green for Low Risk */
}

.light-theme .percentage-label[data-risk-level="1"] {
    color: #FF9800 !important; /* Orange for Moderate Risk */
}

.light-theme .percentage-label[data-risk-level="2"] {
    color: #F44336 !important; /* Red for High Risk */
}

/* Enhanced segments - CENTERED */
.segments {
    display: block !important;
    margin-top: 15px !important;
    max-width: 100% !important;
    overflow: visible !important;
    white-space: normal !important;
    height: auto !important;
    text-align: center !important; /* Center the text boxes */
}

/* Ensure segments are displayed properly */
.segments .segment {
    display: flex !important;
    white-space: normal !important;
}

/* Improved segment layout */
.segments > .segment {
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    margin: 2px 4px !important;
    padding: 8px 6px !important;
    border-radius: 8px !important;
    background: rgba(0, 0, 0, 0.05) !important;
    border: 1px solid rgba(0, 0, 0, 0.1) !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
    position: relative !important;
    overflow: visible !important;
    gap: 6px !important;
    min-width: 140px !important;
    max-width: 140px !important;
    flex-wrap: nowrap !important;
    flex-shrink: 0 !important;
    flex-grow: 0 !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
    backdrop-filter: blur(2px) !important;
}

.segment {
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    margin: 2px 4px !important;
    padding: 8px 6px !important;
    border-radius: 8px !important;
    background: rgba(0, 0, 0, 0.05) !important;
    border: 1px solid rgba(0, 0, 0, 0.1) !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
    position: relative !important;
    overflow: visible !important;
    gap: 6px !important;
    min-width: 140px !important;
    max-width: 140px !important;
    flex-wrap: nowrap !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
    backdrop-filter: blur(2px) !important;
}

/* Segment hover effects removed */

.segment::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(161, 180, 84, 0.1), transparent);
    transition: left 0.5s ease;
}

/* Segment hover before effect removed */

/* Enhanced segment effects */
.segment::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    border-radius: 8px;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, transparent 50%, rgba(0, 0, 0, 0.05) 100%);
    opacity: 0;
    transition: none;
    pointer-events: none;
    z-index: -1;
}

.segment:hover::before {
    opacity: 1;
}

/* New segment layout classes */
.segment-header {
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 6px !important;
    width: 100% !important;
}

.segment-stats {
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 4px !important;
    width: 100% !important;
    flex-wrap: wrap !important;
}

.color-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-right: 0;
    position: relative;
    transition: none;
    flex-shrink: 0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2), 0 0 8px rgba(0, 0, 0, 0.1) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
}

/* Segment color indicator hover effect removed */

/* Segment element styling - improved for new layout */
.segment-label {
    font-weight: 600 !important;
    color: var(--color-text) !important;
    font-size: 10px !important;
    white-space: normal !important;
    min-width: auto !important;
    max-width: none !important;
    display: inline-block !important;
    text-align: center !important;
    line-height: 1.2 !important;
    word-wrap: break-word !important;
    hyphens: auto !important;
}

/* Segment percentage styling */
.segment-percentage {
    font-weight: 600 !important;
    color: var(--color-accent2) !important;
    font-size: 9px !important;
    text-align: center !important;
    background: rgba(0, 0, 0, 0.08) !important;
    padding: 2px 4px !important;
    border-radius: 4px !important;
    min-width: auto !important;
    max-width: none !important;
    white-space: nowrap !important;
    display: inline-block !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
}

.segment-risk-level {
    font-weight: 600 !important;
    color: var(--color-highlight) !important;
    font-size: 9px !important;
    text-align: center !important;
    background: rgba(161, 180, 84, 0.2) !important;
    padding: 2px 4px !important;
    border-radius: 4px !important;
    min-width: auto !important;
    max-width: none !important;
    white-space: nowrap !important;
    display: inline-block !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
}

/* NEW SIMPLE TEXT BOX DESIGN - CENTERED */
.segments .simple-text-box {
    display: inline-block !important;
    background: rgba(0, 0, 0, 0.05) !important;
    border: 1px solid rgba(0, 0, 0, 0.1) !important;
    border-radius: 4px !important;
    width: 100px !important; /* Wider for bigger text */
    height: 20px !important; /* Taller for bigger text */
    margin: 0 2px !important; /* Small margin between boxes */
    padding: 0 !important;
    position: relative !important;
    vertical-align: top !important;
}

/* Text box dot */
.segments .simple-text-box .text-box-dot {
    position: absolute !important;
    left: 2px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    width: 6px !important;
    height: 6px !important;
    border-radius: 50% !important;
    margin: 0 !important;
    padding: 0 !important;
}

/* Text box label - BIGGER TEXT */
.segments .simple-text-box .text-box-label {
    position: absolute !important;
    left: 10px !important; /* More space for bigger text */
    top: 0px !important;
    right: 0px !important;
    height: 10px !important; /* Taller for bigger text */
    margin: 0 !important;
    padding: 0 !important;
    font-weight: 600 !important;
    color: var(--color-text) !important;
    font-size: 7px !important; /* Bigger text */
    line-height: 1 !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
}

/* Text box count - BIGGER TEXT */
.segments .simple-text-box .text-box-count {
    position: absolute !important;
    left: 10px !important; /* More space for bigger text */
    top: 10px !important; /* Adjusted for bigger text */
    right: 0px !important;
    height: 10px !important; /* Taller for bigger text */
    margin: 0 !important;
    padding: 0 !important;
    color: var(--color-text) !important;
    opacity: 0.8 !important;
    font-size: 6px !important; /* Bigger text */
    line-height: 1 !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
}


/* Dark theme for simple text boxes */
.dark-theme .segments .simple-text-box {
    background: rgba(255, 255, 255, 0.05) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
}

.dark-theme .segments .simple-text-box .text-box-label {
    color: #FFFFFF !important;
}

.dark-theme .segments .simple-text-box .text-box-count {
    color: #FFFFFF !important;
    opacity: 0.8 !important;
}

/* Hover effects for simple text boxes */
.segments .simple-text-box:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
    border-color: rgba(0, 0, 0, 0.2) !important;
    background: rgba(0, 0, 0, 0.08) !important;
    transition: all 0.2s ease !important;
}

/* Responsive design for segments */
@media (max-width: 768px) {
    .segments {
        gap: 8px !important;
    }
    
    .segment {
        gap: 4px !important;
        min-width: 120px !important;
        max-width: 120px !important;
        padding: 6px 4px !important;
    }
    
    .segment-label {
        font-size: 9px !important;
    }
    
    .segment-count,
    .segment-percentage,
    .segment-risk-level {
        font-size: 8px !important;
        padding: 1px 3px !important;
    }
    
    .color-indicator {
        width: 8px !important;
        height: 8px !important;
    }
}

@media (max-width: 480px) {
    .segments {
        gap: 6px !important;
    }
    
    .segment {
        min-width: 100px !important;
        max-width: 100px !important;
        padding: 4px 3px !important;
    }
    
    .segment-label {
        font-size: 8px !important;
    }
    
    .segment-count,
    .segment-percentage,
    .segment-risk-level {
        font-size: 7px !important;
        padding: 1px 2px !important;
    }
    
    .color-indicator {
        width: 6px !important;
        height: 6px !important;
    }
}



.donut-chart {
    width: 220px;
    height: 220px;
    border-radius: 50%;
    position: relative;
    overflow: visible;
    box-shadow: 0 0 30px rgba(0, 0, 0, 0.15);
    filter: drop-shadow(0 0 5px rgba(0, 0, 0, 0.05));
    display: grid;
    place-items: center;
    cursor: pointer;
    transition: none;
}

.donut-chart svg {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 3;
    pointer-events: none;
}

.donut-chart svg path {
    transition: none;
    cursor: default;
    stroke: rgba(255, 255, 255, 0.1);
    stroke-width: 1;
    pointer-events: auto;
}

/* Hover effects removed for donut chart */

.light-theme .donut-chart {
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
    transition: none;
}

.donut-chart::before {
    content: '';
    position: absolute;
    width: 60%;
    height: 60%;
    background-color: var(--color-card);
    border-radius: 50%;
    top: 20%;
    left: 20%;
    z-index: 2;
    box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.12);
    transition: none;
}

.light-theme .donut-chart::before {
    box-shadow: inset 0 0 15px rgba(0, 0, 0, 0.06);
    transition: none;
}

.donut-chart-bg {
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    z-index: 1;
    transition: none;
    cursor: default;
}

/* Hover effect removed for donut-chart-bg */

.donut-center-text {
    position: relative;
    z-index: 10;
    font-size: 24px;
    font-weight: 600;
    color: var(--color-text);
    text-align: center;
    line-height: 1;
    pointer-events: none;
    background-color: var(--color-card);
    padding: 8px 12px;
    border-radius: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    width: auto;
    height: auto;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 60px;
    min-height: 40px;
    box-sizing: border-box;
    transition: none;
}



.color-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 6px;
}

.bar-chart-container {
    position: relative;
    height: 220px;
    margin-top: 20px;
}

.bar-chart {
    display: flex;
    justify-content: space-around;
    align-items: flex-end;
    height: 100%;
    padding-bottom: 30px;
}

.bar {
    width: 8%;
    background-color: var(--color-accent2);
    border-radius: 8px 8px 0 0;
    position: relative;
    overflow: hidden;
    transition: height 1s cubic-bezier(0.25, 0.8, 0.25, 1);
    filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.05));
}

.light-theme .bar {
    background-color: var(--color-accent4);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
}

.bar-liquid {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: var(--color-highlight);
    transform: translateY(100%);
    transition: transform 1.5s cubic-bezier(0.25, 0.8, 0.25, 1);
    filter: blur(0.5px);
}

.bar-wave {
    position: absolute;
    width: 200%;
    height: 20px;
    left: -50%;
    border-radius: 40%;
    background: var(--color-accent2);
    animation: barWave 8s infinite linear;
    opacity: 0.8;
}

.light-theme .bar-wave {
    background: var(--color-accent1);
    opacity: 0.7;
}

@keyframes barWave {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(50%);
    }
}

.bar-value {
    position: absolute;
    top: -25px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 12px;
    color: var(--color-text);
}

.bar-label {
    position: absolute;
    bottom: -25px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 12px;
    white-space: nowrap;
    color: var(--color-text);
}

.light-theme .bar-label {
    font-weight: 600;
}

.x-axis {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 2px;
    background-color: rgba(232, 240, 214, 0.3);
}

.light-theme .x-axis {
    background-color: rgba(49, 68, 30, 0.2);
}

.line-chart {
    position: relative;
    height: 100%;
}

.line-path {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    fill: none;
    stroke: var(--color-highlight);
    stroke-width: 2.5;
    stroke-dasharray: 1000;
    stroke-dashoffset: 1000;
    transition: stroke-dashoffset 2.5s ease;
    filter: drop-shadow(0 1px 3px rgba(0, 0, 0, 0.1));
}

.light-theme .line-path {
    stroke: var(--color-accent1);
}

.line-area {
    fill: url(#line-gradient);
    opacity: 0.4;
    clip-path: polygon(0 100%, 0 0, 0 0, 0 100%);
    transition: clip-path 2.5s ease;
    /* Debug: Ensure visibility */
    visibility: visible;
}

.alert-list {
    list-style: none;
    max-height: 280px;
    overflow-y: auto;
    padding: 0;
    margin: 0;
}

.alert-item {
    background-color: rgba(42, 51, 38, 0.7);
    margin-bottom: 8px;
    padding: 12px;
    border-radius: 8px;
    border-left: 3px solid var(--color-highlight);
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
    transition: all 0.3s ease;
    cursor: pointer;
    max-width: 100%;
    word-wrap: break-word;
    overflow: hidden;
}

.alert-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    background-color: rgba(161, 180, 84, 0.1);
    border-left-color: var(--color-highlight);
}

/* Simple hover effects for different alert types */
.alert-item.danger:hover {
    border-left-color: var(--color-danger);
    background-color: rgba(207, 134, 134, 0.1);
}

.alert-item.warning:hover {
    border-left-color: var(--color-warning);
    background-color: rgba(255, 193, 7, 0.1);
}

.alert-item.success:hover {
    border-left-color: var(--color-highlight);
    background-color: rgba(76, 175, 80, 0.1);
}

.light-theme .alert-item {
    background-color: var(--color-card);
    border-left: 4px solid var(--color-accent3);
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.03);
    transition: all 0.3s ease;
    max-width: 100%;
    word-wrap: break-word;
    overflow: hidden;
}

.light-theme .alert-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 187, 106, 0.15);
    background-color: rgba(102, 187, 106, 0.05);
    border-left-color: var(--color-highlight);
}

.alert-item.danger {
    border-left-color: var(--color-danger);
    background-color: rgba(207, 134, 134, 0.1);
    border-left-width: 4px;
}

.alert-item.danger .alert-content h4 {
    color: var(--color-danger);
    font-weight: 600;
}

.alert-item.danger .alert-time {
    color: var(--color-danger);
    opacity: 0.8;
}

.alert-item.warning {
    border-left-color: var(--color-warning);
}



.alert-content h4 {
    font-size: 14px;
    margin-bottom: 3px;
    color: var(--color-text);
    font-weight: 500;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 200px;
    transition: all 0.3s ease;
}

.alert-content p {
    font-size: 12px;
    opacity: 0.7;
    color: var(--color-text);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 180px;
    transition: all 0.3s ease;
}

/* Simple content hover effects removed for cleaner design */

.alert-time {
    font-size: 12px;
    opacity: 0.6;
    color: var(--color-text);
}

.alert-badge {
    display: inline-block;
    padding: 3px 6px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 600;
    transition: all 0.2s ease;
    cursor: default;
}

.alert-badge:hover {
    transform: scale(1.05);
    filter: brightness(1.1);
}

.badge-danger {
    background-color: var(--color-danger);
    color: white;
}

.badge-warning {
    background-color: var(--color-warning);
    color: #333;
}

.light-theme .badge-warning {
    color: white;
}

.badge-success {
    background-color: var(--color-highlight);
    color: #333;
}

.light-theme .badge-success {
    background-color: var(--color-accent3);
    color: white;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .chart-row {
        grid-template-columns: 1fr;
    }
    
    .new-donut-chart {
        width: 400px;
        height: 400px;
    }
    
    .donut-chart-container {
        height: 500px;
    }
    
    .chart-card {
        min-height: 600px;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}

/* Adjustments for larger screens */
@media (min-width: 1200px) {
    .dashboard {
        max-width: calc(100% - 60px);
    }
    
    .stats-grid {
        grid-template-columns: repeat(4, 1fr);
    }
    
    .chart-row {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .chart-card {
        min-height: 400px;
    }
    
    .donut-chart-container {
        height: 300px;
    }
    
    .donut-chart {
        width: 240px;
        height: 240px;
    }
    
    .bar-chart-container {
        height: 280px;
    }
    
    .line-chart-container {
        height: 250px;
    }
    
    .stat-card, .chart-card {
        padding: 20px;
    }
}

/* Additional eye-comfort improvements */
.stat-card, .chart-card {
    border-radius: 20px; /* Slightly more rounded corners */
}

.light-theme .stat-value {
    color: #75aa50; /* Less intense highlight color */
}

.light-theme .chart-card,
.light-theme .stat-card {
    background-image: linear-gradient(to bottom right, rgba(255,255,255,0.05), transparent);
    border: 1px solid rgba(255,255,255,0.1);
}

/* Add subtle grain texture to reduce eye strain from solid colors */
.light-theme:before {
    content: "";
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)' opacity='0.05'/%3E%3C/svg%3E");
    z-index: 1000;
    opacity: 0.06;
}

/* Legacy mobile styles - now handled by modern navigation system */

/* Add this media query for responsive adjustments */
@media (max-width: 768px) {
    .navbar a {
        padding: 12px 25px;  /* Slightly reduce vertical padding for mobile */
    }
    
    .navbar li {
        margin-bottom: 2px;  /* Further reduce spacing on mobile */
    }
}

/* Custom scrollbar - Add this to match USM.html */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: var(--background-color);
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, var(--secondary-color) 0%, #7cb342 100%);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #7cb342 0%, #689f38 100%);
}

/* User section styles for other parts of the dashboard */
.user-section {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    padding: 10px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 5px;
}

.user-section .user-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background-color: #8bc34a;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    margin-right: 15px;
}

.user-section .user-info {
    flex-grow: 1;
}

.user-name {
    font-weight: bold;
    font-size: 18px;
    margin-bottom: 5px;
}

.user-email {
    font-size: 14px;
    color: #888;
}

.logout-btn {
    background-color: #f44336;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
}

.logout-btn:hover {
    background-color: #d32f2f;
}

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

/* Refreshing animations */
@keyframes moonOrbit {
    0% {
        transform: rotate(0deg) translateX(170px) rotate(0deg);
        opacity: 0;
    }
    10% {
        opacity: 1;
    }
    90% {
        opacity: 1;
    }
    100% {
        transform: rotate(360deg) translateX(170px) rotate(-360deg);
        opacity: 0;
    }
}


.refresh-moon {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    background: var(--color-highlight);
    border-radius: 50%;
    transform-origin: center;
    animation: moonOrbit 2s ease-in-out;
    z-index: 10;
}

/* Hover effects for screening issues */
.screening-issues-hover {
    transition: all 0.3s ease;
}

.screening-issues-hover:hover {
    transform: scale(1.02);
    box-shadow: 0 8px 25px rgba(161, 180, 84, 0.2);
}

.main-issue-hover {
    transition: all 0.3s ease;
}

.main-issue-hover:hover {
    background: rgba(161, 180, 84, 0.2) !important;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(161, 180, 84, 0.3);
}

.summary-item-hover {
    transition: all 0.3s ease;
}

.summary-item-hover:hover {
    background: rgba(161, 180, 84, 0.15) !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(161, 180, 84, 0.2);
    border-color: var(--color-highlight) !important;
}

/* MUAC Distribution Chart Styles */
.muac-chart-container {
    padding: 20px;
    height: 300px;
}

/* Nutritional Status Overview - Ultra-Compact Professional Design */
.nutrition-status-container {
    padding: 16px;
    max-height: 400px;
    overflow: hidden;
}

.nutrition-compact {
    display: flex;
    flex-direction: column;
    gap: 16px;
    height: 100%;
}

/* Combined Grid Layout - Space Efficient */
.nutrition-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    flex: 1;
}

/* WHZ Section - Compact 2x2 Grid */
.whz-section {
    background: var(--color-card);
    border-radius: 10px;
    padding: 16px;
    border: 1px solid rgba(161, 180, 84, 0.1);
}

.section-title {
    font-size: 14px;
    font-weight: 600;
    color: var(--color-text);
    margin-bottom: 12px;
    text-align: center;
    opacity: 0.9;
}

.whz-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

.whz-item {
    background: rgba(0, 0, 0, 0.03);
    border-radius: 8px;
    padding: 10px;
    border-left: 3px solid transparent;
    transition: all 0.2s ease;
    position: relative;
}

.whz-item:hover {
    background: rgba(0, 0, 0, 0.06);
    transform: translateY(-1px);
}

.whz-item.sam {
    border-left-color: #F44336;
}

.whz-item.mam {
    border-left-color: #FF9800;
}

.whz-item.normal {
    border-left-color: #4CAF50;
}

.whz-item.overweight {
    border-left-color: #FF5722;
}

.whz-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    margin-bottom: 6px;
    position: relative;
}

.whz-item.sam .whz-dot {
    background: #F44336;
    box-shadow: 0 0 0 2px rgba(244, 67, 54, 0.2);
}

.whz-item.mam .whz-dot {
    background: #FF9800;
    box-shadow: 0 0 0 2px rgba(255, 152, 0, 0.2);
}

.whz-item.normal .whz-dot {
    background: #4CAF50;
    box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
}

.whz-item.overweight .whz-dot {
    background: #FF5722;
    box-shadow: 0 0 0 2px rgba(255, 87, 34, 0.2);
}

.whz-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.whz-label {
    font-size: 11px;
    font-weight: 600;
    color: var(--color-text);
    line-height: 1.2;
    opacity: 0.9;
}

.whz-value {
    font-size: 18px;
    font-weight: 700;
    color: var(--color-highlight);
    line-height: 1;
}

.light-theme .whz-value {
    color: var(--color-accent3);
}

.whz-range {
    font-size: 9px;
    color: var(--color-text);
    opacity: 0.6;
    font-family: 'Courier New', monospace;
    background: rgba(0, 0, 0, 0.03);
    padding: 2px 4px;
    border-radius: 3px;
    display: inline-block;
    width: fit-content;
}

.light-theme .whz-range {
    background: rgba(0, 0, 0, 0.06);
}

/* MUAC Section - Compact Side Panel */
.muac-section {
    background: var(--color-card);
    border-radius: 10px;
    padding: 16px;
    border: 1px solid rgba(161, 180, 84, 0.1);
}

.muac-compact {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.muac-row {
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.muac-row:hover {
    transform: translateX(3px);
}

.muac-label {
    font-size: 10px;
    font-weight: 500;
    color: var(--color-text);
    opacity: 0.8;
    min-width: 80px;
    flex-shrink: 0;
}

.muac-bar {
    height: 6px;
    background: rgba(0, 0, 0, 0.06);
    border-radius: 3px;
    overflow: hidden;
    flex: 1;
    min-width: 60px;
}

.light-theme .muac-bar {
    background: rgba(0, 0, 0, 0.1);
}

.muac-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    min-width: 8px;
}

.muac-fill.normal {
    background: linear-gradient(90deg, #4CAF50, #66BB6A);
}

.muac-fill.mam {
    background: linear-gradient(90deg, #FF9800, #FFB74D);
}

.muac-fill.sam {
    background: linear-gradient(90deg, #F44336, #EF5350);
}

.muac-count {
    font-size: 12px;
    font-weight: 600;
    color: var(--color-highlight);
    min-width: 25px;
    text-align: right;
    flex-shrink: 0;
}

.light-theme .muac-count {
    color: var(--color-accent3);
}

/* Summary Bar - Ultra Compact */
.summary-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    background: var(--color-card);
    border-radius: 10px;
    padding: 12px;
    border: 1px solid rgba(161, 180, 84, 0.1);
    margin-top: auto;
}

.summary-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.summary-label {
    font-size: 11px;
    font-weight: 500;
    color: var(--color-text);
    opacity: 0.7;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.summary-value {
    font-size: 16px;
    font-weight: 700;
    color: var(--color-highlight);
    line-height: 1;
}

.light-theme .summary-value {
    color: var(--color-accent3);
}

.summary-divider {
    width: 1px;
    height: 24px;
    background: rgba(161, 180, 84, 0.2);
    border-radius: 1px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .nutrition-status-container {
        padding: 12px;
        max-height: 350px;
    }
    
    .nutrition-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .whz-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }
    
    .whz-item, .muac-section {
        padding: 12px;
    }
    
    .whz-value {
        font-size: 16px;
    }
    
    .summary-bar {
        padding: 10px;
        gap: 16px;
    }
}

@media (max-width: 480px) {
    .nutrition-status-container {
        padding: 10px;
        max-height: 300px;
    }
    
    .whz-item, .muac-section, .summary-bar {
        padding: 10px;
    }
    
    .whz-grid {
        gap: 6px;
    }
    
    .muac-compact {
        gap: 10px;
    }
    
    .summary-bar {
        gap: 14px;
    }
}

.muac-legend {
    display: flex;
    justify-content: space-around;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 10px;
}

.muac-legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: var(--color-text);
}

.muac-color {
    width: 16px;
    height: 16px;
    border-radius: 3px;
    border: 1px solid rgba(0,0,0,0.1);
}

.muac-bars {
    display: flex;
    justify-content: space-around;
    align-items: flex-end;
    height: 200px;
    padding: 0 20px;
}

.muac-bar {
    width: 60px;
    background: linear-gradient(to top, var(--color-highlight), var(--color-accent2));
    border-radius: 8px 8px 0 0;
    position: relative;
    transition: all 0.3s ease;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.muac-bar:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.muac-bar.normal { background: linear-gradient(to top, #4CAF50, #66BB6A); }
.muac-bar.mam { background: linear-gradient(to top, #FF9800, #FFB74D); }
.muac-bar.sam { background: linear-gradient(to top, #F44336, #EF5350); }

.muac-bar-value {
    position: absolute;
    top: -25px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 12px;
    font-weight: bold;
    color: var(--color-text);
}

.muac-bar-label {
    position: absolute;
    bottom: -30px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 11px;
    color: var(--color-text);
    text-align: center;
    white-space: nowrap;
}

/* WHZ Categories Chart Styles */
.whz-chart-container {
    padding: 20px;
    height: 300px;
}

.whz-bars {
    display: flex;
    justify-content: space-around;
    align-items: flex-end;
    height: 180px;
    margin-bottom: 40px;
}

.whz-bar {
    width: 50px;
    border-radius: 8px 8px 0 0;
    position: relative;
    transition: all 0.3s ease;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.whz-bar:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.whz-bar.sam { background: linear-gradient(to top, #F44336, #EF5350); }
.whz-bar.mam { background: linear-gradient(to top, #FF9800, #FFB74D); }
.whz-bar.normal { background: linear-gradient(to top, #4CAF50, #66BB6A); }
.whz-bar.overweight { background: linear-gradient(to top, #2196F3, #42A5F5); }

.whz-bar-value {
    position: absolute;
    top: -25px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 12px;
    font-weight: bold;
    color: var(--color-text);
}

.whz-labels {
    display: flex;
    justify-content: space-around;
    text-align: center;
}

.whz-labels span {
    font-size: 11px;
    color: var(--color-text);
    line-height: 1.2;
}

/* Trends Chart Styles */
.trends-chart-container {
    height: 400px;
    max-height: 400px;
    width: 100%;
    max-width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: visible; /* Allow tooltips to appear outside */
    position: relative;
}

.quick-date-btn:hover {
    background: rgba(161, 180, 84, 0.2) !important;
    border-color: rgba(161, 180, 84, 0.5) !important;
    transform: translateY(-1px);
}

.quick-date-btn.active {
    background: var(--color-highlight) !important;
    color: white !important;
    border-color: var(--color-highlight) !important;
}

/* Age Classification Chart Styles */
.age-classification-chart-container {
    height: 400px;
    max-height: 400px;
    width: 100%;
    max-width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: visible; /* Allow tooltips to appear outside */
    position: relative;
    box-sizing: border-box;
    padding: 10px;
    margin-top: -30px; /* Move container up */
}

#ageClassificationChart {
    width: 100% !important;
    height: 100% !important;
    max-width: 100% !important;
    max-height: 100% !important;
    display: block !important;
    box-sizing: border-box !important;
    object-fit: contain !important;
}

/* Custom tooltip styles for floating modal */
.chartjs-tooltip {
    position: fixed !important;
    z-index: 9999 !important;
    pointer-events: none !important;
    opacity: 0;
    animation: tooltipFadeIn 0.2s ease-out forwards;
}

@keyframes tooltipFadeIn {
    from {
        opacity: 0;
        transform: translateY(5px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Make Chart.js tooltips appear outside card and on top */
.chartjs-tooltip,
.chartjs-tooltip * {
    z-index: 9999 !important;
    position: relative !important;
}

/* Ensure tooltips can appear outside containers */
.age-classification-chart-container,
.chart-card {
    overflow: visible !important;
}

/* Specific styling for age classification chart card */
.chart-card[style*="grid-column: 1 / -1"] {
    height: 500px;
    max-height: 500px;
    width: 100%;
    max-width: 100%;
    overflow: hidden;
    box-sizing: border-box;
    padding: 20px;
    margin: 0 auto;
}

/* Removed - Age Range Controls no longer needed */
.age-range-controls {
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
}

.age-range-header {
    margin-bottom: 12px;
    text-align: center;
}

.age-range-label {
    font-weight: 600;
    color: var(--text-color);
    font-size: 14px;
    margin: 0;
}

.age-range-inputs {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
    justify-content: center;
}

.input-group {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    min-width: 120px;
}

.input-label {
    font-size: 12px;
    color: var(--text-color-secondary);
    font-weight: 500;
    margin: 0;
}

.input-wrapper {
    display: flex;
    gap: 6px;
    align-items: center;
}

.form-control, .form-select {
    padding: 6px 8px;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    font-size: 13px;
    background: var(--input-bg);
    color: var(--text-color);
    transition: border-color 0.2s, box-shadow 0.2s;
    min-width: 60px;
}

.form-control:focus, .form-select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px rgba(var(--primary-color-rgb), 0.2);
}

.form-control {
    width: 70px;
}

.form-select {
    width: 80px;
}

.button-group {
    display: flex;
    gap: 8px;
    align-items: center;
}

.btn {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-hover);
    transform: translateY(-1px);
}

.btn-secondary {
    background: var(--text-color-secondary);
    color: white;
}

.btn-secondary:hover {
    background: var(--text-color);
    transform: translateY(-1px);
}

/* Removed - Compact Age Range Controls no longer needed */
.age-range-controls-compact {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 6px;
    padding: 10px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    min-width: 350px;
    max-width: 400px;
}

.compact-inputs {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.compact-row {
    display: flex;
    gap: 12px;
    align-items: center;
    justify-content: space-between;
}

.compact-group {
    display: flex;
    align-items: center;
    gap: 6px;
    flex: 1;
}

.compact-label {
    font-size: 12px;
    color: var(--text-color-secondary);
    font-weight: 500;
    margin: 0;
    min-width: 40px;
}

.compact-wrapper {
    display: flex;
    gap: 6px;
    align-items: center;
}

.form-control-compact, .form-select-compact {
    padding: 6px 8px;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    font-size: 12px;
    background: var(--input-bg);
    color: var(--text-color);
    transition: border-color 0.2s;
    min-width: 50px;
}

.form-control-compact {
    width: 60px;
}

.form-select-compact {
    width: 50px;
}

.form-control-compact:focus, .form-select-compact:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 1px rgba(var(--primary-color-rgb), 0.2);
}

.compact-buttons {
    display: flex;
    gap: 8px;
    margin-top: 8px;
}

.btn-compact {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 11px;
    font-weight: 500;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-block;
    text-align: center;
    flex: 1;
}

.btn-primary-compact {
    background: var(--primary-color);
    color: white;
}

.btn-primary-compact:hover {
    background: var(--primary-hover);
    transform: translateY(-1px);
}

.btn-secondary-compact {
    background: var(--text-color-secondary);
    color: white;
}

.btn-secondary-compact:hover {
    background: var(--text-color);
    transform: translateY(-1px);
}

/* Responsive adjustments for age classification chart */
@media (max-width: 768px) {
    .chart-card[style*="grid-column: 1 / -1"] {
        height: 400px;
        max-height: 400px;
        padding: 15px;
    }
    
    .age-classification-chart-container {
        height: 350px;
        max-height: 350px;
        padding: 8px;
    }
    
    .age-range-inputs {
        flex-direction: column;
        gap: 8px;
    }
    
    .input-group {
        min-width: 100%;
        flex-direction: row;
        justify-content: center;
    }
    
    .input-wrapper {
        gap: 8px;
    }
    
    .age-range-controls-compact {
        min-width: 320px;
        max-width: 380px;
    }
    
    .compact-row {
        gap: 8px;
    }
    
    .compact-group {
        gap: 4px;
    }
    
    .form-control-compact, .form-select-compact {
        font-size: 11px;
        padding: 5px 6px;
    }
    
    .form-control-compact {
        width: 55px;
    }
    
    .form-select-compact {
        width: 45px;
    }
    
    .btn-compact {
        font-size: 10px;
        padding: 5px 10px;
    }
}

@media (max-width: 480px) {
    .chart-card[style*="grid-column: 1 / -1"] {
        height: 350px;
        max-height: 350px;
        padding: 10px;
    }
    
    .age-classification-chart-container {
        height: 300px;
        max-height: 300px;
        padding: 5px;
    }
    
    .form-control, .form-select {
        font-size: 12px;
        padding: 5px 6px;
    }
    
    .btn {
        font-size: 12px;
        padding: 5px 10px;
    }
    
    .age-range-controls-compact {
        min-width: 280px;
        max-width: 320px;
    }
    
    .compact-row {
        gap: 6px;
    }
    
    .compact-group {
        gap: 4px;
    }
    
    .form-control-compact, .form-select-compact {
        font-size: 10px;
        padding: 4px 6px;
    }
    
    .form-control-compact {
        width: 50px;
    }
    
    .form-select-compact {
        width: 40px;
    }
    
    .btn-compact {
        font-size: 9px;
        padding: 4px 8px;
    }
}

.dds-score-display {
    text-align: center;
    padding: 20px;
    background: var(--color-card);
    border-radius: 15px;
    border: 2px solid var(--color-highlight);
    min-width: 150px;
}

.dds-number {
    font-size: 36px;
    font-weight: bold;
    color: var(--color-highlight);
    margin-bottom: 5px;
}

.dds-label {
    font-size: 14px;
    color: var(--color-text);
    opacity: 0.8;
    margin-bottom: 10px;
}

.dds-status {
    font-size: 16px;
    font-weight: bold;
    padding: 5px 15px;
    border-radius: 20px;
    color: white;
}

.dds-status.inadequate { background: #F44336; }
.dds-status.adequate { background: #4CAF50; }

.dds-food-groups {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    width: 100%;
    max-width: 300px;
}

.dds-food-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px;
    background: var(--color-card);
    border-radius: 8px;
    border: 1px solid var(--color-accent3);
    font-size: 12px;
    color: var(--color-text);
}

.dds-food-check {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    color: white;
    font-weight: bold;
}

.dds-food-check.checked { background: #4CAF50; }
.dds-food-check.unchecked { background: #ccc; }

/* Geographic Distribution Styles */
.geo-chart-container {
    padding: 15px;
    height: 280px;
    max-height: 280px;
    overflow-y: auto;
    overflow-x: hidden;
    flex: 1;
    position: relative;
    border-radius: 8px;
    background: var(--color-card);
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
}

.geo-chart-container::-webkit-scrollbar {
    width: 6px;
}

.geo-chart-container::-webkit-scrollbar-track {
    background: rgba(0,0,0,0.1);
    border-radius: 3px;
}

.geo-chart-container::-webkit-scrollbar-thumb {
    background: var(--color-highlight);
    border-radius: 3px;
}

.geo-chart-container::-webkit-scrollbar-thumb:hover {
    background: var(--color-accent2);
}

/* Specific styles for Geographic Distribution chart card */
.geo-distribution-card {
    height: auto;
    min-height: 400px;
    max-height: 400px;
    display: flex;
    flex-direction: column;
}

.geo-distribution-card h3 {
    margin-bottom: 15px;
    flex-shrink: 0;
}

.geo-distribution-card .chart-description {
    margin-bottom: 15px;
    flex-shrink: 0;
}

.geo-distribution-card .geo-chart-container {
    flex: 1;
    min-height: 0;
}

.geo-bars {
    display: flex;
    flex-direction: column;
    gap: 6px;
    height: 100%;
    overflow: visible;
}

.geo-bar-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    background: var(--color-card);
    border-radius: 8px;
    border: 1px solid rgba(161, 180, 84, 0.2);
    transition: all 0.3s ease;
    margin-bottom: 6px;
    min-height: 45px;
    flex-shrink: 0;
}

.geo-bar-item:hover {
    transform: translateX(2px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-color: var(--color-highlight);
}

.geo-bar-name {
    flex: 1;
    font-size: 14px;
    color: var(--color-text);
    font-weight: 500;
    min-width: 120px;
}

.geo-bar-progress {
    flex: 2;
    height: 24px;
    background: rgba(161, 180, 84, 0.15);
    border-radius: 12px;
    overflow: hidden;
    position: relative;
    margin: 0 8px;
}

.geo-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--color-highlight), var(--color-accent2));
    border-radius: 12px;
    transition: width 0.5s ease;
    position: relative;
}

.geo-bar-count {
    font-size: 12px;
    color: white;
    font-weight: 600;
    min-width: 50px;
    text-align: center;
    background: var(--color-highlight);
    padding: 6px 10px;
    border-radius: 15px;
}

/* Classification Trends Chart Styles */
.trends-chart-container {
    height: 350px;
    margin-top: 0;
    padding: 0 10px;
    position: relative;
}

.trends-chart {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    height: 300px;
    gap: 2px;
    padding: 15px 10px 10px 10px;
    background: var(--color-bg);
    border-radius: 12px;
    border: 1px solid var(--color-border);
    overflow: hidden;
    width: 100%;
    flex-wrap: nowrap;
    min-width: 0;
    box-sizing: border-box;
    margin: 0 auto;
}

.trend-bar {
    flex: 1;
    min-width: 15px;
    max-width: 30px;
    border-radius: 5px 5px 0 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-end;
    position: relative;
    transition: all 0.3s ease;
    min-height: 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.trend-bar:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.trend-bar-value {
    position: absolute;
    top: -25px;
    font-size: 12px;
    font-weight: 700;
    color: var(--color-text);
    background: var(--color-card);
    padding: 4px 6px;
    border-radius: 4px;
    border: 1px solid var(--color-border);
    white-space: nowrap;
    z-index: 10;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.trends-labels-container {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 2px;
    width: 100%;
    padding: 10px 10px 0 10px;
    height: 50px;
    box-sizing: border-box;
}

.trend-label-item {
    flex: 1;
    min-width: 15px;
    max-width: 30px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    text-align: center;
}

.trend-label-classification {
    font-size: 8px;
    font-weight: 600;
    color: var(--color-text);
    line-height: 1.2;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.trend-label-standard {
    font-size: 7px;
    font-weight: 500;
    color: var(--color-text);
    opacity: 0.8;
    line-height: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}



/* Bar colors for different classifications - Enhanced with gradients */
.trend-bar.normal { 
    background: linear-gradient(135deg, #4CAF50, #66BB6A); 
    border: 1px solid rgba(76, 175, 80, 0.3);
}
.trend-bar.overweight { 
    background: linear-gradient(135deg, #FF9800, #FFB74D); 
    border: 1px solid rgba(255, 152, 0, 0.3);
}
.trend-bar.obese { 
    background: linear-gradient(135deg, #F44336, #EF5350); 
    border: 1px solid rgba(244, 67, 54, 0.3);
}
.trend-bar.underweight { 
    background: linear-gradient(135deg, #FFC107, #FFD54F); 
    border: 1px solid rgba(255, 193, 7, 0.3);
}
.trend-bar.severely-underweight { 
    background: linear-gradient(135deg, #E91E63, #F06292); 
    border: 1px solid rgba(233, 30, 99, 0.3);
}
.trend-bar.stunted { 
    background: linear-gradient(135deg, #9C27B0, #BA68C8); 
    border: 1px solid rgba(156, 39, 176, 0.3);
}
.trend-bar.severely-stunted { 
    background: linear-gradient(135deg, #673AB7, #9575CD); 
    border: 1px solid rgba(103, 58, 183, 0.3);
}
.trend-bar.wasted { 
    background: linear-gradient(135deg, #FF5722, #FF7043); 
    border: 1px solid rgba(255, 87, 34, 0.3);
}
.trend-bar.severely-wasted { 
    background: linear-gradient(135deg, #D32F2F, #E57373); 
    border: 1px solid rgba(211, 47, 47, 0.3);
}
.trend-bar.tall { 
    background: linear-gradient(135deg, #00BCD4, #4DD0E1); 
    border: 1px solid rgba(0, 188, 212, 0.3);
}
.trend-bar.no-data { 
    background: linear-gradient(135deg, #9E9E9E, #BDBDBD); 
    border: 1px solid rgba(158, 158, 158, 0.3);
}

/* Responsive adjustments for Classification Trends Chart */
@media (max-width: 1200px) {
    .trends-chart-container {
        height: 320px;
        padding: 0 8px;
    }
    
    .trends-chart {
        height: 240px;
        gap: 1px;
        padding: 12px 8px 8px 8px;
    }
    
    .trend-bar {
        min-width: 12px;
        max-width: 25px;
    }
    
    .trends-labels-container {
        padding: 8px 8px 0 8px;
        height: 45px;
    }
    
    .trend-label-item {
        min-width: 12px;
        max-width: 25px;
    }
    
}

@media (max-width: 768px) {
    .trends-chart-container {
        height: 280px;
        padding: 0 5px;
    }
    
    .trends-chart {
        height: 210px;
        gap: 1px;
        padding: 8px 5px 5px 5px;
    }
    
    .trend-bar {
        min-width: 10px;
        max-width: 20px;
    }
    
    .trends-labels-container {
        padding: 6px 5px 0 5px;
        height: 40px;
    }
    
    .trend-label-item {
        min-width: 10px;
        max-width: 20px;
    }
    
    
    .trend-bar-value {
        font-size: 10px;
        padding: 2px 4px;
        top: -20px;
    }
}

.alert-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.notification-btn {
    background: #4CAF50;
    border: none;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    cursor: pointer;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.notification-btn:hover {
    background: #45a049;
    transform: scale(1.1);
}

.alert-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.alert-item {
    background-color: rgba(42, 51, 38, 0.7);
    margin-bottom: 6px;
    padding: 8px 10px;
    border-radius: 6px;
    border-left: 3px solid var(--color-highlight);
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
    transition: all 0.3s ease;
    cursor: pointer;
    max-width: 100%;
    word-wrap: break-word;
    overflow: hidden;
}

.alert-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    background-color: rgba(161, 180, 84, 0.1);
    border-left-color: var(--color-highlight);
}

.light-theme .alert-item {
    background-color: var(--color-card);
    border-left: 3px solid var(--color-accent3);
    box-shadow: 0 2px 6px var(--color-shadow);
}

.light-theme .alert-item.danger {
    background-color: rgba(244, 67, 54, 0.05);
    border-left-color: var(--color-danger);
}

.light-theme .alert-item.success {
    background-color: rgba(76, 175, 80, 0.05);
    border-left-color: var(--color-highlight);
}

.light-theme .notify-btn {
    background: linear-gradient(135deg, var(--color-highlight), var(--color-accent1));
    color: white;
    box-shadow: 0 2px 8px rgba(102, 187, 106, 0.3);
}

.light-theme .notify-btn:hover {
    box-shadow: 0 4px 12px rgba(102, 187, 106, 0.4);
}

.light-theme .create-program-btn {
    background: linear-gradient(135deg, var(--color-highlight), var(--color-accent1));
    color: white;
    box-shadow: 0 4px 15px rgba(102, 187, 106, 0.3);
}

.light-theme .create-program-btn:hover {
    box-shadow: 0 6px 20px rgba(102, 187, 106, 0.4);
}

.light-theme .generate-programs-btn {
    background: linear-gradient(135deg, var(--color-highlight), var(--color-accent1));
    color: white;
    box-shadow: 0 4px 15px rgba(102, 187, 106, 0.3);
}

.light-theme .generate-programs-btn:hover {
    box-shadow: 0 6px 20px rgba(102, 187, 106, 0.4);
}

.light-theme .program-card {
    background-color: #FFFFFF;
    border: 1px solid var(--color-border);
    border-left: 3px solid var(--color-highlight);
    box-shadow: 0 4px 15px var(--color-shadow);
    transition: all 0.3s ease;
}

.light-theme .program-card:hover {
    background-color: rgba(102, 187, 106, 0.08) !important;
    box-shadow: 0 8px 25px rgba(102, 187, 106, 0.2) !important;
    border-color: var(--color-highlight) !important;
    transform: translateY(-2px) !important;
    border-left-color: var(--color-accent2) !important;
}

.light-theme .program-card:hover .program-title {
    color: var(--color-highlight);
}

.light-theme .program-card:hover .program-description {
    color: var(--color-text);
}

/* Light theme program card content styling */
.light-theme .program-card .program-title {
    color: #1B3A1B !important;
}

.light-theme .program-card .program-description {
    color: #1B3A1B !important;
    opacity: 0.8;
}

.light-theme .program-card .priority-tag {
    background-color: var(--color-accent2);
    color: #1B3A1B !important;
}

.light-theme .program-card .targeting-info {
    color: #2E7D32 !important;
}

.light-theme .program-card .program-details {
    color: #1B3A1B !important;
    opacity: 0.7;
}

.light-theme .program-card .target-location {
    background-color: rgba(102, 187, 106, 0.1);
    color: #2E7D32 !important;
}

.light-theme .program-card .btn-show-reasoning {
    background-color: #2196F3;
    color: white;
    border: none;
    transition: all 0.3s ease;
}

.light-theme .program-card .btn-show-reasoning:hover {
    background-color: #1976D2;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
}

.light-theme .program-card .btn-create-program {
    background-color: var(--color-highlight);
    color: #1B3A1B !important;
    border: none;
    transition: all 0.3s ease;
}

.light-theme .program-card .btn-create-program:hover {
    background-color: var(--color-accent2);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 187, 106, 0.3);
}

/* Enhanced light theme program card hover effects */
.light-theme .program-card:hover .program-title {
    color: #2E7D32 !important;
    transition: color 0.3s ease;
}

.light-theme .program-card:hover .priority-tag {
    transform: scale(1.05);
    transition: all 0.3s ease;
}

.light-theme .program-card:hover .target-location {
    background-color: rgba(102, 187, 106, 0.2) !important;
    color: #1B3A1B !important;
    transition: all 0.3s ease;
}

/* General program card styling */
.program-card {
    background-color: var(--color-card);
    border: 1px solid var(--color-border);
    border-left: 3px solid var(--color-highlight);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px var(--color-shadow);
}

/* Dark theme program card hover (default) */
.program-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px var(--color-shadow);
    border-color: var(--color-highlight);
    background-color: rgba(34, 53, 34, 0.8);
}

.light-theme .progress-bar {
    background-color: rgba(0, 0, 0, 0.1);
}

.light-theme .date-examples-container {
    background: rgba(76, 175, 80, 0.05);
    border-color: var(--color-border);
}

.light-theme .date-example-item {
    background: rgba(0, 0, 0, 0.02);
    border-left-color: var(--color-accent3);
}

.light-theme .date-example-value {
    background: rgba(76, 175, 80, 0.1);
    color: var(--color-accent3);
}

.alert-content h4 {
    font-size: 12px;
    margin-bottom: 2px;
    color: var(--color-text);
    font-weight: 500;
}

.alert-content p {
    font-size: 10px;
    opacity: 0.7;
    color: var(--color-text);
    margin: 0;
}

.alert-actions {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
}

.alert-buttons {
    display: flex;
    align-items: center;
    gap: 8px;
}

.alert-time {
    font-size: 9px;
    opacity: 0.6;
    color: var(--color-text);
    margin-bottom: 2px;
}

.notify-btn {
    background: linear-gradient(135deg, var(--color-highlight), var(--color-accent3));
    color: white;
    border: none;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(161, 180, 84, 0.3);
    min-width: 60px;
}

.notify-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(161, 180, 84, 0.4);
    filter: brightness(1.1);
}

.notify-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 6px rgba(161, 180, 84, 0.3);
}

/* Light theme support for notification button */
.light-theme .notify-btn {
    background: linear-gradient(135deg, var(--color-accent3), var(--color-highlight));
    box-shadow: 0 2px 8px rgba(142, 185, 110, 0.3);
}

.light-theme .notify-btn:hover {
    box-shadow: 0 4px 12px rgba(142, 185, 110, 0.4);
}

.light-theme .notify-btn:active {
    box-shadow: 0 2px 6px rgba(142, 185, 110, 0.3);
}

.alert-badge {
    display: inline-block;
    padding: 3px 6px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 600;
    transition: all 0.2s ease;
    cursor: default;
}

.alert-badge:hover {
    transform: scale(1.05);
    filter: brightness(1.1);
}

.badge-danger {
    background-color: var(--color-danger);
    color: white;
}

.badge-warning {
    background-color: var(--color-warning);
    color: #333;
}

.light-theme .badge-warning {
    color: white;
}

.badge-success {
    background-color: var(--color-highlight);
    color: #333;
}

.light-theme .badge-success {
    background-color: var(--color-accent3);
    color: white;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .chart-row {
        grid-template-columns: 1fr;
    }
    
    .new-donut-chart {
        width: 400px;
        height: 400px;
    }
    
    .donut-chart-container {
        height: 500px;
    }
    
    .chart-card {
        min-height: 600px;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}

/* Adjustments for larger screens */
@media (min-width: 1200px) {
    .dashboard {
        max-width: calc(100% - 60px);
    }
    
    .stats-grid {
        grid-template-columns: repeat(4, 1fr);
    }
    
    .chart-row {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .chart-card {
        min-height: 400px;
    }
    
    .donut-chart-container {
        height: 300px;
    }
    
    .donut-chart {
        width: 240px;
        height: 240px;
    }
    
    .bar-chart-container {
        height: 280px;
    }
    
    .line-chart-container {
        height: 250px;
    }
    
    .stat-card, .chart-card {
        padding: 20px;
    }
}



/* Additional eye-comfort improvements */
.stat-card, .chart-card {
    border-radius: 20px; /* Slightly more rounded corners */
}

.light-theme .stat-value {
    color: #75aa50; /* Less intense highlight color */
}

.light-theme .chart-card,
.light-theme .stat-card {
    background-image: linear-gradient(to bottom right, rgba(255,255,255,0.05), transparent);
    border: 1px solid rgba(255,255,255,0.1);
}

/* Add subtle grain texture to reduce eye strain from solid colors */
.light-theme:before {
    content: "";
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)' opacity='0.05'/%3E%3C/svg%3E");
    z-index: 1000;
    opacity: 0.06;
}

/* Legacy mobile styles - now handled by modern navigation system */

/* Add this media query for responsive adjustments */
@media (max-width: 768px) {
    .navbar a {
        padding: 12px 25px;  /* Slightly reduce vertical padding for mobile */
    }
    
    .navbar li {
        margin-bottom: 2px;  /* Further reduce spacing on mobile */
    }
}

/* Custom scrollbar - Add this to match USM.html */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: var(--background-color);
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, var(--secondary-color) 0%, #7cb342 100%);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #7cb342 0%, #689f38 100%);
}

/* User section styles for other parts of the dashboard */
.user-section {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    padding: 10px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 5px;
}

.user-section .user-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background-color: #8bc34a;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    margin-right: 15px;
}

.user-section .user-info {
    flex-grow: 1;
}

.user-name {
    font-weight: bold;
    font-size: 18px;
    margin-bottom: 5px;
}

.user-email {
    font-size: 14px;
    color: #888;
}

.logout-btn {
    background-color: #f44336;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
}

.logout-btn:hover {
    background-color: #d32f2f;
}

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

/* Refreshing animations */
@keyframes moonOrbit {
    0% {
        transform: rotate(0deg) translateX(170px) rotate(0deg);
        opacity: 0;
    }
    10% {
        opacity: 1;
    }
    90% {
        opacity: 1;
    }
    100% {
        transform: rotate(360deg) translateX(170px) rotate(-360deg);
        opacity: 0;
    }
}


.refresh-moon {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    background: var(--color-highlight);
    border-radius: 50%;
    transform-origin: center;
    animation: moonOrbit 2s ease-in-out;
    z-index: 10;
}

/* Hover effects for screening issues */
.screening-issues-hover {
    transition: all 0.3s ease;
}

.screening-issues-hover:hover {
    transform: scale(1.02);
    box-shadow: 0 8px 25px rgba(161, 180, 84, 0.2);
}

.main-issue-hover {
    transition: all 0.3s ease;
}

.main-issue-hover:hover {
    background: rgba(161, 180, 84, 0.2) !important;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(161, 180, 84, 0.3);
}

.summary-item-hover {
    transition: all 0.3s ease;
}

.summary-item-hover:hover {
    background: rgba(161, 180, 84, 0.15) !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(161, 180, 84, 0.2);
    border-color: var(--color-highlight) !important;
}
/* New Analytics Styles */
.legend {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 10px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
}

.legend-color {
    width: 16px;
    height: 16px;
    border-radius: 3px;
    flex-shrink: 0;
}

.legend-label {
    flex: 1;
    color: var(--color-text);
}

.legend-value {
    color: var(--color-text);
    opacity: 0.8;
    font-weight: 500;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    padding: 10px;
}

.summary-item {
    background: rgba(161, 180, 84, 0.1);
    border: 1px solid var(--color-highlight);
    border-radius: 8px;
    padding: 12px;
    text-align: center;
}

.summary-item h4 {
    margin: 0 0 8px 0;
    color: var(--color-highlight);
    font-size: 14px;
    font-weight: 600;
}

.summary-item p {
    margin: 0;
    color: var(--color-text);
    font-size: 16px;
    font-weight: 700;
}

.chart-card.full-width {
    grid-column: 1 / -1;
}

.pie-chart-container {
    display: flex;
    align-items: center;
    gap: 25px;
    padding: 25px;
    background: rgba(161, 180, 84, 0.03);
    border-radius: 12px;
    border: 1px solid rgba(161, 180, 84, 0.1);
}

.pie-chart {
    flex-shrink: 0;
    width: 220px;
    height: 220px;
    border-radius: 50%;
    overflow: visible;
    transition: transform 0.3s ease;
}

/* Add smooth transitions for donut chart updates */
.donut-chart-bg, .pie-chart-bg {
    transition: background 0.5s ease-in-out, opacity 0.3s ease-in-out;
}

.segment {
    transition: opacity 0.3s ease-in-out;
}

/* Smooth transitions for center text updates */
#risk-center-text {
    transition: transform 0.15s ease-in-out, color 0.15s ease-in-out;
}

/* Pie chart hover effects removed */

.pie-center-value {
    font-size: 24px;
    font-weight: 800;
    fill: var(--color-highlight);
    opacity: 1;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
    transition: transform 0.15s ease-in-out, fill 0.15s ease-in-out;
}

.light-theme .pie-chart-container {
    background: var(--color-hover);
    border-color: var(--color-border);
}

.light-theme .pie-chart {
    box-shadow: 0 0 20px var(--color-shadow);
}

.light-theme .pie-center-value {
    fill: var(--color-highlight);
}

.light-theme .screening-chart {
    background: var(--color-hover);
    border-color: var(--color-border);
}

.light-theme .donut-chart {
    box-shadow: 0 0 20px var(--color-shadow);
}

.light-theme .donut-chart::before {
    box-shadow: inset 0 0 15px var(--color-shadow);
}

.light-theme .donut-center-text {
    background-color: white;
    color: var(--color-text);
    box-shadow: 0 2px 8px var(--color-shadow);
}

/* Dark theme support for donut chart center text */
.dark-theme .donut-center-text {
    background-color: var(--color-card);
    color: #FFFFFF !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.light-theme .bar {
    background-color: var(--color-accent1);
    box-shadow: 0 2px 5px var(--color-shadow);
}

.light-theme .bar-wave {
    background: var(--color-accent2);
}

.light-theme .line-path {
    stroke: var(--color-highlight);
    filter: drop-shadow(0 2px 4px var(--color-shadow));
}

.light-theme .line-area {
    fill: url(#muac-line-gradient);
    opacity: 0.4;
}

.light-theme .segments .segment {
    background: rgba(0, 0, 0, 0.02);
    border-color: var(--color-border);
    box-shadow: 0 2px 8px var(--color-shadow);
}

.light-theme .segment:hover {
    background: rgba(102, 187, 106, 0.05);
    box-shadow: 0 4px 12px var(--color-shadow);
}

.light-theme .segment-label {
    color: var(--color-text);
}

.light-theme .segment-percentage {
    background: rgba(0, 0, 0, 0.06);
    color: var(--color-accent2);
}

.light-theme .segment-risk-level {
    background: var(--color-active);
    color: var(--color-highlight);
}

.light-theme .segment.compact {
    background: rgba(76, 175, 80, 0.05) !important;
    border-color: var(--color-border) !important;
}

.light-theme .segment.compact .segment-label {
    color: var(--color-text) !important;
}

.light-theme .segment.compact .segment-percentage {
    color: var(--color-text) !important;
}

.light-theme .segment.compact .segment-risk-level {
    background: var(--color-active) !important;
    color: var(--color-highlight) !important;
}

/* Light theme hover effects for compact segments */
.light-theme .segment.compact:hover {
    background: rgba(76, 175, 80, 0.08) !important;
    border-color: var(--color-highlight) !important;
    box-shadow: 0 4px 15px rgba(76, 175, 80, 0.15) !important;
    transform: translateY(-1px) !important;
    transition: all 0.2s ease !important;
}

/* Light theme color indicators - Ensure colored boxes work in light theme */
.light-theme .segment.compact[data-risk-level="0"] .segment-label::before {
    background-color: #4CAF50 !important; /* Light theme green */
    border: 1px solid rgba(76, 175, 80, 0.3) !important;
}

.light-theme .segment.compact[data-risk-level="1"] .segment-label::before {
    background-color: #FF9800 !important; /* Yellow for moderate */
    border: 1px solid rgba(255, 152, 0, 0.3) !important;
}

.light-theme .segment.compact[data-risk-level="2"] .segment-label::before {
    background-color: #F44336 !important; /* Red for high */
    border: 1px solid rgba(244, 67, 54, 0.3) !important;
}

.light-theme .segment.compact[data-risk-level="3"] .segment-label::before {
    background-color: #D32F2F !important; /* Dark Red for severe */
    border: 1px solid rgba(211, 47, 47, 0.3) !important;
}

/* Dark theme color indicators - matching donut chart colors */
.dark-theme .segment.compact[data-risk-level="0"] .segment-dot {
    background-color: #A1B454 !important; /* Dark theme: Green for Low Risk */
}

.dark-theme .segment.compact[data-risk-level="1"] .segment-dot {
    background-color: #F9B97F !important; /* Yellow for Moderate Risk */
}

.dark-theme .segment.compact[data-risk-level="2"] .segment-dot {
    background-color: #E53E3E !important; /* Red for High Risk */
}

.dark-theme .segment.compact[data-risk-level="3"] .segment-dot {
    background-color: #D32F2F !important; /* Dark Red for Severe Risk */
}

/* Dark theme segment styling - ultra compact */
.dark-theme .segments .segment.compact {
    background: rgba(255, 255, 255, 0.05) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    margin: 0 !important;
    padding: 0 !important;
    font-size: 0 !important;
}

.dark-theme .segments .segment.compact .segment-label {
    color: #FFFFFF !important;
    margin: 0 !important;
    padding: 0 !important;
}

.dark-theme .segments .segment.compact .segment-percentage {
    color: #FFFFFF !important;
    opacity: 0.8 !important;
    margin: 0 !important;
    padding: 0 !important;
}

.dark-theme .segment-label {
    color: #FFFFFF !important; /* White text for dark theme */
}

.dark-theme .segment-percentage {
    color: #FFFFFF !important; /* White text for dark theme */
}

/* Light theme response questions */
.light-theme .response-question {
    color: #1B3A1B !important;
    border-bottom-color: var(--color-border) !important;
}

/* Light theme comprehensive text color fixes */
.light-theme .stat-card,
.light-theme .chart-card,
.light-theme .filter-section,
.light-theme .card {
    color: #1B3A1B !important;
}

.light-theme .stat-card h3,
.light-theme .chart-card h3,
.light-theme .card h3 {
    color: #1B3A1B !important;
}

.light-theme .stat-card .stat-value,
.light-theme .chart-card .chart-title {
    color: #1B3A1B !important;
}

.light-theme .filter-group label,
.light-theme .filter-group select {
    color: #1B3A1B !important;
}


.light-theme .custom-select-container .select-header {
    color: #1B3A1B !important;
    background-color: var(--color-card) !important;
    border-color: var(--color-border) !important;
}

.light-theme .dropdown-content .option-item {
    color: #1B3A1B !important;
}

.light-theme .dropdown-content .option-item:hover {
    background-color: var(--color-hover) !important;
}

.light-theme .search-container input {
    color: #1B3A1B !important;
    background-color: var(--color-card) !important;
    border-color: var(--color-border) !important;
}

/* Dark theme support for custom select */
.dark-theme .custom-select-container .select-header {
    background-color: var(--color-card) !important;
    border-color: var(--color-border) !important;
    color: var(--color-text) !important;
}

.dark-theme .dropdown-content {
    background-color: var(--color-card) !important;
    border-color: var(--color-border) !important;
}

.dark-theme .dropdown-content .option-item {
    color: var(--color-text) !important;
}

.dark-theme .dropdown-content .option-item:hover {
    background-color: var(--color-hover) !important;
}

.dark-theme .search-container input {
    background-color: var(--color-card) !important;
    border-color: var(--color-border) !important;
    color: #FFFFFF !important;
}

.dark-theme .search-container input::placeholder {
    color: #CCCCCC !important;
}

/* Dark theme support for metric cards */
.dark-theme .metric-value {
    color: #FFFFFF !important;
}

.dark-theme .card h2 {
    color: #FFFFFF !important;
}

.dark-theme .metric-note {
    color: #CCCCCC !important;
}

/* Dark theme support for WHO dropdown select */
.dark-theme #whoStandardSelect {
    background-color: var(--color-card) !important;
    border-color: var(--color-border) !important;
    color: var(--color-text) !important;
}

.dark-theme #whoStandardSelect option {
    background-color: var(--color-card) !important;
    color: var(--color-text) !important;
}

.light-theme .search-container input::placeholder {
    color: #666 !important;
}

/* Fix all remaining green text colors in light theme */
.light-theme .stat-value,
.light-theme .chart-title,
.light-theme .impact-estimate,
.light-theme .progress-label span:last-child,
.light-theme .segment-percentage,
.light-theme .segment-risk-level,
.light-theme .answer-label,
.light-theme .answer-count,
.light-theme .answer-percentage,
.light-theme .date-range-display,
.light-theme .ai-reasoning-modal h3,
.light-theme .ai-reasoning-modal strong,
.light-theme .notification-modal h3,
.light-theme .notification-modal strong,
.light-theme .community-status,
.light-theme .target-location strong,
.light-theme .metric-value,
.light-theme .metric-change,
.light-theme .whz-value,
.light-theme .chart-description,
.light-theme .metric-note,
.light-theme .response-question,
.light-theme .header-label,
.light-theme .header-count,
.light-theme .header-percent {
    color: #1B3A1B !important;
}







.screening-chart {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(161, 180, 84, 0.05);
    border-radius: 12px;
    border: 1px solid rgba(161, 180, 84, 0.1);
    transition: all 0.3s ease;
}

/* Screening chart hover effects removed */











/* Line Chart Styles for WHZ and MUAC */
.line-path {
    fill: none;
    stroke: var(--color-highlight);
    stroke-width: 3px;
    stroke-linecap: round;
    stroke-linejoin: round;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
    transition: all 0.3s ease;
}

.line-area {
    fill: url(#muac-line-gradient);
    opacity: 0.6;
    transition: all 0.3s ease;
}

#whz-line-area {
    fill: url(#whz-line-gradient);
}

.light-theme .line-path {
    stroke: var(--color-highlight);
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.15));
}

/* Line chart hover effects removed */

@media (max-width: 768px) {
    .pie-chart-container {
        flex-direction: column;
        align-items: center;
        gap: 15px;
    }
    
    .pie-chart {
        width: 150px;
        height: 150px;
    }
    
    .summary-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 10px;
    }
    
    .program-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .create-this-program-btn {
        margin-left: 0;
        align-self: flex-start;
    }
}

/* Create Program Button Styles */
.create-program-btn {
    background: linear-gradient(135deg, var(--color-highlight), var(--color-accent1));
    color: var(--color-bg);
    border: none;
    padding: 12px 20px;
    border-radius: 25px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(161, 180, 84, 0.3);
    text-decoration: none;
}

.create-program-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(161, 180, 84, 0.4);
    background: linear-gradient(135deg, var(--color-accent1), var(--color-highlight));
}

.create-program-btn:active {
    transform: translateY(0);
}

.btn-icon {
    font-size: 16px;
    font-weight: bold;
}

.btn-text {
    white-space: nowrap;
}

.light-theme .create-program-btn {
    background: linear-gradient(135deg, var(--color-highlight), var(--color-accent3));
    color: white;
    box-shadow: 0 4px 15px rgba(118, 187, 110, 0.3);
}

.light-theme .create-program-btn:hover {
    box-shadow: 0 6px 20px rgba(118, 187, 110, 0.4);
    background: linear-gradient(135deg, var(--color-accent3), var(--color-highlight));
}

/* Loading Spinner Styles */
.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid rgba(161, 180, 84, 0.2);
    border-top: 4px solid var(--color-highlight);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.programs-loading {
    background: rgba(161, 180, 84, 0.02);
    border-radius: 10px;
    border: 1px dashed rgba(161, 180, 84, 0.2);
}



/* Generate Programs Button Styles */
.generate-programs-btn {
    background: linear-gradient(135deg, var(--color-highlight), var(--color-accent1));
    color: var(--color-bg);
    border: none;
    padding: 12px 20px;
    border-radius: 25px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(161, 180, 84, 0.3);
    text-decoration: none;
}

.generate-programs-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(161, 180, 84, 0.4);
    background: linear-gradient(135deg, var(--color-accent1), var(--color-highlight));
}

.generate-programs-btn:active {
    transform: translateY(0);
}

.generate-programs-btn .btn-text {
    white-space: nowrap;
}

/* Show AI Reasoning Button Styles */
.show-reasoning-btn {
    background: linear-gradient(135deg, #81d4fa, #4fc3f7);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 25px;
    cursor: pointer;
    font-weight: 600;
    font-size: 12px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 2px 8px rgba(129, 212, 250, 0.3);
    text-decoration: none;
}

.show-reasoning-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(129, 212, 250, 0.4);
    background: linear-gradient(135deg, #4fc3f7, #81d4fa);
}

.show-reasoning-btn:active {
    transform: translateY(0);
}

.show-reasoning-btn .btn-text {
    white-space: nowrap;
}

/* Create This Program Button Styles */
.create-this-program-btn {
    background: linear-gradient(135deg, var(--color-accent2), var(--color-accent3));
    color: var(--color-bg);
    border: none;
    padding: 8px 16px;
    border-radius: 20px;
    cursor: pointer;
    font-weight: 600;
    font-size: 12px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(181, 200, 141, 0.3);
    margin-left: 10px;
    white-space: nowrap;
}

.create-this-program-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(181, 200, 141, 0.4);
    background: linear-gradient(135deg, var(--color-accent3), var(--color-accent2));
}

.create-this-program-btn:active {
    transform: translateY(0);
}

.light-theme .create-this-program-btn {
    background: linear-gradient(135deg, var(--color-accent4), var(--color-accent2));
    color: var(--color-text);
    box-shadow: 0 2px 8px rgba(215, 227, 160, 0.3);
}

.light-theme .create-this-program-btn:hover {
    box-shadow: 0 4px 12px rgba(215, 227, 160, 0.4);
    background: linear-gradient(135deg, var(--color-accent2), var(--color-accent4));
}

/* Community Hub Styles */
.filter-section {
    display: flex;
    justify-content: flex-start;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 20px;
    background-color: var(--color-card);
    padding: 15px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: visible;
    position: relative;
    min-height: 60px;
}

.filter-container {
    display: flex;
    gap: 20px;
    align-items: center;
    position: relative;
    overflow: visible;
    z-index: 10;
    width: 100%;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.filter-group label {
    color: var(--color-highlight);
    font-weight: bold;
}

.filter-group select {
    background-color: var(--color-accent3);
    color: var(--color-text);
    border: 1px solid var(--color-accent3);
    padding: 8px 12px;
    border-radius: 6px;
    min-width: 200px;
}


.card-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
    align-items: stretch;
    min-height: 200px; /* Ensure container has minimum height */
}

.card {
    background-color: var(--color-card);
    padding: 25px;
    border-radius: 16px;
    box-shadow: 0px 4px 15px rgba(0,0,0,0.3);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    border-top: 3px solid var(--color-highlight);
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    min-height: 150px; /* Ensure cards have minimum height */
    border: 2px solid var(--color-highlight); /* Add visible border for debugging */
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0px 8px 20px rgba(154, 230, 110, 0.4);
}

.card h2 {
    font-size: 18px;
    margin-bottom: 15px;
    color: var(--color-text);
    font-weight: 600;
    text-align: center;
}

.metric-value {
    font-size: 36px;
    color: var(--color-text);
    margin: 10px 0;
    font-weight: 700;
    text-align: center;
    line-height: 1;
    min-height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.metric-change {
    display: none; /* Hide the small numbers below */
}

        .metric-note {
            font-size: 12px;
            color: var(--color-text);
            opacity: 0.7;
            line-height: 1.4;
            text-align: center;
        }

        /* Custom Select Styles */
        .custom-select-container {
            position: relative;
            width: 100%;
            max-width: 400px;
        }

        .select-header {
            background-color: var(--color-card);
            border: 2px solid var(--color-border);
            border-radius: 8px;
            padding: 12px 16px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .light-theme .select-header {
            background-color: white;
            border-color: var(--color-border);
            color: var(--color-text);
        }

        .light-theme .select-header:hover {
            border-color: var(--color-highlight);
            box-shadow: 0 2px 8px var(--color-shadow);
        }

        .light-theme .dropdown-content {
            background-color: white;
            border-color: var(--color-border);
            box-shadow: 0 4px 15px var(--color-shadow);
        }

        .light-theme .search-container input {
            background-color: white;
            color: var(--color-text);
            border-color: var(--color-border);
        }

        .light-theme .search-container input:focus {
            border-color: var(--color-highlight);
            box-shadow: 0 0 0 2px rgba(102, 187, 106, 0.1);
        }

        .light-theme .option-header {
            background-color: var(--color-hover);
            color: var(--color-highlight);
        }

        .light-theme .option-item {
            color: var(--color-text);
            transition: all 0.2s ease;
        }

        .light-theme .option-item:hover {
            background-color: var(--color-hover);
            transform: translateX(2px);
        }

        .light-theme .option-item.selected {
            background-color: var(--color-highlight);
            color: white;
        }

        .select-header:hover {
            border-color: var(--color-highlight);
        }

        .dropdown-arrow {
            transition: transform 0.3s ease;
        }

        .dropdown-arrow.active {
            transform: rotate(180deg);
        }

        .dropdown-content {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background-color: var(--color-card);
            border: 2px solid var(--color-border);
            border-radius: 8px;
            margin-top: 4px;
            max-height: 400px;
            overflow: hidden;
            z-index: 1000;
            display: none;
        }

        .dropdown-content.active {
            display: block;
        }

        .search-container {
            padding: 16px;
            border-bottom: 1px solid var(--color-border);
        }

        .search-container input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--color-border);
            border-radius: 6px;
            background-color: var(--color-background);
            color: var(--color-text);
            font-size: 14px;
        }

        .search-container input:focus {
            outline: none;
            border-color: var(--color-highlight);
        }

        .options-container {
            max-height: 300px;
            overflow-y: auto;
        }

        .option-group {
            border-bottom: 1px solid var(--color-border);
        }

        .option-header {
            padding: 12px 16px 8px;
            font-weight: 600;
            color: var(--color-highlight);
            background-color: rgba(154, 230, 110, 0.1);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .option-item {
            padding: 10px 16px;
            cursor: pointer;
            transition: background-color 0.2s ease;
            font-size: 14px;
        }

        .option-item:hover {
            background-color: rgba(154, 230, 110, 0.1);
        }

        .option-item.selected {
            background-color: var(--color-highlight);
            color: var(--color-background);
        }

.program-cards-container {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-top: 8px;
}

.program-card {
    background-color: rgba(23, 35, 23, 0.7);
    padding: 16px;
    border-radius: 10px;
    display: block;
    margin-bottom: 16px;
    transition: all 0.3s ease;
    border-left: 4px solid var(--color-highlight);
    position: relative;
    overflow: hidden;
    cursor: pointer;
}

.program-card:hover {
    transform: translateY(-2px);
    box-shadow: 0px 8px 20px rgba(161, 180, 84, 0.15);
    border-left-color: var(--color-accent2);
}

.program-content {
    flex: 1;
}

.program-title {
    font-size: 15px;
    font-weight: 600;
    color: var(--color-text);
    margin-bottom: 6px;
}

.program-description {
    font-size: 13px;
    color: var(--color-text);
    opacity: 0.8;
    margin-bottom: 12px;
    line-height: 1.3;
}

.program-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
}

.priority-tag {
    display: inline-block;
    padding: 3px 6px;
    border-radius: 3px;
    font-size: 11px;
    margin-top: 8px;
    font-weight: bold;
}

.priority-high {
    background-color: #FF9800;
    color: #1B3A1B;
}

.priority-immediate {
    background-color: #E53E3E;
    color: #FFFFFF;
}

.priority-medium {
    background-color: #4CAF50;
    color: #FFFFFF;
}

/* Light theme priority labels with donut chart colors */
.light-theme .priority-high {
    background-color: #FF9800 !important;
    color: #1B3A1B !important;
}

.light-theme .priority-immediate {
    background-color: #E53E3E !important;
    color: #FFFFFF !important;
}

.light-theme .priority-medium {
    background-color: #4CAF50 !important;
    color: #FFFFFF !important;
}

/* Light theme priority tag hover effects - maintain original colors */
.light-theme .program-card:hover .priority-high {
    background-color: #FF9800 !important;
    color: #1B3A1B !important;
}

.light-theme .program-card:hover .priority-immediate {
    background-color: #E53E3E !important;
    color: #FFFFFF !important;
}

.light-theme .program-card:hover .priority-medium {
    background-color: #4CAF50 !important;
    color: #FFFFFF !important;
}

.impact-estimate {
    font-size: 11px;
    color: var(--color-highlight);
    opacity: 0.9;
}

.progress-container {
    display: flex;
    flex-direction: column;
    gap: 18px;
    margin-top: 15px;
    padding: 5px;
}

.progress-item {
    margin-bottom: 5px;
    transition: transform 0.2s ease;
}

.progress-item:hover {
    transform: translateX(5px);
}

.progress-label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: 500;
}

.progress-label span:last-child {
    color: var(--color-highlight);
    font-weight: 600;
}

.progress-bar {
    width: 100%;
    height: 10px;
    background-color: rgba(34, 53, 34, 0.7);
    border-radius: 6px;
    overflow: hidden;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.2);
}

.progress-fill {
    height: 100%;
    border-radius: 6px;
    transition: width 0.5s ease;
    position: relative;
}

.progress-high { background-color: #FF6B6B; }
.progress-medium { background-color: #FFC107; }
.progress-low { background-color: #666; }

/* Screening Responses Section Styles */
.screening-part {
    background: var(--color-card);
    border-radius: 20px;
    padding: 28px;
    margin-bottom: 28px;
    border: none;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08), 0 2px 8px rgba(0, 0, 0, 0.04);
    position: relative;
    overflow: hidden;
    width: 100%;
    min-height: 260px;
    contain: layout;
}

.screening-part::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--color-highlight), var(--color-accent1), var(--color-highlight));
    border-radius: 20px 20px 0 0;
}

.response-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 25px;
    width: 100%;
    position: relative;
    contain: layout;
}

/* Part 1 specific layout - 4 boxes in one row */
.part1-grid {
    display: grid !important;
    grid-template-columns: repeat(4, 1fr) !important;
    gap: 20px;
    width: 100%;
    position: relative;
    min-height: 200px;
}

/* Force Part 1 grid to always be 4 columns */
.part1-grid {
    grid-template-columns: repeat(4, 1fr) !important;
    grid-auto-flow: row;
    align-items: start;
}

/* Override chart-card flex behavior for screening responses */
.chart-card .screening-part {
    display: block !important;
    flex: none !important;
}

.chart-card .response-grid {
    display: grid !important;
    flex: none !important;
    width: 100% !important;
}

.chart-card .part1-grid {
    display: grid !important;
    flex: none !important;
    width: 100% !important;
    min-height: 260px;
}

/* Ensure chart-card doesn't interfere with grid */
.chart-card[style*="grid-column: 1 / -1"] {
    display: block !important;
    flex-direction: unset !important;
}

/* Column Headers Styling */
.column-headers {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    align-items: center;
    padding: 6px 12px;
    margin-bottom: 8px;
    background: linear-gradient(135deg, rgba(0, 0, 0, 0.08), rgba(0, 0, 0, 0.04));
    border-radius: 6px;
    border: 1px solid rgba(0, 0, 0, 0.1);
    font-weight: 600;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--color-text);
    opacity: 0.9;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
    gap: 8px;
}

.header-label {
    grid-column: 1;
    text-align: left;
    color: var(--color-highlight);
}

.header-count {
    grid-column: 2;
    text-align: center;
    color: var(--color-accent1);
    justify-self: center;
}

.header-percent {
    grid-column: 3;
    text-align: center;
    color: var(--color-accent2);
    justify-self: center;
}

/* Custom scrollbar styling for response answers */
.response-answers::-webkit-scrollbar {
    width: 6px;
}

.response-answers::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.05);
    border-radius: 3px;
}

.response-answers::-webkit-scrollbar-thumb {
    background: var(--color-highlight);
    border-radius: 3px;
    transition: background 0.3s ease;
}

.response-answers::-webkit-scrollbar-thumb:hover {
    background: var(--color-accent1);
}

/* Firefox scrollbar */
.response-answers {
    scrollbar-width: thin;
    scrollbar-color: var(--color-highlight) rgba(0, 0, 0, 0.05);
}

.part1-grid .response-item {
    height: 260px;
    padding: 20px;
    position: relative;
    contain: layout;
    width: 100%;
    max-width: none;
    flex-shrink: 0;
    grid-column: span 1;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

/* Responsive adjustments for Part 1 */
@media (max-width: 1400px) {
    .part1-grid {
        display: grid !important;
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 18px;
        width: 100%;
    }
}

@media (max-width: 768px) {
    .part1-grid {
        display: grid !important;
        grid-template-columns: 1fr !important;
        gap: 16px;
        width: 100%;
    }
    
    .response-item {
        padding: 20px;
        min-height: 140px;
        min-width: 100%;
    }
    
    .response-answer-item {
        padding: 12px 16px;
    }
    
    .answer-count {
        padding: 6px 12px;
        font-size: 11px;
        min-width: 45px;
    }
}

.response-item {
    background: var(--color-card);
    border-radius: 20px;
    padding: 24px;
    border: none;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    height: 260px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08), 0 2px 8px rgba(0, 0, 0, 0.04);
    position: relative;
    overflow: hidden;
    animation: fadeInUp 0.6s ease-out;
    contain: layout style;
    box-sizing: border-box;
    width: 100%;
    flex-shrink: 0;
}

.response-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.12), 0 4px 15px rgba(0, 0, 0, 0.08);
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOutRight {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}

.response-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--color-highlight), var(--color-accent1));
    border-radius: 16px 16px 0 0;
}

.response-item:hover {
    background: var(--color-card);
    border-color: var(--color-highlight);
    transform: translateY(-4px);
    box-shadow: 0 12px 30px rgba(161, 180, 84, 0.2);
}

.response-item:hover::before {
    background: linear-gradient(90deg, var(--color-accent1), var(--color-highlight));
}

.response-question {
    font-weight: 600;
    color: var(--color-text);
    margin-bottom: 16px;
    font-size: 13px;
    opacity: 0.95;
    border-bottom: 1px solid var(--color-border);
    padding-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    height: 50px;
    display: flex;
    align-items: center;
    flex-shrink: 0;
    line-height: 1.2;
}

.response-answers {
    height: 210px;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    width: 100%;
    overflow-y: auto;
    overflow-x: hidden;
    padding-right: 8px;
}

.response-answer-item {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    align-items: center;
    padding: 7px 12px;
    margin-bottom: 6px;
    background: rgba(0, 0, 0, 0.05);
    border-radius: 8px;
    border: none;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    position: relative;
    width: 100%;
    box-sizing: border-box;
    contain: layout;
    gap: 8px;
    cursor: pointer;
}

.response-answer-item:hover {
    background: rgba(0, 0, 0, 0.08);
    transform: translateY(-1px);
    box-shadow: 0 4px 20px rgba(161, 180, 84, 0.15), 0 2px 12px rgba(161, 180, 84, 0.1);
}

.response-answer-item:last-child {
    margin-bottom: 0;
}

.response-answer-item:hover {
    background: rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12), 0 4px 15px rgba(0, 0, 0, 0.08);
}

.answer-label {
    font-size: 11px;
    color: var(--color-text);
    opacity: 0.95;
    font-weight: 500;
    line-height: 1.3;
    padding: 2px 0;
    grid-column: 1;
    text-align: left;
    transition: all 0.3s ease;
}

.response-answer-item:hover .answer-label {
    color: var(--color-highlight);
    text-shadow: 0 0 8px rgba(161, 180, 84, 0.3);
}

.answer-count {
    font-weight: 600;
    color: var(--color-text);
    font-size: 11px;
    padding: 2px 6px;
    background: rgba(0, 0, 0, 0.08);
    border-radius: 4px;
    min-width: 30px;
    text-align: center;
    grid-column: 2;
    justify-self: center;
    transition: all 0.3s ease;
}

.response-answer-item:hover .answer-count {
    background: rgba(161, 180, 84, 0.15);
    color: var(--color-highlight);
    box-shadow: 0 0 12px rgba(161, 180, 84, 0.3);
}

/* Removed glowy shine effect */

.answer-percentage {
    font-size: 10px;
    color: var(--color-text);
    opacity: 0.9;
    font-weight: 500;
    background: rgba(0, 0, 0, 0.05);
    padding: 2px 6px;
    border-radius: 4px;
    min-width: 40px;
    text-align: center;
    grid-column: 3;
    justify-self: center;
    transition: all 0.3s ease;
}

.response-answer-item:hover .answer-percentage {
    background: rgba(161, 180, 84, 0.1);
    color: var(--color-accent1);
    box-shadow: 0 0 10px rgba(161, 180, 84, 0.25);
}

.loading-placeholder {
    color: var(--color-text);
    opacity: 0.5;
    font-style: italic;
    text-align: center;
    padding: 12px;
    font-size: 12px;
}

.no-data-message {
    color: var(--color-text);
    opacity: 0.6;
    text-align: center;
    padding: 12px;
    font-style: italic;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    min-height: 40px;
    font-size: 12px;
}

/* Light theme adjustments */
.light-theme .screening-part {
    background: var(--color-card);
    border: 1px solid var(--color-border);
    box-shadow: 0 6px 20px var(--color-shadow);
}

.light-theme .screening-part::before {
    background: linear-gradient(90deg, var(--color-highlight), var(--color-accent1));
}

.light-theme .response-item {
    background: var(--color-card);
    border: 1px solid var(--color-border);
    box-shadow: 0 6px 20px var(--color-shadow);
}

.light-theme .response-item:hover {
    background: var(--color-card);
    box-shadow: 0 8px 25px rgba(102, 187, 106, 0.15);
    border-color: var(--color-highlight);
}

.light-theme .response-item::before {
    background: linear-gradient(90deg, var(--color-highlight), var(--color-accent1));
}

.light-theme .response-answer-item {
    background: rgba(0, 0, 0, 0.02);
    border: 1px solid var(--color-border);
}

.light-theme .response-answer-item:hover {
    background: rgba(102, 187, 106, 0.05);
    box-shadow: 0 4px 12px var(--color-shadow);
    border-color: var(--color-highlight);
}

.light-theme .column-headers {
    background: linear-gradient(135deg, rgba(0, 0, 0, 0.03), rgba(0, 0, 0, 0.01));
    border-color: var(--color-border);
    box-shadow: 0 1px 4px var(--color-shadow);
}

.light-theme .header-label {
    color: var(--color-highlight);
}

.light-theme .header-count {
    color: var(--color-accent1);
}

.light-theme .header-percent {
    color: var(--color-accent2);
}

.light-theme .response-item {
    background: var(--color-card);
    border: none;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
}

.light-theme .response-item:hover {
    background: var(--color-card);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.light-theme .response-answer-item {
    background: rgba(0, 0, 0, 0.02);
}

.light-theme .response-answer-item:hover {
    background: rgba(0, 0, 0, 0.04);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

/* Refresh Button Styles */
.refresh-btn {
    background: linear-gradient(135deg, var(--color-primary), var(--color-accent1));
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.refresh-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
    background: linear-gradient(135deg, var(--color-accent1), var(--color-primary));
}

.refresh-btn:active {
    transform: translateY(0);
}

.btn-icon {
    font-size: 16px;
}

        .btn-text {
            font-weight: 500;
        }
        
        /* Date Examples Styling */
        .date-examples-container {
            padding: 20px;
            background: rgba(161, 180, 84, 0.05);
            border-radius: 10px;
            margin: 15px 0;
            border: 1px solid rgba(161, 180, 84, 0.1);
        }
        
        .date-examples-container h4 {
            color: var(--color-highlight);
            margin-bottom: 15px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .date-example-item {
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            background: rgba(0, 0, 0, 0.03);
            border-radius: 6px;
            border-left: 3px solid var(--color-accent3);
        }
        
        .date-example-item:last-child {
            margin-bottom: 15px;
        }
        
        .date-example-label {
            font-weight: 500;
            color: var(--color-text);
            font-size: 13px;
        }
        
        .date-example-value {
            color: var(--color-accent1);
            font-weight: 600;
            font-size: 12px;
            background: rgba(161, 180, 84, 0.1);
            padding: 4px 8px;
            border-radius: 4px;
        }
        
        .light-theme .date-example-value {
            background: rgba(118, 187, 110, 0.1);
            color: var(--color-accent3);
        }

/* Prevent layout shifts when switching themes */
body {
    min-height: 100vh;
    transition: background-color 0.3s ease, color 0.3s ease;
}

/* Ensure consistent spacing and alignment */
.dashboard,
.navbar,
.stat-card,
.chart-card,
.filter-section,
.screening-part,
.response-item,
.program-card,
.card {
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
}

/* Light theme specific improvements */
.light-theme .stat-value {
    color: var(--color-highlight);
}

.light-theme .stat-label {
    color: var(--color-text);
    opacity: 0.8;
}

.light-theme .chart-description {
    color: var(--color-text);
    opacity: 0.7;
}

/* Improved button styling for light theme */
.light-theme .btn,
.light-theme button {
    transition: all 0.3s ease;
}

.light-theme .btn:hover,
.light-theme button:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 187, 106, 0.2);
}



/* Consistent form styling */
.light-theme input,
.light-theme select,
.light-theme textarea {
    background-color: white;
    color: var(--color-text);
    border-color: var(--color-border);
    transition: all 0.3s ease;
}

.light-theme input:focus,
.light-theme select:focus,
.light-theme textarea:focus {
    border-color: var(--color-highlight);
    box-shadow: 0 0 0 2px rgba(102, 187, 106, 0.1);
}

/* Improved table styling for light theme */
.light-theme table {
    background-color: var(--color-card);
    border-color: var(--color-border);
}

.light-theme th {
    background-color: var(--color-hover);
    color: var(--color-text);
    border-color: var(--color-border);
}

.light-theme td {
    border-color: var(--color-border);
}

.light-theme tr:hover {
    background-color: var(--color-hover);
}

/* Consistent spacing for light theme */
.light-theme .section,
.light-theme .container,
.light-theme .wrapper {
    background-color: var(--color-card);
    border-color: var(--color-border);
    box-shadow: 0 2px 8px var(--color-shadow);
}

/* Ensure seamless theme switching without breaking layout */
body {
    box-sizing: border-box;
}

/* Final catch-all for any remaining green text in light theme */
.light-theme * {
    color: inherit;
}

.light-theme .card,
.light-theme .stat-card,
.light-theme .chart-card,
.light-theme .response-item,
.light-theme .response-answer-item,
.light-theme .program-card,
.light-theme .segment,
.light-theme .segment.compact {
    color: #1B3A1B !important;
}

/* Ensure all text in light theme is dark */
.light-theme h1,
.light-theme h2,
.light-theme h3,
.light-theme h4,
.light-theme h5,
.light-theme h6,
.light-theme p,
.light-theme span,
.light-theme div,
.light-theme label,
.light-theme strong,
.light-theme b {
    color: #1B3A1B !important;
}

/* Exclude intentional colored elements */
.light-theme .priority-tag,
.light-theme .segment.compact[data-risk-level] .segment-label::before,
.light-theme .whz-item .whz-dot,
.light-theme .color-indicator {
    color: inherit !important;
}

/* Segment percentage styling */
.segment.compact .segment-percentage {
    background-color: rgba(102, 187, 106, 0.08) !important;
    color: #1B3A1B !important;
    padding: 2px 6px !important;
    border-radius: 4px !important;
    font-weight: 500 !important;
    min-width: 32px !important;
    text-align: center !important;
}

.light-theme .segment.compact .segment-percentage {
    background-color: rgba(102, 187, 106, 0.1) !important;
    color: #1B3A1B !important;
    border: 1px solid rgba(102, 187, 106, 0.15) !important;
}

/* Age Range Controls Styles */
.age-range-controls button:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

.age-range-controls input:hover,
.age-range-controls select:hover {
    border-color: var(--primary-color);
}

.age-range-controls input:focus,
.age-range-controls select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px rgba(161, 180, 84, 0.2);
}

/* Smooth transitions for theme switching */
.light-theme,
.dark-theme {
    transition: background-color 0.3s ease, color 0.3s ease;
}

/* Chart text theme support - More specific selectors */
.dark-theme .population-scale,
.dark-theme .population-scale *,
.dark-theme .population-scale .scale-label {
    color: #FFFFFF !important;
}

.light-theme .population-scale,
.light-theme .population-scale *,
.light-theme .population-scale .scale-label {
    color: #000000 !important;
}

/* Chart legend and labels theme support */
.dark-theme .trends-legend,
.dark-theme .trends-legend *,
.dark-theme .trends-legend .legend-item {
    color: #FFFFFF !important;
}

.light-theme .trends-legend,
.light-theme .trends-legend *,
.light-theme .trends-legend .legend-item {
    color: #000000 !important;
}

/* Chart title and axis labels theme support */
.dark-theme .chart-title,
.dark-theme .chart-axis-label,
.dark-theme .chart-card h3,
.dark-theme .chart-description {
    color: #FFFFFF !important;
}

.light-theme .chart-title,
.light-theme .chart-axis-label,
.light-theme .chart-card h3,
.light-theme .chart-description {
    color: #000000 !important;
}

/* Force white text for all chart elements in dark theme */
.dark-theme .chart-card,
.dark-theme .chart-card *,
.dark-theme .trends-chart-container,
.dark-theme .trends-chart-container *,
.dark-theme .age-classification-chart-container,
.dark-theme .age-classification-chart-container * {
    color: #FFFFFF !important;
}

/* Force black text for all chart elements in light theme */
.light-theme .chart-card,
.light-theme .chart-card *,
.light-theme .trends-chart-container,
.light-theme .trends-chart-container *,
.light-theme .age-classification-chart-container,
.light-theme .age-classification-chart-container * {
    color: #000000 !important;
}

/* ===== BARANGAY DROPDOWN FUNCTIONALITY CSS ===== */
.dropdown-content {
    display: none;
    position: absolute;
    background-color: var(--color-card);
    min-width: 250px;
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid var(--color-border);
    border-radius: 8px;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    z-index: 1000;
    top: 100%;
    left: 0;
    right: 0;
}

.dropdown-content.show {
    display: block;
}

.custom-select-container {
    position: relative;
    display: inline-block;
    width: 100%;
}

.select-header {
    background-color: var(--color-card);
    border: 1px solid var(--color-border);
    border-radius: 6px;
    padding: 8px 12px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s ease;
    color: var(--color-text);
}

.select-header:hover {
    border-color: var(--color-highlight);
    background-color: var(--color-hover);
}

.dropdown-arrow {
    margin-left: 8px;
    transition: transform 0.3s ease;
    color: var(--color-text);
}

.dropdown-content.show + .select-header .dropdown-arrow {
    transform: rotate(180deg);
}

.option-item {
    padding: 10px 15px;
    cursor: pointer;
    border-bottom: 1px solid var(--color-border);
    transition: all 0.3s ease;
    color: var(--color-text);
}

.option-item:hover {
    background-color: var(--color-hover);
    color: var(--color-highlight);
}

.option-item:last-child {
    border-bottom: none;
}

.option-group {
    margin-bottom: 10px;
}

.option-header {
    padding: 8px 15px;
    background-color: var(--color-highlight);
    color: white;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.search-container {
    padding: 10px;
    border-bottom: 1px solid var(--color-border);
}

.search-container input {
    width: 100%;
    padding: 8px;
    border: 1px solid var(--color-border);
    border-radius: 4px;
    background-color: var(--color-bg);
    color: var(--color-text);
    font-size: 14px;
}

.search-container input:focus {
    outline: none;
    border-color: var(--color-highlight);
}

.small-width {
    max-width: 200px;
}

/* Dark theme dropdown styles */
.dark-theme .dropdown-content {
    background-color: var(--color-card);
    border-color: var(--color-border);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
}

.dark-theme .select-header {
    background-color: var(--color-card);
    border-color: var(--color-border);
    color: var(--color-text);
}

.dark-theme .option-item {
    color: var(--color-text);
}

/* Light theme dropdown styles */
.light-theme .dropdown-content {
    background-color: var(--color-card);
    border-color: var(--color-border);
    box-shadow: 0 8px 16px var(--color-shadow);
}

.light-theme .select-header {
    background-color: var(--color-card);
    border-color: var(--color-border);
    color: var(--color-text);
}

.light-theme .option-item {
    color: var(--color-text);
}

/* Severe Cases List Styles */
.severe-cases-container {
    height: 400px;
    max-height: 400px;
    width: 100%;
    max-width: 100%;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    position: relative;
    padding: 10px;
    box-sizing: border-box;
}

.severe-cases-list {
    height: 100%;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding-right: 5px;
}

.severe-case-item {
    background: var(--color-bg);
    border: 1px solid var(--color-border);
    border-radius: 8px;
    padding: 12px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.2s ease;
    cursor: pointer;
    min-height: 60px;
}

.severe-case-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.severe-case-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
    flex: 1;
}

.severe-case-name {
    font-weight: 600;
    font-size: 14px;
    color: white;
}

.severe-case-details {
    display: flex;
    gap: 12px;
    font-size: 12px;
    color: rgba(255, 255, 255, 0.8);
}

.severe-case-classification {
    font-weight: 600;
    font-size: 12px;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    background: rgba(255, 255, 255, 0.2);
}

.severe-case-item.severely-underweight {
    background: linear-gradient(135deg, #E91E63, #F06292);
    border-color: #E91E63;
}

.severe-case-item.severely-wasted {
    background: linear-gradient(135deg, #D32F2F, #E57373);
    border-color: #D32F2F;
}

.severe-case-item.severely-stunted {
    background: linear-gradient(135deg, #673AB7, #9575CD);
    border-color: #673AB7;
}

.severe-case-item.severely-underweight-bmi-adult {
    background: linear-gradient(135deg, #E91E63, #F06292);
    border-color: #E91E63;
}

.severe-case-item.severely-underweight-(bmi-adult) {
    background: linear-gradient(135deg, #E91E63, #F06292) !important;
    border-color: #E91E63 !important;
    color: white !important;
}

/* Additional rule for any BMI Adult severe case */
.severe-case-item[class*="bmi-adult"] {
    background: linear-gradient(135deg, #E91E63, #F06292) !important;
    border-color: #E91E63 !important;
    color: white !important;
}

.severe-cases-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: var(--color-text);
    font-size: 16px;
    text-align: center;
    opacity: 0.7;
}

/* Custom scrollbar for severe cases list */
.severe-cases-list::-webkit-scrollbar {
    width: 6px;
}

.severe-cases-list::-webkit-scrollbar-track {
    background: var(--color-bg);
    border-radius: 3px;
}

.severe-cases-list::-webkit-scrollbar-thumb {
    background: var(--color-border);
    border-radius: 3px;
}

.severe-cases-list::-webkit-scrollbar-thumb:hover {
    background: var(--color-text);
}

/* Responsive adjustments for Severe Cases List */
@media (max-width: 1200px) {
    .severe-cases-container {
        height: 350px;
    }
    
    .severe-case-item {
        padding: 10px 14px;
        min-height: 55px;
    }
    
    .severe-case-name {
        font-size: 13px;
    }
    
    .severe-case-details {
        font-size: 11px;
        gap: 10px;
    }
}

@media (max-width: 768px) {
    .severe-cases-container {
        height: 300px;
        padding: 8px;
    }
    
    .severe-case-item {
        padding: 8px 12px;
        min-height: 50px;
    }
    
    .severe-case-name {
        font-size: 12px;
    }
    
    .severe-case-details {
        font-size: 10px;
        gap: 8px;
    }
    
    .severe-case-classification {
        font-size: 10px;
        padding: 3px 6px;
    }
}

/* ===== MODERN 2025 MOBILE NAVIGATION ===== */

/* Mobile Navigation Toggle Button */
.mobile-nav-toggle {
    position: fixed;
    top: 20px;
    left: 20px;
    z-index: 10001;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--color-highlight), rgba(161, 180, 84, 0.8));
    border: none;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    cursor: pointer;
    transition: all 0.3s ease;
    display: none;
}

.mobile-nav-toggle:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
}

.mobile-nav-toggle .toggle-icon {
    color: white;
    font-size: 20px;
    font-weight: bold;
}


/* Mobile Close Button in Navbar */
.mobile-nav-close {
    position: absolute;
    top: 15px;
    right: 15px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: none;
}

.mobile-nav-close:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.1);
}

/* Desktop Navbar Toggle Button - Removed duplicate */

/* Hover state - navbar expanded (shows full width) */
.navbar:hover {
    transform: translateX(0); /* Show full navbar */
    box-shadow: 5px 0 25px rgba(0, 0, 0, 0.2);
    backdrop-filter: blur(15px);
}

/* Body padding will be handled by base styles */

/* Content area animation - removed since we're using transform now */

/* Text content visibility - simple fade */
.navbar-logo-text,
.navbar span:not(.navbar-icon),
.navbar-footer {
    opacity: 0;
    transition: opacity 0.2s ease;
    overflow: hidden;
    white-space: nowrap;
}

/* Minimized navbar - center the logo icon */
.navbar {
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    align-items: center;
    padding-top: 20px;
}

/* Navbar icon hover effect when minimized */
.navbar-icon {
    transition: transform 0.2s ease, color 0.2s ease;
}

.navbar:hover .navbar-icon {
    transform: scale(1.05);
    color: var(--color-primary);
}

/* Expanded navbar state - show everything */
.navbar:hover {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: stretch;
    padding-top: 0;
}

.navbar:hover .navbar-logo-text,
.navbar:hover span:not(.navbar-icon),
.navbar:hover .navbar-footer {
    opacity: 1;
}

/* ===== MOBILE TOP NAVIGATION STYLES ===== */

/* Mobile Top Navigation Bar */
.mobile-top-nav {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    width: 100vw;
    max-width: 100vw;
    z-index: 10000;
    background: var(--color-card);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid var(--color-border);
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
    overflow-x: hidden;
}

.mobile-nav-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    height: 60px;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    overflow-x: hidden;
}

.mobile-nav-logo {
    display: flex;
    align-items: center;
    gap: 10px;
}

.mobile-logo-img {
    width: 32px;
    height: 32px;
    border-radius: 6px;
}

.mobile-logo-text {
    font-size: 18px;
    font-weight: 600;
    color: var(--color-text);
}

.mobile-nav-icons {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}

.mobile-nav-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: var(--color-bg);
    color: var(--color-text);
    text-decoration: none;
    transition: all 0.3s ease;
    border: 1px solid var(--color-border);
}

.mobile-nav-icon:hover {
    background: var(--color-highlight);
    color: white;
    transform: scale(1.05);
}

.mobile-nav-icon.mobile-nav-logout {
    background: rgba(255, 82, 82, 0.1);
    color: #ff5252;
    border-color: rgba(255, 82, 82, 0.3);
}

.mobile-nav-icon.mobile-nav-logout:hover {
    background: #ff5252;
    color: white;
}

.nav-icon {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
}

/* Mobile Styles */
@media (max-width: 768px) {
    .mobile-top-nav {
        display: block !important;
    }
    
    .navbar {
        display: none !important;
    }
    
    body {
        padding-left: 0 !important;
        padding-top: 60px !important;
        width: 100vw !important;
        max-width: 100vw !important;
        overflow-x: hidden !important;
        min-height: 100vh !important;
    }
    
    .dashboard {
        margin-left: 0 !important;
        width: 100vw !important;
        max-width: 100vw !important;
        overflow-x: hidden !important;
        padding: 0 15px !important;
        box-sizing: border-box !important;
    }
    
    .mobile-nav-toggle,
    .mobile-nav-close,
    .nav-overlay {
        display: none !important;
    }
    
    /* Prevent horizontal scrolling on all elements */
    * {
        max-width: 100vw !important;
        overflow-x: hidden !important;
    }
    
    /* Mobile Layout - Mimic Desktop in Smaller Version */
    
    /* Community Metrics Cards - Display in one row on mobile */
    .card-container {
        display: flex !important;
        flex-wrap: nowrap !important;
        gap: 8px !important;
        overflow-x: auto !important;
        padding: 10px 15px !important;
        margin: 0 !important;
        -webkit-overflow-scrolling: touch !important;
    }
    
    .card {
        min-width: 120px !important;
        width: 120px !important;
        padding: 12px !important;
        font-size: 12px !important;
        flex-shrink: 0 !important;
    }
    
    .card h2 {
        font-size: 11px !important;
        margin-bottom: 8px !important;
        line-height: 1.2 !important;
    }
    
    .metric-value {
        font-size: 18px !important;
        font-weight: bold !important;
        margin-bottom: 4px !important;
    }
    
    .metric-change {
        font-size: 10px !important;
        margin-bottom: 4px !important;
    }
    
    .metric-note {
        font-size: 9px !important;
        line-height: 1.2 !important;
        opacity: 0.8 !important;
    }
    
    /* Dashboard Header - Smaller on mobile */
    .dashboard-header h1 {
        font-size: 24px !important;
        margin-bottom: 10px !important;
    }
    
    /* Filter Section - Mobile 2-Row Layout */
    .filter-section {
        padding: 8px 15px !important;
        margin-bottom: 12px !important;
        border-radius: 8px !important;
    }
    
    .filter-container {
        display: flex !important;
        flex-direction: column !important;
        gap: 6px !important;
        align-items: stretch !important;
    }
    
    /* First Row - Labels */
    .filter-labels-row {
        display: flex !important;
        gap: 8px !important;
        margin-bottom: 4px !important;
    }
    
    .filter-label {
        flex: 1 !important;
        font-size: 10px !important;
        font-weight: 600 !important;
        color: var(--color-text) !important;
        text-align: center !important;
        padding: 2px 0 !important;
    }
    
    .filter-group {
        display: contents !important;
    }
    
    .filter-group label {
        display: none !important;
    }
    
    /* Second Row - Dropdowns */
    .filter-dropdowns-row {
        display: flex !important;
        gap: 8px !important;
        align-items: stretch !important;
    }
    
    .custom-select-container {
        width: 100% !important;
        position: relative !important;
    }
    
    .custom-select-container.small-width {
        width: 100% !important;
    }
    
    .select-header {
        font-size: 11px !important;
        padding: 6px 8px !important;
        height: auto !important;
        min-height: 32px !important;
        background: var(--color-card) !important;
        border: 1px solid var(--color-border) !important;
        border-radius: 6px !important;
        cursor: pointer !important;
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        transition: all 0.3s ease !important;
        -webkit-tap-highlight-color: transparent !important;
        user-select: none !important;
        touch-action: manipulation !important;
        flex: 1 !important;
    }
    
    .select-header:hover,
    .select-header:active {
        border-color: var(--color-highlight) !important;
        background: var(--color-bg) !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
    }
    
    .dropdown-arrow {
        font-size: 12px !important;
        transition: transform 0.3s ease !important;
        pointer-events: none !important;
    }
    
    .select-header.active .dropdown-arrow {
        transform: rotate(180deg) !important;
    }
    
    /* Mobile Dropdown Styling */
    .dropdown-content {
        position: absolute !important;
        top: 100% !important;
        left: 0 !important;
        right: 0 !important;
        background: var(--color-card) !important;
        border: 2px solid var(--color-border) !important;
        border-radius: 8px !important;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
        z-index: 1000 !important;
        max-height: 200px !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch !important;
    }
    
    .dropdown-content.active {
        display: block !important;
        opacity: 1 !important;
        visibility: visible !important;
        transform: translateY(0) !important;
    }
    
    .search-container {
        padding: 8px !important;
        border-bottom: 1px solid var(--color-border) !important;
        position: sticky !important;
        top: 0 !important;
        background: var(--color-card) !important;
        z-index: 10 !important;
    }
    
    .search-container input {
        width: 100% !important;
        padding: 8px 12px !important;
        border: 1px solid var(--color-border) !important;
        border-radius: 6px !important;
        font-size: 14px !important;
        background: var(--color-bg) !important;
        color: var(--color-text) !important;
    }
    
    .options-container {
        max-height: 150px !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch !important;
    }
    
    .option-item {
        padding: 12px 16px !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
        border-bottom: 1px solid var(--color-border) !important;
        font-size: 14px !important;
        -webkit-tap-highlight-color: transparent !important;
        touch-action: manipulation !important;
    }
    
    .option-item:hover,
    .option-item:active {
        background: var(--color-highlight) !important;
        color: white !important;
    }
    
    .option-item:last-child {
        border-bottom: none !important;
    }
    
    /* Charts and content - Smaller on mobile */
    .chart-container {
        padding: 8px !important;
        margin: 8px 0 !important;
    }
    
    .chart-container h3 {
        font-size: 14px !important;
        margin-bottom: 6px !important;
    }
    
    /* Chart Cards - Better space utilization */
    .chart-card {
        padding: 4px !important;
        margin: 2px 0 !important;
        min-height: 180px !important;
        width: 100% !important;
    }
    
    .chart-card h3 {
        font-size: 12px !important;
        margin-bottom: 2px !important;
        line-height: 1.2 !important;
    }
    
    .chart-description {
        font-size: 9px !important;
        margin-bottom: 2px !important;
        line-height: 1.2 !important;
        opacity: 0.8 !important;
    }
    
    /* Donut Charts - Perfect center alignment */
    .donut-chart-container {
        height: 160px !important;
        padding: 0 !important;
        margin: 0 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        position: relative !important;
    }
    
    .donut-chart {
        width: 120px !important;
        height: 120px !important;
        margin: 0 auto !important;
        position: relative !important;
    }
    
    .donut-center-text {
        font-size: 16px !important;
        font-weight: bold !important;
        position: absolute !important;
        top: 50% !important;
        left: 50% !important;
        transform: translate(-50%, -50%) !important;
        z-index: 15 !important;
        text-align: center !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 50px !important;
        height: 50px !important;
        border-radius: 50% !important;
        background: var(--color-card) !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
    }
    
    .donut-chart::before {
        width: 50% !important;
        height: 50% !important;
        top: 50% !important;
        left: 50% !important;
        transform: translate(-50%, -50%) !important;
    }
    
    /* Ensure donut center text is always perfectly centered */
    .donut-center-text {
        line-height: 1 !important;
        padding: 0 !important;
        margin: 0 !important;
        border: 2px solid var(--color-border) !important;
    }
    
    /* Dark theme support for donut center */
    .dark-theme .donut-center-text {
        background: var(--color-card) !important;
        color: #FFFFFF !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }
    
    /* Light theme support for donut center */
    .light-theme .donut-center-text {
        background: #FFFFFF !important;
        color: var(--color-text) !important;
        border-color: var(--color-border) !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
    }
    
    /* Chart Segments - Smaller on Mobile */
    .segments {
        margin-top: 8px !important;
        gap: 4px !important;
    }
    
    .segment {
        padding: 4px 6px !important;
        font-size: 10px !important;
        border-radius: 4px !important;
    }
    
    .segment-dot {
        width: 8px !important;
        height: 8px !important;
    }
    
    .segment-label {
        font-size: 10px !important;
    }
    
    .segment-count {
        font-size: 10px !important;
    }
    
    /* Chart Row - Single Column on Mobile */
    .chart-row {
        display: flex !important;
        flex-direction: column !important;
        gap: 8px !important;
    }
    
    /* Critical Alerts - Compact on mobile */
    .critical-alerts {
        padding: 10px !important;
        margin: 10px 0 !important;
    }
    
    .critical-alerts h3 {
        font-size: 16px !important;
        margin-bottom: 8px !important;
    }
    
    .alert-item {
        padding: 8px !important;
        margin-bottom: 6px !important;
        font-size: 12px !important;
    }
    
    .alert-item h4 {
        font-size: 13px !important;
        margin-bottom: 4px !important;
    }
    
    .alert-item p {
        font-size: 11px !important;
        margin-bottom: 4px !important;
    }
    
    /* Intelligent Programs - Compact on mobile */
    .intelligent-programs {
        padding: 10px !important;
        margin: 10px 0 !important;
    }
    
    .intelligent-programs h3 {
        font-size: 16px !important;
        margin-bottom: 8px !important;
    }
    
    .program-card {
        padding: 8px !important;
        margin-bottom: 6px !important;
        font-size: 12px !important;
    }
    
    .program-title {
        font-size: 13px !important;
        margin-bottom: 4px !important;
    }
    
    .program-description {
        font-size: 11px !important;
        margin-bottom: 4px !important;
        line-height: 1.3 !important;
    }
    
    /* Geographic Chart - Compact spacing */
    .geographic-chart-container {
        padding: 4px !important;
        margin: 4px 0 !important;
        height: 200px !important;
    }
    
    .geographic-chart-container h3 {
        font-size: 13px !important;
        margin-bottom: 4px !important;
    }
    
    /* Trends Chart - Mobile 2-Row Layout */
    .trends-chart-container {
        padding: 2px !important;
        margin: 2px 0 !important;
        height: 180px !important;
        width: 100% !important;
    }
    
    .trends-chart-container h3 {
        font-size: 12px !important;
        margin-bottom: 2px !important;
        line-height: 1.2 !important;
    }
    
    /* Trend Chart Title and Description Row */
    .trends-chart-container .chart-title-row {
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        margin-bottom: 4px !important;
    }
    
    .trends-chart-container .chart-title-row h3 {
        margin-bottom: 0 !important;
        flex: 1 !important;
    }
    
    .trends-chart-container .chart-description {
        font-size: 9px !important;
        margin-bottom: 0 !important;
        opacity: 0.8 !important;
        flex: 1 !important;
    }
    
    /* Trend Chart Filter Row */
    .trends-chart-container .chart-filter-row {
        display: flex !important;
        gap: 6px !important;
        margin-bottom: 4px !important;
        align-items: center !important;
    }
    
    .trends-chart-container .chart-filter-row .filter-label {
        font-size: 9px !important;
        font-weight: 500 !important;
        color: var(--color-text-secondary) !important;
        min-width: 40px !important;
    }
    
    .trends-chart-container .chart-filter-row .date-input {
        flex: 1 !important;
        padding: 4px 6px !important;
        font-size: 10px !important;
        border: 1px solid var(--color-border) !important;
        border-radius: 4px !important;
        background: var(--color-card) !important;
        color: var(--color-text) !important;
    }
    
    .trends-chart-container .chart-filter-row .generate-btn {
        padding: 4px 8px !important;
        font-size: 10px !important;
        border-radius: 4px !important;
        background: var(--color-highlight) !important;
        color: white !important;
        border: none !important;
        cursor: pointer !important;
    }
    
    /* Age Classification Chart - Compact space utilization */
    .age-classification-chart-container {
        padding: 2px !important;
        margin: 2px 0 !important;
        height: 140px !important;
        width: 100% !important;
    }
    
    .age-classification-chart-container h3 {
        font-size: 11px !important;
        margin-bottom: 2px !important;
        line-height: 1.2 !important;
    }
    
    /* Chart canvas optimization */
    .trends-chart-container canvas,
    .age-classification-chart-container canvas {
        width: 100% !important;
        height: 100% !important;
        max-width: none !important;
        max-height: none !important;
    }
    
    /* Bar Charts - Compact spacing */
    .bar-chart-container {
        padding: 4px !important;
        margin: 4px 0 !important;
        height: 160px !important;
    }
    
    .bar-chart-container h3 {
        font-size: 13px !important;
        margin-bottom: 4px !important;
    }
    
    /* Ensure content doesn't cause horizontal scroll */
    .dashboard-content,
    .dashboard-header,
    .dashboard-stats,
    .dashboard-cards {
        width: 100% !important;
        max-width: 100% !important;
        overflow-x: hidden !important;
        box-sizing: border-box !important;
    }
}

/* Desktop Styles */
@media (min-width: 769px) {
    .mobile-top-nav {
        display: none !important;
    }
    
    .navbar {
        display: flex !important;
    }
    
    body {
        padding-top: 0 !important;
    }
}

/* Desktop Styles */
@media (min-width: 769px) {
    .mobile-nav-toggle,
    .mobile-nav-close,
    .nav-overlay {
        display: none !important;
    }
    
    .navbar:hover {
        width: 320px !important; /* Hover: expanded */
    }
    
    /* Body padding will be handled by JavaScript */
    
    .dashboard {
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
}


/* Light Theme Adjustments */
.light-theme .mobile-nav-toggle {
    background: linear-gradient(135deg, var(--color-highlight), rgba(142, 185, 110, 0.8));
    box-shadow: 0 4px 15px rgba(142, 185, 110, 0.3);
}

.light-theme .desktop-minimize-toggle {
    background: linear-gradient(135deg, var(--color-highlight), rgba(142, 185, 110, 0.8));
    box-shadow: 0 4px 15px rgba(142, 185, 110, 0.3);
}
</style>
<body class="light-theme">

    <!-- Mobile Top Navigation -->
    <nav class="mobile-top-nav" id="mobileTopNav">
        <div class="mobile-nav-container">
            <div class="mobile-nav-logo">
                <img src="/logo.png" alt="NutriSaur" class="mobile-logo-img">
                <span class="mobile-logo-text">NutriSaur</span>
            </div>
            <div class="mobile-nav-icons">
                <a href="dash" class="mobile-nav-icon" title="Dashboard">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                </a>
                <a href="screening" class="mobile-nav-icon" title="MHO Assessment">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 11H5a2 2 0 0 0-2 2v3c0 1.1.9 2 2 2h4m0-7V9a2 2 0 0 1 2-2h4m0 0V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v2m0 0h4m0 0v2"></path>
                    </svg>
                </a>
                <a href="event" class="mobile-nav-icon" title="Event Notifications">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12,6 12,12 16,14"></polyline>
                    </svg>
                </a>
                <a href="settings" class="mobile-nav-icon" title="Settings">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                </a>
                <a href="logout" class="mobile-nav-icon mobile-nav-logout" title="Logout">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16,17 21,12 16,7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                </a>
            </div>
        </div>
    </nav>

    <!-- Desktop Sidebar Navigation (unchanged) -->
    <div class="navbar" id="navbar">
        <div class="navbar-header">
            
            
            <div class="navbar-logo">
                <div class="navbar-logo-icon">
                    <img src="/logo.png" alt="Logo" style="width: 40px; height: 40px;">
                </div>
                <div class="navbar-logo-text">NutriSaur</div>
            </div>
        </div>
        <div class="navbar-menu">
            <ul>
                <li><a href="dash"><span class="navbar-icon"></span><span>Dashboard</span></a></li>
                <li><a href="screening"><span class="navbar-icon"></span><span>MHO Assessment</span></a></li>
                <li><a href="event"><span class="navbar-icon"></span><span>Nutrition Event Notifications</span></a></li>
                
                <li><a href="ai"><span class="navbar-icon"></span><span>Chatbot & AI Logs</span></a></li>
                <li><a href="settings"><span class="navbar-icon"></span><span>Settings & Admin</span></a></li>
                <li><a href="logout" style="color: #ff5252;"><span class="navbar-icon"></span><span>Logout</span></a></li>
            </ul>
        </div>
        <div class="navbar-footer">
            <div>NutriSaur v2.0 • © 2025</div>
            <div style="margin-top: 10px;">Logged in as: <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></div>
        </div>
    </div>
    
    <div class="dashboard">
        <header>
            <div class="dashboard-header">
                <h1>Dashboard</h1>
            </div>
            <div class="user-info">
                <button id="new-theme-toggle" class="new-theme-toggle-btn" title="Toggle theme">
                    <span class="new-theme-icon">🌙</span>
                </button>
            </div>
        </header>

        <!-- Community Filter Section - Moved to Top -->
        <div class="filter-section">
            <div class="filter-container">
                <div class="filter-group">
                    <label>Select Municipality:</label>
                    <div class="custom-select-container small-width">
                        <div class="select-header" onclick="toggleMunicipalityDropdown()">
                            <span id="selected-municipality-option">All Municipalities</span>
                            <span class="dropdown-arrow">▼</span>
                        </div>
                        <div class="dropdown-content" id="municipality-dropdown-content">
                            <div class="options-container">
                                <div class="option-item" data-value="">All Municipalities</div>
                                <div class="option-item" data-value="ABUCAY">ABUCAY</div>
                                <div class="option-item" data-value="BAGAC">BAGAC</div>
                                <div class="option-item" data-value="CITY OF BALANGA">CITY OF BALANGA</div>
                                <div class="option-item" data-value="DINALUPIHAN">DINALUPIHAN</div>
                                <div class="option-item" data-value="HERMOSA">HERMOSA</div>
                                <div class="option-item" data-value="LIMAY">LIMAY</div>
                                <div class="option-item" data-value="MARIVELES">MARIVELES</div>
                                <div class="option-item" data-value="MORONG">MORONG</div>
                                <div class="option-item" data-value="ORANI">ORANI</div>
                                <div class="option-item" data-value="ORION">ORION</div>
                                <div class="option-item" data-value="PILAR">PILAR</div>
                                <div class="option-item" data-value="SAMAL">SAMAL</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="filter-group">
                    <label>Select Barangay:</label>
                    <div class="custom-select-container small-width">
                        <div class="select-header" onclick="toggleDropdown()">
                            <span id="selected-option">All Barangays</span>
                            <span class="dropdown-arrow">▼</span>
                        </div>
                    <div class="dropdown-content" id="dropdown-content">
                        <div class="search-container">
                            <input type="text" id="search-input" placeholder="Search barangay or municipality..." onkeyup="filterOptions()">
                        </div>
                        <div class="options-container">
                            <!-- Municipality Options -->
                            <div class="option-group">
                                <div class="option-header">Municipalities</div>
                                <div class="option-item" data-value="">All Barangays</div>
                                <div class="option-item" data-value="MUNICIPALITY_ABUCAY">ABUCAY (All Barangays)</div>
                                <div class="option-item" data-value="MUNICIPALITY_BAGAC">BAGAC (All Barangays)</div>
                                <div class="option-item" data-value="MUNICIPALITY_BALANGA">CITY OF BALANGA (All Barangays)</div>
                                <div class="option-item" data-value="MUNICIPALITY_DINALUPIHAN">DINALUPIHAN (All Barangays)</div>
                                <div class="option-item" data-value="MUNICIPALITY_HERMOSA">HERMOSA (All Barangays)</div>
                                <div class="option-item" data-value="MUNICIPALITY_LIMAY">LIMAY (All Barangays)</div>
                                <div class="option-item" data-value="MUNICIPALITY_MARIVELES">MARIVELES (All Barangays)</div>
                                <div class="option-item" data-value="MUNICIPALITY_MORONG">MORONG (All Barangays)</div>
                                <div class="option-item" data-value="MUNICIPALITY_ORANI">ORANI (All Barangays)</div>
                                <div class="option-item" data-value="MUNICIPALITY_ORION">ORION (All Barangays)</div>
                                <div class="option-item" data-value="MUNICIPALITY_PILAR">PILAR (All Barangays)</div>
                                <div class="option-item" data-value="MUNICIPALITY_SAMAL">SAMAL (All Barangays)</div>
                            </div>
                            
                            <!-- Individual Barangays by Municipality -->
                            <div class="option-group">
                                <div class="option-header">ABUCAY</div>
                                <div class="option-item" data-value="Bangkal">Bangkal</div>
                                <div class="option-item" data-value="Calaylayan (Pob.)">Calaylayan (Pob.)</div>
                                <div class="option-item" data-value="Capitangan">Capitangan</div>
                                <div class="option-item" data-value="Gabon">Gabon</div>
                                <div class="option-item" data-value="Laon (Pob.)">Laon (Pob.)</div>
                                <div class="option-item" data-value="Mabatang">Mabatang</div>
                                <div class="option-item" data-value="Poblacion">Poblacion</div>
                                <div class="option-item" data-value="Saguing">Saguing</div>
                                <div class="option-item" data-value="Salapungan">Salapungan</div>
                                <div class="option-item" data-value="Tala">Tala</div>
                            </div>
                            <div class="option-group">
                                <div class="option-header">BAGAC</div>
                                <div class="option-item" data-value="Bagumbayan (Pob.)">Bagumbayan (Pob.)</div>
                                <div class="option-item" data-value="Banawang">Banawang</div>
                                <div class="option-item" data-value="Binuangan">Binuangan</div>
                                <div class="option-item" data-value="Binukawan">Binukawan</div>
                                <div class="option-item" data-value="Ibaba">Ibaba</div>
                                <div class="option-item" data-value="Ibayo">Ibayo</div>
                                <div class="option-item" data-value="Paysawan">Paysawan</div>
                                <div class="option-item" data-value="Quinaoayanan">Quinaoayanan</div>
                                <div class="option-item" data-value="San Antonio">San Antonio</div>
                                <div class="option-item" data-value="Saysain">Saysain</div>
                                <div class="option-item" data-value="Sibucao">Sibucao</div>
                                <div class="option-item" data-value="Tabing-Ilog">Tabing-Ilog</div>
                                <div class="option-item" data-value="Tipo">Tipo</div>
                                <div class="option-item" data-value="Tugatog">Tugatog</div>
                                <div class="option-item" data-value="Wawa">Wawa</div>
                            </div>
                            <div class="option-group">
                                <div class="option-header">CITY OF BALANGA</div>
                                <div class="option-item" data-value="Bagumbayan">Bagumbayan</div>
                                <div class="option-item" data-value="Cabog-Cabog">Cabog-Cabog</div>
                                <div class="option-item" data-value="Munting Batangas (Cadre)">Munting Batangas (Cadre)</div>
                                <div class="option-item" data-value="Cataning">Cataning</div>
                                <div class="option-item" data-value="Central">Central</div>
                                <div class="option-item" data-value="Cupang Proper">Cupang Proper</div>
                                <div class="option-item" data-value="Cupang West">Cupang West</div>
                                <div class="option-item" data-value="Dangcol (Bernabe)">Dangcol (Bernabe)</div>
                                <div class="option-item" data-value="Ibayo">Ibayo</div>
                                <div class="option-item" data-value="Malabia">Malabia</div>
                                <div class="option-item" data-value="Poblacion">Poblacion</div>
                                <div class="option-item" data-value="Pto. Rivas Ibaba">Pto. Rivas Ibaba</div>
                                <div class="option-item" data-value="Pto. Rivas Itaas">Pto. Rivas Itaas</div>
                                <div class="option-item" data-value="San Jose">San Jose</div>
                                <div class="option-item" data-value="Sibacan">Sibacan</div>
                                <div class="option-item" data-value="Camacho">Camacho</div>
                                <div class="option-item" data-value="Talisay">Talisay</div>
                                <div class="option-item" data-value="Tanato">Tanato</div>
                                <div class="option-item" data-value="Tenejero">Tenejero</div>
                                <div class="option-item" data-value="Tortugas">Tortugas</div>
                                <div class="option-item" data-value="Tuyo">Tuyo</div>
                                <div class="option-item" data-value="Bagong Silang">Bagong Silang</div>
                                <div class="option-item" data-value="Cupang North">Cupang North</div>
                                <div class="option-item" data-value="Doña Francisca">Doña Francisca</div>
                                <div class="option-item" data-value="Lote">Lote</div>
                            </div>
                            <div class="option-group">
                                <div class="option-header">DINALUPIHAN</div>
                                <div class="option-item" data-value="Bangal">Bangal</div>
                                <div class="option-item" data-value="Bonifacio (Pob.)">Bonifacio (Pob.)</div>
                                <div class="option-item" data-value="Burgos (Pob.)">Burgos (Pob.)</div>
                                <div class="option-item" data-value="Colo">Colo</div>
                                <div class="option-item" data-value="Daang Bago">Daang Bago</div>
                                <div class="option-item" data-value="Dalao">Dalao</div>
                                <div class="option-item" data-value="Del Pilar">Del Pilar</div>
                                <div class="option-item" data-value="General Luna">General Luna</div>
                                <div class="option-item" data-value="Governor Generoso">Governor Generoso</div>
                                <div class="option-item" data-value="Hacienda">Hacienda</div>
                                <div class="option-item" data-value="Jose Abad Santos (Pob.)">Jose Abad Santos (Pob.)</div>
                                <div class="option-item" data-value="Kataasan">Kataasan</div>
                                <div class="option-item" data-value="Layac">Layac</div>
                                <div class="option-item" data-value="Lourdes">Lourdes</div>
                                <div class="option-item" data-value="Mabini">Mabini</div>
                                <div class="option-item" data-value="Maligaya">Maligaya</div>
                                <div class="option-item" data-value="Naparing">Naparing</div>
                                <div class="option-item" data-value="Paco">Paco</div>
                                <div class="option-item" data-value="Pag-asa">Pag-asa</div>
                                <div class="option-item" data-value="Pagalanggang">Pagalanggang</div>
                                <div class="option-item" data-value="Panggalan">Panggalan</div>
                                <div class="option-item" data-value="Pinulot">Pinulot</div>
                                <div class="option-item" data-value="Poblacion">Poblacion</div>
                                <div class="option-item" data-value="Rizal">Rizal</div>
                                <div class="option-item" data-value="Saguing">Saguing</div>
                                <div class="option-item" data-value="San Benito">San Benito</div>
                                <div class="option-item" data-value="San Isidro">San Isidro</div>
                                <div class="option-item" data-value="San Ramon">San Ramon</div>
                                <div class="option-item" data-value="Santo Cristo">Santo Cristo</div>
                                <div class="option-item" data-value="Sapang Balas">Sapang Balas</div>
                                <div class="option-item" data-value="Sumalo">Sumalo</div>
                                <div class="option-item" data-value="Tipo">Tipo</div>
                                <div class="option-item" data-value="Tuklasan">Tuklasan</div>
                                <div class="option-item" data-value="Turac">Turac</div>
                                <div class="option-item" data-value="Zamora">Zamora</div>
                            </div>
                            <div class="option-group">
                                <div class="option-header">HERMOSA</div>
                                <div class="option-item" data-value="A. Rivera (Pob.)">A. Rivera (Pob.)</div>
                                <div class="option-item" data-value="Almacen">Almacen</div>
                                <div class="option-item" data-value="Bacong">Bacong</div>
                                <div class="option-item" data-value="Balsic">Balsic</div>
                                <div class="option-item" data-value="Bamban">Bamban</div>
                                <div class="option-item" data-value="Burgos-Soliman (Pob.)">Burgos-Soliman (Pob.)</div>
                                <div class="option-item" data-value="Cataning (Pob.)">Cataning (Pob.)</div>
                                <div class="option-item" data-value="Culong">Culong</div>
                                <div class="option-item" data-value="Daungan (Pob.)">Daungan (Pob.)</div>
                                <div class="option-item" data-value="Judicial (Pob.)">Judicial (Pob.)</div>
                                <div class="option-item" data-value="Mabiga">Mabiga</div>
                                <div class="option-item" data-value="Mabuco">Mabuco</div>
                                <div class="option-item" data-value="Maite">Maite</div>
                                <div class="option-item" data-value="Palihan">Palihan</div>
                                <div class="option-item" data-value="Pandatung">Pandatung</div>
                                <div class="option-item" data-value="Pulong Gubat">Pulong Gubat</div>
                                <div class="option-item" data-value="San Pedro (Pob.)">San Pedro (Pob.)</div>
                                <div class="option-item" data-value="Santo Cristo (Pob.)">Santo Cristo (Pob.)</div>
                                <div class="option-item" data-value="Sumalo">Sumalo</div>
                                <div class="option-item" data-value="Tipo">Tipo</div>
                            </div>
                            <div class="option-group">
                                <div class="option-header">LIMAY</div>
                                <div class="option-item" data-value="Alangan">Alangan</div>
                                <div class="option-item" data-value="Kitang I">Kitang I</div>
                                <div class="option-item" data-value="Kitang 2 & Luz">Kitang 2 & Luz</div>
                                <div class="option-item" data-value="Lamao">Lamao</div>
                                <div class="option-item" data-value="Landing">Landing</div>
                                <div class="option-item" data-value="Poblacion">Poblacion</div>
                                <div class="option-item" data-value="Reforma">Reforma</div>
                                <div class="option-item" data-value="San Francisco de Asis">San Francisco de Asis</div>
                                <div class="option-item" data-value="Townsite">Townsite</div>
                            </div>
                            <div class="option-group">
                                <div class="option-header">MARIVELES</div>
                                <div class="option-item" data-value="Alas-asin">Alas-asin</div>
                                <div class="option-item" data-value="Alion">Alion</div>
                                <div class="option-item" data-value="Batangas II">Batangas II</div>
                                <div class="option-item" data-value="Cabcaben">Cabcaben</div>
                                <div class="option-item" data-value="Lucanin">Lucanin</div>
                                <div class="option-item" data-value="Baseco Country (Nassco)">Baseco Country (Nassco)</div>
                                <div class="option-item" data-value="Poblacion">Poblacion</div>
                                <div class="option-item" data-value="San Carlos">San Carlos</div>
                                <div class="option-item" data-value="San Isidro">San Isidro</div>
                                <div class="option-item" data-value="Sisiman">Sisiman</div>
                                <div class="option-item" data-value="Balon-Anito">Balon-Anito</div>
                                <div class="option-item" data-value="Biaan">Biaan</div>
                                <div class="option-item" data-value="Camaya">Camaya</div>
                                <div class="option-item" data-value="Ipag">Ipag</div>
                                <div class="option-item" data-value="Malaya">Malaya</div>
                                <div class="option-item" data-value="Maligaya">Maligaya</div>
                                <div class="option-item" data-value="Mt. View">Mt. View</div>
                                <div class="option-item" data-value="Townsite">Townsite</div>
                            </div>
                            <div class="option-group">
                                <div class="option-header">MORONG</div>
                                <div class="option-item" data-value="Binaritan">Binaritan</div>
                                <div class="option-item" data-value="Mabayo">Mabayo</div>
                                <div class="option-item" data-value="Nagbalayong">Nagbalayong</div>
                                <div class="option-item" data-value="Poblacion">Poblacion</div>
                                <div class="option-item" data-value="Sabang">Sabang</div>
                            </div>
                            <div class="option-group">
                                <div class="option-header">ORANI</div>
                                <div class="option-item" data-value="Apolinario (Pob.)">Apolinario (Pob.)</div>
                                <div class="option-item" data-value="Bagong Paraiso">Bagong Paraiso</div>
                                <div class="option-item" data-value="Balut">Balut</div>
                                <div class="option-item" data-value="Bayan (Pob.)">Bayan (Pob.)</div>
                                <div class="option-item" data-value="Calero (Pob.)">Calero (Pob.)</div>
                                <div class="option-item" data-value="Calutit">Calutit</div>
                                <div class="option-item" data-value="Camachile">Camachile</div>
                                <div class="option-item" data-value="Del Pilar">Del Pilar</div>
                                <div class="option-item" data-value="Kaparangan">Kaparangan</div>
                                <div class="option-item" data-value="Mabatang">Mabatang</div>
                                <div class="option-item" data-value="Maria Fe">Maria Fe</div>
                                <div class="option-item" data-value="Pagtakhan">Pagtakhan</div>
                                <div class="option-item" data-value="Paking-Carbonero (Pob.)">Paking-Carbonero (Pob.)</div>
                                <div class="option-item" data-value="Pantalan Bago (Pob.)">Pantalan Bago (Pob.)</div>
                                <div class="option-item" data-value="Pantalan Luma (Pob.)">Pantalan Luma (Pob.)</div>
                                <div class="option-item" data-value="Parang">Parang</div>
                                <div class="option-item" data-value="Poblacion">Poblacion</div>
                                <div class="option-item" data-value="Rizal (Pob.)">Rizal (Pob.)</div>
                                <div class="option-item" data-value="Sagrada">Sagrada</div>
                                <div class="option-item" data-value="San Jose">San Jose</div>
                                <div class="option-item" data-value="Sibul">Sibul</div>
                                <div class="option-item" data-value="Sili">Sili</div>
                                <div class="option-item" data-value="Sulong">Sulong</div>
                                <div class="option-item" data-value="Tagumpay">Tagumpay</div>
                                <div class="option-item" data-value="Tala">Tala</div>
                                <div class="option-item" data-value="Talimundoc">Talimundoc</div>
                                <div class="option-item" data-value="Tugatog">Tugatog</div>
                                <div class="option-item" data-value="Wawa">Wawa</div>
                            </div>
                            <div class="option-group">
                                <div class="option-header">ORION</div>
                                <div class="option-item" data-value="Arellano (Pob.)">Arellano (Pob.)</div>
                                <div class="option-item" data-value="Bagumbayan (Pob.)">Bagumbayan (Pob.)</div>
                                <div class="option-item" data-value="Balagtas (Pob.)">Balagtas (Pob.)</div>
                                <div class="option-item" data-value="Balut (Pob.)">Balut (Pob.)</div>
                                <div class="option-item" data-value="Bantan">Bantan</div>
                                <div class="option-item" data-value="Bilolo">Bilolo</div>
                                <div class="option-item" data-value="Calungusan">Calungusan</div>
                                <div class="option-item" data-value="Camachile">Camachile</div>
                                <div class="option-item" data-value="Daang Bago">Daang Bago</div>
                                <div class="option-item" data-value="Daan Bago">Daan Bago</div>
                                <div class="option-item" data-value="Daan Bilolo">Daan Bilolo</div>
                                <div class="option-item" data-value="Daan Pare">Daan Pare</div>
                                <div class="option-item" data-value="General Lim (Kaput)">General Lim (Kaput)</div>
                                <div class="option-item" data-value="Kaput">Kaput</div>
                                <div class="option-item" data-value="Lati">Lati</div>
                                <div class="option-item" data-value="Lusung">Lusung</div>
                                <div class="option-item" data-value="Puting Buhangin">Puting Buhangin</div>
                                <div class="option-item" data-value="Sabatan">Sabatan</div>
                                <div class="option-item" data-value="San Vicente">San Vicente</div>
                                <div class="option-item" data-value="Santa Elena">Santa Elena</div>
                                <div class="option-item" data-value="Santo Domingo">Santo Domingo</div>
                                <div class="option-item" data-value="Villa Angeles">Villa Angeles</div>
                                <div class="option-item" data-value="Wakas">Wakas</div>
                            </div>
                            <div class="option-group">
                                <div class="option-header">PILAR</div>
                                <div class="option-item" data-value="Ala-uli">Ala-uli</div>
                                <div class="option-item" data-value="Bagumbayan">Bagumbayan</div>
                                <div class="option-item" data-value="Balut I">Balut I</div>
                                <div class="option-item" data-value="Balut II">Balut II</div>
                                <div class="option-item" data-value="Bantan Munti">Bantan Munti</div>
                                <div class="option-item" data-value="Bantan">Bantan</div>
                                <div class="option-item" data-value="Burgos">Burgos</div>
                                <div class="option-item" data-value="Del Rosario">Del Rosario</div>
                                <div class="option-item" data-value="Diwa">Diwa</div>
                                <div class="option-item" data-value="Landing">Landing</div>
                                <div class="option-item" data-value="Liwa">Liwa</div>
                                <div class="option-item" data-value="Nueva Vida">Nueva Vida</div>
                                <div class="option-item" data-value="Panghulo">Panghulo</div>
                                <div class="option-item" data-value="Pantingan">Pantingan</div>
                                <div class="option-item" data-value="Poblacion">Poblacion</div>
                                <div class="option-item" data-value="Rizal">Rizal</div>
                                <div class="option-item" data-value="Sagrada">Sagrada</div>
                                <div class="option-item" data-value="San Nicolas">San Nicolas</div>
                                <div class="option-item" data-value="San Pedro">San Pedro</div>
                                <div class="option-item" data-value="Santo Niño">Santo Niño</div>
                                <div class="option-item" data-value="Wakas">Wakas</div>
                            </div>
                            <div class="option-group">
                                <div class="option-header">SAMAL</div>
                                <div class="option-item" data-value="East Calaguiman (Pob.)">East Calaguiman (Pob.)</div>
                                <div class="option-item" data-value="East Daang Bago (Pob.)">East Daang Bago (Pob.)</div>
                                <div class="option-item" data-value="Ibaba (Pob.)">Ibaba (Pob.)</div>
                                <div class="option-item" data-value="Imelda">Imelda</div>
                                <div class="option-item" data-value="Lalawigan">Lalawigan</div>
                                <div class="option-item" data-value="Palili">Palili</div>
                                <div class="option-item" data-value="San Juan">San Juan</div>
                                <div class="option-item" data-value="San Roque">San Roque</div>
                                <div class="option-item" data-value="Santa Lucia">Santa Lucia</div>
                                <div class="option-item" data-value="Santo Niño">Santo Niño</div>
                                <div class="option-item" data-value="West Calaguiman (Pob.)">West Calaguiman (Pob.)</div>
                                <div class="option-item" data-value="West Daang Bago (Pob.)">West Daang Bago (Pob.)</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
            <div class="filter-group">
                <label>WHO Standard:</label>
                <select id="whoStandardSelect" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; background: white; color: #333; font-size: 14px;">
                    <option value="weight-for-age" selected>Weight-for-Age (0-71 months)</option>
                    <option value="height-for-age">Height-for-Age (0-71 months)</option>
                    <option value="weight-for-height">Weight-for-Height (0-60 months)</option>
                    <option value="bmi-for-age">BMI-for-Age (5-19 years)</option>
                    <option value="bmi-adult">BMI Adult (≥19 years)</option>
                </select>
            </div>
        </div>



        <!-- Community Metrics Cards - Moved to Top -->
        <div class="card-container" style="gap: 20px;">
            <div class="card" id="card-total-screened">
                <h2>Total Screened</h2>
                <div class="metric-value" id="community-total-screened"><?php echo $timeFrameData['total_screened']; ?></div>
                <div class="metric-change" id="community-screened-change">
                    All Time Data
                </div>
                <div class="metric-note">Children & adults screened in selected time frame</div>
            </div>
            <div class="card" id="card-high-risk">
                <h2>Severely Underweight</h2>
                <div class="metric-value" id="community-high-risk"><?php echo $timeFrameData['high_risk_cases']; ?></div>
                <div class="metric-change" id="community-risk-change">
                    All Time Data
                </div>
                <div class="metric-note">Children with severely underweight status (Weight-for-Age)</div>
            </div>
            <div class="card" id="card-sam-cases">
                <h2>Severely Stunted</h2>
                <div class="metric-value" id="community-sam-cases"><?php echo $timeFrameData['sam_cases']; ?></div>
                <div class="metric-change" id="community-sam-change">
                    All Time Data
                </div>
                <div class="metric-note">Children with severely stunted status (Height-for-Age)</div>
            </div>
            <div class="card" id="card-critical-muac">
                <h2>Severely Wasted</h2>
                <div class="metric-value" id="community-critical-muac"><?php echo $timeFrameData['critical_muac']; ?></div>
                <div class="metric-change" id="community-muac-change">
                    All Time Data
                </div>
                <div class="metric-note">Children with severely wasted status (Weight-for-Height)</div>
            </div>
        </div>



        <div class="chart-row">
            <div class="chart-card">
                    <h3>WHO Growth Standards Classification</h3>
                <p class="chart-description" id="who-chart-description">Distribution of children by Weight-for-Age classification. Shows nutritional status based on weight relative to age (0-71 months).</p>
                <div class="donut-chart-container">
                    <div class="donut-chart">
                        <div class="donut-chart-bg" id="risk-chart-bg"></div>
                        <div class="donut-center-text" id="risk-center-text">0%</div>
                        <div class="percentage-labels" id="percentage-labels"></div>
                    </div>
                </div>
                <div class="segments" id="risk-segments"></div>
            </div>
            

            
            <div class="chart-card">
                <h3>Severe Malnutrition Cases</h3>
                <p class="chart-description">Users with severe malnutrition classifications. Scroll to view all cases.</p>
                <div class="severe-cases-container">
                    <div class="severe-cases-list" id="severe-cases-list">
                        <!-- Severe cases will be generated dynamically -->
                    </div>
                </div>
            </div>
        </div>



        <div class="chart-row">
            <div class="chart-card" style="grid-column: 1 / -1; width: 100%;">
                <div style="margin-bottom: 10px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                        <div style="flex: 1;">
                            <h3 style="margin: 0; padding: 0;">Community Health Trends Over Time</h3>
                            <p class="chart-description" style="margin: 2px 0 0 0; font-size: 12px; line-height: 1.3;">Nutritional trends over time periods.</p>
                        </div>
                        
                        <!-- Date Picker Controls - Compact layout -->
                        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin-left: 15px;">
                            <div style="display: flex; gap: 5px; align-items: center;">
                                <label style="font-size: 12px; color: var(--color-text); font-weight: 500;">From:</label>
                                <input type="date" id="trends-from-date" style="padding: 6px 8px; border: 1px solid var(--color-border); border-radius: 4px; background: var(--color-bg); color: var(--color-text); font-size: 12px; width: 120px;">
                            </div>
                            <div style="display: flex; gap: 5px; align-items: center;">
                                <label style="font-size: 12px; color: var(--color-text); font-weight: 500;">To:</label>
                                <input type="date" id="trends-to-date" style="padding: 6px 8px; border: 1px solid var(--color-border); border-radius: 4px; background: var(--color-bg); color: var(--color-text); font-size: 12px; width: 120px;">
                            </div>
                            <button id="generate-trends-chart" style="padding: 6px 12px; background: var(--color-highlight); color: white; border: none; border-radius: 4px; font-size: 12px; font-weight: 500; cursor: pointer; transition: all 0.2s ease;">
                                📊 Generate
                            </button>
                        </div>
                    </div>
                    
                </div>
                
                <div id="trends-chart-container" class="trends-chart-container" style="height: 400px; max-height: 400px; width: 100%; max-width: 100%; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative; padding: 10px; box-sizing: border-box; margin-top: 0 !important;">
                    <canvas id="trendsLineChart" style="max-width: 100%; max-height: 100%;"></canvas>
                </div>
            </div>
        </div>


        <div class="chart-row">
            <div class="chart-card" style="grid-column: 1 / -1; width: 100%;">
                <div style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                        <div style="flex: 1;">
                <h3>Age Classification Chart</h3>
                <p class="chart-description">Nutritional classifications by age groups. Age range adjusts based on selected WHO standard.</p>
                </div>
                        
                    </div>
                </div>
                
                <div class="age-classification-chart-container">
                    <canvas id="ageClassificationLineChart"></canvas>
                </div>
                    </div>
                </div>
                

                
                




        <!-- Screening Responses Section -->
        <div class="chart-row" style="margin-top: 30px; clear: both;">
            <div style="grid-column: 1 / -1; margin-bottom: 30px;">

                
                <!-- Nutritional Assessment Statistics Grid -->
                    <div class="response-grid">
                        <div class="response-item">
                            <div class="response-question">Gender Distribution</div>
                            <div class="response-answers" id="gender-distribution-responses">
                                <div class="no-data-message">Loading gender data...</div>
                            </div>
                        </div>
                        
                        <div class="response-item">
                            <div class="response-question">Municipality Distribution</div>
                            <div class="response-answers" id="municipality-distribution-responses">
                            <div class="column-headers">
                                <span class="header-label">Municipality</span>
                                <span class="header-count">Count</span>
                                <span class="header-percent">Percentage</span>
                            </div>
                            <div class="response-data-container">
                                <?php if (!empty($nutritionalStatistics['statistics']['municipality_distribution'])): ?>
                                    <?php foreach ($nutritionalStatistics['statistics']['municipality_distribution'] as $municipality => $count): ?>
                                        <div class="response-answer-item">
                                            <span class="answer-label"><?php echo htmlspecialchars($municipality); ?></span>
                                            <span class="answer-count"><?php echo $count; ?></span>
                                            <span class="answer-percentage"><?php 
                                                $totalUsers = $nutritionalStatistics['statistics']['total_users'] ?? 0;
                                                if ($totalUsers > 0) {
                                                    echo round(($count / $totalUsers) * 100, 1);
                                                } else {
                                                    echo '0';
                                                }
                                            ?>%</span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="no-data-message">No municipality data available for selected time frame</div>
                                <?php endif; ?>
                            </div>
                            </div>
                        </div>
                        
                        <div class="response-item">
                            <div class="response-question">Barangay Distribution</div>
                            <div class="response-answers" id="barangay-distribution-responses">
                            <div class="column-headers">
                                <span class="header-label">Barangay</span>
                                <span class="header-count">Count</span>
                                <span class="header-percent">Percentage</span>
                            </div>
                            <div class="response-data-container">
                                <?php if (!empty($nutritionalStatistics['statistics']['barangay_distribution'])): ?>
                                    <?php foreach ($nutritionalStatistics['statistics']['barangay_distribution'] as $barangay => $count): ?>
                                        <div class="response-answer-item">
                                            <span class="answer-label"><?php echo htmlspecialchars($barangay); ?></span>
                                            <span class="answer-count"><?php echo $count; ?></span>
                                            <span class="answer-percentage"><?php 
                                                $totalUsers = $nutritionalStatistics['statistics']['total_users'] ?? 0;
                                                if ($totalUsers > 0) {
                                                    echo round(($count / $totalUsers) * 100, 1);
                                                } else {
                                                    echo '0';
                                                }
                                            ?>%</span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="no-data-message">No barangay data available for selected time frame</div>
                                <?php endif; ?>
                            </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Make nutritional statistics available to JavaScript
        window.nutritionalStatistics = <?php echo json_encode($nutritionalStatistics); ?>;
        console.log('📊 Nutritional Statistics loaded:', window.nutritionalStatistics);
        
        // Custom Dropdown Functions
        function toggleDropdown() {
            const dropdown = document.getElementById('dropdown-content');
            const arrow = document.querySelector('.dropdown-arrow');
            const selectHeader = document.querySelector('.select-header');
            
            if (dropdown && arrow) {
                dropdown.classList.toggle('active');
                arrow.classList.toggle('active');
            }
        }

        // Municipality dropdown functions
        function toggleMunicipalityDropdown() {
            const dropdown = document.getElementById('municipality-dropdown-content');
            const arrow = document.querySelector('#municipality-dropdown-content').parentElement.querySelector('.dropdown-arrow');
            
            if (dropdown && arrow) {
                dropdown.classList.toggle('active');
                arrow.classList.toggle('active');
            }
        }


        async function selectOption(value, text) {
            const selectedOption = document.getElementById('selected-option');
            const dropdownContent = document.getElementById('dropdown-content');
            const dropdownArrow = document.querySelector('.dropdown-arrow');
            
            if (selectedOption && dropdownContent && dropdownArrow) {
                selectedOption.textContent = text;
                dropdownContent.classList.remove('active');
                dropdownArrow.classList.remove('active');
                
                // Update dashboard with all active filters (barangay + municipality + WHO standard)
                await updateDashboardWithAllFilters();
                
                // Test municipality filtering if a municipality is selected
                if (value && value.startsWith('MUNICIPALITY_')) {
                    // Municipality filtering handled by updateDashboardForBarangay
                }
                
                // Update selected state
                document.querySelectorAll('.option-item').forEach(item => {
                    item.classList.remove('selected');
                });
                
                // Try to find the clicked item and mark it as selected
                const clickedItem = document.querySelector(`[data-value="${value}"]`);
                if (clickedItem) {
                    clickedItem.classList.add('selected');
                }
                
                // If "All Barangays" is selected, clear the localStorage
                if (!value || value === '') {
                    localStorage.removeItem('selectedBarangay');
                }
            }
        }

        function filterOptions() {
            const searchInput = document.getElementById('search-input');
            const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
            const optionItems = document.querySelectorAll('.option-item');
            
            if (optionItems.length === 0) {
                return;
            }
            
            optionItems.forEach((item) => {
                const text = item.textContent.toLowerCase();
                const matches = text.includes(searchTerm);
                
                if (matches) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const container = document.querySelector('.custom-select-container');
            
            if (container && !container.contains(event.target)) {
                const dropdown = document.getElementById('dropdown-content');
                const arrow = document.querySelector('.dropdown-arrow');
                
                if (dropdown && arrow) {
                    dropdown.classList.remove('active');
                    arrow.classList.remove('active');
                }
            }
        });

        // Barangay and Municipality selection handling - will be called from main DOMContentLoaded
        function setupBarangaySelection() {
            // Set up click handlers for option items
            const optionItems = document.querySelectorAll('.option-item');
            
            if (optionItems.length === 0) {
                // Try to find option items with a different selector
                const alternativeOptions = document.querySelectorAll('[data-value]');
                
                if (alternativeOptions.length > 0) {
                    alternativeOptions.forEach((item) => {
                        item.addEventListener('click', async function() {
                            const value = this.getAttribute('data-value');
                            const text = this.textContent;
                            await selectOption(value, text);
                        });
                    });
                }
            } else {
                optionItems.forEach((item) => {
                    item.addEventListener('click', async function() {
                        const value = this.getAttribute('data-value');
                        const text = this.textContent;
                        await selectOption(value, text);
                    });
                });
            }
        }

        // Municipality dropdown selection handling
        function setupMunicipalitySelection() {
            // Set up click handlers for municipality option items
            const municipalityOptionItems = document.querySelectorAll('#municipality-dropdown-content .option-item');
            
            municipalityOptionItems.forEach((item) => {
                item.addEventListener('click', function() {
                    const value = this.getAttribute('data-value');
                    const text = this.textContent;
                    selectMunicipality(value, text);
                });
            });
        }


        // Function to update barangay options based on selected municipality
        function updateBarangayOptions(municipality) {
            const barangayDropdown = document.getElementById('dropdown-content');
            if (!barangayDropdown) {
                console.error('❌ Barangay dropdown not found');
                return;
            }
            
            const barangayOptionsContainer = barangayDropdown.querySelector('.options-container');
            if (!barangayOptionsContainer) {
                console.error('❌ Barangay options container not found');
                return;
            }
            
            // Clear existing options except the first "All Barangays" option
            const existingOptions = barangayOptionsContainer.querySelectorAll('.option-group');
            existingOptions.forEach(group => group.remove());
            
            // Reset barangay selection
            const selectedBarangayOption = document.getElementById('selected-option');
            if (selectedBarangayOption) {
                selectedBarangayOption.textContent = 'All Barangays';
            }
            
            if (municipality) {
                // Get barangays for the selected municipality
                const barangays = getBarangayOptions(municipality);
                
                if (barangays.length > 0) {
                    // Create a new option group for the municipality
                    const optionGroup = document.createElement('div');
                    optionGroup.className = 'option-group';
                    
                    const header = document.createElement('div');
                    header.className = 'option-header';
                    header.textContent = municipality;
                    optionGroup.appendChild(header);
                    
                    // Add individual barangay options
                    barangays.forEach(barangay => {
                        const optionItem = document.createElement('div');
                        optionItem.className = 'option-item';
                        optionItem.setAttribute('data-value', barangay);
                        optionItem.textContent = barangay;
                        optionItem.addEventListener('click', function() {
                            selectBarangayOption(barangay, barangay);
                        });
                        optionGroup.appendChild(optionItem);
                    });
                    
                    // Insert after the municipality options
                    const municipalityGroup = barangayOptionsContainer.querySelector('.option-group');
                    if (municipalityGroup) {
                        municipalityGroup.insertAdjacentElement('afterend', optionGroup);
                    } else {
                        barangayOptionsContainer.appendChild(optionGroup);
                    }
                }
            }
        }

        // Function to get barangay options for a municipality (same as screening.php)
        function getBarangayOptions(municipality) {
            const barangayData = {
                'ABUCAY': ['Bangkal', 'Calaylayan (Pob.)', 'Capitangan', 'Gabon', 'Laon (Pob.)', 'Mabatang', 'Poblacion', 'Saguing', 'Salapungan', 'Tala'],
                'BAGAC': ['Bagumbayan (Pob.)', 'Banawang', 'Binuangan', 'Binukawan', 'Ibaba', 'Ibayo', 'Paysawan', 'Quinaoayanan', 'San Antonio', 'Saysain', 'Sibucao', 'Tabing-Ilog', 'Tipo', 'Tugatog', 'Wawa'],
                'CITY OF BALANGA': ['Bagumbayan', 'Cabog-Cabog', 'Munting Batangas (Cadre)', 'Cataning', 'Central', 'Cupang Proper', 'Cupang West', 'Dangcol (Bernabe)', 'Ibayo', 'Malabia', 'Poblacion', 'Pto. Rivas Ibaba', 'Pto. Rivas Itaas', 'San Jose', 'Sibacan', 'Camacho', 'Talisay', 'Tanato', 'Tenejero', 'Tortugas', 'Tuyo', 'Bagong Silang', 'Cupang North', 'Doña Francisca', 'Lote'],
                'DINALUPIHAN': ['Bangal', 'Bonifacio (Pob.)', 'Burgos (Pob.)', 'Colo', 'Daang Bago', 'Dalao', 'Del Pilar', 'General Luna', 'Governor Generoso', 'Hacienda', 'Jose Abad Santos (Pob.)', 'Kataasan', 'Layac', 'Lourdes', 'Mabini', 'Maligaya', 'Naparing', 'Paco', 'Pag-asa', 'Pagalanggang', 'Panggalan', 'Pinulot', 'Poblacion', 'Rizal', 'Saguing', 'San Benito', 'San Isidro', 'San Ramon', 'Santo Cristo', 'Sapang Balas', 'Sumalo', 'Tipo', 'Tuklasan', 'Turac', 'Zamora'],
                'HERMOSA': ['A. Rivera (Pob.)', 'Almacen', 'Bacong', 'Balsic', 'Bamban', 'Burgos-Soliman (Pob.)', 'Cataning (Pob.)', 'Culong', 'Daungan (Pob.)', 'Judicial (Pob.)', 'Mabiga', 'Mabuco', 'Maite', 'Palihan', 'Pandatung', 'Pulong Gubat', 'San Pedro (Pob.)', 'Santo Cristo (Pob.)', 'Sumalo', 'Tipo'],
                'LIMAY': ['Alangan', 'Kitang I', 'Kitang 2 & Luz', 'Lamao', 'Landing', 'Poblacion', 'Reforma', 'San Francisco de Asis', 'Townsite'],
                'MARIVELES': ['Alas-asin', 'Alion', 'Batangas II', 'Cabcaben', 'Lucanin', 'Baseco Country (Nassco)', 'Poblacion', 'San Carlos', 'San Isidro', 'Sisiman', 'Balon-Anito', 'Biaan', 'Camaya', 'Ipag', 'Malaya', 'Maligaya', 'Mt. View', 'Townsite'],
                'MORONG': ['Binaritan', 'Mabayo', 'Nagbalayong', 'Poblacion', 'Sabang'],
                'ORANI': ['Apolinario (Pob.)', 'Bagong Paraiso', 'Balut', 'Bayan (Pob.)', 'Calero (Pob.)', 'Calutit', 'Camachile', 'Del Pilar', 'Kaparangan', 'Mabatang', 'Maria Fe', 'Pagtakhan', 'Paking-Carbonero (Pob.)', 'Pantalan Bago (Pob.)', 'Pantalan Luma (Pob.)', 'Parang', 'Poblacion', 'Rizal (Pob.)', 'Sagrada', 'San Jose', 'Sibul', 'Sili', 'Sulong', 'Tagumpay', 'Tala', 'Talimundoc', 'Tugatog', 'Wawa'],
                'ORION': ['Arellano (Pob.)', 'Bagumbayan (Pob.)', 'Balagtas (Pob.)', 'Balut (Pob.)', 'Bantan', 'Bilolo', 'Calungusan', 'Camachile', 'Daang Bago', 'Daan Bago', 'Daan Bilolo', 'Daan Pare', 'General Lim (Kaput)', 'Kaput', 'Lati', 'Lusung', 'Puting Buhangin', 'Sabatan', 'San Vicente', 'Santa Elena', 'Santo Domingo', 'Villa Angeles', 'Wakas'],
                'PILAR': ['Ala-uli', 'Bagumbayan', 'Balut I', 'Balut II', 'Bantan Munti', 'Bantan', 'Burgos', 'Del Rosario', 'Diwa', 'Landing', 'Liwa', 'Nueva Vida', 'Panghulo', 'Pantingan', 'Poblacion', 'Rizal', 'Sagrada', 'San Nicolas', 'San Pedro', 'Santo Niño', 'Wakas'],
                'SAMAL': ['East Calaguiman (Pob.)', 'East Daang Bago (Pob.)', 'Ibaba (Pob.)', 'Imelda', 'Lalawigan', 'Palili', 'San Juan', 'San Roque', 'Santa Lucia', 'Santo Niño', 'West Calaguiman (Pob.)', 'West Daang Bago (Pob.)']
            };
            
            return barangayData[municipality] || [];
        }

        // Function to handle barangay selection
        function selectBarangayOption(value, text) {
            const selectedOption = document.getElementById('selected-option');
            const dropdownContent = document.getElementById('dropdown-content');
            const dropdownArrow = document.querySelector('#dropdown-content').parentElement.querySelector('.dropdown-arrow');
            
            if (selectedOption && dropdownContent && dropdownArrow) {
                selectedOption.textContent = text;
                dropdownContent.classList.remove('active');
                dropdownArrow.classList.remove('active');
                console.log('Barangay selected:', value, text);
            }
        }

        // Global variable to store the currently selected barangay
        let currentSelectedBarangay = '';
        
        // Function to restore selected barangay from localStorage
        function restoreSelectedBarangay() {
            try {
                const savedBarangay = localStorage.getItem('selectedBarangay');
                if (savedBarangay) {
                    currentSelectedBarangay = savedBarangay;
                    
                    // Update the dropdown display to show the saved selection
                    const selectedOptionElement = document.getElementById('selected-option');
                    if (selectedOptionElement) {
                        // Find the corresponding option text for the saved value
                        const optionItem = document.querySelector(`[data-value="${savedBarangay}"]`);
                        if (optionItem) {
                            selectedOptionElement.textContent = optionItem.textContent;
                        }
                    }
                    
                    // Mark the saved option as selected in the dropdown
                    document.querySelectorAll('.option-item').forEach(item => {
                        item.classList.remove('selected');
                        if (item.getAttribute('data-value') === savedBarangay) {
                            item.classList.add('selected');
                        }
                    });
                    
                    return true; // Indicate successful restoration
                } else {
                    return false; // Indicate no restoration needed
                }
            } catch (error) {
                return false; // Indicate restoration failed
            }
        }
        
        // Function to clear barangay selection
        async function clearBarangaySelection() {
            currentSelectedBarangay = '';
            localStorage.removeItem('selectedBarangay');
            
            // Reset dropdown display
            const selectedOptionElement = document.getElementById('selected-option');
            if (selectedOptionElement) {
                selectedOptionElement.textContent = 'All Barangays';
            }
            
            // Clear selected state from all options
            document.querySelectorAll('.option-item').forEach(item => {
                item.classList.remove('selected');
            });
            
            // Refresh dashboard with no barangay filter
            await updateDashboardForBarangay('');
        }
        
        // Function to get current barangay selection
        function getCurrentBarangay() {
            return currentSelectedBarangay;
        }
        
        // Function to check if a barangay is currently selected
        function isBarangaySelected() {
            return currentSelectedBarangay && currentSelectedBarangay !== '';
        }
        
        // Function to update dashboard data based on selected barangay
        async function updateDashboardForBarangay(barangay) {
            // Store the selected barangay globally
            if (barangay !== undefined && barangay !== null) {
                currentSelectedBarangay = barangay;
                
                // Also store in localStorage for persistence across page refreshes
                if (barangay !== '') {
                    localStorage.setItem('selectedBarangay', barangay);
                } else {
                    localStorage.removeItem('selectedBarangay');
                }
            }
            
            // Update the "Programs in Barangay" metric
            await updateProgramsMetric(barangay);
            
            // Update all charts and metrics for the selected barangay
            await updateCommunityMetrics(barangay);
            
            // Update all charts and metrics for the selected barangay
            await updateCharts(barangay);
            
            // Update analysis section
            await updateAnalysisSection(barangay);
            
            // Update geographic distribution chart
            await updateGeographicChart(barangay);
            
            // Update barangay distribution data using new bulk API
            console.log('🔄 Updating barangay distribution for:', barangay);
            const barangayData = await fetchBarangayDistributionData(barangay);
            updateBarangayDistributionDisplay(barangayData);
            
            // Update gender distribution data using new bulk API
            console.log('🔄 Updating gender distribution for:', barangay);
            const genderData = await fetchGenderDistributionData(barangay);
            updateGenderDistributionDisplay(genderData);
            
            // Update critical alerts - Now handled by assessment data
            // updateCriticalAlerts(barangay); // Deprecated - using assessment data instead
            
            // Automatically refresh intelligent programs for the selected location
            await updateIntelligentPrograms(barangay);
            
            // Update trends chart with new barangay filter
            console.log('🔄 Updating trends chart for barangay:', barangay);
            await updateTrendsChart();
            
            // Update severe cases list with new barangay filter
            console.log('🔄 Updating severe cases list for barangay:', barangay);
            await updateSevereCasesList(barangay);
            
            // Update screening responses for the selected barangay
            setTimeout(() => {
                loadScreeningResponses(barangay);
            }, 1000);
        }

        // Function to calculate total programs across all areas
        async function calculateTotalPrograms() {
            try {
                // Get total users to estimate programs
                const data = await fetchDataFromAPI('community_metrics');
                if (data && data.success && data.total_screened > 0) {
                    // Estimate 1 program per 10 users, minimum 1
                    return Math.max(1, Math.ceil(data.total_screened / 10));
                } else {
                    // No users = no programs
                    return 0;
                }
            } catch (error) {
                return 0;
            }
        }

        // Function to update programs metric
        async function updateProgramsMetric(barangay) {
            const programsElement = document.getElementById('programs-in-barangay');
            const programsChangeElement = document.getElementById('programs-change');
            
            if (programsElement && programsChangeElement) {
                
                if (barangay && barangay !== '') {
                    // Handle municipality selections
                    if (barangay.startsWith('MUNICIPALITY_')) {
                        const municipality = barangay.replace('MUNICIPALITY_', '');
                        let programCount = 0;
                        
                        // Calculate total programs for the entire municipality
                        switch (municipality) {
                            case 'ABUCAY':
                                programCount = 10; // 10 barangays × 1 program each
                                break;
                            case 'BAGAC':
                                programCount = 15; // 15 barangays × 1 program each
                                break;
                            case 'BALANGA':
                                programCount = 25; // 25 barangays × 1 program each
                                break;
                            case 'DINALUPIHAN':
                                programCount = 46; // 46 barangays × 1 program each
                                break;
                            case 'HERMOSA':
                                programCount = 23; // 23 barangays × 1 program each
                                break;
                            case 'LIMAY':
                                programCount = 12; // 12 barangays × 1 program each
                                break;
                            case 'MARIVELES':
                                programCount = 19; // 19 barangays × 1 program each
                                break;
                            case 'MORONG':
                                programCount = 6; // 6 barangays × 1 program each
                                break;
                            case 'ORANI':
                                programCount = 32; // 32 barangays × 1 program each
                                break;
                            case 'ORION':
                                programCount = 23; // 23 barangays × 1 program each
                                break;
                            case 'PILAR':
                                programCount = 19; // 19 barangays × 1 program each
                                break;
                            case 'SAMAL':
                                programCount = 14; // 14 barangays × 1 program each
                                break;
                            default:
                                programCount = 0;
                        }
                        
                        programsElement.textContent = programCount;
                        programsChangeElement.textContent = 'Municipality';
                    } else {
                        // Handle individual barangay selections
                        let programCount = 0;
                        // Since we removed duplicate municipality names, we can use simpler logic
                        if (barangay.includes('Bagumbayan') || barangay.includes('Poblacion') || barangay.includes('Central')) {
                            programCount = 3; // More programs in major areas
                        } else if (barangay.includes('Bangal') || barangay.includes('Bacong') || barangay.includes('Alangan')) {
                            programCount = 2; // Medium programs
                        } else {
                            programCount = 1; // Basic programs
                        }
                        
                        programsElement.textContent = programCount;
                        programsChangeElement.textContent = 'Active';
                    }
                } else {
                    // Show total programs across all barangays
                    // Calculate total programs based on actual data instead of hardcoded value
                    const totalPrograms = await calculateTotalPrograms();
                    programsElement.textContent = totalPrograms;
                    programsChangeElement.textContent = 'All areas';
                }
            }
        }

        // State management for dashboard data
        const dashboardState = {
            totalScreened: null,
            recentRegistrations: null,
            highRisk: null,
            moderateRisk: null,
            samCases: null,
            samChange: null,
            criticalAlerts: null,
            lastUpdate: null,
            isFirstLoad: true,
            cache: new Map(),
            cacheTimeout: 30000 // 30 seconds cache
        };

        // Geographic distribution data from PHP
        const geographicDistributionData = <?php echo json_encode($geographicDistributionData); ?>;
        console.log('🌍 Pre-loaded Geographic Distribution Data:', geographicDistributionData);

        // Function to get all active filters
        function getAllActiveFilters() {
            const municipality = document.getElementById('selected-municipality-option')?.textContent || '';
            const barangay = document.getElementById('selected-option')?.textContent || '';
            const whoStandard = document.getElementById('whoStandardSelect')?.value || 'weight-for-age';
            
            console.log('🔍 getAllActiveFilters - Raw values:', {
                municipality: municipality,
                barangay: barangay,
                whoStandard: whoStandard
            });
            
            // Determine the final filter value
            let finalFilter = '';
            if (barangay && barangay !== 'All Barangays' && barangay !== 'Select Barangay') {
                finalFilter = barangay;
                console.log('🔍 Using barangay as filter:', barangay);
            } else if (municipality && municipality !== 'All Municipalities' && municipality !== 'Select Municipality') {
                finalFilter = municipality;
                console.log('🔍 Using municipality as filter:', municipality);
            }
            
            console.log('🔍 Final filter value:', finalFilter);
            
            return {
                municipality: municipality,
                barangay: barangay,
                whoStandard: whoStandard,
                finalFilter: finalFilter
            };
        }

        // Function to update dashboard with all active filters
        async function updateDashboardWithAllFilters() {
            const filters = getAllActiveFilters();
            console.log('🔄 Updating dashboard with all filters:', filters);
            
            // Update dashboard with the final filter
            await updateDashboardForBarangay(filters.finalFilter);
            
            // Update WHO chart with current WHO standard
            await handleWHOStandardChange();
        }

        // Function to update severely cases cards with WHO classification data
        function updateSeverelyCasesCards(whoData) {
            console.log('📊 Updating severely cases cards with WHO data:', whoData);
            
            try {
                if (!whoData || !whoData.data) {
                    console.log('❌ No WHO data available for severely cases');
                    return;
                }
                
                // Get severely cases from WHO classifications
                const severelyUnderweight = whoData.data.weight_for_age?.['Severely Underweight'] || 0;
                const severelyStunted = whoData.data.height_for_age?.['Severely Stunted'] || 0;
                const severelyWasted = whoData.data.weight_for_height?.['Severely Wasted'] || 0;
                
                console.log('📊 WHO Severely Cases:');
                console.log('  - Severely Underweight (WFA):', severelyUnderweight);
                console.log('  - Severely Stunted (HFA):', severelyStunted);
                console.log('  - Severely Wasted (WFH):', severelyWasted);
                
                // Update Severely Underweight card
                const highRisk = document.getElementById('community-high-risk');
                const riskChange = document.getElementById('community-risk-change');
                if (highRisk && riskChange) {
                    console.log('📊 Setting severely underweight to:', severelyUnderweight);
                    highRisk.textContent = severelyUnderweight;
                    riskChange.textContent = severelyUnderweight;
                    dashboardState.highRisk = severelyUnderweight;
                }
                
                // Update Severely Stunted card
                const samCases = document.getElementById('community-sam-cases');
                const samChange = document.getElementById('community-sam-change');
                if (samCases && samChange) {
                    console.log('📊 Setting severely stunted to:', severelyStunted);
                    samCases.textContent = severelyStunted;
                    samChange.textContent = severelyStunted;
                    dashboardState.samCases = severelyStunted;
                }
                
                // Update Severely Wasted card
                const criticalMuac = document.getElementById('community-critical-muac');
                const muacChange = document.getElementById('community-muac-change');
                if (criticalMuac && muacChange) {
                    console.log('📊 Setting severely wasted to:', severelyWasted);
                    criticalMuac.textContent = severelyWasted;
                    muacChange.textContent = severelyWasted;
                    dashboardState.criticalMuac = severelyWasted;
                }
            } catch (error) {
                console.error('❌ Error updating severely cases cards:', error);
            }
        }

        // Function to update community metrics
        async function updateCommunityMetrics(barangay = '') {
            console.log('🔄 updateCommunityMetrics called with barangay:', barangay);
            // Debounce rapid successive calls to prevent flickering
            if (updateCommunityMetrics.debounceTimer) {
                clearTimeout(updateCommunityMetrics.debounceTimer);
            }
            
            updateCommunityMetrics.debounceTimer = setTimeout(async () => {
                // Prevent concurrent updates
                if (dashboardState.updateInProgress) {
                    console.log('⏳ Update already in progress, skipping...');
                    return;
                }
                dashboardState.updateInProgress = true;
                try {
                    console.log('🔄 Starting community metrics update...');
                
                const params = {};
                if (barangay && barangay !== '') {
                    params.barangay = barangay;
                    
                    if (barangay.startsWith('MUNICIPALITY_')) {
                        const municipality = barangay.replace('MUNICIPALITY_', '');
                    }
                }
                const data = await fetchDataFromAPI('dashboard_assessment_stats', params);
                
                console.log('📊 Community Metrics Data:', data);
                console.log('📊 Data type:', typeof data);
                console.log('📊 Data keys:', Object.keys(data));
                
                if (data && data.success && data.data) {
                    // Update Total Screened (using total_screened from assessment data)
                    const totalScreened = document.getElementById('community-total-screened');
                    const screenedChange = document.getElementById('community-screened-change');
                    
                    console.log('Total Screened Element:', totalScreened);
                    console.log('Screened Change Element:', screenedChange);
                    console.log('Total Users:', data.data.total_screened);
                    console.log('Recent Registrations:', data.data.total_screened);
                    
                    if (totalScreened && screenedChange) {
                        const totalUsersValue = data.data.total_screened || 0;
                        const recentRegValue = data.data.total_screened || 0;
                        
                        console.log('📊 Total screened element:', totalScreened);
                        console.log('📊 Total screened from API (data.data.total_screened):', data.data.total_screened);
                        console.log('📊 Total users from API (data.data.total_users):', data.data.total_users);
                        console.log('📊 Total users from API (data.total_users):', data.total_users);
                        console.log('📊 Total users from API (data.processed_users):', data.processed_users);
                        console.log('📊 Final total users value:', totalUsersValue);
                        
                        // Force update the total screened card
                        console.log('📊 Setting total screened to:', totalUsersValue);
                            totalScreened.textContent = totalUsersValue;
                            dashboardState.totalScreened = totalUsersValue;
                        
                        console.log('📊 Setting screened change to:', recentRegValue);
                            screenedChange.textContent = recentRegValue;
                            dashboardState.recentRegistrations = recentRegValue;
                    } else {
                        console.log('❌ HTML elements not found for Total Screened');
                    }

                    // Note: Severely cases will be updated by WHO classification data
                    // These cards show severely cases from WHO standards, not risk levels
                    console.log('📊 Severely cases will be updated by WHO classification data');
                    
                    // Note: Risk distribution data will be handled by updateCharts() function
                    // which calls the risk_distribution API separately
                }
            } catch (error) {
                console.error('Error in updateCommunityMetrics:', error);
            }
            }, 1000); // 1000ms debounce delay to prevent flickering
        }

        // Function to update charts
        async function updateCharts(barangay = '') {
            // Debounce rapid successive calls to prevent flickering
            if (updateCharts.debounceTimer) {
                clearTimeout(updateCharts.debounceTimer);
            }
            
            updateCharts.debounceTimer = setTimeout(async () => {
                try {
                    console.log('🔄 updateCharts called with barangay:', barangay);
                    
                    // Debug: Check current metric values
                    const totalScreened = document.getElementById('community-total-screened');
                    const highRisk = document.getElementById('community-high-risk');
                    const samCases = document.getElementById('community-sam-cases');
                    const criticalMuac = document.getElementById('community-critical-muac');
                    
                    console.log('🔍 Current Dashboard Metrics:');
                    console.log('  - Total Screened:', totalScreened ? totalScreened.textContent : 'NOT FOUND');
                    console.log('  - High Risk (Severely Underweight):', highRisk ? highRisk.textContent : 'NOT FOUND');
                    console.log('  - SAM Cases (Severely Stunted):', samCases ? samCases.textContent : 'NOT FOUND');
                    console.log('  - Critical MUAC (Severely Wasted):', criticalMuac ? criticalMuac.textContent : 'NOT FOUND');
                
                const params = {};
                if (barangay && barangay !== '') {
                    params.barangay = barangay;
                }

                // Update Risk Distribution Chart - Use correct DatabaseAPI with proper URL construction
                const apiParams = { action: 'analysis_data' };
                if (barangay && barangay !== '') {
                    apiParams.barangay = barangay;
                }
                const apiUrl = constructAPIURL('/api/DatabaseAPI.php', apiParams);
                const response = await fetch(apiUrl);
                const riskData = await response.json();
                console.log('📈 Risk Distribution Data (RAW):', riskData);
                console.log('📈 Risk Data Type:', typeof riskData);
                console.log('📈 Risk Data Keys:', Object.keys(riskData || {}));
                console.log('📈 Risk Data Success:', riskData?.success);
                console.log('📈 Risk Data Data:', riskData?.data);
                console.log('📈 High Risk Cases:', riskData?.data?.high_risk_cases);
                console.log('📈 SAM Cases:', riskData?.data?.sam_cases);
                console.log('📈 Critical MUAC:', riskData?.data?.critical_muac);
                
                if (riskData && riskData.success && riskData.data) {
                    // WHO classification data will be loaded by the DOMContentLoaded event handler
                    
                                    // Update individual cards with risk distribution data
                const highRisk = document.getElementById('community-high-risk');
                const riskChange = document.getElementById('community-risk-change');
                
                console.log('High Risk Element:', highRisk);
                console.log('Risk Change Element:', riskChange);
                console.log('High Risk Data:', riskData.data?.high_risk_cases);
                console.log('SAM Cases Data:', riskData.data?.sam_cases);
                console.log('Critical MUAC Data:', riskData.data?.critical_muac);
                console.log('🔍 Checking HTML elements...');
                console.log('🔍 community-high-risk element:', document.getElementById('community-high-risk'));
                console.log('🔍 community-sam-cases element:', document.getElementById('community-sam-cases'));
                console.log('🔍 community-critical-muac element:', document.getElementById('community-critical-muac'));
                
                if (highRisk && riskChange) {
                    const highRiskValue = riskData.data?.high_risk_cases || 0;
                    const moderateValue = riskData.data?.sam_cases || 0;
                    
                    console.log('Current dashboardState.highRisk:', dashboardState.highRisk);
                    console.log('New highRiskValue:', highRiskValue);
                    console.log('Current dashboardState.moderateRisk:', dashboardState.moderateRisk);
                    console.log('New moderateValue:', moderateValue);
                    
                    // Force update on first load or if data has changed
                    if (dashboardState.isFirstLoad || dashboardState.highRisk === null || dashboardState.highRisk !== highRiskValue) {
                        console.log('✅ Updating highRisk.textContent to:', highRiskValue);
                        console.log('Before update - highRisk.textContent:', highRisk.textContent);
                        highRisk.textContent = highRiskValue;
                        console.log('After update - highRisk.textContent:', highRisk.textContent);
                        dashboardState.highRisk = highRiskValue;
                        if (dashboardState.isFirstLoad) {
                            console.log('🎯 First load completed for highRisk');
                        }
                    } else {
                        console.log('❌ Skipping highRisk update - no change');
                    }
                    
                    if (dashboardState.isFirstLoad || dashboardState.moderateRisk === null || dashboardState.moderateRisk !== moderateValue) {
                        console.log('✅ Updating riskChange.textContent to:', moderateValue);
                        console.log('Before update - riskChange.textContent:', riskChange.textContent);
                        riskChange.textContent = moderateValue;
                        console.log('After update - riskChange.textContent:', riskChange.textContent);
                        dashboardState.moderateRisk = moderateValue;
                        if (dashboardState.isFirstLoad) {
                            console.log('🎯 First load completed for moderateRisk');
                        }
                    } else {
                        console.log('❌ Skipping moderateRisk update - no change');
                    }
                } else {
                    console.log('❌ HTML elements not found for High Risk Cases');
                }

                    const samCases = document.getElementById('community-sam-cases');
                    const samChange = document.getElementById('community-sam-change');
                    
                    console.log('SAM Cases Element:', samCases);
                    console.log('SAM Change Element:', samChange);
                    console.log('SAM Cases Data (severe):', riskData.data?.sam_cases);
                    console.log('SAM Change Data (high):', riskData.data?.critical_muac);
                    
                    if (samCases && samChange) {
                        const samCasesValue = riskData.data?.sam_cases || 0;
                        const samChangeValue = riskData.data?.critical_muac || 0;
                        
                        console.log('Current dashboardState.samCases:', dashboardState.samCases);
                        console.log('New samCasesValue:', samCasesValue);
                        console.log('Current dashboardState.samChange:', dashboardState.samChange);
                        console.log('New samChangeValue:', samChangeValue);
                        
                        // Force update on first load or if data has changed
                        if (dashboardState.isFirstLoad || dashboardState.samCases === null || dashboardState.samCases !== samCasesValue) {
                            console.log('✅ Updating samCases.textContent to:', samCasesValue);
                            console.log('Before update - samCases.textContent:', samCases.textContent);
                            samCases.textContent = samCasesValue;
                            console.log('After update - samCases.textContent:', samCases.textContent);
                            dashboardState.samCases = samCasesValue;
                            if (dashboardState.isFirstLoad) {
                                console.log('🎯 First load completed for samCases');
                            }
                        } else {
                            console.log('❌ Skipping samCases update - no change');
                        }
                        
                        if (dashboardState.isFirstLoad || dashboardState.samChange === null || dashboardState.samChange !== samChangeValue) {
                            console.log('✅ Updating samChange.textContent to:', samChangeValue);
                            console.log('Before update - samChange.textContent:', samChange.textContent);
                            samChange.textContent = samChangeValue;
                            console.log('After update - samChange.textContent:', samChange.textContent);
                            dashboardState.samChange = samChangeValue;
                            if (dashboardState.isFirstLoad) {
                                console.log('🎯 First load completed for samChange');
                            }
                        } else {
                            console.log('❌ Skipping samChange update - no change');
                        }
                    } else {
                        console.log('❌ HTML elements not found for SAM Cases');
                    }
                }

                // Update Nutritional Statistics using the new function
                console.log('🔄 Updating Nutritional Statistics...');
                if (window.nutritionalStatistics) {
                    updateNutritionalStatisticsDisplay(window.nutritionalStatistics);
                } else {
                    console.log('❌ No nutritional statistics data available');
                }

                // Update Geographic Distribution Chart using pre-loaded data
                console.log('🌍 Using pre-loaded Geographic Distribution data...');
                console.log('🌍 Geographic Data:', geographicDistributionData);
                if (geographicDistributionData && geographicDistributionData.length > 0) {
                    console.log('🌍 Updating geographic display with data:', geographicDistributionData);
                    updateGeographicChartDisplay(geographicDistributionData);
                } else {
                    console.log('🌍 No geographic data, showing empty display');
                    updateGeographicChartDisplay([]);
                }

                // Update Critical Alerts - Use assessment data from dashboard stats
                // The critical alerts are now updated via updateCriticalAlertsFromScreeningData()
                // which is called from the dashboard_assessment_stats API data
                
                // Mark first load as complete
                if (dashboardState.isFirstLoad) {
                    dashboardState.isFirstLoad = false;
                    console.log('🎯 Dashboard first load completed - state management now active');
                }
                
                // Update Nutritional Status Overview Card
                updateNutritionalStatusCard([], []);
            } catch (error) {
                console.error('Error updating community metrics:', error);
            } finally {
                dashboardState.updateInProgress = false;
            }
            }, 1000); // 1000ms debounce delay to prevent flickering
        }

        // Function to update geographic distribution
        async function updateGeographicChart(barangay = '') {
            try {
                console.log('🌍 updateGeographicChart called with barangay:', barangay);
                // Use pre-loaded data
                console.log('🌍 Using pre-loaded Geographic Data in updateGeographicChart:', geographicDistributionData);
                
                if (geographicDistributionData && geographicDistributionData.length > 0) {
                    updateGeographicChartDisplay(geographicDistributionData);
                } else {
                    console.log('No geographic data available');
                    updateGeographicChartDisplay([]);
                }
            } catch (error) {
                console.error('Geographic chart update error:', error);
                updateGeographicChartDisplay([]);
            }
        }



        // Function to update geographic distribution display
        function updateGeographicChartDisplay(data) {
            console.log('🌍 updateGeographicChartDisplay called with data:', data);
            const container = document.getElementById('barangay-distribution');
            if (!container) {
                console.error('❌ Geographic chart container not found!');
                return;
            }
            console.log('🌍 Geographic chart container found, updating display...');

            container.innerHTML = '';
            
            if (data && data.length > 0) {
                // Sort by count descending (highest to lowest)
                const sortedData = data.sort((a, b) => (b.count || 0) - (a.count || 0));
                
                // Find the maximum count for percentage calculation
                const maxCount = Math.max(...sortedData.map(d => d.count || 0));
                
                sortedData.forEach((item, index) => {
                    const barItem = document.createElement('div');
                    barItem.className = 'geo-bar-item';
                    
                    // Calculate percentage based on count
                    const percentage = maxCount > 0 ? Math.round(((item.count || 0) / maxCount) * 100) : 0;
                    
                    barItem.innerHTML = `
                        <div class="geo-bar-name">${item.barangay || 'Unknown'}</div>
                        <div class="geo-bar-progress">
                            <div class="geo-bar-fill" style="width: ${percentage}%"></div>
                        </div>
                        <div class="geo-bar-count">${item.count || 0}</div>
                    `;
                    container.appendChild(barItem);
                });
            } else {
                console.log('🌍 No geographic data available, showing no data message');
                // Show no data message
                const noDataItem = document.createElement('div');
                noDataItem.style.cssText = `
                    padding: 20px;
                    text-align: center;
                    color: var(--color-text);
                    opacity: 0.7;
                    font-style: italic;
                `;
                noDataItem.textContent = 'No users found in selected area';
                container.appendChild(noDataItem);
            }
            console.log('🌍 Geographic chart display update completed');
        }
        
        // Function to clean up expired cache entries
        function cleanupCache() {
            const now = Date.now();
            for (const [key, value] of dashboardState.cache.entries()) {
                if (now - value.timestamp > dashboardState.cacheTimeout) {
                    dashboardState.cache.delete(key);
                }
            }
        }
        
        // Clean up cache every 5 minutes
        setInterval(cleanupCache, 300000);

        // Function to update critical alerts - DEPRECATED
        // Critical alerts are now updated via updateCriticalAlertsFromScreeningData()
        // which uses data from the dashboard_assessment_stats API
        async function updateCriticalAlerts(barangay = '') {
            // This function is no longer used - critical alerts are updated
            // via the assessment data from dashboard_assessment_stats API
            console.log('updateCriticalAlerts called but deprecated - using assessment data instead');
        }

        // Function to generate intelligent programs (manual trigger)
        async function generateIntelligentPrograms(barangay = null) {
            // Use passed barangay parameter or fall back to currently selected barangay
            const targetBarangay = barangay !== null ? barangay : currentSelectedBarangay;
            await updateIntelligentPrograms(targetBarangay);
        }

        // Function to show AI reasoning in a popup
        function showAIReasoning(title, reasoning) {
            // Create modal overlay
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 1000;
                opacity: 0;
                transition: opacity 0.3s ease;
            `;
            
            // Create modal content
            const modalContent = document.createElement('div');
            modalContent.style.cssText = `
                background: var(--color-bg, white);
                padding: 25px;
                border-radius: 15px;
                max-width: 500px;
                width: 90%;
                box-shadow: 0 10px 30px rgba(161, 180, 84, 0.2);
                border: 1px solid rgba(161, 180, 84, 0.1);
                transform: translateY(20px);
                transition: transform 0.3s ease;
            `;
            
            modalContent.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0; color: #1B3A1B; font-size: 18px; font-weight: 600;">AI Reasoning</h3>
                    <button onclick="this.closest('.ai-reasoning-modal').remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--color-text); opacity: 0.7; transition: opacity 0.2s ease;">&times;</button>
                </div>
                <div style="margin-bottom: 15px; padding: 12px; background: rgba(161, 180, 84, 0.05); border-radius: 8px; border: 1px solid rgba(161, 180, 84, 0.1);">
                    <strong style="color: #1B3A1B; font-size: 14px;">Program:</strong>
                    <span style="color: var(--color-text); margin-left: 8px; font-weight: 500;">${title}</span>
                </div>
                <div style="background: rgba(161, 180, 84, 0.08); padding: 18px; border-radius: 12px; border-left: 4px solid var(--color-highlight); box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);">
                    <p style="margin: 0; color: var(--color-text); line-height: 1.6; font-style: italic; font-size: 14px;">${reasoning}</p>
                </div>
                <div style="text-align: center; margin-top: 25px;">
                    <button onclick="this.closest('.ai-reasoning-modal').remove()" style="background: linear-gradient(135deg, var(--color-highlight), var(--color-accent1)); color: white; border: none; padding: 12px 24px; border-radius: 25px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(161, 180, 84, 0.3);">
                        Close
                    </button>
                </div>
            `;
            
            // Add modal to page
            modal.appendChild(modalContent);
            modal.className = 'ai-reasoning-modal';
            document.body.appendChild(modal);
            
            // Animate in
            setTimeout(() => {
                modal.style.opacity = '1';
                modalContent.style.transform = 'translateY(0)';
            }, 10);
            
            // Close on background click
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }

        // Function to open notification modal for critical alerts
        function openNotificationModal(userName, userEmail, alertTitle) {
            // Validate inputs
            if (!userName || !userEmail) {
                console.error('Missing user information:', { userName, userEmail, alertTitle });
                alert('Error: Missing user information for notification');
                return;
            }
            
            console.log('Opening notification modal for:', { userName, userEmail, alertTitle });
            
            // Create modal overlay
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 1000;
                opacity: 0;
                transition: opacity 0.3s ease;
            `;
            
            // Create modal content
            const modalContent = document.createElement('div');
            modalContent.style.cssText = `
                background: var(--color-card);
                padding: 25px;
                border-radius: 15px;
                max-width: 500px;
                width: 90%;
                box-shadow: 0 10px 30px rgba(161, 180, 84, 0.2);
                border: 1px solid rgba(161, 180, 84, 0.1);
                transform: translateY(20px);
                transition: transform 0.3s ease;
            `;
            
            // Escape special characters to prevent XSS
            const safeUserName = userName.replace(/'/g, "\\'").replace(/"/g, '\\"');
            const safeAlertTitle = alertTitle.replace(/'/g, "\\'").replace(/"/g, '\\"');
            const safeUserEmail = userEmail.replace(/'/g, "\\'").replace(/"/g, '\\"');
            
            modalContent.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0; color: #1B3A1B; font-size: 18px; font-weight: 600;">📱 Send Notification</h3>
                    <button onclick="this.closest('.notification-modal').remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--color-text); opacity: 0.7; transition: opacity 0.2s ease;">&times;</button>
                </div>
                
                <div style="margin-bottom: 20px; padding: 15px; background: rgba(161, 180, 84, 0.05); border-radius: 8px; border: 1px solid rgba(161, 180, 84, 0.1);">
                    <div style="margin-bottom: 8px;">
                        <strong style="color: #1B3A1B; font-size: 14px;">Recipient:</strong>
                        <span style="color: var(--color-text); margin-left: 8px; font-weight: 500;">${safeUserName}</span>
                    </div>
                    <div>
                        <strong style="color: #1B3A1B; font-size: 14px;">Alert:</strong>
                        <span style="color: var(--color-text); margin-left: 8px; font-weight: 500;">${safeAlertTitle}</span>
                    </div>
                    <div style="margin-top: 8px;">
                        <strong style="color: #1B3A1B; font-size: 14px;">Email:</strong>
                        <span style="color: var(--color-text); margin-left: 8px; font-weight: 500;">${safeUserEmail}</span>
                    </div>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; color: var(--color-text); font-weight: 500;">Notification Message:</label>
                    <textarea id="notification-message" placeholder="Enter your message to this user..." style="width: 100%; min-height: 100px; padding: 12px; border: 1px solid rgba(161, 180, 84, 0.2); border-radius: 8px; background: rgba(42, 51, 38, 0.3); color: var(--color-text); font-size: 14px; resize: vertical; font-family: inherit;"></textarea>
                </div>
                
                <div style="display: flex; gap: 15px; justify-content: flex-end;">
                    <button onclick="this.closest('.notification-modal').remove()" style="background: rgba(161, 180, 84, 0.2); color: var(--color-text); border: none; padding: 12px 24px; border-radius: 25px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s ease;">
                        Cancel
                    </button>
                    <button onclick="sendPersonalNotification('${safeUserName}', '${safeUserEmail}', '${safeAlertTitle}')" style="background: linear-gradient(135deg, var(--color-highlight), var(--color-accent3)); color: white; border: none; padding: 12px 24px; border-radius: 25px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(161, 180, 84, 0.3);">
                        📱 Send Notification
                    </button>
                </div>
            `;
            
            // Add modal to page
            modal.appendChild(modalContent);
            modal.className = 'notification-modal';
            document.body.appendChild(modal);
            
            // Animate in
            setTimeout(() => {
                modal.style.opacity = '1';
                modalContent.style.transform = 'translateY(0)';
            }, 10);
            
            // Close on background click
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }

        // Function to send personal notification
        async function sendPersonalNotification(userName, userEmail, alertTitle) {
            const messageInput = document.getElementById('notification-message');
            const message = messageInput ? messageInput.value.trim() : '';
            
            if (!message) {
                alert('Please enter a message before sending the notification.');
                return;
            }
            
            if (!userEmail) {
                alert('Cannot send notification: User email not available.');
                return;
            }
            
            try {
                console.log('Sending notification to:', { userName, userEmail, alertTitle, message });
                
                // Create a custom event notification using the event.php system
                const notificationData = {
                    title: `🚨 Critical Alert: ${alertTitle}`,
                    body: message,
                    target_user: userEmail,
                    alert_type: 'critical_notification',
                    user_name: userName
                };
                
                console.log('Notification data:', notificationData);
                
                // Send to the centralized DatabaseAPI
                const response = await fetch('/api/DatabaseAPI.php?action=send_notification', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        notification_data: JSON.stringify(notificationData)
                    })
                });
                
                console.log('Response status:', response.status);
                const result = await response.json();
                console.log('Response result:', result);
                
                if (result.success) {
                    // Show success message
                    showNotificationSuccess(`Notification sent successfully to ${userName}!`);
                    
                    // Close the modal
                    const modal = document.querySelector('.notification-modal');
                    if (modal) modal.remove();
                } else {
                    // Show error message
                    showNotificationError(`Failed to send notification: ${result.message || 'Unknown error'}`);
                }
                
            } catch (error) {
                console.error('Error sending notification:', error);
                showNotificationError('Error sending notification. Please try again.');
            }
        }

        // Function to show notification success
        function showNotificationSuccess(message) {
            const successDiv = document.createElement('div');
            successDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, var(--color-highlight), var(--color-accent3));
                color: white;
                padding: 15px 20px;
                border-radius: 10px;
                box-shadow: 0 4px 15px rgba(161, 180, 84, 0.3);
                z-index: 1001;
                max-width: 300px;
                animation: slideInRight 0.3s ease;
            `;
            successDiv.innerHTML = `✅ ${message}`;
            
            document.body.appendChild(successDiv);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (successDiv.parentNode) {
                    successDiv.style.animation = 'slideOutRight 0.3s ease';
                    setTimeout(() => {
                        if (successDiv.parentNode) {
                            successDiv.parentNode.removeChild(successDiv);
                        }
                    }, 300);
                }
            }, 5000);
        }

        // Function to show notification error
        function showNotificationError(message) {
            const errorDiv = document.createElement('div');
            errorDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, var(--color-danger), #e74c3c);
                color: white;
                padding: 15px 20px;
                border-radius: 10px;
                box-shadow: 0 4px 15px rgba(207, 134, 134, 0.3);
                z-index: 1001;
                max-width: 300px;
                animation: slideInRight 0.3s ease;
            `;
            errorDiv.innerHTML = `❌ ${message}`;
                
            document.body.appendChild(errorDiv);
                
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (errorDiv.parentNode) {
                    errorDiv.style.animation = 'slideOutRight 0.3s ease';
                    setTimeout(() => {
                        if (errorDiv.parentNode) {
                            errorDiv.parentNode.removeChild(errorDiv);
                        }
                    }, 300);
                }
            }, 5000);
        }

        // Function to update intelligent programs
        async function updateIntelligentPrograms(barangay = '') {
            try {
                
                // Show loading state
                const loadingElement = document.getElementById('programs-loading');
                const programsContainer = document.getElementById('intelligent-program-cards');
                
                if (loadingElement && programsContainer) {
                    loadingElement.style.display = 'flex';
                    programsContainer.style.display = 'none';
                }
                
                const params = {};
                if (barangay && barangay !== '') {
                    if (barangay.startsWith('MUNICIPALITY_')) {
                        params.municipality = barangay.replace('MUNICIPALITY_', '');
                    } else {
                        params.barangay = barangay;
                    }
                } else {
                }

                const data = await fetchDataFromAPI('intelligent_programs', params);
                
                if (data && data.success) {
                    // Use the intelligent programs data directly
                    const programs = data.data.programs || [];
                    const analysis = data.data.data_analysis || {};
                    updateIntelligentProgramsDisplay(programs, analysis);
                } else {
                    // Show appropriate no-data message
                    showFallbackPrograms();
                }
            } catch (error) {
                // Show fallback programs on error
                showFallbackPrograms();
            }
        }

        // Function to update intelligent programs display
        function updateIntelligentProgramsDisplay(programs, analysis) {
            const loadingElement = document.getElementById('programs-loading');
            const programsContainer = document.getElementById('intelligent-program-cards');
            const debugElement = document.getElementById('community-health-debug');
            const debugContent = document.getElementById('debug-content');
            
            if (!loadingElement || !programsContainer) {
                return;
            }
            
            // Hide loading, show programs
            loadingElement.style.display = 'none';
            programsContainer.style.display = 'block';
            
            if (debugElement && debugContent && analysis) {
                debugElement.style.display = 'block';
                
                const debugInfo = `
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <div><strong>Total Users:</strong> ${analysis.total_users || 0}</div>
                        <div><strong>High Risk %:</strong> ${analysis.high_risk_percentage || 0}%</div>
                        <div><strong>SAM Cases:</strong> ${analysis.sam_cases || 0}</div>
                        <div><strong>Children:</strong> ${analysis.children_count || 0}</div>
                        <div><strong>Elderly:</strong> ${analysis.elderly_count || 0}</div>
                        <div><strong>Low Dietary Diversity:</strong> ${analysis.low_dietary_diversity || 0}</div>
                        <div><strong>Average Risk Score:</strong> ${analysis.average_risk || 0}</div>
                        <div><strong>Community Status:</strong> <span style="color: #1B3A1B;">${analysis.community_health_status || 'Unknown'}</span></div>
                        <div><strong>Programs Generated:</strong> <span style="color: var(--color-accent1); font-weight: bold;">${programs ? programs.length : 0}</span></div>
                    </div>
                    ${analysis.message ? `<div style="margin-top: 10px; padding: 10px; background: rgba(161, 180, 84, 0.1); border-radius: 6px; border-left: 3px solid var(--color-highlight);"><strong>Analysis:</strong> ${analysis.message}</div>` : ''}
                `;
                
                debugContent.innerHTML = debugInfo;
            }
            
            // Clear existing programs
            programsContainer.innerHTML = '';
            
            // Check if this is a no-data response
            if (analysis && analysis.no_data) {
                // Show no-data message
                const noDataCard = document.createElement('div');
                noDataCard.className = 'program-card';
                noDataCard.style.cssText = 'text-align: center; padding: 40px 20px; opacity: 0.8;';
                
                noDataCard.innerHTML = `
                    <div style="font-size: 18px; font-weight: 600; color: var(--color-highlight); margin-bottom: 10px;">
                        No Data Available
                    </div>
                    <div style="font-size: 14px; color: var(--color-text); opacity: 0.8; line-height: 1.5;">
                        ${analysis.message || 'No users found in the selected area. Programs will be generated once users are registered.'}
                    </div>
                    <div style="margin-top: 20px; padding: 12px; background: rgba(161, 180, 84, 0.1); border-radius: 8px; border: 1px solid rgba(161, 180, 84, 0.2);">
                        <div style="font-size: 12px; color: var(--color-text); opacity: 0.7;">
                            <strong>Tip:</strong> Register users in this area to generate intelligent nutrition programs based on real data.
                        </div>
                    </div>
                `;
                
                programsContainer.appendChild(noDataCard);
                return;
            }
            
            if (programs && programs.length > 0) {
                programs.forEach((program, index) => {
                    const programCard = createProgramCard(program, index);
                    programsContainer.appendChild(programCard);
                });
                
            } else {
                showFallbackPrograms();
            }
        }

        // Function to create individual program card
        function createProgramCard(program, index) {
            const card = document.createElement('div');
            card.className = 'program-card';
            
            // Add null checks and default values to prevent undefined display
            const foodName = program.food_name || 'Unnamed Program';
            const foodDescription = program.food_description || 'No description available';
            const foodEmoji = program.food_emoji || '🍽️';
            const nutritionalPriority = program.nutritional_priority || 'Medium';
            const nutritionalImpactScore = program.nutritional_impact_score || 50;
            const ingredients = program.ingredients || 'Ingredients not specified';
            const benefits = program.benefits || 'Benefits not specified';
            const aiReasoning = program.ai_reasoning || 'AI reasoning not available';
            
            // Determine priority class based on nutritional impact score
            let priorityClass = 'priority-medium';
            if (nutritionalImpactScore >= 85) priorityClass = 'priority-immediate';
            else if (nutritionalImpactScore >= 70) priorityClass = 'priority-high';
            
            card.innerHTML = `
                <div class="program-content">
                    <div class="program-title">${foodEmoji} ${foodName}</div>
                    <div class="program-description">${foodDescription}</div>
                    <div class="program-meta">
                        <span class="priority-tag ${priorityClass}">${getPriorityLabel(nutritionalImpactScore)}</span>
                        <div class="program-details" style="margin-top: 6px; font-size: 11px; opacity: 0.8;">
                            <div><strong>Priority:</strong> ${nutritionalPriority}</div>
                            <div><strong>Impact Score:</strong> ${nutritionalImpactScore}/100</div>
                            <div><strong>Ingredients:</strong> ${ingredients}</div>
                            <div class="target-location" style="background: rgba(161, 180, 84, 0.2); padding: 4px 8px; border-radius: 6px; margin-top: 4px; border-left: 3px solid var(--color-highlight);">
                                <strong style="color: #4CAF50;">Benefits:</strong> ${benefits}
                            </div>
                        </div>
                        <div style="display: flex; gap: 10px; margin-top: 8px;">
                            <button class="show-reasoning-btn" onclick="showAIReasoning('${foodName.replace(/'/g, "\\'")}', '${aiReasoning.replace(/'/g, "\\'")}')">
                                Show AI Reasoning
                            </button>
                            <button class="create-this-program-btn" onclick="createProgramFromCard('${foodName.replace(/'/g, "\\'")}', '${nutritionalPriority}', '${nutritionalPriority}', '${foodDescription.replace(/'/g, "\\'")}', '${getPriorityLabel(nutritionalImpactScore)}')">
                                Create This Program
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            // Add smooth animation delay
            card.style.opacity = '0';
            card.style.transform = 'translateY(10px)';
            card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            
            // Use requestAnimationFrame for smoother animation
            requestAnimationFrame(() => {
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            // Remove inline transform after animation to allow CSS hover effects
            setTimeout(() => {
                card.style.removeProperty('transform');
                card.style.removeProperty('transition');
            }, (index * 100) + 300);
            
            return card;
        }

        // Function to get proper priority labels
        function getPriorityLabel(priority) {
            if (typeof priority === 'number') {
                // Handle nutritional impact score
                if (priority >= 85) return 'High Impact';
                if (priority >= 70) return 'Medium Impact';
                if (priority >= 50) return 'Moderate Impact';
                return 'Low Impact';
            } else {
                // Handle string priority
                switch(priority) {
                    case 'Critical':
                        return 'High Risk';
                    case 'High':
                        return 'Moderate Risk';
                    case 'Medium':
                        return 'Low Risk';
                    default:
                        return priority;
                }
            }
        }

        // Function to show fallback programs when API fails
        function showFallbackPrograms() {
            const loadingElement = document.getElementById('programs-loading');
            const programsContainer = document.getElementById('intelligent-program-cards');
            
            if (!loadingElement || !programsContainer) return;
            
            loadingElement.style.display = 'none';
            programsContainer.style.display = 'block';
            
            // Show appropriate message based on whether we have data or not
            const noDataCard = document.createElement('div');
            noDataCard.className = 'program-card';
            noDataCard.style.cssText = 'text-align: center; padding: 40px 20px; opacity: 0.8;';
            
            noDataCard.innerHTML = `
                <div style="font-size: 18px; font-weight: 600; color: var(--color-highlight); margin-bottom: 10px;">
                    No Community Data Available
                </div>
                <div style="font-size: 14px; color: var(--color-text); opacity: 0.8; line-height: 1.5;">
                    The intelligent programs system requires community data to generate targeted nutrition interventions. 
                    Programs will be automatically generated once users are registered and screened in the selected area.
                </div>
                <div style="margin-top: 20px; padding: 12px; background: rgba(161, 180, 84, 0.1); border-radius: 8px; border: 1px solid rgba(161, 180, 84, 0.2);">
                    <div style="font-size: 12px; color: var(--color-text); opacity: 0.7;">
                        <strong>Next Steps:</strong> Register users in this area to enable intelligent program generation based on real community health data.
                    </div>
                </div>
            `;
            
            programsContainer.innerHTML = '';
            programsContainer.appendChild(noDataCard);
            
        }



        // Track current alerts state to prevent unnecessary updates
        let currentAlertsState = { hasAlerts: false, lastContent: '' };
        
        // Initialize alerts state based on current DOM content
        function initializeAlertsState() {
            const container = document.getElementById('critical-alerts');
            if (container) {
                const hasExistingAlerts = container.querySelector('.alert-item:not(.no-alerts-item)');
                currentAlertsState.hasAlerts = !!hasExistingAlerts;
                currentAlertsState.lastContent = container.innerHTML;
            }
        }
        
        function clearAlertsState() {
            currentAlertsState.hasAlerts = false;
            currentAlertsState.lastContent = '';
        }

        // Function to update critical alerts display (legacy - kept for compatibility)
        function updateCriticalAlertsDisplay(data) {
            const container = document.getElementById('critical-alerts');
            if (!container) return;

            // Generate new content
            const newContent = generateCriticalAlertsHTML(data);
            const hasNewAlerts = data && data.length > 0;
            
            // Prevent flickering by checking if content is the same
            if (container.innerHTML === newContent) {
                return; // No changes needed
            }
            
            // Smart update logic: only show "no alerts" if we currently have no alerts displayed
            // and the new data also has no alerts
            const currentlyHasAlerts = currentAlertsState.hasAlerts;
            const shouldShowNoAlerts = !hasNewAlerts && !currentlyHasAlerts;
            
            // If we currently have alerts and the new data has no alerts, keep the current alerts
            // This prevents the flickering from "alerts" -> "no alerts" -> "alerts"
            if (currentlyHasAlerts && !hasNewAlerts) {
                return;
            }
            
            // Update content and state
            container.innerHTML = newContent;
            currentAlertsState.hasAlerts = hasNewAlerts;
            currentAlertsState.lastContent = newContent;
        }

        // Function to generate critical alerts HTML
        function generateCriticalAlertsHTML(data) {
            if (data && data.length > 0) {
                return data.map(alert => {
                    const riskLevel = alert.alert_level || 'Unknown Risk';
                    const ageGroup = alert.age_group || 'Unknown Age';
                    const barangay = alert.barangay || 'Unknown Location';
                    const riskScore = alert.risk_score || 0;
                    const age = alert.age || 'Unknown';
                    const gender = alert.gender || 'Unknown';
                    
                    let type = 'warning';
                    if (riskLevel === 'Severe Risk') type = 'critical';
                    else if (riskLevel === 'High Risk') type = 'warning';
                    else type = 'info';
                    
                    const title = `${riskLevel} - ${ageGroup}`;
                    const user = `Age: ${age} | Gender: ${gender} | Location: ${barangay}`;
                    const time = alert.created_at || 'Recent';
                    const userEmail = alert.user_email || '';
                    
                    return `
                        <li class="alert-item ${type}">
                            <div class="alert-content">
                                <h4>${title}</h4>
                                <p>Risk Score: ${riskScore} | ${user}</p>
                            </div>
                            <div class="alert-actions">
                                <div class="alert-time">${time}</div>
                                <div class="alert-buttons">
                                    <span class="alert-badge badge-${type}">${type === 'critical' ? 'Critical' : 'Warning'}</span>
                                    <button class="notify-btn" onclick="openNotificationModal('${user}', '${userEmail}', '${title}')" title="Send notification to this user">
                                        📱 Notify
                                    </button>
                                </div>
                            </div>
                        </li>
                    `;
                }).join('');
            } else {
                return `
                    <li class="no-alerts-item">
                        <div style="padding: 15px; text-align: center; color: var(--color-text); opacity: 0.7; font-style: italic;">
                            No critical alerts at this time
                        </div>
                    </li>
                `;
                }
            }

        // Function to update analysis section
        async function updateAnalysisSection(barangay = '') {
            try {
                
                const params = {};
                if (barangay && barangay !== '') {
                    params.barangay = barangay;
                } else {
                }

                const data = await fetchDataFromAPI('analysis_data', params);
                
                if (data && data.success) {
                    // Update risk analysis
                    updateRiskAnalysis(data.risk_analysis);
                    
                    // Update demographics
                    updateDemographics(data.demographics);
                } else {
                }
            } catch (error) {
            }
        }

        // Function to update risk analysis
        function updateRiskAnalysis(data) {
            if (!data) return;
            
            const totalUsers = data.total_users || 0;
            const atRiskUsers = data.at_risk_users || 0;
            
            // Calculate percentages
            const highRiskPercent = totalUsers > 0 ? Math.round((atRiskUsers / totalUsers) * 100) : 0;
            const moderateRiskPercent = totalUsers > 0 ? Math.round(((totalUsers - atRiskUsers) * 0.6 / totalUsers) * 100) : 0;
            const lowRiskPercent = totalUsers > 0 ? Math.round(((totalUsers - atRiskUsers) * 0.4 / totalUsers) * 100) : 0;
            const samPercent = totalUsers > 0 ? Math.round((atRiskUsers * 0.3 / totalUsers) * 100) : 0;
            
            // Update progress bars
            const highRiskBar = document.getElementById('high-risk-bar');
            const moderateRiskBar = document.getElementById('moderate-risk-bar');
            const lowRiskBar = document.getElementById('low-risk-bar');
            const samBar = document.getElementById('sam-bar');
            
            if (highRiskBar) {
                highRiskBar.style.width = highRiskPercent + '%';
                document.getElementById('high-risk-percent').textContent = highRiskPercent + '%';
            }
            
            if (moderateRiskBar) {
                moderateRiskBar.style.width = moderateRiskPercent + '%';
                document.getElementById('moderate-risk-percent').textContent = moderateRiskPercent + '%';
            }
            
            if (lowRiskBar) {
                lowRiskBar.style.width = lowRiskPercent + '%';
                document.getElementById('low-risk-percent').textContent = lowRiskPercent + '%';
            }
            
            if (samBar) {
                samBar.style.width = samPercent + '%';
                document.getElementById('sam-percent').textContent = samPercent + '%';
            }
        }

        // Function to update demographics
        function updateDemographics(data) {
            if (!data) return;
            
            const totalUsers = data.total_users || 0;
            
            if (totalUsers > 0) {
                // Calculate percentages for age groups
                const childrenPercent = Math.round(((data.age_0_5 || 0) + (data.age_6_12 || 0) + (data.age_13_17 || 0)) / totalUsers * 100);
                const adultsPercent = Math.round((data.age_18_59 || 0) / totalUsers * 100);
                const elderlyPercent = Math.round((data.age_60_plus || 0) / totalUsers * 100);
                
                // Update progress bars
                const childrenBar = document.getElementById('children-bar');
                const adultsBar = document.getElementById('adults-bar');
                const elderlyBar = document.getElementById('elderly-bar');
                const genderBar = document.getElementById('gender-bar');
                
                if (childrenBar) {
                    childrenBar.style.width = childrenPercent + '%';
                    document.getElementById('children-percent').textContent = childrenPercent + '%';
                }
                
                if (adultsBar) {
                    adultsBar.style.width = adultsPercent + '%';
                    document.getElementById('adults-percent').textContent = adultsPercent + '%';
                }
                
                if (elderlyBar) {
                    elderlyBar.style.width = elderlyPercent + '%';
                    document.getElementById('elderly-percent').textContent = elderlyPercent + '%';
                }
                
                if (genderBar) {
                    // For now, show a balanced distribution
                    genderBar.style.width = '50%';
                    document.getElementById('gender-distribution').textContent = '50% M, 50% F';
                }
            }
        }

        // API Connection and Data Fetching Functions
        const API_BASE_URL = window.location.origin + '/api/';

        // Function to fetch data from centralized DatabaseAPI
        async function fetchDataFromAPI(endpoint, params = {}) {
            try {
                // Create cache key
                const cacheKey = `${endpoint}_${JSON.stringify(params)}`;
                const now = Date.now();
                
                // Check cache first (skip cache for real-time data)
                if (!endpoint.includes('dashboard_assessment_stats') && dashboardState.cache.has(cacheKey)) {
                    const cached = dashboardState.cache.get(cacheKey);
                    if (now - cached.timestamp < dashboardState.cacheTimeout) {
                        console.log(`📦 Using cached data for ${endpoint}`);
                        return cached.data;
                    }
                }
                
                // Use our new assessment API for dashboard stats
                let url;
                if (endpoint === 'dashboard_assessment_stats') {
                    url = `${API_BASE_URL}dashboard_assessment_stats.php`;
                } else {
                    // Use centralized DatabaseAPI for other endpoints
                    url = `${API_BASE_URL}DatabaseAPI.php?action=${endpoint}`;
                }
                
                // Add query parameters if any
                if (Object.keys(params).length > 0) {
                    const queryString = new URLSearchParams(params).toString();
                    url += `&${queryString}`;
                }
                
                console.log('Fetching from centralized DatabaseAPI:', url);
                
                const response = await fetch(url);
                
                if (!response.ok) {
                    console.error('Response not OK:', response.status, response.statusText);
                    const errorText = await response.text();
                    console.error('Error response body:', errorText);
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('DatabaseAPI response for', endpoint, ':', data);
                
                // Cache the result (skip cache for real-time data)
                if (!endpoint.includes('dashboard_assessment_stats')) {
                    dashboardState.cache.set(cacheKey, {
                        data: data,
                        timestamp: now
                    });
                    console.log(`🌐 Cached fresh data for ${endpoint}`);
                }
                console.log('Returning data:', data.data || data);
                
                // For dashboard_assessment_stats, return the full response to preserve success field
                if (endpoint === 'dashboard_assessment_stats') {
                    return data;
                } else {
                return data.data || data; // Return data field if exists, otherwise return full response
                }
            } catch (error) {
                console.error('DatabaseAPI error for', endpoint, ':', error);
                return null;
            }
        }

        // Function to update trends chart with WHO standard counts using same data as donut chart
        async function updateTrendsChart(barangay = '') {
            console.log('📊 Updating trends chart with WHO standard counts...');
            console.log('📊 Trends chart function called with barangay:', barangay);
            
            try {
                const trendsChart = document.getElementById('trends-chart');
                console.log('📊 Trends chart element found:', trendsChart);
                if (!trendsChart) {
                    console.error('❌ Trends chart element not found');
                    return;
                }

                // Clear previous chart
                trendsChart.innerHTML = '';

                // Get current filter values
                const barangayValue = barangay || '';

                // Use the same bulk API that the donut chart uses
                const url = `/api/DatabaseAPI.php?action=get_all_who_classifications_bulk&barangay=${barangayValue}`;
                console.log('📊 Fetching WHO standard data from bulk API (same as donut chart):', url);
                
                const response = await fetch(url);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('WHO standard data received:', data);

                if (!data.success || !data.data || Object.keys(data.data).length === 0) {
                    console.log('No WHO standard data available for trends chart');
                    trendsChart.innerHTML = '<div style="color: var(--color-text); text-align: center; padding: 40px;">No data available</div>';
                    return;
                }

                // Count users eligible for each WHO standard based on age
                const whoStandardCounts = {};
                
                console.log('Processing WHO standard data:', data.data);
                
                // Process each WHO standard data and count only eligible users (exclude "No Data")
                Object.entries(data.data).forEach(([standard, standardData]) => {
                    console.log(`Processing standard: ${standard}`, standardData);
                    
                    if (typeof standardData === 'object' && standardData !== null) {
                        // Count total users for this standard (sum of all classifications EXCEPT "No Data")
                        const totalUsers = Object.entries(standardData).reduce((sum, [classification, count]) => {
                            // Exclude "No Data" from the count
                            if (classification !== 'No Data' && typeof count === 'number') {
                                return sum + count;
                            }
                            return sum;
                        }, 0);
                        
                        console.log(`Total users for ${standard}: ${totalUsers} (excluding No Data)`);
                        
                        if (totalUsers > 0) {
                            // Convert standard name to display format
                            const displayName = standard.replace(/_/g, '-').replace(/\b\w/g, l => l.toUpperCase());
                            whoStandardCounts[displayName] = totalUsers;
                        }
                    }
                });
                
                console.log('WHO standard counts:', whoStandardCounts);

                // Convert to array and include classification breakdown for gradient bars
                const activeStandards = Object.entries(whoStandardCounts)
                    .filter(([standard, count]) => count > 0)
                    .map(([standard, count]) => {
                        // Get classification breakdown for this standard
                        const originalStandard = standard.replace(/-/g, '_').toLowerCase();
                        const classificationBreakdown = data.data[originalStandard] || {};
                        
                        // Filter out "No Data" and create percentage breakdown
                        const filteredClassifications = {};
                        Object.entries(classificationBreakdown).forEach(([classification, classificationCount]) => {
                            if (classification !== 'No Data' && typeof classificationCount === 'number' && classificationCount > 0) {
                                filteredClassifications[classification] = classificationCount;
                            }
                        });
                        
                        return { 
                            standard, 
                            count, 
                            classifications: filteredClassifications 
                        };
                    });

                if (activeStandards.length === 0) {
                    trendsChart.innerHTML = '<div style="color: var(--color-text); text-align: center; padding: 40px;">No WHO standard data found</div>';
                    return;
                }

                // Calculate dynamic scaling
                const maxCount = Math.max(...activeStandards.map(item => item.count));
                const chartHeight = 300;
                const maxBarHeight = Math.min(chartHeight * 0.8, Math.max(120, chartHeight - (activeStandards.length * 10))); // 2x bigger bars
                
                // Add population scale inside the trends chart container
                const totalScreened = data.total_users || 6; // Get total screened users
                const populationSlice = Math.ceil(totalScreened / 10); // Auto-calculate population slice (total users / 10)
                const scaleSteps = 10;
                
                console.log(`WHO Standard Distribution - totalScreened: ${totalScreened}, populationSlice: ${populationSlice}`);
                
                // Create population scale container using the existing 8px padding space
                const populationScale = document.createElement('div');
                populationScale.className = 'population-scale';
                const isLightTheme = document.body.classList.contains('light-theme');
                const textColor = isLightTheme ? '#000000' : '#FFFFFF';
                
                populationScale.style.cssText = `
                    position: absolute;
                    left: -8px;
                    top: 10px;
                    height: ${chartHeight - 20}px;
                    width: 40px;
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                    align-items: flex-end;
                    font-size: 10px;
                    color: ${textColor} !important;
                    opacity: 0.7;
                    z-index: 10;
                `;
                
                // Create scale labels based on calculated population slice (stops at populationSlice)
                // Create a proper scale that shows the population slice and its fractions
                const scaleValues = [];
                for (let i = 6; i >= 1; i--) {
                    scaleValues.push(Math.ceil(populationSlice * (i / 6)));
                }
                
                console.log(`Scale values: ${scaleValues.join(', ')}`);
                scaleValues.forEach((value, index) => {
                    const scaleLabel = document.createElement('div');
                    scaleLabel.style.cssText = `
                        height: ${chartHeight / scaleValues.length}px;
                        display: flex;
                        align-items: center;
                        justify-content: flex-end;
                        padding-right: 5px;
                        border-right: 1px solid ${isLightTheme ? 'rgba(0,0,0,0.1)' : 'rgba(255,255,255,0.1)'};
                        color: ${textColor} !important;
                        font-size: 9px;
                        font-weight: 500;
                    `;
                    scaleLabel.textContent = value.toString();
                    populationScale.appendChild(scaleLabel);
                });
                
                // Add population scale to trends chart container (not inside trends chart)
                const trendsContainer = trendsChart.parentNode;
                if (trendsContainer) {
                    trendsContainer.appendChild(populationScale);
                    trendsContainer.style.position = 'relative';
                    
                    // Add legend inside the 15px margin area
                    const legend = document.createElement('div');
                    legend.className = 'trends-legend';
                    legend.style.cssText = `
                        position: absolute;
                        top: 15px;
                        right: 15px;
                        display: flex;
                        flex-wrap: wrap;
                        gap: 8px;
                        max-width: 200px;
                        z-index: 20;
                        color: ${textColor} !important;
                    `;
                    
                    // Function to get classification acronym
                    function getClassificationAcronym(classification) {
                        const acronyms = {
                            'Severely Underweight': 'SUW',
                            'Underweight': 'UW',
                            'Normal': 'N',
                            'Overweight': 'OW',
                            'Obese': 'O',
                            'Severely Stunted': 'SS',
                            'Stunted': 'S',
                            'Severely Wasted': 'SW',
                            'Wasted': 'W'
                        };
                        return acronyms[classification] || classification.substring(0, 3).toUpperCase();
                    }
                    
                    // Get unique classifications from all active standards
                    const uniqueClassifications = new Set();
                    activeStandards.forEach(standard => {
                        Object.keys(standard.classifications).forEach(classification => {
                            uniqueClassifications.add(classification);
                        });
                    });
                    
                    // Create legend items
                    uniqueClassifications.forEach(classification => {
                        const legendItem = document.createElement('div');
                        legendItem.style.cssText = `
                            display: flex;
                            align-items: center;
                            gap: 4px;
                            font-size: 10px;
                            color: ${textColor} !important;
                            opacity: 0.8;
                        `;
                        
                        const colorDot = document.createElement('div');
                        colorDot.style.cssText = `
                            width: 8px;
                            height: 8px;
                            border-radius: 50%;
                            background-color: ${getClassificationColor(classification)};
                            flex-shrink: 0;
                        `;
                        
                        const label = document.createElement('span');
                        label.textContent = getClassificationAcronym(classification);
                        
                        legendItem.appendChild(colorDot);
                        legendItem.appendChild(label);
                        legend.appendChild(legendItem);
                    });
                    
                    trendsContainer.appendChild(legend);
                }
                
                // Add padding to trends chart to move bars to the right and avoid overlap
                trendsChart.style.cssText = `
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-end;
                    height: ${chartHeight}px;
                    padding: 10px 10px 10px 50px;
                    gap: 5px;
                `;
                
                // Function to get classification color (exact match with age chart and donut chart)
                function getClassificationColor(classification) {
                    const colors = {
                        'Severely Underweight': '#E91E63',  // Pink
                        'Underweight': '#FFC107',           // Amber
                        'Normal': '#4CAF50',                // Green
                        'Overweight': '#FF9800',            // Orange
                        'Obese': '#F44336',                 // Red
                        'Severely Stunted': '#673AB7',      // Purple (matches age chart)
                        'Stunted': '#2196F3',               // Blue
                        'Severely Wasted': '#D32F2F',       // Dark Red (matches age chart)
                        'Wasted': '#FF5722'                 // Deep Orange (matches age chart)
                    };
                    return colors[classification] || '#9E9E9E';
                }

                // Create gradient bars for each WHO standard based on classification distribution
                activeStandards.forEach((item, index) => {
                    // Calculate bar height to align with population scale (stops at populationSlice)
                    const maxValue = populationSlice; // Use calculated population slice as maximum
                    const normalizedCount = Math.min(item.count, maxValue); // Cap at population slice
                    
                    // Calculate bar height as percentage of chart height, with padding to stay within bounds
                    const chartPadding = 20; // Padding to keep bars within container
                    const availableHeight = chartHeight - chartPadding;
                    const barHeight = Math.min((normalizedCount / maxValue) * availableHeight, availableHeight);
                    
                    console.log(`Bar ${index}: count=${item.count}, maxValue=${maxValue}, normalizedCount=${normalizedCount}, barHeight=${barHeight}, availableHeight=${availableHeight}`);
                    
                    // Create container for gradient bar
                    const barContainer = document.createElement('div');
                    barContainer.className = `trend-bar-container`;
                    barContainer.style.cssText = `
                        height: ${barHeight}px;
                        width: 100%;
                        flex: 1;
                        min-width: 30px;
                        max-width: 50px;
                        display: flex;
                        flex-direction: column;
                        border-radius: 4px 4px 0 0;
                        overflow: hidden;
                        border: 1px solid rgba(255,255,255,0.2);
                        cursor: pointer;
                        transition: all 0.3s ease;
                        position: relative;
                    `;
                    barContainer.title = `${item.standard}: ${item.count} users`;
                    
                    // Create gradient segments based on classifications
                    const classifications = item.classifications;
                    const totalUsers = item.count;
                    
                    // Sort classifications by count (descending) for better visual stacking
                    const sortedClassifications = Object.entries(classifications)
                        .sort(([,a], [,b]) => b - a);
                    
                    sortedClassifications.forEach(([classification, count]) => {
                        const percentage = (count / totalUsers) * 100;
                        const segmentHeight = (count / totalUsers) * barHeight;
                        const color = getClassificationColor(classification);
                        
                        const segment = document.createElement('div');
                        segment.style.cssText = `
                            height: ${segmentHeight}px;
                            background-color: ${color};
                            border: none;
                            flex-shrink: 0;
                        `;
                        segment.title = `${classification}: ${count} users (${percentage.toFixed(1)}%)`;
                        
                        barContainer.appendChild(segment);
                    });
                    
                    // Add count value overlay on the bar
                    barContainer.innerHTML += `
                        <div class="trend-bar-value" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 12px; font-weight: bold; color: white; text-shadow: 1px 1px 2px rgba(0,0,0,0.8); pointer-events: none;">
                            ${item.count}
                        </div>
                    `;
                    
                    trendsChart.appendChild(barContainer);
                });

                // Create labels showing WHO standard initials positioned below bars
                const trendsLabels = document.getElementById('trends-labels');
                if (trendsLabels) {
                    trendsLabels.innerHTML = '';
                    trendsLabels.style.cssText = `
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        padding: 10px 10px 0 50px;
                        height: 40px;
                        gap: 5px;
                    `;
                    
                    // Function to get WHO standard initials
                    function getWHOStandardInitials(standard) {
                        const initials = {
                            'Weight-For-Age': 'WFA',
                            'Height-For-Age': 'HFA',
                            'Weight-For-Height': 'WFH',
                            'Bmi-For-Age': 'BFA',
                            'Bmi-Adult': 'BMA'
                        };
                        return initials[standard] || standard;
                    }
                    
                    activeStandards.forEach((item, index) => {
                        const labelDiv = document.createElement('div');
                        labelDiv.className = 'trend-label-item';
                        labelDiv.style.cssText = `
                            flex: 1;
                            text-align: center;
                            font-size: 11px;
                            color: var(--color-text);
                            font-weight: 500;
                            min-width: 30px;
                            max-width: 50px;
                        `;
                        
                        const initials = getWHOStandardInitials(item.standard);
                        labelDiv.textContent = initials;
                        
                        trendsLabels.appendChild(labelDiv);
                    });
                }

                console.log(`✅ Trends chart updated successfully with WHO standard counts:`, activeStandards);

            } catch (error) {
                console.error('❌ Error updating trends chart:', error);
                const trendsChart = document.getElementById('trends-chart');
                if (trendsChart) {
                    trendsChart.innerHTML = '<div style="color: var(--color-text); text-align: center; padding: 40px;">Error loading trends: ' + error.message + '</div>';
                }
            }
        }

        // Helper function to get age range for WHO standard
        function getAgeRangeForWHOStandard(whoStandard) {
            switch (whoStandard) {
                case 'weight-for-age':
                case 'height-for-age':
                    return { fromMonths: 0, toMonths: 71 }; // 0-5.9 years
                case 'weight-for-height':
                    return { fromMonths: 0, toMonths: 60 }; // 0-5 years
                case 'bmi-for-age':
                    return { fromMonths: 60, toMonths: 228 }; // 5-19 years
                case 'bmi-adult':
                    return { fromMonths: 228, toMonths: 600 }; // 19-50 years (extended for display)
                default:
                    return { fromMonths: 0, toMonths: 71 }; // default
            }
        }

        // Helper function to generate age groups based on range
        function generateAgeGroups(fromMonths, toMonths) {
            const numGroups = 10;
            const ageGroups = [];
            
            for (let i = 0; i < numGroups; i++) {
                const startAge = fromMonths + (i * (toMonths - fromMonths) / numGroups);
                const endAge = fromMonths + ((i + 1) * (toMonths - fromMonths) / numGroups);
                
                // Convert to years for display
                const startYears = (startAge / 12).toFixed(1);
                const endYears = (endAge / 12).toFixed(1);
                
                ageGroups.push(`${startYears}-${endYears}y`);
            }
            
            return ageGroups;
        }

        // Helper function to create realistic age distribution based on WHO standard and classification
        function createRealisticAgeDistributionForWHOStandard(totalCount, whoStandard, classification) {
            if (totalCount === 0) return new Array(10).fill(0);
            
            const numGroups = 10;
            const distribution = new Array(numGroups).fill(0);
            
            // Define age distribution patterns based on WHO standard and nutritional science
            let pattern;
            
            if (whoStandard === 'bmi-adult') {
                // BMI-Adult: Distribute across adult ages (19+ years)
                // Most adults are in middle age groups, fewer in very young adult or very old
                // Age groups: 19-24, 25-30, 31-36, 37-42, 43-48, 49-54, 55-60, 61-66, 67-72, 73+
                pattern = [0.08, 0.12, 0.18, 0.22, 0.20, 0.12, 0.06, 0.02, 0.00, 0.00];
            } else if (whoStandard === 'bmi-for-age') {
                // BMI-for-Age: Distribute across 5-19 years
                // Age groups: 5-7, 8-10, 11-13, 14-16, 17-19, 20-22, 23-25, 26-28, 29+
                // More distributed across age groups, peak in middle childhood/adolescence
                pattern = [0.10, 0.12, 0.14, 0.16, 0.16, 0.14, 0.10, 0.06, 0.02, 0.00];
            } else if (whoStandard === 'weight-for-height') {
                // Weight-for-Height: Distribute across 0-5 years
                // More common in younger ages
                if (classification.includes('Wasted') || classification.includes('Underweight')) {
                    pattern = [0.30, 0.25, 0.20, 0.15, 0.10, 0.00, 0.00, 0.00, 0.00, 0.00];
                } else if (classification.includes('Overweight') || classification.includes('Obese')) {
                    pattern = [0.05, 0.10, 0.15, 0.20, 0.25, 0.25, 0.00, 0.00, 0.00, 0.00];
                } else {
                    pattern = [0.15, 0.15, 0.15, 0.15, 0.15, 0.15, 0.10, 0.00, 0.00, 0.00];
                }
            } else {
                // Weight-for-Age and Height-for-Age: Distribute across 0-5.9 years
                if (classification.includes('Severely') || classification.includes('Stunted')) {
                    pattern = [0.25, 0.20, 0.15, 0.12, 0.10, 0.08, 0.05, 0.03, 0.02, 0.00];
                } else if (classification.includes('Tall') || classification.includes('Overweight') || classification.includes('Obese')) {
                    pattern = [0.02, 0.05, 0.08, 0.12, 0.15, 0.18, 0.20, 0.15, 0.05, 0.00];
                } else {
                    pattern = [0.10, 0.10, 0.10, 0.10, 0.10, 0.10, 0.10, 0.10, 0.10, 0.10];
                }
            }
            
            // Distribute the total count according to the pattern
            let remainingCount = totalCount;
            
            for (let i = 0; i < numGroups - 1; i++) {
                const count = Math.round(totalCount * pattern[i]);
                distribution[i] = Math.min(count, remainingCount);
                remainingCount -= distribution[i];
            }
            
            // Put any remaining count in the last group
            distribution[numGroups - 1] = Math.max(0, remainingCount);
            
            return distribution;
        }

        // Helper function to create realistic age distribution based on nutritional science
        function createRealisticAgeDistribution(totalCount, fromMonths, toMonths, classification) {
            if (totalCount === 0) return new Array(10).fill(0);
            
            const numGroups = 10;
            const distribution = new Array(numGroups).fill(0);
            
            // Define age distribution patterns based on nutritional science
            const patterns = {
                // Weight-for-Age patterns
                'Severely Underweight': [0.25, 0.20, 0.15, 0.12, 0.10, 0.08, 0.05, 0.03, 0.02, 0.00], // More common in younger ages
                'Underweight': [0.20, 0.18, 0.15, 0.12, 0.10, 0.08, 0.07, 0.05, 0.03, 0.02],
                'Normal': [0.15, 0.12, 0.10, 0.10, 0.10, 0.10, 0.10, 0.10, 0.08, 0.05],
                'Overweight': [0.05, 0.08, 0.10, 0.12, 0.12, 0.12, 0.12, 0.12, 0.10, 0.07], // Increases with age
                'Obese': [0.02, 0.03, 0.05, 0.08, 0.10, 0.12, 0.12, 0.15, 0.18, 0.15], // More common in older ages
                
                // Height-for-Age patterns (stunting more common in younger ages)
                'Severely Stunted': [0.40, 0.30, 0.20, 0.07, 0.03, 0.00, 0.00, 0.00, 0.00, 0.00], // Very high in youngest ages
                'Stunted': [0.25, 0.20, 0.15, 0.12, 0.10, 0.08, 0.05, 0.03, 0.02, 0.00], // Higher in younger ages
                'Tall': [0.00, 0.02, 0.05, 0.08, 0.12, 0.15, 0.18, 0.20, 0.15, 0.05], // Increases with age
                
                // Weight-for-Height patterns (wasting more common in younger ages)
                'Severely Wasted': [0.30, 0.25, 0.20, 0.12, 0.08, 0.03, 0.02, 0.00, 0.00, 0.00], // High in youngest ages
                'Wasted': [0.20, 0.18, 0.15, 0.12, 0.10, 0.08, 0.07, 0.06, 0.03, 0.01], // Higher in younger ages
                
                // BMI-for-Age patterns (same as weight-for-height)
                'Overweight': [0.05, 0.08, 0.10, 0.12, 0.12, 0.12, 0.12, 0.12, 0.10, 0.07],
                'Obese': [0.02, 0.03, 0.05, 0.08, 0.10, 0.12, 0.12, 0.15, 0.18, 0.15]
            };
            
            const pattern = patterns[classification] || patterns['Normal'];
            
            // Distribute the total count according to the pattern
            let remainingCount = totalCount;
            
            for (let i = 0; i < numGroups - 1; i++) {
                const count = Math.round(totalCount * pattern[i]);
                distribution[i] = Math.min(count, remainingCount);
                remainingCount -= distribution[i];
            }
            
            // Put any remaining count in the last group
            distribution[numGroups - 1] = Math.max(0, remainingCount);
            
            return distribution;
        }

        // Function to update trends chart using screening date data
        // Community Health Trends Over Time - NEW FEATURE
        let trendsLineChart = null;
        
        // Function to initialize trends chart with default date range
        function initializeTrendsChart() {
            // Set default date range to cover 2024 data (when most screening occurred)
            const fromDate = '2024-01-01';
            const toDate = '2024-12-31';
            
            const fromDateInput = document.getElementById('trends-from-date');
            const toDateInput = document.getElementById('trends-to-date');
            
            if (fromDateInput) {
                fromDateInput.value = fromDate;
                console.log('📅 Set from date to:', fromDate);
            }
            
            if (toDateInput) {
                toDateInput.value = toDate;
                console.log('📅 Set to date to:', toDate);
            }
            
            // Load initial data
            updateTrendsChart();
        }
        
        
        // Function to group dates into time periods
        function groupDatesByPeriod(screeningDates, fromDate, toDate) {
            const from = new Date(fromDate);
            const to = new Date(toDate);
            const diffTime = Math.abs(to - from);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            let periodType, periodFormat, maxPeriods;
            
            // Determine grouping strategy based on date range
            if (diffDays <= 31) {
                periodType = 'daily';
                periodFormat = 'MMM DD';
                maxPeriods = Math.min(diffDays, 15);
            } else if (diffDays <= 365) {
                periodType = 'monthly';
                periodFormat = 'MMM YYYY';
                maxPeriods = 12;
            } else {
                periodType = 'yearly';
                periodFormat = 'YYYY';
                maxPeriods = 10;
            }
            
            // Create time periods
            const periods = [];
            const periodData = {};
            
            if (periodType === 'daily') {
                const periodSize = Math.max(1, Math.ceil(diffDays / maxPeriods));
                for (let i = 0; i < diffDays; i += periodSize) {
                    const periodStart = new Date(from);
                    periodStart.setDate(from.getDate() + i);
                    const periodEnd = new Date(periodStart);
                    periodEnd.setDate(periodStart.getDate() + periodSize - 1);
                    
                    const periodLabel = `${periodStart.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${periodEnd.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}`;
                    periods.push(periodLabel);
                    periodData[periodLabel] = { start: periodStart, end: periodEnd, data: [] };
                }
            } else if (periodType === 'monthly') {
                const current = new Date(from.getFullYear(), from.getMonth(), 1);
                while (current <= to && periods.length < maxPeriods) {
                    const periodLabel = current.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                    periods.push(periodLabel);
                    
                    const periodEnd = new Date(current.getFullYear(), current.getMonth() + 1, 0);
                    periodData[periodLabel] = { start: new Date(current), end: periodEnd, data: [] };
                    
                    current.setMonth(current.getMonth() + 1);
                }
            } else { // yearly
                const current = new Date(from.getFullYear(), 0, 1);
                while (current <= to && periods.length < maxPeriods) {
                    const periodLabel = current.getFullYear().toString();
                    periods.push(periodLabel);
                    
                    const periodEnd = new Date(current.getFullYear(), 11, 31);
                    periodData[periodLabel] = { start: new Date(current), end: periodEnd, data: [] };
                    
                    current.setFullYear(current.getFullYear() + 1);
                }
            }
            
            return { periods, periodData, periodType };
        }
        
        // Function to update trends chart
        async function updateTrendsChart() {
            console.log('📊 Updating Trends Chart...');
            
            try {
                const fromDateInput = document.getElementById('trends-from-date');
                const toDateInput = document.getElementById('trends-to-date');
                const fromDate = fromDateInput ? fromDateInput.value : '';
                const toDate = toDateInput ? toDateInput.value : '';
                
                console.log('📅 Date inputs found:', { fromDateInput: !!fromDateInput, toDateInput: !!toDateInput });
                console.log('📅 Current date values:', { fromDate, toDate });
                
                if (!fromDate || !toDate) {
                    console.log('📅 Date range not selected - fromDate:', fromDate, 'toDate:', toDate);
                    return;
                }
                
                // Get current filters
                const filters = getAllActiveFilters();
                const barangay = filters.finalFilter;
                const whoStandard = filters.whoStandard || 'weight-for-age';
                
                console.log('📊 Trends Chart Filters:', { fromDate, toDate, barangay, whoStandard });
                
                // Show loading state - target the trends chart container directly
                const chartContainer = document.getElementById('trends-chart-container');
                if (chartContainer) {
                    chartContainer.innerHTML = '<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: var(--color-text); font-size: 16px; text-align: center;">Loading trends chart...</div>';
                }
                
                // Fetch screening data with date range
                const apiUrl = `/api/DatabaseAPI.php?action=get_trends_chart_data&from_date=${fromDate}&to_date=${toDate}&barangay=${encodeURIComponent(barangay)}&who_standard=${whoStandard}`;
                console.log('📊 Fetching trends data from:', apiUrl);
                
                const response = await fetch(apiUrl);
                const data = await response.json();
                
                console.log('📊 Trends API response:', data);
                
                if (!data.success) {
                    console.error('Failed to fetch trends data:', data.message);
                    if (chartContainer) {
                        chartContainer.innerHTML = '<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: var(--color-text); font-size: 16px; text-align: center;">Error loading trends data</div>';
                    }
                    return;
                }
                
                const { timeLabels, datasets, totalUsers } = data.data;
                
                console.log('📊 Chart data analysis:', { 
                    timeLabels: timeLabels ? timeLabels.length : 0, 
                    datasets: datasets ? datasets.length : 0, 
                    totalUsers: totalUsers || 0 
                });
                
                if (!timeLabels || timeLabels.length === 0) {
                    console.log('No time labels available');
                    if (chartContainer) {
                        chartContainer.innerHTML = '<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: var(--color-text); font-size: 16px; text-align: center;">No time period data available for selected date range</div>';
                    }
                    return;
                }
                
                if (!datasets || datasets.length === 0) {
                    console.log('No chartable datasets available - showing message with user count');
                    if (chartContainer) {
                        chartContainer.innerHTML = `<div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: var(--color-text); font-size: 16px; text-align: center;">
                            <div style="margin-bottom: 10px;">📊 Found ${totalUsers || 0} users in selected date range</div>
                            <div style="font-size: 14px; opacity: 0.7;">No nutritional classifications available to display for this period</div>
                            <div style="font-size: 12px; opacity: 0.5; margin-top: 10px;">Try selecting a different WHO standard or date range</div>
                        </div>`;
                    }
                    return;
                }
                
                // Create chart - target the specific container for the trends line chart
                const trendsChartContainer = document.getElementById('trends-chart-container');
                if (trendsChartContainer) {
                    trendsChartContainer.innerHTML = '<canvas id="trendsLineChart"></canvas>';
                    const canvas = document.getElementById('trendsLineChart');
                    const ctx = canvas.getContext('2d');
                    
                    // Destroy existing chart
                    if (trendsLineChart) {
                        trendsLineChart.destroy();
                    }
                    
                    console.log('📊 Creating trends line chart with data:', { timeLabels, datasets, totalUsers });
                    
                    // Get theme-aware colors
                    const isLightTheme = document.body.classList.contains('light-theme');
                    const textColor = isLightTheme ? '#000000' : '#FFFFFF';
                    
                    trendsLineChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: timeLabels,
                            datasets: datasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                    labels: {
                                        usePointStyle: true,
                                        padding: 20,
                                        font: {
                                            size: 12
                                        },
                                        color: textColor
                                    }
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false,
                                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                    titleColor: 'white',
                                    bodyColor: 'white',
                                    borderColor: 'rgba(255, 255, 255, 0.2)',
                                    borderWidth: 1,
                                    callbacks: {
                                        title: function(tooltipItems) {
                                            return `Period: ${tooltipItems[0].label}`;
                                        },
                                        label: function(context) {
                                            const dataset = context.dataset;
                                            const value = context.parsed.y;
                                            // Calculate total for this specific time period across all datasets
                                            const timePeriodTotal = context.chart.data.datasets.reduce((sum, ds) => {
                                                return sum + (ds.data[context.dataIndex] || 0);
                                            }, 0);
                                            
                                            if (timePeriodTotal === 0) {
                                                return `${dataset.label}: ${value} (0%)`;
                                            }
                                            
                                            // Calculate exact percentage
                                            const exactPercentage = (value / timePeriodTotal) * 100;
                                            
                                            // For the last dataset in the tooltip, adjust to ensure total is 100%
                                            const tooltipItems = context.chart.tooltip.dataPoints;
                                            const currentDatasetIndex = tooltipItems.findIndex(item => item.datasetIndex === context.datasetIndex);
                                            const isLastItem = currentDatasetIndex === tooltipItems.length - 1;
                                            
                                            if (isLastItem && tooltipItems.length > 1) {
                                                // Calculate what the total would be with current rounding
                                                let runningTotal = 0;
                                                for (let i = 0; i < tooltipItems.length - 1; i++) {
                                                    const itemValue = tooltipItems[i].parsed.y;
                                                    const itemPercentage = Math.round((itemValue / timePeriodTotal) * 100);
                                                    runningTotal += itemPercentage;
                                                }
                                                // Make the last item's percentage = 100 - running total
                                                const adjustedPercentage = 100 - runningTotal;
                                                return `${dataset.label}: ${value} (${adjustedPercentage}%)`;
                                            } else {
                                                const percentage = Math.round(exactPercentage);
                                                return `${dataset.label}: ${value} (${percentage}%)`;
                                            }
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    display: true,
                                    title: {
                                        display: true,
                                        text: 'Time Period',
                                        font: {
                                            size: 12,
                                            weight: 'bold'
                                        },
                                        color: textColor
                                    },
                                    grid: {
                                        color: function(context) {
                                            return document.body.classList.contains('light-theme') 
                                                ? 'rgba(0, 0, 0, 0.2)' 
                                                : 'rgba(255, 255, 255, 0.2)';
                                        },
                                        lineWidth: 2
                                    },
                                    ticks: {
                                        color: textColor,
                                        font: {
                                            size: 11
                                        }
                                    }
                                },
                                y: {
                                    display: true,
                                    title: {
                                        display: true,
                                        text: 'Number of Users',
                                        font: {
                                            size: 12,
                                            weight: 'bold'
                                        },
                                        color: textColor
                                    },
                                    beginAtZero: true,
                                    grid: {
                                        color: function(context) {
                                            return document.body.classList.contains('light-theme') 
                                                ? 'rgba(0, 0, 0, 0.2)' 
                                                : 'rgba(255, 255, 255, 0.2)';
                                        },
                                        lineWidth: 2
                                    },
                                    ticks: {
                                        color: textColor,
                                        font: {
                                            size: 11
                                        }
                                    }
                                }
                            },
                            interaction: {
                                mode: 'nearest',
                                axis: 'x',
                                intersect: false
                            }
                        }
                    });
                }
                
                console.log('✅ Trends chart updated successfully');
                
            } catch (error) {
                console.error('❌ Error updating trends chart:', error);
                const trendsChartContainer = document.getElementById('trends-chart-container');
                if (trendsChartContainer) {
                    trendsChartContainer.innerHTML = '<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: var(--color-text); font-size: 16px; text-align: center;">Error loading trends chart</div>';
                }
            }
        }

        // Function to update age classification chart using donut chart data
        // Age Classification Line Chart - NEW FEATURE
        let ageClassificationLineChart = null;
        
        async function updateAgeClassificationChart(barangay = '') {
            console.log('📊 Updating Age Classification Line Chart...');
            
            try {
                // Check if canvas exists, if not, restore it
                let canvas = document.getElementById('ageClassificationLineChart');
                if (!canvas) {
                    console.log('Canvas not found, restoring...');
                    const chartContainer = document.querySelector('.age-classification-chart-container');
                    if (chartContainer) {
                        chartContainer.innerHTML = '<canvas id="ageClassificationLineChart"></canvas>';
                        canvas = document.getElementById('ageClassificationLineChart');
                    }
                    if (!canvas) {
                        console.error('Age classification line chart canvas not found and could not be restored');
                        return;
                    }
                }
                
                // Show loading state
                const chartContainer = document.querySelector('.age-classification-chart-container');
                if (chartContainer) {
                    chartContainer.innerHTML = '<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: var(--color-text); font-size: 16px; text-align: center;">Loading age classification chart...</div>';
                }

                // Get current WHO standard and barangay values
                const whoStandard = document.getElementById('whoStandardSelect')?.value || 'weight-for-age';
                const barangayValue = barangay || '';

                console.log('📊 Using WHO Standard:', whoStandard);
                console.log('📊 Using Barangay:', barangayValue);

                // Fetch data from the new API endpoint
                const apiUrl = `/api/DatabaseAPI.php?action=get_age_classification_line_chart&who_standard=${whoStandard}&barangay=${encodeURIComponent(barangayValue)}`;
                console.log('📊 Fetching age classification data from:', apiUrl);
                
                const response = await fetch(apiUrl);
                const data = await response.json();
                
                console.log('📊 Age classification API response:', data);
                
                if (!data.success) {
                    console.error('Failed to fetch age classification line chart data:', data.message);
                    return;
                }
                
                const { ageLabels, datasets, totalUsers, whoStandard: returnedStandard } = data.data;
                
                if (!ageLabels || ageLabels.length === 0 || !datasets || datasets.length === 0) {
                    console.log('No age classification line chart data available');
                    // Show a message in the chart container
                    if (chartContainer) {
                        chartContainer.innerHTML = '<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: var(--color-text); font-size: 16px; text-align: center;">No data available for age classification chart</div>';
                    }
                    return;
                }

                console.log('📊 Age Classification Line Chart Data:', { ageLabels, datasets, totalUsers, totalPopulation: data.totalPopulation });

                // Destroy existing chart
                if (ageClassificationLineChart) {
                    ageClassificationLineChart.destroy();
                }
                
                // Restore canvas element
                if (chartContainer) {
                    chartContainer.innerHTML = '<canvas id="ageClassificationLineChart"></canvas>';
                    const newCanvas = document.getElementById('ageClassificationLineChart');
                    const newCtx = newCanvas.getContext('2d');
                    
                    console.log('📊 Creating Chart.js line chart with data:', { ageLabels, datasets, totalUsers });
                    
                    // Get theme-aware colors for age classification chart
                    const isLightTheme = document.body.classList.contains('light-theme');
                    const textColor = isLightTheme ? '#000000' : '#FFFFFF';
                    
                    ageClassificationLineChart = new Chart(newCtx, {
                    type: 'line',
                    data: {
                        labels: ageLabels,
                        datasets: datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    padding: 20,
                                    font: {
                                        size: 12
                                    },
                                    color: textColor
                                }
                            },
                            title: {
                                display: false
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                                position: 'nearest',
                                backgroundColor: 'rgba(0, 0, 0, 0.9)',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                borderColor: 'rgba(255, 255, 255, 0.1)',
                                borderWidth: 1,
                                cornerRadius: 8,
                                displayColors: true,
                                titleFont: {
                                    size: 14,
                                    weight: 'bold'
                                },
                                bodyFont: {
                                    size: 13
                                },
                                padding: 12,
                                callbacks: {
                                    title: function(context) {
                                        return `Age Group: ${context[0].label}`;
                                    },
                                    label: function(context) {
                                        const label = context.dataset.label || '';
                                        const value = context.parsed.y;
                                        return `${label}: ${value} user${value !== 1 ? 's' : ''}`;
                                    },
                                    afterBody: function(tooltipItems) {
                                        const total = tooltipItems.reduce((sum, item) => sum + item.parsed.y, 0);
                                        return `Total: ${total} user${total !== 1 ? 's' : ''}`;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                display: true,
                                title: {
                                    display: true,
                                    text: 'Age Groups',
                                    font: {
                                        size: 14,
                                        weight: 'bold'
                                    },
                                    color: textColor
                                },
                                grid: {
                                    color: function(context) {
                                        return document.body.classList.contains('light-theme') 
                                            ? 'rgba(0, 0, 0, 0.2)' 
                                            : 'rgba(255, 255, 255, 0.2)';
                                    },
                                    lineWidth: 2
                                },
                                ticks: {
                                    maxRotation: 45,
                                    minRotation: 45,
                                    font: {
                                        size: 11
                                    },
                                    color: textColor
                                }
                            },
                            y: {
                                display: true,
                                title: {
                                    display: true,
                                    text: 'Number of Users',
                                    font: {
                                        size: 14,
                                        weight: 'bold'
                                    },
                                    color: textColor
                                },
                                beginAtZero: true,
                                suggestedMax: data.totalPopulation || totalUsers,
                                grid: {
                                    color: function(context) {
                                        return document.body.classList.contains('light-theme') 
                                            ? 'rgba(0, 0, 0, 0.2)' 
                                            : 'rgba(255, 255, 255, 0.2)';
                                    },
                                    lineWidth: 2
                                },
                                ticks: {
                                    stepSize: 1,
                                    font: {
                                        size: 11
                                    },
                                    color: textColor
                                }
                            }
                        },
                        interaction: {
                            mode: 'nearest',
                            axis: 'x',
                            intersect: false
                        },
                        animation: {
                            duration: 1000,
                            easing: 'easeInOutQuart'
                        }
                    }
                    });
                    
                    console.log('✅ Age Classification Line Chart created successfully');
                }

            } catch (error) {
                console.error('❌ Error updating age classification line chart:', error);
                }
            }
        function updateWHOChartDescription(whoStandard) {
            const descriptions = {
                'weight-for-age': 'Distribution of children by Weight-for-Age classification. Shows nutritional status based on weight relative to age (0-71 months).',
                'height-for-age': 'Distribution of children by Height-for-Age classification. Shows stunting status based on height relative to age (0-71 months).',
                'weight-for-height': 'Distribution of children by Weight-for-Height classification. Shows wasting status based on weight relative to height (0-60 months).',
                'bmi-for-age': 'Distribution of children by BMI-for-Age classification. Shows nutritional status based on BMI relative to age (5-19 years).',
                'bmi-adult': 'Distribution of adults by BMI classification. Shows nutritional status based on BMI for adults (≥19 years).'
            };
            
            const descriptionElement = document.getElementById('who-chart-description');
            if (descriptionElement) {
                descriptionElement.textContent = descriptions[whoStandard] || descriptions['weight-for-age'];
            }
        }

        // Function to update WHO classification chart
        function updateWHOClassificationChart(data) {
            console.log('WHO Chart Update - Data received:', data);
            console.log('Data type:', typeof data);
            console.log('Data classifications:', data?.classifications);
            console.log('Data total:', data?.total);
            
            try {
                // Get chart elements
                const chartBg = document.getElementById('risk-chart-bg');
                const centerText = document.getElementById('risk-center-text');
                const segments = document.getElementById('risk-segments');
                
                console.log('Chart elements found:');
                console.log('  - chartBg (risk-chart-bg):', chartBg);
                console.log('  - centerText (risk-center-text):', centerText);
                console.log('  - segments (risk-segments):', segments);
                
                if (!chartBg || !centerText || !segments) {
                    console.error('Chart elements not found');
                    return;
                }
                
                // Clear previous data
                segments.innerHTML = '';
                chartBg.style.opacity = '0.8';

                // Check if we have valid data
                if (!data || !data.classifications || data.total === 0) {
                    // Check if we have "No Data" classifications (users outside age range)
                    const hasNoData = data && data.classifications && data.classifications['No Data'] > 0;
                    
                    if (hasNoData) {
                        const noDataCount = data.classifications['No Data'];
                        centerText.innerHTML = `<div style="font-size: 14px; font-weight: 600; color: #666;">${noDataCount} Users</div><div style="font-size: 12px; color: #999; margin-top: 4px;">Outside Age Range</div>`;
                        centerText.style.color = '#666';
                        chartBg.style.background = 'conic-gradient(#e0e0e0 0% 100%)';
                        chartBg.style.opacity = '0.4';
                        segments.innerHTML = `
                            <div style="text-align: center; color: #666; font-size: 12px; line-height: 1.4;">
                                <div style="font-weight: 600; margin-bottom: 4px;">Age Range Mismatch</div>
                                <div style="opacity: 0.8;">Users are outside the required age range for this WHO standard</div>
                            </div>
                        `;
                    } else {
                        centerText.textContent = 'No Data';
                        centerText.style.color = '#999';
                        chartBg.style.background = 'conic-gradient(#e0e0e0 0% 100%)';
                        chartBg.style.opacity = '0.3';
                        segments.innerHTML = '<div style="text-align: center; color: #999; font-style: italic;">No data available</div>';
                    }
                    return;
                }
                
                const classifications = data.classifications;
                const totalUsers = data.total;
                
                console.log('Processing classifications:', classifications);
                console.log('Classifications keys:', Object.keys(classifications));
                console.log('Classifications values:', Object.values(classifications));
                console.log('Total users:', totalUsers);
                
                // Check if classifications object has any non-zero values
                const hasData = Object.values(classifications).some(count => count > 0);
                console.log('Has data (non-zero counts):', hasData);
                
                // Define colors for each classification (matching bar graph colors)
                const colors = {
                    'Severely Underweight': '#E91E63',
                    'Underweight': '#FFC107',
                    'Normal': '#4CAF50',
                    'Overweight': '#FF9800',
                    'Obese': '#F44336',
                    'Severely Wasted': '#D32F2F',
                    'Wasted': '#FF5722',
                    'Severely Stunted': '#673AB7',
                    'Stunted': '#9C27B0',
                    'Tall': '#00BCD4',
                };
                
                // Create segments array with only classifications that have data
                const chartSegments = [];
                let currentAngle = 0;
                
                Object.keys(classifications).forEach(classification => {
                    const count = classifications[classification];
                    if (count > 0 && classification !== 'No Data') {
                        const percentage = (count / totalUsers) * 100;
                        chartSegments.push({
                            label: classification,
                            count: count,
                            percentage: percentage,
                            color: colors[classification] || '#999',
                            startAngle: currentAngle,
                            endAngle: currentAngle + percentage
                        });
                        currentAngle += percentage;
                    }
                });
                
                console.log('Chart segments:', chartSegments);
                console.log('Chart segments length:', chartSegments.length);
                console.log('Chart segments details:', chartSegments.map(s => ({ label: s.label, count: s.count, percentage: s.percentage })));
                
                // Update center text
                centerText.textContent = totalUsers;
                centerText.style.color = '#333';
                
                // Create conic gradient
                if (chartSegments.length > 0) {
                    const gradientParts = chartSegments.map(segment => 
                        `${segment.color} ${segment.startAngle}% ${segment.endAngle}%`
                    );
                    const gradientString = `conic-gradient(${gradientParts.join(', ')})`;
                    console.log('Creating gradient:', gradientString);
                    chartBg.style.background = gradientString;
                    chartBg.style.opacity = '1';
                    console.log('Chart background set to:', chartBg.style.background);
                    
                    // Check computed styles
                    const computedStyle = window.getComputedStyle(chartBg);
                    console.log('Computed background:', computedStyle.background);
                    console.log('Computed opacity:', computedStyle.opacity);
                    console.log('Element dimensions:', {
                        width: chartBg.offsetWidth,
                        height: chartBg.offsetHeight,
                        display: computedStyle.display,
                        visibility: computedStyle.visibility
                    });
                } else {
                    console.log('No chart segments, showing no data state');
                    chartBg.style.background = 'conic-gradient(#e0e0e0 0% 100%)';
                    chartBg.style.opacity = '0.3';
                }
                
                // Create simple text boxes - no complex UI
                chartSegments.forEach(segment => {
                    const textBox = document.createElement('div');
                    textBox.className = 'simple-text-box';
                    textBox.innerHTML = `
                        <div class="text-box-dot" style="background-color: ${segment.color};"></div>
                        <div class="text-box-label">${segment.label}</div>
                        <div class="text-box-count">${segment.count} (${segment.percentage.toFixed(1)}%)</div>
                    `;
                    segments.appendChild(textBox);
                });
                
                console.log('WHO chart updated successfully');
                
                // Note: Severely cases will be updated by the manual WHO chart update
                // which has access to the full WHO data structure
                
            } catch (error) {
                console.error('Error updating WHO classification chart:', error);
                
                // Show error state
                const centerText = document.getElementById('risk-center-text');
                const chartBg = document.getElementById('risk-chart-bg');
                const segments = document.getElementById('risk-segments');
                
                if (centerText) centerText.textContent = 'Error';
                if (chartBg) {
                    chartBg.style.background = 'conic-gradient(#ffebee 0% 100%)';
                    chartBg.style.opacity = '0.5';
                }
                if (segments) {
                    segments.innerHTML = '<div style="text-align: center; color: #f44336;">Error loading data</div>';
                }
            }
        }

        // Function to handle WHO standard dropdown change
        async function handleWHOStandardChange() {
            // Prevent multiple simultaneous calls
            if (window.whoClassificationLoading) {
                console.log('WHO classification already loading, skipping...');
                return;
            }
            
            window.whoClassificationLoading = true;
            console.log('🔄 Starting WHO standard change handler...');
            
            const select = document.getElementById('whoStandardSelect');
            const selectedStandard = select ? select.value : 'weight-for-age';
            
            console.log('📊 WHO Standard selected:', selectedStandard);
            console.log('📊 Dropdown element found:', !!select);
            console.log('📊 Dropdown value:', select ? select.value : 'N/A');
            
            try {
                // Get current active filters
                const filters = getAllActiveFilters();
                const barangay = filters.finalFilter;
                
                console.log('📡 Fetching WHO data with active filters...');
                console.log('📊 Active filters:', filters);
                
                // Fetch WHO classification data
                const response = await fetchWHOClassificationData(selectedStandard, barangay);
                console.log('📊 Data received for chart update:', response);
                
                // Update the chart with the correct data structure
                console.log('🎨 Updating chart...');
                updateWHOClassificationChart(response);
                updateWHOChartDescription(selectedStandard);
                
                // Also update the age classification chart with the new WHO standard
                console.log('🎨 Updating age classification chart...');
                await updateAgeClassificationChart(barangay);
                
                // Update trends chart with new WHO standard
                console.log('🎨 Updating trends chart...');
                await updateTrendsChart();
                
                // Update severe cases list with new WHO standard
                console.log('🎨 Updating severe cases list...');
                console.log('📊 WHO Standard before severe cases call:', selectedStandard);
                console.log('📊 Dropdown value before severe cases call:', select ? select.value : 'N/A');
                await updateSevereCasesList(barangay);
                
                console.log('✅ Chart update completed');
                
            } catch (error) {
                console.error('❌ Error updating WHO classification chart:', error);
            } finally {
                window.whoClassificationLoading = false;
                console.log('🏁 WHO classification loading completed');
            }
        }

        // Function to fetch WHO classification data
        async function fetchWHOClassificationData(whoStandard, barangay) {
            try {
                console.log('Fetching WHO data for:', { whoStandard, barangay });
                
                // OPTIMIZED: Use bulk API and extract specific standard with age filtering
                const url = `/api/DatabaseAPI.php?action=get_all_who_classifications_bulk&barangay=${barangay}&who_standard=${whoStandard}`;
                console.log('API URL (bulk):', url);
                
                const response = await fetch(url);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                console.log('Bulk API Response:', result);
                
                if (!result.success) {
                    throw new Error(result.error || 'Failed to fetch WHO classification data');
                }
                
                // OPTIMIZED: Extract specific WHO standard from bulk response
                const data = result.data || {};
                
                // Map WHO standard names to bulk API keys
                const standardMapping = {
                    'weight-for-age': 'weight_for_age',
                    'height-for-age': 'height_for_age', 
                    'weight-for-height': 'weight_for_height',
                    'bmi-for-age': 'bmi_for_age',
                    'bmi-adult': 'bmi_adult'
                };
                
                const apiKey = standardMapping[whoStandard] || whoStandard;
                const classifications = data[apiKey] || {};
                
                // Calculate total for this specific WHO standard (sum of all valid classifications)
                const total = Object.entries(classifications).reduce((sum, [classification, count]) => {
                    return classification === 'No Data' ? sum : sum + count;
                }, 0);
                
                console.log(`Mapping ${whoStandard} to ${apiKey}:`, classifications);
                console.log(`Classification values:`, Object.values(classifications));
                console.log(`Calculated total:`, total);
                console.log(`Original total_users:`, result.total_users);
                
                console.log(`Extracted ${whoStandard} data:`, { classifications, total });
                
                // Convert to expected format for donut chart
                const processedData = {
                    success: true,
                    data: {
                        classifications: classifications,
                        total: total
                    }
                };
                
                console.log('Processed data for donut chart:', processedData);
                
                return {
                    classifications: classifications,
                    total: total
                };
                
            } catch (error) {
                console.error('Error fetching WHO classification data:', error);
                return {
                    classifications: {}, 
                    total: 0,
                    who_standard: whoStandard,
                    error: error.message
                };
            }
        }


        // Function to fetch barangay distribution data using bulk API (no time frame)
        async function fetchBarangayDistributionData(barangay = '') {
            try {
                console.log('Fetching barangay distribution data for:', { barangay });
                
                const url = `/api/DatabaseAPI.php?action=get_barangay_distribution_bulk&barangay=${barangay}`;
                console.log('Barangay API URL:', url);
                
                const response = await fetch(url);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                console.log('Barangay API Response:', result);
                
                if (!result.success) {
                    throw new Error(result.message || 'Failed to fetch barangay distribution data');
                }
                
                return result.data;
                
            } catch (error) {
                console.error('Error fetching barangay distribution data:', error);
                return {
                    barangay_distribution: {},
                    municipality_distribution: {},
                    total_users: 0,
                    error: error.message
                };
            }
        }

        // Function to fetch gender distribution data using bulk API (no time frame)
        async function fetchGenderDistributionData(barangay = '') {
            try {
                console.log('Fetching gender distribution data for:', { barangay });
                
                const url = `/api/DatabaseAPI.php?action=get_gender_distribution_bulk&barangay=${barangay}`;
                console.log('Gender API URL:', url);
                
                const response = await fetch(url);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                console.log('Gender API Response:', result);
                
                if (!result.success) {
                    throw new Error(result.message || 'Failed to fetch gender distribution data');
                }
                
                return result.data;
                
            } catch (error) {
                console.error('Error fetching gender distribution data:', error);
                return {
                    gender_distribution: {},
                    total_users: 0,
                    error: error.message
                };
            }
        }

        // Function to update gender distribution display
        function updateGenderDistributionDisplay(data) {
            console.log('Updating gender distribution display:', data);
            
            const genderContainer = document.getElementById('gender-distribution-responses');
            if (genderContainer) {
                const genderData = data.gender_distribution || {};
                const totalUsers = data.total_users || 0;
                
                // Clear existing content
                genderContainer.innerHTML = '';
                
                // Add headers
                const headers = document.createElement('div');
                headers.className = 'column-headers';
                headers.innerHTML = `
                    <span class="header-label">Gender</span>
                    <span class="header-count">Count</span>
                    <span class="header-percent">Percentage</span>
                `;
                genderContainer.appendChild(headers);
                
                // Add data container
                const dataContainer = document.createElement('div');
                dataContainer.className = 'response-data-container';
                
                if (Object.keys(genderData).length > 0) {
                    Object.entries(genderData).forEach(([gender, count]) => {
                        const percentage = totalUsers > 0 ? ((count / totalUsers) * 100).toFixed(1) : 0;
                        
                        const item = document.createElement('div');
                        item.className = 'response-answer-item';
                        item.innerHTML = `
                            <span class="answer-label">${gender}</span>
                            <span class="answer-count">${count}</span>
                            <span class="answer-percentage">${percentage}%</span>
                        `;
                        dataContainer.appendChild(item);
                    });
                } else {
                    const noData = document.createElement('div');
                    noData.className = 'no-data-message';
                    noData.textContent = 'No gender data available';
                    dataContainer.appendChild(noData);
                }
                
                genderContainer.appendChild(dataContainer);
            }
        }

        // Function to update barangay and municipality distribution displays
        function updateBarangayDistributionDisplay(data) {
            console.log('Updating barangay distribution display:', data);
            
            // Update barangay distribution
            const barangayContainer = document.getElementById('barangay-distribution-responses');
            if (barangayContainer) {
                const barangayData = data.barangay_distribution || {};
                const totalUsers = data.total_users || 0;
                
                // Clear existing content
                barangayContainer.innerHTML = '';
                
                // Add headers
                const headers = document.createElement('div');
                headers.className = 'column-headers';
                headers.innerHTML = `
                    <span class="header-label">Barangay</span>
                    <span class="header-count">Count</span>
                    <span class="header-percent">Percentage</span>
                `;
                barangayContainer.appendChild(headers);
                
                // Add data container
                const dataContainer = document.createElement('div');
                dataContainer.className = 'response-data-container';
                
                if (Object.keys(barangayData).length > 0) {
                    Object.entries(barangayData).forEach(([barangay, count]) => {
                        const percentage = totalUsers > 0 ? ((count / totalUsers) * 100).toFixed(1) : 0;
                        
                        const item = document.createElement('div');
                        item.className = 'response-answer-item';
                        item.innerHTML = `
                            <span class="answer-label">${barangay}</span>
                            <span class="answer-count">${count}</span>
                            <span class="answer-percentage">${percentage}%</span>
                        `;
                        dataContainer.appendChild(item);
                    });
                } else {
                    const noData = document.createElement('div');
                    noData.className = 'no-data-message';
                    noData.textContent = 'No barangay data available';
                    dataContainer.appendChild(noData);
                }
                
                barangayContainer.appendChild(dataContainer);
            }
            
            // Update municipality distribution
            const municipalityContainer = document.getElementById('municipality-distribution-responses');
            if (municipalityContainer) {
                const municipalityData = data.municipality_distribution || {};
                const totalUsers = data.total_users || 0;
                
                // Clear existing content
                municipalityContainer.innerHTML = '';
                
                // Add headers
                const headers = document.createElement('div');
                headers.className = 'column-headers';
                headers.innerHTML = `
                    <span class="header-label">Municipality</span>
                    <span class="header-count">Count</span>
                    <span class="header-percent">Percentage</span>
                `;
                municipalityContainer.appendChild(headers);
                
                // Add data container
                const dataContainer = document.createElement('div');
                dataContainer.className = 'response-data-container';
                
                if (Object.keys(municipalityData).length > 0) {
                    Object.entries(municipalityData).forEach(([municipality, count]) => {
                        const percentage = totalUsers > 0 ? ((count / totalUsers) * 100).toFixed(1) : 0;
                        
                        const item = document.createElement('div');
                        item.className = 'response-answer-item';
                        item.innerHTML = `
                            <span class="answer-label">${municipality}</span>
                            <span class="answer-count">${count}</span>
                            <span class="answer-percentage">${percentage}%</span>
                        `;
                        dataContainer.appendChild(item);
                    });
                } else {
                    const noData = document.createElement('div');
                    noData.className = 'no-data-message';
                    noData.textContent = 'No municipality data available';
                    dataContainer.appendChild(noData);
                }
                
                municipalityContainer.appendChild(dataContainer);
            }
        }

        // Function to update Nutritional Status Overview Card
        function updateNutritionalStatusCard(whzData, muacData) {
            
            try {
                // Update WHZ Categories
                if (whzData && whzData.length > 0) {
                    whzData.forEach(item => {
                        // Map the labels to the new IDs
                        let elementId = '';
                        if (item.label === 'Severe Acute Malnutrition') elementId = 'whz-sam-count';
                        else if (item.label === 'Moderate Acute Malnutrition') elementId = 'whz-mam-count';
                        else if (item.label === 'Normal Growth') elementId = 'whz-normal-count';
                        else if (item.label === 'Overweight') elementId = 'whz-overweight-count';
                        
                        const element = document.getElementById(elementId);
                        if (element) {
                            element.textContent = item.value || 0;
                        }
                    });
                }
                
                // Update MUAC Categories
                if (muacData && muacData.length > 0) {
                    muacData.forEach(item => {
                        // Map the labels to the new IDs
                        let elementId = '';
                        if (item.label === 'Severe Acute Malnutrition') elementId = 'muac-sam-count';
                        else if (item.label === 'Moderate Acute Malnutrition') elementId = 'muac-mam-count';
                        else if (item.label === 'Normal Growth') elementId = 'muac-normal-count';
                        else if (item.label === 'Overweight') elementId = 'muac-overweight-count';
                        
                        const element = document.getElementById(elementId);
                        if (element) {
                            element.textContent = item.value || 0;
                        }
                    });
                }
                
            } catch (error) {
                console.error('Error updating nutritional status card:', error);
            }
        }
        
        // Function to update Nutritional Summary
        function updateNutritionalSummary(whzData, muacData) {
            try {
                // Calculate Total At Risk (SAM + MAM from both WHZ and MUAC)
                let totalAtRisk = 0;
                let totalCases = 0;
                
                if (whzData) {
                    whzData.forEach(item => {
                        if (item.label === 'Severe Acute Malnutrition' || item.label === 'Moderate Acute Malnutrition') {
                            totalAtRisk += item.value || 0;
                        }
                        totalCases += item.value || 0;
                    });
                }
                
                if (muacData) {
                    muacData.forEach(item => {
                        if (item.label === 'SAM' || item.label === 'MAM') {
                            totalAtRisk += item.value || 0;
                        }
                        totalCases += item.value || 0;
                    });
                }
                
                // Update the summary elements
                const atRiskElement = document.getElementById('total-at-risk');
                const totalCasesElement = document.getElementById('total-cases');
                const atRiskPercentElement = document.getElementById('at-risk-percent');
                
                if (atRiskElement) atRiskElement.textContent = totalAtRisk;
                if (totalCasesElement) totalCasesElement.textContent = totalCases;
                if (atRiskPercentElement && totalCases > 0) {
                    const percentage = Math.round((totalAtRisk / totalCases) * 100);
                    atRiskPercentElement.textContent = percentage + '%';
                }
                
            } catch (error) {
                console.error('Error updating nutritional summary:', error);
                }
            }
        
        // Enhanced date formatting with more options
        function formatDate(dateString, format = 'relative') {
            const date = new Date(dateString);
            
            if (format === 'relative') {
                return formatTimeAgo(dateString);
            } else if (format === 'short') {
                return date.toLocaleDateString('en-US', { 
                    month: 'short', 
                    day: 'numeric', 
                    year: 'numeric' 
                });
            } else if (format === 'long') {
                return date.toLocaleDateString('en-US', { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });
            }
            
            return date.toLocaleDateString();
        }

        // Set up age range control event listeners
        function setupAgeRangeControls() {
            // Apply button
            const applyBtn = document.getElementById('applyAgeRange');
            if (applyBtn) {
                applyBtn.addEventListener('click', applyAgeRange);
            }
            
            // Reset button
            const resetBtn = document.getElementById('resetAgeRange');
            if (resetBtn) {
                resetBtn.addEventListener('click', resetAgeRange);
            }
            
            // Update display when inputs change
            const inputs = ['ageFromMonths', 'ageFromUnit', 'ageToMonths', 'ageToUnit'];
            inputs.forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                }
            });
            
            
            // Add window resize listener to resize chart
            window.addEventListener('resize', () => {
                if (ageClassificationChartInstance) {
                    const canvas = document.getElementById('ageClassificationChart');
                    if (canvas) {
                        const container = canvas.parentElement;
                        const containerRect = container.getBoundingClientRect();
                        const availableWidth = Math.max(300, containerRect.width - 20);
                        const availableHeight = Math.max(250, containerRect.height - 20);
                        canvas.width = availableWidth;
                        canvas.height = availableHeight;
                        ageClassificationChartInstance.resize();
                    }
                }
            });
        }

        // Initialize WHO dropdown on page load
        document.addEventListener('DOMContentLoaded', async function() {
            console.log('DOM Content Loaded - Initializing WHO dropdown');
            
            // Initialize dropdown selections
            setupBarangaySelection();
            setupMunicipalitySelection();
            
            // Initialize trends chart
            initializeTrendsChart();
            
            // Initialize severe cases list
            updateSevereCasesList('');
            
        // Debug: Check initial metric values on page load
        setTimeout(() => {
            // Debug card visibility
            console.log('🔍 Card Debug - Checking card elements:');
            const cardContainer = document.querySelector('.card-container');
            const cards = document.querySelectorAll('.card');
            console.log('Card Container:', cardContainer);
            console.log('Number of cards found:', cards.length);
            cards.forEach((card, index) => {
                console.log(`Card ${index + 1}:`, card);
                console.log(`Card ${index + 1} display:`, window.getComputedStyle(card).display);
                console.log(`Card ${index + 1} visibility:`, window.getComputedStyle(card).visibility);
                console.log(`Card ${index + 1} opacity:`, window.getComputedStyle(card).opacity);
            });
            
            const totalScreened = document.getElementById('community-total-screened');
            const highRisk = document.getElementById('community-high-risk');
            const samCases = document.getElementById('community-sam-cases');
            const criticalMuac = document.getElementById('community-critical-muac');
                
                console.log('🔍 Initial Dashboard Metrics (Page Load):');
                console.log('  - Total Screened:', totalScreened ? totalScreened.textContent : 'NOT FOUND');
                console.log('  - High Risk (Severely Underweight):', highRisk ? highRisk.textContent : 'NOT FOUND');
                console.log('  - SAM Cases (Severely Stunted):', samCases ? samCases.textContent : 'NOT FOUND');
                console.log('  - Critical MUAC (Severely Wasted):', criticalMuac ? criticalMuac.textContent : 'NOT FOUND');
            }, 1000);
            
            // Set up WHO dropdown event listener
            const whoSelect = document.getElementById('whoStandardSelect');
            if (whoSelect) {
                console.log('WHO dropdown found, setting up event listener');
                whoSelect.value = 'weight-for-age';
                
                // Add the event listener
                whoSelect.addEventListener('change', async function() {
                    console.log('WHO dropdown changed to:', this.value);
                    await handleWHOStandardChange();
                });
                
                // Load initial data
                updateWHOChartDescription('weight-for-age');
                await handleWHOStandardChange();
                
                // Load initial trends chart with all classifications
                console.log('📊 Loading initial trends chart...');
                console.log('📊 updateTrendsChart function exists:', typeof updateTrendsChart);
                try {
                    await updateTrendsChart('');
                    console.log('📊 Trends chart loaded successfully');
                } catch (error) {
                    console.error('❌ Error loading trends chart:', error);
                }
                
                
                // Load initial age classification chart with default range
                console.log('📊 Loading initial age classification chart...');
                await updateAgeClassificationChart('');
                
                
                // Load initial dashboard metrics and geographic distribution
                console.log('🔄 Loading initial dashboard metrics and geographic distribution...');
                await updateCommunityMetrics('');
                
                // Load barangay distribution data using new bulk API
                console.log('🔄 Loading barangay distribution data...');
                const barangayData = await fetchBarangayDistributionData('');
                updateBarangayDistributionDisplay(barangayData);
                
                // Load gender distribution data using new bulk API
                console.log('🔄 Loading gender distribution data...');
                const genderData = await fetchGenderDistributionData('');
                updateGenderDistributionDisplay(genderData);
                
                // Load geographic distribution immediately with pre-loaded data
                console.log('🌍 Loading geographic distribution with pre-loaded data...');
                if (geographicDistributionData && geographicDistributionData.length > 0) {
                    console.log('🌍 Updating geographic display with pre-loaded data:', geographicDistributionData);
                    updateGeographicChartDisplay(geographicDistributionData);
                } else {
                    console.log('🌍 No pre-loaded geographic data available');
                    updateGeographicChartDisplay([]);
                }
                
                window.whoDataLoaded = true;
            } else {
                console.log('WHO dropdown not found');
            }
        });

        // Theme toggle function
        function newToggleTheme() {
            const body = document.body;
            const icon = document.querySelector('.new-theme-icon');
            
            if (body.classList.contains('dark-theme')) {
                body.classList.remove('dark-theme');
                body.classList.add('light-theme');
                if (icon) icon.textContent = '☀️';
                localStorage.setItem('theme', 'light');
                console.log('Switched to light theme');
            } else {
                body.classList.remove('light-theme');
                body.classList.add('dark-theme');
                if (icon) icon.textContent = '🌙';
                localStorage.setItem('theme', 'dark');
                console.log('Switched to dark theme');
            }
            
            // Refresh charts to apply new theme
            setTimeout(() => {
                // Refresh trends chart if it exists
                if (typeof updateTrendsChart === 'function') {
                    updateTrendsChart();
                }
                
                // Refresh age classification chart if it exists
                if (typeof updateAgeClassificationChart === 'function') {
                    const currentBarangay = getCurrentBarangay();
                    updateAgeClassificationChart(currentBarangay);
                }
                
                // Refresh severe cases list if it exists
                if (typeof updateSevereCasesList === 'function') {
                    const currentBarangay = getCurrentBarangay();
                    updateSevereCasesList(currentBarangay);
                }
                
                console.log('Charts refreshed for new theme');
            }, 100);
        }

        // Initialize theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            const body = document.body;
            const icon = document.querySelector('.new-theme-icon');
            
            if (savedTheme === 'light') {
                body.classList.remove('dark-theme');
                body.classList.add('light-theme');
                if (icon) icon.textContent = '☀️';
            } else {
                body.classList.remove('light-theme');
                body.classList.add('dark-theme');
                if (icon) icon.textContent = '🌙';
            }

            // Add theme toggle event listener
            const themeToggleBtn = document.getElementById('new-theme-toggle');
            if (themeToggleBtn) {
                themeToggleBtn.addEventListener('click', newToggleTheme);
            }
        });

        // Function to update nutritional statistics display
        function updateNutritionalStatisticsDisplay(statistics) {
            console.log('🔄 Updating Nutritional Statistics Display:', statistics);
            
            if (!statistics || !statistics.statistics) {
                console.log('❌ No statistics data available');
                return;
            }
            
            const stats = statistics.statistics;
            const totalUsers = stats.total_users || 0;
            
            
            // Update Municipality Distribution
            updateStatisticDisplay('municipality-distribution-responses', stats.municipality_distribution, totalUsers);
            
            // Update Barangay Distribution
            updateStatisticDisplay('barangay-distribution-responses', stats.barangay_distribution, totalUsers);
        }
        
        // Helper function to update individual statistic displays
        function updateStatisticDisplay(containerId, data, totalUsers) {
            const container = document.getElementById(containerId);
            if (!container) {
                console.log(`❌ Container not found: ${containerId}`);
                return;
            }
            
            const dataContainer = container.querySelector('.response-data-container');
            if (!dataContainer) {
                console.log(`❌ Data container not found in: ${containerId}`);
                return;
            }
            
            if (!data || Object.keys(data).length === 0) {
                dataContainer.innerHTML = '<div class="no-data-message">No data available for selected time frame</div>';
                return;
            }
            
            let html = '';
            for (const [key, count] of Object.entries(data)) {
                const percentage = totalUsers > 0 ? round((count / totalUsers) * 100, 1) : 0;
                html += `
                    <div class="response-answer-item">
                        <span class="answer-label">${key}</span>
                        <span class="answer-count">${count}</span>
                        <span class="answer-percentage">${percentage}%</span>
                    </div>
                `;
            }
            
            dataContainer.innerHTML = html;
        }
        
        // Helper function to round numbers
        function round(number, decimals) {
            return Math.round(number * Math.pow(10, decimals)) / Math.pow(10, decimals);
        }

        // ==============================================
        // BARANGAY DROPDOWN FUNCTIONALITY - FIXED  
        // Minimal integration with existing code
        // ==============================================

        // Global variable to track current selected barangay
        var dashboardSelectedBarangay = '';

        // Municipality and Barangay data (same as screening.php)
        const municipalitiesData = {
            'ABUCAY': ['Bangkal', 'Calaylayan (Pob.)', 'Capitangan', 'Gabon', 'Laon (Pob.)', 'Mabatang', 'Poblacion', 'Saguing', 'Salapungan', 'Tala'],
            'BAGAC': ['Bagumbayan (Pob.)', 'Banawang', 'Binuangan', 'Binukawan', 'Ibaba', 'Ibayo', 'Paysawan', 'Quinaoayanan', 'San Antonio', 'Saysain', 'Sibucao', 'Tabing-Ilog', 'Tipo', 'Tugatog', 'Wawa'],
            'CITY OF BALANGA': ['Bagumbayan', 'Cabog-Cabog', 'Munting Batangas (Cadre)', 'Cataning', 'Central', 'Cupang Proper', 'Cupang West', 'Dangcol (Bernabe)', 'Ibayo', 'Malabia', 'Poblacion', 'Pto. Rivas Ibaba', 'Pto. Rivas Itaas', 'San Jose', 'Sibacan', 'Camacho', 'Talisay', 'Tanato', 'Tenejero', 'Tortugas', 'Tuyo', 'Bagong Silang', 'Cupang North', 'Doña Francisca', 'Lote'],
            'DINALUPIHAN': ['Bangal', 'Bonifacio (Pob.)', 'Burgos (Pob.)', 'Colo', 'Daang Bago', 'Dalao', 'Del Pilar', 'General Luna', 'Governor Generoso', 'Hacienda', 'Jose Abad Santos (Pob.)', 'Kataasan', 'Layac', 'Lourdes', 'Mabini', 'Maligaya', 'Naparing', 'Paco', 'Pag-asa', 'Pagalanggang', 'Panggalan', 'Pinulot', 'Poblacion', 'Rizal', 'Saguing', 'San Benito', 'San Isidro', 'San Ramon', 'Santo Cristo', 'Sapang Balas', 'Sumalo', 'Tipo', 'Tuklasan', 'Turac', 'Zamora'],
            'HERMOSA': ['A. Rivera (Pob.)', 'Almacen', 'Bacong', 'Balsic', 'Bamban', 'Burgos-Soliman (Pob.)', 'Cataning (Pob.)', 'Culong', 'Daungan (Pob.)', 'Judicial (Pob.)', 'Mabiga', 'Mabuco', 'Maite', 'Palihan', 'Pandatung', 'Pulong Gubat', 'San Pedro (Pob.)', 'Santo Cristo (Pob.)', 'Sumalo', 'Tipo'],
            'LIMAY': ['Alangan', 'Kitang I', 'Kitang 2 & Luz', 'Lamao', 'Landing', 'Poblacion', 'Reforma', 'San Francisco de Asis', 'Townsite'],
            'MARIVELES': ['Alas-asin', 'Alion', 'Batangas II', 'Cabcaben', 'Lucanin', 'Mabayo', 'Malaya', 'Maligaya', 'Mountain View', 'Poblacion', 'San Carlos', 'San Isidro', 'San Nicolas', 'San Pedro', 'Saysain', 'Sisiman', 'Tukuran'],
            'MORONG': ['Binaritan', 'Mabayo', 'Nagbalayong', 'Poblacion', 'Sabang', 'San Pedro', 'Sitio Liyang'],
            'ORANI': ['Apolinario (Pob.)', 'Bagong Paraiso', 'Balut', 'Bayan (Pob.)', 'Calero (Pob.)', 'Calutit', 'Camachile', 'Del Pilar', 'Kaparangan', 'Mabatang', 'Maria Fe', 'Pagtakhan', 'Paking-Carbonero (Pob.)', 'Pantalan Bago (Pob.)', 'Pantalan Luma (Pob.)', 'Parang', 'Poblacion', 'Rizal (Pob.)', 'Sagrada', 'San Jose', 'Sibul', 'Sili', 'Sulong', 'Tagumpay', 'Tala', 'Talimundoc', 'Tugatog', 'Wawa'],
            'ORION': ['Arellano (Pob.)', 'Bagumbayan (Pob.)', 'Balagtas (Pob.)', 'Balut (Pob.)', 'Bantan', 'Bilolo', 'Calungusan', 'Camachile', 'Daang Bago', 'Daan Bago', 'Daan Bilolo', 'Daan Pare', 'General Lim (Kaput)', 'Kaput', 'Lati', 'Lusung', 'Puting Buhangin', 'Sabatan', 'San Vicente', 'Santa Elena', 'Santo Domingo', 'Villa Angeles', 'Wakas'],
            'PILAR': ['Ala-uli', 'Bagumbayan', 'Balut I', 'Balut II', 'Bantan Munti', 'Bantan', 'Burgos', 'Del Rosario', 'Diwa', 'Landing', 'Liwa', 'Nueva Vida', 'Panghulo', 'Pantingan', 'Poblacion', 'Rizal', 'Sagrada', 'San Nicolas', 'San Pedro', 'Santo Niño', 'Wakas'],
            'SAMAL': ['East Calaguiman (Pob.)', 'East Daang Bago (Pob.)', 'Ibaba (Pob.)', 'Imelda', 'Lalawigan', 'Palili', 'San Juan', 'San Roque', 'Santa Lucia', 'Santo Niño', 'West Calaguiman (Pob.)', 'West Daang Bago (Pob.)']
        };

        // Function to update barangay dropdown based on selected municipality
        function updateBarangayDropdown(municipality) {
            console.log('🏘️ Updating barangay dropdown for municipality:', municipality);
            
            try {
                const barangayDropdown = document.getElementById('dropdown-content');
                const selectedSpan = document.getElementById('selected-option');
                
                if (!barangayDropdown) {
                    console.error('❌ Barangay dropdown not found');
                    return;
                }

                // Clear existing barangay options
                barangayDropdown.innerHTML = '';

                // Reset selected option text
                if (selectedSpan) {
                    selectedSpan.textContent = 'Select Barangay';
                }

                // Get barangays for the selected municipality
                const barangays = municipalitiesData[municipality];
                
                if (barangays && barangays.length > 0) {
                    // Add barangays to dropdown
                    barangays.forEach(barangay => {
                        const optionDiv = document.createElement('div');
                        optionDiv.className = 'option-item';
                        optionDiv.setAttribute('data-value', barangay);
                        optionDiv.textContent = barangay;
                        
                        // Add click event
                        optionDiv.addEventListener('click', function() {
                            selectOption(barangay, barangay);
                        });
                        
                        barangayDropdown.appendChild(optionDiv);
                    });
                    
                    console.log(`✅ Added ${barangays.length} barangays for ${municipality}`);
                } else {
                    // No barangays found
                    const noDataDiv = document.createElement('div');
                    noDataDiv.className = 'option-item';
                    noDataDiv.textContent = 'No barangays available';
                    noDataDiv.style.opacity = '0.5';
                    barangayDropdown.appendChild(noDataDiv);
                    
                    console.log('⚠️ No barangays found for municipality:', municipality);
                }
            } catch (error) {
                console.error('❌ Error updating barangay dropdown:', error);
            }
        }

        // Function to handle municipality selection  
        function selectMunicipality(municipalityValue, municipalityText) {
            console.log('🏛️ Municipality selected:', municipalityValue, municipalityText);
            
            try {
                // Update municipality display
                const municipalitySpan = document.getElementById('selected-municipality-option');
                if (municipalitySpan) {
                    municipalitySpan.textContent = municipalityText;
                }
                
                // Close municipality dropdown
                const municipalityDropdown = document.getElementById('municipality-dropdown-content');
                if (municipalityDropdown) {
                    municipalityDropdown.classList.remove('show');
                }
                
                // Update barangay dropdown with barangays from selected municipality
                updateBarangayDropdown(municipalityValue);
                
                // Update dashboard with all active filters (municipality + WHO standard)
                updateDashboardWithAllFilters();
                
                console.log('✅ Municipality selection complete, dashboard updated with all filters');
            } catch (error) {
                console.error('❌ Error in municipality selection:', error);
            }
        }

        // Fixed API URL construction function
        function constructAPIURL(endpoint, params = {}) {
            let url = endpoint;
            const paramKeys = Object.keys(params);
            
            if (paramKeys.length > 0) {
                // Use ? for first parameter, & for subsequent ones
                url += '?';
                const paramStrings = paramKeys.map(key => {
                    return `${encodeURIComponent(key)}=${encodeURIComponent(params[key])}`;
                });
                url += paramStrings.join('&');
            }
            
            console.log('🔧 Constructed URL:', url);
            return url;
        }

        // Dropdown toggle functions  
        function toggleDropdown() {
            const dropdown = document.getElementById('dropdown-content');
            if (dropdown) {
                dropdown.classList.toggle('show');
                console.log('🔽 Barangay dropdown toggled');
            }
        }

        function toggleMunicipalityDropdown() {
            const dropdown = document.getElementById('municipality-dropdown-content');  
            if (dropdown) {
                dropdown.classList.toggle('show');
                console.log('🔽 Municipality dropdown toggled');
            }
        }

        // Filter options function for search
        function filterOptions() {
            const input = document.getElementById('search-input');
            const dropdown = document.getElementById('dropdown-content');
            
            if (!input || !dropdown) return;
            
            const filter = input.value.toLowerCase();
            const options = dropdown.querySelectorAll('.option-item');
            
            options.forEach(option => {
                const text = option.textContent.toLowerCase();
                if (text.includes(filter)) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            });
            
            console.log('🔍 Filtered options with:', filter);
        }

        // Select option function
        function selectOption(value, text) {
            const selectedSpan = document.getElementById('selected-option');
            if (selectedSpan) {
                selectedSpan.textContent = text;
            }
            
            dashboardSelectedBarangay = value;
            console.log('✅ Selected barangay:', value);
            
            // Close dropdown
            const dropdown = document.getElementById('dropdown-content');
            if (dropdown) {
                dropdown.classList.remove('show');
            }
            
            // Use existing functions with the selected barangay
            console.log('🔄 updateDashboardForBarangay called with barangay:', value);
            
            // Call existing functions that already exist in the dashboard
            try {
                console.log('🔄 Updating dashboard for barangay:', value);
                
                // Force refresh all dashboard components with barangay filter
                // This will trigger the existing functions to re-fetch data with the barangay parameter
                
                // 1. Update WHO classifications with proper barangay filtering
                console.log('🔄 Updating WHO classifications for barangay:', value);
                const whoApiUrl = constructAPIURL('/api/DatabaseAPI.php', {
                    action: 'get_all_who_classifications_bulk',
                    barangay: value
                });
                console.log('🌐 WHO API URL:', whoApiUrl);
                
                fetch(whoApiUrl)
                    .then(response => {
                        console.log('📡 WHO API Response Status:', response.status);
                        return response.json();
                    })
                    .then(data => {
                        console.log('📊 WHO classifications data (RAW):', data);
                        console.log('📊 WHO data success:', data?.success);
                        console.log('📊 WHO data keys:', Object.keys(data || {}));
                        console.log('📊 WHO data.data keys:', Object.keys(data?.data || {}));
                        
                        // Update WHO chart with barangay filter
                        if (data && data.success && data.data) {
                            // Get current WHO standard
                            const whoStandardSelect = document.getElementById('who-standard-select');
                            const currentStandard = whoStandardSelect ? whoStandardSelect.value : 'weight-for-age';
                            
                            console.log('🔄 Updating WHO chart with barangay filter:', value, 'standard:', currentStandard);
                            console.log('📊 WHO data for standard', currentStandard, ':', data.data);
                            
                            // Debug: Check what keys are actually available in the API response
                            console.log('📊 DEBUG: currentStandard from dropdown:', currentStandard);
                            console.log('📊 DEBUG: Available keys in API response:', Object.keys(data.data));
                            
                            // The API returns keys with underscores, so we need to convert the dropdown value
                            const standardKey = currentStandard.replace(/-/g, '_');
                            let standardData = data.data[standardKey];
                            
                            console.log('📊 DEBUG: Converted standardKey:', standardKey);
                            console.log('📊 DEBUG: Found data for key:', !!standardData);
                            
                            // If not found, try alternative key formats
                            if (!standardData) {
                                console.log('📊 DEBUG: Trying alternative key formats...');
                                const alternativeKeys = [
                                    currentStandard, // Original format
                                    currentStandard.replace(/-/g, '_'), // Underscore format
                                    currentStandard.replace(/-/g, ''), // No separator format
                                ];
                                
                                for (const altKey of alternativeKeys) {
                                    if (data.data[altKey]) {
                                        console.log('📊 DEBUG: Found data with alternative key:', altKey);
                                        standardData = data.data[altKey];
                                        break;
                                    }
                                }
                            }
                            
                            console.log('📊 DEBUG: Final standard data:', standardData);
                            
                            if (standardData) {
                                console.log('📊 Standard classifications:', standardData.classifications);
                                console.log('📊 Standard total users:', standardData.total_users);
                            }
                            
                            // Trigger WHO chart update with the filtered data
                            if (typeof updateWHOChart === 'function') {
                                console.log('🔄 Using existing updateWHOChart function');
                                updateWHOChart(data);
                            } else {
                                console.log('🔄 Using manual WHO chart update');
                                // Fallback: manually update the WHO chart
                                updateWHOChartManually(data, currentStandard);
                            }
                        } else {
                            console.log('⚠️ No valid WHO data received for barangay:', value);
                        }
                    })
                    .catch(error => {
                        console.error('❌ Error fetching WHO data:', error);
                        console.error('❌ Error details:', error.message);
                    });
                
                // 2. Update trends chart
                console.log('🔄 Updating trends chart for barangay:', value);
                const trendsApiUrl = constructAPIURL('/api/DatabaseAPI.php', {
                    action: 'get_all_who_classifications_bulk',
                    barangay: value
                });
                fetch(trendsApiUrl)
                    .then(response => response.json())
                    .then(data => {
                        console.log('📊 Trends chart data:', data);
                        // Trigger existing trends chart update
                        if (typeof updateTrendsChart === 'function') {
                            updateTrendsChart(value);
                        }
                    })
                    .catch(error => console.error('❌ Error fetching trends data:', error));
                
                // 3. Update age classification chart
                console.log('🔄 Updating age classification chart for barangay:', value);
                const ageApiUrl = constructAPIURL('/api/DatabaseAPI.php', {
                    action: 'get_age_classification_line_chart',
                    who_standard: 'weight-for-age',
                    barangay: value
                });
                fetch(ageApiUrl)
                    .then(response => response.json())
                    .then(data => {
                        console.log('📊 Age classification data:', data);
                        // Trigger existing age chart update
                        if (typeof updateAgeClassificationChart === 'function') {
                            updateAgeClassificationChart(value);
                        }
                    })
                    .catch(error => console.error('❌ Error fetching age data:', error));
                
                // 4. Update barangay distribution
                console.log('🔄 Updating barangay distribution for barangay:', value);
                const barangayApiUrl = constructAPIURL('/api/DatabaseAPI.php', {
                    action: 'get_barangay_distribution_bulk',
                    barangay: value
                });
                fetch(barangayApiUrl)
                    .then(response => response.json())
                    .then(data => {
                        console.log('📊 Barangay distribution data:', data);
                        // Trigger existing barangay distribution update
                        if (typeof updateBarangayDistributionDisplay === 'function') {
                            updateBarangayDistributionDisplay(data);
                        }
                    })
                    .catch(error => console.error('❌ Error fetching barangay data:', error));
                
                // 5. Update gender distribution
                console.log('🔄 Updating gender distribution for barangay:', value);
                const genderApiUrl = constructAPIURL('/api/DatabaseAPI.php', {
                    action: 'get_gender_distribution_bulk',
                    barangay: value
                });
                fetch(genderApiUrl)
                    .then(response => response.json())
                    .then(data => {
                        console.log('📊 Gender distribution data:', data);
                        // Trigger existing gender distribution update
                        if (typeof updateGenderDistributionDisplay === 'function') {
                            updateGenderDistributionDisplay(data);
                        }
                    })
                    .catch(error => console.error('❌ Error fetching gender data:', error));
                
                // 6. Update community metrics with correct API
                console.log('🔄 Updating community metrics for barangay:', value);
                
                // Try the legacy API first (which we know works)
                const communityApiUrl = `https://nutrisaur-production.up.railway.app/api/dashboard_assessment_stats.php?barangay=${encodeURIComponent(value)}`;
                console.log('🌐 Community API URL (legacy):', communityApiUrl);
                
                fetch(communityApiUrl)
                    .then(response => {
                        console.log('📡 Community API Response Status:', response.status);
                        return response.json();
                    })
                    .then(data => {
                        console.log('📊 Community metrics data (RAW):', data);
                        console.log('📊 Community data success:', data?.success);
                        console.log('📊 Community data keys:', Object.keys(data || {}));
                        console.log('📊 Community data.data:', data?.data);
                        
                        // Update UI elements here if needed
                        if (data && data.success) {
                            console.log('📊 Updating community metrics UI...');
                            console.log('📊 Full community data structure:', JSON.stringify(data, null, 2));
                            
                            // Update total screened - try different data paths
                            const totalScreened = document.getElementById('community-total-screened');
                            console.log('📊 Total screened element:', totalScreened);
                            console.log('📊 Total screened from API (data.data.total_screened):', data.data?.total_screened);
                            console.log('📊 Total users from API (data.data.total_users):', data.data?.total_users);
                            console.log('📊 Total users from API (data.total_users):', data.total_users);
                            console.log('📊 Total users from API (data.processed_users):', data.processed_users);
                            
                            // Try multiple data paths for total users - prioritize total_screened
                            let totalUsers = data.data?.total_screened || data.data?.total_users || data.total_users || data.processed_users || 0;
                            console.log('📊 Final total users value:', totalUsers);
                            
                            if (totalScreened && totalUsers >= 0) {
                                console.log('📊 Setting total screened to:', totalUsers);
                                totalScreened.textContent = totalUsers;
                            } else {
                                console.log('⚠️ Could not update total screened - missing element or data');
                                console.log('⚠️ Element found:', !!totalScreened, 'Total users:', totalUsers);
                            }
                            
                            // Update other metrics if available
                            if (data.data) {
                                console.log('📊 Available community metrics:', Object.keys(data.data));
                                
                                // Update high risk if available
                                const highRisk = document.getElementById('community-high-risk');
                                if (highRisk && data.data.high_risk) {
                                    console.log('📊 Setting high risk to:', data.data.high_risk);
                                    highRisk.textContent = data.data.high_risk;
                                }
                                
                                // Update SAM cases if available
                                const samCases = document.getElementById('community-sam-cases');
                                if (samCases && data.data.sam_cases) {
                                    console.log('📊 Setting SAM cases to:', data.data.sam_cases);
                                    samCases.textContent = data.data.sam_cases;
                                }
                                
                                // Update critical MUAC if available
                                const criticalMuac = document.getElementById('community-critical-muac');
                                if (criticalMuac && data.data.critical_muac) {
                                    console.log('📊 Setting critical MUAC to:', data.data.critical_muac);
                                    criticalMuac.textContent = data.data.critical_muac;
                                }
                            }
                        } else {
                            console.log('⚠️ Community metrics API returned unsuccessful response');
                            console.log('🔄 Trying fallback API...');
                            
                            // Fallback to DatabaseAPI.php
                            const fallbackApiUrl = constructAPIURL('/api/DatabaseAPI.php', {
                                action: 'get_community_metrics',
                                barangay: value
                            });
                            console.log('🌐 Fallback API URL:', fallbackApiUrl);
                            
                            fetch(fallbackApiUrl)
                                .then(response => response.json())
                                .then(fallbackData => {
                                    console.log('📊 Fallback API response:', fallbackData);
                                    // Handle fallback response here if needed
                                })
                                .catch(fallbackError => {
                                    console.error('❌ Fallback API also failed:', fallbackError);
                                });
                        }
                    })
                    .catch(error => {
                        console.error('❌ Error fetching community metrics:', error);
                        console.error('❌ Error details:', error.message);
                    });
                
                // 7. Update charts with correct API
                console.log('🔄 Updating charts for barangay:', value);
                const chartsApiUrl = constructAPIURL('/api/DatabaseAPI.php', {
                    action: 'analysis_data',
                    barangay: value
                });
                fetch(chartsApiUrl)
                    .then(response => response.json())
                    .then(data => {
                        console.log('📊 Charts data:', data);
                        // Update chart elements here if needed
                    })
                    .catch(error => console.error('❌ Error fetching charts data:', error));
                
                console.log('✅ Dashboard update initiated for barangay:', value);
                
            } catch (error) {
                console.error('❌ Error updating dashboard:', error);
            }
        }

        // Initialize dropdown event listeners
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Initializing municipality and barangay dropdown functionality...');
            
            // Initialize trends chart event listeners
            const generateBtn = document.getElementById('generate-trends-chart');
            
            if (generateBtn) {
                generateBtn.addEventListener('click', updateTrendsChart);
            }
            
            // Add event listeners to date inputs for automatic chart updates
            const fromDateInput = document.getElementById('trends-from-date');
            const toDateInput = document.getElementById('trends-to-date');
            
            if (fromDateInput) {
                fromDateInput.addEventListener('change', updateTrendsChart);
            }
            
            if (toDateInput) {
                toDateInput.addEventListener('change', updateTrendsChart);
            }
            
            // Wait a bit for the DOM to fully render
            setTimeout(() => {
                try {
                    // Add click listeners to municipality option items
                    const municipalityDropdown = document.getElementById('municipality-dropdown-content');
                    if (municipalityDropdown) {
                        const municipalityOptions = municipalityDropdown.querySelectorAll('.option-item');
                        console.log('📍 Found', municipalityOptions.length, 'municipality options');
                        
                        municipalityOptions.forEach(item => {
                            // Remove existing listeners to avoid duplicates
                            try {
                                item.removeEventListener('click', handleMunicipalityClick);
                                item.addEventListener('click', handleMunicipalityClick);
                            } catch (error) {
                                console.warn('⚠️ Error adding municipality listener:', error);
                            }
                        });
                    } else {
                        console.error('❌ Municipality dropdown not found');
                    }
                    
                    // Add click listeners to barangay option items (for initial static options)
                    const barangayDropdown = document.getElementById('dropdown-content');
                    if (barangayDropdown) {
                        const barangayOptions = barangayDropdown.querySelectorAll('.option-item');
                        console.log('📍 Found', barangayOptions.length, 'barangay options');
                        
                        barangayOptions.forEach(item => {
                            // Remove existing listeners to avoid duplicates
                            try {
                                item.removeEventListener('click', handleBarangayClick);
                                item.addEventListener('click', handleBarangayClick);
                            } catch (error) {
                                console.warn('⚠️ Error adding barangay listener:', error);
                            }
                        });
                    } else {
                        console.error('❌ Barangay dropdown not found');
                    }
                    
                    console.log('✅ Municipality and barangay dropdown functionality initialized');
                    
                } catch (error) {
                    console.error('❌ Error initializing dropdown functionality:', error);
                }
            }, 200); // Increased delay to ensure DOM is ready
        });

        // Separate event handler functions to avoid conflicts
        function handleMunicipalityClick(event) {
            const value = this.getAttribute('data-value');
            const text = this.textContent;
            console.log('🏛️ Municipality option clicked:', value, text);
            selectMunicipality(value, text);
        }

        function handleBarangayClick(event) {
            const value = this.getAttribute('data-value');
            const text = this.textContent;
            console.log('🏘️ Barangay option clicked:', value, text);
            selectOption(value, text);
        }

        // Manual WHO chart update function
        function updateWHOChartManually(data, whoStandard) {
            console.log('🔄 Manually updating WHO chart with data:', data, 'standard:', whoStandard);
            console.log('📊 Full data object:', JSON.stringify(data, null, 2));
            
            try {
                // Get the current WHO standard data from the bulk response
                console.log('📊 DEBUG: whoStandard from parameter:', whoStandard);
                console.log('📊 DEBUG: Available keys in API response:', Object.keys(data?.data || {}));
                
                // Convert the standard name to match API response format
                const standardKey = whoStandard.replace(/-/g, '_');
                console.log('📊 DEBUG: Converted standardKey:', standardKey);
                console.log('📊 DEBUG: Key exists in API data:', data?.data?.hasOwnProperty(standardKey));
                
                const standardData = data.data[standardKey];
                console.log('📊 Standard data found:', standardData);
                
                if (standardData) {
                    console.log('📊 WHO chart data for', whoStandard, ':', standardData);
                    console.log('📊 Classifications object:', standardData);
                    console.log('📊 Total users from actual_totals:', data.actual_totals[standardKey]);
                    
                    // Use the actual_totals for total users and standardData for classifications
                    const totalUsers = data.actual_totals[standardKey] || 0;
                    const classifications = standardData;
                    
                    // Update the donut chart with the filtered data
                    const chartBg = document.getElementById('risk-chart-bg');
                    const centerText = document.getElementById('risk-center-text');
                    const segments = document.getElementById('risk-segments');
                    
                    console.log('📊 Chart elements found:');
                    console.log('  - chartBg:', chartBg);
                    console.log('  - centerText:', centerText);
                    console.log('  - segments:', segments);
                    
                    if (chartBg && centerText && segments) {
                        // Use the actual_totals for total users and standardData for classifications
                        const totalUsers = data.actual_totals[standardKey] || 0;
                        const classifications = standardData;
                        
                        console.log('📊 Updating WHO chart - Total users:', totalUsers, 'Classifications:', classifications);
                        console.log('📊 Classification entries:', Object.entries(classifications));
                        
                        // Update center text
                        console.log('📊 Setting center text to:', totalUsers);
                        centerText.textContent = totalUsers;
                        
                        // Update chart segments based on classifications
                        if (totalUsers > 0) {
                            // Create segments for each classification
                            let segmentHtml = '';
                            let totalPercentage = 0;
                            
                            console.log('📊 Processing classifications...');
                            Object.entries(classifications).forEach(([key, value], index) => {
                                console.log(`📊 Processing ${key}: ${value} users`);
                                if (value > 0) {
                                    const percentage = (value / totalUsers) * 100;
                                    const color = getClassificationColor(key);
                                    const startAngle = totalPercentage;
                                    const endAngle = totalPercentage + percentage;
                                    
                                    console.log(`📊 ${key}: ${value} users (${percentage.toFixed(2)}%) - ${color} - ${startAngle}deg to ${endAngle}deg`);
                                    
                                    segmentHtml += `
                                        <div class="segment" style="
                                            background: conic-gradient(from ${startAngle}deg, ${color} ${startAngle}deg ${endAngle}deg, transparent ${endAngle}deg);
                                            position: absolute;
                                            top: 0;
                                            left: 0;
                                            width: 100%;
                                            height: 100%;
                                            border-radius: 50%;
                                        "></div>
                                    `;
                                    
                                    totalPercentage += percentage;
                                }
                            });
                            
                            console.log('📊 Total percentage:', totalPercentage);
                            console.log('📊 Setting segments HTML:', segmentHtml);
                            segments.innerHTML = segmentHtml;
                            
                            // Update chart background
                            if (totalPercentage > 0) {
                                const backgroundStyle = `conic-gradient(#E91E63 0% ${totalPercentage}%, #f0f0f0 ${totalPercentage}% 100%)`;
                                console.log('📊 Setting chart background to:', backgroundStyle);
                                chartBg.style.background = backgroundStyle;
                            }
                            
                            console.log('✅ WHO chart updated manually with barangay filter');
                            
                            // Update severely cases cards with WHO data
                            updateSeverelyCasesCards(data);
                        } else {
                            // No data - show empty chart
                            console.log('📊 No data - showing empty chart');
                            centerText.textContent = '0';
                            segments.innerHTML = '';
                            
                            // Update severely cases cards with zero data
                            updateSeverelyCasesCards({ data: { 
                                weight_for_age: { 'Severely Underweight': 0 },
                                height_for_age: { 'Severely Stunted': 0 },
                                weight_for_height: { 'Severely Wasted': 0 }
                            }});
                            chartBg.style.background = '#f0f0f0';
                        }
                    } else {
                        console.error('❌ WHO chart elements not found');
                        console.error('❌ Missing elements:', {
                            chartBg: !chartBg,
                            centerText: !centerText,
                            segments: !segments
                        });
                    }
                } else {
                    console.log('⚠️ No data available for WHO standard:', whoStandard);
                    console.log('⚠️ Standard data:', standardData);
                    console.log('⚠️ Has classifications:', standardData?.classifications);
                }
            } catch (error) {
                console.error('❌ Error updating WHO chart manually:', error);
                console.error('❌ Error stack:', error.stack);
            }
        }

        // Helper function to get classification colors
        function getClassificationColor(classification) {
            const colors = {
                'severe': '#E91E63',
                'moderate': '#FF9800', 
                'mild': '#FFC107',
                'normal': '#4CAF50',
                'overweight': '#9C27B0',
                'obese': '#F44336'
            };
            return colors[classification] || '#E91E63';
        }

        // NEW: Function to update severe cases list
        async function updateSevereCasesList(barangay = '') {
            console.log('📊 Updating severe cases list...');
            console.log('📊 Severe cases function called with barangay:', barangay);
            
            try {
                const severeCasesList = document.getElementById('severe-cases-list');
                console.log('📊 Severe cases list element found:', severeCasesList);
                if (!severeCasesList) {
                    console.error('❌ Severe cases list element not found');
                    return;
                }

                // Clear previous list
                severeCasesList.innerHTML = '';

                // Get current filter values
                const barangayValue = barangay || '';
                const whoStandardSelect = document.getElementById('whoStandardSelect');
                const whoStandard = whoStandardSelect ? whoStandardSelect.value : 'weight-for-age';
                
                // Debug logging
                console.log('📊 Severe cases - WHO Standard Select element:', whoStandardSelect);
                console.log('📊 Severe cases - WHO Standard value:', whoStandard);
                console.log('📊 Severe cases - Dropdown value at time of call:', whoStandardSelect ? whoStandardSelect.value : 'N/A');

                // Fetch severe cases data
                const url = `/api/DatabaseAPI.php?action=get_severe_cases&barangay=${encodeURIComponent(barangayValue)}&who_standard=${whoStandard}`;
                console.log('📊 Fetching severe cases data from:', url);
                
                const response = await fetch(url);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Severe cases data received:', data);

                if (!data.success || !data.data || !data.data.severe_cases || data.data.severe_cases.length === 0) {
                    console.log('No severe cases found');
                    severeCasesList.innerHTML = '<div class="severe-cases-empty">No severe malnutrition cases found for the selected filters</div>';
                    return;
                }

                const severeCases = data.data.severe_cases;
                console.log(`Found ${severeCases.length} severe cases`);

                // Create list items for each severe case
                severeCases.forEach((caseData, index) => {
                    const caseItem = document.createElement('div');
                    
                    // Convert classification to CSS class name
                    let cssClass = caseData.classification.toLowerCase().replace(/\s+/g, '-');
                    // No special handling needed - use the actual classification
                    
                    caseItem.className = `severe-case-item ${cssClass}`;
                    
                    // Debug: Log the CSS class being applied
                    console.log('🔍 Severe case CSS class:', cssClass, 'for classification:', caseData.classification);
                    
                    // Format screening date
                    const screeningDate = new Date(caseData.screening_date);
                    const formattedDate = screeningDate.toLocaleDateString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric'
                    });
                    
                    caseItem.innerHTML = `
                        <div class="severe-case-info">
                            <div class="severe-case-name">${caseData.name}</div>
                            <div class="severe-case-details">
                                <span>Age: ${caseData.age}</span>
                                <span>Date: ${formattedDate}</span>
                            </div>
                        </div>
                        <div class="severe-case-classification">${caseData.classification}</div>
                    `;
                    
                    severeCasesList.appendChild(caseItem);
                });

                console.log(`✅ Severe cases list updated successfully with ${severeCases.length} cases`);

            } catch (error) {
                console.error('❌ Error updating severe cases list:', error);
                const severeCasesList = document.getElementById('severe-cases-list');
                if (severeCasesList) {
                    severeCasesList.innerHTML = '<div class="severe-cases-empty">Error loading severe cases: ' + error.message + '</div>';
                }
            }
        }

        // ===== MODERN MOBILE TOP NAVIGATION SYSTEM =====
        
        // Initialize modern mobile top navigation
        function initNavigation() {
            console.log('🚀 Initializing modern mobile top navigation...');
            
            const navbar = document.getElementById('navbar');
            
            // Check if elements exist
            console.log('📱 Desktop navbar exists:', !!navbar);
            
            // Initialize mobile dropdown functionality
            initMobileDropdowns();
            
            // Desktop hover navigation (unchanged)
            if (navbar && window.innerWidth >= 769) {
                navbar.addEventListener('mouseenter', function() {
                    expandNavbar();
                });
                
                navbar.addEventListener('mouseleave', function() {
                    minimizeNavbar();
                });
            }
            
            // Handle window resize
            window.addEventListener('resize', function() {
                const isMobile = window.innerWidth <= 768;
                
                if (isMobile) {
                    // Mobile: hide desktop navbar, show mobile top nav
                    if (navbar) navbar.style.display = 'none';
                    document.body.style.paddingLeft = '0';
                    document.body.style.paddingTop = '60px';
                    document.body.style.width = '100vw';
                    document.body.style.maxWidth = '100vw';
                    document.body.style.overflowX = 'hidden';
                    document.body.style.minHeight = '100vh';
                } else {
                    // Desktop: show desktop navbar, hide mobile top nav
                    if (navbar) navbar.style.display = 'flex';
                    document.body.style.paddingLeft = '40px';
                    document.body.style.paddingTop = '0';
                    document.body.style.width = '';
                    document.body.style.maxWidth = '';
                    document.body.style.overflowX = '';
                }
            });
            
            // Desktop navigation functions (unchanged)
            function expandNavbar() {
                if (window.innerWidth >= 769) {
                    document.body.style.paddingLeft = '320px';
                }
            }
            
            function minimizeNavbar() {
                if (window.innerWidth >= 769) {
                    document.body.style.paddingLeft = '40px';
                }
            }
            
            // Set initial state
            const isMobile = window.innerWidth <= 768;
            if (isMobile) {
                if (navbar) navbar.style.display = 'none';
                document.body.style.paddingLeft = '0';
                document.body.style.paddingTop = '60px';
                document.body.style.width = '100vw';
                document.body.style.maxWidth = '100vw';
                document.body.style.overflowX = 'hidden';
                document.body.style.minHeight = '100vh';
            } else {
                if (navbar) navbar.style.display = 'flex';
                document.body.style.paddingLeft = '40px';
                document.body.style.paddingTop = '0';
                document.body.style.width = '';
                document.body.style.maxWidth = '';
                document.body.style.overflowX = '';
            }
            
            console.log('✅ Modern mobile top navigation system initialized successfully');
        }
        
        // Initialize mobile dropdown functionality
        function initMobileDropdowns() {
            console.log('📱 Initializing mobile dropdown functionality...');
            
            // Get all dropdown elements
            const selectHeaders = document.querySelectorAll('.select-header');
            const dropdownContents = document.querySelectorAll('.dropdown-content');
            const optionItems = document.querySelectorAll('.option-item');
            
            console.log('📱 Found select headers:', selectHeaders.length);
            console.log('📱 Found dropdown contents:', dropdownContents.length);
            console.log('📱 Found option items:', optionItems.length);
            
            // Add touch and click event listeners to select headers
            selectHeaders.forEach((header, index) => {
                const dropdown = dropdownContents[index];
                if (!dropdown) return;
                
                // Touch events for mobile
                header.addEventListener('touchstart', function(e) {
                    e.preventDefault();
                    toggleDropdown(header, dropdown);
                });
                
                // Click events for desktop
                header.addEventListener('click', function(e) {
                    e.preventDefault();
                    toggleDropdown(header, dropdown);
                });
                
                // Add active class styling
                header.addEventListener('touchstart', function() {
                    header.classList.add('active');
                });
                
                header.addEventListener('touchend', function() {
                    setTimeout(() => header.classList.remove('active'), 150);
                });
            });
            
            // Add event listeners to option items
            optionItems.forEach((item) => {
                // Touch events for mobile
                item.addEventListener('touchstart', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    selectOption(item);
                });
                
                // Click events for desktop
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    selectOption(item);
                });
            });
            
            // Close dropdowns when clicking outside
            document.addEventListener('touchstart', function(e) {
                if (!e.target.closest('.custom-select-container')) {
                    closeAllDropdowns();
                }
            });
            
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.custom-select-container')) {
                    closeAllDropdowns();
                }
            });
            
            console.log('✅ Mobile dropdown functionality initialized');
        }
        
        // Toggle dropdown function
        function toggleDropdown(header, dropdown) {
            const isActive = dropdown.classList.contains('active');
            
            // Close all other dropdowns first
            closeAllDropdowns();
            
            if (!isActive) {
                dropdown.classList.add('active');
                header.classList.add('active');
            }
        }
        
        // Close all dropdowns
        function closeAllDropdowns() {
            const dropdowns = document.querySelectorAll('.dropdown-content');
            const headers = document.querySelectorAll('.select-header');
            
            dropdowns.forEach(dropdown => dropdown.classList.remove('active'));
            headers.forEach(header => header.classList.remove('active'));
        }
        
        // Select option function
        function selectOption(item) {
            const value = item.getAttribute('data-value');
            const text = item.textContent.trim();
            const container = item.closest('.custom-select-container');
            const header = container.querySelector('.select-header span');
            
            if (header) {
                header.textContent = text;
            }
            
            // Close dropdown
            closeAllDropdowns();
            
            // Trigger change event if needed
            if (typeof selectOption === 'function') {
                selectOption(value, text);
            }
            
            console.log('📱 Option selected:', value, text);
        }

        // Real-time dashboard updates
        let realTimeInterval;
        let isUpdating = false;
        let lastUpdateTime = Date.now();

        async function updateDashboardData() {
            if (isUpdating) {
                console.log('⏳ Dashboard update already in progress, skipping...');
                return;
            }

            isUpdating = true;
            
            try {
                // Show loading indicator
                showLoadingIndicator();
                
                // Get current barangay filter
                const barangaySelect = document.querySelector('select[name="barangay"]');
                const currentBarangay = barangaySelect ? barangaySelect.value : 'all';
                
                // Fetch updated data
                const response = await fetch(`/api/dashboard_data.php?barangay=${currentBarangay}&t=${Date.now()}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const dashboardData = await response.json();
                
                if (!dashboardData.success) {
                    throw new Error(dashboardData.message || 'Failed to fetch dashboard data');
                }
                
                console.log('🔄 Dashboard data updated:', dashboardData.timestamp);
                
                // Update dashboard elements
                updateDashboardElements(dashboardData.data);
                
                // Update last update time
                lastUpdateTime = Date.now();
                updateLastUpdateIndicator();
                
                hideLoadingIndicator();
                
            } catch (error) {
                console.error('❌ Error updating dashboard:', error);
                hideLoadingIndicator();
                showErrorIndicator();
            } finally {
                isUpdating = false;
            }
        }

        function updateDashboardElements(data) {
            // Update main metrics
            updateMetrics(data.metrics);
            
            // Update statistics
            updateStatistics(data.nutritional_statistics);
            
            // Note: Charts are updated via existing dropdown change handlers
            // Real-time updates only refresh metrics and statistics
        }

        function updateMetrics(metrics) {
            // Update total screened
            const totalElement = document.querySelector('.metric-card .metric-number');
            if (totalElement && totalElement.textContent !== metrics.total_screened.toString()) {
                animateNumberChange(totalElement, metrics.total_screened);
            }
            
            // Update other metric cards
            const metricCards = document.querySelectorAll('.metric-card');
            metricCards.forEach((card, index) => {
                const numberElement = card.querySelector('.metric-number');
                if (numberElement) {
                    let newValue;
                    switch (index) {
                        case 0: newValue = metrics.total_screened; break;
                        case 1: newValue = metrics.high_risk_cases; break;
                        case 2: newValue = metrics.sam_cases; break;
                        case 3: newValue = metrics.severely_wasted; break;
                        default: return;
                    }
                    
                    if (numberElement.textContent !== newValue.toString()) {
                        animateNumberChange(numberElement, newValue);
                    }
                }
            });
        }


        function updateStatistics(stats) {
            // Update nutritional statistics display
            if (window.nutritionalStatistics) {
                window.nutritionalStatistics = stats;
                console.log('📊 Nutritional statistics updated');
            }
        }

        function animateNumberChange(element, newValue) {
            const currentValue = parseInt(element.textContent) || 0;
            const difference = newValue - currentValue;
            
            if (difference === 0) return;
            
            // Add animation class
            element.classList.add('updating');
            
            // Animate the number change
            const duration = 500;
            const steps = 20;
            const stepValue = difference / steps;
            let currentStep = 0;
            
            const animation = setInterval(() => {
                currentStep++;
                const displayValue = Math.round(currentValue + (stepValue * currentStep));
                element.textContent = displayValue;
                
                if (currentStep >= steps) {
                    clearInterval(animation);
                    element.textContent = newValue;
                    element.classList.remove('updating');
                    
                    // Add flash effect for significant changes
                    if (Math.abs(difference) > 0) {
                        element.classList.add('changed');
                        setTimeout(() => element.classList.remove('changed'), 1000);
                    }
                }
            }, duration / steps);
        }

        function showLoadingIndicator() {
            let indicator = document.getElementById('realtime-loading');
            if (!indicator) {
                indicator = document.createElement('div');
                indicator.id = 'realtime-loading';
                indicator.innerHTML = '🔄 Updating...';
                indicator.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: rgba(0, 123, 255, 0.9);
                    color: white;
                    padding: 8px 16px;
                    border-radius: 20px;
                    font-size: 12px;
                    z-index: 10000;
                    opacity: 0;
                    transition: opacity 0.3s ease;
                `;
                document.body.appendChild(indicator);
            }
            indicator.style.opacity = '1';
        }

        function hideLoadingIndicator() {
            const indicator = document.getElementById('realtime-loading');
            if (indicator) {
                indicator.style.opacity = '0';
            }
        }

        function showErrorIndicator() {
            let indicator = document.getElementById('realtime-error');
            if (!indicator) {
                indicator = document.createElement('div');
                indicator.id = 'realtime-error';
                indicator.innerHTML = '⚠️ Update failed';
                indicator.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: rgba(220, 53, 69, 0.9);
                    color: white;
                    padding: 8px 16px;
                    border-radius: 20px;
                    font-size: 12px;
                    z-index: 10000;
                    opacity: 0;
                    transition: opacity 0.3s ease;
                `;
                document.body.appendChild(indicator);
            }
            indicator.style.opacity = '1';
            setTimeout(() => {
                indicator.style.opacity = '0';
            }, 3000);
        }

        function updateLastUpdateIndicator() {
            let indicator = document.getElementById('last-update-indicator');
            if (!indicator) {
                indicator = document.createElement('div');
                indicator.id = 'last-update-indicator';
                indicator.style.cssText = `
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    background: rgba(40, 167, 69, 0.9);
                    color: white;
                    padding: 6px 12px;
                    border-radius: 15px;
                    font-size: 11px;
                    z-index: 9999;
                    opacity: 0.7;
                `;
                document.body.appendChild(indicator);
            }
            
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            indicator.textContent = `Last updated: ${timeString}`;
        }

        function startRealTimeUpdates() {
            console.log('🚀 Starting real-time dashboard updates (every 3 seconds)');
            
            // Initial update
            updateLastUpdateIndicator();
            
            // Set up interval for updates every 3 seconds
            realTimeInterval = setInterval(updateDashboardData, 3000);
            
            // Add pause/resume functionality when tab is not visible
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    console.log('⏸️ Pausing real-time updates (tab hidden)');
                    if (realTimeInterval) {
                        clearInterval(realTimeInterval);
                        realTimeInterval = null;
                    }
                } else {
                    console.log('▶️ Resuming real-time updates (tab visible)');
                    if (!realTimeInterval) {
                        realTimeInterval = setInterval(updateDashboardData, 3000);
                        // Immediate update when tab becomes visible
                        updateDashboardData();
                    }
                }
            });
        }

        function stopRealTimeUpdates() {
            console.log('⏹️ Stopping real-time dashboard updates');
            if (realTimeInterval) {
                clearInterval(realTimeInterval);
                realTimeInterval = null;
            }
        }

        // Add CSS for animations
        const style = document.createElement('style');
        style.textContent = `
            .metric-number.updating {
                color: #007bff;
                transition: color 0.3s ease;
            }
            
            .metric-number.changed {
                background: rgba(40, 167, 69, 0.2);
                border-radius: 4px;
                padding: 2px 4px;
                animation: flash 1s ease-out;
            }
            
            @keyframes flash {
                0% { background: rgba(40, 167, 69, 0.4); }
                100% { background: rgba(40, 167, 69, 0.1); }
            }
        `;
        document.head.appendChild(style);

        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                initNavigation();
                startRealTimeUpdates();
            });
        } else {
            initNavigation();
            startRealTimeUpdates();
        }

    </script>
</body>
</html>
