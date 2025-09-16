# WHO Growth Standards Test Files (Ages 0-71 Months)

This directory contains comprehensive test CSV files for validating the WHO Growth Standards classification system for ages 0-71 months.

## Test Files Created:

### 1. `test_who_classifications_0_35_months_correct.csv`
- **Purpose**: Basic test cases for key ages (0, 1, 6, 12, 18, 24, 30, 35 months)
- **Format**: Follows the EXACT CSV template format from screening.php
- **Test Cases**: 4 classifications per age (Severely Underweight, Underweight, Normal, Overweight)
- **Total Records**: 64 test cases (32 boys + 32 girls)

### 2. `comprehensive_who_test_0_35_months_correct.csv`
- **Purpose**: Extended test cases for more ages
- **Format**: Follows the EXACT CSV template format from screening.php
- **Test Cases**: Covers ages 0, 1, 2, 3, 4, 5, 6, 12, 18, 24, 30, 35 months
- **Total Records**: 96 test cases (48 boys + 48 girls)

### 3. `boundary_test_0_35_months_correct.csv`
- **Purpose**: Boundary testing for critical ages
- **Format**: Follows the EXACT CSV template format from screening.php
- **Test Cases**: Tests the exact min/max values for each classification range
- **Ages Covered**: 0, 6, 12, 24, 35 months
- **Total Records**: 60 boundary test cases (30 boys + 30 girls)

### 4. `test_who_classifications_36_71_months_correct.csv`
- **Purpose**: Basic test cases for ages 36-71 months
- **Format**: Follows the EXACT CSV template format from screening.php
- **Test Cases**: 4 classifications per age (Severely Underweight, Underweight, Normal, Overweight)
- **Ages Covered**: 36, 42, 48, 54, 60, 66, 71 months
- **Total Records**: 56 test cases (28 boys + 28 girls)

### 5. `comprehensive_who_test_36_71_months_correct.csv`
- **Purpose**: Comprehensive test cases for ages 36-71 months
- **Format**: Follows the EXACT CSV template format from screening.php
- **Test Cases**: Covers ages 36, 42, 48, 54, 60, 66, 71 months
- **Total Records**: 56 test cases (28 boys + 28 girls)

### 6. `boundary_test_36_71_months_correct.csv`
- **Purpose**: Boundary testing for ages 36-71 months
- **Format**: Follows the EXACT CSV template format from screening.php
- **Test Cases**: Tests the exact min/max values for each classification range
- **Ages Covered**: 36, 48, 60, 71 months
- **Total Records**: 32 boundary test cases (16 boys + 16 girls)

### 7. Legacy Files (Incorrect Format):
- `test_who_classifications_0_35_months.csv` - Contains extra columns
- `comprehensive_who_test_0_35_months.csv` - Contains extra columns
- `complete_boundary_test_0_35_months.csv` - Contains extra columns
- `final_comprehensive_test_0_35_months.csv` - Contains extra columns

## CSV Format Requirements:

The test files follow the exact format required by the screening.php CSV import:

```csv
name,email,password,municipality,barangay,sex,birthday,is_pregnant,weight,height,screening_date,expected_classification,age_months,test_type
```

### Required Fields:
- **name**: Full name of the test person
- **email**: Unique email address (used as primary key)
- **password**: At least 6 characters
- **municipality**: Must be "CITY OF BALANGA" (exact format)
- **barangay**: Must be "Bagumbayan" (exact format)
- **sex**: Must be exactly "Male" or "Female"
- **birthday**: Date format YYYY-MM-DD
- **is_pregnant**: "No" for all test cases
- **weight**: Weight in kg (0.1-1000 kg, max 2 decimal places)
- **height**: Height in cm (1-300 cm, max 2 decimal places)
- **screening_date**: Date format YYYY-MM-DD HH:MM:SS

### Additional Fields (for testing):
- **expected_classification**: The expected WHO classification result
- **age_months**: Calculated age in months for verification
- **test_type**: Type of test (basic, boundary, etc.)

## How to Use:

1. **Import into Screening Page**:
   - Go to the screening page
   - Click "Import CSV" button
   - Select one of the test files
   - Upload and verify the classifications

2. **Verify Results**:
   - Check that the actual classifications match the expected_classification column
   - Verify that ages are calculated correctly
   - Ensure all test cases pass

3. **Test Different Scenarios**:
   - Use `test_who_classifications_0_35_months.csv` for quick basic testing
   - Use `complete_boundary_test_0_35_months.csv` for boundary testing
   - Use `final_comprehensive_test_0_35_months.csv` for complete validation

## Expected Classifications:

The test files include weights that should result in:
- **Severely Underweight**: Weight ≤ severely_underweight_max
- **Underweight**: severely_underweight_max < weight ≤ underweight_max  
- **Normal**: underweight_max < weight < overweight_min
- **Overweight**: weight ≥ overweight_min

## Notes:

- All test data uses realistic weights and heights for each age
- Birthdays are calculated to give exact ages in months
- All test cases use "CITY OF BALANGA" and "Bagumbayan" as required
- Email addresses are unique and follow the pattern: [sex][age][classification]@test.com
- All test cases are for non-pregnant individuals

## Validation:

After importing, verify that:
1. All users are classified correctly according to WHO standards
2. Age calculations are accurate
3. No validation errors occur during import
4. The dashboard shows the correct classification counts
5. The screening page displays the correct classifications

This comprehensive test suite ensures the WHO Growth Standards classification system works correctly for all ages 0-71 months.
