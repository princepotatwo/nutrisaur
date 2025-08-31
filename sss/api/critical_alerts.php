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
    
    // Get critical cases with user details for notifications
    $sql = "
        SELECT 
            up.id,
            up.name,
            up.user_email,
            up.barangay,
            up.risk_score,
            up.bmi,
            up.muac,
            up.whz_score,
            up.created_at,
            up.updated_at
        FROM user_preferences up
    ";
    
    if ($whereClause) {
        $sql .= " " . $whereClause . " AND (up.risk_score >= 70 OR up.bmi < 16 OR up.muac < 11.5 OR up.whz_score < -3)";
    } else {
        $sql .= " WHERE (up.risk_score >= 70 OR up.bmi < 16 OR up.muac < 11.5 OR up.whz_score < -3)";
    }
    
    $sql .= "
        ORDER BY up.risk_score DESC, up.created_at DESC
        LIMIT 10
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format data for alerts - match the dashboard format
    $alertData = [];
    foreach ($alerts as $alert) {
        // Determine the specific risk factor
        $riskFactors = [];
        if ($alert['risk_score'] >= 70) $riskFactors[] = 'High Risk Score (' . $alert['risk_score'] . ')';
        if ($alert['bmi'] < 16) $riskFactors[] = 'Low BMI (' . $alert['bmi'] . ')';
        if ($alert['muac'] < 11.5) $riskFactors[] = 'Critical MUAC (' . $alert['muac'] . 'cm)';
        if ($alert['whz_score'] < -3) $riskFactors[] = 'SAM Case (WHZ: ' . $alert['whz_score'] . ')';
        
        $riskDescription = implode(', ', $riskFactors);
        
        $alertData[] = [
            'type' => 'critical',
            'message' => 'High malnutrition risk: ' . $riskDescription,
            'user' => $alert['name'] ?: $alert['user_email'] ?: 'User ' . $alert['id'],
            'user_email' => $alert['user_email'], // Essential for notifications
            'time' => date('M j, Y', strtotime($alert['created_at'])),
            'risk_score' => $alert['risk_score'],
            'bmi' => $alert['bmi'],
            'muac' => $alert['muac'],
            'whz_score' => $alert['whz_score']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $alertData,
        'total_critical_cases' => count($alertData),
        'barangay_filter' => $barangay
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
