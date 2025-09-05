# Settings.php Database Fix Summary

## Problem
The `settings.php` file was incorrectly trying to use the `users` table for user data display and management, when it should only use the `user_preferences` table. The `users` table is only for login authentication.

## Root Cause
- Queries were joining `user_preferences` with `users` table unnecessarily
- References to non-existent columns like `first_name`, `last_name`, `municipality` from `users` table
- Wrong foreign key relationships (`user_id` vs `user_email`)

## Solution Applied
**Removed all references to `users` table and made `settings.php` work exclusively with `user_preferences` table.**

### Changes Made:

1. **✅ Removed JOIN with users table:**
   - Changed from: `FROM user_preferences up LEFT JOIN users u ON up.user_email = u.email`
   - Changed to: `FROM user_preferences`

2. **✅ Fixed column references:**
   - Removed references to `u.first_name`, `u.last_name`, `u.municipality` (don't exist in users table)
   - Used only columns that exist in `user_preferences` table:
     - `id`, `user_email`, `username`, `name`, `barangay`, `income`, `risk_score`, `created_at`, `updated_at`

3. **✅ Updated data processing:**
   - Changed from: `'name' => trim($pref['first_name'] . ' ' . $pref['last_name'])`
   - Changed to: `'name' => $pref['name'] ?? 'N/A'`

4. **✅ Fixed DELETE query:**
   - Removed unnecessary JOIN with users table
   - Now directly deletes from `user_preferences` table

5. **✅ Disabled municipality filtering:**
   - Since `municipality` column doesn't exist in `user_preferences` table
   - Added comment for future implementation if needed

## Result
- `settings.php` now works exclusively with `user_preferences` table
- No more column mismatch errors
- Clean separation: `users` table for authentication, `user_preferences` table for user data management
- All queries are now properly aligned with the actual database schema

## Database Schema Alignment
The `user_preferences` table contains all the necessary user data:
- `id` - Primary key
- `user_email` - User identifier (replaces user_id)
- `username` - Username
- `name` - Full name
- `barangay` - Location
- `income` - Income level
- `risk_score` - Risk assessment score
- `created_at`, `updated_at` - Timestamps
