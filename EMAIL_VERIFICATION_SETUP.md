# Email Verification System Setup Guide

This guide will help you set up PHP Mailer with 4-digit verification codes for user registration in your Nutrisaur application.

## What's Been Added

### 1. Database Changes
- Added verification fields to the `users` table:
  - `email_verified` (TINYINT) - Whether email is verified
  - `verification_code` (VARCHAR(4)) - 4-digit verification code
  - `verification_code_expires` (TIMESTAMP) - When code expires
  - `verification_sent_at` (TIMESTAMP) - When code was sent

### 2. New Files Created
- `vendor/phpmailer/phpmailer/` - PHPMailer library
- `email_config.php` - Email configuration settings
- `public/api/EmailService.php` - Email service class
- `public/api/verify_email.php` - Email verification endpoint
- `public/api/resend_verification.php` - Resend verification endpoint
- `public/test_email.php` - Email configuration test page
- `add_verification_fields.sql` - Database migration script

### 3. Modified Files
- `public/api/register.php` - Now sends verification emails
- `public/api/login.php` - Checks for email verification

## Setup Instructions

### Step 1: Update Database
Run the SQL migration to add verification fields:

```sql
-- Add email verification fields to users table
ALTER TABLE users 
ADD COLUMN email_verified TINYINT(1) DEFAULT 0,
ADD COLUMN verification_code VARCHAR(4) NULL,
ADD COLUMN verification_code_expires TIMESTAMP NULL,
ADD COLUMN verification_sent_at TIMESTAMP NULL;

-- Create index for faster verification lookups
CREATE INDEX idx_verification_code ON users(verification_code);
CREATE INDEX idx_email_verified ON users(email_verified);
```

### Step 2: Configure Email Settings
Edit `email_config.php` and update these values:

```php
define('SMTP_USERNAME', 'your-email@gmail.com');  // Your Gmail address
define('SMTP_PASSWORD', 'your-app-password');     // Your Gmail App Password
```

### Step 3: Get Gmail App Password
1. Go to your Google Account settings
2. Enable 2-Step Verification if not already enabled
3. Go to Security → App passwords
4. Generate a new app password for "Mail"
5. Use this password in `email_config.php`

### Step 4: Test Email Configuration
Visit `https://your-domain.com/test_email.php` to test your email setup.

## API Endpoints

### Registration
**POST** `/api/register.php`
```json
{
    "username": "testuser",
    "email": "test@example.com",
    "password": "password123"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Registration successful! Please check your email for verification code.",
    "data": {
        "user_id": 123,
        "username": "testuser",
        "email": "test@example.com",
        "requires_verification": true
    }
}
```

### Email Verification
**POST** `/api/verify_email.php`
```json
{
    "email": "test@example.com",
    "verification_code": "1234"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Email verified successfully! Welcome to Nutrisaur!",
    "data": {
        "user_id": 123,
        "username": "testuser",
        "email": "test@example.com",
        "email_verified": true
    }
}
```

### Resend Verification
**POST** `/api/resend_verification.php`
```json
{
    "email": "test@example.com"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Verification code sent successfully! Please check your email.",
    "data": {
        "user_id": 123,
        "email": "test@example.com"
    }
}
```

### Login (Updated)
**POST** `/api/login.php`
```json
{
    "username": "testuser",
    "password": "password123"
}
```

**If email not verified:**
```json
{
    "success": false,
    "message": "Please verify your email address before logging in. Check your email for the verification code.",
    "requires_verification": true,
    "data": {
        "user_id": 123,
        "email": "test@example.com"
    }
}
```

## Features

### Security Features
- 4-digit verification codes
- 5-minute expiration time
- Rate limiting (1 resend per minute)
- Secure password hashing
- Email validation

### User Experience
- Professional HTML email templates
- Clear error messages
- Welcome email after verification
- Resend functionality
- Mobile-friendly design

### Error Handling
- Invalid verification codes
- Expired codes
- Email sending failures
- Database connection issues
- Rate limiting messages

## Email Templates

### Verification Email
- Professional HTML design
- 4-digit code prominently displayed
- Security warnings
- Expiration information
- Branded with Nutrisaur colors

### Welcome Email
- Sent after successful verification
- Welcome message
- Feature highlights
- Next steps guidance

## Testing

### Test Email Configuration
Visit `/test_email.php` to:
- View current email settings
- Send test emails
- Verify SMTP configuration
- Check for errors

### Manual Testing
1. Register a new user
2. Check email for verification code
3. Verify email with the code
4. Try logging in
5. Test resend functionality

## Troubleshooting

### Common Issues

1. **Email not sending**
   - Check Gmail App Password
   - Verify 2-Step Verification is enabled
   - Check SMTP settings

2. **Verification code not working**
   - Check if code is expired (5 minutes)
   - Verify code format (4 digits)
   - Check database connection

3. **Rate limiting**
   - Wait 60 seconds between resend requests
   - Check verification_sent_at timestamp

### Debug Information
- Check error logs for detailed messages
- Use test_email.php for configuration testing
- Verify database fields are added correctly

## Mobile App Integration

### Registration Flow
1. User registers with username, email, password
2. App shows "Check your email" message
3. User enters verification code
4. App calls verify_email.php
5. On success, user can login

### Login Flow
1. User attempts login
2. If email not verified, show verification screen
3. User enters verification code
4. After verification, user can login normally

### Error Handling
- Handle `requires_verification` flag
- Show appropriate error messages
- Provide resend option
- Graceful fallback for email failures

## Security Considerations

- Verification codes expire after 5 minutes
- Rate limiting prevents abuse
- Codes are cleared after successful verification
- Email addresses are validated
- Passwords are properly hashed
- SQL injection protection with prepared statements

## Performance

- Database indexes for fast lookups
- Efficient email sending with PHPMailer
- Minimal database queries
- Proper error logging
- Graceful degradation

This email verification system provides a secure, user-friendly way to verify user accounts while maintaining good performance and security practices.
