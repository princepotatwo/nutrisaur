-- Test with a single user to identify missing required fields
-- This will help us see what columns are actually required

INSERT INTO `community_users` (
  `name`, `email`, `municipality`, `barangay`, `sex`, `birthday`, 
  `is_pregnant`, `weight`, `height`, `screening_date`
) VALUES 
('Test User', 'test@test.com', 'CITY OF BALANGA', 'Central', 'Male', '2024-01-01', NULL, 10.0, 80.0, NOW());
