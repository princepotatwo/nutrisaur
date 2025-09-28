<?php
// Test server deployment - check if the correct file is being used
header('Content-Type: application/json');

// Check if DatabaseAPI.php exists in the correct location
$databasePath = '../../DatabaseAPI.php';
$databaseExists = file_exists($databasePath);

// Check if the file is readable
$databaseReadable = $databaseExists ? is_readable($databasePath) : false;

// Get current working directory
$currentDir = getcwd();

// Get the file path of this script
$scriptPath = __FILE__;

// Get the directory of this script
$scriptDir = dirname(__FILE__);

// Try to include the DatabaseAPI.php file
$includeSuccess = false;
$includeError = '';

try {
    if ($databaseExists) {
        require_once $databasePath;
        $includeSuccess = true;
    } else {
        $includeError = "DatabaseAPI.php not found at: " . $databasePath;
    }
} catch (Exception $e) {
    $includeError = "Error including DatabaseAPI.php: " . $e->getMessage();
}

echo json_encode([
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'database_path' => $databasePath,
    'database_exists' => $databaseExists,
    'database_readable' => $databaseReadable,
    'include_success' => $includeSuccess,
    'include_error' => $includeError,
    'current_directory' => $currentDir,
    'script_path' => $scriptPath,
    'script_directory' => $scriptDir,
    'file_list' => [
        'api_directory' => scandir($scriptDir),
        'parent_directory' => scandir(dirname($scriptDir)),
        'root_directory' => scandir(dirname(dirname($scriptDir)))
    ]
]);
?>
