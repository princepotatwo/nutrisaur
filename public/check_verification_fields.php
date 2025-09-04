<?php
/**
 * Check and add verification fields to users table
 */

require_once __DIR__ . "/../config.php";

try {
    $pdo = getDatabaseConnection();
    
    if (!$pdo) {
        echo "Database connection failed\n";
        exit;
    }
    
    echo "=== Database Verification Fields Check ===\n\n";
    
    // Check if verification fields exist
    $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'email_verified'");
    $stmt->execute();
    $emailVerifiedExists = $stmt->rowCount() > 0;
    
    $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'verification_code'");
    $stmt->execute();
    $verificationCodeExists = $stmt->rowCount() > 0;
    
    $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'verification_code_expires'");
    $stmt->execute();
    $verificationExpiresExists = $stmt->rowCount() > 0;
    
    $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'verification_sent_at'");
    $stmt->execute();
    $verificationSentExists = $stmt->rowCount() > 0;
    
    echo "Checking verification fields:\n";
    echo "email_verified: " . ($emailVerifiedExists ? "EXISTS" : "MISSING") . "\n";
    echo "verification_code: " . ($verificationCodeExists ? "EXISTS" : "MISSING") . "\n";
    echo "verification_code_expires: " . ($verificationExpiresExists ? "EXISTS" : "MISSING") . "\n";
    echo "verification_sent_at: " . ($verificationSentExists ? "EXISTS" : "MISSING") . "\n";
    
    // Add missing fields
    if (!$emailVerifiedExists) {
        echo "Adding email_verified field...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN email_verified TINYINT(1) DEFAULT 0");
        echo "✓ email_verified field added\n";
    }
    
    if (!$verificationCodeExists) {
        echo "Adding verification_code field...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN verification_code VARCHAR(4) NULL");
        echo "✓ verification_code field added\n";
    }
    
    if (!$verificationExpiresExists) {
        echo "Adding verification_code_expires field...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN verification_code_expires TIMESTAMP NULL");
        echo "✓ verification_code_expires field added\n";
    }
    
    if (!$verificationSentExists) {
        echo "Adding verification_sent_at field...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN verification_sent_at TIMESTAMP NULL");
        echo "✓ verification_sent_at field added\n";
    }
    
    // Check if indexes exist
    $stmt = $pdo->prepare("SHOW INDEX FROM users WHERE Key_name = 'idx_verification_code'");
    $stmt->execute();
    $verificationIndexExists = $stmt->rowCount() > 0;
    
    $stmt = $pdo->prepare("SHOW INDEX FROM users WHERE Key_name = 'idx_email_verified'");
    $stmt->execute();
    $emailVerifiedIndexExists = $stmt->rowCount() > 0;
    
    echo "\nChecking indexes:\n";
    echo "idx_verification_code: " . ($verificationIndexExists ? "EXISTS" : "MISSING") . "\n";
    echo "idx_email_verified: " . ($emailVerifiedIndexExists ? "EXISTS" : "MISSING") . "\n";
    
    // Add missing indexes
    if (!$verificationIndexExists) {
        echo "Adding idx_verification_code index...\n";
        $pdo->exec("CREATE INDEX idx_verification_code ON users(verification_code)");
        echo "✓ idx_verification_code index added\n";
    }
    
    if (!$emailVerifiedIndexExists) {
        echo "Adding idx_email_verified index...\n";
        $pdo->exec("CREATE INDEX idx_email_verified ON users(email_verified)");
        echo "✓ idx_email_verified index added\n";
    }
    
    echo "\n=== PHPMailer Installation Check ===\n\n";
    
    // Check PHPMailer files
    $phpmailerPath = __DIR__ . "/../vendor/phpmailer/phpmailer/src/";
    $requiredFiles = ['Exception.php', 'PHPMailer.php', 'SMTP.php'];
    
    echo "Checking PHPMailer files:\n";
    foreach ($requiredFiles as $file) {
        $filePath = $phpmailerPath . $file;
        if (file_exists($filePath)) {
            echo "✓ $file - EXISTS\n";
        } else {
            echo "✗ $file - MISSING\n";
        }
    }
    
    // Check email config
    $emailConfigPath = __DIR__ . "/../email_config.php";
    if (file_exists($emailConfigPath)) {
        echo "✓ email_config.php - EXISTS\n";
    } else {
        echo "✗ email_config.php - MISSING\n";
    }
    
    // Check EmailService
    $emailServicePath = __DIR__ . "/api/EmailService.php";
    if (file_exists($emailServicePath)) {
        echo "✓ EmailService.php - EXISTS\n";
    } else {
        echo "✗ EmailService.php - MISSING\n";
    }
    
    echo "\n=== Email Configuration Check ===\n\n";
    
    // Try to load email config
    if (file_exists($emailConfigPath)) {
        require_once $emailConfigPath;
        
        echo "SMTP Host: " . (defined('SMTP_HOST') ? SMTP_HOST : 'NOT SET') . "\n";
        echo "SMTP Port: " . (defined('SMTP_PORT') ? SMTP_PORT : 'NOT SET') . "\n";
        echo "SMTP Username: " . (defined('SMTP_USERNAME') ? SMTP_USERNAME : 'NOT SET') . "\n";
        echo "SMTP Password: " . (defined('SMTP_PASSWORD') ? (SMTP_PASSWORD === 'your-app-password' ? 'NOT CONFIGURED' : 'CONFIGURED') : 'NOT SET') . "\n";
        echo "From Email: " . (defined('FROM_EMAIL') ? FROM_EMAIL : 'NOT SET') . "\n";
    } else {
        echo "email_config.php not found!\n";
    }
    
    echo "\n✅ Database verification fields check completed!\n";
    echo "\n📧 Next steps:\n";
    echo "1. If any PHPMailer files are missing, they need to be uploaded\n";
    echo "2. If email_config.php is missing, it needs to be created\n";
    echo "3. Visit /test_email to test the email configuration\n";
    echo "4. Visit /home to test the registration flow\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
