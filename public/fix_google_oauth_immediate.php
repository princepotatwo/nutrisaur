<?php
// Immediate fix for Google OAuth API path issue
header('Content-Type: application/json');

$apiFile = 'api/google-oauth-community.php';
$apiPath = __DIR__ . '/' . $apiFile;

if (file_exists($apiPath)) {
    // Read the current file
    $content = file_get_contents($apiPath);
    
    // Check if it still has the old path
    if (strpos($content, "require_once '../DatabaseAPI.php';") !== false) {
        // Replace with the correct path
        $newContent = str_replace(
            "require_once '../DatabaseAPI.php';",
            "require_once '../../DatabaseAPI.php';",
            $content
        );
        
        // Write the corrected content back
        $result = file_put_contents($apiPath, $newContent);
        
        if ($result !== false) {
            echo json_encode([
                'success' => true,
                'message' => 'Google OAuth API file updated successfully',
                'file' => $apiFile,
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
            'success' => true,
            'message' => 'Google OAuth API file already has correct path',
            'file' => $apiFile,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Google OAuth API file not found',
        'file' => $apiFile,
        'directory' => __DIR__
    ]);
}
?>
