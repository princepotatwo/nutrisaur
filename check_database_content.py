#!/usr/bin/env python3
"""
Check if there are additional users in the database not in ssss.csv
"""

import requests
import json

def get_database_user_count():
    """Get the actual user count from the database"""
    
    base_url = "https://nutrisaur-production.up.railway.app"
    
    # Try different endpoints to get user data
    endpoints = [
        '/api/DatabaseAPI.php?action=get_community_users&barangay=',
        '/api/DatabaseAPI.php?action=get_detailed_screening_responses&timeFrame=1d&barangay=',
        '/api/DatabaseAPI.php?action=select&table=community_users&columns=COUNT(*)&where=1=1'
    ]
    
    for endpoint in endpoints:
        try:
            url = f"{base_url}{endpoint}"
            print(f"Trying: {url}")
            
            response = requests.get(url, timeout=10)
            if response.status_code == 200:
                data = response.json()
                print(f"✅ Success with {endpoint}")
                print(f"Response: {json.dumps(data, indent=2)[:500]}...")
                
                # Try to extract user count
                if isinstance(data, dict):
                    if 'data' in data and isinstance(data['data'], list):
                        print(f"Found {len(data['data'])} users in data array")
                    elif 'total_users' in data:
                        print(f"Total users: {data['total_users']}")
                    elif 'count' in data:
                        print(f"Count: {data['count']}")
                
                print()
        except Exception as e:
            print(f"❌ Error with {endpoint}: {e}")
            print()

def check_specific_user_data():
    """Check if we can get specific user data to compare"""
    
    print("🔍 CHECKING SPECIFIC USER DATA")
    print("=" * 50)
    
    base_url = "https://nutrisaur-production.up.railway.app"
    
    # Try to get user data with more details
    try:
        url = f"{base_url}/api/DatabaseAPI.php"
        params = {
            'action': 'get_detailed_screening_responses',
            'timeFrame': '1d',
            'barangay': ''
        }
        
        response = requests.get(url, params=params, timeout=10)
        if response.status_code == 200:
            data = response.json()
            
            if data.get('success') and 'data' in data:
                users = data['data']
                print(f"✅ Found {len(users)} users in detailed screening responses")
                
                # Show first few users
                print(f"\nFirst 3 users:")
                for i, user in enumerate(users[:3]):
                    print(f"User {i+1}: {user}")
                
                # Check if this matches our CSV
                print(f"\n📊 COMPARISON:")
                print(f"CSV users: 63")
                print(f"API users: {len(users)}")
                
                if len(users) == 63:
                    print("✅ User counts match - same data source")
                else:
                    print(f"❌ User counts differ - different data sources")
                    print(f"   Difference: {len(users) - 63:+d} users")
                
            else:
                print(f"❌ No user data found in response")
                print(f"Response: {data}")
        else:
            print(f"❌ HTTP Error: {response.status_code}")
            
    except Exception as e:
        print(f"❌ Error: {e}")

def analyze_discrepancy_pattern():
    """Analyze the pattern of discrepancies"""
    
    print("\n🔍 DISCREPANCY PATTERN ANALYSIS")
    print("=" * 50)
    
    discrepancies = {
        'weight-for-height': {'csv': 37, 'api': 63, 'diff': 26},
        'bmi-for-age': {'csv': 42, 'api': 47, 'diff': 5},
        'bmi-adult': {'csv': 5, 'api': 21, 'diff': 16}
    }
    
    print("Discrepancy Analysis:")
    for standard, data in discrepancies.items():
        print(f"\n{standard}:")
        print(f"  CSV: {data['csv']} eligible")
        print(f"  API: {data['api']} eligible")
        print(f"  Difference: +{data['diff']} users")
        
        # Calculate percentage increase
        pct_increase = (data['diff'] / data['csv']) * 100 if data['csv'] > 0 else 0
        print(f"  Percentage increase: {pct_increase:.1f}%")
    
    print(f"\n🎯 PATTERN OBSERVATIONS:")
    print("1. weight-for-height: +26 users (70% increase)")
    print("2. bmi-for-age: +5 users (12% increase)")
    print("3. bmi-adult: +16 users (320% increase)")
    print()
    print("The pattern suggests the API is including additional users")
    print("that are older than what's in the CSV export.")

def main():
    """Main investigation function"""
    print("🔍 INVESTIGATING DATABASE CONTENT")
    print("=" * 60)
    print("Checking if there are additional users in the database")
    print("that weren't included in the ssss.csv export.")
    print()
    
    get_database_user_count()
    check_specific_user_data()
    analyze_discrepancy_pattern()

if __name__ == "__main__":
    main()
