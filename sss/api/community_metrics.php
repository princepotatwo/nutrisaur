<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database connection
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
    $dsn = "mysql:host={$mysql_host};port={$mysql_port};dbname={$mysql_database};charset=utf8mb4";
    $conn = new PDO($dsn, $mysql_user, $mysql_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

try {
    $barangay = $_GET['barangay'] ?? '';
    
    $whereClause = "";
    $params = [];
    
    if ($barangay && $barangay !== '') {
        if (strpos($barangay, 'MUNICIPALITY_') === 0) {
            $municipality = str_replace('MUNICIPALITY_', '', $barangay);
            $whereClause = "WHERE up.barangay LIKE ?";
            $params = ["%$municipality%"];
        } else {
            $whereClause = "WHERE up.barangay = ?";
            $params = [$barangay];
        }
    }
    
    // Get community metrics
    $sql = "
        SELECT 
            COUNT(*) as total_screenings,
            AVG(up.risk_score) as avg_risk_score,
            SUM(CASE WHEN up.risk_score >= 70 THEN 1 ELSE 0 END) as high_risk,
            SUM(CASE WHEN up.risk_score >= 30 AND up.risk_score < 70 THEN 1 ELSE 0 END) as moderate_risk,
            SUM(CASE WHEN up.risk_score < 30 THEN 1 ELSE 0 END) as low_risk,
            SUM(CASE WHEN up.whz_score < -3 THEN 1 ELSE 0 END) as sam_cases,
            SUM(CASE WHEN up.muac < 11.5 THEN 1 ELSE 0 END) as critical_muac,
            COUNT(DISTINCT up.barangay) as barangays_covered
        FROM user_preferences up
    ";
    
    if ($whereClause) {
        $sql .= " " . $whereClause;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $metrics = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent activity (last 7 days)
    $recentSql = "
        SELECT 
            COUNT(*) as screenings_this_week
        FROM user_preferences up
        WHERE up.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ";
    
    if ($whereClause) {
        $recentSql .= " AND " . str_replace('WHERE ', '', $whereClause);
        $recentStmt = $conn->prepare($recentSql);
        $recentStmt->execute($params);
    } else {
        $recentStmt = $conn->prepare($recentSql);
        $recentStmt->execute();
    }
    
    $recentActivity = $recentStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_screenings' => (int)$metrics['total_screenings'],
            'avg_risk_score' => round((float)$metrics['avg_risk_score'], 1),
            'risk_distribution' => [
                'high' => (int)$metrics['high_risk'],
                'moderate' => (int)$metrics['moderate_risk'],
                'low' => (int)$metrics['low_risk']
            ],
            'sam_cases' => (int)$metrics['sam_cases'],
            'critical_muac' => (int)$metrics['critical_muac'],
            'barangays_covered' => (int)$metrics['barangays_covered'],
            'recent_activity' => [
                'screenings_this_week' => (int)$recentActivity['screenings_this_week']
            ]
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
