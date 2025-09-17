#!/usr/bin/env python3
import csv

def create_small_batches():
    print("Creating small batch files for testing...")
    
    # Read the comprehensive CSV file
    with open('weight_for_age_comprehensive_test.csv', 'r', newline='', encoding='utf-8') as file:
        reader = csv.reader(file)
        header = next(reader)  # Get the header row
        all_data = list(reader)
    
    # Create batches of 20 records each
    batch_size = 20
    num_batches = (len(all_data) + batch_size - 1) // batch_size
    
    for i in range(num_batches):
        start_idx = i * batch_size
        end_idx = min(start_idx + batch_size, len(all_data))
        batch_data = all_data[start_idx:end_idx]
        
        batch_filename = f'weight_for_age_batch_{i+1:02d}.csv'
        
        with open(batch_filename, 'w', newline='', encoding='utf-8') as file:
            writer = csv.writer(file, quoting=csv.QUOTE_ALL)
            writer.writerow(header)
            writer.writerows(batch_data)
        
        print(f"Created {batch_filename}: {len(batch_data)} records (ages {batch_data[0][0].split('_')[2]} to {batch_data[-1][0].split('_')[2]})")
    
    print(f"\nTotal batches created: {num_batches}")
    print("Each batch contains 20 test cases for easier import testing")

if __name__ == "__main__":
    create_small_batches()
