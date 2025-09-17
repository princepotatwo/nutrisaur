#!/usr/bin/env python3
import csv

def create_correct_weight_test():
    print("Creating Weight-for-Age test file with CORRECT WHO standard weight ranges...")
    
    # Header
    header = ["name", "email", "password", "municipality", "barangay", "sex", "birthday", "is_pregnant", "weight", "height", "screening_date"]
    
    # Test data for ages 0-5 months with correct WHO standard ranges
    test_data = []
    
    # Base date for screening
    screening_date = "2024-01-15 10:30:00"
    
    # WHO standard weight ranges for males (0-5 months)
    male_ranges = {
        0: {  # Birth
            'severely_underweight': [1.0, 1.5, 2.0],
            'underweight': [2.1, 2.3],
            'normal': [2.5, 3.0, 3.5, 4.0],
            'overweight': [4.5, 5.0]
        },
        1: {  # 1 month
            'severely_underweight': [2.0, 2.5, 2.8],
            'underweight': [3.0, 3.2],
            'normal': [3.5, 4.0, 4.5, 5.5],
            'overweight': [6.0, 6.5]
        },
        2: {  # 2 months
            'severely_underweight': [2.5, 3.0, 3.5],
            'underweight': [3.9, 4.1],
            'normal': [4.5, 5.0, 5.5, 6.5],
            'overweight': [7.5, 8.0]
        },
        3: {  # 3 months
            'severely_underweight': [3.0, 3.5, 4.0],
            'underweight': [4.5, 4.7],
            'normal': [5.0, 5.5, 6.0, 7.5],
            'overweight': [8.5, 9.0]
        },
        4: {  # 4 months
            'severely_underweight': [3.5, 4.0, 4.5],
            'underweight': [5.0, 5.3],
            'normal': [5.6, 6.0, 6.5, 8.0],
            'overweight': [9.0, 9.5]
        },
        5: {  # 5 months
            'severely_underweight': [4.0, 4.5, 5.0],
            'underweight': [5.4, 5.7],
            'normal': [6.0, 6.5, 7.0, 8.5],
            'overweight': [9.5, 10.0]
        }
    }
    
    # WHO standard weight ranges for females (0-5 months) - generally lower
    female_ranges = {
        0: {  # Birth
            'severely_underweight': [0.8, 1.2, 1.6],
            'underweight': [1.8, 2.0],
            'normal': [2.2, 2.6, 3.0, 3.8],
            'overweight': [4.2, 4.5]
        },
        1: {  # 1 month
            'severely_underweight': [1.5, 2.0, 2.5],
            'underweight': [2.8, 3.0],
            'normal': [3.2, 3.6, 4.0, 5.0],
            'overweight': [5.5, 6.0]
        },
        2: {  # 2 months
            'severely_underweight': [2.0, 2.5, 3.0],
            'underweight': [3.5, 3.7],
            'normal': [4.0, 4.5, 5.0, 6.0],
            'overweight': [6.5, 7.0]
        },
        3: {  # 3 months
            'severely_underweight': [2.5, 3.0, 3.5],
            'underweight': [4.0, 4.2],
            'normal': [4.5, 5.0, 5.5, 6.8],
            'overweight': [7.5, 8.0]
        },
        4: {  # 4 months
            'severely_underweight': [3.0, 3.5, 4.0],
            'underweight': [4.5, 4.7],
            'normal': [5.0, 5.5, 6.0, 7.5],
            'overweight': [8.5, 9.0]
        },
        5: {  # 5 months
            'severely_underweight': [3.5, 4.0, 4.5],
            'underweight': [5.0, 5.2],
            'normal': [5.5, 6.0, 6.5, 8.0],
            'overweight': [9.0, 9.5]
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
    with open('weight_for_age_correct_ranges.csv', 'w', newline='', encoding='utf-8') as file:
        writer = csv.writer(file, quoting=csv.QUOTE_ALL)
        writer.writerow(header)
        writer.writerows(test_data)
    
    print(f"Created weight_for_age_correct_ranges.csv with {len(test_data)} test cases")
    print("Uses correct WHO standard weight ranges for ages 0-5 months")
    
    # Show sample data
    print("\nSample data:")
    for i in range(min(15, len(test_data))):
        print(f"  {test_data[i][0]}: {test_data[i][8]}kg, {test_data[i][5]}, Age {test_data[i][2]}m")

if __name__ == "__main__":
    create_correct_weight_test()
