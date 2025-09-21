#!/usr/bin/env python3
"""
Test dynamic age group generation (10 equal columns for any range)
"""

import requests
import json

def test_dynamic_age_groups():
    base_url = "https://nutrisaur-production.up.railway.app"
    
    # Test different age ranges to see dynamic age group generation
    test_cases = [
        {"from_months": 0, "to_months": 12, "description": "0-12 months (1 year)"},
        {"from_months": 0, "to_months": 120, "description": "0-120 months (10 years)"},
        {"from_months": 60, "to_months": 600, "description": "5-50 years (60-600 months)"},
        {"from_months": 0, "to_months": 1200, "description": "0-100 years (0-1200 months)"},
        {"from_months": 12, "to_months": 120, "description": "1-10 years (12-120 months)"}
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
                
                # Check if we get exactly 10 age groups
                if len(age_groups) == 10:
                    print(f"   ✅ Perfect! Got exactly 10 age groups as expected")
                else:
                    print(f"   ❌ Expected 10 age groups, got {len(age_groups)}")
                
                # Show the age group distribution
                print(f"   📈 Age Group Distribution:")
                for i, age_group in enumerate(age_groups, 1):
                    print(f"      {i:2d}. {age_group}")
                
                # Show some sample data
                print(f"   📊 Sample Data (first 3 classifications):")
                for classification in classifications[:3]:
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
    print("🧪 Testing Dynamic Age Group Generation (10 equal columns for any range)")
    test_dynamic_age_groups()
