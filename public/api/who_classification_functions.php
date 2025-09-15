<?php
// WHO Classification Functions - extracted from dash.php to avoid HTML output

require_once __DIR__ . '/DatabaseHelper.php';
require_once __DIR__ . '/../../who_growth_standards.php';

// Function to get adult BMI classification (for children over 71 months)
function getAdultBMIClassification($bmi) {
    if ($bmi < 18.5) return 'Underweight';
    if ($bmi < 25) return 'Normal weight';
    if ($bmi < 30) return 'Overweight';
    return 'Obese';
}

// Function to get screening responses by time frame
function getScreeningResponsesByTimeFrame($db, $timeFrame, $barangay = null) {
    try {
        error_log("🔍 getScreeningResponsesByTimeFrame - Starting");
        error_log("  - Time Frame: $timeFrame");
        error_log("  - Barangay: " . ($barangay ?: 'null'));
        
        // Build where clause based on time frame
        $whereClause = "1=1";
        $params = [];
        
        // Add time frame filter - for now, let's get all users regardless of date
        // TODO: Implement proper date filtering based on actual screening dates
        switch ($timeFrame) {
            case '1d':
                // Get all users for now - no date filtering
                break;
            case '7d':
                // Get all users for now - no date filtering
                break;
            case '30d':
                // Get all users for now - no date filtering
                break;
            case '90d':
                // Get all users for now - no date filtering
                break;
            case '1y':
                // Get all users for now - no date filtering
                break;
        }
        
        // Add barangay filter if specified
        if ($barangay && $barangay !== '') {
            $whereClause .= " AND barangay = ?";
            $params[] = $barangay;
        }
        
        error_log("  - Where clause: $whereClause");
        error_log("  - Params: " . json_encode($params));
        
        // First, let's test getting all users without date filtering
        $allUsersResult = $db->select('community_users', '*', '1=1', [], 'screening_date DESC');
        error_log("  - All users count (no filtering): " . count($allUsersResult['data'] ?? []));
        
        // Get users from community_users table
        $result = $db->select('community_users', '*', $whereClause, $params, 'screening_date DESC');
        
        error_log("  - Query result success: " . ($result['success'] ? 'true' : 'false'));
        error_log("  - Query result data count: " . count($result['data'] ?? []));
        
        if ($result['success']) {
            return $result['data'];
        } else {
            error_log("Error fetching screening responses: " . $result['error']);
            return [];
        }
        
    } catch (Exception $e) {
        error_log("Exception in getScreeningResponsesByTimeFrame: " . $e->getMessage());
        return [];
    }
}

// Function to get WHO classification data for donut chart using decision tree
function getWHOClassificationData($db, $timeFrame, $barangay = null, $whoStandard = 'weight-for-age') {
    try {
        error_log("🔍 WHO Classification Debug - Starting");
        error_log("  - Time Frame: $timeFrame");
        error_log("  - Barangay: " . ($barangay ?: 'null'));
        error_log("  - WHO Standard: $whoStandard");
        
        // Get users data using the same method as other functions
        $users = getScreeningResponsesByTimeFrame($db, $timeFrame, $barangay);
        error_log("  - Total users found: " . count($users));
        
        // Count classifications for the selected WHO standard
        $classifications = [
            'Underweight' => 0,
            'Normal' => 0,
            'Overweight' => 0,
            'Obese' => 0,
            'No Data' => 0
        ];
        
        foreach ($users as $user) {
            try {
                error_log("  - Processing user: " . ($user['email'] ?? 'unknown'));
                
                // Calculate age in months - check if birth_date exists and is not null
                $ageInMonths = 0;
                if (isset($user['birth_date']) && $user['birth_date'] !== null && $user['birth_date'] !== '') {
                    try {
                        $birthDate = new DateTime($user['birth_date']);
                        $today = new DateTime();
                        $age = $today->diff($birthDate);
                        $ageInMonths = ($age->y * 12) + $age->m;
                        
                        // Add partial month if more than half the month has passed
                        if ($age->d >= 15) {
                            $ageInMonths += 1;
                        }
                    } catch (Exception $e) {
                        error_log("    - Error calculating age: " . $e->getMessage());
                        $ageInMonths = 0;
                    }
                } else {
                    // If no birth_date, assume adult (over 71 months)
                    $ageInMonths = 72; // 6 years old
                    error_log("    - No birth_date found, assuming adult age (72 months)");
                }
                
                error_log("    - Age in months: $ageInMonths");
                error_log("    - Weight: " . ($user['weight'] ?? 'null'));
                error_log("    - Height: " . ($user['height'] ?? 'null'));
                error_log("    - Sex: " . ($user['sex'] ?? 'null'));
                
                $classification = 'No Data';
                $shouldProcess = false;
                
                // Apply age and height restrictions like in screening.php
                if ($whoStandard === 'weight-for-age' || $whoStandard === 'height-for-age') {
                    // These standards are for children 0-71 months only
                    $shouldProcess = ($ageInMonths >= 0 && $ageInMonths <= 71);
                    error_log("    - Age restriction check: " . ($shouldProcess ? 'true' : 'false') . " (age: $ageInMonths, standard: $whoStandard)");
                } elseif ($whoStandard === 'bmi-for-age') {
                    // BMI-for-age can be used for both children (0-71 months) and adults (>71 months)
                    if ($ageInMonths <= 71) {
                        // Child: use WHO Growth Standards
                        $shouldProcess = true;
                        error_log("    - Child BMI-for-age: " . ($shouldProcess ? 'true' : 'false') . " (age: $ageInMonths)");
                    } else {
                        // Adult: use adult BMI classification
                        $shouldProcess = true;
                        error_log("    - Adult BMI-for-age: " . ($shouldProcess ? 'true' : 'false') . " (age: $ageInMonths)");
                    }
                } elseif ($whoStandard === 'weight-for-height') {
                    // Weight-for-Height: 65-120 cm height range
                    $heightCm = floatval($user['height']);
                    $shouldProcess = ($heightCm >= 65 && $heightCm <= 120);
                    error_log("    - Height restriction check: " . ($shouldProcess ? 'true' : 'false') . " (height: $heightCm cm, standard: $whoStandard)");
                } elseif ($whoStandard === 'weight-for-length') {
                    // Weight-for-Length: 45-110 cm height range
                    $heightCm = floatval($user['height']);
                    $shouldProcess = ($heightCm >= 45 && $heightCm <= 110);
                    error_log("    - Length restriction check: " . ($shouldProcess ? 'true' : 'false') . " (height: $heightCm cm, standard: $whoStandard)");
                }
                
                error_log("    - Should process: " . ($shouldProcess ? 'true' : 'false'));
                
                if ($shouldProcess) {
                    if ($ageInMonths > 71 && $whoStandard === 'bmi-for-age') {
                        // For adults (>71 months), use adult BMI classification
                        $bmi = floatval($user['weight']) / pow(floatval($user['height']) / 100, 2);
                        $classification = getAdultBMIClassification($bmi);
                        error_log("    - Adult BMI classification: $classification (BMI: $bmi)");
                    } else {
                        // Use WHO Growth Standards for children 0-71 months
                        $who = new WHOGrowthStandards();
                        // Use a default birth date if none exists (6 years ago for adult classification)
                        $birthDateForAssessment = isset($user['birth_date']) && $user['birth_date'] !== null && $user['birth_date'] !== '' 
                            ? $user['birth_date'] 
                            : date('Y-m-d', strtotime('-6 years'));
                        
                        $assessment = $who->getComprehensiveAssessment(
                            floatval($user['weight']), 
                            floatval($user['height']), 
                            $birthDateForAssessment, 
                            $user['sex']
                        );
                        
                        error_log("    - WHO Assessment result: " . json_encode($assessment));
                        
                        if ($assessment['success'] && isset($assessment['results'])) {
                            $results = $assessment['results'];
                            
                            // Get classification for the selected standard
                            switch ($whoStandard) {
                                case 'weight-for-age':
                                    $classification = $results['weight_for_age']['classification'] ?? 'No Data';
                                    break;
                                case 'height-for-age':
                                    $classification = $results['height_for_age']['classification'] ?? 'No Data';
                                    break;
                                case 'weight-for-height':
                                    $classification = $results['weight_for_height']['classification'] ?? 'No Data';
                                    break;
                                case 'weight-for-length':
                                    $classification = $results['weight_for_length']['classification'] ?? 'No Data';
                                    break;
                                case 'bmi-for-age':
                                    $classification = $results['bmi_for_age']['classification'] ?? 'No Data';
                                    break;
                            }
                            error_log("    - WHO classification: $classification");
                        } else {
                            error_log("    - WHO assessment failed");
                        }
                    }
                }
                
                // Map classifications to our categories
                error_log("    - Final classification: $classification");
                
                // Only count users that were actually processed (shouldProcess = true)
                if ($shouldProcess) {
                    if (in_array($classification, ['Severely Underweight', 'Underweight'])) {
                        $classifications['Underweight']++;
                        error_log("    - Mapped to Underweight");
                    } elseif (in_array($classification, ['Normal', 'Normal weight'])) {
                        $classifications['Normal']++;
                        error_log("    - Mapped to Normal");
                    } elseif (in_array($classification, ['Overweight'])) {
                        $classifications['Overweight']++;
                        error_log("    - Mapped to Overweight");
                    } elseif (in_array($classification, ['Obese', 'Severely Obese'])) {
                        $classifications['Obese']++;
                        error_log("    - Mapped to Obese");
                    } else {
                        $classifications['No Data']++;
                        error_log("    - Mapped to No Data");
                    }
                } else {
                    // Don't count users that don't meet age/height requirements
                    error_log("    - User not counted due to age/height restrictions");
                }
                
            } catch (Exception $e) {
                error_log("WHO assessment error for user {$user['email']}: " . $e->getMessage());
                $classifications['No Data']++;
            }
        }
        
        // Calculate total processed users (sum of all classifications)
        $totalProcessedUsers = $classifications['Underweight'] + $classifications['Normal'] + $classifications['Overweight'] + $classifications['Obese'] + $classifications['No Data'];
        
        error_log("📊 Final WHO Classification Results:");
        error_log("  - Underweight: " . $classifications['Underweight']);
        error_log("  - Normal: " . $classifications['Normal']);
        error_log("  - Overweight: " . $classifications['Overweight']);
        error_log("  - Obese: " . $classifications['Obese']);
        error_log("  - No Data: " . $classifications['No Data']);
        error_log("  - Total Processed Users: " . $totalProcessedUsers);
        error_log("  - Total Database Users: " . count($users));
        
        return [
            'success' => true,
            'classifications' => $classifications,
            'total_users' => $totalProcessedUsers,
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
                'Obese' => 0,
                'No Data' => 0
            ],
            'total_users' => 0,
            'who_standard' => $whoStandard
        ];
    }
}
?>
