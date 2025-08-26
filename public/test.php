<?php
// Simple test file for Railway deployment
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nutrisaur - Railway Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .info { color: #17a2b8; }
        .warning { color: #ffc107; }
        .error { color: #dc3545; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; border-left: 4px solid; }
        .status-success { background: #d4edda; border-left-color: #28a745; }
        .status-info { background: #d1ecf1; border-left-color: #17a2b8; }
        .status-warning { background: #fff3cd; border-left-color: #ffc107; }
        .status-error { background: #f8d7da; border-left-color: #dc3545; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Nutrisaur Railway Deployment Test</h1>
        
        <div class="status status-success">
            <h2>✅ PHP Environment Check</h2>
            <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
            <p><strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
            <p><strong>Railway Port:</strong> <?php echo $_ENV['PORT'] ?? 'Not set'; ?></p>
        </div>

        <div class="status status-info">
            <h2>🔧 Extensions Check</h2>
            <?php
            $required_extensions = ['mysqli', 'pdo', 'json', 'curl'];
            foreach ($required_extensions as $ext) {
                if (extension_loaded($ext)) {
                    echo "<p class='success'>✅ $ext: Loaded</p>";
                } else {
                    echo "<p class='error'>❌ $ext: Not loaded</p>";
                }
            }
            ?>
        </div>

        <div class="status status-info">
            <h2>📁 File System Check</h2>
            <?php
            $files_to_check = [
                'settings_verified_mho.php' => 'Main MHO Settings',
                'health.php' => 'Health Check Endpoint',
                'index.php' => 'Main Entry Point'
            ];
            
            foreach ($files_to_check as $file => $description) {
                if (file_exists($file)) {
                    echo "<p class='success'>✅ $description ($file): Found</p>";
                } else {
                    echo "<p class='error'>❌ $description ($file): Missing</p>";
                }
            }
            ?>
        </div>

        <div class="status status-success">
            <h2>🎯 Next Steps</h2>
            <p>If all checks above show ✅, your Railway deployment is working correctly!</p>
            <p><a href="/health">🔍 Check Health Endpoint</a></p>
            <p><a href="/">🏠 Go to Main App</a></p>
        </div>

        <div class="status status-warning">
            <h2>⚠️ Troubleshooting</h2>
            <p>If you see any ❌ errors:</p>
            <ul>
                <li>Check Railway build logs</li>
                <li>Verify nixpacks.toml configuration</li>
                <li>Ensure all required files are in the repository</li>
            </ul>
        </div>
    </div>
</body>
</html>
