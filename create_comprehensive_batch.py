#!/usr/bin/env python3
import csv
from datetime import datetime, timedelta

def create_comprehensive_batch():
    print("Creating comprehensive test batch with all classifications for both genders...")
    
    # Header
    header = ["name", "email", "password", "municipality", "barangay", "sex", "birthday", "is_pregnant", "weight", "height", "screening_date"]
    
    # Test data for ages 0-2 months with all classifications
    test_data = []
    
    # Base screening date
    screening_date = "2024-01-15 10:30:00"
    
    # EXACT ranges from the decision tree in who_growth_standards.php
    male_ranges = {
        0: {  # Birth - SU ≤ 2.1, U 2.2-2.4, N 2.5-4.4, O ≥ 4.5
            'severely_underweight': [1.0, 1.5, 2.0],
            'underweight': [2.2, 2.3],
            'normal': [2.5, 3.0, 3.5, 4.0],
            'overweight': [4.5, 5.0]
        },
        1: {  # 1 month - SU ≤ 2.9, U 3.0-3.3, N 3.4-5.8, O ≥ 5.9
            'severely_underweight': [2.0, 2.5, 2.8],
            'underweight': [3.0, 3.2],
            'normal': [3.5, 4.0, 4.5, 5.5],
            'overweight': [6.0, 6.5]
        },
        2: {  # 2 months - SU ≤ 3.8, U 3.9-4.2, N 4.3-7.1, O ≥ 7.2
            'severely_underweight': [2.5, 3.0, 3.5],
            'underweight': [3.9, 4.1],
            'normal': [4.5, 5.0, 5.5, 6.5],
            'overweight': [7.5, 8.0]
        }
    }
    
    # EXACT ranges from the decision tree for females
    female_ranges = {
        0: {  # Birth - SU ≤ 2.0, U 2.1-2.3, N 2.4-4.2, O ≥ 4.3
            'severely_underweight': [1.0, 1.5, 1.8],
            'underweight': [2.1, 2.2],
            'normal': [2.4, 2.8, 3.2, 3.8],
            'overweight': [4.3, 4.5]
        },
        1: {  # 1 month - SU ≤ 2.7, U 2.8-3.1, N 3.2-5.5, O ≥ 5.6
            'severely_underweight': [1.5, 2.0, 2.5],
            'underweight': [2.8, 3.0],
            'normal': [3.2, 3.6, 4.0, 5.0],
            'overweight': [5.6, 6.0]
        },
        2: {  # 2 months - SU ≤ 3.4, U 3.5-3.9, N 4.0-6.5, O ≥ 6.6
            'severely_underweight': [2.0, 2.5, 3.0],
            'underweight': [3.5, 3.7],
            'normal': [4.0, 4.5, 5.0, 6.0],
            'overweight': [6.6, 7.0]
        }
    }
    
    # Generate test data
    for age_months in range(3):  # 0 to 2 months
        # Calculate birthday (age_months before screening date)
        base_date = datetime(2024, 1, 15)
        birthday_date = base_date - timedelta(days=age_months * 30)  # Approximate month as 30 days
        birthday = birthday_date.strftime('%Y-%m-%d')
        
        # Calculate height based on age (realistic heights)
        height = 50 + (age_months * 2.0)  # 50cm at birth, +2cm per month
        
        # Male test cases
        male_cases = male_ranges[age_months]
        for classification, weights in male_cases.items():
            for i, weight in enumerate(weights):
                test_data.append([
                    f"Test_Male_{age_months}m_{classification.upper()}{i+1}",
                    f"test{age_months}@example.com",
                    "password123",
                    "CITY OF BALANGA",
                    "Bagumbayan",
                    "Male",
                    birthday,
                    "No",
                    str(weight),
                    str(height),
                    screening_date
                ])
        
        # Female test cases
        female_cases = female_ranges[age_months]
        for classification, weights in female_cases.items():
            for i, weight in enumerate(weights):
                test_data.append([
                    f"Test_Female_{age_months}m_{classification.upper()}{i+1}",
                    f"test{age_months}f@example.com",
                    "password123",
                    "MARIVELES",
                    "Alion",
                    "Female",
                    birthday,
                    "No",
                    str(weight),
                    str(height),
                    screening_date
                ])
    
    # Write to CSV
    with open('comprehensive_classification_test.csv', 'w', newline='', encoding='utf-8') as file:
        writer = csv.writer(file, quoting=csv.QUOTE_ALL)
        writer.writerow(header)
        writer.writerows(test_data)
    
    print(f"Created comprehensive_classification_test.csv with {len(test_data)} test cases")
    print("Includes all classifications: Severely Underweight, Underweight, Normal, Overweight")
    print("Covers ages 0-2 months for both male and female")
    print("Uses correct birthday calculations for proper age determination")
    
    # Show sample data
    print("\nSample data:")
    for i in range(min(15, len(test_data))):
        print(f"  {test_data[i][0]}: {test_data[i][8]}kg, {test_data[i][5]}, Birthday: {test_data[i][6]}")

if __name__ == "__main__":
    create_comprehensive_batch()
