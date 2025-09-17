#!/usr/bin/env python3
import csv

def create_medium_test():
    print("Creating medium test file with 50 records...")
    
    # Read the comprehensive CSV file
    with open('weight_for_age_comprehensive_test.csv', 'r', newline='', encoding='utf-8') as file:
        reader = csv.reader(file)
        header = next(reader)  # Get the header row
        all_data = list(reader)
    
    # Take first 50 records
    medium_data = all_data[:50]
    
    with open('weight_for_age_test_medium.csv', 'w', newline='', encoding='utf-8') as file:
        writer = csv.writer(file, quoting=csv.QUOTE_ALL)
        writer.writerow(header)
        writer.writerows(medium_data)
    
    print(f"Created weight_for_age_test_medium.csv with {len(medium_data)} records")
    print("This covers ages 0-4 months with all classifications")

if __name__ == "__main__":
    create_medium_test()
