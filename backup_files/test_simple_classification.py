#!/usr/bin/env python3
"""
Simple test to debug classification issue
"""

import requests
import json

def test_simple_classification():
    base_url = "https://nutrisaur-production.up.railway.app"
    
    print("🔍 Simple Classification Test")
    print("=" * 40)
    
    # Test the donut chart data first
    print("📊 Donut Chart Data (Height-for-Age):")
    donut_url = f"{base_url}/api/DatabaseAPI.php"
    donut_params = {
        'action': 'get_all_who_classifications_bulk',
        'time_frame': '1d',
        'barangay': '',
        'who_standard': 'height-for-age'
    }
    
    try:
        donut_response = requests.get(donut_url, params=donut_params, timeout=10)
        donut_data = donut_response.json()
        
        if donut_data.get('success'):
            height_data = donut_data['data'].get('height_for_age', {})
            print(f"   Severely Stunted: {height_data.get('Severely Stunted', 0)}")
            print(f"   Stunted: {height_data.get('Stunted', 0)}")
            print(f"   Normal: {height_data.get('Normal', 0)}")
            print(f"   Tall: {height_data.get('Tall', 0)}")
        else:
            print(f"   ❌ Donut chart error: {donut_data.get('message')}")
    except Exception as e:
        print(f"   ❌ Donut chart error: {e}")
    
    print("\n📊 Age Classification Chart Data:")
    chart_url = f"{base_url}/api/DatabaseAPI.php"
    chart_params = {
        'action': 'get_age_classification_chart',
        'barangay': '',
        'time_frame': '1d',
        'from_months': 0,
        'to_months': 71
    }
    
    try:
        chart_response = requests.get(chart_url, params=chart_params, timeout=10)
        chart_data = chart_response.json()
        
        if chart_data.get('success'):
            chart_data_dict = chart_data['data']['chartData']
            print(f"   Severely Stunted: {sum(chart_data_dict.get('Severely Stunted', []))}")
            print(f"   Stunted: {sum(chart_data_dict.get('Stunted', []))}")
            print(f"   Normal: {sum(chart_data_dict.get('Normal', []))}")
            print(f"   Tall: {sum(chart_data_dict.get('Tall', []))}")
            
            # Check if there are any non-zero values
            total_users = 0
            for classification in chart_data_dict:
                values = chart_data_dict[classification]
                total = sum(values)
                total_users += total
                if total > 0:
                    print(f"   ✅ {classification}: {total} users")
                else:
                    print(f"   ❌ {classification}: 0 users")
            
            print(f"\n   Total users: {total_users}")
        else:
            print(f"   ❌ Chart error: {chart_data.get('message')}")
    except Exception as e:
        print(f"   ❌ Chart error: {e}")

if __name__ == "__main__":
    test_simple_classification()
