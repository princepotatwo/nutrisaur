<?php
// Test Node.js availability in Railway
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

echo json_encode([
    'test' => 'Node.js availability in Railway',
    'timestamp' => date('Y-m-d H:i:s'),
    'tests' => []
]);

// Test 1: Check if node command exists
$nodeVersion = shell_exec('node --version 2>&1');
$nodeAvailable = !empty($nodeVersion) && strpos($nodeVersion, 'v') === 0;

echo json_encode([
    'test' => 'Node.js availability in Railway',
    'timestamp' => date('Y-m-d H:i:s'),
    'tests' => [
        'node_version' => [
            'available' => $nodeAvailable,
            'version' => trim($nodeVersion),
            'command' => 'node --version'
        ]
    ]
]);

// Test 2: Check if npm exists
$npmVersion = shell_exec('npm --version 2>&1');
$npmAvailable = !empty($npmVersion) && is_numeric(trim($npmVersion));

echo json_encode([
    'test' => 'Node.js availability in Railway',
    'timestamp' => date('Y-m-d H:i:s'),
    'tests' => [
        'node_version' => [
            'available' => $nodeAvailable,
            'version' => trim($nodeVersion),
            'command' => 'node --version'
        ],
        'npm_version' => [
            'available' => $npmAvailable,
            'version' => trim($npmVersion),
            'command' => 'npm --version'
        ]
    ]
]);

// Test 3: Check if email service file exists
$emailServicePath = __DIR__ . '/../../email-service-simple.js';
$emailServiceExists = file_exists($emailServicePath);

echo json_encode([
    'test' => 'Node.js availability in Railway',
    'timestamp' => date('Y-m-d H:i:s'),
    'tests' => [
        'node_version' => [
            'available' => $nodeAvailable,
            'version' => trim($nodeVersion),
            'command' => 'node --version'
        ],
        'npm_version' => [
            'available' => $npmAvailable,
            'version' => trim($npmVersion),
            'command' => 'npm --version'
        ],
        'email_service_file' => [
            'exists' => $emailServiceExists,
            'path' => $emailServicePath,
            'real_path' => realpath($emailServicePath)
        ]
    ]
]);

// Test 4: Try to run a simple Node.js command
$simpleNodeTest = shell_exec('node -e "console.log(\'Node.js is working\')" 2>&1');
$simpleNodeWorking = strpos($simpleNodeTest, 'Node.js is working') !== false;

echo json_encode([
    'test' => 'Node.js availability in Railway',
    'timestamp' => date('Y-m-d H:i:s'),
    'tests' => [
        'node_version' => [
            'available' => $nodeAvailable,
            'version' => trim($nodeVersion),
            'command' => 'node --version'
        ],
        'npm_version' => [
            'available' => $npmAvailable,
            'version' => trim($npmVersion),
            'command' => 'npm --version'
        ],
        'email_service_file' => [
            'exists' => $emailServiceExists,
            'path' => $emailServicePath,
            'real_path' => realpath($emailServicePath)
        ],
        'simple_node_test' => [
            'working' => $simpleNodeWorking,
            'output' => trim($simpleNodeTest),
            'command' => 'node -e "console.log(\'Node.js is working\')"'
        ]
    ]
]);

// Test 5: Try to require the email service
if ($emailServiceExists && $nodeAvailable) {
    $emailServiceTest = shell_exec('node -e "try { require(\'' . $emailServicePath . '\'); console.log(\'Email service loaded successfully\'); } catch(e) { console.error(\'Email service error:\', e.message); }" 2>&1');
    $emailServiceWorking = strpos($emailServiceTest, 'Email service loaded successfully') !== false;
    
    echo json_encode([
        'test' => 'Node.js availability in Railway',
        'timestamp' => date('Y-m-d H:i:s'),
        'tests' => [
            'node_version' => [
                'available' => $nodeAvailable,
                'version' => trim($nodeVersion),
                'command' => 'node --version'
            ],
            'npm_version' => [
                'available' => $npmAvailable,
                'version' => trim($npmVersion),
                'command' => 'npm --version'
            ],
            'email_service_file' => [
                'exists' => $emailServiceExists,
                'path' => $emailServicePath,
                'real_path' => realpath($emailServicePath)
            ],
            'simple_node_test' => [
                'working' => $simpleNodeWorking,
                'output' => trim($simpleNodeTest),
                'command' => 'node -e "console.log(\'Node.js is working\')"'
            ],
            'email_service_test' => [
                'working' => $emailServiceWorking,
                'output' => trim($emailServiceTest),
                'command' => 'node -e "require(\'' . $emailServicePath . '\')"'
            ]
        ]
    ]);
}
?>
