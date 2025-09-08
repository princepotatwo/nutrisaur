-- Simplified Community Users Table for Nutritional Screening Data
-- This table stores basic user info and screening answers only

CREATE TABLE `community_users` (
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `municipality` varchar(100) NOT NULL COMMENT 'Answer to question 1',
  `barangay` varchar(100) NOT NULL COMMENT 'Answer to question 2',
  `sex` varchar(20) NOT NULL COMMENT 'Answer to question 3',
  `birthday` varchar(20) NOT NULL COMMENT 'Answer to question 4 (age)',
  `is_pregnant` varchar(10) DEFAULT NULL COMMENT 'Answer to question 5',
  `weight` varchar(20) NOT NULL COMMENT 'Answer to question 6',
  `height` varchar(20) NOT NULL COMMENT 'Answer to question 7',
  `muac` varchar(20) NOT NULL COMMENT 'Answer to question 8',
  `screening_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`email`),
  KEY `municipality` (`municipality`),
  KEY `barangay` (`barangay`),
  KEY `screening_date` (`screening_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Community nutritional screening data - simplified';

-- Sample data for testing (optional)
INSERT INTO `community_users` (
  `name`, `email`, `password`, `municipality`, `barangay`, `sex`, 
  `birthday`, `is_pregnant`, `weight`, `height`, `muac`
) VALUES 
(
  'Juan Dela Cruz', 'juan@example.com', '$2y$10$example_hash',
  'CITY OF BALANGA', 'Central', 'Male', 
  '1990-05-15', NULL, '70.5', '175.0', '26.8'
),
(
  'Maria Santos', 'maria@example.com', '$2y$10$example_hash',
  'DINALUPIHAN', 'Bonifacio (Pob.)', 'Female', 
  '1985-12-03', 'No', '55.2', '160.0', '24.5'
);
