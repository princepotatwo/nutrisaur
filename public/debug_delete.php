<?php
header('Content-Type: text/plain');
echo "=== DELETION DEBUG ===\n";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "GET params: " . json_encode($_GET) . "\n";
echo "POST params: " . json_encode($_POST) . "\n";

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $programId = $_GET['delete'];
    echo "\nAttempting to delete program ID: $programId\n";
    
    try {
        require_once __DIR__ . '/api/DatabaseAPI.php';
        $db = DatabaseAPI::getInstance();
        echo "Database connection successful\n";
        
        $result = $db->universalDelete('programs', 'program_id = ?', [$programId]);
        echo "Delete result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
        
        if ($result['success']) {
            echo "✅ SUCCESS: Event deleted\n";
        } else {
            echo "❌ FAILED: " . $result['message'] . "\n";
        }
    } catch (Exception $e) {
        echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    }
} else {
    echo "\nNo valid delete parameter provided\n";
    echo "Usage: debug_delete.php?delete=ID\n";
}

echo "\n=== END DEBUG ===\n";
?>
