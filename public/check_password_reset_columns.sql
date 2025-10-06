-- Check if password reset columns exist in community_users table
-- This script will show you what columns are available

-- Show all columns in community_users table
DESCRIBE community_users;

-- Check specifically for password reset related columns
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'community_users' 
AND TABLE_SCHEMA = DATABASE()
AND COLUMN_NAME LIKE '%password%' OR COLUMN_NAME LIKE '%reset%'
ORDER BY COLUMN_NAME;

-- Check for indexes on password reset columns
SELECT 
    INDEX_NAME,
    COLUMN_NAME,
    NON_UNIQUE
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_NAME = 'community_users' 
AND TABLE_SCHEMA = DATABASE()
AND (COLUMN_NAME LIKE '%password%' OR COLUMN_NAME LIKE '%reset%')
ORDER BY INDEX_NAME, SEQ_IN_INDEX;

-- Show sample data with password reset columns
SELECT 
    email, 
    name, 
    password_reset_code, 
    password_reset_expires, 
    screening_date 
FROM community_users 
LIMIT 5;
