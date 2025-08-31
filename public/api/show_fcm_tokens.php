<?php
// Simple script to show FCM tokens
header('Content-Type: application/json');

// Database connection
$host = 'localhost';
$dbname = 'nutrisaur_db';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get FCM tokens
    $stmt = $conn->prepare("SELECT id, fcm_token, user_email, user_barangay, is_active, created_at FROM fcm_tokens WHERE is_active = TRUE ORDER BY created_at DESC");
    $stmt->execute();
    $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mask the tokens for security (show first 20 chars)
    foreach ($tokens as &$token) {
        $token['fcm_token_preview'] = substr($token['fcm_token'], 0, 20) . '...';
        unset($token['fcm_token']); // Remove full token for security
    }
    
    echo json_encode([
        'success' => true,
        'total_tokens' => count($tokens),
        'tokens' => $tokens
    ], JSON_PRETTY_PRINT);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
