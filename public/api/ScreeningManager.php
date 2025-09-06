<?php
/**
 * Dynamic Screening Manager
 * Handles all screening operations with automatic database adaptation
 * NO MORE MANUAL DATABASE CHANGES!
 */

require_once __DIR__ . '/DatabaseAPI.php';

class ScreeningManager {
    private $db;
    private static $instance = null;
    
    public function __construct() {
        $this->db = DatabaseAPI::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * AUTO-CREATE OR UPDATE SCREENING TABLE
     * This method automatically adapts the database structure
     */
    public function ensureScreeningTableExists() {
        try {
            $pdo = $this->db->getPDO();
            if (!$pdo) return false;
            
            // Create comprehensive screening table with all possible fields
            $sql = "CREATE TABLE IF NOT EXISTS comprehensive_screening (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_email VARCHAR(255) NOT NULL,
                user_id INT NULL,
                
                -- Basic Information
                municipality VARCHAR(255) NULL,
                barangay VARCHAR(255) NULL,
                birthdate DATE NULL,
                age INT NULL,
                age_months INT NULL,
                sex ENUM('Male', 'Female') NULL,
                pregnant ENUM('Yes', 'No', 'Not Applicable') DEFAULT 'Not Applicable',
                
                -- Anthropometric Data
                weight DECIMAL(5,2) NULL,
                height DECIMAL(5,2) NULL,
                bmi DECIMAL(4,2) NULL,
                bmi_category VARCHAR(50) NULL,
                muac DECIMAL(4,2) NULL COMMENT 'Mid-Upper Arm Circumference',
                
                -- Meal Assessment (24-hour recall)
                food_carbs BOOLEAN DEFAULT FALSE,
                food_protein BOOLEAN DEFAULT FALSE,
                food_veggies_fruits BOOLEAN DEFAULT FALSE,
                food_dairy BOOLEAN DEFAULT FALSE,
                meal_details TEXT NULL,
                dietary_diversity_score INT DEFAULT 0,
                
                -- Family History
                family_diabetes BOOLEAN DEFAULT FALSE,
                family_hypertension BOOLEAN DEFAULT FALSE,
                family_heart_disease BOOLEAN DEFAULT FALSE,
                family_kidney_disease BOOLEAN DEFAULT FALSE,
                family_tuberculosis BOOLEAN DEFAULT FALSE,
                family_obesity BOOLEAN DEFAULT FALSE,
                family_malnutrition BOOLEAN DEFAULT FALSE,
                family_other TEXT NULL,
                family_none BOOLEAN DEFAULT FALSE,
                
                -- Lifestyle
                lifestyle ENUM('Active', 'Sedentary', 'Moderate', 'Other') NULL,
                lifestyle_other TEXT NULL,
                physical_activity_hours DECIMAL(3,1) NULL,
                smoking BOOLEAN DEFAULT FALSE,
                alcohol BOOLEAN DEFAULT FALSE,
                
                -- Immunization (for children)
                imm_bcg BOOLEAN DEFAULT FALSE,
                imm_dpt BOOLEAN DEFAULT FALSE,
                imm_polio BOOLEAN DEFAULT FALSE,
                imm_measles BOOLEAN DEFAULT FALSE,
                imm_hepatitis BOOLEAN DEFAULT FALSE,
                imm_vitamin_a BOOLEAN DEFAULT FALSE,
                imm_complete BOOLEAN DEFAULT FALSE,
                
                -- Clinical Assessment
                chronic_illness BOOLEAN DEFAULT FALSE,
                chronic_illness_details TEXT NULL,
                medication_use BOOLEAN DEFAULT FALSE,
                medication_details TEXT NULL,
                mental_health_concerns BOOLEAN DEFAULT FALSE,
                disability BOOLEAN DEFAULT FALSE,
                
                -- Physical Signs/Symptoms
                swelling_edema BOOLEAN DEFAULT FALSE,
                recent_weight_loss BOOLEAN DEFAULT FALSE,
                appetite_changes BOOLEAN DEFAULT FALSE,
                fatigue BOOLEAN DEFAULT FALSE,
                weakness BOOLEAN DEFAULT FALSE,
                dizziness BOOLEAN DEFAULT FALSE,
                headache BOOLEAN DEFAULT FALSE,
                abdominal_pain BOOLEAN DEFAULT FALSE,
                nausea BOOLEAN DEFAULT FALSE,
                diarrhea BOOLEAN DEFAULT FALSE,
                dental_problems BOOLEAN DEFAULT FALSE,
                skin_problems BOOLEAN DEFAULT FALSE,
                hair_loss BOOLEAN DEFAULT FALSE,
                nail_changes BOOLEAN DEFAULT FALSE,
                bone_pain BOOLEAN DEFAULT FALSE,
                joint_pain BOOLEAN DEFAULT FALSE,
                muscle_cramps BOOLEAN DEFAULT FALSE,
                physical_signs TEXT NULL,
                
                -- Socioeconomic
                income_level ENUM('Very Low', 'Low', 'Medium', 'High') NULL,
                education_level VARCHAR(100) NULL,
                occupation VARCHAR(100) NULL,
                family_size INT NULL,
                water_source VARCHAR(100) NULL,
                sanitation_facilities VARCHAR(100) NULL,
                
                -- Assessment Results
                risk_score INT DEFAULT 0,
                risk_level ENUM('Low', 'Moderate', 'High', 'Severe') NULL,
                malnutrition_status ENUM('Normal', 'Mild', 'Moderate', 'Severe') NULL,
                assessment_summary TEXT NULL,
                recommendations TEXT NULL,
                intervention_needed BOOLEAN DEFAULT FALSE,
                follow_up_date DATE NULL,
                
                -- Custom/Dynamic Fields (JSON for flexibility)
                custom_data JSON NULL,
                
                -- Metadata
                screening_type VARCHAR(50) DEFAULT 'comprehensive',
                screening_version VARCHAR(10) DEFAULT '1.0',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                created_by VARCHAR(255) NULL,
                
                INDEX idx_user_email (user_email),
                INDEX idx_municipality (municipality),
                INDEX idx_barangay (barangay),
                INDEX idx_risk_score (risk_score),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($sql);
            return true;
            
        } catch (Exception $e) {
            error_log("ScreeningManager: Error creating table: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * SAVE SCREENING DATA (SUPER FLEXIBLE)
     * Accepts any data structure and saves it automatically
     */
    public function saveScreeningData($data) {
        try {
            // Ensure table exists
            $this->ensureScreeningTableExists();
            
            $pdo = $this->db->getPDO();
            if (!$pdo) {
                return ['success' => false, 'message' => 'Database not available'];
            }
            
            // Process the data
            $processedData = $this->processScreeningData($data);
            
            // Check if record exists
            $existingId = $this->getExistingScreeningId($processedData['user_email']);
            
            if ($existingId) {
                // Update existing record
                return $this->updateScreeningRecord($existingId, $processedData);
            } else {
                // Insert new record
                return $this->insertScreeningRecord($processedData);
            }
            
        } catch (Exception $e) {
            error_log("ScreeningManager: Error saving data: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error saving screening data: ' . $e->getMessage()];
        }
    }
    
    /**
     * PROCESS INCOMING DATA (FLEXIBLE)
     * Handles both Android JSON and web form data
     */
    private function processScreeningData($data) {
        $processed = [];
        
        // Handle different input formats
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        
        // Basic information
        $processed['user_email'] = $data['email'] ?? $data['user_email'] ?? '';
        $processed['user_id'] = $data['user_id'] ?? null;
        $processed['municipality'] = $data['municipality'] ?? '';
        $processed['barangay'] = $data['barangay'] ?? '';
        $processed['birthdate'] = $this->parseDate($data['birthdate'] ?? '');
        $processed['age'] = intval($data['age'] ?? 0);
        $processed['age_months'] = intval($data['age_months'] ?? 0);
        $processed['sex'] = $data['sex'] ?? '';
        $processed['pregnant'] = $data['pregnant'] ?? 'Not Applicable';
        
        // Anthropometric data
        $processed['weight'] = floatval($data['weight'] ?? 0);
        $processed['height'] = floatval($data['height'] ?? 0);
        $processed['bmi'] = floatval($data['bmi'] ?? 0);
        $processed['bmi_category'] = $data['bmi_category'] ?? '';
        $processed['muac'] = floatval($data['muac'] ?? 0);
        
        // Food groups (handle both individual booleans and nested objects)
        if (isset($data['food_groups'])) {
            $foodGroups = is_string($data['food_groups']) ? json_decode($data['food_groups'], true) : $data['food_groups'];
            $processed['food_carbs'] = $foodGroups['carbohydrates'] ?? $foodGroups['carbs'] ?? false;
            $processed['food_protein'] = $foodGroups['protein'] ?? false;
            $processed['food_veggies_fruits'] = $foodGroups['vegetables_fruits'] ?? $foodGroups['veggies_fruits'] ?? false;
            $processed['food_dairy'] = $foodGroups['dairy'] ?? false;
        } else {
            $processed['food_carbs'] = $data['food_carbs'] ?? false;
            $processed['food_protein'] = $data['food_protein'] ?? false;
            $processed['food_veggies_fruits'] = $data['food_veggies_fruits'] ?? false;
            $processed['food_dairy'] = $data['food_dairy'] ?? false;
        }
        
        $processed['meal_details'] = $data['meal_recall'] ?? $data['meal_details'] ?? '';
        $processed['dietary_diversity_score'] = $this->calculateDietaryDiversity($processed);
        
        // Family history (handle both individual booleans and arrays)
        if (isset($data['family_history'])) {
            $familyHistory = is_string($data['family_history']) ? json_decode($data['family_history'], true) : $data['family_history'];
            if (is_array($familyHistory)) {
                // Handle array format (from Android)
                $processed['family_diabetes'] = in_array('Diabetes', $familyHistory) || ($familyHistory['diabetes'] ?? false);
                $processed['family_hypertension'] = in_array('Hypertension', $familyHistory) || ($familyHistory['hypertension'] ?? false);
                $processed['family_heart_disease'] = in_array('Heart Disease', $familyHistory) || ($familyHistory['heart_disease'] ?? false);
                $processed['family_kidney_disease'] = in_array('Kidney Disease', $familyHistory) || ($familyHistory['kidney_disease'] ?? false);
                $processed['family_tuberculosis'] = in_array('Tuberculosis', $familyHistory) || ($familyHistory['tuberculosis'] ?? false);
                $processed['family_obesity'] = in_array('Obesity', $familyHistory) || ($familyHistory['obesity'] ?? false);
                $processed['family_malnutrition'] = in_array('Malnutrition', $familyHistory) || ($familyHistory['malnutrition'] ?? false);
                $processed['family_other'] = $familyHistory['other'] ?? '';
                $processed['family_none'] = $familyHistory['none'] ?? false;
            }
        } else {
            // Handle individual boolean fields
            $processed['family_diabetes'] = $data['family_diabetes'] ?? false;
            $processed['family_hypertension'] = $data['family_hypertension'] ?? false;
            $processed['family_heart_disease'] = $data['family_heart_disease'] ?? false;
            $processed['family_kidney_disease'] = $data['family_kidney_disease'] ?? false;
            $processed['family_tuberculosis'] = $data['family_tuberculosis'] ?? false;
            $processed['family_obesity'] = $data['family_obesity'] ?? false;
            $processed['family_malnutrition'] = $data['family_malnutrition'] ?? false;
            $processed['family_other'] = $data['family_other'] ?? '';
            $processed['family_none'] = $data['family_none'] ?? false;
        }
        
        // Lifestyle
        $processed['lifestyle'] = $data['lifestyle'] ?? '';
        $processed['lifestyle_other'] = $data['lifestyle_other'] ?? $data['other_lifestyle'] ?? '';
        
        // Immunization (handle both individual booleans and nested objects)
        if (isset($data['immunization'])) {
            $immunization = is_string($data['immunization']) ? json_decode($data['immunization'], true) : $data['immunization'];
            $processed['imm_bcg'] = $immunization['bcg'] ?? false;
            $processed['imm_dpt'] = $immunization['dpt'] ?? false;
            $processed['imm_polio'] = $immunization['polio'] ?? false;
            $processed['imm_measles'] = $immunization['measles'] ?? false;
            $processed['imm_hepatitis'] = $immunization['hepatitis'] ?? false;
            $processed['imm_vitamin_a'] = $immunization['vitamin_a'] ?? false;
        } else {
            $processed['imm_bcg'] = $data['imm_bcg'] ?? false;
            $processed['imm_dpt'] = $data['imm_dpt'] ?? false;
            $processed['imm_polio'] = $data['imm_polio'] ?? false;
            $processed['imm_measles'] = $data['imm_measles'] ?? false;
            $processed['imm_hepatitis'] = $data['imm_hepatitis'] ?? false;
            $processed['imm_vitamin_a'] = $data['imm_vitamin_a'] ?? false;
        }
        
        // Calculate risk score
        $processed['risk_score'] = $data['risk_score'] ?? $this->calculateRiskScore($processed);
        $processed['risk_level'] = $this->determineRiskLevel($processed['risk_score']);
        $processed['malnutrition_status'] = $this->determineMalnutritionStatus($processed);
        
        // Assessment and recommendations
        $processed['assessment_summary'] = $this->generateAssessmentSummary($processed);
        $processed['recommendations'] = $this->generateRecommendations($processed);
        $processed['intervention_needed'] = $processed['risk_score'] >= 30;
        
        // Store any extra data as JSON
        $customData = [];
        foreach ($data as $key => $value) {
            if (!isset($processed[$key]) && !in_array($key, ['screening_data', 'action'])) {
                $customData[$key] = $value;
            }
        }
        if (!empty($customData)) {
            $processed['custom_data'] = json_encode($customData);
        }
        
        $processed['screening_type'] = 'comprehensive';
        $processed['screening_version'] = '2.0';
        $processed['created_by'] = $data['created_by'] ?? 'system';
        
        return $processed;
    }
    
    /**
     * CALCULATE RISK SCORE (FLEXIBLE)
     */
    private function calculateRiskScore($data) {
        $score = 0;
        
        // BMI scoring
        $bmi = $data['bmi'] ?? 0;
        if ($bmi < 18.5) $score += 25;
        else if ($bmi >= 25 && $bmi < 30) $score += 15;
        else if ($bmi >= 30) $score += 30;
        
        // Age-based scoring
        $age = $data['age'] ?? 0;
        if ($age < 5) $score += 10;
        else if ($age > 65) $score += 8;
        
        // Dietary diversity scoring
        $foodGroups = 0;
        if ($data['food_carbs'] ?? false) $foodGroups++;
        if ($data['food_protein'] ?? false) $foodGroups++;
        if ($data['food_veggies_fruits'] ?? false) $foodGroups++;
        if ($data['food_dairy'] ?? false) $foodGroups++;
        
        if ($foodGroups < 3) $score += 15;
        
        // Family history scoring
        if ($data['family_diabetes'] ?? false) $score += 8;
        if ($data['family_hypertension'] ?? false) $score += 6;
        if ($data['family_heart_disease'] ?? false) $score += 10;
        if ($data['family_kidney_disease'] ?? false) $score += 12;
        if ($data['family_tuberculosis'] ?? false) $score += 7;
        if ($data['family_obesity'] ?? false) $score += 5;
        if ($data['family_malnutrition'] ?? false) $score += 15;
        
        // Lifestyle scoring
        if (($data['lifestyle'] ?? '') === 'Sedentary') $score += 15;
        
        // Immunization scoring (for children)
        if ($age <= 12) {
            $missingVaccines = 0;
            if (!($data['imm_bcg'] ?? false)) $missingVaccines++;
            if (!($data['imm_dpt'] ?? false)) $missingVaccines++;
            if (!($data['imm_polio'] ?? false)) $missingVaccines++;
            if (!($data['imm_measles'] ?? false)) $missingVaccines++;
            if (!($data['imm_hepatitis'] ?? false)) $missingVaccines++;
            if (!($data['imm_vitamin_a'] ?? false)) $missingVaccines++;
            
            $score += $missingVaccines * 2;
        }
        
        return min($score, 100);
    }
    
    /**
     * HELPER METHODS
     */
    private function calculateDietaryDiversity($data) {
        $diversity = 0;
        if ($data['food_carbs'] ?? false) $diversity++;
        if ($data['food_protein'] ?? false) $diversity++;
        if ($data['food_veggies_fruits'] ?? false) $diversity++;
        if ($data['food_dairy'] ?? false) $diversity++;
        return $diversity;
    }
    
    private function determineRiskLevel($score) {
        if ($score < 20) return 'Low';
        if ($score < 50) return 'Moderate';
        if ($score < 80) return 'High';
        return 'Severe';
    }
    
    private function determineMalnutritionStatus($data) {
        $bmi = $data['bmi'] ?? 0;
        $riskScore = $data['risk_score'] ?? 0;
        
        if ($riskScore >= 80 || $bmi < 16) return 'Severe';
        if ($riskScore >= 50 || $bmi < 17) return 'Moderate';
        if ($riskScore >= 30 || $bmi < 18.5) return 'Mild';
        return 'Normal';
    }
    
    private function generateAssessmentSummary($data) {
        $age = $data['age'] ?? 0;
        $bmi = $data['bmi'] ?? 0;
        $riskScore = $data['risk_score'] ?? 0;
        $riskLevel = $data['risk_level'] ?? 'Low';
        
        return "Comprehensive nutrition screening for {$age}-year-old {$data['sex']}. BMI: {$bmi}, Risk Score: {$riskScore}/100 ({$riskLevel} risk).";
    }
    
    private function generateRecommendations($data) {
        $recommendations = [];
        $riskScore = $data['risk_score'] ?? 0;
        $bmi = $data['bmi'] ?? 0;
        
        if ($riskScore >= 80) {
            $recommendations[] = "URGENT: Immediate medical attention required for severe malnutrition risk.";
        }
        
        if ($bmi < 18.5) {
            $recommendations[] = "Increase caloric intake with nutrient-dense foods.";
        } elseif ($bmi >= 25) {
            $recommendations[] = "Focus on weight management through balanced diet and exercise.";
        }
        
        if (($data['food_carbs'] ?? false) + ($data['food_protein'] ?? false) + ($data['food_veggies_fruits'] ?? false) + ($data['food_dairy'] ?? false) < 3) {
            $recommendations[] = "Improve dietary diversity by including all food groups.";
        }
        
        if (($data['lifestyle'] ?? '') === 'Sedentary') {
            $recommendations[] = "Increase physical activity to at least 30 minutes daily.";
        }
        
        return implode(' ', $recommendations);
    }
    
    private function parseDate($dateString) {
        if (empty($dateString)) return null;
        
        try {
            // Handle various date formats
            $formats = ['Y-m-d', 'M d, Y', 'F d, Y', 'd/m/Y', 'm/d/Y'];
            
            foreach ($formats as $format) {
                $date = DateTime::createFromFormat($format, $dateString);
                if ($date) {
                    return $date->format('Y-m-d');
                }
            }
            
            // Try strtotime as fallback
            $timestamp = strtotime($dateString);
            if ($timestamp) {
                return date('Y-m-d', $timestamp);
            }
            
        } catch (Exception $e) {
            error_log("ScreeningManager: Error parsing date '$dateString': " . $e->getMessage());
        }
        
        return null;
    }
    
    private function getExistingScreeningId($userEmail) {
        try {
            $pdo = $this->db->getPDO();
            $stmt = $pdo->prepare("SELECT id FROM comprehensive_screening WHERE user_email = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$userEmail]);
            $result = $stmt->fetch();
            return $result ? $result['id'] : null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    private function insertScreeningRecord($data) {
        try {
            $pdo = $this->db->getPDO();
            
            // Build dynamic INSERT query
            $columns = array_keys($data);
            $placeholders = str_repeat('?,', count($columns) - 1) . '?';
            $sql = "INSERT INTO comprehensive_screening (" . implode(',', $columns) . ") VALUES ($placeholders)";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute(array_values($data));
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Screening data saved successfully',
                    'screening_id' => $pdo->lastInsertId(),
                    'risk_score' => $data['risk_score'],
                    'risk_level' => $data['risk_level']
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to save screening data'];
            }
            
        } catch (Exception $e) {
            error_log("ScreeningManager: Insert error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    private function updateScreeningRecord($id, $data) {
        try {
            $pdo = $this->db->getPDO();
            
            // Build dynamic UPDATE query
            $setParts = [];
            $values = [];
            foreach ($data as $column => $value) {
                if ($column !== 'id') {
                    $setParts[] = "$column = ?";
                    $values[] = $value;
                }
            }
            $values[] = $id;
            
            $sql = "UPDATE comprehensive_screening SET " . implode(', ', $setParts) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($values);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Screening data updated successfully',
                    'screening_id' => $id,
                    'risk_score' => $data['risk_score'],
                    'risk_level' => $data['risk_level']
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to update screening data'];
            }
            
        } catch (Exception $e) {
            error_log("ScreeningManager: Update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * GET SCREENING DATA
     */
    public function getScreeningData($userEmail = null, $limit = 100) {
        try {
            $this->ensureScreeningTableExists();
            
            $pdo = $this->db->getPDO();
            if (!$pdo) return [];
            
            if ($userEmail) {
                $stmt = $pdo->prepare("SELECT * FROM comprehensive_screening WHERE user_email = ? ORDER BY created_at DESC LIMIT ?");
                $stmt->execute([$userEmail, $limit]);
            } else {
                $stmt = $pdo->prepare("SELECT * FROM comprehensive_screening ORDER BY created_at DESC LIMIT ?");
                $stmt->execute([$limit]);
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("ScreeningManager: Error getting data: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * GET SCREENING STATISTICS
     */
    public function getScreeningStats($barangay = '') {
        try {
            $this->ensureScreeningTableExists();
            
            $pdo = $this->db->getPDO();
            if (!$pdo) return [];
            
            $whereClause = $barangay ? "WHERE barangay = ?" : "";
            $params = $barangay ? [$barangay] : [];
            
            $sql = "SELECT 
                COUNT(*) as total_screenings,
                AVG(risk_score) as avg_risk_score,
                SUM(CASE WHEN risk_score >= 80 THEN 1 ELSE 0 END) as severe_risk,
                SUM(CASE WHEN risk_score >= 50 AND risk_score < 80 THEN 1 ELSE 0 END) as high_risk,
                SUM(CASE WHEN risk_score >= 30 AND risk_score < 50 THEN 1 ELSE 0 END) as moderate_risk,
                SUM(CASE WHEN risk_score < 30 THEN 1 ELSE 0 END) as low_risk,
                SUM(CASE WHEN intervention_needed = 1 THEN 1 ELSE 0 END) as intervention_needed
                FROM comprehensive_screening $whereClause";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("ScreeningManager: Error getting stats: " . $e->getMessage());
            return [];
        }
    }
}

// Global function for easy access
function getScreeningManager() {
    return ScreeningManager::getInstance();
}

?>
