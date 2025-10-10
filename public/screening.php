<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    header('Location: /home');
    exit;
}

// Use centralized DatabaseAPI
require_once __DIR__ . '/api/DatabaseHelper.php';

// Use WHO Growth Standards directly
require_once __DIR__ . '/../who_growth_standards.php';

// Get user's municipality for filtering
$user_municipality = null;
if (isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    try {
        require_once __DIR__ . "/../config.php";
        $pdo = getDatabaseConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT municipality FROM users WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user_data = $stmt->fetch();
            $user_municipality = $user_data['municipality'] ?? null;
        }
    } catch (Exception $e) {
        error_log("Error getting user municipality in screening.php: " . $e->getMessage());
    }
}

// Function to get adult BMI classification (for children over 71 months)
// This is only used as fallback for children over 71 months when WHO standards don't apply
function getAdultBMIClassification($bmi) {
    if ($bmi < 18.5) return ['z_score' => -1.0, 'classification' => 'Underweight'];
    if ($bmi < 25) return ['z_score' => 0.0, 'classification' => 'Normal'];
    if ($bmi < 30) return ['z_score' => 1.0, 'classification' => 'Overweight'];
    return ['z_score' => 2.0, 'classification' => 'Obese'];
}

// Function to get accurate z-score range based on WHO classification and standard
function getAccurateZScoreRange($classification, $standard) {
    if ($classification === null || $classification === 'N/A') {
        return 'N/A';
    }
    
    // Handle special error cases that don't have z-score ranges
    $errorClassifications = [
        'Not applicable',
        'Age out of range', 
        'Height out of range',
        'Weight out of range',
        'No data available',
        'Age not found'
    ];
    
    if (in_array($classification, $errorClassifications)) {
        return $classification; // Return the error message as-is
    }
    
    // Define z-score ranges based on WHO standards for each classification
    // This works for ALL WHO standards - just use the classification from the decision tree
    $ranges = [
        'weight-for-age' => [
            'Severely Underweight' => '≤ -3SD',
            'Underweight' => '> -3SD to ≤ -2SD', 
            'Normal' => '> -2SD to ≤ +2SD',
            'Overweight' => '> +2SD'
        ],
        'height-for-age' => [
            'Severely Stunted' => '≤ -3SD',
            'Stunted' => '> -3SD to ≤ -2SD',
            'Normal' => '> -2SD to ≤ +2SD', 
            'Tall' => '> +2SD'
        ],
        'weight-for-height' => [
            'Severely Wasted' => '≤ -3SD',
            'Wasted' => '> -3SD to ≤ -2SD',
            'Normal' => '> -2SD to ≤ +2SD',
            'Overweight' => '> +2SD to ≤ +3SD',
            'Obese' => '> +3SD'
        ],
        'bmi-for-age' => [
            'Underweight' => '< 5th percentile',
            'Normal' => '5th – 85th percentile',
            'Overweight' => '85th – 95th percentile',
            'Obese' => '> 95th percentile'
        ],
        'bmi-adult' => [
            'Underweight' => '< 18.5 kg/m²',
            'Normal' => '18.5-24.9 kg/m²',
            'Overweight' => '25.0-29.9 kg/m²',
            'Obese' => '≥ 30.0 kg/m²'
        ]
    ];
    
    // Return the appropriate range for the classification and standard
    if (isset($ranges[$standard][$classification])) {
        return $ranges[$standard][$classification];
    }
    
    // If classification not found, return the classification itself (might be a new error type)
    return $classification;
}

// Function to convert z-score to standard deviation range display (legacy function)
function getStandardDeviationRange($zScore) {
    if ($zScore === null || $zScore === 'N/A') {
        return 'N/A';
    }
    
    $zScore = floatval($zScore);
    
    if ($zScore < -3) {
        return '< -3SD';
    } elseif ($zScore >= -3 && $zScore < -2) {
        return '-3SD to < -2SD';
    } elseif ($zScore >= -2 && $zScore <= 2) {
        return '-2SD to +2SD';
    } elseif ($zScore > 2 && $zScore <= 3) {
        return '> +2SD to +3SD';
    } else {
        return '> +3SD';
    }
}

// Wrapper function to use WHO Growth Standards
function getNutritionalAssessment($user) {
    try {
        $who = new WHOGrowthStandards();
        
        // Calculate age in months for WHO standards using screening date
        $birthDate = new DateTime($user['birthday']);
        $screeningDate = new DateTime($user['screening_date'] ?? date('Y-m-d H:i:s'));
        $age = $birthDate->diff($screeningDate);
        $ageInMonths = ($age->y * 12) + $age->m;
        
        // Get comprehensive WHO Growth Standards assessment
        $assessment = $who->getComprehensiveAssessment(
            floatval($user['weight']), 
            floatval($user['height']), 
            $user['birthday'], 
            $user['sex'],
            $user['screening_date'] ?? null
        );
        
        if ($assessment['success']) {
            return $assessment;
        } else {
            return [
                'success' => false,
                'error' => $assessment['error'] ?? 'Assessment failed',
                'nutritional_status' => 'Assessment Error',
                'risk_level' => 'Unknown',
                'category' => 'Error'
            ];
        }
    } catch (Exception $e) {
        error_log("WHO Growth Standards error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'nutritional_status' => 'Assessment Error',
            'risk_level' => 'Unknown',
            'category' => 'Error'
        ];
    }
}


// Get database helper instance
$db = DatabaseHelper::getInstance();

// Check if database is available
$dbError = null;
if (!$db->isAvailable()) {
    $dbError = "Database connection not available";
    error_log($dbError);
}

// Get user info
$username = $_SESSION['username'] ?? 'Unknown User';
$user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;

// Municipalities and Barangays data
$municipalities = [
    'ABUCAY' => ['Bangkal', 'Calaylayan (Pob.)', 'Capitangan', 'Gabon', 'Laon (Pob.)', 'Mabatang', 'Omboy', 'Salian', 'Wawa (Pob.)'],
    'BAGAC' => ['Bagumbayan (Pob.)', 'Banawang', 'Binuangan', 'Binukawan', 'Ibaba', 'Ibis', 'Pag-asa (Wawa-Sibacan)', 'Parang', 'Paysawan', 'Quinawan', 'San Antonio', 'Saysain', 'Tabing-Ilog (Pob.)', 'Atilano L. Ricardo'],
    'CITY OF BALANGA (Capital)' => ['Bagumbayan', 'Cabog-Cabog', 'Munting Batangas (Cadre)', 'Cataning', 'Central', 'Cupang Proper', 'Cupang West', 'Dangcol (Bernabe)', 'Ibayo', 'Malabia', 'Poblacion', 'Pto. Rivas Ibaba', 'Pto. Rivas Itaas', 'San Jose', 'Sibacan', 'Camacho', 'Talisay', 'Tanato', 'Tenejero', 'Tortugas', 'Tuyo', 'Bagong Silang', 'Cupang North', 'Doña Francisca', 'Lote'],
    'DINALUPIHAN' => ['Bangal', 'Bonifacio (Pob.)', 'Burgos (Pob.)', 'Colo', 'Daang Bago', 'Dalao', 'Del Pilar (Pob.)', 'Gen. Luna (Pob.)', 'Gomez (Pob.)', 'Happy Valley', 'Kataasan', 'Layac', 'Luacan', 'Mabini Proper (Pob.)', 'Mabini Ext. (Pob.)', 'Magsaysay', 'Naparing', 'New San Jose', 'Old San Jose', 'Padre Dandan (Pob.)', 'Pag-asa', 'Pagalanggang', 'Pinulot', 'Pita', 'Rizal (Pob.)', 'Roosevelt', 'Roxas (Pob.)', 'Saguing', 'San Benito', 'San Isidro (Pob.)', 'San Pablo (Bulate)', 'San Ramon', 'San Simon', 'Santo Niño', 'Sapang Balas', 'Santa Isabel (Tabacan)', 'Torres Bugauen (Pob.)', 'Tucop', 'Zamora (Pob.)', 'Aquino', 'Bayan-bayanan', 'Maligaya', 'Payangan', 'Pentor', 'Tubo-tubo', 'Jose C. Payumo, Jr.'],
    'HERMOSA' => ['A. Rivera (Pob.)', 'Almacen', 'Bacong', 'Balsic', 'Bamban', 'Burgos-Soliman (Pob.)', 'Cataning (Pob.)', 'Culis', 'Daungan (Pob.)', 'Mabiga', 'Mabuco', 'Maite', 'Mambog - Mandama', 'Palihan', 'Pandatung', 'Pulo', 'Saba', 'San Pedro (Pob.)', 'Santo Cristo (Pob.)', 'Sumalo', 'Tipo', 'Judge Roman Cruz Sr. (Mandama)', 'Sacrifice Valley'],
    'LIMAY' => ['Alangan', 'Kitang I', 'Kitang 2 & Luz', 'Lamao', 'Landing', 'Poblacion', 'Reformista', 'Townsite', 'Wawa', 'Duale', 'San Francisco de Asis', 'St. Francis II'],
    'MARIVELES' => ['Alas-asin', 'Alion', 'Batangas II', 'Cabcaben', 'Lucanin', 'Baseco Country (Nassco)', 'Poblacion', 'San Carlos', 'San Isidro', 'Sisiman', 'Balon-Anito', 'Biaan', 'Camaya', 'Ipag', 'Malaya', 'Maligaya', 'Mt. View', 'Townsite'],
    'MORONG' => ['Binaritan', 'Mabayo', 'Nagbalayong', 'Poblacion', 'Sabang'],
    'ORANI' => ['Bagong Paraiso (Pob.)', 'Balut (Pob.)', 'Bayan (Pob.)', 'Calero (Pob.)', 'Paking-Carbonero (Pob.)', 'Centro II (Pob.)', 'Dona', 'Kaparangan', 'Masantol', 'Mulawin', 'Pag-asa', 'Palihan (Pob.)', 'Pantalan Bago (Pob.)', 'Pantalan Luma (Pob.)', 'Parang Parang (Pob.)', 'Centro I (Pob.)', 'Sibul', 'Silahis', 'Tala', 'Talimundoc', 'Tapulao', 'Tenejero (Pob.)', 'Tugatog', 'Wawa (Pob.)', 'Apollo', 'Kabalutan', 'Maria Fe', 'Puksuan', 'Tagumpay'],
    'ORION' => ['Arellano (Pob.)', 'Bagumbayan (Pob.)', 'Balagtas (Pob.)', 'Balut (Pob.)', 'Bantan', 'Bilolo', 'Calungusan', 'Camachile', 'Daang Bago (Pob.)', 'Daang Bilolo (Pob.)', 'Daang Pare', 'General Lim (Kaput)', 'Kapunitan', 'Lati (Pob.)', 'Lusungan (Pob.)', 'Puting Buhangin', 'Sabatan', 'San Vicente (Pob.)', 'Santo Domingo', 'Villa Angeles (Pob.)', 'Wakas (Pob.)', 'Wawa (Pob.)', 'Santa Elena'],
    'PILAR' => ['Ala-uli', 'Bagumbayan', 'Balut I', 'Balut II', 'Bantan Munti', 'Burgos', 'Del Rosario (Pob.)', 'Diwa', 'Landing', 'Liyang', 'Nagwaling', 'Panilao', 'Pantingan', 'Poblacion', 'Rizal (Pob.)', 'Santa Rosa', 'Wakas North', 'Wakas South', 'Wawa'],
    'SAMAL' => ['East Calaguiman (Pob.)', 'East Daang Bago (Pob.)', 'Ibaba (Pob.)', 'Imelda', 'Lalawigan', 'Palili', 'San Juan (Pob.)', 'San Roque (Pob.)', 'Santa Lucia', 'Sapa', 'Tabing Ilog', 'Gugo', 'West Calaguiman (Pob.)', 'West Daang Bago (Pob.)']
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $screening_data = [
            'municipality' => $_POST['municipality'] ?? '',
            'barangay' => $_POST['barangay'] ?? '',
            'sex' => $_POST['sex'] ?? '',
            'birthday' => $_POST['birthday'] ?? '',
            'is_pregnant' => $_POST['is_pregnant'] ?? '',
            'weight' => $_POST['weight'] ?? '',
            'height' => $_POST['height'] ?? '',
            'muac' => $_POST['muac'] ?? '',
            'screening_date' => date('Y-m-d H:i:s'),
            'fcm_token' => $_POST['fcm_token'] ?? null
        ];

        // Get user info from session
        $user_email = $_SESSION['user_email'] ?? 'user@example.com';
        $name = $_SESSION['name'] ?? 'User';
        $password = $_SESSION['password'] ?? 'default_password';

        // Get WHO Growth Standards assessment
        $user_data = [
            'name' => $name,
            'email' => $user_email,
            'password' => $password,
            'municipality' => $screening_data['municipality'],
            'barangay' => $screening_data['barangay'],
            'sex' => $screening_data['sex'],
            'birthday' => $screening_data['birthday'],
            'is_pregnant' => $screening_data['is_pregnant'],
            'weight' => $screening_data['weight'],
            'height' => $screening_data['height'],
            'muac' => $screening_data['muac'],
            'screening_date' => $screening_data['screening_date'],
            'fcm_token' => $screening_data['fcm_token']
        ];
        
        // Get WHO Growth Standards assessment
        $assessment = getNutritionalAssessment($user_data);
        
        // Insert into community_users table using DatabaseHelper
        $insertData = $user_data;
        
        // Add WHO Growth Standards results if available
        if ($assessment['success'] && isset($assessment['growth_standards'])) {
            $growthStandards = $assessment['growth_standards'];
            $insertData['bmi-for-age'] = $growthStandards['bmi_for_age']['z_score'] ?? null;
            $insertData['weight-for-height'] = $growthStandards['weight_for_height']['z_score'] ?? null;
            $insertData['weight-for-age'] = $growthStandards['weight_for_age']['z_score'] ?? null;
            $insertData['weight-for-length'] = $growthStandards['weight_for_length']['z_score'] ?? null;
            $insertData['height-for-age'] = $growthStandards['height_for_age']['z_score'] ?? null;
            $insertData['bmi'] = $assessment['bmi'] ?? null;
            $insertData['bmi_category'] = $growthStandards['bmi_for_age']['classification'] ?? null;
            $insertData['nutritional_risk'] = $assessment['nutritional_risk'] ?? 'Low';
            $insertData['muac_cm'] = $screening_data['muac'];
            $insertData['muac_category'] = $assessment['muac_category'] ?? null;
            $insertData['follow_up_required'] = ($assessment['nutritional_risk'] !== 'Low') ? 1 : 0;
            $insertData['notes'] = 'Processed by WHO Growth Standards - ' . implode(', ', $assessment['recommendations'] ?? []);
        }
        
        // Insert using DatabaseHelper
        $result = $db->insert('community_users', $insertData);
        
        if ($result['success']) {
            $success_message = "Screening assessment saved successfully with WHO Growth Standards analysis!";
        } else {
            $error_message = "Error saving screening assessment: " . ($result['error'] ?? 'Unknown error');
        }
        
    } catch (Exception $e) {
        $error_message = "Error saving screening assessment: " . $e->getMessage();
    }
}

// Get existing screening assessments using DatabaseHelper
$screening_assessments = [];
if ($db->isAvailable()) {
    try {
        $user_email = $_SESSION['user_email'] ?? 'user@example.com';
        $result = $db->select(
            'community_users', 
            '*', 
            'email = ?', 
            [$user_email], 
            'screening_date DESC'
        );
        $screening_assessments = $result['success'] ? $result['data'] : [];
    } catch (Exception $e) {
        error_log("Error fetching screening assessments: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MHO Nutritional Assessment Module - NutriSaur</title>
</head>
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
    --flagged-bg: #4A2C2C;
    --flagged-border: #CF8686;
}

/* Apply dark theme by default to prevent flash */
body {
    background-color: var(--color-bg);
    color: var(--color-text);
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

/* ===== NAVBAR STYLES ===== */
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
    transition: transform 0.3s ease-in-out;
    transform: translateX(-280px); /* Show only 40px */
}

/* Base body styles */
body {
    padding-left: 40px; /* Space for minimized navbar */
    transition: padding-left 0.4s ease;
}

/* Dark theme navbar styles */
.dark-theme .navbar {
    background-color: var(--color-card);
    box-shadow: 3px 0 15px rgba(0, 0, 0, 0.2);
}

/* Light theme navbar styles */
.light-theme .navbar {
    background-color: var(--color-card);
    box-shadow: 3px 0 15px var(--color-shadow);
}

.navbar-header {
    padding: 35px 25px;
    display: flex;
    align-items: center;
    border-bottom: 2px solid rgba(164, 188, 46, 0.15);
    background: linear-gradient(135deg, rgba(161, 180, 84, 0.05) 0%, transparent 100%);
    position: relative;
    overflow: hidden;
}

/* Dark theme navbar header styles */
.dark-theme .navbar-header {
    background: linear-gradient(135deg, rgba(161, 180, 84, 0.05) 0%, transparent 100%);
    border-bottom-color: rgba(164, 188, 46, 0.15);
}

/* Light theme navbar header styles */
.light-theme .navbar-header {
    background: linear-gradient(135deg, rgba(102, 187, 106, 0.05) 0%, transparent 100%);
    border-bottom-color: rgba(102, 187, 106, 0.15);
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

/* Dark theme navbar header after styles */
.dark-theme .navbar-header::after {
    background: linear-gradient(90deg, transparent, rgba(161, 180, 84, 0.3), transparent);
}

/* Light theme navbar header after styles */
.light-theme .navbar-header::after {
    background: linear-gradient(90deg, transparent, rgba(102, 187, 106, 0.3), transparent);
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
    background: linear-gradient(135deg, rgba(102, 187, 106, 0.1), rgba(102, 187, 106, 0.05));
    border-color: var(--color-border);
    box-shadow: 0 2px 8px var(--color-shadow);
}

.light-theme .navbar-logo:hover .navbar-logo-icon {
    background: linear-gradient(135deg, rgba(102, 187, 106, 0.15), rgba(102, 187, 106, 0.08));
    border-color: var(--color-border);
    box-shadow: 0 4px 15px var(--color-shadow);
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
    border-bottom: 1px solid rgba(102, 187, 106, 0.08);
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
    background: linear-gradient(90deg, transparent, rgba(102, 187, 106, 0.1), transparent);
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
    background: linear-gradient(90deg, var(--color-hover) 0%, rgba(102, 187, 106, 0.04) 100%);
    color: #1B3A1B;
    box-shadow: 0 4px 15px var(--color-shadow);
}

.light-theme .navbar a.active {
    background: linear-gradient(90deg, var(--color-active) 0%, rgba(102, 187, 106, 0.08) 100%);
    border-left-color: var(--color-highlight);
    box-shadow: 0 6px 20px var(--color-shadow);
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

/* Dark theme navbar footer styles */
.dark-theme .navbar-footer {
    border-top-color: rgba(164, 188, 46, 0.15);
    background: linear-gradient(135deg, transparent 0%, rgba(161, 180, 84, 0.03) 100%);
}

/* Light theme navbar footer styles */
.light-theme .navbar-footer {
    border-top-color: rgba(102, 187, 106, 0.15);
    background: linear-gradient(135deg, transparent 0%, rgba(102, 187, 106, 0.03) 100%);
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

/* Dark theme navbar footer before styles */
.dark-theme .navbar-footer::before {
    background: linear-gradient(90deg, transparent, rgba(161, 180, 84, 0.2), transparent);
}

/* Light theme navbar footer before styles */
.light-theme .navbar-footer::before {
    background: linear-gradient(90deg, transparent, rgba(102, 187, 106, 0.2), transparent);
}

.navbar-footer div:first-child {
    font-weight: 600;
    color: var(--color-highlight);
    margin-bottom: 8px;
}

/* Dark theme navbar footer text styles */
.dark-theme .navbar-footer div:first-child {
    color: var(--color-highlight);
}

/* Hover state - navbar expanded (shows full width) */
.navbar:hover {
    transform: translateX(0); /* Show full navbar */
    box-shadow: 5px 0 25px rgba(0, 0, 0, 0.2);
    backdrop-filter: blur(15px);
}

/* Hide text content when navbar is minimized */
.navbar-logo-text,
.navbar span:not(.navbar-icon),
.navbar-footer {
    opacity: 0;
    transition: opacity 0.3s ease, transform 0.3s ease;
    transform: translateX(-10px);
    white-space: nowrap;
}

/* Show text content when navbar is hovered */
.navbar:hover .navbar-logo-text,
.navbar:hover span:not(.navbar-icon),
.navbar:hover .navbar-footer {
    opacity: 1;
    transform: translateX(0);
}

/* Minimized navbar - center the logo icon */
.navbar {
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    align-items: center;
    padding-top: 20px;
}

/* Navbar icon hover effect when minimized */
.navbar-icon {
    transition: transform 0.2s ease, color 0.2s ease;
}

.navbar:hover .navbar-icon {
    transform: scale(1.05);
    color: var(--color-primary);
}

/* Expanded navbar state - show everything */
.navbar:hover {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: stretch;
    padding-top: 0;
}

/* ===== MOBILE TOP NAVIGATION STYLES ===== */

/* Mobile Top Navigation Bar */
.mobile-top-nav {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    width: 100vw;
    max-width: 100vw;
    z-index: 10000;
    background: var(--color-card);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid var(--color-border);
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
    overflow-x: hidden;
}

.mobile-nav-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    height: 60px;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    overflow-x: hidden;
}

.mobile-nav-logo {
    display: flex;
    align-items: center;
    gap: 10px;
}

.mobile-logo-img {
    width: 32px;
    height: 32px;
    border-radius: 6px;
}

.mobile-logo-text {
    font-size: 18px;
    font-weight: 600;
    color: var(--color-text);
}

.mobile-nav-icons {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}

.mobile-nav-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: var(--color-bg);
    border: 1px solid var(--color-border);
    color: var(--color-text);
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 16px;
    cursor: pointer;
}

.mobile-nav-icon:hover {
    background: var(--color-hover);
    border-color: var(--color-highlight);
    color: var(--color-highlight);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.mobile-nav-icon.active {
    background: var(--color-highlight);
    border-color: var(--color-highlight);
    color: white;
}

/* Mobile Navigation Overlay */
.mobile-nav-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    backdrop-filter: blur(5px);
}

.mobile-nav-sidebar {
    position: fixed;
    top: 0;
    right: -100%;
    width: 280px;
    height: 100vh;
    background: var(--color-card);
    box-shadow: -5px 0 25px rgba(0, 0, 0, 0.2);
    transition: right 0.3s ease;
    z-index: 10001;
    display: flex;
    flex-direction: column;
}

.mobile-nav-sidebar.open {
    right: 0;
}

.mobile-nav-header {
    padding: 20px;
    border-bottom: 1px solid var(--color-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.mobile-nav-close {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    background: var(--color-bg);
    border: 1px solid var(--color-border);
    color: var(--color-text);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 18px;
    transition: all 0.3s ease;
}

.mobile-nav-close:hover {
    background: var(--color-hover);
    border-color: var(--color-highlight);
    color: var(--color-highlight);
}

.mobile-nav-menu {
    flex: 1;
    padding: 20px 0;
}

.mobile-nav-menu ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.mobile-nav-menu li {
    margin-bottom: 2px;
}

.mobile-nav-menu a {
    display: flex;
    align-items: center;
    padding: 15px 20px;
    color: var(--color-text);
    text-decoration: none;
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}

.mobile-nav-menu a:hover {
    background: var(--color-hover);
    border-left-color: var(--color-highlight);
    color: var(--color-highlight);
}

.mobile-nav-menu a.active {
    background: var(--color-active);
    border-left-color: var(--color-highlight);
    color: var(--color-highlight);
    font-weight: 600;
}

.mobile-nav-menu .navbar-icon {
    margin-right: 12px;
    font-size: 18px;
}

/* ===== RESPONSIVE DESIGN ===== */

/* Desktop styles - hover navigation */
@media (min-width: 769px) {
    .mobile-top-nav,
    .mobile-nav-overlay,
    .mobile-nav-sidebar,
    .mobile-nav-close,
    .nav-overlay {
        display: none !important;
    }
    
    .navbar:hover {
        width: 320px !important; /* Hover: expanded */
    }
    
    /* Body padding will be handled by JavaScript */
}

/* Mobile styles */
@media (max-width: 768px) {
    /* Hide desktop navbar on mobile */
    .navbar {
        display: none !important;
    }
    
    /* Show mobile top navigation */
    .mobile-top-nav {
        display: block !important;
    }
    
    /* Adjust body for mobile */
    body {
        padding-left: 0 !important;
        padding-top: 60px !important;
        width: 100vw !important;
        max-width: 100vw !important;
        overflow-x: hidden !important;
        min-height: 100vh !important;
    }
    
    /* Adjust main content for mobile */
    .dashboard {
        margin-left: 0 !important;
        padding: 15px !important;
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
    }
}

.light-theme .navbar-footer div:first-child {
    color: #1B3A1B;
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

/* Header styles */
header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

/* Dashboard Header Styles */
.dashboard-header {
    background-color: var(--color-card);
    border-radius: 12px;
    padding: 18px 22px;
    margin-bottom: 25px;
    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
    border: 1px solid rgba(161, 180, 84, 0.2);
    transition: all 0.3s ease;
}

.dashboard-header:hover {
    box-shadow: 0 5px 18px rgba(0, 0, 0, 0.12);
    transform: translateY(-1px);
}

.dashboard-title h1 {
    margin: 0;
    color: var(--color-text);
    font-size: 36px;
    font-weight: 700;
    line-height: 1.2;
}

.light-theme .dashboard-title h1 {
    color: #1B3A1B;
    font-size: 36px;
    font-weight: 700;
}

.dashboard-header .user-info {
    display: flex;
    align-items: center;
}

/* User info styles */
.user-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

/* New Theme toggle button - Orange for moon, Black for sun */
.new-theme-toggle-btn {
    background: #FF9800; /* Default orange for moon icon */
    border: none;
    color: #333; /* Dark color for moon icon */
    padding: 10px 15px;
    border-radius: 25px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 18px;
    min-width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 15px rgba(255, 152, 0, 0.3);
    font-weight: bold;
}

.new-theme-toggle-btn:hover {
    background: #F57C00; /* Darker orange on hover */
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 152, 0, 0.4);
}

.new-theme-toggle-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 10px rgba(255, 152, 0, 0.3);
}

.new-theme-toggle-btn .new-theme-icon {
    font-size: 20px;
    transition: transform 0.3s ease;
}

/* Dark theme: Orange background for moon icon */
.dark-theme .new-theme-toggle-btn {
    background: #FF9800;
    color: #333; /* Dark color for moon icon */
    box-shadow: 0 4px 15px rgba(255, 152, 0, 0.3);
}

.dark-theme .new-theme-toggle-btn:hover {
    background: #F57C00;
    box-shadow: 0 6px 20px rgba(255, 152, 0, 0.4);
}

/* Dark theme: Orange moon icon */
.dark-theme .new-theme-toggle-btn .new-theme-icon {
    color: #333;
}

/* Light theme: Black background for sun icon */
.light-theme .new-theme-toggle-btn {
    background: #000000;
    color: #FFFFFF; /* White color for sun icon */
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
}

.light-theme .new-theme-toggle-btn:hover {
    background: #333333;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.5);
}

/* Light theme: White sun icon */
.light-theme .new-theme-toggle-btn .new-theme-icon {
    color: #FFFFFF;
}

/* Card Deck Fan Component Styles */
.card-deck-container {
    background: var(--color-card);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--color-border);
    width: 100%;
}

.deck-header {
    margin-bottom: 20px;
}

.search-filter-container {
    display: flex;
    flex-direction: column;
    gap: 15px;
    align-items: center;
}

.search-box {
    display: flex;
    align-items: center;
    background: var(--color-card);
    border: 1px solid var(--color-border);
    border-radius: 25px;
    padding: 8px 15px;
    width: 100%;
    max-width: 400px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.search-input {
    flex: 1;
    border: none;
    background: transparent;
    color: var(--color-text);
    font-size: 14px;
    padding: 8px 0;
    outline: none;
}

.search-input::placeholder {
    color: var(--color-text);
    opacity: 0.6;
}

.search-btn {
    background: none;
    border: none;
    color: var(--color-highlight);
    font-size: 16px;
    cursor: pointer;
    padding: 5px;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.search-btn:hover {
    background: rgba(161, 180, 84, 0.1);
}

.filter-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: center;
}

.filter-btn {
    background: var(--color-card);
    border: 1px solid var(--color-border);
    color: var(--color-text);
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.filter-btn:hover {
    background: rgba(161, 180, 84, 0.1);
    border-color: var(--color-highlight);
}

.filter-btn.active {
    background: var(--color-highlight);
    color: white;
    border-color: var(--color-highlight);
}

.deck-card.hidden {
    display: none !important;
}

.deck-card {
    transition: all 0.3s ease;
    opacity: 1;
}

.deck-card.hidden {
    opacity: 0;
    transform: scale(0.95);
    pointer-events: none;
}

.no-results-message {
    grid-column: 1 / -1;
    text-align: center;
    padding: 40px;
    color: var(--color-text);
    font-style: italic;
    background: var(--color-card);
    border-radius: 12px;
    border: 1px dashed var(--color-border);
}

.deck-wrapper {
    position: relative;
    overflow: hidden;
}

.deck-container {
    position: relative;
    height: 400px;
    border-radius: 24px;
    border: 1px solid var(--color-border);
    background: linear-gradient(135deg, var(--color-card) 0%, rgba(161, 180, 84, 0.05) 100%);
    backdrop-filter: blur(10px);
    overflow: hidden;
    width: 100%;
    margin-bottom: 12px;
}

.deck-cards {
    display: flex;
    gap: 15px;
    padding: 24px;
    height: 100%;
    overflow-x: auto;
    overflow-y: hidden;
    scrollbar-width: none;
    -ms-overflow-style: none;
    scroll-behavior: smooth;
    max-width: 100%;
    align-items: center;
}

.deck-header-section {
    padding: 12px 24px 8px 24px;
    border-bottom: 1px solid var(--color-border);
}

.section-title {
    color: var(--color-highlight);
    font-size: 16px;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.deck-cards::-webkit-scrollbar {
    display: none;
}

/* Responsive design for card deck */
@media (max-width: 1400px) {
    .screening-container {
        max-width: 1200px;
        padding: 25px;
    }
    
    .deck-cards {
        gap: 12px;
        padding: 20px;
    }
    
    .deck-card {
        width: 200px;
        height: 280px;
        min-width: 200px;
    }
}

@media (max-width: 1200px) {
    .screening-container {
        max-width: 1000px;
        padding: 20px;
    }
    
    .deck-cards {
        gap: 10px;
        padding: 18px;
    }
    
    .deck-card {
        height: 260px;
    }
    
    .deck-container {
        height: 350px;
    }
}

@media (max-width: 768px) {
    .screening-container {
        max-width: 100%;
        padding: 15px;
    }
    
    .deck-cards {
        gap: 8px;
        padding: 15px;
    }
    
    .deck-card {
        height: 240px;
    }
    
    .deck-container {
        height: 300px;
    }
}

@media (max-width: 480px) {
    .screening-container {
        padding: 10px;
    }
    
    .deck-cards {
        gap: 6px;
        padding: 12px;
    }
    
    .deck-card {
        height: 200px;
    }
    
    .deck-container {
        height: 250px;
    }
}

.deck-card {
    position: relative;
    width: 220px;
    height: 320px;
    min-width: 220px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    transform: translateX(0px) translateY(0px) scale(1);
    flex-shrink: 0;
}

.deck-card:hover {
    transform: translateY(-10px);
}

.card-main {
    width: 100%;
    height: 100%;
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    background: linear-gradient(135deg, var(--color-card) 0%, rgba(161, 180, 84, 0.1) 100%);
    backdrop-filter: blur(10px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    padding: 16px;
    box-sizing: border-box;
    transition: all 0.3s ease;
}

.card-main:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.2);
    border-color: var(--color-highlight);
}
    padding: 20px;
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
    position: relative;
    z-index: 10;
    overflow: hidden;
}



.card-header {
    text-align: center;
    margin-bottom: 12px;
}

.card-header h4 {
    color: var(--color-highlight);
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 6px;
    line-height: 1.2;
}

.card-location {
    color: var(--color-text);
    font-size: 12px;
    opacity: 0.7;
    line-height: 1.2;
}

.card-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
    overflow: hidden;
}

.card-stat {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
    border-bottom: 1px solid var(--color-border);
}

.card-stat:last-child {
    border-bottom: none;
}

.stat-label {
    color: var(--color-text);
    font-size: 12px;
    font-weight: 500;
    opacity: 0.8;
}

.stat-value {
    color: var(--color-highlight);
    font-size: 13px;
    font-weight: 600;
}

.bmi-normal {
    color: #4CAF50 !important;
    background: rgba(76, 175, 80, 0.1);
    padding: 1px 4px;
    border-radius: 6px;
    font-weight: 600;
}

.bmi-overweight {
    color: #FF9800 !important;
    background: rgba(255, 152, 0, 0.1);
    padding: 1px 4px;
    border-radius: 6px;
    font-weight: 600;
}

.bmi-underweight {
    color: #FF1744 !important;
    background: rgba(255, 23, 68, 0.15);
    padding: 1px 4px;
    border-radius: 6px;
    font-weight: 600;
}

.bmi-obese {
    color: #D32F2F !important;
    background: rgba(211, 47, 47, 0.1);
    padding: 1px 4px;
    border-radius: 6px;
    font-weight: 600;
}

.diet-balanced {
    color: #4CAF50 !important;
}

.diet-at-risk {
    color: #FF9800 !important;
}

.lifestyle-active {
    color: #4CAF50 !important;
}

.lifestyle-sedentary {
    color: #FF9800 !important;
}

.risk-low-risk {
    color: #4CAF50 !important;
    background: rgba(76, 175, 80, 0.1);
    padding: 2px 6px;
    border-radius: 8px;
    font-weight: 700;
}

.risk-medium-risk {
    color: #FF9800 !important;
    background: rgba(255, 152, 0, 0.1);
    padding: 2px 6px;
    border-radius: 8px;
    font-weight: 700;
}

.risk-high-risk {
    color: #FF1744 !important;
    background: rgba(255, 23, 68, 0.15);
    padding: 2px 6px;
    border-radius: 8px;
    font-weight: 700;
}

.pregnancy-yes {
    color: #E91E63 !important;
    background: rgba(233, 30, 99, 0.1);
    padding: 2px 6px;
    border-radius: 8px;
    font-weight: 700;
}

.pregnancy-no {
    color: #4CAF50 !important;
    background: rgba(76, 175, 80, 0.1);
    padding: 2px 6px;
    border-radius: 8px;
    font-weight: 700;
}



/* Light theme adjustments */
.light-theme .deck-container {
    background: linear-gradient(135deg, var(--color-card) 0%, rgba(102, 187, 106, 0.05) 100%);
}

.light-theme .card-main {
    background: linear-gradient(135deg, var(--color-card) 0%, rgba(102, 187, 106, 0.1) 100%);
}

.light-theme .fan-card {
    background: linear-gradient(135deg, var(--color-card) 0%, rgba(102, 187, 106, 0.15) 100%);
    box-shadow: 0 12px 35px rgba(102, 187, 106, 0.2);
}

.light-theme .fan-label {
    background: linear-gradient(135deg, var(--color-highlight) 0%, var(--color-accent1) 100%);
    box-shadow: 0 4px 12px rgba(102, 187, 106, 0.3);
}

.light-theme .fan-label:hover {
    box-shadow: 0 6px 16px rgba(102, 187, 106, 0.4);
}
        .screening-container {
            width: 100%;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .screening-form {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .form-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: var(--bg-color);
        }

        .section-title {
            font-size: 1.5em;
            font-weight: bold;
            margin-bottom: 20px;
            color: var(--text-color);
            border-bottom: 2px solid var(--accent-color);
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-color);
        }

        .form-input, .form-select {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--input-bg);
            color: var(--text-color);
            font-size: 16px;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(var(--accent-color-rgb), 0.1);
        }

        .radio-group {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .radio-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .radio-item input[type="radio"] {
            width: 18px;
            height: 18px;
        }

        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }

        .age-inputs {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .age-inputs .form-group {
            flex: 1;
        }

        .bmi-display {
            background: var(--accent-color);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            text-align: center;
            font-weight: bold;
        }

        .submit-btn {
            background: var(--accent-color);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            background: var(--accent-color-dark);
            transform: translateY(-2px);
        }

        .screening-history {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .history-table th,
        .history-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .history-table th {
            background: var(--accent-color);
            color: white;
            font-weight: bold;
        }

        .history-table tr:hover {
            background: var(--hover-bg);
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .conditional-field {
            display: none;
        }

        .conditional-field.show {
            display: block;
        }

        .error-message {
            color: #dc3545;
            font-size: 14px;
            margin-top: 5px;
        }

        .success-message {
            color: #28a745;
            font-size: 14px;
            margin-top: 5px;
        }

        /* Assessment Results Styles */
        .assessment-results {
            margin-bottom: 30px;
        }

        .results-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .results-header h2 {
            font-size: 2.5em;
            margin-bottom: 10px;
            color: var(--text-color);
        }

        .results-header p {
            font-size: 1.1em;
            color: var(--text-color-secondary);
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s ease;
        }

        .summary-card:hover {
            transform: translateY(-5px);
        }

        .card-icon {
            font-size: 3em;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--accent-color);
            border-radius: 50%;
            color: white;
        }

        .card-content h3 {
            font-size: 1.1em;
            margin-bottom: 8px;
            color: var(--text-color);
        }

        .card-value {
            font-size: 2.5em;
            font-weight: bold;
            color: var(--accent-color);
        }

        .assessment-table-container {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .table-header h3 {
            font-size: 1.5em;
            color: var(--text-color);
            margin: 0;
        }

        .table-controls {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .search-input {
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--input-bg);
            color: var(--text-color);
            min-width: 200px;
        }

        .filter-select {
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--input-bg);
            color: var(--text-color);
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
        }

        .no-data-icon {
            font-size: 4em;
            margin-bottom: 20px;
        }

        .no-data h3 {
            font-size: 1.8em;
            margin-bottom: 10px;
            color: var(--text-color);
        }

        .no-data p {
            font-size: 1.1em;
            color: var(--text-color-secondary);
            margin-bottom: 30px;
        }

        .mobile-app-info {
            background: var(--bg-color);
            border-radius: 10px;
            padding: 25px;
            max-width: 600px;
            margin: 0 auto;
        }

        .mobile-app-info h4 {
            font-size: 1.3em;
            margin-bottom: 15px;
            color: var(--text-color);
        }

        .mobile-app-info ul {
            list-style: none;
            padding: 0;
        }

        .mobile-app-info li {
            padding: 8px 0;
            color: var(--text-color);
            position: relative;
            padding-left: 25px;
        }

        .mobile-app-info li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: var(--accent-color);
            font-weight: bold;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .assessment-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .assessment-table th,
        .assessment-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .assessment-table th {
            background: var(--accent-color);
            color: white;
            font-weight: bold;
            position: sticky;
            top: 0;
        }

        .assessment-table tr:hover {
            background: var(--hover-bg);
        }

        .bmi-value {
            font-weight: bold;
            color: var(--text-color);
        }

        .bmi-category {
            display: block;
            font-size: 0.9em;
            color: var(--text-color-secondary);
            margin-top: 5px;
        }

        .risk-score {
            font-weight: bold;
            font-size: 1.1em;
        }


        /* Action buttons styling - matching settings.php design */
        .action-buttons {
            display: flex;
            gap: 4px;
            justify-content: center;
            align-items: center;
            padding: 0;
            flex-wrap: nowrap;
            width: 100%;
            box-sizing: border-box;
            text-align: center;
            height: auto;
            min-height: auto;
            position: relative;
            z-index: 10;
            overflow: visible;
        }

        .action-buttons .btn-view {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            min-width: 55px;
            height: 32px;
            display: inline-block;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            white-space: nowrap;
            background-color: var(--color-highlight) !important;
            color: white !important;
            vertical-align: middle;
            margin: 0;
            line-height: 1;
            position: relative;
            z-index: 20;
            overflow: visible;
            pointer-events: auto;
        }

        .action-buttons .btn-view:hover {
            background-color: #8CA86E !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 8px rgba(161, 180, 84, 0.3) !important;
        }

        .action-buttons .btn-view:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Light theme action buttons */
        .light-theme .action-buttons .btn-view {
            background-color: rgba(102, 187, 106, 0.15);
            color: var(--color-highlight);
            border: 2px solid rgba(102, 187, 106, 0.4);
        }

        .light-theme .action-buttons .btn-view:hover {
            background-color: rgba(102, 187, 106, 0.25);
        }

        /* Note Button Styling */
        .action-buttons .btn-note {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            min-width: 55px;
            height: 32px;
            display: inline-block;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            white-space: nowrap;
            background-color: #FFC107 !important;
            color: white !important;
            vertical-align: middle;
            margin: 0 0 0 6px;
            line-height: 1;
            position: relative;
            z-index: 20;
            overflow: visible;
            pointer-events: auto;
        }

        .action-buttons .btn-note:hover {
            background-color: #FFB300 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 8px rgba(255, 193, 7, 0.3) !important;
        }

        .action-buttons .btn-note:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Light theme note buttons */
        .light-theme .action-buttons .btn-note {
            background-color: rgba(255, 193, 7, 0.15);
            color: white;
            border: 2px solid rgba(255, 193, 7, 0.4);
        }

        .light-theme .action-buttons .btn-note:hover {
            background-color: rgba(255, 193, 7, 0.25);
        }


        /* Flagged User Row Highlighting */
        .flagged-user-row {
            background-color: rgba(255, 23, 68, 0.25) !important;
            border-left: 6px solid #FF1744 !important;
            box-shadow: 0 0 10px rgba(255, 23, 68, 0.3) !important;
        }

        .flagged-user-row:hover {
            background-color: rgba(255, 23, 68, 0.35) !important;
            box-shadow: 0 0 15px rgba(255, 23, 68, 0.4) !important;
        }

        .light-theme .flagged-user-row {
            background-color: rgba(255, 23, 68, 0.2) !important;
            border-left: 6px solid #FF1744 !important;
            box-shadow: 0 0 8px rgba(255, 23, 68, 0.25) !important;
        }

        .light-theme .flagged-user-row:hover {
            background-color: rgba(255, 23, 68, 0.3) !important;
            box-shadow: 0 0 12px rgba(255, 23, 68, 0.35) !important;
        }

        /* Assessment Details Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }
        
        .modal[style*="display: block"] {
            display: flex !important;
        }

        .modal-content {
            background: var(--card-bg);
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 15px;
        }

        .modal-header h3 {
            margin: 0;
            color: var(--text-color);
        }

        .close {
            color: var(--text-color);
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: var(--accent-color);
        }

        .assessment-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .detail-section {
            background: var(--bg-color);
            padding: 20px;
            border-radius: 10px;
        }

        .detail-section h4 {
            margin-bottom: 15px;
            color: var(--text-color);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 8px;
        }

        /* Modern User Profile Modal Styles */
        .user-profile-modal {
            backdrop-filter: blur(10px);
            animation: modalFadeIn 0.3s ease-out;
        }

        .user-profile-modal.modal-show {
            opacity: 1;
        }

        .user-profile-modal.modal-show .profile-modal-content {
            transform: scale(1);
        }

        .user-profile-modal.modal-hide {
            opacity: 0;
        }

        .user-profile-modal.modal-hide .profile-modal-content {
            transform: scale(0.95);
        }

        .profile-modal-content {
            background: var(--card-bg);
            margin: 0;
            padding: 0;
            border-radius: 20px;
            width: 95%;
            max-width: 1000px;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            transform: scale(0.95);
            transition: all 0.3s ease-out;
        }

        .profile-header {
            background: linear-gradient(135deg, var(--color-highlight) 0%, rgba(161, 180, 84, 0.8) 100%);
            padding: 20px 30px;
            display: flex;
            align-items: center;
            gap: 20px;
            position: relative;
            color: white;
        }

        .profile-avatar {
            flex-shrink: 0;
        }

        .avatar-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
        }

        .avatar-initials {
            font-size: 32px;
            font-weight: bold;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .profile-title h2 {
            margin: 0 0 5px 0;
            font-size: 28px;
            font-weight: 600;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .profile-subtitle {
            margin: 0 0 5px 0;
            opacity: 0.9;
            font-size: 16px;
        }

        .profile-screening-date {
            margin: 0 0 10px 0;
            opacity: 0.8;
            font-size: 13px;
            font-style: italic;
        }

        .profile-badges {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-blue {
            background: rgba(59, 130, 246, 0.9);
            color: white;
        }

        .badge-pink {
            background: rgba(236, 72, 153, 0.9);
            color: white;
        }

        .badge-orange {
            background: rgba(249, 115, 22, 0.9);
            color: white;
        }

        .profile-close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #ff4444;
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: white;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            z-index: 1000;
        }

        .profile-close-btn:hover {
            background: #ff6666;
            transform: scale(1.1);
        }

        /* Profile Header Buttons */
        .profile-header-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-left: auto;
        }

        .profile-action-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .profile-food-btn {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            padding: 12px 20px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(76, 175, 80, 0.2);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .profile-food-btn:hover {
            background: linear-gradient(135deg, #45a049 0%, #4CAF50 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(76, 175, 80, 0.4);
        }
        
        .profile-note-btn {
            background: rgba(255, 193, 7, 0.9);
            color: #1B1B1B;
        }

        .profile-note-btn:hover {
            background: rgba(255, 193, 7, 1);
            transform: translateY(-1px);
        }

        .profile-add-note-btn {
            background: rgba(255, 193, 7, 0.9);
            color: #1B1B1B;
        }

        .profile-add-note-btn:hover {
            background: rgba(255, 193, 7, 1);
            transform: translateY(-1px);
        }

        .profile-flag-btn {
            background: rgba(255, 23, 68, 0.9);
            color: white;
        }

        .profile-flag-btn.flagged {
            background: rgba(76, 175, 80, 0.9);
        }

        .profile-flag-btn:hover {
            background: rgba(255, 23, 68, 1);
            transform: translateY(-1px);
        }

        .profile-flag-btn.flagged:hover {
            background: rgba(76, 175, 80, 1);
        }

        /* Food History Modal - Table Design */
        .food-history-modal {
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
        }

        .food-history-content {
            padding: 0;
            max-height: 80vh;
            overflow-y: auto;
        }

        .food-history-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .food-history-table thead {
            background: linear-gradient(135deg, var(--color-highlight) 0%, rgba(161, 180, 84, 0.8) 100%);
            color: white;
        }

        .food-history-table th {
            padding: 16px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
        }

        .food-history-table td {
            padding: 12px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: top;
        }

        .food-history-table tbody tr {
            transition: all 0.3s ease;
        }

        .food-history-table tbody tr:hover {
            background: rgba(161, 180, 84, 0.05);
        }

        .food-history-table tbody tr.flagged {
            background: linear-gradient(135deg, rgba(255, 152, 0, 0.1) 0%, rgba(255, 152, 0, 0.05) 100%);
            border-left: 4px solid #ff9800;
        }

        .food-name-cell {
            font-weight: 600;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .food-flag-indicator {
            color: #ff9800;
            font-size: 16px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .food-serving-cell {
            color: #6c757d;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .food-edit-btn {
            padding: 4px 8px;
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .food-edit-btn:hover {
            background: linear-gradient(135deg, #45a049 0%, #4CAF50 100%);
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(76, 175, 80, 0.3);
        }

        .food-date-cell {
            font-weight: 600;
            color: var(--text-color);
            white-space: nowrap;
        }

        .food-meal-cell {
            background: rgba(161, 180, 84, 0.1);
            color: var(--color-highlight);
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-align: center;
            display: inline-block;
        }

        .food-nutrition-cell {
            text-align: right;
            font-size: 12px;
        }

        .food-calories {
            font-weight: 600;
            color: var(--color-highlight);
            font-size: 13px;
        }

        .food-macros {
            color: #6c757d;
            font-size: 10px;
            margin-top: 2px;
        }

        .food-actions-cell {
            display: flex;
            gap: 6px;
            justify-content: flex-end;
        }

        .food-action-btn {
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .food-flag-action {
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
            color: #1B1B1B;
        }

        .food-flag-action.unflag {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
        }

        .food-flag-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }

        .food-comment-action {
            background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
            color: white;
        }

        .food-comment-action:hover {
            background: linear-gradient(135deg, #1976D2 0%, #2196F3 100%);
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(33, 150, 243, 0.3);
        }

        .food-comment-cell {
            background: linear-gradient(135deg, rgba(33, 150, 243, 0.1) 0%, rgba(33, 150, 243, 0.05) 100%);
            padding: 8px;
            border-radius: 6px;
            font-size: 11px;
            border-left: 3px solid #2196F3;
            margin-top: 4px;
        }

        .day-summary {
            background: linear-gradient(135deg, var(--color-highlight) 0%, rgba(161, 180, 84, 0.8) 100%);
            color: white;
            padding: 12px 16px;
            margin-bottom: 16px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .day-summary.flagged {
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
        }

        .day-summary-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .day-summary-date {
            font-weight: 600;
            font-size: 16px;
        }

        .day-summary-totals {
            font-size: 13px;
            opacity: 0.9;
        }

        .day-summary-actions {
            display: flex;
            gap: 8px;
        }

        .day-action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .day-flag-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .day-flag-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .day-comment-btn {
            background: rgba(33, 150, 243, 0.8);
            color: white;
        }

        .day-comment-btn:hover {
            background: rgba(33, 150, 243, 1);
        }

        /* Responsive Design for Food History Table */
        @media (max-width: 768px) {
            .food-history-content {
                padding: 0;
            }

            .food-history-table {
                font-size: 12px;
            }

            .food-history-table th,
            .food-history-table td {
                padding: 8px 6px;
            }

            .food-actions-cell {
                flex-direction: column;
                gap: 4px;
            }

            .food-action-btn {
                padding: 4px 8px;
                font-size: 9px;
            }

            .day-summary {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
            }

            .day-summary-actions {
                width: 100%;
                justify-content: flex-start;
            }
        }

        .profile-content {
            padding: 30px;
            overflow-y: auto;
            max-height: calc(90vh - 200px);
        }

        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .profile-card {
            background: var(--bg-color);
            border-radius: 15px;
            border: 1px solid rgba(161, 180, 84, 0.1);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .profile-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(161, 180, 84, 0.15);
        }

        .profile-card.full-width {
            grid-column: 1 / -1;
        }

        .card-header {
            background: linear-gradient(135deg, rgba(161, 180, 84, 0.1) 0%, rgba(161, 180, 84, 0.05) 100%);
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(161, 180, 84, 0.1);
        }

        .card-icon {
            font-size: 24px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--color-highlight);
            border-radius: 10px;
            color: white;
        }

        .card-header h3 {
            margin: 0;
            color: var(--color-text);
            font-size: 18px;
            font-weight: 600;
        }

        .card-content {
            padding: 20px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(161, 180, 84, 0.1);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 500;
            color: var(--color-text);
            opacity: 0.8;
            font-size: 14px;
        }

        .info-value {
            font-weight: 600;
            color: var(--color-text);
            text-align: right;
            font-size: 14px;
        }

        .text-orange {
            color: #f97316 !important;
        }

        .measurement-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .measurement-item {
            text-align: center;
            padding: 15px;
            background: rgba(161, 180, 84, 0.05);
            border-radius: 10px;
            border: 1px solid rgba(161, 180, 84, 0.1);
        }

        .measurement-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--color-highlight);
            margin-bottom: 5px;
        }

        .measurement-label {
            font-size: 12px;
            color: var(--color-text);
            opacity: 0.7;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                backdrop-filter: blur(0px);
            }
            to {
                opacity: 1;
                backdrop-filter: blur(10px);
            }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive Design for Profile Modal */
        @media (max-width: 768px) {
            .profile-modal-content {
                width: 98%;
                max-height: 95vh;
                transform: scale(0.95);
            }
            
            .user-profile-modal.modal-show .profile-modal-content {
                transform: scale(1);
            }
            
            .user-profile-modal.modal-hide .profile-modal-content {
                transform: scale(0.95);
            }

            .profile-header {
                padding: 20px;
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .profile-title h2 {
                font-size: 24px;
            }

            .profile-content {
                padding: 20px;
            }

            .profile-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .measurement-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .measurement-item {
                padding: 12px;
            }

            .measurement-value {
                font-size: 20px;
            }
        }

        /* Legacy User Details Modal Styles (for compatibility) */
        .user-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .detail-section h3 {
            margin-bottom: 15px;
            color: var(--accent-color);
            border-bottom: 2px solid var(--accent-color);
            padding-bottom: 8px;
            font-size: 1.2em;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .detail-item label {
            font-weight: bold;
            color: var(--text-color);
            min-width: 120px;
        }

        .detail-item span {
            color: var(--text-color);
            text-align: right;
        }

        .detail-item {
            margin-bottom: 10px;
        }

        .detail-label {
            font-weight: bold;
            color: var(--text-color);
        }

        .detail-value {
            color: var(--text-color-secondary);
            margin-left: 10px;
        }

        /* MHO Description Styles */
        .mho-description {
            margin-bottom: 30px;
        }

        .description-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border-left: 4px solid var(--accent-color);
        }

        .description-card h3 {
            color: var(--accent-color);
            font-size: 1.5em;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .description-card p {
            font-size: 1.1em;
            line-height: 1.6;
            margin-bottom: 25px;
            color: var(--text-color);
        }

        .assessment-features, .assessment-process {
            margin-bottom: 25px;
        }

        .assessment-features h4, .assessment-process h4 {
            color: var(--text-color);
            font-size: 1.2em;
            margin-bottom: 15px;
            border-bottom: 2px solid var(--accent-color);
            padding-bottom: 8px;
        }

        .assessment-features ul, .assessment-process ol {
            padding-left: 20px;
        }

        .assessment-features li, .assessment-process li {
            margin-bottom: 10px;
            line-height: 1.5;
            color: var(--text-color);
        }

        .assessment-features strong {
            color: var(--accent-color);
        }

        .assessment-process ol {
            counter-reset: step-counter;
        }

        .assessment-process li {
            counter-increment: step-counter;
            position: relative;
            padding-left: 30px;
        }

        .assessment-process li::before {
            content: counter(step-counter);
            position: absolute;
            left: 0;
            top: 0;
            background: var(--accent-color);
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8em;
            font-weight: bold;
        }

        /* User Management Styles - Fixed for Full Screen */
        .user-management-container {
            background-color: var(--color-card);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
            margin-bottom: 30px;
            border: 1px solid var(--color-border);
            position: relative;
            overflow: visible;
            width: 100%;
            max-width: 100%;
            margin-left: 0;
            margin-right: 0;
            box-sizing: border-box;
        }

        /* Dark theme specific styles */
        .dark-theme .user-management-container {
            background-color: var(--color-card);
            border: 1px solid var(--color-border);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }

        .light-theme .user-management-container {
            background: var(--color-card);
            border: 1px solid var(--color-border);
            box-shadow: 0 6px 20px var(--color-shadow);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background: linear-gradient(135deg, var(--color-card) 0%, rgba(161, 180, 84, 0.1) 100%);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
            border: 2px solid rgba(161, 180, 84, 0.3);
            position: relative;
            z-index: 1;
        }

        /* Dark theme table header styles */
        .dark-theme .table-header {
            background: linear-gradient(135deg, var(--color-card) 0%, rgba(161, 180, 84, 0.1) 100%);
            border-color: rgba(161, 180, 84, 0.3);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        /* Light theme table header styles */
        .light-theme .table-header {
            background: linear-gradient(135deg, var(--color-card) 0%, rgba(102, 187, 106, 0.1) 100%);
            border-color: rgba(102, 187, 106, 0.3);
            box-shadow: 0 4px 15px var(--color-shadow);
        }

        .table-header h2 {
            color: var(--color-highlight);
            font-size: 24px;
            margin: 0;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        
        /* Table Header - Organized Grid Layout with Green Container */
        .table-header {
            background: linear-gradient(135deg, var(--color-card) 0%, rgba(161, 180, 84, 0.1) 100%);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border: 2px solid rgba(161, 180, 84, 0.3);
            position: relative;
            overflow: visible;
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }

        /* Add subtle pattern overlay */
        .table-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 49%, rgba(161, 180, 84, 0.03) 50%, transparent 51%);
            pointer-events: none;
        }

        .header-controls {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            width: 100%;
            position: relative;
            z-index: 2;
        }

        /* First Row - Action Buttons + Search (Same Row) */
        .top-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            width: 100%;
            align-items: center;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            align-items: center;
            width: 100%;
            justify-content: flex-start;
        }

        .search-section {
            display: flex;
            align-items: center;
            width: 100%;
            justify-content: flex-end;
        }
        
        .search-container {
            display: flex;
            align-items: center;
            background: var(--color-card);
            border-radius: 10px;
            padding: 10px 15px;
            border: 2px solid rgba(161, 180, 84, 0.3);
            transition: all 0.3s ease;
            flex: 1;
            min-width: 0;
            max-width: 350px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .search-container:focus-within {
            border-color: var(--color-highlight);
            box-shadow: 0 0 0 3px rgba(161, 180, 84, 0.2);
            transform: translateY(-2px);
        }
        
        .search-input {
            border: none;
            background: transparent;
            color: var(--color-text);
            padding: 8px 10px;
            font-size: 14px;
            outline: none;
            width: 100%;
            font-weight: 500;
        }
        
        .search-input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .search-input:focus {
            outline: none;
        }

        .search-btn {
            background: var(--color-highlight);
            border: none;
            color: var(--color-bg);
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            font-weight: 600;
            min-width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(161, 180, 84, 0.3);
        }
        
        .search-btn:hover {
            background: var(--color-accent1);
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(161, 180, 84, 0.4);
        }

        /* Second Row - Filters Container with Green Background and Grid Layout */
        .filters-container {
            width: 100%;
            background: linear-gradient(135deg, rgba(161, 180, 84, 0.15) 0%, rgba(161, 180, 84, 0.05) 100%);
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(161, 180, 84, 0.2);
            position: relative;
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }

        /* Light theme filters container */
        .light-theme .filters-container {
            background: linear-gradient(135deg, rgba(102, 187, 106, 0.15) 0%, rgba(102, 187, 106, 0.05) 100%);
            border-color: rgba(102, 187, 106, 0.2);
        }
        
        /* Filters Grid - Organized Layout with Equal Grid Sizes */
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 12px;
            align-items: end;
            width: 100%;
            min-height: 60px;
        }

        /* Individual filter groups with equal sizing */
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 0;
            flex: 1;
            justify-content: flex-end;
        }

        /* Age input formatting */
        .age-input {
            font-family: 'Courier New', monospace;
            text-align: center;
            letter-spacing: 1px;
        }

        .age-input::placeholder {
            font-family: 'Courier New', monospace;
            text-align: center;
            letter-spacing: 1px;
        }
        
        .filter-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--color-highlight);
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            text-align: center;
        }
        
        .filter-select {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid rgba(161, 180, 84, 0.3);
            border-radius: 8px;
            font-size: 13px;
            background: var(--color-card);
            color: var(--color-text);
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .filter-select:focus {
            outline: none;
            border-color: var(--color-highlight);
            box-shadow: 0 0 0 3px rgba(161, 180, 84, 0.2);
            transform: translateY(-2px);
        }
        
        .filter-select:hover {
            border-color: var(--color-highlight);
        }

        /* Light theme specific styles */
        .light-theme .search-input,
        .light-theme .filter-select {
            background: var(--color-card);
            color: var(--color-text);
            border-color: var(--color-border);
        }

        .light-theme .search-input::placeholder {
            color: var(--color-text);
            opacity: 0.6;
        }

        .light-theme .search-input:focus,
        .light-theme .filter-select:focus {
            border-color: var(--color-highlight);
            box-shadow: 0 0 0 1px rgba(161, 180, 84, 0.2);
        }

        .light-theme .filter-select:hover {
            border-color: var(--color-highlight);
        }

        .light-theme .filter-label {
            color: var(--color-text);
        }

        /* New Control Grid Layout - Flexbox for Strict Containment */
        .control-grid {
            background: linear-gradient(135deg, var(--color-card) 0%, rgba(161, 180, 84, 0.1) 100%);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border: 2px solid rgba(161, 180, 84, 0.3);
            display: flex;
            flex-direction: column;
            gap: 20px;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            position: relative;
            overflow: hidden;
            contain: layout style;
        }

        /* Row 1: Action Buttons and Search - Flexbox Child */
        .control-row-1 {
            display: flex;
            gap: 20px;
            align-items: center;
            justify-content: space-between;
            padding: 10px 0;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            overflow: hidden;
            position: relative;
            flex-shrink: 0;
            contain: layout style;
        }

        .action-section {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-shrink: 0;
            max-width: 50%;
            box-sizing: border-box;
            overflow: hidden;
            position: static;
            contain: layout;
        }

        .filter-dropdowns {
            display: flex;
            gap: 12px;
            flex-shrink: 0;
            align-items: center;
        }

        .filter-select {
            padding: 10px 15px;
            border: 2px solid rgba(161, 180, 84, 0.3);
            background: var(--color-bg);
            border-radius: 8px;
            font-size: 14px;
            color: var(--color-text);
            outline: none;
            transition: all 0.3s ease;
            min-width: 160px;
            max-width: 200px;
            cursor: pointer;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            height: 45px;
        }

        .filter-select:hover {
            border-color: rgba(161, 180, 84, 0.6);
        }

        .filter-select:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 2px rgba(161, 180, 84, 0.2);
        }

        .search-section {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            justify-content: flex-end;
            max-width: 50%;
            box-sizing: border-box;
            overflow: hidden;
            position: static;
            contain: layout;
        }

        .search-input {
            border: 2px solid rgba(161, 180, 84, 0.4);
            background: var(--color-bg);
            padding: 12px 18px;
            border-radius: 8px;
            font-size: 14px;
            color: var(--color-text);
            outline: none;
            transition: all 0.3s ease;
            width: 100%;
            max-width: 100%;
            min-width: 0;
            flex: 1;
            flex-shrink: 1;
            height: 45px;
            box-sizing: border-box;
            position: static;
        }

        .search-input:focus {
            border-color: var(--color-accent1);
            box-shadow: 0 0 0 3px rgba(161, 180, 84, 0.1);
        }

        .search-btn {
            background: var(--color-accent1);
            border: none;
            color: white;
            padding: 12px 18px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            margin-left: 0;
            transition: all 0.3s ease;
            height: 45px;
            min-width: 60px;
        }

        .search-btn:hover {
            background: var(--color-accent2);
            transform: scale(1.05);
        }

        /* Row 2: Filter Controls - Strict Container */
        .control-row-2 {
            background: linear-gradient(135deg, rgba(161, 180, 84, 0.15) 0%, rgba(161, 180, 84, 0.05) 100%);
            border-radius: 10px;
            padding: 20px;
            border: 1px solid rgba(161, 180, 84, 0.2);
            overflow: hidden;
            position: relative;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            flex-shrink: 0;
            contain: layout style;
        }

        .filter-section {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 2fr;
            gap: 20px;
            align-items: end;
            overflow: hidden;
            position: static;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            contain: layout;
        }

        .filter-item {
            display: flex;
            flex-direction: column;
            gap: 8px;
            position: static;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            overflow: hidden;
        }

        .filter-item label {
            font-size: 14px;
            font-weight: 700;
            color: var(--color-highlight);
            text-align: center;
            margin: 0;
            padding: 0;
            line-height: 1.2;
            letter-spacing: 0.5px;
        }

        .filter-item select,
        .filter-item input {
            width: 100%;
            max-width: 100%;
            padding: 12px 15px;
            border: 2px solid rgba(161, 180, 84, 0.3);
            border-radius: 8px;
            background: var(--color-bg);
            color: var(--color-text);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            position: static;
            height: 45px;
            box-sizing: border-box;
            overflow: hidden;
        }

        .filter-item select:hover,
        .filter-item input:hover {
            border-color: var(--color-accent1);
        }

        .filter-item select:focus,
        .filter-item input:focus {
            outline: none;
            border-color: var(--color-accent1);
            box-shadow: 0 0 0 2px rgba(161, 180, 84, 0.1);
        }

        /* Age input formatting */
        .filter-item input[type="text"] {
            font-family: 'Courier New', monospace;
        }

        /* WHO Standard buttons styling - Responsive to Container */
        .who-standard-buttons {
            display: flex;
            flex-wrap: nowrap;
            gap: 4px;
            justify-content: stretch;
            align-items: center;
            width: 100%;
        }

        /* Ensure text wraps properly and displays fully */
        .who-standard-btn * {
            box-sizing: border-box;
            max-width: 100%;
            word-wrap: break-word;
            hyphens: auto;
            white-space: normal;
        }

        .who-standard-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: clamp(4px, 1vw, 8px) clamp(2px, 0.5vw, 6px);
            border: 2px solid rgba(161, 180, 84, 0.3);
            border-radius: 8px;
            background: var(--color-bg);
            color: var(--color-text);
            cursor: pointer;
            transition: all 0.3s ease, flex-grow 0.3s ease, padding 0.3s ease;
            flex: 1 1 0;
            min-width: 0;
            min-height: 45px;
            height: auto;
            font-family: inherit;
            position: relative;
            overflow: visible;
            gap: 1px;
            box-sizing: border-box;
        }

        .who-standard-btn:hover {
            border-color: var(--color-highlight);
            background: rgba(161, 180, 84, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(161, 180, 84, 0.2);
        }

        .who-standard-btn.active {
            border-color: var(--color-highlight);
            background: var(--color-highlight);
            color: white;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(161, 180, 84, 0.3);
        }

        .who-standard-btn.active:hover {
            background: var(--color-highlight);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(161, 180, 84, 0.4);
        }

        .btn-title {
            font-size: clamp(4px, 1.2vw, 9px);
            font-weight: 600;
            line-height: 1;
            margin: 0;
            text-align: center;
            white-space: normal;
            word-wrap: break-word;
            hyphens: auto;
            transition: font-size 0.3s ease;
            overflow: visible;
            max-width: 100%;
            display: block;
            padding: 0 2px;
        }

        .btn-subtitle {
            font-size: clamp(3px, 0.9vw, 7px);
            opacity: 0.8;
            line-height: 1;
            text-align: center;
            font-weight: 400;
            white-space: normal;
            word-wrap: break-word;
            hyphens: auto;
            transition: font-size 0.3s ease;
            margin-top: 1px;
            overflow: visible;
            max-width: 100%;
            display: block;
            padding: 0 2px;
        }

        .who-standard-btn.active .btn-subtitle {
            opacity: 0.9;
        }

        /* Responsive adjustments for WHO standard buttons */
        
        /* Large screens - navbar expanded (more space available) */
        @media (min-width: 1400px) {
            .who-standard-btn {
                min-height: 50px;
                padding: clamp(6px, 1.2vw, 10px) clamp(4px, 0.8vw, 8px);
            }
            
            .btn-title {
                font-size: clamp(6px, 1.4vw, 11px);
            }
            
            .btn-subtitle {
                font-size: clamp(5px, 1.1vw, 9px);
            }
        }
        
        /* Medium screens - navbar collapsed (more space available) */
        @media (min-width: 1200px) and (max-width: 1399px) {
            .who-standard-btn {
                min-height: 48px;
                padding: clamp(5px, 1.1vw, 9px) clamp(3px, 0.7vw, 7px);
            }
            
            .btn-title {
                font-size: clamp(5px, 1.3vw, 10px);
            }
            
            .btn-subtitle {
                font-size: clamp(4px, 1vw, 8px);
            }
        }
        
        /* Standard desktop - responsive to navbar state */
        @media (min-width: 769px) and (max-width: 1199px) {
            .who-standard-btn {
                min-height: 45px;
                padding: clamp(4px, 1vw, 8px) clamp(2px, 0.6vw, 6px);
            }
            
            .btn-title {
                font-size: clamp(4px, 1.2vw, 9px);
            }
            
            .btn-subtitle {
                font-size: clamp(3px, 0.9vw, 7px);
            }
        }
        
        /* Mobile screens */
        @media (max-width: 768px) {
            .who-standard-buttons {
                gap: 2px;
            }
            
            .who-standard-btn {
                min-height: 40px;
                padding: clamp(3px, 1.5vw, 6px) clamp(2px, 1vw, 4px);
            }
            
            .btn-title {
                font-size: clamp(4px, 2.5vw, 8px);
            }
            
            .btn-subtitle {
                font-size: clamp(3px, 2vw, 6px);
            }
        }

        /* Dynamic responsiveness based on navbar state */
        .who-standard-buttons.navbar-collapsed .who-standard-btn {
            min-height: 48px;
            padding: clamp(5px, 1.1vw, 9px) clamp(3px, 0.7vw, 7px);
        }
        
        .who-standard-buttons.navbar-collapsed .btn-title {
            font-size: clamp(5px, 1.3vw, 10px);
        }
        
        .who-standard-buttons.navbar-collapsed .btn-subtitle {
            font-size: clamp(4px, 1vw, 8px);
        }
        
        .who-standard-buttons.navbar-expanded .who-standard-btn {
            min-height: 45px;
            padding: clamp(4px, 1vw, 8px) clamp(2px, 0.6vw, 6px);
        }
        
        .who-standard-buttons.navbar-expanded .btn-title {
            font-size: clamp(4px, 1.1vw, 9px);
        }
        
        .who-standard-buttons.navbar-expanded .btn-subtitle {
            font-size: clamp(3px, 0.8vw, 7px);
        }

        /* Hidden dropdown for compatibility */
        #standardFilter {
            display: none !important;
        }

        /* Table responsive behavior for different screen sizes */
        @media (max-width: 1400px) {
            .table-responsive {
                overflow-x: auto;
            }
            
            .user-table {
                min-width: 1200px;
            }
        }
        
        @media (max-width: 1200px) {
            .table-responsive {
                overflow-x: auto;
            }
            
            .user-table {
                min-width: 1000px;
            }
            
            .user-table th,
            .user-table td {
                min-width: 70px;
                padding: 8px 5px;
                font-size: 11px;
            }
        }
        
        @media (max-width: 992px) {
            .table-responsive {
                overflow-x: auto;
            }
            
            .user-table {
                min-width: 900px;
            }
            
            .user-table th:nth-child(1),
            .user-table td:nth-child(1) {
                min-width: 200px !important;
            }
        }

        /* Light theme adjustments */
        .light-theme .control-grid {
            background: linear-gradient(135deg, var(--color-card) 0%, rgba(102, 187, 106, 0.1) 100%);
            border-color: rgba(102, 187, 106, 0.3);
        }

        .light-theme .control-row-2 {
            background: linear-gradient(135deg, rgba(102, 187, 106, 0.15) 0%, rgba(102, 187, 106, 0.05) 100%);
            border-color: rgba(102, 187, 106, 0.2);
        }

        .light-theme .filter-item label {
            color: var(--color-text);
        }
        

        /* Responsive Design */
        @media (max-width: 1200px) {
            .filter-section {
                grid-template-columns: 1fr 1fr 1fr 1.5fr;
                gap: 10px;
            }
        }

        @media (max-width: 768px) {
            .control-grid {
                gap: 12px;
            }

            .control-row-1 {
                flex-direction: column;
                gap: 12px;
            }

            .control-row-2 {
                padding: 10px;
            }

            .filter-section {
                grid-template-columns: 1fr 1fr 2fr;
                gap: 8px;
            }
            
            /* Give WHO STANDARD more space on mobile */
            .filter-item:nth-child(4) {
                grid-column: 3 / 4;
            }

            .action-section {
                justify-content: center;
                flex-wrap: wrap;
            }

            .btn-add, .btn-secondary {
                flex: 0 0 auto;
                min-width: 120px;
            }

            .filter-dropdowns {
                justify-content: center;
                flex-wrap: wrap;
            }

            .filter-select {
                min-width: 110px;
                flex: 1;
            }

            .search-input {
                width: 100%;
                max-width: none;
            }
        }

        @media (max-width: 480px) {
            .control-grid {
                gap: 10px;
                padding: 12px;
            }

            .control-row-2 {
                padding: 8px;
            }

            .filter-section {
                grid-template-columns: 1fr 1fr;
                gap: 6px;
            }
            
            /* Make WHO STANDARD span full width on very small screens */
            .filter-item:nth-child(4) {
                grid-column: 1 / -1;
            }

            .action-section {
                flex-direction: column;
                gap: 8px;
            }

            /* Ensure name column is still readable on mobile */
            .user-table th:nth-child(1),
            .user-table td:nth-child(1) {
                min-width: 150px !important;
                font-size: 12px !important;
            }

            .action-buttons {
                flex-direction: column;
                gap: 6px;
            }

            .action-buttons .btn-view {
                padding: 4px 8px;
                font-size: 9px;
                min-width: 40px;
            }

            .action-buttons .btn-note {
                padding: 4px 8px;
                font-size: 9px;
                min-width: 40px;
                height: 24px;
            }

            .profile-header-buttons {
                flex-direction: column;
                gap: 5px;
                top: 10px;
                right: 10px;
            }

            .profile-action-btn {
                padding: 6px 12px;
                font-size: 10px;
            }

            .filter-dropdowns {
                flex-direction: column;
                gap: 6px;
            }

            .filter-select {
                min-width: 90px;
                font-size: 9px;
                padding: 4px 6px;
            }

            .filter-item {
                gap: 3px;
            }

            .filter-item label {
                font-size: 10px;
            }

            .filter-item select,
            .filter-item input {
                padding: 5px 6px;
                font-size: 10px;
            }
        }

        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .header-controls {
            display: flex;
            flex-direction: column;
            gap: 15px;
            width: 100%;
        }

        .search-row {
            display: flex;
            gap: 15px;
            align-items: center;
            width: 100%;
            flex-wrap: wrap;
        }

        .action-row {
            display: flex;
            gap: 12px;
            align-items: center;
            width: 100%;
            flex-wrap: wrap;
            justify-content: center;
        }

        .btn-add {
            background: var(--color-highlight);
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 1px solid var(--color-border);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 12px;
            white-space: nowrap;
            flex: 0 0 auto;
            height: 36px;
            min-width: 140px;
        }

        .btn-add:hover {
            background: var(--color-primary);
            transform: translateY(-1px);
        }

        .btn-icon {
            font-size: 16px;
            line-height: 1;
        }

        .btn-text {
            font-size: 13px;
            font-weight: 600;
        }

        .btn-secondary {
            background: var(--color-accent3);
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 1px solid var(--color-border);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 12px;
            white-space: nowrap;
            flex: 0 0 auto;
            height: 36px;
            min-width: 120px;
        }

        .btn-secondary:hover {
            background: var(--color-accent2);
            transform: translateY(-1px);
        }

        .btn {
            padding: 14px 28px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .btn-edit, .btn-suspend, .btn-delete {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            margin: 0 4px;
            transition: all 0.3s ease;
            cursor: pointer !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            border: none;
            min-width: 60px;
            max-width: 80px;
            display: inline-block !important;
            text-align: center;
            line-height: 1.2;
            position: relative;
            z-index: 10;
        }

        .btn-edit {
            background-color: rgba(161, 180, 84, 0.15);
            color: var(--color-highlight);
            border: 2px solid rgba(161, 180, 84, 0.4);
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(161, 180, 84, 0.2);
        }

        .btn-suspend {
            background-color: rgba(224, 201, 137, 0.15);
            color: var(--color-warning);
            border: 2px solid rgba(224, 201, 137, 0.4);
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(224, 201, 137, 0.2);
        }

        .btn-delete {
            background-color: rgba(207, 134, 134, 0.15);
            color: var(--color-danger);
            border: 2px solid rgba(207, 134, 134, 0.4);
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(207, 134, 134, 0.2);
        }

        .light-theme .btn-edit {
            background-color: rgba(102, 187, 106, 0.15);
            color: var(--color-highlight);
            border: 2px solid rgba(102, 187, 106, 0.4);
            font-weight: 600;
        }

        .light-theme .btn-suspend {
            background-color: rgba(255, 183, 77, 0.15);
            color: var(--color-warning);
            border: 2px solid rgba(255, 183, 77, 0.4);
            font-weight: 600;
        }

        .light-theme .btn-delete {
            background-color: rgba(229, 115, 115, 0.15);
            color: var(--color-danger);
            border: 2px solid rgba(229, 115, 115, 0.4);
            font-weight: 600;
        }

        .btn-edit:hover, .btn-suspend:hover, .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            filter: brightness(1.1);
            transform: translateY(-2px) scale(1.05);
        }

        .btn-edit:active, .btn-suspend:active, .btn-delete:active {
            transform: translateY(0) scale(0.98);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .btn-edit:focus, .btn-suspend:focus, .btn-delete:focus {
            outline: 2px solid var(--color-highlight);
            outline-offset: 2px;
            transform: translateY(-1px);
        }

        /* Ensure buttons look clickable */
        .btn-edit, .btn-suspend, .btn-delete {
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            position: relative;
            overflow: hidden;
            pointer-events: auto !important;
            touch-action: manipulation;
        }

        /* Add a subtle background pattern to make buttons more visible */
        .btn-edit::before, .btn-suspend::before, .btn-delete::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s ease;
            pointer-events: none;
        }

        .btn-edit:hover::before, .btn-suspend:hover::before, .btn-delete:hover::before {
            left: 100%;
        }

        /* Ensure buttons are always clickable */
        .btn-edit, .btn-suspend, .btn-delete {
            pointer-events: auto !important;
            cursor: pointer !important;
            position: relative;
            z-index: 100;
            /* Make buttons clearly look clickable */
            background-image: linear-gradient(145deg, rgba(255,255,255,0.1), transparent);
            border-style: solid;
            border-width: 2px;
            text-decoration: none;
            /* Prevent text selection */
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        /* Table responsive container - main definition */
        .table-responsive {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            overflow-y: visible;
            border-radius: 15px;
            border: 1px solid var(--color-border);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            background: var(--color-card);
            margin-top: 15px;
            position: relative;
            /* Custom scrollbar styling */
            scrollbar-width: thin;
            scrollbar-color: rgba(161, 180, 84, 0.3) transparent;
            pointer-events: auto;
        }

        .table-responsive::-webkit-scrollbar {
            height: 8px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background: rgba(161, 180, 84, 0.1);
            border-radius: 4px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background: rgba(161, 180, 84, 0.3);
            border-radius: 4px;
        }

        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: rgba(161, 180, 84, 0.5);
        }

        .user-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 0;
            table-layout: auto;
            border-radius: 15px;
            overflow: hidden;
            border: none;
            box-shadow: none;
            min-width: 100%;
            max-width: 100%;
            background: var(--color-card);
        }

        /* Auto-fit columns - automatically distributes space equally */
        .user-table th,
        .user-table td {
            width: auto;
            min-width: 80px;
            max-width: none;
            box-sizing: border-box;
        }

        /* Ensure name column shows full names - HIGH PRIORITY */
        .user-table th:nth-child(1),
        .user-table td:nth-child(1) {
            min-width: 250px !important;
            max-width: none !important;
            white-space: nowrap !important;
            word-wrap: normal !important;
            word-break: normal !important;
            overflow-wrap: normal !important;
            text-align: left !important;
            padding-left: 12px !important;
            overflow: visible !important;
            text-overflow: clip !important;
            width: 250px !important;
        }

        /* Ensure email column shows full email address */
        .user-table th:nth-child(2),
        .user-table td:nth-child(2) {
            min-width: 200px;
            max-width: none;
            white-space: normal;
            word-wrap: break-word;
        }

        .user-table thead { 
            background-color: var(--color-card);
        }

        .user-table tbody tr:nth-child(odd) {
            background-color: rgba(84, 96, 72, 0.3);
        }

        .user-table tbody tr:nth-child(even) {
            background-color: rgba(84, 96, 72, 0.1);
        }

        /* Dark theme table styles */
        .dark-theme .user-table {
            border: 1px solid var(--color-border);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .dark-theme .user-table thead {
            background-color: var(--color-card);
        }

        .dark-theme .user-table tbody tr:nth-child(odd) {
            background-color: rgba(161, 180, 84, 0.1);
        }

        .dark-theme .user-table tbody tr:nth-child(even) {
            background-color: rgba(161, 180, 84, 0.05);
        }

        /* Light theme table styles */
        .light-theme .user-table {
            border: 1px solid var(--color-border);
            box-shadow: 0 4px 20px var(--color-shadow);
        }

        .light-theme .user-table thead {
            background-color: var(--color-card);
        }

        .light-theme .user-table tbody tr:nth-child(odd) {
            background-color: rgba(102, 187, 106, 0.1);
        }

        .light-theme .user-table tbody tr:nth-child(even) {
            background-color: rgba(102, 187, 106, 0.05);
        }

        .user-table tbody tr {
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            height: auto;
            min-height: 50px;
            position: relative;
            overflow: visible;
        }

        .user-table tbody tr:hover {
            border-left-color: var(--color-highlight);
            background-color: rgba(161, 180, 84, 0.2);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(161, 180, 84, 0.15);
        }

        .user-table th,
        .user-table td {
            padding: 11.25px 7.5px;
            text-align: center;
            border-bottom: 1px solid rgba(161, 180, 84, 0.2);
            white-space: normal;
            word-wrap: break-word;
            word-break: break-word;
            overflow-wrap: break-word;
            font-size: 13px;
            vertical-align: middle;
            height: auto;
            font-weight: 500;
            position: relative;
            line-height: 1.4;
            max-width: none;
            overflow: visible;
            text-overflow: clip;
        }

        /* CRITICAL: Override name column to show full text */
        .user-table th:nth-child(1),
        .user-table td:nth-child(1) {
            white-space: nowrap !important;
            word-wrap: normal !important;
            word-break: normal !important;
            overflow-wrap: normal !important;
            text-align: left !important;
            min-width: 250px !important;
            width: 250px !important;
        }

        /* Professional table borders - Add vertical grid lines between columns */
        .user-table th:not(:last-child),
        .user-table td:not(:last-child) {
            border-right: 1px solid rgba(161, 180, 84, 0.1);
        }

        /* Ensure actions column is always visible and has right border */
        .user-table th:last-child,
        .user-table td:last-child {
            white-space: nowrap;
            overflow: visible;
            text-overflow: clip;
            min-width: 140px;
            text-align: center;
            border-right: 1px solid rgba(161, 180, 84, 0.1);
            vertical-align: middle;
            height: auto;
            padding: 11.25px 7.5px;
            display: table-cell;
            position: relative;
            z-index: 5;
            pointer-events: auto;
        }

        /* Center alignment utility class */
        .text-center {
            text-align: center !important;
        }
        
        
        /* Standard Value Styling */
        .standard-value {
            font-size: 11px;
            line-height: 1.3;
            text-align: center;
        }
        
        /* Classification Styling */
        .classification {
            font-size: 11px;
            font-weight: 600;
            text-align: center;
            color: var(--color-text);
        }



        /* Responsive table wrapper - duplicate removed */


        /* Auto-fit columns - automatically distributes space equally */
        /* All columns will automatically get equal width distribution */
        /* No need for specific nth-child rules - table will auto-adjust */

        .user-table th {
            color: var(--color-highlight);
            font-weight: 700;
            font-size: 13px;
            position: sticky;
            top: 0;
            background-color: var(--color-card);
            z-index: 10;
            border-bottom: 2px solid rgba(161, 180, 84, 0.4);
            padding: 15px 7.5px;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            text-transform: uppercase;
            letter-spacing: 0.3px;
            backdrop-filter: blur(10px);
            text-align: center;
            line-height: 1.3;
        }

        .tooltip {
            position: relative;
            cursor: pointer;
        }

        .tooltiptext {
            visibility: hidden;
            width: 200px;
            background: var(--color-card);
            color: var(--color-text);
            text-align: center;
            border-radius: 8px;
            padding: 8px;
            position: absolute;
            z-index: 1000;
            bottom: 125%;
            left: 50%;
            margin-left: -100px;
            opacity: 0;
            transition: opacity 0.3s;
            border: 1px solid var(--color-border);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            font-size: 12px;
            line-height: 1.4;
        }

        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }

        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }

        .status-active {
            background-color: var(--color-highlight);
        }

        .status-suspended {
            background-color: var(--color-warning);
        }

        .status-inactive {
            background-color: var(--color-danger);
        }

        .risk-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            display: inline-block;
            text-align: center;
            min-width: 50px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .risk-badge.good {
            background-color: rgba(161, 180, 84, 0.15);
            color: #A1B454;
        }

        .risk-badge.at,
        .risk-badge.risk {
            background-color: rgba(224, 201, 137, 0.15);
            color: #E0C989;
        }

        .risk-badge.malnourished {
            background-color: rgba(207, 134, 134, 0.15);
            color: #CF8686;
        }

        /* Light theme risk badge styles */
        .light-theme .risk-badge.good {
            background-color: rgba(102, 187, 106, 0.15);
            color: var(--color-highlight);
            border: 1px solid rgba(102, 187, 106, 0.3);
        }

        .light-theme .risk-badge.at,
        .light-theme .risk-badge.risk {
            background-color: rgba(255, 183, 77, 0.15);
            color: var(--color-warning);
            border: 1px solid rgba(255, 183, 77, 0.3);
        }

        .light-theme .risk-badge.malnourished {
            background-color: rgba(229, 115, 115, 0.15);
            color: var(--color-danger);
            border: 1px solid rgba(229, 115, 115, 0.3);
        }

        /* Add hover effects for risk badges */
        .risk-badge:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .risk-badge.low:hover {
            background-color: rgba(161, 180, 84, 0.25);
            transform: scale(1.05) translateY(-1px);
        }

        .risk-badge.medium:hover {
            background-color: rgba(224, 201, 137, 0.25);
            transform: scale(1.05) translateY(-1px);
        }

        .risk-badge.high:hover {
            background-color: rgba(207, 134, 134, 0.25);
            transform: scale(1.05) translateY(-1px);
        }

        .light-theme .risk-badge.low:hover {
            background-color: rgba(102, 187, 106, 0.25);
        }

        .light-theme .risk-badge.medium:hover {
            background-color: rgba(255, 183, 77, 0.25);
        }

        .light-theme .risk-badge.high:hover {
            background-color: rgba(229, 115, 115, 0.25);
        }

        /* Add hover effects for table rows */
        .user-table tbody tr {
            transition: all 0.3s ease;
            position: relative;
        }

        .user-table tbody tr::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(161, 180, 84, 0.05), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .user-table tbody tr:hover::after {
            opacity: 1;
        }

        .user-table tbody tr:hover {
            border-left-color: var(--color-highlight);
            background-color: rgba(161, 180, 84, 0.2);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(161, 180, 84, 0.15);
        }

        /* Add hover effects for search container */
        .search-container {
            display: flex;
            align-items: center;
            background: var(--color-card);
            border-radius: 8px;
            padding: 8px 12px;
            border: 1px solid rgba(161, 180, 84, 0.3);
            transition: all 0.2s ease;
            flex: 1;
            min-width: 0;
            max-width: 300px;
        }

        .search-container:focus-within {
            border-color: var(--color-highlight);
            box-shadow: 0 0 0 2px rgba(161, 180, 84, 0.2);
            transform: translateY(-1px);
        }

        .search-input {
            border: none;
            background: transparent;
            color: var(--color-text);
            padding: 6px 8px;
            font-size: 14px;
            outline: none;
            width: 100%;
            font-weight: 500;
        }

        .search-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
            font-weight: 400;
        }

        .search-input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        /* Dark theme search input styles */
        .dark-theme .search-input {
            background: var(--color-card);
            color: var(--color-text);
            border: 2px solid rgba(161, 180, 84, 0.3);
            border-radius: 8px;
            padding: 10px 12px;
            transition: all 0.3s ease;
        }

        .dark-theme .search-input:focus {
            border-color: var(--color-highlight);
            box-shadow: 0 0 0 3px rgba(161, 180, 84, 0.2);
            transform: translateY(-2px);
        }

        .dark-theme .search-input:hover {
            border-color: var(--color-highlight);
        }

        .dark-theme .search-input::placeholder {
            color: rgba(232, 240, 214, 0.6);
        }

        /* Light theme search input styles */
        .light-theme .search-input {
            background: var(--color-card);
            color: var(--color-text);
            border: 2px solid rgba(161, 180, 84, 0.3);
            border-radius: 8px;
            padding: 10px 12px;
            transition: all 0.3s ease;
        }

        .light-theme .search-input:focus {
            border-color: var(--color-highlight);
            box-shadow: 0 0 0 3px rgba(161, 180, 84, 0.2);
            transform: translateY(-2px);
        }

        .light-theme .search-input:hover {
            border-color: var(--color-highlight);
        }

        .light-theme .search-input::placeholder {
            color: rgba(65, 89, 57, 0.6);
        }

        .search-btn {
            background: var(--color-highlight);
            border: none;
            color: var(--color-bg);
            padding: 6px 8px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s ease;
            font-weight: 600;
            min-width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .search-btn:hover {
            background: var(--color-accent1);
            transform: scale(1.02);
        }

        /* Location filter styles */
        .location-filter-container {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            max-width: 250px;
        }

        .location-select {
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid var(--color-border);
            background-color: var(--color-card);
            color: var(--color-text);
            font-size: 14px;
            width: 100%;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        /* Dark theme location select styles */
        .dark-theme .location-select {
            background-color: var(--color-card);
            color: var(--color-text);
            border-color: var(--color-border);
        }

        .light-theme .location-select {
            background-color: var(--color-card);
            border: 1px solid var(--color-border);
            color: var(--color-text);
        }

        .location-select:focus {
            outline: none;
            border-color: var(--color-highlight);
            box-shadow: 0 0 0 2px rgba(161, 180, 84, 0.2);
            transform: translateY(-1px);
        }

        .light-theme .location-select:focus {
            border-color: var(--color-highlight);
            box-shadow: 0 0 0 2px var(--color-shadow);
        }

        .no-data-message {
            text-align: center;
            padding: 30px;
            color: var(--color-text);
            opacity: 0.7;
            font-style: italic;
        }

        /* CSV Upload Styles */
        .csv-upload-area {
            background: linear-gradient(135deg, rgba(161, 180, 84, 0.1), rgba(161, 180, 84, 0.05));
            border: 2px dashed rgba(161, 180, 84, 0.4);
            border-radius: 15px;
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .csv-upload-area:hover {
            background: linear-gradient(135deg, rgba(161, 180, 84, 0.15), rgba(161, 180, 84, 0.1));
            border-color: var(--color-highlight);
            transform: translateY(-2px);
        }

        .csv-upload-area.dragover {
            background: linear-gradient(135deg, rgba(161, 180, 84, 0.2), rgba(161, 180, 84, 0.15));
            border-color: var(--color-highlight);
            transform: scale(1.02);
        }

        .upload-text h4 {
            color: var(--color-highlight);
            font-size: 20px;
            margin-bottom: 10px;
        }

        .upload-text p {
            color: var(--color-text);
            margin-bottom: 8px;
            opacity: 0.9;
        }

        .csv-format {
            font-size: 12px;
            opacity: 0.7;
        }

        .csv-import-modal-content {
            position: fixed !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            margin: 0 !important;
            height: 85vh !important;
            width: 90% !important;
            max-width: 800px !important;
            border-radius: 15px !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3) !important;
            overflow: hidden !important;
            z-index: 1001 !important;
        }

        .csv-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }

        .csv-preview-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 12px;
        }

        .csv-preview-table th,
        .csv-preview-table td {
            padding: 8px;
            text-align: left;
            border: 1px solid var(--color-border);
        }

        .csv-preview-table th {
            background-color: var(--color-highlight);
            color: white;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: var(--color-card);
            margin: 15% auto;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 600px;
            position: relative;
        }

        .close {
            color: var(--color-text);
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: var(--color-danger);
        }

        .btn-submit {
            background-color: var(--color-highlight);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-submit:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .btn-cancel {
            background-color: var(--color-danger);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
        }

        .csv-import-info {
            background-color: rgba(161, 180, 84, 0.1);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .csv-import-info h4 {
            color: var(--color-highlight);
            margin-bottom: 10px;
        }

        /* Nutritional Assessment Status Styles - Plain Text with Colors */
        .nutritional-status {
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            padding: 8px 4px;
            white-space: nowrap;
        }

        .nutritional-status.severe-acute-malnutrition-sam {
            color: #FF1744;
        }

        .nutritional-status.moderate-acute-malnutrition-mam {
            color: #FF9800;
        }

        .nutritional-status.mild-acute-malnutrition-wasting {
            color: #FFC107;
        }

        .nutritional-status.stunting-chronic-malnutrition {
            color: #9C27B0;
        }

        .nutritional-status.maternal-undernutrition-at-risk {
            color: #E91E63;
        }

        .nutritional-status.maternal-at-risk {
            color: #FF5722;
        }

        .nutritional-status.severe-underweight {
            color: #FF1744;
        }

        .nutritional-status.moderate-underweight {
            color: #FF9800;
        }

        .nutritional-status.mild-underweight {
            color: #FFC107;
        }

        .nutritional-status.normal {
            color: #4CAF50;
        }

        .nutritional-status.overweight {
            color: #FF9800;
        }

        .nutritional-status.obesity-class-i {
            color: #FF5722;
        }

        .nutritional-status.obesity-class-ii {
            color: #FF1744;
        }

        .nutritional-status.obesity-class-iii-severe {
            color: #FF1744;
        }

        .nutritional-status.invalid-data {
            color: #9E9E9E;
        }

        .nutritional-status.assessment-error {
            color: #9E9E9E;
        }

        /* WHO Growth Standards Classification Styles */
        .who-classification {
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            padding: 8px 4px;
            white-space: nowrap;
            border-radius: 6px;
            margin: 2px;
        }

        .who-classification.severely-underweight {
            color: #D32F2F;
            background: rgba(211, 47, 47, 0.1);
            border: 1px solid rgba(211, 47, 47, 0.3);
        }

        .who-classification.underweight {
            color: #FF9800;
            background: rgba(255, 152, 0, 0.1);
            border: 1px solid rgba(255, 152, 0, 0.3);
        }

        .who-classification.normal {
            color: #4CAF50;
            background: rgba(76, 175, 80, 0.1);
            border: 1px solid rgba(76, 175, 80, 0.3);
        }

        .who-classification.overweight {
            color: #FF5722;
            background: rgba(255, 87, 34, 0.1);
            border: 1px solid rgba(255, 87, 34, 0.3);
        }

        .who-classification.obese {
            color: #8B4513;
            background: rgba(139, 69, 19, 0.1);
            border: 1px solid rgba(139, 69, 19, 0.3);
        }

        .who-classification.n/a {
            color: #9E9E9E;
            background: rgba(158, 158, 158, 0.1);
            border: 1px solid rgba(158, 158, 158, 0.3);
        }

        .who-classification.height-out-of-range {
            color: #9C27B0;
            background: rgba(156, 39, 176, 0.1);
            border: 1px solid rgba(156, 39, 176, 0.3);
        }

        .who-classification.age-out-of-range {
            color: #607D8B;
            background: rgba(96, 125, 139, 0.1);
            border: 1px solid rgba(96, 125, 139, 0.3);
        }

        /* Hover effects for WHO classifications */
        .who-classification:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .who-classification.severely-underweight:hover {
            background: rgba(211, 47, 47, 0.2);
        }

        .who-classification.underweight:hover {
            background: rgba(255, 152, 0, 0.2);
        }

        .who-classification.normal:hover {
            background: rgba(76, 175, 80, 0.2);
        }

        .who-classification.overweight:hover {
            background: rgba(255, 87, 34, 0.2);
        }

        .who-classification.obese:hover {
            background: rgba(139, 69, 19, 0.2);
        }

        .risk-level {
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            padding: 8px 4px;
            white-space: nowrap;
        }

        .risk-level.very-high {
            color: #FF1744;
        }

        .risk-level.high {
            color: #FF1744;
        }

        .risk-level.medium {
            color: #FF9800;
        }

        .risk-level.low-medium {
            color: #FFC107;
        }

        .risk-level.low {
            color: #4CAF50;
        }

        .risk-level.unknown {
            color: #9E9E9E;
        }

        .category {
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            padding: 8px 4px;
            white-space: nowrap;
        }

        .category.undernutrition {
            color: #FF9800;
        }

        .category.overnutrition {
            color: #FF1744;
        }

        .category.normal {
            color: #4CAF50;
        }

        .category.error {
            color: #9E9E9E;
        }
        
        /* Conditional column styles */
        .conditional-column {
            transition: all 0.3s ease;
        }
        
        .conditional-column[style*="display: none"] {
            width: 0;
            padding: 0;
            margin: 0;
            border: none;
            overflow: hidden;
        }
        
        /* Standard deviation range styling */
        .sd-range {
            font-weight: 600;
            color: var(--color-highlight);
        }

    </style>
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
                <li><a href="screening"><span class="navbar-icon"></span><span>MHO Assessment</span></a></li>
                <li><a href="event"><span class="navbar-icon"></span><span>Nutrition Event Notifications</span></a></li>
                
                <li><a href="ai"><span class="navbar-icon"></span><span>Chatbot & AI Logs</span></a></li>
                <li><a href="settings"><span class="navbar-icon"></span><span>Settings & Admin</span></a></li>
                <li><a href="logout" style="color: #ff5252;"><span class="navbar-icon"></span><span>Logout</span></a></li>
            </ul>
        </div>
        <div class="navbar-footer">
            <div>NutriSaur v2.0 • © 2025</div>
            <div style="margin-top: 10px;">Logged in as: <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></div>
        </div>
    </div>

    <!-- Mobile Top Navigation -->
    <div class="mobile-top-nav">
        <div class="mobile-nav-container">
            <div class="mobile-nav-logo">
                <img src="/logo.png" alt="Logo" class="mobile-logo-img">
                <span class="mobile-logo-text">NutriSaur</span>
            </div>
            <div class="mobile-nav-icons">
                <a href="dash" class="mobile-nav-icon" title="Dashboard">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
                    </svg>
                </a>
                <a href="screening" class="mobile-nav-icon active" title="MHO Assessment">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                    </svg>
                </a>
                <a href="event" class="mobile-nav-icon" title="Events">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/>
                    </svg>
                </a>
                <a href="ai" class="mobile-nav-icon" title="AI Chatbot">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M20 2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h4l4 4 4-4h4c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/>
                    </svg>
                </a>
                <a href="settings" class="mobile-nav-icon" title="Settings">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19.14,12.94c0.04-0.3,0.06-0.61,0.06-0.94c0-0.32-0.02-0.64-0.07-0.94l2.03-1.58c0.18-0.14,0.23-0.41,0.12-0.61 l-1.92-3.32c-0.12-0.22-0.37-0.29-0.59-0.22l-2.39,0.96c-0.5-0.38-1.03-0.7-1.62-0.94L14.4,2.81c-0.04-0.24-0.24-0.41-0.48-0.41 h-3.84c-0.24,0-0.43,0.17-0.47,0.41L9.25,5.35C8.66,5.59,8.12,5.92,7.63,6.29L5.24,5.33c-0.22-0.08-0.47,0-0.59,0.22L2.74,8.87 C2.62,9.08,2.66,9.34,2.86,9.48l2.03,1.58C4.84,11.36,4.82,11.69,4.82,12s0.02,0.64,0.07,0.94l-2.03,1.58 c-0.18,0.14-0.23,0.41-0.12,0.61l1.92,3.32c0.12,0.22,0.37,0.29,0.59,0.22l2.39-0.96c0.5,0.38,1.03,0.7,1.62,0.94l0.36,2.54 c0.05,0.24,0.24,0.41,0.48,0.41h3.84c0.24,0,0.44-0.17,0.47-0.41l0.36-2.54c0.59-0.24,1.13-0.56,1.62-0.94l2.39,0.96 c0.22,0.08,0.47,0,0.59-0.22l1.92-3.32c0.12-0.22,0.07-0.47-0.12-0.61L19.14,12.94z M12,15.6c-1.98,0-3.6-1.62-3.6-3.6 s1.62-3.6,3.6-3.6s3.6,1.62,3.6,3.6S13.98,15.6,12,15.6z"/>
                    </svg>
                </a>
                <a href="logout" class="mobile-nav-icon" title="Logout" style="color: #ff5252;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <div class="dashboard">
        <header class="dashboard-header fade-in">
            <div class="dashboard-title">
                <h1>MHO Nutritional Assessment Module</h1>
            </div>
            <div class="user-info">
                <button id="new-theme-toggle" class="new-theme-toggle-btn" title="Toggle theme">
                    <span class="new-theme-icon">🌙</span>
                </button>
            </div>
        </header>

        <div class="screening-container">
            <div class="user-management-container">
                <!-- New Organized Grid Layout -->
                <div class="control-grid">
                    <!-- Row 1: Action Buttons and Search -->
                    <div class="control-row-1">
                        <div class="action-section">
                            <button class="btn-add" onclick="downloadCSVTemplate()">
                                <span class="btn-icon">📥</span>
                                <span class="btn-text">Download Template</span>
                            </button>
                            <button class="btn-secondary" onclick="showCSVImportModal()">
                                <span class="btn-icon">📁</span>
                                <span class="btn-text">Import CSV</span>
                            </button>
                        </div>
                        
                        <!-- New Sorting and Classification Filters -->
                        <div class="filter-dropdowns">
                            <select id="sortBy" onchange="sortTable()" class="filter-select">
                                <option value="">Sort by...</option>
                                <option value="name_asc">Name (A-Z)</option>
                                <option value="name_desc">Name (Z-A)</option>
                                <option value="email_asc">Email (A-Z)</option>
                                <option value="email_desc">Email (Z-A)</option>
                                <option value="age_asc">Age (Youngest)</option>
                                <option value="age_desc">Age (Oldest)</option>
                                <option value="screening_date_asc">Screening Date (Oldest)</option>
                                <option value="screening_date_desc">Screening Date (Newest)</option>
                            </select>
                            
                            <select id="classificationFilter" onchange="filterByClassification()" class="filter-select">
                                <option value="">All Classifications</option>
                                <optgroup label="Weight-for-Age">
                                    <option value="Severely Underweight">Severely Underweight</option>
                                    <option value="Underweight">Underweight</option>
                                    <option value="Normal">Normal</option>
                                    <option value="Overweight">Overweight</option>
                                </optgroup>
                                <optgroup label="Height-for-Age (Stunting)">
                                    <option value="Severely Stunted">Severely Stunted</option>
                                    <option value="Stunted">Stunted</option>
                                    <option value="Tall">Tall</option>
                                </optgroup>
                                <optgroup label="Weight-for-Height (Wasting)">
                                    <option value="Severely Wasted">Severely Wasted</option>
                                    <option value="Wasted">Wasted</option>
                                    <option value="Overweight">Overweight</option>
                                    <option value="Obese">Obese</option>
                                </optgroup>
                                <optgroup label="BMI-for-Age">
                                    <option value="Severely Underweight">Severely Underweight</option>
                                    <option value="Underweight">Underweight</option>
                                    <option value="Normal">Normal</option>
                                    <option value="Overweight">Overweight</option>
                                    <option value="Obese">Obese</option>
                                </optgroup>
                            </select>
                        </div>
                        
                        <div class="search-section">
                            <input type="text" id="searchInput" placeholder="Search by name, email..." class="search-input">
                            <button type="button" onclick="searchAssessments()" class="search-btn">🔍</button>
                        </div>
                    </div>

                    <!-- Row 2: Filter Controls -->
                    <div class="control-row-2">
                        <div class="filter-section">
                            <div class="filter-item">
                                <label>MUNICIPALITY</label>
                                <select id="municipalityFilter" onchange="filterByMunicipality()" <?php echo (isset($_SESSION['admin_id']) && $_SESSION['admin_id'] === 'super_admin') ? '' : 'disabled'; ?>>
                                    <?php if (isset($_SESSION['admin_id']) && $_SESSION['admin_id'] === 'super_admin'): ?>
                                        <option value="">All</option>
                                        <option value="ABUCAY">ABUCAY</option>
                                        <option value="BAGAC">BAGAC</option>
                                        <option value="CITY OF BALANGA">CITY OF BALANGA</option>
                                        <option value="DINALUPIHAN">DINALUPIHAN</option>
                                        <option value="HERMOSA">HERMOSA</option>
                                        <option value="LIMAY">LIMAY</option>
                                        <option value="MARIVELES">MARIVELES</option>
                                        <option value="MORONG">MORONG</option>
                                        <option value="ORANI">ORANI</option>
                                        <option value="ORION">ORION</option>
                                        <option value="PILAR">PILAR</option>
                                        <option value="SAMAL">SAMAL</option>
                                    <?php else: ?>
                                        <option value="<?php echo htmlspecialchars($user_municipality ?? 'No Municipality Assigned'); ?>" selected>
                                            <?php echo htmlspecialchars($user_municipality ?? 'No Municipality Assigned'); ?>
                                        </option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <div class="filter-item">
                                <label>BARANGAY</label>
                                <select id="barangayFilter" onchange="filterByBarangay()">
                                    <option value="">All</option>
                                </select>
                            </div>
                            
                            
                            
                            <div class="filter-item">
                                <label>SEX</label>
                                <select id="sexFilter" onchange="filterBySex()">
                                    <option value="">All</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            
                            <div class="filter-item">
                                <label>WHO STANDARD</label>
                                <div class="who-standard-buttons">
                                    <button type="button" class="who-standard-btn active" data-standard="weight-for-age" onclick="selectWHOStandard('weight-for-age', this)">
                                        <span class="btn-title">Weight-for-Age</span>
                                        <span class="btn-subtitle">(0-71 months)</span>
                                    </button>
                                    <button type="button" class="who-standard-btn" data-standard="height-for-age" onclick="selectWHOStandard('height-for-age', this)">
                                        <span class="btn-title">Height-for-Age</span>
                                        <span class="btn-subtitle">(0-71 months)</span>
                                    </button>
                                    <button type="button" class="who-standard-btn" data-standard="weight-for-height" onclick="selectWHOStandard('weight-for-height', this)">
                                        <span class="btn-title">Weight-for-Height</span>
                                        <span class="btn-subtitle">(0-60 months)</span>
                                    </button>
                                    <button type="button" class="who-standard-btn" data-standard="bmi-for-age" onclick="selectWHOStandard('bmi-for-age', this)">
                                        <span class="btn-title">BMI-for-Age</span>
                                        <span class="btn-subtitle">(5-19 years)</span>
                                    </button>
                                    <button type="button" class="who-standard-btn" data-standard="bmi-adult" onclick="selectWHOStandard('bmi-adult', this)">
                                        <span class="btn-title">BMI Adult</span>
                                        <span class="btn-subtitle">(≥19 years)</span>
                                    </button>
                                </div>
                                <!-- Hidden select to maintain compatibility with existing logic -->
                                <select id="standardFilter" style="display: none;" onchange="filterByStandard()">
                                    <option value="weight-for-age" selected>Weight-for-Age (0-71 months)</option>
                                    <option value="height-for-age">Height-for-Age (0-71 months)</option>
                                    <option value="weight-for-height">Weight-for-Height (0-60 months)</option>
                                    <option value="bmi-for-age">BMI-for-Age (5-19 years)</option>
                                    <option value="bmi-adult">BMI Adult (≥19 years)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="no-users-message" style="display:none;" class="no-data-message">
                    No users found in the database. Add your first user!
                </div>

                <div class="table-responsive">
                <table class="user-table">
                    <thead id="tableHeaders">
                        <tr>
                            <th>NAME</th>
                            <th>EMAIL</th>
                            <th>AGE</th>
                            <th>SEX</th>
                            <th id="weightHeader" class="conditional-column">WEIGHT (kg)</th>
                            <th id="heightHeader" class="conditional-column">HEIGHT (cm)</th>
                            <th id="bmiHeader" class="conditional-column">BMI</th>
                            <th id="standardHeader">Z-SCORE</th>
                            <th>CLASSIFICATION</th>
                            <th>SCREENING DATE</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <?php
                        // Get community_users data directly from database
                        if ($db->isAvailable()) {
                            try {
                                // Use Universal DatabaseAPI to get users for HTML display
                                $result = $db->select(
                                    'community_users',
                                    '*',
                                    '',
                                    [],
                                    'screening_date DESC'
                                );
                                
                                $users = $result['success'] ? $result['data'] : [];
                                
                                
                                if (!empty($users)) {
                                    foreach ($users as $user) {
                                        // Use WHO Growth Standards decision tree for each user
                                        try {
                                            $who = new WHOGrowthStandards();
                                            
                                            
                                            $assessment = $who->getComprehensiveAssessment(
                                                floatval($user['weight']),
                                                floatval($user['height']),
                                                $user['birthday'],
                                                $user['sex'],
                                                $user['screening_date']
                                            );
                                        } catch (Exception $e) {
                                            // If WHO calculation fails, use fallback
                                            $assessment = ['success' => false, 'error' => $e->getMessage()];
                                        }
                                        
                                        if ($assessment['success']) {
                                            // Get WHO Growth Standards results from decision tree
                                            $results = $assessment['results'];
                                            $wfa_zscore = $results['weight_for_age']['z_score'] ?? null;
                                            $hfa_zscore = $results['height_for_age']['z_score'] ?? null;
                                            $wfh_zscore = $results['weight_for_height']['z_score'] ?? null;
                                            $wfl_zscore = $results['weight_for_length']['z_score'] ?? null;
                                            $bmi_zscore = $results['bmi_for_age']['z_score'] ?? null;
                                            
                                            $wfa_classification = $results['weight_for_age']['classification'] ?? 'N/A';
                                            $hfa_classification = $results['height_for_age']['classification'] ?? 'N/A';
                                            $wfh_classification = $results['weight_for_height']['classification'] ?? 'N/A';
                                            $wfl_classification = $results['weight_for_length']['classification'] ?? 'N/A';
                                            $bmi_classification = $results['bmi_for_age']['classification'] ?? 'N/A';
                                            
                                            
                                            // Format z-scores for display (only Z-score value, no prefix)
                                            $wfa_display = $wfa_zscore !== null ? number_format($wfa_zscore, 2) : 'N/A';
                                            $hfa_display = $hfa_zscore !== null ? number_format($hfa_zscore, 2) : 'N/A';
                                            $wfh_display = $wfh_zscore !== null ? number_format($wfh_zscore, 2) : 'N/A';
                                            $wfl_display = $wfl_zscore !== null ? number_format($wfl_zscore, 2) : 'N/A';
                                            $bmi_display = $bmi_zscore !== null ? number_format($bmi_zscore, 2) : 'N/A';
                                        } else {
                                            // If WHO calculation fails, show N/A
                                            $wfa_display = 'N/A';
                                            $hfa_display = 'N/A';
                                            $wfh_display = 'N/A';
                                            $wfl_display = 'N/A';
                                            $bmi_display = 'N/A';
                                            
                                            // Also set classification variables to N/A
                                            $wfa_classification = 'N/A';
                                            $hfa_classification = 'N/A';
                                            $wfh_classification = 'N/A';
                                            $wfl_classification = 'N/A';
                                            $bmi_classification = 'N/A';
                                        }
                                        
                                        // Calculate age in years and months using screening date
                                        $birthDate = new DateTime($user['birthday']);
                                        $screeningDate = new DateTime($user['screening_date']);
                                        $age = $birthDate->diff($screeningDate);
                                        
                                        // More accurate age calculation including days
                                        $ageInMonths = ($age->y * 12) + $age->m;
                                        // Add partial month if more than half the month has passed
                                        if ($age->d >= 15) {
                                            $ageInMonths += 1;
                                        }
                                        
                                        $ageDisplay = $age->y . 'y ' . $age->m . 'm';
                                        
                                        // Calculate BMI (with division by zero protection)
                                        $bmi = 'N/A';
                                        if ($user['weight'] && $user['height'] && $user['height'] > 0) {
                                            $bmi = round($user['weight'] / pow($user['height'] / 100, 2), 1);
                                        }
                                        
                                        // Generate all WHO Growth Standards data for this user
                                        $whoData = [
                                            'weight-for-age' => ['display' => $wfa_display, 'classification' => $wfa_classification],
                                            'height-for-age' => ['display' => $hfa_display, 'classification' => $hfa_classification],
                                            'weight-for-height' => ['display' => $wfh_display, 'classification' => $wfh_classification],
                                            'weight-for-length' => ['display' => $wfl_display, 'classification' => $wfl_classification],
                                            'bmi-for-age' => ['display' => $bmi_display, 'classification' => $bmi_classification]
                                        ];
                                        
                                        // Add BMI Adult data for adults ≥19 years (228+ months)
                                        if ($ageInMonths >= 228) {
                                            $adultBmiClassification = getAdultBMIClassification($bmi);
                                            $whoData['bmi-adult'] = [
                                                'display' => $bmi,
                                                'classification' => $adultBmiClassification['classification'],
                                                'z_score' => $adultBmiClassification['z_score']
                                            ];
                                        }
                                        
                                        // Generate rows for ALL WHO standards, let JavaScript filter which ones to show
                                        foreach ($whoData as $standardName => $standardData) {
                                            // Skip standards that don't apply to this age/height
                                            if ($standardName === 'weight-for-age' && $ageInMonths > 71) continue;
                                            if ($standardName === 'height-for-age' && $ageInMonths > 71) continue;
                                            if ($standardName === 'weight-for-height' && $ageInMonths > 60) continue;
                                            if ($standardName === 'bmi-for-age' && ($ageInMonths < 60 || $ageInMonths >= 228)) continue;
                                            if ($standardName === 'bmi-adult' && $ageInMonths < 228) continue;
                                            
                                            
                                            // Display appropriate value based on standard type
                                            if ($standardName === 'bmi-for-age' || $standardName === 'bmi-adult') {
                                                // For BMI standards, show accurate z-score range (not BMI value)
                                                $classification = $standardData['classification'] ?? 'N/A';
                                                $accurateRange = getAccurateZScoreRange($classification, $standardName);
                                                $zScoreDisplay = $accurateRange;
                                            } else {
                                                // For other standards, show accurate z-score range
                                                $classification = $standardData['classification'] ?? 'N/A';
                                                $accurateRange = getAccurateZScoreRange($classification, $standardName);
                                                $zScoreDisplay = $accurateRange;
                                            }
                                            
                                            // Add flagged class if user is flagged
                                            $flaggedClass = (!empty($user['is_flagged']) && $user['is_flagged'] == 1) ? ' flagged-user-row' : '';
                                            echo '<tr class="' . $flaggedClass . '" data-email="' . htmlspecialchars($user['email']) . '" data-standard="' . $standardName . '" data-age-months="' . $ageInMonths . '" data-height="' . $user['height'] . '" data-municipality="' . htmlspecialchars($user['municipality'] ?? '') . '" data-barangay="' . htmlspecialchars($user['barangay'] ?? '') . '" data-sex="' . htmlspecialchars($user['sex'] ?? '') . '">';
                                            $fullName = htmlspecialchars($user['name'] ?? 'N/A');
                                            echo '<td class="text-center" title="' . $fullName . '" data-full-name="' . $fullName . '">' . $fullName . '</td>';
                                            echo '<td class="text-center">' . htmlspecialchars($user['email'] ?? 'N/A') . '</td>';
                                            echo '<td class="text-center">' . $ageDisplay . '</td>';
                                            echo '<td class="text-center">' . htmlspecialchars($user['sex'] ?? 'N/A') . '</td>';
                                            
                                            // Show conditional columns based on standard
                                            if ($standardName === 'weight-for-age' || $standardName === 'bmi-for-age' || $standardName === 'bmi-adult') {
                                                echo '<td class="text-center conditional-column">' . htmlspecialchars($user['weight'] ?? 'N/A') . '</td>';
                                            } else {
                                                echo '<td class="text-center conditional-column" style="display:none;">' . htmlspecialchars($user['weight'] ?? 'N/A') . '</td>';
                                            }
                                            
                                            if ($standardName === 'height-for-age' || $standardName === 'weight-for-height' || $standardName === 'bmi-for-age' || $standardName === 'bmi-adult') {
                                                // Get height value from database
                                                $heightValue = $user['height'] ?? 'N/A';
                                                
                                                // If height is missing, calculate from BMI and weight
                                                if ($heightValue === 'N/A' || empty($heightValue)) {
                                                    if (!empty($user['weight']) && !empty($bmi) && $bmi !== 'N/A' && is_numeric($bmi) && is_numeric($user['weight'])) {
                                                        $calculatedHeight = sqrt($user['weight'] / $bmi) * 100;
                                                        $heightValue = round($calculatedHeight, 1);
                                                    }
                                                }
                                                echo '<td class="text-center conditional-column">' . htmlspecialchars($heightValue) . '</td>';
                                            } else {
                                                $heightValue = $user['height'] ?? 'N/A';
                                                echo '<td class="text-center conditional-column" style="display:none;">' . htmlspecialchars($heightValue) . '</td>';
                                            }
                                            
                                            // Show BMI column for BMI standards, hide for others
                                            if ($standardName === 'bmi-for-age' || $standardName === 'bmi-adult') {
                                                echo '<td class="text-center conditional-column">' . $bmi . '</td>';
                                            } else {
                                                echo '<td class="text-center conditional-column" style="display:none;">' . $bmi . '</td>';
                                            }
                                            
                                            // Show Z-Score column for non-BMI standards, hide for BMI standards
                                            if ($standardName === 'bmi-for-age' || $standardName === 'bmi-adult') {
                                                echo '<td class="text-center standard-value" style="display:none;">' . htmlspecialchars($zScoreDisplay) . '</td>';
                                            } else {
                                                echo '<td class="text-center standard-value">' . htmlspecialchars($zScoreDisplay) . '</td>';
                                            }
                                            
                                            // Ensure classification column shows proper classification name
                                            $classificationDisplay = 'N/A';
                                            if ($standardName === 'bmi-for-age') {
                                                $classificationDisplay = $bmi_classification ?? 'N/A';
                                            } elseif ($standardName === 'bmi-adult') {
                                                $classificationDisplay = $adultBmiClassification['classification'] ?? 'N/A';
                                            } else {
                                                $classificationDisplay = $standardData['classification'] ?? 'N/A';
                                            }
                                            
                                            echo '<td class="text-center">' . htmlspecialchars($classificationDisplay) . '</td>';
                                            // Format screening date to show only date
                                            $screeningDate = $user['screening_date'] ?? 'N/A';
                                            if ($screeningDate !== 'N/A' && $screeningDate) {
                                                $date = new DateTime($screeningDate);
                                                $screeningDate = $date->format('Y-m-d');
                                            }
                                            echo '<td class="text-center">' . htmlspecialchars($screeningDate) . '</td>';
                                            echo '<td class="text-center">';
                                            echo '<div class="action-buttons">';
                                            echo '<button class="btn-view" onclick="console.log(\'🔍 View button clicked for user EMAIL: ' . htmlspecialchars($user['email']) . '\'); viewUserDetails(\'' . htmlspecialchars($user['email']) . '\')" title="View Full Details">';
                                            echo 'View';
                                            echo '</button>';
                                            // Only show note button if user has notes
                                            $hasNotes = !empty($user['notes']);
                                            if ($hasNotes) {
                                                // Extract date from note (format: [date time] note content)
                                                $noteDate = '';
                                                if (preg_match('/^\[([^\]]+)\]/', $user['notes'], $matches)) {
                                                    $fullDateTime = $matches[1];
                                                    // Extract only the date part (before the space)
                                                    $dateParts = explode(' ', $fullDateTime);
                                                    $noteDate = $dateParts[0]; // Only the date part
                                                }
                                                
                                                echo '<button class="btn-note" onclick="viewUserNote(\'' . htmlspecialchars($user['email']) . '\', \'' . htmlspecialchars($user['name']) . '\')" title="View Note - ' . htmlspecialchars($noteDate) . '">';
                                                echo 'Note';
                                                if ($noteDate) {
                                                    echo '<br><small style="font-size: 9px; opacity: 0.8;">' . htmlspecialchars($noteDate) . '</small>';
                                                }
                                                echo '</button>';
                                            }
                                            echo '</div>';
                                            echo '</td>';
                                            echo '</tr>';
                                        }
                                        
                                        // No hidden rows needed - only show one row per user based on selected standard
                                    }
                                } else {
                                    // No users in database
                                    echo '<!-- No users in database -->';
                                }
                            } catch (Exception $e) {
                                    echo '<tr><td colspan="9" class="no-data-message">Error loading users: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                        }
                    } else {
                            echo '<tr><td colspan="9" class="no-data-message">Database connection failed.</td></tr>';
                    }
                        ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>

    <!-- CSV Import Modal -->
    <div id="csvImportModal" class="modal">
        <div class="modal-content csv-import-modal-content">
            <span class="close" onclick="closeCSVImportModal()">&times;</span>
            <h2>Import Assessments from CSV</h2>
            <div style="height: calc(85vh - 120px); overflow-y: auto; padding-right: 10px;">
            
            <!-- Status Message Area -->
            <div id="csvStatusMessage" style="display: none; margin-bottom: 20px; padding: 15px; border-radius: 8px; font-weight: 600;"></div>
            
            <div class="csv-import-info">
                <div style="background-color: rgba(233, 141, 124, 0.2); border: 2px solid var(--color-danger); border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <h4 style="color: var(--color-danger); margin: 0 0 10px 0;">⚠️ CRITICAL: EXACT FORMAT REQUIRED</h4>
                            <p style="margin: 0; color: var(--color-danger); font-weight: 600;">CSV data MUST use exact municipality/barangay names and formats from the mobile app. Any deviation will cause validation errors.</p>
                        </div>
                    </div>
                </div>
                
                <h4>📋 CSV Import Instructions</h4>
                <p><strong>1.</strong> Download the template CSV file with exact column names and order</p>
                <p><strong>2.</strong> Name: Full name of the person (required field)</p>
                <p><strong>3.</strong> Email: Valid email address (required field) - used as primary key</p>
                <p><strong>4.</strong> Password: At least 6 characters long (required field)</p>
                <p><strong>5.</strong> Use exact municipality names: ABUCAY, BAGAC, CITY OF BALANGA, DINALUPIHAN, HERMOSA, LIMAY, MARIVELES, MORONG, ORANI, ORION, PILAR, SAMAL</p>
                <p><strong>6.</strong> Use exact barangay names that match the selected municipality</p>
                <p><strong>7.</strong> Sex must be exactly "Male" or "Female" (no "Other")</p>
                <p><strong>8.</strong> Pregnancy status: "Yes", "No", or leave empty (for males or not applicable)</p>
                <p><strong>9.</strong> Weight: 0.1-1000 kg (max 2 decimal places), Height: 1-300 cm (max 2 decimal places)</p>
                <p><strong>10.</strong> Date format: YYYY-MM-DD for birthday, YYYY-MM-DD HH:MM:SS for screening_date</p>
                <p><strong>11.</strong> Template format must match exactly - no extra columns, no missing columns</p>
                <p><strong>12.</strong> Upload your completed CSV file and review the preview</p>
                <p><strong>13.</strong> Click Import CSV to process the data</p>
            </div>
            
            <form id="csvImportForm">
                <div class="csv-upload-area" id="uploadArea" onclick="document.getElementById('csvFile').click()" style="cursor: pointer;" 
                     ondragover="handleDragOver(event)" 
                     ondrop="handleDrop(event)" 
                     ondragleave="handleDragLeave(event)">
                    <input type="file" id="csvFile" accept=".csv" style="display: none;" onchange="handleFileSelect(event)">
                    <div class="upload-text">
                        <h4>Upload CSV File</h4>
                        <p>Click here or drag and drop your CSV file</p>
                        <small class="csv-format">Supported format: .csv</small>
                    </div>
                </div>
                
                <div id="csvPreview" style="display: none;"></div>
                
                <div class="csv-actions">
                    <button type="button" class="btn btn-submit" id="importCSVBtn" disabled onclick="processCSVImport()">📥 Import CSV</button>
                    <button type="button" class="btn btn-cancel" id="cancelBtn" style="display: none;" onclick="cancelUpload()">❌ Cancel Upload</button>
                </div>
            </form>
            </div>
        </div>
    </div>

    <script>
        // ===== MODERN 2025 NAVIGATION SYSTEM =====
        // Navigation state management
        let navState = {
            isMobile: window.innerWidth <= 768,
            isHovered: false
        };

        // DOM elements
        const navbar = document.querySelector('.navbar');
        const mobileTopNav = document.querySelector('.mobile-top-nav');
        const body = document.body;

        // Initialize navigation
        function initNavigation() {
            console.log('🚀 Initializing Navigation System...');
            
            // Check initial screen size
            navState.isMobile = window.innerWidth <= 768;
            console.log('📱 Mobile mode:', navState.isMobile);
            
            updateNavbarState();
            setupEventListeners();
            
            console.log('✅ Navigation system initialized');
        }

        // Setup event listeners
        function setupEventListeners() {
            // Desktop hover events
            if (navbar) {
                navbar.addEventListener('mouseenter', () => {
                    if (!navState.isMobile) {
                        navState.isHovered = true;
                        updateBodyPadding();
                    }
                });

                navbar.addEventListener('mouseleave', () => {
                    if (!navState.isMobile) {
                        navState.isHovered = false;
                        updateBodyPadding();
                    }
                });
            }

            // Window resize handler
            window.addEventListener('resize', handleResize);
        }

        // Update navbar state
        function updateNavbarState() {
            if (navState.isMobile) {
                // Mobile: show top nav, hide sidebar
                if (navbar) navbar.style.display = 'none';
                if (mobileTopNav) mobileTopNav.style.display = 'block';
                body.style.paddingLeft = '0';
                body.style.paddingTop = '60px';
            } else {
                // Desktop: show sidebar, hide top nav
                if (navbar) navbar.style.display = 'flex';
                if (mobileTopNav) mobileTopNav.style.display = 'none';
                body.style.paddingTop = '0';
                updateBodyPadding();
            }
        }

        // Update body padding for desktop hover effect
        function updateBodyPadding() {
            if (!navState.isMobile) {
                if (navState.isHovered) {
                    body.style.paddingLeft = '320px'; // Expanded navbar width
                } else {
                    body.style.paddingLeft = '40px'; // Minimized navbar width
                }
                
                // Update WHO standard buttons responsiveness based on available space
                updateWHOButtonsResponsiveness();
            }
        }
        
        function updateWHOButtonsResponsiveness() {
            const whoButtons = document.querySelector('.who-standard-buttons');
            if (whoButtons) {
                // Add class to indicate navbar state for additional CSS targeting
                if (navState.isHovered) {
                    whoButtons.classList.add('navbar-expanded');
                    whoButtons.classList.remove('navbar-collapsed');
                } else {
                    whoButtons.classList.add('navbar-collapsed');
                    whoButtons.classList.remove('navbar-expanded');
                }
            }
        }

        // Handle window resize
        function handleResize() {
            const wasMobile = navState.isMobile;
            navState.isMobile = window.innerWidth <= 768;
            
            if (wasMobile !== navState.isMobile) {
                console.log('📱 Screen size changed. Mobile mode:', navState.isMobile);
                updateNavbarState();
            }
        }

        // Debug: Test function to verify viewUserDetails is accessible
        window.testViewUserDetails = function() {
            console.log('🧪 Testing viewUserDetails function...');
            console.log('   - Function exists:', typeof viewUserDetails);
            console.log('   - Function definition:', viewUserDetails);
            if (typeof viewUserDetails === 'function') {
                console.log('✅ viewUserDetails function is accessible');
                console.log('💡 You can test it by running: viewUserDetails(1)');
            } else {
                console.error('❌ viewUserDetails function is NOT accessible');
            }
        };

        // Debug: Check if viewUserDetails function exists
        console.log('🔧 Checking viewUserDetails function:', typeof viewUserDetails);
        console.log('💡 Run testViewUserDetails() in console to test the function');
        
        // Debug: Add click event listeners to all view buttons
        document.addEventListener('DOMContentLoaded', function() {
            console.log('📋 DOM loaded, setting up view button debugging...');
            
            try {
                const viewButtons = document.querySelectorAll('.btn-view');
                console.log('🔍 Found', viewButtons.length, 'view buttons');
                
                if (viewButtons.length === 0) {
                    console.warn('⚠️ No view buttons found! Check if table is rendered correctly.');
                    console.log('🔍 Looking for table rows...');
                    const tableRows = document.querySelectorAll('.user-table tbody tr');
                    console.log('   - Found', tableRows.length, 'table rows');
                }
                
                viewButtons.forEach((button, index) => {
                    console.log(`🔘 View button ${index + 1}:`, button);
                    console.log(`   - onclick attribute:`, button.getAttribute('onclick'));
                    console.log(`   - has click listeners:`, button.onclick !== null);
                    console.log(`   - parent element:`, button.parentElement);
                    
                        // Add additional click listener for debugging
                        button.addEventListener('click', function(e) {
                            console.log('🎯 Click event fired on view button:', e.target);
                            console.log('   - Button text:', e.target.textContent);
                            console.log('   - onclick attribute:', e.target.getAttribute('onclick'));
                            console.log('   - Button position:', e.target.getBoundingClientRect());
                            console.log('   - Button z-index:', window.getComputedStyle(e.target).zIndex);
                            console.log('   - Button pointer-events:', window.getComputedStyle(e.target).pointerEvents);
                            console.log('   - Parent container:', e.target.parentElement);
                            console.log('   - Parent z-index:', window.getComputedStyle(e.target.parentElement).zIndex);
                            console.log('   - Parent overflow:', window.getComputedStyle(e.target.parentElement).overflow);
                            
                            // Try to manually execute the onclick if it exists
                            const onclickAttr = e.target.getAttribute('onclick');
                            if (onclickAttr) {
                                console.log('🔧 Attempting to execute onclick manually...');
                                try {
                                    eval(onclickAttr);
                                } catch (error) {
                                    console.error('❌ Error executing onclick:', error);
                                }
                            }
                        });
                });
                
                // Test if viewUserDetails function is available
                console.log('🧪 Testing viewUserDetails availability...');
                if (typeof window.viewUserDetails === 'function') {
                    console.log('✅ viewUserDetails is available on window object');
                } else if (typeof viewUserDetails === 'function') {
                    console.log('✅ viewUserDetails is available in local scope');
                } else {
                    console.error('❌ viewUserDetails function is NOT available');
                }
                
            } catch (error) {
                console.error('❌ Error in view button debugging setup:', error);
            }
        });

        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                initNavigation();
                updateWHOButtonsResponsiveness();
            });
        } else {
            initNavigation();
            updateWHOButtonsResponsiveness();
        }

        // Municipalities and Barangays data
        const municipalities = <?php echo json_encode($municipalities); ?>;

        // Filter functions - defined early to avoid reference errors
        function filterByMunicipality() {
            const municipality = document.getElementById('municipalityFilter').value;
            const barangayFilter = document.getElementById('barangayFilter');
            
            // Clear barangay filter when municipality changes
            barangayFilter.innerHTML = '<option value="">All Barangays</option>';
            
            if (municipality) {
                // Populate barangay options based on selected municipality
                const barangayOptions = getBarangayOptions(municipality);
                barangayOptions.forEach(barangay => {
                    const option = document.createElement('option');
                    option.value = barangay;
                    option.textContent = barangay;
                    barangayFilter.appendChild(option);
                });
            }
            
            applyAllFilters();
        }
        
        function filterByBarangay() {
            applyAllFilters();
        }
        

        
        function filterBySex() {
            applyAllFilters();
        }
        
        function getBarangayOptions(municipality) {
            const barangayData = {
                'ABUCAY': ['Bangkal', 'Calaylayan (Pob.)', 'Capitangan', 'Gabon', 'Laon (Pob.)', 'Mabatang', 'Poblacion', 'Saguing', 'Salapungan', 'Tala'],
                'BAGAC': ['Bagumbayan (Pob.)', 'Banawang', 'Binuangan', 'Binukawan', 'Ibaba', 'Ibayo', 'Paysawan', 'Quinaoayanan', 'San Antonio', 'Saysain', 'Sibucao', 'Tabing-Ilog', 'Tipo', 'Tugatog', 'Wawa'],
                'CITY OF BALANGA': ['Bagumbayan', 'Cabog-Cabog', 'Munting Batangas (Cadre)', 'Cataning', 'Central', 'Cupang Proper', 'Cupang West', 'Dangcol (Bernabe)', 'Ibayo', 'Malabia', 'Poblacion', 'Pto. Rivas Ibaba', 'Pto. Rivas Itaas', 'San Jose', 'Sibacan', 'Camacho', 'Talisay', 'Tanato', 'Tenejero', 'Tortugas', 'Tuyo', 'Bagong Silang', 'Cupang North', 'Doña Francisca', 'Lote'],
                'DINALUPIHAN': ['Bangal', 'Bonifacio (Pob.)', 'Burgos (Pob.)', 'Colo', 'Daang Bago', 'Dalao', 'Del Pilar', 'General Luna', 'Governor Generoso', 'Hacienda', 'Jose Abad Santos (Pob.)', 'Kataasan', 'Layac', 'Lourdes', 'Mabini', 'Maligaya', 'Naparing', 'Paco', 'Pag-asa', 'Pagalanggang', 'Panggalan', 'Pinulot', 'Poblacion', 'Rizal', 'Saguing', 'San Benito', 'San Isidro', 'San Ramon', 'Santo Cristo', 'Sapang Balas', 'Sumalo', 'Tipo', 'Tuklasan', 'Turac', 'Zamora'],
                'HERMOSA': ['A. Rivera (Pob.)', 'Almacen', 'Bacong', 'Balsic', 'Bamban', 'Burgos-Soliman (Pob.)', 'Cataning (Pob.)', 'Culong', 'Daungan (Pob.)', 'Judicial (Pob.)', 'Mabiga', 'Mabuco', 'Maite', 'Palihan', 'Pandatung', 'Pulong Gubat', 'San Pedro (Pob.)', 'Santo Cristo (Pob.)', 'Sumalo', 'Tipo'],
                'LIMAY': ['Alangan', 'Kitang I', 'Kitang 2 & Luz', 'Lamao', 'Landing', 'Poblacion', 'Reforma', 'San Francisco de Asis', 'Townsite'],
                'MARIVELES': ['Alas-asin', 'Alion', 'Batangas II', 'Cabcaben', 'Lucanin', 'Mabayo', 'Malaya', 'Maligaya', 'Mountain View', 'Poblacion', 'San Carlos', 'San Isidro', 'San Nicolas', 'San Pedro', 'Saysain', 'Sisiman', 'Tukuran'],
                'MORONG': ['Binaritan', 'Mabayo', 'Nagbalayong', 'Poblacion', 'Sabang', 'San Pedro', 'Sitio Liyang'],
                'ORANI': ['Apolinario (Pob.)', 'Bagong Paraiso', 'Balut', 'Bayan (Pob.)', 'Calero (Pob.)', 'Calutit', 'Camachile', 'Del Pilar', 'Kaparangan', 'Mabatang', 'Maria Fe', 'Pagtakhan', 'Paking-Carbonero (Pob.)', 'Pantalan Bago (Pob.)', 'Pantalan Luma (Pob.)', 'Parang', 'Poblacion', 'Rizal (Pob.)', 'Sagrada', 'San Jose', 'Sibul', 'Sili', 'Sulong', 'Tagumpay', 'Tala', 'Talimundoc', 'Tugatog', 'Wawa'],
                'ORION': ['Arellano (Pob.)', 'Bagumbayan (Pob.)', 'Balagtas (Pob.)', 'Balut (Pob.)', 'Bantan', 'Bilolo', 'Calungusan', 'Camachile', 'Daang Bago', 'Daan Bago', 'Daan Bilolo', 'Daan Pare', 'General Lim (Kaput)', 'Kaput', 'Lati', 'Lusung', 'Puting Buhangin', 'Sabatan', 'San Vicente', 'Santa Elena', 'Santo Domingo', 'Villa Angeles', 'Wakas'],
                'PILAR': ['Ala-uli', 'Bagumbayan', 'Balut I', 'Balut II', 'Bantan Munti', 'Bantan', 'Burgos', 'Del Rosario', 'Diwa', 'Landing', 'Liwa', 'Nueva Vida', 'Panghulo', 'Pantingan', 'Poblacion', 'Rizal', 'Sagrada', 'San Nicolas', 'San Pedro', 'Santo Niño', 'Wakas'],
                'SAMAL': ['East Calaguiman (Pob.)', 'East Daang Bago (Pob.)', 'Ibaba (Pob.)', 'Imelda', 'Lalawigan', 'Palili', 'San Juan', 'San Roque', 'Santa Lucia', 'Santo Niño', 'West Calaguiman (Pob.)', 'West Daang Bago (Pob.)']
            };
            
            return barangayData[municipality] || [];
        }
        
        function applyAllFilters() {
            const municipality = document.getElementById('municipalityFilter').value;
            const barangay = document.getElementById('barangayFilter').value;
            const sex = document.getElementById('sexFilter').value;
            const standard = document.getElementById('standardFilter').value;
            const classification = document.getElementById('classificationFilter').value;
            const risk = document.getElementById('riskFilter') ? document.getElementById('riskFilter').value : '';
            const location = document.getElementById('locationFilter') ? document.getElementById('locationFilter').value : '';
            
            console.log('Applying filters:', { municipality, barangay, sex, standard });
            
            const tableRows = document.querySelectorAll('.user-table tbody tr');
            let visibleCount = 0;
            
            tableRows.forEach((row, index) => {
                let showRow = true;
                
                // Debug: Log first few rows
                if (index < 3) {
                    console.log(`Row ${index}:`, {
                        municipality: row.dataset.municipality,
                        barangay: row.dataset.barangay,
                        sex: row.dataset.sex,
                        ageMonths: row.dataset.ageMonths,
                        standard: row.dataset.standard
                    });
                }
                
                // Debug: Log all unique municipalities
                if (index === 0) {
                    const allMunicipalities = Array.from(tableRows).map(row => row.dataset.municipality).filter(Boolean);
                    const uniqueMunicipalities = [...new Set(allMunicipalities)];
                    console.log('All municipalities in data:', uniqueMunicipalities);
                }
                
                // Municipality filter
                if (municipality && showRow) {
                    const rowMunicipality = row.dataset.municipality;
                    if (rowMunicipality !== municipality) {
                        showRow = false;
                    }
                }
                
                // Barangay filter
                if (barangay && showRow) {
                    const rowBarangay = row.dataset.barangay;
                    if (rowBarangay !== barangay) {
                        showRow = false;
                    }
                }
                
                
                // Sex filter
                if (sex && showRow) {
                    const rowSex = row.dataset.sex;
                    if (rowSex !== sex) {
                        showRow = false;
                    }
                    if (index < 3) {
                        console.log(`Sex filter: looking for "${sex}", found "${rowSex}", showRow: ${showRow}`);
                    }
                }
                
                // Standard filter - updated to handle all ages
                if (standard && showRow) {
                    const rowStandard = row.dataset.standard;
                    const ageMonths = parseInt(row.dataset.ageMonths);
                    
                    if (standard === 'all-ages') {
                        // Show all standards for all ages
                        showRow = true;
                    } else if (standard === 'bmi-for-age') {
                        // BMI-for-Age: 5-19 years (60-228 months)
                        if (ageMonths < 60 || ageMonths >= 228 || rowStandard !== 'bmi-for-age') {
                            showRow = false;
                        }
                    } else if (standard === 'bmi-adult') {
                        // BMI Adult: ≥19 years (228+ months)
                        if (ageMonths < 228 || rowStandard !== 'bmi-adult') {
                            showRow = false;
                        }
                    } else {
                        // For specific WHO standards, check age and height restrictions
                        if (standard === 'weight-for-age' || standard === 'height-for-age') {
                            // These are only for children 0-71 months
                            if (ageMonths > 71 || rowStandard !== standard) {
                                showRow = false;
                            }
                        } else if (standard === 'weight-for-height') {
                            // Weight-for-Height: 0-60 months (0-5 years) - for acute malnutrition assessment
                            if (ageMonths > 60 || rowStandard !== standard) {
                                showRow = false;
                            }
                        } else if (standard === 'bmi-for-age') {
                            // BMI-for-Age: 5-19 years (60-228 months)
                            if (ageMonths < 60 || ageMonths >= 228 || rowStandard !== standard) {
                                showRow = false;
                            }
                        } else {
                            // Default: exact match
                            if (rowStandard !== standard) {
                                showRow = false;
                            }
                        }
                    }
                }
                
                // Classification filter
                if (classification && showRow) {
                    // Classification is always in column 8 (index 8)
                    const rowClassification = row.cells[8].textContent.trim();
                    
                    if (rowClassification !== classification) {
                        showRow = false;
                    }
                }
                
                // Risk filter (check if any WHO Growth Standard column contains the risk term)
                if (risk && showRow) {
                    const wfa = row.cells[2].textContent.trim();
                    const hfa = row.cells[3].textContent.trim();
                    const wfh = row.cells[4].textContent.trim();
                    const wfl = row.cells[5].textContent.trim();
                    const bmi = row.cells[6].textContent.trim();
                    
                    const matchesRisk = wfa.includes(risk) || 
                                      hfa.includes(risk) || 
                                      wfh.includes(risk) || 
                                      wfl.includes(risk) || 
                                      bmi.includes(risk);
                    
                    if (!matchesRisk) {
                        showRow = false;
                    }
                }
                
                // Location filter (check municipality or barangay)
                if (location && showRow) {
                    const rowMunicipality = row.dataset.municipality || '';
                    const rowBarangay = row.dataset.barangay || '';
                    
                    const matchesLocation = rowMunicipality.toLowerCase().includes(location.toLowerCase()) ||
                                          rowBarangay.toLowerCase().includes(location.toLowerCase());
                    
                    if (!matchesLocation) {
                        showRow = false;
                    }
                }
                
                if (showRow) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            updateNoDataMessage(visibleCount);
        }

        // Initialize screening page
        document.addEventListener('DOMContentLoaded', function() {
            initializeTableFunctionality();
            updateTableHeaders(); // Initialize table headers on page load
            updateClassificationOptions(); // Initialize classification dropdown based on default standard
            filterByStandard(); // Initialize with default WHO standard (weight-for-age)
            
            // Auto-set municipality for non-super admins
            <?php if (!isset($_SESSION['admin_id']) || $_SESSION['admin_id'] !== 'super_admin'): ?>
            const municipalityValue = <?php echo json_encode($user_municipality); ?>;
            if (municipalityValue) {
                const municipalityFilter = document.getElementById('municipalityFilter');
                if (municipalityFilter) {
                    municipalityFilter.value = municipalityValue;
                    filterByMunicipality(); // Trigger the filter
                    console.log('🏛️ Auto-selected municipality for user:', municipalityValue);
                }
            }
            <?php endif; ?>
        });



        function viewAssessmentDetails(id) {
            // Fetch assessment details via AJAX
                            fetch(`/api/DatabaseAPI.php?action=comprehensive_screening&screening_id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert('Error loading assessment details: ' + data.error);
                        return;
                    }
                    showAssessmentModal(data);
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading assessment details');
                });
        }

        function viewUserDetails(userEmail) {
            console.log('🔍 ViewUserDetails function called!');
            console.log('   - userEmail parameter:', userEmail);
            console.log('   - typeof userEmail:', typeof userEmail);
            
            // Validate userEmail
            if (!userEmail || userEmail === 'undefined' || userEmail === 'null') {
                console.error('❌ Invalid userEmail:', userEmail);
                alert('Error: Invalid user email');
                return;
            }

            // Show loading indicator
            const loadingModal = document.createElement('div');
            loadingModal.className = 'modal';
            loadingModal.style.display = 'block';
            loadingModal.innerHTML = `
                <div class="modal-content" style="text-align: center; padding: 40px;">
                    <h3>Loading user details...</h3>
                    <div style="margin: 20px 0;">
                        <div style="border: 4px solid #f3f3f3; border-top: 4px solid var(--color-highlight); border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto;"></div>
                    </div>
                </div>
            `;
            document.body.appendChild(loadingModal);

            console.log('📡 Fetching user details from API...');
            
        // Fetch user details via AJAX using email as identifier
        fetch('api/DatabaseAPI.php?action=get_community_user_data', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ email: userEmail })
        })
            .then(response => {
                    console.log('📥 API Response received:', response.status, response.statusText);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    return response.json();
                })
                    .then(data => {
                        console.log('📋 User data received:', data);
                        
                        // Remove loading modal
                        loadingModal.remove();
                        
                        if (!data.success) {
                            console.error('❌ API Error:', data.message);
                            alert('Error loading user details: ' + data.message);
                            return;
                        }
                        
                        if (!data.user || Object.keys(data.user).length === 0) {
                            console.error('❌ Empty data received');
                            alert('Error: No user data received');
                            return;
                        }
                        
                        console.log('✅ Showing user modal...');
                        showUserDetailsModal(data.user);
                    })
                .catch(error => {
                    console.error('❌ Fetch Error:', error);
                    
                    // Remove loading modal
                    loadingModal.remove();
                    
                    alert('Error loading user details: ' + error.message);
                });
        }

        function showAssessmentModal(assessment) {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>MHO Nutritional Assessment - ${assessment.municipality}, ${assessment.barangay}</h3>
                        <span class="close" onclick="this.closest('.modal').remove()">&times;</span>
                    </div>
                    <div class="assessment-details">
                        <div class="detail-section">
                            <h4>📊 Basic Information</h4>
                            <div class="detail-item">
                                <span class="detail-label">Age:</span>
                                <span class="detail-value">${assessment.age} years${assessment.age_months ? `, ${assessment.age_months} months` : ''}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Sex:</span>
                                <span class="detail-value">${assessment.sex}</span>
                            </div>
                            ${assessment.pregnant ? `
                            <div class="detail-item">
                                <span class="detail-label">Pregnant:</span>
                                <span class="detail-value">${assessment.pregnant}</span>
                            </div>
                            ` : ''}
                        </div>
                        
                        <div class="detail-section">
                            <h4>📏 Anthropometric Data</h4>
                            <div class="detail-item">
                                <span class="detail-label">Weight:</span>
                                <span class="detail-value">${assessment.weight} kg</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Height:</span>
                                <span class="detail-value">${assessment.height} cm</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">BMI:</span>
                                <span class="detail-value">${assessment.bmi} (${getBMICategory(assessment.bmi)})</span>
                            </div>
                        </div>
                        
                        <div class="detail-section">
                            <h4>⚠️ Decision Tree Assessment</h4>
                            <div class="detail-item">
                                <span class="detail-label">Decision Tree Score:</span>
                                <span class="detail-value">${assessment.risk_score}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Nutritional Risk Level:</span>
                                <span class="detail-value">${getRiskLevel(assessment.risk_score)}</span>
                            </div>
                        </div>
                        
                        ${assessment.meal_recall ? `
                        <div class="detail-section">
                            <h4>🍽️ Meal Assessment</h4>
                            <div class="detail-item">
                                <span class="detail-label">24-Hour Recall:</span>
                                <span class="detail-value">${assessment.meal_recall}</span>
                            </div>
                        </div>
                        ` : ''}
                        
                        ${assessment.family_history ? `
                        <div class="detail-section">
                            <h4>👨‍👩‍👧‍👦 Family History</h4>
                            <div class="detail-item">
                                <span class="detail-label">Conditions:</span>
                                <span class="detail-value">${JSON.parse(assessment.family_history).join(', ')}</span>
                            </div>
                        </div>
                        ` : ''}
                        
                        <div class="detail-section">
                            <h4>🏃‍♀️ Lifestyle</h4>
                            <div class="detail-item">
                                <span class="detail-label">Activity Level:</span>
                                <span class="detail-value">${assessment.lifestyle}${assessment.lifestyle_other ? ` - ${assessment.lifestyle_other}` : ''}</span>
                            </div>
                        </div>
                        
                        ${assessment.assessment_summary ? `
                        <div class="detail-section" style="grid-column: 1 / -1;">
                            <h4>📋 Assessment Summary</h4>
                            <div class="detail-item">
                                <span class="detail-value">${assessment.assessment_summary}</span>
                            </div>
                        </div>
                        ` : ''}
                        
                        ${assessment.recommendations ? `
                        <div class="detail-section" style="grid-column: 1 / -1;">
                            <h4>💡 Recommendations</h4>
                            <div class="detail-item">
                                <span class="detail-value">${assessment.recommendations}</span>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            modal.style.display = 'block';
            
            // Close modal when clicking outside
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }

        function showUserDetailsModal(userData) {
            // Remove any existing modals first to prevent duplicates
            const existingModals = document.querySelectorAll('.user-profile-modal');
            existingModals.forEach(modal => modal.remove());
            
            // Calculate age from birthday if available
            let ageDisplay = 'N/A';
            if (userData.birthday) {
                const birthDate = new Date(userData.birthday);
                const today = new Date();
                const age = Math.floor((today - birthDate) / (365.25 * 24 * 60 * 60 * 1000));
                ageDisplay = `${age} years old`;
            }

            // Calculate BMI if weight and height are available
            let bmiDisplay = 'N/A';
            if (userData.weight && userData.height) {
                const heightInM = userData.height / 100;
                const bmi = (userData.weight / (heightInM * heightInM)).toFixed(1);
                bmiDisplay = bmi;
            }

            // Format dates
            const formatDate = (dateString) => {
                if (!dateString) return 'N/A';
                const date = new Date(dateString);
                return date.toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });
            };

            // Get pregnancy status display
            const getPregnancyStatus = (isPregnant) => {
                if (isPregnant === 'Yes') return 'Pregnant';
                if (isPregnant === '0' || isPregnant === 0) return 'Not Pregnant';
                return isPregnant || 'N/A';
            };

            // Format screening date for subtitle
            const formatScreeningDate = (dateString) => {
                if (!dateString) return '';
                const date = new Date(dateString);
                return `Screened on ${date.toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                })}`;
            };

            const modal = document.createElement('div');
            modal.className = 'modal user-profile-modal';
            modal.innerHTML = `
                <div class="modal-content profile-modal-content">
                    <button class="profile-close-btn" onclick="closeUserModal(this)">
                        X
                    </button>
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <div class="avatar-circle">
                                <span class="avatar-initials">${(userData.name || 'U').charAt(0).toUpperCase()}</span>
                            </div>
                        </div>
                        <div class="profile-title">
                            <h2>${userData.name || 'Unknown User'}</h2>
                            <p class="profile-subtitle">${userData.email || 'No email provided'}</p>
                            ${userData.screening_date ? `<p class="profile-screening-date">${formatScreeningDate(userData.screening_date)}</p>` : ''}
                            <div class="profile-badges">
                                <span class="badge ${userData.sex === 'Male' ? 'badge-blue' : 'badge-pink'}">${userData.sex || 'N/A'}</span>
                                ${userData.is_pregnant === 'Yes' ? '<span class="badge badge-orange">Pregnant</span>' : ''}
                            </div>
                        </div>
                        <div class="profile-header-buttons">
                            <button class="profile-action-btn profile-food-btn" onclick="viewFoodHistory('${userData.email}', '${userData.name}');" title="View Food History">
                                🍽️ Food History
                            </button>
                            ${userData.notes && userData.notes.trim() ? 
                                `<button class="profile-action-btn profile-note-btn" onclick="closeUserModal(this); addUserNote('${userData.email}', '${userData.name}');" title="View/Edit Note">
                                    Note
                                </button>` : 
                                `<button class="profile-action-btn profile-add-note-btn" onclick="closeUserModal(this); addUserNote('${userData.email}', '${userData.name}');" title="Add Note">
                                    Add Note
                                </button>`
                            }
                            <button class="profile-action-btn profile-flag-btn ${userData.is_flagged == 1 ? 'flagged' : ''}" onclick="toggleUserFlag('${userData.email}', '${userData.name}', ${userData.is_flagged == 1 ? 'true' : 'false'}, this);" title="${userData.is_flagged == 1 ? 'Unflag User' : 'Flag User'}">
                                ${userData.is_flagged == 1 ? 'Unflag' : 'Flag'}
                            </button>
                        </div>
                    </div>
                    
                    <div class="profile-content">
                        <div class="profile-grid">
                            <!-- Personal Information Card -->
                            <div class="profile-card">
                                <div class="card-header">
                                    <h3>Personal Information</h3>
                                </div>
                                <div class="card-content">
                                    <div class="info-row">
                                        <span class="info-label">Full Name</span>
                                        <span class="info-value">${userData.name || 'N/A'}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Email Address</span>
                                        <span class="info-value">${userData.email || 'N/A'}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Date of Birth</span>
                                        <span class="info-value">${formatDate(userData.birthday)}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Age</span>
                                        <span class="info-value">${ageDisplay}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Sex</span>
                                        <span class="info-value">${userData.sex || 'N/A'}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Weight</span>
                                        <span class="info-value">${userData.weight ? `${userData.weight} kg` : 'N/A'}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Height</span>
                                        <span class="info-value">${userData.height ? `${userData.height} cm` : 'N/A'}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Pregnancy Status</span>
                                        <span class="info-value">${getPregnancyStatus(userData.is_pregnant)}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Location Information Card -->
                            <div class="profile-card">
                                <div class="card-header">
                                    <h3>Location Details</h3>
                                </div>
                                <div class="card-content">
                                    <div class="info-row">
                                        <span class="info-label">Municipality</span>
                                        <span class="info-value">${userData.municipality || 'N/A'}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Barangay</span>
                                        <span class="info-value">${userData.barangay || 'N/A'}</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            modal.style.display = 'block';
            
            // Add animation
            setTimeout(() => {
                modal.classList.add('modal-show');
            }, 10);
            
            // Close modal when clicking outside
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeUserModal(modal.querySelector('.profile-close-btn'));
                }
            });

            // Close with Escape key
            const handleEscape = (e) => {
                if (e.key === 'Escape') {
                    closeUserModal(modal.querySelector('.profile-close-btn'));
                    document.removeEventListener('keydown', handleEscape);
                }
            };
            document.addEventListener('keydown', handleEscape);
        }

        function closeUserModal(button) {
            const modal = button.closest('.modal');
            if (modal) {
                modal.classList.add('modal-hide');
                setTimeout(() => {
                    modal.remove();
                }, 300);
            }
        }

        // BMI calculations are now handled by WHO Growth Standards PHP backend
        // This function is kept for compatibility but should not be used
        function getBMICategory(bmi) {
            // All BMI calculations are now done by who_growth_standards.php
            return 'N/A';
        }

        function getRiskLevel(score) {
            if (score <= 10) return 'Low';
            if (score <= 20) return 'Medium';
            return 'High';
        }

        // Search and filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing filters...');
            
            // Add real-time search functionality
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                console.log('Search input found, adding event listener');
                searchInput.addEventListener('input', searchAssessments);
            } else {
                console.error('Search input not found!');
            }
            
            // Test if filter elements exist
            const municipalityFilter = document.getElementById('municipalityFilter');
            const barangayFilter = document.getElementById('barangayFilter');
            const sexFilter = document.getElementById('sexFilter');
            const standardFilter = document.getElementById('standardFilter');
            
            console.log('Filter elements found:', {
                municipality: !!municipalityFilter,
                barangay: !!barangayFilter,
                sex: !!sexFilter,
                standard: !!standardFilter
            });
            
            // Initialize all filters
            console.log('Applying initial filters...');
            applyAllFilters();
            
        });

        function filterAssessments() {
            const searchTerm = document.getElementById('searchAssessments').value.toLowerCase();
            const riskFilter = document.getElementById('filterRisk').value;
            const rows = document.querySelectorAll('.assessment-row');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const riskScore = parseInt(row.dataset.risk);
                
                let showRow = true;
                
                // Search filter
                if (searchTerm && !text.includes(searchTerm)) {
                    showRow = false;
                }
                
                // Risk filter
                if (riskFilter) {
                    if (riskFilter === 'low' && riskScore > 10) showRow = false;
                    if (riskFilter === 'medium' && (riskScore <= 10 || riskScore > 20)) showRow = false;
                    if (riskFilter === 'high' && riskScore <= 20) showRow = false;
                }
                
                row.style.display = showRow ? '' : 'none';
            });
        }

        // Theme persistence and toggle
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing theme system...');
            
            // Load saved theme from localStorage
            const savedTheme = localStorage.getItem('theme') || 'dark'; // Default to dark theme
            const body = document.body;
            const icon = document.querySelector('.new-theme-icon');
            
            console.log('Saved theme:', savedTheme);
            
            if (savedTheme === 'dark') {
                body.classList.remove('light-theme');
                body.classList.add('dark-theme');
                if (icon) icon.textContent = '🌙';
                console.log('Applied dark theme');
            } else {
                body.classList.remove('dark-theme');
                body.classList.add('light-theme');
                if (icon) icon.textContent = '☀️';
                console.log('Applied light theme');
            }
            
            // Theme toggle button event listener
            const themeToggleBtn = document.getElementById('new-theme-toggle');
            if (themeToggleBtn && !themeToggleBtn.hasAttribute('data-listener-added')) {
                themeToggleBtn.setAttribute('data-listener-added', 'true');
                console.log('Theme toggle button found, adding event listener');
                themeToggleBtn.addEventListener('click', function() {
                    console.log('Theme toggle clicked');
                    const body = document.body;
                    const icon = this.querySelector('.new-theme-icon');
                    
                    if (body.classList.contains('dark-theme')) {
                        body.classList.remove('dark-theme');
                        body.classList.add('light-theme');
                        if (icon) icon.textContent = '☀️';
                        localStorage.setItem('theme', 'light');
                        console.log('Switched to light theme');
                    } else {
                        body.classList.remove('light-theme');
                        body.classList.add('dark-theme');
                        if (icon) icon.textContent = '🌙';
                        localStorage.setItem('theme', 'dark');
                        console.log('Switched to dark theme');
                    }
                });
            } else {
                console.error('Theme toggle button not found!');
            }
        });

        // Fallback theme initialization (in case DOMContentLoaded already fired)
        function initializeTheme() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            const body = document.body;
            const icon = document.querySelector('.new-theme-icon');
            
            if (savedTheme === 'dark') {
                body.classList.remove('light-theme');
                body.classList.add('dark-theme');
                if (icon) icon.textContent = '🌙';
            } else {
                body.classList.remove('dark-theme');
                body.classList.add('light-theme');
                if (icon) icon.textContent = '☀️';
            }
            
            // Theme toggle button event listener (already added above, skipping duplicate)
        }

        // Initialize theme immediately
        initializeTheme();

        // Enhanced MHO Assessment Table JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            initializeTableFunctionality();
            // Re-initialize theme in case it wasn't applied
            initializeTheme();
        });

        function initializeTableFunctionality() {
            // Add row hover effects
            const tableRows = document.querySelectorAll('.user-table tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.01)';
                });
                
                row.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            });
        }

        function searchAssessments() {
            const searchInput = document.getElementById('searchInput');
            const searchTerm = searchInput.value.toLowerCase().trim();
            
            // If search term is empty, just apply filters
            if (!searchTerm) {
                applyAllFilters();
                return;
            }
            
            const tableRows = document.querySelectorAll('.user-table tbody tr');
            let visibleCount = 0;
            
            tableRows.forEach(row => {
                // First check if row passes current filters
                const passesFilters = checkRowFilters(row);
                
                if (!passesFilters) {
                    row.style.display = 'none';
                    return;
                }
                
                // Then check if row matches search term
                const name = row.cells[0].textContent.toLowerCase();
                const fullName = row.cells[0].getAttribute('data-full-name')?.toLowerCase() || '';
                const email = row.cells[1].textContent.toLowerCase();
                const age = row.cells[2].textContent.toLowerCase();
                const sex = row.cells[3].textContent.toLowerCase();
                const weight = row.cells[4].textContent.toLowerCase();
                const height = row.cells[5].textContent.toLowerCase();
                const bmi = row.cells[6].textContent.toLowerCase();
                const standardValue = row.cells[7].textContent.toLowerCase();
                const classification = row.cells[8].textContent.toLowerCase();
                const screeningDate = row.cells[9].textContent.toLowerCase();
                
                const matchesSearch = name.includes(searchTerm) || 
                                   fullName.includes(searchTerm) ||
                                   email.includes(searchTerm) || 
                                   age.includes(searchTerm) || 
                                   sex.includes(searchTerm) || 
                                   weight.includes(searchTerm) || 
                                   height.includes(searchTerm) || 
                                   bmi.includes(searchTerm) || 
                                   standardValue.includes(searchTerm) || 
                                   classification.includes(searchTerm) ||
                                   screeningDate.includes(searchTerm);
                
                if (matchesSearch) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            updateNoDataMessage(visibleCount);
        }
        
        // Helper function to check if a row passes current filters
        function checkRowFilters(row) {
            const municipality = document.getElementById('municipalityFilter').value;
            const barangay = document.getElementById('barangayFilter').value;
            const sex = document.getElementById('sexFilter').value;
            const standard = document.getElementById('standardFilter').value;
            
            // Municipality filter
            if (municipality) {
                const rowMunicipality = row.dataset.municipality;
                if (rowMunicipality !== municipality) {
                    return false;
                }
            }
            
            // Barangay filter
            if (barangay) {
                const rowBarangay = row.dataset.barangay;
                if (rowBarangay !== barangay) {
                    return false;
                }
            }
            
            
            // Sex filter
            if (sex) {
                const rowSex = row.dataset.sex;
                if (rowSex !== sex) {
                    return false;
                }
            }
            
            // Standard filter
            if (standard) {
                const rowStandard = row.dataset.standard;
                const ageMonths = parseInt(row.dataset.ageMonths);
                
                if (standard === 'all-ages') {
                    return true;
                } else if (standard === 'bmi-for-age') {
                    return rowStandard === 'bmi-for-age' && ageMonths >= 60 && ageMonths < 228;
                } else if (standard === 'bmi-adult') {
                    return rowStandard === 'bmi-adult' && ageMonths >= 228;
                } else {
                    if (standard === 'weight-for-age' || standard === 'height-for-age') {
                        if (ageMonths > 71 || rowStandard !== standard) {
                            return false;
                        }
                    } else if (standard === 'weight-for-height') {
                        if (ageMonths > 60 || rowStandard !== standard) {
                            return false;
                        }
                    } else {
                        if (rowStandard !== standard) {
                            return false;
                        }
                    }
                }
            }
            
            return true;
        }

        // Helper function to parse age string to months
        function parseAgeToMonths(ageString) {
            if (!ageString || ageString === 'N/A') return 0;
            
            // Parse format like "3y 0m" or "0y 9m"
            const match = ageString.match(/(\d+)y\s*(\d+)m/);
            if (match) {
                const years = parseInt(match[1]) || 0;
                const months = parseInt(match[2]) || 0;
                return (years * 12) + months;
            }
            
            // If it's just a number, assume it's months
            const num = parseInt(ageString);
            return isNaN(num) ? 0 : num;
        }

        // New sorting function
        function sortTable() {
            const sortBy = document.getElementById('sortBy').value;
            if (!sortBy) return;
            
            const table = document.querySelector('.user-table tbody');
            const rows = Array.from(table.querySelectorAll('tr'));
            
            console.log('Sorting by:', sortBy, 'Rows found:', rows.length);
            
            rows.sort((a, b) => {
                let aValue, bValue;
                
                switch(sortBy) {
                    case 'name_asc':
                        aValue = a.cells[0].textContent.trim();
                        bValue = b.cells[0].textContent.trim();
                        return aValue.localeCompare(bValue);
                    case 'name_desc':
                        aValue = a.cells[0].textContent.trim();
                        bValue = b.cells[0].textContent.trim();
                        return bValue.localeCompare(aValue);
                    case 'email_asc':
                        aValue = a.cells[1].textContent.trim();
                        bValue = b.cells[1].textContent.trim();
                        console.log('Email ASC:', aValue, 'vs', bValue);
                        return aValue.localeCompare(bValue);
                    case 'email_desc':
                        aValue = a.cells[1].textContent.trim();
                        bValue = b.cells[1].textContent.trim();
                        console.log('Email DESC:', aValue, 'vs', bValue);
                        return bValue.localeCompare(aValue);
                    case 'age_asc':
                        aValue = parseAgeToMonths(a.cells[2].textContent.trim());
                        bValue = parseAgeToMonths(b.cells[2].textContent.trim());
                        return aValue - bValue;
                    case 'age_desc':
                        aValue = parseAgeToMonths(a.cells[2].textContent.trim());
                        bValue = parseAgeToMonths(b.cells[2].textContent.trim());
                        return bValue - aValue;
                    case 'screening_date_asc':
                        aValue = new Date(a.cells[9].textContent.trim());
                        bValue = new Date(b.cells[9].textContent.trim());
                        return aValue - bValue;
                    case 'screening_date_desc':
                        aValue = new Date(a.cells[9].textContent.trim());
                        bValue = new Date(b.cells[9].textContent.trim());
                        return bValue - aValue;
                    default:
                        return 0;
                }
            });
            
            // Clear the table and re-append sorted rows
            table.innerHTML = '';
            rows.forEach(row => table.appendChild(row));
        }

        // New classification filtering function
        function filterByClassification() {
            // Use the unified filter system
            applyAllFilters();
        }

        function filterByRisk() {
            // Use the unified filter system
            applyAllFilters();
        }

        function filterByLocation() {
            // Use the unified filter system
            applyAllFilters();
        }

        function updateClassificationOptions() {
            const standardFilter = document.getElementById('standardFilter');
            const classificationFilter = document.getElementById('classificationFilter');
            
            if (!standardFilter || !classificationFilter) return;
            
            const selectedStandard = standardFilter.value;
            
            // Clear existing options
            classificationFilter.innerHTML = '<option value="">All Classifications</option>';
            
            // Add options based on selected standard
            if (selectedStandard === 'weight-for-age') {
                classificationFilter.innerHTML += `
                    <optgroup label="Weight-for-Age">
                        <option value="Severely Underweight">Severely Underweight</option>
                        <option value="Underweight">Underweight</option>
                        <option value="Normal">Normal</option>
                        <option value="Overweight">Overweight</option>
                    </optgroup>`;
            } else if (selectedStandard === 'height-for-age') {
                classificationFilter.innerHTML += `
                    <optgroup label="Height-for-Age (Stunting)">
                        <option value="Severely Stunted">Severely Stunted</option>
                        <option value="Stunted">Stunted</option>
                        <option value="Normal">Normal</option>
                        <option value="Tall">Tall</option>
                    </optgroup>`;
            } else if (selectedStandard === 'weight-for-height') {
                classificationFilter.innerHTML += `
                    <optgroup label="Weight-for-Height (Wasting)">
                        <option value="Severely Wasted">Severely Wasted</option>
                        <option value="Wasted">Wasted</option>
                        <option value="Normal">Normal</option>
                        <option value="Overweight">Overweight</option>
                        <option value="Obese">Obese</option>
                    </optgroup>`;
            } else if (selectedStandard === 'bmi-for-age') {
                classificationFilter.innerHTML += `
                    <optgroup label="BMI-for-Age">
                        <option value="Underweight">Underweight</option>
                        <option value="Normal">Normal</option>
                        <option value="Overweight">Overweight</option>
                        <option value="Obese">Obese</option>
                    </optgroup>`;
            } else if (selectedStandard === 'bmi-adult') {
                classificationFilter.innerHTML += `
                    <optgroup label="BMI Adult">
                        <option value="Underweight">Underweight</option>
                        <option value="Normal">Normal</option>
                        <option value="Overweight">Overweight</option>
                        <option value="Obese">Obese</option>
                    </optgroup>`;
            } else if (selectedStandard === 'all-ages') {
                // Show all classifications when "All Ages" is selected
                classificationFilter.innerHTML += `
                    <optgroup label="Weight-for-Age">
                        <option value="Severely Underweight">Severely Underweight</option>
                        <option value="Underweight">Underweight</option>
                        <option value="Normal">Normal</option>
                        <option value="Overweight">Overweight</option>
                    </optgroup>
                    <optgroup label="Height-for-Age (Stunting)">
                        <option value="Severely Stunted">Severely Stunted</option>
                        <option value="Stunted">Stunted</option>
                        <option value="Normal">Normal</option>
                        <option value="Tall">Tall</option>
                    </optgroup>
                    <optgroup label="Weight-for-Height (Wasting)">
                        <option value="Severely Wasted">Severely Wasted</option>
                        <option value="Wasted">Wasted</option>
                        <option value="Normal">Normal</option>
                        <option value="Overweight">Overweight</option>
                        <option value="Obese">Obese</option>
                    </optgroup>
                    <optgroup label="BMI-for-Age">
                        <option value="Underweight">Underweight</option>
                        <option value="Normal">Normal</option>
                        <option value="Overweight">Overweight</option>
                        <option value="Obese">Obese</option>
                    </optgroup>
                    <optgroup label="BMI Adult">
                        <option value="Underweight">Underweight</option>
                        <option value="Normal">Normal</option>
                        <option value="Overweight">Overweight</option>
                        <option value="Obese">Obese</option>
                    </optgroup>`;
            }
        }

        // New function to handle WHO standard button selection
        function selectWHOStandard(standardValue, buttonElement) {
            // Update button states
            document.querySelectorAll('.who-standard-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            buttonElement.classList.add('active');
            
            // Update hidden select to maintain compatibility with existing logic
            const standardFilter = document.getElementById('standardFilter');
            if (standardFilter) {
                standardFilter.value = standardValue;
                
                // Trigger the existing filter logic
                filterByStandard();
            }
        }

        function filterByStandard() {
            updateTableHeaders();
            updateTableBodyColumns();
            
            // Clear classification filter when standard changes
            const classificationFilter = document.getElementById('classificationFilter');
            if (classificationFilter) {
                classificationFilter.value = '';
            }
            
            updateClassificationOptions(); // Update classification dropdown based on standard
            
            // Show only rows for the selected WHO standard
            const standardFilter = document.getElementById('standardFilter');
            const selectedStandard = standardFilter ? standardFilter.value : 'weight-for-age';
            
            const tableRows = document.querySelectorAll('.user-table tbody tr');
            tableRows.forEach(row => {
                const rowStandard = row.dataset.standard;
                if (rowStandard === selectedStandard) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Apply other filters on the visible rows
            applyAllFilters();
        }
        
        function updateTableBodyColumns() {
            const standardFilter = document.getElementById('standardFilter');
            const selectedStandard = standardFilter ? standardFilter.value : 'all-ages';
            
            // Get all table rows
            const tableRows = document.querySelectorAll('.user-table tbody tr');
            
            tableRows.forEach(row => {
                const cells = row.querySelectorAll('td');
                
                // Show/hide conditional columns based on selected standard
                if (cells.length >= 10) { // Ensure we have enough cells (now includes email)
                    // Weight column (index 4) - shifted due to email column
                    if (selectedStandard === 'weight-for-age' || selectedStandard === 'bmi-for-age' || selectedStandard === 'bmi-adult' || selectedStandard === 'weight-for-height' || selectedStandard === 'all-ages') {
                        cells[4].style.display = '';
                    } else {
                        cells[4].style.display = 'none';
                    }
                    
                    // Height column (index 5) - shifted due to email column
                    if (selectedStandard === 'height-for-age' || selectedStandard === 'weight-for-height' || selectedStandard === 'bmi-for-age' || selectedStandard === 'bmi-adult' || selectedStandard === 'all-ages') {
                        cells[5].style.display = '';
                    } else {
                        cells[5].style.display = 'none';
                    }
                    
                    // BMI column (index 6) - shifted due to email column
                    if (selectedStandard === 'bmi-for-age' || selectedStandard === 'bmi-adult' || selectedStandard === 'all-ages') {
                        cells[6].style.display = '';
                    } else {
                        cells[6].style.display = 'none';
                    }
                }
            });
        }
        
        function updateTableHeaders() {
            const standardFilter = document.getElementById('standardFilter');
            const standardHeader = document.getElementById('standardHeader');
            const weightHeader = document.getElementById('weightHeader');
            const heightHeader = document.getElementById('heightHeader');
            const bmiHeader = document.getElementById('bmiHeader');
            
            if (standardFilter && standardHeader) {
                const selectedStandard = standardFilter.value;
                
                // Update standard header based on selected standard
                if (selectedStandard === 'bmi-for-age' || selectedStandard === 'bmi-adult') {
                    standardHeader.textContent = 'BMI';
                    standardHeader.style.display = 'none'; // Hide Z-Score column for BMI standards
                } else {
                    standardHeader.textContent = 'Z-SCORE';
                    standardHeader.style.display = ''; // Show Z-Score column for other standards
                }
                
                // Show/hide conditional columns based on selected standard
                if (weightHeader && heightHeader && bmiHeader) {
                    // Reset all columns
                    weightHeader.style.display = '';
                    heightHeader.style.display = '';
                    bmiHeader.style.display = '';
                    
                    // Show only relevant columns based on standard
                    switch(selectedStandard) {
                        case 'weight-for-age':
                            // Show weight column only
                            weightHeader.style.display = '';
                            heightHeader.style.display = 'none';
                            bmiHeader.style.display = 'none';
                            break;
                        case 'bmi-for-age':
                        case 'bmi-adult':
                            // Show weight, height, and BMI columns for BMI standards
                            weightHeader.style.display = '';
                            heightHeader.style.display = '';
                            bmiHeader.style.display = '';
                            break;
                        case 'height-for-age':
                            // Show height column only
                            weightHeader.style.display = 'none';
                            heightHeader.style.display = '';
                            bmiHeader.style.display = 'none';
                            break;
                        case 'weight-for-height':
                            // Show both weight and height columns
                            weightHeader.style.display = '';
                            heightHeader.style.display = '';
                            bmiHeader.style.display = 'none';
                            break;
                        default:
                            // Show all columns for 'all-ages'
                            weightHeader.style.display = '';
                            heightHeader.style.display = '';
                            bmiHeader.style.display = '';
                    }
                }
            }
        }

        function updateNoDataMessage(visibleCount) {
            const noDataMessage = document.querySelector('.no-data-message');
            const tbody = document.querySelector('.user-table tbody');
            
                if (visibleCount === 0) {
                    if (!noDataMessage) {
                        const message = document.createElement('tr');
                        message.className = 'no-data-message';
                        message.innerHTML = '<td colspan="10"><div>No assessments found matching your criteria.</div></td>';
                        tbody.appendChild(message);
                    }
                } else if (noDataMessage) {
                    noDataMessage.remove();
                }
        }

        function viewAssessment(id) {
            // Get assessment data (in real implementation, this would fetch from database)
            const assessmentData = getAssessmentData(id);
            showAssessmentModal(assessmentData);
        }

        function editAssessment(id) {
            // In real implementation, this would redirect to edit form
            alert(`Edit assessment ${id} - Redirecting to edit form...`);
        }

        function deleteAssessment(id) {
            if (confirm('Are you sure you want to delete this assessment?')) {
                // In real implementation, this would delete from database
                const row = document.querySelector(`tr[data-id="${id}"]`);
                if (row) {
                    row.remove();
                    alert('Assessment deleted successfully!');
                }
            }
        }

        function getAssessmentData(id) {
            // Fetch assessment data from database via API
            return fetch(`/api/nutritional_assessment_api.php?action=get_assessment&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        return data.data;
                    } else {
                        throw new Error(data.message || 'Failed to fetch assessment data');
                    }
                })
                .catch(error => {
                    console.error('Error fetching assessment data:', error);
                    throw error;
                });
        }

        function showAssessmentModal(assessment) {
            const modal = document.createElement('div');
            modal.className = 'assessment-modal';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                backdrop-filter: blur(5px);
            `;
            
            const modalContent = document.createElement('div');
            modalContent.style.cssText = `
                background: var(--color-card);
                border-radius: 16px;
                padding: 30px;
                max-width: 700px;
                width: 90%;
                max-height: 80vh;
                overflow-y: auto;
                border: 1px solid var(--color-highlight);
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
                position: relative;
            `;
            
            // Create header buttons container
            const headerButtons = document.createElement('div');
            headerButtons.style.cssText = `
                position: absolute;
                top: 15px;
                right: 20px;
                display: flex;
                gap: 10px;
                align-items: center;
            `;
            
            // Create Note button
            const noteBtn = document.createElement('button');
            noteBtn.innerHTML = 'Note';
            noteBtn.style.cssText = `
                background: #FFC107;
                color: #1B1B1B;
                border: none;
                padding: 8px 16px;
                border-radius: 6px;
                font-size: 12px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
            `;
            noteBtn.addEventListener('click', function() {
                modal.remove();
                addUserNote(userEmail, userData.name);
            });
            
            // Create Flag button
            const flagBtn = document.createElement('button');
            const isFlagged = userData.is_flagged == 1;
            flagBtn.innerHTML = isFlagged ? 'Unflag' : 'Flag';
            flagBtn.style.cssText = `
                background: ${isFlagged ? '#4CAF50' : '#FF1744'};
                color: white;
                border: none;
                padding: 8px 16px;
                border-radius: 6px;
                font-size: 12px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
            `;
            flagBtn.addEventListener('click', function() {
                toggleUserFlag(userEmail, userData.name, isFlagged);
            });

            const closeBtn = document.createElement('button');
            closeBtn.innerHTML = '✕';
            closeBtn.style.cssText = `
                background: none;
                border: none;
                color: var(--color-highlight);
                font-size: 24px;
                cursor: pointer;
                padding: 5px;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.3s ease;
            `;
            
            headerButtons.appendChild(noteBtn);
            headerButtons.appendChild(flagBtn);
            headerButtons.appendChild(closeBtn);
            
            closeBtn.addEventListener('mouseenter', function() {
                this.style.background = 'var(--color-highlight)';
                this.style.color = 'var(--color-bg)';
            });
            
            closeBtn.addEventListener('mouseleave', function() {
                this.style.background = 'none';
                this.style.color = 'var(--color-highlight)';
            });
            
            closeBtn.addEventListener('click', function() {
                modal.remove();
            });
            
            modalContent.innerHTML = `
                <h2 style="color: var(--color-highlight); margin-bottom: 25px; text-align: center; font-size: 28px;">
                    MHO Assessment Details - ${assessment.name}
                </h2>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div style="background: rgba(161, 180, 84, 0.1); padding: 15px; border-radius: 12px; border: 1px solid var(--color-border);">
                        <h3 style="color: var(--color-highlight); margin-bottom: 12px; font-size: 16px;">📋 Basic Information</h3>
                        <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>Name:</strong> ${assessment.name}</p>
                        <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>Age:</strong> ${assessment.age} years</p>
                        <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>Sex:</strong> ${assessment.sex}</p>
                        <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>Location:</strong> ${assessment.barangay}, ${assessment.municipality}</p>
                    </div>
                    
                    <div style="background: rgba(161, 180, 84, 0.1); padding: 15px; border-radius: 12px; border: 1px solid var(--color-border);">
                        <h3 style="color: var(--color-highlight); margin-bottom: 12px; font-size: 16px;">📏 Anthropometric Data</h3>
                        <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>Height:</strong> ${assessment.height} cm</p>
                        <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>Weight:</strong> ${assessment.weight} kg</p>
                        <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>BMI:</strong> ${assessment.bmi}</p>
                        <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>Risk Level:</strong> <span style="color: ${assessment.risk_level === 'Low Risk' ? '#4CAF50' : assessment.risk_level === 'Medium Risk' ? '#FF9800' : '#FF1744'}">${assessment.risk_level}</span></p>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div style="background: rgba(161, 180, 84, 0.1); padding: 15px; border-radius: 12px; border: 1px solid var(--color-border);">
                        <h3 style="color: var(--color-highlight); margin-bottom: 12px; font-size: 16px;">🍽️ Meal Assessment</h3>
                        <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>24-Hour Recall:</strong> <span style="color: ${assessment.meal_assessment === 'Balanced' ? '#4CAF50' : '#FF9800'}">${assessment.meal_assessment}</span></p>
                    </div>
                    
                    <div style="background: rgba(161, 180, 84, 0.1); padding: 15px; border-radius: 12px; border: 1px solid var(--color-border);">
                        <h3 style="color: var(--color-highlight); margin-bottom: 12px; font-size: 16px;">🏃 Lifestyle</h3>
                        <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>Activity Level:</strong> <span style="color: ${assessment.lifestyle === 'Active' ? '#4CAF50' : '#FF9800'}">${assessment.lifestyle}</span></p>
                    </div>
                </div>
                
                <div style="background: rgba(161, 180, 84, 0.1); padding: 15px; border-radius: 12px; border: 1px solid var(--color-border); margin-bottom: 20px;">
                    <h3 style="color: var(--color-highlight); margin-bottom: 12px; font-size: 16px;">👨‍👩‍👧‍👦 Family History</h3>
                    <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>Conditions:</strong> ${assessment.family_history.join(', ')}</p>
                </div>
                
                <div style="background: rgba(161, 180, 84, 0.15); padding: 15px; border-radius: 12px; border: 1px solid var(--color-highlight);">
                    <h3 style="color: var(--color-highlight); margin-bottom: 12px; font-size: 16px;">💡 Recommendations</h3>
                    <p style="color: var(--color-text); margin-bottom: 8px; font-size: 14px;"><strong>Assessment:</strong> ${assessment.recommendation}</p>
                    <p style="color: var(--color-text); margin-bottom: 0; font-size: 14px;"><strong>Intervention:</strong> ${assessment.intervention}</p>
                </div>
            `;
            
            modalContent.appendChild(headerButtons);
            modal.appendChild(modalContent);
            document.body.appendChild(modal);
            
            // Close modal when clicking outside
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }







        // CSV Functions
        function downloadCSVTemplate() {
            const csvContent = [
                ['name', 'email', 'password', 'municipality', 'barangay', 'sex', 'birthday', 'is_pregnant', 'weight', 'height', 'screening_date'],
                ['John Doe', 'john@example.com', 'password123', 'CITY OF BALANGA', 'Bagumbayan', 'Male', '1999-01-15', 'No', '70.5', '175.0', '2024-01-15 10:30:00'],
                ['Jane Smith', 'jane@example.com', 'mypass456', 'MARIVELES', 'Alion', 'Female', '1995-03-20', 'Yes', '65.2', '160.0', '2024-01-15 14:30:00']
            ];
            
            const csv = csvContent.map(row => row.map(field => `"${field}"`).join(',')).join('\n');
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'community_users_template.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(url);
        }

        function showCSVImportModal() {
            const modal = document.getElementById('csvImportModal');
            if (modal) {
                modal.style.display = 'block';
                resetCSVForm();
            }
        }

        function closeCSVImportModal() {
            const modal = document.getElementById('csvImportModal');
            if (modal) {
                modal.style.display = 'none';
                resetCSVForm();
            }
        }

        function resetCSVForm() {
            document.getElementById('csvFile').value = '';
            document.getElementById('csvPreview').style.display = 'none';
            document.getElementById('importCSVBtn').disabled = true;
            document.getElementById('cancelBtn').style.display = 'none';
            hideCSVStatus();
        }

        function handleDragOver(e) {
            e.preventDefault();
            e.stopPropagation();
            document.getElementById('uploadArea').classList.add('dragover');
        }

        function handleDragLeave(e) {
            e.preventDefault();
            e.stopPropagation();
            document.getElementById('uploadArea').classList.remove('dragover');
        }

        function handleDrop(e) {
            e.preventDefault();
            e.stopPropagation();
            document.getElementById('uploadArea').classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const file = files[0];
                if (file.type === 'text/csv' || file.name.endsWith('.csv')) {
                    document.getElementById('csvFile').files = files;
                    handleFileSelect(document.getElementById('csvFile'));
                } else {
                    showCSVStatus('error', 'Please upload a CSV file.');
                }
            }
        }

        function handleFileSelect(input) {
            const file = input.files[0];
            if (file && (file.type === 'text/csv' || file.name.endsWith('.csv'))) {
                previewCSV(file);
            } else {
                showCSVStatus('error', 'Please select a valid CSV file.');
            }
        }

        function previewCSV(file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const csv = e.target.result;
                const lines = csv.split('\n').filter(line => line.trim());
                
                if (lines.length < 2) {
                    showCSVStatus('error', 'CSV file must contain at least a header row and one data row.');
                    return;
                }

                const headers = lines[0].split(',').map(h => h.replace(/"/g, '').trim());
                const previewRows = lines.slice(1, 6);
                
                let tableHTML = '<h4>📋 Preview (First 5 rows)</h4>';
                tableHTML += '<div style="overflow-x: auto;"><table class="csv-preview-table">';
                tableHTML += '<thead><tr>';
                headers.forEach(header => {
                    tableHTML += `<th>${header}</th>`;
                });
                tableHTML += '</tr></thead><tbody>';
                
                previewRows.forEach(row => {
                    const cells = row.split(',').map(cell => cell.replace(/"/g, '').trim());
                    tableHTML += '<tr>';
                    cells.forEach(cell => {
                        tableHTML += `<td>${cell}</td>`;
                    });
                    tableHTML += '</tr>';
                });
                
                tableHTML += '</tbody></table></div>';
                
                document.getElementById('csvPreview').innerHTML = tableHTML;
                document.getElementById('csvPreview').style.display = 'block';
                document.getElementById('importCSVBtn').disabled = false;
                document.getElementById('cancelBtn').style.display = 'inline-block';
                
                showCSVStatus('success', `CSV loaded successfully! ${lines.length - 1} rows ready for import.`);
            };
            
            reader.readAsText(file);
        }

        function processCSVImport() {
            const fileInput = document.getElementById('csvFile');
            const file = fileInput.files[0];
            
            if (!file) {
                showCSVStatus('error', 'Please select a CSV file first.');
                return;
            }
            
            showCSVStatus('info', 'Processing CSV import...');
            document.getElementById('importCSVBtn').disabled = true;
            document.getElementById('importCSVBtn').innerHTML = '🔄 Processing...';
            
            const formData = new FormData();
            formData.append('csvFile', file);
            formData.append('action', 'import_community_users');
            
            fetch('api/community_users_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text(); // Get as text first
            })
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        showCSVStatus('success', `CSV imported successfully! ${data.imported_count} users imported.`);
                        // Refresh the data table
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    } else {
                        showCSVStatus('error', `Import failed: ${data.message}`);
                        if (data.errors && data.errors.length > 0) {
                            showCSVStatus('error', `Errors: ${data.errors.join(', ')}`);
                        }
                    }
                } catch (parseError) {
                    console.error('JSON Parse Error:', parseError);
                    console.error('Response text:', text);
                    showCSVStatus('error', 'Server returned invalid response. Please check server logs.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showCSVStatus('error', 'Failed to import CSV. Please try again.');
            })
            .finally(() => {
                document.getElementById('importCSVBtn').disabled = false;
                document.getElementById('importCSVBtn').innerHTML = '📥 Import CSV';
            });
        }

        function showCSVStatus(type, message) {
            const statusDiv = document.getElementById('csvStatusMessage');
            statusDiv.style.display = 'block';
            statusDiv.className = `csv-status ${type}`;
            statusDiv.textContent = message;
            
            if (type === 'success') {
                statusDiv.style.backgroundColor = 'rgba(161, 180, 84, 0.2)';
                statusDiv.style.color = 'var(--color-highlight)';
                statusDiv.style.border = '1px solid var(--color-highlight)';
            } else if (type === 'info') {
                statusDiv.style.backgroundColor = 'rgba(102, 187, 106, 0.2)';
                statusDiv.style.color = 'var(--color-highlight)';
                statusDiv.style.border = '1px solid var(--color-highlight)';
            } else {
                statusDiv.style.backgroundColor = 'rgba(233, 141, 124, 0.2)';
                statusDiv.style.color = 'var(--color-danger)';
                statusDiv.style.border = '1px solid var(--color-danger)';
            }
        }

        function hideCSVStatus() {
            const statusDiv = document.getElementById('csvStatusMessage');
            statusDiv.style.display = 'none';
        }

        function cancelUpload() {
            resetCSVForm();
        }

        // Note System Functions
        function addUserNote(userEmail, userName) {
            console.log('📝 Add Note function called for:', userEmail, userName);
            
            if (!userEmail || userEmail === 'undefined' || userEmail === 'null') {
                console.error('❌ Invalid userEmail:', userEmail);
                alert('Error: Invalid user email');
                return;
            }

            // Show edit note modal for adding new note
            showEditNoteModal(userEmail, userName, '');
        }

        function viewUserNote(userEmail, userName) {
            console.log('👁️ View Note function called for:', userEmail, userName);
            
            if (!userEmail || userEmail === 'undefined' || userEmail === 'null') {
                console.error('❌ Invalid userEmail:', userEmail);
                return;
            }

            // Fetch existing notes for this user
            fetch('api/DatabaseAPI.php?action=get_user_notes', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ email: userEmail })
            })
            .then(response => response.json())
            .then(data => {
                const existingNotes = data.success && data.notes ? data.notes : '';
                showViewNoteModal(userEmail, userName, existingNotes);
            })
            .catch(error => {
                console.error('❌ Error fetching existing notes:', error);
                showViewNoteModal(userEmail, userName, '');
            });
        }

        function showViewNoteModal(userEmail, userName, noteContent) {
            // Extract date and content from note
            let noteDate = '';
            let noteText = noteContent;
            
            if (noteContent && noteContent.match(/^\[([^\]]+)\]/)) {
                const match = noteContent.match(/^\[([^\]]+)\]\s*(.*)/);
                noteDate = match[1];
                noteText = match[2];
            }
            
            // Clean up any remaining timestamps in the note text
            if (noteText) {
                noteText = noteText.replace(/\[\d+\/\d+\/\d+, \d+:\d+:\d+ [AP]M\]/g, '').trim();
            }
            
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.style.display = 'block';
            modal.innerHTML = `
                <div class="modal-content" style="max-width: 500px; padding: 30px; border-radius: 12px; background: var(--color-card);">
                    <button class="profile-close-btn" onclick="closeNoteModal(this)" style="position: absolute; top: 15px; right: 15px;">
                        ✕
                    </button>
                    <h3 style="margin: 0 0 10px 0; color: var(--color-text); font-size: 20px;">Note</h3>
                    <p style="margin: 0 0 15px 0; color: var(--color-text); opacity: 0.8;">
                        <strong>User:</strong> ${userName} (${userEmail})
                    </p>
                    ${noteDate ? `<p style="margin: 0 0 20px 0; color: var(--color-text); opacity: 0.7; font-style: italic; font-size: 14px;">${noteDate}</p>` : ''}
                    <div style="margin-bottom: 20px; padding: 15px; background: var(--color-input); border-radius: 8px; border: 1px solid var(--color-border);">
                        <p style="margin: 0; color: var(--color-text); line-height: 1.5; white-space: pre-wrap;">${noteText || 'This user has no notes.'}</p>
                    </div>
                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button onclick="closeNoteModal(this)" 
                                style="padding: 10px 20px; border: 2px solid var(--color-border); background: transparent; 
                                       color: var(--color-text); border-radius: 8px; cursor: pointer; font-weight: 600;">
                            Close
                        </button>
                        <button onclick="closeNoteModal(this); showEditNoteModal('${userEmail}', '${userName}', '${noteContent.replace(/'/g, "\\'")}')" 
                                style="padding: 10px 20px; background: var(--color-highlight); color: white; 
                                       border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                            Edit Note
                        </button>
                        <button onclick="deleteUserNote('${userEmail}', '${userName}', this)" 
                                style="padding: 10px 20px; background: #ff4444; color: white; 
                                       border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                            Delete Note
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Close modal when clicking outside
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeNoteModal(modal.querySelector('.profile-close-btn'));
                }
            });
            
            // Close with Escape key
            const handleEscape = (e) => {
                if (e.key === 'Escape') {
                    closeNoteModal(modal.querySelector('.profile-close-btn'));
                    document.removeEventListener('keydown', handleEscape);
                }
            };
            document.addEventListener('keydown', handleEscape);
        }

        function showEditNoteModal(userEmail, userName, existingNotes) {
            // Extract content without date for editing
            let noteText = existingNotes;
            if (existingNotes && existingNotes.match(/^\[([^\]]+)\]/)) {
                const match = existingNotes.match(/^\[([^\]]+)\]\s*(.*)/);
                noteText = match[2];
            }
            
            // Clean up any remaining timestamps in the note text
            if (noteText) {
                noteText = noteText.replace(/\[\d+\/\d+\/\d+, \d+:\d+:\d+ [AP]M\]/g, '').trim();
            }
            
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.style.display = 'block';
            modal.innerHTML = `
                <div class="modal-content" style="max-width: 500px; padding: 30px; border-radius: 12px; background: var(--color-card);">
                    <button class="profile-close-btn" onclick="closeNoteModal(this)" style="position: absolute; top: 15px; right: 15px;">
                        ✕
                    </button>
                    <h3 style="margin: 0 0 20px 0; color: var(--color-text); font-size: 20px;">${existingNotes ? 'Edit Note' : 'Add Note'}</h3>
                    <p style="margin: 0 0 15px 0; color: var(--color-text); opacity: 0.8;">
                        <strong>User:</strong> ${userName} (${userEmail})
                    </p>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; color: var(--color-text); font-weight: 600;">Note:</label>
                        <textarea id="noteText" placeholder="Enter your note about this user..." 
                                style="width: 100%; height: 120px; padding: 12px; border: 2px solid var(--color-border); 
                                       border-radius: 8px; background: var(--color-input); color: var(--color-text); 
                                       font-family: inherit; font-size: 14px; resize: vertical; box-sizing: border-box;"
                                maxlength="1000">${noteText}</textarea>
                        <div style="text-align: right; margin-top: 5px; font-size: 12px; color: var(--color-text); opacity: 0.6;">
                            <span id="charCount">0</span>/1000 characters
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button onclick="closeNoteModal(this)" 
                                style="padding: 10px 20px; border: 2px solid var(--color-border); background: transparent; 
                                       color: var(--color-text); border-radius: 8px; cursor: pointer; font-weight: 600;">
                            Cancel
                        </button>
                        <button onclick="saveUserNote('${userEmail}', '${userName}')" 
                                style="padding: 10px 20px; background: var(--color-highlight); color: white; 
                                       border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                            ${existingNotes ? 'Update Note' : 'Save Note'}
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Set up character counter
            const textarea = document.getElementById('noteText');
            const charCount = document.getElementById('charCount');
            
            textarea.addEventListener('input', function() {
                charCount.textContent = this.value.length;
            });
            
            // Initialize character count
            charCount.textContent = textarea.value.length;
            
            // Close modal when clicking outside
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeNoteModal(modal.querySelector('.profile-close-btn'));
                }
            });
            
            // Close with Escape key
            const handleEscape = (e) => {
                if (e.key === 'Escape') {
                    closeNoteModal(modal.querySelector('.profile-close-btn'));
                    document.removeEventListener('keydown', handleEscape);
                }
            };
            document.addEventListener('keydown', handleEscape);
        }

        function closeNoteModal(button) {
            const modal = button.closest('.modal');
            if (modal) {
                modal.remove();
            }
        }

        function saveUserNote(userEmail, userName) {
            const noteText = document.getElementById('noteText').value.trim();
            
            console.log('💾 Saving note for:', userEmail, 'Note:', noteText);
            
            // Show loading state
            const saveBtn = event.target;
            const originalText = saveBtn.textContent;
            saveBtn.textContent = 'Saving...';
            saveBtn.disabled = true;
            
            // Prepare note - if empty, save as empty (this will remove the note)
            const now = new Date();
            const dateOnly = now.toLocaleDateString();
            const timeOnly = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
            const noteToSave = noteText ? `[${dateOnly} ${timeOnly}] ${noteText}` : '';
            
            // Save note via API
            fetch('api/DatabaseAPI.php?action=save_user_note', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    email: userEmail, 
                    note: noteToSave 
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('📝 Note save response:', data);
                
                if (data.success) {
                    if (noteText) {
                        alert(`Note saved successfully for ${userName}!`);
                    } else {
                        alert(`Note removed for ${userName}!`);
                    }
                    closeNoteModal(saveBtn);
                    // Refresh the page to update the note button visibility
                    location.reload();
                } else {
                    alert('Error saving note: ' + (data.message || 'Unknown error'));
                    saveBtn.textContent = originalText;
                    saveBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('❌ Error saving note:', error);
                alert('Error saving note. Please try again.');
                saveBtn.textContent = originalText;
                saveBtn.disabled = false;
            });
        }

        function deleteUserNote(userEmail, userName, buttonElement) {
            if (!confirm(`Are you sure you want to delete the note for ${userName}?`)) {
                return;
            }
            
            console.log('🗑️ Deleting note for:', userEmail);
            
            // Delete note via API
            fetch('api/DatabaseAPI.php?action=save_user_note', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    email: userEmail, 
                    note: '' 
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('🗑️ Note delete response:', data);
                console.log('🗑️ Response type:', typeof data);
                console.log('🗑️ Success value:', data.success);
                console.log('🗑️ Message:', data.message);
                
                if (data && data.success === true) {
                    alert(`Note deleted successfully for ${userName}!`);
                    closeNoteModal(buttonElement || event.target);
                    // Refresh the page to update the note button visibility
                    location.reload();
                } else {
                    alert('Error deleting note: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('❌ Error deleting note:', error);
                alert('Error deleting note. Please try again.');
            });
        }

        // Toggle User Flag Function
        function toggleUserFlag(userEmail, userName, currentFlagStatus, buttonElement) {
            console.log('🚩 Toggle Flag function called for:', userEmail, userName, 'Current status:', currentFlagStatus);
            
            if (!userEmail || userEmail === 'undefined' || userEmail === 'null') {
                console.error('❌ Invalid userEmail:', userEmail);
                return;
            }

            console.log('🚩 Toggling flag for:', userEmail);
            
            // Save flag status via API
            fetch('api/DatabaseAPI.php?action=toggle_user_flag', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    email: userEmail
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('🚩 Flag toggle response:', data);
                
                if (data.success) {
                    // Update the button text and style immediately
                    const flagBtn = buttonElement || event.target;
                    const newFlagStatus = data.is_flagged == 1;
                    
                    if (flagBtn) {
                        flagBtn.innerHTML = newFlagStatus ? 'Unflag' : 'Flag';
                        flagBtn.title = newFlagStatus ? 'Unflag User' : 'Flag User';
                        flagBtn.style.background = newFlagStatus ? '#4CAF50' : '#FF1744';
                    }
                    
                    // Update the table row highlighting
                    const tableRow = document.querySelector(`tr[data-email="${userEmail}"]`);
                    if (tableRow) {
                        if (newFlagStatus) {
                            tableRow.classList.add('flagged-user-row');
                        } else {
                            tableRow.classList.remove('flagged-user-row');
                        }
                    }
                    
                    // Refresh the modal with updated data if it's open
                    const existingModal = document.querySelector('.modal');
                    if (existingModal && existingModal.style.display === 'block') {
                        console.log('🔄 Refreshing modal with updated flag status...');
                        // Close current modal and reopen with fresh data
                        existingModal.remove();
                        // Small delay to ensure modal is fully closed before reopening
                        setTimeout(() => {
                            viewUserDetails(userEmail);
                        }, 100);
                    }
                    
                    console.log('✅ Flag status updated successfully');
                } else {
                    console.error('❌ Error updating flag:', data.message);
                }
            })
            .catch(error => {
                console.error('❌ Error toggling flag:', error);
            });
        }

        function viewFoodHistory(userEmail, userName) {
            console.log('🍽️ View Food History function called for:', userEmail, userName);
            
            if (!userEmail || userEmail === 'undefined' || userEmail === 'null') {
                console.error('❌ Invalid userEmail:', userEmail);
                alert('Error: Invalid user email');
                return;
            }

            // Show loading modal
            const loadingModal = document.createElement('div');
            loadingModal.className = 'modal';
            loadingModal.style.display = 'block';
            loadingModal.innerHTML = `
                <div class="modal-content" style="text-align: center; padding: 40px;">
                    <h3>Loading food history...</h3>
                    <div style="margin: 20px 0;">
                        <div style="border: 4px solid #f3f3f3; border-top: 4px solid var(--color-highlight); border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto;"></div>
                    </div>
                </div>
            `;
            document.body.appendChild(loadingModal);

            // Fetch food history from API
            fetch('api/DatabaseAPI.php?action=get_user_history&user_email=' + encodeURIComponent(userEmail))
                .then(response => {
                    console.log('📥 Food History API Response:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('📋 Food history data received:', data);
                    
                    // Remove loading modal
                    loadingModal.remove();
                    
                    if (!data.success) {
                        console.error('❌ API Error:', data.message);
                        alert('Error loading food history: ' + data.message);
                        return;
                    }
                    
                    // Show food history modal with table layout
                    showFoodHistoryTableModal(userName, userEmail, data.data || []);
                })
                .catch(error => {
                    console.error('❌ Fetch Error:', error);
                    loadingModal.remove();
                    alert('Error loading food history: ' + error.message);
                });
        }

        function showFoodHistoryModal(userName, userEmail, foodData) {
            console.log('🎯 showFoodHistoryModal called with:', { userName, userEmail, foodDataLength: foodData.length });
            
            // Remove any existing modals
            const existingModals = document.querySelectorAll('.modal');
            existingModals.forEach(modal => modal.remove());
            
            // Group food data by date
            const groupedByDate = {};
            foodData.forEach(food => {
                if (!groupedByDate[food.date]) {
                    groupedByDate[food.date] = [];
                }
                groupedByDate[food.date].push(food);
            });
            
            // Sort dates
            const sortedDates = Object.keys(groupedByDate).sort((a, b) => new Date(b) - new Date(a));
            
            // Create modal HTML
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.style.display = 'block';
            modal.innerHTML = `
                <div class="modal-content" style="max-width: 95vw; max-height: 90vh; overflow-y: auto;">
                    <div class="modal-header">
                        <h3>🍽️ Food History - ${userName}</h3>
                        <span class="close" onclick="this.closest('.modal').remove()">&times;</span>
                    </div>
                    <div class="food-history-content">
                        ${sortedDates.length === 0 ? 
                            '<div style="text-align: center; padding: 40px; color: #666;">No food history found for this user.</div>' :
                            sortedDates.map(date => {
                                const dayFoods = groupedByDate[date];
                                const totalCalories = dayFoods.reduce((sum, food) => sum + parseInt(food.calories), 0);
                                const totalProtein = dayFoods.reduce((sum, food) => sum + parseFloat(food.protein), 0);
                                const totalCarbs = dayFoods.reduce((sum, food) => sum + parseFloat(food.carbs), 0);
                                const totalFat = dayFoods.reduce((sum, food) => sum + parseFloat(food.fat), 0);
                                
                                // Check if any food in this day is flagged
                                const isDayFlagged = dayFoods.some(food => food.is_day_flagged == 1);
                                const dayComment = dayFoods.find(food => food.mho_comment)?.mho_comment || '';
                                
                                return `
                                    <div class="food-day-section ${isDayFlagged ? 'flagged' : ''}">
                                        <div class="day-header">
                                            <div class="day-title">
                                                <h4>📅 ${new Date(date).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</h4>
                                                ${isDayFlagged ? '<span class="day-flag-icon" title="Day flagged by MHO">🚩</span>' : ''}
                                            </div>
                                            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 12px;">
                                                <div class="day-totals">
                                                    <div><strong>Total: ${totalCalories} kcal</strong></div>
                                                    <div>Protein: ${totalProtein.toFixed(1)}g | Carbs: ${totalCarbs.toFixed(1)}g | Fat: ${totalFat.toFixed(1)}g</div>
                                                </div>
                                                <div class="day-actions">
                                                    <button class="food-flag-btn" onclick="flagEntireDay('${userEmail}', '${date}')" title="Flag entire day">
                                                        ${isDayFlagged ? '🚩 Unflag Day' : '🚩 Flag Day'}
                                                    </button>
                                                    <button class="food-comment-btn" onclick="addCommentToDay('${userEmail}', '${date}')" title="Add comment to day">
                                                        💬 Comment
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        ${dayComment ? `
                                            <div class="day-comment">
                                                <strong>MHO Comment:</strong> ${dayComment}
                                            </div>
                                        ` : ''}
                                        <div class="meals-container">
                                            ${['Breakfast', 'Lunch', 'Dinner', 'Snacks'].map(meal => {
                                                const mealFoods = dayFoods.filter(food => food.meal_category === meal);
                                                if (mealFoods.length === 0) return '';
                                                
                                                const mealCalories = mealFoods.reduce((sum, food) => sum + parseInt(food.calories), 0);
                                                
                                                return `
                                                    <div class="meal-section">
                                                        <div class="meal-header">
                                                            <h5 class="meal-title">${meal}</h5>
                                                            <span class="meal-calories">${mealCalories} kcal</span>
                                                        </div>
                                                        <div class="meal-foods">
                                                            ${mealFoods.map(food => `
                                                                <div class="food-item ${food.is_flagged == 1 ? 'flagged' : ''}">
                                                                    <div class="food-info">
                                                                        <div class="food-name">
                                                                            <span>${food.food_name}</span>
                                                                            ${food.is_flagged == 1 ? '<span style="color: #ff9800; font-size: 16px;" title="Flagged by MHO">🚩</span>' : ''}
                                                                        </div>
                                                                        <div class="food-serving">
                                                                            <span>(${food.serving_size})</span>
                                                                            <button class="food-edit-btn" onclick="editServingSize(${food.id}, '${userEmail}', '${date}', '${food.serving_size}')" title="Edit serving size">
                                                                                ✏️ Edit
                                                                            </button>
                                                                        </div>
                                                                        ${food.mho_comment ? `
                                                                            <div class="food-comment">
                                                                                <strong>MHO:</strong> ${food.mho_comment}
                                                                            </div>
                                                                        ` : ''}
                                                                    </div>
                                                                    <div class="food-nutrition">
                                                                        <div class="food-calories">${food.calories} kcal</div>
                                                                        <div class="food-macros">P: ${food.protein}g | C: ${food.carbs}g | F: ${food.fat}g</div>
                                                                    </div>
                                                                    <div class="food-actions">
                                                                        <button class="food-action-btn food-flag-action ${food.is_flagged == 1 ? 'unflag' : ''}" onclick="toggleFoodItemFlag(${food.id}, '${userEmail}', '${date}', ${food.is_flagged == 1})" title="${food.is_flagged == 1 ? 'Unflag food' : 'Flag food'}">
                                                                            ${food.is_flagged == 1 ? '🚩 Unflag' : '🚩 Flag'}
                                                                        </button>
                                                                        <button class="food-action-btn food-comment-action" onclick="addCommentToFood(${food.id}, '${userEmail}', '${date}')" title="Add comment">
                                                                            💬
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            `).join('')}
                                                        </div>
                                                    </div>
                                                `;
                                            }).join('')}
                                        </div>
                                    </div>
                                `;
                            }).join('')
                        }
                    </div>
                </div>
            `;
            
            console.log('🎯 About to append modal to DOM:', modal);
            document.body.appendChild(modal);
            console.log('🎯 Modal appended to DOM. Modal display style:', modal.style.display);
            console.log('🎯 Modal visibility:', window.getComputedStyle(modal).display);
        }

        // Food History Interactive Functions
        function toggleFoodItemFlag(foodId, userEmail, date, isCurrentlyFlagged) {
            const mhoEmail = 'admin@nutrisaur.com'; // You can get this from session or pass as parameter
            
            if (isCurrentlyFlagged) {
                // Unflag the food item
                fetch('api/DatabaseAPI.php?action=unflag_food', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: foodId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Food item unflagged successfully');
                        // Refresh the food history modal
                        viewFoodHistory(userEmail, document.querySelector('.modal h3').textContent.replace('🍽️ Food History - ', ''));
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error unflagging food:', error);
                    alert('Error unflagging food item');
                });
            } else {
                // Flag the food item
                const comment = prompt('Add a comment (optional):') || '';
                fetch('api/DatabaseAPI.php?action=flag_food', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: foodId,
                        mho_email: mhoEmail,
                        comment: comment
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Food item flagged successfully');
                        // Refresh the food history modal
                        viewFoodHistory(userEmail, document.querySelector('.modal h3').textContent.replace('🍽️ Food History - ', ''));
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error flagging food:', error);
                    alert('Error flagging food item');
                });
            }
        }

        function flagEntireDay(userEmail, date) {
            const mhoEmail = 'admin@nutrisaur.com'; // You can get this from session or pass as parameter
            
            fetch('api/DatabaseAPI.php?action=flag_day', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_email: userEmail,
                    date: date,
                    mho_email: mhoEmail,
                    comment: prompt('Add a comment for the entire day (optional):') || ''
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Day flagged successfully');
                    // Refresh the food history modal
                    viewFoodHistory(userEmail, document.querySelector('.modal h3').textContent.replace('🍽️ Food History - ', ''));
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error flagging day:', error);
                alert('Error flagging day');
            });
        }

        function unflagFoodItem(foodId, userEmail, date) {
            fetch('api/DatabaseAPI.php?action=unflag_food', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: foodId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Food item unflagged successfully');
                    // Refresh the food history modal
                    viewFoodHistory(userEmail, document.querySelector('.modal h3').textContent.replace('🍽️ Food History - ', ''));
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error unflagging food:', error);
                alert('Error unflagging food item');
            });
        }

        function unflagEntireDay(userEmail, date) {
            fetch('api/DatabaseAPI.php?action=unflag_day', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_email: userEmail,
                    date: date
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Day unflagged successfully');
                    // Refresh the food history modal
                    viewFoodHistory(userEmail, document.querySelector('.modal h3').textContent.replace('🍽️ Food History - ', ''));
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error unflagging day:', error);
                alert('Error unflagging day');
            });
        }

        function editServingSize(foodId, userEmail, date, currentSize) {
            const newSize = prompt('Enter new serving size:', currentSize);
            if (newSize && newSize !== currentSize) {
                fetch('api/DatabaseAPI.php?action=update_serving_size', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: foodId,
                        serving_size: newSize
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Serving size updated successfully');
                        // Refresh the food history modal
                        viewFoodHistory(userEmail, document.querySelector('.modal h3').textContent.replace('🍽️ Food History - ', ''));
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error updating serving size:', error);
                    alert('Error updating serving size');
                });
            }
        }

        function addCommentToFood(foodId, userEmail, date) {
            const comment = prompt('Add a comment for this food item:');
            if (comment) {
                const mhoEmail = 'admin@nutrisaur.com'; // You can get this from session or pass as parameter
                
                fetch('api/DatabaseAPI.php?action=add_comment', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: foodId,
                        comment: comment,
                        mho_email: mhoEmail
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Comment added successfully');
                        // Refresh the food history modal
                        viewFoodHistory(userEmail, document.querySelector('.modal h3').textContent.replace('🍽️ Food History - ', ''));
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error adding comment:', error);
                    alert('Error adding comment');
                });
            }
        }

        function addCommentToDay(userEmail, date) {
            const comment = prompt('Add a comment for this day:');
            if (comment) {
                const mhoEmail = 'admin@nutrisaur.com'; // You can get this from session or pass as parameter
                
                // Get the first food item for this day to add comment
                fetch('api/DatabaseAPI.php?action=get_user_history&user_email=' + encodeURIComponent(userEmail))
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.data.length > 0) {
                            const dayFoods = data.data.filter(food => food.date === date);
                            if (dayFoods.length > 0) {
                                const firstFoodId = dayFoods[0].id;
                                
                fetch('api/DatabaseAPI.php?action=add_comment', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: firstFoodId,
                        comment: comment,
                        mho_email: mhoEmail
                    })
                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        alert('Comment added successfully');
                                        // Refresh the food history modal
                                        viewFoodHistory(userEmail, document.querySelector('.modal h3').textContent.replace('🍽️ Food History - ', ''));
                                    } else {
                                        alert('Error: ' + data.error);
                                    }
                                })
                                .catch(error => {
                                    console.error('Error adding comment:', error);
                                    alert('Error adding comment');
                                });
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error getting food data:', error);
                        alert('Error adding comment');
                    });
            }
        }

        // Complete the showFoodHistoryModal function
        function showFoodHistoryModal(userName, userEmail, foodData) {
            console.log('🎯 showFoodHistoryModal called with:', { userName, userEmail, foodDataLength: foodData.length });
            
            // Remove any existing modals
            const existingModals = document.querySelectorAll('.modal');
            existingModals.forEach(modal => modal.remove());
            
            // Group food data by date
            const groupedByDate = {};
            foodData.forEach(food => {
                if (!groupedByDate[food.date]) {
                    groupedByDate[food.date] = [];
                }
                groupedByDate[food.date].push(food);
            });
            
            // Sort dates
            const sortedDates = Object.keys(groupedByDate).sort((a, b) => new Date(b) - new Date(a));
            
            // Create modal HTML
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.style.display = 'block';
            modal.innerHTML = `
                <div class="modal-content" style="max-width: 95vw; max-height: 90vh; overflow-y: auto;">
                    <div class="modal-header">
                        <h3>🍽️ Food History - ${userName}</h3>
                        <span class="close" onclick="this.closest('.modal').remove()">&times;</span>
                    </div>
                    <div class="food-history-content">
                        ${sortedDates.length === 0 ? 
                            '<div style="text-align: center; padding: 40px; color: #666;">No food history found for this user.</div>' :
                            sortedDates.map(date => {
                                const dayFoods = groupedByDate[date];
                                const totalCalories = dayFoods.reduce((sum, food) => sum + parseInt(food.calories), 0);
                                const totalProtein = dayFoods.reduce((sum, food) => sum + parseFloat(food.protein), 0);
                                const totalCarbs = dayFoods.reduce((sum, food) => sum + parseFloat(food.carbs), 0);
                                const totalFat = dayFoods.reduce((sum, food) => sum + parseFloat(food.fat), 0);
                                
                                // Check if any food in this day is flagged
                                const isDayFlagged = dayFoods.some(food => food.is_day_flagged == 1);
                                const dayComment = dayFoods.find(food => food.mho_comment)?.mho_comment || '';
                                
                                return `
                                    <div class="food-day-section ${isDayFlagged ? 'flagged' : ''}">
                                        <div class="day-header">
                                            <div class="day-title">
                                                <h4>📅 ${new Date(date).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</h4>
                                                ${isDayFlagged ? '<span class="day-flag-icon" title="Day flagged by MHO">🚩</span>' : ''}
                                            </div>
                                            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 12px;">
                                                <div class="day-totals">
                                                    <div><strong>Total: ${totalCalories} kcal</strong></div>
                                                    <div>Protein: ${totalProtein.toFixed(1)}g | Carbs: ${totalCarbs.toFixed(1)}g | Fat: ${totalFat.toFixed(1)}g</div>
                                                </div>
                                                <div class="day-actions">
                                                    <button class="food-flag-btn" onclick="flagEntireDay('${userEmail}', '${date}')" title="Flag entire day">
                                                        ${isDayFlagged ? '🚩 Unflag Day' : '🚩 Flag Day'}
                                                    </button>
                                                    <button class="food-comment-btn" onclick="addCommentToDay('${userEmail}', '${date}')" title="Add comment to day">
                                                        💬 Comment
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        ${dayComment ? `
                                            <div class="day-comment">
                                                <strong>MHO Comment:</strong> ${dayComment}
                                            </div>
                                        ` : ''}
                                        <div class="meals-container">
                                            ${['Breakfast', 'Lunch', 'Dinner', 'Snacks'].map(meal => {
                                                const mealFoods = dayFoods.filter(food => food.meal_category === meal);
                                                if (mealFoods.length === 0) return '';
                                                
                                                const mealCalories = mealFoods.reduce((sum, food) => sum + parseInt(food.calories), 0);
                                                
                                                return `
                                                    <div class="meal-section">
                                                        <div class="meal-header">
                                                            <h5 class="meal-title">${meal}</h5>
                                                            <span class="meal-calories">${mealCalories} kcal</span>
                                                        </div>
                                                        <div class="meal-foods">
                                                            ${mealFoods.map(food => `
                                                                <div class="food-item" style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #eee; ${food.is_flagged == 1 ? 'background-color: var(--flagged-bg, #ffebee); border-left: 3px solid var(--flagged-border, #f44336);' : ''}">
                                                                    <div class="food-info" style="flex: 1;">
                                                                        <div style="display: flex; align-items: center; gap: 8px;">
                                                                            <span style="font-weight: 600;">${food.food_name}</span>
                                                                            ${food.is_flagged == 1 ? '<span style="color: var(--flagged-border, #f44336); font-size: 16px;" title="Flagged by MHO">🚩</span>' : ''}
                                                                        </div>
                                                                        <div style="display: flex; align-items: center; gap: 10px; margin-top: 4px;">
                                                                            <span style="color: #666;">(${food.serving_size})</span>
                                                                            <button onclick="editServingSize(${food.id}, '${userEmail}', '${date}', '${food.serving_size}')" style="padding: 2px 6px; background: #4CAF50; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 10px;" title="Edit serving size">
                                                                                ✏️ Edit
                                                                            </button>
                                                                        </div>
                                                                        <div style="font-size: 12px; color: #888; margin-top: 2px;">
                                                                            ${food.calories} kcal | ${food.protein}g protein | ${food.carbs}g carbs | ${food.fat}g fat
                                                                        </div>
                                                                        ${food.mho_comment ? `
                                                                            <div style="background: #fff3cd; padding: 5px; border-radius: 3px; margin-top: 5px; border-left: 3px solid #ffc107; font-size: 11px;">
                                                                                <strong>MHO Comment:</strong> ${food.mho_comment}
                                                                            </div>
                                                                        ` : ''}
                                                                    </div>
                                                                    <div class="food-actions" style="display: flex; gap: 5px;">
                                                                        <button onclick="toggleFoodItemFlag(${food.id}, '${userEmail}', '${date}', ${food.is_flagged == 1})" style="padding: 4px 8px; background: ${food.is_flagged == 1 ? '#4CAF50' : '#f44336'}; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 10px;" title="${food.is_flagged == 1 ? 'Unflag this food' : 'Flag this food'}">
                                                                            ${food.is_flagged == 1 ? '🚩 Unflag' : '🚩 Flag'}
                                                                        </button>
                                                                        <button onclick="addCommentToFood(${food.id}, '${userEmail}', '${date}')" style="padding: 4px 8px; background: #2196F3; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 10px;" title="Add comment to this food">
                                                                            💬 Comment
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            `).join('')}
                                                        </div>
                                                    </div>
                                                `;
                                            }).join('')}
                                        </div>
                                    </div>
                                `;
                            }).join('')}
                    </div>
                </div>
            `;
            
            // Add modal to DOM
            document.body.appendChild(modal);
            
            // Add click outside to close
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }

        // Helper functions for food history actions
        function toggleFoodItemFlag(foodId, userEmail, date, isCurrentlyFlagged) {
            if (isCurrentlyFlagged) {
                // Unflag
                fetch(`api/DatabaseAPI.php?action=unflag_food`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: foodId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Food item unflagged successfully!');
                        // Refresh the modal
                        showFoodHistoryModal(userEmail.split('@')[0], userEmail, []);
                    } else {
                        alert('Error unflagging food item: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error unflagging food item');
                });
            } else {
                // Flag with comment
                const comment = prompt('Enter reason for flagging this food item:');
                if (comment) {
                    fetch(`api/DatabaseAPI.php?action=flag_food`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ 
                            id: foodId, 
                            mho_comment: comment,
                            flagged_by: '<?php echo $_SESSION["admin_email"] ?? "MHO Official"; ?>'
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Food item flagged successfully!');
                            // Refresh the modal
                            showFoodHistoryModal(userEmail.split('@')[0], userEmail, []);
                        } else {
                            alert('Error flagging food item: ' + data.error);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error flagging food item');
                    });
                }
            }
        }

        function addCommentToFood(foodId, userEmail, date) {
            const comment = prompt('Enter comment for this food item:');
            if (comment) {
                fetch(`api/DatabaseAPI.php?action=add_comment`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        id: foodId, 
                        mho_comment: comment,
                        flagged_by: '<?php echo $_SESSION["admin_email"] ?? "MHO Official"; ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Comment added successfully!');
                        // Refresh the modal
                        showFoodHistoryModal(userEmail.split('@')[0], userEmail, []);
                    } else {
                        alert('Error adding comment: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error adding comment');
                });
            }
        }

        function flagEntireDay(userEmail, date) {
            const comment = prompt('Enter reason for flagging this entire day:');
            if (comment) {
                fetch(`api/DatabaseAPI.php?action=flag_day`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        user_email: userEmail, 
                        date: date,
                        mho_comment: comment,
                        flagged_by: '<?php echo $_SESSION["admin_email"] ?? "MHO Official"; ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Entire day flagged successfully!');
                        // Refresh the modal
                        showFoodHistoryModal(userEmail.split('@')[0], userEmail, []);
                    } else {
                        alert('Error flagging day: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error flagging day');
                });
            }
        }

        function addCommentToDay(userEmail, date) {
            const comment = prompt('Enter comment for this day:');
            if (comment) {
                fetch(`api/DatabaseAPI.php?action=add_comment`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        user_email: userEmail, 
                        date: date,
                        mho_comment: comment,
                        flagged_by: '<?php echo $_SESSION["admin_email"] ?? "MHO Official"; ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Comment added successfully!');
                        // Refresh the modal
                        showFoodHistoryModal(userEmail.split('@')[0], userEmail, []);
                    } else {
                        alert('Error adding comment: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error adding comment');
                });
            }
        }

        function editServingSize(foodId, userEmail, date, currentServing) {
            const newServing = prompt('Enter new serving size:', currentServing);
            if (newServing && newServing !== currentServing) {
                fetch(`api/DatabaseAPI.php?action=update_serving_size`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        id: foodId, 
                        serving_size: newServing
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Serving size updated successfully!');
                        // Refresh the modal
                        showFoodHistoryModal(userEmail.split('@')[0], userEmail, []);
                    } else {
                        alert('Error updating serving size: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating serving size');
                });
            }
        }

        // New table-based food history modal
        function showFoodHistoryTableModal(userName, userEmail, foodData) {
            console.log('🎯 showFoodHistoryTableModal called with:', { userName, userEmail, foodDataLength: foodData.length });
            
            // Remove any existing modals
            const existingModals = document.querySelectorAll('.modal');
            existingModals.forEach(modal => modal.remove());
            
            // Create modal HTML with table structure
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.style.display = 'block';
            modal.innerHTML = `
                <div class="modal-content" style="max-width: 95vw; max-height: 90vh; overflow-y: auto;">
                    <div class="modal-header">
                        <h3>🍽️ Food History - ${userName}</h3>
                        <span class="close" onclick="this.closest('.modal').remove()">&times;</span>
                    </div>
                    <div class="food-history-content">
                        ${foodData.length === 0 ? 
                            '<div style="text-align: center; padding: 40px; color: #666;">No food history found for this user.</div>' :
                            `
                            <table class="food-history-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Meal</th>
                                        <th>Food Item</th>
                                        <th>Serving</th>
                                        <th>Nutrition</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${foodData.map(food => `
                                        <tr class="${food.is_flagged == 1 ? 'flagged' : ''}">
                                            <td class="food-date-cell">${new Date(food.date).toLocaleDateString()}</td>
                                            <td class="food-meal-cell">${food.meal_category}</td>
                                            <td class="food-name-cell">
                                                ${food.food_name}
                                                ${food.is_flagged == 1 ? '<span class="food-flag-indicator">🚩</span>' : ''}
                                            </td>
                                            <td class="food-serving-cell">
                                                ${food.serving_size}
                                                <button class="food-edit-btn" onclick="editServingSize(${food.id}, '${userEmail}', '${food.date}', '${food.serving_size}')" title="Edit serving size">
                                                    ✏️
                                                </button>
                                            </td>
                                            <td class="food-nutrition-cell">
                                                <div class="food-calories">${food.calories} kcal</div>
                                                <div class="food-macros">P: ${food.protein}g | C: ${food.carbs}g | F: ${food.fat}g</div>
                                            </td>
                                            <td class="food-actions-cell">
                                                <button class="food-action-btn food-flag-action ${food.is_flagged == 1 ? 'unflag' : ''}" onclick="toggleFoodItemFlag(${food.id}, '${userEmail}', '${food.date}', ${food.is_flagged == 1})" title="${food.is_flagged == 1 ? 'Unflag food' : 'Flag food'}">
                                                    ${food.is_flagged == 1 ? '🚩 Unflag' : '🚩 Flag'}
                                                </button>
                                                <button class="food-action-btn food-comment-action" onclick="addCommentToFood(${food.id}, '${userEmail}', '${food.date}')" title="Add comment">
                                                    💬
                                                </button>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                            `}
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
        }


    </script>
</body>
</html>
