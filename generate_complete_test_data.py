#!/usr/bin/env python3
import csv
from datetime import datetime, timedelta

def generate_test_data():
    # Header
    header = ["name", "email", "password", "municipality", "barangay", "sex", "birthday", "is_pregnant", "weight", "height", "screening_date"]
    
    # Test data
    test_data = []
    
    # Base date for screening
    screening_date = "2024-01-15 10:30:00"
    
    # Generate data for each age (0-71 months)
    for age_months in range(72):  # 0 to 71 months
        # Calculate birthday (screening date - age in months)
        base_date = datetime(2024, 1, 15)
        birthday = base_date - timedelta(days=age_months * 30)  # Approximate 30 days per month
        birthday_str = birthday.strftime("%Y-%m-%d")
        
        # Calculate height based on age (approximate)
        height = 50 + (age_months * 1.0)  # Rough height progression
        
        # Male test cases for this age
        male_cases = [
            # Severely Underweight cases
            {"weight": 1.0 + (age_months * 0.2), "classification": "SU"},
            {"weight": 1.5 + (age_months * 0.3), "classification": "SU2"},
            
            # Underweight cases  
            {"weight": 2.0 + (age_months * 0.4), "classification": "U"},
            {"weight": 2.5 + (age_months * 0.5), "classification": "U2"},
            
            # Normal cases
            {"weight": 3.0 + (age_months * 0.6), "classification": "N"},
            {"weight": 4.0 + (age_months * 0.7), "classification": "N2"},
            {"weight": 5.0 + (age_months * 0.8), "classification": "N3"},
            {"weight": 6.0 + (age_months * 0.9), "classification": "N4"},
            
            # Overweight cases
            {"weight": 7.0 + (age_months * 1.0), "classification": "O"},
            {"weight": 8.0 + (age_months * 1.1), "classification": "O2"}
        ]
        
        # Female test cases for this age (generally lower weights than males)
        female_cases = [
            # Severely Underweight cases
            {"weight": 0.8 + (age_months * 0.15), "classification": "SU"},
            {"weight": 1.2 + (age_months * 0.25), "classification": "SU2"},
            
            # Underweight cases
            {"weight": 1.8 + (age_months * 0.35), "classification": "U"},
            {"weight": 2.2 + (age_months * 0.45), "classification": "U2"},
            
            # Normal cases
            {"weight": 2.8 + (age_months * 0.55), "classification": "N"},
            {"weight": 3.5 + (age_months * 0.65), "classification": "N2"},
            {"weight": 4.5 + (age_months * 0.75), "classification": "N3"},
            {"weight": 5.5 + (age_months * 0.85), "classification": "N4"},
            
            # Overweight cases
            {"weight": 6.5 + (age_months * 0.95), "classification": "O"},
            {"weight": 7.5 + (age_months * 1.05), "classification": "O2"}
        ]
        
        # Add male test cases
        for i, case in enumerate(male_cases):
            test_data.append([
                f"Test_Male_{age_months}m_{case['classification']}",
                f"test{age_months}@example.com",
                "password123",
                "CITY OF BALANGA",
                "Bagumbayan",
                "Male",
                birthday_str,
                "No",
                str(case['weight']),
                str(height),
                screening_date
            ])
        
        # Add female test cases
        for i, case in enumerate(female_cases):
            test_data.append([
                f"Test_Female_{age_months}m_{case['classification']}",
                f"test{age_months}f@example.com",
                "password123",
                "MARIVELES",
                "Alion",
                "Female",
                birthday_str,
                "No",
                str(case['weight']),
                str(height),
                screening_date
            ])
    
    return header, test_data

def main():
    print("Generating comprehensive test data for ages 0-71 months...")
    
    header, test_data = generate_test_data()
    
    # Write to CSV with quoted fields to match template format
    with open('weight_for_age_comprehensive_test.csv', 'w', newline='', encoding='utf-8') as file:
        writer = csv.writer(file, quoting=csv.QUOTE_ALL)
        writer.writerow(header)
        writer.writerows(test_data)
    
    print(f"Generated {len(test_data)} test cases")
    print("File saved as: weight_for_age_comprehensive_test.csv")
    
    # Show some sample data
    print("\nSample data (first 10 rows):")
    for i in range(min(10, len(test_data))):
        print(f"  {test_data[i][0]}: {test_data[i][6]} months, {test_data[i][8]}kg, {test_data[i][5]}")

if __name__ == "__main__":
    main()
