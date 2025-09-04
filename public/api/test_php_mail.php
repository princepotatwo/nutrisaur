<?php
// Test PHP mail() function
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'No data received']);
        exit;
    }
    
    $email = $data['email'] ?? '';
    $subject = $data['subject'] ?? 'Test Email';
    $message = $data['message'] ?? 'This is a test email';
    
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        exit;
    }
    
    // Set headers for HTML email
    $headers = "From: kevinpingol123@gmail.com\r\n";
    $headers .= "Reply-To: kevinpingol123@gmail.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    // Send email
    $result = mail($email, $subject, $message, $headers);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Email sent successfully using PHP mail()',
            'data' => [
                'to' => $email,
                'subject' => $subject,
                'method' => 'PHP mail()'
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send email using PHP mail()',
            'data' => [
                'to' => $email,
                'subject' => $subject,
                'method' => 'PHP mail()'
            ]
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
