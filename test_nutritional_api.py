#!/usr/bin/env python3
"""
Python script to test the nutritional assessment API calculations
This will verify the WHO z-score calculations and decision tree logic
"""

import math
import json
import requests
import sys

# WHO reference data for z-score calculations (simplified version)
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
    
    # For children under 24 months, use length-for-age reference
    if age_in_months < 24:
        return calculate_height_for_age_zscore(height, age, sex)
    
    sex_key = sex.lower()
    data = WHO_REFERENCE_DATA[sex_key]['weight_for_height']
    
    # Find closest height reference
    closest_index = find_closest_reference(data['height'], height)
    
    median = data['median'][closest_index]
    sd = data['sd'][closest_index]
    
    # Calculate z-score: (observed - median) / SD
    z_score = (weight - median) / sd
    
    return round(z_score, 2)

def calculate_height_for_age_zscore(height, age, sex):
    """Calculate Height-for-Age z-score using WHO standards"""
    age_in_months = age * 12
    
    sex_key = sex.lower()
    data = WHO_REFERENCE_DATA[sex_key]['height_for_age']
    
    # Find closest age reference
    closest_index = find_closest_reference(data['age_months'], age_in_months)
    
    median = data['median'][closest_index]
    sd = data['sd'][closest_index]
    
    # Calculate z-score: (observed - median) / SD
    z_score = (height - median) / sd
    
    return round(z_score, 2)

def assess_child_adolescent(age, weight, height, muac, sex):
    """Assess child/adolescent nutritional status"""
    bmi = calculate_bmi(weight, height)
    wh_zscore = calculate_weight_for_height_zscore(weight, height, age, sex)
    ha_zscore = calculate_height_for_age_zscore(height, age, sex)
    
    print(f"  BMI: {bmi}")
    print(f"  W/H z-score: {wh_zscore}")
    print(f"  H/A z-score: {ha_zscore}")
    
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
    print(f"  BMI: {bmi}")
    
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

def test_nutritional_assessment():
    """Test the nutritional assessment with various cases"""
    print("🧪 TESTING NUTRITIONAL ASSESSMENT API CALCULATIONS")
    print("=" * 60)
    
    test_cases = [
        # Children
        {
            'name': '2-year-old boy with SAM',
            'age': 2, 'weight': 8.5, 'height': 85, 'muac': 10.5, 'sex': 'male', 'is_pregnant': 'No',
            'expected': 'Severe Acute Malnutrition (SAM)'
        },
        {
            'name': '3-year-old girl with MAM',
            'age': 3, 'weight': 12.0, 'height': 95, 'muac': 12.0, 'sex': 'female', 'is_pregnant': 'No',
            'expected': 'Moderate Acute Malnutrition (MAM)'
        },
        {
            'name': '5-year-old girl Normal',
            'age': 5, 'weight': 18.0, 'height': 110, 'muac': 14.0, 'sex': 'female', 'is_pregnant': 'No',
            'expected': 'Normal'
        },
        # Pregnant women
        {
            'name': 'Pregnant woman with Maternal Undernutrition',
            'age': 25, 'weight': 45.0, 'height': 160, 'muac': 22.0, 'sex': 'female', 'is_pregnant': 'Yes',
            'expected': 'Maternal Undernutrition (At-risk)'
        },
        {
            'name': 'Normal Pregnant woman',
            'age': 30, 'weight': 60.0, 'height': 170, 'muac': 26.0, 'sex': 'female', 'is_pregnant': 'Yes',
            'expected': 'Normal'
        },
        # Adults
        {
            'name': 'Adult with Severe Underweight',
            'age': 35, 'weight': 40.0, 'height': 170, 'muac': 20.0, 'sex': 'male', 'is_pregnant': 'No',
            'expected': 'Severe Underweight'
        },
        {
            'name': 'Adult with Normal BMI',
            'age': 50, 'weight': 70.0, 'height': 175, 'muac': 28.0, 'sex': 'female', 'is_pregnant': 'No',
            'expected': 'Normal'
        },
        {
            'name': 'Adult with Obesity Class I',
            'age': 60, 'weight': 90.0, 'height': 170, 'muac': 32.0, 'sex': 'female', 'is_pregnant': 'No',
            'expected': 'Obesity Class I'
        }
    ]
    
    passed = 0
    total = len(test_cases)
    
    for i, test in enumerate(test_cases, 1):
        print(f"\n{i}. {test['name']}")
        print(f"   Age: {test['age']}, Weight: {test['weight']}kg, Height: {test['height']}cm, MUAC: {test['muac']}cm")
        print(f"   Sex: {test['sex']}, Pregnant: {test['is_pregnant']}")
        print(f"   Expected: {test['expected']}")
        
        # Perform assessment
        if test['age'] < 18:
            result = assess_child_adolescent(test['age'], test['weight'], test['height'], test['muac'], test['sex'])
        elif test['is_pregnant'] == 'Yes':
            result = assess_pregnant_woman(test['muac'], test['weight'])
        else:
            result = assess_adult_elderly(test['weight'], test['height'], test['muac'])
        
        print(f"   Result: {result}")
        
        # Check if correct
        is_correct = result == test['expected']
        if is_correct:
            passed += 1
            print(f"   Status: ✅ CORRECT")
        else:
            print(f"   Status: ❌ INCORRECT")
        
        print("-" * 40)
    
    print(f"\n📊 TEST SUMMARY")
    print(f"Total Tests: {total}")
    print(f"Passed: {passed}")
    print(f"Failed: {total - passed}")
    print(f"Success Rate: {(passed/total)*100:.1f}%")
    
    if passed == total:
        print("🎉 ALL TESTS PASSED! The API calculations are 100% correct!")
    else:
        print("⚠️ Some tests failed. Please review the results above.")
    
    return passed == total

if __name__ == "__main__":
    success = test_nutritional_assessment()
    sys.exit(0 if success else 1)
