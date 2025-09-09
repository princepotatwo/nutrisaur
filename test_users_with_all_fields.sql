-- Test Users with all possible fields to avoid missing column errors
-- This includes fields that might be required but not visible in the table structure

INSERT INTO `community_users` (
  `name`, `email`, `password`, `municipality`, `barangay`, `sex`, `birthday`, 
  `is_pregnant`, `weight`, `height`, `screening_date`, `fcm_token`
) VALUES 

-- Test Case 1: Normal 6-month-old boy
('Juan Carlos Santos', 'juan.santos@test.com', 'password123', 'CITY OF BALANGA', 'Central', 'Male', '2024-06-15', NULL, 7.8, 68.0, NOW(), NULL),

-- Test Case 2: Underweight 4-month-old girl  
('Maria Sofia Reyes', 'maria.reyes@test.com', 'password123', 'DINALUPIHAN', 'Bonifacio (Pob.)', 'Female', '2024-08-15', NULL, 5.2, 62.0, NOW(), NULL),

-- Test Case 3: Overweight 8-month-old boy
('Miguel Angel Cruz', 'miguel.cruz@test.com', 'password123', 'HERMOSA', 'Saba', 'Male', '2024-04-15', NULL, 10.5, 72.0, NOW(), NULL),

-- Test Case 4: Normal 12-month-old girl
('Isabella Grace Lopez', 'isabella.lopez@test.com', 'password123', 'ORANI', 'Poblacion', 'Female', '2023-12-15', NULL, 9.2, 76.0, NOW(), NULL),

-- Test Case 5: Severely underweight 5-month-old boy
('Diego Emmanuel Torres', 'diego.torres@test.com', 'password123', 'CITY OF BALANGA', 'Tortugas', 'Male', '2024-07-15', NULL, 4.8, 65.0, NOW(), NULL),

-- Test Case 6: Normal 18-month-old boy
('Lucas James Garcia', 'lucas.garcia@test.com', 'password123', 'DINALUPIHAN', 'Paco', 'Male', '2023-06-15', NULL, 11.5, 84.0, NOW(), NULL),

-- Test Case 7: Stunted 24-month-old girl
('Emma Rose Martinez', 'emma.martinez@test.com', 'password123', 'HERMOSA', 'Mabuco', 'Female', '2022-12-15', NULL, 10.2, 80.0, NOW(), NULL),

-- Test Case 8: Wasted 20-month-old boy
('Sebastian Lee Rodriguez', 'sebastian.rodriguez@test.com', 'password123', 'ORANI', 'Tala', 'Male', '2023-04-15', NULL, 9.8, 86.0, NOW(), NULL),

-- Test Case 9: Normal 30-month-old boy
('Olivia Grace Hernandez', 'olivia.hernandez@test.com', 'password123', 'CITY OF BALANGA', 'Tenejero', 'Male', '2022-06-15', NULL, 13.8, 94.0, NOW(), NULL),

-- Test Case 10: Overweight 36-month-old girl
('Mateo Alexander Flores', 'mateo.flores@test.com', 'password123', 'DINALUPIHAN', 'Roosevelt', 'Female', '2021-12-15', NULL, 16.5, 96.0, NOW(), NULL),

-- Test Case 11: Underweight 28-month-old boy
('Sofia Marie Gutierrez', 'sofia.gutierrez@test.com', 'password123', 'HERMOSA', 'Mandama', 'Male', '2022-08-15', NULL, 11.2, 91.0, NOW(), NULL),

-- Test Case 12: Normal 48-month-old boy (4 years)
('Gabriel James Ramos', 'gabriel.ramos@test.com', 'password123', 'ORANI', 'Paule 1', 'Male', '2020-12-15', NULL, 16.8, 104.0, NOW(), NULL),

-- Test Case 13: Underweight 42-month-old girl
('Valentina Rose Morales', 'valentina.morales@test.com', 'password123', 'CITY OF BALANGA', 'Central', 'Female', '2021-06-15', NULL, 13.5, 100.0, NOW(), NULL),

-- Test Case 14: Overweight 60-month-old boy (5 years)
('Noah Alexander Vega', 'noah.vega@test.com', 'password123', 'DINALUPIHAN', 'Bonifacio (Pob.)', 'Male', '2019-12-15', NULL, 22.5, 112.0, NOW(), NULL),

-- Test Case 15: Normal 54-month-old girl
('Camila Grace Silva', 'camila.silva@test.com', 'password123', 'HERMOSA', 'Saba', 'Female', '2020-06-15', NULL, 18.2, 108.0, NOW(), NULL),

-- Test Case 16: Severely stunted 45-month-old boy
('Liam James Castro', 'liam.castro@test.com', 'password123', 'ORANI', 'Poblacion', 'Male', '2021-03-15', NULL, 14.2, 95.0, NOW(), NULL),

-- Test Case 17: Normal 50-month-old girl
('Aria Marie Delgado', 'aria.delgado@test.com', 'password123', 'CITY OF BALANGA', 'Tortugas', 'Female', '2020-10-15', NULL, 17.5, 106.0, NOW(), NULL),

-- Test Case 18: Obese 55-month-old boy
('Ethan Lee Herrera', 'ethan.herrera@test.com', 'password123', 'DINALUPIHAN', 'Paco', 'Male', '2020-05-15', NULL, 24.8, 110.0, NOW(), NULL),

-- Test Case 19: Normal 40-month-old girl
('Maya Grace Jimenez', 'maya.jimenez@test.com', 'password123', 'HERMOSA', 'Mabuco', 'Female', '2021-08-15', NULL, 15.8, 102.0, NOW(), NULL),

-- Test Case 20: Maximum age boy (71 months)
('Logan Alexander Ruiz', 'logan.ruiz@test.com', 'password123', 'ORANI', 'Tala', 'Male', '2019-01-15', NULL, 21.5, 115.0, NOW(), NULL);

-- Verification query
SELECT 
    name,
    email,
    sex,
    birthday,
    weight,
    height,
    municipality,
    barangay
FROM community_users 
WHERE email LIKE '%@test.com'
ORDER BY birthday DESC;
