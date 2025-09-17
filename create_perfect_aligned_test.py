#!/usr/bin/env python3
import csv

def create_perfect_aligned_test():
    print("Creating Weight-for-Age test file with PERFECT alignment to decision tree ranges...")
    
    # Header
    header = ["name", "email", "password", "municipality", "barangay", "sex", "birthday", "is_pregnant", "weight", "height", "screening_date"]
    
    # Test data for ages 0-5 months with EXACT decision tree ranges
    test_data = []
    
    # Base date for screening
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
        },
        3: {  # 3 months - SU ≤ 4.4, U 4.5-4.9, N 5.0-8.0, O ≥ 8.1
            'severely_underweight': [3.0, 3.5, 4.0],
            'underweight': [4.5, 4.7],
            'normal': [5.0, 5.5, 6.0, 7.5],
            'overweight': [8.5, 9.0]
        },
        4: {  # 4 months - SU ≤ 4.9, U 5.0-5.5, N 5.6-8.7, O ≥ 8.8
            'severely_underweight': [3.5, 4.0, 4.5],
            'underweight': [5.0, 5.3],
            'normal': [5.6, 6.0, 6.5, 8.0],
            'overweight': [9.0, 9.5]
        },
        5: {  # 5 months - SU ≤ 5.3, U 5.4-5.9, N 6.0-9.3, O ≥ 9.4
            'severely_underweight': [4.0, 4.5, 5.0],
            'underweight': [5.4, 5.7],
            'normal': [6.0, 6.5, 7.0, 8.5],
            'overweight': [9.5, 10.0]
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
        },
        3: {  # 3 months - SU ≤ 4.0, U 4.1-4.5, N 4.6-7.4, O ≥ 7.5
            'severely_underweight': [2.5, 3.0, 3.5],
            'underweight': [4.1, 4.3],
            'normal': [4.6, 5.2, 5.7, 6.8],
            'overweight': [7.5, 7.8]
        },
        4: {  # 4 months - SU ≤ 4.4, U 4.5-5.0, N 5.1-8.1, O ≥ 8.2
            'severely_underweight': [3.0, 3.5, 4.0],
            'underweight': [4.5, 4.7],
            'normal': [5.1, 5.8, 6.3, 7.5],
            'overweight': [8.2, 8.6]
        },
        5: {  # 5 months - SU ≤ 4.8, U 4.9-5.4, N 5.5-8.7, O ≥ 8.8
            'severely_underweight': [3.5, 4.0, 4.5],
            'underweight': [4.9, 5.2],
            'normal': [5.5, 6.0, 6.5, 8.0],
            'overweight': [8.8, 9.2]
        }
    }
    
    # Generate test data
    for age_months in range(6):  # 0 to 5 months
        # Calculate birthday
        birthday = "2024-01-15"
        
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
    with open('weight_for_age_perfect_aligned.csv', 'w', newline='', encoding='utf-8') as file:
        writer = csv.writer(file, quoting=csv.QUOTE_ALL)
        writer.writerow(header)
        writer.writerows(test_data)
    
    print(f"Created weight_for_age_perfect_aligned.csv with {len(test_data)} test cases")
    print("Uses EXACT decision tree ranges from who_growth_standards.php")
    
    # Show sample data
    print("\nSample data:")
    for i in range(min(15, len(test_data))):
        print(f"  {test_data[i][0]}: {test_data[i][8]}kg, {test_data[i][5]}, Age {age_months}m")

if __name__ == "__main__":
    create_perfect_aligned_test()
