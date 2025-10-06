-- Add password reset fields to community_users table
-- This script adds the necessary columns to support password reset functionality for community users

-- Check and add password reset code column (if not exists)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'community_users' 
     AND column_name = 'password_reset_code' 
     AND table_schema = DATABASE()) > 0,
    'SELECT "password_reset_code column already exists" as message',
    'ALTER TABLE community_users ADD COLUMN password_reset_code VARCHAR(4) DEFAULT NULL COMMENT "4-digit password reset code"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add password reset expiration column (if not exists)
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

-- Add indexes for faster password reset lookups (if not exists)
-- Note: MySQL doesn't support IF NOT EXISTS for CREATE INDEX, so we'll use a different approach

-- Check and create index for password_reset_code
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE table_name = 'community_users' 
     AND index_name = 'idx_community_users_reset_code' 
     AND table_schema = DATABASE()) > 0,
    'SELECT "Index idx_community_users_reset_code already exists" as message',
    'CREATE INDEX idx_community_users_reset_code ON community_users(password_reset_code)'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and create index for password_reset_expires
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

-- Update the table comment to document the password reset integration
ALTER TABLE community_users COMMENT = 'Community users table with support for password reset functionality';

-- Verify the changes
DESCRIBE community_users;

-- Show sample data to verify the new columns
SELECT email, name, password_reset_code, password_reset_expires, screening_date FROM community_users LIMIT 5;
