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
        
        error_log("Sending email via Node.js - Email: $email, Username: $username, Code: $verificationCode");
        
        // Call Node.js email service
        $nodeScript = __DIR__ . "/../../email-service-simple.js";
        
        if (!file_exists($nodeScript)) {
            error_log("Node.js script not found: $nodeScript");
            echo json_encode(['success' => false, 'message' => 'Email service not available']);
            exit;
        }
        
        // Create a temporary script to send the email
        $tempScript = "const { sendVerificationEmail } = require('$nodeScript');\n";
        $tempScript .= "sendVerificationEmail('$email', '$username', '$verificationCode')\n";
        $tempScript .= "  .then(result => {\n";
        $tempScript .= "    console.log(JSON.stringify({success: result}));\n";
        $tempScript .= "    process.exit(result ? 0 : 1);\n";
        $tempScript .= "  })\n";
        $tempScript .= "  .catch(error => {\n";
        $tempScript .= "    console.error(JSON.stringify({error: error.message}));\n";
        $tempScript .= "    process.exit(1);\n";
        $tempScript .= "  });\n";
        
        $tempFile = tempnam(sys_get_temp_dir(), 'email_');
        file_put_contents($tempFile, $tempScript);
        
        // Execute Node.js script
        $command = "node $tempFile 2>&1";
        $output = shell_exec($command);
        
        // Clean up temp file
        unlink($tempFile);
        
        error_log("Node.js output: $output");
        
        // Parse the output
        $lines = explode("\n", trim($output));
        $lastLine = end($lines);
        
        try {
            $result = json_decode($lastLine, true);
            if ($result && isset($result['success'])) {
                if ($result['success']) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Email sent successfully via Node.js',
                        'method' => 'nodejs'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to send email via Node.js',
                        'error' => $result['error'] ?? 'Unknown error'
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid response from Node.js service',
                    'output' => $output
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to parse Node.js response',
                'output' => $output,
                'error' => $e->getMessage()
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    }
    
} catch (Exception $e) {
    error_log("Node.js Email Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Server error occurred. Please try again later.',
        'debug' => $e->getMessage()
    ]);
}
?>
