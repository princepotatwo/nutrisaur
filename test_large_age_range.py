#!/usr/bin/env python3
"""
Test Age Classification Chart API with very large age range (0 months to 100 years)
"""

import requests
import json

def test_large_age_range():
    base_url = "https://nutrisaur-production.up.railway.app"
    
    # Test very large age range
    test_cases = [
        {"from_months": 0, "to_months": 1200, "description": "Very large range (0-1200 months = 0-100 years)"},
        {"from_months": 0, "to_months": 852, "description": "Large range (0-852 months = 0-71 years)"},
        {"from_months": 0, "to_months": 10000, "description": "Extremely large range (0-10000 months = 0-833 years)"}
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
                
                # Check if we get all standard age groups for large ranges
                if test_case['to_months'] >= 72:
                    expected_groups = ['0-6m', '6-12m', '1-2y', '2-3y', '3-4y', '4-5y', '5-6y']
                    print(f"   🎯 Expected Groups (all): {expected_groups}")
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
    print("🧪 Testing Age Classification Chart API with very large age ranges")
    test_large_age_range()
