#!/usr/bin/env python3
"""
Investigate why database export (ssss.csv) differs from API results
"""

import pandas as pd
import requests
import json
from datetime import datetime

def calculate_age_in_months(birthday_str, screening_date_str):
    """Calculate age in months (matching API logic)"""
    try:
        birthday = datetime.strptime(birthday_str, '%Y-%m-%d').date()
        screening_date = datetime.strptime(screening_date_str, '%Y-%m-%d %H:%M:%S').date()
        
        years = screening_date.year - birthday.year
        months = screening_date.month - birthday.month
        total_months = years * 12 + months
        
        if screening_date.day < birthday.day:
            total_months -= 1
            
        return max(0, total_months)
    except Exception as e:
        print(f"Age calculation error: {e}")
        return 0

def simulate_api_logic_on_csv():
    """Simulate the API logic on the CSV data to see if we get the same results"""
    
    print("🔍 SIMULATING API LOGIC ON CSV DATA")
    print("=" * 60)
    
    # Load CSV
    df = pd.read_csv('ssss.csv')
    print(f"✅ Loaded CSV with {len(df)} users")
    
    # Calculate ages
    df['age_months'] = df.apply(lambda row: calculate_age_in_months(
        row['birthday'], row['screening_date']
    ), axis=1)
    
    # WHO Standards age restrictions (matching API)
    age_restrictions = {
        'weight-for-age': {'min': 0, 'max': 71},
        'height-for-age': {'min': 0, 'max': 71},
        'weight-for-height': {'min': 0, 'max': 60},
        'bmi-for-age': {'min': 24, 'max': 228},
        'bmi-adult': {'min': 228, 'max': 999}
    }
    
    # Simulate API processing
    results = {}
    
    for standard, restrictions in age_restrictions.items():
        min_age = restrictions['min']
        max_age = restrictions['max']
        
        # Count eligible users (matching API logic)
        eligible = df[(df['age_months'] >= min_age) & (df['age_months'] <= max_age)]
        ineligible = df[(df['age_months'] < min_age) | (df['age_months'] > max_age)]
        
        results[standard] = {
            'eligible': len(eligible),
            'ineligible': len(ineligible),
            'total': len(df)
        }
        
        print(f"\n{standard.upper()}:")
        print(f"  Age range: {min_age}-{max_age} months")
        print(f"  Eligible: {len(eligible)}")
        print(f"  Ineligible: {len(ineligible)}")
        
        if len(eligible) > 0:
            print(f"  Eligible age range: {eligible['age_months'].min():.0f}-{eligible['age_months'].max():.0f} months")
    
    return results

def get_api_results():
    """Get current API results"""
    base_url = "https://nutrisaur-production.up.railway.app"
    url = f"{base_url}/api/DatabaseAPI.php"
    params = {
        'action': 'get_all_who_classifications_bulk',
        'barangay': '',
        'who_standard': 'weight-for-age'
    }
    
    try:
        response = requests.get(url, params=params, timeout=10)
        if response.status_code == 200:
            data = response.json()
            if data.get('success'):
                return data
    except Exception as e:
        print(f"Error getting API data: {e}")
    
    return None

def compare_results():
    """Compare CSV simulation with API results"""
    
    print("\n🔍 COMPARING CSV SIMULATION WITH API RESULTS")
    print("=" * 60)
    
    # Get CSV simulation results
    csv_results = simulate_api_logic_on_csv()
    
    # Get API results
    print("\n📥 Getting API results...")
    api_data = get_api_results()
    if not api_data:
        print("❌ Could not get API data")
        return
    
    api_totals = api_data.get('actual_totals', {})
    
    print(f"\n📊 COMPARISON:")
    print("-" * 40)
    
    standards = ['weight-for-age', 'height-for-age', 'weight-for-height', 'bmi-for-age', 'bmi-adult']
    
    for standard in standards:
        csv_eligible = csv_results[standard]['eligible']
        api_eligible = api_totals.get(standard.replace('-', '_'), 0)
        
        match = "✅" if csv_eligible == api_eligible else "❌"
        diff = api_eligible - csv_eligible
        
        print(f"{match} {standard}:")
        print(f"  CSV simulation: {csv_eligible}")
        print(f"  API result: {api_eligible}")
        print(f"  Difference: {diff:+d}")
        print()
    
    # Analyze discrepancies
    print(f"🎯 DISCREPANCY ANALYSIS:")
    print("-" * 40)
    
    discrepancies = []
    for standard in standards:
        csv_eligible = csv_results[standard]['eligible']
        api_eligible = api_totals.get(standard.replace('-', '_'), 0)
        
        if csv_eligible != api_eligible:
            discrepancies.append({
                'standard': standard,
                'csv': csv_eligible,
                'api': api_eligible,
                'diff': api_eligible - csv_eligible
            })
    
    if not discrepancies:
        print("✅ No discrepancies found - CSV and API match perfectly!")
    else:
        print(f"❌ Found {len(discrepancies)} discrepancies:")
        for disc in discrepancies:
            print(f"  {disc['standard']}: API has {disc['diff']:+d} more eligible users")
        
        print(f"\n🔧 POSSIBLE CAUSES:")
        print("1. API is using different data than what's in ssss.csv")
        print("2. API has additional data processing logic not accounted for")
        print("3. API is using cached or processed data")
        print("4. There are multiple data sources being combined")
        print("5. API is filtering data differently than expected")

def check_data_consistency():
    """Check if the CSV data is consistent with what we expect"""
    
    print(f"\n🔍 DATA CONSISTENCY CHECK")
    print("=" * 60)
    
    df = pd.read_csv('ssss.csv')
    
    # Check for data quality issues
    print(f"📊 CSV Data Quality:")
    print(f"  Total users: {len(df)}")
    print(f"  Missing weights: {df['weight'].isna().sum()}")
    print(f"  Missing heights: {df['height'].isna().sum()}")
    print(f"  Missing birthdays: {df['birthday'].isna().sum()}")
    print(f"  Missing screening dates: {df['screening_date'].isna().sum()}")
    
    # Check age distribution
    df['age_months'] = df.apply(lambda row: calculate_age_in_months(
        row['birthday'], row['screening_date']
    ), axis=1)
    
    print(f"\n📊 Age Distribution:")
    print(f"  Min age: {df['age_months'].min():.0f} months")
    print(f"  Max age: {df['age_months'].max():.0f} months")
    print(f"  Mean age: {df['age_months'].mean():.1f} months")
    
    # Check for users that should be eligible but aren't
    print(f"\n📊 Eligibility Analysis:")
    
    age_restrictions = {
        'weight-for-height': {'min': 0, 'max': 60},
        'bmi-for-age': {'min': 24, 'max': 228},
        'bmi-adult': {'min': 228, 'max': 999}
    }
    
    for standard, restrictions in age_restrictions.items():
        eligible = df[(df['age_months'] >= restrictions['min']) & (df['age_months'] <= restrictions['max'])]
        print(f"  {standard}: {len(eligible)} eligible users")
        
        if len(eligible) > 0:
            print(f"    Age range: {eligible['age_months'].min():.0f}-{eligible['age_months'].max():.0f} months")

def main():
    """Main investigation function"""
    print("🔍 INVESTIGATING DATABASE vs API DISCREPANCY")
    print("=" * 60)
    print("Since ssss.csv is exported from the database, we need to understand")
    print("why the API results differ from what we expect based on this data.")
    print()
    
    check_data_consistency()
    compare_results()

if __name__ == "__main__":
    main()
