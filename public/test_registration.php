<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUTRISAUR - Registration Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        button {
            background-color: #A1B454;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
        }
        button:hover {
            background-color: #8CA86E;
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>NUTRISAUR Registration Test</h1>
        <p>Use this form to test the registration and verification process.</p>
        
        <div id="message"></div>
        
        <!-- Registration Form -->
        <div id="registration-form">
            <h2>Test Registration</h2>
            <form id="test-register-form">
                <div class="form-group">
                    <label for="test-username">Username:</label>
                    <input type="text" id="test-username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="test-email">Email:</label>
                    <input type="email" id="test-email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="test-password">Password:</label>
                    <input type="password" id="test-password" name="password" required>
                </div>
                <button type="submit">Test Registration</button>
            </form>
        </div>
        
        <!-- Verification Form -->
        <div id="verification-form" style="display: none;">
            <h2>Test Email Verification</h2>
            <form id="test-verify-form">
                <div class="form-group">
                    <label for="verify-email">Email:</label>
                    <input type="email" id="verify-email" name="email" readonly>
                </div>
                <div class="form-group">
                    <label for="verify-code">Verification Code:</label>
                    <input type="text" id="verify-code" name="verification_code" placeholder="Enter 4-digit code" maxlength="4" pattern="[0-9]{4}" required>
                </div>
                <button type="submit">Verify Email</button>
                <button type="button" id="resend-code">Resend Code</button>
                <button type="button" id="back-to-register">Back to Registration</button>
            </form>
        </div>
        
        <div style="margin-top: 30px;">
            <h3>Test Results</h3>
            <div id="test-results"></div>
        </div>
        
        <div style="margin-top: 20px;">
            <a href="test_verification.php">Run System Tests</a> | 
            <a href="home.php">Back to Home</a>
        </div>
    </div>

    <script>
        let currentTestEmail = '';
        let currentTestUsername = '';
        
        // Registration form handler
        document.getElementById('test-register-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const username = document.getElementById('test-username').value;
            const email = document.getElementById('test-email').value;
            const password = document.getElementById('test-password').value;
            
            showMessage('Testing registration...', 'info');
            
            try {
                const formData = new FormData();
                formData.append('username', username);
                formData.append('email', email);
                formData.append('password', password);
                formData.append('ajax_action', 'register');
                
                const response = await fetch('/home.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    if (data.requires_verification) {
                        currentTestEmail = email;
                        currentTestUsername = username;
                        
                        showMessage('Registration successful! Please check your email for verification code.', 'success');
                        showMessage('Test verification code: ' + (data.data?.verification_code || 'Not provided'), 'info');
                        
                        // Show verification form
                        document.getElementById('registration-form').style.display = 'none';
                        document.getElementById('verification-form').style.display = 'block';
                        document.getElementById('verify-email').value = email;
                    } else {
                        showMessage('Registration successful! (No verification required)', 'success');
                    }
                } else {
                    showMessage('Registration failed: ' + data.message, 'error');
                }
            } catch (error) {
                showMessage('Registration test failed: ' + error.message, 'error');
            }
        });
        
        // Verification form handler
        document.getElementById('test-verify-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const email = document.getElementById('verify-email').value;
            const code = document.getElementById('verify-code').value;
            
            showMessage('Testing verification...', 'info');
            
            try {
                const formData = new FormData();
                formData.append('email', email);
                formData.append('verification_code', code);
                formData.append('ajax_action', 'verify_email');
                
                const response = await fetch('/home.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage('Email verification successful!', 'success');
                    addTestResult('✅ Email verification test passed');
                } else {
                    showMessage('Verification failed: ' + data.message, 'error');
                    addTestResult('❌ Email verification test failed: ' + data.message);
                }
            } catch (error) {
                showMessage('Verification test failed: ' + error.message, 'error');
                addTestResult('❌ Email verification test failed: ' + error.message);
            }
        });
        
        // Resend code handler
        document.getElementById('resend-code').addEventListener('click', async function() {
            const email = document.getElementById('verify-email').value;
            
            showMessage('Resending verification code...', 'info');
            
            try {
                const formData = new FormData();
                formData.append('email', email);
                formData.append('ajax_action', 'resend_verification');
                
                const response = await fetch('/home.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage('Verification code sent successfully!', 'success');
                    addTestResult('✅ Resend verification test passed');
                } else {
                    showMessage('Resend failed: ' + data.message, 'error');
                    addTestResult('❌ Resend verification test failed: ' + data.message);
                }
            } catch (error) {
                showMessage('Resend test failed: ' + error.message, 'error');
                addTestResult('❌ Resend verification test failed: ' + error.message);
            }
        });
        
        // Back to registration handler
        document.getElementById('back-to-register').addEventListener('click', function() {
            document.getElementById('verification-form').style.display = 'none';
            document.getElementById('registration-form').style.display = 'block';
            clearMessage();
        });
        
        function showMessage(message, type) {
            const messageDiv = document.getElementById('message');
            messageDiv.textContent = message;
            messageDiv.className = 'message ' + type;
        }
        
        function clearMessage() {
            document.getElementById('message').textContent = '';
            document.getElementById('message').className = 'message';
        }
        
        function addTestResult(result) {
            const resultsDiv = document.getElementById('test-results');
            const resultItem = document.createElement('div');
            resultItem.textContent = result;
            resultItem.style.marginBottom = '5px';
            resultsDiv.appendChild(resultItem);
        }
    </script>
</body>
</html>
