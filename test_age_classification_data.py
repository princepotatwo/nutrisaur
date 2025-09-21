#!/usr/bin/env python3
"""
Test script to analyze age classification data inconsistencies
"""

import requests
import json
from collections import defaultdict

def test_age_classification_api():
    """Test the age classification API and analyze the data"""
    
    # Test parameters
    base_url = "https://nutrisaur-production.up.railway.app"
    api_url = f"{base_url}/api/DatabaseAPI.php"
    
    params = {
        'action': 'get_age_classification_chart',
        'barangay': '',
        'from_months': 0,
        'to_months': 71,
        'who_standard': 'weight-for-age'
    }
    
    print("🔍 Testing Age Classification API")
    print(f"URL: {api_url}")
    print(f"Params: {params}")
    print("-" * 50)
    
    try:
        response = requests.get(api_url, params=params, timeout=10)
        print(f"Status Code: {response.status_code}")
        
        if response.status_code == 200:
            data = response.json()
            print(f"Response Type: {type(data)}")
            print(f"Success: {data.get('success', 'N/A')}")
            
            if data.get('success'):
                chart_data = data.get('chartData', {})
                total_users = data.get('totalUsers', 0)
                age_groups = data.get('ageGroups', [])
                classifications = data.get('classifications', [])
                
                print(f"\n📊 Data Analysis:")
                print(f"Total Users: {total_users}")
                print(f"Age Groups: {len(age_groups)} - {age_groups}")
                print(f"Classifications: {classifications}")
                
                print(f"\n📈 Chart Data Breakdown:")
                total_chart_users = 0
                
                for classification, values in chart_data.items():
                    if isinstance(values, list):
                        classification_total = sum(values)
                        total_chart_users += classification_total
                        print(f"{classification}: {values} (Total: {classification_total})")
                    else:
                        print(f"{classification}: {values} (Not a list)")
                
                print(f"\n🔍 Totals Comparison:")
                print(f"API Total Users: {total_users}")
                print(f"Chart Data Sum: {total_chart_users}")
                print(f"Difference: {total_users - total_chart_users}")
                
                if total_chart_users != total_users:
                    print(f"\n❌ MISMATCH DETECTED!")
                    print(f"The chart data doesn't add up to the total users")
                    print(f"This explains why the line chart shows incorrect totals")
                else:
                    print(f"\n✅ Totals match - data is consistent")
                
                # Analyze age distribution patterns
                print(f"\n📊 Age Distribution Analysis:")
                for classification, values in chart_data.items():
                    if isinstance(values, list) and sum(values) > 0:
                        print(f"\n{classification}:")
                        for i, count in enumerate(values):
                            if count > 0:
                                age_group = age_groups[i] if i < len(age_groups) else f"Group {i}"
                                print(f"  {age_group}: {count} users")
                
            else:
                print(f"❌ API returned success: false")
                print(f"Error: {data.get('error', 'Unknown error')}")
        else:
            print(f"❌ HTTP Error: {response.status_code}")
            print(f"Response: {response.text}")
            
    except requests.exceptions.RequestException as e:
        print(f"❌ Request failed: {e}")
    except json.JSONDecodeError as e:
        print(f"❌ JSON decode error: {e}")
        print(f"Raw response: {response.text}")

def test_bulk_api_for_comparison():
    """Test the bulk API to compare with age classification data"""
    
    base_url = "https://nutrisaur-production.up.railway.app"
    api_url = f"{base_url}/api/DatabaseAPI.php"
    
    params = {
        'action': 'get_all_who_classifications_bulk',
        'barangay': '',
        'who_standard': 'weight-for-age'
    }
    
    print(f"\n🔍 Testing Bulk API for Comparison")
    print(f"URL: {api_url}")
    print(f"Params: {params}")
    print("-" * 50)
    
    try:
        response = requests.get(api_url, params=params, timeout=10)
        print(f"Status Code: {response.status_code}")
        
        if response.status_code == 200:
            data = response.json()
            print(f"Response Type: {type(data)}")
            print(f"Success: {data.get('success', 'N/A')}")
            
            if data.get('success'):
                total_users = data.get('total_users', 0)
                classifications = data.get('classifications', {})
                
                print(f"\n📊 Bulk API Data:")
                print(f"Total Users: {total_users}")
                print(f"Classifications: {classifications}")
                
                total_bulk = sum(classifications.values()) if isinstance(classifications, dict) else 0
                print(f"Sum of Classifications: {total_bulk}")
                
                if total_bulk != total_users:
                    print(f"❌ Bulk API also has mismatch: {total_users} vs {total_bulk}")
                else:
                    print(f"✅ Bulk API totals are consistent")
                    
        else:
            print(f"❌ HTTP Error: {response.status_code}")
            print(f"Response: {response.text}")
            
    except requests.exceptions.RequestException as e:
        print(f"❌ Request failed: {e}")
    except json.JSONDecodeError as e:
        print(f"❌ JSON decode error: {e}")

if __name__ == "__main__":
    print("🧪 Age Classification Data Analysis")
    print("=" * 60)
    
    # Test age classification API
    test_age_classification_api()
    
    # Test bulk API for comparison
    test_bulk_api_for_comparison()
    
    print(f"\n🎯 Conclusion:")
    print(f"If there's a mismatch between API totals and chart data sums,")
    print(f"the line chart will show incorrect totals because it's using")
    print(f"the chart data arrays instead of the API total_users value.")
