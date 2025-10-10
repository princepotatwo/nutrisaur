-- Add parent contact information columns to community_users table
-- This script adds the necessary columns to store parent/guardian contact information

ALTER TABLE community_users 
ADD COLUMN parent_name VARCHAR(255) DEFAULT NULL,
ADD COLUMN parent_phone VARCHAR(20) DEFAULT NULL,
ADD COLUMN parent_email VARCHAR(255) DEFAULT NULL;

-- Add indexes for better performance
CREATE INDEX idx_parent_name ON community_users(parent_name);
CREATE INDEX idx_parent_phone ON community_users(parent_phone);
CREATE INDEX idx_parent_email ON community_users(parent_email);
