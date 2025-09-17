#!/usr/bin/env python3
import csv

def extract_normal_0_30():
    print("Extracting Normal classification cases for ages 0-30 months...")
    
    # Read the comprehensive CSV file
    with open('weight_for_age_comprehensive_test.csv', 'r', newline='', encoding='utf-8') as file:
        reader = csv.reader(file)
        header = next(reader)  # Get the header row
        all_data = list(reader)
    
    # Filter for Normal cases (N, N2, N3, N4) and ages 0-30 months
    normal_data = []
    
    for row in all_data:
        name = row[0]  # First column is name
        # Extract age from name like "Test_Male_5m_N" -> 5
        if '_' in name:
            parts = name.split('_')
            if len(parts) >= 3:
                age_part = parts[2]  # e.g., "5m"
                if age_part.endswith('m'):
                    age = int(age_part[:-1])  # Remove 'm' and convert to int
                    
                    # Check if it's a Normal case and age 0-30
                    if age <= 30 and ('_N' in name or '_N2' in name or '_N3' in name or '_N4' in name):
                        normal_data.append(row)
    
    # Write to new CSV file
    with open('weight_for_age_normal_0_30.csv', 'w', newline='', encoding='utf-8') as file:
        writer = csv.writer(file, quoting=csv.QUOTE_ALL)
        writer.writerow(header)
        writer.writerows(normal_data)
    
    print(f"Created weight_for_age_normal_0_30.csv with {len(normal_data)} Normal cases")
    print("Covers ages 0-30 months for both male and female")
    
    # Show sample data
    print("\nSample data:")
    for i in range(min(10, len(normal_data))):
        print(f"  {normal_data[i][0]}: {normal_data[i][6]} months, {normal_data[i][8]}kg, {normal_data[i][5]}")

if __name__ == "__main__":
    extract_normal_0_30()
