-- Add missing password reset expiration column to community_users table
-- This script only adds the password_reset_expires column if it doesn't exist

-- Check if password_reset_expires column exists, if not add it
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'community_users' 
     AND column_name = 'password_reset_expires' 
     AND table_schema = DATABASE()) > 0,
    'SELECT "password_reset_expires column already exists" as message',
    'ALTER TABLE community_users ADD COLUMN password_reset_expires DATETIME DEFAULT NULL COMMENT "Password reset code expiration time"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index for faster lookups (if not exists)
-- Note: MySQL doesn't support IF NOT EXISTS for CREATE INDEX, so we'll use a different approach
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE table_name = 'community_users' 
     AND index_name = 'idx_community_users_reset_expires' 
     AND table_schema = DATABASE()) > 0,
    'SELECT "Index idx_community_users_reset_expires already exists" as message',
    'CREATE INDEX idx_community_users_reset_expires ON community_users(password_reset_expires)'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verify the changes
DESCRIBE community_users;

-- Show sample data to verify the columns
SELECT email, name, password_reset_code, password_reset_expires, screening_date FROM community_users LIMIT 5;
