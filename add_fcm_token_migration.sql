-- Migration script to add FCM token column to community_users table
-- This script adds FCM token support for push notifications

-- Add FCM token column to community_users table
ALTER TABLE `community_users` 
ADD COLUMN `fcm_token` VARCHAR(255) DEFAULT NULL COMMENT 'Firebase Cloud Messaging token for push notifications' AFTER `screening_date`;

-- Add index for FCM token for better performance
CREATE INDEX `idx_fcm_token` ON `community_users` (`fcm_token`);

-- Update the table comment to reflect FCM support
ALTER TABLE `community_users` COMMENT = 'Community nutritional screening data - simplified with FCM support';

-- Show the updated table structure
DESCRIBE `community_users`;
