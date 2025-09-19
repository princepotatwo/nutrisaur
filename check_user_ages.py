#!/usr/bin/env python3
"""
Check what age ranges have users in the database
"""

import requests
import json

def check_user_ages():
    base_url = "https://nutrisaur-production.up.railway.app"
    
    # Get all users without age filtering
    url = f"{base_url}/api/DatabaseAPI.php"
    params = {
        'action': 'get_age_classification_chart',
        'barangay': '',
        'time_frame': '1d',
        'from_months': 0,
        'to_months': 72
    }
    
    try:
        response = requests.get(url, params=params, timeout=10)
        response.raise_for_status()
        data = response.json()
        
        if data.get('success'):
            raw_data = data['data']['rawData']
            print("🔍 User distribution by age group:")
            
            total_users = 0
            for age_group, classifications in raw_data.items():
                group_total = sum(classifications.values())
                total_users += group_total
                print(f"   {age_group}: {group_total} users")
                
                # Show breakdown by classification
                for classification, count in classifications.items():
                    if count > 0:
                        print(f"      - {classification}: {count}")
            
            print(f"\n📊 Total users: {total_users}")
            
            # Check if there are users in 60-72 months range
            if '5-6y' in raw_data:
                five_six_years = raw_data['5-6y']
                five_six_total = sum(five_six_years.values())
                print(f"\n🎯 5-6y age group (60-72 months): {five_six_total} users")
                
                if five_six_total > 0:
                    print("   ✅ There ARE users in 60-72 months range")
                else:
                    print("   ❌ There are NO users in 60-72 months range")
            else:
                print("\n❌ 5-6y age group not found in data")
                
        else:
            print(f"❌ API Error: {data.get('message', 'Unknown error')}")
            
    except Exception as e:
        print(f"❌ Error: {e}")

if __name__ == "__main__":
    check_user_ages()
