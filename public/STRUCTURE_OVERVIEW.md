# Public Directory Structure Overview

## Main Files (Root Level)
- `home.php` - Login/Registration page
- `dash.php` - Main dashboard
- `logout.php` - Logout functionality
- `index.php` - Main router
- `railway_entry.php` - Railway deployment entry point

## Application Pages
- `settings.php` - User settings
- `AI.php` - AI functionality
- `event.php` - Events management
- `screening.php` - Screening functionality
- `NR.php` - NR page
- `FPM.php` - FPM page

## Configuration & Setup
- `config.php` - Database and app configuration
- `setup-database.php` - Database setup
- `database-status.php` - Database status check
- `railway-database-guide.php` - Railway deployment guide

## Assets
- `optimized_styles.css` - Main stylesheet
- `logo.png` - Application logo
- `google.png` - Google image
- `STYLE/` - Additional style files

## API Directory (`/api/`)
Contains all API endpoints:
- `DatabaseAPI.php` - Main database API class
- `login.php` - Login API
- `register.php` - Registration API
- `check_session.php` - Session validation
- `unified_api.php` - Unified API endpoint
- And many other API files...

## Testing
- `test_login_flow.html` - Login flow test page

## Login Flow
1. User visits `home.php`
2. If not logged in → shows login/register forms
3. If logged in → redirects to `dash.php`
4. After successful login → redirects to `dash.php`
5. If user tries to access `dash.php` without login → redirects to `home.php`
6. Logout → clears session and redirects to `home.php`

## No More Duplication!
- ❌ Removed duplicate `sss/` folder
- ✅ All files are now in their proper locations
- ✅ All paths have been updated correctly
- ✅ Login flow works seamlessly
