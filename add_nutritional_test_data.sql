-- Add diverse nutritional assessment test data
-- Clear existing test data first
DELETE FROM community_users WHERE email LIKE '%@test.com';

-- Insert diverse test data to demonstrate all nutritional statuses
INSERT INTO community_users 
(name, email, password, municipality, barangay, sex, birthday, is_pregnant, weight, height, muac, screening_date) 
VALUES 

-- Children with different nutritional statuses
('Child SAM', 'child_sam@test.com', '$2y$10$example_hash', 'CITY OF BALANGA', 'Central', 'Male', '2020-01-15', NULL, '12.0', '95.0', '10.5', NOW()),
('Child MAM', 'child_mam@test.com', '$2y$10$example_hash', 'DINALUPIHAN', 'Bonifacio (Pob.)', 'Female', '2019-06-10', NULL, '14.0', '100.0', '12.0', NOW()),
('Child Normal', 'child_normal@test.com', '$2y$10$example_hash', 'ORANI', 'Centro I (Pob.)', 'Male', '2018-03-20', NULL, '20.0', '110.0', '14.0', NOW()),

-- Pregnant women
('Pregnant At-risk', 'pregnant_atrisk@test.com', '$2y$10$example_hash', 'MARIVELES', 'Poblacion', 'Female', '1995-08-12', 'Yes', '45.0', '160.0', '22.0', NOW()),
('Pregnant Normal', 'pregnant_normal@test.com', '$2y$10$example_hash', 'HERMOSA', 'A. Rivera (Pob.)', 'Female', '1992-11-05', 'Yes', '58.0', '165.0', '26.0', NOW()),

-- Adults with different BMI categories
('Adult Severe Underweight', 'adult_severe@test.com', '$2y$10$example_hash', 'BAGAC', 'Bagumbayan (Pob.)', 'Male', '1988-04-18', NULL, '45.0', '170.0', '20.0', NOW()),
('Adult Moderate Underweight', 'adult_moderate@test.com', '$2y$10$example_hash', 'LIMAY', 'Poblacion', 'Female', '1990-07-22', NULL, '50.0', '170.0', '22.0', NOW()),
('Adult Mild Underweight', 'adult_mild@test.com', '$2y$10$example_hash', 'MORONG', 'Poblacion', 'Male', '1985-12-03', NULL, '55.0', '175.0', '24.0', NOW()),
('Adult Normal', 'adult_normal@test.com', '$2y$10$example_hash', 'ORION', 'Arellano (Pob.)', 'Female', '1987-09-14', NULL, '65.0', '165.0', '25.0', NOW()),
('Adult Overweight', 'adult_overweight@test.com', '$2y$10$example_hash', 'PILAR', 'Poblacion', 'Male', '1983-02-28', NULL, '85.0', '175.0', '28.0', NOW()),
('Adult Obesity Class I', 'adult_obesity1@test.com', '$2y$10$example_hash', 'SAMAL', 'East Calaguiman (Pob.)', 'Female', '1980-06-15', NULL, '95.0', '165.0', '30.0', NOW()),
('Adult Obesity Class II', 'adult_obesity2@test.com', '$2y$10$example_hash', 'ABUCAY', 'Bangkal', 'Male', '1978-10-08', NULL, '110.0', '170.0', '32.0', NOW()),
('Adult Obesity Class III', 'adult_obesity3@test.com', '$2y$10$example_hash', 'CITY OF BALANGA', 'Bagumbayan', 'Female', '1975-03-25', NULL, '130.0', '160.0', '35.0', NOW());
