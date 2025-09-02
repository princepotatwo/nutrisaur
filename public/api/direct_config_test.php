<?php
header('Content-Type: application/json');

echo json_encode([
    'step' => '1',
    'message' => 'Testing config.php functions directly'
]);

try {
    require_once __DIR__ . "/../config.php";
    
    echo json_encode([
        'step' => '2',
        'message' => 'Config.php included successfully'
    ]);
    
    $pdo = getDatabaseConnection();
    $mysqli = getMysqliConnection();
    
    echo json_encode([
        'step' => '3',
        'message' => 'Database connections tested',
        'pdo' => $pdo ? 'success' : 'failed',
        'mysqli' => $mysqli ? 'success' : 'failed'
    ]);
    
    if ($pdo) {
        $test = $pdo->query("SELECT 1");
        echo json_encode([
            'step' => '4',
            'message' => 'PDO query test',
            'result' => $test ? 'success' : 'failed'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
