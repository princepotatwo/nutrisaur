#!/usr/bin/env python3
"""
Analyze BMI test users to show expected classifications for both BMI-for-Age and BMI Adult
"""

import csv
from datetime import datetime, date

def calculate_age_in_months(birthday_str, screening_date_str):
    """Calculate age in months"""
    birthday = datetime.strptime(birthday_str, '%Y-%m-%d').date()
    screening_date = datetime.strptime(screening_date_str, '%Y-%m-%d %H:%M:%S').date()
    
    age_years = screening_date.year - birthday.year
    age_months = screening_date.month - birthday.month
    total_months = age_years * 12 + age_months
    
    return total_months

def calculate_bmi(weight_kg, height_cm):
    """Calculate BMI"""
    height_m = height_cm / 100
    return weight_kg / (height_m ** 2)

def get_adult_bmi_classification(bmi):
    """Get adult BMI classification"""
    if bmi < 18.5:
        return "Underweight"
    elif bmi < 25:
        return "Normal"
    elif bmi < 30:
        return "Overweight"
    else:
        return "Obese"

def get_bmi_for_age_classification(bmi, age_months, sex):
    """Get BMI-for-Age classification (simplified - actual uses WHO standards)"""
    # This is a simplified version - the actual system uses WHO growth standards
    # For demonstration purposes, we'll use approximate ranges
    if age_months < 24:
        return "Not applicable (under 2 years)"
    elif age_months >= 228:
        return "Not applicable (19+ years - use BMI Adult)"
    else:
        # Simplified classification for 2-18 years
        if bmi < 16:
            return "Severely Underweight"
        elif bmi < 18.5:
            return "Underweight"
        elif bmi < 25:
            return "Normal"
        elif bmi < 30:
            return "Overweight"
        else:
            return "Obese"

def analyze_users():
    """Analyze the test users"""
    print("BMI Test Users Analysis")
    print("=" * 80)
    print()
    
    with open('bmi_test_users.csv', 'r') as file:
        reader = csv.DictReader(file)
        
        for i, user in enumerate(reader, 1):
            name = user['name']
            birthday = user['birthday']
            screening_date = user['screening_date']
            weight = float(user['weight'])
            height = float(user['height'])
            sex = user['sex']
            
            age_months = calculate_age_in_months(birthday, screening_date)
            age_years = age_months / 12
            bmi = calculate_bmi(weight, height)
            
            # Determine eligibility
            bmi_for_age_eligible = 24 <= age_months < 228  # 2-18 years
            bmi_adult_eligible = age_months >= 228  # 19+ years
            
            # Get classifications
            bmi_for_age_class = get_bmi_for_age_classification(bmi, age_months, sex) if bmi_for_age_eligible else "Not eligible"
            bmi_adult_class = get_adult_bmi_classification(bmi) if bmi_adult_eligible else "Not eligible"
            
            print(f"User {i}: {name}")
            print(f"  Age: {age_months} months ({age_years:.1f} years)")
            print(f"  Weight: {weight} kg, Height: {height} cm")
            print(f"  BMI: {bmi:.1f}")
            print(f"  BMI-for-Age eligible: {bmi_for_age_eligible} -> {bmi_for_age_class}")
            print(f"  BMI Adult eligible: {bmi_adult_eligible} -> {bmi_adult_class}")
            print()

if __name__ == "__main__":
    analyze_users()
