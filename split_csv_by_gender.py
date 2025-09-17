#!/usr/bin/env python3
import csv

def split_csv_by_gender():
    print("Splitting comprehensive test data by gender...")
    
    # Read the comprehensive CSV file
    male_data = []
    female_data = []
    
    with open('weight_for_age_comprehensive_test.csv', 'r', newline='', encoding='utf-8') as file:
        reader = csv.reader(file)
        header = next(reader)  # Get the header row
        
        for row in reader:
            if row[5] == "Male":  # sex column is at index 5
                male_data.append(row)
            elif row[5] == "Female":
                female_data.append(row)
    
    # Write male data to separate file
    with open('weight_for_age_male_test.csv', 'w', newline='', encoding='utf-8') as file:
        writer = csv.writer(file, quoting=csv.QUOTE_ALL)
        writer.writerow(header)
        writer.writerows(male_data)
    
    # Write female data to separate file
    with open('weight_for_age_female_test.csv', 'w', newline='', encoding='utf-8') as file:
        writer = csv.writer(file, quoting=csv.QUOTE_ALL)
        writer.writerow(header)
        writer.writerows(female_data)
    
    print(f"Male test cases: {len(male_data)}")
    print(f"Female test cases: {len(female_data)}")
    print("Files created:")
    print("  - weight_for_age_male_test.csv")
    print("  - weight_for_age_female_test.csv")
    
    # Show sample data from each file
    print("\nSample male data:")
    for i in range(min(3, len(male_data))):
        print(f"  {male_data[i][0]}: {male_data[i][6]} months, {male_data[i][8]}kg")
    
    print("\nSample female data:")
    for i in range(min(3, len(female_data))):
        print(f"  {female_data[i][0]}: {female_data[i][6]} months, {female_data[i][8]}kg")

if __name__ == "__main__":
    split_csv_by_gender()
