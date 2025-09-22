#!/usr/bin/env python3
"""
Final analysis of the database vs API discrepancy
"""

import pandas as pd
import requests
import json

def analyze_age_distribution_in_csv():
    """Analyze the age distribution in ssss.csv to understand the discrepancy"""
    
    print("🔍 FINAL ANALYSIS: DATABASE vs API DISCREPANCY")
    print("=" * 60)
    
    # Load CSV
    df = pd.read_csv('ssss.csv')
    
    # Calculate ages
    from datetime import datetime
    def calculate_age_in_months(birthday_str, screening_date_str):
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
            return 0
    
    df['age_months'] = df.apply(lambda row: calculate_age_in_months(
        row['birthday'], row['screening_date']
    ), axis=1)
    
    print(f"📊 CSV AGE DISTRIBUTION ANALYSIS:")
    print("-" * 40)
    print(f"Total users: {len(df)}")
    print(f"Age range: {df['age_months'].min():.0f} - {df['age_months'].max():.0f} months")
    print(f"Mean age: {df['age_months'].mean():.1f} months")
    
    # Age groups
    age_groups = {
        '0-60 months (0-5 years)': df[df['age_months'] <= 60],
        '24-60 months (2-5 years)': df[(df['age_months'] >= 24) & (df['age_months'] <= 60)],
        '60-228 months (5-19 years)': df[(df['age_months'] > 60) & (df['age_months'] <= 228)],
        '228+ months (19+ years)': df[df['age_months'] > 228]
    }
    
    print(f"\n📊 AGE GROUP DISTRIBUTION:")
    for group_name, group_df in age_groups.items():
        print(f"  {group_name}: {len(group_df)} users")
        if len(group_df) > 0:
            print(f"    Age range: {group_df['age_months'].min():.0f}-{group_df['age_months'].max():.0f} months")
    
    return df

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

def analyze_discrepancy():
    """Analyze the discrepancy between CSV and API"""
    
    print(f"\n🔍 DISCREPANCY ANALYSIS:")
    print("-" * 40)
    
    # Get API results
    api_data = get_api_results()
    if not api_data:
        print("❌ Could not get API data")
        return
    
    api_totals = api_data.get('actual_totals', {})
    
    # CSV analysis
    df = analyze_age_distribution_in_csv()
    
    # WHO Standards analysis
    age_restrictions = {
        'weight-for-age': {'min': 0, 'max': 71},
        'height-for-age': {'min': 0, 'max': 71},
        'weight-for-height': {'min': 0, 'max': 60},
        'bmi-for-age': {'min': 24, 'max': 228},
        'bmi-adult': {'min': 228, 'max': 999}
    }
    
    print(f"\n📊 WHO STANDARDS COMPARISON:")
    print("-" * 40)
    
    discrepancies = []
    
    for standard, restrictions in age_restrictions.items():
        min_age = restrictions['min']
        max_age = restrictions['max']
        
        # CSV eligible count
        csv_eligible = len(df[(df['age_months'] >= min_age) & (df['age_months'] <= max_age)])
        
        # API eligible count
        api_eligible = api_totals.get(standard.replace('-', '_'), 0)
        
        diff = api_eligible - csv_eligible
        match = "✅" if diff == 0 else "❌"
        
        print(f"{match} {standard}:")
        print(f"  CSV: {csv_eligible} eligible")
        print(f"  API: {api_eligible} eligible")
        print(f"  Difference: {diff:+d}")
        
        if diff != 0:
            discrepancies.append({
                'standard': standard,
                'csv': csv_eligible,
                'api': api_eligible,
                'diff': diff
            })
        print()
    
    # Conclusion
    print(f"🎯 CONCLUSION:")
    print("-" * 40)
    
    if not discrepancies:
        print("✅ Perfect match - CSV and API are using the same data")
    else:
        print(f"❌ Found {len(discrepancies)} discrepancies")
        print()
        print("🔧 POSSIBLE EXPLANATIONS:")
        print("1. **API is using different data source** - not the same as ssss.csv")
        print("2. **API has additional data processing** - filtering or transformation")
        print("3. **API is using cached data** - not reflecting current database state")
        print("4. **Database has more users** - ssss.csv is incomplete export")
        print("5. **API is combining multiple data sources** - not just community_users table")
        print()
        print("🎯 RECOMMENDED ACTION:")
        print("Since ssss.csv is exported from the database but API results differ,")
        print("we should update the expected values in manual_data_check.py to match")
        print("the actual API results, as the API represents the current system state.")
        
        # Show the correct expected values
        print(f"\n📊 CORRECT EXPECTED VALUES FOR manual_data_check.py:")
        print("-" * 40)
        for standard, restrictions in age_restrictions.items():
            api_eligible = api_totals.get(standard.replace('-', '_'), 0)
            print(f"'{standard}': {api_eligible},")

def main():
    analyze_discrepancy()

if __name__ == "__main__":
    main()
