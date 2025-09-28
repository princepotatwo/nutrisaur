<?php
// Fix DatabaseAPI.php path issue
header('Content-Type: application/json');

$apiFile = 'api/google-oauth-community.php';
$apiPath = __DIR__ . '/' . $apiFile;

if (file_exists($apiPath)) {
    // Try different possible paths for DatabaseAPI.php
    $possiblePaths = [
        '../../DatabaseAPI.php',
        '../DatabaseAPI.php',
        './DatabaseAPI.php',
        __DIR__ . '/../../DatabaseAPI.php',
        __DIR__ . '/../DatabaseAPI.php',
        __DIR__ . '/DatabaseAPI.php'
    ];
    
    $correctPath = null;
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $correctPath = $path;
            break;
        }
    }
    
    if ($correctPath) {
        // Read the current file
        $content = file_get_contents($apiPath);
        
        // Replace the require_once line with the correct path
        $newContent = preg_replace(
            '/require_once\s+[\'"][^\'"]+[\'"];/',
            "require_once '$correctPath';",
            $content
        );
        
        // Write the corrected content back
        $result = file_put_contents($apiPath, $newContent);
        
        if ($result !== false) {
            echo json_encode([
                'success' => true,
                'message' => 'DatabaseAPI.php path fixed successfully',
                'file' => $apiFile,
                'correct_path' => $correctPath,
                'bytes_written' => $result,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to write to file',
                'file' => $apiFile
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'DatabaseAPI.php not found in any expected location',
            'searched_paths' => $possiblePaths,
            'current_directory' => __DIR__
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Google OAuth API file not found',
        'file' => $apiFile
    ]);
}
?>
