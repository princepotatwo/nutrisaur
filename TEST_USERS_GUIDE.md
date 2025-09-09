# WHO Growth Standards Test Users Guide

This guide explains how to use the diverse test users to validate the WHO Growth Standards functionality in your nutritional screening system.

## Overview

The test data includes **25 diverse test users** covering:
- **All age groups** from newborns (0 months) to maximum WHO age (71 months)
- **Different nutritional statuses** (Normal, Underweight, Overweight, Severely Underweight)
- **Various growth patterns** (Stunted, Wasted, Normal, Obese)
- **Edge cases** and boundary conditions
- **Both male and female** children

## Files Created

1. **`test_users_who_growth_standards.sql`** - SQL script with test data
2. **`test_who_growth_functionality.php`** - Comprehensive test display script
3. **`process_test_users.php`** - Script to process and save WHO standards
4. **`TEST_USERS_GUIDE.md`** - This guide

## Setup Instructions

### Step 1: Run the SQL Script

Execute the SQL script to add test users to your database:

```sql
-- Run this in your MySQL database
SOURCE test_users_who_growth_standards.sql;
```

This will:
- Add the required WHO Growth Standards columns to `community_users` table
- Insert 25 diverse test users
- Create verification queries

### Step 2: Process the Test Users

Run the processing script to calculate and save WHO Growth Standards:

```bash
# Via web browser
http://your-domain.com/process_test_users.php

# Or via command line
php process_test_users.php
```

### Step 3: View Detailed Results

View comprehensive test results:

```bash
# Via web browser
http://your-domain.com/test_who_growth_functionality.php
```

## Test User Categories

### Infants (0-12 months) - 4 users
- **TEST-001**: Normal 6-month-old boy
- **TEST-002**: Underweight 3-month-old girl
- **TEST-003**: Severely underweight 9-month-old boy
- **TEST-004**: Overweight 12-month-old girl

### Toddlers (13-24 months) - 4 users
- **TEST-005**: Normal 18-month-old boy
- **TEST-006**: Stunted 24-month-old girl (low height-for-age)
- **TEST-007**: Wasted 20-month-old boy (low weight-for-height)
- **TEST-008**: Normal 15-month-old girl

### Preschoolers (25-36 months) - 4 users
- **TEST-009**: Normal 30-month-old boy
- **TEST-010**: Overweight 36-month-old girl
- **TEST-011**: Severely stunted 28-month-old boy
- **TEST-012**: Normal 33-month-old girl

### Older Preschoolers (37-60 months) - 4 users
- **TEST-013**: Normal 48-month-old boy (4 years)
- **TEST-014**: Underweight 42-month-old girl
- **TEST-015**: Obese 60-month-old boy (5 years)
- **TEST-016**: Normal 54-month-old girl

### Edge Cases (61-71 months) - 2 users
- **TEST-017**: Newborn (0 months) - boy
- **TEST-018**: Maximum age (71 months) - girl

### Special Cases - 7 users
- **TEST-019**: Borderline underweight 24-month-old boy
- **TEST-020**: Borderline overweight 36-month-old girl
- **TEST-021**: Very tall 30-month-old boy (high height-for-age)
- **TEST-022**: Very short 36-month-old girl (low height-for-age)
- **TEST-023**: High weight, normal height 24-month-old boy
- **TEST-024**: Low weight, normal height 18-month-old girl
- **TEST-025**: Extreme case - severely malnourished 12-month-old

## Expected Results

### Nutritional Risk Distribution
- **Low Risk**: ~40% (Normal growth patterns)
- **Moderate Risk**: ~40% (Underweight/Overweight indicators)
- **Severe Risk**: ~20% (Severe malnutrition cases)

### Age Group Coverage
- **Infants (0-12m)**: 4 users
- **Toddlers (13-24m)**: 4 users
- **Preschoolers (25-36m)**: 4 users
- **Older Preschoolers (37-60m)**: 4 users
- **Edge Cases (61-71m)**: 2 users
- **Special Cases**: 7 users

## Validation Points

### 1. Z-Score Calculations
Verify that z-scores are calculated correctly:
- Formula: `(observed - median) / sd`
- Range: Typically -3 to +3
- Accuracy: Should match WHO standards exactly

### 2. Classifications
Check that classifications are correct:
- **Severely Underweight**: z-score < -3
- **Underweight**: z-score -3 to < -2
- **Normal**: z-score -2 to +2
- **Overweight**: z-score > +2

### 3. Age Ranges
Ensure all indicators work within their ranges:
- **Weight-for-Age**: 0-71 months
- **Height-for-Age**: 0-71 months
- **Weight-for-Height**: 65-120 cm
- **Weight-for-Length**: 45-110 cm
- **BMI-for-Age**: 0-71 months

### 4. Database Integration
Verify that results are saved correctly:
- All z-scores are stored
- Classifications are updated
- BMI calculations are correct

## Testing Scenarios

### Scenario 1: Normal Growth
**Test Users**: TEST-001, TEST-005, TEST-008, TEST-009, TEST-012, TEST-013, TEST-016, TEST-018
**Expected**: All indicators show "Normal" classification, Low nutritional risk

### Scenario 2: Underweight
**Test Users**: TEST-002, TEST-006, TEST-007, TEST-011, TEST-014, TEST-019, TEST-024
**Expected**: Various indicators show "Underweight", Moderate to Severe risk

### Scenario 3: Overweight/Obese
**Test Users**: TEST-004, TEST-010, TEST-015, TEST-020, TEST-023
**Expected**: BMI-for-Age shows "Overweight", Moderate risk

### Scenario 4: Severe Malnutrition
**Test Users**: TEST-003, TEST-011, TEST-025
**Expected**: Multiple indicators show "Severely Underweight", Severe risk

### Scenario 5: Edge Cases
**Test Users**: TEST-017 (newborn), TEST-018 (max age), TEST-021 (very tall), TEST-022 (very short)
**Expected**: Proper handling of boundary conditions

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check `config.php` database settings
   - Ensure MySQL is running
   - Verify table exists

2. **Missing Columns Error**
   - Run the ALTER TABLE statements in the SQL script
   - Check column names match exactly

3. **Age Calculation Error**
   - Verify birth dates are in correct format (YYYY-MM-DD)
   - Check age calculation logic

4. **Z-Score Calculation Error**
   - Verify WHO standards data is correct
   - Check mathematical operations

### Verification Queries

```sql
-- Check if test users exist
SELECT COUNT(*) FROM community_users WHERE screening_id LIKE 'TEST-%';

-- Check if WHO columns exist
DESCRIBE community_users;

-- Check sample results
SELECT screening_id, age, sex, `weight-for-age`, `height-for-age`, `weight-for-height`, `bmi-for-age` 
FROM community_users 
WHERE screening_id LIKE 'TEST-%' 
LIMIT 5;
```

## Integration with Screening.php

The test users are designed to work with your existing `screening.php` file. The WHO Growth Standards will be automatically calculated and displayed when viewing these test users in the screening interface.

### Expected Display in Screening.php

1. **Growth Standards Section**: Shows all 5 WHO indicators
2. **Z-Score Values**: Displays calculated z-scores
3. **Classifications**: Shows nutritional status
4. **Risk Assessment**: Displays overall nutritional risk
5. **Recommendations**: Provides appropriate guidance

## Performance Testing

These test users can also be used for performance testing:

1. **Load Testing**: Process all 25 users simultaneously
2. **Database Performance**: Monitor query execution times
3. **Memory Usage**: Check PHP memory consumption
4. **Response Times**: Measure API response times

## Customization

You can modify the test data by:

1. **Adding More Users**: Insert additional test cases
2. **Changing Parameters**: Modify weights, heights, ages
3. **Adding Scenarios**: Include specific medical conditions
4. **Geographic Data**: Add different municipalities/barangays

## Support

If you encounter issues:

1. Check the error messages in the processing script
2. Verify database connectivity
3. Ensure all required columns exist
4. Check PHP error logs
5. Validate input data format

## Conclusion

These test users provide comprehensive coverage of the WHO Growth Standards functionality, ensuring your nutritional screening system works correctly across all age groups and nutritional statuses. Use them to validate your implementation and demonstrate the system's capabilities.
