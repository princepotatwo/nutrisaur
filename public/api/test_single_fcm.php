<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Test with a specific FCM token
$testToken = 'fDtx-1b_RJi7Z6PtcgqDNk:APA91bFljVu501yPaGO-XhA7pGIVV8ZMK6lwLcYHLhJlb4Twzj-o2ZVhq1UKf2rpuywULVrD7U0tHem0pF9f5SwmLM12xXttWZG35kJo_wwpTs4UAS_6_CA';

echo json_encode([
    'test' => 'Single FCM Test',
    'token' => $testToken,
    'timestamp' => date('Y-m-d H:i:s')
]);

// Firebase Admin SDK configuration
$serviceAccountData = [
    "type" => "service_account",
    "project_id" => "nutrisaur-ebf29",
    "private_key_id" => "152a242b3b3d1fd2a41fc3f22f188a517377b1f6",
    "private_key" => "-----BEGIN PRIVATE KEY-----\nMIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQCYjO00aaKgKo9K\nlCIShWR27+fiQubXdeGpEl9a1VbkB9mA537FXiDwHcTVi5odcVpt1P8neoeTTCAZ\nY9LyzEmPrvN6KhgvscLg7bNftdjWowGy6BCxLDKMxjo9rvz0twZJPeyhfcgIg3a4\nOUBawhI3wJ9AJal2x2dovn7r2dH3rQx17lG4Lp8x/0ccARVDXgKTBBp8Wz6qV6dM\nyINFMRV21za0a9D74v99iMRylTR+BbHmBNSFpBnsEHRncxMrOAmBDUxDKgFP9SQQ\nWIuoLZdUcl4myFutYgxPNOLPrK09SqFGkllXsTOaXpPoMjkUT05q+o6zlFSMfdhf\nr3Pc9v+BAgMBAAECggEABjctvgY00lZRls4Q0lTdikwNlGY57hr3OhKN0I9jNjDM\nr6ge/e8vI+FuoO3KdnslHlcAm2zuY7XFFAvBb1OcBq7v7DEVYbIaONxug86OunYT\nTmUOsw1UNPCptFQyKc1gQ/EyOEU99nzWxDxO7zO7lspyIqS2Mij/QWELnlP2bmMu\nRXb9sEzw2YbddUgpV5G1A0z9ZKKqoR5DgqD9Fyt8Im4Jxm/tqFKjY/mS5vWtQCTG\nnj1AkiLfcHQY6gAs7DiM/WxqKQL/sgI3HE7GcKYiqZ6CUu6S07TOaTknat7gSTqC\nWXVRDOZV6wv0dYZLaGZ3ZGH9arqFpjExy9G4diRnbQKBgQDFvQPpht3zWBQS2BB3\nv5E6tJoC0JKyB6+kuvlTYOj6w834tIOj946kQd51xOURvjMXELkuDsv+v593PFDK\nQzk+uZnBzaYHO22nS3jEMgrcoypKQsRM7NWf6l005f9ZywQZoElgKpw/yy9hj4fA\nHEHyIWbxQ9YepKpN+JNtbsFWRQKBgQDFf3zV46WljQJuLnQK9CHbyQjusrRRsfth\ntUH+6QtE0aZ/yyiFMb59Qake6E5BTS6j1/fmqSJNnlUy8Vzq17MhKVhOL+XXbAtS\noacGDHRVbZqg07QfylatJu1tuYWGP4HkACX6wt4VZkP553c2P3bc11VdqUWFPg2w\nB+hlQqAGDQKBgQCDznOeJXUrMSnoSbfoanx/GkWS3L78Bt2Qu8VYS7/g78YLIyCg\nmnKtkO6dqApdYmAh3tbhGaHnBIpia4Ua3eZ5pjQUmGU0auuz2T394bGV0vlsmMbK\n1A+t0gYhLbKhgw8PmeVvQdf3OhQyPv9pEizvHk7FQcenk3GmGa0EBBDB1QKBgQCq\nLu5KfSzrGDRE74CNQ8u9UWGCFrmtQBonGwLwfq54MdQwgMa8552G38K04Gc2fCS5\niMuUlp/5lHtEN5niv9YauD7guQNseyzSmnuLicXhK144QdUQI9JGyKmFiH0Xrrfl\n2X7gs3Sdqg7fVGZ0d4GcUW29FLTUWSGAU4AdaajtLQKBgCXK629zzUE/7op7Zl9p\nEp/s+xn5lhrpo8ZXho0D/ajfQkjrDbMmpWdUMIwtAK75WMn16TRA/+RIynAV5Z5B\nte0wbxwkXAXtAcp2qH+1fe/9w7hTQLP1808HP/VnG9fLnWECIxRxrsP33am3hUHo\n2q8Bsx0vioPXrYNHzr3K1hRH\n-----END PRIVATE KEY-----\n",
    "client_email" => "firebase-adminsdk-fbsvc@nutrisaur-ebf29.iam.gserviceaccount.com",
    "client_id" => "115564910409083368011",
    "auth_uri" => "https://accounts.google.com/o/oauth2/auth",
    "token_uri" => "https://oauth2.googleapis.com/token",
    "auth_provider_x509_cert_url" => "https://www.googleapis.com/oauth2/v1/certs",
    "client_x509_cert_url" => "https://www.googleapis.com/robot/v1/metadata/x509/firebase-adminsdk-fbsvc%40nutrisaur-ebf29.iam.gserviceaccount.com",
    "universe_domain" => "googleapis.com"
];

// Generate JWT token
function generateFirebaseJWT($projectId, $privateKey, $clientEmail) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'RS256']);
    $payload = json_encode([
        'iss' => $clientEmail,
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => time() + 3600,
        'iat' => time()
    ]);
    
    $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    $signature = '';
    openssl_sign($base64Header . '.' . $base64Payload, $signature, $privateKey, 'SHA256');
    $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64Header . '.' . $base64Payload . '.' . $base64Signature;
}

// Send test notification
try {
    $projectId = $serviceAccountData['project_id'];
    $privateKey = $serviceAccountData['private_key'];
    $clientEmail = $serviceAccountData['client_email'];
    
    $jwtToken = generateFirebaseJWT($projectId, $privateKey, $clientEmail);
    
    $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
    
    $data = [
        'message' => [
            'token' => $testToken,
            'notification' => [
                'title' => '🧪 Test Notification',
                'body' => 'This is a test notification from the server!'
            ],
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'sound' => 'default',
                    'priority' => 'high'
                ]
            ]
        ]
    ];
    
    $headers = [
        'Authorization: Bearer ' . $jwtToken,
        'Content-Type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "\n" . json_encode([
        'http_code' => $httpCode,
        'response' => $response,
        'error' => $error,
        'jwt_token_length' => strlen($jwtToken)
    ]);
    
} catch (Exception $e) {
    echo "\n" . json_encode([
        'error' => $e->getMessage()
    ]);
}
?>
