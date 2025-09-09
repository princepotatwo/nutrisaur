-- Add 20 diverse users to test comprehensive nutritional assessment system
-- This will demonstrate all nutritional statuses and verify calculations

-- Clear existing test data first
DELETE FROM community_users WHERE email LIKE '%@test.com';

-- Insert 20 diverse test users covering all nutritional assessment categories
INSERT INTO community_users 
(name, email, password, municipality, barangay, sex, birthday, is_pregnant, weight, height, muac, screening_date) 
VALUES 

-- CHILDREN (Age < 18) - Testing WHO Growth Standards
-- Child 1: Severe Acute Malnutrition (SAM) - Very low weight and height
('Child SAM Test', 'child_sam_test@test.com', '$2y$10$example_hash', 'CITY OF BALANGA', 'Central', 'Male', '2020-03-15', NULL, '8.5', '85.0', '9.8', NOW()),

-- Child 2: Moderate Acute Malnutrition (MAM) - Low weight for height
('Child MAM Test', 'child_mam_test@test.com', '$2y$10$example_hash', 'DINALUPIHAN', 'Bonifacio (Pob.)', 'Female', '2019-08-20', NULL, '11.2', '95.0', '11.8', NOW()),

-- Child 3: Mild Acute Malnutrition (Wasting) - Slightly low weight
('Child Wasting Test', 'child_wasting_test@test.com', '$2y$10$example_hash', 'ORANI', 'Centro I (Pob.)', 'Male', '2018-12-10', NULL, '13.5', '105.0', '12.8', NOW()),

-- Child 4: Stunting (Chronic Malnutrition) - Low height for age
('Child Stunting Test', 'child_stunting_test@test.com', '$2y$10$example_hash', 'MARIVELES', 'Poblacion', 'Female', '2017-05-25', NULL, '18.0', '95.0', '13.5', NOW()),

-- Child 5: Normal child
('Child Normal Test', 'child_normal_test@test.com', '$2y$10$example_hash', 'HERMOSA', 'A. Rivera (Pob.)', 'Male', '2016-11-30', NULL, '22.0', '115.0', '14.2', NOW()),

-- PREGNANT WOMEN - Testing MUAC thresholds
-- Pregnant 1: Maternal Undernutrition (At-risk) - Very low MUAC
('Pregnant At-risk Test', 'pregnant_atrisk_test@test.com', '$2y$10$example_hash', 'BAGAC', 'Bagumbayan (Pob.)', 'Female', '1995-07-12', 'Yes', '42.0', '155.0', '21.5', NOW()),

-- Pregnant 2: Maternal At-risk - Moderate MUAC
('Pregnant Moderate Test', 'pregnant_moderate_test@test.com', '$2y$10$example_hash', 'LIMAY', 'Poblacion', 'Female', '1992-04-18', 'Yes', '48.0', '160.0', '24.2', NOW()),

-- Pregnant 3: Normal pregnant woman
('Pregnant Normal Test', 'pregnant_normal_test@test.com', '$2y$10$example_hash', 'MORONG', 'Poblacion', 'Female', '1990-09-05', 'Yes', '58.0', '165.0', '26.8', NOW()),

-- ADULTS (Age ≥ 18) - Testing BMI categories
-- Adult 1: Severe Underweight - BMI < 16.0
('Adult Severe Underweight', 'adult_severe_underweight@test.com', '$2y$10$example_hash', 'ORION', 'Arellano (Pob.)', 'Male', '1988-02-14', NULL, '38.0', '165.0', '18.5', NOW()),

-- Adult 2: Moderate Underweight - BMI 16.0-16.9
('Adult Moderate Underweight', 'adult_moderate_underweight@test.com', '$2y$10$example_hash', 'PILAR', 'Poblacion', 'Female', '1985-11-22', NULL, '42.0', '165.0', '20.0', NOW()),

-- Adult 3: Mild Underweight - BMI 17.0-18.4
('Adult Mild Underweight', 'adult_mild_underweight@test.com', '$2y$10$example_hash', 'SAMAL', 'East Calaguiman (Pob.)', 'Male', '1983-06-08', NULL, '48.0', '170.0', '22.5', NOW()),

-- Adult 4: Normal weight - BMI 18.5-24.9
('Adult Normal Weight', 'adult_normal_weight@test.com', '$2y$10$example_hash', 'ABUCAY', 'Bangkal', 'Female', '1980-12-03', NULL, '62.0', '165.0', '25.0', NOW()),

-- Adult 5: Overweight - BMI 25.0-29.9
('Adult Overweight', 'adult_overweight@test.com', '$2y$10$example_hash', 'CITY OF BALANGA', 'Bagumbayan', 'Male', '1978-08-17', NULL, '85.0', '175.0', '28.5', NOW()),

-- Adult 6: Obesity Class I - BMI 30.0-34.9
('Adult Obesity Class I', 'adult_obesity_class1@test.com', '$2y$10$example_hash', 'DINALUPIHAN', 'Gomez (Pob.)', 'Female', '1975-03-25', NULL, '95.0', '165.0', '31.0', NOW()),

-- Adult 7: Obesity Class II - BMI 35.0-39.9
('Adult Obesity Class II', 'adult_obesity_class2@test.com', '$2y$10$example_hash', 'ORANI', 'Bayan (Pob.)', 'Male', '1972-10-11', NULL, '110.0', '170.0', '33.5', NOW()),

-- Adult 8: Obesity Class III (Severe) - BMI ≥ 40.0
('Adult Obesity Class III', 'adult_obesity_class3@test.com', '$2y$10$example_hash', 'MARIVELES', 'San Carlos', 'Female', '1970-01-28', NULL, '130.0', '160.0', '36.0', NOW()),

-- ADOLESCENTS (Age 13-17) - Testing edge cases
-- Adolescent 1: Underweight adolescent
('Adolescent Underweight', 'adolescent_underweight@test.com', '$2y$10$example_hash', 'HERMOSA', 'Burgos-Soliman (Pob.)', 'Male', '2008-04-15', NULL, '35.0', '150.0', '20.0', NOW()),

-- Adolescent 2: Overweight adolescent
('Adolescent Overweight', 'adolescent_overweight@test.com', '$2y$10$example_hash', 'BAGAC', 'San Antonio', 'Female', '2007-09-20', NULL, '75.0', '160.0', '28.0', NOW()),

-- ELDERLY (Age 65+) - Testing age-specific considerations
-- Elderly 1: Normal elderly
('Elderly Normal', 'elderly_normal@test.com', '$2y$10$example_hash', 'LIMAY', 'Townsite', 'Male', '1955-12-10', NULL, '68.0', '170.0', '26.0', NOW()),

-- Elderly 2: Underweight elderly
('Elderly Underweight', 'elderly_underweight@test.com', '$2y$10$example_hash', 'MORONG', 'Sabang', 'Female', '1952-06-05', NULL, '45.0', '155.0', '21.0', NOW()),

-- EDGE CASES - Testing boundary conditions
-- Edge Case 1: Just turned 18 (adult threshold)
('Edge Case Adult', 'edge_case_adult@test.com', '$2y$10$example_hash', 'ORION', 'Lati (Pob.)', 'Male', '2006-01-01', NULL, '55.0', '175.0', '24.0', NOW()),

-- Edge Case 2: Just under 18 (child threshold)
('Edge Case Child', 'edge_case_child@test.com', '$2y$10$example_hash', 'PILAR', 'Wawa', 'Female', '2006-12-31', NULL, '50.0', '160.0', '23.0', NOW()),

-- Edge Case 3: Very young child (6 months)
('Edge Case Infant', 'edge_case_infant@test.com', '$2y$10$example_hash', 'SAMAL', 'Santa Lucia', 'Male', '2024-06-15', NULL, '6.5', '65.0', '12.0', NOW()),

-- Edge Case 4: Invalid data (to test error handling)
('Edge Case Invalid', 'edge_case_invalid@test.com', '$2y$10$example_hash', 'ABUCAY', 'Capitangan', 'Male', '1990-01-01', NULL, '0', '0', '0', NOW());

-- Display summary of what we've added
SELECT 
    'Test Data Added Successfully!' as Status,
    COUNT(*) as Total_Users,
    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, birthday, CURDATE()) < 18 THEN 1 ELSE 0 END) as Children,
    SUM(CASE WHEN is_pregnant = 'Yes' THEN 1 ELSE 0 END) as Pregnant_Women,
    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, birthday, CURDATE()) >= 18 AND is_pregnant != 'Yes' THEN 1 ELSE 0 END) as Adults_Elderly
FROM community_users 
WHERE email LIKE '%@test.com';
