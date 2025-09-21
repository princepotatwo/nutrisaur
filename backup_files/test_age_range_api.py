#!/usr/bin/env python3
"""
Test Age Classification Chart API with different age ranges
"""

import requests
import json

def test_age_range_api():
    base_url = "https://nutrisaur-production.up.railway.app"
    
    # Test different age ranges
    test_cases = [
        {"from_months": 0, "to_months": 71, "description": "Full range (0-71 months)"},
        {"from_months": 12, "to_months": 36, "description": "Custom range (12-36 months)"},
        {"from_months": 24, "to_months": 48, "description": "Custom range (24-48 months)"},
        {"from_months": 0, "to_months": 12, "description": "Infant range (0-12 months)"},
        {"from_months": 60, "to_months": 72, "description": "Older range (60-72 months)"}
    ]
    
    for test_case in test_cases:
        print(f"\n🔍 Testing: {test_case['description']}")
        print(f"   Range: {test_case['from_months']} to {test_case['to_months']} months")
        
        url = f"{base_url}/api/DatabaseAPI.php"
        params = {
            'action': 'get_age_classification_chart',
            'barangay': '',
            'time_frame': '1d',
            'from_months': test_case['from_months'],
            'to_months': test_case['to_months']
        }
        
        try:
            response = requests.get(url, params=params, timeout=10)
            response.raise_for_status()
            data = response.json()
            
            if data.get('success'):
                age_groups = data['data']['ageGroups']
                classifications = data['data']['classifications']
                chart_data = data['data']['chartData']
                
                print(f"   ✅ API Response Success")
                print(f"   📊 Age Groups ({len(age_groups)}): {age_groups}")
                print(f"   📊 Classifications ({len(classifications)}): {classifications}")
                
                # Check if age groups make sense for the range
                expected_groups = []
                if test_case['from_months'] < 6 and test_case['to_months'] > 0:
                    expected_groups.append('0-6m')
                if test_case['from_months'] < 12 and test_case['to_months'] > 6:
                    expected_groups.append('6-12m')
                if test_case['from_months'] < 24 and test_case['to_months'] > 12:
                    expected_groups.append('1-2y')
                if test_case['from_months'] < 36 and test_case['to_months'] > 24:
                    expected_groups.append('2-3y')
                if test_case['from_months'] < 48 and test_case['to_months'] > 36:
                    expected_groups.append('3-4y')
                if test_case['from_months'] < 60 and test_case['to_months'] > 48:
                    expected_groups.append('4-5y')
                if test_case['from_months'] < 72 and test_case['to_months'] > 60:
                    expected_groups.append('5-6y')
                
                print(f"   🎯 Expected Groups: {expected_groups}")
                print(f"   ✅ Actual Groups: {age_groups}")
                
                if age_groups == expected_groups:
                    print(f"   ✅ Age groups match expected!")
                else:
                    print(f"   ❌ Age groups don't match expected!")
                
                # Show some sample data
                print(f"   📈 Sample Data:")
                for classification in classifications[:3]:  # Show first 3 classifications
                    values = chart_data.get(classification, [])
                    print(f"      {classification}: {values}")
                    
            else:
                print(f"   ❌ API Error: {data.get('message', 'Unknown error')}")
                
        except requests.exceptions.RequestException as e:
            print(f"   ❌ Request Error: {e}")
        except json.JSONDecodeError as e:
            print(f"   ❌ JSON Error: {e}")
        except Exception as e:
            print(f"   ❌ Unexpected Error: {e}")

if __name__ == "__main__":
    print("🧪 Testing Age Classification Chart API with different age ranges")
    test_age_range_api()
