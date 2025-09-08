-- Community Users Table for Nutritional Screening Data
-- This table stores community members who have completed nutritional screening

CREATE TABLE `community_users` (
  `community_user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT 'Link to main users table if registered',
  `screening_id` varchar(50) NOT NULL COMMENT 'Unique screening session ID',
  `municipality` varchar(100) NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `sex` enum('Male','Female','Other') NOT NULL,
  `birthday` date NOT NULL COMMENT 'Date of birth for age calculation',
  `age` int(3) NOT NULL COMMENT 'Calculated age at time of screening',
  `is_pregnant` tinyint(1) DEFAULT NULL COMMENT 'NULL if not applicable, 0 for no, 1 for yes',
  `weight_kg` decimal(5,2) NOT NULL COMMENT 'Weight in kilograms',
  `height_cm` decimal(5,2) NOT NULL COMMENT 'Height in centimeters',
  `muac_cm` decimal(5,2) NOT NULL COMMENT 'Mid-Upper Arm Circumference in cm',
  `bmi` decimal(4,1) DEFAULT NULL COMMENT 'Calculated BMI (weight/height²)',
  `bmi_category` varchar(20) DEFAULT NULL COMMENT 'Underweight, Normal, Overweight, Obese',
  `muac_category` varchar(20) DEFAULT NULL COMMENT 'Normal, Moderate, Severe malnutrition',
  `nutritional_risk` varchar(20) DEFAULT NULL COMMENT 'Low, Moderate, High, Severe',
  `screening_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `screened_by` varchar(100) DEFAULT NULL COMMENT 'Name of health worker who conducted screening',
  `notes` text DEFAULT NULL COMMENT 'Additional notes or observations',
  `follow_up_required` tinyint(1) DEFAULT 0 COMMENT 'Whether follow-up is needed',
  `follow_up_date` date DEFAULT NULL COMMENT 'Scheduled follow-up date',
  `status` enum('active','inactive','deleted') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`community_user_id`),
  UNIQUE KEY `screening_id` (`screening_id`),
  KEY `user_id` (`user_id`),
  KEY `municipality` (`municipality`),
  KEY `barangay` (`barangay`),
  KEY `screening_date` (`screening_date`),
  KEY `nutritional_risk` (`nutritional_risk`),
  KEY `status` (`status`),
  CONSTRAINT `community_users_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Community nutritional screening data';

-- Indexes for better performance
CREATE INDEX `idx_municipality_barangay` ON `community_users` (`municipality`, `barangay`);
CREATE INDEX `idx_age_sex` ON `community_users` (`age`, `sex`);
CREATE INDEX `idx_nutritional_risk_date` ON `community_users` (`nutritional_risk`, `screening_date`);

-- Sample data for testing (optional)
INSERT INTO `community_users` (
  `screening_id`, `municipality`, `barangay`, `sex`, `birthday`, `age`, 
  `is_pregnant`, `weight_kg`, `height_cm`, `muac_cm`, `bmi`, `bmi_category`, 
  `muac_category`, `nutritional_risk`, `screened_by`, `notes`
) VALUES 
(
  'SCR-2025-001', 'CITY OF BALANGA', 'Central', 'Female', '1990-05-15', 34,
  NULL, 55.5, 160.0, 24.5, 21.7, 'Normal', 'Normal', 'Low',
  'Health Worker 1', 'Regular screening - no concerns'
),
(
  'SCR-2025-002', 'DINALUPIHAN', 'Bonifacio (Pob.)', 'Male', '1985-12-03', 39,
  NULL, 70.2, 175.0, 26.8, 22.9, 'Normal', 'Normal', 'Low',
  'Health Worker 2', 'Good nutritional status'
);
