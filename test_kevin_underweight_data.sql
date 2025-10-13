-- Test data for Kevin (kevinpingol123@gmail.com) - Underweight adult with BMI for age
-- This will create screening history showing underweight progression

INSERT INTO screening_history (user_email, screening_date, weight, height, bmi, age_months, sex, classification_type, classification, z_score, nutritional_risk) VALUES

-- Kevin - Underweight adult male progression over 6 months
('kevinpingol123@gmail.com', '2024-01-15 10:00:00', 55.0, 175.0, 18.0, 300, 'Male', 'bmi-for-age', 'Underweight', -1.5, 'High'),
('kevinpingol123@gmail.com', '2024-02-15 10:00:00', 54.5, 175.0, 17.8, 301, 'Male', 'bmi-for-age', 'Underweight', -1.7, 'High'),
('kevinpingol123@gmail.com', '2024-03-15 10:00:00', 56.0, 175.0, 18.3, 302, 'Male', 'bmi-for-age', 'Underweight', -1.3, 'High'),
('kevinpingol123@gmail.com', '2024-04-15 10:00:00', 57.5, 175.0, 18.8, 303, 'Male', 'bmi-for-age', 'Underweight', -1.0, 'Medium'),
('kevinpingol123@gmail.com', '2024-05-15 10:00:00', 59.0, 175.0, 19.3, 304, 'Male', 'bmi-for-age', 'Underweight', -0.7, 'Medium'),
('kevinpingol123@gmail.com', '2024-06-15 10:00:00', 60.5, 175.0, 19.8, 305, 'Male', 'bmi-for-age', 'Underweight', -0.4, 'Medium'),

-- Add some weight-for-age data for Kevin as well (if he was younger)
('kevinpingol123@gmail.com', '2024-01-15 10:00:00', 55.0, 175.0, 18.0, 300, 'Male', 'weight-for-age', 'Underweight', -1.8, 'High'),
('kevinpingol123@gmail.com', '2024-02-15 10:00:00', 54.5, 175.0, 17.8, 301, 'Male', 'weight-for-age', 'Underweight', -2.0, 'High'),
('kevinpingol123@gmail.com', '2024-03-15 10:00:00', 56.0, 175.0, 18.3, 302, 'Male', 'weight-for-age', 'Underweight', -1.6, 'High'),
('kevinpingol123@gmail.com', '2024-04-15 10:00:00', 57.5, 175.0, 18.8, 303, 'Male', 'weight-for-age', 'Underweight', -1.3, 'Medium'),
('kevinpingol123@gmail.com', '2024-05-15 10:00:00', 59.0, 175.0, 19.3, 304, 'Male', 'weight-for-age', 'Underweight', -1.0, 'Medium'),
('kevinpingol123@gmail.com', '2024-06-15 10:00:00', 60.5, 175.0, 19.8, 305, 'Male', 'weight-for-age', 'Underweight', -0.7, 'Medium'),

-- Add height-for-age data for Kevin
('kevinpingol123@gmail.com', '2024-01-15 10:00:00', 55.0, 175.0, 18.0, 300, 'Male', 'height-for-age', 'Normal', 0.2, 'Low'),
('kevinpingol123@gmail.com', '2024-02-15 10:00:00', 54.5, 175.0, 17.8, 301, 'Male', 'height-for-age', 'Normal', 0.2, 'Low'),
('kevinpingol123@gmail.com', '2024-03-15 10:00:00', 56.0, 175.0, 18.3, 302, 'Male', 'height-for-age', 'Normal', 0.2, 'Low'),
('kevinpingol123@gmail.com', '2024-04-15 10:00:00', 57.5, 175.0, 18.8, 303, 'Male', 'height-for-age', 'Normal', 0.2, 'Low'),
('kevinpingol123@gmail.com', '2024-05-15 10:00:00', 59.0, 175.0, 19.3, 304, 'Male', 'height-for-age', 'Normal', 0.2, 'Low'),
('kevinpingol123@gmail.com', '2024-06-15 10:00:00', 60.5, 175.0, 19.8, 305, 'Male', 'height-for-age', 'Normal', 0.2, 'Low');
