<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Test - Nutrisaur</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            color: #4CAF50;
            margin-bottom: 30px;
        }
        .test-section {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .test-section h3 {
            margin-top: 0;
            color: #333;
        }
        button {
            background: #4CAF50;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px;
        }
        button:hover {
            background: #45a049;
        }
        button:disabled {
            background: #cccccc;
            cursor: not-allowed;
        }
        .result {
            margin-top: 15px;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            white-space: pre-wrap;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        .code-display {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 18px;
            text-align: center;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Email Test Tool</h1>
            <p>Test multiple email sending methods for Nutrisaur</p>
        </div>

        <!-- Test 1: PHP mail() -->
        <div class="test-section">
            <h3>Test 1: PHP mail() Function</h3>
            <p>Send a simple test email using PHP's built-in mail() function.</p>
            <button onclick="testPhpMail()">Send Test Email (PHP mail())</button>
            <div id="php-result" class="result" style="display: none;"></div>
        </div>

        <!-- Test 2: Node.js Email Service -->
        <div class="test-section">
            <h3>Test 2: Node.js Email Service</h3>
            <p>Send a test email using the Node.js email service.</p>
            <button onclick="testNodeEmail()">Send Test Email (Node.js)</button>
            <div id="node-result" class="result" style="display: none;"></div>
        </div>

        <!-- Test 3: cURL Email -->
        <div class="test-section">
            <h3>Test 3: cURL Email Service</h3>
            <p>Send email using cURL to external email service.</p>
            <button onclick="testCurlEmail()">Send Test Email (cURL)</button>
            <div id="curl-result" class="result" style="display: none;"></div>
        </div>

        <!-- Test 4: File-based Email -->
        <div class="test-section">
            <h3>Test 4: File-based Email</h3>
            <p>Create email file that can be processed by system mail.</p>
            <button onclick="testFileEmail()">Send Test Email (File-based)</button>
            <div id="file-result" class="result" style="display: none;"></div>
        </div>

        <!-- Test 5: SendGrid API -->
        <div class="test-section">
            <h3>Test 5: SendGrid API</h3>
            <p>Send email using SendGrid API (if configured).</p>
            <button onclick="testSendGrid()">Send Test Email (SendGrid)</button>
            <div id="sendgrid-result" class="result" style="display: none;"></div>
        </div>

        <!-- Test 6: Working Email Solution -->
        <div class="test-section">
            <h3>Test 6: Working Email Solution</h3>
            <p>Send email using multiple fallback methods.</p>
            <button onclick="testWorkingEmail()">Send Working Email</button>
            <div id="working-result" class="result" style="display: none;"></div>
        </div>
    </div>

    <script>
        function showResult(elementId, message, type = 'info') {
            const element = document.getElementById(elementId);
            element.className = `result ${type}`;
            element.textContent = message;
            element.style.display = 'block';
        }

        function testPhpMail() {
            const button = event.target;
            button.disabled = true;
            button.textContent = 'Sending...';

            fetch('/api/test_php_mail.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    email: 'kevinpingol123@gmail.com',
                    subject: 'Test Email from Nutrisaur',
                    message: 'This is a test email sent at ' + new Date().toLocaleString()
                })
            })
            .then(response => response.json())
            .then(data => {
                showResult('php-result', JSON.stringify(data, null, 2), data.success ? 'success' : 'error');
            })
            .catch(error => {
                showResult('php-result', 'Error: ' + error.message, 'error');
            })
            .finally(() => {
                button.disabled = false;
                button.textContent = 'Send Test Email (PHP mail())';
            });
        }

        function testNodeEmail() {
            const button = event.target;
            button.disabled = true;
            button.textContent = 'Sending...';

            fetch('/api/test_node_email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    email: 'kevinpingol123@gmail.com',
                    username: 'TestUser',
                    verificationCode: '1234'
                })
            })
            .then(response => response.json())
            .then(data => {
                showResult('node-result', JSON.stringify(data, null, 2), data.success ? 'success' : 'error');
            })
            .catch(error => {
                showResult('node-result', 'Error: ' + error.message, 'error');
            })
            .finally(() => {
                button.disabled = false;
                button.textContent = 'Send Test Email (Node.js)';
            });
        }

        function testCurlEmail() {
            const button = event.target;
            button.disabled = true;
            button.textContent = 'Sending...';

            fetch('/api/test_curl_email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    email: 'kevinpingol123@gmail.com',
                    username: 'TestUser',
                    verificationCode: '1234'
                })
            })
            .then(response => response.json())
            .then(data => {
                showResult('curl-result', JSON.stringify(data, null, 2), data.success ? 'success' : 'error');
            })
            .catch(error => {
                showResult('curl-result', 'Error: ' + error.message, 'error');
            })
            .finally(() => {
                button.disabled = false;
                button.textContent = 'Send Test Email (cURL)';
            });
        }

        function testFileEmail() {
            const button = event.target;
            button.disabled = true;
            button.textContent = 'Sending...';

            fetch('/api/test_file_email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    email: 'kevinpingol123@gmail.com',
                    username: 'TestUser',
                    verificationCode: '1234'
                })
            })
            .then(response => response.json())
            .then(data => {
                showResult('file-result', JSON.stringify(data, null, 2), data.success ? 'success' : 'error');
            })
            .catch(error => {
                showResult('file-result', 'Error: ' + error.message, 'error');
            })
            .finally(() => {
                button.disabled = false;
                button.textContent = 'Send Test Email (File-based)';
            });
        }

        function testSendGrid() {
            const button = event.target;
            button.disabled = true;
            button.textContent = 'Sending...';

            fetch('/api/test_sendgrid.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    email: 'kevinpingol123@gmail.com',
                    username: 'TestUser',
                    verificationCode: '1234'
                })
            })
            .then(response => response.json())
            .then(data => {
                showResult('sendgrid-result', JSON.stringify(data, null, 2), data.success ? 'success' : 'error');
            })
            .catch(error => {
                showResult('sendgrid-result', 'Error: ' + error.message, 'error');
            })
            .finally(() => {
                button.disabled = false;
                button.textContent = 'Send Test Email (SendGrid)';
            });
        }

        function testWorkingEmail() {
            const button = event.target;
            button.disabled = true;
            button.textContent = 'Sending...';

            const code = Math.floor(1000 + Math.random() * 9000).toString();

            fetch('/api/test_working_email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    email: 'kevinpingol123@gmail.com',
                    username: 'TestUser',
                    verificationCode: code
                })
            })
            .then(response => response.json())
            .then(data => {
                const message = `Code: ${code}\n\nResponse: ${JSON.stringify(data, null, 2)}`;
                showResult('working-result', message, data.success ? 'success' : 'error');
            })
            .catch(error => {
                showResult('working-result', 'Error: ' + error.message, 'error');
            })
            .finally(() => {
                button.disabled = false;
                button.textContent = 'Send Working Email';
            });
        }
    </script>
</body>
</html>
