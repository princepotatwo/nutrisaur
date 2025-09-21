#!/usr/bin/env python3
"""
Debug the age group overlap logic
"""

def debug_age_logic():
    # Standard age groups
    standard_age_groups = {
        '0-6m': [0, 6],
        '6-12m': [6, 12],
        '1-2y': [12, 24],
        '2-3y': [24, 36],
        '3-4y': [36, 48],
        '4-5y': [48, 60],
        '5-6y': [60, 72]
    }
    
    # Test cases
    test_cases = [
        {"from_months": 0, "to_months": 71, "description": "Full range"},
        {"from_months": 12, "to_months": 36, "description": "12-36 months"},
        {"from_months": 24, "to_months": 48, "description": "24-48 months"},
        {"from_months": 60, "to_months": 72, "description": "60-72 months"},
        {"from_months": 0, "to_months": 12, "description": "0-12 months"},
        {"from_months": 0, "to_months": 1200, "description": "0-1200 months (100 years)"}
    ]
    
    for test_case in test_cases:
        print(f"\n🔍 Testing: {test_case['description']}")
        print(f"   Range: {test_case['from_months']} to {test_case['to_months']} months")
        
        # Apply WHO standards limit
        who_max_months = 71
        effective_from_months = max(test_case['from_months'], 0)
        effective_to_months = min(test_case['to_months'], who_max_months)
        
        print(f"   Effective range: {effective_from_months} to {effective_to_months} months")
        
        age_groups = []
        
        for label, range_data in standard_age_groups.items():
            group_start = range_data[0]
            group_end = range_data[1]
            
            # Check overlap logic
            overlaps = group_start < effective_to_months and group_end > effective_from_months
            
            print(f"   {label}: [{group_start}, {group_end}) - Overlaps: {overlaps}")
            print(f"      Condition: {group_start} < {effective_to_months} AND {group_end} > {effective_from_months}")
            print(f"      Result: {group_start < effective_to_months} AND {group_end > effective_from_months} = {overlaps}")
            
            if overlaps:
                age_groups.append(label)
        
        print(f"   ✅ Final age groups: {age_groups}")

if __name__ == "__main__":
    debug_age_logic()
