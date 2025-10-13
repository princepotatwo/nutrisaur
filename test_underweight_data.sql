-- Test data for underweight adult with BMI for age
-- This will create screening history for an underweight adult user

INSERT INTO screening_history (user_email, screening_date, weight, height, bmi, age_months, sex, classification_type, classification, z_score, nutritional_risk) VALUES

-- Underweight adult male - 25 years old (300 months)
('underweight.girl@example.com', '2024-01-15 10:00:00', 45.0, 165.0, 16.5, 300, 'Female', 'bmi-for-age', 'Underweight', -1.8, 'High'),
('underweight.girl@example.com', '2024-02-15 10:00:00', 44.5, 165.0, 16.3, 301, 'Female', 'bmi-for-age', 'Underweight', -1.9, 'High'),
('underweight.girl@example.com', '2024-03-15 10:00:00', 44.0, 165.0, 16.2, 302, 'Female', 'bmi-for-age', 'Underweight', -2.0, 'High'),
('underweight.girl@example.com', '2024-04-15 10:00:00', 46.0, 165.0, 16.9, 303, 'Female', 'bmi-for-age', 'Underweight', -1.5, 'High'),
('underweight.girl@example.com', '2024-05-15 10:00:00', 47.5, 165.0, 17.4, 304, 'Female', 'bmi-for-age', 'Underweight', -1.2, 'Medium'),

-- Underweight adult male - 30 years old (360 months)  
('severe.boy@example.com', '2024-01-20 10:00:00', 50.0, 175.0, 16.3, 360, 'Male', 'bmi-for-age', 'Underweight', -2.2, 'High'),
('severe.boy@example.com', '2024-02-20 10:00:00', 49.5, 175.0, 16.2, 361, 'Male', 'bmi-for-age', 'Underweight', -2.3, 'High'),
('severe.boy@example.com', '2024-03-20 10:00:00', 51.0, 175.0, 16.7, 362, 'Male', 'bmi-for-age', 'Underweight', -1.9, 'High'),
('severe.boy@example.com', '2024-04-20 10:00:00', 52.5, 175.0, 17.1, 363, 'Male', 'bmi-for-age', 'Underweight', -1.6, 'High'),
('severe.boy@example.com', '2024-05-20 10:00:00', 54.0, 175.0, 17.6, 364, 'Male', 'bmi-for-age', 'Underweight', -1.3, 'Medium'),

-- Normal weight adult for comparison - 28 years old (336 months)
('normal.boy@example.com', '2024-01-25 10:00:00', 70.0, 175.0, 22.9, 336, 'Male', 'bmi-for-age', 'Normal', 0.2, 'Low'),
('normal.boy@example.com', '2024-02-25 10:00:00', 71.0, 175.0, 23.2, 337, 'Male', 'bmi-for-age', 'Normal', 0.4, 'Low'),
('normal.boy@example.com', '2024-03-25 10:00:00', 72.0, 175.0, 23.5, 338, 'Male', 'bmi-for-age', 'Normal', 0.6, 'Low'),
('normal.boy@example.com', '2024-04-25 10:00:00', 71.5, 175.0, 23.3, 339, 'Male', 'bmi-for-age', 'Normal', 0.5, 'Low'),
('normal.boy@example.com', '2024-05-25 10:00:00', 73.0, 175.0, 23.8, 340, 'Male', 'bmi-for-age', 'Normal', 0.8, 'Low');

-- Add some weight-for-age data for children (under 71 months)
INSERT INTO screening_history (user_email, screening_date, weight, height, bmi, age_months, sex, classification_type, classification, z_score, nutritional_risk) VALUES

-- Underweight child - 5 years old (60 months)
('birth.normal@example.com', '2024-01-10 10:00:00', 15.0, 110.0, 12.4, 60, 'Male', 'weight-for-age', 'Underweight', -1.8, 'High'),
('birth.normal@example.com', '2024-02-10 10:00:00', 15.5, 111.0, 12.6, 61, 'Male', 'weight-for-age', 'Underweight', -1.6, 'High'),
('birth.normal@example.com', '2024-03-10 10:00:00', 16.0, 112.0, 12.7, 62, 'Male', 'weight-for-age', 'Underweight', -1.4, 'High'),
('birth.normal@example.com', '2024-04-10 10:00:00', 16.5, 113.0, 12.9, 63, 'Male', 'weight-for-age', 'Underweight', -1.2, 'Medium'),
('birth.normal@example.com', '2024-05-10 10:00:00', 17.0, 114.0, 13.1, 64, 'Male', 'weight-for-age', 'Underweight', -1.0, 'Medium'),

-- Normal weight child for comparison - 4 years old (48 months)
('birth.severe@example.com', '2024-01-05 10:00:00', 18.0, 105.0, 16.3, 48, 'Female', 'weight-for-age', 'Normal', 0.3, 'Low'),
('birth.severe@example.com', '2024-02-05 10:00:00', 18.5, 106.0, 16.5, 49, 'Female', 'weight-for-age', 'Normal', 0.5, 'Low'),
('birth.severe@example.com', '2024-03-05 10:00:00', 19.0, 107.0, 16.6, 50, 'Female', 'weight-for-age', 'Normal', 0.7, 'Low'),
('birth.severe@example.com', '2024-04-05 10:00:00', 19.5, 108.0, 16.7, 51, 'Female', 'weight-for-age', 'Normal', 0.9, 'Low'),
('birth.severe@example.com', '2024-05-05 10:00:00', 20.0, 109.0, 16.8, 52, 'Female', 'weight-for-age', 'Normal', 1.1, 'Low');
