<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    // Redirect to login page if not logged in
    header('Location: /');
    exit;
}

// Database connection - Use the same working approach as simple_db_test.php
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

// Create database connection
try {
    $dsn = "mysql:host={$mysql_host};port={$mysql_port};dbname={$mysql_database};charset=utf8mb4";
    $conn = new PDO($dsn, $mysql_user, $mysql_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ]);
} catch (PDOException $e) {
    // If database connection fails, show error but don't crash
    $conn = null;
    $dbError = "Database connection failed: " . $e->getMessage();
}

/*
 * UPDATED DATABASE STRUCTURE - ALIGNED WITH ScreeningFormActivity.java
 * 
 * This dashboard now uses the updated user_preferences table structure that matches
 * the Android app's data collection fields. The table structure follows the
 * "ONE COLUMN PER QUESTION" approach as requested by the user.
 * 
 * Key changes:
 * - Removed MHO risk update button (no longer needed)
 * - Database queries already use the correct user_preferences table
 * - All MHO calculations are now handled directly from the updated table structure
 * - No additional tables or complex joins required
 * 
 * Database structure matches ScreeningFormActivity.java fields:
 * - Basic info: user_email, name, birthday, age, gender, height, weight, bmi, muac
 * - Physical signs: physical_thin, physical_shorter, physical_weak, physical_none
 * - Clinical factors: swelling, weight_loss, feeding_behavior, etc.
 * - Risk assessment: risk_score (calculated using MHO standards)
 * - Location: barangay, income
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Start the session
session_start();

// Debug: Check session status
echo "<!-- Debug: Session started -->";
echo "<!-- Debug: Session data: " . print_r($_SESSION, true) . " -->";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<!-- Debug: No user_id in session, redirecting to home.php -->";
    // Redirect to login page if not logged in
    header("Location: home.php");
    exit;
}

echo "<!-- Debug: User is logged in, user_id: " . $_SESSION['user_id'] . " -->";

// Get user info from session
$userId = $_SESSION['user_id'] ?? 'unknown';
$username = $_SESSION['username'] ?? 'User';
$email = $_SESSION['email'] ?? 'user@example.com';
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';

echo "<!-- Debug: User info - userId: $userId, username: $username, email: $email -->";

// Database connection already established from config.php include
    
            // Get user profile data from users and user_preferences tables
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
            // Handle case where user_id might not exist
            $profile = null;
        }
        
        // Get user nutrition goals
        $stmt = $conn->prepare("SELECT * FROM nutrition_goals WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $goals = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Get initial time frame data (default to 1 day)
        $currentTimeFrame = '1d';
        $currentBarangay = '';
        $timeFrameData = getTimeFrameData($conn, $currentTimeFrame, $currentBarangay);
        $screeningResponsesData = getScreeningResponsesByTimeFrame($conn, $currentTimeFrame, $currentBarangay);

// Handle logout
if (isset($_GET['logout'])) {
    // Destroy the session
    session_unset();
    session_destroy();
    
    // Redirect to login page
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
    <style>
/* Dark Theme - Default */
:root {
    --color-bg: #1A211A;
    --color-card: #2A3326;
    --color-highlight: #A1B454;
    --color-text: #E8F0D6;
    --color-accent1: #8CA86E;
    --color-accent2: #B5C88D;
    --color-accent3: #546048;
    --color-accent4: #C9D8AA;
    --color-danger: #CF8686;
    --color-warning: #E0C989;
    --color-border: rgba(161, 180, 84, 0.2);
    --color-shadow: rgba(0, 0, 0, 0.1);
    --color-hover: rgba(161, 180, 84, 0.08);
    --color-active: rgba(161, 180, 84, 0.15);
}

/* Light Theme - Light Greenish Colors */
.light-theme {
    --color-bg: #F0F7F0;
    --color-card: #FFFFFF;
    --color-highlight: #66BB6A;
    --color-text: #1B3A1B;
    --color-accent1: #81C784;
    --color-accent2: #4CAF50;
    --color-accent3: #2E7D32;
    --color-accent4: #A5D6A7;
    --color-danger: #E57373;
    --color-warning: #FFB74D;
    --color-border: #C8E6C9;
    --color-shadow: rgba(76, 175, 80, 0.1);
    --color-hover: rgba(76, 175, 80, 0.08);
    --color-active: rgba(76, 175, 80, 0.15);
}

/* Base navbar styles */
.navbar {
    position: fixed;
    top: 0;
    left: 0;
    width: 320px;
    height: 100vh;
    background-color: var(--color-card);
    box-shadow: 3px 0 15px rgba(0, 0, 0, 0.1);
    padding: 0;
    box-sizing: border-box;
    overflow-y: auto;
    z-index: 1000;
    display: flex;
    flex-direction: column;
    backdrop-filter: blur(10px);
}

/* Base body styles */
body {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    min-height: 100vh;
    background-color: var(--color-bg);
    color: var(--color-text);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    padding-left: 320px;
    line-height: 1.6;
    letter-spacing: 0.2px;
}

/* Dashboard container */
.dashboard {
    max-width: calc(100% - 60px);
    width: 100%;
    margin: 0 auto;
    padding: 20px;
}

/* Navbar header styles */
.navbar-header {
    padding: 35px 25px;
    display: flex;
    align-items: center;
    border-bottom: 2px solid rgba(164, 188, 46, 0.15);
    background: linear-gradient(135deg, rgba(161, 180, 84, 0.05) 0%, transparent 100%);
    position: relative;
    overflow: hidden;
}

.navbar-header::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(161, 180, 84, 0.3), transparent);
}

/* Navbar menu styles */
.navbar-menu {
    flex: 1;
    padding: 30px 0;
}

.navbar ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.navbar li {
    margin-bottom: 2px;
    position: relative;
    transition: all 0.3s ease;
}

.navbar li:hover {
    transform: translateX(5px);
}

.navbar li:not(:last-child) {
    border-bottom: 1px solid rgba(161, 180, 84, 0.08);
}

/* Navbar footer styles */
.navbar-footer {
    padding: 25px;
    text-align: center;
    border-top: 2px solid rgba(164, 188, 46, 0.15);
    background: linear-gradient(135deg, transparent 0%, rgba(161, 180, 84, 0.03) 100%);
    position: relative;
    overflow: hidden;
}

.navbar-footer::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(161, 180, 84, 0.3), transparent);
}

.navbar-footer div:first-child {
    font-weight: 600;
    color: var(--color-highlight);
    margin-bottom: 8px;
}

/* Header styles */
header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.dashboard-header {
    /* Removed card styling - no background, padding, border-radius, or box-shadow */
    padding: 0;
    margin-bottom: 20px;
}

.dashboard-header h1 {
    margin: 0;
    color: var(--color-text);
    font-size: 36px;
    font-weight: 700;
    line-height: 1.2;
}

/* User info styles */
.user-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: var(--color-accent3);
    display: flex;
    justify-content: center;
    align-items: center;
    color: var(--color-text);
    font-weight: bold;
}

/* Theme toggle button - OLD STYLES REMOVED */

/* Filter section styles */
.filter-section {
    background: var(--color-card);
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

/* Critical Alerts - Simple and Clean Design */

/* New theme toggle button design */
.new-theme-toggle-btn {
    background: #FF9800;
    border: none;
    color: #FFFFFF;
    padding: 10px 12px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 44px;
    height: 44px;
}

.new-theme-toggle-btn:hover {
    background: #F57C00;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
}

.new-theme-toggle-btn .new-theme-icon {
    font-size: 18px;
    transition: all 0.3s ease;
}

/* Light theme - black button with white icon */
.light-theme .new-theme-toggle-btn {
    background: #000000;
}

.light-theme .new-theme-toggle-btn:hover {
    background: #333333;
}

.light-theme .new-theme-toggle-btn .new-theme-icon {
    color: #FFFFFF !important;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.filter-group label {
    font-weight: 600;
    color: var(--color-text);
    min-width: 120px;
}

.custom-select-container {
    position: relative;
    min-width: 200px;
}

.select-header {
    background: var(--color-bg);
    border: 2px solid var(--color-border);
    border-radius: 8px;
    padding: 10px 15px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s ease;
}

.select-header:hover {
    border-color: var(--color-highlight);
}

.dropdown-arrow {
    transition: transform 0.3s ease;
}

.dropdown-content {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: var(--color-card);
    border: 2px solid var(--color-border);
    border-radius: 8px;
    max-height: 300px;
    overflow-y: auto;
    z-index: 1000;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

/* Stats grid styles */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: var(--color-card);
    padding: 24px;
    border-radius: 16px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    border: 1px solid var(--color-border);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.stat-card h3 {
    margin: 0 0 15px 0;
    color: var(--color-text);
    font-size: 18px;
    font-weight: 600;
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    color: var(--color-highlight);
    margin-bottom: 8px;
}

.stat-label {
    color: var(--color-text);
    opacity: 0.8;
    font-size: 14px;
}

/* Chart row styles */
.chart-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.chart-card {
    background: var(--color-card);
    padding: 24px;
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    border: 1px solid var(--color-border);
}

.chart-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
}

.chart-card h3 {
    margin: 0 0 20px 0;
    color: var(--color-text);
    font-size: 18px;
    font-weight: 600;
}

/* Make text smaller and fit better */
.dashboard {
    font-size: 14px;
}

.dashboard-header h1 {
    font-size: 36px !important;
    line-height: 1.2;
    font-weight: 700;
}

.stat-card h3 {
    font-size: 16px !important;
    line-height: 1.3;
}

.stat-value {
    font-size: 18px !important;
    line-height: 1.2;
}

.chart-title {
    font-size: 16px !important;
    line-height: 1.3;
}

.segment-label {
    font-size: 12px !important;
    line-height: 1.2;
}

.segment-percentage {
    font-size: 11px !important;
    line-height: 1.1;
}

.percentage-label {
    font-size: 11px !important;
    font-weight: 600;
}

.filter-section label {
    font-size: 13px !important;
}

.filter-section select,
.filter-section input {
    font-size: 13px !important;
}

/* Navbar link font size - consistent with other pages */
.navbar a {
    font-size: 17px !important;
}

/* Logo text font size - consistent with other pages */
.navbar-logo-text {
    font-size: 24px !important;
}

/* Ensure navbar styles are properly enforced */
.navbar a,
.navbar a:hover,
.navbar a.active {
    font-size: 17px !important;
}

.navbar-logo-text,
.navbar-logo-text:hover {
    font-size: 24px !important;
}

.light-theme .navbar-header {
    background: linear-gradient(135deg, rgba(142, 185, 110, 0.05) 0%, transparent 100%);
}

.light-theme .navbar-header::after {
    background: linear-gradient(90deg, transparent, rgba(142, 185, 110, 0.3), transparent);
}

.navbar-logo {
    display: flex;
    align-items: center;
    transition: transform 0.3s ease;
}

.navbar-logo:hover {
    transform: scale(1.05);
}

.navbar-logo-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    margin-right: 20px;
    display: flex;
    justify-content: center;
    align-items: center;
    color: var(--color-text);
    font-weight: bold;
    font-size: 20px;
    background: linear-gradient(135deg, rgba(161, 180, 84, 0.1), rgba(161, 180, 84, 0.05));
    border: 1px solid rgba(161, 180, 84, 0.2);
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(161, 180, 84, 0.1);
}

.navbar-logo:hover .navbar-logo-icon {
    background: linear-gradient(135deg, rgba(161, 180, 84, 0.15), rgba(161, 180, 84, 0.08));
    border-color: rgba(161, 180, 84, 0.3);
    box-shadow: 0 4px 15px rgba(161, 180, 84, 0.2);
}

.light-theme .navbar-logo-icon {
    background: linear-gradient(135deg, rgba(142, 185, 110, 0.1), rgba(142, 185, 110, 0.05));
    border-color: rgba(142, 185, 110, 0.2);
    box-shadow: 0 2px 8px rgba(142, 185, 110, 0.1);
}

.light-theme .navbar-logo:hover .navbar-logo-icon {
    background: linear-gradient(135deg, rgba(142, 185, 110, 0.15), rgba(142, 185, 110, 0.08));
    border-color: rgba(142, 185, 110, 0.3);
    box-shadow: 0 4px 15px rgba(142, 185, 110, 0.2);
}

.navbar-logo-text {
    font-size: 24px;
    font-weight: 600;
    color: var(--color-text);
}

.navbar-menu {
    flex: 1;
    padding: 30px 0;
}

.navbar ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.navbar li {
    margin-bottom: 2px;
    position: relative;
    transition: all 0.3s ease;
}

.navbar li:hover {
    transform: translateX(5px);
}

.navbar li:not(:last-child) {
    border-bottom: 1px solid rgba(161, 180, 84, 0.08);
}

.light-theme .navbar li:not(:last-child) {
    border-bottom: 1px solid rgba(142, 185, 110, 0.08);
}

.navbar a {
    text-decoration: none;
    color: var(--color-text);
    font-size: 17px;
    padding: 18px 25px;
    display: flex;
    align-items: center;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    opacity: 0.9;
    border-radius: 0 12px 12px 0;
    margin-right: 10px;
    overflow: hidden;
    background: linear-gradient(90deg, transparent 0%, transparent 100%);
}

.navbar a::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(161, 180, 84, 0.1), transparent);
    transition: left 0.5s ease;
}

.light-theme .navbar a::before {
    background: linear-gradient(90deg, transparent, rgba(142, 185, 110, 0.1), transparent);
}

.navbar a:hover {
    background: linear-gradient(90deg, rgba(161, 180, 84, 0.08) 0%, rgba(161, 180, 84, 0.04) 100%);
    color: var(--color-highlight);
    opacity: 1;
    transform: translateX(3px);
    box-shadow: 0 4px 15px rgba(161, 180, 84, 0.15);
}

.navbar a:hover::before {
    left: 100%;
}

.navbar a.active {
    background: linear-gradient(90deg, rgba(161, 180, 84, 0.15) 0%, rgba(161, 180, 84, 0.08) 100%);
    color: var(--color-highlight);
    opacity: 1;
    font-weight: 600;
    border-left: 4px solid var(--color-highlight);
    box-shadow: 0 6px 20px rgba(161, 180, 84, 0.2);
    transform: translateX(2px);
}

.light-theme .navbar a:hover {
    background: linear-gradient(90deg, rgba(142, 185, 110, 0.08) 0%, rgba(142, 185, 110, 0.04) 100%);
    box-shadow: 0 4px 15px rgba(142, 185, 110, 0.15);
}

.light-theme .navbar a.active {
    background: linear-gradient(90deg, rgba(142, 185, 110, 0.15) 0%, rgba(142, 185, 110, 0.08) 100%);
    border-left-color: var(--color-accent3);
    box-shadow: 0 6px 20px rgba(142, 185, 110, 0.2);
}

.navbar-icon {
    margin-right: 15px;
    width: 24px;
    font-size: 20px;
}

.navbar-footer {
    padding: 25px 20px;
    border-top: 2px solid rgba(164, 188, 46, 0.15);
    font-size: 12px;
    opacity: 0.7;
    text-align: center;
    background: linear-gradient(135deg, transparent 0%, rgba(161, 180, 84, 0.03) 100%);
    position: relative;
}

.navbar-footer::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(161, 180, 84, 0.2), transparent);
}

.light-theme .navbar-footer {
    background: linear-gradient(135deg, transparent 0%, rgba(142, 185, 110, 0.03) 100%);
}

.light-theme .navbar-footer::before {
    background: linear-gradient(90deg, transparent, rgba(142, 185, 110, 0.2), transparent);
}

.navbar-footer div:first-child {
    font-weight: 600;
    color: var(--color-highlight);
    margin-bottom: 8px;
}

.light-theme .navbar-footer div:first-child {
    color: var(--color-accent3);
}

.light-theme body {
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100" height="100" opacity="0.1"><path d="M10,10 Q50,20 90,10 Q80,50 90,90 Q50,80 10,90 Q20,50 10,10 Z" fill="%2376BB43"/></svg>');
    background-size: 300px;
}

.dashboard {
    max-width: calc(100% - 60px);
    width: 100%;
    margin: 0 auto;
}

header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    background-color: var(--color-card);
    padding: 15px 25px;
    border-radius: 12px;
    box-shadow: 0 4px 15px var(--color-shadow);
    border: 1px solid var(--color-border);
    transition: all 0.3s ease;
}

.logo {
    display: flex;
    align-items: center;
}

        .logo-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            margin-right: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--color-text);
            font-weight: bold;
        }

.light-theme .logo-icon {
    color: var(--color-highlight);
}

h1 {
    font-size: 24px;
    font-weight: 600;
    color: var(--color-text);
    margin: 0;
}

.light-theme h1 {
    color: #1B3A1B;
}

/* Dashboard header specific styling */
.dashboard-header h1 {
    color: var(--color-text);
    margin: 0;
    font-size: 24px;
    font-weight: 600;
}

.light-theme .dashboard-header h1 {
    color: #1B3A1B;
    font-size: 36px;
    font-weight: 700;
}

/* Header user info styles */
header .user-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Old theme toggle styles removed */

/* Old theme toggle styles removed */

.light-theme .stat-card {
    background-color: var(--color-card);
    border: 1px solid var(--color-border);
    box-shadow: 0 6px 20px var(--color-shadow);
}

.light-theme .chart-card {
    background-color: var(--color-card);
    border: 1px solid var(--color-border);
    box-shadow: 0 8px 24px var(--color-shadow);
}

.light-theme .stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 187, 106, 0.15);
}

.light-theme .chart-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 32px rgba(102, 187, 106, 0.15);
}

.light-theme .filter-section {
    background-color: var(--color-card);
    border: 1px solid var(--color-border);
    box-shadow: 0 4px 15px var(--color-shadow);
}

.light-theme .filter-group select {
    background-color: white;
    color: var(--color-text);
    border-color: var(--color-border);
}

.light-theme .filter-group select:focus {
    border-color: var(--color-highlight);
    box-shadow: 0 0 0 2px rgba(102, 187, 106, 0.1);
}

.light-theme .time-btn {
    background-color: white;
    color: var(--color-text);
    border-color: var(--color-border);
    transition: all 0.3s ease;
}

.light-theme .time-btn.active {
    background-color: var(--color-highlight);
    color: white;
    border-color: var(--color-highlight);
}

.light-theme .time-btn:hover {
    background-color: var(--color-accent1);
    color: white;
    border-color: var(--color-accent1);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 187, 106, 0.2);
}

.light-theme .card {
    background-color: var(--color-card);
    border: 1px solid var(--color-border);
    box-shadow: 0 4px 15px var(--color-shadow);
}

.light-theme .card:hover {
    box-shadow: 0 8px 20px rgba(76, 175, 80, 0.2);
}

/* Old theme toggle styles removed */
}

/* User avatar styles removed */

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background-color: var(--color-card);
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
    position: relative;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    font-size: 32px;
    margin-bottom: 15px;
    text-align: center;
    opacity: 0.8;
    transition: transform 0.3s ease;
}

.stat-card:hover .stat-icon {
    transform: scale(1.1);
    opacity: 1;
}

.light-theme .stat-card {
    border: none;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
}

.stat-card h3 {
    font-size: 16px;
    margin-bottom: 10px;
    opacity: 0.95;
    color: var(--color-text);
    font-weight: 500;
}

.stat-value {
    font-size: 32px;
    font-weight: 600;
    margin-bottom: 5px;
    color: var(--color-text);
}

.light-theme .stat-value {
    color: #1B3A1B;
}

.stat-label {
    font-size: 14px;
    opacity: 0.8;
    color: var(--color-text);
}

.light-theme .stat-label {
    opacity: 0.85;
}

.chart-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(480px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

/* Improved chart styling */
.chart-card {
    background-color: var(--color-card);
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
    display: flex;
    flex-direction: column;
    min-height: 420px;
    max-height: none;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.chart-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.1);
}

.chart-card h3 {
    font-size: 18px;
    margin-bottom: 15px;
    color: var(--color-text);
}

.light-theme .chart-card h3 {
    color: var(--color-accent3);
}

.chart-description {
    font-size: 13px;
    color: var(--color-text);
    opacity: 0.8;
    margin-bottom: 20px;
    line-height: 1.4;
    font-style: italic;
}

.light-theme .chart-description {
    opacity: 0.7;
    color: var(--color-text);
}

/* Improved bar chart styling */
.bar {
    width: 8%;
    background-color: var(--color-accent2);
    border-radius: 8px 8px 0 0;
    position: relative;
    overflow: hidden;
    transition: height 1s cubic-bezier(0.25, 0.8, 0.25, 1), 
                background-color 0.3s ease,
                transform 0.2s ease;
    filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.05));
    cursor: pointer;
}

.bar:hover {
    transform: scale(1.05);
    filter: drop-shadow(0 6px 12px rgba(0, 0, 0, 0.1));
}

/* Improved line chart styling */
.line-path {
    fill: none;
    stroke: var(--color-highlight);
    stroke-width: 3;
    stroke-dasharray: 1000;
    stroke-dashoffset: 1000;
    transition: stroke-dashoffset 2.5s ease;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
    /* Debug: Ensure visibility */
    opacity: 1;
    visibility: visible;
}

/* Performance optimizations */
.chart-card * {
    will-change: transform, opacity;
}

/* Loading state */
.chart-card.loading {
    opacity: 0.7;
    pointer-events: none;
}

/* Chart Labels and Legends */
.chart-labels {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 16px;
    padding: 12px;
    background: var(--color-card);
    border-radius: 8px;
    border: 1px solid var(--color-accent3);
}

.label-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: var(--color-text);
}

.label-color {
    width: 12px;
    height: 12px;
    border-radius: 2px;
    display: inline-block;
}

/* Axis Labels */
.x-axis {
    display: flex;
    justify-content: space-between;
    margin-top: 8px;
    padding: 0 4px;
}

.axis-label {
    font-size: 11px;
    color: var(--color-text);
    opacity: 0.7;
    text-align: center;
    flex: 1;
}

.y-axis-label {
    position: absolute;
    left: -30px;
    top: 50%;
    transform: rotate(-90deg) translateX(50%);
    font-size: 12px;
    color: var(--color-text);
    opacity: 0.8;
    white-space: nowrap;
}

/* Bar Chart Labels */
.bar-chart-container {
    position: relative;
    padding-left: 40px;
    padding-bottom: 30px;
}

.bar-chart {
    position: relative;
    height: 200px;
    margin-bottom: 8px;
}

/* Line Chart Labels */
.line-chart-container {
    position: relative;
    padding-left: 30px;
    padding-bottom: 20px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.line-chart {
    width: 100%;
    height: 180px;
    margin-bottom: 5px;
    flex: 1;
    position: relative;
}

.y-axis-label {
    position: absolute;
    left: -25px;
    top: 50%;
    transform: rotate(-90deg) translateX(50%);
    font-size: 11px;
    color: var(--color-text);
    opacity: 0.8;
    white-space: nowrap;
}

.donut-chart-container {
    position: relative;
    height: 280px;
    display: flex;
    justify-content: center;
    align-items: center;
}



/* Percentage labels around the donut */
.percentage-labels {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    pointer-events: none;
    z-index: 5;
}

.percentage-label {
    position: absolute;
    background: rgba(0, 0, 0, 0.85);
    color: white;
    padding: 6px 10px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
    opacity: 1;
    transform: scale(1);
    transition: none;
    pointer-events: none;
    cursor: default;
    border: 1px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.4), 0 0 20px rgba(0, 0, 0, 0.2);
    backdrop-filter: blur(5px);
    z-index: 5;
}

/* Hover effects removed - percentage labels are now static */

/* Light theme percentage labels - white backgrounds with dark theme text colors */
.light-theme .percentage-label {
    background: rgba(255, 255, 255, 0.95) !important;
    color: #1B3A1B !important;
    border: 1px solid rgba(102, 187, 106, 0.3) !important;
    box-shadow: 0 3px 12px rgba(102, 187, 106, 0.2), 0 0 20px rgba(102, 187, 106, 0.1) !important;
    backdrop-filter: blur(5px) !important;
}

/* Ensure percentage labels maintain their colors in light theme */
.light-theme .percentage-label[data-risk-level="0"] {
    color: #4CAF50 !important; /* Green for Low Risk */
}

.light-theme .percentage-label[data-risk-level="1"] {
    color: #FF9800 !important; /* Orange for Moderate Risk */
}

.light-theme .percentage-label[data-risk-level="2"] {
    color: #F44336 !important; /* Red for High Risk */
}

/* Enhanced segments with improved layout and responsiveness */
.segments {
    display: flex !important;
    justify-content: center !important;
    flex-wrap: wrap !important;
    margin-top: 15px !important;
    gap: 12px !important;
    max-width: 100% !important;
    flex-direction: row !important;
    align-items: flex-start !important;
    overflow: visible !important;
    white-space: normal !important;
    height: auto !important;
}

/* Ensure segments are displayed properly */
.segments .segment {
    display: flex !important;
    white-space: normal !important;
}

/* Improved segment layout */
.segments > .segment {
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    margin: 2px 4px !important;
    padding: 8px 6px !important;
    border-radius: 8px !important;
    background: rgba(0, 0, 0, 0.05) !important;
    border: 1px solid rgba(0, 0, 0, 0.1) !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
    position: relative !important;
    overflow: visible !important;
    gap: 6px !important;
    min-width: 140px !important;
    max-width: 140px !important;
    flex-wrap: nowrap !important;
    flex-shrink: 0 !important;
    flex-grow: 0 !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
    backdrop-filter: blur(2px) !important;
}

.segment {
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    margin: 2px 4px !important;
    padding: 8px 6px !important;
    border-radius: 8px !important;
    background: rgba(0, 0, 0, 0.05) !important;
    border: 1px solid rgba(0, 0, 0, 0.1) !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
    position: relative !important;
    overflow: visible !important;
    gap: 6px !important;
    min-width: 140px !important;
    max-width: 140px !important;
    flex-wrap: nowrap !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
    backdrop-filter: blur(2px) !important;
}

/* Segment hover effects removed */

.segment::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(161, 180, 84, 0.1), transparent);
    transition: left 0.5s ease;
}

/* Segment hover before effect removed */

/* Enhanced segment effects */
.segment::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    border-radius: 8px;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, transparent 50%, rgba(0, 0, 0, 0.05) 100%);
    opacity: 0;
    transition: none;
    pointer-events: none;
    z-index: -1;
}

.segment:hover::before {
    opacity: 1;
}

/* New segment layout classes */
.segment-header {
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 6px !important;
    width: 100% !important;
}

.segment-stats {
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 4px !important;
    width: 100% !important;
    flex-wrap: wrap !important;
}

.color-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-right: 0;
    position: relative;
    transition: none;
    flex-shrink: 0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2), 0 0 8px rgba(0, 0, 0, 0.1) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
}

/* Segment color indicator hover effect removed */

/* Segment element styling - improved for new layout */
.segment-label {
    font-weight: 600 !important;
    color: var(--color-text) !important;
    font-size: 10px !important;
    white-space: nowrap !important;
    min-width: auto !important;
    max-width: none !important;
    display: inline-block !important;
    text-align: center !important;
}

/* Segment percentage styling */
.segment-percentage {
    font-weight: 600 !important;
    color: var(--color-accent2) !important;
    font-size: 9px !important;
    text-align: center !important;
    background: rgba(0, 0, 0, 0.08) !important;
    padding: 2px 4px !important;
    border-radius: 4px !important;
    min-width: auto !important;
    max-width: none !important;
    white-space: nowrap !important;
    display: inline-block !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
}

.segment-risk-level {
    font-weight: 600 !important;
    color: var(--color-highlight) !important;
    font-size: 9px !important;
    text-align: center !important;
    background: rgba(161, 180, 84, 0.2) !important;
    padding: 2px 4px !important;
    border-radius: 4px !important;
    min-width: auto !important;
    max-width: none !important;
    white-space: nowrap !important;
    display: inline-block !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
}

/* Compact single-line segments */
.segment.compact {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 4px !important;
    padding: 6px 8px !important;
    margin-bottom: 0 !important;
    background: rgba(0, 0, 0, 0.05) !important;
    border: 1px solid rgba(0, 0, 0, 0.1) !important;
    border-radius: 6px !important;
    font-size: 10px !important;
    min-height: 24px !important;
    flex-direction: row !important;
    flex-wrap: nowrap !important;
    flex: 1 !important;
    max-width: calc(33.33% - 8px) !important;
    box-sizing: border-box !important;
}

.segment.compact .segment-label {
    text-align: center !important;
    font-weight: 600 !important;
    color: var(--color-text) !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    font-size: 9px !important;
    flex: 1 !important;
}

/* Compact segment percentage styling */
.segment.compact .segment-percentage {
    color: var(--color-text) !important;
    opacity: 0.8 !important;
    text-align: center !important;
    white-space: nowrap !important;
    font-size: 9px !important;
    min-width: 30px !important;
}

/* Color indicators for each risk level - Colored boxes matching donut chart colors */
.segment.compact[data-risk-level="0"] .segment-label::before {
    content: "" !important;
    display: inline-block !important;
    width: 8px !important;
    height: 8px !important;
    background-color: #4CAF50 !important; /* Light theme: Green for Low Risk */
    border-radius: 2px !important;
    margin-right: 6px !important;
    vertical-align: middle !important;
}

.segment.compact[data-risk-level="1"] .segment-label::before {
    content: "" !important;
    display: inline-block !important;
    width: 8px !important;
    height: 8px !important;
    background-color: #FF9800 !important; /* Yellow for Moderate Risk */
    border-radius: 2px !important;
    margin-right: 6px !important;
    vertical-align: middle !important;
}

.segment.compact[data-risk-level="2"] .segment-label::before {
    content: "" !important;
    display: inline-block !important;
    width: 8px !important;
    height: 8px !important;
    background-color: #F44336 !important; /* Red for High Risk */
    border-radius: 2px !important;
    margin-right: 6px !important;
    vertical-align: middle !important;
}

.segment.compact[data-risk-level="3"] .segment-label::before {
    content: "" !important;
    display: inline-block !important;
    width: 8px !important;
    height: 8px !important;
    background-color: #D32F2F !important; /* Dark Red for Severe Risk */
    border-radius: 2px !important;
    margin-right: 6px !important;
    vertical-align: middle !important;
}

/* Hover effects for compact segments */
.segment.compact:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
    border-color: rgba(0, 0, 0, 0.2) !important;
    background: rgba(0, 0, 0, 0.08) !important;
    transition: all 0.2s ease !important;
}

/* Ensure segments container supports compact layout */
.segments:has(.segment.compact) {
    display: flex !important;
    flex-direction: row !important;
    gap: 8px !important;
    width: 100% !important;
    justify-content: space-between !important;
}

/* Responsive design for segments */
@media (max-width: 768px) {
    .segments {
        gap: 8px !important;
    }
    
    .segment {
        gap: 4px !important;
        min-width: 120px !important;
        max-width: 120px !important;
        padding: 6px 4px !important;
    }
    
    .segment-label {
        font-size: 9px !important;
    }
    
    .segment-count,
    .segment-percentage,
    .segment-risk-level {
        font-size: 8px !important;
        padding: 1px 3px !important;
    }
    
    .color-indicator {
        width: 8px !important;
        height: 8px !important;
    }
}

@media (max-width: 480px) {
    .segments {
        gap: 6px !important;
    }
    
    .segment {
        min-width: 100px !important;
        max-width: 100px !important;
        padding: 4px 3px !important;
    }
    
    .segment-label {
        font-size: 8px !important;
    }
    
    .segment-count,
    .segment-percentage,
    .segment-risk-level {
        font-size: 7px !important;
        padding: 1px 2px !important;
    }
    
    .color-indicator {
        width: 6px !important;
        height: 6px !important;
    }
}



.donut-chart {
    width: 220px;
    height: 220px;
    border-radius: 50%;
    position: relative;
    overflow: visible;
    box-shadow: 0 0 30px rgba(0, 0, 0, 0.15);
    filter: drop-shadow(0 0 5px rgba(0, 0, 0, 0.05));
    display: grid;
    place-items: center;
    cursor: pointer;
    transition: none;
}

.donut-chart svg {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 3;
    pointer-events: none;
}

.donut-chart svg path {
    transition: none;
    cursor: default;
    stroke: rgba(255, 255, 255, 0.1);
    stroke-width: 1;
    pointer-events: auto;
}

/* Hover effects removed for donut chart */

.light-theme .donut-chart {
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
    transition: none;
}

.donut-chart::before {
    content: '';
    position: absolute;
    width: 60%;
    height: 60%;
    background-color: var(--color-card);
    border-radius: 50%;
    top: 20%;
    left: 20%;
    z-index: 2;
    box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.12);
    transition: none;
}

.light-theme .donut-chart::before {
    box-shadow: inset 0 0 15px rgba(0, 0, 0, 0.06);
    transition: none;
}

.donut-chart-bg {
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    z-index: 1;
    transition: none;
    cursor: default;
}

/* Hover effect removed for donut-chart-bg */

.donut-center-text {
    position: relative;
    z-index: 10;
    font-size: 24px;
    font-weight: 600;
    color: var(--color-text);
    text-align: center;
    line-height: 1;
    pointer-events: none;
    background-color: var(--color-card);
    padding: 8px 12px;
    border-radius: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    width: auto;
    height: auto;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 60px;
    min-height: 40px;
    box-sizing: border-box;
    transition: none;
}



.color-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 6px;
}

.bar-chart-container {
    position: relative;
    height: 220px;
    margin-top: 20px;
}

.bar-chart {
    display: flex;
    justify-content: space-around;
    align-items: flex-end;
    height: 100%;
    padding-bottom: 30px;
}

.bar {
    width: 8%;
    background-color: var(--color-accent2);
    border-radius: 8px 8px 0 0;
    position: relative;
    overflow: hidden;
    transition: height 1s cubic-bezier(0.25, 0.8, 0.25, 1);
    filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.05));
}

.light-theme .bar {
    background-color: var(--color-accent4);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
}

.bar-liquid {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: var(--color-highlight);
    transform: translateY(100%);
    transition: transform 1.5s cubic-bezier(0.25, 0.8, 0.25, 1);
    filter: blur(0.5px);
}

.bar-wave {
    position: absolute;
    width: 200%;
    height: 20px;
    left: -50%;
    border-radius: 40%;
    background: var(--color-accent2);
    animation: barWave 8s infinite linear;
    opacity: 0.8;
}

.light-theme .bar-wave {
    background: var(--color-accent1);
    opacity: 0.7;
}

@keyframes barWave {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(50%);
    }
}

.bar-value {
    position: absolute;
    top: -25px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 12px;
    color: var(--color-text);
}

.bar-label {
    position: absolute;
    bottom: -25px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 12px;
    white-space: nowrap;
    color: var(--color-text);
}

.light-theme .bar-label {
    font-weight: 600;
}

.x-axis {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 2px;
    background-color: rgba(232, 240, 214, 0.3);
}

.light-theme .x-axis {
    background-color: rgba(49, 68, 30, 0.2);
}

.line-chart {
    position: relative;
    height: 100%;
}

.line-path {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    fill: none;
    stroke: var(--color-highlight);
    stroke-width: 2.5;
    stroke-dasharray: 1000;
    stroke-dashoffset: 1000;
    transition: stroke-dashoffset 2.5s ease;
    filter: drop-shadow(0 1px 3px rgba(0, 0, 0, 0.1));
}

.light-theme .line-path {
    stroke: var(--color-accent1);
}

.line-area {
    fill: url(#line-gradient);
    opacity: 0.4;
    clip-path: polygon(0 100%, 0 0, 0 0, 0 100%);
    transition: clip-path 2.5s ease;
    /* Debug: Ensure visibility */
    visibility: visible;
}

.alert-list {
    list-style: none;
    max-height: 280px;
    overflow-y: auto;
    padding: 0;
    margin: 0;
}

.alert-item {
    background-color: rgba(42, 51, 38, 0.7);
    margin-bottom: 8px;
    padding: 12px;
    border-radius: 8px;
    border-left: 3px solid var(--color-highlight);
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
    transition: all 0.3s ease;
    cursor: pointer;
    max-width: 100%;
    word-wrap: break-word;
    overflow: hidden;
}

.alert-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    background-color: rgba(161, 180, 84, 0.1);
    border-left-color: var(--color-highlight);
}

/* Simple hover effects for different alert types */
.alert-item.danger:hover {
    border-left-color: var(--color-danger);
    background-color: rgba(207, 134, 134, 0.1);
}

.alert-item.warning:hover {
    border-left-color: var(--color-warning);
    background-color: rgba(255, 193, 7, 0.1);
}

.alert-item.success:hover {
    border-left-color: var(--color-highlight);
    background-color: rgba(76, 175, 80, 0.1);
}

.light-theme .alert-item {
    background-color: var(--color-card);
    border-left: 4px solid var(--color-accent3);
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.03);
    transition: all 0.3s ease;
    max-width: 100%;
    word-wrap: break-word;
    overflow: hidden;
}

.light-theme .alert-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 187, 106, 0.15);
    background-color: rgba(102, 187, 106, 0.05);
    border-left-color: var(--color-highlight);
}

.alert-item.danger {
    border-left-color: var(--color-danger);
    background-color: rgba(207, 134, 134, 0.1);
    border-left-width: 4px;
}

.alert-item.danger .alert-content h4 {
    color: var(--color-danger);
    font-weight: 600;
}

.alert-item.danger .alert-time {
    color: var(--color-danger);
    opacity: 0.8;
}

.alert-item.warning {
    border-left-color: var(--color-warning);
}



.alert-content h4 {
    font-size: 14px;
    margin-bottom: 3px;
    color: var(--color-text);
    font-weight: 500;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 200px;
    transition: all 0.3s ease;
}

.alert-content p {
    font-size: 12px;
    opacity: 0.7;
    color: var(--color-text);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 180px;
    transition: all 0.3s ease;
}

/* Simple content hover effects removed for cleaner design */

.alert-time {
    font-size: 12px;
    opacity: 0.6;
    color: var(--color-text);
}

.alert-badge {
    display: inline-block;
    padding: 3px 6px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 600;
    transition: all 0.2s ease;
    cursor: default;
}

.alert-badge:hover {
    transform: scale(1.05);
    filter: brightness(1.1);
}

.badge-danger {
    background-color: var(--color-danger);
    color: white;
}

.badge-warning {
    background-color: var(--color-warning);
    color: #333;
}

.light-theme .badge-warning {
    color: white;
}

.badge-success {
    background-color: var(--color-highlight);
    color: #333;
}

.light-theme .badge-success {
    background-color: var(--color-accent3);
    color: white;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .chart-row {
        grid-template-columns: 1fr;
    }
    
    .new-donut-chart {
        width: 400px;
        height: 400px;
    }
    
    .donut-chart-container {
        height: 500px;
    }
    
    .chart-card {
        min-height: 600px;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}

/* Adjustments for larger screens */
@media (min-width: 1200px) {
    .dashboard {
        max-width: calc(100% - 60px);
    }
    
    .stats-grid {
        grid-template-columns: repeat(4, 1fr);
    }
    
    .chart-row {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .chart-card {
        min-height: 400px;
    }
    
    .donut-chart-container {
        height: 300px;
    }
    
    .donut-chart {
        width: 240px;
        height: 240px;
    }
    
    .bar-chart-container {
        height: 280px;
    }
    
    .line-chart-container {
        height: 250px;
    }
    
    .stat-card, .chart-card {
        padding: 20px;
    }
}

/* Additional eye-comfort improvements */
.stat-card, .chart-card {
    border-radius: 20px; /* Slightly more rounded corners */
}

.light-theme .stat-value {
    color: #75aa50; /* Less intense highlight color */
}

.light-theme .chart-card,
.light-theme .stat-card {
    background-image: linear-gradient(to bottom right, rgba(255,255,255,0.05), transparent);
    border: 1px solid rgba(255,255,255,0.1);
}

/* Add subtle grain texture to reduce eye strain from solid colors */
.light-theme:before {
    content: "";
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)' opacity='0.05'/%3E%3C/svg%3E");
    z-index: 1000;
    opacity: 0.06;
}

/* Update the responsive navbar styles */
@media (max-width: 768px) {
    .navbar {
        width: 80px;
        transform: translateX(0);
        transition: transform 0.3s ease, width 0.3s ease;
    }
    
    .navbar:hover {
        width: 320px;
    }
    
    .navbar-logo-text, .navbar span:not(.navbar-icon) {
        opacity: 0;
        transition: opacity 0.2s ease;
    }
    
    .navbar:hover .navbar-logo-text, 
    .navbar:hover span:not(.navbar-icon) {
        opacity: 1;
    }
    
    body {
        padding-left: 100px;
    }
}

/* Add this media query for responsive adjustments */
@media (max-width: 768px) {
    .navbar a {
        padding: 12px 25px;  /* Slightly reduce vertical padding for mobile */
    }
    
    .navbar li {
        margin-bottom: 2px;  /* Further reduce spacing on mobile */
    }
}

/* Custom scrollbar - Add this to match USM.html */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: var(--background-color);
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, var(--secondary-color) 0%, #7cb342 100%);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #7cb342 0%, #689f38 100%);
}

/* User section styles for other parts of the dashboard */
.user-section {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    padding: 10px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 5px;
}

.user-section .user-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background-color: #8bc34a;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    margin-right: 15px;
}

.user-section .user-info {
    flex-grow: 1;
}

.user-name {
    font-weight: bold;
    font-size: 18px;
    margin-bottom: 5px;
}

.user-email {
    font-size: 14px;
    color: #888;
}

.logout-btn {
    background-color: #f44336;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
}

.logout-btn:hover {
    background-color: #d32f2f;
}

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

/* Refreshing animations */
@keyframes moonOrbit {
    0% {
        transform: rotate(0deg) translateX(170px) rotate(0deg);
        opacity: 0;
    }
    10% {
        opacity: 1;
    }
    90% {
        opacity: 1;
    }
    100% {
        transform: rotate(360deg) translateX(170px) rotate(-360deg);
        opacity: 0;
    }
}


.refresh-moon {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    background: var(--color-highlight);
    border-radius: 50%;
    transform-origin: center;
    animation: moonOrbit 2s ease-in-out;
    z-index: 10;
}

/* Hover effects for screening issues */
.screening-issues-hover {
    transition: all 0.3s ease;
}

.screening-issues-hover:hover {
    transform: scale(1.02);
    box-shadow: 0 8px 25px rgba(161, 180, 84, 0.2);
}

.main-issue-hover {
    transition: all 0.3s ease;
}

.main-issue-hover:hover {
    background: rgba(161, 180, 84, 0.2) !important;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(161, 180, 84, 0.3);
}

.summary-item-hover {
    transition: all 0.3s ease;
}

.summary-item-hover:hover {
    background: rgba(161, 180, 84, 0.15) !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(161, 180, 84, 0.2);
    border-color: var(--color-highlight) !important;
}

/* MUAC Distribution Chart Styles */
.muac-chart-container {
    padding: 20px;
    height: 300px;
}

/* Nutritional Status Overview - Ultra-Compact Professional Design */
.nutrition-status-container {
    padding: 16px;
    max-height: 400px;
    overflow: hidden;
}

.nutrition-compact {
    display: flex;
    flex-direction: column;
    gap: 16px;
    height: 100%;
}

/* Combined Grid Layout - Space Efficient */
.nutrition-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    flex: 1;
}

/* WHZ Section - Compact 2x2 Grid */
.whz-section {
    background: var(--color-card);
    border-radius: 10px;
    padding: 16px;
    border: 1px solid rgba(161, 180, 84, 0.1);
}

.section-title {
    font-size: 14px;
    font-weight: 600;
    color: var(--color-text);
    margin-bottom: 12px;
    text-align: center;
    opacity: 0.9;
}

.whz-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

.whz-item {
    background: rgba(0, 0, 0, 0.03);
    border-radius: 8px;
    padding: 10px;
    border-left: 3px solid transparent;
    transition: all 0.2s ease;
    position: relative;
}

.whz-item:hover {
    background: rgba(0, 0, 0, 0.06);
    transform: translateY(-1px);
}

.whz-item.sam {
    border-left-color: #F44336;
}

.whz-item.mam {
    border-left-color: #FF9800;
}

.whz-item.normal {
    border-left-color: #4CAF50;
}

.whz-item.overweight {
    border-left-color: #FF5722;
}

.whz-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    margin-bottom: 6px;
    position: relative;
}

.whz-item.sam .whz-dot {
    background: #F44336;
    box-shadow: 0 0 0 2px rgba(244, 67, 54, 0.2);
}

.whz-item.mam .whz-dot {
    background: #FF9800;
    box-shadow: 0 0 0 2px rgba(255, 152, 0, 0.2);
}

.whz-item.normal .whz-dot {
    background: #4CAF50;
    box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
}

.whz-item.overweight .whz-dot {
    background: #FF5722;
    box-shadow: 0 0 0 2px rgba(255, 87, 34, 0.2);
}

.whz-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.whz-label {
    font-size: 11px;
    font-weight: 600;
    color: var(--color-text);
    line-height: 1.2;
    opacity: 0.9;
}

.whz-value {
    font-size: 18px;
    font-weight: 700;
    color: var(--color-highlight);
    line-height: 1;
}

.light-theme .whz-value {
    color: var(--color-accent3);
}

.whz-range {
    font-size: 9px;
    color: var(--color-text);
    opacity: 0.6;
    font-family: 'Courier New', monospace;
    background: rgba(0, 0, 0, 0.03);
    padding: 2px 4px;
    border-radius: 3px;
    display: inline-block;
    width: fit-content;
}

.light-theme .whz-range {
    background: rgba(0, 0, 0, 0.06);
}

/* MUAC Section - Compact Side Panel */
.muac-section {
    background: var(--color-card);
    border-radius: 10px;
    padding: 16px;
    border: 1px solid rgba(161, 180, 84, 0.1);
}

.muac-compact {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.muac-row {
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.muac-row:hover {
    transform: translateX(3px);
}

.muac-label {
    font-size: 10px;
    font-weight: 500;
    color: var(--color-text);
    opacity: 0.8;
    min-width: 80px;
    flex-shrink: 0;
}

.muac-bar {
    height: 6px;
    background: rgba(0, 0, 0, 0.06);
    border-radius: 3px;
    overflow: hidden;
    flex: 1;
    min-width: 60px;
}

.light-theme .muac-bar {
    background: rgba(0, 0, 0, 0.1);
}

.muac-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    min-width: 8px;
}

.muac-fill.normal {
    background: linear-gradient(90deg, #4CAF50, #66BB6A);
}

.muac-fill.mam {
    background: linear-gradient(90deg, #FF9800, #FFB74D);
}

.muac-fill.sam {
    background: linear-gradient(90deg, #F44336, #EF5350);
}

.muac-count {
    font-size: 12px;
    font-weight: 600;
    color: var(--color-highlight);
    min-width: 25px;
    text-align: right;
    flex-shrink: 0;
}

.light-theme .muac-count {
    color: var(--color-accent3);
}

/* Summary Bar - Ultra Compact */
.summary-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    background: var(--color-card);
    border-radius: 10px;
    padding: 12px;
    border: 1px solid rgba(161, 180, 84, 0.1);
    margin-top: auto;
}

.summary-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.summary-label {
    font-size: 11px;
    font-weight: 500;
    color: var(--color-text);
    opacity: 0.7;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.summary-value {
    font-size: 16px;
    font-weight: 700;
    color: var(--color-highlight);
    line-height: 1;
}

.light-theme .summary-value {
    color: var(--color-accent3);
}

.summary-divider {
    width: 1px;
    height: 24px;
    background: rgba(161, 180, 84, 0.2);
    border-radius: 1px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .nutrition-status-container {
        padding: 12px;
        max-height: 350px;
    }
    
    .nutrition-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .whz-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }
    
    .whz-item, .muac-section {
        padding: 12px;
    }
    
    .whz-value {
        font-size: 16px;
    }
    
    .summary-bar {
        padding: 10px;
        gap: 16px;
    }
}

@media (max-width: 480px) {
    .nutrition-status-container {
        padding: 10px;
        max-height: 300px;
    }
    
    .whz-item, .muac-section, .summary-bar {
        padding: 10px;
    }
    
    .whz-grid {
        gap: 6px;
    }
    
    .muac-compact {
        gap: 10px;
    }
    
    .summary-bar {
        gap: 14px;
    }
}

.muac-legend {
    display: flex;
    justify-content: space-around;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 10px;
}

.muac-legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: var(--color-text);
}

.muac-color {
    width: 16px;
    height: 16px;
    border-radius: 3px;
    border: 1px solid rgba(0,0,0,0.1);
}

.muac-bars {
    display: flex;
    justify-content: space-around;
    align-items: flex-end;
    height: 200px;
    padding: 0 20px;
}

.muac-bar {
    width: 60px;
    background: linear-gradient(to top, var(--color-highlight), var(--color-accent2));
    border-radius: 8px 8px 0 0;
    position: relative;
    transition: all 0.3s ease;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.muac-bar:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.muac-bar.normal { background: linear-gradient(to top, #4CAF50, #66BB6A); }
.muac-bar.mam { background: linear-gradient(to top, #FF9800, #FFB74D); }
.muac-bar.sam { background: linear-gradient(to top, #F44336, #EF5350); }

.muac-bar-value {
    position: absolute;
    top: -25px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 12px;
    font-weight: bold;
    color: var(--color-text);
}

.muac-bar-label {
    position: absolute;
    bottom: -30px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 11px;
    color: var(--color-text);
    text-align: center;
    white-space: nowrap;
}

/* WHZ Categories Chart Styles */
.whz-chart-container {
    padding: 20px;
    height: 300px;
}

.whz-bars {
    display: flex;
    justify-content: space-around;
    align-items: flex-end;
    height: 180px;
    margin-bottom: 40px;
}

.whz-bar {
    width: 50px;
    border-radius: 8px 8px 0 0;
    position: relative;
    transition: all 0.3s ease;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.whz-bar:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.whz-bar.sam { background: linear-gradient(to top, #F44336, #EF5350); }
.whz-bar.mam { background: linear-gradient(to top, #FF9800, #FFB74D); }
.whz-bar.normal { background: linear-gradient(to top, #4CAF50, #66BB6A); }
.whz-bar.overweight { background: linear-gradient(to top, #2196F3, #42A5F5); }

.whz-bar-value {
    position: absolute;
    top: -25px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 12px;
    font-weight: bold;
    color: var(--color-text);
}

.whz-labels {
    display: flex;
    justify-content: space-around;
    text-align: center;
}

.whz-labels span {
    font-size: 11px;
    color: var(--color-text);
    line-height: 1.2;
}

/* Dietary Diversity Score Styles */
.dds-container {
    padding: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
}

.dds-score-display {
    text-align: center;
    padding: 20px;
    background: var(--color-card);
    border-radius: 15px;
    border: 2px solid var(--color-highlight);
    min-width: 150px;
}

.dds-number {
    font-size: 36px;
    font-weight: bold;
    color: var(--color-highlight);
    margin-bottom: 5px;
}

.dds-label {
    font-size: 14px;
    color: var(--color-text);
    opacity: 0.8;
    margin-bottom: 10px;
}

.dds-status {
    font-size: 16px;
    font-weight: bold;
    padding: 5px 15px;
    border-radius: 20px;
    color: white;
}

.dds-status.inadequate { background: #F44336; }
.dds-status.adequate { background: #4CAF50; }

.dds-food-groups {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    width: 100%;
    max-width: 300px;
}

.dds-food-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px;
    background: var(--color-card);
    border-radius: 8px;
    border: 1px solid var(--color-accent3);
    font-size: 12px;
    color: var(--color-text);
}

.dds-food-check {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    color: white;
    font-weight: bold;
}

.dds-food-check.checked { background: #4CAF50; }
.dds-food-check.unchecked { background: #ccc; }

/* Geographic Distribution Styles */
.geo-chart-container {
    padding: 15px;
    height: 280px;
    overflow-y: auto;
    flex: 1;
}

.geo-bars {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.geo-bar-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px;
    background: var(--color-card);
    border-radius: 6px;
    border-left: 3px solid var(--color-highlight);
    transition: all 0.3s ease;
    cursor: pointer;
}

.geo-bar-item:hover {
    transform: translateX(3px);
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.geo-bar-name {
    flex: 1;
    font-size: 13px;
    color: var(--color-text);
    font-weight: 500;
}

.geo-bar-progress {
    flex: 2;
    height: 16px;
    background: rgba(161, 180, 84, 0.2);
    border-radius: 8px;
    overflow: hidden;
    position: relative;
}

.geo-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--color-highlight), var(--color-accent2));
    border-radius: 8px;
    transition: width 0.8s ease;
}

.geo-bar-percentage {
    font-size: 11px;
    color: var(--color-text);
    font-weight: bold;
    min-width: 35px;
    text-align: right;
}

/* Critical Alerts Styles */
.critical-alerts {
    max-height: 280px;
    overflow-y: auto;
    flex: 1;
}

.alert-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.alert-item {
    background-color: rgba(42, 51, 38, 0.7);
    margin-bottom: 6px;
    padding: 8px 10px;
    border-radius: 6px;
    border-left: 3px solid var(--color-highlight);
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
    transition: all 0.3s ease;
    cursor: pointer;
    max-width: 100%;
    word-wrap: break-word;
    overflow: hidden;
}

.alert-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    background-color: rgba(161, 180, 84, 0.1);
    border-left-color: var(--color-highlight);
}

.light-theme .alert-item {
    background-color: var(--color-card);
    border-left: 3px solid var(--color-accent3);
    box-shadow: 0 2px 6px var(--color-shadow);
}

.light-theme .alert-item.danger {
    background-color: rgba(244, 67, 54, 0.05);
    border-left-color: var(--color-danger);
}

.light-theme .alert-item.warning {
    background-color: rgba(255, 193, 7, 0.05);
    border-left-color: var(--color-warning);
}

.light-theme .alert-item.success {
    background-color: rgba(76, 175, 80, 0.05);
    border-left-color: var(--color-highlight);
}

.light-theme .notify-btn {
    background: linear-gradient(135deg, var(--color-highlight), var(--color-accent1));
    color: white;
    box-shadow: 0 2px 8px rgba(102, 187, 106, 0.3);
}

.light-theme .notify-btn:hover {
    box-shadow: 0 4px 12px rgba(102, 187, 106, 0.4);
}

.light-theme .create-program-btn {
    background: linear-gradient(135deg, var(--color-highlight), var(--color-accent1));
    color: white;
    box-shadow: 0 4px 15px rgba(102, 187, 106, 0.3);
}

.light-theme .create-program-btn:hover {
    box-shadow: 0 6px 20px rgba(102, 187, 106, 0.4);
}

.light-theme .generate-programs-btn {
    background: linear-gradient(135deg, var(--color-highlight), var(--color-accent1));
    color: white;
    box-shadow: 0 4px 15px rgba(102, 187, 106, 0.3);
}

.light-theme .generate-programs-btn:hover {
    box-shadow: 0 6px 20px rgba(102, 187, 106, 0.4);
}

.light-theme .program-card {
    background-color: #FFFFFF;
    border: 1px solid var(--color-border);
    border-left: 3px solid var(--color-highlight);
    box-shadow: 0 4px 15px var(--color-shadow);
    transition: all 0.3s ease;
}

.light-theme .program-card:hover {
    background-color: rgba(102, 187, 106, 0.08) !important;
    box-shadow: 0 8px 25px rgba(102, 187, 106, 0.2) !important;
    border-color: var(--color-highlight) !important;
    transform: translateY(-2px) !important;
    border-left-color: var(--color-accent2) !important;
}

.light-theme .program-card:hover .program-title {
    color: var(--color-highlight);
}

.light-theme .program-card:hover .program-description {
    color: var(--color-text);
}

/* Light theme program card content styling */
.light-theme .program-card .program-title {
    color: #1B3A1B !important;
}

.light-theme .program-card .program-description {
    color: #1B3A1B !important;
    opacity: 0.8;
}

.light-theme .program-card .priority-tag {
    background-color: var(--color-accent2);
    color: #1B3A1B !important;
}

.light-theme .program-card .targeting-info {
    color: #2E7D32 !important;
}

.light-theme .program-card .program-details {
    color: #1B3A1B !important;
    opacity: 0.7;
}

.light-theme .program-card .target-location {
    background-color: rgba(102, 187, 106, 0.1);
    color: #2E7D32 !important;
}

.light-theme .program-card .btn-show-reasoning {
    background-color: #2196F3;
    color: white;
    border: none;
    transition: all 0.3s ease;
}

.light-theme .program-card .btn-show-reasoning:hover {
    background-color: #1976D2;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
}

.light-theme .program-card .btn-create-program {
    background-color: var(--color-highlight);
    color: #1B3A1B !important;
    border: none;
    transition: all 0.3s ease;
}

.light-theme .program-card .btn-create-program:hover {
    background-color: var(--color-accent2);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 187, 106, 0.3);
}

/* Enhanced light theme program card hover effects */
.light-theme .program-card:hover .program-title {
    color: #2E7D32 !important;
    transition: color 0.3s ease;
}

.light-theme .program-card:hover .priority-tag {
    transform: scale(1.05);
    transition: all 0.3s ease;
}

.light-theme .program-card:hover .target-location {
    background-color: rgba(102, 187, 106, 0.2) !important;
    color: #1B3A1B !important;
    transition: all 0.3s ease;
}

/* General program card styling */
.program-card {
    background-color: var(--color-card);
    border: 1px solid var(--color-border);
    border-left: 3px solid var(--color-highlight);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px var(--color-shadow);
}

/* Dark theme program card hover (default) */
.program-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px var(--color-shadow);
    border-color: var(--color-highlight);
    background-color: rgba(34, 53, 34, 0.8);
}

.light-theme .progress-bar {
    background-color: rgba(0, 0, 0, 0.1);
}

.light-theme .date-examples-container {
    background: rgba(76, 175, 80, 0.05);
    border-color: var(--color-border);
}

.light-theme .date-example-item {
    background: rgba(0, 0, 0, 0.02);
    border-left-color: var(--color-accent3);
}

.light-theme .date-example-value {
    background: rgba(76, 175, 80, 0.1);
    color: var(--color-accent3);
}

.alert-content h4 {
    font-size: 12px;
    margin-bottom: 2px;
    color: var(--color-text);
    font-weight: 500;
}

.alert-content p {
    font-size: 10px;
    opacity: 0.7;
    color: var(--color-text);
    margin: 0;
}

.alert-actions {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
}

.alert-buttons {
    display: flex;
    align-items: center;
    gap: 8px;
}

.alert-time {
    font-size: 9px;
    opacity: 0.6;
    color: var(--color-text);
    margin-bottom: 2px;
}

.notify-btn {
    background: linear-gradient(135deg, var(--color-highlight), var(--color-accent3));
    color: white;
    border: none;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(161, 180, 84, 0.3);
    min-width: 60px;
}

.notify-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(161, 180, 84, 0.4);
    filter: brightness(1.1);
}

.notify-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 6px rgba(161, 180, 84, 0.3);
}

/* Light theme support for notification button */
.light-theme .notify-btn {
    background: linear-gradient(135deg, var(--color-accent3), var(--color-highlight));
    box-shadow: 0 2px 8px rgba(142, 185, 110, 0.3);
}

.light-theme .notify-btn:hover {
    box-shadow: 0 4px 12px rgba(142, 185, 110, 0.4);
}

.light-theme .notify-btn:active {
    box-shadow: 0 2px 6px rgba(142, 185, 110, 0.3);
}

.alert-badge {
    display: inline-block;
    padding: 3px 6px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 600;
    transition: all 0.2s ease;
    cursor: default;
}

.alert-badge:hover {
    transform: scale(1.05);
    filter: brightness(1.1);
}

.badge-danger {
    background-color: var(--color-danger);
    color: white;
}

.badge-warning {
    background-color: var(--color-warning);
    color: #333;
}

.light-theme .badge-warning {
    color: white;
}

.badge-success {
    background-color: var(--color-highlight);
    color: #333;
}

.light-theme .badge-success {
    background-color: var(--color-accent3);
    color: white;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .chart-row {
        grid-template-columns: 1fr;
    }
    
    .new-donut-chart {
        width: 400px;
        height: 400px;
    }
    
    .donut-chart-container {
        height: 500px;
    }
    
    .chart-card {
        min-height: 600px;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}

/* Adjustments for larger screens */
@media (min-width: 1200px) {
    .dashboard {
        max-width: calc(100% - 60px);
    }
    
    .stats-grid {
        grid-template-columns: repeat(4, 1fr);
    }
    
    .chart-row {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .chart-card {
        min-height: 400px;
    }
    
    .donut-chart-container {
        height: 300px;
    }
    
    .donut-chart {
        width: 240px;
        height: 240px;
    }
    
    .bar-chart-container {
        height: 280px;
    }
    
    .line-chart-container {
        height: 250px;
    }
    
    .stat-card, .chart-card {
        padding: 20px;
    }
}



/* Additional eye-comfort improvements */
.stat-card, .chart-card {
    border-radius: 20px; /* Slightly more rounded corners */
}

.light-theme .stat-value {
    color: #75aa50; /* Less intense highlight color */
}

.light-theme .chart-card,
.light-theme .stat-card {
    background-image: linear-gradient(to bottom right, rgba(255,255,255,0.05), transparent);
    border: 1px solid rgba(255,255,255,0.1);
}

/* Add subtle grain texture to reduce eye strain from solid colors */
.light-theme:before {
    content: "";
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)' opacity='0.05'/%3E%3C/svg%3E");
    z-index: 1000;
    opacity: 0.06;
}

/* Update the responsive navbar styles */
@media (max-width: 768px) {
    .navbar {
        width: 80px;
        transform: translateX(0);
        transition: transform 0.3s ease, width 0.3s ease;
    }
    
    .navbar:hover {
        width: 320px;
    }
    
    .navbar-logo-text, .navbar span:not(.navbar-icon) {
        opacity: 0;
        transition: opacity 0.2s ease;
    }
    
    .navbar:hover .navbar-logo-text, 
    .navbar:hover span:not(.navbar-icon) {
        opacity: 1;
    }
    
    body {
        padding-left: 100px;
    }
}

/* Add this media query for responsive adjustments */
@media (max-width: 768px) {
    .navbar a {
        padding: 12px 25px;  /* Slightly reduce vertical padding for mobile */
    }
    
    .navbar li {
        margin-bottom: 2px;  /* Further reduce spacing on mobile */
    }
}

/* Custom scrollbar - Add this to match USM.html */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: var(--background-color);
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, var(--secondary-color) 0%, #7cb342 100%);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #7cb342 0%, #689f38 100%);
}

/* User section styles for other parts of the dashboard */
.user-section {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    padding: 10px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 5px;
}

.user-section .user-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background-color: #8bc34a;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    margin-right: 15px;
}

.user-section .user-info {
    flex-grow: 1;
}

.user-name {
    font-weight: bold;
    font-size: 18px;
    margin-bottom: 5px;
}

.user-email {
    font-size: 14px;
    color: #888;
}

.logout-btn {
    background-color: #f44336;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
}

.logout-btn:hover {
    background-color: #d32f2f;
}

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

/* Refreshing animations */
@keyframes moonOrbit {
    0% {
        transform: rotate(0deg) translateX(170px) rotate(0deg);
        opacity: 0;
    }
    10% {
        opacity: 1;
    }
    90% {
        opacity: 1;
    }
    100% {
        transform: rotate(360deg) translateX(170px) rotate(-360deg);
        opacity: 0;
    }
}


.refresh-moon {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    background: var(--color-highlight);
    border-radius: 50%;
    transform-origin: center;
    animation: moonOrbit 2s ease-in-out;
    z-index: 10;
}

/* Hover effects for screening issues */
.screening-issues-hover {
    transition: all 0.3s ease;
}

.screening-issues-hover:hover {
    transform: scale(1.02);
    box-shadow: 0 8px 25px rgba(161, 180, 84, 0.2);
}

.main-issue-hover {
    transition: all 0.3s ease;
}

.main-issue-hover:hover {
    background: rgba(161, 180, 84, 0.2) !important;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(161, 180, 84, 0.3);
}

.summary-item-hover {
    transition: all 0.3s ease;
}

.summary-item-hover:hover {
    background: rgba(161, 180, 84, 0.15) !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(161, 180, 84, 0.2);
    border-color: var(--color-highlight) !important;
}
/* New Analytics Styles */
.legend {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 10px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
}

.legend-color {
    width: 16px;
    height: 16px;
    border-radius: 3px;
    flex-shrink: 0;
}

.legend-label {
    flex: 1;
    color: var(--color-text);
}

.legend-value {
    color: var(--color-text);
    opacity: 0.8;
    font-weight: 500;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    padding: 10px;
}

.summary-item {
    background: rgba(161, 180, 84, 0.1);
    border: 1px solid var(--color-highlight);
    border-radius: 8px;
    padding: 12px;
    text-align: center;
}

.summary-item h4 {
    margin: 0 0 8px 0;
    color: var(--color-highlight);
    font-size: 14px;
    font-weight: 600;
}

.summary-item p {
    margin: 0;
    color: var(--color-text);
    font-size: 16px;
    font-weight: 700;
}

.chart-card.full-width {
    grid-column: 1 / -1;
}

.pie-chart-container {
    display: flex;
    align-items: center;
    gap: 25px;
    padding: 25px;
    background: rgba(161, 180, 84, 0.03);
    border-radius: 12px;
    border: 1px solid rgba(161, 180, 84, 0.1);
}

.pie-chart {
    flex-shrink: 0;
    width: 220px;
    height: 220px;
    border-radius: 50%;
    overflow: visible;
    transition: transform 0.3s ease;
}

/* Add smooth transitions for donut chart updates */
.donut-chart-bg, .pie-chart-bg {
    transition: background 0.5s ease-in-out, opacity 0.3s ease-in-out;
}

.segment {
    transition: opacity 0.3s ease-in-out;
}

/* Smooth transitions for center text updates */
#risk-center-text {
    transition: transform 0.15s ease-in-out, color 0.15s ease-in-out;
}

/* Pie chart hover effects removed */

.pie-center-value {
    font-size: 24px;
    font-weight: 800;
    fill: var(--color-highlight);
    opacity: 1;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
    transition: transform 0.15s ease-in-out, fill 0.15s ease-in-out;
}

.light-theme .pie-chart-container {
    background: var(--color-hover);
    border-color: var(--color-border);
}

.light-theme .pie-chart {
    box-shadow: 0 0 20px var(--color-shadow);
}

.light-theme .pie-center-value {
    fill: var(--color-highlight);
}

.light-theme .screening-chart {
    background: var(--color-hover);
    border-color: var(--color-border);
}

.light-theme .donut-chart {
    box-shadow: 0 0 20px var(--color-shadow);
}

.light-theme .donut-chart::before {
    box-shadow: inset 0 0 15px var(--color-shadow);
}

.light-theme .donut-center-text {
    background-color: white;
    color: var(--color-text);
    box-shadow: 0 2px 8px var(--color-shadow);
}

.light-theme .bar {
    background-color: var(--color-accent1);
    box-shadow: 0 2px 5px var(--color-shadow);
}

.light-theme .bar-wave {
    background: var(--color-accent2);
}

.light-theme .line-path {
    stroke: var(--color-highlight);
    filter: drop-shadow(0 2px 4px var(--color-shadow));
}

.light-theme .line-area {
    fill: url(#muac-line-gradient);
    opacity: 0.4;
}

.light-theme .segments .segment {
    background: rgba(0, 0, 0, 0.02);
    border-color: var(--color-border);
    box-shadow: 0 2px 8px var(--color-shadow);
}

.light-theme .segment:hover {
    background: rgba(102, 187, 106, 0.05);
    box-shadow: 0 4px 12px var(--color-shadow);
}

.light-theme .segment-label {
    color: var(--color-text);
}

.light-theme .segment-percentage {
    background: rgba(0, 0, 0, 0.06);
    color: var(--color-accent2);
}

.light-theme .segment-risk-level {
    background: var(--color-active);
    color: var(--color-highlight);
}

.light-theme .segment.compact {
    background: rgba(76, 175, 80, 0.05) !important;
    border-color: var(--color-border) !important;
}

.light-theme .segment.compact .segment-label {
    color: var(--color-text) !important;
}

.light-theme .segment.compact .segment-percentage {
    color: var(--color-text) !important;
}

.light-theme .segment.compact .segment-risk-level {
    background: var(--color-active) !important;
    color: var(--color-highlight) !important;
}

/* Light theme hover effects for compact segments */
.light-theme .segment.compact:hover {
    background: rgba(76, 175, 80, 0.08) !important;
    border-color: var(--color-highlight) !important;
    box-shadow: 0 4px 15px rgba(76, 175, 80, 0.15) !important;
    transform: translateY(-1px) !important;
    transition: all 0.2s ease !important;
}

/* Light theme color indicators - Ensure colored boxes work in light theme */
.light-theme .segment.compact[data-risk-level="0"] .segment-label::before {
    background-color: #4CAF50 !important; /* Light theme green */
    border: 1px solid rgba(76, 175, 80, 0.3) !important;
}

.light-theme .segment.compact[data-risk-level="1"] .segment-label::before {
    background-color: #FF9800 !important; /* Yellow for moderate */
    border: 1px solid rgba(255, 152, 0, 0.3) !important;
}

.light-theme .segment.compact[data-risk-level="2"] .segment-label::before {
    background-color: #F44336 !important; /* Red for high */
    border: 1px solid rgba(244, 67, 54, 0.3) !important;
}

.light-theme .segment.compact[data-risk-level="3"] .segment-label::before {
    background-color: #D32F2F !important; /* Dark Red for severe */
    border: 1px solid rgba(211, 47, 47, 0.3) !important;
}

/* Dark theme color indicators - matching donut chart colors */
.dark-theme .segment.compact[data-risk-level="0"] .segment-label::before {
    background-color: #A1B454 !important; /* Dark theme: Green for Low Risk */
}

.dark-theme .segment.compact[data-risk-level="1"] .segment-label::before {
    background-color: #F9B97F !important; /* Yellow for Moderate Risk */
}

.dark-theme .segment.compact[data-risk-level="2"] .segment-label::before {
    background-color: #E53E3E !important; /* Red for High Risk */
}

.dark-theme .segment.compact[data-risk-level="3"] .segment-label::before {
    background-color: #D32F2F !important; /* Dark Red for Severe Risk */
}

/* Dark theme segment text styling - ensure white text */
.dark-theme .segment.compact .segment-label {
    color: #FFFFFF !important; /* White text for dark theme */
}

.dark-theme .segment.compact .segment-percentage {
    color: #FFFFFF !important; /* White text for dark theme */
}

.dark-theme .segment-label {
    color: #FFFFFF !important; /* White text for dark theme */
}

.dark-theme .segment-percentage {
    color: #FFFFFF !important; /* White text for dark theme */
}

/* Light theme response questions */
.light-theme .response-question {
    color: #1B3A1B !important;
    border-bottom-color: var(--color-border) !important;
}

/* Light theme comprehensive text color fixes */
.light-theme .stat-card,
.light-theme .chart-card,
.light-theme .filter-section,
.light-theme .card {
    color: #1B3A1B !important;
}

.light-theme .stat-card h3,
.light-theme .chart-card h3,
.light-theme .card h3 {
    color: #1B3A1B !important;
}

.light-theme .stat-card .stat-value,
.light-theme .chart-card .chart-title {
    color: #1B3A1B !important;
}

.light-theme .filter-group label,
.light-theme .filter-group select {
    color: #1B3A1B !important;
}

.light-theme .time-btn {
    color: #1B3A1B !important;
}

.light-theme .time-btn.active {
    background-color: var(--color-highlight) !important;
    color: #1B3A1B !important;
}

.light-theme .custom-select-container .select-header {
    color: #1B3A1B !important;
}

.light-theme .dropdown-content .option-item {
    color: #1B3A1B !important;
}

.light-theme .search-container input {
    color: #1B3A1B !important;
}

.light-theme .search-container input::placeholder {
    color: #666 !important;
}

/* Fix all remaining green text colors in light theme */
.light-theme .stat-value,
.light-theme .chart-title,
.light-theme .impact-estimate,
.light-theme .progress-label span:last-child,
.light-theme .segment-percentage,
.light-theme .segment-risk-level,
.light-theme .answer-label,
.light-theme .answer-count,
.light-theme .answer-percentage,
.light-theme .date-range-display,
.light-theme .ai-reasoning-modal h3,
.light-theme .ai-reasoning-modal strong,
.light-theme .notification-modal h3,
.light-theme .notification-modal strong,
.light-theme .community-status,
.light-theme .target-location strong,
.light-theme .metric-value,
.light-theme .metric-change,
.light-theme .whz-value,
.light-theme .chart-description,
.light-theme .metric-note,
.light-theme .response-question,
.light-theme .header-label,
.light-theme .header-count,
.light-theme .header-percent {
    color: #1B3A1B !important;
}







.screening-chart {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(161, 180, 84, 0.05);
    border-radius: 12px;
    border: 1px solid rgba(161, 180, 84, 0.1);
    transition: all 0.3s ease;
}

/* Screening chart hover effects removed */











/* Line Chart Styles for WHZ and MUAC */
.line-path {
    fill: none;
    stroke: var(--color-highlight);
    stroke-width: 3px;
    stroke-linecap: round;
    stroke-linejoin: round;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
    transition: all 0.3s ease;
}

.line-area {
    fill: url(#muac-line-gradient);
    opacity: 0.6;
    transition: all 0.3s ease;
}

#whz-line-area {
    fill: url(#whz-line-gradient);
}

.light-theme .line-path {
    stroke: var(--color-highlight);
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.15));
}

/* Line chart hover effects removed */

@media (max-width: 768px) {
    .pie-chart-container {
        flex-direction: column;
        align-items: center;
        gap: 15px;
    }
    
    .pie-chart {
        width: 150px;
        height: 150px;
    }
    
    .summary-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 10px;
    }
    
    .program-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .create-this-program-btn {
        margin-left: 0;
        align-self: flex-start;
    }
}

/* Create Program Button Styles */
.create-program-btn {
    background: linear-gradient(135deg, var(--color-highlight), var(--color-accent1));
    color: var(--color-bg);
    border: none;
    padding: 12px 20px;
    border-radius: 25px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(161, 180, 84, 0.3);
    text-decoration: none;
}

.create-program-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(161, 180, 84, 0.4);
    background: linear-gradient(135deg, var(--color-accent1), var(--color-highlight));
}

.create-program-btn:active {
    transform: translateY(0);
}

.btn-icon {
    font-size: 16px;
    font-weight: bold;
}

.btn-text {
    white-space: nowrap;
}

.light-theme .create-program-btn {
    background: linear-gradient(135deg, var(--color-highlight), var(--color-accent3));
    color: white;
    box-shadow: 0 4px 15px rgba(118, 187, 110, 0.3);
}

.light-theme .create-program-btn:hover {
    box-shadow: 0 6px 20px rgba(118, 187, 110, 0.4);
    background: linear-gradient(135deg, var(--color-accent3), var(--color-highlight));
}

/* Loading Spinner Styles */
.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid rgba(161, 180, 84, 0.2);
    border-top: 4px solid var(--color-highlight);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.programs-loading {
    background: rgba(161, 180, 84, 0.02);
    border-radius: 10px;
    border: 1px dashed rgba(161, 180, 84, 0.2);
}



/* Generate Programs Button Styles */
.generate-programs-btn {
    background: linear-gradient(135deg, var(--color-highlight), var(--color-accent1));
    color: var(--color-bg);
    border: none;
    padding: 12px 20px;
    border-radius: 25px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(161, 180, 84, 0.3);
    text-decoration: none;
}

.generate-programs-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(161, 180, 84, 0.4);
    background: linear-gradient(135deg, var(--color-accent1), var(--color-highlight));
}

.generate-programs-btn:active {
    transform: translateY(0);
}

.generate-programs-btn .btn-text {
    white-space: nowrap;
}

/* Show AI Reasoning Button Styles */
.show-reasoning-btn {
    background: linear-gradient(135deg, #81d4fa, #4fc3f7);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 25px;
    cursor: pointer;
    font-weight: 600;
    font-size: 12px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 2px 8px rgba(129, 212, 250, 0.3);
    text-decoration: none;
}

.show-reasoning-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(129, 212, 250, 0.4);
    background: linear-gradient(135deg, #4fc3f7, #81d4fa);
}

.show-reasoning-btn:active {
    transform: translateY(0);
}

.show-reasoning-btn .btn-text {
    white-space: nowrap;
}

/* Create This Program Button Styles */
.create-this-program-btn {
    background: linear-gradient(135deg, var(--color-accent2), var(--color-accent3));
    color: var(--color-bg);
    border: none;
    padding: 8px 16px;
    border-radius: 20px;
    cursor: pointer;
    font-weight: 600;
    font-size: 12px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(181, 200, 141, 0.3);
    margin-left: 10px;
    white-space: nowrap;
}

.create-this-program-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(181, 200, 141, 0.4);
    background: linear-gradient(135deg, var(--color-accent3), var(--color-accent2));
}

.create-this-program-btn:active {
    transform: translateY(0);
}

.light-theme .create-this-program-btn {
    background: linear-gradient(135deg, var(--color-accent4), var(--color-accent2));
    color: var(--color-text);
    box-shadow: 0 2px 8px rgba(215, 227, 160, 0.3);
}

.light-theme .create-this-program-btn:hover {
    box-shadow: 0 4px 12px rgba(215, 227, 160, 0.4);
    background: linear-gradient(135deg, var(--color-accent2), var(--color-accent4));
}

/* Community Hub Styles */
.filter-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    background-color: var(--color-card);
    padding: 15px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.filter-group label {
    color: var(--color-highlight);
    font-weight: bold;
}

.filter-group select {
    background-color: var(--color-accent3);
    color: var(--color-text);
    border: 1px solid var(--color-accent3);
    padding: 8px 12px;
    border-radius: 6px;
    min-width: 200px;
}

.time-frame-buttons {
    display: flex;
    gap: 8px;
}

.time-btn {
    background-color: var(--color-accent3);
    color: var(--color-text);
    border: 1px solid var(--color-accent3);
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.time-btn.active {
    background-color: var(--color-highlight);
    color: var(--color-bg);
    border-color: var(--color-highlight);
    font-weight: bold;
}

.time-btn:hover {
    background-color: var(--color-accent2);
}

.card-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.card {
    background-color: var(--color-card);
    padding: 25px;
    border-radius: 16px;
    box-shadow: 0px 4px 15px rgba(0,0,0,0.3);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    border-top: 3px solid var(--color-highlight);
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0px 8px 20px rgba(154, 230, 110, 0.4);
}

.card h2 {
    font-size: 18px;
    margin-bottom: 15px;
    color: var(--color-text);
    font-weight: 600;
}

.metric-value {
    font-size: 32px;
    color: var(--color-text);
    margin-bottom: 8px;
    font-weight: 700;
}

.metric-change {
    font-size: 14px;
    color: var(--color-text);
    margin-bottom: 10px;
    font-weight: 500;
}

        .metric-note {
            font-size: 12px;
            color: var(--color-text);
            opacity: 0.7;
            line-height: 1.4;
        }

        /* Custom Select Styles */
        .custom-select-container {
            position: relative;
            width: 100%;
            max-width: 400px;
        }

        .select-header {
            background-color: var(--color-card);
            border: 2px solid var(--color-border);
            border-radius: 8px;
            padding: 12px 16px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .light-theme .select-header {
            background-color: white;
            border-color: var(--color-border);
            color: var(--color-text);
        }

        .light-theme .select-header:hover {
            border-color: var(--color-highlight);
            box-shadow: 0 2px 8px var(--color-shadow);
        }

        .light-theme .dropdown-content {
            background-color: white;
            border-color: var(--color-border);
            box-shadow: 0 4px 15px var(--color-shadow);
        }

        .light-theme .search-container input {
            background-color: white;
            color: var(--color-text);
            border-color: var(--color-border);
        }

        .light-theme .search-container input:focus {
            border-color: var(--color-highlight);
            box-shadow: 0 0 0 2px rgba(102, 187, 106, 0.1);
        }

        .light-theme .option-header {
            background-color: var(--color-hover);
            color: var(--color-highlight);
        }

        .light-theme .option-item {
            color: var(--color-text);
            transition: all 0.2s ease;
        }

        .light-theme .option-item:hover {
            background-color: var(--color-hover);
            transform: translateX(2px);
        }

        .light-theme .option-item.selected {
            background-color: var(--color-highlight);
            color: white;
        }

        .select-header:hover {
            border-color: var(--color-highlight);
        }

        .dropdown-arrow {
            transition: transform 0.3s ease;
        }

        .dropdown-arrow.active {
            transform: rotate(180deg);
        }

        .dropdown-content {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background-color: var(--color-card);
            border: 2px solid var(--color-border);
            border-radius: 8px;
            margin-top: 4px;
            max-height: 400px;
            overflow: hidden;
            z-index: 1000;
            display: none;
        }

        .dropdown-content.active {
            display: block;
        }

        .search-container {
            padding: 16px;
            border-bottom: 1px solid var(--color-border);
        }

        .search-container input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--color-border);
            border-radius: 6px;
            background-color: var(--color-background);
            color: var(--color-text);
            font-size: 14px;
        }

        .search-container input:focus {
            outline: none;
            border-color: var(--color-highlight);
        }

        .options-container {
            max-height: 300px;
            overflow-y: auto;
        }

        .option-group {
            border-bottom: 1px solid var(--color-border);
        }

        .option-header {
            padding: 12px 16px 8px;
            font-weight: 600;
            color: var(--color-highlight);
            background-color: rgba(154, 230, 110, 0.1);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .option-item {
            padding: 10px 16px;
            cursor: pointer;
            transition: background-color 0.2s ease;
            font-size: 14px;
        }

        .option-item:hover {
            background-color: rgba(154, 230, 110, 0.1);
        }

        .option-item.selected {
            background-color: var(--color-highlight);
            color: var(--color-background);
        }

.program-cards-container {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-top: 8px;
}

.program-card {
    background-color: rgba(23, 35, 23, 0.7);
    padding: 16px;
    border-radius: 10px;
    display: block;
    margin-bottom: 16px;
    transition: all 0.3s ease;
    border-left: 4px solid var(--color-highlight);
    position: relative;
    overflow: hidden;
    cursor: pointer;
}

.program-card:hover {
    transform: translateY(-2px);
    box-shadow: 0px 8px 20px rgba(161, 180, 84, 0.15);
    border-left-color: var(--color-accent2);
}

.program-content {
    flex: 1;
}

.program-title {
    font-size: 15px;
    font-weight: 600;
    color: var(--color-text);
    margin-bottom: 6px;
}

.program-description {
    font-size: 13px;
    color: var(--color-text);
    opacity: 0.8;
    margin-bottom: 12px;
    line-height: 1.3;
}

.program-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
}

.priority-tag {
    display: inline-block;
    padding: 3px 6px;
    border-radius: 3px;
    font-size: 11px;
    margin-top: 8px;
    font-weight: bold;
}

.priority-high {
    background-color: #FF9800;
    color: #1B3A1B;
}

.priority-immediate {
    background-color: #E53E3E;
    color: #FFFFFF;
}

.priority-medium {
    background-color: #4CAF50;
    color: #FFFFFF;
}

/* Light theme priority labels with donut chart colors */
.light-theme .priority-high {
    background-color: #FF9800 !important;
    color: #1B3A1B !important;
}

.light-theme .priority-immediate {
    background-color: #E53E3E !important;
    color: #FFFFFF !important;
}

.light-theme .priority-medium {
    background-color: #4CAF50 !important;
    color: #FFFFFF !important;
}

/* Light theme priority tag hover effects - maintain original colors */
.light-theme .program-card:hover .priority-high {
    background-color: #FF9800 !important;
    color: #1B3A1B !important;
}

.light-theme .program-card:hover .priority-immediate {
    background-color: #E53E3E !important;
    color: #FFFFFF !important;
}

.light-theme .program-card:hover .priority-medium {
    background-color: #4CAF50 !important;
    color: #FFFFFF !important;
}

.impact-estimate {
    font-size: 11px;
    color: var(--color-highlight);
    opacity: 0.9;
}

.progress-container {
    display: flex;
    flex-direction: column;
    gap: 18px;
    margin-top: 15px;
    padding: 5px;
}

.progress-item {
    margin-bottom: 5px;
    transition: transform 0.2s ease;
}

.progress-item:hover {
    transform: translateX(5px);
}

.progress-label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: 500;
}

.progress-label span:last-child {
    color: var(--color-highlight);
    font-weight: 600;
}

.progress-bar {
    width: 100%;
    height: 10px;
    background-color: rgba(34, 53, 34, 0.7);
    border-radius: 6px;
    overflow: hidden;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.2);
}

.progress-fill {
    height: 100%;
    border-radius: 6px;
    transition: width 0.5s ease;
    position: relative;
}

.progress-high { background-color: #FF6B6B; }
.progress-medium { background-color: #FFC107; }
.progress-low { background-color: #666; }

/* Screening Responses Section Styles */
.screening-part {
    background: var(--color-card);
    border-radius: 20px;
    padding: 28px;
    margin-bottom: 28px;
    border: none;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08), 0 2px 8px rgba(0, 0, 0, 0.04);
    position: relative;
    overflow: hidden;
    width: 100%;
    min-height: 260px;
    contain: layout;
}

.screening-part::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--color-highlight), var(--color-accent1), var(--color-highlight));
    border-radius: 20px 20px 0 0;
}

.response-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 25px;
    width: 100%;
    position: relative;
    contain: layout;
}

/* Part 1 specific layout - 4 boxes in one row */
.part1-grid {
    display: grid !important;
    grid-template-columns: repeat(4, 1fr) !important;
    gap: 20px;
    width: 100%;
    position: relative;
    min-height: 200px;
}

/* Force Part 1 grid to always be 4 columns */
.part1-grid {
    grid-template-columns: repeat(4, 1fr) !important;
    grid-auto-flow: row;
    align-items: start;
}

/* Override chart-card flex behavior for screening responses */
.chart-card .screening-part {
    display: block !important;
    flex: none !important;
}

.chart-card .response-grid {
    display: grid !important;
    flex: none !important;
    width: 100% !important;
}

.chart-card .part1-grid {
    display: grid !important;
    flex: none !important;
    width: 100% !important;
    min-height: 260px;
}

/* Ensure chart-card doesn't interfere with grid */
.chart-card[style*="grid-column: 1 / -1"] {
    display: block !important;
    flex-direction: unset !important;
}

/* Column Headers Styling */
.column-headers {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    align-items: center;
    padding: 6px 12px;
    margin-bottom: 8px;
    background: linear-gradient(135deg, rgba(0, 0, 0, 0.08), rgba(0, 0, 0, 0.04));
    border-radius: 6px;
    border: 1px solid rgba(0, 0, 0, 0.1);
    font-weight: 600;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--color-text);
    opacity: 0.9;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
    gap: 8px;
}

.header-label {
    grid-column: 1;
    text-align: left;
    color: var(--color-highlight);
}

.header-count {
    grid-column: 2;
    text-align: center;
    color: var(--color-accent1);
    justify-self: center;
}

.header-percent {
    grid-column: 3;
    text-align: center;
    color: var(--color-accent2);
    justify-self: center;
}

/* Custom scrollbar styling for response answers */
.response-answers::-webkit-scrollbar {
    width: 6px;
}

.response-answers::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.05);
    border-radius: 3px;
}

.response-answers::-webkit-scrollbar-thumb {
    background: var(--color-highlight);
    border-radius: 3px;
    transition: background 0.3s ease;
}

.response-answers::-webkit-scrollbar-thumb:hover {
    background: var(--color-accent1);
}

/* Firefox scrollbar */
.response-answers {
    scrollbar-width: thin;
    scrollbar-color: var(--color-highlight) rgba(0, 0, 0, 0.05);
}

.part1-grid .response-item {
    height: 260px;
    padding: 20px;
    position: relative;
    contain: layout;
    width: 100%;
    max-width: none;
    flex-shrink: 0;
    grid-column: span 1;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

/* Responsive adjustments for Part 1 */
@media (max-width: 1400px) {
    .part1-grid {
        display: grid !important;
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 18px;
        width: 100%;
    }
}

@media (max-width: 768px) {
    .part1-grid {
        display: grid !important;
        grid-template-columns: 1fr !important;
        gap: 16px;
        width: 100%;
    }
    
    .response-item {
        padding: 20px;
        min-height: 140px;
        min-width: 100%;
    }
    
    .response-answer-item {
        padding: 12px 16px;
    }
    
    .answer-count {
        padding: 6px 12px;
        font-size: 11px;
        min-width: 45px;
    }
}

.response-item {
    background: var(--color-card);
    border-radius: 20px;
    padding: 24px;
    border: none;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    height: 260px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08), 0 2px 8px rgba(0, 0, 0, 0.04);
    position: relative;
    overflow: hidden;
    animation: fadeInUp 0.6s ease-out;
    contain: layout style;
    box-sizing: border-box;
    width: 100%;
    flex-shrink: 0;
}

.response-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.12), 0 4px 15px rgba(0, 0, 0, 0.08);
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOutRight {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}

.response-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--color-highlight), var(--color-accent1));
    border-radius: 16px 16px 0 0;
}

.response-item:hover {
    background: var(--color-card);
    border-color: var(--color-highlight);
    transform: translateY(-4px);
    box-shadow: 0 12px 30px rgba(161, 180, 84, 0.2);
}

.response-item:hover::before {
    background: linear-gradient(90deg, var(--color-accent1), var(--color-highlight));
}

.response-question {
    font-weight: 600;
    color: var(--color-text);
    margin-bottom: 16px;
    font-size: 13px;
    opacity: 0.95;
    border-bottom: 1px solid var(--color-border);
    padding-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    height: 50px;
    display: flex;
    align-items: center;
    flex-shrink: 0;
    line-height: 1.2;
}

.response-answers {
    height: 210px;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    width: 100%;
    overflow-y: auto;
    overflow-x: hidden;
    padding-right: 8px;
}

.response-answer-item {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    align-items: center;
    padding: 7px 12px;
    margin-bottom: 6px;
    background: rgba(0, 0, 0, 0.05);
    border-radius: 8px;
    border: none;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    position: relative;
    width: 100%;
    box-sizing: border-box;
    contain: layout;
    gap: 8px;
    cursor: pointer;
}

.response-answer-item:hover {
    background: rgba(0, 0, 0, 0.08);
    transform: translateY(-1px);
    box-shadow: 0 4px 20px rgba(161, 180, 84, 0.15), 0 2px 12px rgba(161, 180, 84, 0.1);
    border: 1px solid rgba(161, 180, 84, 0.2);
}

.response-answer-item:last-child {
    margin-bottom: 0;
}

.response-answer-item:hover {
    background: rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12), 0 4px 15px rgba(0, 0, 0, 0.08);
}

.answer-label {
    font-size: 11px;
    color: var(--color-text);
    opacity: 0.95;
    font-weight: 500;
    line-height: 1.3;
    padding: 2px 0;
    grid-column: 1;
    text-align: left;
    transition: all 0.3s ease;
}

.response-answer-item:hover .answer-label {
    color: var(--color-highlight);
    text-shadow: 0 0 8px rgba(161, 180, 84, 0.3);
}

.answer-count {
    font-weight: 600;
    color: var(--color-text);
    font-size: 11px;
    padding: 2px 6px;
    background: rgba(0, 0, 0, 0.08);
    border-radius: 4px;
    min-width: 30px;
    text-align: center;
    grid-column: 2;
    justify-self: center;
    transition: all 0.3s ease;
}

.response-answer-item:hover .answer-count {
    background: rgba(161, 180, 84, 0.15);
    color: var(--color-highlight);
    box-shadow: 0 0 12px rgba(161, 180, 84, 0.3);
}

/* Removed glowy shine effect */

.answer-percentage {
    font-size: 10px;
    color: var(--color-text);
    opacity: 0.9;
    font-weight: 500;
    background: rgba(0, 0, 0, 0.05);
    padding: 2px 6px;
    border-radius: 4px;
    min-width: 40px;
    text-align: center;
    grid-column: 3;
    justify-self: center;
    transition: all 0.3s ease;
}

.response-answer-item:hover .answer-percentage {
    background: rgba(161, 180, 84, 0.1);
    color: var(--color-accent1);
    box-shadow: 0 0 10px rgba(161, 180, 84, 0.25);
}

.loading-placeholder {
    color: var(--color-text);
    opacity: 0.5;
    font-style: italic;
    text-align: center;
    padding: 12px;
    font-size: 12px;
}

.no-data-message {
    color: var(--color-text);
    opacity: 0.6;
    text-align: center;
    padding: 12px;
    font-style: italic;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    min-height: 40px;
    font-size: 12px;
}

/* Light theme adjustments */
.light-theme .screening-part {
    background: var(--color-card);
    border: 1px solid var(--color-border);
    box-shadow: 0 6px 20px var(--color-shadow);
}

.light-theme .screening-part::before {
    background: linear-gradient(90deg, var(--color-highlight), var(--color-accent1));
}

.light-theme .response-item {
    background: var(--color-card);
    border: 1px solid var(--color-border);
    box-shadow: 0 6px 20px var(--color-shadow);
}

.light-theme .response-item:hover {
    background: var(--color-card);
    box-shadow: 0 8px 25px rgba(102, 187, 106, 0.15);
    border-color: var(--color-highlight);
}

.light-theme .response-item::before {
    background: linear-gradient(90deg, var(--color-highlight), var(--color-accent1));
}

.light-theme .response-answer-item {
    background: rgba(0, 0, 0, 0.02);
    border: 1px solid var(--color-border);
}

.light-theme .response-answer-item:hover {
    background: rgba(102, 187, 106, 0.05);
    box-shadow: 0 4px 12px var(--color-shadow);
    border-color: var(--color-highlight);
}

.light-theme .column-headers {
    background: linear-gradient(135deg, rgba(0, 0, 0, 0.03), rgba(0, 0, 0, 0.01));
    border-color: var(--color-border);
    box-shadow: 0 1px 4px var(--color-shadow);
}

.light-theme .header-label {
    color: var(--color-highlight);
}

.light-theme .header-count {
    color: var(--color-accent1);
}

.light-theme .header-percent {
    color: var(--color-accent2);
}

.light-theme .response-item {
    background: var(--color-card);
    border: none;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
}

.light-theme .response-item:hover {
    background: var(--color-card);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.light-theme .response-answer-item {
    background: rgba(0, 0, 0, 0.02);
}

.light-theme .response-answer-item:hover {
    background: rgba(0, 0, 0, 0.04);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

/* Refresh Button Styles */
.refresh-btn {
    background: linear-gradient(135deg, var(--color-primary), var(--color-accent1));
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.refresh-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
    background: linear-gradient(135deg, var(--color-accent1), var(--color-primary));
}

.refresh-btn:active {
    transform: translateY(0);
}

.btn-icon {
    font-size: 16px;
}

        .btn-text {
            font-weight: 500;
        }
        
        /* Date Examples Styling */
        .date-examples-container {
            padding: 20px;
            background: rgba(161, 180, 84, 0.05);
            border-radius: 10px;
            margin: 15px 0;
            border: 1px solid rgba(161, 180, 84, 0.1);
        }
        
        .date-examples-container h4 {
            color: var(--color-highlight);
            margin-bottom: 15px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .date-example-item {
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            background: rgba(0, 0, 0, 0.03);
            border-radius: 6px;
            border-left: 3px solid var(--color-accent3);
        }
        
        .date-example-item:last-child {
            margin-bottom: 15px;
        }
        
        .date-example-label {
            font-weight: 500;
            color: var(--color-text);
            font-size: 13px;
        }
        
        .date-example-value {
            color: var(--color-accent1);
            font-weight: 600;
            font-size: 12px;
            background: rgba(161, 180, 84, 0.1);
            padding: 4px 8px;
            border-radius: 4px;
        }
        
        .light-theme .date-example-value {
            background: rgba(118, 187, 110, 0.1);
            color: var(--color-accent3);
        }

/* Prevent layout shifts when switching themes */
body {
    min-height: 100vh;
    transition: background-color 0.3s ease, color 0.3s ease;
}

/* Ensure consistent spacing and alignment */
.dashboard,
.navbar,
.stat-card,
.chart-card,
.filter-section,
.screening-part,
.response-item,
.program-card,
.card {
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
}

/* Light theme specific improvements */
.light-theme .stat-value {
    color: var(--color-highlight);
}

.light-theme .stat-label {
    color: var(--color-text);
    opacity: 0.8;
}

.light-theme .chart-description {
    color: var(--color-text);
    opacity: 0.7;
}

/* Improved button styling for light theme */
.light-theme .btn,
.light-theme button {
    transition: all 0.3s ease;
}

.light-theme .btn:hover,
.light-theme button:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 187, 106, 0.2);
}



/* Consistent form styling */
.light-theme input,
.light-theme select,
.light-theme textarea {
    background-color: white;
    color: var(--color-text);
    border-color: var(--color-border);
    transition: all 0.3s ease;
}

.light-theme input:focus,
.light-theme select:focus,
.light-theme textarea:focus {
    border-color: var(--color-highlight);
    box-shadow: 0 0 0 2px rgba(102, 187, 106, 0.1);
}

/* Improved table styling for light theme */
.light-theme table {
    background-color: var(--color-card);
    border-color: var(--color-border);
}

.light-theme th {
    background-color: var(--color-hover);
    color: var(--color-text);
    border-color: var(--color-border);
}

.light-theme td {
    border-color: var(--color-border);
}

.light-theme tr:hover {
    background-color: var(--color-hover);
}

/* Consistent spacing for light theme */
.light-theme .section,
.light-theme .container,
.light-theme .wrapper {
    background-color: var(--color-card);
    border-color: var(--color-border);
    box-shadow: 0 2px 8px var(--color-shadow);
}

/* Ensure seamless theme switching without breaking layout */
body {
    box-sizing: border-box;
}

/* Final catch-all for any remaining green text in light theme */
.light-theme * {
    color: inherit;
}

.light-theme .card,
.light-theme .stat-card,
.light-theme .chart-card,
.light-theme .response-item,
.light-theme .response-answer-item,
.light-theme .program-card,
.light-theme .segment,
.light-theme .segment.compact {
    color: #1B3A1B !important;
}

/* Ensure all text in light theme is dark */
.light-theme h1,
.light-theme h2,
.light-theme h3,
.light-theme h4,
.light-theme h5,
.light-theme h6,
.light-theme p,
.light-theme span,
.light-theme div,
.light-theme label,
.light-theme strong,
.light-theme b {
    color: #1B3A1B !important;
}

/* Exclude intentional colored elements */
.light-theme .priority-tag,
.light-theme .segment.compact[data-risk-level] .segment-label::before,
.light-theme .whz-item .whz-dot,
.light-theme .color-indicator {
    color: inherit !important;
}

/* Segment percentage styling */
.segment.compact .segment-percentage {
    background-color: rgba(102, 187, 106, 0.08) !important;
    color: #1B3A1B !important;
    padding: 2px 6px !important;
    border-radius: 4px !important;
    font-weight: 500 !important;
    min-width: 32px !important;
    text-align: center !important;
}

.light-theme .segment.compact .segment-percentage {
    background-color: rgba(102, 187, 106, 0.1) !important;
    color: #1B3A1B !important;
    border: 1px solid rgba(102, 187, 106, 0.15) !important;
}

/* Smooth transitions for theme switching */
.light-theme,
.dark-theme {
    transition: background-color 0.3s ease, color 0.3s ease;
}
</style>
</head>
<body class="light-theme">

    <div class="navbar">
        <div class="navbar-header">
            <div class="navbar-logo">
                <div class="navbar-logo-icon">
                    <img src="logo.png" alt="Logo" style="width: 40px; height: 40px;">
                </div>
                <div class="navbar-logo-text">NutriSaur</div>
            </div>
        </div>
        <div class="navbar-menu">
            <ul>
                <li><a href="dash.php"><span class="navbar-icon"></span><span>Dashboard</span></a></li>

                <li><a href="event.php"><span class="navbar-icon"></span><span>Nutrition Event Notifications</span></a></li>
                <li><a href="AI.php"><span class="navbar-icon"></span><span>Chatbot & AI Logs</span></a></li>
                <li><a href="settings.php"><span class="navbar-icon"></span><span>Settings & Admin</span></a></li>
                <li><a href="logout.php" style="color: #ff5252;"><span class="navbar-icon"></span><span>Logout</span></a></li>
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
            <div class="chart-card">
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
            console.log('toggleDropdown called');
            const dropdown = document.getElementById('dropdown-content');
            const arrow = document.querySelector('.dropdown-arrow');
            const selectHeader = document.querySelector('.select-header');
            
            console.log('Dropdown elements found:', { 
                dropdown: !!dropdown, 
                arrow: !!arrow, 
                selectHeader: !!selectHeader 
            });
            
            if (dropdown && arrow) {
                dropdown.classList.toggle('active');
                arrow.classList.toggle('active');
                console.log('Dropdown toggled, active state:', dropdown.classList.contains('active'));
                
                // Log the current state
                if (dropdown.classList.contains('active')) {
                    console.log('Dropdown is now OPEN');
                    console.log('Dropdown content:', dropdown.innerHTML);
                } else {
                    console.log('Dropdown is now CLOSED');
                }
            } else {
                console.error('Dropdown elements not found:', { dropdown: !!dropdown, arrow: !!arrow });
            }
        }

        function selectOption(value, text) {
            console.log('selectOption called with:', { value, text });
            
            const selectedOption = document.getElementById('selected-option');
            const dropdownContent = document.getElementById('dropdown-content');
            const dropdownArrow = document.querySelector('.dropdown-arrow');
            
            console.log('Elements found:', { 
                selectedOption: !!selectedOption, 
                dropdownContent: !!dropdownContent, 
                dropdownArrow: !!dropdownArrow 
            });
            
            if (selectedOption && dropdownContent && dropdownArrow) {
                selectedOption.textContent = text;
                dropdownContent.classList.remove('active');
                dropdownArrow.classList.remove('active');
                
                console.log('Updated selected option to:', text);
                console.log('Closed dropdown');
                
                // Update dashboard data based on selected barangay or municipality
                console.log('Calling updateDashboardForBarangay with value:', value);
                updateDashboardForBarangay(value);
                
                // Test municipality filtering if a municipality is selected
                if (value && value.startsWith('MUNICIPALITY_')) {
                    console.log('Testing municipality filtering...');
                    testMunicipalityFiltering(value);
                }
                
                // Test user data consistency for debugging
                console.log('Testing user data consistency...');
                testUserDataConsistency('hwheh@ushs.dijs'); // Test with the user from logs
                
                // Update selected state
                document.querySelectorAll('.option-item').forEach(item => {
                    item.classList.remove('selected');
                });
                
                // Try to find the clicked item and mark it as selected
                const clickedItem = document.querySelector(`[data-value="${value}"]`);
                if (clickedItem) {
                    clickedItem.classList.add('selected');
                    console.log('Marked item as selected:', clickedItem.textContent);
                } else {
                    console.error('Could not find clicked item to mark as selected');
                }
                
                // If "All Barangays" is selected, clear the localStorage
                if (!value || value === '') {
                    localStorage.removeItem('selectedBarangay');
                    console.log('Cleared barangay selection from localStorage (All Barangays selected)');
                }
            } else {
                console.error('Required elements not found for selectOption');
            }
        }

        function filterOptions() {
            const searchInput = document.getElementById('search-input');
            const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
            const optionItems = document.querySelectorAll('.option-item');
            
            console.log('filterOptions called');
            console.log('Search input element:', !!searchInput);
            console.log('Search term:', searchTerm);
            console.log('Total option items:', optionItems.length);
            
            if (optionItems.length === 0) {
                console.error('No option items found for filtering!');
                return;
            }
            
            let visibleCount = 0;
            optionItems.forEach((item, index) => {
                const text = item.textContent.toLowerCase();
                const matches = text.includes(searchTerm);
                
                if (matches) {
                    item.style.display = 'block';
                    visibleCount++;
                    console.log(`Option ${index} visible:`, text);
                } else {
                    item.style.display = 'none';
                    console.log(`Option ${index} hidden:`, text);
                }
            });
            
            console.log('Visible options after filtering:', visibleCount);
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            console.log('Document click event:', event.target);
            
            const container = document.querySelector('.custom-select-container');
            console.log('Container found:', !!container);
            
            if (container && !container.contains(event.target)) {
                console.log('Click outside container detected');
                const dropdown = document.getElementById('dropdown-content');
                const arrow = document.querySelector('.dropdown-arrow');
                
                if (dropdown && arrow) {
                    dropdown.classList.remove('active');
                    arrow.classList.remove('active');
                    console.log('Dropdown closed by outside click');
                } else {
                    console.error('Dropdown elements not found for outside click close');
                }
            }
        });

        // Barangay and Municipality selection handling
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Setting up barangay selection handlers...');
            
            // Set up click handlers for option items
            const optionItems = document.querySelectorAll('.option-item');
            console.log('Found option items:', optionItems.length);
            
            if (optionItems.length === 0) {
                console.error('No option items found! This means the dropdown is not working properly.');
                console.log('Checking if dropdown container exists...');
                const dropdownContainer = document.querySelector('.dropdown-content');
                console.log('Dropdown container:', dropdownContainer);
                
                if (dropdownContainer) {
                    console.log('Dropdown container HTML:', dropdownContainer.innerHTML);
                }
                
                // Try to find option items with a different selector
                const alternativeOptions = document.querySelectorAll('[data-value]');
                console.log('Alternative options found:', alternativeOptions.length);
                
                if (alternativeOptions.length > 0) {
                    console.log('Using alternative options...');
                    alternativeOptions.forEach((item, index) => {
                        const value = item.getAttribute('data-value');
                        const text = item.textContent;
                        console.log(`Alternative option ${index}:`, { value, text });
                        
                        item.addEventListener('click', function() {
                            const value = this.getAttribute('data-value');
                            const text = this.textContent;
                            console.log('Alternative barangay selected:', { value, text });
                            selectOption(value, text);
                        });
                    });
                }
            } else {
                optionItems.forEach((item, index) => {
                    const value = item.getAttribute('data-value');
                    const text = item.textContent;
                    console.log(`Setting up option ${index}:`, { value, text });
                    
                    item.addEventListener('click', function() {
                        const value = this.getAttribute('data-value');
                        const text = this.textContent;
                        console.log('Barangay selected:', { value, text });
                        selectOption(value, text);
                    });
                });
            }
            
            console.log('Barangay selection handlers setup complete');
        });

        // Global variable to store the currently selected barangay
        let currentSelectedBarangay = '';
        
        // Function to restore selected barangay from localStorage
        function restoreSelectedBarangay() {
            try {
                const savedBarangay = localStorage.getItem('selectedBarangay');
                if (savedBarangay) {
                    console.log('Found saved barangay in localStorage:', savedBarangay);
                    currentSelectedBarangay = savedBarangay;
                    
                    // Update the dropdown display to show the saved selection
                    const selectedOptionElement = document.getElementById('selected-option');
                    if (selectedOptionElement) {
                        // Find the corresponding option text for the saved value
                        const optionItem = document.querySelector(`[data-value="${savedBarangay}"]`);
                        if (optionItem) {
                            selectedOptionElement.textContent = optionItem.textContent;
                            console.log('Restored dropdown display to:', optionItem.textContent);
                        } else {
                            console.log('Could not find option item for saved value:', savedBarangay);
                        }
                    }
                    
                    // Mark the saved option as selected in the dropdown
                    document.querySelectorAll('.option-item').forEach(item => {
                        item.classList.remove('selected');
                        if (item.getAttribute('data-value') === savedBarangay) {
                            item.classList.add('selected');
                            console.log('Marked saved option as selected:', item.textContent);
                        }
                    });
                    
                    console.log('Successfully restored barangay selection:', savedBarangay);
                    return true; // Indicate successful restoration
                } else {
                    console.log('No saved barangay found in localStorage');
                    return false; // Indicate no restoration needed
                }
            } catch (error) {
                console.error('Error restoring barangay selection:', error);
                return false; // Indicate restoration failed
            }
        }
        
        // Function to clear barangay selection
        function clearBarangaySelection() {
            currentSelectedBarangay = '';
            localStorage.removeItem('selectedBarangay');
            console.log('Cleared barangay selection');
            
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
            console.log('=== UPDATING DASHBOARD FOR BARANGAY ===');
            console.log('Barangay parameter:', barangay);
            console.log('Barangay type:', typeof barangay);
            console.log('Barangay length:', barangay ? barangay.length : 0);
            
            // Store the selected barangay globally
            if (barangay !== undefined && barangay !== null) {
                currentSelectedBarangay = barangay;
                console.log('Stored current barangay:', currentSelectedBarangay);
                
                // Also store in localStorage for persistence across page refreshes
                if (barangay !== '') {
                    localStorage.setItem('selectedBarangay', barangay);
                    console.log('Saved barangay to localStorage:', barangay);
                } else {
                    localStorage.removeItem('selectedBarangay');
                    console.log('Cleared barangay from localStorage (All Barangays selected)');
                }
            }
            
            // Update the "Programs in Barangay" metric
            console.log('Calling updateProgramsMetric...');
            updateProgramsMetric(barangay);
            
            // Update all charts and metrics for the selected barangay
            console.log('Calling updateCommunityMetrics...');
            updateCommunityMetrics(barangay);
            
            // Update all charts and metrics for the selected barangay
            console.log('Calling updateCharts...');
            updateCharts(barangay);
            
            // Update analysis section
            console.log('Calling updateAnalysisSection...');
            updateAnalysisSection(barangay);
            
            // Update geographic distribution chart
            console.log('Calling updateGeographicChart...');
            updateGeographicChart(barangay);
            
            // Update critical alerts
            console.log('Calling updateCriticalAlerts...');
            updateCriticalAlerts(barangay);
            
            // Automatically refresh intelligent programs for the selected location
            console.log('Calling updateIntelligentPrograms...');
            updateIntelligentPrograms(barangay);
            
            // Update screening responses for the selected barangay
            console.log('Calling loadScreeningResponses...');
            setTimeout(() => {
                loadScreeningResponses(barangay);
            }, 1000);
            
            console.log('=== DASHBOARD UPDATE COMPLETE ===');
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
                console.error('Error calculating total programs:', error);
                return 0;
            }
        }

        // Function to update programs metric
        async function updateProgramsMetric(barangay) {
            console.log('updateProgramsMetric called with barangay:', barangay);
            
            const programsElement = document.getElementById('programs-in-barangay');
            const programsChangeElement = document.getElementById('programs-change');
            
            if (programsElement && programsChangeElement) {
                console.log('Programs elements found, updating...');
                
                if (barangay && barangay !== '') {
                    console.log('Processing specific barangay/municipality:', barangay);
                    
                    // Handle municipality selections
                    if (barangay.startsWith('MUNICIPALITY_')) {
                        const municipality = barangay.replace('MUNICIPALITY_', '');
                        let programCount = 0;
                        
                        console.log('Processing municipality:', municipality);
                        
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
                        
                        console.log(`Municipality ${municipality} has ${programCount} programs`);
                        programsElement.textContent = programCount;
                        programsChangeElement.textContent = 'Municipality';
                    } else {
                        // Handle individual barangay selections
                        console.log('Processing individual barangay:', barangay);
                        let programCount = 0;
                        // Since we removed duplicate municipality names, we can use simpler logic
                        if (barangay.includes('Bagumbayan') || barangay.includes('Poblacion') || barangay.includes('Central')) {
                            programCount = 3; // More programs in major areas
                        } else if (barangay.includes('Bangal') || barangay.includes('Bacong') || barangay.includes('Alangan')) {
                            programCount = 2; // Medium programs
                        } else {
                            programCount = 1; // Basic programs
                        }
                        
                        console.log(`Barangay ${barangay} has ${programCount} programs`);
                        programsElement.textContent = programCount;
                        programsChangeElement.textContent = 'Active';
                    }
                } else {
                    // Show total programs across all barangays
                    console.log('No barangay selected, showing total programs');
                    // Calculate total programs based on actual data instead of hardcoded value
                    const totalPrograms = await calculateTotalPrograms();
                    programsElement.textContent = totalPrograms;
                    programsChangeElement.textContent = 'All areas';
                }
            } else {
                console.error('Programs elements not found:', { programsElement: !!programsElement, programsChangeElement: !!programsChangeElement });
            }
        }

        // Function to update community metrics
        async function updateCommunityMetrics(barangay = '') {
            try {
                console.log('updateCommunityMetrics called with barangay:', barangay);
                console.log('Barangay type:', typeof barangay);
                console.log('Barangay truthy check:', !!barangay);
                console.log('Barangay empty check:', barangay !== '');
                
                const params = {};
                if (barangay && barangay !== '') {
                    params.barangay = barangay;
                    console.log('Added barangay to params:', barangay);
                    
                    // Add debugging for municipality filtering
                    if (barangay.startsWith('MUNICIPALITY_')) {
                        const municipality = barangay.replace('MUNICIPALITY_', '');
                        console.log('Municipality selected:', municipality);
                        console.log('This should filter for all barangays in:', municipality);
                    }
                } else {
                    console.log('No barangay filter applied');
                }

                console.log('Final params object:', params);
                console.log('Fetching community metrics with params:', params);
                const data = await fetchDataFromAPI('community_metrics', params);
                
                if (data && data.success) {
                    console.log('Community metrics data received:', data);
                    console.log('Total screened:', data.total_screened);
                    console.log('High risk cases:', data.high_risk_cases);
                    console.log('SAM cases:', data.sam_cases);
                    console.log('Barangay filter applied:', data.barangay_filter);
                    console.log('Average risk score from API:', data.avg_risk_score);
                    
                    // Update Total Screened
                    const totalScreened = document.getElementById('community-total-screened');
                    const screenedChange = document.getElementById('community-screened-change');
                    if (totalScreened && screenedChange) {
                        totalScreened.textContent = data.total_screened || 0;
                        screenedChange.textContent = data.screened_change || 'No change';
                    }

                    // Update High Risk Cases
                    const highRisk = document.getElementById('community-high-risk');
                    const riskChange = document.getElementById('community-risk-change');
                    if (highRisk && riskChange) {
                        highRisk.textContent = data.high_risk_cases || 0;
                        riskChange.textContent = data.risk_change || 'No change';
                    }

                    // Update SAM Cases
                    const samCases = document.getElementById('community-sam-cases');
                    const samChange = document.getElementById('community-sam-change');
                    if (samCases && samChange) {
                        samCases.textContent = data.sam_cases || 0;
                        samChange.textContent = data.sam_change || 'No change';
                    }
                    
                    // Store the average risk score globally for use in charts
                    window.globalAverageRiskScore = data.avg_risk_score || 0;
                    console.log('Stored global average risk score:', window.globalAverageRiskScore);
                    
                    // Update the risk chart center text immediately if it exists
                    const riskCenterText = document.getElementById('risk-center-text');
                    if (riskCenterText && window.globalAverageRiskScore > 0) {
                        riskCenterText.textContent = Math.round(window.globalAverageRiskScore) + '%';
                        console.log('Updated risk chart center text to:', riskCenterText.textContent);
                    }
                    
                    // Update critical alerts with the new community metrics data
                    console.log('Updating critical alerts with community metrics data...');
                    console.log('Data being passed to critical alerts:', data);
                    // Note: Critical alerts are now handled by updateCriticalAlerts() function
                } else {
                    console.error('Failed to fetch community metrics:', data);
                }
            } catch (error) {
                console.error('Error updating community metrics:', error);
            }
        }

        // Function to update charts
        async function updateCharts(barangay = '') {
            try {
                console.log('updateCharts called with barangay:', barangay);
                
                const params = {};
                if (barangay && barangay !== '') {
                    params.barangay = barangay;
                    console.log('Added barangay to chart params:', barangay);
                } else {
                    console.log('No barangay filter for charts');
                }

                console.log('Chart params object:', params);
                console.log('Updating charts with barangay filter:', barangay);

                // Update Risk Distribution Chart
                console.log('Fetching risk distribution...');
                const riskData = await fetchDataFromAPI('risk_distribution', params);
                if (riskData && riskData.success) {
                    console.log('Risk distribution data:', riskData);
                    updateRiskChart(riskData.data);
                } else {
                    console.log('Risk distribution failed or no data:', riskData);
                }

                // Update Screening Responses (Age, Gender, Income, Height, Swelling, Weight Loss, Feeding, Physical Signs, Dietary, Clinical)
                console.log('Fetching screening responses...');
                const screeningData = await fetchDataFromAPI('screening_responses', params);
                console.log('Screening responses API response:', screeningData);
                if (screeningData && screeningData.success) {
                    console.log('Screening responses data:', screeningData.data);
                    updateScreeningResponsesDisplay(screeningData.data);
                } else {
                    console.log('Screening responses failed or no data:', screeningData);
                }

                // Update Geographic Distribution Chart
                console.log('Fetching geographic distribution...');
                const geoData = await fetchDataFromAPI('geographic_distribution', params);
                if (geoData && geoData.success) {
                    console.log('Geographic data received:', geoData);
                    updateGeographicChartDisplay(geoData.data);
                } else {
                    console.log('Geographic data failed or no data:', geoData);
                }

                // Update Critical Alerts
                console.log('Fetching critical alerts...');
                const alertsData = await fetchDataFromAPI('critical_alerts', params);
                if (alertsData && alertsData.success) {
                    console.log('Critical alerts data received:', alertsData);
                    updateCriticalAlertsDisplay(alertsData.data);
                } else {
                    console.log('Critical alerts failed or no data:', alertsData);
                }
                
                // Update Nutritional Status Overview Card
                console.log('Updating Nutritional Status Overview Card...');
                updateNutritionalStatusCard([], []);
            } catch (error) {
                console.error('Error updating charts:', error);
            }
        }

        // Function to update geographic distribution
        async function updateGeographicChart(barangay = '') {
            try {
                console.log('updateGeographicChart called with barangay:', barangay);
                
                const params = {};
                if (barangay && barangay !== '') {
                    params.barangay = barangay;
                    console.log('Added barangay to geographic params:', barangay);
                } else {
                    console.log('No barangay filter for geographic chart');
                }

                console.log('Geographic chart params:', params);
                const data = await fetchDataFromAPI('geographic_distribution', params);
                if (data && data.success) {
                    console.log('Geographic data received:', data);
                    updateGeographicChartDisplay(data.data);
                } else {
                    console.log('Geographic data failed or no data:', data);
                }
            } catch (error) {
                console.error('Error updating geographic chart:', error);
            }
        }

        // Function to update geographic chart
        async function updateGeographicChart(barangay = '') {
            try {
                console.log('updateGeographicChart called with barangay:', barangay);
                
                const params = {};
                if (barangay && barangay !== '') {
                    params.barangay = barangay;
                    console.log('Added barangay to geographic chart params:', barangay);
                } else {
                    console.log('No barangay filter for geographic chart');
                }

                console.log('Geographic chart params:', params);
                const data = await fetchDataFromAPI('geographic_distribution', params);
                
                if (data && data.success) {
                    console.log('Geographic chart data received:', data);
                    updateGeographicChartDisplay(data.data);
                } else {
                    console.log('Geographic chart failed or no data:', data);
                }
            } catch (error) {
                console.error('Error updating geographic chart:', error);
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
                console.log('updateCriticalAlerts called with barangay:', barangay);
                
                const params = {};
                if (barangay && barangay !== '') {
                    params.barangay = barangay;
                    console.log('Added barangay to critical alerts params:', barangay);
                } else {
                    console.log('No barangay filter for critical alerts');
                }

                console.log('Critical alerts params:', params);
                const data = await fetchDataFromAPI('critical_alerts', params);
                if (data && data.success) {
                    console.log('Critical alerts data received:', data);
                    updateCriticalAlertsDisplay(data.data);
                } else {
                    console.log('Critical alerts failed or no data:', data);
                }
            } catch (error) {
                console.error('Error updating critical alerts:', error);
            }
        }

        // Function to generate intelligent programs (manual trigger)
        async function generateIntelligentPrograms(barangay = null) {
            // Use passed barangay parameter or fall back to currently selected barangay
            const targetBarangay = barangay !== null ? barangay : currentSelectedBarangay;
            console.log('generateIntelligentPrograms called with barangay:', targetBarangay);
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
            
            modalContent.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0; color: #1B3A1B; font-size: 18px; font-weight: 600;">📱 Send Notification</h3>
                    <button onclick="this.closest('.notification-modal').remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--color-text); opacity: 0.7; transition: opacity 0.2s ease;">&times;</button>
                </div>
                
                <div style="margin-bottom: 20px; padding: 15px; background: rgba(161, 180, 84, 0.05); border-radius: 8px; border: 1px solid rgba(161, 180, 84, 0.1);">
                    <div style="margin-bottom: 8px;">
                        <strong style="color: #1B3A1B; font-size: 14px;">Recipient:</strong>
                        <span style="color: var(--color-text); margin-left: 8px; font-weight: 500;">${userName}</span>
                    </div>
                    <div>
                        <strong style="color: #1B3A1B; font-size: 14px;">Alert:</strong>
                        <span style="color: var(--color-text); margin-left: 8px; font-weight: 500;">${alertTitle}</span>
                    </div>
                    ${userEmail ? `<div style="margin-top: 8px;"><strong style="color: #1B3A1B; font-size: 14px;">Email:</strong><span style="color: var(--color-text); margin-left: 8px; font-weight: 500;">${userEmail}</span></div>` : ''}
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; color: var(--color-text); font-weight: 500;">Notification Message:</label>
                    <textarea id="notification-message" placeholder="Enter your message to this user..." style="width: 100%; min-height: 100px; padding: 12px; border: 1px solid rgba(161, 180, 84, 0.2); border-radius: 8px; background: rgba(42, 51, 38, 0.3); color: var(--color-text); font-size: 14px; resize: vertical; font-family: inherit;"></textarea>
                </div>
                
                <div style="display: flex; gap: 15px; justify-content: flex-end;">
                    <button onclick="this.closest('.notification-modal').remove()" style="background: rgba(161, 180, 84, 0.2); color: var(--color-text); border: none; padding: 12px 24px; border-radius: 25px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s ease;">
                        Cancel
                    </button>
                    <button onclick="sendPersonalNotification('${userName}', '${userEmail}', '${alertTitle}')" style="background: linear-gradient(135deg, var(--color-highlight), var(--color-accent3)); color: white; border: none; padding: 12px 24px; border-radius: 25px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(161, 180, 84, 0.3);">
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
                // Create a custom event notification using the event.php system
                const notificationData = {
                    title: `🚨 Critical Alert: ${alertTitle}`,
                    body: message,
                    target_user: userEmail,
                    alert_type: 'critical_notification',
                    user_name: userName
                };
                
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
                
                const result = await response.json();
                
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
                console.log('updateIntelligentPrograms called with barangay:', barangay);
                
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
                    console.log('Added location to intelligent programs params:', params);
                } else {
                    console.log('No location filter for intelligent programs');
                }

                console.log('Intelligent programs params:', params);
                const data = await fetchDataFromAPI('intelligent_programs', params);
                
                if (data && data.success) {
                    console.log('Intelligent programs data received:', data);
                    updateIntelligentProgramsDisplay(data.programs, data.data_analysis);
                } else {
                    console.log('Intelligent programs failed or no data:', data);
                    // Show appropriate no-data message
                    showFallbackPrograms();
                }
            } catch (error) {
                console.error('Error updating intelligent programs:', error);
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
                console.error('Intelligent programs elements not found');
                return;
            }
            
            // Hide loading, show programs
            loadingElement.style.display = 'none';
            programsContainer.style.display = 'block';
            
            // Show debug information if available
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
                console.log('Displayed no-data message');
                return;
            }
            
            if (programs && programs.length > 0) {
                programs.forEach((program, index) => {
                    const programCard = createProgramCard(program, index);
                    programsContainer.appendChild(programCard);
                });
                
                console.log(`Generated ${programs.length} intelligent program cards`);
            } else {
                console.log('No programs to display');
                showFallbackPrograms();
            }
        }

        // Function to create individual program card
        function createProgramCard(program, index) {
            const card = document.createElement('div');
            card.className = 'program-card';
            
            // Determine priority class
            let priorityClass = 'priority-medium';
            if (program.priority === 'Critical') priorityClass = 'priority-immediate';
            else if (program.priority === 'High') priorityClass = 'priority-high';
            
            card.innerHTML = `
                <div class="program-content">
                    <div class="program-title">${program.title}</div>
                    <div class="program-description">${program.description}</div>
                    <div class="program-meta">
                        <span class="priority-tag ${priorityClass}">${getPriorityLabel(program.priority)}</span>
                        <div class="program-details" style="margin-top: 6px; font-size: 11px; opacity: 0.8;">
                            <div><strong>Type:</strong> ${program.type}</div>
                            <div><strong>Duration:</strong> ${program.duration}</div>
                            <div><strong>Target:</strong> ${program.target_audience}</div>
                            <div class="target-location" style="background: rgba(161, 180, 84, 0.2); padding: 4px 8px; border-radius: 6px; margin-top: 4px; border-left: 3px solid var(--color-highlight);">
                                <strong style="color: #4CAF50;">Target Location:</strong> ${program.location}
                            </div>
                        </div>
                        <div style="display: flex; gap: 10px; margin-top: 8px;">
                            <button class="show-reasoning-btn" onclick="showAIReasoning('${program.title}', '${program.reasoning}')">
                                Show AI Reasoning
                            </button>
                            <button class="create-this-program-btn" onclick="createProgramFromCard('${program.title}', '${program.type}', '${program.location}', '${program.description}', '${program.priority}')">
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
            
            console.log('Showing no-data message instead of fallback programs');
        }

        // Test function for critical alerts - can be called from console
        function testCriticalAlerts() {
            console.log('=== TESTING CRITICAL ALERTS ===');
            const container = document.getElementById('critical-alerts');
            if (container) {
                console.log('Critical alerts container found:', container);
                console.log('Current innerHTML length:', container.innerHTML.length);
                console.log('Current innerHTML:', container.innerHTML);
                
                // Test with sample data (1 user with both SAM and high risk)
                const testData = {
                    sam_cases: 1,
                    high_risk_cases: 1,
                    critical_muac: 0,
                    latest_update: 'now'
                };
                
                console.log('Testing with sample data:', testData);
                console.log('This should show 2 separate alerts (original design)');
                // Note: Test function updated to use new critical alerts system
            } else {
                console.error('Critical alerts container NOT found!');
            }
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
                console.log('Initialized alerts state:', currentAlertsState.hasAlerts);
            }
        }
        
        // Function to force clear alerts state (for debugging or manual reset)
        function clearAlertsState() {
            currentAlertsState.hasAlerts = false;
            currentAlertsState.lastContent = '';
            console.log('Cleared alerts state');
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
                console.log('Keeping current alerts to prevent flicker');
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
                console.log('updateAnalysisSection called with barangay:', barangay);
                
                const params = {};
                if (barangay && barangay !== '') {
                    params.barangay = barangay;
                    console.log('Added barangay to analysis params:', barangay);
                } else {
                    console.log('No barangay filter for analysis section');
                }

                console.log('Analysis section params:', params);
                const data = await fetchDataFromAPI('analysis_data', params);
                
                if (data && data.success) {
                    console.log('Analysis data received:', data);
                    // Update risk analysis
                    updateRiskAnalysis(data.risk_analysis);
                    
                    // Update demographics
                    updateDemographics(data.demographics);
                } else {
                    console.log('Analysis data failed or no data:', data);
                }
            } catch (error) {
                console.error('Error updating analysis section:', error);
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

        // Time frame button handling - DISABLED FOR NOW
        /*
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Setting up time frame button handlers...');
            const timeButtons = document.querySelectorAll('.time-btn');
            console.log('Found time buttons:', timeButtons.length);
            
            if (timeButtons.length === 0) {
                console.error('No time buttons found!');
                } else {
                timeButtons.forEach((button, index) => {
                    console.log(`Setting up time button ${index}:`, button.textContent);
                    button.addEventListener('click', function() {
                        // Remove active class from all buttons
                        timeButtons.forEach(btn => btn.classList.remove('active'));
                        
                        // Add active class to clicked button
                        this.classList.add('active');
                        
                        // Get the selected time frame
                        const timeFrame = this.textContent;
                        console.log('Selected time frame:', timeFrame);
                        
                        // Update data based on time frame (you can implement time-based filtering here)
                        updateDashboardForTimeFrame(timeFrame);
                    });
                });
            }
            
            console.log('Time frame button handlers setup complete');
        });
        */

        // Function to update dashboard data based on time frame - DISABLED FOR NOW
        /*
        function updateDashboardForTimeFrame(timeFrame) {
            console.log('=== UPDATING DASHBOARD FOR TIME FRAME ===');
            console.log('Time frame parameter:', timeFrame);
            console.log('Time frame type:', typeof timeFrame);
            
            // You can implement time-based data filtering here
            // For example, showing data for the last day, week, month, etc.
            
            console.log('Time frame update complete (placeholder implementation)');
            console.log('=== TIME FRAME UPDATE COMPLETE ===');
        }
        */

        // API Connection and Data Fetching Functions
        const API_BASE_URL = 'http://localhost/thesis355/unified_api.php';

        // Function to fetch data from API
        async function fetchDataFromAPI(endpoint, params = {}) {
            try {
                // Add endpoint to params
                const allParams = { endpoint, ...params };
                const queryString = new URLSearchParams(allParams).toString();
                const url = `${API_BASE_URL}?${queryString}`;
                
                console.log(`Fetching data from: ${url}`);
                console.log(`Endpoint: ${endpoint}, Params:`, params);
                console.log(`Full URL: ${url}`);
                
                const response = await fetch(url);
                console.log(`Response status: ${response.status}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log(`API Response for ${endpoint}:`, data);
                return data;
            } catch (error) {
                console.error(`Error fetching data for ${endpoint}:`, error);
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
                    console.error('Risk chart elements not found:', { chartBg: !!chartBg, centerText: !!centerText, segments: !!segments });
                    return;
                }
                
                // Add loading state to prevent flickering
                chartBg.style.opacity = '0.8';

                console.log('Updating risk chart with data:', data);
                
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
                
                console.log('Theme:', isDarkTheme ? 'Dark' : 'Light');
                console.log('Colors array:', colors);
                
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
                    // API returns data in format: [{label: 'Low Risk', value: 1, color: '#4CAF50', risk_score: 51}, ...]
                    // OR: [{label: 'Low Risk', value: 1, color: '#4CAF50', risk_scores: [51, 45, 38]}, ...]
                    // The risk_score field should contain the actual risk percentage (0-100) for each user
                    // Risk thresholds: Low(0-19), Moderate(20-49), High(50-79), Severe(80+)
                    data.forEach(item => {
                        totalUsers += item.value;
                        
                        // Map the label to the correct index
                        if (item.label === 'Low Risk') riskLevels[0] = item.value;
                        else if (item.label === 'Moderate Risk') riskLevels[1] = item.value;
                        else if (item.label === 'High Risk') riskLevels[2] = item.value;
                        else if (item.label === 'Critical Risk') riskLevels[3] = item.value;
                        else if (item.label === 'Severe Risk') riskLevels[3] = item.value;
                        
                        // Store actual risk scores for each user
                        if (item.risk_score !== undefined) {
                            // If risk_score is a single value, repeat it for each user
                            for (let i = 0; i < item.value; i++) {
                                actualRiskScores.push(item.risk_score);
                            }
                        } else if (item.risk_scores && Array.isArray(item.risk_scores)) {
                            // If risk_scores is an array, add all scores
                            actualRiskScores.push(...item.risk_scores);
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
                    
                    // Clear any existing SVG chart
                    const donutChart = document.querySelector('.donut-chart');
                    if (donutChart) {
                        const existingSvg = donutChart.querySelector('svg');
                        if (existingSvg) {
                            existingSvg.remove();
                        }
                    }
                    
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
                        console.log('Using global average risk score from community metrics:', averageRisk);
                    } else if (actualRiskScores.length > 0) {
                        // Fallback to actual risk scores from chart data if available
                        const sum = actualRiskScores.reduce((total, score) => total + score, 0);
                        averageRisk = Math.round(sum / actualRiskScores.length);
                        console.log('Using actual risk scores from chart data:', actualRiskScores);
                        console.log('Calculated average from actual scores:', averageRisk);
                } else {
                        // Final fallback to weighted average if no actual scores available
                        // For 1 user with 100% risk, this should give 100
                        // Updated to match Android app risk thresholds: Low(0-19), Moderate(20-49), High(50-79), Severe(80+)
                        const weightedSum = (riskLevels[0] * 10) + (riskLevels[1] * 35) + (riskLevels[2] * 65) + (riskLevels[3] * 90);
                        averageRisk = Math.round(weightedSum / totalUsers);
                        console.log('Using fallback weighted average calculation');
                        console.log('Note: API should provide risk_score field for accurate calculations');
                        console.log('Risk thresholds: Low(0-19), Moderate(20-49), High(50-79), Severe(80+)');
                    }
                }
                
                console.log('Risk levels distribution:', riskLevels);
                console.log('Risk levels array details:', riskLevels.map((count, index) => `${labels[index]}: ${count}`));
                console.log('Total users:', totalUsers);
                console.log('At risk percentage:', atRiskPercentage);
                console.log('Average risk score:', averageRisk);
                console.log('Global average risk score available:', window.globalAverageRiskScore);
                console.log('Actual risk scores from API:', actualRiskScores);
                console.log('Raw API data received:', data);
                console.log('Risk thresholds: Low(0-19), Moderate(20-49), High(50-79), Severe(80+)');
                
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
                
                console.log('Final gradient string:', gradientString);
                console.log('Total percentage calculated:', calculatedTotalPercentage);
                
                // Create clean donut chart without hover effects
                const donutChart = document.querySelector('.donut-chart');
                if (donutChart) {
                    // Remove existing SVG if any
                    const existingSvg = donutChart.querySelector('svg');
                    if (existingSvg) {
                        existingSvg.remove();
                    }
                    
                    // Create SVG container
                    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                    svg.setAttribute('width', '200');
                    svg.setAttribute('height', '200');
                    svg.setAttribute('viewBox', '0 0 200 200');
                    svg.style.position = 'absolute';
                    svg.style.top = '0';
                    svg.style.left = '0';
                    svg.style.zIndex = '3';
                    svg.style.pointerEvents = 'none';
                    
                    // Create individual segments based on risk score distribution
                    let currentAngle = 0;
                    
                    if (actualRiskScores.length > 0) {
                        // Use actual risk scores for SVG segments - MATCHING ANDROID APP LOGIC
                        const lowRiskCount = actualRiskScores.filter(score => score < 20).length;
                        const moderateRiskCount = actualRiskScores.filter(score => score >= 20 && score < 50).length;
                        const highRiskCount = actualRiskScores.filter(score => score >= 50 && score < 80).length;
                        const severeRiskCount = actualRiskScores.filter(score => score >= 80).length;
                        
                        const lowRiskPercentage = (lowRiskCount / actualRiskScores.length) * 100;
                        const moderateRiskPercentage = (moderateRiskCount / actualRiskScores.length) * 100;
                        const highRiskPercentage = (highRiskCount / actualRiskScores.length) * 100;
                        const severeRiskPercentage = (severeRiskCount / actualRiskScores.length) * 100;
                        
                        // Create segments for each risk level
                        const riskSegments = [
                            { index: 0, percentage: lowRiskPercentage, count: lowRiskCount, label: 'Low Risk' },
                            { index: 1, percentage: moderateRiskPercentage, count: moderateRiskCount, label: 'Moderate Risk' },
                            { index: 2, percentage: highRiskPercentage, count: highRiskCount, label: 'High Risk' },
                            { index: 3, percentage: severeRiskPercentage, count: severeRiskCount, label: 'Severe Risk' }
                        ];
                        
                        riskSegments.forEach((segment, segmentIndex) => {
                            if (segment.count > 0) {
                                const startAngle = currentAngle;
                                const endAngle = currentAngle + segment.percentage;
                                
                                // Convert percentages to degrees
                                const startDeg = (startAngle / 100) * 360;
                                const endDeg = (endAngle / 100) * 360;
                                
                                // Calculate SVG path for segment (donut shape)
                                const radius = 100;
                                const innerRadius = 60;
                                
                                const startRad = (startDeg - 90) * (Math.PI / 180);
                                const endRad = (endDeg - 90) * (Math.PI / 180);
                                
                                const x1 = 100 + radius * Math.cos(startRad);
                                const y1 = 100 + radius * Math.sin(startRad);
                                const x2 = 100 + radius * Math.cos(endRad);
                                const y2 = 100 + radius * Math.sin(endRad);
                                
                                const x1Inner = 100 + innerRadius * Math.cos(startRad);
                                const y1Inner = 100 + innerRadius * Math.sin(startRad);
                                const x2Inner = 100 + innerRadius * Math.cos(endRad);
                                const y2Inner = 100 + innerRadius * Math.sin(endRad);
                                
                                // Create large arc flag
                                const largeArcFlag = (endDeg - startDeg) > 180 ? 1 : 0;
                                
                                // Create path data for donut segment
                                const pathData = [
                                    `M ${x1} ${y1}`,
                                    `A ${radius} ${radius} 0 ${largeArcFlag} 1 ${x2} ${y2}`,
                                    `L ${x2Inner} ${y2Inner}`,
                                    `A ${innerRadius} ${innerRadius} 0 ${largeArcFlag} 0 ${x1Inner} ${y1Inner}`,
                                    'Z'
                                ].join(' ');
                                
                                // Create clean path element without hover effects
                                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                                path.setAttribute('d', pathData);
                                path.setAttribute('fill', colors[segment.index]);
                                path.style.opacity = '0.9';
                                path.style.transition = 'none';
                                path.style.cursor = 'default';
                                path.style.pointerEvents = 'none';
                                path.style.stroke = 'rgba(255, 255, 255, 0.1)';
                                path.style.strokeWidth = '1';
                                
                                // No hover effects - clean static display
                                
                                svg.appendChild(path);
                                currentAngle = endAngle;
                                
                                console.log(`Created clean SVG segment ${segmentIndex}: ${segment.label} - ${segment.percentage.toFixed(1)}% (${startDeg.toFixed(1)}° to ${endDeg.toFixed(1)}°)`);
                            }
                        });
                    } else {
                        // Fallback to original logic if no risk scores available
                        validSegments.forEach((segment, segmentIndex) => {
                            if (segment.count > 0) {
                                const startAngle = currentAngle;
                                const endAngle = currentAngle + segment.percentage;
                                
                                // Convert percentages to degrees
                                const startDeg = (startAngle / 100) * 360;
                                const endDeg = (endAngle / 100) * 360;
                                
                                // Calculate SVG path for segment (donut shape)
                                const radius = 100;
                                const innerRadius = 60;
                                
                                const startRad = (startDeg - 90) * (Math.PI / 180);
                                const endRad = (endDeg - 90) * (Math.PI / 180);
                                
                                const x1 = 100 + radius * Math.cos(startRad);
                                const y1 = 100 + radius * Math.sin(startRad);
                                const x2 = 100 + radius * Math.cos(endRad);
                                const y2 = 100 + radius * Math.sin(endRad);
                                
                                const x1Inner = 100 + innerRadius * Math.cos(startRad);
                                const y1Inner = 100 + innerRadius * Math.sin(startRad);
                                const x2Inner = 100 + innerRadius * Math.cos(endRad);
                                const y2Inner = 100 + innerRadius * Math.sin(endRad);
                                
                                // Create large arc flag
                                const largeArcFlag = (endDeg - startDeg) > 180 ? 1 : 0;
                                
                                // Create path data for donut segment
                                const pathData = [
                                    `M ${x1} ${y1}`,
                                    `A ${radius} ${radius} 0 ${largeArcFlag} 1 ${x2} ${y2}`,
                                    `L ${x2Inner} ${y2Inner}`,
                                    `A ${innerRadius} ${innerRadius} 0 ${largeArcFlag} 0 ${x1Inner} ${y1Inner}`,
                                    'Z'
                                ].join(' ');
                                
                                // Create clean path element without hover effects
                                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                                path.setAttribute('d', pathData);
                                path.setAttribute('fill', colors[segment.index]);
                                path.style.opacity = '0.9';
                                path.style.transition = 'none';
                                path.style.cursor = 'default';
                                path.style.pointerEvents = 'none';
                                path.style.stroke = 'rgba(255, 255, 255, 0.1)';
                                path.style.strokeWidth = '1';
                                
                                // No hover effects - clean static display
                                
                                svg.appendChild(path);
                                currentAngle = endAngle;
                                
                                console.log(`Created clean SVG segment ${segmentIndex}: ${segment.label} - ${segment.percentage.toFixed(1)}% (${startDeg.toFixed(1)}° to ${endDeg.toFixed(1)}°)`);
                            }
                        });
                    }
                    
                    donutChart.appendChild(svg);
                    console.log(`Clean SVG with ${svg.children.length} segments added to donut chart (no hover effects)`);
                }
                
                // Apply the conic gradient as background (will be behind SVG segments)
                if (gradientString.trim()) {
                    chartBg.style.background = `conic-gradient(${gradientString})`;
                    chartBg.style.opacity = '0.1'; // Make it very subtle since we have SVG segments on top
                    console.log('Applied background gradient:', gradientString);
                } else {
                    // Fallback to a default gradient if no data
                    chartBg.style.background = 'conic-gradient(#e0e0e0 0% 100%)';
                    chartBg.style.opacity = '0.1';
                    console.log('Applied fallback background gradient');
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
                                
                                console.log(`Created percentage label: ${segment.label} - ${segment.percentage.toFixed(1)}% at angle ${angleInDegrees.toFixed(1)}°`);
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
                    
                    console.log('Risk score-based distribution:', {
                        lowRisk: { count: lowRiskCount, percentage: lowRiskPercentage.toFixed(1) },
                        moderateRisk: { count: moderateRiskCount, percentage: moderateRiskPercentage.toFixed(1) },
                        highRisk: { count: highRiskCount, percentage: highRiskPercentage.toFixed(1) },
                        severeRisk: { count: severeRiskCount, percentage: severeRiskPercentage.toFixed(1) }
                    });
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
                    
                    console.log(`Updated segment: ${labels[index]} - ${count} (${percentage.toFixed(1)}%)`);
                });
                
                // Set chart background to visible immediately - no flickering
                chartBg.style.opacity = '1';
                chartBg.style.transition = 'none';
                
                console.log('Risk chart updated successfully');
                
            } catch (error) {
                console.error('Error updating risk chart:', error);
            }
        }





        // Function to update Nutritional Status Overview Card
        function updateNutritionalStatusCard(whzData, muacData) {
            console.log('updateNutritionalStatusCard called with:', { whzData, muacData });
            
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
                
                console.log('Nutritional Status Overview Card updated successfully');
            } catch (error) {
                console.error('Error updating Nutritional Status Overview Card:', error);
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
                console.error('Error updating Nutritional Summary:', error);
            }
        }

        // Helper function to create line path (copied from dashold.php design)
        function createLinePath(data) {
            console.log('createLinePath called with data:', data);
            
            if (!data || data.length === 0) {
                console.log('No data provided to createLinePath, returning empty string');
                return '';
            }
            
            const width = 1000;
            const height = 500;
            
            console.log('Chart dimensions:', { width, height });
            
            const maxValue = Math.max(...data.map(d => d.value));
            
            // Handle single data point case
            if (data.length === 1) {
                const x = width / 2; // Center horizontally
                const y = height - (data[0].value / maxValue) * height;
                const path = `M ${x},${y} L ${x},${y}`; // Single point as small line
                console.log('Single data point path:', path);
                return path;
            }
            
            const xStep = width / (data.length - 1);
            const yScale = height / maxValue;
            
            console.log('Path calculation:', { maxValue, xStep, yScale, dataLength: data.length });
            
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
            
            console.log('Generated path:', pathString);
            console.log('Generated area:', areaString);
            
            return { path: pathString, area: areaString };
        }

        // Initialize dashboard on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('=== DASHBOARD INITIALIZATION STARTED ===');
            console.log('Dashboard initialized - DOM loaded');
            
            // Initialize global average risk score
            window.globalAverageRiskScore = 0;
            console.log('Initialized global average risk score:', window.globalAverageRiskScore);
            
            // Check if key DOM elements exist
            console.log('Checking key DOM elements...');
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
            
            console.log('Key DOM elements status:', keyElements);
            
            // Check dropdown elements specifically
            console.log('Checking dropdown elements...');
            const dropdownElements = {
                'custom-select-container': document.querySelector('.custom-select-container'),
                'options-container': document.querySelector('.options-container'),
                'option-items': document.querySelectorAll('.option-item')
            };
            
            console.log('Dropdown elements status:', dropdownElements);
            
            // Test API connection first
            console.log('Testing API connection...');
            testAPIConnection();
            
            // Restore selected barangay from localStorage if available
            const barangayRestored = restoreSelectedBarangay();
            
            // Load initial data with error handling
            try {
                console.log('Loading initial dashboard data...');
                
                if (barangayRestored && currentSelectedBarangay) {
                    console.log('Loading data for previously selected barangay:', currentSelectedBarangay);
                    updateDashboardForBarangay(currentSelectedBarangay);
                } else {
                    console.log('Loading data for: All Barangays (default)');
                    
                    console.log('Calling updateCommunityMetrics()...');
                    updateCommunityMetrics();
                    
                    console.log('Calling updateCharts()...');
                    updateCharts();
                    
                    console.log('Calling updateGeographicChart()...');
                    updateGeographicChart();
                    
                    console.log('Calling updateCriticalAlerts()...');
                    // Initialize critical alerts - will be updated with real data from API
                    // Don't call updateCriticalAlerts here - it will be called automatically when community metrics are loaded
                    console.log('Critical alerts will be updated when community metrics data is loaded');
                    
                    console.log('Calling updateAnalysisSection()...');
                    updateAnalysisSection();
                    
                    console.log('Auto-generating intelligent programs...');
                    generateIntelligentPrograms(currentSelectedBarangay);
                }
                
                console.log('Initial data loading complete');
            } catch (error) {
                console.error('Error loading initial data:', error);
            }
            
            // Set up auto-refresh every 3 seconds with seamless updates
            console.log('Setting up auto-refresh every 3 seconds...');
            setInterval(() => {
                try {
                    console.log('Auto-refresh triggered');
                    // Use requestAnimationFrame for smooth, seamless updates
                    requestAnimationFrame(() => {
                        console.log('Auto-refresh: updating community metrics...');
                        updateCommunityMetrics(currentSelectedBarangay);
                        console.log('Auto-refresh: updating charts...');
                        updateCharts(currentSelectedBarangay);
                        console.log('Auto-refresh: updating critical alerts...');
                        updateCriticalAlerts(currentSelectedBarangay);
                        console.log('Auto-refresh complete');
                    });
                } catch (error) {
                    console.error('Error in auto-refresh:', error);
                }
            }, 3000);
            console.log('Auto-refresh setup complete');
            
            // Initialize critical alerts state tracking
            initializeAlertsState();
            
            console.log('=== DASHBOARD INITIALIZATION COMPLETE ===');
            
            // Test intelligent programs API connection
            console.log('Testing intelligent programs API connection...');
            testIntelligentProgramsAPI();
        });
        
        // Theme toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Setting up theme toggle...');
            
            // Initialize theme from localStorage or default to dark
            const savedTheme = localStorage.getItem('nutrisaur-theme') || 'light';
            document.body.className = savedTheme + '-theme';
            

            
            // Theme toggle functionality is handled in the main DOMContentLoaded event listener below
        });
        
        // Theme icon updates are handled in the main toggleTheme function
        

        
        // Additional debugging for dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            console.log('=== ADDITIONAL DROPDOWN DEBUGGING ===');
            
            // Check if dropdown elements exist after a short delay
            setTimeout(() => {
                console.log('Checking dropdown elements after delay...');
                
                const dropdownContent = document.getElementById('dropdown-content');
                const optionItems = document.querySelectorAll('.option-item');
                const selectHeader = document.querySelector('.select-header');
                
                console.log('Dropdown elements after delay:', {
                    dropdownContent: !!dropdownContent,
                    optionItemsCount: optionItems.length,
                    selectHeader: !!selectHeader
                });
                
                if (dropdownContent) {
                    console.log('Dropdown content HTML:', dropdownContent.innerHTML);
                }
                
                if (optionItems.length > 0) {
                    console.log('First few option items:');
                    // Convert NodeList to Array before using slice
                    Array.from(optionItems).slice(0, 3).forEach((item, index) => {
                        console.log(`Option ${index}:`, { 
                            text: item.textContent, 
                            value: item.getAttribute('data-value'), 
                            classes: item.className 
                        });
                    });
                }
                
                // Try to find any elements with data-value attribute
                const allDataValueElements = document.querySelectorAll('[data-value]');
                console.log('All elements with data-value attribute:', allDataValueElements.length);
                
                if (allDataValueElements.length > 0) {
                    console.log('First few data-value elements:');
                    // Convert NodeList to Array before using slice
                    Array.from(allDataValueElements).slice(0, 5).forEach((item, index) => {
                        console.log(`Data-value element ${index}:`, { 
                            text: item.textContent, 
                            value: item.getAttribute('data-value'), 
                            tagName: item.tagName,
                            classes: item.className 
                        });
                    });
                }
                
                // After checking elements, try to restore barangay selection if it was delayed
                if (currentSelectedBarangay && !document.querySelector('.option-item.selected')) {
                    console.log('Attempting to restore barangay selection after delay...');
                    restoreSelectedBarangay();
                }
                
                // If we still have a selected barangay but no visual indication, update the dashboard
                if (currentSelectedBarangay && currentSelectedBarangay !== '') {
                    console.log('Ensuring dashboard reflects current barangay selection...');
                    updateDashboardForBarangay(currentSelectedBarangay);
                }
                
                console.log('=== DROPDOWN DEBUGGING COMPLETE ===');
            }, 1000);
        });

        // Test API connection
        async function testAPIConnection() {
            console.log('Testing API connection...');
            try {
                const response = await fetch('http://localhost/thesis355/unified_api.php?test=1');
                console.log('API Response status:', response.status);
                if (response.ok) {
                    const data = await response.text();
                    console.log('API Response:', data);
                    console.log('API connection successful');
                } else {
                    console.error('API not responding properly');
                }
            } catch (error) {
                console.error('API Connection failed:', error);
                console.log('Please check if unified_api.php exists and XAMPP is running');
            }
        }
        
        // Test municipality filtering
        async function testMunicipalityFiltering(barangay) {
            console.log('Testing municipality filtering for:', barangay);
            try {
                const response = await fetch(`http://localhost/thesis355/unified_api.php?endpoint=test_municipality&barangay=${encodeURIComponent(barangay)}`);
                if (response.ok) {
                    const data = await response.json();
                    console.log('Municipality test response:', data);
                    
                    if (data.success) {
                        console.log('Municipality test successful');
                        console.log('Is municipality:', data.is_municipality);
                        console.log('Municipality name:', data.municipality_name);
                        console.log('Barangays in municipality:', data.barangays_in_municipality);
                        console.log('Total users in municipality:', data.total_users_in_municipality);
                        
                        if (data.sql_query) {
                            console.log('SQL query used:', data.sql_query);
                            console.log('SQL parameters:', data.params);
                        }
                    }
                } else {
                    console.error('Municipality test failed');
                }
            } catch (error) {
                console.error('Error testing municipality filtering:', error);
            }
        }
        
        // Test intelligent programs API
        async function testIntelligentProgramsAPI() {
            console.log('Testing intelligent programs API...');
            try {
                // Test general endpoint
                const response = await fetch('http://localhost/thesis355/unified_api.php?endpoint=intelligent_programs');
                if (response.ok) {
                    const data = await response.json();
                    console.log('Intelligent programs API test successful:', data);
                    
                    if (data.success && data.programs) {
                        console.log(`API returned ${data.programs.length} programs`);
                        console.log('Sample program:', data.programs[0]);
                    } else {
                        console.warn('API returned success but no programs');
                    }
                } else {
                    console.error('Intelligent programs API test failed:', response.status);
                }
                
                // Test with barangay parameter
                const barangayResponse = await fetch('http://localhost/thesis355/unified_api.php?endpoint=intelligent_programs&barangay=Bangkal');
                if (barangayResponse.ok) {
                    const barangayData = await barangayResponse.json();
                    console.log('Barangay-specific programs test:', barangayData);
                }
                
            } catch (error) {
                console.error('Error testing intelligent programs API:', error);
            }
        }

        // Debug function to show program generation analysis
        async function debugProgramGeneration() {
            console.log('Debugging program generation...');
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
                
                console.log('Debug params:', params);
                const data = await fetchDataFromAPI('intelligent_programs', params);
                
                if (data && data.success) {
                    console.log('=== PROGRAM GENERATION DEBUG ===');
                    console.log('Total programs:', data.programs ? data.programs.length : 0);
                    console.log('Data analysis:', data.data_analysis);
                    console.log('Community health status:', data.data_analysis?.community_health_status);
                    console.log('High risk percentage:', data.data_analysis?.high_risk_percentage);
                    console.log('SAM cases:', data.data_analysis?.sam_cases);
                    console.log('Total users:', data.data_analysis?.total_users);
                    
                    // Show debug info in the UI
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
                    
                    // Update the programs display
                    updateIntelligentProgramsDisplay(data.programs, data.data_analysis);
                    
                } else {
                    console.error('Debug failed - no data received');
                }
            } catch (error) {
                console.error('Error debugging program generation:', error);
            }
        }

        // Test user data consistency
        async function testUserDataConsistency(email) {
            console.log('Testing user data consistency for:', email);
            try {
                const response = await fetch(`http://localhost/thesis355/unified_api.php?endpoint=check_user_data&email=${encodeURIComponent(email)}`);
                if (response.ok) {
                    const data = await response.json();
                    console.log('User data consistency test response:', data);
                    
                    if (data.success) {
                        console.log('User data consistency test successful');
                        console.log('Individual column barangay:', data.user_data.barangay);
                        console.log('User barangay:', data.user_data.barangay);
                        console.log('Barangay match:', data.comparison.barangay_match);
                        
                        if (!data.comparison.barangay_match) {
                            console.warn('BARANGAY MISMATCH DETECTED!');
                            console.warn('Individual column:', data.user_data.barangay);
                            console.warn('User data barangay:', data.user_data.barangay);
                        }
                    }
                } else {
                    console.error('User data consistency test failed');
                }
            } catch (error) {
                console.error('Error testing user data consistency:', error);
            }
        }
        
        // Debug function to check what the API actually returns
        async function debugAPIResponse() {
            console.log('=== DEBUGGING API RESPONSE ===');
            
            try {
                const response = await fetch('http://localhost/thesis355/unified_api.php?endpoint=screening_responses');
                if (response.ok) {
                    const data = await response.json();
                    console.log('Raw API response:', data);
                    
                    if (data.success && data.data) {
                        console.log('\n=== DETAILED DATA ANALYSIS ===');
                        
                        // Check each section
                        Object.keys(data.data).forEach(key => {
                            const section = data.data[key];
                            console.log(`\n--- ${key} ---`);
                            
                            if (Array.isArray(section)) {
                                section.forEach((item, index) => {
                                    console.log(`  Item ${index}:`, item);
                                    if (item.label) {
                                        console.log(`    Label: "${item.label}" (Type: ${typeof item.label})`);
                                    }
                                    if (item.value !== undefined) {
                                        console.log(`    Value: ${item.value} (Type: ${typeof item.value})`);
                                    }
                                });
                            } else {
                                console.log('  Not an array:', section);
                            }
                        });
                    }
                } else {
                    console.log('API request failed:', response.status);
                }
            } catch (error) {
                console.error('Debug API error:', error);
            }
            
            console.log('=== API DEBUG COMPLETE ===');
        }
        
        // Debug function to check database directly
        async function debugDatabaseDirectly() {
            console.log('=== DEBUGGING DATABASE DIRECTLY ===');
            
            // Test 1: Check if we can connect to the database
            console.log('Test 1: Database connection test');
            try {
                const response1 = await fetch('http://localhost/thesis355/debug_db.php');
                console.log('Database test response status:', response1.status);
                if (response1.ok) {
                    const data1 = await response1.text();
                    console.log('Database test response:', data1);
                } else {
                    console.log('Database test failed:', response1.status);
                }
            } catch (error) {
                console.error('Database test error:', error);
            }
            
            // Test 2: Check user_preferences table directly
            console.log('Test 2: Direct table check');
            try {
                const response2 = await fetch('http://localhost/thesis355/debug_table.php?table=user_preferences');
                console.log('Table check response status:', response2.status);
                if (response2.ok) {
                    const data2 = await response2.text();
                    console.log('Table check response:', data2);
                } else {
                    console.log('Table check failed:', response2.status);
                }
            } catch (error) {
                console.error('Table check error:', error);
            }
            
            console.log('=== DATABASE DEBUG COMPLETE ===');
        }
        
        // Test screening responses API directly
        async function testScreeningResponsesAPI() {
            console.log('=== TESTING SCREENING RESPONSES API ===');
            
            // Test 1: Basic endpoint
            console.log('Test 1: Basic endpoint');
            try {
                const response1 = await fetch('http://localhost/thesis355/unified_api.php?endpoint=screening_responses');
                console.log('Response 1 status:', response1.status);
                if (response1.ok) {
                    const data1 = await response1.json();
                    console.log('Response 1 data:', data1);
                } else {
                    const error1 = await response1.text();
                    console.log('Response 1 error:', error1);
                }
            } catch (error) {
                console.error('Test 1 failed:', error);
            }
            
            // Test 2: With barangay filter
            console.log('Test 2: With barangay filter');
            try {
                const response2 = await fetch('http://localhost/thesis355/unified_api.php?endpoint=screening_responses&barangay=Bangkal');
                console.log('Response 2 status:', response2.status);
                if (response2.ok) {
                    const data2 = await response2.json();
                    console.log('Response 2 data:', data2);
                } else {
                    const error2 = await response2.text();
                    console.log('Response 2 error:', error2);
                }
            } catch (error) {
                console.error('Test 2 failed:', error);
            }
            
            // Test 3: Check if API file exists
            console.log('Test 3: Check API file');
            try {
                const response3 = await fetch('http://localhost/thesis355/unified_api.php?test=1');
                console.log('Response 3 status:', response3.status);
                if (response3.ok) {
                    const data3 = await response3.text();
                    console.log('Response 3 (API file check):', data3.substring(0, 200) + '...');
                }
            } catch (error) {
                console.error('Test 3 failed:', error);
            }
            
            // Test 4: Check database table structure
            console.log('Test 4: Check database table structure');
            try {
                const response4 = await fetch('http://localhost/thesis355/unified_api.php?endpoint=check_table_structure&table=user_preferences');
                console.log('Response 4 status:', response4.status);
                if (response4.ok) {
                    const data4 = await response4.json();
                    console.log('Response 4 (table structure):', data4);
                } else {
                    const error4 = await response4.text();
                    console.log('Response 4 error:', error4);
                }
            } catch (error) {
                console.error('Test 4 failed:', error);
            }
            
            // Test 5: Check sample data
            console.log('Test 5: Check sample data');
            try {
                const response5 = await fetch('http://localhost/thesis355/unified_api.php?endpoint=check_sample_data&table=user_preferences&limit=5');
                console.log('Response 5 status:', response5.status);
                if (response5.ok) {
                    const data5 = await response5.json();
                    console.log('Response 5 (sample data):', data5);
                } else {
                    const error5 = await response5.text();
                    console.log('Response 5 error:', error5);
                }
            } catch (error) {
                console.error('Test 5 failed:', error);
            }
            
            // Test 6: Check raw user_preferences data
            console.log('Test 6: Check raw user_preferences data');
            try {
                const response6 = await fetch('http://localhost/thesis355/unified_api.php?endpoint=check_raw_data&table=user_preferences&limit=10');
                console.log('Response 6 status:', response6.status);
                if (response6.ok) {
                    const data6 = await response6.json();
                    console.log('Response 6 (raw data):', data6);
                } else {
                    const error6 = await response6.text();
                    console.log('Response 6 error:', error6);
                }
            } catch (error) {
                console.error('Test 6 failed:', error);
            }
            
            console.log('=== SCREENING RESPONSES API TEST COMPLETE ===');
        }

        // Function to create new program - redirects to event.php in same tab
        function createNewProgram() {
            console.log('createNewProgram called');
            // Redirect to event.php in the same tab
            window.location.href = 'event.php';
        }

        // Function to create program from card - redirects to event.php with pre-filled data
        function createProgramFromCard(title, type, location, description, urgency) {
            console.log('createProgramFromCard called with:', { title, type, location, description, urgency });
            
            // Create URL parameters with proper encoding
            const params = new URLSearchParams({
                program: title,
                type: type,
                location: location,
                description: description,
                urgency: urgency
            });
            
            const url = `event.php?${params.toString()}`;
            console.log('Redirecting to:', url);
            
            // Redirect to event.php in the same tab with pre-filled form data
            window.location.href = url;
        }

        // Function to test risk calculation (for debugging)
        function testRiskCalculation() {
            console.log('=== TESTING RISK CALCULATION ===');
            console.log('Global average risk score:', window.globalAverageRiskScore);
            
            // Test with sample data
            const sampleData = [
                { label: 'Low Risk', value: 1, risk_scores: [51] }
            ];
            
            console.log('Sample data:', sampleData);
            
            // Simulate the risk calculation
            let actualRiskScores = [];
            sampleData.forEach(item => {
                if (item.risk_scores && Array.isArray(item.risk_scores)) {
                    actualRiskScores.push(...item.risk_scores);
                }
            });
            
            if (actualRiskScores.length > 0) {
                const sum = actualRiskScores.reduce((total, score) => total + score, 0);
                const calculatedAverage = Math.round(sum / actualRiskScores.length);
                console.log('Calculated average from sample data:', calculatedAverage);
                console.log('Expected: 51%');
                console.log('Actual: ' + calculatedAverage + '%');
                console.log('Match:', calculatedAverage === 51 ? '✅ YES' : '❌ NO');
            }
            
            console.log('=== RISK CALCULATION TEST COMPLETE ===');
        }
        

        
        // Expose functions globally for console testing
        window.testRiskCalculation = testRiskCalculation;
        window.testScreeningResponsesAPI = testScreeningResponsesAPI;
        window.debugDatabaseDirectly = debugDatabaseDirectly;
        window.debugAPIResponse = debugAPIResponse;
        
        // Screening Responses Functions
        async function loadScreeningResponses(barangay = '') {
            try {
                let url = 'http://localhost/thesis355/unified_api.php?endpoint=screening_responses';
                if (barangay && barangay !== '') {
                    url += `&barangay=${encodeURIComponent(barangay)}`;
                }
                
                console.log('Loading screening responses for barangay:', barangay || 'All Barangays');
                console.log('API URL:', url);
                
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
                    console.error('Failed to fetch screening responses:', response.status);
                    showScreeningResponsesError(`HTTP ${response.status}: ${errorText}`);
                }
            } catch (error) {
                console.error('Network/parsing error:', error);
                showScreeningResponsesError(`Connection error: ${error.message}`);
            }
        }
        

        
        function updateResponseSection(elementId, data, labelType, totalScreened = null) {
            console.log(`=== updateResponseSection called for ${elementId} ===`);
            console.log('Parameters:', { elementId, data, labelType, totalScreened });
            
            const element = document.getElementById(elementId);
            if (!element) {
                console.error(`Element ${elementId} not found in DOM`);
                return;
            }
            
            console.log(`Element found:`, element);
            console.log(`Element HTML:`, element.innerHTML);
            
            // Find the data container (after the headers)
            const dataContainer = element.querySelector('.response-data-container');
            console.log(`Data container found:`, dataContainer);
            
            if (!dataContainer) {
                console.warn(`Data container not found in ${elementId}, creating one after the headers`);
                // If no data container exists, create one after the headers
                const headers = element.querySelector('.column-headers');
                if (headers) {
                    const newDataContainer = document.createElement('div');
                    newDataContainer.className = 'response-data-container';
                    element.appendChild(newDataContainer);
                    console.log(`Created new data container for ${elementId}`);
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
                console.warn(`Raw data for ${elementId}:`, { data, labelType });
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
            
            console.log(`Total for ${elementId}:`, total);
            
            data.forEach((item, index) => {
                const count = item.count || item.value || 0;
                const percentage = total > 0 ? ((count / total) * 100).toFixed(1) : 0;
                const displayLabel = getDisplayLabel(item, labelType);
                
                console.log(`Item ${index}:`, { item, count, percentage, displayLabel });
                
                html += `
                    <div class="response-answer-item">
                        <span class="answer-label">${displayLabel}</span>
                        <span class="answer-count">${count}</span>
                        <span class="answer-percentage">${percentage}%</span>
                    </div>
                `;
            });
            
            console.log(`Generated HTML for ${elementId}:`, html);
            if (dataContainer) {
                dataContainer.innerHTML = html;
                } else {
                element.innerHTML = html;
            }
        }
        
        // Function to get proper display labels for different data types
        function getDisplayLabel(item, labelType) {
            console.log(`getDisplayLabel called with:`, { item, labelType });
            
            // Handle different field name patterns from API
            let label = '';
            
            // Check for different field name patterns
            if (item.label !== undefined) {
                label = item.label;
                console.log(`Found label field: "${label}"`);
            } else if (item.swelling_status !== undefined) {
                label = item.swelling_status;
                console.log(`Found swelling_status field: "${label}"`);
            } else if (item.weight_loss_status !== undefined) {
                label = item.weight_loss_status;
                console.log(`Found weight_loss_status field: "${label}"`);
            } else if (item.feeding_behavior_status !== undefined) {
                label = item.feeding_behavior_status;
                console.log(`Found feeding_behavior_status field: "${label}"`);
            } else if (item.dietary_diversity_level !== undefined) {
                label = item.dietary_diversity_level;
                console.log(`Found dietary_diversity_level field: "${label}"`);
            } else if (item.age_group !== undefined) {
                label = item.age_group;
                console.log(`Found age_group field: "${label}"`);
            } else if (item.gender !== undefined) {
                label = item.gender;
                console.log(`Found gender field: "${label}"`);
            } else if (item.income_level !== undefined) {
                label = item.income_level;
                console.log(`Found income_level field: "${label}"`);
            } else if (item.height_range !== undefined) {
                label = item.height_range;
                console.log(`Found height_range field: "${label}"`);
            } else if (item.value !== undefined) {
                label = item.value;
                console.log(`Found value field: "${label}"`);
            } else if (item.count !== undefined) {
                label = item.count;
                console.log(`Found count field: "${label}"`);
            } else if (item.name !== undefined) {
                label = item.name;
                console.log(`Found name field: "${label}"`);
            }
            
            console.log(`Final label extracted: "${label}"`);
            
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
            console.log('=== DOM CONTENT LOADED ===');
            
            // Initialize dashboard with default data (1 day)
            console.log('Initializing dashboard with default time frame...');
            updateDashboardByTimeFrame('1d');
            
            // Initialize Intelligent Programs
            console.log('Initializing Intelligent Programs...');
            generateIntelligentPrograms();
            
            const timeButtons = document.querySelectorAll('.time-btn');
            
            timeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    console.log('Time frame button clicked:', this.getAttribute('data-timeframe'));
                    
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
                    
                    console.log('Barangay selected:', selectedValue, selectedText);
                    
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
            console.log('Updating dashboard for time frame:', timeFrame);
            
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
                console.log(`Fetching time frame data: ${timeFrame}, barangay: ${barangay}`);
                const response = await fetch(`http://localhost/thesis355/unified_api.php?endpoint=time_frame_data&time_frame=${timeFrame}&barangay=${encodeURIComponent(barangay)}`);
                
                if (response.ok) {
                    const responseData = await response.json();
                    console.log('=== API RESPONSE ===');
                    console.log('Full response:', responseData);
                    
                    if (responseData.success) {
                        console.log('Success response, data:', responseData.data);
                        updateDashboardWithData(responseData.data);
                    } else {
                        console.error('Failed to fetch time frame data:', responseData.message);
                        hideDashboardLoading();
                    }
                } else {
                    console.error('HTTP error:', response.status);
                    hideDashboardLoading();
                    }
                }
            } catch (error) {
                console.error('Network error:', error);
                hideDashboardLoading();
            }
        }
        */
        
        // Function to update dashboard with new data - DISABLED FOR NOW
        /*
        function updateDashboardWithData(data) {
            console.log('=== UPDATE DASHBOARD WITH DATA ===');
            console.log('Raw data received:', data);
            console.log('Data types:', {
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
            
            console.log('Values to set:', {
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
                console.log('Setting date range to:', dateRangeText);
                dateRangeElement.textContent = dateRangeText;
            } else {
                console.warn('Date range element not found');
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
            
            console.log('Dashboard updated with new data:', data);
        }
        */
        
        // Function to update screening responses display with new data
        function updateScreeningResponsesDisplay(data) {
            console.log('=== updateScreeningResponsesDisplay called ===');
            console.log('Data received:', data);
            
            // Update age groups
            console.log('Updating age groups...');
            updateResponseSection('age-group-responses', data.age_groups || [], 'Age Group', data.total_screened);
            
            // Update gender distribution
            console.log('Updating gender distribution...');
            updateResponseSection('gender-responses', data.gender_distribution || [], 'Gender', data.total_screened);
            
            // Update income levels
            console.log('Updating income levels...');
            updateResponseSection('income-responses', data.income_levels || [], 'Income Level', data.total_screened);
            
            // Update height distribution
            console.log('Updating height distribution...');
            updateResponseSection('height-responses', data.height_distribution || [], 'Height Range', data.total_screened);
            
            // Update swelling distribution
            console.log('Updating swelling distribution...');
            updateResponseSection('swelling-responses', data.swelling_distribution || [], 'Swelling Status', data.total_screened);
            
            // Update weight loss distribution
            console.log('Updating weight loss distribution...');
            updateResponseSection('weight-loss-responses', data.weight_loss_distribution || [], 'Weight Loss Status', data.total_screened);
            
            // Update feeding behavior distribution
            console.log('Updating feeding behavior distribution...');
            updateResponseSection('feeding-behavior-responses', data.feeding_behavior_distribution || [], 'Feeding Behavior', data.total_screened);
            
            // Update physical signs
            console.log('Updating physical signs...');
            updateResponseSection('physical-signs-responses', data.physical_signs || [], 'Physical Sign', data.total_screened);
            
            // Update dietary diversity distribution
            console.log('Updating dietary diversity distribution...');
            updateResponseSection('dietary-diversity-responses', data.dietary_diversity_distribution || [], 'Dietary Diversity Score', data.total_screened);
            
            // Update clinical risk factors
            console.log('Updating clinical risk factors...');
            updateResponseSection('clinical-risk-responses', data.clinical_risk_factors || [], 'Clinical Risk Factor', data.total_screened);
            
            // Update critical alerts
            console.log('Updating critical alerts...');
            updateCriticalAlerts(data);
            
            console.log('=== updateScreeningResponsesDisplay complete ===');
        }
        
        // Function to update critical alerts based on new data
        function updateCriticalAlerts(data) {
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
                    console.log('Keeping current alerts to prevent "no alerts" flicker');
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
            console.log('Setting up new theme toggle event listener');
            newToggleBtn.addEventListener('click', newToggleTheme);
            console.log('New theme toggle event listener added successfully');
            
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
                    updateDashboardByLocation(selectedValue);
                }
            });
        }
    
    </script>
</body>
</html>



