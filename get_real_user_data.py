#!/usr/bin/env python3
"""
Get real user data from the database for simulation
"""

import requests
import json
from datetime import datetime

def get_real_user_data():
    """Get real user data from the database"""
    base_url = "https://nutrisaur-production.up.railway.app"
    
    # Try to get user data from a different endpoint
    endpoints = [
        '/api/DatabaseAPI.php?action=get_detailed_screening_responses&timeFrame=1d&barangay=',
        '/api/DatabaseAPI.php?action=get_all_users&barangay=',
        '/api/DatabaseAPI.php?action=get_community_users&barangay='
    ]
    
    for endpoint in endpoints:
        try:
            url = f"{base_url}{endpoint}"
            print(f"Trying: {url}")
            
            response = requests.get(url, timeout=10)
            if response.status_code == 200:
                data = response.json()
                print(f"✅ Success with {endpoint}")
                print(f"Response keys: {list(data.keys()) if isinstance(data, dict) else 'Not a dict'}")
                
                # Save the response
                with open(f'real_user_data_{endpoint.split("=")[1]}.json', 'w') as f:
                    json.dump(data, f, indent=2)
                
                return data
            else:
                print(f"❌ HTTP {response.status_code} for {endpoint}")
        except Exception as e:
            print(f"❌ Error with {endpoint}: {e}")
    
    return None

def analyze_current_api_response():
    """Analyze the current API response to understand the data structure"""
    try:
        with open('current_api_data.json', 'r') as f:
            data = json.load(f)
        
        print("🔍 CURRENT API RESPONSE ANALYSIS")
        print("=" * 50)
        print(f"Success: {data.get('success')}")
        print(f"Total Users: {data.get('total_users')}")
        print(f"Processed Users: {data.get('processed_users')}")
        
        if 'data' in data:
            print(f"\nData structure:")
            for standard, classifications in data['data'].items():
                print(f"  {standard}: {classifications}")
        
        if 'actual_totals' in data:
            print(f"\nActual totals: {data['actual_totals']}")
        
        return data
    except Exception as e:
        print(f"Error analyzing API response: {e}")
        return None

def main():
    print("📊 GETTING REAL USER DATA")
    print("=" * 50)
    
    # First analyze the current API response
    current_data = analyze_current_api_response()
    
    # Try to get real user data
    real_data = get_real_user_data()
    
    if real_data:
        print("✅ Real user data retrieved successfully")
    else:
        print("❌ Could not retrieve real user data")
        print("Will use current API response for simulation")

if __name__ == "__main__":
    main()
