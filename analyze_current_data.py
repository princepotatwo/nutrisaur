#!/usr/bin/env python3
"""
Analyze current WHO classification data and compare with expected results
"""

import requests
import json
from collections import defaultdict

def analyze_current_data():
    """Analyze current data from the API"""
    
    base_url = "https://nutrisaur-production.up.railway.app"
    api_url = f"{base_url}/api/DatabaseAPI.php"
    
    who_standards = [
        'weight-for-age',
        'height-for-age', 
        'weight-for-height',
        'bmi-for-age',
        'bmi-adult'
    ]
    
    print("🔍 CURRENT DATA ANALYSIS")
    print("=" * 60)
    
    current_results = {}
    
    for standard in who_standards:
        print(f"\n📊 Analyzing {standard.upper()}")
        print("-" * 40)
        
        params = {
            'action': 'get_all_who_classifications_bulk',
            'barangay': '',
            'who_standard': standard
        }
        
        try:
            response = requests.get(api_url, params=params, timeout=10)
            print(f"Status Code: {response.status_code}")
            
            if response.status_code == 200:
                data = response.json()
                print(f"Success: {data.get('success', 'N/A')}")
                
                if data.get('success'):
                    total_users = data.get('total_users', 0)
                    classifications = data.get('data', {}).get(standard.replace('-', '_'), {})
                    
                    print(f"Total Users: {total_users}")
                    print(f"Classifications: {classifications}")
                    
                    # Calculate sum of classifications
                    total_classifications = sum(classifications.values()) if isinstance(classifications, dict) else 0
                    print(f"Sum of Classifications: {total_classifications}")
                    
                    # Store results
                    current_results[standard] = {
                        'total_users': total_users,
                        'classifications': classifications,
                        'sum_classifications': total_classifications
                    }
                    
                    # Show distribution
                    if isinstance(classifications, dict):
                        print(f"Distribution:")
                        for classification, count in classifications.items():
                            if count > 0:
                                percentage = (count / total_users) * 100 if total_users > 0 else 0
                                print(f"  {classification}: {count} ({percentage:.1f}%)")
                    
                    # Check for accuracy
                    if total_classifications == total_users:
                        print("✅ ACCURATE - Totals match")
                    else:
                        print(f"❌ INACCURATE - Mismatch: {total_users} vs {total_classifications}")
                    
                else:
                    print(f"❌ API returned success: false")
                    print(f"Error: {data.get('error', 'Unknown error')}")
                    current_results[standard] = {'error': data.get('error', 'Unknown error')}
            else:
                print(f"❌ HTTP Error: {response.status_code}")
                print(f"Response: {response.text}")
                current_results[standard] = {'error': f'HTTP {response.status_code}'}
                
        except requests.exceptions.RequestException as e:
            print(f"❌ Request failed: {e}")
            current_results[standard] = {'error': str(e)}
        except json.JSONDecodeError as e:
            print(f"❌ JSON decode error: {e}")
            current_results[standard] = {'error': str(e)}

    return current_results

def compare_with_expected():
    """Compare current results with expected results"""
    
    expected_results = {
        'weight-for-age': {
            'total': 16,
            'classifications': {
                'Severely Underweight': 4,
                'Underweight': 4,
                'Normal': 4,
                'Overweight': 4,
                'Obese': 0
            }
        },
        'height-for-age': {
            'total': 13,
            'classifications': {
                'Severely Stunted': 3,
                'Stunted': 3,
                'Normal': 4,
                'Tall': 3
            }
        },
        'weight-for-height': {
            'total': 12,
            'classifications': {
                'Severely Wasted': 3,
                'Wasted': 3,
                'Normal': 3,
                'Overweight': 2,
                'Obese': 1
            }
        },
        'bmi-for-age': {
            'total': 16,
            'classifications': {
                'Severely Underweight': 2,
                'Underweight': 2,
                'Normal': 4,
                'Overweight': 4,
                'Obese': 4
            }
        },
        'bmi-adult': {
            'total': 16,
            'classifications': {
                'Underweight': 4,
                'Normal': 4,
                'Overweight': 4,
                'Obese': 4
            }
        }
    }
    
    print(f"\n\n📊 COMPARISON WITH EXPECTED RESULTS")
    print("=" * 60)
    
    current_results = analyze_current_data()
    
    for standard, expected in expected_results.items():
        print(f"\n🔍 {standard.upper()}")
        print("-" * 40)
        
        if standard in current_results and 'error' not in current_results[standard]:
            current = current_results[standard]
            expected_total = expected['total']
            current_total = current['total_users']
            
            print(f"Expected Total: {expected_total}")
            print(f"Current Total: {current_total}")
            
            if current_total == expected_total:
                print("✅ TOTAL MATCHES EXPECTED")
            else:
                difference = current_total - expected_total
                print(f"❌ TOTAL MISMATCH: {difference:+d} users")
                
                if current_total > expected_total:
                    print(f"   → {current_total - expected_total} extra users")
                else:
                    print(f"   → {expected_total - current_total} missing users")
            
            # Compare classifications
            print(f"\nClassification Comparison:")
            current_classifications = current['classifications']
            expected_classifications = expected['classifications']
            
            all_classifications = set(current_classifications.keys()) | set(expected_classifications.keys())
            
            for classification in sorted(all_classifications):
                current_count = current_classifications.get(classification, 0)
                expected_count = expected_classifications.get(classification, 0)
                
                if current_count == expected_count:
                    print(f"  ✅ {classification}: {current_count} (matches)")
                else:
                    difference = current_count - expected_count
                    print(f"  ❌ {classification}: {current_count} (expected {expected_count}, {difference:+d})")
        
        else:
            error = current_results.get(standard, {}).get('error', 'Unknown error')
            print(f"❌ ERROR: {error}")

def analyze_user_distribution():
    """Analyze the distribution of users across age ranges"""
    
    print(f"\n\n👥 USER DISTRIBUTION ANALYSIS")
    print("=" * 60)
    
    base_url = "https://nutrisaur-production.up.railway.app"
    api_url = f"{base_url}/api/DatabaseAPI.php"
    
    # Get all users to analyze age distribution
    params = {
        'action': 'get_all_who_classifications_bulk',
        'barangay': '',
        'who_standard': 'weight-for-age'  # Use this to get all user data
    }
    
    try:
        response = requests.get(api_url, params=params, timeout=15)
        if response.status_code == 200:
            data = response.json()
            if data.get('success'):
                total_users = data.get('total_users', 0)
                print(f"Total Users in Database: {total_users}")
                
                # Analyze each WHO standard's coverage
                who_standards = ['weight_for_age', 'height_for_age', 'weight_for_height', 'bmi_for_age', 'bmi_adult']
                
                print(f"\nCoverage by WHO Standard:")
                for standard in who_standards:
                    standard_data = data.get('data', {}).get(standard, {})
                    standard_total = sum(standard_data.values()) if isinstance(standard_data, dict) else 0
                    percentage = (standard_total / total_users) * 100 if total_users > 0 else 0
                    print(f"  {standard.replace('_', '-').title()}: {standard_total} users ({percentage:.1f}%)")
                
                # Check for overlapping users
                print(f"\nPotential Issues:")
                if total_users > 80:  # Expected max from our test CSV
                    print(f"  ⚠️  More users than expected ({total_users} > 80)")
                    print(f"      → May have old data that wasn't cleared")
                
                # Check for missing data
                empty_standards = []
                for standard in who_standards:
                    standard_data = data.get('data', {}).get(standard, {})
                    if not standard_data or all(count == 0 for count in standard_data.values()):
                        empty_standards.append(standard)
                
                if empty_standards:
                    print(f"  ❌ Empty standards: {', '.join(empty_standards)}")
                else:
                    print(f"  ✅ All standards have data")
                    
    except Exception as e:
        print(f"❌ Error analyzing user distribution: {e}")

def main():
    """Main analysis function"""
    
    print("🚀 WHO CLASSIFICATION DATA ANALYSIS")
    print("=" * 60)
    print("Analyzing current data and comparing with expected results...")
    
    # Run analysis
    compare_with_expected()
    analyze_user_distribution()
    
    print(f"\n\n🎯 SUMMARY")
    print("=" * 60)
    print("Based on your observations:")
    print("  - Weight-for-Age: 42 users (expected 16)")
    print("  - Height-for-Age: 30 users (expected 13)")  
    print("  - Weight-for-Height: 42 users (expected 12)")
    print("  - BMI-for-Age: 42 users (expected 16)")
    print("  - BMI-Adult: 5 users (expected 16)")
    print()
    print("🔍 POTENTIAL ISSUES:")
    print("  1. Old data may not have been cleared")
    print("  2. Users may be counted in multiple WHO standards")
    print("  3. Age eligibility filtering may not be working correctly")
    print("  4. BMI-Adult has very few users (5 vs expected 16)")
    print()
    print("💡 RECOMMENDATIONS:")
    print("  1. Clear all existing data before uploading test CSV")
    print("  2. Verify age calculations are correct")
    print("  3. Check WHO standard eligibility rules")
    print("  4. Ensure each user is only counted once per standard")

if __name__ == "__main__":
    main()
