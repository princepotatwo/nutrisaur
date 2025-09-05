<?php
// Test database data
session_start();

// Use Universal DatabaseAPI
require_once __DIR__ . '/api/DatabaseHelper.php';

// Set JSON header
header('Content-Type: application/json');

// Get database helper instance
$db = DatabaseHelper::getInstance();

if (!$db->isAvailable()) {
    echo json_encode(['success' => false, 'error' => 'Database not available']);
    exit;
}

try {
    // Get table structure
    $structure = $db->describe('user_preferences');
    
    // Get count of records
    $countResult = $db->query("SELECT COUNT(*) as count FROM user_preferences", []);
    
    // Get sample data
    $sampleResult = $db->select('user_preferences', '*', '', [], 'id DESC', '5');
    
    echo json_encode([
        'success' => true,
        'table_structure' => $structure['success'] ? $structure['data'] : 'Error getting structure',
        'record_count' => $countResult['success'] ? $countResult['data'][0]['count'] : 'Error getting count',
        'sample_data' => $sampleResult['success'] ? $sampleResult['data'] : 'Error getting sample data',
        'sample_count' => $sampleResult['success'] ? count($sampleResult['data']) : 0
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
