-- Check the actual table structure to see what fields are required
-- This will help us identify which fields are NOT NULL without defaults

DESCRIBE community_users;

-- Alternative query to check column constraints
SELECT 
    COLUMN_NAME,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    DATA_TYPE,
    COLUMN_KEY
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'community_users' 
AND TABLE_SCHEMA = DATABASE()
ORDER BY ORDINAL_POSITION;
