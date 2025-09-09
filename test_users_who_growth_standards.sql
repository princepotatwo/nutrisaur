-- Test Users for WHO Growth Standards Testing
-- This SQL script creates diverse test users to validate WHO Growth Standards functionality
-- across different age groups, nutritional statuses, and scenarios

-- First, ensure the required columns exist in community_users table
ALTER TABLE `community_users` 
ADD COLUMN IF NOT EXISTS `bmi-for-age` DECIMAL(4,2) DEFAULT NULL COMMENT 'BMI-for-age z-score (WHO standards)',
ADD COLUMN IF NOT EXISTS `weight-for-height` DECIMAL(4,2) DEFAULT NULL COMMENT 'Weight-for-height z-score (WHO standards)',
ADD COLUMN IF NOT EXISTS `weight-for-age` DECIMAL(4,2) DEFAULT NULL COMMENT 'Weight-for-age z-score (WHO standards)',
ADD COLUMN IF NOT EXISTS `weight-for-length` DECIMAL(4,2) DEFAULT NULL COMMENT 'Weight-for-length z-score (WHO standards)',
ADD COLUMN IF NOT EXISTS `height-for-age` DECIMAL(4,2) DEFAULT NULL COMMENT 'Height-for-age z-score (WHO standards)';

-- Clear existing test data (optional - comment out if you want to keep existing data)
-- DELETE FROM `community_users` WHERE `screening_id` LIKE 'TEST-%';

-- Insert diverse test users for WHO Growth Standards testing
INSERT INTO `community_users` (
  `screening_id`, `municipality`, `barangay`, `sex`, `birthday`, `age`, 
  `is_pregnant`, `weight_kg`, `height_cm`, `muac_cm`, `bmi`, `bmi_category`, 
  `muac_category`, `nutritional_risk`, `screened_by`, `notes`, `follow_up_required`
) VALUES 

-- ========================================
-- INFANTS (0-12 months) - Testing early growth patterns
-- ========================================

-- Test Case 1: Normal 6-month-old boy
(
  'TEST-001', 'CITY OF BALANGA', 'Central', 'Male', '2024-06-15', 6,
  NULL, 7.5, 67.0, 15.2, 16.7, 'Normal', 'Normal', 'Low',
  'Health Worker 1', 'Normal growth pattern for 6-month-old boy', 0
),

-- Test Case 2: Underweight 3-month-old girl
(
  'TEST-002', 'DINALUPIHAN', 'Bonifacio (Pob.)', 'Female', '2024-09-15', 3,
  NULL, 4.8, 58.0, 13.5, 14.3, 'Underweight', 'Moderate', 'Moderate',
  'Health Worker 2', 'Underweight 3-month-old - needs monitoring', 1
),

-- Test Case 3: Severely underweight 9-month-old boy
(
  'TEST-003', 'HERMOSA', 'Saba', 'Male', '2024-03-15', 9,
  NULL, 6.2, 70.0, 12.8, 12.6, 'Severely Underweight', 'Severe', 'Severe',
  'Health Worker 3', 'Severe malnutrition - immediate intervention needed', 1
),

-- Test Case 4: Overweight 12-month-old girl
(
  'TEST-004', 'ORANI', 'Poblacion', 'Female', '2023-12-15', 12,
  NULL, 11.8, 75.0, 16.8, 21.0, 'Overweight', 'Normal', 'Moderate',
  'Health Worker 4', 'Overweight 12-month-old - dietary counseling needed', 1
),

-- ========================================
-- TODDLERS (13-24 months) - Testing critical growth period
-- ========================================

-- Test Case 5: Normal 18-month-old boy
(
  'TEST-005', 'CITY OF BALANGA', 'Tortugas', 'Male', '2023-06-15', 18,
  NULL, 11.2, 82.0, 15.8, 16.6, 'Normal', 'Normal', 'Low',
  'Health Worker 1', 'Normal growth for 18-month-old', 0
),

-- Test Case 6: Stunted 24-month-old girl (low height-for-age)
(
  'TEST-006', 'DINALUPIHAN', 'Paco', 'Female', '2022-12-15', 24,
  NULL, 9.8, 80.0, 14.2, 15.3, 'Normal', 'Moderate', 'Moderate',
  'Health Worker 2', 'Stunted growth - height below normal range', 1
),

-- Test Case 7: Wasted 20-month-old boy (low weight-for-height)
(
  'TEST-007', 'HERMOSA', 'Mabuco', 'Male', '2023-04-15', 20,
  NULL, 8.5, 85.0, 13.1, 11.8, 'Underweight', 'Severe', 'Severe',
  'Health Worker 3', 'Wasted - low weight for height', 1
),

-- Test Case 8: Normal 15-month-old girl
(
  'TEST-008', 'ORANI', 'Tala', 'Female', '2023-09-15', 15,
  NULL, 10.5, 78.0, 15.5, 17.3, 'Normal', 'Normal', 'Low',
  'Health Worker 4', 'Normal growth pattern', 0
),

-- ========================================
-- PRESCHOOLERS (25-36 months) - Testing continued growth
-- ========================================

-- Test Case 9: Normal 30-month-old boy
(
  'TEST-009', 'CITY OF BALANGA', 'Tenejero', 'Male', '2022-06-15', 30,
  NULL, 13.0, 92.0, 16.2, 15.4, 'Normal', 'Normal', 'Low',
  'Health Worker 1', 'Normal growth for 30-month-old', 0
),

-- Test Case 10: Overweight 36-month-old girl
(
  'TEST-010', 'DINALUPIHAN', 'Roosevelt', 'Female', '2021-12-15', 36,
  NULL, 16.8, 95.0, 18.5, 18.6, 'Overweight', 'Normal', 'Moderate',
  'Health Worker 2', 'Overweight 3-year-old - dietary intervention', 1
),

-- Test Case 11: Severely stunted 28-month-old boy
(
  'TEST-011', 'HERMOSA', 'Mandama', 'Male', '2022-08-15', 28,
  NULL, 10.2, 85.0, 13.8, 14.1, 'Underweight', 'Severe', 'Severe',
  'Health Worker 3', 'Severely stunted - critical intervention needed', 1
),

-- Test Case 12: Normal 33-month-old girl
(
  'TEST-012', 'ORANI', 'Paule 1', 'Female', '2022-03-15', 33,
  NULL, 14.2, 94.0, 16.8, 16.1, 'Normal', 'Normal', 'Low',
  'Health Worker 4', 'Normal growth development', 0
),

-- ========================================
-- OLDER PRESCHOOLERS (37-60 months) - Testing pre-school age growth
-- ========================================

-- Test Case 13: Normal 48-month-old boy (4 years)
(
  'TEST-013', 'CITY OF BALANGA', 'Tortugas', 'Male', '2020-12-15', 48,
  NULL, 16.5, 102.0, 17.8, 15.9, 'Normal', 'Normal', 'Low',
  'Health Worker 1', 'Normal 4-year-old growth', 0
),

-- Test Case 14: Underweight 42-month-old girl
(
  'TEST-014', 'DINALUPIHAN', 'Paco', 'Female', '2021-06-15', 42,
  NULL, 13.8, 98.0, 15.2, 14.4, 'Underweight', 'Moderate', 'Moderate',
  'Health Worker 2', 'Underweight 3.5-year-old - needs support', 1
),

-- Test Case 15: Obese 60-month-old boy (5 years)
(
  'TEST-015', 'HERMOSA', 'Saba', 'Male', '2019-12-15', 60,
  NULL, 22.5, 110.0, 20.2, 18.6, 'Overweight', 'Normal', 'Moderate',
  'Health Worker 3', 'Obese 5-year-old - urgent dietary intervention', 1
),

-- Test Case 16: Normal 54-month-old girl
(
  'TEST-016', 'ORANI', 'Poblacion', 'Female', '2020-06-15', 54,
  NULL, 18.2, 107.0, 17.5, 15.9, 'Normal', 'Normal', 'Low',
  'Health Worker 4', 'Normal growth for 4.5-year-old', 0
),

-- ========================================
-- EDGE CASES - Testing boundary conditions
-- ========================================

-- Test Case 17: Newborn (0 months) - boy
(
  'TEST-017', 'CITY OF BALANGA', 'Central', 'Male', '2024-12-15', 0,
  NULL, 3.2, 50.0, 12.5, 12.8, 'Normal', 'Normal', 'Low',
  'Health Worker 1', 'Newborn - normal birth measurements', 0
),

-- Test Case 18: Maximum age (71 months) - girl
(
  'TEST-018', 'DINALUPIHAN', 'Bonifacio (Pob.)', 'Female', '2019-01-15', 71,
  NULL, 20.8, 113.0, 18.8, 16.3, 'Normal', 'Normal', 'Low',
  'Health Worker 2', 'Maximum age for WHO standards - 5 years 11 months', 0
),

-- Test Case 19: Borderline underweight 24-month-old boy
(
  'TEST-019', 'HERMOSA', 'Mabuco', 'Male', '2022-12-15', 24,
  NULL, 10.8, 87.0, 15.0, 14.3, 'Underweight', 'Normal', 'Moderate',
  'Health Worker 3', 'Borderline underweight - close to normal range', 1
),

-- Test Case 20: Borderline overweight 36-month-old girl
(
  'TEST-020', 'ORANI', 'Tala', 'Female', '2021-12-15', 36,
  NULL, 15.2, 95.0, 17.2, 16.8, 'Normal', 'Normal', 'Low',
  'Health Worker 4', 'Borderline normal - close to overweight', 0
),

-- ========================================
-- SPECIAL CASES - Testing specific scenarios
-- ========================================

-- Test Case 21: Very tall 30-month-old boy (high height-for-age)
(
  'TEST-021', 'CITY OF BALANGA', 'Tenejero', 'Male', '2022-06-15', 30,
  NULL, 14.5, 98.0, 16.8, 15.1, 'Normal', 'Normal', 'Low',
  'Health Worker 1', 'Tall for age but normal weight - genetic variation', 0
),

-- Test Case 22: Very short 36-month-old girl (low height-for-age)
(
  'TEST-022', 'DINALUPIHAN', 'Roosevelt', 'Female', '2021-12-15', 36,
  NULL, 12.8, 88.0, 15.5, 16.5, 'Normal', 'Moderate', 'Moderate',
  'Health Worker 2', 'Short for age - possible stunting', 1
),

-- Test Case 23: High weight, normal height 24-month-old boy
(
  'TEST-023', 'HERMOSA', 'Mandama', 'Male', '2022-12-15', 24,
  NULL, 13.5, 87.0, 17.5, 17.8, 'Overweight', 'Normal', 'Moderate',
  'Health Worker 3', 'High weight for height - dietary counseling', 1
),

-- Test Case 24: Low weight, normal height 18-month-old girl
(
  'TEST-024', 'ORANI', 'Paule 1', 'Female', '2023-06-15', 18,
  NULL, 9.2, 82.0, 14.0, 13.7, 'Underweight', 'Moderate', 'Moderate',
  'Health Worker 4', 'Low weight for height - nutritional support', 1
),

-- Test Case 25: Extreme case - severely malnourished 12-month-old
(
  'TEST-025', 'CITY OF BALANGA', 'Tortugas', 'Male', '2023-12-15', 12,
  NULL, 6.8, 70.0, 11.5, 13.9, 'Severely Underweight', 'Severe', 'Severe',
  'Health Worker 1', 'CRITICAL: Severely malnourished - immediate intervention', 1
);

-- ========================================
-- VERIFICATION QUERIES
-- ========================================

-- Query to verify test data was inserted correctly
SELECT 
    screening_id,
    sex,
    age,
    weight_kg,
    height_cm,
    bmi,
    bmi_category,
    nutritional_risk,
    notes
FROM community_users 
WHERE screening_id LIKE 'TEST-%'
ORDER BY age, sex;

-- Query to count test cases by age group
SELECT 
    CASE 
        WHEN age BETWEEN 0 AND 12 THEN 'Infants (0-12m)'
        WHEN age BETWEEN 13 AND 24 THEN 'Toddlers (13-24m)'
        WHEN age BETWEEN 25 AND 36 THEN 'Preschoolers (25-36m)'
        WHEN age BETWEEN 37 AND 60 THEN 'Older Preschoolers (37-60m)'
        WHEN age BETWEEN 61 AND 71 THEN 'Edge Cases (61-71m)'
        ELSE 'Other'
    END as age_group,
    COUNT(*) as count,
    COUNT(CASE WHEN nutritional_risk = 'Severe' THEN 1 END) as severe_cases,
    COUNT(CASE WHEN nutritional_risk = 'Moderate' THEN 1 END) as moderate_cases,
    COUNT(CASE WHEN nutritional_risk = 'Low' THEN 1 END) as low_cases
FROM community_users 
WHERE screening_id LIKE 'TEST-%'
GROUP BY 
    CASE 
        WHEN age BETWEEN 0 AND 12 THEN 'Infants (0-12m)'
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
