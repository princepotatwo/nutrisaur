#!/usr/bin/env python3
"""
Final test to verify all classifications are working
"""

import requests
import json

def test_final_classification():
    base_url = "https://nutrisaur-production.up.railway.app"
    
    print("🔍 Final Classification Test")
    print("=" * 50)
    
    # Test the age classification chart
    url = f"{base_url}/api/DatabaseAPI.php"
    params = {
        'action': 'get_age_classification_chart',
        'barangay': '',
        'time_frame': '1d',
        'from_months': 0,
        'to_months': 71  # WHO standards range
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
            
            print(f"\n📈 Classification Summary:")
            total_users = 0
            missing_classifications = []
            
            for classification in classifications:
                values = chart_data.get(classification, [])
                total_for_classification = sum(values)
                total_users += total_for_classification
                
                if total_for_classification > 0:
                    print(f"   ✅ {classification}: {total_for_classification} users")
                else:
                    print(f"   ❌ {classification}: 0 users")
                    missing_classifications.append(classification)
            
            print(f"\n👥 Total Users: {total_users}")
            
            if missing_classifications:
                print(f"\n❌ Missing Classifications: {missing_classifications}")
                print("   This means the classification logic is not working correctly.")
            else:
                print(f"\n✅ All classifications have data!")
                
            # Show detailed breakdown for first few age groups
            print(f"\n📊 Detailed Breakdown (First 3 Age Groups):")
            for i, age_group in enumerate(age_groups[:3]):
                print(f"   {age_group}:")
                for classification in classifications:
                    values = chart_data.get(classification, [])
                    if i < len(values) and values[i] > 0:
                        print(f"      {classification}: {values[i]} users")
                        
        else:
            print(f"❌ API Error: {data.get('message', 'Unknown error')}")
            
    except Exception as e:
        print(f"❌ Error: {e}")

if __name__ == "__main__":
    test_final_classification()
