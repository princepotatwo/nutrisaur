<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    header('Location: /home');
    exit;
}

$mysql_host = 'mainline.proxy.rlwy.net';
$mysql_port = 26063;
$mysql_user = 'root';
$mysql_password = 'nZhQwfTnAJfFieCpIclAMtOQbBxcjwgy';
$mysql_database = 'railway';

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
    
    // Railway-specific PDO options for better connection stability
    $pdoOptions = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 30, // Increased timeout for Railway
        PDO::ATTR_PERSISTENT => false, // Disable persistent connections for Railway
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
    ];
    
    $conn = new PDO($dsn, $mysql_user, $mysql_password, $pdoOptions);
    
    // Test the connection
    $conn->query("SELECT 1");
    
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    $conn = null;
    $dbError = "Database connection failed: " . $e->getMessage();
}

// PHP Date formatting function for user-friendly time display
function formatTimeAgoPHP($dateString) {
    $now = new DateTime();
    $date = new DateTime($dateString);
    $diff = $now->diff($date);
    
    if ($diff->y > 0) {
        return $diff->y == 1 ? '1 year ago' : $diff->y . ' years ago';
    } elseif ($diff->m > 0) {
        return $diff->m == 1 ? '1 month ago' : $diff->m . ' months ago';
    } elseif ($diff->d > 0) {
        if ($diff->d >= 7) {
            $weeks = floor($diff->d / 7);
            return $weeks == 1 ? '1 week ago' : $weeks . ' weeks ago';
        } else {
            return $diff->d == 1 ? '1 day ago' : $diff->d . ' days ago';
        }
    } elseif ($diff->h > 0) {
        return $diff->h == 1 ? '1 hour ago' : $diff->h . ' hours ago';
    } elseif ($diff->i > 0) {
        return $diff->i == 1 ? '1 minute ago' : $diff->i . ' minutes ago';
    } else {
        return 'Just now';
    }
}

// Enhanced PHP date formatting with more options
function formatDatePHP($dateString, $format = 'relative') {
    $date = new DateTime($dateString);
    
    switch ($format) {
        case 'relative':
            return formatTimeAgoPHP($dateString);
        case 'short':
            return $date->format('M j, Y');
        case 'long':
            return $date->format('l, F j, Y');
        case 'time':
            return $date->format('g:i A');
        case 'datetime':
            return $date->format('M j, Y g:i A');
        default:
            return $date->format('Y-m-d');
    }
}

// Function to get time frame data from user_preferences table
function getTimeFrameData($conn, $timeFrame, $barangay = null) {
    $now = new DateTime();
    $startDate = new DateTime();
    
    // Calculate start date based on time frame
    switch($timeFrame) {
        case '1d':
            $startDate->modify('-1 day');
            break;
        case '1w':
            $startDate->modify('-1 week');
            break;
        case '1m':
            $startDate->modify('-1 month');
            break;
        case '3m':
            $startDate->modify('-3 months');
            break;
        case '1y':
            $startDate->modify('-1 year');
            break;
        default:
            $startDate->modify('-1 day');
    }
    
    $startDateStr = $startDate->format('Y-m-d H:i:s');
    $endDateStr = $now->format('Y-m-d H:i:s');
    
    try {
        // Build the query based on barangay filter
        $whereClause = "WHERE (up.created_at BETWEEN :start_date AND :end_date) OR (up.updated_at BETWEEN :start_date AND :end_date)";
        $params = [':start_date' => $startDateStr, ':end_date' => $endDateStr];
        
        if ($barangay && $barangay !== '') {
            $whereClause .= " AND up.barangay = :barangay";
            $params[':barangay'] = $barangay;
        }
        
        $query = "
            SELECT 
                COUNT(*) as total_screened,
                SUM(CASE WHEN up.risk_score >= 50 THEN 1 ELSE 0 END) as high_risk_cases,
                SUM(CASE WHEN up.whz_score < -3 THEN 1 ELSE 0 END) as sam_cases,
                SUM(CASE WHEN up.muac < 11.5 THEN 1 ELSE 0 END) as critical_muac,
                AVG(up.risk_score) as avg_risk_score,
                AVG(up.whz_score) as avg_whz_score,
                AVG(up.muac) as avg_muac,
                COUNT(DISTINCT up.barangay) as barangays_covered,
                MIN(up.created_at) as earliest_screening,
                MAX(up.updated_at) as latest_update
            FROM user_preferences up
            $whereClause
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Add time frame info
        $data['time_frame'] = $timeFrame;
        $data['start_date'] = $startDateStr;
        $data['end_date'] = $endDateStr;
        $data['start_date_formatted'] = $startDate->format('M j, Y');
        $data['end_date_formatted'] = $now->format('M j, Y');
        
        return $data;
        
    } catch (PDOException $e) {
        error_log("Error getting time frame data: " . $e->getMessage());
        return [
            'total_screened' => 0,
            'high_risk_cases' => 0,
            'sam_cases' => 0,
            'critical_muac' => 0,
            'avg_risk_score' => 0,
            'avg_whz_score' => 0,
            'avg_muac' => 0,
            'barangays_covered' => 0,
            'time_frame' => $timeFrame,
            'start_date_formatted' => $startDate->format('M j, Y'),
            'end_date_formatted' => $now->format('M j, Y')
        ];
    }
}

// Function to get screening responses by time frame
function getScreeningResponsesByTimeFrame($conn, $timeFrame, $barangay = null) {
    $now = new DateTime();
    $startDate = new DateTime();
    
    // Calculate start date based on time frame
    switch($timeFrame) {
        case '1d':
            $startDate->modify('-1 day');
            break;
        case '1w':
            $startDate->modify('-1 week');
            break;
        case '1m':
            $startDate->modify('-1 month');
            break;
        case '3m':
            $startDate->modify('-3 months');
            break;
        case '1y':
            $startDate->modify('-1 year');
            break;
        default:
            $startDate->modify('-1 day');
    }
    
    $startDateStr = $startDate->format('Y-m-d H:i:s');
    $endDateStr = $now->format('Y-m-d H:i:s');
    
    try {
        $whereClause = "WHERE (up.created_at BETWEEN :start_date AND :end_date) OR (up.updated_at BETWEEN :start_date AND :end_date)";
        $params = [':start_date' => $startDateStr, ':end_date' => $endDateStr];
        
        if ($barangay && $barangay !== '') {
            $whereClause .= " AND up.barangay = :barangay";
            $params[':barangay'] = $barangay;
        }
        
        // Get age groups
        $ageQuery = "
            SELECT 
                CASE 
                    WHEN up.age < 1 THEN 'Under 1 year'
                    WHEN up.age < 6 THEN '1-5 years'
                    WHEN up.age < 12 THEN '6-11 years'
                    WHEN up.age < 18 THEN '12-17 years'
                    WHEN up.age < 25 THEN '18-24 years'
                    WHEN up.age < 35 THEN '25-34 years'
                    WHEN up.age < 45 THEN '35-44 years'
                    WHEN up.age < 55 THEN '45-54 years'
                    WHEN up.age < 65 THEN '55-64 years'
                    ELSE '65+ years'
                END as age_group,
                COUNT(*) as count
            FROM user_preferences up
            $whereClause
            GROUP BY age_group
            ORDER BY MIN(up.age)
        ";
        
        $stmt = $conn->prepare($ageQuery);
        $stmt->execute($params);
        $ageGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get gender distribution
        $genderQuery = "
            SELECT 
                up.gender,
                COUNT(*) as count
            FROM user_preferences up
            $whereClause
            GROUP BY up.gender
        ";
        
        $stmt = $conn->prepare($genderQuery);
        $stmt->execute($params);
        $genderDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get income levels
        $incomeQuery = "
            SELECT 
                up.income,
                COUNT(*) as count
            FROM user_preferences up
            $whereClause
            GROUP BY up.income
        ";
        
        $stmt = $conn->prepare($incomeQuery);
        $stmt->execute($params);
        $incomeLevels = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get height distribution
        $heightQuery = "
            SELECT 
                CASE 
                    WHEN up.height < 100 THEN 'Under 100 cm'
                    WHEN up.height < 120 THEN '100-119 cm'
                    WHEN up.height < 140 THEN '120-139 cm'
                    WHEN up.height < 160 THEN '140-159 cm'
                    WHEN up.height < 180 THEN '160-179 cm'
                    ELSE '180+ cm'
                END as height_range,
                COUNT(*) as count
            FROM user_preferences up
            $whereClause
            GROUP BY height_range
            ORDER BY MIN(up.height)
        ";
        
        $stmt = $conn->prepare($heightQuery);
        $stmt->execute($params);
        $heightDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get swelling distribution
        $swellingQuery = "
            SELECT 
                up.swelling,
                COUNT(*) as count
            FROM user_preferences up
            $whereClause
            GROUP BY up.swelling
        ";
        
        $stmt = $conn->prepare($swellingQuery);
        $stmt->execute($params);
        $swellingDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get weight loss distribution
        $weightLossQuery = "
            SELECT 
                up.weight_loss,
                COUNT(*) as count
            FROM user_preferences up
            $whereClause
            GROUP BY up.weight_loss
        ";
        
        $stmt = $conn->prepare($weightLossQuery);
        $stmt->execute($params);
        $weightLossDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get feeding behavior distribution
        $feedingQuery = "
            SELECT 
                up.feeding_behavior,
                COUNT(*) as count
            FROM user_preferences up
            $whereClause
            GROUP BY up.feeding_behavior
        ";
        
        $stmt = $conn->prepare($feedingQuery);
        $stmt->execute($params);
        $feedingBehaviorDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get physical signs
        $physicalQuery = "
            SELECT 
                CASE 
                    WHEN up.physical_thin = 'yes' THEN 'Thin Appearance'
                    WHEN up.physical_shorter = 'yes' THEN 'Shorter Stature'
                    WHEN up.physical_weak = 'yes' THEN 'Weak Physical Condition'
                    WHEN up.physical_none = 'yes' THEN 'No Physical Signs'
                    ELSE 'Not Assessed'
                END as physical_sign,
                COUNT(*) as count
            FROM user_preferences up
            $whereClause
            GROUP BY physical_sign
        ";
        
        $stmt = $conn->prepare($physicalQuery);
        $stmt->execute($params);
        $physicalSigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get dietary diversity
        $dietaryQuery = "
            SELECT 
                CASE 
                    WHEN up.dietary_diversity_score = 0 THEN 'No Food Groups (0)'
                    WHEN up.dietary_diversity_score <= 2 THEN 'Very Low Diversity (1-2 food groups)'
                    WHEN up.dietary_diversity_score <= 4 THEN 'Low Diversity (3-4 food groups)'
                    WHEN up.dietary_diversity_score <= 6 THEN 'Medium Diversity (5-6 food groups)'
                    WHEN up.dietary_diversity_score <= 8 THEN 'Good Diversity (7-8 food groups)'
                    ELSE 'High Diversity (9-10 food groups)'
                END as dietary_diversity_level,
                COUNT(*) as count
            FROM user_preferences up
            $whereClause
            GROUP BY dietary_diversity_level
            ORDER BY MIN(up.dietary_diversity_score)
        ";
        
        $stmt = $conn->prepare($dietaryQuery);
        $stmt->execute($params);
        $dietaryDiversityDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get clinical risk factors
        $clinicalQuery = "
            SELECT 
                CASE 
                    WHEN up.diarrhea = 'yes' THEN 'Diarrhea'
                    WHEN up.fever = 'yes' THEN 'Fever'
                    WHEN up.cough = 'yes' THEN 'Cough'
                    ELSE 'No Clinical Risk Factors'
                END as clinical_risk_factor,
                COUNT(*) as count
            FROM user_preferences up
            $whereClause
            GROUP BY clinical_risk_factor
        ";
        
        $stmt = $conn->prepare($clinicalQuery);
        $stmt->execute($params);
        $clinicalRiskFactors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'age_groups' => $ageGroups,
            'gender_distribution' => $genderDistribution,
            'income_levels' => $incomeLevels,
            'height_distribution' => $heightDistribution,
            'swelling_distribution' => $swellingDistribution,
            'weight_loss_distribution' => $weightLossDistribution,
            'feeding_behavior_distribution' => $feedingBehaviorDistribution,
            'physical_signs' => $physicalSigns,
            'dietary_diversity_distribution' => $dietaryDiversityDistribution,
            'clinical_risk_factors' => $clinicalRiskFactors,
            'time_frame' => $timeFrame,
            'start_date' => $startDateStr,
            'end_date' => $endDateStr
        ];
        
    } catch (PDOException $e) {
        error_log("Error getting screening responses by time frame: " . $e->getMessage());
        return [];
    }
}

$userId = $_SESSION['user_id'] ?? 'unknown';
$username = $_SESSION['username'] ?? 'User';
$email = $_SESSION['email'] ?? 'user@example.com';
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';

$profile = null;
try {
    $stmt = $conn->prepare("
        SELECT u.*, up.* 
        FROM users u 
        LEFT JOIN user_preferences up ON u.email = up.user_email 
        WHERE u.user_id = :user_id
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $profile = null;
}
        
        $stmt = $conn->prepare("SELECT * FROM nutrition_goals WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $goals = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        $currentTimeFrame = '1d';
        $currentBarangay = '';
        $timeFrameData = getTimeFrameData($conn, $currentTimeFrame, $currentBarangay);
        $screeningResponsesData = getScreeningResponsesByTimeFrame($conn, $currentTimeFrame, $currentBarangay);

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: home.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriSaur Dashboard</title>
  
</head>
<body class="light-theme">

    <div class="navbar">
        <div class="navbar-header">
            <div class="navbar-logo">
                <div class="navbar-logo-icon">
                    <img src="/logo.png" alt="Logo" style="width: 40px; height: 40px;">
                </div>
                <div class="navbar-logo-text">NutriSaur</div>
            </div>
        </div>
        <div class="navbar-menu">
            <ul>
                <li><a href="dash"><span class="navbar-icon"></span><span>Dashboard</span></a></li>

                <li><a href="event"><span class="navbar-icon"></span><span>Nutrition Event Notifications</span></a></li>
                
                <li><a href="ai"><span class="navbar-icon"></span><span>Chatbot & AI Logs</span></a></li>
                <li><a href="settings"><span class="navbar-icon"></span><span>Settings & Admin</span></a></li>
                <li><a href="logout" style="color: #ff5252;"><span class="navbar-icon"></span><span>Logout</span></a></li>
            </ul>
        </div>
        <div class="navbar-footer">
            <div>NutriSaur v1.0 • © 2023</div>
            <div style="margin-top: 10px;">Logged in as: <?php echo htmlspecialchars($username); ?></div>
        </div>
    </div>
    
    <div class="dashboard">
        <header>
            <div class="dashboard-header">
                <h1>Dashboard</h1>
            </div>
            <div class="user-info">
                <button id="new-theme-toggle" class="new-theme-toggle-btn" title="Toggle theme">
                    <span class="new-theme-icon">🌙</span>
                </button>
            </div>
        </header>

        <!-- Community Filter Section - Moved to Top -->
        <div class="filter-section">
            <div class="filter-group">
                <label>Select Barangay:</label>
                <div class="custom-select-container">
                    <div class="select-header" onclick="toggleDropdown()">
                        <span id="selected-option">All Barangays</span>
                        <span class="dropdown-arrow">▼</span>
                    </div>
                    <div class="dropdown-content" id="dropdown-content">
                        <div class="search-container">
                            <input type="text" id="search-input" placeholder="Search barangay or municipality..." onkeyup="filterOptions()">
                        </div>
                        <div class="options-container">
                            <!-- Municipality Options -->
                            <div class="option-group">
                                <div class="option-header">Municipalities</div>
                                <div class="option-item" data-value="">All Barangays</div>
                                <div class="option-item" data-value="MUNICIPALITY_ABUCAY">ABUCAY (All Barangays)</div>
                                <div class="option-item" data-value="MUNICIPALITY_BAGAC">BAGAC (All Barangays)</div>
                                <div class="option-item" data-value="MUNICIPALITY_BALANGA">CITY OF BALANGA (All Barangays)</div>
                                <div class="option-item" data-value="MUNICIPALITY_DINALUPIHAN">DINALUPIHAN (All Barangays)</div>
                                <div class="option-item" data-value="MUNICIPALITY_HERMOSA">HERMOSA (All Barangays)</div>
                                <div class="option-item" data-value="MUNICIPALITY_LIMAY">LIMAY (All Barangays)</div>
                                <div class="option-item" data-value="MUNICIPALITY_MARIVELES">MARIVELES (All Barangays)</div>
                                <div class="option-item" data-value="MUNICIPALITY_MORONG">MORONG (All Barangays)</div>
                                <div class="option-item" data-value="MUNICIPALITY_ORANI">ORANI (All Barangays)</div>
                                <div class="option-item" data-value="MUNICIPALITY_ORION">ORION (All Barangays)</div>
                                <div class="option-item" data-value="MUNICIPALITY_PILAR">PILAR (All Barangays)</div>
                                <div class="option-item" data-value="MUNICIPALITY_SAMAL">SAMAL (All Barangays)</div>
                                                        </div>
                            
                            <!-- Individual Barangays by Municipality -->
                            <div class="option-group">
                                <div class="option-header">ABUCAY</div>
                                <div class="option-item" data-value="Bangkal">Bangkal</div>
                                <div class="option-item" data-value="Calaylayan (Pob.)">Calaylayan (Pob.)</div>
                                <div class="option-item" data-value="Capitangan">Capitangan</div>
                                <div class="option-item" data-value="Gabon">Gabon</div>
                                <div class="option-item" data-value="Laon (Pob.)">Laon (Pob.)</div>
                                <div class="option-item" data-value="Mabatang">Mabatang</div>
                                <div class="option-item" data-value="Omboy">Omboy</div>
                                <div class="option-item" data-value="Salian">Salian</div>
                                <div class="option-item" data-value="Wawa (Pob.)">Wawa (Pob.)</div>
                            </div>
                            <div class="option-group">
                                <div class="option-header">BAGAC</div>
                                <div class="option-item" data-value="Bagumbayan (Pob.)">Bagumbayan (Pob.)</div>
                                <div class="option-item" data-value="Banawang">Banawang</div>
                                <div class="option-item" data-value="Binuangan">Binuangan</div>
                                <div class="option-item" data-value="Binukawan">Binukawan</div>
                                <div class="option-item" data-value="Ibaba">Ibaba</div>
                                <div class="option-item" data-value="Ibis">Ibis</div>
                                <div class="option-item" data-value="Pag-asa (Wawa-Sibacan)">Pag-asa (Wawa-Sibacan)</div>
                                <div class="option-item" data-value="Parang">Parang</div>
                                <div class="option-item" data-value="Paysawan">Paysawan</div>
                                <div class="option-item" data-value="Quinawan">Quinawan</div>
                                <div class="option-item" data-value="San Antonio">San Antonio</div>
                                <div class="option-item" data-value="Saysain">Saysain</div>
                                <div class="option-item" data-value="Tabing-Ilog (Pob.)">Tabing-Ilog (Pob.)</div>
                                <div class="option-item" data-value="Atilano L. Ricardo">Atilano L. Ricardo</div>
                            </div>
                            <div class="option-group">
                                <div class="option-header">CITY OF BALANGA</div>
                                <div class="option-item" data-value="Bagumbayan">Bagumbayan</div>
                                <div class="option-item" data-value="Cabog-Cabog">Cabog-Cabog</div>
                                <div class="option-item" data-value="Munting Batangas (Cadre)">Munting Batangas (Cadre)</div>
                                <div class="option-item" data-value="Cataning">Cataning</div>
                                <div class="option-item" data-value="Central">Central</div>
                                <div class="option-item" data-value="Cupang Proper">Cupang Proper</div>
                                <div class="option-item" data-value="Cupang West">Cupang West</div>
                                <div class="option-item" data-value="Dangcol (Bernabe)">Dangcol (Bernabe)</div>
                                <div class="option-item" data-value="Ibayo">Ibayo</div>
                                <div class="option-item" data-value="Malabia">Malabia</div>
                                <div class="option-item" data-value="Poblacion">Poblacion</div>
                                <div class="option-item" data-value="Pto. Rivas Ibaba">Pto. Rivas Ibaba</div>
                                <div class="option-item" data-value="Pto. Rivas Itaas">Pto. Rivas Itaas</div>
                                <div class="option-item" data-value="San Jose">San Jose</div>
                                <div class="option-item" data-value="Sibacan">Sibacan</div>
                                <div class="option-item" data-value="Camacho">Camacho</div>
                                <div class="option-item" data-value="Talisay">Talisay</div>
                                <div class="option-item" data-value="Tanato">Tanato</div>
                                <div class="option-item" data-value="Tenejero">Tenejero</div>
                                <div class="option-item" data-value="Tortugas">Tortugas</div>
                                <div class="option-item" data-value="Tuyo">Tuyo</div>
                                <div class="option-item" data-value="Bagong Silang">Bagong Silang</div>
                                <div class="option-item" data-value="Cupang North">Cupang North</div>
                                <div class="option-item" data-value="Doña Francisca">Doña Francisca</div>
                                <div class="option-item" data-value="Lote">Lote</div>
                            </div>
                            <div class="option-group">
                                <div class="option-header">DINALUPIHAN</div>
                                <div class="option-item" data-value="Bangal">Bangal</div>
                                <div class="option-item" data-value="Bonifacio (Pob.)">Bonifacio (Pob.)</div>
                                <div class="option-item" data-value="Burgos (Pob.)">Burgos (Pob.)</div>
                                <div class="option-item" data-value="Colo">Colo</div>
                                <div class="option-item" data-value="Daang Bago">Daang Bago</div>
                                <div class="option-item" data-value="Dalao">Dalao</div>
                                <div class="option-item" data-value="Del Pilar (Pob.)">Del Pilar (Pob.)</div>
                                <div class="option-item" data-value="Gen. Luna (Pob.)">Gen. Luna (Pob.)</div>
                                <div class="option-item" data-value="Gomez (Pob.)">Gomez (Pob.)</div>
                                <div class="option-item" data-value="Happy Valley">Happy Valley</div>
                                <div class="option-item" data-value="Kataasan">Kataasan</div>
                                <div class="option-item" data-value="Layac">Layac</div>
                                <div class="option-item" data-value="Luacan">Luacan</div>
                                <div class="option-item" data-value="Mabini Proper (Pob.)">Mabini Proper (Pob.)</div>
                                <div class="option-item" data-value="Mabini Ext. (Pob.)">Mabini Ext. (Pob.)</div>
                                <div class="option-item" data-value="Magsaysay">Magsaysay</div>
                                <div class="option-item" data-value="Naparing">Naparing</div>
                                <div class="option-item" data-value="New San Jose">New San Jose</div>
                                <div class="option-item" data-value="Old San Jose">Old San Jose</div>
                                <div class="option-item" data-value="Padre Dandan (Pob.)">Padre Dandan (Pob.)</div>
                                <div class="option-item" data-value="Pag-asa">Pag-asa</div>
                                <div class="option-item" data-value="Pagalanggang">Pagalanggang</div>
                                <div class="option-item" data-value="Pinulot">Pinulot</div>
                                <div class="option-item" data-value="Pita">Pita</div>
                                <div class="option-item" data-value="Rizal (Pob.)">Rizal (Pob.)</div>
                                <div class="option-item" data-value="Roosevelt">Roosevelt</div>
                                <div class="option-item" data-value="Roxas (Pob.)">Roxas (Pob.)</div>
                                <div class="option-item" data-value="Saguing">Saguing</div>
                                <div class="option-item" data-value="San Benito">San Benito</div>
                                <div class="option-item" data-value="San Isidro (Pob.)">San Isidro (Pob.)</div>
                                <div class="option-item" data-value="San Pablo (Bulate)">San Pablo (Bulate)</div>
                                <div class="option-item" data-value="San Ramon">San Ramon</div>
                                <div class="option-item" data-value="San Simon">San Simon</div>
                                <div class="option-item" data-value="Santo Niño">Santo Niño</div>
                                <div class="option-item" data-value="Sapang Balas">Sapang Balas</div>
                                <div class="option-item" data-value="Santa Isabel (Tabacan)">Santa Isabel (Tabacan)</div>
                                <div class="option-item" data-value="Torres Bugauen (Pob.)">Torres Bugauen (Pob.)</div>
                                <div class="option-item" data-value="Tucop">Tucop</div>
                                <div class="option-item" data-value="Zamora (Pob.)">Zamora (Pob.)</div>
                                <div class="option-item" data-value="Aquino">Aquino</div>
                                <div class="option-item" data-value="Bayan-bayanan">Bayan-bayanan</div>
                                <div class="option-item" data-value="Maligaya">Maligaya</div>
                                <div class="option-item" data-value="Payangan">Payangan</div>
                                <div class="option-item" data-value="Pentor">Pentor</div>
                                <div class="option-item" data-value="Tubo-tubo">Tubo-tubo</div>
                                <div class="option-item" data-value="Jose C. Payumo, Jr.">Jose C. Payumo, Jr.</div>
                            </div>
                            <div class="option-group">
                                <div class="option-header">HERMOSA</div>
                                <div class="option-item" data-value="A. Rivera (Pob.)">A. Rivera (Pob.)</div>
                                <div class="option-item" data-value="Almacen">Almacen</div>
                                <div class="option-item" data-value="Bacong">Bacong</div>
                                <div class="option-item" data-value="Balsic">Balsic</div>
                                <div class="option-item" data-value="Bamban">Bamban</div>
                                <div class="option-item" data-value="Burgos-Soliman (Pob.)">Burgos-Soliman (Pob.)</div>
                                <div class="option-item" data-value="Cataning (Pob.)">Cataning (Pob.)</div>
                                <div class="option-item" data-value="Culis">Culis</div>
                                <div class="option-item" data-value="Daungan (Pob.)">Daungan (Pob.)</div>
                                <div class="option-item" data-value="Mabiga">Mabiga</div>
                                <div class="option-item" data-value="Mabuco">Mabuco</div>
                                <div class="option-item" data-value="Maite">Maite</div>
                                <div class="option-item" data-value="Mambog - Mandama">Mambog - Mandama</div>
                                <div class="option-item" data-value="Palihan">Palihan</div>
                                <div class="option-item" data-value="Pandatung">Pandatung</div>
                                <div class="option-item" data-value="Pulo">Pulo</div>
                                <div class="option-item" data-value="Saba">Saba</div>
                                <div class="option-item" data-value="San Pedro (Pob.)">San Pedro (Pob.)</div>
                                <div class="option-item" data-value="Santo Cristo (Pob.)">Santo Cristo (Pob.)</div>
                                <div class="option-item" data-value="Sumalo">Sumalo</div>
                                <div class="option-item" data-value="Tipo">Tipo</div>
                                <div class="option-item" data-value="Judge Roman Cruz Sr. (Mandama)">Judge Roman Cruz Sr. (Mandama)</div>
                                <div class="option-item" data-value="Sacrifice Valley">Sacrifice Valley</div>
                            </div>
                            <div class="option-group">
                                <div class="option-header">LIMAY</div>
                                <div class="option-item" data-value="Alangan">Alangan</div>
                                <div class="option-item" data-value="Kitang I">Kitang I</div>
                                <div class="option-item" data-value="Kitang 2 & Luz">Kitang 2 & Luz</div>
                                <div class="option-item" data-value="Lamao">Lamao</div>
                                <div class="option-item" data-value="Landing">Landing</div>
                                <div class="option-item" data-value="Poblacion">Poblacion</div>
                                <div class="option-item" data-value="Reformista">Reformista</div>
                                <div class="option-item" data-value="Townsite">Townsite</div>
                                <div class="option-item" data-value="Wawa">Wawa</div>
                                <div class="option-item" data-value="Duale">Duale</div>
                                <div class="option-item" data-value="San Francisco de Asis">San Francisco de Asis</div>
                                <div class="option-item" data-value="St. Francis II">St. Francis II</div>
                            </div>
                            <div class="option-group">
                                <div class="option-header">MARIVELES</div>
                                <div class="option-item" data-value="Alas-asin">Alas-asin</div>
                                <div class="option-item" data-value="Alion">Alion</div>
                                <div class="option-item" data-value="Batangas II">Batangas II</div>
                                <div class="option-item" data-value="Cabcaben">Cabcaben</div>
                                <div class="option-item" data-value="Lucanin">Lucanin</div>
                                <div class="option-item" data-value="Baseco Country (Nassco)">Baseco Country (Nassco)</div>
                                <div class="option-item" data-value="Poblacion">Poblacion</div>
                                <div class="option-item" data-value="San Carlos">San Carlos</div>
                                <div class="option-item" data-value="San Isidro">San Isidro</div>
                                <div class="option-item" data-value="Sisiman">Sisiman</div>
                                <div class="option-item" data-value="Balon-Anito">Balon-Anito</div>
                                <div class="option-item" data-value="Biaan">Biaan</div>
                                <div class="option-item" data-value="Camaya">Camaya</div>
                                <div class="option-item" data-value="Ipag">Ipag</div>
                                <div class="option-item" data-value="Malaya">Malaya</div>
                                <div class="option-item" data-value="Maligaya">Maligaya</div>
                                <div class="option-item" data-value="Mt. View">Mt. View</div>
                                <div class="option-item" data-value="Townsite">Townsite</div>
                            </div>
                            <div class="option-group">
                                <div class="option-header">MORONG</div>
                                <div class="option-item" data-value="Binaritan">Binaritan</div>
                                <div class="option-item" data-value="Mabayo">Mabayo</div>
                                <div class="option-item" data-value="Nagbalayong">Nagbalayong</div>
                                <div class="option-item" data-value="Poblacion">Poblacion</div>
                                <div class="option-item" data-value="Sabang">Sabang</div>
                            </div>
                            <div class="option-group">
                                <div class="option-header">ORANI</div>
                                <div class="option-item" data-value="Bagong Paraiso (Pob.)">Bagong Paraiso (Pob.)</div>
                                <div class="option-item" data-value="Balut (Pob.)">Balut (Pob.)</div>
                                <div class="option-item" data-value="Bayan (Pob.)">Bayan (Pob.)</div>
                                <div class="option-item" data-value="Calero (Pob.)">Calero (Pob.)</div>
                                <div class="option-item" data-value="Paking-Carbonero (Pob.)">Paking-Carbonero (Pob.)</div>
                                <div class="option-item" data-value="Centro II (Pob.)">Centro II (Pob.)</div>
                                <div class="option-item" data-value="Dona">Dona</div>
                                <div class="option-item" data-value="Kaparangan">Kaparangan</div>
                                <div class="option-item" data-value="Masantol">Masantol</div>
                                <div class="option-item" data-value="Mulawin">Mulawin</div>
                                <div class="option-item" data-value="Pag-asa">Pag-asa</div>
                                <div class="option-item" data-value="Palihan (Pob.)">Palihan (Pob.)</div>
                                <div class="option-item" data-value="Pantalan Bago (Pob.)">Pantalan Bago (Pob.)</div>
                                <div class="option-item" data-value="Pantalan Luma (Pob.)">Pantalan Luma (Pob.)</div>
                                <div class="option-item" data-value="Parang Parang (Pob.)">Parang Parang (Pob.)</div>
                                <div class="option-item" data-value="Centro I (Pob.)">Centro I (Pob.)</div>
                                <div class="option-item" data-value="Sibul">Sibul</div>
                                <div class="option-item" data-value="Silahis">Silahis</div>
                                <div class="option-item" data-value="Tala">Tala</div>
                                <div class="option-item" data-value="Talimundoc">Talimundoc</div>
                                <div class="option-item" data-value="Tapulao">Tapulao</div>
                                <div class="option-item" data-value="Tenejero (Pob.)">Tenejero (Pob.)</div>
                                <div class="option-item" data-value="Tugatog">Tugatog</div>
                                <div class="option-item" data-value="Wawa (Pob.)">Wawa (Pob.)</div>
                                <div class="option-item" data-value="Apollo">Apollo</div>
                                <div class="option-item" data-value="Kabalutan">Kabalutan</div>
                                <div class="option-item" data-value="Maria Fe">Maria Fe</div>
                                <div class="option-item" data-value="Puksuan">Puksuan</div>
                                <div class="option-item" data-value="Tagumpay">Tagumpay</div>
                            </div>
                            <div class="option-group">
                                <div class="option-header">ORION</div>
                                <div class="option-item" data-value="Arellano (Pob.)">Arellano (Pob.)</div>
                                <div class="option-item" data-value="Bagumbayan (Pob.)">Bagumbayan (Pob.)</div>
                                <div class="option-item" data-value="Balagtas (Pob.)">Balagtas (Pob.)</div>
                                <div class="option-item" data-value="Balut (Pob.)">Balut (Pob.)</div>
                                <div class="option-item" data-value="Bantan">Bantan</div>
                                <div class="option-item" data-value="Bilolo">Bilolo</div>
                                <div class="option-item" data-value="Calungusan">Calungusan</div>
                                <div class="option-item" data-value="Camachile">Camachile</div>
                                <div class="option-item" data-value="Daang Bago (Pob.)">Daang Bago (Pob.)</div>
                                <div class="option-item" data-value="Daang Bilolo (Pob.)">Daang Bilolo (Pob.)</div>
                                <div class="option-item" data-value="Daang Pare">Daang Pare</div>
                                <div class="option-item" data-value="General Lim (Kaput)">General Lim (Kaput)</div>
                                <div class="option-item" data-value="Kapunitan">Kapunitan</div>
                                <div class="option-item" data-value="Lati (Pob.)">Lati (Pob.)</div>
                                <div class="option-item" data-value="Lusungan (Pob.)">Lusungan (Pob.)</div>
                                <div class="option-item" data-value="Puting Buhangin">Puting Buhangin</div>
                                <div class="option-item" data-value="Sabatan">Sabatan</div>
                                <div class="option-item" data-value="San Vicente (Pob.)">San Vicente (Pob.)</div>
                                <div class="option-item" data-value="Santo Domingo">Santo Domingo</div>
                                <div class="option-item" data-value="Villa Angeles (Pob.)">Villa Angeles (Pob.)</div>
                                <div class="option-item" data-value="Wakas (Pob.)">Wakas (Pob.)</div>
                                <div class="option-item" data-value="Wawa (Pob.)">Wawa (Pob.)</div>
                                <div class="option-item" data-value="Santa Elena">Santa Elena</div>
                            </div>
                            <div class="option-group">
                                <div class="option-header">PILAR</div>
                                <div class="option-item" data-value="Ala-uli">Ala-uli</div>
                                <div class="option-item" data-value="Bagumbayan">Bagumbayan</div>
                                <div class="option-item" data-value="Balut I">Balut I</div>
                                <div class="option-item" data-value="Balut II">Balut II</div>
                                <div class="option-item" data-value="Bantan Munti">Bantan Munti</div>
                                <div class="option-item" data-value="Burgos">Burgos</div>
                                <div class="option-item" data-value="Del Rosario (Pob.)">Del Rosario (Pob.)</div>
                                <div class="option-item" data-value="Diwa">Diwa</div>
                                <div class="option-item" data-value="Landing">Landing</div>
                                <div class="option-item" data-value="Liyang">Liyang</div>
                                <div class="option-item" data-value="Nagwaling">Nagwaling</div>
                                <div class="option-item" data-value="Panilao">Panilao</div>
                                <div class="option-item" data-value="Pantingan">Pantingan</div>
                                <div class="option-item" data-value="Poblacion">Poblacion</div>
                                <div class="option-item" data-value="Rizal">Rizal</div>
                                <div class="option-item" data-value="Santa Rosa">Santa Rosa</div>
                                <div class="option-item" data-value="Wakas North">Wakas North</div>
                                <div class="option-item" data-value="Wakas South">Wakas South</div>
                                <div class="option-item" data-value="Wawa">Wawa</div>
                            </div>
                            <div class="option-group">
                                <div class="option-header">SAMAL</div>
                                <div class="option-item" data-value="East Calaguiman (Pob.)">East Calaguiman (Pob.)</div>
                                <div class="option-item" data-value="East Daang Bago (Pob.)">East Daang Bago (Pob.)</div>
                                <div class="option-item" data-value="Ibaba (Pob.)">Ibaba (Pob.)</div>
                                <div class="option-item" data-value="Imelda">Imelda</div>
                                <div class="option-item" data-value="Lalawigan">Lalawigan</div>
                                <div class="option-item" data-value="Palili">Palili</div>
                                <div class="option-item" data-value="San Juan (Pob.)">San Juan (Pob.)</div>
                                <div class="option-item" data-value="San Roque (Pob.)">San Roque (Pob.)</div>
                                <div class="option-item" data-value="Santa Lucia">Santa Lucia</div>
                                <div class="option-item" data-value="Sapa">Sapa</div>
                                <div class="option-item" data-value="Tabing Ilog">Tabing Ilog</div>
                                <div class="option-item" data-value="Gugo">Gugo</div>
                                <div class="option-item" data-value="West Calaguiman (Pob.)">West Calaguiman (Pob.)</div>
                                <div class="option-item" data-value="West Daang Bago (Pob.)">West Daang Bago (Pob.)</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="filter-group">
                <label>Time Frame:</label>
                <div class="time-frame-buttons">
                    <button class="time-btn active" data-timeframe="1d">1 Day</button>
                    <button class="time-btn" data-timeframe="1w">1 Week</button>
                    <button class="time-btn" data-timeframe="1m">1 Month</button>
                    <button class="time-btn" data-timeframe="3m">3 Months</button>
                    <button class="time-btn" data-timeframe="1y">1 Year</button>
                </div>
            </div>
        </div>



        <!-- Community Metrics Cards - Moved to Top -->
        <div class="card-container" style="gap: 20px;">
            <div class="card">
                <h2>Total Screened</h2>
                <div class="metric-value" id="community-total-screened"><?php echo $timeFrameData['total_screened']; ?></div>
                <div class="metric-change" id="community-screened-change">
                    <?php echo $timeFrameData['start_date_formatted']; ?> - <?php echo $timeFrameData['end_date_formatted']; ?>
                </div>
                <div class="metric-note">Children & adults screened in selected time frame</div>
            </div>
            <div class="card">
                <h2>High Risk Cases</h2>
                <div class="metric-value" id="community-high-risk"><?php echo $timeFrameData['high_risk_cases']; ?></div>
                <div class="metric-change" id="community-risk-change">
                    <?php echo $timeFrameData['start_date_formatted']; ?> - <?php echo $timeFrameData['end_date_formatted']; ?>
                </div>
                <div class="metric-note">Risk score ≥30 (WHO standard)</div>
            </div>
            <div class="card">
                <h2>SAM Cases</h2>
                <div class="metric-value" id="community-sam-cases"><?php echo $timeFrameData['sam_cases']; ?></div>
                <div class="metric-change" id="community-sam-change">
                    <?php echo $timeFrameData['start_date_formatted']; ?> - <?php echo $timeFrameData['end_date_formatted']; ?>
                </div>
                <div class="metric-note">Severe Acute Malnutrition (WHZ < -3)</div>
            </div>
            <div class="card">
                <h2>Critical MUAC</h2>
                <div class="metric-value" id="community-critical-muac"><?php echo $timeFrameData['critical_muac']; ?></div>
                <div class="metric-change" id="community-muac-change">
                    <?php echo $timeFrameData['start_date_formatted']; ?> - <?php echo $timeFrameData['end_date_formatted']; ?>
                </div>
                <div class="metric-note">MUAC < 11.5cm (critical threshold)</div>
            </div>
        </div>



        <div class="chart-row">
            <div class="chart-card">
                <h3>Malnutrition Risk Levels</h3>
                <p class="chart-description">Distribution of children by malnutrition risk severity based on screening assessments. Higher risk levels indicate greater nutritional intervention needs.</p>
                <div class="donut-chart-container">
                    <div class="donut-chart">
                        <div class="donut-chart-bg" id="risk-chart-bg"></div>
                        <div class="donut-center-text" id="risk-center-text">0%</div>
                        <div class="percentage-labels" id="percentage-labels"></div>
                    </div>
                </div>
                <div class="segments" id="risk-segments"></div>
            </div>
            

            
            <div class="chart-card">
                <h3>Critical Alerts</h3>
                <p class="chart-description">Priority cases requiring immediate medical attention based on clinical indicators and screening results.</p>
                <ul class="alert-list" id="critical-alerts">
                    <!-- Critical alerts will be added dynamically -->
                </ul>
            </div>
        </div>



        <div class="chart-row">
            <div class="chart-card geo-distribution-card">
                <h3>Geographic Distribution</h3>
                <p class="chart-description">User distribution by barangay showing percentage of total users. Red indicators show SAM cases per barangay.</p>
                <div class="geo-chart-container">
                    <div class="geo-bars" id="barangay-prevalence"></div>
                </div>
            </div>
            
            <div class="chart-card">
                <h3>Dietary Diversity Score</h3>
                <p class="chart-description">Minimum Dietary Diversity for Women (MDD-W) - evidence-based indicator of dietary quality and nutritional adequacy.</p>
                <div id="dds-issues-chart"></div>
            </div>
        </div>





        <!-- Community Programs Section -->
        <div class="chart-row" style="margin-bottom: 30px; display: block; gap: 0;">
            <div class="chart-card" style="grid-column: 1 / -1; margin: 0; width: 100%; min-height: 450px; max-height: none !important; padding: 20px; overflow: visible; box-sizing: border-box;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <div>
                        <h3>Intelligent Community Programs</h3>
                        <p class="chart-description">AI-generated nutrition intervention programs based on real-time community data analysis</p>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button id="generate-programs-btn" class="generate-programs-btn" onclick="generateIntelligentPrograms()">
                            <span class="btn-text">Generate Programs</span>
                        </button>
                        <button id="create-program-btn" class="create-program-btn" onclick="createNewProgram()">
                            <span class="btn-text">Create Program</span>
                        </button>

                    </div>
                </div>
                
                <!-- Initial State -->
                <div id="programs-loading" class="programs-loading" style="display: flex; justify-content: center; align-items: center; height: 150px;">
                    <div style="text-align: center;">
                        <div class="loading-spinner"></div>
                        <p style="margin-top: 10px; color: var(--color-text); opacity: 0.7;">Analyzing community data and generating intelligent programs...</p>
                    </div>
                </div>
                

                
                <!-- Dynamic Program Cards Container -->
                <div id="intelligent-program-cards" class="program-cards-container" style="gap: 12px; margin-top: 12px; display: none;">
                    <!-- Programs will be dynamically generated here -->
                </div>
                

            </div>
        </div>



        <!-- Screening Responses Section -->
        <div class="chart-row" style="margin-top: 30px; clear: both;">
            <div style="grid-column: 1 / -1; margin-bottom: 30px;">

                
                <!-- Unified Screening Responses Grid -->
                    <div class="response-grid">
                        <div class="response-item">
                            <div class="response-question">Age Group Distribution</div>
                            <div class="response-answers" id="age-group-responses">
                            <div class="column-headers">
                                <span class="header-label">Age Group</span>
                                <span class="header-count">Count</span>
                                <span class="header-percent">Percentage</span>
                            </div>
                            <div class="response-data-container">
                                <?php if (!empty($screeningResponsesData['age_groups'])): ?>
                                    <?php foreach ($screeningResponsesData['age_groups'] as $ageGroup): ?>
                                        <div class="response-answer-item">
                                            <span class="answer-label"><?php echo htmlspecialchars($ageGroup['age_group']); ?></span>
                                            <span class="answer-count"><?php echo $ageGroup['count']; ?></span>
                                            <span class="answer-percentage"><?php echo round(($ageGroup['count'] / $timeFrameData['total_screened']) * 100, 1); ?>%</span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="no-data-message">No age data available for selected time frame</div>
                                <?php endif; ?>
                            </div>
                            </div>
                        </div>
                        
                        <div class="response-item">
                            <div class="response-question">Gender Distribution</div>
                            <div class="response-answers" id="gender-responses">
                            <div class="column-headers">
                                <span class="header-label">Gender</span>
                                <span class="header-count">Count</span>
                                <span class="header-percent">Percentage</span>
                            </div>
                            <div class="response-data-container">
                                <?php if (!empty($screeningResponsesData['gender_distribution'])): ?>
                                    <?php foreach ($screeningResponsesData['gender_distribution'] as $gender): ?>
                                        <div class="response-answer-item">
                                            <span class="answer-label"><?php echo htmlspecialchars($gender['gender']); ?></span>
                                            <span class="answer-count"><?php echo $gender['count']; ?></span>
                                            <span class="answer-percentage"><?php echo round(($gender['count'] / $timeFrameData['total_screened']) * 100, 1); ?>%</span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="no-data-message">No gender data available for selected time frame</div>
                                <?php endif; ?>
                            </div>
                            </div>
                        </div>
                        
                        <div class="response-item">
                            <div class="response-question">Income Level Distribution</div>
                            <div class="response-answers" id="income-responses">
                            <div class="column-headers">
                                <span class="header-label">Income Level</span>
                                <span class="header-count">Count</span>
                                <span class="header-percent">Percentage</span>
                            </div>
                                                    <div class="response-data-container">
                            <?php if (!empty($screeningResponsesData['income_levels'])): ?>
                                <?php foreach ($screeningResponsesData['income_levels'] as $income): ?>
                                    <div class="response-answer-item">
                                        <span class="answer-label"><?php echo htmlspecialchars($income['income']); ?></span>
                                        <span class="answer-count"><?php echo $income['count']; ?></span>
                                        <span class="answer-percentage"><?php echo round(($income['count'] / $timeFrameData['total_screened']) * 100, 1); ?>%</span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-data-message">No income data available for selected time frame</div>
                            <?php endif; ?>
                        </div>
                            </div>
                        </div>
                        
                        <div class="response-item">
                            <div class="response-question">Height Distribution</div>
                            <div class="response-answers" id="height-responses">
                            <div class="column-headers">
                                <span class="header-label">Height Range</span>
                                <span class="header-count">Count</span>
                                <span class="header-percent">Percentage</span>
                            </div>
                            <div class="response-data-container">
                            <?php if (!empty($screeningResponsesData['height_distribution'])): ?>
                                <?php foreach ($screeningResponsesData['height_distribution'] as $height): ?>
                                    <div class="response-answer-item">
                                        <span class="answer-label"><?php echo htmlspecialchars($height['height_range']); ?></span>
                                        <span class="answer-count"><?php echo $height['count']; ?></span>
                                        <span class="answer-percentage"><?php echo round(($height['count'] / $timeFrameData['total_screened']) * 100, 1); ?>%</span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-data-message">No height data available for selected time frame</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                        <div class="response-item">
                            <div class="response-question">Swelling (Edema)</div>
                            <div class="response-answers" id="swelling-responses">
                            <div class="column-headers">
                                <span class="header-label">Swelling Status</span>
                                <span class="header-count">Count</span>
                                <span class="header-percent">Percentage</span>
                            </div>
                            <div class="response-data-container">
                                <div class="loading-placeholder">Loading swelling data...</div>
                            </div>
                            </div>
                        </div>
                        
                        <div class="response-item">
                            <div class="response-question">Weight Loss Status</div>
                            <div class="response-answers" id="weight-loss-responses">
                            <div class="column-headers">
                                <span class="header-label">Weight Loss</span>
                                <span class="header-count">Count</span>
                                <span class="header-percent">Percentage</span>
                            </div>
                            <div class="response-data-container">
                                <div class="loading-placeholder">Loading weight loss data...</div>
                            </div>
                            </div>
                        </div>
                        
                        <div class="response-item">
                            <div class="response-question">Feeding Behavior</div>
                            <div class="response-answers" id="feeding-behavior-responses">
                            <div class="column-headers">
                                <span class="header-label">Feeding Status</span>
                                <span class="header-count">Count</span>
                                <span class="header-percent">Percentage</span>
                            </div>
                            <div class="response-data-container">
                                <div class="loading-placeholder">Loading feeding behavior data...</div>
                            </div>
                            </div>
                        </div>
                        
                        <div class="response-item">
                            <div class="response-question">Physical Signs Assessment</div>
                            <div class="response-answers" id="physical-signs-responses">
                            <div class="column-headers">
                                <span class="header-label">Physical Signs</span>
                                <span class="header-count">Count</span>
                                <span class="header-percent">Percentage</span>
                            </div>
                            <div class="response-data-container">
                                <div class="loading-placeholder">Loading physical signs data...</div>
                            </div>
                            </div>
                        </div>
                        
                        <div class="response-item">
                            <div class="response-question">Dietary Diversity Score</div>
                            <div class="response-answers" id="dietary-diversity-responses">
                            <div class="column-headers">
                                <span class="header-label">Dietary Score</span>
                                <span class="header-count">Count</span>
                                <span class="header-percent">Percentage</span>
                            </div>
                            <div class="response-data-container">
                                <div class="loading-placeholder">Loading dietary diversity data...</div>
                            </div>
                            </div>
                        </div>
                        
                        <div class="response-item">
                            <div class="response-question">Clinical Risk Factors</div>
                            <div class="response-answers" id="clinical-risk-responses">
                            <div class="column-headers">
                                <span class="header-label">Risk Factors</span>
                                <span class="header-count">Count</span>
                                <span class="header-percent">Percentage</span>
                            </div>
                            <div class="response-data-container">
                                <div class="loading-placeholder">Loading clinical risk data...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Custom Dropdown Functions
        function toggleDropdown() {
            const dropdown = document.getElementById('dropdown-content');
            const arrow = document.querySelector('.dropdown-arrow');
            const selectHeader = document.querySelector('.select-header');
            
            if (dropdown && arrow) {
                dropdown.classList.toggle('active');
                arrow.classList.toggle('active');
            }
        }

        function selectOption(value, text) {
            const selectedOption = document.getElementById('selected-option');
            const dropdownContent = document.getElementById('dropdown-content');
            const dropdownArrow = document.querySelector('.dropdown-arrow');
            
            if (selectedOption && dropdownContent && dropdownArrow) {
                selectedOption.textContent = text;
                dropdownContent.classList.remove('active');
                dropdownArrow.classList.remove('active');
                
                // Update dashboard data based on selected barangay or municipality
                updateDashboardForBarangay(value);
                
                // Test municipality filtering if a municipality is selected
                if (value && value.startsWith('MUNICIPALITY_')) {
                    // Municipality filtering handled by updateDashboardForBarangay
                }
                
                // Update selected state
                document.querySelectorAll('.option-item').forEach(item => {
                    item.classList.remove('selected');
                });
                
                // Try to find the clicked item and mark it as selected
                const clickedItem = document.querySelector(`[data-value="${value}"]`);
                if (clickedItem) {
                    clickedItem.classList.add('selected');
                }
                
                // If "All Barangays" is selected, clear the localStorage
                if (!value || value === '') {
                    localStorage.removeItem('selectedBarangay');
                }
            }
        }

        function filterOptions() {
            const searchInput = document.getElementById('search-input');
            const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
            const optionItems = document.querySelectorAll('.option-item');
            
            if (optionItems.length === 0) {
                return;
            }
            
            optionItems.forEach((item) => {
                const text = item.textContent.toLowerCase();
                const matches = text.includes(searchTerm);
                
                if (matches) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const container = document.querySelector('.custom-select-container');
            
            if (container && !container.contains(event.target)) {
                const dropdown = document.getElementById('dropdown-content');
                const arrow = document.querySelector('.dropdown-arrow');
                
                if (dropdown && arrow) {
                    dropdown.classList.remove('active');
                    arrow.classList.remove('active');
                }
            }
        });

        // Barangay and Municipality selection handling - will be called from main DOMContentLoaded
        function setupBarangaySelection() {
            // Set up click handlers for option items
            const optionItems = document.querySelectorAll('.option-item');
            
            if (optionItems.length === 0) {
                // Try to find option items with a different selector
                const alternativeOptions = document.querySelectorAll('[data-value]');
                
                if (alternativeOptions.length > 0) {
                    alternativeOptions.forEach((item) => {
                        item.addEventListener('click', function() {
                            const value = this.getAttribute('data-value');
                            const text = this.textContent;
                            selectOption(value, text);
                        });
                    });
                }
            } else {
                optionItems.forEach((item) => {
                    item.addEventListener('click', function() {
                        const value = this.getAttribute('data-value');
                        const text = this.textContent;
                        selectOption(value, text);
                    });
                });
            }
        }

        // Global variable to store the currently selected barangay
        let currentSelectedBarangay = '';
        
        // Function to restore selected barangay from localStorage
        function restoreSelectedBarangay() {
            try {
                const savedBarangay = localStorage.getItem('selectedBarangay');
                if (savedBarangay) {
                    currentSelectedBarangay = savedBarangay;
                    
                    // Update the dropdown display to show the saved selection
                    const selectedOptionElement = document.getElementById('selected-option');
                    if (selectedOptionElement) {
                        // Find the corresponding option text for the saved value
                        const optionItem = document.querySelector(`[data-value="${savedBarangay}"]`);
                        if (optionItem) {
                            selectedOptionElement.textContent = optionItem.textContent;
                        }
                    }
                    
                    // Mark the saved option as selected in the dropdown
                    document.querySelectorAll('.option-item').forEach(item => {
                        item.classList.remove('selected');
                        if (item.getAttribute('data-value') === savedBarangay) {
                            item.classList.add('selected');
                        }
                    });
                    
                    return true; // Indicate successful restoration
                } else {
                    return false; // Indicate no restoration needed
                }
            } catch (error) {
                return false; // Indicate restoration failed
            }
        }
        
        // Function to clear barangay selection
        function clearBarangaySelection() {
            currentSelectedBarangay = '';
            localStorage.removeItem('selectedBarangay');
            
            // Reset dropdown display
            const selectedOptionElement = document.getElementById('selected-option');
            if (selectedOptionElement) {
                selectedOptionElement.textContent = 'All Barangays';
            }
            
            // Clear selected state from all options
            document.querySelectorAll('.option-item').forEach(item => {
                item.classList.remove('selected');
            });
            
            // Refresh dashboard with no barangay filter
            updateDashboardForBarangay('');
        }
        
        // Function to get current barangay selection
        function getCurrentBarangay() {
            return currentSelectedBarangay;
        }
        
        // Function to check if a barangay is currently selected
        function isBarangaySelected() {
            return currentSelectedBarangay && currentSelectedBarangay !== '';
        }
        
        // Function to update dashboard data based on selected barangay
        function updateDashboardForBarangay(barangay) {
            // Store the selected barangay globally
            if (barangay !== undefined && barangay !== null) {
                currentSelectedBarangay = barangay;
                
                // Also store in localStorage for persistence across page refreshes
                if (barangay !== '') {
                    localStorage.setItem('selectedBarangay', barangay);
                } else {
                    localStorage.removeItem('selectedBarangay');
                }
            }
            
            // Update the "Programs in Barangay" metric
            updateProgramsMetric(barangay);
            
            // Update all charts and metrics for the selected barangay
            updateCommunityMetrics(barangay);
            
            // Update all charts and metrics for the selected barangay
            updateCharts(barangay);
            
            // Update analysis section
            updateAnalysisSection(barangay);
            
            // Update geographic distribution chart
            updateGeographicChart(barangay);
            
            // Update critical alerts
            updateCriticalAlerts(barangay);
            
            // Automatically refresh intelligent programs for the selected location
            updateIntelligentPrograms(barangay);
            
            // Update screening responses for the selected barangay
            setTimeout(() => {
                loadScreeningResponses(barangay);
            }, 1000);
        }

        // Function to calculate total programs across all areas
        async function calculateTotalPrograms() {
            try {
                // Get total users to estimate programs
                const data = await fetchDataFromAPI('community_metrics');
                if (data && data.success && data.total_screened > 0) {
                    // Estimate 1 program per 10 users, minimum 1
                    return Math.max(1, Math.ceil(data.total_screened / 10));
                } else {
                    // No users = no programs
                    return 0;
                }
            } catch (error) {
                return 0;
            }
        }

        // Function to update programs metric
        async function updateProgramsMetric(barangay) {
            const programsElement = document.getElementById('programs-in-barangay');
            const programsChangeElement = document.getElementById('programs-change');
            
            if (programsElement && programsChangeElement) {
                
                if (barangay && barangay !== '') {
                    // Handle municipality selections
                    if (barangay.startsWith('MUNICIPALITY_')) {
                        const municipality = barangay.replace('MUNICIPALITY_', '');
                        let programCount = 0;
                        
                        // Calculate total programs for the entire municipality
                        switch (municipality) {
                            case 'ABUCAY':
                                programCount = 10; // 10 barangays × 1 program each
                                break;
                            case 'BAGAC':
                                programCount = 15; // 15 barangays × 1 program each
                                break;
                            case 'BALANGA':
                                programCount = 25; // 25 barangays × 1 program each
                                break;
                            case 'DINALUPIHAN':
                                programCount = 46; // 46 barangays × 1 program each
                                break;
                            case 'HERMOSA':
                                programCount = 23; // 23 barangays × 1 program each
                                break;
                            case 'LIMAY':
                                programCount = 12; // 12 barangays × 1 program each
                                break;
                            case 'MARIVELES':
                                programCount = 19; // 19 barangays × 1 program each
                                break;
                            case 'MORONG':
                                programCount = 6; // 6 barangays × 1 program each
                                break;
                            case 'ORANI':
                                programCount = 32; // 32 barangays × 1 program each
                                break;
                            case 'ORION':
                                programCount = 23; // 23 barangays × 1 program each
                                break;
                            case 'PILAR':
                                programCount = 19; // 19 barangays × 1 program each
                                break;
                            case 'SAMAL':
                                programCount = 14; // 14 barangays × 1 program each
                                break;
                            default:
                                programCount = 0;
                        }
                        
                        programsElement.textContent = programCount;
                        programsChangeElement.textContent = 'Municipality';
                    } else {
                        // Handle individual barangay selections
                        let programCount = 0;
                        // Since we removed duplicate municipality names, we can use simpler logic
                        if (barangay.includes('Bagumbayan') || barangay.includes('Poblacion') || barangay.includes('Central')) {
                            programCount = 3; // More programs in major areas
                        } else if (barangay.includes('Bangal') || barangay.includes('Bacong') || barangay.includes('Alangan')) {
                            programCount = 2; // Medium programs
                        } else {
                            programCount = 1; // Basic programs
                        }
                        
                        programsElement.textContent = programCount;
                        programsChangeElement.textContent = 'Active';
                    }
                } else {
                    // Show total programs across all barangays
                    // Calculate total programs based on actual data instead of hardcoded value
                    const totalPrograms = await calculateTotalPrograms();
                    programsElement.textContent = totalPrograms;
                    programsChangeElement.textContent = 'All areas';
                }
            }
        }

        // Function to update community metrics
        async function updateCommunityMetrics(barangay = '') {
            try {
                const params = {};
                if (barangay && barangay !== '') {
                    params.barangay = barangay;
                    
                    if (barangay.startsWith('MUNICIPALITY_')) {
                        const municipality = barangay.replace('MUNICIPALITY_', '');
                    }
                }
                const data = await fetchDataFromAPI('community_metrics', params);
                
                if (data && data.success) {
                    // Update Total Screened
                    const totalScreened = document.getElementById('community-total-screened');
                    const screenedChange = document.getElementById('community-screened-change');
                    if (totalScreened && screenedChange) {
                        totalScreened.textContent = data.data.total_screenings || 0;
                        screenedChange.textContent = data.data.recent_activity.screenings_this_week || 0;
                    }

                    // Update High Risk Cases
                    const highRisk = document.getElementById('community-high-risk');
                    const riskChange = document.getElementById('community-risk-change');
                    if (highRisk && riskChange) {
                        highRisk.textContent = data.data.risk_distribution.high || 0;
                        riskChange.textContent = data.data.risk_distribution.moderate || 0;
                    }

                    // Update SAM Cases (using moderate risk as proxy)
                    const samCases = document.getElementById('community-sam-cases');
                    const samChange = document.getElementById('community-sam-change');
                    if (samCases && samChange) {
                        samCases.textContent = data.data.risk_distribution.moderate || 0;
                        samChange.textContent = data.data.risk_distribution.low || 0;
                    }
                    
                    // Calculate average risk score from distribution
                    const totalRisk = (data.data.risk_distribution.high * 75) + (data.data.risk_distribution.moderate * 50) + (data.data.risk_distribution.low * 25);
                    const totalUsers = data.data.risk_distribution.high + data.data.risk_distribution.moderate + data.data.risk_distribution.low;
                    const avgRiskScore = totalUsers > 0 ? totalRisk / totalUsers : 0;
                    
                    // Store the average risk score globally for use in charts
                    window.globalAverageRiskScore = avgRiskScore;
                    
                    // Update the risk chart center text immediately if it exists
                    const riskCenterText = document.getElementById('risk-center-text');
                    if (riskCenterText && window.globalAverageRiskScore > 0) {
                        riskCenterText.textContent = Math.round(window.globalAverageRiskScore) + '%';
                    }
                    
                    // Note: Critical alerts are now handled by updateCriticalAlerts() function
                }
            } catch (error) {
                // Error handling for community metrics update
            }
        }

        // Function to update charts
        async function updateCharts(barangay = '') {
            try {
                const params = {};
                if (barangay && barangay !== '') {
                    params.barangay = barangay;
                }

                // Update Risk Distribution Chart
                const riskData = await fetchDataFromAPI('risk_distribution', params);
                if (riskData && riskData.success) {
                    updateRiskChart(riskData.data);
                }

                // Update Screening Responses (Age, Gender, Income, Height, Swelling, Weight Loss, Feeding, Physical Signs, Dietary, Clinical)
                const screeningData = await fetchDataFromAPI('detailed_screening_responses', params);
                if (screeningData && screeningData.success) {
                    updateScreeningResponsesDisplay(screeningData.data);
                }

                // Update Geographic Distribution Chart
                const geoData = await fetchDataFromAPI('geographic_distribution', params);
                if (geoData && geoData.success) {
                    updateGeographicChartDisplay(geoData.data);
                }

                // Update Critical Alerts
                const alertsData = await fetchDataFromAPI('critical_alerts', params);
                if (alertsData && alertsData.success) {
                    updateCriticalAlertsDisplay(alertsData.data);
                }
                
                // Update Nutritional Status Overview Card
                updateNutritionalStatusCard([], []);
            } catch (error) {
                // Error handling for charts update
            }
        }

        // Function to update geographic distribution
        async function updateGeographicChart(barangay = '') {
            try {
                const params = {};
                if (barangay && barangay !== '') {
                    params.barangay = barangay;
                }
                const data = await fetchDataFromAPI('geographic_distribution', params);
                if (data && data.success) {
                    updateGeographicChartDisplay(data.data);
                }
            } catch (error) {
                // Error handling for geographic chart update
            }
        }



        // Function to update geographic distribution display
        function updateGeographicChartDisplay(data) {
            const container = document.getElementById('barangay-prevalence');
            if (!container) return;

            container.innerHTML = '';
            
            if (data && data.length > 0) {
                data.forEach(item => {
                    const barItem = document.createElement('div');
                    barItem.className = 'geo-bar-item';
                    
                    // Calculate percentage based on count
                    const maxCount = Math.max(...data.map(d => d.count));
                    const percentage = maxCount > 0 ? Math.round((item.count / maxCount) * 100) : 0;
                    
                    barItem.innerHTML = `
                        <div class="geo-bar-name">${item.barangay}</div>
                        <div class="geo-bar-progress">
                            <div class="geo-bar-fill" style="width: ${percentage}%"></div>
                        </div>
                        <div class="geo-bar-percentage">${item.count}</div>
                    `;
                    container.appendChild(barItem);
                });
            } else {
                // Show no data message
                const noDataItem = document.createElement('div');
                noDataItem.style.cssText = `
                    padding: 15px;
                    text-align: center;
                    color: var(--color-text);
                    opacity: 0.7;
                    font-style: italic;
                `;
                noDataItem.textContent = 'No geographic data available for selected area';
                container.appendChild(noDataItem);
            }
        }

        // Function to update critical alerts
        async function updateCriticalAlerts(barangay = '') {
            try {
                // Debounce rapid successive calls to prevent flickering
                if (updateCriticalAlerts.debounceTimer) {
                    clearTimeout(updateCriticalAlerts.debounceTimer);
                }
                
                updateCriticalAlerts.debounceTimer = setTimeout(async () => {
                    const params = {};
                    if (barangay && barangay !== '') {
                        params.barangay = barangay;
                    }

                    const data = await fetchDataFromAPI('critical_alerts', params);
                    console.log('Critical alerts data received:', data);
                    
                    if (data && data.success) {
                        updateCriticalAlertsDisplay(data.data);
                    }
                }, 300); // 300ms debounce delay
                
            } catch (error) {
                console.error('Error updating critical alerts:', error);
            }
        }

        // Function to generate intelligent programs (manual trigger)
        async function generateIntelligentPrograms(barangay = null) {
            // Use passed barangay parameter or fall back to currently selected barangay
            const targetBarangay = barangay !== null ? barangay : currentSelectedBarangay;
            await updateIntelligentPrograms(targetBarangay);
        }

        // Function to show AI reasoning in a popup
        function showAIReasoning(title, reasoning) {
            // Create modal overlay
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 1000;
                opacity: 0;
                transition: opacity 0.3s ease;
            `;
            
            // Create modal content
            const modalContent = document.createElement('div');
            modalContent.style.cssText = `
                background: var(--color-bg, white);
                padding: 25px;
                border-radius: 15px;
                max-width: 500px;
                width: 90%;
                box-shadow: 0 10px 30px rgba(161, 180, 84, 0.2);
                border: 1px solid rgba(161, 180, 84, 0.1);
                transform: translateY(20px);
                transition: transform 0.3s ease;
            `;
            
            modalContent.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0; color: #1B3A1B; font-size: 18px; font-weight: 600;">AI Reasoning</h3>
                    <button onclick="this.closest('.ai-reasoning-modal').remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--color-text); opacity: 0.7; transition: opacity 0.2s ease;">&times;</button>
                </div>
                <div style="margin-bottom: 15px; padding: 12px; background: rgba(161, 180, 84, 0.05); border-radius: 8px; border: 1px solid rgba(161, 180, 84, 0.1);">
                    <strong style="color: #1B3A1B; font-size: 14px;">Program:</strong>
                    <span style="color: var(--color-text); margin-left: 8px; font-weight: 500;">${title}</span>
                </div>
                <div style="background: rgba(161, 180, 84, 0.08); padding: 18px; border-radius: 12px; border-left: 4px solid var(--color-highlight); box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);">
                    <p style="margin: 0; color: var(--color-text); line-height: 1.6; font-style: italic; font-size: 14px;">${reasoning}</p>
                </div>
                <div style="text-align: center; margin-top: 25px;">
                    <button onclick="this.closest('.ai-reasoning-modal').remove()" style="background: linear-gradient(135deg, var(--color-highlight), var(--color-accent1)); color: white; border: none; padding: 12px 24px; border-radius: 25px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(161, 180, 84, 0.3);">
                        Close
                    </button>
                </div>
            `;
            
            // Add modal to page
            modal.appendChild(modalContent);
            modal.className = 'ai-reasoning-modal';
            document.body.appendChild(modal);
            
            // Animate in
            setTimeout(() => {
                modal.style.opacity = '1';
                modalContent.style.transform = 'translateY(0)';
            }, 10);
            
            // Close on background click
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }

        // Function to open notification modal for critical alerts
        function openNotificationModal(userName, userEmail, alertTitle) {
            // Validate inputs
            if (!userName || !userEmail) {
                console.error('Missing user information:', { userName, userEmail, alertTitle });
                alert('Error: Missing user information for notification');
                return;
            }
            
            console.log('Opening notification modal for:', { userName, userEmail, alertTitle });
            
            // Create modal overlay
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 1000;
                opacity: 0;
                transition: opacity 0.3s ease;
            `;
            
            // Create modal content
            const modalContent = document.createElement('div');
            modalContent.style.cssText = `
                background: var(--color-card);
                padding: 25px;
                border-radius: 15px;
                max-width: 500px;
                width: 90%;
                box-shadow: 0 10px 30px rgba(161, 180, 84, 0.2);
                border: 1px solid rgba(161, 180, 84, 0.1);
                transform: translateY(20px);
                transition: transform 0.3s ease;
            `;
            
            // Escape special characters to prevent XSS
            const safeUserName = userName.replace(/'/g, "\\'").replace(/"/g, '\\"');
            const safeAlertTitle = alertTitle.replace(/'/g, "\\'").replace(/"/g, '\\"');
            const safeUserEmail = userEmail.replace(/'/g, "\\'").replace(/"/g, '\\"');
            
            modalContent.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0; color: #1B3A1B; font-size: 18px; font-weight: 600;">📱 Send Notification</h3>
                    <button onclick="this.closest('.notification-modal').remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--color-text); opacity: 0.7; transition: opacity 0.2s ease;">&times;</button>
                </div>
                
                <div style="margin-bottom: 20px; padding: 15px; background: rgba(161, 180, 84, 0.05); border-radius: 8px; border: 1px solid rgba(161, 180, 84, 0.1);">
                    <div style="margin-bottom: 8px;">
                        <strong style="color: #1B3A1B; font-size: 14px;">Recipient:</strong>
                        <span style="color: var(--color-text); margin-left: 8px; font-weight: 500;">${safeUserName}</span>
                    </div>
                    <div>
                        <strong style="color: #1B3A1B; font-size: 14px;">Alert:</strong>
                        <span style="color: var(--color-text); margin-left: 8px; font-weight: 500;">${safeAlertTitle}</span>
                    </div>
                    <div style="margin-top: 8px;">
                        <strong style="color: #1B3A1B; font-size: 14px;">Email:</strong>
                        <span style="color: var(--color-text); margin-left: 8px; font-weight: 500;">${safeUserEmail}</span>
                    </div>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; color: var(--color-text); font-weight: 500;">Notification Message:</label>
                    <textarea id="notification-message" placeholder="Enter your message to this user..." style="width: 100%; min-height: 100px; padding: 12px; border: 1px solid rgba(161, 180, 84, 0.2); border-radius: 8px; background: rgba(42, 51, 38, 0.3); color: var(--color-text); font-size: 14px; resize: vertical; font-family: inherit;"></textarea>
                </div>
                
                <div style="display: flex; gap: 15px; justify-content: flex-end;">
                    <button onclick="this.closest('.notification-modal').remove()" style="background: rgba(161, 180, 84, 0.2); color: var(--color-text); border: none; padding: 12px 24px; border-radius: 25px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s ease;">
                        Cancel
                    </button>
                    <button onclick="sendPersonalNotification('${safeUserName}', '${safeUserEmail}', '${safeAlertTitle}')" style="background: linear-gradient(135deg, var(--color-highlight), var(--color-accent3)); color: white; border: none; padding: 12px 24px; border-radius: 25px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(161, 180, 84, 0.3);">
                        📱 Send Notification
                    </button>
                </div>
            `;
            
            // Add modal to page
            modal.appendChild(modalContent);
            modal.className = 'notification-modal';
            document.body.appendChild(modal);
            
            // Animate in
            setTimeout(() => {
                modal.style.opacity = '1';
                modalContent.style.transform = 'translateY(0)';
            }, 10);
            
            // Close on background click
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }

        // Function to send personal notification
        async function sendPersonalNotification(userName, userEmail, alertTitle) {
            const messageInput = document.getElementById('notification-message');
            const message = messageInput ? messageInput.value.trim() : '';
            
            if (!message) {
                alert('Please enter a message before sending the notification.');
                return;
            }
            
            if (!userEmail) {
                alert('Cannot send notification: User email not available.');
                return;
            }
            
            try {
                console.log('Sending notification to:', { userName, userEmail, alertTitle, message });
                
                // Create a custom event notification using the event.php system
                const notificationData = {
                    title: `🚨 Critical Alert: ${alertTitle}`,
                    body: message,
                    target_user: userEmail,
                    alert_type: 'critical_notification',
                    user_name: userName
                };
                
                console.log('Notification data:', notificationData);
                
                // Send to the event.php notification system
                const response = await fetch('event.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        action: 'send_personal_notification',
                        notification_data: JSON.stringify(notificationData)
                    })
                });
                
                console.log('Response status:', response.status);
                const result = await response.json();
                console.log('Response result:', result);
                
                if (result.success) {
                    // Show success message
                    showNotificationSuccess(`Notification sent successfully to ${userName}!`);
                    
                    // Close the modal
                    const modal = document.querySelector('.notification-modal');
                    if (modal) modal.remove();
                } else {
                    // Show error message
                    showNotificationError(`Failed to send notification: ${result.message || 'Unknown error'}`);
                }
                
            } catch (error) {
                console.error('Error sending notification:', error);
                showNotificationError('Error sending notification. Please try again.');
            }
        }

        // Function to show notification success
        function showNotificationSuccess(message) {
            const successDiv = document.createElement('div');
            successDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, var(--color-highlight), var(--color-accent3));
                color: white;
                padding: 15px 20px;
                border-radius: 10px;
                box-shadow: 0 4px 15px rgba(161, 180, 84, 0.3);
                z-index: 1001;
                max-width: 300px;
                animation: slideInRight 0.3s ease;
            `;
            successDiv.innerHTML = `✅ ${message}`;
            
            document.body.appendChild(successDiv);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (successDiv.parentNode) {
                    successDiv.style.animation = 'slideOutRight 0.3s ease';
                    setTimeout(() => {
                        if (successDiv.parentNode) {
                            successDiv.parentNode.removeChild(successDiv);
                        }
                    }, 300);
                }
            }, 5000);
        }

        // Function to show notification error
        function showNotificationError(message) {
            const errorDiv = document.createElement('div');
            errorDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, var(--color-danger), #e74c3c);
                color: white;
                padding: 15px 20px;
                border-radius: 10px;
                box-shadow: 0 4px 15px rgba(207, 134, 134, 0.3);
                z-index: 1001;
                max-width: 300px;
                animation: slideInRight 0.3s ease;
            `;
            errorDiv.innerHTML = `❌ ${message}`;
                
            document.body.appendChild(errorDiv);
                
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (errorDiv.parentNode) {
                    errorDiv.style.animation = 'slideOutRight 0.3s ease';
                    setTimeout(() => {
                        if (errorDiv.parentNode) {
                            errorDiv.parentNode.removeChild(errorDiv);
                        }
                    }, 300);
                }
            }, 5000);
        }

        // Function to update intelligent programs
        async function updateIntelligentPrograms(barangay = '') {
            try {
                
                // Show loading state
                const loadingElement = document.getElementById('programs-loading');
                const programsContainer = document.getElementById('intelligent-program-cards');
                
                if (loadingElement && programsContainer) {
                    loadingElement.style.display = 'flex';
                    programsContainer.style.display = 'none';
                }
                
                const params = {};
                if (barangay && barangay !== '') {
                    if (barangay.startsWith('MUNICIPALITY_')) {
                        params.municipality = barangay.replace('MUNICIPALITY_', '');
                    } else {
                        params.barangay = barangay;
                    }
                } else {
                }

                const data = await fetchDataFromAPI('ai_food_recommendations', params);
                
                if (data && data.success) {
                    // Map AI food recommendations to programs format
                    const programs = data.data || [];
                    const analysis = {
                        total_users: programs.length,
                        high_risk_percentage: 0,
                        sam_cases: 0,
                        children_count: 0,
                        elderly_count: 0,
                        low_dietary_diversity: 0,
                        average_risk: 0,
                        community_health_status: 'Active',
                        message: `Generated ${programs.length} AI food recommendations based on community data`
                    };
                    updateIntelligentProgramsDisplay(programs, analysis);
                } else {
                    // Show appropriate no-data message
                    showFallbackPrograms();
                }
            } catch (error) {
                // Show fallback programs on error
                showFallbackPrograms();
            }
        }

        // Function to update intelligent programs display
        function updateIntelligentProgramsDisplay(programs, analysis) {
            const loadingElement = document.getElementById('programs-loading');
            const programsContainer = document.getElementById('intelligent-program-cards');
            const debugElement = document.getElementById('community-health-debug');
            const debugContent = document.getElementById('debug-content');
            
            if (!loadingElement || !programsContainer) {
                return;
            }
            
            // Hide loading, show programs
            loadingElement.style.display = 'none';
            programsContainer.style.display = 'block';
            
            if (debugElement && debugContent && analysis) {
                debugElement.style.display = 'block';
                
                const debugInfo = `
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <div><strong>Total Users:</strong> ${analysis.total_users || 0}</div>
                        <div><strong>High Risk %:</strong> ${analysis.high_risk_percentage || 0}%</div>
                        <div><strong>SAM Cases:</strong> ${analysis.sam_cases || 0}</div>
                        <div><strong>Children:</strong> ${analysis.children_count || 0}</div>
                        <div><strong>Elderly:</strong> ${analysis.elderly_count || 0}</div>
                        <div><strong>Low Dietary Diversity:</strong> ${analysis.low_dietary_diversity || 0}</div>
                        <div><strong>Average Risk Score:</strong> ${analysis.average_risk || 0}</div>
                        <div><strong>Community Status:</strong> <span style="color: #1B3A1B;">${analysis.community_health_status || 'Unknown'}</span></div>
                        <div><strong>Programs Generated:</strong> <span style="color: var(--color-accent1); font-weight: bold;">${programs ? programs.length : 0}</span></div>
                    </div>
                    ${analysis.message ? `<div style="margin-top: 10px; padding: 10px; background: rgba(161, 180, 84, 0.1); border-radius: 6px; border-left: 3px solid var(--color-highlight);"><strong>Analysis:</strong> ${analysis.message}</div>` : ''}
                `;
                
                debugContent.innerHTML = debugInfo;
            }
            
            // Clear existing programs
            programsContainer.innerHTML = '';
            
            // Check if this is a no-data response
            if (analysis && analysis.no_data) {
                // Show no-data message
                const noDataCard = document.createElement('div');
                noDataCard.className = 'program-card';
                noDataCard.style.cssText = 'text-align: center; padding: 40px 20px; opacity: 0.8;';
                
                noDataCard.innerHTML = `
                    <div style="font-size: 18px; font-weight: 600; color: var(--color-highlight); margin-bottom: 10px;">
                        No Data Available
                    </div>
                    <div style="font-size: 14px; color: var(--color-text); opacity: 0.8; line-height: 1.5;">
                        ${analysis.message || 'No users found in the selected area. Programs will be generated once users are registered.'}
                    </div>
                    <div style="margin-top: 20px; padding: 12px; background: rgba(161, 180, 84, 0.1); border-radius: 8px; border: 1px solid rgba(161, 180, 84, 0.2);">
                        <div style="font-size: 12px; color: var(--color-text); opacity: 0.7;">
                            <strong>Tip:</strong> Register users in this area to generate intelligent nutrition programs based on real data.
                        </div>
                    </div>
                `;
                
                programsContainer.appendChild(noDataCard);
                return;
            }
            
            if (programs && programs.length > 0) {
                programs.forEach((program, index) => {
                    const programCard = createProgramCard(program, index);
                    programsContainer.appendChild(programCard);
                });
                
            } else {
                showFallbackPrograms();
            }
        }

        // Function to create individual program card
        function createProgramCard(program, index) {
            const card = document.createElement('div');
            card.className = 'program-card';
            
            // Add null checks and default values to prevent undefined display
            const foodName = program.food_name || 'Unnamed Program';
            const foodDescription = program.food_description || 'No description available';
            const foodEmoji = program.food_emoji || '🍽️';
            const nutritionalPriority = program.nutritional_priority || 'Medium';
            const nutritionalImpactScore = program.nutritional_impact_score || 50;
            const ingredients = program.ingredients || 'Ingredients not specified';
            const benefits = program.benefits || 'Benefits not specified';
            const aiReasoning = program.ai_reasoning || 'AI reasoning not available';
            
            // Determine priority class based on nutritional impact score
            let priorityClass = 'priority-medium';
            if (nutritionalImpactScore >= 85) priorityClass = 'priority-immediate';
            else if (nutritionalImpactScore >= 70) priorityClass = 'priority-high';
            
            card.innerHTML = `
                <div class="program-content">
                    <div class="program-title">${foodEmoji} ${foodName}</div>
                    <div class="program-description">${foodDescription}</div>
                    <div class="program-meta">
                        <span class="priority-tag ${priorityClass}">${getPriorityLabel(nutritionalImpactScore)}</span>
                        <div class="program-details" style="margin-top: 6px; font-size: 11px; opacity: 0.8;">
                            <div><strong>Priority:</strong> ${nutritionalPriority}</div>
                            <div><strong>Impact Score:</strong> ${nutritionalImpactScore}/100</div>
                            <div><strong>Ingredients:</strong> ${ingredients}</div>
                            <div class="target-location" style="background: rgba(161, 180, 84, 0.2); padding: 4px 8px; border-radius: 6px; margin-top: 4px; border-left: 3px solid var(--color-highlight);">
                                <strong style="color: #4CAF50;">Benefits:</strong> ${benefits}
                            </div>
                        </div>
                        <div style="display: flex; gap: 10px; margin-top: 8px;">
                            <button class="show-reasoning-btn" onclick="showAIReasoning('${foodName.replace(/'/g, "\\'")}', '${aiReasoning.replace(/'/g, "\\'")}')">
                                Show AI Reasoning
                            </button>
                            <button class="create-this-program-btn" onclick="createProgramFromCard('${foodName.replace(/'/g, "\\'")}', '${nutritionalPriority}', '${nutritionalPriority}', '${foodDescription.replace(/'/g, "\\'")}', '${getPriorityLabel(nutritionalImpactScore)}')">
                                Create This Program
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            // Add smooth animation delay
            card.style.opacity = '0';
            card.style.transform = 'translateY(10px)';
            card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            
            // Use requestAnimationFrame for smoother animation
            requestAnimationFrame(() => {
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            // Remove inline transform after animation to allow CSS hover effects
            setTimeout(() => {
                card.style.removeProperty('transform');
                card.style.removeProperty('transition');
            }, (index * 100) + 300);
            
            return card;
        }

        // Function to get proper priority labels
        function getPriorityLabel(priority) {
            if (typeof priority === 'number') {
                // Handle nutritional impact score
                if (priority >= 85) return 'High Impact';
                if (priority >= 70) return 'Medium Impact';
                if (priority >= 50) return 'Moderate Impact';
                return 'Low Impact';
            } else {
                // Handle string priority
                switch(priority) {
                    case 'Critical':
                        return 'High Risk';
                    case 'High':
                        return 'Moderate Risk';
                    case 'Medium':
                        return 'Low Risk';
                    default:
                        return priority;
                }
            }
        }

        // Function to show fallback programs when API fails
        function showFallbackPrograms() {
            const loadingElement = document.getElementById('programs-loading');
            const programsContainer = document.getElementById('intelligent-program-cards');
            
            if (!loadingElement || !programsContainer) return;
            
            loadingElement.style.display = 'none';
            programsContainer.style.display = 'block';
            
            // Show appropriate message based on whether we have data or not
            const noDataCard = document.createElement('div');
            noDataCard.className = 'program-card';
            noDataCard.style.cssText = 'text-align: center; padding: 40px 20px; opacity: 0.8;';
            
            noDataCard.innerHTML = `
                <div style="font-size: 18px; font-weight: 600; color: var(--color-highlight); margin-bottom: 10px;">
                    No Community Data Available
                </div>
                <div style="font-size: 14px; color: var(--color-text); opacity: 0.8; line-height: 1.5;">
                    The intelligent programs system requires community data to generate targeted nutrition interventions. 
                    Programs will be automatically generated once users are registered and screened in the selected area.
                </div>
                <div style="margin-top: 20px; padding: 12px; background: rgba(161, 180, 84, 0.1); border-radius: 8px; border: 1px solid rgba(161, 180, 84, 0.2);">
                    <div style="font-size: 12px; color: var(--color-text); opacity: 0.7;">
                        <strong>Next Steps:</strong> Register users in this area to enable intelligent program generation based on real community health data.
                    </div>
                </div>
            `;
            
            programsContainer.innerHTML = '';
            programsContainer.appendChild(noDataCard);
            
        }



        // Track current alerts state to prevent unnecessary updates
        let currentAlertsState = { hasAlerts: false, lastContent: '' };
        
        // Initialize alerts state based on current DOM content
        function initializeAlertsState() {
            const container = document.getElementById('critical-alerts');
            if (container) {
                const hasExistingAlerts = container.querySelector('.alert-item:not(.no-alerts-item)');
                currentAlertsState.hasAlerts = !!hasExistingAlerts;
                currentAlertsState.lastContent = container.innerHTML;
            }
        }
        
        function clearAlertsState() {
            currentAlertsState.hasAlerts = false;
            currentAlertsState.lastContent = '';
        }

        // Function to update critical alerts display (legacy - kept for compatibility)
        function updateCriticalAlertsDisplay(data) {
            const container = document.getElementById('critical-alerts');
            if (!container) return;

            // Generate new content
            const newContent = generateCriticalAlertsHTML(data);
            const hasNewAlerts = data && data.length > 0;
            
            // Prevent flickering by checking if content is the same
            if (container.innerHTML === newContent) {
                return; // No changes needed
            }
            
            // Smart update logic: only show "no alerts" if we currently have no alerts displayed
            // and the new data also has no alerts
            const currentlyHasAlerts = currentAlertsState.hasAlerts;
            const shouldShowNoAlerts = !hasNewAlerts && !currentlyHasAlerts;
            
            // If we currently have alerts and the new data has no alerts, keep the current alerts
            // This prevents the flickering from "alerts" -> "no alerts" -> "alerts"
            if (currentlyHasAlerts && !hasNewAlerts) {
                return;
            }
            
            // Update content and state
            container.innerHTML = newContent;
            currentAlertsState.hasAlerts = hasNewAlerts;
            currentAlertsState.lastContent = newContent;
        }

        // Function to generate critical alerts HTML
        function generateCriticalAlertsHTML(data) {
            if (data && data.length > 0) {
                return data.map(alert => {
                    const title = alert.message || 'High malnutrition risk detected';
                    const user = alert.user || 'Unknown user';
                    const time = alert.time || 'Recent';
                    const type = alert.type || 'critical';
                    const userEmail = alert.user_email || '';
                    
                    return `
                        <li class="alert-item ${type}">
                            <div class="alert-content">
                                <h4>${title}</h4>
                                <p>${user} - Requires immediate attention</p>
                            </div>
                            <div class="alert-actions">
                                <div class="alert-time">${time}</div>
                                <div class="alert-buttons">
                                    <span class="alert-badge badge-${type}">${type === 'critical' ? 'Critical' : 'Warning'}</span>
                                    <button class="notify-btn" onclick="openNotificationModal('${user}', '${userEmail}', '${title}')" title="Send notification to this user">
                                        📱 Notify
                                    </button>
                                </div>
                            </div>
                        </li>
                    `;
                }).join('');
            } else {
                return `
                    <li class="no-alerts-item">
                        <div style="padding: 15px; text-align: center; color: var(--color-text); opacity: 0.7; font-style: italic;">
                            No critical alerts at this time
                        </div>
                    </li>
                `;
                }
            }

        // Function to update analysis section
        async function updateAnalysisSection(barangay = '') {
            try {
                
                const params = {};
                if (barangay && barangay !== '') {
                    params.barangay = barangay;
                } else {
                }

                const data = await fetchDataFromAPI('analysis_data', params);
                
                if (data && data.success) {
                    // Update risk analysis
                    updateRiskAnalysis(data.risk_analysis);
                    
                    // Update demographics
                    updateDemographics(data.demographics);
                } else {
                }
            } catch (error) {
            }
        }

        // Function to update risk analysis
        function updateRiskAnalysis(data) {
            if (!data) return;
            
            const totalUsers = data.total_users || 0;
            const atRiskUsers = data.at_risk_users || 0;
            
            // Calculate percentages
            const highRiskPercent = totalUsers > 0 ? Math.round((atRiskUsers / totalUsers) * 100) : 0;
            const moderateRiskPercent = totalUsers > 0 ? Math.round(((totalUsers - atRiskUsers) * 0.6 / totalUsers) * 100) : 0;
            const lowRiskPercent = totalUsers > 0 ? Math.round(((totalUsers - atRiskUsers) * 0.4 / totalUsers) * 100) : 0;
            const samPercent = totalUsers > 0 ? Math.round((atRiskUsers * 0.3 / totalUsers) * 100) : 0;
            
            // Update progress bars
            const highRiskBar = document.getElementById('high-risk-bar');
            const moderateRiskBar = document.getElementById('moderate-risk-bar');
            const lowRiskBar = document.getElementById('low-risk-bar');
            const samBar = document.getElementById('sam-bar');
            
            if (highRiskBar) {
                highRiskBar.style.width = highRiskPercent + '%';
                document.getElementById('high-risk-percent').textContent = highRiskPercent + '%';
            }
            
            if (moderateRiskBar) {
                moderateRiskBar.style.width = moderateRiskPercent + '%';
                document.getElementById('moderate-risk-percent').textContent = moderateRiskPercent + '%';
            }
            
            if (lowRiskBar) {
                lowRiskBar.style.width = lowRiskPercent + '%';
                document.getElementById('low-risk-percent').textContent = lowRiskPercent + '%';
            }
            
            if (samBar) {
                samBar.style.width = samPercent + '%';
                document.getElementById('sam-percent').textContent = samPercent + '%';
            }
        }

        // Function to update demographics
        function updateDemographics(data) {
            if (!data) return;
            
            const totalUsers = data.total_users || 0;
            
            if (totalUsers > 0) {
                // Calculate percentages for age groups
                const childrenPercent = Math.round(((data.age_0_5 || 0) + (data.age_6_12 || 0) + (data.age_13_17 || 0)) / totalUsers * 100);
                const adultsPercent = Math.round((data.age_18_59 || 0) / totalUsers * 100);
                const elderlyPercent = Math.round((data.age_60_plus || 0) / totalUsers * 100);
                
                // Update progress bars
                const childrenBar = document.getElementById('children-bar');
                const adultsBar = document.getElementById('adults-bar');
                const elderlyBar = document.getElementById('elderly-bar');
                const genderBar = document.getElementById('gender-bar');
                
                if (childrenBar) {
                    childrenBar.style.width = childrenPercent + '%';
                    document.getElementById('children-percent').textContent = childrenPercent + '%';
                }
                
                if (adultsBar) {
                    adultsBar.style.width = adultsPercent + '%';
                    document.getElementById('adults-percent').textContent = adultsPercent + '%';
                }
                
                if (elderlyBar) {
                    elderlyBar.style.width = elderlyPercent + '%';
                    document.getElementById('elderly-percent').textContent = elderlyPercent + '%';
                }
                
                if (genderBar) {
                    // For now, show a balanced distribution
                    genderBar.style.width = '50%';
                    document.getElementById('gender-distribution').textContent = '50% M, 50% F';
                }
            }
        }

        // API Connection and Data Fetching Functions
        const API_BASE_URL = window.location.origin + '/sss/api/';

        // Function to fetch data from API
        async function fetchDataFromAPI(endpoint, params = {}) {
            try {
                // Build URL for local API endpoints
                let url = `${API_BASE_URL}${endpoint}.php`;
                
                // Add query parameters if any
                if (Object.keys(params).length > 0) {
                    const queryString = new URLSearchParams(params).toString();
                    url += `?${queryString}`;
                }
                
                console.log('Fetching from API:', url);
                
                const response = await fetch(url);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('API response for', endpoint, ':', data);
                return data;
            } catch (error) {
                console.error('API error for', endpoint, ':', error);
                return null;
            }
        }

        // Function to update risk distribution chart
        function updateRiskChart(data) {
            try {
                // Get the donut chart elements
                const chartBg = document.getElementById('risk-chart-bg');
                const centerText = document.getElementById('risk-center-text');
                const segments = document.getElementById('risk-segments');
                
                if (!chartBg || !centerText || !segments) {
                    return;
                }
                
                // Add loading state to prevent flickering
                chartBg.style.opacity = '0.8';

                
                // Preserve existing segments for smooth transitions
                const existingSegments = segments.querySelectorAll('.segment');
                existingSegments.forEach(segment => {
                    segment.style.opacity = '0';
                });
                
                // Define colors and labels for risk levels - MATCHING ANDROID APP LOGIC
                // Risk thresholds: Low(0-19), Moderate(20-49), High(50-79), Severe(80+)
                const isDarkTheme = document.body.classList.contains('dark-theme');
                const colors = isDarkTheme ? [
                    '#4CAF50',      // Green for Low Risk
                    '#FF9800',      // Orange for Moderate Risk
                    '#F44336',      // Red for High Risk
                    '#D32F2F'       // Dark Red for Severe Risk
                ] : [
                    '#4CAF50',      // Green for Low Risk
                    '#FF9800',      // Orange for Moderate Risk
                    '#F44336',      // Red for High Risk
                    '#D32F2F'       // Dark Red for Severe Risk
                ];
                
                
                const labels = [
                    'Low Risk',
                    'Moderate Risk',
                    'High Risk',
                    'Severe Risk'
                ];
                
                // Handle the API data structure correctly
                // Risk thresholds: Low(0-19), Moderate(20-49), High(50-79), Severe(80+)
                let riskLevels = [0, 0, 0, 0]; // [Low, Moderate, High, Severe]
                let totalUsers = 0;
                let actualRiskScores = []; // Store actual risk scores from API
                
                if (data && data.length > 0) {
                    // API returns data in format: [{risk_level: 'Low', count: 1}, {risk_level: 'Moderate', count: 1}, {risk_level: 'High', count: 1}]
                    // Risk thresholds: Low(0-19), Moderate(20-49), High(50-79), Severe(80+)
                    data.forEach(item => {
                        totalUsers += item.count;
                        
                        // Map the risk_level to the correct index (case-insensitive)
                        const riskLevel = item.risk_level.toLowerCase();
                        if (riskLevel === 'low risk') riskLevels[0] = item.count;
                        else if (riskLevel === 'moderate risk') riskLevels[1] = item.count;
                        else if (riskLevel === 'high risk') riskLevels[2] = item.count;
                        else if (riskLevel === 'critical risk') riskLevels[3] = item.count;
                        else if (riskLevel === 'severe risk') riskLevels[3] = item.count;
                        
                        // Store actual risk scores for each user (using count as proxy)
                        // Since we don't have individual risk scores, we'll use the weighted average approach
                        const riskScore = riskLevel === 'low risk' ? 10 : 
                                        riskLevel === 'moderate risk' ? 35 : 
                                        riskLevel === 'high risk' ? 65 : 90;
                        
                        for (let i = 0; i < item.count; i++) {
                            actualRiskScores.push(riskScore);
                        }
                    });
                }
                
                // If no data from API, clear the chart properly
                if (totalUsers === 0) {
                    // Show "No Data" message in center
                    centerText.textContent = 'No Data';
                    centerText.style.color = '#999';
                    
                    // Clear the background gradient
                    chartBg.style.background = 'conic-gradient(#e0e0e0 0% 100%)';
                    chartBg.style.opacity = '0.3';
                    
                    // Clear segments and show no data message
                    segments.innerHTML = '<div style="text-align: center; color: #999; font-style: italic;">No data available for selected area</div>';
                    

                    
                    // Clear percentage labels
                    const percentageLabelsContainer = document.getElementById('percentage-labels');
                    if (percentageLabelsContainer) {
                        percentageLabelsContainer.innerHTML = '';
                    }
                    
                    // Restore opacity and return
                    chartBg.style.opacity = '1';
                    return;
                }
                
                // Calculate both: percentage of users at risk AND average risk score
                let atRiskPercentage = 0;
                let averageRisk = 0;
                
                if (totalUsers > 0) {
                    // Count users in moderate, high, and severe risk
                    // Risk thresholds: Low(0-19), Moderate(20-49), High(50-79), Severe(80+)
                    const atRiskUsers = riskLevels[1] + riskLevels[2] + riskLevels[3];
                    atRiskPercentage = Math.round((atRiskUsers / totalUsers) * 100);
                    
                    // Use the global average risk score from community metrics (more accurate)
                    if (window.globalAverageRiskScore !== undefined && window.globalAverageRiskScore > 0) {
                        averageRisk = Math.round(window.globalAverageRiskScore);
                    } else if (actualRiskScores.length > 0) {
                        // Fallback to actual risk scores from chart data if available
                        const sum = actualRiskScores.reduce((total, score) => total + score, 0);
                        averageRisk = Math.round(sum / actualRiskScores.length);
                    } else {
                        // Final fallback to weighted average if no actual scores available
                        // For 1 user with 100% risk, this should give 100
                        // Updated to match Android app risk thresholds: Low(0-19), Moderate(20-49), High(50-79), Severe(80+)
                        const weightedSum = (riskLevels[0] * 10) + (riskLevels[1] * 35) + (riskLevels[2] * 65) + (riskLevels[3] * 90);
                        averageRisk = Math.round(weightedSum / totalUsers);
                    }
                }
                
                
                // Update center text with average risk score (this is what you want)
                if (centerText.textContent !== averageRisk + '%') {
                    centerText.style.transform = 'scale(1.1)';
                    centerText.style.color = '#FFD700';
                    
                    setTimeout(() => {
                        centerText.textContent = averageRisk + '%';
                        centerText.style.transform = 'scale(1)';
                        centerText.style.color = '';
                    }, 150);
                } else {
                    centerText.textContent = averageRisk + '%';
                }
                
                // Create conic gradient for the donut chart
                let gradientString = '';
                let currentPercent = 0;
                
                // Calculate total percentage to handle rounding errors
                let calculatedTotalPercentage = 0;
                const validSegments = [];
                
                riskLevels.forEach((count, index) => {
                    if (count > 0) {
                        const percentage = (count / totalUsers) * 100;
                        calculatedTotalPercentage += percentage;
                        validSegments.push({ count, index, percentage });
                    }
                });
                
                // Build gradient string with proper percentage handling
                let segmentPercent = 0;
                validSegments.forEach((segment, segmentIndex) => {
                    const startPercent = segmentPercent;
                    segmentPercent += segment.percentage;
                    
                    // For the last segment, ensure it goes to exactly 100%
                    const endPercent = segmentIndex === validSegments.length - 1 ? 100 : segmentPercent;
                    gradientString += `${colors[segment.index]} ${startPercent}% ${endPercent}%`;
                    
                    if (endPercent < 100) {
                        gradientString += ', ';
                    }
                });
                
                
                // Apply the conic gradient to the background
                if (gradientString.trim()) {
                    chartBg.style.background = `conic-gradient(${gradientString})`;
                    chartBg.style.opacity = '1';
                } else {
                    // Fallback if no data
                    chartBg.style.background = 'conic-gradient(#e0e0e0 0% 100%)';
                    chartBg.style.opacity = '0.3';
                }
                
                // Create percentage labels around the donut chart - properly aligned with segments
                const percentageLabelsContainer = document.getElementById('percentage-labels');
                if (percentageLabelsContainer) {
                    percentageLabelsContainer.innerHTML = ''; // Clear existing labels
                    
                    // Use the same data that creates the donut chart segments
                    let currentAngle = 0;
                    
                    if (actualRiskScores.length > 0) {
                        // Use actual risk scores for percentage labels - MATCHING ANDROID APP LOGIC
                        const lowRiskCount = actualRiskScores.filter(score => score < 20).length;
                        const moderateRiskCount = actualRiskScores.filter(score => score >= 20 && score < 50).length;
                        const highRiskCount = actualRiskScores.filter(score => score >= 50 && score < 80).length;
                        const severeRiskCount = actualRiskScores.filter(score => score >= 80).length;
                        
                        const lowRiskPercentage = (lowRiskCount / actualRiskScores.length) * 100;
                        const moderateRiskPercentage = (moderateRiskCount / actualRiskScores.length) * 100;
                        const highRiskPercentage = (highRiskCount / actualRiskScores.length) * 100;
                        const severeRiskPercentage = (severeRiskCount / actualRiskScores.length) * 100;
                        
                        // Create labels for each risk level
                        const riskLabels = [
                            { index: 0, percentage: lowRiskPercentage, count: lowRiskCount, label: 'Low Risk' },
                            { index: 1, percentage: moderateRiskPercentage, count: moderateRiskCount, label: 'Moderate Risk' },
                            { index: 2, percentage: highRiskPercentage, count: highRiskCount, label: 'High Risk' },
                            { index: 3, percentage: severeRiskPercentage, count: severeRiskCount, label: 'Severe Risk' }
                        ];
                        
                        riskLabels.forEach((segment, segmentIndex) => {
                            if (segment.count > 0) {
                                // Calculate the center angle of this segment
                                const segmentCenterAngle = currentAngle + (segment.percentage / 2);
                                currentAngle += segment.percentage;
                                
                                // Convert percentage to degrees (360° = 100%)
                                const angleInDegrees = (segmentCenterAngle / 100) * 360;
                                
                                // Calculate position on the circle (radius = 130px from center for better positioning)
                                const radius = 130;
                                const radian = (angleInDegrees - 90) * (Math.PI / 180); // -90 to start from top
                                const x = Math.cos(radian) * radius;
                                const y = Math.sin(radian) * radius;
                                
                                // Create percentage label
                                const labelDiv = document.createElement('div');
                                labelDiv.className = 'percentage-label';
                                labelDiv.setAttribute('data-risk-level', segment.index);
                                labelDiv.style.left = `calc(50% + ${x}px)`;
                                labelDiv.style.top = `calc(50% + ${y}px)`;
                                labelDiv.style.transform = `translate(-50%, -50%) scale(1)`;
                                labelDiv.textContent = `${segment.percentage.toFixed(1)}%`;
                                // Colors are now controlled by CSS based on data-risk-level
                                
                                // No hover effects - static percentage labels
                                
                                percentageLabelsContainer.appendChild(labelDiv);
                                
                                // Set label to visible immediately - no flickering
                                labelDiv.style.opacity = '1';
                                labelDiv.style.transform = 'translate(-50%, -50%) scale(1)';
                                labelDiv.style.transition = 'none';
                                
                            }
                        });
                    } else {
                        // Fallback to original logic if no risk scores available
                        let currentAngle = 0;
                        validSegments.forEach((segment, segmentIndex) => {
                            if (segment.count > 0) {
                                // Calculate the center angle of this segment
                                const segmentCenterAngle = currentAngle + (segment.percentage / 2);
                                currentAngle += segment.percentage;
                                
                                // Convert percentage to degrees (360° = 100%)
                                const angleInDegrees = (segmentCenterAngle / 100) * 360;
                                
                                // Calculate position on the circle (radius = 130px from center)
                                const radius = 130;
                                const radian = (angleInDegrees - 90) * (Math.PI / 180); // -90 to start from top
                                const x = Math.cos(radian) * radius;
                                const y = Math.sin(radian) * radius;
                                
                                // Create percentage label
                                const labelDiv = document.createElement('div');
                                labelDiv.className = 'percentage-label';
                                labelDiv.setAttribute('data-risk-level', segment.index);
                                labelDiv.style.left = `calc(50% + ${x}px)`;
                                labelDiv.style.top = `calc(50% + ${y}px)`;
                                labelDiv.style.transform = `translate(-50%, -50%) scale(0.8)`;
                                labelDiv.textContent = `${segment.percentage.toFixed(1)}%`;
                                // Colors are now controlled by CSS based on data-risk-level
                                
                                // No hover effects - static percentage labels
                                
                                percentageLabelsContainer.appendChild(labelDiv);
                                
                                // Set label to visible immediately - no flickering
                                labelDiv.style.opacity = '1';
                                labelDiv.style.transform = 'translate(-50%, -50%) scale(1)';
                                labelDiv.style.transition = 'none';
                            }
                        });
                    }
                }
                
                // Create enhanced segments info with labels and tooltips
                segments.innerHTML = ''; // Clear existing segments
                
                // Calculate percentages based on actual risk score ranges from MHO calculation
                let totalCalculatedPercentage = 0;
                const segmentPercentages = [];
                
                if (actualRiskScores.length > 0) {
                    // Use actual risk scores to calculate proper distribution - MATCHING ANDROID APP LOGIC
                    const lowRiskCount = actualRiskScores.filter(score => score < 20).length;
                    const moderateRiskCount = actualRiskScores.filter(score => score >= 20 && score < 50).length;
                    const highRiskCount = actualRiskScores.filter(score => score >= 50 && score < 80).length;
                    const severeRiskCount = actualRiskScores.filter(score => score >= 80).length;
                    
                    const lowRiskPercentage = (lowRiskCount / actualRiskScores.length) * 100;
                    const moderateRiskPercentage = (moderateRiskCount / actualRiskScores.length) * 100;
                    const highRiskPercentage = (highRiskCount / actualRiskScores.length) * 100;
                    const severeRiskPercentage = (severeRiskCount / actualRiskScores.length) * 100;
                    
                    // Create segments based on actual risk score distribution
                    if (lowRiskCount > 0) {
                        segmentPercentages.push({ index: 0, percentage: lowRiskPercentage, count: lowRiskCount });
                        totalCalculatedPercentage += lowRiskPercentage;
                    }
                    if (moderateRiskCount > 0) {
                        segmentPercentages.push({ index: 1, percentage: moderateRiskPercentage, count: moderateRiskCount });
                        totalCalculatedPercentage += moderateRiskPercentage;
                    }
                    if (highRiskCount > 0) {
                        segmentPercentages.push({ index: 2, percentage: highRiskPercentage, count: highRiskCount });
                        totalCalculatedPercentage += highRiskPercentage;
                    }
                    if (severeRiskCount > 0) {
                        segmentPercentages.push({ index: 3, percentage: severeRiskPercentage, count: severeRiskCount });
                        totalCalculatedPercentage += severeRiskPercentage;
                    }
                } else {
                    // Fallback to count-based calculation if no risk scores available
                    riskLevels.forEach((count, index) => {
                        if (count > 0) {
                            let percentage = (count / totalUsers) * 100;
                            segmentPercentages.push({ index, percentage, count });
                            totalCalculatedPercentage += percentage;
                        }
                    });
                }
                
                // Adjust the last segment to make total exactly 100%
                if (segmentPercentages.length > 0) {
                    const lastSegment = segmentPercentages[segmentPercentages.length - 1];
                    const adjustment = 100 - totalCalculatedPercentage;
                    lastSegment.percentage += adjustment;
                }
                
                segmentPercentages.forEach((segmentData, segmentIndex) => {
                    const { index, percentage, count } = segmentData;
                        
                                         // Create compact single-line segment
                    const segmentDiv = document.createElement('div');
                     segmentDiv.className = 'segment compact';
                    segmentDiv.setAttribute('data-risk-level', index);
                     
                     // Force inline styles to ensure horizontal layout within each segment
                     segmentDiv.style.display = 'flex';
                     segmentDiv.style.alignItems = 'center';
                     segmentDiv.style.justifyContent = 'space-between';
                     segmentDiv.style.flexDirection = 'row';
                     segmentDiv.style.flexWrap = 'nowrap';
                     segmentDiv.style.flex = '1';
                     
                     // Create compact single-line layout - removed user count for cleaner display
                    segmentDiv.innerHTML = `
                            <span class="segment-label">${labels[index]}</span>
                            <span class="segment-percentage">${percentage.toFixed(1)}%</span>
                    `;
                    
                    // Add hover effects to make segments more interactive
                    segmentDiv.addEventListener('mouseenter', () => {
                         segmentDiv.style.transform = 'translateY(-1px)';
                         segmentDiv.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.15)';
                        segmentDiv.style.borderColor = colors[index];
                        segmentDiv.style.background = `rgba(${colors[index].replace(')', ', 0.1)').replace('rgb(', '')}`;
                    });
                    
                    segmentDiv.addEventListener('mouseleave', () => {
                         segmentDiv.style.transform = 'translateY(0)';
                        segmentDiv.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.08)';
                        segmentDiv.style.borderColor = 'rgba(0, 0, 0, 0.1)';
                        segmentDiv.style.background = 'rgba(0, 0, 0, 0.05)';
                    });
                    
                    // Add click event to highlight corresponding donut segment
                    segmentDiv.addEventListener('click', () => {
                        // Hover effects removed - no more pulse or scale effects
                    });
                        
                    segments.appendChild(segmentDiv);
                    
                    // Set segment to visible immediately - no flickering
                    segmentDiv.style.opacity = '1';
                    segmentDiv.style.transition = 'none';
                    
                });
                
                // Set chart background to visible immediately - no flickering
                chartBg.style.opacity = '1';
                chartBg.style.transition = 'none';
                
                
            } catch (error) {
            }
        }





        // Function to update Nutritional Status Overview Card
        function updateNutritionalStatusCard(whzData, muacData) {
            
            try {
                // Update WHZ Categories
                if (whzData && whzData.length > 0) {
                    whzData.forEach(item => {
                        // Map the labels to the new IDs
                        let elementId = '';
                        if (item.label === 'Severe Acute Malnutrition') elementId = 'whz-sam-count';
                        else if (item.label === 'Moderate Acute Malnutrition') elementId = 'whz-mam-count';
                        else if (item.label === 'Normal Growth') elementId = 'whz-normal-count';
                        else if (item.label === 'Overweight') elementId = 'whz-overweight-count';
                        
                        const element = document.getElementById(elementId);
                        if (element) {
                            element.textContent = item.value || 0;
                        }
                    });
                }
                
                // Update MUAC Distribution
                if (muacData && muacData.length > 0) {
                    muacData.forEach(item => {
                        // Map the labels to the new IDs
                        let elementId = '';
                        if (item.label === 'Normal') elementId = 'muac-normal-count';
                        else if (item.label === 'MAM') elementId = 'muac-mam-count';
                        else if (item.label === 'SAM') elementId = 'muac-sam-count';
                        
                        const element = document.getElementById(elementId);
                        if (element) {
                            element.textContent = item.value || 0;
                        }
                        
                        // Update MUAC progress bars
                        let fillId = '';
                        if (item.label === 'Normal') fillId = 'muac-normal-fill';
                        else if (item.label === 'MAM') fillId = 'muac-mam-fill';
                        else if (item.label === 'SAM') fillId = 'muac-sam-fill';
                        
                        const fillElement = document.getElementById(fillId);
                        if (fillElement) {
                            // Calculate percentage based on total
                            const total = muacData.reduce((sum, d) => sum + d.value, 0);
                            const percentage = total > 0 ? (item.value / total) * 100 : 0;
                            fillElement.style.width = `${percentage}%`;
                        }
                    });
                }
                
                // Update Summary Statistics
                updateNutritionalSummary(whzData, muacData);
                
            } catch (error) {
            }
        }
        
        // Function to update Nutritional Summary
        function updateNutritionalSummary(whzData, muacData) {
            try {
                // Calculate Total At Risk (SAM + MAM from both WHZ and MUAC)
                let totalAtRisk = 0;
                let totalCases = 0;
                
                if (whzData) {
                    whzData.forEach(item => {
                        if (item.label === 'Severe Acute Malnutrition' || item.label === 'Moderate Acute Malnutrition') {
                            totalAtRisk += item.value || 0;
                        }
                        totalCases += item.value || 0;
                    });
                }
                
                if (muacData) {
                    muacData.forEach(item => {
                        if (item.label === 'SAM' || item.label === 'MAM') {
                            totalAtRisk += item.value || 0;
                        }
                        totalCases += item.value || 0;
                    });
                }
                
                // Update Total At Risk
                const totalAtRiskElement = document.getElementById('total-at-risk');
                if (totalAtRiskElement) {
                    totalAtRiskElement.textContent = totalAtRisk;
                }
                
                // Update Intervention Priority
                const interventionPriorityElement = document.getElementById('intervention-priority');
                if (interventionPriorityElement) {
                    let priority = 'Low';
                    if (totalAtRisk > 0) {
                        const riskPercentage = (totalAtRisk / totalCases) * 100;
                        if (riskPercentage >= 20) priority = 'Critical';
                        else if (riskPercentage >= 10) priority = 'High';
                        else if (riskPercentage >= 5) priority = 'Medium';
                    }
                    interventionPriorityElement.textContent = priority;
                }
                
                // Update Recovery Rate (placeholder - you can implement actual recovery tracking)
                const recoveryRateElement = document.getElementById('recovery-rate');
                if (recoveryRateElement) {
                    // This would typically come from historical data
                    // For now, showing a placeholder
                    recoveryRateElement.textContent = '85%';
                }
                
            } catch (error) {
            }
        }

        // Helper function to create line path (copied from dashold.php design)
        function createLinePath(data) {
            
            if (!data || data.length === 0) {
                return '';
            }
            
            const width = 1000;
            const height = 500;
            
            
            const maxValue = Math.max(...data.map(d => d.value));
            
            // Handle single data point case
            if (data.length === 1) {
                const x = width / 2; // Center horizontally
                const y = height - (data[0].value / maxValue) * height;
                const path = `M ${x},${y} L ${x},${y}`; // Single point as small line
                return path;
            }
            
            const xStep = width / (data.length - 1);
            const yScale = height / maxValue;
            
            
            // Create path from left to right (like dashold.php)
            let pathString = `M 0,${height - data[0].value * yScale}`;
            let areaString = `M 0,${height - data[0].value * yScale}`;
            
            for (let i = 1; i < data.length; i++) {
                const x = i * xStep;
                const y = height - data[i].value * yScale;
                pathString += ` L ${x},${y}`;
                areaString += ` L ${x},${y}`;
            }
            
            // Close the area path to the bottom (like dashold.php)
            areaString += ` L ${width},${height} L 0,${height} Z`;
            
            
            return { path: pathString, area: areaString };
        }

        document.addEventListener('DOMContentLoaded', function() {
            window.globalAverageRiskScore = 0;
            
            const keyElements = {
                'community-total-screened': document.getElementById('community-total-screened'),
                'community-high-risk': document.getElementById('community-high-risk'),
                'community-sam-cases': document.getElementById('community-sam-cases'),
                'programs-in-barangay': document.getElementById('programs-in-barangay'),
                'risk-chart-bg': document.getElementById('risk-chart-bg'),
                'risk-center-text': document.getElementById('risk-center-text'),
                'risk-segments': document.getElementById('risk-segments'),
                'critical-alerts': document.getElementById('critical-alerts'),
                'barangay-prevalence': document.getElementById('barangay-prevalence'),
                'new-theme-toggle': document.getElementById('new-theme-toggle'),
                'dropdown-content': document.getElementById('dropdown-content'),
                'selected-option': document.getElementById('selected-option'),
                'dropdown-arrow': document.querySelector('.dropdown-arrow'),
                'search-input': document.getElementById('search-input')
            };
            
            setupBarangaySelection();
            
            const barangayRestored = restoreSelectedBarangay();
            
            try {
                if (barangayRestored && currentSelectedBarangay) {
                    updateDashboardForBarangay(currentSelectedBarangay);
                } else {
                    updateCommunityMetrics();
                    updateCharts();
                    updateGeographicChart();
                    updateAnalysisSection();
                    generateIntelligentPrograms(currentSelectedBarangay);
                }
            } catch (error) {
            }
            
            setInterval(() => {
                try {
                    requestAnimationFrame(() => {
                        updateCommunityMetrics(currentSelectedBarangay);
                        updateCharts(currentSelectedBarangay);
                        updateCriticalAlerts(currentSelectedBarangay);
                    });
                } catch (error) {
                }
            }, 3000);
            
            initializeAlertsState();
            
            // Add test notification button for debugging
            addTestNotificationButton();
        });
        
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('nutrisaur-theme') || 'light';
            document.body.className = savedTheme + '-theme';
        });
        
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                const dropdownContent = document.getElementById('dropdown-content');
                const optionItems = document.querySelectorAll('.option-item');
                const selectHeader = document.querySelector('.select-header');
                
                if (currentSelectedBarangay && !document.querySelector('.option-item.selected')) {
                    restoreSelectedBarangay();
                }
                
                if (currentSelectedBarangay && currentSelectedBarangay !== '') {
                    updateDashboardForBarangay(currentSelectedBarangay);
                }
            }, 1000);
        });

        
        

        async function debugProgramGeneration() {
            try {
                const currentBarangay = currentSelectedBarangay || '';
                const params = {};
                if (currentBarangay && currentBarangay !== '') {
                    if (currentBarangay.startsWith('MUNICIPALITY_')) {
                        params.municipality = currentBarangay.replace('MUNICIPALITY_', '');
                    } else {
                        params.barangay = currentBarangay;
                    }
                }
                
                const data = await fetchDataFromAPI('intelligent_programs', params);
                
                if (data && data.success) {
                    const debugElement = document.getElementById('community-health-debug');
                    const debugContent = document.getElementById('debug-content');
                    
                    if (debugElement && debugContent) {
                        debugElement.style.display = 'block';
                        
                        const debugInfo = `
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                                <div><strong>Total Users:</strong> ${data.data_analysis.total_users || 0}</div>
                                <div><strong>High Risk %:</strong> ${data.data_analysis.high_risk_percentage || 0}%</div>
                                <div><strong>SAM Cases:</strong> ${data.data_analysis.sam_cases || 0}</div>
                                <div><strong>Children:</strong> ${data.data_analysis.children_count || 0}</div>
                                <div><strong>Elderly:</strong> ${data.data_analysis.elderly_count || 0}</div>
                                <div><strong>Low Dietary Diversity:</strong> ${data.data_analysis.low_dietary_diversity || 0}</div>
                                <div><strong>Average Risk Score:</strong> ${data.data_analysis.average_risk || 0}</div>
                                <div><strong>Community Status:</strong> <span style="color: var(--color-highlight);">${data.data_analysis.community_health_status || 'Unknown'}</span></div>
                                <div><strong>Programs Generated:</strong> <span style="color: var(--color-accent1); font-weight: bold;">${data.programs ? data.programs.length : 0}</span></div>
                            </div>
                            ${data.data_analysis.message ? `<div style="margin-top: 10px; padding: 10px; background: rgba(161, 180, 84, 0.1); border-radius: 6px; border-left: 3px solid var(--color-highlight);"><strong>Analysis:</strong> ${data.data_analysis.message}</div>` : ''}
                            <div style="margin-top: 10px; padding: 10px; background: rgba(255, 193, 7, 0.1); border-radius: 6px; border-left: 3px solid #FFC107;">
                                <strong>Debug Info:</strong> This shows why ${data.programs ? data.programs.length : 0} program(s) were generated. The system analyzes community health data to determine program count.
                            </div>
                        `;
                        
                        debugContent.innerHTML = debugInfo;
                    }
                    
                    updateIntelligentProgramsDisplay(data.programs, data.data_analysis);
                    
                }
            } catch (error) {
            }
        }

        
        
        

        // Function to create new program - redirects to event.php in same tab
        function createNewProgram() {
            // Redirect to event.php in the same tab
                            window.location.href = '/event';
        }

        // Function to create program from card - redirects to event.php with pre-filled data
        function createProgramFromCard(title, type, location, description, urgency) {
            
            // Create URL parameters with proper encoding
            const params = new URLSearchParams({
                program: title,
                type: type,
                location: location,
                description: description,
                urgency: urgency
            });
            
            const url = `event.php?${params.toString()}`;
            
            // Redirect to event.php in the same tab with pre-filled form data
            window.location.href = url;
        }

        

        
        // Expose functions globally for console testing
        
        // Screening Responses Functions
        async function loadScreeningResponses(barangay = '') {
            try {
                let url = 'https://nutrisaur-production.up.railway.app/unified_api.php?endpoint=screening_responses';
                if (barangay && barangay !== '') {
                    url += `&barangay=${encodeURIComponent(barangay)}`;
                }
                
                
                const response = await fetch(url);
                
                if (response.ok) {
                    const data = await response.json();
                    
                    if (data.success && data.data) {
                        updateScreeningResponsesDisplay(data.data);
                    } else {
                        showScreeningResponsesError('API returned no data');
                    }
                } else {
                    const errorText = await response.text();
                    showScreeningResponsesError(`HTTP ${response.status}: ${errorText}`);
                }
            } catch (error) {
                showScreeningResponsesError(`Connection error: ${error.message}`);
            }
        }
        

        
        function updateResponseSection(elementId, data, labelType, totalScreened = null) {
            
            const element = document.getElementById(elementId);
            if (!element) {
                return;
            }
            
            
            // Find the data container (after the headers)
            const dataContainer = element.querySelector('.response-data-container');
            
            if (!dataContainer) {
                // If no data container exists, create one after the headers
                const headers = element.querySelector('.column-headers');
                if (headers) {
                    const newDataContainer = document.createElement('div');
                    newDataContainer.className = 'response-data-container';
                    element.appendChild(newDataContainer);
                }
            }
            
            if (!data || data.length === 0) {
                if (dataContainer) {
                    dataContainer.innerHTML = '<div class="no-data-message">No data available for selected time frame</div>';
                } else {
                    element.innerHTML = '<div class="no-data-message">No data available for selected time frame</div>';
                }
                return;
            }
            
            // Debug: Show raw data if something goes wrong
            if (data.length > 0 && !data[0].count && !data[0].value) {
                const debugHtml = `
                    <div class="no-data-message">
                        <div>Raw data received:</div>
                        <pre style="font-size: 10px; margin-top: 10px; text-align: left;">${JSON.stringify(data, null, 2)}</pre>
                    </div>
                `;
                if (dataContainer) {
                    dataContainer.innerHTML = debugHtml;
                } else {
                    element.innerHTML = debugHtml;
                }
                return;
            }
            
            let html = '';
            // Use provided total or calculate from data
            const total = totalScreened || data.reduce((sum, item) => sum + (item.count || item.value || 0), 0);
            
            
            data.forEach((item, index) => {
                const count = item.count || item.value || 0;
                const percentage = total > 0 ? ((count / total) * 100).toFixed(1) : 0;
                const displayLabel = getDisplayLabel(item, labelType);
                
                
                html += `
                    <div class="response-answer-item">
                        <span class="answer-label">${displayLabel}</span>
                        <span class="answer-count">${count}</span>
                        <span class="answer-percentage">${percentage}%</span>
                    </div>
                `;
            });
            
            if (dataContainer) {
                dataContainer.innerHTML = html;
                } else {
                element.innerHTML = html;
            }
        }
        
        // Function to get proper display labels for different data types
        function getDisplayLabel(item, labelType) {
            
            // Handle different field name patterns from API
            let label = '';
            
            // Check for different field name patterns
            if (item.label !== undefined) {
                label = item.label;
            } else if (item.swelling_status !== undefined) {
                label = item.swelling_status;
            } else if (item.weight_loss_status !== undefined) {
                label = item.weight_loss_status;
            } else if (item.feeding_behavior_status !== undefined) {
                label = item.feeding_behavior_status;
            } else if (item.dietary_diversity_level !== undefined) {
                label = item.dietary_diversity_level;
            } else if (item.age_group !== undefined) {
                label = item.age_group;
            } else if (item.gender !== undefined) {
                label = item.gender;
            } else if (item.income_level !== undefined) {
                label = item.income_level;
            } else if (item.height_range !== undefined) {
                label = item.height_range;
            } else if (item.value !== undefined) {
                label = item.value;
            } else if (item.count !== undefined) {
                label = item.count;
            } else if (item.name !== undefined) {
                label = item.name;
            }
            
            
            // Handle null, undefined, empty strings, and common database "unknown" values
            if (!label || 
                label === 'Unknown' || 
                label === 'unknown' || 
                label === 'null' || 
                label === 'NULL' || 
                label === '' || 
                label === 'undefined' ||
                label === 'N/A' ||
                label === 'n/a' ||
                label === 'Not Available' ||
                label === 'not available' ||
                label === 'None' ||
                label === 'none' ||
                label === 'Unknown' ||
                label === 'unknown' ||
                label === 'Not Specified' ||
                label === 'not specified') {
                return getDefaultLabel(labelType);
            }
            
            // Clean up common database values and edge cases
            let cleanLabel = label.toString().trim();
            
            // Handle HTML entities and encoding issues
            cleanLabel = cleanLabel
                .replace(/&amp;/g, '&')
                .replace(/&lt;/g, '<')
                .replace(/&gt;/g, '>')
                .replace(/&quot;/g, '"')
                .replace(/&#39;/g, "'")
                .replace(/&nbsp;/g, ' ');
            
            // Handle specific label types
            switch (labelType) {
                case 'Age Group':
                    return formatAgeGroup(cleanLabel);
                case 'Gender':
                    return formatGender(cleanLabel);
                case 'Income Level':
                    return formatIncomeLevel(cleanLabel);
                case 'Height Range':
                    return formatHeightRange(cleanLabel);
                case 'Swelling Status':
                    return formatSwellingStatus(cleanLabel);
                case 'Weight Loss Status':
                    return formatWeightLossStatus(cleanLabel);
                case 'Feeding Behavior':
                    return formatFeedingBehavior(cleanLabel);
                case 'Physical Sign':
                    return formatPhysicalSign(cleanLabel);
                case 'Dietary Diversity Score':
                    return formatDietaryDiversity(cleanLabel);
                case 'Clinical Risk Factor':
                    return formatClinicalRiskFactor(cleanLabel);
                default:
                    // For unknown label types, try to format intelligently
                    return formatGenericLabel(cleanLabel);
            }
        }
        
        // Function to format generic labels intelligently
        function formatGenericLabel(label) {
            if (!label) return 'Data not available';
            
            const clean = label.toString().trim().toLowerCase();
            
            // Handle common database patterns
            if (clean === 'yes' || clean === '1' || clean === 'true') return 'Yes';
            if (clean === 'no' || clean === '0' || clean === 'false') return 'No';
            if (clean === 'positive' || clean === 'pos') return 'Positive';
            if (clean === 'negative' || clean === 'neg') return 'Negative';
            if (clean === 'present' || clean === 'detected') return 'Present';
            if (clean === 'absent' || clean === 'not_detected') return 'Absent';
            
            // Handle numeric ranges
            if (!isNaN(clean) && clean.includes('-')) {
                return clean.split('-').map(part => part.trim()).join(' - ');
            }
            
            // Handle underscores and hyphens
            if (clean.includes('_') || clean.includes('-')) {
                return clean.replace(/[_-]/g, ' ').split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ');
            }
            
            // Default formatting
            return clean.charAt(0).toUpperCase() + clean.slice(1);
        }
        
        // Helper functions for formatting specific data types
        function formatAgeGroup(age) {
            const ageNum = parseInt(age);
            if (isNaN(ageNum)) return age;
            
            if (ageNum < 1) return 'Under 1 year';
            if (ageNum < 6) return '1-5 years';
            if (ageNum < 12) return '6-11 years';
            if (ageNum < 18) return '12-17 years';
            if (ageNum < 25) return '18-24 years';
            if (ageNum < 35) return '25-34 years';
            if (ageNum < 45) return '35-44 years';
            if (ageNum < 55) return '45-54 years';
            if (ageNum < 65) return '55-64 years';
            return '65+ years';
        }
        
        function formatGender(gender) {
            if (!gender) return 'Not specified';
            
            const clean = gender.toString().toLowerCase().trim();
            if (clean === 'm' || clean === 'male' || clean === '1') return 'Male';
            if (clean === 'f' || clean === 'female' || clean === '2') return 'Female';
            if (clean === 'o' || clean === 'other' || clean === '3') return 'Other';
            return gender.charAt(0).toUpperCase() + gender.slice(1).toLowerCase();
        }
        
        function formatIncomeLevel(income) {
            if (!income) return 'Not specified';
            
            const clean = income.toString().toLowerCase().trim();
            if (clean === 'low' || clean === '1' || clean === 'below_poverty') return 'Below Poverty Line';
            if (clean === 'medium' || clean === '2' || clean === 'low_income') return 'Low Income';
            if (clean === 'high' || clean === '3' || clean === 'middle_income') return 'Middle Income';
            if (clean === 'very_high' || clean === '4' || clean === 'high_income') return 'High Income';
            return income.charAt(0).toUpperCase() + income.slice(1).toLowerCase();
        }
        
        function formatHeightRange(height) {
            if (!height) return 'Not specified';
            
            const heightNum = parseFloat(height);
            if (isNaN(heightNum)) return height;
            
            if (heightNum < 100) return 'Under 100 cm';
            if (heightNum < 120) return '100-119 cm';
            if (heightNum < 140) return '120-139 cm';
            if (heightNum < 160) return '140-159 cm';
            if (heightNum < 180) return '160-179 cm';
            return '180+ cm';
        }
        
        function formatSwellingStatus(swelling) {
            if (!swelling) return 'Not specified';
            
            const clean = swelling.toString().toLowerCase().trim();
            if (clean === 'yes' || clean === '1' || clean === 'true' || clean === 'present') return 'Swelling Present';
            if (clean === 'no' || clean === '0' || clean === 'false' || clean === 'absent') return 'No Swelling';
            if (clean === 'mild' || clean === '2' || clean === 'slight') return 'Mild Swelling';
            if (clean === 'severe' || clean === '3' || clean === 'significant') return 'Severe Swelling';
            if (clean === 'moderate' || clean === '4') return 'Moderate Swelling';
            return swelling.charAt(0).toUpperCase() + swelling.slice(1).toLowerCase();
        }
        
        function formatWeightLossStatus(weightLoss) {
            if (!weightLoss) return 'Not specified';
            
            const clean = weightLoss.toString().toLowerCase().trim();
            if (clean === 'yes' || clean === '1' || clean === 'true') return 'Weight Loss Detected';
            if (clean === 'no' || clean === '0' || clean === 'false') return 'No Weight Loss';
            if (clean === 'mild' || clean === '2') return 'Mild Weight Loss';
            if (clean === 'severe' || clean === '3') return 'Severe Weight Loss';
            return weightLoss.charAt(0).toUpperCase() + weightLoss.slice(1).toLowerCase();
        }
        
        function formatFeedingBehavior(feeding) {
            if (!feeding) return 'Not specified';
            
            const clean = feeding.toString().toLowerCase().trim();
            if (clean === 'normal' || clean === '1') return 'Normal Feeding';
            if (clean === 'poor' || clean === '2') return 'Poor Feeding';
            if (clean === 'very_poor' || clean === '3') return 'Very Poor Feeding';
            if (clean === 'refuses' || clean === '4') return 'Refuses Food';
            return feeding.charAt(0).toUpperCase() + feeding.slice(1).toLowerCase();
        }
        
        function formatPhysicalSign(sign) {
            if (!sign) return 'Not specified';
            
            const clean = sign.toString().toLowerCase().trim();
            if (clean === 'thin' || clean === '1') return 'Thin Appearance';
            if (clean === 'shorter' || clean === '2') return 'Shorter Stature';
            if (clean === 'weak' || clean === '3') return 'Weak Physical Condition';
            if (clean === 'none' || clean === '0') return 'No Physical Signs';
            return sign.charAt(0).toUpperCase() + sign.slice(1).toLowerCase();
        }
        
        function formatDietaryDiversity(diversity) {
            if (!diversity) return 'Not specified';
            
            const diversityNum = parseInt(diversity);
            if (isNaN(diversityNum)) return diversity;
            
            if (diversityNum === 0) return 'No Food Groups (0)';
            if (diversityNum === 1) return 'Very Low Diversity (1 food group)';
            if (diversityNum === 2) return 'Very Low Diversity (2 food groups)';
            if (diversityNum === 3) return 'Low Diversity (3 food groups)';
            if (diversityNum === 4) return 'Low Diversity (4 food groups)';
            if (diversityNum === 5) return 'Medium Diversity (5 food groups)';
            if (diversityNum === 6) return 'Good Diversity (6 food groups)';
            if (diversityNum === 7) return 'Good Diversity (7 food groups)';
            if (diversityNum === 8) return 'High Diversity (8 food groups)';
            if (diversityNum === 9) return 'High Diversity (9 food groups)';
            if (diversityNum === 10) return 'Excellent Diversity (10 food groups)';
            return `High Diversity (${diversityNum} food groups)`;
        }
        
        function formatClinicalRiskFactor(risk) {
            if (!risk) return 'Not specified';
            
            const clean = risk.toString().toLowerCase().trim();
            if (clean === 'diarrhea' || clean === '1') return 'Diarrhea';
            if (clean === 'fever' || clean === '2') return 'Fever';
            if (clean === 'cough' || clean === '3') return 'Cough';
            if (clean === 'none' || clean === '0') return 'No Clinical Risk Factors';
            return risk.charAt(0).toUpperCase() + risk.slice(1).toLowerCase();
        }
        
        // Function to get default labels when data is missing
        function getDefaultLabel(labelType) {
            switch (labelType) {
                case 'Age Group':
                    return 'Age not recorded';
                case 'Gender':
                    return 'Gender not specified';
                case 'Income Level':
                    return 'Income not recorded';
                case 'Height Range':
                    return 'Height not measured';
                case 'Swelling Status':
                    return 'Swelling not assessed';
                case 'Weight Loss Status':
                    return 'Weight loss not assessed';
                case 'Feeding Behavior':
                    return 'Feeding behavior not observed';
                case 'Physical Sign':
                    return 'Physical signs not assessed';
                case 'Dietary Diversity Score':
                    return 'Dietary diversity not assessed';
                case 'Clinical Risk Factor':
                    return 'Clinical factors not assessed';
                default:
                    return 'Data not available';
            }
        }
        
        function showScreeningResponsesError(message) {
            const sections = [
                'age-group-responses', 'gender-responses', 'income-responses', 'height-responses',
                'swelling-responses', 'weight-loss-responses', 'feeding-behavior-responses', 'physical-signs-responses',
                'dietary-diversity-responses', 'clinical-risk-responses'
            ];
            
            sections.forEach(sectionId => {
                const element = document.getElementById(sectionId);
                if (element) {
                    element.innerHTML = `<div class="no-data-message">${message}</div>`;
                }
            });
        }
        
                    // Date formatting function for user-friendly time display
            function formatTimeAgo(dateString) {
                const now = new Date();
                const date = new Date(dateString);
                const diffInMs = now - date;
                const diffInSeconds = Math.floor(diffInMs / 1000);
                const diffInMinutes = Math.floor(diffInSeconds / 60);
                const diffInHours = Math.floor(diffInMinutes / 60);
                const diffInDays = Math.floor(diffInHours / 24);
                const diffInWeeks = Math.floor(diffInDays / 7);
                const diffInMonths = Math.floor(diffInDays / 30);
                const diffInYears = Math.floor(diffInDays / 365);
                
                if (diffInSeconds < 60) {
                    return 'Just now';
                } else if (diffInMinutes < 60) {
                    return diffInMinutes === 1 ? '1 minute ago' : `${diffInMinutes} minutes ago`;
                } else if (diffInHours < 24) {
                    return diffInHours === 1 ? '1 hour ago' : `${diffInHours} hours ago`;
                } else if (diffInDays < 7) {
                    return diffInDays === 1 ? '1 day ago' : `${diffInDays} days ago`;
                } else if (diffInWeeks < 4) {
                    return diffInWeeks === 1 ? '1 week ago' : `${diffInWeeks} weeks ago`;
                            } else if (diffInMonths < 12) {
                    return diffInMonths === 1 ? '1 month ago' : `${diffInMonths} months ago`;
                } else {
                    return diffInYears === 1 ? '1 year ago' : `${diffInYears} years ago`;
                }
            }
        
        // Enhanced date formatting with more options
        function formatDate(dateString, format = 'relative') {
            const date = new Date(dateString);
            
            if (format === 'relative') {
                return formatTimeAgo(dateString);
            } else if (format === 'short') {
                return date.toLocaleDateString('en-US', { 
                    month: 'short', 
                    day: 'numeric', 
                    year: 'numeric' 
                });
            } else if (format === 'long') {
                return date.toLocaleDateString('en-US', { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });
            } else if (format === 'time') {
                return date.toLocaleTimeString('en-US', { 
                    hour: '2-digit', 
                    minute: '2-digit' 
                });
            } else if (format === 'datetime') {
                return date.toLocaleString('en-US', { 
                    month: 'short', 
                    day: 'numeric', 
                    year: 'numeric',
                    hour: '2-digit', 
                    minute: '2-digit' 
                });
            }
            
            return date.toLocaleDateString();
        }
        
        // Function to update time displays on the page
        function updateTimeDisplays() {
            // Find all elements with data-time attributes and update them
            const timeElements = document.querySelectorAll('[data-time]');
            timeElements.forEach(element => {
                const dateString = element.getAttribute('data-time');
                if (dateString) {
                    element.textContent = formatTimeAgo(dateString);
                    element.title = formatDate(dateString, 'long'); // Show full date on hover
                }
            });
            
            // Find all elements with data-date attributes and update them
            const dateElements = document.querySelectorAll('[data-date]');
            dateElements.forEach(element => {
                const dateString = element.getAttribute('data-date');
                if (dateString) {
                    element.textContent = formatDate(dateString, 'short');
                    element.title = formatDate(dateString, 'long'); // Show full date on hover
                }
            });
        }
        
        // Auto-update time displays every minute
        setInterval(updateTimeDisplays, 60000);
        
        // Time frame button functionality - DISABLED FOR NOW
        /*
        document.addEventListener('DOMContentLoaded', function() {
            
            // Initialize dashboard with default data (1 day)
            updateDashboardByTimeFrame('1d');
            
            // Initialize Intelligent Programs
            generateIntelligentPrograms();
            
            const timeButtons = document.querySelectorAll('.time-btn');
            
            timeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    
                    // Remove active class from all buttons
                    timeButtons.forEach(btn => btn.classList.remove('active'));
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Get the selected time frame
                    const timeFrame = this.getAttribute('data-timeframe');
                    
                    // Update dashboard data based on time frame
                    updateDashboardByTimeFrame(timeFrame);
                });
            });
            
            // Barangay filter functionality
            const barangayOptions = document.querySelectorAll('.option-item');
            barangayOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const selectedValue = this.getAttribute('data-value');
                    const selectedText = this.textContent;
                    
                    
                    // Update the selected option display
                    document.getElementById('selected-option').textContent = selectedText;
                    document.getElementById('selected-option').setAttribute('data-value', selectedValue);
                    
                    // Close the dropdown
                    
                    // Close the dropdown
                    document.getElementById('dropdown-content').classList.remove('active');
                    document.querySelector('.dropdown-arrow').classList.remove('active');
                    
                    // Get current time frame
                    const activeTimeButton = document.querySelector('.time-btn.active');
                    const currentTimeFrame = activeTimeButton ? activeTimeButton.getAttribute('data-timeframe') : '1d';
                    
                    // Update dashboard with new barangay filter
                    updateDashboardByTimeFrame(currentTimeFrame);
                });
            });
        });
        */
        
        // Function to update dashboard based on selected time frame - DISABLED FOR NOW
        /*
        function updateDashboardByTimeFrame(timeFrame) {
            
            // Get selected time frame
            const selectedBarangay = document.getElementById('selected-option').getAttribute('data-value') || '';
            
            // Show loading state
            showDashboardLoading();
            
            // Fetch new data based on time frame and barangay - DISABLED FOR NOW
            // fetchTimeFrameData(timeFrame, selectedBarangay);
        }
        */
        
        // Function to fetch time frame data from server - DISABLED FOR NOW
        /*
        async function fetchTimeFrameData(timeFrame, barangay) {
            try {
                const response = await fetch(`https://nutrisaur-production.up.railway.app/unified_api.php?endpoint=time_frame_data&time_frame=${timeFrame}&barangay=${encodeURIComponent(barangay)}`);
                
                if (response.ok) {
                    const responseData = await response.json();
                    
                    if (responseData.success) {
                        updateDashboardWithData(responseData.data);
                    } else {
                        hideDashboardLoading();
                    }
                } else {
                    hideDashboardLoading();
                    }
                }
            } catch (error) {
                hideDashboardLoading();
            }
        }
        */
        
        // Function to update dashboard with new data - DISABLED FOR NOW
        /*
        function updateDashboardWithData(data) {
                total_screened: typeof data.total_screened,
                high_risk_cases: typeof data.high_risk_cases,
                sam_cases: typeof data.sam_cases,
                critical_muac: typeof data.critical_muac
            });
            
            // Update metrics cards
            const totalScreened = data.total_screened || 0;
            const highRiskCases = data.high_risk_cases || 0;
            const samCases = data.sam_cases || 0;
            const criticalMuac = data.critical_muac || 0;
            
                totalScreened,
                highRiskCases,
                samCases,
                criticalMuac
            });
            
            document.getElementById('community-total-screened').textContent = totalScreened;
            document.getElementById('community-high-risk').textContent = highRiskCases;
            document.getElementById('community-sam-cases').textContent = samCases;
            document.getElementById('community-critical-muac').textContent = criticalMuac;
            
            // Update date range display
            const dateRangeElement = document.getElementById('date-range-display');
            if (dateRangeElement) {
                const dateRangeText = `${data.start_date_formatted} - ${data.end_date_formatted}`;
                dateRangeElement.textContent = dateRangeText;
            } else {
            }
            
            // Update metric change displays
            const metricChanges = document.querySelectorAll('.metric-change');
            metricChanges.forEach(change => {
                change.textContent = `${data.start_date_formatted} - ${data.end_date_formatted}`;
            });
            
            // Update screening responses if available
            if (data.screening_responses) {
                updateScreeningResponsesDisplay(data);
            }
            
            // Hide loading state
            hideDashboardLoading();
            
        }
        */
        
        // Function to update screening responses display with new data
        function updateScreeningResponsesDisplay(data) {
            
            // Update age groups
            updateResponseSection('age-group-responses', data.age_groups || [], 'Age Group', data.total_screened);
            
            // Update gender distribution
            updateResponseSection('gender-responses', data.gender || [], 'Gender', data.total_screened);
            
            // Update income levels
            updateResponseSection('income-responses', data.income_levels || [], 'Income Level', data.total_screened);
            
            // Update height distribution
            updateResponseSection('height-responses', data.height || [], 'Height Range', data.total_screened);
            
            // Update swelling distribution
            updateResponseSection('swelling-responses', data.swelling || [], 'Swelling Status', data.total_screened);
            
            // Update weight loss distribution
            updateResponseSection('weight-loss-responses', data.weight_loss || [], 'Weight Loss Status', data.total_screened);
            
            // Update feeding behavior distribution
            updateResponseSection('feeding-behavior-responses', data.feeding_behavior || [], 'Feeding Behavior', data.total_screened);
            
            // Update physical signs
            updateResponseSection('physical-signs-responses', data.physical_signs || [], 'Physical Sign', data.total_screened);
            
            // Update dietary diversity distribution
            updateResponseSection('dietary-diversity-responses', data.dietary_diversity || [], 'Dietary Diversity Score', data.total_screened);
            
            // Update clinical risk factors
            updateResponseSection('clinical-risk-responses', data.clinical_risk || [], 'Clinical Risk Factor', data.total_screened);
            
            // Update critical alerts from screening data
            updateCriticalAlertsFromScreeningData(data);
            
        }
        
        // Function to update critical alerts based on screening response data
        function updateCriticalAlertsFromScreeningData(data) {
            const alertsContainer = document.getElementById('critical-alerts');
            if (!alertsContainer) return;
            
            let alertsHtml = '';
            let hasRealAlerts = false;
            
            // Check if data exists and has the required properties
            if (data && data.sam_cases > 0) {
                hasRealAlerts = true;
                alertsHtml += `
                    <li class="alert-item danger">
                        <div class="alert-content">
                            <h4>SAM Cases Detected</h4>
                            <p>${data.sam_cases} case(s) of Severe Acute Malnutrition</p>
                        </div>
                        <div class="alert-time" data-time="${data.latest_update || 'now'}">
                            ${formatTimeAgo(data.latest_update || 'now')}
                        </div>
                    </li>
                `;
            }
            
            if (data && data.high_risk_cases > 0) {
                hasRealAlerts = true;
                alertsHtml += `
                    <li class="alert-item warning">
                        <div class="alert-content">
                            <h4>High Risk Cases</h4>
                            <p>${data.high_risk_cases} case(s) with risk score ≥30</p>
                        </div>
                        <div class="alert-time" data-time="${data.latest_update || 'now'}">
                            ${formatTimeAgo(data.latest_update || 'now')}
                        </div>
                    </li>
                `;
            }
            
            if (data && data.critical_muac > 0) {
                hasRealAlerts = true;
                alertsHtml += `
                    <li class="alert-item danger">
                        <div class="alert-content">
                            <h4>Critical MUAC Readings</h4>
                            <p>${data.critical_muac} case(s) with MUAC < 11.5cm</p>
                        </div>
                        <div class="alert-time" data-time="${data.latest_update || 'now'}">
                            ${formatTimeAgo(data.latest_update || 'now')}
                        </div>
                    </li>
                `;
            }
            
            // Smart update logic: only show "no alerts" if we don't currently have alerts
            // This prevents flickering when there are already alerts displayed
            const currentlyHasAlerts = currentAlertsState.hasAlerts;
            
            if (!hasRealAlerts) {
                // If we currently have alerts displayed, keep them instead of showing "no alerts"
                if (currentlyHasAlerts) {
                    return;
                }
                
                // Only show "no alerts" if we genuinely start with no alerts
                alertsHtml = `
                    <li class="alert-item success">
                        <div class="alert-content">
                            <h4>No Critical Cases</h4>
                            <p>All screenings within normal ranges for selected time frame</p>
                        </div>
                        <div class="alert-time" data-time="now">
                            Just now
                        </div>
                    </li>
                `;
            }
            
            // Only update if content has actually changed
            if (alertsContainer.innerHTML !== alertsHtml) {
                alertsContainer.innerHTML = alertsHtml;
                currentAlertsState.hasAlerts = hasRealAlerts;
                currentAlertsState.lastContent = alertsHtml;
            }
        }
        
        // Function to show dashboard loading state
        function showDashboardLoading() {
            const loadingElements = document.querySelectorAll('.metric-value, .metric-change');
            loadingElements.forEach(element => {
                element.style.opacity = '0.5';
            });
            
            // Show loading spinner or indicator
            const dateRangeElement = document.getElementById('date-range-display');
            if (dateRangeElement) {
                dateRangeElement.textContent = 'Loading...';
            }
        }
        
        // Function to hide dashboard loading state
        function hideDashboardLoading() {
            const loadingElements = document.querySelectorAll('.metric-value, .metric-change');
            loadingElements.forEach(element => {
                element.style.opacity = '1';
            });
        }
        

        
        // Function to populate JavaScript date examples
        function populateDateExamples() {
            const sampleDate = '2024-01-15T10:30:00';
            
            document.getElementById('js-relative-example').textContent = formatTimeAgo(sampleDate);
            document.getElementById('js-short-example').textContent = formatDate(sampleDate, 'short');
            document.getElementById('js-long-example').textContent = formatDate(sampleDate, 'long');
            document.getElementById('js-time-example').textContent = formatDate(sampleDate, 'time');
            document.getElementById('js-datetime-example').textContent = formatDate(sampleDate, 'datetime');
        }
        
        // New theme toggle functionality
        function newToggleTheme() {
            const body = document.body;
            const toggleBtn = document.getElementById('new-theme-toggle');
            const icon = toggleBtn.querySelector('.new-theme-icon');
            const isCurrentlyLight = body.classList.contains('light-theme');
            toggleBtn.style.transition = 'none'; // Temporarily disable transitions

            if (isCurrentlyLight) {
                requestAnimationFrame(() => {
                    body.classList.remove('light-theme');
                    body.classList.add('dark-theme');
                    icon.textContent = '🌙'; // Moon icon for dark theme
                    body.style.backgroundColor = '#1A211A';
                    body.style.color = '#E8F0D6';
                    toggleBtn.style.backgroundColor = '#FF9800'; // Orange button
                    setTimeout(() => { toggleBtn.style.transition = ''; }, 50);
                });
                localStorage.setItem('nutrisaur-theme', 'dark');
            } else {
                requestAnimationFrame(() => {
                    body.classList.remove('dark-theme');
                    body.classList.add('light-theme');
                    icon.textContent = '☀'; // Sun icon for light theme
                    body.style.backgroundColor = '#F0F7F0';
                    body.style.color = '#1B3A1B';
                    toggleBtn.style.backgroundColor = '#000000'; // Black button
                    icon.style.color = '#FFFFFF'; // White sun icon
                    setTimeout(() => { toggleBtn.style.transition = ''; }, 50);
                });
                localStorage.setItem('nutrisaur-theme', 'light');
            }
            body.offsetHeight; // Force repaint
        }

        // Old theme toggle function removed - using newToggleTheme instead
        
        // Initialize theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('nutrisaur-theme') || 'light';
            const newToggleBtn = document.getElementById('new-theme-toggle');
            const newIcon = newToggleBtn.querySelector('.new-theme-icon');
            
            // Set initial theme
            document.body.className = savedTheme + '-theme';
            
            // Initialize button state and colors
            if (savedTheme === 'dark') {
                newIcon.textContent = '🌙'; // Moon icon for dark theme
                newToggleBtn.style.backgroundColor = '#FF9800'; // Orange button
            } else {
                newIcon.textContent = '☀'; // Sun icon for light theme
                newToggleBtn.style.backgroundColor = '#000000'; // Black button
                newIcon.style.color = '#FFFFFF'; // White sun icon
            }
            
            // Add click event to new theme toggle button
            newToggleBtn.addEventListener('click', newToggleTheme);
            
            // Initialize dropdown functionality
            initializeDropdown();
        });
        
        // Dropdown functionality for filter section
        function toggleDropdown() {
            const dropdown = document.getElementById('dropdown-content');
            const arrow = document.querySelector('.dropdown-arrow');
            
            if (dropdown.style.display === 'block') {
                dropdown.style.display = 'none';
                arrow.style.transform = 'rotate(0deg)';
            } else {
                dropdown.style.display = 'block';
                arrow.style.transform = 'rotate(180deg)';
            }
        }
        
        function filterOptions() {
            const input = document.getElementById('search-input');
            const filter = input.value.toLowerCase();
            const options = document.querySelectorAll('.option-item');
            
            options.forEach(option => {
                const text = option.textContent.toLowerCase();
                if (text.includes(filter)) {
                    option.style.display = 'block';
                } else {
                    option.style.display = 'none';
                }
            });
        }
        
        function initializeDropdown() {
            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                const dropdown = document.getElementById('dropdown-content');
                const selectContainer = document.querySelector('.custom-select-container');
                
                if (!selectContainer.contains(event.target)) {
                    dropdown.style.display = 'none';
                    document.querySelector('.dropdown-arrow').style.transform = 'rotate(0deg)';
                }
            });
            
            // Handle option selection
            document.addEventListener('click', function(event) {
                if (event.target.classList.contains('option-item')) {
                    const selectedValue = event.target.getAttribute('data-value');
                    const selectedText = event.target.textContent;
                    
                    document.getElementById('selected-option').textContent = selectedText;
                    document.getElementById('dropdown-content').style.display = 'none';
                    document.querySelector('.dropdown-arrow').style.transform = 'rotate(0deg)';
                    
                    // Update dashboard based on selected location
                    updateDashboardForBarangay(selectedValue);
                }
            });
        }
    
        // Function to test notification system with sample data
        function testNotificationSystem() {
            const alertsContainer = document.getElementById('critical-alerts');
            if (!alertsContainer) return;
            
            // Create sample critical alert for testing
            const sampleAlert = `
                <li class="alert-item critical">
                    <div class="alert-content">
                        <h4>High malnutrition risk: High Risk Score (75), Low BMI (15.2)</h4>
                        <p>Test User - Requires immediate attention</p>
                    </div>
                    <div class="alert-actions">
                        <div class="alert-time">Just now</div>
                        <div class="alert-buttons">
                            <span class="alert-badge badge-critical">Critical</span>
                            <button class="notify-btn" onclick="openNotificationModal('Test User', 'test@example.com', 'High malnutrition risk: High Risk Score (75), Low BMI (15.2)')" title="Send notification to this user">
                                📱 Notify
                            </button>
                        </div>
                    </div>
                </li>
            `;
            
            alertsContainer.innerHTML = sampleAlert;
            currentAlertsState.hasAlerts = true;
            currentAlertsState.lastContent = sampleAlert;
            
            console.log('Test critical alert created. Click the Notify button to test the notification system.');
        }
        
        // Add test button to the dashboard for easy testing
        function addTestNotificationButton() {
            const dashboardHeader = document.querySelector('.dashboard-header');
            if (dashboardHeader && !document.getElementById('test-notification-btn')) {
                const testBtn = document.createElement('button');
                testBtn.id = 'test-notification-btn';
                testBtn.innerHTML = '🧪 Test Notifications';
                testBtn.style.cssText = `
                    background: linear-gradient(135deg, #FF9800, #FF5722);
                    color: white;
                    border: none;
                    padding: 8px 16px;
                    border-radius: 20px;
                    cursor: pointer;
                    font-size: 12px;
                    margin-left: 15px;
                    transition: all 0.3s ease;
                `;
                
                testBtn.addEventListener('click', testNotificationSystem);
                dashboardHeader.appendChild(testBtn);
            }
        }
    </script>
</body>
</html>



