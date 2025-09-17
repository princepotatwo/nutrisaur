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
        
        // Get users from community_users table
        $result = $db->select('community_users', '*', $whereClause, $params, 'screening_date DESC');
        
        if ($result['success']) {
            return $result['data'];
        } else {
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
        // Get users data using the same method as other functions
        $users = getScreeningResponsesByTimeFrame($db, $timeFrame, $barangay);
        
        // Count classifications for the selected WHO standard
        // Initialize all possible classifications
        $classifications = [
            'Severely Underweight' => 0,
            'Underweight' => 0,
            'Normal' => 0,
            'Overweight' => 0,
            'Obese' => 0,
            'Severely Wasted' => 0,
            'Wasted' => 0,
            'Severely Stunted' => 0,
            'Stunted' => 0,
            'Tall' => 0,
            'No Data' => 0
        ];
        
        foreach ($users as $user) {
            try {
                // Calculate age in months using screening date (same as screening.php)
                $ageInMonths = 0;
                if (isset($user['birthday']) && $user['birthday'] !== null && $user['birthday'] !== '') {
                    try {
                        $birthDate = new DateTime($user['birthday']);
                        $screeningDate = new DateTime($user['screening_date'] ?? date('Y-m-d H:i:s'));
                        $age = $birthDate->diff($screeningDate);
                        $ageInMonths = ($age->y * 12) + $age->m;
                        
                        // Add partial month if more than half the month has passed
                        if ($age->d >= 15) {
                            $ageInMonths += 1;
                        }
                    } catch (Exception $e) {
                        $ageInMonths = 0;
                    }
                } else {
                    // If no birthday, assume adult (over 71 months)
                    $ageInMonths = 72; // 6 years old
                }
                
                $classification = 'No Data';
                $shouldProcess = false;
                
                // Apply age and height restrictions exactly like screening.php
                if ($whoStandard === 'weight-for-age' || $whoStandard === 'height-for-age') {
                    // These standards are for children 0-71 months only
                    $shouldProcess = ($ageInMonths >= 0 && $ageInMonths <= 71);
                    error_log("  - User age: {$ageInMonths} months, WFA/HFA eligible: " . ($shouldProcess ? 'YES' : 'NO'));
                } elseif ($whoStandard === 'bmi-for-age') {
                    // BMI-for-Age: 2-19 years (24-228 months) - exactly like screening.php
                    $shouldProcess = ($ageInMonths >= 24 && $ageInMonths < 228);
                    error_log("  - User age: {$ageInMonths} months, BMI-for-Age eligible: " . ($shouldProcess ? 'YES' : 'NO'));
                } elseif ($whoStandard === 'bmi-adult') {
                    // BMI Adult: ≥19 years (228+ months) - exactly like screening.php
                    $shouldProcess = ($ageInMonths >= 228);
                    error_log("  - User age: {$ageInMonths} months, BMI-Adult eligible: " . ($shouldProcess ? 'YES' : 'NO'));
                } elseif ($whoStandard === 'weight-for-height') {
                    // Weight-for-Height: 0-60 months (0-5 years) - exactly like screening.php
                    $shouldProcess = ($ageInMonths >= 0 && $ageInMonths <= 60);
                    error_log("  - User age: {$ageInMonths} months, WFH eligible: " . ($shouldProcess ? 'YES' : 'NO'));
                }
                
                if ($shouldProcess) {
                    if ($whoStandard === 'bmi-adult') {
                        // For BMI-adult, use adult BMI classification
                        $bmi = floatval($user['weight_kg']) / pow(floatval($user['height_cm']) / 100, 2);
                        $classification = getAdultBMIClassification($bmi);
                    } elseif ($ageInMonths > 71 && $whoStandard === 'bmi-for-age') {
                        // For adults (>71 months) using bmi-for-age, use adult BMI classification
                        $bmi = floatval($user['weight_kg']) / pow(floatval($user['height_cm']) / 100, 2);
                        $classification = getAdultBMIClassification($bmi);
                    } else {
                        // Use WHO Growth Standards for children (same as screening.php)
                        $who = new WHOGrowthStandards();
                        
                        $assessment = $who->getComprehensiveAssessment(
                            floatval($user['weight_kg']), 
                            floatval($user['height_cm']), 
                            $user['birthday'], 
                            $user['sex'],
                            $user['screening_date'] ?? null
                        );
                        
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
                                case 'bmi-for-age':
                                    $classification = $results['bmi_for_age']['classification'] ?? 'No Data';
                                    break;
                            }
                        }
                    }
                }
                
                // Map classifications to our categories
                // Only count users that were actually processed (shouldProcess = true)
                if ($shouldProcess) {
                    // Map all possible WHO classifications
                    if ($classification === 'Severely Underweight') {
                        $classifications['Severely Underweight']++;
                    } elseif ($classification === 'Underweight') {
                        $classifications['Underweight']++;
                    } elseif (in_array($classification, ['Normal', 'Normal weight'])) {
                        $classifications['Normal']++;
                    } elseif ($classification === 'Overweight') {
                        $classifications['Overweight']++;
                    } elseif (in_array($classification, ['Obese', 'Severely Obese'])) {
                        $classifications['Obese']++;
                    } elseif ($classification === 'Severely Wasted') {
                        $classifications['Severely Wasted']++;
                    } elseif ($classification === 'Wasted') {
                        $classifications['Wasted']++;
                    } elseif ($classification === 'Severely Stunted') {
                        $classifications['Severely Stunted']++;
                    } elseif ($classification === 'Stunted') {
                        $classifications['Stunted']++;
                    } elseif ($classification === 'Tall') {
                        $classifications['Tall']++;
                    } else {
                        $classifications['No Data']++;
                    }
                }
                
            } catch (Exception $e) {
                error_log("WHO assessment error for user {$user['email']}: " . $e->getMessage());
                $classifications['No Data']++;
            }
        }
        
        // Calculate total processed users (sum of all classifications)
        $totalProcessedUsers = $classifications['Severely Underweight'] + $classifications['Underweight'] + $classifications['Normal'] + $classifications['Overweight'] + $classifications['Obese'] + $classifications['Severely Wasted'] + $classifications['Wasted'] + $classifications['Severely Stunted'] + $classifications['Stunted'] + $classifications['Tall'] + $classifications['No Data'];
        
        error_log("📊 WHO Classification Summary for $whoStandard:");
        error_log("  - Total users in database: " . count($users));
        error_log("  - Users processed (eligible): $totalProcessedUsers");
        error_log("  - Classifications: " . json_encode($classifications));
        
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
                'Severely Underweight' => 0,
                'Underweight' => 0,
                'Normal' => 0,
                'Overweight' => 0,
                'Obese' => 0,
                'Severely Wasted' => 0,
                'Wasted' => 0,
                'Severely Stunted' => 0,
                'Stunted' => 0,
                'Tall' => 0,
                'No Data' => 0
            ],
            'total_users' => 0,
            'who_standard' => $whoStandard
        ];
    }
}
?>
