-- Add email verification fields to users table
ALTER TABLE users 
ADD COLUMN email_verified TINYINT(1) DEFAULT 0,
ADD COLUMN verification_code VARCHAR(4) NULL,
ADD COLUMN verification_code_expires TIMESTAMP NULL,
ADD COLUMN verification_sent_at TIMESTAMP NULL;

-- Create index for faster verification lookups
CREATE INDEX idx_verification_code ON users(verification_code);
CREATE INDEX idx_email_verified ON users(email_verified);
