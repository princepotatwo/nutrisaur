#!/usr/bin/env python3
"""
Test all classifications to see which ones are missing from Age Classification Chart
"""

import requests
import json

def test_all_classifications():
    base_url = "https://nutrisaur-production.up.railway.app"
    
    print("🔍 Testing All Classifications in Age Classification Chart")
    print("=" * 60)
    
    # Test the age classification chart
    url = f"{base_url}/api/DatabaseAPI.php"
    params = {
        'action': 'get_age_classification_chart',
        'barangay': '',
        'time_frame': '1d',
        'from_months': 0,
        'to_months': 852  # 0-71 years
    }
    
    try:
        response = requests.get(url, params=params, timeout=10)
        response.raise_for_status()
        data = response.json()
        
        if data.get('success'):
            age_groups = data['data']['ageGroups']
            classifications = data['data']['classifications']
            chart_data = data['data']['chartData']
            
            print(f"📊 Age Classification Chart Results:")
            print(f"   Age Groups: {len(age_groups)}")
            print(f"   Classifications: {len(classifications)}")
            print(f"   Classifications List: {classifications}")
            
            print(f"\n📈 Data Distribution by Classification:")
            total_users = 0
            for classification in classifications:
                values = chart_data.get(classification, [])
                total_for_classification = sum(values)
                total_users += total_for_classification
                if total_for_classification > 0:
                    print(f"   ✅ {classification}: {total_for_classification} users")
                else:
                    print(f"   ❌ {classification}: 0 users (MISSING!)")
            
            print(f"\n👥 Total Users: {total_users}")
            
            # Check for missing classifications
            expected_classifications = [
                'Normal', 'Underweight', 'Severely Underweight', 
                'Overweight', 'Obese', 'Stunted', 'Severely Stunted', 
                'Wasted', 'Severely Wasted', 'Tall'
            ]
            
            print(f"\n🔍 Missing Classifications Check:")
            for expected in expected_classifications:
                if expected in classifications:
                    values = chart_data.get(expected, [])
                    total = sum(values)
                    if total > 0:
                        print(f"   ✅ {expected}: {total} users")
                    else:
                        print(f"   ⚠️  {expected}: 0 users (present but no data)")
                else:
                    print(f"   ❌ {expected}: NOT FOUND in classifications list")
                    
        else:
            print(f"❌ API Error: {data.get('message', 'Unknown error')}")
            
    except Exception as e:
        print(f"❌ Error: {e}")

if __name__ == "__main__":
    test_all_classifications()
