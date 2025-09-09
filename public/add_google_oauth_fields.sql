-- Add Google OAuth fields to users table
-- This script adds the necessary columns to support Google OAuth authentication

-- Add Google OAuth related columns to the users table
ALTER TABLE users 
ADD COLUMN google_id VARCHAR(255) NULL UNIQUE,
ADD COLUMN google_name VARCHAR(255) NULL,
ADD COLUMN google_picture TEXT NULL,
ADD COLUMN google_given_name VARCHAR(100) NULL,
ADD COLUMN google_family_name VARCHAR(100) NULL;

-- Add index for faster Google ID lookups
CREATE INDEX idx_users_google_id ON users(google_id);

-- Add index for email lookups (if not already exists)
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);

-- Update existing users to have NULL values for new Google fields
-- (This is handled automatically by MySQL when adding NULL columns)

-- Optional: Add a comment to document the Google OAuth integration
ALTER TABLE users COMMENT = 'Users table with support for both traditional and Google OAuth authentication';

-- Verify the changes
DESCRIBE users;
