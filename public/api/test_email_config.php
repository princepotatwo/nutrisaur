<?php
// Test email_config.php path
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Headers are now set by the router
session_start();

try {
    $results = [];
    
    // Test different paths
    $paths = [
        'Path 1 (current)' => __DIR__ . "/../../../email_config.php",
        'Path 2 (alternative)' => __DIR__ . "/../../email_config.php",
        'Path 3 (root)' => dirname(__DIR__, 3) . "/email_config.php",
        'Path 4 (absolute)' => "/var/www/html/email_config.php"
    ];
    
    foreach ($paths as $name => $path) {
        $results[$name] = [
            'path' => $path,
            'exists' => file_exists($path),
            'readable' => is_readable($path)
        ];
        
        if (file_exists($path)) {
            try {
                require_once $path;
                $results[$name]['included'] = true;
                $results[$name]['smtp_username'] = defined('SMTP_USERNAME') ? SMTP_USERNAME : 'NOT_DEFINED';
                $results[$name]['smtp_host'] = defined('SMTP_HOST') ? SMTP_HOST : 'NOT_DEFINED';
            } catch (Exception $e) {
                $results[$name]['included'] = false;
                $results[$name]['error'] = $e->getMessage();
            }
        }
    }
    
    // List all files in root directory
    $rootDir = dirname(__DIR__, 3);
    $rootFiles = [];
    if (is_dir($rootDir)) {
        $files = scandir($rootDir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $rootFiles[] = $file;
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Email config path test',
        'results' => $results,
        'root_directory' => $rootDir,
        'root_files' => $rootFiles,
        'current_directory' => __DIR__,
        'directory_structure' => [
            'current' => __DIR__,
            'parent' => dirname(__DIR__),
            'grandparent' => dirname(__DIR__, 2),
            'root' => dirname(__DIR__, 3)
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
