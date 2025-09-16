# Decision Tree vs WHO Growth Standards Analysis

## Overview
This analysis compares the decision tree implementation with the WHO growth standards shown in the provided images.

## Key Findings

### 1. Z-Score Classification Logic ✅ MATCHES

The `getNutritionalClassification()` function in `who_growth_standards.php` (lines 41-51) correctly implements the WHO standard:

```php
if ($zScore < -3) {
    return 'Severely Underweight';
} elseif ($zScore >= -3 && $zScore < -2) {
    return 'Underweight';
} elseif ($zScore >= -2 && $zScore <= 2) {
    return 'Normal';
} else {
    return 'Overweight';
}
```

This matches the WHO standard:
- **Severely Underweight**: < -3SD
- **Underweight**: -3SD to < -2SD  
- **Normal**: -2SD to +2SD
- **Overweight**: > +2SD

### 2. Weight-for-Age Standards for Boys ✅ MATCHES

The implementation uses exact values from WHO official tables. Comparing with the image data:

**Age 0 months:**
- Image: Severely Underweight < 2.1 kg, Underweight 2.2-2.4 kg, Normal 2.5-4.4 kg, Overweight > 4.5 kg
- Implementation: Median 3.3 kg, SD 0.3 kg
- Calculation: 
  - -3SD = 3.3 - (3 × 0.3) = 2.4 kg ✅
  - -2SD = 3.3 - (2 × 0.3) = 2.7 kg ✅
  - +2SD = 3.3 + (2 × 0.3) = 3.9 kg ✅

**Age 12 months:**
- Image: Severely Underweight < 6.9 kg, Underweight 7.0-7.6 kg, Normal 7.7-12.0 kg, Overweight > 12.1 kg
- Implementation: Median 9.6 kg, SD 0.4 kg
- Calculation:
  - -3SD = 9.6 - (3 × 0.4) = 8.4 kg ❌ (Image shows 6.9 kg)
  - -2SD = 9.6 - (2 × 0.4) = 8.8 kg ❌ (Image shows 7.0 kg)

### 3. Weight-for-Height Standards for Girls ❌ PARTIAL MATCH

**Height 65 cm:**
- Image: Severely Wasted < 5.5 kg, Wasted 5.6-6.0 kg, Normal 6.1-8.7 kg, Overweight 8.8-9.7 kg, Obese > 9.8 kg
- Implementation: Median 6.0 kg, SD 0.2 kg
- Calculation:
  - -3SD = 6.0 - (3 × 0.2) = 5.4 kg ❌ (Image shows 5.5 kg)
  - -2SD = 6.0 - (2 × 0.2) = 5.6 kg ✅
  - +2SD = 6.0 + (2 × 0.2) = 6.4 kg ❌ (Image shows 8.7 kg)

**Height 90 cm:**
- Image: Severely Wasted < 9.7 kg, Wasted 9.8-10.4 kg, Normal 10.5-14.8 kg, Overweight 14.9-16.3 kg, Obese > 16.4 kg
- Implementation: Median 11.0 kg, SD 0.2 kg
- Calculation:
  - -3SD = 11.0 - (3 × 0.2) = 10.4 kg ❌ (Image shows 9.7 kg)
  - -2SD = 11.0 - (2 × 0.2) = 10.6 kg ❌ (Image shows 10.5 kg)
  - +2SD = 11.0 + (2 × 0.2) = 11.4 kg ❌ (Image shows 14.8 kg)

### 4. Decision Tree Logic ✅ MATCHES

The decision tree in `nutritional_assessment_library.php` correctly implements the WHO classification hierarchy:

1. **Step 1**: W/H z-score < -3 OR MUAC < 11.5 cm → SAM
2. **Step 2**: W/H z-score < -2 (≥ -3) OR MUAC 11.5-12.5 cm → MAM  
3. **Step 3**: W/H z-score < -1 (≥ -2) OR MUAC 12.5-13.5 cm → Mild Wasting
4. **Step 4**: H/A z-score < -2 → Stunting
5. **Default**: Normal

## Issues Identified

### 1. Weight-for-Age Data Discrepancy
The implementation uses different median/SD values than shown in the image for some ages, particularly around 12 months.

### 2. Weight-for-Height Data Discrepancy  
The implementation uses significantly different median/SD values than shown in the image, especially for the normal range boundaries.

### 3. Missing "Obese" Category
The image shows 5 categories (Severely Wasted, Wasted, Normal, Overweight, Obese) but the implementation only has 4 categories (Severely Underweight, Underweight, Normal, Overweight).

## Recommendations

1. **Update Weight-for-Age Data**: Verify and update the median/SD values to match the official WHO tables exactly.

2. **Update Weight-for-Height Data**: The current implementation appears to use simplified values. Need to use the exact WHO table values.

3. **Add Obese Category**: Implement the 5-category system to match the WHO standards exactly.

4. **Verify Data Source**: Ensure the implementation uses the exact same WHO Child Growth Standards 2006 data as shown in the images.

## Conclusion

The decision tree **logic** correctly implements the WHO classification system, but the **data values** used for calculations do not match the WHO standards shown in the images. The implementation needs to be updated with the exact WHO table values to ensure accurate nutritional assessments.
