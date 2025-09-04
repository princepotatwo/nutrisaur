<?php
// PHP endpoint to call Node.js email service
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Headers are now set by the router
session_start();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get data from request
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            $data = $_POST;
        }
        
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'No data received']);
            exit;
        }
        
        $email = $data['email'] ?? '';
        $username = $data['username'] ?? '';
        $verificationCode = $data['verification_code'] ?? '';
        
        if (empty($email) || empty($username) || empty($verificationCode)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }
        
        // Call Node.js email service
        $nodeScript = __DIR__ . "/../../email-service.js";
        $nodeData = json_encode([
            'email' => $email,
            'username' => $username,
            'verification_code' => $verificationCode
        ]);
        
        // Execute Node.js script
        $command = "node $nodeScript " . escapeshellarg($nodeData);
        $output = shell_exec($command . " 2>&1");
        
        if ($output !== null) {
            echo json_encode([
                'success' => true,
                'message' => 'Email sent successfully',
                'output' => $output
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to send email',
                'error' => 'Node.js execution failed'
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    }
    
} catch (Exception $e) {
    error_log("Send Email Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Server error occurred. Please try again later.',
        'debug' => $e->getMessage()
    ]);
}
?>
