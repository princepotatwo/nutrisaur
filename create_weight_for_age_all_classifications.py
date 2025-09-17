#!/usr/bin/env python3
import csv

def create_weight_for_age_all_classifications():
    print("Creating Weight-for-Age test file with ALL classifications...")
    
    # Header
    header = ["name", "email", "password", "municipality", "barangay", "sex", "birthday", "is_pregnant", "weight", "height", "screening_date"]
    
    # Test data for ages 0-5 months with all classifications
    test_data = []
    
    # Base date for screening
    screening_date = "2024-01-15 10:30:00"
    
    # Test cases for each age (0-5 months)
    for age_months in range(6):  # 0 to 5 months
        # Calculate birthday
        base_date = "2024-01-15"
        birthday = base_date  # Same day for simplicity
        
        # Calculate height based on age
        height = 50 + (age_months * 1.0)
        
        # Male test cases for this age - using WHO standards ranges
        male_cases = [
            # Severely Underweight cases (should be < -3SD)
            {"weight": 1.0 + (age_months * 0.1), "classification": "SU", "desc": "Very low weight"},
            {"weight": 1.5 + (age_months * 0.2), "classification": "SU2", "desc": "Very low weight 2"},
            
            # Underweight cases (should be -3SD to -2SD)
            {"weight": 2.0 + (age_months * 0.3), "classification": "U", "desc": "Low weight"},
            {"weight": 2.2 + (age_months * 0.4), "classification": "U2", "desc": "Low weight 2"},
            
            # Normal cases (should be -2SD to +2SD)
            {"weight": 2.5 + (age_months * 0.5), "classification": "N", "desc": "Normal"},
            {"weight": 3.0 + (age_months * 0.6), "classification": "N2", "desc": "Normal 2"},
            {"weight": 4.0 + (age_months * 0.7), "classification": "N3", "desc": "Normal 3"},
            {"weight": 5.0 + (age_months * 0.8), "classification": "N4", "desc": "Normal 4"},
            
            # Overweight cases (should be > +2SD)
            {"weight": 6.0 + (age_months * 1.0), "classification": "O", "desc": "High weight"},
            {"weight": 7.0 + (age_months * 1.1), "classification": "O2", "desc": "High weight 2"}
        ]
        
        # Female test cases for this age (generally lower weights)
        female_cases = [
            # Severely Underweight cases
            {"weight": 0.8 + (age_months * 0.1), "classification": "SU", "desc": "Very low weight"},
            {"weight": 1.2 + (age_months * 0.15), "classification": "SU2", "desc": "Very low weight 2"},
            
            # Underweight cases
            {"weight": 1.8 + (age_months * 0.25), "classification": "U", "desc": "Low weight"},
            {"weight": 2.0 + (age_months * 0.3), "classification": "U2", "desc": "Low weight 2"},
            
            # Normal cases
            {"weight": 2.4 + (age_months * 0.4), "classification": "N", "desc": "Normal"},
            {"weight": 3.0 + (age_months * 0.5), "classification": "N2", "desc": "Normal 2"},
            {"weight": 4.0 + (age_months * 0.6), "classification": "N3", "desc": "Normal 3"},
            {"weight": 5.0 + (age_months * 0.7), "classification": "N4", "desc": "Normal 4"},
            
            # Overweight cases
            {"weight": 6.0 + (age_months * 0.8), "classification": "O", "desc": "High weight"},
            {"weight": 7.0 + (age_months * 0.9), "classification": "O2", "desc": "High weight 2"}
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
                birthday,
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
                birthday,
                "No",
                str(case['weight']),
                str(height),
                screening_date
            ])
    
    # Write to CSV
    with open('weight_for_age_all_classifications.csv', 'w', newline='', encoding='utf-8') as file:
        writer = csv.writer(file, quoting=csv.QUOTE_ALL)
        writer.writerow(header)
        writer.writerows(test_data)
    
    print(f"Created weight_for_age_all_classifications.csv with {len(test_data)} test cases")
    print("Includes: Severely Underweight, Underweight, Normal, Overweight for ages 0-5 months")
    
    # Show sample data
    print("\nSample data:")
    for i in range(min(10, len(test_data))):
        print(f"  {test_data[i][0]}: {test_data[i][8]}kg, {test_data[i][5]}")

if __name__ == "__main__":
    create_weight_for_age_all_classifications()
