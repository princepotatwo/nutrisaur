<?php
// Debug server file structure
header('Content-Type: application/json');

$currentDir = getcwd();
$scriptDir = dirname(__FILE__);
$parentDir = dirname($scriptDir);
$rootDir = dirname($parentDir);

// Check different possible locations for DatabaseAPI.php
$possiblePaths = [
    '../../DatabaseAPI.php',
    '../DatabaseAPI.php',
    './DatabaseAPI.php',
    $scriptDir . '/../../DatabaseAPI.php',
    $parentDir . '/DatabaseAPI.php',
    $rootDir . '/DatabaseAPI.php'
];

$results = [];

foreach ($possiblePaths as $path) {
    $fullPath = $path;
    if (!str_starts_with($path, '/')) {
        $fullPath = $scriptDir . '/' . $path;
    }
    
    $results[] = [
        'path' => $path,
        'full_path' => $fullPath,
        'exists' => file_exists($fullPath),
        'readable' => file_exists($fullPath) ? is_readable($fullPath) : false,
        'is_file' => file_exists($fullPath) ? is_file($fullPath) : false
    ];
}

// List directories to understand structure
$directories = [
    'current' => $currentDir,
    'script' => $scriptDir,
    'parent' => $parentDir,
    'root' => $rootDir
];

$dirContents = [];
foreach ($directories as $name => $dir) {
    if (is_dir($dir)) {
        $dirContents[$name] = [
            'path' => $dir,
            'contents' => scandir($dir)
        ];
    }
}

echo json_encode([
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'directories' => $directories,
    'directory_contents' => $dirContents,
    'possible_paths' => $results,
    'current_working_directory' => getcwd(),
    'script_location' => __FILE__
], JSON_PRETTY_PRINT);
?>
