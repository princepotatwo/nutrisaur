W-- Create screening_assessments table for comprehensive nutrition screening
CREATE TABLE IF NOT EXISTS `screening_assessments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `municipality` varchar(255) NOT NULL,
  `barangay` varchar(255) NOT NULL,
  `age` int(11) NOT NULL,
  `age_months` int(11) DEFAULT NULL,
  `sex` varchar(10) NOT NULL,
  `pregnant` varchar(20) DEFAULT NULL,
  `weight` decimal(5,2) NOT NULL,
  `height` decimal(5,2) NOT NULL,
  `bmi` decimal(4,2) DEFAULT NULL,
  `meal_recall` text DEFAULT NULL,
  `family_history` json DEFAULT NULL,
  `lifestyle` varchar(50) NOT NULL,
  `lifestyle_other` varchar(255) DEFAULT NULL,
  `immunization` json DEFAULT NULL,
  `risk_score` int(11) DEFAULT 0,
  `assessment_summary` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_municipality` (`municipality`),
  KEY `idx_barangay` (`barangay`),
  KEY `idx_age` (`age`),
  KEY `idx_sex` (`sex`),
  KEY `idx_bmi` (`bmi`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_risk_score` (`risk_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add foreign key constraint if users table exists
-- ALTER TABLE `screening_assessments` ADD CONSTRAINT `fk_screening_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- Create index for better performance on common queries
CREATE INDEX `idx_screening_user_date` ON `screening_assessments` (`user_id`, `created_at`);
CREATE INDEX `idx_screening_location` ON `screening_assessments` (`municipality`, `barangay`);
CREATE INDEX `idx_screening_demographics` ON `screening_assessments` (`age`, `sex`, `bmi`);

-- Add comments for documentation
ALTER TABLE `screening_assessments` 
COMMENT = 'Comprehensive nutrition screening assessments with all required fields from DOH guidelines';

-- Sample data for testing (optional)
-- INSERT INTO `screening_assessments` (
--   `user_id`, `municipality`, `barangay`, `age`, `age_months`, `sex`, `pregnant`, 
--   `weight`, `height`, `bmi`, `meal_recall`, `family_history`, `lifestyle`, 
--   `lifestyle_other`, `immunization`, `risk_score`, `assessment_summary`, `recommendations`
-- ) VALUES (
--   1, 'CITY OF BALANGA (Capital)', 'Poblacion', 25, NULL, 'Female', 'No',
--   55.5, 160.0, 21.68, 'Breakfast: rice, egg, coffee. Lunch: chicken, vegetables, rice. Dinner: fish, soup.',
--   '["Diabetes", "Hypertension"]', 'Active', NULL, NULL, 15,
--   'Normal BMI with balanced diet. Risk factors: family history of diabetes and hypertension.',
--   'Continue balanced diet. Monitor blood sugar and blood pressure regularly. Regular exercise recommended.'
-- );
