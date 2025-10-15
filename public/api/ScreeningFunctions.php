<?php
/**
 * Screening Functions - Extracted for reuse
 * Used by both screening.php and auto-screening
 */

require_once __DIR__ . '/../../who_growth_standards.php';
require_once __DIR__ . '/../../config.php';

/**
 * Get nutritional assessment for a user
 */
function getNutritionalAssessment($user) {
    try {
        $who = new WHOGrowthStandards();
        
        // Calculate age in months for WHO standards using screening date
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
            return $assessment;
        } else {
            return [
                'success' => false,
                'error' => $assessment['error'] ?? 'Assessment failed',
                'nutritional_status' => 'Assessment Error',
                'risk_level' => 'Unknown',
                'category' => 'Error'
            ];
        }
    } catch (Exception $e) {
        error_log("WHO Growth Standards error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'nutritional_status' => 'Assessment Error',
            'risk_level' => 'Unknown',
            'category' => 'Error'
        ];
    }
}

/**
 * Save screening history to database
 */
function saveScreeningHistory($user_data, $assessment) {
    try {
        $pdo = getDatabaseConnection();
        
        if (!$pdo) {
            error_log("Database connection failed in saveScreeningHistory");
            return false;
        }
        
        // Calculate age in months
        $birthDate = new DateTime($user_data['birthday']);
        $screeningDate = new DateTime($user_data['screening_date'] ?? date('Y-m-d H:i:s'));
        $age = $birthDate->diff($screeningDate);
        $ageInMonths = ($age->y * 12) + $age->m;
        
        // Prepare base history data
        $historyData = [
            'user_email' => $user_data['email'],
            'screening_date' => $user_data['screening_date'],
            'weight' => $user_data['weight'],
            'height' => $user_data['height'],
            'bmi' => $assessment['results']['bmi'] ?? null,
            'age_months' => $ageInMonths,
            'sex' => $user_data['sex'],
            'nutritional_risk' => $assessment['nutritional_risk'] ?? 'Low'
        ];
        
        // Save multiple classification types if available
        if ($assessment['success'] && isset($assessment['results'])) {
            $growthStandards = $assessment['results'];
            
            // BMI for age
            if (isset($growthStandards['bmi_for_age'])) {
                $bmiData = $growthStandards['bmi_for_age'];
                $stmt = $pdo->prepare("INSERT INTO screening_history (user_email, screening_date, weight, height, bmi, age_months, sex, classification_type, classification, z_score, nutritional_risk) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $historyData['user_email'],
                    $historyData['screening_date'],
                    $historyData['weight'],
                    $historyData['height'],
                    $historyData['bmi'],
                    $historyData['age_months'],
                    $historyData['sex'],
                    'bmi-for-age',
                    $bmiData['classification'] ?? null,
                    $bmiData['z_score'] ?? null,
                    $historyData['nutritional_risk']
                ]);
            }
            
            // Weight for age
            if (isset($growthStandards['weight_for_age'])) {
                $weightAgeData = $growthStandards['weight_for_age'];
                $stmt = $pdo->prepare("INSERT INTO screening_history (user_email, screening_date, weight, height, bmi, age_months, sex, classification_type, classification, z_score, nutritional_risk) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $historyData['user_email'],
                    $historyData['screening_date'],
                    $historyData['weight'],
                    $historyData['height'],
                    $historyData['bmi'],
                    $historyData['age_months'],
                    $historyData['sex'],
                    'weight-for-age',
                    $weightAgeData['classification'] ?? null,
                    $weightAgeData['z_score'] ?? null,
                    $historyData['nutritional_risk']
                ]);
            }
            
            // Height for age
            if (isset($growthStandards['height_for_age'])) {
                $heightAgeData = $growthStandards['height_for_age'];
                $stmt = $pdo->prepare("INSERT INTO screening_history (user_email, screening_date, weight, height, bmi, age_months, sex, classification_type, classification, z_score, nutritional_risk) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $historyData['user_email'],
                    $historyData['screening_date'],
                    $historyData['weight'],
                    $historyData['height'],
                    $historyData['bmi'],
                    $historyData['age_months'],
                    $historyData['sex'],
                    'height-for-age',
                    $heightAgeData['classification'] ?? null,
                    $heightAgeData['z_score'] ?? null,
                    $historyData['nutritional_risk']
                ]);
            }
            
            // Weight for height
            if (isset($growthStandards['weight_for_height'])) {
                $weightHeightData = $growthStandards['weight_for_height'];
                $stmt = $pdo->prepare("INSERT INTO screening_history (user_email, screening_date, weight, height, bmi, age_months, sex, classification_type, classification, z_score, nutritional_risk) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $historyData['user_email'],
                    $historyData['screening_date'],
                    $historyData['weight'],
                    $historyData['height'],
                    $historyData['bmi'],
                    $historyData['age_months'],
                    $historyData['sex'],
                    'weight-for-height',
                    $weightHeightData['classification'] ?? null,
                    $weightHeightData['z_score'] ?? null,
                    $historyData['nutritional_risk']
                ]);
            }
        }
        
        // Save Adult BMI classification if user is an adult (≥19 years or 228 months)
        if ($ageInMonths >= 228) {
            error_log("Auto-screening: User is adult (age: $ageInMonths months), saving Adult BMI classification");
            $bmi = $historyData['bmi'];
            error_log("Auto-screening: BMI value from historyData: " . ($bmi ?? 'null'));
            if ($bmi !== null) {
                $adultClassification = getAdultBMIClassification($bmi);
                error_log("Auto-screening: Adult BMI classification: " . json_encode($adultClassification));
                $stmt = $pdo->prepare("INSERT INTO screening_history (user_email, screening_date, weight, height, bmi, age_months, sex, classification_type, classification, z_score, nutritional_risk) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $result = $stmt->execute([
                    $historyData['user_email'],
                    $historyData['screening_date'],
                    $historyData['weight'],
                    $historyData['height'],
                    $historyData['bmi'],
                    $historyData['age_months'],
                    $historyData['sex'],
                    'bmi-adult',
                    $adultClassification['classification'],
                    $adultClassification['z_score'],
                    $historyData['nutritional_risk']
                ]);
                error_log("Auto-screening: Adult BMI insert result: " . ($result ? 'success' : 'failed'));
            } else {
                error_log("Auto-screening: BMI is null, cannot save Adult BMI classification");
            }
        } else {
            error_log("Auto-screening: User is not adult (age: $ageInMonths months), checking WHO standards");
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error saving screening history: " . $e->getMessage());
        return false;
    }
}

/**
 * Get Adult BMI classification
 */
function getAdultBMIClassification($bmi) {
    if ($bmi < 16.0) {
        return ['classification' => 'Severely Underweight', 'z_score' => -3];
    } elseif ($bmi >= 16.0 && $bmi < 18.5) {
        return ['classification' => 'Underweight', 'z_score' => -2];
    } elseif ($bmi >= 18.5 && $bmi < 25.0) {
        return ['classification' => 'Normal', 'z_score' => 0];
    } elseif ($bmi >= 25.0 && $bmi < 30.0) {
        return ['classification' => 'Overweight', 'z_score' => 1];
    } else {
        return ['classification' => 'Obese', 'z_score' => 2];
    }
}

?>

