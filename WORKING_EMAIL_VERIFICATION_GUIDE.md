# Working Email Verification System Guide

## Overview
This guide explains how to use the working Node.js email verification system that has been tested and confirmed to work.

## What's Working

### 1. Node.js Email Service
- **File**: `email-service-simple.js`
- **Status**: ✅ Working and tested
- **Email Provider**: Gmail with App Password
- **Configuration**: Already set up with working credentials

### 2. Registration API
- **File**: `public/api/register_nodejs.php`
- **Status**: ✅ Ready to use
- **Features**: 
  - Creates user account
  - Generates 4-digit verification code
  - Sends email using Node.js service
  - Stores verification data in database

### 3. Verification API
- **File**: `public/api/verify_nodejs.php`
- **Status**: ✅ Ready to use
- **Features**:
  - Validates 4-digit verification code
  - Marks email as verified
  - Sends welcome email
  - Handles expired codes

## How to Use

### Step 1: Test Email Service
```bash
# Test the email service directly
node email-service-simple.js
```
This should send a test email to `kevinpingol123@gmail.com` and show "SUCCESS".

### Step 2: Register a New User
**Endpoint**: `POST /api/register_nodejs.php`

**Request Body**:
```json
{
    "username": "testuser",
    "email": "user@example.com",
    "password": "password123"
}
```

**Response**:
```json
{
    "success": true,
    "message": "Registration successful! Please check your email for verification code.",
    "data": {
        "user_id": 123,
        "username": "testuser",
        "email": "user@example.com",
        "requires_verification": true,
        "email_sent": true
    }
}
```

### Step 3: Verify Email
**Endpoint**: `POST /api/verify_nodejs.php`

**Request Body**:
```json
{
    "email": "user@example.com",
    "verification_code": "1234"
}
```

**Response**:
```json
{
    "success": true,
    "message": "Email verified successfully! Welcome to Nutrisaur!",
    "data": {
        "user_id": 123,
        "username": "testuser",
        "email": "user@example.com",
        "email_verified": true
    }
}
```

## Database Requirements

Make sure your `users` table has these fields:
```sql
ALTER TABLE users 
ADD COLUMN email_verified TINYINT(1) DEFAULT 0,
ADD COLUMN verification_code VARCHAR(4) NULL,
ADD COLUMN verification_code_expires TIMESTAMP NULL,
ADD COLUMN verification_sent_at TIMESTAMP NULL;
```

## Email Configuration

The email service is configured with:
- **SMTP Host**: smtp.gmail.com
- **Port**: 587
- **Security**: TLS
- **Email**: kevinpingol123@gmail.com
- **App Password**: eoax bdlz bogm ikjk

## Testing

### Test Registration
```bash
php test_registration.php
```

### Test Email Service
```bash
node email-service-simple.js
```

## Troubleshooting

### 1. Email Not Sending
- Check if Node.js is installed: `node --version`
- Check if nodemailer is installed: `npm list nodemailer`
- Verify Gmail App Password is correct
- Check if 2-Step Verification is enabled on Gmail

### 2. Registration Fails
- Check database connection
- Verify all required fields are provided
- Check if username/email already exists

### 3. Verification Fails
- Check if verification code is correct (4 digits)
- Check if code is expired (5 minutes)
- Verify email exists in database

## Integration with Mobile App

### Registration Flow
1. User registers with username, email, password
2. App calls `/api/register_nodejs.php`
3. App shows "Check your email" message
4. User enters verification code
5. App calls `/api/verify_nodejs.php`
6. On success, user can login

### Error Handling
- Handle `requires_verification` flag
- Show appropriate error messages
- Implement resend verification functionality

## Security Features

- 4-digit verification codes
- 5-minute expiration time
- Secure password hashing
- Email validation
- Rate limiting (can be added)

## Next Steps

1. Update your mobile app to use these new endpoints
2. Test the complete registration flow
3. Implement resend verification functionality
4. Add rate limiting for security
5. Monitor email delivery rates

## Support

If you encounter issues:
1. Check the error logs
2. Test the email service directly
3. Verify database configuration
4. Check network connectivity
5. Contact support with specific error messages
