<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Headers are now set by the router
session_start();

try {
    // Use the same working approach as other API files
    require_once __DIR__ . "/../../config.php";
    $pdo = getDatabaseConnection();
    
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    // Get action parameter
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            // Get all community users
            $stmt = $pdo->prepare("SELECT * FROM community_users ORDER BY screening_date DESC");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'count' => count($users),
                'data' => $users
            ], JSON_PRETTY_PRINT);
            break;
            
        case 'stats':
            // Get statistics
            $stmt = $pdo->prepare("SELECT * FROM community_users");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
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
            
            echo json_encode([
                'success' => true,
                'data' => $stats
            ], JSON_PRETTY_PRINT);
            break;
            
        case 'test':
            // Simple test to check if table exists
            $stmt = $pdo->prepare("SELECT * FROM community_users ORDER BY screening_date DESC LIMIT 5");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'message' => 'Community users table is working!',
                'table_exists' => true,
                'count' => count($users),
                'sample_data' => $users
            ], JSON_PRETTY_PRINT);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action. Use: list, stats, or test'
            ], JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'table_exists' => false
    ], JSON_PRETTY_PRINT);
}
?>
