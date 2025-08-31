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
    
    // Get risk distribution data
    $sql = "
        SELECT 
            CASE 
                WHEN up.risk_score < 20 THEN 'Low Risk'
                WHEN up.risk_score >= 20 AND up.risk_score < 50 THEN 'Moderate Risk'
                WHEN up.risk_score >= 50 AND up.risk_score < 80 THEN 'High Risk'
                ELSE 'Severe Risk'
            END as risk_level,
            COUNT(*) as count
        FROM user_preferences up
    ";
    
    if ($whereClause) {
        $sql .= " " . $whereClause;
    }
    
    $sql .= " GROUP BY risk_level ORDER BY MIN(up.risk_score)";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $riskData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $riskData
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
