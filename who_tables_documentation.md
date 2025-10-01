# WHO Growth Standards Decision Trees and Tables Documentation

## Overview
The WHO Growth Standards implementation uses decision trees to classify child growth based on Z-scores calculated from WHO reference tables. This system provides comprehensive nutritional assessment for children aged 0-71 months.

## Key Decision Trees

### 1. Weight-for-Age (WFA) Decision Tree
**Purpose**: Assesses if child's weight is appropriate for their age
**Z-Score Thresholds**:
- < -3 SD: Severely Underweight
- -3 to -2 SD: Underweight  
- -2 to +2 SD: Normal
- > +2 SD: Overweight

### 2. Height-for-Age (HFA) Decision Tree
**Purpose**: Assesses stunting (chronic malnutrition)
**Z-Score Thresholds**:
- < -3 SD: Severely Stunted
- -3 to -2 SD: Stunted
- -2 to +2 SD: Normal Height
- > +2 SD: Tall

### 3. Weight-for-Height (WFH) Decision Tree
**Purpose**: Assesses wasting (acute malnutrition)
**Z-Score Thresholds**:
- < -3 SD: Severely Wasted
- -3 to -2 SD: Wasted
- -2 to +2 SD: Normal Weight
- +2 to +3 SD: Overweight
- > +3 SD: Obese

### 4. BMI-for-Age Decision Tree
**Purpose**: Assesses body composition for children
**Z-Score Thresholds**:
- < -1.645 SD: Underweight
- -1.645 to +1.036 SD: Normal BMI
- +1.036 to +1.645 SD: Overweight
- > +1.645 SD: Obese

### 5. Adult BMI Decision Tree (for ages 18+)
**Purpose**: Assesses adult body composition
**BMI Thresholds**:
- < 18.5: Underweight
- 18.5-24.9: Normal Weight
- 25.0-29.9: Overweight
- ≥ 30.0: Obese

### 6. Risk Assessment Decision Tree
**Purpose**: Provides overall nutritional risk level
**Risk Levels**:
- **Severe**: Any severe indicator present (immediate medical attention)
- **Moderate**: Any moderate indicators present (community support)
- **Low**: All indicators normal (routine care)

## Key Tables in WHO Growth Standards

### 1. Weight-for-Age Tables
- **Boys**: 0-71 months (WHO reference data)
- **Girls**: 0-71 months (WHO reference data)
- **Data Points**: Monthly intervals with median, -3SD, -2SD, -1SD, +1SD, +2SD, +3SD
- **Usage**: Calculate Z-scores for age-appropriate weight assessment

### 2. Height-for-Age Tables
- **Boys**: 0-71 months (WHO reference data)
- **Girls**: 0-71 months (WHO reference data)
- **Data Points**: Monthly intervals with median and standard deviations
- **Usage**: Calculate Z-scores for stunting assessment

### 3. Weight-for-Height Tables
- **Boys**: Height range 45-120 cm
- **Girls**: Height range 45-120 cm
- **Data Points**: Height intervals with weight percentiles
- **Usage**: Calculate Z-scores for wasting assessment

### 4. BMI-for-Age Tables
- **Boys**: 0-71 months
- **Girls**: 0-71 months
- **Data Points**: Age intervals with BMI percentiles
- **Usage**: Calculate Z-scores for body composition assessment

## Z-Score Calculation Process

1. **Input**: Child's measurements (weight, height, age, sex)
2. **Lookup**: Find corresponding WHO reference values from tables
3. **Calculate**: Z-score = (observed value - median) / standard deviation
4. **Classify**: Apply decision tree logic to determine nutritional status
5. **Assess**: Combine all indicators for overall risk assessment

## Decision Tree Algorithm Implementation

```php
class DecisionTreeNode {
    public $condition;    // Function to evaluate
    public $trueChild;    // Node if condition is true
    public $falseChild;   // Node if condition is false
    public $result;       // Final result if leaf node
    public $isLeaf;       // Boolean indicating if this is a leaf
}
```

## Integration with Nutrition App

The WHO Growth Standards are integrated into the nutrition app to:
1. **Screen children** during community health assessments
2. **Calculate appropriate calorie targets** based on nutritional status
3. **Provide recommendations** for nutritional intervention
4. **Track progress** over time with follow-up screenings
5. **Generate reports** for healthcare providers

## Usage Example

```php
$who = new WHOGrowthStandards();
$results = $who->processAllGrowthStandards($weight, $height, $birthDate, $sex);

// Results contain:
// - Weight-for-Age classification
// - Height-for-Age classification  
// - Weight-for-Height classification
// - BMI-for-Age classification
// - Overall risk level
// - Recommendations
```

This system ensures accurate, standardized nutritional assessment following WHO guidelines for child growth monitoring and malnutrition detection.
