<?php
/**
 * Google OAuth Database Setup Script
 * This script adds the necessary database fields for Google OAuth integration
 */

// Include the database configuration
require_once __DIR__ . "/../config.php";

// Set content type to JSON for API responses
header('Content-Type: application/json');

// Check if this is a POST request to run the migration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migration'])) {
    try {
        $pdo = getDatabaseConnection();
        
        if ($pdo === null) {
            echo json_encode([
                'success' => false, 
                'message' => 'Database connection failed'
            ]);
            exit;
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Check if Google OAuth columns already exist
        $checkColumns = $pdo->query("SHOW COLUMNS FROM users LIKE 'google_id'");
        if ($checkColumns->rowCount() > 0) {
            echo json_encode([
                'success' => true, 
                'message' => 'Google OAuth fields already exist in users table',
                'already_exists' => true
            ]);
            $pdo->rollback();
            exit;
        }
        
        // Add Google OAuth columns
        $sql = "
            ALTER TABLE users 
            ADD COLUMN google_id VARCHAR(255) NULL UNIQUE,
            ADD COLUMN google_name VARCHAR(255) NULL,
            ADD COLUMN google_picture TEXT NULL,
            ADD COLUMN google_given_name VARCHAR(100) NULL,
            ADD COLUMN google_family_name VARCHAR(100) NULL
        ";
        
        $pdo->exec($sql);
        
        // Add indexes
        $pdo->exec("CREATE INDEX idx_users_google_id ON users(google_id)");
        
        // Check if email index exists, if not create it
        $checkEmailIndex = $pdo->query("SHOW INDEX FROM users WHERE Key_name = 'idx_users_email'");
        if ($checkEmailIndex->rowCount() === 0) {
            $pdo->exec("CREATE INDEX idx_users_email ON users(email)");
        }
        
        // Update table comment
        $pdo->exec("ALTER TABLE users COMMENT = 'Users table with support for both traditional and Google OAuth authentication'");
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Google OAuth fields added successfully to users table'
        ]);
        
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollback();
        }
        
        echo json_encode([
            'success' => false, 
            'message' => 'Migration failed: ' . $e->getMessage()
        ]);
        
        error_log("Google OAuth migration error: " . $e->getMessage());
    }
    exit;
}

// Check if this is a GET request to check migration status
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['check_status'])) {
    try {
        $pdo = getDatabaseConnection();
        
        if ($pdo === null) {
            echo json_encode([
                'success' => false, 
                'message' => 'Database connection failed'
            ]);
            exit;
        }
        
        // Check if Google OAuth columns exist
        $checkColumns = $pdo->query("SHOW COLUMNS FROM users LIKE 'google_id'");
        $hasGoogleFields = $checkColumns->rowCount() > 0;
        
        // Get table structure
        $columns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'has_google_fields' => $hasGoogleFields,
            'columns' => $columns,
            'message' => $hasGoogleFields ? 
                'Google OAuth fields are present' : 
                'Google OAuth fields are missing'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => 'Status check failed: ' . $e->getMessage()
        ]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google OAuth Setup - NUTRISAUR</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1A211A, #2A3326);
            color: #E8F0D6;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(42, 51, 38, 0.8);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(161, 180, 84, 0.2);
        }
        
        h1 {
            color: #A1B454;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .status-card {
            background: rgba(161, 180, 84, 0.1);
            border: 1px solid rgba(161, 180, 84, 0.3);
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .status-card.success {
            background: rgba(76, 175, 80, 0.1);
            border-color: rgba(76, 175, 80, 0.3);
        }
        
        .status-card.warning {
            background: rgba(255, 193, 7, 0.1);
            border-color: rgba(255, 193, 7, 0.3);
        }
        
        .status-card.error {
            background: rgba(244, 67, 54, 0.1);
            border-color: rgba(244, 67, 54, 0.3);
        }
        
        .btn {
            background: #A1B454;
            color: #1A211A;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin: 10px 5px;
        }
        
        .btn:hover {
            background: #8CA86E;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(161, 180, 84, 0.3);
        }
        
        .btn:disabled {
            background: #546048;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .code-block {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(161, 180, 84, 0.2);
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
        }
        
        .step {
            margin: 20px 0;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            border-left: 4px solid #A1B454;
        }
        
        .step h3 {
            color: #A1B454;
            margin-top: 0;
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            display: none;
        }
        
        .message.success {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
            border: 1px solid rgba(76, 175, 80, 0.3);
        }
        
        .message.error {
            background: rgba(244, 67, 54, 0.1);
            color: #F44336;
            border: 1px solid rgba(244, 67, 54, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🦕 Google OAuth Setup</h1>
        
        <div class="status-card" id="status-card">
            <h3>Database Status</h3>
            <p id="status-message">Checking database status...</p>
            <div id="status-details"></div>
        </div>
        
        <div class="message" id="message"></div>
        
        <div class="step">
            <h3>Step 1: Google Cloud Console Setup</h3>
            <p>Before running the migration, you need to set up Google OAuth credentials:</p>
            <ol>
                <li>Go to <a href="https://console.cloud.google.com/" target="_blank" style="color: #A1B454;">Google Cloud Console</a></li>
                <li>Create a new project or select an existing one</li>
                <li>Enable the Google+ API and Google OAuth2 API</li>
                <li>Go to "Credentials" and create OAuth 2.0 Client IDs</li>
                <li>Add your domain to authorized origins</li>
                <li>Copy the Client ID and Client Secret</li>
            </ol>
        </div>
        
        <div class="step">
            <h3>Step 2: Update Configuration</h3>
            <p>Update the following files with your Google OAuth credentials:</p>
            <div class="code-block">
                <strong>google-oauth-config.js:</strong><br>
                clientId: 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com'
            </div>
            <div class="code-block">
                <strong>home.php (in exchangeGoogleCodeForToken function):</strong><br>
                $clientId = 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com';<br>
                $clientSecret = 'YOUR_GOOGLE_CLIENT_SECRET';
            </div>
        </div>
        
        <div class="step">
            <h3>Step 3: Run Database Migration</h3>
            <p>Click the button below to add Google OAuth fields to your users table:</p>
            <button class="btn" id="run-migration" onclick="runMigration()">Run Migration</button>
            <button class="btn" id="check-status" onclick="checkStatus()">Check Status</button>
        </div>
        
        <div class="step">
            <h3>Step 4: Test Google OAuth</h3>
            <p>After completing the setup, test the Google OAuth functionality:</p>
            <ol>
                <li>Go to your home page</li>
                <li>Click "Sign in with Google" or "Sign up with Google"</li>
                <li>Complete the Google OAuth flow</li>
                <li>Verify that you're logged in successfully</li>
            </ol>
        </div>
    </div>

    <script>
        // Check status on page load
        document.addEventListener('DOMContentLoaded', function() {
            checkStatus();
        });
        
        async function checkStatus() {
            try {
                const response = await fetch('?check_status=1');
                const data = await response.json();
                
                const statusCard = document.getElementById('status-card');
                const statusMessage = document.getElementById('status-message');
                const statusDetails = document.getElementById('status-details');
                
                if (data.success) {
                    if (data.has_google_fields) {
                        statusCard.className = 'status-card success';
                        statusMessage.textContent = '✅ Google OAuth fields are present in the database';
                        statusDetails.innerHTML = '<p>Your database is ready for Google OAuth integration.</p>';
                    } else {
                        statusCard.className = 'status-card warning';
                        statusMessage.textContent = '⚠️ Google OAuth fields are missing';
                        statusDetails.innerHTML = '<p>Click "Run Migration" to add the required fields.</p>';
                    }
                } else {
                    statusCard.className = 'status-card error';
                    statusMessage.textContent = '❌ Error checking database status';
                    statusDetails.innerHTML = '<p>' + data.message + '</p>';
                }
            } catch (error) {
                console.error('Error checking status:', error);
                showMessage('Error checking database status: ' + error.message, 'error');
            }
        }
        
        async function runMigration() {
            const button = document.getElementById('run-migration');
            const originalText = button.textContent;
            
            button.disabled = true;
            button.textContent = 'Running Migration...';
            
            try {
                const formData = new FormData();
                formData.append('run_migration', '1');
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    if (data.already_exists) {
                        showMessage('Google OAuth fields already exist in the database.', 'success');
                    } else {
                        showMessage('Migration completed successfully! Google OAuth fields have been added.', 'success');
                    }
                    checkStatus(); // Refresh status
                } else {
                    showMessage('Migration failed: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Migration error:', error);
                showMessage('Migration failed: ' + error.message, 'error');
            } finally {
                button.disabled = false;
                button.textContent = originalText;
            }
        }
        
        function showMessage(message, type) {
            const messageDiv = document.getElementById('message');
            messageDiv.textContent = message;
            messageDiv.className = 'message ' + type;
            messageDiv.style.display = 'block';
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 5000);
        }
    </script>
</body>
</html>
