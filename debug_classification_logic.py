#!/usr/bin/env python3
"""
Debug the classification logic to see why Tall, Severely Stunted, and Underweight are missing
"""

import requests
import json

def debug_classification_logic():
    base_url = "https://nutrisaur-production.up.railway.app"
    
    print("🔍 Debugging Classification Logic")
    print("=" * 50)
    
    # Test with a smaller range to focus on WHO standards (0-71 months)
    url = f"{base_url}/api/DatabaseAPI.php"
    params = {
        'action': 'get_age_classification_chart',
        'barangay': '',
        'time_frame': '1d',
        'from_months': 0,
        'to_months': 71  # Focus on WHO standards range
    }
    
    try:
        response = requests.get(url, params=params, timeout=10)
        response.raise_for_status()
        data = response.json()
        
        if data.get('success'):
            age_groups = data['data']['ageGroups']
            classifications = data['data']['classifications']
            chart_data = data['data']['chartData']
            
            print(f"📊 WHO Standards Range (0-71 months):")
            print(f"   Age Groups: {age_groups}")
            print(f"   Total Users: {sum(sum(chart_data.get(c, [])) for c in classifications)}")
            
            print(f"\n📈 Classification Breakdown:")
            for classification in classifications:
                values = chart_data.get(classification, [])
                total = sum(values)
                if total > 0:
                    print(f"   ✅ {classification}: {total} users")
                    # Show which age groups have this classification
                    for i, value in enumerate(values):
                        if value > 0:
                            print(f"      - {age_groups[i]}: {value} users")
                else:
                    print(f"   ❌ {classification}: 0 users")
            
            # Now test the donut chart data to compare
            print(f"\n🔍 Comparing with Donut Chart Data:")
            donut_tests = [
                {'standard': 'height-for-age', 'expected': ['Severely Stunted', 'Stunted', 'Normal', 'Tall']},
                {'standard': 'weight-for-age', 'expected': ['Severely Underweight', 'Underweight', 'Normal', 'Overweight', 'Obese']},
                {'standard': 'weight-for-height', 'expected': ['Severely Wasted', 'Wasted', 'Normal', 'Overweight', 'Obese']}
            ]
            
            for test in donut_tests:
                donut_url = f"{base_url}/api/DatabaseAPI.php"
                donut_params = {
                    'action': 'get_all_who_classifications_bulk',
                    'time_frame': '1d',
                    'barangay': '',
                    'who_standard': test['standard']
                }
                
                try:
                    donut_response = requests.get(donut_url, params=donut_params, timeout=10)
                    donut_data = donut_response.json()
                    
                    if donut_data.get('success'):
                        donut_classifications = donut_data['data'].get(test['standard'].replace('-', '_'), {})
                        print(f"\n   📊 {test['standard'].upper()}:")
                        for expected in test['expected']:
                            count = donut_classifications.get(expected, 0)
                            if count > 0:
                                print(f"      ✅ {expected}: {count} users")
                            else:
                                print(f"      ❌ {expected}: 0 users")
                    else:
                        print(f"   ❌ Failed to get {test['standard']} data")
                        
                except Exception as e:
                    print(f"   ❌ Error getting {test['standard']}: {e}")
                    
        else:
            print(f"❌ API Error: {data.get('message', 'Unknown error')}")
            
    except Exception as e:
        print(f"❌ Error: {e}")

if __name__ == "__main__":
    debug_classification_logic()
