-- Add user account columns to community_users table
-- This allows community_users to function as both user accounts and screening data

ALTER TABLE `community_users` 
ADD COLUMN `name` varchar(100) DEFAULT NULL COMMENT 'User full name' AFTER `community_user_id`,
ADD COLUMN `email` varchar(100) DEFAULT NULL COMMENT 'User email address' AFTER `name`,
ADD COLUMN `password` varchar(255) DEFAULT NULL COMMENT 'Hashed password' AFTER `email`;

-- Add unique constraint on email for user accounts
ALTER TABLE `community_users` 
ADD UNIQUE KEY `unique_email` (`email`);

-- Add index on email for faster lookups
ALTER TABLE `community_users` 
ADD KEY `idx_email` (`email`);

-- Update existing records to have default values for required fields
UPDATE `community_users` 
SET 
    `municipality` = COALESCE(`municipality`, 'Not specified'),
    `barangay` = COALESCE(`barangay`, 'Not specified'),
    `sex` = COALESCE(`sex`, 'Other'),
    `birthday` = COALESCE(`birthday`, '1900-01-01'),
    `age` = COALESCE(`age`, 0),
    `weight_kg` = COALESCE(`weight_kg`, 0.00),
    `height_cm` = COALESCE(`height_cm`, 0.00),
    `muac_cm` = COALESCE(`muac_cm`, 0.00)
WHERE `municipality` IS NULL OR `barangay` IS NULL OR `sex` IS NULL OR `birthday` IS NULL OR `age` IS NULL OR `weight_kg` IS NULL OR `height_cm` IS NULL OR `muac_cm` IS NULL;
