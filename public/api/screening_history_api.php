<?php
/**
 * Screening History API
 * Provides endpoints for fetching screening history data for progress tracking
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once __DIR__ . '/../config.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Config file not found: ' . $e->getMessage()
    ]);
    exit();
}

try {
    // Get database connection
    $pdo = getDatabaseConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Get action from request
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $user_email = $_GET['user_email'] ?? $_POST['user_email'] ?? '';
    $limit = intval($_GET['limit'] ?? $_POST['limit'] ?? 10);
    
    // Validate required parameters
    if (empty($user_email)) {
        throw new Exception('user_email parameter is required');
    }
    
    switch ($action) {
        case 'get_history':
            // Fetch screening history for a user
            $stmt = $pdo->prepare("
                SELECT 
                    screening_date,
                    weight,
                    height,
                    bmi,
                    age_months,
                    sex,
                    classification_type,
                    classification,
                    z_score,
                    nutritional_risk,
                    created_at
                FROM screening_history 
                WHERE user_email = ? 
                ORDER BY screening_date DESC 
                LIMIT ?
            ");
            
            $stmt->execute([$user_email, $limit]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Debug: Log the number of records found
            error_log("Screening history API: Found " . count($history) . " records for user: $user_email");
            
            // Format data for Chart.js
            $chartData = [
                'labels' => [],
                'datasets' => [
                    [
                        'label' => 'Weight (kg)',
                        'data' => [],
                        'borderColor' => '#8CA86E',
                        'backgroundColor' => 'rgba(140, 168, 110, 0.1)',
                        'yAxisID' => 'y'
                    ],
                    [
                        'label' => 'Height (cm)',
                        'data' => [],
                        'borderColor' => '#A1B454',
                        'backgroundColor' => 'rgba(161, 180, 84, 0.1)',
                        'yAxisID' => 'y'
                    ],
                    [
                        'label' => 'BMI',
                        'data' => [],
                        'borderColor' => '#B5C88D',
                        'backgroundColor' => 'rgba(181, 200, 141, 0.1)',
                        'yAxisID' => 'y1'
                    ]
                ]
            ];
            
            $tableData = [];
            $classifications = [];
            
            foreach ($history as $record) {
                $date = new DateTime($record['screening_date']);
                $dateLabel = $date->format('M j, Y');
                
                // Add to chart data
                $chartData['labels'][] = $dateLabel;
                $chartData['datasets'][0]['data'][] = floatval($record['weight']);
                $chartData['datasets'][1]['data'][] = floatval($record['height']);
                $chartData['datasets'][2]['data'][] = floatval($record['bmi']);
                
                // Add to table data
                $tableData[] = [
                    'date' => $dateLabel,
                    'weight' => $record['weight'],
                    'height' => $record['height'],
                    'bmi' => $record['bmi'],
                    'classification_type' => $record['classification_type'],
                    'classification' => $record['classification'],
                    'z_score' => $record['z_score'],
                    'nutritional_risk' => $record['nutritional_risk']
                ];
                
                // Track classifications for legend
                if ($record['classification']) {
                    $classifications[$record['classification']] = getClassificationColor($record['classification']);
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'chart' => $chartData,
                    'table' => $tableData,
                    'classifications' => $classifications,
                    'total_records' => count($history)
                ]
            ]);
            break;
            
        case 'get_user_count':
            // Get count of screening records for a user
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM screening_history WHERE user_email = ?");
            $stmt->execute([$user_email]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'count' => intval($result['count'])
            ]);
            break;
            
        default:
            throw new Exception('Invalid action. Supported actions: get_history, get_user_count');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Get color for classification
 */
function getClassificationColor($classification) {
    $colors = [
        // Red for severe conditions
        'Severely Underweight' => '#CF8686',
        'Severely Stunted' => '#CF8686',
        'Severely Wasted' => '#CF8686',
        'Obese' => '#CF8686',
        
        // Orange for moderate conditions
        'Underweight' => '#E0C989',
        'Stunted' => '#E0C989',
        'Wasted' => '#E0C989',
        'Overweight' => '#E0C989',
        
        // Green for normal
        'Normal' => '#8CA86E',
        
        // Blue for tall
        'Tall' => '#6FA8DC'
    ];
    
    return $colors[$classification] ?? '#8CA86E'; // Default to green
}
?>
