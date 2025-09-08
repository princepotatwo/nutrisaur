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

class CommunityUsersSimpleAPI {
    private $db;
    
    public function __construct() {
        $this->db = DatabaseAPI::getInstance();
    }
    
    /**
     * Save community user screening data (simplified)
     */
    public function saveScreeningData($data) {
        try {
            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Prepare data for insertion
            $insertData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $hashedPassword,
                'municipality' => $data['municipality'],
                'barangay' => $data['barangay'],
                'sex' => $data['sex'],
                'birthday' => $data['birthday'],
                'is_pregnant' => $data['is_pregnant'] ?? null,
                'weight' => $data['weight'],
                'height' => $data['height'],
                'muac' => $data['muac']
            ];
            
            $result = $this->db->insert('community_users', $insertData);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Screening data saved successfully',
                    'data' => [
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'municipality' => $data['municipality'],
                        'barangay' => $data['barangay']
                    ]
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
            $where = [];
            
            if ($municipality) {
                $where['municipality'] = $municipality;
            }
            
            if ($barangay) {
                $where['barangay'] = $barangay;
            }
            
            $users = $this->db->select('community_users', $where, '*', $limit, $offset, 'screening_date DESC');
            
            // Remove password from response
            foreach ($users as &$user) {
                unset($user['password']);
            }
            
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
     * Get screening statistics
     */
    public function getScreeningStats($municipality = null, $barangay = null) {
        try {
            $where = [];
            
            if ($municipality) {
                $where['municipality'] = $municipality;
            }
            
            if ($barangay) {
                $where['barangay'] = $barangay;
            }
            
            $users = $this->db->select('community_users', $where);
            
            $stats = [
                'total_screened' => count($users),
                'municipalities' => [],
                'barangays' => [],
                'gender_distribution' => [],
                'pregnancy_status' => []
            ];
            
            foreach ($users as $user) {
                // Municipalities
                $mun = $user['municipality'];
                $stats['municipalities'][$mun] = ($stats['municipalities'][$mun] ?? 0) + 1;
                
                // Barangays
                $brgy = $user['barangay'];
                $stats['barangays'][$brgy] = ($stats['barangays'][$brgy] ?? 0) + 1;
                
                // Gender distribution
                $gender = $user['sex'];
                $stats['gender_distribution'][$gender] = ($stats['gender_distribution'][$gender] ?? 0) + 1;
                
                // Pregnancy status
                $pregnant = $user['is_pregnant'] ?? 'N/A';
                $stats['pregnancy_status'][$pregnant] = ($stats['pregnancy_status'][$pregnant] ?? 0) + 1;
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
     * Login community user
     */
    public function loginUser($email, $password) {
        try {
            $users = $this->db->select('community_users', ['email' => $email]);
            
            if (empty($users)) {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }
            
            $user = $users[0];
            
            if (password_verify($password, $user['password'])) {
                // Remove password from response
                unset($user['password']);
                
                return [
                    'success' => true,
                    'message' => 'Login successful',
                    'user' => $user
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Invalid password'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
}

// Handle API requests
$api = new CommunityUsersSimpleAPI();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'POST':
        if ($action === 'save_screening') {
            $input = json_decode(file_get_contents('php://input'), true);
            echo json_encode($api->saveScreeningData($input));
        } elseif ($action === 'login') {
            $input = json_decode(file_get_contents('php://input'), true);
            echo json_encode($api->loginUser($input['email'], $input['password']));
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
            
            echo json_encode($api->getScreeningStats($municipality, $barangay));
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
?>
