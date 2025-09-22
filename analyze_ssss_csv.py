#!/usr/bin/env python3
"""
Analyze ssss.csv for WHO standards accuracy
Check age ranges, totals, and data consistency
"""

import pandas as pd
from datetime import datetime, date
import json

def calculate_age_in_months(birthday_str, screening_date_str):
    """Calculate age in months"""
    try:
        birthday = datetime.strptime(birthday_str, '%Y-%m-%d').date()
        screening_date = datetime.strptime(screening_date_str, '%Y-%m-%d %H:%M:%S').date()
        
        years = screening_date.year - birthday.year
        months = screening_date.month - birthday.month
        total_months = years * 12 + months
        
        # Adjust for day difference
        if screening_date.day < birthday.day:
            total_months -= 1
            
        return max(0, total_months)
    except Exception as e:
        print(f"Age calculation error for {birthday_str}: {e}")
        return 0

def analyze_who_standards():
    """Analyze WHO standards eligibility for ssss.csv data"""
    
    print("🔍 SSSS.CSV WHO STANDARDS ANALYSIS")
    print("=" * 60)
    
    # Load the CSV file
    try:
        df = pd.read_csv('ssss.csv')
        print(f"✅ Loaded CSV with {len(df)} users")
    except Exception as e:
        print(f"❌ Error loading CSV: {e}")
        return
    
    # WHO Standards age restrictions
    age_restrictions = {
        'weight-for-age': {'min': 0, 'max': 71},      # 0-71 months (0-5.9 years)
        'height-for-age': {'min': 0, 'max': 71},      # 0-71 months (0-5.9 years)
        'weight-for-height': {'min': 0, 'max': 60},   # 0-60 months (0-5 years)
        'bmi-for-age': {'min': 24, 'max': 228},       # 24-228 months (2-19 years)
        'bmi-adult': {'min': 228, 'max': 999}         # 228+ months (19+ years)
    }
    
    # Calculate age for each user
    df['age_months'] = df.apply(lambda row: calculate_age_in_months(
        row['birthday'], row['screening_date']
    ), axis=1)
    
    # Add age in years for readability
    df['age_years'] = df['age_months'] / 12
    
    print(f"\n📊 AGE DISTRIBUTION:")
    print("-" * 40)
    print(f"Total users: {len(df)}")
    print(f"Age range: {df['age_months'].min():.0f} - {df['age_months'].max():.0f} months")
    print(f"Age range: {df['age_years'].min():.1f} - {df['age_years'].max():.1f} years")
    
    # Analyze each WHO standard
    print(f"\n📊 WHO STANDARDS ELIGIBILITY:")
    print("-" * 40)
    
    results = {}
    
    for standard, restrictions in age_restrictions.items():
        min_age = restrictions['min']
        max_age = restrictions['max']
        
        # Count eligible users
        eligible = df[(df['age_months'] >= min_age) & (df['age_months'] <= max_age)]
        ineligible = df[(df['age_months'] < min_age) | (df['age_months'] > max_age)]
        
        results[standard] = {
            'eligible_count': len(eligible),
            'ineligible_count': len(ineligible),
            'total_count': len(df),
            'age_range': f"{min_age}-{max_age} months"
        }
        
        print(f"\n{standard.upper()}:")
        print(f"  Age range: {min_age}-{max_age} months ({min_age/12:.1f}-{max_age/12:.1f} years)")
        print(f"  Eligible users: {len(eligible)}")
        print(f"  Ineligible users: {len(ineligible)}")
        print(f"  Percentage eligible: {(len(eligible)/len(df)*100):.1f}%")
        
        if len(eligible) > 0:
            print(f"  Eligible age range: {eligible['age_months'].min():.0f}-{eligible['age_months'].max():.0f} months")
    
    # Compare with expected values from manual_data_check.py
    print(f"\n📊 COMPARISON WITH EXPECTED VALUES:")
    print("-" * 40)
    
    expected_totals = {
        'weight-for-age': 42,
        'height-for-age': 42,
        'weight-for-height': 63,
        'bmi-for-age': 47,
        'bmi-adult': 21
    }
    
    for standard, expected in expected_totals.items():
        actual = results[standard]['eligible_count']
        diff = actual - expected
        status = "✅" if actual == expected else "❌"
        print(f"{status} {standard}: {actual} (expected {expected}, diff: {diff:+d})")
    
    # Detailed age analysis
    print(f"\n📊 DETAILED AGE ANALYSIS:")
    print("-" * 40)
    
    # Group by age ranges
    age_groups = {
        '0-5 years (0-60 months)': df[df['age_months'] <= 60],
        '2-5 years (24-60 months)': df[(df['age_months'] >= 24) & (df['age_months'] <= 60)],
        '5-19 years (60-228 months)': df[(df['age_months'] > 60) & (df['age_months'] <= 228)],
        '19+ years (228+ months)': df[df['age_months'] > 228]
    }
    
    for group_name, group_df in age_groups.items():
        print(f"{group_name}: {len(group_df)} users")
        if len(group_df) > 0:
            print(f"  Age range: {group_df['age_months'].min():.0f}-{group_df['age_months'].max():.0f} months")
    
    # Check for data quality issues
    print(f"\n📊 DATA QUALITY CHECK:")
    print("-" * 40)
    
    # Check for missing data
    missing_weight = df['weight'].isna().sum()
    missing_height = df['height'].isna().sum()
    missing_birthday = df['birthday'].isna().sum()
    missing_screening_date = df['screening_date'].isna().sum()
    
    print(f"Missing weight: {missing_weight}")
    print(f"Missing height: {missing_height}")
    print(f"Missing birthday: {missing_birthday}")
    print(f"Missing screening_date: {missing_screening_date}")
    
    # Check for invalid data
    invalid_weight = (df['weight'] <= 0).sum()
    invalid_height = (df['height'] <= 0).sum()
    
    print(f"Invalid weight (≤0): {invalid_weight}")
    print(f"Invalid height (≤0): {invalid_height}")
    
    # Save detailed results
    print(f"\n💾 SAVING DETAILED RESULTS...")
    
    # Create detailed user analysis
    user_analysis = []
    for _, user in df.iterrows():
        user_data = {
            'name': user['name'],
            'email': user['email'],
            'age_months': user['age_months'],
            'age_years': user['age_years'],
            'weight': user['weight'],
            'height': user['height'],
            'sex': user['sex'],
            'birthday': user['birthday'],
            'screening_date': user['screening_date'],
            'eligibility': {}
        }
        
        # Check eligibility for each standard
        for standard, restrictions in age_restrictions.items():
            is_eligible = (user['age_months'] >= restrictions['min'] and 
                          user['age_months'] <= restrictions['max'])
            user_data['eligibility'][standard] = is_eligible
        
        user_analysis.append(user_data)
    
    # Save to JSON
    with open('ssss_analysis_results.json', 'w') as f:
        json.dump({
            'summary': results,
            'expected_comparison': expected_totals,
            'user_analysis': user_analysis
        }, f, indent=2)
    
    print(f"✅ Results saved to ssss_analysis_results.json")
    
    return results

def main():
    """Main analysis function"""
    results = analyze_who_standards()
    
    print(f"\n🎯 SUMMARY:")
    print("=" * 60)
    print("The ssss.csv file contains test data for WHO standards validation.")
    print("This analysis shows how many users are eligible for each standard")
    print("based on their age at the time of screening.")

if __name__ == "__main__":
    main()
