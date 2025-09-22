#!/usr/bin/env python3
"""
Compare API data with ssss.csv to identify discrepancies
"""

import requests
import json
import pandas as pd

def get_api_data():
    """Get current API data"""
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
    """Analyze the discrepancy between API and CSV data"""
    
    print("🔍 API vs CSV DATA COMPARISON")
    print("=" * 60)
    
    # Get API data
    print("📥 Getting API data...")
    api_data = get_api_data()
    if not api_data:
        print("❌ Could not get API data")
        return
    
    print(f"✅ API data retrieved")
    print(f"Total users in API: {api_data.get('total_users', 0)}")
    
    # Get CSV data
    print("\n📥 Loading CSV data...")
    try:
        df = pd.read_csv('ssss.csv')
        print(f"✅ CSV loaded with {len(df)} users")
    except Exception as e:
        print(f"❌ Error loading CSV: {e}")
        return
    
    # Compare totals
    print(f"\n📊 DATA COMPARISON:")
    print("-" * 40)
    print(f"API total users: {api_data.get('total_users', 0)}")
    print(f"CSV total users: {len(df)}")
    
    if api_data.get('total_users', 0) == len(df):
        print("✅ Total user counts match")
    else:
        print("❌ Total user counts DO NOT match")
        print("   → API is using different data than ssss.csv")
    
    # Compare WHO standards results
    print(f"\n📊 WHO STANDARDS COMPARISON:")
    print("-" * 40)
    
    # Load our previous analysis
    try:
        with open('ssss_analysis_results.json', 'r') as f:
            csv_analysis = json.load(f)
    except Exception as e:
        print(f"Error loading CSV analysis: {e}")
        return
    
    api_results = api_data.get('data', {})
    csv_results = csv_analysis['summary']
    
    standards = ['weight_for_age', 'height_for_age', 'weight_for_height', 'bmi_for_age', 'bmi_adult']
    
    for standard in standards:
        api_eligible = api_data.get('actual_totals', {}).get(standard, 0)
        csv_eligible = csv_results[standard.replace('_', '-')]['eligible_count']
        
        match = "✅" if api_eligible == csv_eligible else "❌"
        diff = api_eligible - csv_eligible
        
        print(f"{match} {standard}:")
        print(f"  API: {api_eligible} eligible")
        print(f"  CSV: {csv_eligible} eligible")
        print(f"  Difference: {diff:+d}")
        print()
    
    # Conclusion
    print(f"🎯 CONCLUSION:")
    print("-" * 40)
    
    total_matches = sum(1 for standard in standards 
                       if api_data.get('actual_totals', {}).get(standard, 0) == 
                          csv_results[standard.replace('_', '-')]['eligible_count'])
    
    if total_matches == len(standards):
        print("✅ API data matches ssss.csv perfectly")
        print("   → The API is using the correct data")
    else:
        print(f"❌ API data does NOT match ssss.csv ({total_matches}/{len(standards)} standards match)")
        print("   → The API is using different data than ssss.csv")
        print("   → This explains the discrepancy in expected values")
        
        print(f"\n🔧 RECOMMENDATIONS:")
        print("1. Check if ssss.csv is the current database data")
        print("2. Verify if there are other users in the database")
        print("3. Update expected values to match actual API data")
        print("4. Or update the database to match ssss.csv data")

def main():
    analyze_discrepancy()

if __name__ == "__main__":
    main()
