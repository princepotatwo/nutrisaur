-- Simple script to add password reset functionality to community_users table
-- This script will add the missing column and indexes, ignoring errors if they already exist

-- Add password_reset_expires column (ignore error if already exists)
ALTER TABLE community_users 
ADD COLUMN password_reset_expires DATETIME DEFAULT NULL COMMENT 'Password reset code expiration time';

-- Try to create indexes (will fail silently if they already exist)
CREATE INDEX idx_community_users_reset_code ON community_users(password_reset_code);
CREATE INDEX idx_community_users_reset_expires ON community_users(password_reset_expires);

-- Verify the changes
DESCRIBE community_users;

-- Show sample data
SELECT email, name, password_reset_code, password_reset_expires, screening_date FROM community_users LIMIT 5;
