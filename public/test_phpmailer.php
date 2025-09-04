<?php
/**
 * Simple PHPMailer Test
 */

echo "=== PHPMailer Simple Test ===\n\n";

// Check if PHPMailer files exist
$phpmailerPath = __DIR__ . "/../vendor/phpmailer/phpmailer/src/";
$requiredFiles = ['Exception.php', 'PHPMailer.php', 'SMTP.php'];

echo "Checking PHPMailer files:\n";
$allFilesExist = true;
foreach ($requiredFiles as $file) {
    $filePath = $phpmailerPath . $file;
    if (file_exists($filePath)) {
        echo "✓ $file - EXISTS\n";
    } else {
        echo "✗ $file - MISSING\n";
        $allFilesExist = false;
    }
}

if (!$allFilesExist) {
    echo "\n❌ PHPMailer files are missing! Please ensure the vendor directory is uploaded.\n";
    exit;
}

// Try to load PHPMailer
try {
    require_once $phpmailerPath . 'Exception.php';
    require_once $phpmailerPath . 'PHPMailer.php';
    require_once $phpmailerPath . 'SMTP.php';
    
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;
    
    echo "\n✓ PHPMailer classes loaded successfully\n";
    
    // Create a simple PHPMailer instance
    $mail = new PHPMailer(true);
    echo "✓ PHPMailer instance created successfully\n";
    
    // Check email config
    $emailConfigPath = __DIR__ . "/../email_config.php";
    if (file_exists($emailConfigPath)) {
        require_once $emailConfigPath;
        echo "✓ email_config.php loaded\n";
        
        echo "\nEmail Configuration:\n";
        echo "SMTP Host: " . (defined('SMTP_HOST') ? SMTP_HOST : 'NOT SET') . "\n";
        echo "SMTP Port: " . (defined('SMTP_PORT') ? SMTP_PORT : 'NOT SET') . "\n";
        echo "SMTP Username: " . (defined('SMTP_USERNAME') ? SMTP_USERNAME : 'NOT SET') . "\n";
        echo "SMTP Password: " . (defined('SMTP_PASSWORD') ? (SMTP_PASSWORD === 'your-app-password' ? 'NOT CONFIGURED' : 'CONFIGURED') : 'NOT SET') . "\n";
        
        if (defined('SMTP_USERNAME') && defined('SMTP_PASSWORD') && SMTP_PASSWORD !== 'your-app-password') {
            echo "\n✅ Email configuration appears to be set up correctly!\n";
        } else {
            echo "\n⚠️  Email configuration needs to be updated in email_config.php\n";
        }
    } else {
        echo "✗ email_config.php not found\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ Error loading PHPMailer: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "\n❌ Error loading PHPMailer: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
