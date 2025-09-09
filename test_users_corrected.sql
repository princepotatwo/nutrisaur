-- Test Users for WHO Growth Standards - Corrected Format
-- This SQL script creates 40 diverse test users with proper table structure
-- All nutritional data will be calculated by the WHO Growth Standards decision tree

-- Insert 40 diverse test users covering all age groups and nutritional scenarios
INSERT INTO `community_users` (
  `name`, `email`, `municipality`, `barangay`, `sex`, `birthday`, 
  `is_pregnant`, `weight`, `height`, `screening_date`
) VALUES 

-- ========================================
-- NEWBORNS (0-3 months) - 5 users
-- ========================================

-- Test Case 1: Normal newborn boy
('Juan Carlos Santos', 'juan.santos@test.com', 'CITY OF BALANGA', 'Central', 'Male', '2024-12-15', NULL, 3.5, 50.5, NOW()),

-- Test Case 2: Low birth weight girl
('Maria Sofia Reyes', 'maria.reyes@test.com', 'DINALUPIHAN', 'Bonifacio (Pob.)', 'Female', '2024-11-20', NULL, 2.8, 48.0, NOW()),

-- Test Case 3: Large newborn boy
('Miguel Angel Cruz', 'miguel.cruz@test.com', 'HERMOSA', 'Saba', 'Male', '2024-10-10', NULL, 4.2, 52.0, NOW()),

-- Test Case 4: Normal newborn girl
('Isabella Grace Lopez', 'isabella.lopez@test.com', 'ORANI', 'Poblacion', 'Female', '2024-09-05', NULL, 3.2, 49.5, NOW()),

-- Test Case 5: Very low birth weight boy
('Diego Emmanuel Torres', 'diego.torres@test.com', 'CITY OF BALANGA', 'Tortugas', 'Male', '2024-08-15', NULL, 2.1, 45.0, NOW()),

-- ========================================
-- INFANTS (4-12 months) - 8 users
-- ========================================

-- Test Case 6: Normal 6-month-old boy
('TEST-006', 'DINALUPIHAN', 'Paco', 'Male', '2024-06-15', 6, NULL, 7.8, 68.0, 15.2, NULL, NULL, 'Normal', 'Low', 'Health Worker 2', 'Normal 6-month-old boy', 0, 'active'),

-- Test Case 7: Underweight 4-month-old girl
('TEST-007', 'HERMOSA', 'Mabuco', 'Female', '2024-08-15', 4, NULL, 5.2, 62.0, 13.5, NULL, NULL, 'Moderate', 'Moderate', 'Health Worker 3', 'Underweight 4-month-old girl', 1, 'active'),

-- Test Case 8: Overweight 8-month-old boy
('TEST-008', 'ORANI', 'Tala', 'Male', '2024-04-15', 8, NULL, 10.5, 72.0, 16.8, NULL, NULL, 'Moderate', 'Moderate', 'Health Worker 4', 'Overweight 8-month-old boy', 1, 'active'),

-- Test Case 9: Normal 10-month-old girl
('TEST-009', 'CITY OF BALANGA', 'Tenejero', 'Female', '2024-02-15', 10, NULL, 8.9, 74.0, 15.8, NULL, NULL, 'Normal', 'Low', 'Health Worker 1', 'Normal 10-month-old girl', 0, 'active'),

-- Test Case 10: Severely underweight 5-month-old boy
('TEST-010', 'DINALUPIHAN', 'Roosevelt', 'Male', '2024-07-15', 5, NULL, 4.8, 65.0, 12.8, NULL, NULL, 'Severe', 'Severe', 'Health Worker 2', 'Severely underweight 5-month-old boy', 1, 'active'),

-- Test Case 11: Normal 12-month-old girl
('TEST-011', 'HERMOSA', 'Mandama', 'Female', '2023-12-15', 12, NULL, 9.2, 76.0, 16.2, NULL, NULL, 'Normal', 'Low', 'Health Worker 3', 'Normal 12-month-old girl', 0, 'active'),

-- Test Case 12: Tall 9-month-old boy
('TEST-012', 'ORANI', 'Paule 1', 'Male', '2024-03-15', 9, NULL, 9.8, 78.0, 16.5, NULL, NULL, 'Normal', 'Low', 'Health Worker 4', 'Tall 9-month-old boy', 0, 'active'),

-- Test Case 13: Short 7-month-old girl
('TEST-013', 'CITY OF BALANGA', 'Central', 'Female', '2024-05-15', 7, NULL, 7.2, 69.0, 14.8, NULL, NULL, 'Moderate', 'Moderate', 'Health Worker 1', 'Short 7-month-old girl', 1, 'active'),

-- ========================================
-- TODDLERS (13-24 months) - 8 users
-- ========================================

-- Test Case 14: Normal 18-month-old boy
('TEST-014', 'DINALUPIHAN', 'Bonifacio (Pob.)', 'Male', '2023-06-15', 18, NULL, 11.5, 84.0, 16.8, NULL, NULL, 'Normal', 'Low', 'Health Worker 2', 'Normal 18-month-old boy', 0, 'active'),

-- Test Case 15: Stunted 24-month-old girl
('TEST-015', 'HERMOSA', 'Saba', 'Female', '2022-12-15', 24, NULL, 10.2, 80.0, 15.2, NULL, NULL, 'Moderate', 'Moderate', 'Health Worker 3', 'Stunted 24-month-old girl', 1, 'active'),

-- Test Case 16: Wasted 20-month-old boy
('TEST-016', 'ORANI', 'Poblacion', 'Male', '2023-04-15', 20, NULL, 9.8, 86.0, 14.8, NULL, NULL, 'Severe', 'Severe', 'Health Worker 4', 'Wasted 20-month-old boy', 1, 'active'),

-- Test Case 17: Normal 15-month-old girl
('TEST-017', 'CITY OF BALANGA', 'Tortugas', 'Female', '2023-09-15', 15, NULL, 10.8, 82.0, 16.2, NULL, NULL, 'Normal', 'Low', 'Health Worker 1', 'Normal 15-month-old girl', 0, 'active'),

-- Test Case 18: Overweight 22-month-old boy
('TEST-018', 'DINALUPIHAN', 'Paco', 'Male', '2023-02-15', 22, NULL, 13.8, 88.0, 18.2, NULL, NULL, 'Moderate', 'Moderate', 'Health Worker 2', 'Overweight 22-month-old boy', 1, 'active'),

-- Test Case 19: Normal 16-month-old girl
('TEST-019', 'HERMOSA', 'Mabuco', 'Female', '2023-08-15', 16, NULL, 11.2, 83.0, 16.5, NULL, NULL, 'Normal', 'Low', 'Health Worker 3', 'Normal 16-month-old girl', 0, 'active'),

-- Test Case 20: Severely malnourished 14-month-old boy
('TEST-020', 'ORANI', 'Tala', 'Male', '2023-10-15', 14, NULL, 7.5, 79.0, 13.2, NULL, NULL, 'Severe', 'Severe', 'Health Worker 4', 'Severely malnourished 14-month-old boy', 1, 'active'),

-- Test Case 21: Normal 21-month-old girl
('TEST-021', 'CITY OF BALANGA', 'Tenejero', 'Female', '2023-03-15', 21, NULL, 12.1, 87.0, 17.2, NULL, NULL, 'Normal', 'Low', 'Health Worker 1', 'Normal 21-month-old girl', 0, 'active'),

-- ========================================
-- PRESCHOOLERS (25-36 months) - 8 users
-- ========================================

-- Test Case 22: Normal 30-month-old boy
('TEST-022', 'DINALUPIHAN', 'Roosevelt', 'Male', '2022-06-15', 30, NULL, 13.8, 94.0, 17.5, NULL, NULL, 'Normal', 'Low', 'Health Worker 2', 'Normal 30-month-old boy', 0, 'active'),

-- Test Case 23: Overweight 36-month-old girl
('TEST-023', 'HERMOSA', 'Mandama', 'Female', '2021-12-15', 36, NULL, 16.5, 96.0, 19.2, NULL, NULL, 'Moderate', 'Moderate', 'Health Worker 3', 'Overweight 36-month-old girl', 1, 'active'),

-- Test Case 24: Underweight 28-month-old boy
('TEST-024', 'ORANI', 'Paule 1', 'Male', '2022-08-15', 28, NULL, 11.2, 91.0, 15.8, NULL, NULL, 'Moderate', 'Moderate', 'Health Worker 4', 'Underweight 28-month-old boy', 1, 'active'),

-- Test Case 25: Normal 33-month-old girl
('TEST-025', 'CITY OF BALANGA', 'Central', 'Female', '2022-03-15', 33, NULL, 14.5, 95.0, 18.2, NULL, NULL, 'Normal', 'Low', 'Health Worker 1', 'Normal 33-month-old girl', 0, 'active'),

-- Test Case 26: Stunted 26-month-old boy
('TEST-026', 'DINALUPIHAN', 'Bonifacio (Pob.)', 'Male', '2022-10-15', 26, NULL, 12.8, 88.0, 16.5, NULL, NULL, 'Moderate', 'Moderate', 'Health Worker 2', 'Stunted 26-month-old boy', 1, 'active'),

-- Test Case 27: Normal 35-month-old girl
('TEST-027', 'HERMOSA', 'Saba', 'Female', '2022-01-15', 35, NULL, 15.2, 98.0, 18.8, NULL, NULL, 'Normal', 'Low', 'Health Worker 3', 'Normal 35-month-old girl', 0, 'active'),

-- Test Case 28: Obese 32-month-old boy
('TEST-028', 'ORANI', 'Poblacion', 'Male', '2022-04-15', 32, NULL, 18.8, 97.0, 20.8, NULL, NULL, 'Moderate', 'Moderate', 'Health Worker 4', 'Obese 32-month-old boy', 1, 'active'),

-- Test Case 29: Normal 29-month-old girl
('TEST-029', 'CITY OF BALANGA', 'Tortugas', 'Female', '2022-07-15', 29, NULL, 13.9, 93.0, 17.8, NULL, NULL, 'Normal', 'Low', 'Health Worker 1', 'Normal 29-month-old girl', 0, 'active'),

-- ========================================
-- OLDER PRESCHOOLERS (37-60 months) - 8 users
-- ========================================

-- Test Case 30: Normal 48-month-old boy (4 years)
('TEST-030', 'DINALUPIHAN', 'Paco', 'Male', '2020-12-15', 48, NULL, 16.8, 104.0, 18.5, NULL, NULL, 'Normal', 'Low', 'Health Worker 2', 'Normal 4-year-old boy', 0, 'active'),

-- Test Case 31: Underweight 42-month-old girl
('TEST-031', 'HERMOSA', 'Mabuco', 'Female', '2021-06-15', 42, NULL, 13.5, 100.0, 16.8, NULL, NULL, 'Moderate', 'Moderate', 'Health Worker 3', 'Underweight 42-month-old girl', 1, 'active'),

-- Test Case 32: Overweight 60-month-old boy (5 years)
('TEST-032', 'ORANI', 'Tala', 'Male', '2019-12-15', 60, NULL, 22.5, 112.0, 21.8, NULL, NULL, 'Moderate', 'Moderate', 'Health Worker 4', 'Overweight 5-year-old boy', 1, 'active'),

-- Test Case 33: Normal 54-month-old girl
('TEST-033', 'CITY OF BALANGA', 'Tenejero', 'Female', '2020-06-15', 54, NULL, 18.2, 108.0, 19.2, NULL, NULL, 'Normal', 'Low', 'Health Worker 1', 'Normal 54-month-old girl', 0, 'active'),

-- Test Case 34: Severely stunted 45-month-old boy
('TEST-034', 'DINALUPIHAN', 'Roosevelt', 'Male', '2021-03-15', 45, NULL, 14.2, 95.0, 17.2, NULL, NULL, 'Severe', 'Severe', 'Health Worker 2', 'Severely stunted 45-month-old boy', 1, 'active'),

-- Test Case 35: Normal 50-month-old girl
('TEST-035', 'HERMOSA', 'Mandama', 'Female', '2020-10-15', 50, NULL, 17.5, 106.0, 18.8, NULL, NULL, 'Normal', 'Low', 'Health Worker 3', 'Normal 50-month-old girl', 0, 'active'),

-- Test Case 36: Obese 55-month-old boy
('TEST-036', 'ORANI', 'Paule 1', 'Male', '2020-05-15', 55, NULL, 24.8, 110.0, 22.8, NULL, NULL, 'Moderate', 'Moderate', 'Health Worker 4', 'Obese 55-month-old boy', 1, 'active'),

-- Test Case 37: Normal 40-month-old girl
('TEST-037', 'CITY OF BALANGA', 'Central', 'Female', '2021-08-15', 40, NULL, 15.8, 102.0, 18.2, NULL, NULL, 'Normal', 'Low', 'Health Worker 1', 'Normal 40-month-old girl', 0, 'active'),

-- ========================================
-- EDGE CASES (61-71 months) - 3 users
-- ========================================

-- Test Case 38: Maximum age boy (71 months)
('TEST-038', 'DINALUPIHAN', 'Bonifacio (Pob.)', 'Male', '2019-01-15', 71, NULL, 21.5, 115.0, 20.2, NULL, NULL, 'Normal', 'Low', 'Health Worker 2', 'Maximum age boy (71 months)', 0, 'active'),

-- Test Case 39: Normal 65-month-old girl
('TEST-039', 'HERMOSA', 'Saba', 'Female', '2019-07-15', 65, NULL, 19.8, 113.0, 19.5, NULL, NULL, 'Normal', 'Low', 'Health Worker 3', 'Normal 65-month-old girl', 0, 'active'),

-- Test Case 40: Underweight 70-month-old boy
('TEST-040', 'ORANI', 'Poblacion', 'Male', '2019-02-15', 70, NULL, 17.2, 111.0, 18.2, NULL, NULL, 'Moderate', 'Moderate', 'Health Worker 4', 'Underweight 70-month-old boy', 1, 'active');

-- ========================================
-- VERIFICATION QUERIES
-- ========================================

-- Query to verify test data was inserted correctly
SELECT 
    screening_id,
    municipality,
    barangay,
    sex,
    birthday,
    age,
    weight_kg,
    height_cm,
    nutritional_risk,
    notes
FROM community_users 
WHERE screening_id LIKE 'TEST-%'
ORDER BY age, sex;

-- Query to count test cases by age group
SELECT 
    CASE 
        WHEN age BETWEEN 0 AND 3 THEN 'Newborns (0-3m)'
        WHEN age BETWEEN 4 AND 12 THEN 'Infants (4-12m)'
        WHEN age BETWEEN 13 AND 24 THEN 'Toddlers (13-24m)'
        WHEN age BETWEEN 25 AND 36 THEN 'Preschoolers (25-36m)'
        WHEN age BETWEEN 37 AND 60 THEN 'Older Preschoolers (37-60m)'
        WHEN age BETWEEN 61 AND 71 THEN 'Edge Cases (61-71m)'
        ELSE 'Other'
    END as age_group,
    COUNT(*) as count
FROM community_users 
WHERE screening_id LIKE 'TEST-%'
GROUP BY 
    CASE 
        WHEN age BETWEEN 0 AND 3 THEN 'Newborns (0-3m)'
        WHEN age BETWEEN 4 AND 12 THEN 'Infants (4-12m)'
        WHEN age BETWEEN 13 AND 24 THEN 'Toddlers (13-24m)'
        WHEN age BETWEEN 25 AND 36 THEN 'Preschoolers (25-36m)'
        WHEN age BETWEEN 37 AND 60 THEN 'Older Preschoolers (37-60m)'
        WHEN age BETWEEN 61 AND 71 THEN 'Edge Cases (61-71m)'
        ELSE 'Other'
    END
ORDER BY MIN(age);

-- Query to show nutritional risk distribution
SELECT 
    nutritional_risk,
    COUNT(*) as count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM community_users WHERE screening_id LIKE 'TEST-%'), 2) as percentage
FROM community_users 
WHERE screening_id LIKE 'TEST-%'
GROUP BY nutritional_risk
ORDER BY 
    CASE nutritional_risk
        WHEN 'Severe' THEN 1
        WHEN 'Moderate' THEN 2
        WHEN 'Low' THEN 3
        ELSE 4
    END;
