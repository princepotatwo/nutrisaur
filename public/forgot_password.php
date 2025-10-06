<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUTRISAUR - Forgot Password</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #2A3326 0%, #A1B454 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: hidden;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            font-size: 32px;
            font-weight: bold;
            color: #2A3326;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #666;
            font-size: 16px;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }

        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin: 0 10px;
            transition: all 0.3s ease;
        }

        .step.active {
            background: #A1B454;
            color: white;
        }

        .step.completed {
            background: #4CAF50;
            color: white;
        }

        .step.inactive {
            background: #E0E0E0;
            color: #999;
        }

        .step-line {
            width: 30px;
            height: 2px;
            background: #E0E0E0;
            margin-top: 19px;
        }

        .step-line.completed {
            background: #4CAF50;
        }

        .form-step {
            display: none;
        }

        .form-step.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #E0E0E0;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #A1B454;
        }

        .btn {
            width: 100%;
            padding: 15px;
            background: #A1B454;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s ease;
            margin-top: 10px;
        }

        .btn:hover {
            background: #8FA43A;
        }

        .btn:disabled {
            background: #CCCCCC;
            cursor: not-allowed;
        }

        .btn-secondary {
            background: #6C757D;
        }

        .btn-secondary:hover {
            background: #5A6268;
        }

        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }

        .message.success {
            background: #D4EDDA;
            color: #155724;
            border: 1px solid #C3E6CB;
        }

        .message.error {
            background: #F8D7DA;
            color: #721C24;
            border: 1px solid #F5C6CB;
        }

        .message.info {
            background: #D1ECF1;
            color: #0C5460;
            border: 1px solid #BEE5EB;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #A1B454;
            text-decoration: none;
            font-weight: 500;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .loading {
            display: none;
            text-align: center;
            margin: 20px 0;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #A1B454;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .code-input {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
        }

        .code-digit {
            width: 50px;
            height: 50px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            border: 2px solid #E0E0E0;
            border-radius: 10px;
            background: #F8F9FA;
        }

        .code-digit:focus {
            outline: none;
            border-color: #A1B454;
            background: white;
        }

        .resend-section {
            text-align: center;
            margin-top: 20px;
        }

        .resend-link {
            color: #A1B454;
            text-decoration: none;
            font-weight: 500;
        }

        .resend-link:hover {
            text-decoration: underline;
        }

        .resend-link:disabled {
            color: #999;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">NUTRISAUR</div>
            <div class="subtitle">Reset Your Password</div>
        </div>

        <div class="step-indicator">
            <div class="step active" id="step1">1</div>
            <div class="step-line"></div>
            <div class="step inactive" id="step2">2</div>
            <div class="step-line"></div>
            <div class="step inactive" id="step3">3</div>
        </div>

        <!-- Step 1: Email Input -->
        <div class="form-step active" id="formStep1">
            <div class="form-group">
                <label for="email">Enter your email address</label>
                <input type="email" id="email" placeholder="your.email@example.com" required>
            </div>
            <button class="btn" onclick="sendResetCode()">Send Reset Code</button>
            <div class="loading" id="loading1">
                <div class="spinner"></div>
                <p>Sending reset code...</p>
            </div>
        </div>

        <!-- Step 2: Code Verification -->
        <div class="form-step" id="formStep2">
            <div class="message info">
                We've sent a 4-digit reset code to <strong id="userEmail"></strong>
            </div>
            <div class="form-group">
                <label>Enter the 4-digit code</label>
                <div class="code-input">
                    <input type="text" class="code-digit" maxlength="1" id="code1" onkeyup="moveToNext(1)">
                    <input type="text" class="code-digit" maxlength="1" id="code2" onkeyup="moveToNext(2)">
                    <input type="text" class="code-digit" maxlength="1" id="code3" onkeyup="moveToNext(3)">
                    <input type="text" class="code-digit" maxlength="1" id="code4" onkeyup="moveToNext(4)">
                </div>
            </div>
            <button class="btn" onclick="verifyCode()">Verify Code</button>
            <div class="resend-section">
                <a href="#" class="resend-link" onclick="resendCode()" id="resendLink">Resend Code</a>
                <div id="resendTimer" style="display: none;">
                    Resend available in <span id="countdown">60</span> seconds
                </div>
            </div>
            <div class="loading" id="loading2">
                <div class="spinner"></div>
                <p>Verifying code...</p>
            </div>
        </div>

        <!-- Step 3: New Password -->
        <div class="form-step" id="formStep3">
            <div class="message success">
                Code verified! Now set your new password
            </div>
            <div class="form-group">
                <label for="newPassword">New Password</label>
                <input type="password" id="newPassword" placeholder="Enter new password" required>
            </div>
            <div class="form-group">
                <label for="confirmPassword">Confirm Password</label>
                <input type="password" id="confirmPassword" placeholder="Confirm new password" required>
            </div>
            <button class="btn" onclick="updatePassword()">Update Password</button>
            <div class="loading" id="loading3">
                <div class="spinner"></div>
                <p>Updating password...</p>
            </div>
        </div>

        <div class="back-link">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>

    <script>
        let currentStep = 1;
        let userEmail = '';
        let resetCode = '';

        function showStep(step) {
            // Hide all steps
            document.querySelectorAll('.form-step').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.step').forEach(el => {
                el.classList.remove('active', 'completed');
                el.classList.add('inactive');
            });
            document.querySelectorAll('.step-line').forEach(el => el.classList.remove('completed'));

            // Show current step
            document.getElementById('formStep' + step).classList.add('active');
            
            // Update step indicators
            for (let i = 1; i <= step; i++) {
                const stepEl = document.getElementById('step' + i);
                if (i < step) {
                    stepEl.classList.remove('inactive');
                    stepEl.classList.add('completed');
                } else if (i === step) {
                    stepEl.classList.remove('inactive');
                    stepEl.classList.add('active');
                }
                
                if (i < step) {
                    const lineEl = stepEl.nextElementSibling;
                    if (lineEl && lineEl.classList.contains('step-line')) {
                        lineEl.classList.add('completed');
                    }
                }
            }
        }

        function showMessage(message, type = 'info') {
            const messageEl = document.createElement('div');
            messageEl.className = `message ${type}`;
            messageEl.textContent = message;
            
            const currentStepEl = document.querySelector('.form-step.active');
            currentStepEl.insertBefore(messageEl, currentStepEl.firstChild);
            
            setTimeout(() => {
                messageEl.remove();
            }, 5000);
        }

        function showLoading(step, show = true) {
            document.getElementById('loading' + step).style.display = show ? 'block' : 'none';
        }

        async function sendResetCode() {
            const email = document.getElementById('email').value.trim();
            
            if (!email) {
                showMessage('Please enter your email address', 'error');
                return;
            }

            if (!isValidEmail(email)) {
                showMessage('Please enter a valid email address', 'error');
                return;
            }

            showLoading(1, true);
            
            try {
                const response = await fetch('api/DatabaseAPI.php?action=forgot_password_community', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `email=${encodeURIComponent(email)}`
                });

                const data = await response.json();
                
                if (data.success) {
                    userEmail = email;
                    document.getElementById('userEmail').textContent = email;
                    currentStep = 2;
                    showStep(2);
                    showMessage('Reset code sent to your email!', 'success');
                    startResendTimer();
                } else {
                    showMessage(data.message || 'Failed to send reset code', 'error');
                }
            } catch (error) {
                showMessage('Network error. Please try again.', 'error');
            } finally {
                showLoading(1, false);
            }
        }

        function moveToNext(index) {
            const currentInput = document.getElementById('code' + index);
            const nextInput = document.getElementById('code' + (index + 1));
            
            if (currentInput.value.length === 1 && nextInput) {
                nextInput.focus();
            }
        }

        async function verifyCode() {
            const code1 = document.getElementById('code1').value;
            const code2 = document.getElementById('code2').value;
            const code3 = document.getElementById('code3').value;
            const code4 = document.getElementById('code4').value;
            
            const code = code1 + code2 + code3 + code4;
            
            if (code.length !== 4) {
                showMessage('Please enter the complete 4-digit code', 'error');
                return;
            }

            showLoading(2, true);
            
            try {
                const response = await fetch('api/DatabaseAPI.php?action=verify_reset_code_community', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `email=${encodeURIComponent(userEmail)}&reset_code=${code}`
                });

                const data = await response.json();
                
                if (data.success) {
                    resetCode = code;
                    currentStep = 3;
                    showStep(3);
                    showMessage('Code verified successfully!', 'success');
                } else {
                    showMessage(data.message || 'Invalid or expired code', 'error');
                    // Clear the code inputs
                    document.querySelectorAll('.code-digit').forEach(input => input.value = '');
                    document.getElementById('code1').focus();
                }
            } catch (error) {
                showMessage('Network error. Please try again.', 'error');
            } finally {
                showLoading(2, false);
            }
        }

        async function updatePassword() {
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (!newPassword || !confirmPassword) {
                showMessage('Please fill in all password fields', 'error');
                return;
            }

            if (newPassword.length < 6) {
                showMessage('Password must be at least 6 characters long', 'error');
                return;
            }

            if (newPassword !== confirmPassword) {
                showMessage('Passwords do not match', 'error');
                return;
            }

            showLoading(3, true);
            
            try {
                const response = await fetch('api/DatabaseAPI.php?action=update_password_community', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `email=${encodeURIComponent(userEmail)}&reset_code=${resetCode}&new_password=${encodeURIComponent(newPassword)}`
                });

                const data = await response.json();
                
                if (data.success) {
                    showMessage('Password updated successfully! You can now login with your new password.', 'success');
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 2000);
                } else {
                    showMessage(data.message || 'Failed to update password', 'error');
                }
            } catch (error) {
                showMessage('Network error. Please try again.', 'error');
            } finally {
                showLoading(3, false);
            }
        }

        function resendCode() {
            sendResetCode();
        }

        function startResendTimer() {
            const resendLink = document.getElementById('resendLink');
            const resendTimer = document.getElementById('resendTimer');
            const countdownEl = document.getElementById('countdown');
            
            resendLink.style.display = 'none';
            resendTimer.style.display = 'block';
            
            let timeLeft = 60;
            countdownEl.textContent = timeLeft;
            
            const timer = setInterval(() => {
                timeLeft--;
                countdownEl.textContent = timeLeft;
                
                if (timeLeft <= 0) {
                    clearInterval(timer);
                    resendLink.style.display = 'block';
                    resendTimer.style.display = 'none';
                }
            }, 1000);
        }

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        // Auto-focus first code input when step 2 is shown
        document.addEventListener('DOMContentLoaded', function() {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        if (document.getElementById('formStep2').classList.contains('active')) {
                            document.getElementById('code1').focus();
                        }
                    }
                });
            });
            
            observer.observe(document.getElementById('formStep2'), {
                attributes: true,
                attributeFilter: ['class']
            });
        });
    </script>
</body>
</html>
