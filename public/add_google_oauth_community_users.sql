-- Add Google OAuth field to community_users table
-- This script adds the necessary column to support Google OAuth authentication for community users

-- Add Google OAuth flag to the community_users table
ALTER TABLE community_users 
ADD COLUMN google_oauth TINYINT(1) DEFAULT 0 COMMENT 'Flag to indicate if user signed up via Google OAuth';

-- Add index for faster Google OAuth lookups
CREATE INDEX idx_community_users_google_oauth ON community_users(google_oauth);

-- Add index for email lookups (if not already exists)
CREATE INDEX IF NOT EXISTS idx_community_users_email ON community_users(email);

-- Update the table comment to document the Google OAuth integration
ALTER TABLE community_users COMMENT = 'Community users table with support for both traditional and Google OAuth authentication';

-- Verify the changes
DESCRIBE community_users;

-- Show sample data to verify the new column
SELECT email, name, google_oauth, created_at FROM community_users LIMIT 5;
