<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

class CommunityUsersAPI {
    private $db;
    
    public function __construct() {
        $this->db = DatabaseAPI::getInstance();
    }
    
    /**
     * Save community user screening data
     */
    public function saveScreeningData($data) {
        try {
            // Generate unique screening ID
            $screeningId = 'SCR-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Calculate age from birthday
            $birthday = new DateTime($data['birthday']);
            $today = new DateTime();
            $age = $today->diff($birthday)->y;
            
            // Calculate BMI
            $heightM = $data['height_cm'] / 100;
            $bmi = $data['weight_kg'] / ($heightM * $heightM);
            $bmiCategory = $this->getBMICategory($bmi);
            
            // Determine MUAC category
            $muacCategory = $this->getMUACCategory($data['muac_cm'], $data['sex'], $age);
            
            // Determine nutritional risk
            $nutritionalRisk = $this->getNutritionalRisk($bmi, $data['muac_cm'], $data['sex'], $age);
            
            // Prepare data for insertion
            $insertData = [
                'screening_id' => $screeningId,
                'user_id' => $data['user_id'] ?? null,
                'municipality' => $data['municipality'],
                'barangay' => $data['barangay'],
                'sex' => $data['sex'],
                'birthday' => $data['birthday'],
                'age' => $age,
                'is_pregnant' => $data['is_pregnant'] ?? null,
                'weight_kg' => $data['weight_kg'],
                'height_cm' => $data['height_cm'],
                'muac_cm' => $data['muac_cm'],
                'bmi' => round($bmi, 1),
                'bmi_category' => $bmiCategory,
                'muac_category' => $muacCategory,
                'nutritional_risk' => $nutritionalRisk,
                'screened_by' => $data['screened_by'] ?? null,
                'notes' => $data['notes'] ?? null,
                'follow_up_required' => $this->requiresFollowUp($nutritionalRisk, $bmi, $data['muac_cm']),
                'follow_up_date' => $this->getFollowUpDate($nutritionalRisk)
            ];
            
            $result = $this->db->insert('community_users', $insertData);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Screening data saved successfully',
                    'screening_id' => $screeningId,
                    'data' => $insertData
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to save screening data'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get community users by municipality and barangay
     */
    public function getCommunityUsers($municipality = null, $barangay = null, $limit = 50, $offset = 0) {
        try {
            $where = ['status' => 'active'];
            
            if ($municipality) {
                $where['municipality'] = $municipality;
            }
            
            if ($barangay) {
                $where['barangay'] = $barangay;
            }
            
            $users = $this->db->select('community_users', $where, '*', $limit, $offset, 'screening_date DESC');
            
            return [
                'success' => true,
                'data' => $users,
                'count' => count($users)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get nutritional statistics by area
     */
    public function getNutritionalStats($municipality = null, $barangay = null) {
        try {
            $where = ['status' => 'active'];
            
            if ($municipality) {
                $where['municipality'] = $municipality;
            }
            
            if ($barangay) {
                $where['barangay'] = $barangay;
            }
            
            $users = $this->db->select('community_users', $where);
            
            $stats = [
                'total_screened' => count($users),
                'bmi_categories' => [],
                'muac_categories' => [],
                'nutritional_risk' => [],
                'age_groups' => [],
                'gender_distribution' => []
            ];
            
            foreach ($users as $user) {
                // BMI categories
                $bmiCat = $user['bmi_category'] ?? 'Unknown';
                $stats['bmi_categories'][$bmiCat] = ($stats['bmi_categories'][$bmiCat] ?? 0) + 1;
                
                // MUAC categories
                $muacCat = $user['muac_category'] ?? 'Unknown';
                $stats['muac_categories'][$muacCat] = ($stats['muac_categories'][$muacCat] ?? 0) + 1;
                
                // Nutritional risk
                $risk = $user['nutritional_risk'] ?? 'Unknown';
                $stats['nutritional_risk'][$risk] = ($stats['nutritional_risk'][$risk] ?? 0) + 1;
                
                // Age groups
                $ageGroup = $this->getAgeGroup($user['age']);
                $stats['age_groups'][$ageGroup] = ($stats['age_groups'][$ageGroup] ?? 0) + 1;
                
                // Gender distribution
                $gender = $user['sex'];
                $stats['gender_distribution'][$gender] = ($stats['gender_distribution'][$gender] ?? 0) + 1;
            }
            
            return [
                'success' => true,
                'data' => $stats
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get BMI category based on BMI value
     */
    private function getBMICategory($bmi) {
        if ($bmi < 18.5) return 'Underweight';
        if ($bmi < 25) return 'Normal';
        if ($bmi < 30) return 'Overweight';
        return 'Obese';
    }
    
    /**
     * Get MUAC category based on MUAC, sex, and age
     */
    private function getMUACCategory($muac, $sex, $age) {
        // WHO MUAC cutoffs for adults
        if ($sex === 'Female') {
            if ($muac < 19.0) return 'Severe';
            if ($muac < 22.0) return 'Moderate';
            return 'Normal';
        } else {
            if ($muac < 20.0) return 'Severe';
            if ($muac < 23.0) return 'Moderate';
            return 'Normal';
        }
    }
    
    /**
     * Determine nutritional risk level
     */
    private function getNutritionalRisk($bmi, $muac, $sex, $age) {
        $bmiCategory = $this->getBMICategory($bmi);
        $muacCategory = $this->getMUACCategory($muac, $sex, $age);
        
        if ($bmiCategory === 'Underweight' && $muacCategory === 'Severe') {
            return 'Severe';
        }
        if ($bmiCategory === 'Underweight' || $muacCategory === 'Moderate') {
            return 'High';
        }
        if ($bmiCategory === 'Normal' && $muacCategory === 'Normal') {
            return 'Low';
        }
        return 'Moderate';
    }
    
    /**
     * Check if follow-up is required
     */
    private function requiresFollowUp($nutritionalRisk, $bmi, $muac) {
        return $nutritionalRisk === 'High' || $nutritionalRisk === 'Severe' || 
               $bmi < 18.5 || $muac < 22.0;
    }
    
    /**
     * Get follow-up date based on risk level
     */
    private function getFollowUpDate($nutritionalRisk) {
        $days = 30; // Default 30 days
        
        switch ($nutritionalRisk) {
            case 'Severe':
                $days = 7; // 1 week
                break;
            case 'High':
                $days = 14; // 2 weeks
                break;
            case 'Moderate':
                $days = 30; // 1 month
                break;
            default:
                return null; // No follow-up needed
        }
        
        return date('Y-m-d', strtotime("+{$days} days"));
    }
    
    /**
     * Get age group for statistics
     */
    private function getAgeGroup($age) {
        if ($age < 18) return 'Under 18';
        if ($age < 30) return '18-29';
        if ($age < 45) return '30-44';
        if ($age < 60) return '45-59';
        return '60+';
    }
}

// Handle API requests
$api = new CommunityUsersAPI();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'POST':
        if ($action === 'save_screening') {
            $input = json_decode(file_get_contents('php://input'), true);
            echo json_encode($api->saveScreeningData($input));
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        break;
        
    case 'GET':
        if ($action === 'get_users') {
            $municipality = $_GET['municipality'] ?? null;
            $barangay = $_GET['barangay'] ?? null;
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);
            
            echo json_encode($api->getCommunityUsers($municipality, $barangay, $limit, $offset));
        } elseif ($action === 'get_stats') {
            $municipality = $_GET['municipality'] ?? null;
            $barangay = $_GET['barangay'] ?? null;
            
            echo json_encode($api->getNutritionalStats($municipality, $barangay));
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
?>
