<?php
// Debug registration endpoint
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Headers are now set by the router
session_start();

try {
    error_log("=== REGISTRATION DEBUG START ===");
    
    // Test 1: Basic PHP execution
    error_log("Test 1: Basic PHP execution - PASSED");
    
    // Test 2: Check if config.php exists and can be included
    try {
        require_once __DIR__ . "/../config.php";
        error_log("Test 2: config.php included successfully");
    } catch (Exception $e) {
        error_log("Test 2 FAILED: config.php error - " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'config.php failed', 'details' => $e->getMessage()]);
        exit;
    }
    
    // Test 3: Check database connection
    try {
        $pdo = getDatabaseConnection();
        if ($pdo) {
            error_log("Test 3: Database connection successful");
        } else {
            error_log("Test 3 FAILED: Database connection returned null");
            echo json_encode(['success' => false, 'error' => 'Database connection failed']);
            exit;
        }
    } catch (Exception $e) {
        error_log("Test 3 FAILED: Database connection error - " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database connection error', 'details' => $e->getMessage()]);
        exit;
    }
    
    // Test 4: Check if users table exists
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() > 0) {
            error_log("Test 4: Users table exists");
        } else {
            error_log("Test 4 FAILED: Users table does not exist");
            echo json_encode(['success' => false, 'error' => 'Users table not found']);
            exit;
        }
    } catch (Exception $e) {
        error_log("Test 4 FAILED: Table check error - " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Table check failed', 'details' => $e->getMessage()]);
        exit;
    }
    
    // Test 5: Check users table structure
    try {
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'Field');
        error_log("Test 5: Users table columns: " . implode(', ', $columnNames));
        
        // Check for required columns
        $requiredColumns = ['user_id', 'username', 'email', 'password', 'verification_code', 'verification_code_expires'];
        $missingColumns = array_diff($requiredColumns, $columnNames);
        
        if (!empty($missingColumns)) {
            error_log("Test 5 FAILED: Missing columns: " . implode(', ', $missingColumns));
            echo json_encode(['success' => false, 'error' => 'Missing required columns', 'missing' => $missingColumns]);
            exit;
        }
    } catch (Exception $e) {
        error_log("Test 5 FAILED: Column check error - " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Column check failed', 'details' => $e->getMessage()]);
        exit;
    }
    
    // Test 6: Test email configuration
    try {
        require_once __DIR__ . "/../../../email_config.php";
        error_log("Test 6: Email config included successfully");
        error_log("Email config - SMTP_USERNAME: " . SMTP_USERNAME);
        error_log("Email config - SMTP_HOST: " . SMTP_HOST);
        error_log("Email config - SMTP_PORT: " . SMTP_PORT);
    } catch (Exception $e) {
        error_log("Test 6 FAILED: Email config error - " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Email config failed', 'details' => $e->getMessage()]);
        exit;
    }
    
    // Test 7: Test PHPMailer inclusion
    try {
        require_once __DIR__ . "/../../vendor/phpmailer/phpmailer/src/Exception.php";
        require_once __DIR__ . "/../../vendor/phpmailer/phpmailer/src/PHPMailer.php";
        require_once __DIR__ . "/../../vendor/phpmailer/phpmailer/src/SMTP.php";
        error_log("Test 7: PHPMailer files included successfully");
    } catch (Exception $e) {
        error_log("Test 7 FAILED: PHPMailer inclusion error - " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'PHPMailer inclusion failed', 'details' => $e->getMessage()]);
        exit;
    }
    
    // Test 8: Test EmailService inclusion
    try {
        require_once __DIR__ . "/EmailService.php";
        error_log("Test 8: EmailService included successfully");
    } catch (Exception $e) {
        error_log("Test 8 FAILED: EmailService inclusion error - " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'EmailService inclusion failed', 'details' => $e->getMessage()]);
        exit;
    }
    
    // Test 9: Test Node.js email service
    try {
        $nodeScript = __DIR__ . "/../../email-service.js";
        if (file_exists($nodeScript)) {
            error_log("Test 9: Node.js email service file exists");
        } else {
            error_log("Test 9 FAILED: Node.js email service file not found");
            echo json_encode(['success' => false, 'error' => 'Node.js email service file not found']);
            exit;
        }
    } catch (Exception $e) {
        error_log("Test 9 FAILED: Node.js check error - " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Node.js check failed', 'details' => $e->getMessage()]);
        exit;
    }
    
    error_log("=== ALL TESTS PASSED ===");
    
    // If we get here, all tests passed
    echo json_encode([
        'success' => true,
        'message' => 'All registration components are working correctly',
        'tests_passed' => 9,
        'database_connected' => true,
        'users_table_exists' => true,
        'email_config_loaded' => true,
        'phpmailer_available' => true,
        'nodejs_service_available' => true
    ]);
    
} catch (Exception $e) {
    error_log("=== REGISTRATION DEBUG ERROR ===");
    error_log("Unexpected error: " . $e->getMessage());
    error_log("File: " . $e->getFile());
    error_log("Line: " . $e->getLine());
    
    echo json_encode([
        'success' => false,
        'error' => 'Unexpected error during debug',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
