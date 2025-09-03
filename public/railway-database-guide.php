<?php
/**
 * Railway Database Setup Guide
 * Complete guide for setting up MySQL database in Railway
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Railway Database Setup Guide - Nutrisaur</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1, h2, h3 {
            color: #333;
        }
        .step {
            background: #f8f9fa;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
            border-radius: 5px;
        }
        .warning {
            background: #fff3cd;
            border-left-color: #ffc107;
        }
        .success {
            background: #d4edda;
            border-left-color: #28a745;
        }
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
        }
        code {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .status-box {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            font-weight: bold;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
        }
        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Railway Database Setup Guide</h1>
        <p>This guide will help you set up a MySQL database in Railway for your Nutrisaur application.</p>

        <?php
        // Include the enhanced config
        require_once __DIR__ . "/config.php";
        
        // Check current database status
        $db = new RailwayDatabaseConfig();
        $connectionStatus = $db->getPDOConnection() ? 'success' : 'error';
        $statusMessage = $connectionStatus === 'success' ? 'Database Connected!' : 'Database Not Connected';
        ?>

        <div class="status-box <?php echo $connectionStatus; ?>">
            🔍 Current Status: <?php echo $statusMessage; ?>
        </div>

        <h2>📋 Step-by-Step Setup Instructions</h2>

        <div class="step">
            <h3>Step 1: Add MySQL Database to Railway</h3>
            <ol>
                <li>Go to your <a href="https://railway.app/dashboard" target="_blank">Railway Dashboard</a></li>
                <li>Select your Nutrisaur project</li>
                <li>Click <strong>"New Service"</strong></li>
                <li>Choose <strong>"Database"</strong> → <strong>"MySQL"</strong></li>
                <li>Wait for the database to be provisioned (usually 1-2 minutes)</li>
            </ol>
        </div>

        <div class="step">
            <h3>Step 2: Connect Database to Your App</h3>
            <ol>
                <li>In your Railway project, go to the <strong>"Variables"</strong> tab</li>
                <li>Railway automatically sets these variables when you add a database:
                    <ul>
                        <li><code>MYSQL_PUBLIC_URL</code> - Main connection string</li>
                        <li><code>MYSQLHOST</code> - Database host</li>
                        <li><code>MYSQLPORT</code> - Database port</li>
                        <li><code>MYSQLUSER</code> - Database username</li>
                        <li><code>MYSQLPASSWORD</code> - Database password</li>
                        <li><code>MYSQLDATABASE</code> - Database name</li>
                    </ul>
                </li>
                <li>Your app will automatically detect and use these variables</li>
            </ol>
        </div>

        <div class="step">
            <h3>Step 3: Deploy and Test</h3>
            <ol>
                <li>Railway automatically redeploys your app when you add a database</li>
                <li>Wait for the deployment to complete</li>
                <li>Test the connection using the links below</li>
            </ol>
        </div>

        <h2>🔧 Testing Tools</h2>
        
        <div class="step">
            <a href="setup-database.php" class="btn">🔍 Check Database Status</a>
            <a href="database-status.php" class="btn">📊 Database Diagnostics</a>
            <a href="health" class="btn">❤️ Health Check</a>
        </div>

        <h2>🚨 Common Issues & Solutions</h2>

        <div class="step warning">
            <h3>Issue: "No such file or directory" Error</h3>
            <p><strong>Cause:</strong> Database service not yet provisioned or connection variables not set.</p>
            <p><strong>Solution:</strong> Wait 2-3 minutes after adding the database service, then redeploy your app.</p>
        </div>

        <div class="step warning">
            <h3>Issue: Connection Timeout</h3>
            <p><strong>Cause:</strong> Database is still starting up or network issues.</p>
            <p><strong>Solution:</strong> The enhanced Database API includes retry logic and will automatically retry connections.</p>
        </div>

        <div class="step warning">
            <h3>Issue: SSL Certificate Errors</h3>
            <p><strong>Cause:</strong> Railway's SSL configuration conflicts with PHP's SSL verification.</p>
            <p><strong>Solution:</strong> The enhanced config disables SSL verification for Railway connections.</p>
        </div>

        <h2>🔍 Current Environment Variables</h2>
        
        <div class="step">
            <h3>Environment Variables Status</h3>
            <pre><?php
            $envVars = [
                'MYSQL_PUBLIC_URL' => $_ENV['MYSQL_PUBLIC_URL'] ?? 'NOT SET',
                'MYSQLHOST' => $_ENV['MYSQLHOST'] ?? 'NOT SET',
                'MYSQLPORT' => $_ENV['MYSQLPORT'] ?? 'NOT SET',
                'MYSQLUSER' => $_ENV['MYSQLUSER'] ?? 'NOT SET',
                'MYSQLPASSWORD' => isset($_ENV['MYSQLPASSWORD']) ? 'SET' : 'NOT SET',
                'MYSQLDATABASE' => $_ENV['MYSQLDATABASE'] ?? 'NOT SET',
                'DATABASE_URL' => $_ENV['DATABASE_URL'] ?? 'NOT SET'
            ];
            
            foreach ($envVars as $var => $value) {
                $status = $value === 'NOT SET' ? '❌' : '✅';
                echo "{$status} {$var}: {$value}\n";
            }
            ?></pre>
        </div>

        <h2>📚 Enhanced Database API Features</h2>

        <div class="step success">
            <h3>✅ Multiple Connection Methods</h3>
            <ul>
                <li><strong>MYSQL_PUBLIC_URL</strong> - Railway's preferred method</li>
                <li><strong>Individual Environment Variables</strong> - MYSQLHOST, MYSQLUSER, etc.</li>
                <li><strong>DATABASE_URL</strong> - Alternative format</li>
                <li><strong>MYSQL_URL</strong> - Another Railway format</li>
                <li><strong>Fallback Configuration</strong> - For local development</li>
            </ul>
        </div>

        <div class="step success">
            <h3>✅ Automatic Retry Logic</h3>
            <ul>
                <li>Up to 3 connection attempts</li>
                <li>Exponential backoff (2s, 4s, 8s delays)</li>
                <li>Detailed logging for debugging</li>
                <li>Graceful fallback when database is unavailable</li>
            </ul>
        </div>

        <div class="step success">
            <h3>✅ Railway-Optimized Settings</h3>
            <ul>
                <li>Disabled persistent connections (Railway doesn't like them)</li>
                <li>Disabled SSL verification (prevents certificate issues)</li>
                <li>Increased timeout (15 seconds)</li>
                <li>Proper charset configuration (utf8mb4)</li>
            </ul>
        </div>

        <h2>🎯 Next Steps</h2>

        <div class="step">
            <ol>
                <li><strong>Add MySQL Database:</strong> Follow Step 1 above</li>
                <li><strong>Wait for Provisioning:</strong> Give Railway 2-3 minutes</li>
                <li><strong>Test Connection:</strong> Use the testing tools above</li>
                <li><strong>Import Schema:</strong> Use the setup script to create tables</li>
                <li><strong>Verify Functionality:</strong> Test login/registration</li>
            </ol>
        </div>

        <div class="step success">
            <h3>🎉 You're All Set!</h3>
            <p>Once you follow these steps, your Nutrisaur application will have full database functionality with:</p>
            <ul>
                <li>✅ User authentication and registration</li>
                <li>✅ Admin panel functionality</li>
                <li>✅ FCM token management</li>
                <li>✅ Notification system</li>
                <li>✅ All database operations</li>
            </ul>
        </div>

        <hr>
        <p><em>Last updated: <?php echo date('Y-m-d H:i:s'); ?></em></p>
    </div>
</body>
</html>
