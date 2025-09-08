<?php
require_once 'config.php';

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $db = DatabaseAPI::getInstance();
    
    // Get action parameter
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            // Get all community users
            $users = $db->select('community_users', [], '*', 100, 0, 'screening_date DESC');
            
            $response = [
                'success' => true,
                'count' => count($users),
                'data' => $users
            ];
            break;
            
        case 'stats':
            // Get statistics
            $users = $db->select('community_users', []);
            
            $stats = [
                'total_users' => count($users),
                'municipalities' => [],
                'barangays' => [],
                'gender_distribution' => [],
                'pregnancy_status' => [],
                'recent_screenings' => 0
            ];
            
            $recentDate = date('Y-m-d', strtotime('-7 days'));
            
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
                
                // Recent screenings (last 7 days)
                if ($user['screening_date'] >= $recentDate) {
                    $stats['recent_screenings']++;
                }
            }
            
            $response = [
                'success' => true,
                'data' => $stats
            ];
            break;
            
        case 'by_municipality':
            // Get users by municipality
            $municipality = $_GET['municipality'] ?? '';
            if (empty($municipality)) {
                throw new Exception('Municipality parameter is required');
            }
            
            $users = $db->select('community_users', ['municipality' => $municipality]);
            
            $response = [
                'success' => true,
                'municipality' => $municipality,
                'count' => count($users),
                'data' => $users
            ];
            break;
            
        case 'by_barangay':
            // Get users by barangay
            $barangay = $_GET['barangay'] ?? '';
            if (empty($barangay)) {
                throw new Exception('Barangay parameter is required');
            }
            
            $users = $db->select('community_users', ['barangay' => $barangay]);
            
            $response = [
                'success' => true,
                'barangay' => $barangay,
                'count' => count($users),
                'data' => $users
            ];
            break;
            
        case 'search':
            // Search users by name or email
            $query = $_GET['q'] ?? '';
            if (empty($query)) {
                throw new Exception('Search query is required');
            }
            
            $users = $db->select('community_users', [], '*', 50, 0, 'screening_date DESC');
            $filteredUsers = array_filter($users, function($user) use ($query) {
                return stripos($user['name'], $query) !== false || 
                       stripos($user['email'], $query) !== false;
            });
            
            $response = [
                'success' => true,
                'query' => $query,
                'count' => count($filteredUsers),
                'data' => array_values($filteredUsers)
            ];
            break;
            
        case 'export':
            // Export data as CSV
            $users = $db->select('community_users', [], '*', 1000, 0, 'screening_date DESC');
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="community_users_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($output, [
                'Name', 'Email', 'Municipality', 'Barangay', 'Sex', 'Birthday', 
                'Is Pregnant', 'Weight (kg)', 'Height (cm)', 'MUAC (cm)', 'Screening Date'
            ]);
            
            // CSV data
            foreach ($users as $user) {
                fputcsv($output, [
                    $user['name'],
                    $user['email'],
                    $user['municipality'],
                    $user['barangay'],
                    $user['sex'],
                    $user['birthday'],
                    $user['is_pregnant'] ?? 'N/A',
                    $user['weight'],
                    $user['height'],
                    $user['muac'],
                    $user['screening_date']
                ]);
            }
            
            fclose($output);
            exit();
            
        default:
            throw new Exception('Invalid action');
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
