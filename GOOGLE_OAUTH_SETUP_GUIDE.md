# Google OAuth Setup Guide for NUTRISAUR

This guide will help you set up Google OAuth authentication for the NUTRISAUR web application.

## Prerequisites

- A Google Cloud Platform account
- Access to your NUTRISAUR database
- Admin access to your web server

## Step 1: Google Cloud Console Setup

### 1.1 Create or Select a Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Note down your project ID

### 1.2 Enable Required APIs

1. In the Google Cloud Console, go to "APIs & Services" > "Library"
2. Search for and enable the following APIs:
   - **Google+ API** (for user profile information)
   - **Google OAuth2 API** (for authentication)

### 1.3 Create OAuth 2.0 Credentials

1. Go to "APIs & Services" > "Credentials"
2. Click "Create Credentials" > "OAuth 2.0 Client IDs"
3. If prompted, configure the OAuth consent screen first:
   - Choose "External" user type
   - Fill in the required information:
     - App name: "NUTRISAUR"
     - User support email: your email
     - Developer contact: your email
   - Add your domain to "Authorized domains"
   - Save and continue through the scopes and test users screens

4. For the OAuth client:
   - Application type: "Web application"
   - Name: "NUTRISAUR Web Client"
   - Authorized JavaScript origins: 
     - `http://localhost` (for local development)
     - `https://yourdomain.com` (for production)
   - Authorized redirect URIs:
     - `http://localhost/home.php` (for local development)
     - `https://yourdomain.com/home.php` (for production)

5. Click "Create" and copy the **Client ID** and **Client Secret**

## Step 2: Update Configuration Files

### 2.1 Update Google OAuth Configuration

Edit `/public/google-oauth-config.js`:

```javascript
const GOOGLE_OAUTH_CONFIG = {
    clientId: 'YOUR_ACTUAL_CLIENT_ID.apps.googleusercontent.com',
    redirectUri: window.location.origin + '/home.php',
    scope: 'openid email profile',
    responseType: 'code',
    state: 'google_oauth_state'
};
```

### 2.2 Update PHP Configuration

Edit `/public/home.php` and update the `exchangeGoogleCodeForToken` function:

```php
function exchangeGoogleCodeForToken($code) {
    $clientId = 'YOUR_ACTUAL_CLIENT_ID.apps.googleusercontent.com';
    $clientSecret = 'YOUR_ACTUAL_CLIENT_SECRET';
    // ... rest of the function
}
```

## Step 3: Database Migration

### 3.1 Run the Database Migration

1. Navigate to `/public/setup_google_oauth.php` in your browser
2. Click "Run Migration" to add the required database fields
3. Verify that the migration was successful

### 3.2 Manual Database Update (Alternative)

If you prefer to run the SQL manually:

```sql
-- Add Google OAuth fields to users table
ALTER TABLE users 
ADD COLUMN google_id VARCHAR(255) NULL UNIQUE,
ADD COLUMN google_name VARCHAR(255) NULL,
ADD COLUMN google_picture TEXT NULL,
ADD COLUMN google_given_name VARCHAR(100) NULL,
ADD COLUMN google_family_name VARCHAR(100) NULL;

-- Add indexes for better performance
CREATE INDEX idx_users_google_id ON users(google_id);
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
```

## Step 4: Test the Integration

### 4.1 Test Google OAuth Flow

1. Go to your home page (`/home.php`)
2. Click "Sign in with Google" or "Sign up with Google"
3. Complete the Google OAuth flow
4. Verify that you're logged in successfully
5. Check that your user data is stored in the database

### 4.2 Verify Database Integration

Check that the Google OAuth data is properly stored:

```sql
SELECT user_id, username, email, google_id, google_name, google_picture 
FROM users 
WHERE google_id IS NOT NULL;
```

## Step 5: Production Deployment

### 5.1 Update Production Configuration

1. Update the Google OAuth configuration with your production domain
2. Add your production domain to Google Cloud Console authorized origins
3. Update the redirect URIs in Google Cloud Console

### 5.2 Security Considerations

1. **Never commit credentials to version control**
2. Use environment variables for sensitive data:
   ```php
   $clientId = $_ENV['GOOGLE_CLIENT_ID'] ?? 'YOUR_CLIENT_ID';
   $clientSecret = $_ENV['GOOGLE_CLIENT_SECRET'] ?? 'YOUR_CLIENT_SECRET';
   ```

3. **Enable HTTPS in production**
4. **Regularly rotate your OAuth credentials**

## Troubleshooting

### Common Issues

1. **"Invalid client" error**
   - Check that your Client ID is correct
   - Verify that your domain is in authorized origins

2. **"Redirect URI mismatch" error**
   - Ensure the redirect URI in Google Console matches your actual URL
   - Check for trailing slashes and protocol (http vs https)

3. **"Access blocked" error**
   - Verify that your OAuth consent screen is properly configured
   - Check if your app is in testing mode and add test users

4. **Database errors**
   - Ensure the migration was run successfully
   - Check database permissions
   - Verify table structure

### Debug Mode

Enable debug logging by adding this to your PHP code:

```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
```

## Features

The Google OAuth integration provides:

- **Seamless Login**: Users can sign in with their Google account
- **Account Linking**: Existing users can link their Google account
- **Profile Data**: Automatically retrieves user's name, email, and profile picture
- **Email Verification**: Google-verified emails are automatically marked as verified
- **Fallback Support**: Traditional email/password authentication still works

## Security Features

- **State Parameter**: Prevents CSRF attacks
- **Token Validation**: Verifies tokens with Google's servers
- **Secure Storage**: Google IDs are stored securely in the database
- **Session Management**: Proper session handling for OAuth users

## Support

If you encounter issues:

1. Check the browser console for JavaScript errors
2. Check the server error logs for PHP errors
3. Verify your Google Cloud Console configuration
4. Test with a different Google account
5. Check network connectivity to Google's servers

## Next Steps

After successful setup:

1. Customize the OAuth consent screen with your app's branding
2. Add additional scopes if needed (e.g., for accessing Google Drive)
3. Implement user profile picture display in your dashboard
4. Add Google account unlinking functionality
5. Consider implementing Google Calendar integration for nutrition tracking

---

**Note**: This integration uses Google's OAuth 2.0 flow and follows Google's security best practices. Make sure to keep your credentials secure and regularly review your OAuth settings in the Google Cloud Console.
