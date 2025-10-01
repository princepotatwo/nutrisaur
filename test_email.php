<?php
// Test script to debug email sending issue
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing email sending functionality...\n\n";

// Test the sendVerificationEmail function
function sendVerificationEmail($email, $username, $verificationCode) {
    echo "Attempting to send email to: $email\n";
    echo "Username: $username\n";
    echo "Verification Code: $verificationCode\n\n";
    
    // Use Resend API for reliable email delivery
    $apiKey = 're_P6tUyJB2_FjTagamRhwJrJ29q22mmyU4V';
    $apiUrl = 'https://api.resend.com/emails';
    
    $emailData = [
        'from' => 'NUTRISAUR <onboarding@resend.dev>',
        'to' => [$email],
        'subject' => 'NUTRISAUR - Email Verification Test',
        'html' => "
        <html>
        <head>
            <title>Email Verification Test</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2A3326; color: #A1B454; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                .verification-code { 
                    background: #2A3326; 
                    color: #A1B454; 
                    font-size: 32px; 
                    font-weight: bold; 
                    text-align: center; 
                    padding: 20px; 
                    border-radius: 8px; 
                    margin: 20px 0;
                    letter-spacing: 4px;
                }
                .footer { text-align: center; margin-top: 30px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Welcome to NUTRISAUR!</h1>
                </div>
                <div class='content'>
                    <p>Hello " . htmlspecialchars($username) . ",</p>
                    <p>Thank you for registering with NUTRISAUR. To complete your registration, please use the verification code below:</p>
                    <div class='verification-code'>" . $verificationCode . "</div>
                    <p><strong>This code will expire in 5 minutes.</strong></p>
                    <p>If you did not create an account with NUTRISAUR, please ignore this email.</p>
                    <div class='footer'>
                        <p>Best regards,<br>NUTRISAUR Team</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        "
    ];
    
    echo "Sending request to Resend API...\n";
    echo "API URL: $apiUrl\n";
    echo "API Key: " . substr($apiKey, 0, 10) . "...\n\n";
    
    // Send email via Resend API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlInfo = curl_getinfo($ch);
    curl_close($ch);
    
    echo "cURL Info:\n";
    print_r($curlInfo);
    echo "\nHTTP Code: $httpCode\n";
    echo "cURL Error: " . ($curlError ? $curlError : 'None') . "\n";
    echo "Response: $response\n\n";
    
    if ($curlError) {
        echo "❌ cURL Error: " . $curlError . "\n";
        return false;
    }
    
    if ($httpCode === 200) {
        $responseData = json_decode($response, true);
        if (isset($responseData['id'])) {
            echo "✅ Email sent successfully via Resend API. Email ID: " . $responseData['id'] . "\n";
            return true;
        } else {
            echo "❌ Invalid response format. Response: $response\n";
            return false;
        }
    } else {
        echo "❌ HTTP Error $httpCode. Response: $response\n";
        return false;
    }
}

// Test with a sample email
$testEmail = "test@example.com"; // Replace with your actual email for testing
$testUsername = "TestUser";
$testCode = "1234";

echo "=== EMAIL SENDING TEST ===\n";
$result = sendVerificationEmail($testEmail, $testUsername, $testCode);

if ($result) {
    echo "\n✅ Email test PASSED\n";
} else {
    echo "\n❌ Email test FAILED\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
