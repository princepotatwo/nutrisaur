<?php
/**
 * Debug API - Simple test to see what's happening
 */

// Start the session
session_start();

// Set JSON header
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $debug = [];
    
    // Test 1: Basic PHP
    $debug[] = "PHP is working";
    
    // Test 2: Session check
    $debug[] = "Session user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET');
    
    // Test 3: Config file
    if (file_exists(__DIR__ . "/../../config.php")) {
        $debug[] = "Config file exists";
        require_once __DIR__ . "/../../config.php";
        $debug[] = "Config file included";
    } else {
        $debug[] = "Config file NOT found";
    }
    
    // Test 4: Database connection
    if (function_exists('getDatabaseConnection')) {
        $debug[] = "getDatabaseConnection function exists";
        try {
            $pdo = getDatabaseConnection();
            if ($pdo) {
                $debug[] = "PDO connection successful";
                $pdo->query("SELECT 1");
                $debug[] = "PDO query test successful";
            } else {
                $debug[] = "PDO connection returned null";
            }
        } catch (Exception $e) {
            $debug[] = "PDO connection error: " . $e->getMessage();
        }
    } else {
        $debug[] = "getDatabaseConnection function NOT found";
    }
    
    // Test 5: DatabaseAPI
    if (file_exists(__DIR__ . '/DatabaseAPI.php')) {
        $debug[] = "DatabaseAPI file exists";
        try {
            require_once __DIR__ . '/DatabaseAPI.php';
            $debug[] = "DatabaseAPI included";
            
            $db = DatabaseAPI::getInstance();
            $debug[] = "DatabaseAPI instance created";
            
            if ($db->isAvailable()) {
                $debug[] = "Database is available";
            } else {
                $debug[] = "Database is NOT available";
            }
        } catch (Exception $e) {
            $debug[] = "DatabaseAPI error: " . $e->getMessage();
        } catch (Error $e) {
            $debug[] = "DatabaseAPI fatal error: " . $e->getMessage();
        }
    } else {
        $debug[] = "DatabaseAPI file NOT found";
    }
    
    echo json_encode([
        'success' => true,
        'debug' => $debug,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Exception: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Fatal Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
