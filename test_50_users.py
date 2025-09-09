#!/usr/bin/env python3
"""
Comprehensive 50-user test for nutritional assessment API
This will test all possible malnutrition types, edge cases, and age groups
"""

import math
import random
import sys
from datetime import datetime, timedelta

# WHO reference data (same as before)
WHO_REFERENCE_DATA = {
    'male': {
        'weight_for_height': {
            'height': [65, 70, 75, 80, 85, 90, 95, 100, 105, 110, 115, 120, 125, 130, 135, 140, 145, 150, 155, 160, 165, 170, 175, 180, 185, 190],
            'median': [6.5, 7.3, 8.0, 8.7, 9.3, 9.9, 10.4, 10.9, 11.3, 11.7, 12.1, 12.4, 12.7, 13.0, 13.3, 13.6, 13.9, 14.2, 14.5, 14.8, 15.1, 15.4, 15.7, 16.0, 16.3, 16.6],
            'sd': [0.7, 0.8, 0.8, 0.9, 0.9, 1.0, 1.0, 1.1, 1.1, 1.2, 1.2, 1.3, 1.3, 1.4, 1.4, 1.5, 1.5, 1.6, 1.6, 1.7, 1.7, 1.8, 1.8, 1.9, 1.9, 2.0]
        },
        'height_for_age': {
            'age_months': [0, 1, 2, 3, 6, 9, 12, 18, 24, 30, 36, 42, 48, 54, 60, 66, 72, 78, 84, 90, 96, 102, 108, 114, 120, 126, 132, 138, 144, 150, 156, 162, 168, 174, 180, 186, 192, 198, 204, 210, 216, 222, 228, 234, 240],
            'median': [49.9, 54.7, 58.4, 61.4, 67.6, 72.0, 75.7, 82.5, 87.1, 91.9, 96.1, 99.9, 103.3, 106.7, 109.9, 112.9, 115.7, 118.4, 121.0, 123.5, 125.9, 128.2, 130.4, 132.5, 134.5, 136.4, 138.2, 139.9, 141.5, 143.0, 144.4, 145.7, 146.9, 148.0, 149.0, 149.9, 150.7, 151.4, 152.0, 152.5, 152.9, 153.2, 153.4, 153.5, 153.6],
            'sd': [1.9, 2.1, 2.3, 2.4, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5]
        }
    },
    'female': {
        'weight_for_height': {
            'height': [65, 70, 75, 80, 85, 90, 95, 100, 105, 110, 115, 120, 125, 130, 135, 140, 145, 150, 155, 160, 165, 170, 175, 180, 185, 190],
            'median': [6.0, 6.7, 7.3, 7.9, 8.4, 8.9, 9.3, 9.7, 10.1, 10.4, 10.7, 11.0, 11.3, 11.6, 11.9, 12.2, 12.5, 12.8, 13.1, 13.4, 13.7, 14.0, 14.3, 14.6, 14.9, 15.2],
            'sd': [0.6, 0.7, 0.7, 0.8, 0.8, 0.9, 0.9, 1.0, 1.0, 1.1, 1.1, 1.2, 1.2, 1.3, 1.3, 1.4, 1.4, 1.5, 1.5, 1.6, 1.6, 1.7, 1.7, 1.8, 1.8, 1.9]
        },
        'height_for_age': {
            'age_months': [0, 1, 2, 3, 6, 9, 12, 18, 24, 30, 36, 42, 48, 54, 60, 66, 72, 78, 84, 90, 96, 102, 108, 114, 120, 126, 132, 138, 144, 150, 156, 162, 168, 174, 180, 186, 192, 198, 204, 210, 216, 222, 228, 234, 240],
            'median': [49.1, 53.7, 57.1, 59.8, 65.3, 70.1, 74.0, 80.7, 85.7, 90.4, 94.5, 98.1, 101.3, 104.5, 107.5, 110.3, 112.9, 115.4, 117.7, 119.9, 122.0, 124.0, 125.9, 127.7, 129.4, 131.0, 132.5, 133.9, 135.2, 136.4, 137.5, 138.5, 139.4, 140.2, 140.9, 141.5, 142.0, 142.4, 142.7, 142.9, 143.0, 143.0, 142.9, 142.7, 142.5],
            'sd': [1.9, 2.0, 2.1, 2.2, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3]
        }
    }
}

def calculate_bmi(weight, height):
    """Calculate BMI"""
    if height <= 0:
        return 0
    height_in_meters = height / 100
    return round(weight / (height_in_meters * height_in_meters), 1)

def find_closest_reference(data_list, target_value):
    """Find closest reference value in WHO data"""
    closest_index = 0
    min_diff = abs(target_value - data_list[0])
    
    for i in range(1, len(data_list)):
        diff = abs(target_value - data_list[i])
        if diff < min_diff:
            min_diff = diff
            closest_index = i
    
    return closest_index

def calculate_weight_for_height_zscore(weight, height, age, sex):
    """Calculate Weight-for-Height z-score using WHO standards"""
    age_in_months = age * 12
    
    if age_in_months < 24:
        return calculate_height_for_age_zscore(height, age, sex)
    
    sex_key = sex.lower()
    data = WHO_REFERENCE_DATA[sex_key]['weight_for_height']
    
    closest_index = find_closest_reference(data['height'], height)
    median = data['median'][closest_index]
    sd = data['sd'][closest_index]
    
    z_score = (weight - median) / sd
    return round(z_score, 2)

def calculate_height_for_age_zscore(height, age, sex):
    """Calculate Height-for-Age z-score using WHO standards"""
    age_in_months = age * 12
    
    sex_key = sex.lower()
    data = WHO_REFERENCE_DATA[sex_key]['height_for_age']
    
    closest_index = find_closest_reference(data['age_months'], age_in_months)
    median = data['median'][closest_index]
    sd = data['sd'][closest_index]
    
    z_score = (height - median) / sd
    return round(z_score, 2)

def assess_child_adolescent(age, weight, height, muac, sex):
    """Assess child/adolescent nutritional status"""
    bmi = calculate_bmi(weight, height)
    wh_zscore = calculate_weight_for_height_zscore(weight, height, age, sex)
    ha_zscore = calculate_height_for_age_zscore(height, age, sex)
    
    # Decision tree logic
    if wh_zscore < -3 or (age >= 0.5 and age < 5 and muac < 11.5):
        return "Severe Acute Malnutrition (SAM)"
    elif (wh_zscore < -2 and wh_zscore >= -3) or (age >= 0.5 and age < 5 and muac >= 11.5 and muac < 12.5):
        return "Moderate Acute Malnutrition (MAM)"
    elif (wh_zscore < -1 and wh_zscore >= -2) or (age >= 0.5 and age < 5 and muac >= 12.5 and muac < 13.5):
        return "Mild Acute Malnutrition (Wasting)"
    elif ha_zscore < -2:
        return "Stunting (Chronic Malnutrition)"
    else:
        return "Normal"

def assess_pregnant_woman(muac, weight):
    """Assess pregnant woman nutritional status"""
    if muac < 23.0:
        return "Maternal Undernutrition (At-risk)"
    elif muac >= 23.0 and muac < 25.0:
        return "Maternal At-risk"
    else:
        return "Normal"

def assess_adult_elderly(weight, height, muac):
    """Assess adult/elderly nutritional status"""
    bmi = calculate_bmi(weight, height)
    
    if bmi < 16.0:
        return "Severe Underweight"
    elif bmi >= 16.0 and bmi < 17.0:
        return "Moderate Underweight"
    elif bmi >= 17.0 and bmi < 18.5:
        return "Mild Underweight"
    elif bmi >= 18.5 and bmi < 25.0:
        return "Normal"
    elif bmi >= 25.0 and bmi < 30.0:
        return "Overweight"
    elif bmi >= 30.0 and bmi < 35.0:
        return "Obesity Class I"
    elif bmi >= 35.0 and bmi < 40.0:
        return "Obesity Class II"
    else:
        return "Obesity Class III (Severe)"

def generate_test_cases():
    """Generate 50 diverse test cases covering all malnutrition types"""
    test_cases = []
    
    # CHILDREN (20 cases) - Various ages and conditions
    children_cases = [
        # SAM cases (5)
        {'age': 1, 'weight': 6.5, 'height': 70, 'muac': 10.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Severe Acute Malnutrition (SAM)'},
        {'age': 2, 'weight': 8.0, 'height': 80, 'muac': 10.5, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Severe Acute Malnutrition (SAM)'},
        {'age': 3, 'weight': 9.5, 'height': 90, 'muac': 11.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Severe Acute Malnutrition (SAM)'},
        {'age': 4, 'weight': 11.0, 'height': 95, 'muac': 11.2, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Severe Acute Malnutrition (SAM)'},
        {'age': 1.5, 'weight': 7.0, 'height': 75, 'muac': 10.8, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Severe Acute Malnutrition (SAM)'},
        
        # MAM cases (5)
        {'age': 2, 'weight': 9.5, 'height': 85, 'muac': 12.0, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Moderate Acute Malnutrition (MAM)'},
        {'age': 3, 'weight': 11.5, 'height': 95, 'muac': 12.2, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Moderate Acute Malnutrition (MAM)'},
        {'age': 4, 'weight': 13.0, 'height': 100, 'muac': 12.4, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Moderate Acute Malnutrition (MAM)'},
        {'age': 2.5, 'weight': 10.0, 'height': 88, 'muac': 12.1, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Moderate Acute Malnutrition (MAM)'},
        {'age': 3.5, 'weight': 12.5, 'height': 98, 'muac': 12.3, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Moderate Acute Malnutrition (MAM)'},
        
        # Stunting cases (5)
        {'age': 5, 'weight': 16.0, 'height': 95, 'muac': 13.5, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Stunting (Chronic Malnutrition)'},
        {'age': 6, 'weight': 18.0, 'height': 100, 'muac': 14.0, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Stunting (Chronic Malnutrition)'},
        {'age': 7, 'weight': 20.0, 'height': 105, 'muac': 14.5, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Stunting (Chronic Malnutrition)'},
        {'age': 8, 'weight': 22.0, 'height': 110, 'muac': 15.0, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Stunting (Chronic Malnutrition)'},
        {'age': 9, 'weight': 24.0, 'height': 115, 'muac': 15.5, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Stunting (Chronic Malnutrition)'},
        
        # Normal children (5)
        {'age': 5, 'weight': 18.0, 'height': 110, 'muac': 14.0, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Normal'},
        {'age': 6, 'weight': 20.0, 'height': 115, 'muac': 14.5, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Normal'},
        {'age': 7, 'weight': 22.0, 'height': 120, 'muac': 15.0, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Normal'},
        {'age': 8, 'weight': 25.0, 'height': 125, 'muac': 15.5, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Normal'},
        {'age': 9, 'weight': 28.0, 'height': 130, 'muac': 16.0, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Normal'},
    ]
    
    # PREGNANT WOMEN (10 cases)
    pregnant_cases = [
        # Maternal Undernutrition (3)
        {'age': 25, 'weight': 40.0, 'height': 155, 'muac': 21.5, 'sex': 'female', 'is_pregnant': 'Yes', 'expected': 'Maternal Undernutrition (At-risk)'},
        {'age': 28, 'weight': 42.0, 'height': 160, 'muac': 22.0, 'sex': 'female', 'is_pregnant': 'Yes', 'expected': 'Maternal Undernutrition (At-risk)'},
        {'age': 30, 'weight': 45.0, 'height': 165, 'muac': 22.5, 'sex': 'female', 'is_pregnant': 'Yes', 'expected': 'Maternal Undernutrition (At-risk)'},
        
        # Maternal At-risk (3)
        {'age': 26, 'weight': 48.0, 'height': 160, 'muac': 23.5, 'sex': 'female', 'is_pregnant': 'Yes', 'expected': 'Maternal At-risk'},
        {'age': 29, 'weight': 50.0, 'height': 165, 'muac': 24.0, 'sex': 'female', 'is_pregnant': 'Yes', 'expected': 'Maternal At-risk'},
        {'age': 32, 'weight': 52.0, 'height': 170, 'muac': 24.5, 'sex': 'female', 'is_pregnant': 'Yes', 'expected': 'Maternal At-risk'},
        
        # Normal Pregnant (4)
        {'age': 24, 'weight': 55.0, 'height': 160, 'muac': 25.5, 'sex': 'female', 'is_pregnant': 'Yes', 'expected': 'Normal'},
        {'age': 27, 'weight': 58.0, 'height': 165, 'muac': 26.0, 'sex': 'female', 'is_pregnant': 'Yes', 'expected': 'Normal'},
        {'age': 31, 'weight': 62.0, 'height': 170, 'muac': 26.5, 'sex': 'female', 'is_pregnant': 'Yes', 'expected': 'Normal'},
        {'age': 35, 'weight': 65.0, 'height': 175, 'muac': 27.0, 'sex': 'female', 'is_pregnant': 'Yes', 'expected': 'Normal'},
    ]
    
    # ADULTS (20 cases) - Various BMI categories
    adult_cases = [
        # Severe Underweight (3)
        {'age': 35, 'weight': 35.0, 'height': 170, 'muac': 18.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Severe Underweight'},
        {'age': 40, 'weight': 38.0, 'height': 175, 'muac': 19.0, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Severe Underweight'},
        {'age': 45, 'weight': 42.0, 'height': 180, 'muac': 20.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Severe Underweight'},
        
        # Moderate Underweight (3) - BMI 16.0-16.9
        {'age': 30, 'weight': 46.2, 'height': 170, 'muac': 20.5, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Moderate Underweight'},  # BMI = 16.0
        {'age': 38, 'weight': 51.5, 'height': 175, 'muac': 21.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Moderate Underweight'},  # BMI = 16.8
        {'age': 42, 'weight': 54.0, 'height': 180, 'muac': 21.5, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Moderate Underweight'},  # BMI = 16.7
        
        # Mild Underweight (3)
        {'age': 32, 'weight': 52.0, 'height': 170, 'muac': 22.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Mild Underweight'},
        {'age': 36, 'weight': 55.0, 'height': 175, 'muac': 22.5, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Mild Underweight'},
        {'age': 44, 'weight': 58.0, 'height': 180, 'muac': 23.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Mild Underweight'},
        
        # Normal (4)
        {'age': 25, 'weight': 65.0, 'height': 170, 'muac': 25.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Normal'},
        {'age': 30, 'weight': 60.0, 'height': 165, 'muac': 24.0, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Normal'},
        {'age': 40, 'weight': 70.0, 'height': 175, 'muac': 26.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Normal'},
        {'age': 50, 'weight': 65.0, 'height': 170, 'muac': 25.5, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Normal'},
        
        # Overweight (3)
        {'age': 28, 'weight': 80.0, 'height': 170, 'muac': 28.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Overweight'},
        {'age': 35, 'weight': 75.0, 'height': 165, 'muac': 27.0, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Overweight'},
        {'age': 45, 'weight': 85.0, 'height': 175, 'muac': 29.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Overweight'},
        
        # Obesity Class I (2)
        {'age': 33, 'weight': 95.0, 'height': 170, 'muac': 32.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Obesity Class I'},
        {'age': 37, 'weight': 90.0, 'height': 165, 'muac': 31.0, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Obesity Class I'},
        
        # Obesity Class II (1)
        {'age': 41, 'weight': 110.0, 'height': 170, 'muac': 35.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Obesity Class II'},
        
        # Obesity Class III (1)
        {'age': 39, 'weight': 130.0, 'height': 170, 'muac': 40.0, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Obesity Class III (Severe)'},
    ]
    
    test_cases = children_cases + pregnant_cases + adult_cases
    return test_cases

def test_50_users():
    """Test 50 different users with comprehensive coverage"""
    print("🧪 COMPREHENSIVE 50-USER NUTRITIONAL ASSESSMENT TEST")
    print("=" * 70)
    
    test_cases = generate_test_cases()
    total_tests = len(test_cases)
    passed = 0
    failed = 0
    
    # Track results by category
    categories = {
        'Children': {'total': 0, 'passed': 0, 'failed': 0},
        'Pregnant Women': {'total': 0, 'passed': 0, 'failed': 0},
        'Adults': {'total': 0, 'passed': 0, 'failed': 0}
    }
    
    print(f"Testing {total_tests} users across all malnutrition types...\n")
    
    for i, test in enumerate(test_cases, 1):
        # Determine category
        if test['age'] < 18:
            category = 'Children'
        elif test['is_pregnant'] == 'Yes':
            category = 'Pregnant Women'
        else:
            category = 'Adults'
        
        categories[category]['total'] += 1
        
        print(f"{i:2d}. {test['age']}yo {test['sex']} - {category}")
        print(f"    Weight: {test['weight']}kg, Height: {test['height']}cm, MUAC: {test['muac']}cm")
        print(f"    Expected: {test['expected']}")
        
        # Perform assessment
        try:
            if test['age'] < 18:
                result = assess_child_adolescent(test['age'], test['weight'], test['height'], test['muac'], test['sex'])
            elif test['is_pregnant'] == 'Yes':
                result = assess_pregnant_woman(test['muac'], test['weight'])
            else:
                result = assess_adult_elderly(test['weight'], test['height'], test['muac'])
            
            # Check if correct
            is_correct = result == test['expected']
            
            if is_correct:
                passed += 1
                categories[category]['passed'] += 1
                print(f"    Result: {result} ✅")
            else:
                failed += 1
                categories[category]['failed'] += 1
                print(f"    Result: {result} ❌")
                print(f"    Expected: {test['expected']}")
            
        except Exception as e:
            failed += 1
            categories[category]['failed'] += 1
            print(f"    Error: {str(e)} ❌")
        
        print()
    
    # Print detailed results
    print("📊 DETAILED RESULTS BY CATEGORY")
    print("=" * 50)
    
    for category, stats in categories.items():
        if stats['total'] > 0:
            success_rate = (stats['passed'] / stats['total']) * 100
            print(f"{category}: {stats['passed']}/{stats['total']} ({success_rate:.1f}%)")
    
    print("\n📈 OVERALL SUMMARY")
    print("=" * 30)
    print(f"Total Tests: {total_tests}")
    print(f"Passed: {passed}")
    print(f"Failed: {failed}")
    print(f"Success Rate: {(passed/total_tests)*100:.1f}%")
    
    if passed == total_tests:
        print("\n🎉 PERFECT SCORE! All 50 tests passed!")
        print("The API is 100% accurate across all malnutrition types!")
    elif passed >= total_tests * 0.95:
        print(f"\n✅ EXCELLENT! {passed}/{total_tests} tests passed!")
        print("The API is highly accurate with only minor issues.")
    elif passed >= total_tests * 0.90:
        print(f"\n✅ GOOD! {passed}/{total_tests} tests passed!")
        print("The API is mostly accurate with some issues to address.")
    else:
        print(f"\n⚠️ NEEDS IMPROVEMENT! {passed}/{total_tests} tests passed!")
        print("The API has significant accuracy issues that need to be fixed.")
    
    return passed == total_tests

if __name__ == "__main__":
    success = test_50_users()
    sys.exit(0 if success else 1)
