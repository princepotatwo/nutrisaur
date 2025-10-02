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
    color: var(--color-highlight);
    font-size: 22px;
    font-weight: 600;
    margin: 0;
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
    color: #F44336 !important;
    background: rgba(244, 67, 54, 0.1);
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
    color: #F44336 !important;
    background: rgba(244, 67, 54, 0.1);
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

        /* User Details Modal Styles */
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

        /* User Management Styles - Same as settings.php */
        .user-management-container {
            background-color: var(--color-card);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
            margin-bottom: 30px;
            border: 1px solid var(--color-border);
            position: relative;
            overflow: hidden;
            width: 100%;
            max-width: calc(100% - 60px);
            margin-left: 0;
            margin-right: 0;
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

        /* New Control Grid Layout */
        .control-grid {
            background: linear-gradient(135deg, var(--color-card) 0%, rgba(161, 180, 84, 0.1) 100%);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border: 2px solid rgba(161, 180, 84, 0.3);
            display: grid;
            grid-template-rows: auto auto;
            gap: 15px;
        }

        /* Row 1: Action Buttons and Search */
        .control-row-1 {
            display: flex;
            gap: 12px;
            align-items: center;
            justify-content: space-between;
        }

        .action-section {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-shrink: 0;
        }

        .filter-dropdowns {
            display: flex;
            gap: 6px;
            flex-shrink: 0;
            align-items: center;
        }

        .filter-select {
            padding: 5px 8px;
            border: 2px solid rgba(161, 180, 84, 0.3);
            background: var(--color-bg);
            border-radius: 6px;
            font-size: 10px;
            color: var(--color-text);
            outline: none;
            transition: all 0.3s ease;
            min-width: 130px;
            max-width: 150px;
            cursor: pointer;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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
            gap: 8px;
            flex: 1;
            justify-content: flex-end;
        }

        .search-input {
            border: 2px solid rgba(161, 180, 84, 0.4);
            background: var(--color-bg);
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 11px;
            color: var(--color-text);
            outline: none;
            transition: all 0.3s ease;
            width: 250px;
            flex-shrink: 0;
        }

        .search-input:focus {
            border-color: var(--color-accent1);
            box-shadow: 0 0 0 3px rgba(161, 180, 84, 0.1);
        }

        .search-btn {
            background: var(--color-accent1);
            border: none;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 11px;
            margin-left: 8px;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            background: var(--color-accent2);
            transform: scale(1.05);
        }

        /* Row 2: Filter Controls */
        .control-row-2 {
            background: linear-gradient(135deg, rgba(161, 180, 84, 0.15) 0%, rgba(161, 180, 84, 0.05) 100%);
            border-radius: 8px;
            padding: 12px;
            border: 1px solid rgba(161, 180, 84, 0.2);
            overflow: visible;
            position: relative;
            z-index: 50;
        }

        .filter-section {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 12px;
            align-items: end;
            overflow: visible;
            position: relative;
            z-index: 40;
        }

        .filter-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
            position: relative;
            z-index: 30;
        }

        .filter-item label {
            font-size: 11px;
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
            padding: 6px 8px;
            border: 2px solid rgba(161, 180, 84, 0.3);
            border-radius: 4px;
            background: var(--color-bg);
            color: var(--color-text);
            font-size: 11px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            position: relative;
            z-index: 100;
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

        /* WHO Standard dropdown specific styling */
        #standardFilter {
            background: var(--color-bg) !important;
            border: 2px solid rgba(161, 180, 84, 0.3) !important;
            position: relative !important;
            z-index: 200 !important;
        }

        #standardFilter option {
            background: var(--color-bg) !important;
            color: var(--color-text) !important;
            padding: 8px !important;
            border: none !important;
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
                grid-template-columns: repeat(4, 1fr);
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
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
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
                grid-template-columns: repeat(2, 1fr);
                gap: 6px;
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
                height: 24px;
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

        .table-responsive {
            border-radius: 12px;
            border: 1px solid var(--color-border);
            width: 100%;
            max-width: 100%;
        }

        .user-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 10px;
            table-layout: auto;
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid var(--color-border);
            box-shadow: 0 4px 20px var(--color-shadow);
            min-width: 100%;
        }

        /* Auto-fit columns - automatically distributes space equally */
        .user-table th,
        .user-table td {
            width: auto !important;
            min-width: 60px;
            max-width: none;
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



        /* Responsive table wrapper */
        .table-responsive {
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }


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
            background: transparent;
            color: var(--color-text);
        }

        .dark-theme .search-input::placeholder {
            color: rgba(232, 240, 214, 0.6);
        }

        /* Light theme search input styles */
        .light-theme .search-input {
            background: transparent;
            color: var(--color-text);
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
            color: #F44336;
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
            color: #F44336;
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
            color: #F44336;
        }

        .nutritional-status.obesity-class-iii-severe {
            color: #8B4513;
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
            color: #8B4513;
        }

        .risk-level.high {
            color: #F44336;
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
            color: #F44336;
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
                                <select id="municipalityFilter" onchange="filterByMunicipality()">
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
                                </select>
                            </div>
                            
                            <div class="filter-item">
                                <label>BARANGAY</label>
                                <select id="barangayFilter" onchange="filterByBarangay()">
                                    <option value="">All</option>
                                </select>
                            </div>
                            
                            <div class="filter-item">
                                <label>AGE FROM</label>
                                <div style="display: flex; gap: 4px; align-items: center;">
                                    <input type="number" id="ageFromYears" min="0" max="120" placeholder="0" 
                                           style="width: 50px; text-align: center;" onchange="filterByAgeRange()">
                                    <span style="font-size: 10px; color: var(--color-text);">Y</span>
                                    <input type="number" id="ageFromMonths" min="0" max="11" placeholder="0" 
                                           style="width: 50px; text-align: center;" onchange="filterByAgeRange()">
                                    <span style="font-size: 10px; color: var(--color-text);">M</span>
                                </div>
                            </div>
                            
                            <div class="filter-item">
                                <label>AGE TO</label>
                                <div style="display: flex; gap: 4px; align-items: center;">
                                    <input type="number" id="ageToYears" min="0" max="120" placeholder="0" 
                                           style="width: 50px; text-align: center;" onchange="filterByAgeRange()">
                                    <span style="font-size: 10px; color: var(--color-text);">Y</span>
                                    <input type="number" id="ageToMonths" min="0" max="11" placeholder="0" 
                                           style="width: 50px; text-align: center;" onchange="filterByAgeRange()">
                                    <span style="font-size: 10px; color: var(--color-text);">M</span>
                                </div>
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
                                <select id="standardFilter" onchange="filterByStandard()">
                                    <option value="weight-for-age">Weight-for-Age (0-71 months)</option>
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
                                            
                                            echo '<tr data-standard="' . $standardName . '" data-age-months="' . $ageInMonths . '" data-height="' . $user['height'] . '" data-municipality="' . htmlspecialchars($user['municipality'] ?? '') . '" data-barangay="' . htmlspecialchars($user['barangay'] ?? '') . '" data-sex="' . htmlspecialchars($user['sex'] ?? '') . '">';
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
                                            echo '<td class="text-center">' . htmlspecialchars($user['screening_date'] ?? 'N/A') . '</td>';
                                            echo '<td class="text-center">';
                                            echo '<div class="action-buttons">';
                                            echo '<button class="btn-view" onclick="viewUserDetails(' . $user['id'] . ')" title="View Full Details">';
                                            echo 'View';
                                            echo '</button>';
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
        

        function filterByAgeRange() {
            // Validate age range inputs
            const ageFromYears = document.getElementById('ageFromYears').value;
            const ageFromMonths = document.getElementById('ageFromMonths').value;
            const ageToYears = document.getElementById('ageToYears').value;
            const ageToMonths = document.getElementById('ageToMonths').value;
            
            // Check if any age fields have values
            if (ageFromYears || ageFromMonths || ageToYears || ageToMonths) {
                const fromYears = parseInt(ageFromYears) || 0;
                const fromMonths = parseInt(ageFromMonths) || 0;
                const toYears = parseInt(ageToYears) || 0;
                const toMonths = parseInt(ageToMonths) || 0;
                
                const fromTotalMonths = fromYears * 12 + fromMonths;
                const toTotalMonths = toYears * 12 + toMonths;
                
                // Only validate if both from and to have values
                if ((ageFromYears || ageFromMonths) && (ageToYears || ageToMonths)) {
                    // Validate that "from" age is not greater than "to" age
                    if (fromTotalMonths > toTotalMonths) {
                        // Show warning and clear the "to" fields
                        alert('Age "From" cannot be greater than Age "To". Please adjust your selection.');
                        document.getElementById('ageToYears').value = '';
                        document.getElementById('ageToMonths').value = '';
                        return;
                    }
                }
            }
            
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
            const ageFromYears = document.getElementById('ageFromYears').value;
            const ageFromMonths = document.getElementById('ageFromMonths').value;
            const ageToYears = document.getElementById('ageToYears').value;
            const ageToMonths = document.getElementById('ageToMonths').value;
            const sex = document.getElementById('sexFilter').value;
            const standard = document.getElementById('standardFilter').value;
            const classification = document.getElementById('classificationFilter').value;
            const risk = document.getElementById('riskFilter') ? document.getElementById('riskFilter').value : '';
            const location = document.getElementById('locationFilter') ? document.getElementById('locationFilter').value : '';
            
            console.log('Applying filters:', { municipality, barangay, ageFromYears, ageFromMonths, ageToYears, ageToMonths, sex, standard });
            
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
                
                // Age range filter (now supports all ages)
                if ((ageFromYears || ageFromMonths || ageToYears || ageToMonths) && showRow) {
                    const ageMonths = parseInt(row.dataset.ageMonths);
                    
                    // Parse age from separate year and month inputs
                    let fromMonths = null;
                    let toMonths = null;
                    
                    // Only process if at least one field has a value (not empty string)
                    if (ageFromYears || ageFromMonths) {
                        const years = parseInt(ageFromYears) || 0;
                        const months = parseInt(ageFromMonths) || 0;
                        fromMonths = years * 12 + months;
                    }
                    
                    if (ageToYears || ageToMonths) {
                        const years = parseInt(ageToYears) || 0;
                        const months = parseInt(ageToMonths) || 0;
                        toMonths = years * 12 + months;
                    }
                    
                    // Validate age range (from should not be greater than to)
                    if (fromMonths !== null && toMonths !== null && fromMonths > toMonths) {
                        // Invalid range - hide all rows
                        showRow = false;
                    } else {
                        // Apply age filters
                        if (fromMonths !== null && ageMonths < fromMonths) {
                            showRow = false;
                        }
                        if (toMonths !== null && ageMonths > toMonths) {
                            showRow = false;
                        }
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

        function viewUserDetails(userId) {
            // Fetch user details via AJAX
            fetch(`/api/DatabaseAPI.php?action=get_user_details&user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Error fetching user details:', data.error);
                        alert('Error loading user details: ' + data.error);
                        return;
                    }
                    showUserDetailsModal(data);
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading user details');
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
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>User Details - ${userData.name || 'N/A'}</h2>
                        <span class="close" onclick="this.parentElement.parentElement.parentElement.remove()">&times;</span>
                    </div>
                    <div class="modal-body">
                        <div class="user-details-grid">
                            <div class="detail-section">
                                <h3>Personal Information</h3>
                                <div class="detail-item">
                                    <label>Name:</label>
                                    <span>${userData.name || 'N/A'}</span>
                                </div>
                                <div class="detail-item">
                                    <label>Email:</label>
                                    <span>${userData.email || 'N/A'}</span>
                                </div>
                                <div class="detail-item">
                                    <label>Age:</label>
                                    <span>${userData.age || 'N/A'}</span>
                                </div>
                                <div class="detail-item">
                                    <label>Sex:</label>
                                    <span>${userData.sex || 'N/A'}</span>
                                </div>
                            </div>
                            
                            <div class="detail-section">
                                <h3>Location Information</h3>
                                <div class="detail-item">
                                    <label>Municipality:</label>
                                    <span>${userData.municipality || 'N/A'}</span>
                                </div>
                                <div class="detail-item">
                                    <label>Barangay:</label>
                                    <span>${userData.barangay || 'N/A'}</span>
                                </div>
                            </div>
                            
                            <div class="detail-section">
                                <h3>Physical Measurements</h3>
                                <div class="detail-item">
                                    <label>Weight (kg):</label>
                                    <span>${userData.weight || 'N/A'}</span>
                                </div>
                                <div class="detail-item">
                                    <label>Height (cm):</label>
                                    <span>${userData.height || 'N/A'}</span>
                                </div>
                                <div class="detail-item">
                                    <label>BMI:</label>
                                    <span>${userData.bmi || 'N/A'}</span>
                                </div>
                                <div class="detail-item">
                                    <label>MUAC (cm):</label>
                                    <span>${userData.muac_cm || 'N/A'}</span>
                                </div>
                            </div>
                            
                            <div class="detail-section">
                                <h3>Assessment Results</h3>
                                <div class="detail-item">
                                    <label>Weight-for-Age Z-Score:</label>
                                    <span>${userData.weight_for_age || 'N/A'}</span>
                                </div>
                                <div class="detail-item">
                                    <label>Height-for-Age Z-Score:</label>
                                    <span>${userData.height_for_age || 'N/A'}</span>
                                </div>
                                <div class="detail-item">
                                    <label>Weight-for-Height Z-Score:</label>
                                    <span>${userData.weight_for_height || 'N/A'}</span>
                                </div>
                                <div class="detail-item">
                                    <label>BMI-for-Age Z-Score:</label>
                                    <span>${userData.bmi_for_age || 'N/A'}</span>
                                </div>
                                <div class="detail-item">
                                    <label>BMI Category:</label>
                                    <span>${userData.bmi_category || 'N/A'}</span>
                                </div>
                                <div class="detail-item">
                                    <label>MUAC Category:</label>
                                    <span>${userData.muac_category || 'N/A'}</span>
                                </div>
                                <div class="detail-item">
                                    <label>Nutritional Risk:</label>
                                    <span>${userData.nutritional_risk || 'N/A'}</span>
                                </div>
                                <div class="detail-item">
                                    <label>Screening Date:</label>
                                    <span>${userData.screening_date || 'N/A'}</span>
                                </div>
                                <div class="detail-item">
                                    <label>Record Created:</label>
                                    <span>${userData.created_at || 'N/A'}</span>
                                </div>
                            </div>
                        </div>
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
            const ageFromYearsFilter = document.getElementById('ageFromYears');
            const ageFromMonthsFilter = document.getElementById('ageFromMonths');
            const ageToYearsFilter = document.getElementById('ageToYears');
            const ageToMonthsFilter = document.getElementById('ageToMonths');
            const sexFilter = document.getElementById('sexFilter');
            const standardFilter = document.getElementById('standardFilter');
            
            console.log('Filter elements found:', {
                municipality: !!municipalityFilter,
                barangay: !!barangayFilter,
                ageFromYears: !!ageFromYearsFilter,
                ageFromMonths: !!ageFromMonthsFilter,
                ageToYears: !!ageToYearsFilter,
                ageToMonths: !!ageToMonthsFilter,
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
            const ageFromYears = document.getElementById('ageFromYears').value;
            const ageFromMonths = document.getElementById('ageFromMonths').value;
            const ageToYears = document.getElementById('ageToYears').value;
            const ageToMonths = document.getElementById('ageToMonths').value;
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
            
            // Age range filter
            if ((ageFromYears || ageFromMonths || ageToYears || ageToMonths)) {
                const ageMonths = parseInt(row.dataset.ageMonths);
                
                let fromMonths = null;
                let toMonths = null;
                
                if (ageFromYears || ageFromMonths) {
                    const years = parseInt(ageFromYears) || 0;
                    const months = parseInt(ageFromMonths) || 0;
                    fromMonths = years * 12 + months;
                }
                
                if (ageToYears || ageToMonths) {
                    const years = parseInt(ageToYears) || 0;
                    const months = parseInt(ageToMonths) || 0;
                    toMonths = years * 12 + months;
                }
                
                if (fromMonths !== null && toMonths !== null && fromMonths > toMonths) {
                    return false;
                } else {
                    if (fromMonths !== null && ageMonths < fromMonths) {
                        return false;
                    }
                    if (toMonths !== null && ageMonths > toMonths) {
                        return false;
                    }
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
            
            const closeBtn = document.createElement('button');
            closeBtn.innerHTML = '✕';
            closeBtn.style.cssText = `
                position: absolute;
                top: 15px;
                right: 20px;
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
                        <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>Risk Level:</strong> <span style="color: ${assessment.risk_level === 'Low Risk' ? '#4CAF50' : assessment.risk_level === 'Medium Risk' ? '#FF9800' : '#F44336'}">${assessment.risk_level}</span></p>
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
            
            modalContent.appendChild(closeBtn);
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


    </script>
</body>
</html>
