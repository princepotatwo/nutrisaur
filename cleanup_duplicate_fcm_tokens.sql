-- Cleanup Duplicate FCM Tokens SQL Script
-- This script removes duplicate FCM tokens, keeping only the latest one per user

-- First, let's see how many duplicate tokens we have
SELECT 
    email, 
    COUNT(*) as token_count 
FROM community_users 
WHERE fcm_token IS NOT NULL AND fcm_token != ''
GROUP BY email 
HAVING COUNT(*) > 1;

-- Clean up duplicates by keeping only the latest token per user
-- This will delete older duplicate tokens
DELETE c1 FROM community_users c1
INNER JOIN community_users c2 
WHERE c1.email = c2.email 
AND c1.fcm_token IS NOT NULL 
AND c1.fcm_token != ''
AND c2.fcm_token IS NOT NULL 
AND c2.fcm_token != ''
AND (c1.updated_at < c2.updated_at OR (c1.updated_at = c2.updated_at AND c1.community_user_id < c2.community_user_id));

-- Verify the cleanup - this should return 0 rows
SELECT 
    email, 
    COUNT(*) as token_count 
FROM community_users 
WHERE fcm_token IS NOT NULL AND fcm_token != ''
GROUP BY email 
HAVING COUNT(*) > 1;

-- Show final token count
SELECT 
    COUNT(*) as total_active_tokens 
FROM community_users 
WHERE fcm_token IS NOT NULL AND fcm_token != '';

-- Show users with their latest FCM tokens
SELECT 
    email,
    barangay,
    fcm_token,
    updated_at
FROM community_users 
WHERE fcm_token IS NOT NULL AND fcm_token != ''
ORDER BY email, updated_at DESC;
