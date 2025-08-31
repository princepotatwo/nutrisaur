# FCM Debug Guide - Nutrisaur

## Overview
This guide helps diagnose and fix Firebase Cloud Messaging (FCM) notification issues in the Nutrisaur application.

## Recent Improvements Made

### 1. Enhanced Error Handling
- Fixed the `[null]` error logs issue by properly checking `error_get_last()` return values
- Added detailed error information with structured data
- Improved error categorization and logging

### 2. Better Debugging Tools
- **Enhanced FCM Test Dashboard** (`sss/test_fcm.php`)
  - Added diagnostic section with detailed system information
  - Improved error reporting with structured data
  - Better environment variable validation

- **Command-line Debug Script** (`sss/debug_fcm.php`)
  - Run from terminal: `php debug_fcm.php`
  - Comprehensive system health check
  - FCM functionality testing

- **Health Check Endpoint** (`public/health`)
  - Railway health check with FCM status
  - Environment variable validation
  - Service status monitoring

## Common FCM Issues and Solutions

### Issue 1: "Failed to send FCM notification - check error logs for details. Error logs: [null]"
**Cause**: The `error_get_last()` function was returning `null` and being included in error logs.

**Solution**: ✅ **Fixed** - Added proper null checking and enhanced error reporting.

### Issue 2: Missing Firebase Credentials
**Symptoms**: FCM notifications fail with credential-related errors.

**Solutions**:
1. **Check Environment Variables**:
   ```bash
   # Run the debug script
   php sss/debug_fcm.php
   
   # Or check the health endpoint
   curl https://your-domain.railway.app/health
   ```

2. **Verify Railway Environment Variables**:
   - `FIREBASE_PROJECT_ID`
   - `FIREBASE_CLIENT_EMAIL`
   - `FIREBASE_PRIVATE_KEY_ID`
   - `FIREBASE_CLIENT_ID`
   - `FIREBASE_PRIVATE_KEY`

3. **Check Firebase Admin SDK File**:
   - Look for `*firebase*admin*.json` files in:
     - `sss/` directory
     - `public/api/` directory
     - `/var/www/html/sss/`
     - `/var/www/html/public/api/`

### Issue 3: Access Token Generation Failure
**Symptoms**: FCM fails during JWT token generation.

**Solutions**:
1. **Verify Private Key Format**:
   - Ensure private key has proper PEM format
   - Check for `-----BEGIN PRIVATE KEY-----` markers
   - Verify no extra whitespace or encoding issues

2. **Check OpenSSL Extension**:
   ```bash
   php -m | grep openssl
   ```

### Issue 4: Database Connection Issues
**Symptoms**: FCM fails due to database connectivity problems.

**Solutions**:
1. **Check Database Connection**:
   ```bash
   php sss/debug_fcm.php
   ```

2. **Verify FCM Tokens Table**:
   - Ensure `fcm_tokens` table exists
   - Check for active tokens
   - Verify table structure

## Debugging Steps

### Step 1: Run Basic Diagnostics
```bash
# From the project root
php sss/debug_fcm.php
```

### Step 2: Check Web Dashboard
1. Navigate to `sss/test_fcm.php`
2. Click "Run Diagnostic" for detailed system information
3. Click "Check Status" for FCM system status
4. Click "Send Test Notification" to test FCM functionality

### Step 3: Check Health Endpoint
```bash
curl https://your-domain.railway.app/health
```

### Step 4: Review Error Logs
Check the application error logs for detailed FCM error information:
- Look for structured error details
- Check error categorization
- Review environment variable status

## Environment Variable Setup

### Railway Environment Variables
Ensure these are set in your Railway project:

```bash
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_CLIENT_EMAIL=your-service-account@your-project.iam.gserviceaccount.com
FIREBASE_PRIVATE_KEY_ID=your-private-key-id
FIREBASE_CLIENT_ID=your-client-id
FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\nYour-Private-Key-Content\n-----END PRIVATE KEY-----"
```

### Private Key Format
The private key should be properly formatted with newlines:
```bash
# Correct format
FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC...\n-----END PRIVATE KEY-----"
```

## Testing FCM

### Test from Dashboard
1. Go to `sss/test_fcm.php`
2. Click "Send Test Notification"
3. Check results and any error messages

### Test from Command Line
```bash
php sss/debug_fcm.php
```

### Test from API
```bash
curl -X POST https://your-domain.railway.app/sss/test_fcm.php \
  -d "action=test_fcm"
```

## Monitoring and Logs

### Error Log Locations
- Application error logs (check your hosting environment)
- Railway logs (via Railway dashboard)
- Browser console (for web dashboard errors)

### Key Log Messages to Look For
- `FCM notification failed - sendFCMNotification returned false`
- `Firebase credentials not found in any location`
- `Failed to generate access token`
- `Invalid Firebase credentials`

## Troubleshooting Checklist

- [ ] Environment variables are set correctly
- [ ] Firebase Admin SDK file exists (if using file-based auth)
- [ ] Database connection is working
- [ ] FCM tokens table exists and has data
- [ ] PHP extensions (curl, openssl, json) are loaded
- [ ] Private key format is correct
- [ ] Firebase project ID is valid
- [ ] Service account has proper permissions

## Support

If issues persist after following this guide:
1. Run the debug script and share the output
2. Check the health endpoint status
3. Review error logs for specific error messages
4. Verify Firebase project configuration
5. Check Railway environment variable settings

## Recent Changes
- **Fixed**: `[null]` error logs issue
- **Added**: Comprehensive diagnostic tools
- **Enhanced**: Error reporting and categorization
- **Improved**: Environment variable validation
- **Added**: Command-line debugging capabilities
