-- Test Users for WHO Growth Standards - Simple Format
-- This SQL script creates 40 diverse test users with only basic fields
-- The WHO Growth Standards script will calculate and populate the z-score columns

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
('Lucas James Garcia', 'lucas.garcia@test.com', 'DINALUPIHAN', 'Paco', 'Male', '2024-06-15', NULL, 7.8, 68.0, NOW()),

-- Test Case 7: Underweight 4-month-old girl
('Emma Rose Martinez', 'emma.martinez@test.com', 'HERMOSA', 'Mabuco', 'Female', '2024-08-15', NULL, 5.2, 62.0, NOW()),

-- Test Case 8: Overweight 8-month-old boy
('Sebastian Lee Rodriguez', 'sebastian.rodriguez@test.com', 'ORANI', 'Tala', 'Male', '2024-04-15', NULL, 10.5, 72.0, NOW()),

-- Test Case 9: Normal 10-month-old girl
('Olivia Grace Hernandez', 'olivia.hernandez@test.com', 'CITY OF BALANGA', 'Tenejero', 'Female', '2024-02-15', NULL, 8.9, 74.0, NOW()),

-- Test Case 10: Severely underweight 5-month-old boy
('Mateo Alexander Flores', 'mateo.flores@test.com', 'DINALUPIHAN', 'Roosevelt', 'Male', '2024-07-15', NULL, 4.8, 65.0, NOW()),

-- Test Case 11: Normal 12-month-old girl
('Sofia Marie Gutierrez', 'sofia.gutierrez@test.com', 'HERMOSA', 'Mandama', 'Female', '2023-12-15', NULL, 9.2, 76.0, NOW()),

-- Test Case 12: Tall 9-month-old boy
('Gabriel James Ramos', 'gabriel.ramos@test.com', 'ORANI', 'Paule 1', 'Male', '2024-03-15', NULL, 9.8, 78.0, NOW()),

-- Test Case 13: Short 7-month-old girl
('Valentina Rose Morales', 'valentina.morales@test.com', 'CITY OF BALANGA', 'Central', 'Female', '2024-05-15', NULL, 7.2, 69.0, NOW()),

-- ========================================
-- TODDLERS (13-24 months) - 8 users
-- ========================================

-- Test Case 14: Normal 18-month-old boy
('Noah Alexander Vega', 'noah.vega@test.com', 'DINALUPIHAN', 'Bonifacio (Pob.)', 'Male', '2023-06-15', NULL, 11.5, 84.0, NOW()),

-- Test Case 15: Stunted 24-month-old girl
('Camila Grace Silva', 'camila.silva@test.com', 'HERMOSA', 'Saba', 'Female', '2022-12-15', NULL, 10.2, 80.0, NOW()),

-- Test Case 16: Wasted 20-month-old boy
('Liam James Castro', 'liam.castro@test.com', 'ORANI', 'Poblacion', 'Male', '2023-04-15', NULL, 9.8, 86.0, NOW()),

-- Test Case 17: Normal 15-month-old girl
('Aria Marie Delgado', 'aria.delgado@test.com', 'CITY OF BALANGA', 'Tortugas', 'Female', '2023-09-15', NULL, 10.8, 82.0, NOW()),

-- Test Case 18: Overweight 22-month-old boy
('Ethan Lee Herrera', 'ethan.herrera@test.com', 'DINALUPIHAN', 'Paco', 'Male', '2023-02-15', NULL, 13.8, 88.0, NOW()),

-- Test Case 19: Normal 16-month-old girl
('Maya Grace Jimenez', 'maya.jimenez@test.com', 'HERMOSA', 'Mabuco', 'Female', '2023-08-15', NULL, 11.2, 83.0, NOW()),

-- Test Case 20: Severely malnourished 14-month-old boy
('Logan Alexander Ruiz', 'logan.ruiz@test.com', 'ORANI', 'Tala', 'Male', '2023-10-15', NULL, 7.5, 79.0, NOW()),

-- Test Case 21: Normal 21-month-old girl
('Zoe Marie Aguilar', 'zoe.aguilar@test.com', 'CITY OF BALANGA', 'Tenejero', 'Female', '2023-03-15', NULL, 12.1, 87.0, NOW()),

-- ========================================
-- PRESCHOOLERS (25-36 months) - 8 users
-- ========================================

-- Test Case 22: Normal 30-month-old boy
('Mason James Vargas', 'mason.vargas@test.com', 'DINALUPIHAN', 'Roosevelt', 'Male', '2022-06-15', NULL, 13.8, 94.0, NOW()),

-- Test Case 23: Overweight 36-month-old girl
('Luna Grace Mendoza', 'luna.mendoza@test.com', 'HERMOSA', 'Mandama', 'Female', '2021-12-15', NULL, 16.5, 96.0, NOW()),

-- Test Case 24: Underweight 28-month-old boy
('Jackson Lee Guerrero', 'jackson.guerrero@test.com', 'ORANI', 'Paule 1', 'Male', '2022-08-15', NULL, 11.2, 91.0, NOW()),

-- Test Case 25: Normal 33-month-old girl
('Nora Marie Sandoval', 'nora.sandoval@test.com', 'CITY OF BALANGA', 'Central', 'Female', '2022-03-15', NULL, 14.5, 95.0, NOW()),

-- Test Case 26: Stunted 26-month-old boy
('Aiden Alexander Pena', 'aiden.pena@test.com', 'DINALUPIHAN', 'Bonifacio (Pob.)', 'Male', '2022-10-15', NULL, 12.8, 88.0, NOW()),

-- Test Case 27: Normal 35-month-old girl
('Elena Grace Rios', 'elena.rios@test.com', 'HERMOSA', 'Saba', 'Female', '2022-01-15', NULL, 15.2, 98.0, NOW()),

-- Test Case 28: Obese 32-month-old boy
('Caleb James Medina', 'caleb.medina@test.com', 'ORANI', 'Poblacion', 'Male', '2022-04-15', NULL, 18.8, 97.0, NOW()),

-- Test Case 29: Normal 29-month-old girl
('Iris Marie Contreras', 'iris.contreras@test.com', 'CITY OF BALANGA', 'Tortugas', 'Female', '2022-07-15', NULL, 13.9, 93.0, NOW()),

-- ========================================
-- OLDER PRESCHOOLERS (37-60 months) - 8 users
-- ========================================

-- Test Case 30: Normal 48-month-old boy (4 years)
('Owen Alexander Salinas', 'owen.salinas@test.com', 'DINALUPIHAN', 'Paco', 'Male', '2020-12-15', NULL, 16.8, 104.0, NOW()),

-- Test Case 31: Underweight 42-month-old girl
('Violet Grace Espinoza', 'violet.espinoza@test.com', 'HERMOSA', 'Mabuco', 'Female', '2021-06-15', NULL, 13.5, 100.0, NOW()),

-- Test Case 32: Overweight 60-month-old boy (5 years)
('Wyatt Lee Valdez', 'wyatt.valdez@test.com', 'ORANI', 'Tala', 'Male', '2019-12-15', NULL, 22.5, 112.0, NOW()),

-- Test Case 33: Normal 54-month-old girl
('Hazel Marie Ortega', 'hazel.ortega@test.com', 'CITY OF BALANGA', 'Tenejero', 'Female', '2020-06-15', NULL, 18.2, 108.0, NOW()),

-- Test Case 34: Severely stunted 45-month-old boy
('Hunter James Estrada', 'hunter.estrada@test.com', 'DINALUPIHAN', 'Roosevelt', 'Male', '2021-03-15', NULL, 14.2, 95.0, NOW()),

-- Test Case 35: Normal 50-month-old girl
('Aurora Grace Nunez', 'aurora.nunez@test.com', 'HERMOSA', 'Mandama', 'Female', '2020-10-15', NULL, 17.5, 106.0, NOW()),

-- Test Case 36: Obese 55-month-old boy
('Grayson Alexander Carrillo', 'grayson.carrillo@test.com', 'ORANI', 'Paule 1', 'Male', '2020-05-15', NULL, 24.8, 110.0, NOW()),

-- Test Case 37: Normal 40-month-old girl
('Stella Marie Leon', 'stella.leon@test.com', 'CITY OF BALANGA', 'Central', 'Female', '2021-08-15', NULL, 15.8, 102.0, NOW()),

-- ========================================
-- EDGE CASES (61-71 months) - 3 users
-- ========================================

-- Test Case 38: Maximum age boy (71 months)
('Theo James Marquez', 'theo.marquez@test.com', 'DINALUPIHAN', 'Bonifacio (Pob.)', 'Male', '2019-01-15', NULL, 21.5, 115.0, NOW()),

-- Test Case 39: Normal 65-month-old girl
('Lily Grace Robles', 'lily.robles@test.com', 'HERMOSA', 'Saba', 'Female', '2019-07-15', NULL, 19.8, 113.0, NOW()),

-- Test Case 40: Underweight 70-month-old boy
('Finn Alexander De La Cruz', 'finn.delacruz@test.com', 'ORANI', 'Poblacion', 'Male', '2019-02-15', NULL, 17.2, 111.0, NOW());

-- ========================================
-- VERIFICATION QUERIES
-- ========================================

-- Query to verify test data was inserted correctly
SELECT 
    name,
    email,
    sex,
    birthday,
    weight,
    height,
    municipality,
    barangay,
    screening_date
FROM community_users 
WHERE email LIKE '%@test.com'
ORDER BY birthday DESC;

-- Query to count test cases by age group (calculated from birthday)
SELECT 
    CASE 
        WHEN DATEDIFF(CURDATE(), birthday) / 30.44 BETWEEN 0 AND 3 THEN 'Newborns (0-3m)'
        WHEN DATEDIFF(CURDATE(), birthday) / 30.44 BETWEEN 4 AND 12 THEN 'Infants (4-12m)'
        WHEN DATEDIFF(CURDATE(), birthday) / 30.44 BETWEEN 13 AND 24 THEN 'Toddlers (13-24m)'
        WHEN DATEDIFF(CURDATE(), birthday) / 30.44 BETWEEN 25 AND 36 THEN 'Preschoolers (25-36m)'
        WHEN DATEDIFF(CURDATE(), birthday) / 30.44 BETWEEN 37 AND 60 THEN 'Older Preschoolers (37-60m)'
        WHEN DATEDIFF(CURDATE(), birthday) / 30.44 BETWEEN 61 AND 71 THEN 'Edge Cases (61-71m)'
        ELSE 'Other'
    END as age_group,
    COUNT(*) as count
FROM community_users 
WHERE email LIKE '%@test.com'
GROUP BY 
    CASE 
        WHEN DATEDIFF(CURDATE(), birthday) / 30.44 BETWEEN 0 AND 3 THEN 'Newborns (0-3m)'
        WHEN DATEDIFF(CURDATE(), birthday) / 30.44 BETWEEN 4 AND 12 THEN 'Infants (4-12m)'
        WHEN DATEDIFF(CURDATE(), birthday) / 30.44 BETWEEN 13 AND 24 THEN 'Toddlers (13-24m)'
        WHEN DATEDIFF(CURDATE(), birthday) / 30.44 BETWEEN 25 AND 36 THEN 'Preschoolers (25-36m)'
        WHEN DATEDIFF(CURDATE(), birthday) / 30.44 BETWEEN 37 AND 60 THEN 'Older Preschoolers (37-60m)'
        WHEN DATEDIFF(CURDATE(), birthday) / 30.44 BETWEEN 61 AND 71 THEN 'Edge Cases (61-71m)'
        ELSE 'Other'
    END
ORDER BY MIN(DATEDIFF(CURDATE(), birthday) / 30.44);
