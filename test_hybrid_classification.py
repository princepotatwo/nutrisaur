#!/usr/bin/env python3
"""
Test hybrid classification system for all age ranges
"""

import requests
import json

def test_hybrid_classification():
    base_url = "https://nutrisaur-production.up.railway.app"
    
    # Test different age ranges to verify hybrid classification
    test_cases = [
        {"from_months": 0, "to_months": 71, "description": "0-71 months (WHO standards only)"},
        {"from_months": 0, "to_months": 120, "description": "0-120 months (WHO + BMI-for-age)"},
        {"from_months": 0, "to_months": 852, "description": "0-71 years (All age ranges)"},
        {"from_months": 240, "to_months": 600, "description": "20-50 years (Adult BMI only)"},
        {"from_months": 60, "to_months": 240, "description": "5-20 years (BMI-for-age)"}
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
                
                # Count total users across all age groups
                total_users = 0
                for classification in classifications:
                    values = chart_data.get(classification, [])
                    total_users += sum(values)
                
                print(f"   👥 Total Users: {total_users}")
                
                # Show data distribution
                print(f"   📈 Data Distribution:")
                for classification in classifications:
                    values = chart_data.get(classification, [])
                    total_for_classification = sum(values)
                    if total_for_classification > 0:
                        print(f"      {classification}: {total_for_classification} users")
                
                # Show age group breakdown
                print(f"   📊 Age Group Breakdown:")
                for i, age_group in enumerate(age_groups):
                    group_total = 0
                    for classification in classifications:
                        values = chart_data.get(classification, [])
                        if i < len(values):
                            group_total += values[i]
                    if group_total > 0:
                        print(f"      {age_group}: {group_total} users")
                        
            else:
                print(f"   ❌ API Error: {data.get('message', 'Unknown error')}")
                
        except requests.exceptions.RequestException as e:
            print(f"   ❌ Request Error: {e}")
        except json.JSONDecodeError as e:
            print(f"   ❌ JSON Error: {e}")
        except Exception as e:
            print(f"   ❌ Unexpected Error: {e}")

if __name__ == "__main__":
    print("🧪 Testing Hybrid Classification System (WHO + BMI-for-Age + Adult BMI)")
    test_hybrid_classification()
