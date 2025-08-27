<?php
/**
 * Unified API for Nutrisaur Dashboard - Direct File
 * This file exists directly in public/ so Railway can find it
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON content type
header('Content-Type: application/json');

// Check if user is logged in
// Temporarily disabled for testing
/*
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
*/

// Database connection - Use the same working approach
$mysql_host = 'mainline.proxy.rlwy.net';
$mysql_port = 26063;
$mysql_user = 'root';
$mysql_password = 'nZhQwfTnAJfFieCpIclAMtOQbBxcjwgy';
$mysql_database = 'railway';

// If MYSQL_PUBLIC_URL is set (Railway sets this), parse it
if (isset($_ENV['MYSQL_PUBLIC_URL'])) {
    $mysql_url = $_ENV['MYSQL_PUBLIC_URL'];
    $pattern = '/mysql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/(.+)/';
    if (preg_match($pattern, $mysql_url, $matches)) {
        $mysql_user = $matches[1];
        $mysql_password = $matches[2];
        $mysql_host = $matches[3];
        $mysql_port = $matches[4];
        $mysql_database = $matches[5];
    }
}

try {
    // Create database connection
    $dsn = "mysql:host={$mysql_host};port={$mysql_port};dbname={$mysql_database};charset=utf8mb4";
    $pdo = new PDO($dsn, $mysql_user, $mysql_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Get the endpoint from query parameters
$endpoint = $_GET['endpoint'] ?? '';

// Route to appropriate handler
switch ($endpoint) {
    case 'community_metrics':
        getCommunityMetrics($pdo);
        break;
        
    case 'risk_distribution':
        getRiskDistribution($pdo);
        break;
        
    case 'screening_responses':
        getScreeningResponses($pdo);
        break;
        
    case 'geographic_distribution':
        getGeographicDistribution($pdo);
        break;
        
    case 'critical_alerts':
        getCriticalAlerts($pdo);
        break;
        
    case 'time_frame_data':
        getTimeFrameData($pdo);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid endpoint']);
        break;
}

function getCommunityMetrics($pdo) {
    try {
        // Get basic counts
        $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
        $totalUsers = $stmt->fetch()['total_users'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total_screenings FROM user_preferences");
        $totalScreenings = $stmt->fetch()['total_screenings'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as high_risk FROM user_preferences WHERE risk_score > 70");
        $highRisk = $stmt->fetch()['high_risk'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as moderate_risk FROM user_preferences WHERE risk_score BETWEEN 30 AND 70");
        $moderateRisk = $stmt->fetch()['moderate_risk'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as low_risk FROM user_preferences WHERE risk_score < 30");
        $lowRisk = $stmt->fetch()['low_risk'];
        
        // Get recent activity
        $stmt = $pdo->query("SELECT COUNT(*) as recent_screenings FROM user_preferences WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $recentScreenings = $stmt->fetch()['recent_screenings'];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'total_users' => $totalUsers,
                'total_screenings' => $totalScreenings,
                'risk_distribution' => [
                    'high' => $highRisk,
                    'moderate' => $moderateRisk,
                    'low' => $lowRisk
                ],
                'recent_activity' => [
                    'screenings_this_week' => $recentScreenings
                ]
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getRiskDistribution($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT 
                CASE 
                    WHEN risk_score < 30 THEN 'Low'
                    WHEN risk_score BETWEEN 30 AND 70 THEN 'Moderate'
                    ELSE 'High'
                END as risk_level,
                COUNT(*) as count
            FROM user_preferences 
            GROUP BY risk_level
            ORDER BY risk_score
        ");
        
        $distribution = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $distribution]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getScreeningResponses($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as count
            FROM user_preferences 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        
        $responses = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $responses]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getGeographicDistribution($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT 
                barangay,
                COUNT(*) as count
            FROM users 
            GROUP BY barangay 
            ORDER BY count DESC
        ");
        
        $distribution = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $distribution]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getCriticalAlerts($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT 
                u.email,
                u.barangay,
                up.risk_score,
                up.created_at
            FROM users u
            JOIN user_preferences up ON u.email = up.user_email
            WHERE up.risk_score > 80
            ORDER BY up.risk_score DESC
            LIMIT 10
        ");
        
        $alerts = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $alerts]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getTimeFrameData($pdo) {
    try {
        $timeFrame = $_GET['time_frame'] ?? '7d';
        $barangay = $_GET['barangay'] ?? null;
        
        $whereClause = "";
        $params = [];
        
        if ($barangay) {
            $whereClause = "WHERE u.barangay = ?";
            $params[] = $barangay;
        }
        
        switch ($timeFrame) {
            case '7d':
                $interval = 'INTERVAL 7 DAY';
                break;
            case '30d':
                $interval = 'INTERVAL 30 DAY';
                break;
            case '90d':
                $interval = 'INTERVAL 90 DAY';
                break;
            default:
                $interval = 'INTERVAL 7 DAY';
        }
        
        $sql = "
            SELECT 
                DATE(up.created_at) as date,
                COUNT(*) as count
            FROM user_preferences up
            JOIN users u ON up.user_email = u.email
            $whereClause
            AND up.created_at >= DATE_SUB(NOW(), $interval)
            GROUP BY DATE(up.created_at)
            ORDER BY date DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>
