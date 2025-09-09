-- Add FCM token column to community_users table
-- This script adds the fcm_token column to support push notifications

ALTER TABLE `community_users` 
ADD COLUMN `fcm_token` TEXT NULL COMMENT 'Firebase Cloud Messaging token for push notifications' AFTER `notes`,
ADD COLUMN `device_name` VARCHAR(100) NULL COMMENT 'Device name for FCM token' AFTER `fcm_token`,
ADD COLUMN `app_version` VARCHAR(20) DEFAULT '1.0' COMMENT 'App version when token was registered' AFTER `device_name`,
ADD COLUMN `platform` VARCHAR(20) DEFAULT 'android' COMMENT 'Platform (android/ios/web)' AFTER `app_version`,
ADD COLUMN `fcm_updated_at` TIMESTAMP NULL COMMENT 'When FCM token was last updated' AFTER `platform`;

-- Add index for FCM token lookups
CREATE INDEX `idx_community_users_fcm_token` ON `community_users` (`fcm_token`(100));
CREATE INDEX `idx_community_users_fcm_active` ON `community_users` (`fcm_token`, `status`) WHERE `fcm_token` IS NOT NULL AND `fcm_token` != '';

-- Add unique constraint to prevent duplicate FCM tokens
ALTER TABLE `community_users` 
ADD UNIQUE KEY `unique_fcm_token` (`fcm_token`) USING BTREE;
