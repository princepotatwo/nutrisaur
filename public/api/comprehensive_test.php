<?php
header('Content-Type: application/json');

echo json_encode([
    'test' => 'comprehensive',
    'timestamp' => date('Y-m-d H:i:s')
]);

// Test 1: Direct config.php inclusion
try {
    require_once __DIR__ . "/../config.php";
    $pdo1 = getDatabaseConnection();
    $test1 = $pdo1 ? 'success' : 'failed';
} catch (Exception $e) {
    $test1 = 'error: ' . $e->getMessage();
}

echo json_encode([
    'test1' => 'Direct config.php inclusion',
    'result' => $test1
]);

// Test 2: DatabaseAPI instantiation
try {
    require_once __DIR__ . "/DatabaseAPI.php";
    $db = new DatabaseAPI();
    $pdo2 = $db->getPDO();
    $test2 = $pdo2 ? 'success' : 'failed';
} catch (Exception $e) {
    $test2 = 'error: ' . $e->getMessage();
}

echo json_encode([
    'test2' => 'DatabaseAPI instantiation',
    'result' => $test2
]);

// Test 3: DatabaseAPI testConnection method
try {
    $test3 = $db->testConnection() ? 'success' : 'failed';
} catch (Exception $e) {
    $test3 = 'error: ' . $e->getMessage();
}

echo json_encode([
    'test3' => 'DatabaseAPI testConnection',
    'result' => $test3
]);

// Test 4: Simulate login.php authentication
try {
    $result = $db->authenticateUser('test', 'test');
    $test4 = $result['success'] ? 'success' : 'failed: ' . $result['message'];
} catch (Exception $e) {
    $test4 = 'error: ' . $e->getMessage();
}

echo json_encode([
    'test4' => 'DatabaseAPI authenticateUser',
    'result' => $test4
]);

?>
