<?php
require_once '../config.php';

try {
    $db = DatabaseAPI::getInstance();
    
    // Get action parameter
    $action = $_GET['action'] ?? 'test';
    
    switch ($action) {
        case 'test':
            // Simple test to check if table exists
            $users = $db->select('community_users', [], '*', 5, 0, 'screening_date DESC');
            
            $response = [
                'success' => true,
                'message' => 'Community users table is working!',
                'table_exists' => true,
                'count' => count($users),
                'sample_data' => $users
            ];
            break;
            
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
            
        default:
            throw new Exception('Invalid action');
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'table_exists' => false
    ], JSON_PRETTY_PRINT);
}
?>
