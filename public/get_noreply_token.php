<?php
// get_noreply_token.php - Get refresh token for noreply.nutrisaur@gmail.com
$clientId = '43537903747-ppt6bbcnfa60p0hchanl32equ9c3b0ao.apps.googleusercontent.com';
$clientSecret = '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';
$redirectUri = 'https://nutrisaur-production.up.railway.app/get_noreply_token.php';

// Step 1: Get authorization code
if (!isset($_GET['code'])) {
    $authUrl = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
        'client_id' => $clientId,
        'redirect_uri' => $redirectUri,
        'scope' => 'https://www.googleapis.com/auth/gmail.send',
        'response_type' => 'code',
        'access_type' => 'offline',
        'prompt' => 'consent'
    ]);
    
    echo "<h2>Gmail API Authorization for noreply.nutrisaur@gmail.com</h2>";
    echo "<p><strong>IMPORTANT:</strong> Make sure you're logged into noreply.nutrisaur@gmail.com</p>";
    echo "<p>Click the link below to authorize:</p>";
    echo "<a href='$authUrl' style='background: #4285f4; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Authorize noreply.nutrisaur@gmail.com</a>";
    exit;
}

// Step 2: Exchange code for tokens
$code = $_GET['code'];
$tokenUrl = 'https://oauth2.googleapis.com/token';
$data = [
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'code' => $code,
    'grant_type' => 'authorization_code',
    'redirect_uri' => $redirectUri
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $tokenUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$tokens = json_decode($response, true);

if (isset($tokens['refresh_token'])) {
    echo "<h2>✅ Success!</h2>";
    echo "<p><strong>Refresh Token for noreply.nutrisaur@gmail.com:</strong></p>";
    echo "<textarea style='width: 100%; height: 100px; font-family: monospace;'>" . $tokens['refresh_token'] . "</textarea>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>Copy the refresh token above</li>";
    echo "<li>Replace '1//04your_refresh_token_here' in home.php with this token</li>";
    echo "<li>Delete this file (get_noreply_token.php) for security</li>";
    echo "</ol>";
} else {
    echo "<h2>❌ Error</h2>";
    echo "<p>Failed to get refresh token. Response:</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}
?>
