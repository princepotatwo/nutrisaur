#!/usr/bin/env python3
"""
Simulation of WHO Classifications API Logic
This recreates the current API logic in Python for testing and debugging
"""

import json
import requests
from datetime import datetime, date
from typing import Dict, List, Any, Tuple

class WHOSimulation:
    def __init__(self):
        self.age_restrictions = {
            'weight-for-age': {'min': 0, 'max': 71},      # 0-71 months (0-5.9 years)
            'height-for-age': {'min': 0, 'max': 71},      # 0-71 months (0-5.9 years)
            'weight-for-height': {'min': 0, 'max': 60},   # 0-60 months (0-5 years)
            'bmi-for-age': {'min': 24, 'max': 228},       # 24-228 months (2-19 years)
            'bmi-adult': {'min': 228, 'max': 999}         # 228+ months (19+ years)
        }
        
        # Initialize classification arrays (matching API structure)
        self.all_classifications = {
            'weight_for_age': {
                'Severely Underweight': 0, 'Underweight': 0, 'Normal': 0, 
                'Overweight': 0, 'Obese': 0, 'No Data': 0
            },
            'height_for_age': {
                'Severely Stunted': 0, 'Stunted': 0, 'Normal': 0, 
                'Tall': 0, 'No Data': 0
            },
            'weight_for_height': {
                'Severely Wasted': 0, 'Wasted': 0, 'Normal': 0, 
                'Overweight': 0, 'Obese': 0, 'No Data': 0
            },
            'bmi_for_age': {
                'Severely Underweight': 0, 'Underweight': 0, 'Normal': 0, 
                'Overweight': 0, 'Obese': 0, 'No Data': 0
            },
            'bmi_adult': {
                'Underweight': 0, 'Normal': 0, 'Overweight': 0, 
                'Obese': 0, 'No Data': 0
            }
        }

    def calculate_age_in_months(self, birthday: str, screening_date: str = None) -> int:
        """Calculate age in months (simplified version)"""
        try:
            if not birthday:
                return 0
                
            # Parse birthday
            if isinstance(birthday, str):
                if '-' in birthday:
                    birth_dt = datetime.strptime(birthday, '%Y-%m-%d').date()
                else:
                    birth_dt = datetime.strptime(birthday, '%Y-%m-%d').date()
            else:
                birth_dt = birthday
                
            # Use screening date or current date
            if screening_date:
                if isinstance(screening_date, str):
                    screen_dt = datetime.strptime(screening_date, '%Y-%m-%d').date()
                else:
                    screen_dt = screening_date
            else:
                screen_dt = date.today()
                
            # Calculate age in months
            years = screen_dt.year - birth_dt.year
            months = screen_dt.month - birth_dt.month
            total_months = years * 12 + months
            
            # Adjust for day difference
            if screen_dt.day < birth_dt.day:
                total_months -= 1
                
            return max(0, total_months)
        except Exception as e:
            print(f"Age calculation error: {e}")
            return 0

    def get_age_restrictions(self, who_standard: str) -> Dict[str, int]:
        """Get age restrictions for WHO standard"""
        return self.age_restrictions.get(who_standard, {'min': 0, 'max': 999})

    def process_who_standard(self, classifications: Dict[str, int], results: Dict[str, Any], 
                           standard: str, age_in_months: int, user: Dict[str, Any]) -> None:
        """Process individual WHO standard classification (matching API logic)"""
        # Age restrictions
        age_restrictions = self.get_age_restrictions(standard)
        if age_in_months < age_restrictions['min'] or age_in_months > age_restrictions['max']:
            classifications['No Data'] += 1
            return
        
        # Get classification from results (simplified - we'll use mock data for now)
        classification = 'No Data'
        if standard in results:
            classification = results[standard].get('classification', 'No Data')
        
        # Count classification
        if classification in classifications:
            classifications[classification] += 1
        else:
            classifications['No Data'] += 1

    def process_bmi_adult(self, classifications: Dict[str, int], user: Dict[str, Any], age_in_months: int) -> None:
        """Process BMI Adult classification (matching API logic)"""
        # Age restrictions for BMI Adult
        age_restrictions = self.get_age_restrictions('bmi-adult')
        if age_in_months < age_restrictions['min'] or age_in_months > age_restrictions['max']:
            classifications['No Data'] += 1
            return
        
        # Calculate BMI
        try:
            weight = float(user.get('weight', 0))
            height = float(user.get('height', 0))
            
            if weight > 0 and height > 0:
                height_m = height / 100  # Convert cm to meters
                bmi = weight / (height_m ** 2)
                
                # BMI Adult classifications
                if bmi < 18.5:
                    classifications['Underweight'] += 1
                elif bmi < 25:
                    classifications['Normal'] += 1
                elif bmi < 30:
                    classifications['Overweight'] += 1
                else:
                    classifications['Obese'] += 1
            else:
                classifications['No Data'] += 1
        except Exception:
            classifications['No Data'] += 1

    def simulate_current_api_logic(self, users: List[Dict[str, Any]]) -> Dict[str, Any]:
        """Simulate the current API logic"""
        print("🔄 SIMULATING CURRENT API LOGIC")
        print("=" * 50)
        
        # Reset classifications
        for standard in self.all_classifications:
            for classification in self.all_classifications[standard]:
                self.all_classifications[standard][classification] = 0
        
        total_processed = 0
        total_users = len(users)
        
        print(f"Processing {total_users} users...")
        
        # Process all users (matching API logic)
        for i, user in enumerate(users):
            try:
                # Calculate age once
                age_in_months = self.calculate_age_in_months(
                    user.get('birthday'), 
                    user.get('screening_date')
                )
                
                print(f"User {i+1}: Age {age_in_months} months, Weight: {user.get('weight')}, Height: {user.get('height')}")
                
                # Mock comprehensive assessment results (we'll use real API data)
                # For now, we'll simulate the classification logic
                assessment_results = {
                    'weight_for_age': {'classification': 'Normal'},
                    'height_for_age': {'classification': 'Normal'},
                    'weight_for_height': {'classification': 'Normal'},
                    'bmi_for_age': {'classification': 'Normal'}
                }
                
                # Process each WHO standard with proper age filtering
                self.process_who_standard(
                    self.all_classifications['weight_for_age'], 
                    assessment_results, 'weight-for-age', age_in_months, user
                )
                self.process_who_standard(
                    self.all_classifications['height_for_age'], 
                    assessment_results, 'height-for-age', age_in_months, user
                )
                self.process_who_standard(
                    self.all_classifications['weight_for_height'], 
                    assessment_results, 'weight-for-height', age_in_months, user
                )
                self.process_who_standard(
                    self.all_classifications['bmi_for_age'], 
                    assessment_results, 'bmi-for-age', age_in_months, user
                )
                self.process_bmi_adult(
                    self.all_classifications['bmi_adult'], user, age_in_months
                )
                
                total_processed += 1
                
            except Exception as e:
                print(f"Error processing user {i+1}: {e}")
                # Mark all as no data if processing failed
                for standard in self.all_classifications:
                    self.all_classifications[standard]['No Data'] += 1
        
        # Calculate actual totals for each WHO standard (excluding "No Data")
        actual_totals = {}
        for standard, classifications in self.all_classifications.items():
            total = 0
            for classification, count in classifications.items():
                if classification != 'No Data':
                    total += count
            actual_totals[standard] = total
        
        return {
            'success': True,
            'data': self.all_classifications,
            'total_users': total_users,
            'processed_users': total_processed,
            'actual_totals': actual_totals
        }

    def load_current_api_data(self) -> List[Dict[str, Any]]:
        """Load current API data for simulation"""
        try:
            with open('current_api_data.json', 'r') as f:
                data = json.load(f)
            
            # Extract user data from API response
            # We need to get the actual user data, not just the classifications
            # For now, we'll create mock user data based on the current counts
            users = []
            
            # Create mock users based on current data patterns
            # This is a simplified approach - in reality we'd need the actual user records
            for i in range(63):  # Current total users
                users.append({
                    'email': f'user{i+1}@test.com',
                    'birthday': '2015-01-01',  # Mock birthday
                    'screening_date': '2024-01-01',  # Mock screening date
                    'weight': 25.0,  # Mock weight
                    'height': 100.0,  # Mock height
                    'sex': 'M' if i % 2 == 0 else 'F'  # Mock sex
                })
            
            return users
        except Exception as e:
            print(f"Error loading API data: {e}")
            return []

    def print_results(self, results: Dict[str, Any]) -> None:
        """Print simulation results in the same format as the API"""
        print("\n🔍 SIMULATION RESULTS")
        print("=" * 60)
        
        standards = {
            'weight_for_age': 'WEIGHT-FOR-AGE',
            'height_for_age': 'HEIGHT-FOR-AGE',
            'weight_for_height': 'WEIGHT-FOR-HEIGHT',
            'bmi_for_age': 'BMI-FOR-AGE',
            'bmi_adult': 'BMI-ADULT'
        }
        
        for standard_key, standard_name in standards.items():
            print(f"\n📊 {standard_name}")
            print("-" * 40)
            
            classifications = results['data'][standard_key]
            total_users = results['total_users']
            sum_classifications = sum(classifications.values())
            
            print(f"✅ Simulation Success")
            print(f"Total Users: {total_users}")
            print(f"Classifications Sum: {sum_classifications}")
            
            if classifications:
                print(f"Classifications:")
                for classification, count in classifications.items():
                    if count > 0:
                        print(f"  {classification}: {count}")
            
            if total_users == sum_classifications:
                print(f"✅ DATA CONSISTENT")
            else:
                print(f"❌ DATA INCONSISTENT: {total_users} vs {sum_classifications}")
        
        # Summary
        print(f"\n\n📋 SUMMARY")
        print("=" * 60)
        print(f"Total Users Across All Standards: {results['total_users'] * 5}")
        print(f"Processed Users: {results['processed_users']}")
        
        # Expected vs Actual
        expected = {
            'weight_for_age': 16,
            'height_for_age': 13,
            'weight_for_height': 12,
            'bmi_for_age': 16,
            'bmi_adult': 16
        }
        
        print(f"\n📊 EXPECTED VS ACTUAL:")
        for standard_key, expected_count in expected.items():
            actual_count = results['actual_totals'].get(standard_key, 0)
            if actual_count == expected_count:
                print(f"  ✅ {standard_key}: {actual_count} (matches expected {expected_count})")
            else:
                diff = actual_count - expected_count
                print(f"  ❌ {standard_key}: {actual_count} (expected {expected_count}, {diff:+d})")

def main():
    """Main simulation function"""
    print("🧪 WHO CLASSIFICATIONS API SIMULATION")
    print("=" * 60)
    
    # Create simulation instance
    sim = WHOSimulation()
    
    # Load current API data
    print("📥 Loading current API data...")
    users = sim.load_current_api_data()
    
    if not users:
        print("❌ No user data available for simulation")
        return
    
    print(f"✅ Loaded {len(users)} users")
    
    # Run simulation
    results = sim.simulate_current_api_logic(users)
    
    # Print results
    sim.print_results(results)
    
    print(f"\n🎯 SIMULATION COMPLETE")
    print("=" * 60)

if __name__ == "__main__":
    main()
