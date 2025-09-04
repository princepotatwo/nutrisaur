<?php
// Simple test for verification system
error_log("=== TEST VERIFICATION SYSTEM ===");

try {
    // Test 1: Basic file includes
    error_log("Testing file includes...");
    
    $configPath = __DIR__ . "/../config.php";
    $dbApiPath = __DIR__ . "/DatabaseAPI.php";
    $emailPath = __DIR__ . "/EmailService.php";
    $emailConfigPath = __DIR__ . "/../../email_config.php";
    
    error_log("Config path: $configPath - Exists: " . (file_exists($configPath) ? 'yes' : 'no'));
    error_log("DatabaseAPI path: $dbApiPath - Exists: " . (file_exists($dbApiPath) ? 'yes' : 'no'));
    error_log("EmailService path: $emailPath - Exists: " . (file_exists($emailPath) ? 'yes' : 'no'));
    error_log("Email config path: $emailConfigPath - Exists: " . (file_exists($emailConfigPath) ? 'yes' : 'no'));
    
    // Test 2: Include config
    if (file_exists($configPath)) {
        require_once $configPath;
        error_log("Config included successfully");
    } else {
        throw new Exception("Config file not found");
    }
    
    // Test 3: Include DatabaseAPI
    if (file_exists($dbApiPath)) {
        require_once $dbApiPath;
        error_log("DatabaseAPI included successfully");
    } else {
        throw new Exception("DatabaseAPI file not found");
    }
    
    // Test 4: Initialize DatabaseAPI
    error_log("Initializing DatabaseAPI...");
    $db = DatabaseAPI::getInstance();
    error_log("DatabaseAPI initialized successfully");
    
    // Test 5: Check database connection
    $dbStatus = $db->getDatabaseStatus();
    error_log("Database status: " . json_encode($dbStatus));
    
    if (!$db->isDatabaseAvailable()) {
        throw new Exception("Database not available");
    }
    
    error_log("Database connection successful");
    
    // Test 6: Include EmailService
    if (file_exists($emailPath)) {
        require_once $emailPath;
        error_log("EmailService included successfully");
    } else {
        throw new Exception("EmailService file not found");
    }
    
    // Test 7: Include email config
    if (file_exists($emailConfigPath)) {
        require_once $emailConfigPath;
        error_log("Email config included successfully");
    } else {
        throw new Exception("Email config file not found");
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'All tests passed',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Test failed: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
