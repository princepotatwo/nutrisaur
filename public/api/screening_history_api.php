<?php
/**
 * Screening History API
 * Provides endpoints for fetching screening history data for progress tracking
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1); // Show errors for debugging
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
    require_once __DIR__ . '/../../config.php';
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
    
    // Debug: Log that we got here
    error_log("Screening history API: Database connection successful");
    
    // Get action from request
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $user_email = $_GET['user_email'] ?? $_POST['user_email'] ?? '';
    $limit = max(1, intval($_GET['limit'] ?? $_POST['limit'] ?? 10)); // Ensure minimum 1 and integer
    $classification_type = $_GET['classification_type'] ?? $_POST['classification_type'] ?? 'bmi-for-age';
    
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
                LIMIT " . intval($limit)
            );
            
            $stmt->execute([$user_email]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Debug: Log the number of records found
            error_log("Screening history API: Found " . count($history) . " records for user: $user_email");
            error_log("Screening history API: Limit value: $limit");
            
            // Group data by date and filter for selected WHO standard
            $groupedData = [];
            foreach ($history as $record) {
                $date = $record['screening_date'];
                
                // Filter by selected classification type
                if ($record['classification_type'] === $classification_type) {
                    if (!isset($groupedData[$date])) {
                        $groupedData[$date] = $record;
                    }
                }
            }
            
            // Sort by date (oldest first for better timeline visualization)
            ksort($groupedData);
            
            // Format data for Chart.js - dynamic based on selected standard
            $chartData = [
                'labels' => [],
                'datasets' => [
                    [
                        'label' => strtoupper(str_replace('-', ' ', $classification_type)),
                        'data' => [],
                        'borderColor' => '#8CA86E',
                        'backgroundColor' => 'rgba(140, 168, 110, 0.2)',
                        'tension' => 0.4,
                        'fill' => true,
                        'pointBackgroundColor' => '#8CA86E',
                        'pointBorderColor' => '#6B8E4A',
                        'pointBorderWidth' => 2,
                        'pointRadius' => 6,
                        'pointHoverRadius' => 8
                    ]
                ]
            ];
            
            $tableData = [];
            $classifications = [];
            
            foreach ($groupedData as $record) {
                $date = new DateTime($record['screening_date']);
                $dateLabel = $date->format('M j, Y');
                
                // Calculate fresh classification for BMI Adult
                $freshClassification = $record['classification']; // Default to stored
                if ($classification_type === 'bmi-adult') {
                    $bmi = floatval($record['bmi']);
                    if ($bmi < 16.0) {
                        $freshClassification = 'Severely Underweight';
                    } else if ($bmi < 18.5) {
                        $freshClassification = 'Underweight';
                    } else if ($bmi < 25) {
                        $freshClassification = 'Normal';
                    } else if ($bmi < 30) {
                        $freshClassification = 'Overweight';
                    } else {
                        $freshClassification = 'Obese';
                    }
                }
                
                // Add to chart data - choose appropriate metric based on standard
                $yValue = floatval($record['bmi']); // Default to BMI
                if ($classification_type === 'weight-for-age' || $classification_type === 'weight-for-height') {
                    $yValue = floatval($record['weight']);
                } elseif ($classification_type === 'height-for-age') {
                    $yValue = floatval($record['height']);
                }
                
                $chartData['labels'][] = $dateLabel;
                $chartData['datasets'][0]['data'][] = [
                    'x' => $dateLabel,
                    'y' => $yValue,
                    'classification' => $freshClassification
                ];
                
                // Add to table data (simplified)
                $tableData[] = [
                    'date' => $dateLabel,
                    'weight' => $record['weight'],
                    'bmi' => $record['bmi'],
                    'classification' => $freshClassification
                ];
                
                // Track classifications for legend
                if ($freshClassification) {
                    $classifications[$freshClassification] = getClassificationColor($freshClassification);
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
