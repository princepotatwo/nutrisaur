# NutriSaur Project Deployment Guide

## Overview
This guide explains how to deploy your NutriSaur PHP project to InfinityFree hosting service.

## Changes Made for Deployment

### 1. Centralized Configuration
- ✅ Created `config.php` at the root of htdocs/ with database connection settings
- ✅ Updated all PHP files to use relative paths: `require_once __DIR__ . "/../config.php"`
- ✅ Removed duplicate database connection code from individual files

### 2. Updated Files
- ✅ `config.php` (root) - Centralized configuration
- ✅ `sss/api/config.php` - Now includes root config
- ✅ `sss/api/login.php` - Uses centralized config
- ✅ `sss/home.php` - Uses centralized config
- ✅ `sss/dashboard.php` - Uses centralized config
- ✅ `unified_api_broken.php` - Uses centralized config
- ✅ `sss/settings_verified_mho.php` - Updated fetch calls to use relative paths
- ✅ `sss/settings_fixed.php` - Updated fetch calls to use relative paths

### 3. Android Project Updates
- ✅ `app/src/main/java/com/example/nutrisaur11/Constants.java` - Updated API URLs for production

## Deployment Steps

### Step 1: Prepare Your InfinityFree Account
1. Sign up for a free account at [InfinityFree.net](https://infinityfree.net)
2. Create a new hosting account
3. Note down your:
   - Domain name (e.g., `yourname.infinityfreeapp.com`)
   - Database host
   - Database name
   - Database username
   - Database password

### Step 2: Update Configuration for Production
1. Copy `config_production.php` to `config.php`
2. Edit `config.php` with your InfinityFree credentials:
   ```php
   $host = "your_infinityfree_host"; // e.g., "sql.infinityfree.com"
   $dbname = "your_infinityfree_database_name";
   $dbUsername = "your_infinityfree_username";
   $dbPassword = "your_infinityfree_password";
   $base_url = "https://yourdomain.infinityfreeapp.com/";
   ```

### Step 3: Update Android App Constants
1. In `app/src/main/java/com/example/nutrisaur11/Constants.java`:
   ```java
   public static final String API_BASE_URL = "https://yourdomain.infinityfreeapp.com/";
   ```

### Step 4: Upload Files to InfinityFree
1. Use File Manager in InfinityFree control panel or FTP
2. Upload the entire project structure:
   ```
   public_html/
   ├── config.php
   ├── web/          (your web system)
   └── api/          (your PHP API)
   ```

### Step 5: Create Database
1. In InfinityFree control panel, go to MySQL Databases
2. Create a new database
3. Import your existing database structure and data

### Step 6: Test Your Application
1. Test the web interface at `https://yourdomain.infinityfreeapp.com/web/`
2. Test the API at `https://yourdomain.infinityfreeapp.com/api/`
3. Test your Android app with the new API URLs

## File Structure After Deployment
```
public_html/
├── config.php                    # Centralized configuration
├── web/                          # Your web system
│   ├── index.php
│   ├── home.php
│   ├── dashboard.php
│   └── ...
└── api/                          # Your PHP API
    ├── config.php                # Includes root config
    ├── login.php
    ├── register.php
    └── ...
```

## Important Notes

### 1. HTTPS Required
- InfinityFree provides free SSL certificates
- All API calls must use `https://` not `http://`
- Update Android app URLs accordingly

### 2. Database Limitations
- InfinityFree has database size and connection limits
- Monitor your usage to stay within free tier limits

### 3. File Permissions
- Ensure PHP files have 644 permissions
- Ensure directories have 755 permissions

### 4. Error Handling
- Production config includes proper error logging
- Database connection errors are logged, not displayed to users

## Troubleshooting

### Common Issues
1. **Database Connection Failed**
   - Verify database credentials in `config.php`
   - Check if database exists in InfinityFree

2. **404 Errors**
   - Ensure all files are uploaded to correct locations
   - Check file permissions

3. **API Not Working**
   - Verify API URLs in Android app
   - Check browser console for JavaScript errors

### Support
- InfinityFree Support: [support.infinityfree.net](https://support.infinityfree.net)
- Check error logs in InfinityFree control panel

## Rollback Plan
If deployment fails:
1. Keep your local XAMPP setup as backup
2. Revert `config.php` to localhost settings
3. Revert Android app Constants.java to local IP
4. Test locally before attempting deployment again

## Security Considerations
- ✅ Database credentials are centralized and not hardcoded
- ✅ Relative paths prevent directory traversal attacks
- ✅ Error messages don't expose sensitive information
- ⚠️ Remember to update passwords for production
- ⚠️ Consider implementing rate limiting for API endpoints
