<?php
/**
 * Web-accessible Forgot Password Database Fix
 * Run this through your web browser to fix the database issue
 */

require_once 'config.php';

// Set content type
header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Forgot Password Database Issue</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Fix Forgot Password Database Issue</h1>
        <p>This tool will add the missing database columns required for forgot password functionality.</p>
        
        <?php
        try {
            // Get database connection
            $pdo = getDatabaseConnection();
            
            if (!$pdo) {
                throw new Exception('Database connection failed');
            }
            
            echo "<div class='info'>Starting database fix...</div>";
            
            // Check if password_reset_code column exists
            $checkCodeColumn = $pdo->query("SHOW COLUMNS FROM community_users LIKE 'password_reset_code'");
            $codeColumnExists = $checkCodeColumn->rowCount() > 0;
            
            // Check if password_reset_expires column exists
            $checkExpiresColumn = $pdo->query("SHOW COLUMNS FROM community_users LIKE 'password_reset_expires'");
            $expiresColumnExists = $checkExpiresColumn->rowCount() > 0;
            
            echo "<div class='info'>Current status:</div>";
            echo "<ul>";
            echo "<li>password_reset_code column exists: " . ($codeColumnExists ? "✅ YES" : "❌ NO") . "</li>";
            echo "<li>password_reset_expires column exists: " . ($expiresColumnExists ? "✅ YES" : "❌ NO") . "</li>";
            echo "</ul>";
            
            // Add the password reset columns if they don't exist
            if (!$codeColumnExists) {
                echo "<div class='info'>Adding password_reset_code column...</div>";
                $sql1 = "ALTER TABLE community_users 
                        ADD COLUMN password_reset_code VARCHAR(4) DEFAULT NULL COMMENT '4-digit password reset code'";
                $pdo->exec($sql1);
                echo "<div class='success'>✅ password_reset_code column added successfully</div>";
            } else {
                echo "<div class='success'>✅ password_reset_code column already exists</div>";
            }
            
            if (!$expiresColumnExists) {
                echo "<div class='info'>Adding password_reset_expires column...</div>";
                $sql2 = "ALTER TABLE community_users 
                        ADD COLUMN password_reset_expires DATETIME DEFAULT NULL COMMENT 'Password reset code expiration time'";
                $pdo->exec($sql2);
                echo "<div class='success'>✅ password_reset_expires column added successfully</div>";
            } else {
                echo "<div class='success'>✅ password_reset_expires column already exists</div>";
            }
            
            // Add indexes for faster lookups (ignore errors if they already exist)
            try {
                $pdo->exec("CREATE INDEX idx_community_users_reset_code ON community_users(password_reset_code)");
                echo "<div class='success'>✅ Index on password_reset_code created</div>";
            } catch (Exception $e) {
                echo "<div class='warning'>ℹ️ Index on password_reset_code already exists or error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
            
            try {
                $pdo->exec("CREATE INDEX idx_community_users_reset_expires ON community_users(password_reset_expires)");
                echo "<div class='success'>✅ Index on password_reset_expires created</div>";
            } catch (Exception $e) {
                echo "<div class='warning'>ℹ️ Index on password_reset_expires already exists or error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
            
            // Verify the changes
            echo "<div class='info'>Verifying changes...</div>";
            $result = $pdo->query("DESCRIBE community_users");
            $columns = $result->fetchAll(PDO::FETCH_ASSOC);
            
            $hasResetCode = false;
            $hasResetExpires = false;
            
            echo "<pre>";
            echo "Community Users Table Structure:\n";
            echo "================================\n";
            foreach ($columns as $column) {
                if ($column['Field'] === 'password_reset_code') {
                    $hasResetCode = true;
                    echo "✅ " . $column['Field'] . ": " . $column['Type'] . " " . $column['Null'] . " " . $column['Default'] . "\n";
                } elseif ($column['Field'] === 'password_reset_expires') {
                    $hasResetExpires = true;
                    echo "✅ " . $column['Field'] . ": " . $column['Type'] . " " . $column['Null'] . " " . $column['Default'] . "\n";
                } else {
                    echo "   " . $column['Field'] . ": " . $column['Type'] . " " . $column['Null'] . " " . $column['Default'] . "\n";
                }
            }
            echo "</pre>";
            
            if ($hasResetCode && $hasResetExpires) {
                echo "<div class='success'>";
                echo "<h2>🎉 SUCCESS: Forgot password functionality should now work!</h2>";
                echo "<p>The community_users table now has the required columns for password reset.</p>";
                echo "</div>";
                
                // Test the API endpoint
                echo "<div class='info'>Testing forgot password API...</div>";
                
                // Get a test user
                $stmt = $pdo->query("SELECT email FROM community_users LIMIT 1");
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    $testEmail = $user['email'];
                    echo "<p>Testing with email: <strong>" . htmlspecialchars($testEmail) . "</strong></p>";
                    
                    // Simulate the API call
                    $_POST['email'] = $testEmail;
                    $_GET['action'] = 'forgot_password_community';
                    $_SERVER['REQUEST_METHOD'] = 'POST';
                    
                    // Capture output
                    ob_start();
                    include 'api/DatabaseAPI.php';
                    $output = ob_get_clean();
                    
                    echo "<pre>API Response: " . htmlspecialchars($output) . "</pre>";
                    
                    $response = json_decode($output, true);
                    if ($response && isset($response['success'])) {
                        if ($response['success']) {
                            echo "<div class='success'>✅ API test successful! Forgot password is working correctly.</div>";
                        } else {
                            echo "<div class='warning'>⚠️ API returned: " . htmlspecialchars($response['message']) . "</div>";
                        }
                    } else {
                        echo "<div class='error'>❌ Invalid API response format</div>";
                    }
                } else {
                    echo "<div class='warning'>⚠️ No users found in database to test with</div>";
                }
                
            } else {
                echo "<div class='error'>";
                echo "<h2>❌ ERROR: Some columns are still missing!</h2>";
                echo "<p>password_reset_code exists: " . ($hasResetCode ? "YES" : "NO") . "</p>";
                echo "<p>password_reset_expires exists: " . ($hasResetExpires ? "YES" : "NO") . "</p>";
                echo "</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>";
            echo "<h2>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</h2>";
            echo "<pre>Stack trace: " . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            echo "</div>";
        }
        ?>
        
        <hr>
        <p><strong>Next Steps:</strong></p>
        <ul>
            <li>Test the forgot password functionality in your Android app</li>
            <li>The toast should now show "Reset code sent to your email!" instead of column errors</li>
            <li>You can delete this file after confirming the fix works</li>
        </ul>
        
        <a href="javascript:location.reload()" class="btn">🔄 Run Fix Again</a>
        <a href="home.php" class="btn">🏠 Back to Home</a>
    </div>
</body>
</html>
