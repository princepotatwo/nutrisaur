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
    
    $pdoOptions = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 30,
        PDO::ATTR_PERSISTENT => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
    ];
    
    $conn = new PDO($dsn, $mysql_user, $mysql_password, $pdoOptions);
    $conn->query("SELECT 1");
    
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    $conn = null;
    $dbError = "Database connection failed: " . $e->getMessage();
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
            'age' => $_POST['age'] ?? '',
            'age_months' => $_POST['age_months'] ?? '',
            'sex' => $_POST['sex'] ?? '',
            'pregnant' => $_POST['pregnant'] ?? '',
            'weight' => $_POST['weight'] ?? '',
            'height' => $_POST['height'] ?? '',
            'bmi' => $_POST['bmi'] ?? '',
            'meal_recall' => $_POST['meal_recall'] ?? '',
            'family_history' => $_POST['family_history'] ?? [],
            'lifestyle' => $_POST['lifestyle'] ?? '',
            'lifestyle_other' => $_POST['lifestyle_other'] ?? '',
            'immunization' => $_POST['immunization'] ?? [],
            'created_at' => date('Y-m-d H:i:s'),
            'user_id' => $user_id
        ];

        // Calculate BMI
        if (!empty($screening_data['weight']) && !empty($screening_data['height'])) {
            $weight = floatval($screening_data['weight']);
            $height = floatval($screening_data['height']) / 100; // Convert cm to meters
            $screening_data['bmi'] = round($weight / ($height * $height), 2);
        }

        // Insert into database
        $stmt = $conn->prepare("INSERT INTO screening_assessments (
            user_id, municipality, barangay, age, age_months, sex, pregnant, 
            weight, height, bmi, meal_recall, family_history, lifestyle, 
            lifestyle_other, immunization, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $screening_data['user_id'],
            $screening_data['municipality'],
            $screening_data['barangay'],
            $screening_data['age'],
            $screening_data['age_months'],
            $screening_data['sex'],
            $screening_data['pregnant'],
            $screening_data['weight'],
            $screening_data['height'],
            $screening_data['bmi'],
            $screening_data['meal_recall'],
            json_encode($screening_data['family_history']),
            $screening_data['lifestyle'],
            $screening_data['lifestyle_other'],
            json_encode($screening_data['immunization']),
            $screening_data['created_at']
        ]);

        $success_message = "Screening assessment saved successfully!";
        
    } catch (Exception $e) {
        $error_message = "Error saving screening assessment: " . $e->getMessage();
    }
}

// Get existing screening assessments
$screening_assessments = [];
if ($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM screening_assessments WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        $screening_assessments = $stmt->fetchAll();
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
    padding: 15px;
    margin-bottom: 15px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--color-border);
}

.deck-header {
    text-align: center;
    margin-bottom: 15px;
}

.deck-header h3 {
    color: var(--color-highlight);
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 3px;
}

.deck-header p {
    color: var(--color-text);
    font-size: 13px;
    opacity: 0.8;
    margin: 0;
}

.deck-wrapper {
    position: relative;
    overflow: hidden;
}

.deck-container {
    position: relative;
    height: 350px;
    border-radius: 24px;
    border: 1px solid var(--color-border);
    background: linear-gradient(135deg, var(--color-card) 0%, rgba(161, 180, 84, 0.05) 100%);
    backdrop-filter: blur(10px);
    overflow: hidden;
}

.deck-cards {
    display: flex;
    gap: 12px;
    padding: 20px;
    height: 100%;
    align-items: center;
    justify-content: center;
    overflow-x: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
    scroll-behavior: smooth;
}

.deck-cards::-webkit-scrollbar {
    display: none;
}

.deck-card {
    position: relative;
    width: 200px;
    height: 280px;
    flex-shrink: 0;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    transform: translateX(0px) translateY(0px) scale(1);
}

.deck-card:hover {
    transform: translateY(-10px);
}

.card-main {
    width: 100%;
    height: 100%;
    border-radius: 16px;
    border: 1px solid var(--color-border);
    background: linear-gradient(135deg, var(--color-card) 0%, rgba(161, 180, 84, 0.1) 100%);
    backdrop-filter: blur(10px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    padding: 20px;
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
    position: relative;
    z-index: 10;
}



.card-header {
    text-align: center;
    margin-bottom: 20px;
}

.card-header h4 {
    color: var(--color-highlight);
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 5px;
}

.card-location {
    color: var(--color-text);
    font-size: 12px;
    opacity: 0.7;
}

.card-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.card-stat {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
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
    font-size: 14px;
    font-weight: 600;
}

.bmi-normal {
    color: #4CAF50 !important;
}

.bmi-overweight {
    color: #FF9800 !important;
}

.bmi-underweight {
    color: #F44336 !important;
}

.bmi-obese {
    color: #D32F2F !important;
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
}

.risk-medium-risk {
    color: #FF9800 !important;
}

.risk-high-risk {
    color: #F44336 !important;
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
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

        .risk-level {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: bold;
            text-transform: uppercase;
        }

        .risk-level.low {
            background: #d4edda;
            color: #155724;
        }

        .risk-level.medium {
            background: #fff3cd;
            color: #856404;
        }

        .risk-level.high {
            background: #f8d7da;
            color: #721c24;
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
                <!-- Card Deck Fan Component -->
                <div class="card-deck-container">
                    <div class="deck-header">
                        <h3>🎴 Community Assessment Cards</h3>
                        <p>Click on a community member card to view detailed nutritional assessment information</p>
                    </div>
                    
                    <div class="deck-wrapper">
                        <div class="deck-container">
                            <div class="deck-cards">
                                <?php
                                // Sample user data based on Decision Tree Nutritional Assessment
                                $sample_users = [
                                    [
                                        'name' => 'Maria Santos',
                                        'age' => '28',
                                        'sex' => 'Female',
                                        'pregnant' => 'No',
                                        'municipality' => 'Balanga',
                                        'barangay' => 'Bagumbayan',
                                        'height' => '158',
                                        'weight' => '55',
                                        'bmi' => '22.0',
                                        'bmi_category' => 'Normal',
                                        'meal_assessment' => 'Balanced',
                                        'family_history' => ['Hypertension'],
                                        'lifestyle' => 'Active',
                                        'immunization_status' => 'Complete',
                                        'risk_factors' => ['Family History: Hypertension'],
                                        'risk_score' => '8',
                                        'risk_level' => 'Low Risk',
                                        'recommendation' => 'Maintain current healthy lifestyle',
                                        'intervention' => 'Regular monitoring'
                                    ],
                                    [
                                        'name' => 'Juan Dela Cruz',
                                        'age' => '45',
                                        'sex' => 'Male',
                                        'pregnant' => 'Not Applicable',
                                        'municipality' => 'Balanga',
                                        'barangay' => 'Cupang Proper',
                                        'height' => '170',
                                        'weight' => '78',
                                        'bmi' => '27.0',
                                        'bmi_category' => 'Overweight',
                                        'meal_assessment' => 'At Risk',
                                        'family_history' => ['Diabetes', 'Hypertension'],
                                        'lifestyle' => 'Sedentary',
                                        'immunization_status' => 'Complete',
                                        'risk_factors' => ['BMI: Overweight', 'Unbalanced Diet', 'Sedentary Lifestyle', 'Family History: Diabetes, Hypertension'],
                                        'risk_score' => '22',
                                        'risk_level' => 'High Risk',
                                        'recommendation' => 'Immediate lifestyle intervention needed',
                                        'intervention' => 'Nutrition counseling, physical activity program, regular health monitoring'
                                    ],
                                    [
                                        'name' => 'Ana Reyes',
                                        'age' => '32',
                                        'sex' => 'Female',
                                        'pregnant' => 'No',
                                        'municipality' => 'Balanga',
                                        'barangay' => 'Poblacion',
                                        'height' => '162',
                                        'weight' => '48',
                                        'bmi' => '18.3',
                                        'bmi_category' => 'Underweight',
                                        'meal_assessment' => 'At Risk',
                                        'family_history' => ['Malnutrition'],
                                        'lifestyle' => 'Active',
                                        'immunization_status' => 'Complete',
                                        'risk_factors' => ['BMI: Underweight', 'Unbalanced Diet', 'Family History: Malnutrition'],
                                        'risk_score' => '18',
                                        'risk_level' => 'Medium Risk',
                                        'recommendation' => 'Nutrition intervention needed',
                                        'intervention' => 'DOH feeding program, nutrition counseling'
                                    ],
                                    [
                                        'name' => 'Pedro Martinez',
                                        'age' => '38',
                                        'sex' => 'Male',
                                        'pregnant' => 'Not Applicable',
                                        'municipality' => 'Balanga',
                                        'barangay' => 'Sibacan',
                                        'height' => '175',
                                        'weight' => '85',
                                        'bmi' => '27.8',
                                        'bmi_category' => 'Overweight',
                                        'meal_assessment' => 'Balanced',
                                        'family_history' => ['Heart Disease'],
                                        'lifestyle' => 'Sedentary',
                                        'immunization_status' => 'Complete',
                                        'risk_factors' => ['BMI: Overweight', 'Sedentary Lifestyle', 'Family History: Heart Disease'],
                                        'risk_score' => '25',
                                        'risk_level' => 'High Risk',
                                        'recommendation' => 'Immediate intervention needed',
                                        'intervention' => 'Cardiac consultation, weight management program, physical activity'
                                    ],
                                    [
                                        'name' => 'Luz Fernandez',
                                        'age' => '25',
                                        'sex' => 'Female',
                                        'pregnant' => 'No',
                                        'municipality' => 'Balanga',
                                        'barangay' => 'Tenejero',
                                        'height' => '155',
                                        'weight' => '52',
                                        'bmi' => '21.6',
                                        'bmi_category' => 'Normal',
                                        'meal_assessment' => 'Balanced',
                                        'family_history' => ['None'],
                                        'lifestyle' => 'Active',
                                        'immunization_status' => 'Complete',
                                        'risk_factors' => ['None'],
                                        'risk_score' => '5',
                                        'risk_level' => 'Low Risk',
                                        'recommendation' => 'Maintain current status',
                                        'intervention' => 'Regular health monitoring'
                                    ]
                                ];
                                ?>
                                
                                <?php foreach ($sample_users as $index => $user): ?>
                                <div class="deck-card" data-index="<?php echo $index; ?>">
                                    <div class="card-main">
                                        <div class="card-header">
                                            <h4><?php echo htmlspecialchars($user['name']); ?></h4>
                                            <span class="card-location"><?php echo htmlspecialchars($user['barangay'] . ', ' . $user['municipality']); ?></span>
                                        </div>
                                        <div class="card-content">
                                            <div class="card-stat">
                                                <span class="stat-label">Age/Sex</span>
                                                <span class="stat-value"><?php echo $user['age']; ?>y, <?php echo $user['sex']; ?></span>
                                            </div>
                                            <div class="card-stat">
                                                <span class="stat-label">BMI</span>
                                                <span class="stat-value bmi-<?php echo strtolower($user['bmi_category']); ?>"><?php echo $user['bmi']; ?> (<?php echo $user['bmi_category']; ?>)</span>
                                            </div>
                                            <div class="card-stat">
                                                <span class="stat-label">Diet</span>
                                                <span class="stat-value diet-<?php echo strtolower(str_replace(' ', '-', $user['meal_assessment'])); ?>"><?php echo $user['meal_assessment']; ?></span>
                                            </div>
                                            <div class="card-stat">
                                                <span class="stat-label">Lifestyle</span>
                                                <span class="stat-value lifestyle-<?php echo strtolower($user['lifestyle']); ?>"><?php echo $user['lifestyle']; ?></span>
                                            </div>
                                            <div class="card-stat">
                                                <span class="stat-label">Risk Level</span>
                                                <span class="stat-value risk-<?php echo strtolower(str_replace(' ', '-', $user['risk_level'])); ?>"><?php echo $user['risk_level']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    

                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>


        </div>
    </div>

    <script>
        // Municipalities and Barangays data
        const municipalities = <?php echo json_encode($municipalities); ?>;

        // Initialize form
        document.addEventListener('DOMContentLoaded', function() {
            initializeForm();
        });

        function initializeForm() {
            // Municipality change handler
            document.getElementById('municipality').addEventListener('change', function() {
                const municipality = this.value;
                const barangaySelect = document.getElementById('barangay');
                
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                
                if (municipality && municipalities[municipality]) {
                    municipalities[municipality].forEach(barangay => {
                        const option = document.createElement('option');
                        option.value = barangay;
                        option.textContent = barangay;
                        barangaySelect.appendChild(option);
                    });
                }
            });

            // Age input handler
            document.getElementById('age').addEventListener('input', function() {
                const age = parseInt(this.value);
                const monthsField = document.getElementById('months-field');
                
                if (age < 1) {
                    monthsField.classList.add('show');
                } else {
                    monthsField.classList.remove('show');
                }
                
                // Show/hide immunization section
                const immunizationSection = document.getElementById('immunization-section');
                if (age <= 12) {
                    immunizationSection.classList.add('show');
                } else {
                    immunizationSection.classList.remove('show');
                }
            });

            // Sex change handler
            document.querySelectorAll('input[name="sex"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    const pregnantField = document.getElementById('pregnant-field');
                    const age = parseInt(document.getElementById('age').value);
                    
                    if (this.value === 'Female' && age >= 12 && age <= 50) {
                        pregnantField.classList.add('show');
                    } else {
                        pregnantField.classList.remove('show');
                    }
                });
            });

            // BMI calculation
            document.getElementById('weight').addEventListener('input', calculateBMI);
            document.getElementById('height').addEventListener('input', calculateBMI);

            // Lifestyle other field
            document.querySelectorAll('input[name="lifestyle"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    const otherField = document.getElementById('lifestyle-other-field');
                    if (this.value === 'Other') {
                        otherField.classList.add('show');
                    } else {
                        otherField.classList.remove('show');
                    }
                });
            });

            // Meal analysis
            document.getElementById('meal_recall').addEventListener('input', analyzeMeal);

            // Form validation
            document.getElementById('screeningForm').addEventListener('submit', validateForm);
        }

        function calculateBMI() {
            const weight = parseFloat(document.getElementById('weight').value);
            const height = parseFloat(document.getElementById('height').value);
            
            if (weight && height) {
                const heightM = height / 100;
                const bmi = weight / (heightM * heightM);
                
                document.getElementById('bmi-value').textContent = bmi.toFixed(2);
                
                let category = '';
                if (bmi < 18.5) category = 'Underweight';
                else if (bmi < 25) category = 'Normal';
                else if (bmi < 30) category = 'Overweight';
                else category = 'Obese';
                
                document.getElementById('bmi-category').textContent = 'Category: ' + category;
                document.getElementById('bmi-display').style.display = 'block';
            }
        }

        function analyzeMeal() {
            const mealText = this.value.toLowerCase();
            const foodGroups = {
                carbs: ['rice', 'bread', 'pasta', 'potato', 'corn', 'cereal', 'oatmeal'],
                protein: ['meat', 'fish', 'chicken', 'pork', 'beef', 'egg', 'milk', 'cheese', 'beans', 'tofu'],
                vegetables: ['vegetable', 'carrot', 'broccoli', 'spinach', 'lettuce', 'tomato', 'onion'],
                fruits: ['fruit', 'apple', 'banana', 'orange', 'mango', 'grape']
            };
            
            let foundGroups = [];
            Object.keys(foodGroups).forEach(group => {
                if (foodGroups[group].some(food => mealText.includes(food))) {
                    foundGroups.push(group);
                }
            });
            
            const analysis = document.getElementById('meal-analysis');
            if (foundGroups.length >= 3) {
                analysis.textContent = '✅ Balanced diet detected';
                analysis.className = 'success-message';
            } else {
                analysis.textContent = '⚠️ At Risk: Missing major food groups';
                analysis.className = 'error-message';
            }
        }

        function validateForm(e) {
            let isValid = true;
            
            // Age validation
            const age = parseInt(document.getElementById('age').value);
            if (age < 0 || age > 120) {
                document.getElementById('age-error').textContent = 'Age cannot be negative or > 120 years';
                isValid = false;
            } else {
                document.getElementById('age-error').textContent = '';
            }
            
            // Weight validation
            const weight = parseFloat(document.getElementById('weight').value);
            const ageForValidation = parseInt(document.getElementById('age').value);
            if (ageForValidation < 5 && weight > 50) {
                document.getElementById('weight-error').textContent = 'Weight seems unusually high for age < 5';
                isValid = false;
            } else if (weight < 2 || weight > 250) {
                document.getElementById('weight-error').textContent = 'Weight must be between 2-250 kg';
                isValid = false;
            } else {
                document.getElementById('weight-error').textContent = '';
            }
            
            // Height validation
            const height = parseFloat(document.getElementById('height').value);
            if (ageForValidation < 5 && height > 130) {
                document.getElementById('height-error').textContent = 'Height seems unusually high for age < 5';
                isValid = false;
            } else if (height < 30 || height > 250) {
                document.getElementById('height-error').textContent = 'Height must be between 30-250 cm';
                isValid = false;
            } else {
                document.getElementById('height-error').textContent = '';
            }
            
            // Family history validation
            const familyHistoryCheckboxes = document.querySelectorAll('input[name="family_history[]"]:checked');
            if (familyHistoryCheckboxes.length === 0) {
                document.getElementById('family-history-error').textContent = 'Please select at least one option or choose None';
                isValid = false;
            } else {
                document.getElementById('family-history-error').textContent = '';
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        }

        function viewAssessmentDetails(id) {
            // Fetch assessment details via AJAX
            fetch(`/api/comprehensive_screening.php?screening_id=${id}`)
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

        // Theme toggle
        document.getElementById('new-theme-toggle').addEventListener('click', function() {
            const body = document.body;
            const icon = this.querySelector('.new-theme-icon');
            
            if (body.classList.contains('dark-theme')) {
                body.classList.remove('dark-theme');
                body.classList.add('light-theme');
                icon.textContent = '☀️';
            } else {
                body.classList.remove('light-theme');
                body.classList.add('dark-theme');
                icon.textContent = '🌙';
            }
        });

        // Card Deck Modal JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            const deckCards = document.querySelectorAll('.deck-card');
            
            deckCards.forEach((card, index) => {
                // Click to show modal
                card.addEventListener('click', function() {
                    showCardModal(index);
                });
            });
            
            function showCardModal(cardIndex) {
                const card = deckCards[cardIndex];
                const cardData = <?php echo json_encode($sample_users); ?>;
                const user = cardData[cardIndex];
                
                // Create modal
                const modal = document.createElement('div');
                modal.className = 'card-modal';
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
                    max-width: 600px;
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
                
                // Risk level calculation
                let riskLevel = 'Low Risk';
                let riskColor = '#4CAF50';
                if (user.risk_score > 20) {
                    riskLevel = 'High Risk';
                    riskColor = '#F44336';
                } else if (user.risk_score > 10) {
                    riskLevel = 'Medium Risk';
                    riskColor = '#FF9800';
                }
                
                // Recommendation
                let recommendation = 'Maintain current status';
                if (user.risk_score > 20) {
                    recommendation = 'Immediate intervention needed';
                } else if (user.risk_score > 10) {
                    recommendation = 'Regular monitoring';
                }
                
                modalContent.innerHTML = `
                    <h2 style="color: var(--color-highlight); margin-bottom: 25px; text-align: center; font-size: 28px;">
                        ${user.name} - Decision Tree Assessment
                    </h2>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div style="background: rgba(161, 180, 84, 0.1); padding: 15px; border-radius: 12px; border: 1px solid var(--color-border);">
                            <h3 style="color: var(--color-highlight); margin-bottom: 12px; font-size: 16px;">📋 Basic Information</h3>
                            <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>Name:</strong> ${user.name}</p>
                            <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>Age:</strong> ${user.age} years</p>
                            <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>Sex:</strong> ${user.sex}</p>
                            <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>Pregnant:</strong> ${user.pregnant}</p>
                            <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>Location:</strong> ${user.barangay}, ${user.municipality}</p>
                        </div>
                        
                        <div style="background: rgba(161, 180, 84, 0.1); padding: 15px; border-radius: 12px; border: 1px solid var(--color-border);">
                            <h3 style="color: var(--color-highlight); margin-bottom: 12px; font-size: 16px;">📏 Anthropometric Data</h3>
                            <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>Height:</strong> ${user.height} cm</p>
                            <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>Weight:</strong> ${user.weight} kg</p>
                            <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>BMI:</strong> ${user.bmi}</p>
                            <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>Category:</strong> <span style="color: ${user.bmi_category === 'Normal' ? '#4CAF50' : user.bmi_category === 'Overweight' ? '#FF9800' : user.bmi_category === 'Underweight' ? '#F44336' : '#D32F2F'}">${user.bmi_category}</span></p>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div style="background: rgba(161, 180, 84, 0.1); padding: 15px; border-radius: 12px; border: 1px solid var(--color-border);">
                            <h3 style="color: var(--color-highlight); margin-bottom: 12px; font-size: 16px;">🍽️ Meal Assessment</h3>
                            <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>24-Hour Recall:</strong> <span style="color: ${user.meal_assessment === 'Balanced' ? '#4CAF50' : '#FF9800'}">${user.meal_assessment}</span></p>
                        </div>
                        
                        <div style="background: rgba(161, 180, 84, 0.1); padding: 15px; border-radius: 12px; border: 1px solid var(--color-border);">
                            <h3 style="color: var(--color-highlight); margin-bottom: 12px; font-size: 16px;">🏃 Lifestyle</h3>
                            <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>Activity Level:</strong> <span style="color: ${user.lifestyle === 'Active' ? '#4CAF50' : '#FF9800'}">${user.lifestyle}</span></p>
                        </div>
                    </div>
                    
                    <div style="background: rgba(161, 180, 84, 0.1); padding: 15px; border-radius: 12px; border: 1px solid var(--color-border); margin-bottom: 20px;">
                        <h3 style="color: var(--color-highlight); margin-bottom: 12px; font-size: 16px;">👨‍👩‍👧‍👦 Family History</h3>
                        <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>Conditions:</strong> ${user.family_history.join(', ')}</p>
                    </div>
                    
                    <div style="background: rgba(161, 180, 84, 0.1); padding: 15px; border-radius: 12px; border: 1px solid var(--color-border); margin-bottom: 20px;">
                        <h3 style="color: var(--color-highlight); margin-bottom: 12px; font-size: 16px;">💉 Immunization Status</h3>
                        <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>Status:</strong> <span style="color: ${user.immunization_status === 'Complete' ? '#4CAF50' : '#FF9800'}">${user.immunization_status}</span></p>
                    </div>
                    
                    <div style="background: rgba(161, 180, 84, 0.1); padding: 15px; border-radius: 12px; border: 1px solid var(--color-border); margin-bottom: 20px;">
                        <h3 style="color: var(--color-highlight); margin-bottom: 12px; font-size: 16px;">⚠️ Risk Factors</h3>
                        <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>Identified:</strong> ${user.risk_factors.join(', ')}</p>
                        <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>Risk Score:</strong> ${user.risk_score}</p>
                        <p style="color: var(--color-text); margin-bottom: 6px; font-size: 13px;"><strong>Risk Level:</strong> <span style="color: ${riskColor}">${user.risk_level}</span></p>
                    </div>
                    
                    <div style="background: rgba(161, 180, 84, 0.15); padding: 15px; border-radius: 12px; border: 1px solid var(--color-highlight);">
                        <h3 style="color: var(--color-highlight); margin-bottom: 12px; font-size: 16px;">💡 Decision Tree Recommendation</h3>
                        <p style="color: var(--color-text); margin-bottom: 8px; font-size: 14px;"><strong>Assessment:</strong> ${user.recommendation}</p>
                        <p style="color: var(--color-text); margin-bottom: 0; font-size: 14px;"><strong>Intervention:</strong> ${user.intervention}</p>
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
        });

        function showFanCardDetails(fanCard) {
            const cardType = fanCard.dataset.type;
            const content = fanCard.querySelector('.fan-content').innerHTML;
            
            // Create modal for detailed view
            const modal = document.createElement('div');
            modal.className = 'fan-modal';
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
                max-width: 500px;
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
                <h3 style="color: var(--color-highlight); margin-bottom: 20px; text-align: center; font-size: 24px;">
                    ${cardType.charAt(0).toUpperCase() + cardType.slice(1)} Assessment Details
                </h3>
                <div style="color: var(--color-text);">
                    ${content}
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
    </script>
</body>
</html>
