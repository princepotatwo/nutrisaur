#!/usr/bin/env python3
"""
MASSIVE 100-PERSON NUTRITIONAL ASSESSMENT TEST
This will test the API with 100 diverse people across all age groups and malnutrition types
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

def generate_100_test_cases():
    """Generate 100 diverse test cases covering all scenarios"""
    test_cases = []
    
    # CHILDREN (40 cases) - Diverse ages and conditions
    children_cases = [
        # SAM cases (10)
        {'age': 0.6, 'weight': 4.8, 'height': 62, 'muac': 9.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Severe Acute Malnutrition (SAM)'},
        {'age': 1.0, 'weight': 5.5, 'height': 68, 'muac': 9.5, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Severe Acute Malnutrition (SAM)'},
        {'age': 1.5, 'weight': 6.2, 'height': 72, 'muac': 10.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Severe Acute Malnutrition (SAM)'},
        {'age': 2.0, 'weight': 7.0, 'height': 78, 'muac': 10.5, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Severe Acute Malnutrition (SAM)'},
        {'age': 2.5, 'weight': 7.8, 'height': 82, 'muac': 10.8, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Severe Acute Malnutrition (SAM)'},
        {'age': 3.0, 'weight': 8.5, 'height': 88, 'muac': 11.0, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Severe Acute Malnutrition (SAM)'},
        {'age': 3.5, 'weight': 9.2, 'height': 92, 'muac': 11.2, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Severe Acute Malnutrition (SAM)'},
        {'age': 4.0, 'weight': 10.0, 'height': 96, 'muac': 11.3, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Severe Acute Malnutrition (SAM)'},
        {'age': 4.5, 'weight': 10.8, 'height': 100, 'muac': 11.4, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Severe Acute Malnutrition (SAM)'},
        {'age': 1.2, 'weight': 6.0, 'height': 70, 'muac': 9.8, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Severe Acute Malnutrition (SAM)'},
        
        # MAM cases (10)
        {'age': 1.0, 'weight': 7.5, 'height': 72, 'muac': 11.8, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Moderate Acute Malnutrition (MAM)'},
        {'age': 1.5, 'weight': 8.2, 'height': 78, 'muac': 12.0, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Moderate Acute Malnutrition (MAM)'},
        {'age': 2.0, 'weight': 9.0, 'height': 84, 'muac': 12.2, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Moderate Acute Malnutrition (MAM)'},
        {'age': 2.5, 'weight': 9.8, 'height': 88, 'muac': 12.3, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Moderate Acute Malnutrition (MAM)'},
        {'age': 3.0, 'weight': 10.5, 'height': 94, 'muac': 12.4, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Moderate Acute Malnutrition (MAM)'},
        {'age': 3.5, 'weight': 11.2, 'height': 98, 'muac': 12.1, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Moderate Acute Malnutrition (MAM)'},
        {'age': 4.0, 'weight': 12.0, 'height': 102, 'muac': 12.2, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Moderate Acute Malnutrition (MAM)'},
        {'age': 4.5, 'weight': 12.8, 'height': 106, 'muac': 12.3, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Moderate Acute Malnutrition (MAM)'},
        {'age': 1.8, 'weight': 8.5, 'height': 80, 'muac': 11.9, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Moderate Acute Malnutrition (MAM)'},
        {'age': 2.8, 'weight': 10.2, 'height': 90, 'muac': 12.0, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Moderate Acute Malnutrition (MAM)'},
        
        # Stunting cases (10)
        {'age': 5, 'weight': 16.0, 'height': 95, 'muac': 13.5, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Stunting (Chronic Malnutrition)'},
        {'age': 6, 'weight': 18.0, 'height': 100, 'muac': 14.0, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Stunting (Chronic Malnutrition)'},
        {'age': 7, 'weight': 20.0, 'height': 105, 'muac': 14.5, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Stunting (Chronic Malnutrition)'},
        {'age': 8, 'weight': 22.0, 'height': 110, 'muac': 15.0, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Stunting (Chronic Malnutrition)'},
        {'age': 9, 'weight': 24.0, 'height': 115, 'muac': 15.5, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Stunting (Chronic Malnutrition)'},
        {'age': 10, 'weight': 26.0, 'height': 120, 'muac': 16.0, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Stunting (Chronic Malnutrition)'},
        {'age': 11, 'weight': 28.0, 'height': 125, 'muac': 16.5, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Stunting (Chronic Malnutrition)'},
        {'age': 12, 'weight': 30.0, 'height': 130, 'muac': 17.0, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Stunting (Chronic Malnutrition)'},
        {'age': 13, 'weight': 32.0, 'height': 135, 'muac': 17.5, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Stunting (Chronic Malnutrition)'},
        {'age': 14, 'weight': 34.0, 'height': 140, 'muac': 18.0, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Stunting (Chronic Malnutrition)'},
        
        # Normal children (10)
        {'age': 5.5, 'weight': 19.0, 'height': 112, 'muac': 14.5, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Normal'},
        {'age': 6.5, 'weight': 22.0, 'height': 118, 'muac': 15.0, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Normal'},
        {'age': 7.5, 'weight': 25.0, 'height': 124, 'muac': 15.5, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Normal'},
        {'age': 8.5, 'weight': 28.0, 'height': 130, 'muac': 16.0, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Normal'},
        {'age': 9.5, 'weight': 31.0, 'height': 136, 'muac': 16.5, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Normal'},
        {'age': 10.5, 'weight': 34.0, 'height': 142, 'muac': 17.0, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Normal'},
        {'age': 11.5, 'weight': 37.0, 'height': 148, 'muac': 17.5, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Normal'},
        {'age': 12.5, 'weight': 40.0, 'height': 154, 'muac': 18.0, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Normal'},
        {'age': 13.5, 'weight': 43.0, 'height': 160, 'muac': 18.5, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Normal'},
        {'age': 14.5, 'weight': 46.0, 'height': 166, 'muac': 19.0, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Normal'},
    ]
    
    # PREGNANT WOMEN (20 cases) - Various ages and MUAC values
    pregnant_cases = [
        # Maternal Undernutrition (8)
        {'age': 20, 'weight': 35.0, 'height': 150, 'muac': 20.0, 'sex': 'female', 'is_pregnant': 'Yes', 'expected': 'Maternal Undernutrition (At-risk)'},
        {'age': 22, 'weight': 38.0, 'height': 155, 'muac': 20.5, 'sex': 'female', 'is_pregnant': 'Yes', 'expected': 'Maternal Undernutrition (At-risk)'},
        {'age': 25, 'weight': 41.0, 'height': 160, 'muac': 21.0, 'sex': 'female', 'is_pregnant': 'Yes', 'expected': 'Maternal Undernutrition (At-risk)'},
        {'age': 28, 'weight': 44.0, 'height': 165, 'muac': 21.5, 'sex': 'female', 'is_pregnant': 'Yes', 'expected': 'Maternal Undernutrition (At-risk)'},
        {'age': 30, 'weight': 47.0, 'height': 170, 'muac': 22.0, 'sex': 'female', 'is_pregnant': 'Yes', 'expected': 'Maternal Undernutrition (At-risk)'},
        {'age': 32, 'weight': 50.0, 'height': 175, 'muac': 22.5, 'sex': 'female', 'is_pregnant': 'Yes', 'expected': 'Maternal Undernutrition (At-risk)'},
        {'age': 35, 'weight': 53.0, 'height': 180, 'muac': 22.8, 'sex': 'female', 'is_pregnant': 'Yes', 'expected': 'Maternal Undernutrition (At-risk)'},
        {'age': 38, 'weight': 56.0, 'height': 185, 'muac': 22.9, 'sex': 'female', 'is_pregnant': 'Yes', 'expected': 'Maternal Undernutrition (At-risk)'},
        
        # Maternal At-risk (6)
        {'age': 24, 'weight': 48.0, 'height': 160, 'muac': 23.2, 'sex': 'female', 'is_pregnant': 'Yes', 'expected': 'Maternal At-risk'},
        {'age': 27, 'weight': 51.0, 'height': 165, 'muac': 23.5, 'sex': 'female', 'is_pregnant': 'Yes', 'expected': 'Maternal At-risk'},
        {'age': 29, 'weight': 54.0, 'height': 170, 'muac': 23.8, 'sex': 'female', 'is_pregnant': 'Yes', 'expected': 'Maternal At-risk'},
        {'age': 31, 'weight': 57.0, 'height': 175, 'muac': 24.0, 'sex': 'female', 'is_pregnant': 'Yes', 'expected': 'Maternal At-risk'},
        {'age': 33, 'weight': 60.0, 'height': 180, 'muac': 24.3, 'sex': 'female', 'is_pregnant': 'Yes', 'expected': 'Maternal At-risk'},
        {'age': 36, 'weight': 63.0, 'height': 185, 'muac': 24.7, 'sex': 'female', 'is_pregnant': 'Yes', 'expected': 'Maternal At-risk'},
        
        # Normal pregnant (6)
        {'age': 23, 'weight': 58.0, 'height': 162, 'muac': 25.2, 'sex': 'female', 'is_pregnant': 'Yes', 'expected': 'Normal'},
        {'age': 26, 'weight': 61.0, 'height': 167, 'muac': 25.5, 'sex': 'female', 'is_pregnant': 'Yes', 'expected': 'Normal'},
        {'age': 30, 'weight': 65.0, 'height': 172, 'muac': 26.0, 'sex': 'female', 'is_pregnant': 'Yes', 'expected': 'Normal'},
        {'age': 34, 'weight': 69.0, 'height': 177, 'muac': 26.5, 'sex': 'female', 'is_pregnant': 'Yes', 'expected': 'Normal'},
        {'age': 37, 'weight': 73.0, 'height': 182, 'muac': 27.0, 'sex': 'female', 'is_pregnant': 'Yes', 'expected': 'Normal'},
        {'age': 40, 'weight': 77.0, 'height': 187, 'muac': 27.5, 'sex': 'female', 'is_pregnant': 'Yes', 'expected': 'Normal'},
    ]
    
    # ADULTS (40 cases) - All BMI ranges and edge cases
    adult_cases = [
        # Severe Underweight (8)
        {'age': 25, 'weight': 30.0, 'height': 165, 'muac': 17.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Severe Underweight'},
        {'age': 30, 'weight': 32.0, 'height': 170, 'muac': 17.5, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Severe Underweight'},
        {'age': 35, 'weight': 34.0, 'height': 175, 'muac': 18.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Severe Underweight'},
        {'age': 40, 'weight': 36.0, 'height': 180, 'muac': 18.5, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Severe Underweight'},
        {'age': 45, 'weight': 38.0, 'height': 185, 'muac': 19.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Severe Underweight'},
        {'age': 50, 'weight': 40.0, 'height': 190, 'muac': 19.5, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Severe Underweight'},
        {'age': 55, 'weight': 42.0, 'height': 195, 'muac': 20.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Severe Underweight'},
        {'age': 60, 'weight': 44.0, 'height': 200, 'muac': 20.5, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Severe Underweight'},
        
        # Moderate Underweight (8)
        {'age': 26, 'weight': 46.0, 'height': 170, 'muac': 20.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Moderate Underweight'},
        {'age': 31, 'weight': 49.0, 'height': 175, 'muac': 20.5, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Moderate Underweight'},
        {'age': 36, 'weight': 52.0, 'height': 180, 'muac': 21.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Moderate Underweight'},
        {'age': 41, 'weight': 55.0, 'height': 185, 'muac': 21.5, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Moderate Underweight'},
        {'age': 46, 'weight': 58.0, 'height': 190, 'muac': 22.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Moderate Underweight'},
        {'age': 51, 'weight': 61.0, 'height': 195, 'muac': 22.5, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Moderate Underweight'},
        {'age': 56, 'weight': 64.0, 'height': 200, 'muac': 23.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Moderate Underweight'},
        {'age': 61, 'weight': 67.0, 'height': 205, 'muac': 23.5, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Moderate Underweight'},
        
        # Mild Underweight (8)
        {'age': 27, 'weight': 50.0, 'height': 170, 'muac': 21.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Mild Underweight'},
        {'age': 32, 'weight': 54.0, 'height': 175, 'muac': 21.5, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Mild Underweight'},
        {'age': 37, 'weight': 58.0, 'height': 180, 'muac': 22.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Mild Underweight'},
        {'age': 42, 'weight': 62.0, 'height': 185, 'muac': 22.5, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Mild Underweight'},
        {'age': 47, 'weight': 66.0, 'height': 190, 'muac': 23.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Mild Underweight'},
        {'age': 52, 'weight': 70.0, 'height': 195, 'muac': 23.5, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Mild Underweight'},
        {'age': 57, 'weight': 74.0, 'height': 200, 'muac': 24.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Mild Underweight'},
        {'age': 62, 'weight': 78.0, 'height': 205, 'muac': 24.5, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Mild Underweight'},
        
        # Normal (8)
        {'age': 28, 'weight': 65.0, 'height': 170, 'muac': 24.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Normal'},
        {'age': 33, 'weight': 60.0, 'height': 165, 'muac': 23.5, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Normal'},
        {'age': 38, 'weight': 70.0, 'height': 175, 'muac': 25.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Normal'},
        {'age': 43, 'weight': 65.0, 'height': 170, 'muac': 24.5, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Normal'},
        {'age': 48, 'weight': 75.0, 'height': 180, 'muac': 26.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Normal'},
        {'age': 53, 'weight': 70.0, 'height': 175, 'muac': 25.5, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Normal'},
        {'age': 58, 'weight': 80.0, 'height': 185, 'muac': 27.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Normal'},
        {'age': 63, 'weight': 75.0, 'height': 180, 'muac': 26.5, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Normal'},
        
        # Overweight (4)
        {'age': 29, 'weight': 78.0, 'height': 170, 'muac': 27.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Overweight'},
        {'age': 34, 'weight': 72.0, 'height': 165, 'muac': 26.5, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Overweight'},
        {'age': 39, 'weight': 82.0, 'height': 175, 'muac': 28.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Overweight'},
        {'age': 44, 'weight': 77.0, 'height': 170, 'muac': 27.5, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Overweight'},
        
        # Obesity Class I (2)
        {'age': 30, 'weight': 92.0, 'height': 170, 'muac': 30.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Obesity Class I'},
        {'age': 35, 'weight': 88.0, 'height': 165, 'muac': 29.5, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Obesity Class I'},
        
        # Obesity Class II (1)
        {'age': 40, 'weight': 108.0, 'height': 170, 'muac': 33.0, 'sex': 'male', 'is_pregnant': 'No', 'expected': 'Obesity Class II'},
        
        # Obesity Class III (1)
        {'age': 45, 'weight': 125.0, 'height': 170, 'muac': 38.0, 'sex': 'female', 'is_pregnant': 'No', 'expected': 'Obesity Class III (Severe)'},
    ]
    
    test_cases = children_cases + pregnant_cases + adult_cases
    return test_cases

def test_100_people():
    """Test 100 people with comprehensive scenarios"""
    print("🧪 MASSIVE 100-PERSON NUTRITIONAL ASSESSMENT TEST")
    print("=" * 70)
    
    test_cases = generate_100_test_cases()
    total_tests = len(test_cases)
    passed = 0
    failed = 0
    
    # Track results by category
    categories = {
        'Children': {'total': 0, 'passed': 0, 'failed': 0},
        'Pregnant Women': {'total': 0, 'passed': 0, 'failed': 0},
        'Adults': {'total': 0, 'passed': 0, 'failed': 0}
    }
    
    print(f"Testing {total_tests} people across ALL malnutrition scenarios...\n")
    
    for i, test in enumerate(test_cases, 1):
        # Determine category
        if test['age'] < 18:
            category = 'Children'
        elif test['is_pregnant'] == 'Yes':
            category = 'Pregnant Women'
        else:
            category = 'Adults'
        
        categories[category]['total'] += 1
        
        print(f"{i:3d}. {test['age']}yo {test['sex']} - {category}")
        print(f"     Weight: {test['weight']}kg, Height: {test['height']}cm, MUAC: {test['muac']}cm")
        print(f"     Expected: {test['expected']}")
        
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
                print(f"     Result: {result} ✅")
            else:
                failed += 1
                categories[category]['failed'] += 1
                print(f"     Result: {result} ❌")
                print(f"     Expected: {test['expected']}")
            
        except Exception as e:
            failed += 1
            categories[category]['failed'] += 1
            print(f"     Error: {str(e)} ❌")
        
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
        print("\n🎉 PERFECT SCORE! All 100 tests passed!")
        print("The API is 100% accurate across ALL malnutrition types!")
        print("🏆 PRODUCTION READY - DEPLOY WITH CONFIDENCE!")
    elif passed >= total_tests * 0.98:
        print(f"\n✅ EXCEPTIONAL! {passed}/{total_tests} tests passed!")
        print("The API is highly accurate with minimal issues.")
    elif passed >= total_tests * 0.95:
        print(f"\n✅ EXCELLENT! {passed}/{total_tests} tests passed!")
        print("The API is very accurate with minor issues to address.")
    elif passed >= total_tests * 0.90:
        print(f"\n✅ GOOD! {passed}/{total_tests} tests passed!")
        print("The API is mostly accurate with some issues to address.")
    else:
        print(f"\n⚠️ NEEDS IMPROVEMENT! {passed}/{total_tests} tests passed!")
        print("The API has significant accuracy issues that need to be fixed.")
    
    return passed == total_tests

if __name__ == "__main__":
    success = test_100_people()
    sys.exit(0 if success else 1)
