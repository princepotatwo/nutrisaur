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

// Wrapper function to use WHO Growth Standards
function getNutritionalAssessment($user) {
    try {
        $who = new WHOGrowthStandards();
        
        // Calculate age in months for WHO standards
        $birthDate = new DateTime($user['birthday']);
        $today = new DateTime();
        $age = $today->diff($birthDate);
        $ageInMonths = ($age->y * 12) + $age->m;
        
        // Get comprehensive WHO Growth Standards assessment
        $assessment = $who->getComprehensiveAssessment(
            floatval($user['weight']), 
            floatval($user['height']), 
            $user['birthday'], 
            $user['sex']
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


        .btn-view {
            background: var(--accent-color);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background 0.3s ease;
        }

        .btn-view:hover {
            background: var(--accent-color-dark);
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
            background-color: var(--color-card);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
            border: 1px solid var(--color-border);
            position: relative;
            z-index: 1;
        }

        /* Dark theme table header styles */
        .dark-theme .table-header {
            background-color: var(--color-card);
            border-color: var(--color-border);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        /* Light theme table header styles */
        .light-theme .table-header {
            background-color: var(--color-card);
            border-color: var(--color-border);
            box-shadow: 0 4px 15px var(--color-shadow);
        }

        .table-header h2 {
            color: var(--color-highlight);
            font-size: 24px;
            margin: 0;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .header-controls {
            display: flex;
            flex-direction: column;
            gap: 20px;
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
            background-color: var(--color-highlight);
            color: white;
            padding: 10px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-icon {
            font-size: 16px;
            line-height: 1;
        }

        .btn-text {
            font-size: 14px;
            font-weight: 600;
        }

        .btn-secondary {
            background-color: var(--color-accent3);
            color: white;
            padding: 10px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-secondary:hover {
            background-color: var(--color-accent2);
            transform: translateY(-1px);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
            align-items: center;
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
        }

        .user-table tbody tr:hover {
            border-left-color: var(--color-highlight);
            background-color: rgba(161, 180, 84, 0.2);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(161, 180, 84, 0.15);
        }

        .user-table th,
        .user-table td {
            padding: 12px 8px;
            text-align: left;
            border-bottom: 1px solid rgba(161, 180, 84, 0.2);
            white-space: normal;
            word-wrap: break-word;
            word-break: break-word;
            overflow-wrap: break-word;
            font-size: 14px;
            font-weight: 500;
            vertical-align: middle;
            position: relative;
            line-height: 1.4;
            max-width: none;
            overflow: visible;
            text-overflow: clip;
        }

        /* Ensure actions column is always visible */
        .user-table th:last-child,
        .user-table td:last-child {
            white-space: nowrap;
            overflow: visible;
            text-overflow: clip;
            min-width: 120px;
            text-align: center;
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
            font-size: 14px;
            position: sticky;
            top: 0;
            background-color: var(--color-card);
            z-index: 10;
            border-bottom: 2px solid rgba(161, 180, 84, 0.4);
            padding-bottom: 12px;
            padding-top: 12px;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            text-transform: uppercase;
            letter-spacing: 0.3px;
            backdrop-filter: blur(10px);
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


    </style>
</head>
<body>
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
            <div>NutriSaur v1.0 • © 2023</div>
            <div style="margin-top: 10px;">Logged in as: <?php echo htmlspecialchars($username); ?></div>
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
                <div class="table-header">
                    <div class="header-controls">
                        <div class="search-row" style="justify-content: center; gap: 15px;">
                            <div class="search-container" style="width: 250px;">
                                <input type="text" id="searchInput" placeholder="Search by name, email..." class="search-input">
                                <button type="button" onclick="searchAssessments()" class="search-btn">🔍</button>
                            </div>
                            <div class="location-filter-container" style="width: 200px;">
                                <select id="sexFilter" onchange="filterBySex()" class="location-select">
                                    <option value="">All Genders</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div class="location-filter-container" style="width: 250px;">
                                <select id="standardFilter" onchange="filterByStandard()" class="location-select">
                                    <option value="weight-for-age">Weight-for-Age (0-71 months)</option>
                                    <option value="height-for-age">Height-for-Age (0-71 months)</option>
                                    <option value="weight-for-height">Weight-for-Height (65-120 cm)</option>
                                    <option value="weight-for-length">Weight-for-Length (45-110 cm)</option>
                                    <option value="bmi-for-age">BMI-for-Age (0-71 months)</option>
                                </select>
                            </div>
                        </div>

                        <!-- CSV Action Buttons -->
                        <div class="action-buttons" style="margin-top: 15px; text-align: center;">
                            <button class="btn btn-add" onclick="downloadCSVTemplate()">
                                <span class="btn-icon">📥</span>
                                <span class="btn-text">Download Template</span>
                            </button>
                            <button class="btn btn-add" onclick="showCSVImportModal()">
                                <span class="btn-icon">📁</span>
                                <span class="btn-text">Import CSV</span>
                            </button>
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
                            <th>WEIGHT (kg)</th>
                            <th>HEIGHT (cm)</th>
                            <th>BMI</th>
                            <th id="standardHeader">WEIGHT-FOR-AGE</th>
                            <th>CLASSIFICATION</th>
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
                                                $user['sex']
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
                                            
                                            // Format z-scores for display
                                            $wfa_display = $wfa_zscore !== null ? 'Z: ' . number_format($wfa_zscore, 2) . ' (' . $wfa_classification . ')' : 'N/A';
                                            $hfa_display = $hfa_zscore !== null ? 'Z: ' . number_format($hfa_zscore, 2) . ' (' . $hfa_classification . ')' : 'N/A';
                                            $wfh_display = $wfh_zscore !== null ? 'Z: ' . number_format($wfh_zscore, 2) . ' (' . $wfh_classification . ')' : 'N/A';
                                            $wfl_display = $wfl_zscore !== null ? 'Z: ' . number_format($wfl_zscore, 2) . ' (' . $wfl_classification . ')' : 'N/A';
                                            $bmi_display = $bmi_zscore !== null ? 'Z: ' . number_format($bmi_zscore, 2) . ' (' . $bmi_classification . ')' : 'N/A';
                                        } else {
                                            // Fallback to database values if WHO calculation fails
                                            $wfa_classification = $user['weight-for-age'] ?? $user['`weight-for-age`'] ?? 'N/A';
                                            $hfa_classification = $user['height-for-age'] ?? $user['`height-for-age`'] ?? 'N/A';
                                            $wfh_classification = $user['weight-for-height'] ?? $user['`weight-for-height`'] ?? 'N/A';
                                            $wfl_classification = $user['weight-for-length'] ?? $user['`weight-for-length`'] ?? 'N/A';
                                            $bmi_classification = $user['bmi_category'] ?? 'N/A';
                                            
                                            // Format z-scores for display
                                            $wfa_display = is_numeric($wfa_classification) ? 'Z: ' . number_format($wfa_classification, 2) : $wfa_classification;
                                            $hfa_display = is_numeric($hfa_classification) ? 'Z: ' . number_format($hfa_classification, 2) : $hfa_classification;
                                            $wfh_display = is_numeric($wfh_classification) ? 'Z: ' . number_format($wfh_classification, 2) : $wfh_classification;
                                            $wfl_display = is_numeric($wfl_classification) ? 'Z: ' . number_format($wfl_classification, 2) : $wfl_classification;
                                            $bmi_display = is_numeric($bmi_classification) ? 'Z: ' . number_format($bmi_classification, 2) : $bmi_classification;
                                        }
                                        
                                        // Calculate age in years and months
                                        $birthDate = new DateTime($user['birthday']);
                                        $today = new DateTime();
                                        $age = $today->diff($birthDate);
                                        $ageInMonths = ($age->y * 12) + $age->m;
                                        $ageDisplay = $age->y . 'y ' . $age->m . 'm';
                                        
                                        // Calculate BMI
                                        $bmi = $user['weight'] && $user['height'] ? round($user['weight'] / pow($user['height'] / 100, 2), 1) : 'N/A';
                                        
                                        // Generate all WHO Growth Standards data for this user
                                        $whoData = [
                                            'weight-for-age' => ['display' => $wfa_display, 'classification' => $wfa_classification],
                                            'height-for-age' => ['display' => $hfa_display, 'classification' => $hfa_classification],
                                            'weight-for-height' => ['display' => $wfh_display, 'classification' => $wfh_classification],
                                            'weight-for-length' => ['display' => $wfl_display, 'classification' => $wfl_classification],
                                            'bmi-for-age' => ['display' => $bmi_display, 'classification' => $bmi_classification]
                                        ];
                                        
                                        // Generate rows for each standard
                                        foreach ($whoData as $standard => $data) {
                                            $ageLimit = in_array($standard, ['weight-for-age', 'height-for-age', 'bmi-for-age']) ? $ageInMonths <= 71 : true;
                                            $heightLimit = true;
                                            
                                            if ($standard === 'weight-for-height') {
                                                $heightLimit = $user['height'] >= 65 && $user['height'] <= 120;
                                            } elseif ($standard === 'weight-for-length') {
                                                $heightLimit = $user['height'] >= 45 && $user['height'] <= 110;
                                            }
                                            
                                            if ($ageLimit && $heightLimit) {
                                                // For children over 71 months, show BMI only
                                                if ($ageInMonths > 71 && $standard !== 'bmi-for-age') {
                                                    continue; // Skip WHO standards for older children
                                                }
                                                
                                                // For older children, show BMI with adult classification
                                                if ($ageInMonths > 71 && $standard === 'bmi-for-age') {
                                                    $adultBmiClassification = getAdultBMIClassification($bmi);
                                                    $data['classification'] = $adultBmiClassification;
                                                    $data['display'] = 'BMI: ' . $bmi . ' (' . $adultBmiClassification . ')';
                                                }
                                                
                                                echo '<tr data-standard="' . $standard . '" data-age-months="' . $ageInMonths . '" data-height="' . $user['height'] . '">';
                                                echo '<td>' . htmlspecialchars($user['name'] ?? 'N/A') . '</td>';
                                                echo '<td>' . htmlspecialchars($user['email'] ?? 'N/A') . '</td>';
                                                echo '<td>' . $ageDisplay . '</td>';
                                                echo '<td>' . htmlspecialchars($user['weight'] ?? 'N/A') . '</td>';
                                                echo '<td>' . htmlspecialchars($user['height'] ?? 'N/A') . '</td>';
                                                echo '<td>' . $bmi . '</td>';
                                                echo '<td class="standard-value">' . htmlspecialchars($data['display']) . '</td>';
                                                echo '<td class="classification">' . htmlspecialchars($data['classification']) . '</td>';
                                                echo '<td class="action-buttons">';
                                                echo '<button class="btn-edit" onclick="editUser(\'' . htmlspecialchars($user['email']) . '\')" title="Edit User">✏️</button>';
                                                echo '<button class="btn-delete" onclick="deleteUser(\'' . htmlspecialchars($user['email']) . '\')" title="Delete User">🗑️</button>';
                                                echo '</td>';
                                                echo '</tr>';
                                            }
                                        }
                                    }
                                } else {
                                    // No users in database
                                    echo '<!-- No users in database -->';
                                }
                            } catch (Exception $e) {
                                echo '<tr><td colspan="8" class="no-data-message">Error loading users: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                        }
                    } else {
                            echo '<tr><td colspan="8" class="no-data-message">Database connection failed.</td></tr>';
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
                            <p style="margin: 0; color: var(--color-danger); font-weight: 600;">CSV data MUST use EXACTLY the same answer options as the mobile app. Any deviation will cause validation errors and prevent import.</p>
                        </div>
                    </div>
                </div>
                
                <h4>📋 CSV Import Instructions</h4>
                <p><strong>1.</strong> Download template with exact mobile app formats</p>
                <p><strong>2.</strong> Fill data using ONLY specified answer options</p>
                <p><strong>3.</strong> Upload your completed CSV file</p>
                <p><strong>4.</strong> Review and confirm import</p>
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

        // Initialize screening page
        document.addEventListener('DOMContentLoaded', function() {
            initializeTableFunctionality();
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

        function getBMICategory(bmi) {
            if (bmi < 18.5) return 'Underweight';
            if (bmi < 25) return 'Normal';
            if (bmi < 30) return 'Overweight';
            return 'Obese';
        }

        function getRiskLevel(score) {
            if (score <= 10) return 'Low';
            if (score <= 20) return 'Medium';
            return 'High';
        }

        // Search and filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchAssessments');
            const filterSelect = document.getElementById('filterRisk');
            
            if (searchInput) {
                searchInput.addEventListener('input', filterAssessments);
            }
            
            if (filterSelect) {
                filterSelect.addEventListener('change', filterAssessments);
            }
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
            // Load saved theme from localStorage
            const savedTheme = localStorage.getItem('theme');
            const body = document.body;
            const icon = document.querySelector('.new-theme-icon');
            
            if (savedTheme === 'dark') {
                body.classList.remove('light-theme');
                body.classList.add('dark-theme');
                icon.textContent = '🌙';
            } else {
                body.classList.remove('dark-theme');
                body.classList.add('light-theme');
                icon.textContent = '☀️';
            }
        });

        // Theme toggle
        document.getElementById('new-theme-toggle').addEventListener('click', function() {
            const body = document.body;
            const icon = this.querySelector('.new-theme-icon');
            
            if (body.classList.contains('dark-theme')) {
                body.classList.remove('dark-theme');
                body.classList.add('light-theme');
                icon.textContent = '☀️';
                localStorage.setItem('theme', 'light');
            } else {
                body.classList.remove('light-theme');
                body.classList.add('dark-theme');
                icon.textContent = '🌙';
                localStorage.setItem('theme', 'dark');
            }
        });

        // Enhanced MHO Assessment Table JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            initializeTableFunctionality();
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
            const tableRows = document.querySelectorAll('.user-table tbody tr');
            
            let visibleCount = 0;
            
            tableRows.forEach(row => {
                const name = row.cells[0].textContent.toLowerCase();
                const email = row.cells[1].textContent.toLowerCase();
                const age = row.cells[2].textContent.toLowerCase();
                const weight = row.cells[3].textContent.toLowerCase();
                const height = row.cells[4].textContent.toLowerCase();
                const bmi = row.cells[5].textContent.toLowerCase();
                const standardValue = row.cells[6].textContent.toLowerCase();
                const classification = row.cells[7].textContent.toLowerCase();
                
                const matchesSearch = name.includes(searchTerm) || 
                                   email.includes(searchTerm) || 
                                   age.includes(searchTerm) || 
                                   weight.includes(searchTerm) || 
                                   height.includes(searchTerm) ||
                                   bmi.includes(searchTerm) ||
                                   standardValue.includes(searchTerm) ||
                                   classification.includes(searchTerm);
                
                if (matchesSearch) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            updateNoDataMessage(visibleCount);
        }

        function filterByRisk() {
            const classificationFilter = document.getElementById('riskFilter').value;
            const tableRows = document.querySelectorAll('.user-table tbody tr');
            
            let visibleCount = 0;
            
            tableRows.forEach(row => {
                const wfa = row.cells[2].textContent.trim();
                const hfa = row.cells[3].textContent.trim();
                const wfh = row.cells[4].textContent.trim();
                const wfl = row.cells[5].textContent.trim();
                const bmi = row.cells[6].textContent.trim();
                
                let matchesFilter = true;
                
                if (classificationFilter) {
                    // Check if any of the WHO Growth Standards columns contain the filter term
                    matchesFilter = wfa.includes(classificationFilter) || 
                                  hfa.includes(classificationFilter) || 
                                  wfh.includes(classificationFilter) || 
                                  wfl.includes(classificationFilter) || 
                                  bmi.includes(classificationFilter);
                }
                
                if (matchesFilter) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            updateNoDataMessage(visibleCount);
        }

        function filterByLocation() {
            const locationFilter = document.getElementById('locationFilter').value;
            const tableRows = document.querySelectorAll('.user-table tbody tr');
            
            let visibleCount = 0;
            
            tableRows.forEach(row => {
                const location = row.cells[3].textContent.toLowerCase();
                
                if (!locationFilter || location.includes(locationFilter.toLowerCase())) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            updateNoDataMessage(visibleCount);
        }

        function filterByStandard() {
            const standardFilter = document.getElementById('standardFilter').value;
            const tableRows = document.querySelectorAll('.user-table tbody tr');
            const standardHeader = document.getElementById('standardHeader');
            
            // Update header based on selected standard
            const standardNames = {
                'weight-for-age': 'WEIGHT-FOR-AGE',
                'height-for-age': 'HEIGHT-FOR-AGE', 
                'weight-for-height': 'WEIGHT-FOR-HEIGHT',
                'weight-for-length': 'WEIGHT-FOR-LENGTH',
                'bmi-for-age': 'BMI-FOR-AGE'
            };
            
            standardHeader.textContent = standardNames[standardFilter] || 'WEIGHT-FOR-AGE';
            
            let visibleCount = 0;
            
            tableRows.forEach(row => {
                const rowStandard = row.dataset.standard;
                
                // Show only rows that match the selected standard
                if (rowStandard === standardFilter) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            updateNoDataMessage(visibleCount);
        }

        function updateNoDataMessage(visibleCount) {
            const noDataMessage = document.querySelector('.no-data-message');
            const tbody = document.querySelector('.user-table tbody');
            
                if (visibleCount === 0) {
                    if (!noDataMessage) {
                        const message = document.createElement('tr');
                        message.className = 'no-data-message';
                        message.innerHTML = '<td colspan="8"><div>No assessments found matching your criteria.</div></td>';
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
                ['name', 'email', 'municipality', 'barangay', 'sex', 'birthday', 'is_pregnant', 'weight', 'height', 'muac', 'screening_date'],
                ['John Doe', 'john@example.com', 'CITY OF BALANGA (Capital)', 'Bagumbayan', 'Male', '1999-01-15', 'No', '70', '175', '25', '2024-01-15 10:30:00']
            ];
            
            const csv = csvContent.map(row => row.map(field => `"${field}"`).join(',')).join('\n');
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'assessment_template.csv';
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
            showCSVStatus('info', 'CSV import functionality will be implemented in the backend.');
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
