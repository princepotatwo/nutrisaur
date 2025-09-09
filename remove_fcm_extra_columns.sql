-- SQL query to remove FCM token status and updated_at columns
-- This keeps only the basic fcm_token column

-- Remove the fcm_token_status column
ALTER TABLE `community_users` DROP COLUMN `fcm_token_status`;

-- Remove the fcm_token_updated_at column  
ALTER TABLE `community_users` DROP COLUMN `fcm_token_updated_at`;

-- Drop the index for fcm_token_status (run only if the index exists)
-- DROP INDEX `idx_fcm_token_status` ON `community_users`;

-- Show the updated table structure
DESCRIBE `community_users`;
