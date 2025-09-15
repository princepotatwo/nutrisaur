<?php
/**
 * Community Users API
 * Handles CSV import and other operations for community users
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Include the main configuration file
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/DatabaseHelper.php';

try {
    // Check if config.php exists
    if (!file_exists(__DIR__ . '/../../config.php')) {
        throw new Exception("Config file not found at: " . __DIR__ . '/../../config.php');
    }
    
    // Use DatabaseHelper like screening.php does
    $db = DatabaseHelper::getInstance();
    if (!$db->isAvailable()) {
        throw new Exception("Database connection not available");
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'import_community_users') {
            handleCSVImport($db);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

function handleCSVImport($db) {
    if (!isset($_FILES['csvFile']) || $_FILES['csvFile']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No CSV file uploaded or upload error']);
        return;
    }
    
    // Define exact municipality and barangay data from mobile app
    $validMunicipalities = [
        "ABUCAY", "BAGAC", "CITY OF BALANGA", "DINALUPIHAN", "HERMOSA", 
        "LIMAY", "MARIVELES", "MORONG", "ORANI", "ORION", "PILAR", "SAMAL"
    ];
    
    $barangayMap = [
        "ABUCAY" => ["Bangkal", "Calaylayan (Pob.)", "Capitangan", "Gabon", "Laon (Pob.)", 
                    "Mabatang", "Omboy", "Salian", "Wawa (Pob.)"],
        "BAGAC" => ["Bagumbayan (Pob.)", "Banawang", "Binuangan", "Binukawan", "Ibaba", 
                   "Ibis", "Pag-asa (Wawa-Sibacan)", "Parang", "Paysawan", "Quinawan", 
                   "San Antonio", "Saysain", "Tabing-Ilog (Pob.)", "Atilano L. Ricardo"],
        "CITY OF BALANGA" => ["Bagong Silang", "Bagumbayan", "Batanes", "Cataning", "Central", 
                             "Dangcol", "Doña Francisca", "Lote", "Malabia", "Munting Batangas", 
                             "Poblacion", "Puerto Rivas Ibaba", "Puerto Rivas Itaas", "San Jose", 
                             "Sibacan", "Talipapa", "Tanato", "Tenejero", "Tortugas", "Tuyo"],
        "DINALUPIHAN" => ["Bayan-bayanan", "Bonifacio", "Burgos", "Daang Bago", "Del Pilar", 
                         "General Emilio Aguinaldo", "General Luna", "Kawayan", "Layac", 
                         "Lourdes", "Luakan", "Maligaya", "Naparing", "Paco", "Pag-asa", 
                         "Pag-asa (Wawa-Sibacan)", "Poblacion", "Rizal", "Saguing", "San Benito", 
                         "San Isidro", "San Ramon", "Santo Niño", "Sapang Kawayan", "Tipo", 
                         "Tubo-tubo", "Zamora"],
        "HERMOSA" => ["A. Ricardo", "Almacen", "Bamban", "Burgos-Soliman", "Cataning", 
                     "Culis", "Daungan", "Judicial", "Mabiga", "Mabuco", "Maite", 
                     "Mambog - Mandama", "Palihan", "Pandatung", "Poblacion", "Saba", 
                     "Sacatihan", "Sumalo", "Tipo", "Tortugas"],
        "LIMAY" => ["Alangan", "Kitang I", "Kitang II", "Kitang III", "Kitang IV", 
                   "Kitang V", "Lamao", "Luz", "Poblacion", "Reforma", "Sitio Baga", 
                   "Sitio Pulo", "Wawa"],
        "MARIVELES" => ["Alion", "Balong Anito", "Baseco Country", "Batan", "Biaan", 
                       "Cabcaben", "Camaya", "Lucanin", "Mabayo", "Malaya", "Maligaya", 
                       "Mountain View", "Poblacion", "San Carlos", "San Isidro", "San Nicolas", 
                       "Saysain", "Sisiman", "Townsite", "Vista Alegre"],
        "MORONG" => ["Binaritan", "Mabayo", "Nagbalayong", "Poblacion", "Sabang", 
                    "San Jose", "Sitio Pulo"],
        "ORANI" => ["Apollo", "Bagong Paraiso", "Balut", "Bayani", "Cabral", "Calero", 
                   "Calutit", "Camachile", "Kaparangan", "Luna", "Mabolo", "Magtaong", 
                   "Maligaya", "Pag-asa", "Paglabanan", "Pagtakhan", "Palihan", 
                   "Poblacion", "Rizal", "Sagrada", "San Jose", "Sulong", "Tagumpay", 
                   "Tala", "Talimundoc", "Tapulao", "Tugatog", "Wawa"],
        "ORION" => ["Balagtas", "Balut", "Bantan", "Bilolo", "Calungusan", "Camachile", 
                   "Kapunitan", "Lati", "Puting Bato", "Sabatan", "San Vicente", 
                   "Wawa", "Poblacion"],
        "PILAR" => ["Alas-asin", "Bantan Munti", "Bantan", "Del Rosario", "Diwa", 
                   "Landing", "Liwayway", "Nagbalayong", "Panilao", "Pantingan", 
                   "Poblacion", "Rizal", "Saguing", "Santo Cristo", "Wakas"],
        "SAMAL" => ["Bagumbayan", "Bantan", "Bilolo", "Calungusan", "Camachile", 
                   "Kapunitan", "Lati", "Puting Bato", "Sabatan", "San Vicente", 
                   "Wawa", "Balagtas", "Balut", "Bataan"]
    ];
    
    $file = $_FILES['csvFile'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    
    // Validate file type
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if ($fileExtension !== 'csv') {
        echo json_encode(['success' => false, 'message' => 'Please upload a CSV file']);
        return;
    }
    
    // Read CSV file
    $csvData = [];
    if (($handle = fopen($fileTmpName, 'r')) !== FALSE) {
        $header = fgetcsv($handle); // Read header row
        
        // Validate header - STRICT template format validation
        $expectedHeaders = ['name', 'email', 'password', 'municipality', 'barangay', 'sex', 'birthday', 'is_pregnant', 'weight_kg', 'height_cm', 'muac_cm', 'screening_id', 'screening_date'];
        
        // Check if headers match exactly (case-sensitive, order-sensitive)
        if ($header !== $expectedHeaders) {
            $missingHeaders = array_diff($expectedHeaders, $header);
            $extraHeaders = array_diff($header, $expectedHeaders);
            $errorMsg = "CSV format does not match template exactly. ";
            if (!empty($missingHeaders)) {
                $errorMsg .= "Missing columns: " . implode(', ', $missingHeaders) . ". ";
            }
            if (!empty($extraHeaders)) {
                $errorMsg .= "Extra columns: " . implode(', ', $extraHeaders) . ". ";
            }
            $errorMsg .= "Required format: " . implode(', ', $expectedHeaders);
            echo json_encode(['success' => false, 'message' => $errorMsg]);
            fclose($handle);
            return;
        }
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            // Skip empty rows
            if (empty(array_filter($data, function($value) { return trim($value) !== ''; }))) {
                continue;
            }
            
            // Strict validation: must have exactly the expected number of columns
            if (count($data) !== count($header)) {
                $errors[] = "Row " . (count($csvData) + 2) . ": Incorrect number of columns. Expected " . count($header) . ", got " . count($data);
                continue;
            }
            
            $csvData[] = array_combine($header, $data);
        }
        fclose($handle);
    } else {
        echo json_encode(['success' => false, 'message' => 'Could not read CSV file']);
        return;
    }
    
    if (empty($csvData)) {
        echo json_encode(['success' => false, 'message' => 'No data found in CSV file']);
        return;
    }
    
    $importedCount = 0;
    $errors = [];
    
    try {
        foreach ($csvData as $rowIndex => $row) {
            $rowNumber = $rowIndex + 2; // +2 because we skip header and arrays are 0-indexed
            
            // Clean and validate data - match mobile app column names exactly
            $name = trim($row['name'] ?? '');
            $email = trim($row['email'] ?? '');
            $password = trim($row['password'] ?? '');
            $municipality = trim($row['municipality'] ?? '');
            $barangay = trim($row['barangay'] ?? '');
            $sex = trim($row['sex'] ?? '');
            $birthday = trim($row['birthday'] ?? '');
            $isPregnant = trim($row['is_pregnant'] ?? '');
            $weight = floatval($row['weight_kg'] ?? 0);
            $height = floatval($row['height_cm'] ?? 0);
            $muac = floatval($row['muac_cm'] ?? 0);
            $screeningId = trim($row['screening_id'] ?? '');
            $screeningDate = trim($row['screening_date'] ?? '');
            
            // Validate required fields - match mobile app validation exactly
            
            // Name validation
            if (empty($name)) {
                $errors[] = "Row $rowNumber: Name is required";
                continue;
            }
            if (strlen($name) < 2) {
                $errors[] = "Row $rowNumber: Name must be at least 2 characters long";
                continue;
            }
            
            // Email validation - match mobile app pattern
            if (empty($email)) {
                $errors[] = "Row $rowNumber: Email is required";
                continue;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Row $rowNumber: Please enter a valid email";
                continue;
            }
            
            // Password validation - 6+ characters required
            if (empty($password)) {
                $errors[] = "Row $rowNumber: Password is required";
                continue;
            }
            if (strlen($password) < 6) {
                $errors[] = "Row $rowNumber: Password must be at least 6 characters long";
                continue;
            }
            
            // Municipality validation - exact list from mobile app
            if (empty($municipality)) {
                $errors[] = "Row $rowNumber: Municipality is required";
                continue;
            }
            if (!in_array($municipality, $validMunicipalities)) {
                $errors[] = "Row $rowNumber: Invalid municipality. Must be one of: " . implode(', ', $validMunicipalities);
                continue;
            }
            
            // Barangay validation - must match municipality
            if (empty($barangay)) {
                $errors[] = "Row $rowNumber: Barangay is required";
                continue;
            }
            if (!isset($barangayMap[$municipality]) || !in_array($barangay, $barangayMap[$municipality])) {
                $errors[] = "Row $rowNumber: Invalid barangay '$barangay' for municipality '$municipality'";
                continue;
            }
            
            // Sex validation - match mobile app exactly (only Male, Female)
            if (!in_array($sex, ['Male', 'Female'])) {
                $errors[] = "Row $rowNumber: Sex must be Male or Female";
                continue;
            }
            
            if (empty($birthday)) {
                $errors[] = "Row $rowNumber: Birthday is required";
                continue;
            }
            
            // Strict date format validation - must match exactly YYYY-MM-DD
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthday)) {
                $errors[] = "Row $rowNumber: Birthday must be in exact format YYYY-MM-DD (e.g., 1999-01-15)";
                continue;
            }
            
            $birthDate = DateTime::createFromFormat('Y-m-d', $birthday);
            if (!$birthDate || $birthDate->format('Y-m-d') !== $birthday) {
                $errors[] = "Row $rowNumber: Invalid birthday date. Use valid YYYY-MM-DD format";
                continue;
            }
            
            // Calculate age for validation only
            $today = new DateTime();
            $age = $today->diff($birthDate)->y;
            
            if ($age < 0 || $age > 150) {
                $errors[] = "Row $rowNumber: Invalid age: $age years";
                continue;
            }
            
            // Screening ID validation
            if (empty($screeningId)) {
                $errors[] = "Row $rowNumber: Screening ID is required";
                continue;
            }
            if (!preg_match('/^SCR-\d{4}-\d{3}$/', $screeningId)) {
                $errors[] = "Row $rowNumber: Screening ID must be in format SCR-YYYY-XXX (e.g., SCR-2025-001)";
                continue;
            }
            
            // Strict validation for measurements - match mobile app exactly
            if (!is_numeric($row['weight_kg']) || $weight <= 0 || $weight > 1000) {
                $errors[] = "Row $rowNumber: Weight must be a number between 0.1 and 1000 kg";
                continue;
            }
            
            if (!is_numeric($row['height_cm']) || $height <= 0 || $height > 300) {
                $errors[] = "Row $rowNumber: Height must be a number between 1 and 300 cm";
                continue;
            }
            
            if (!is_numeric($row['muac_cm']) || $muac <= 0 || $muac > 50) {
                $errors[] = "Row $rowNumber: MUAC must be a number between 5 and 50 cm";
                continue;
            }
            
            // Additional strict validation for decimal places
            if (strpos($row['weight_kg'], '.') !== false && strlen(substr($row['weight_kg'], strpos($row['weight_kg'], '.') + 1)) > 2) {
                $errors[] = "Row $rowNumber: Weight can have maximum 2 decimal places";
                continue;
            }
            
            if (strpos($row['height_cm'], '.') !== false && strlen(substr($row['height_cm'], strpos($row['height_cm'], '.') + 1)) > 2) {
                $errors[] = "Row $rowNumber: Height can have maximum 2 decimal places";
                continue;
            }
            
            if (strpos($row['muac_cm'], '.') !== false && strlen(substr($row['muac_cm'], strpos($row['muac_cm'], '.') + 1)) > 2) {
                $errors[] = "Row $rowNumber: MUAC can have maximum 2 decimal places";
                continue;
            }
            
            // Validate pregnancy status - match mobile app exactly
            $isPregnantValue = null;
            if (strtolower($isPregnant) === 'yes') {
                $isPregnantValue = 1;
            } elseif (strtolower($isPregnant) === 'no') {
                $isPregnantValue = 0;
            } elseif (empty($isPregnant)) {
                $isPregnantValue = null; // Not applicable
            } else {
                $errors[] = "Row $rowNumber: Pregnancy status must be 'Yes', 'No', or empty";
                continue;
            }
            
            // Strict screening date validation
            $screeningDateTime = null;
            if (!empty($screeningDate)) {
                // Must match exact format YYYY-MM-DD HH:MM:SS
                if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $screeningDate)) {
                    $errors[] = "Row $rowNumber: Screening date must be in exact format YYYY-MM-DD HH:MM:SS (e.g., 2024-01-15 10:30:00)";
                    continue;
                }
                
                $screeningDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $screeningDate);
                if (!$screeningDateTime || $screeningDateTime->format('Y-m-d H:i:s') !== $screeningDate) {
                    $errors[] = "Row $rowNumber: Invalid screening date. Use valid YYYY-MM-DD HH:MM:SS format";
                    continue;
                }
            } else {
                $screeningDateTime = new DateTime();
            }
            
            // Note: BMI calculation removed as column may not exist in actual table
            
            // Check if user already exists (by email or screening_id) using DatabaseHelper
            $existingUser = null;
            $checkResult = $db->select('community_users', 'id', "email = ? OR screening_id = ?", [$email, $screeningId]);
            if ($checkResult['success'] && !empty($checkResult['data'])) {
                $existingUser = $checkResult['data'][0];
            }
            
            // Calculate age
            $age = $today->diff($birthDate)->y;
            
            // Calculate BMI
            $bmi = null;
            if ($height > 0) {
                $bmi = round($weight / pow($height / 100, 2), 1);
            }
            
            // Prepare data for insert/update
            $userData = [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'municipality' => $municipality,
                'barangay' => $barangay,
                'sex' => $sex,
                'birthday' => $birthday,
                'age' => $age,
                'is_pregnant' => $isPregnantValue,
                'weight_kg' => $weight,
                'height_cm' => $height,
                'muac_cm' => $muac,
                'bmi' => $bmi,
                'screening_id' => $screeningId,
                'screening_date' => $screeningDateTime->format('Y-m-d H:i:s')
            ];
            
            if ($existingUser) {
                // Update existing user using DatabaseHelper
                $updateResult = $db->update('community_users', $userData, "email = ? OR screening_id = ?", [$email, $screeningId]);
                
                if (!$updateResult['success']) {
                    $errors[] = "Row $rowNumber: Failed to update existing user: " . ($updateResult['message'] ?? 'Unknown error');
                    continue;
                }
            } else {
                // Insert new user using DatabaseHelper
                $insertResult = $db->insert('community_users', $userData);
                
                if (!$insertResult['success']) {
                    $errors[] = "Row $rowNumber: Failed to insert new user: " . ($insertResult['message'] ?? 'Unknown error');
                    continue;
                }
            }
            
            $importedCount++;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'CSV imported successfully',
            'imported_count' => $importedCount,
            'errors' => $errors
        ]);
        
    } catch (Exception $e) {
        error_log("CSV import error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Import failed: ' . $e->getMessage(),
            'errors' => $errors
        ]);
    }
}
?>
