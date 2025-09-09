#!/usr/bin/env python3
"""
WHO Growth Standards Stress Test
Tests the PHP implementation with 50 simulated users
"""

import requests
import json
import random
import time
from datetime import datetime, timedelta
import statistics

# Configuration
BASE_URL = "http://localhost/nutrisaur11/public/api"
# If running locally, adjust the URL accordingly
# BASE_URL = "http://localhost:8000/public/api"

class WHOStressTest:
    def __init__(self):
        self.results = []
        self.errors = []
        self.start_time = None
        self.end_time = None
        
    def generate_test_user(self, user_id):
        """Generate a realistic test user with random but valid data"""
        # Random age between 0-71 months
        age_months = random.randint(0, 71)
        
        # Calculate birth date
        birth_date = datetime.now() - timedelta(days=age_months * 30.44)  # Approximate days per month
        birth_date_str = birth_date.strftime('%Y-%m-%d')
        
        # Random sex
        sex = random.choice(['Male', 'Female'])
        
        # Generate realistic weight and height based on age and sex
        if sex == 'Male':
            # Boys: Weight ranges from 3.3kg (0 months) to 19.5kg (71 months)
            base_weight = 3.3 + (age_months * 0.23)  # Approximate growth curve
            weight = max(2.0, base_weight + random.uniform(-1.0, 1.0))
            
            # Boys: Height ranges from 49.9cm (0 months) to 113.8cm (71 months)
            base_height = 49.9 + (age_months * 0.9)  # Approximate growth curve
            height = max(45.0, base_height + random.uniform(-5.0, 5.0))
        else:
            # Girls: Weight ranges from 3.2kg (0 months) to 20.9kg (71 months)
            base_weight = 3.2 + (age_months * 0.25)  # Approximate growth curve
            weight = max(2.0, base_weight + random.uniform(-1.0, 1.0))
            
            # Girls: Height ranges from 49.1cm (0 months) to 112.7cm (71 months)
            base_height = 49.1 + (age_months * 0.89)  # Approximate growth curve
            height = max(45.0, base_height + random.uniform(-5.0, 5.0))
        
        return {
            'user_id': user_id,
            'weight': round(weight, 1),
            'height': round(height, 1),
            'birth_date': birth_date_str,
            'sex': sex,
            'age_months': age_months
        }
    
    def test_single_user(self, user_data):
        """Test a single user with the WHO Growth Standards API"""
        try:
            # Test the process_growth_standards endpoint
            url = f"{BASE_URL}/process_growth_standards.php"
            payload = {
                'weight': user_data['weight'],
                'height': user_data['height'],
                'birth_date': user_data['birth_date'],
                'sex': user_data['sex']
            }
            
            start_time = time.time()
            response = requests.post(url, data=payload, timeout=10)
            end_time = time.time()
            
            response_time = (end_time - start_time) * 1000  # Convert to milliseconds
            
            if response.status_code == 200:
                try:
                    result = response.json()
                    return {
                        'user_id': user_data['user_id'],
                        'success': True,
                        'response_time_ms': response_time,
                        'status_code': response.status_code,
                        'data': result,
                        'error': None
                    }
                except json.JSONDecodeError:
                    return {
                        'user_id': user_data['user_id'],
                        'success': False,
                        'response_time_ms': response_time,
                        'status_code': response.status_code,
                        'data': None,
                        'error': f"Invalid JSON response: {response.text[:100]}"
                    }
            else:
                return {
                    'user_id': user_data['user_id'],
                    'success': False,
                    'response_time_ms': response_time,
                    'status_code': response.status_code,
                    'data': None,
                    'error': f"HTTP {response.status_code}: {response.text[:100]}"
                }
                
        except requests.exceptions.Timeout:
            return {
                'user_id': user_data['user_id'],
                'success': False,
                'response_time_ms': 10000,  # 10 second timeout
                'status_code': None,
                'data': None,
                'error': "Request timeout (10s)"
            }
        except requests.exceptions.RequestException as e:
            return {
                'user_id': user_data['user_id'],
                'success': False,
                'response_time_ms': 0,
                'status_code': None,
                'data': None,
                'error': f"Request error: {str(e)}"
            }
        except Exception as e:
            return {
                'user_id': user_data['user_id'],
                'success': False,
                'response_time_ms': 0,
                'status_code': None,
                'data': None,
                'error': f"Unexpected error: {str(e)}"
            }
    
    def run_stress_test(self, num_users=50):
        """Run stress test with specified number of users"""
        print(f"🚀 Starting WHO Growth Standards Stress Test with {num_users} users...")
        print(f"📡 Testing endpoint: {BASE_URL}/process_growth_standards.php")
        print("=" * 60)
        
        self.start_time = time.time()
        
        # Generate test users
        test_users = [self.generate_test_user(i+1) for i in range(num_users)]
        
        # Test each user
        for i, user in enumerate(test_users):
            print(f"Testing user {i+1}/{num_users} (ID: {user['user_id']}, Age: {user['age_months']} months, Sex: {user['sex']})")
            
            result = self.test_single_user(user)
            self.results.append(result)
            
            if result['success']:
                print(f"  ✅ Success - {result['response_time_ms']:.1f}ms")
            else:
                print(f"  ❌ Failed - {result['error']}")
                self.errors.append(result)
        
        self.end_time = time.time()
        
        # Generate report
        self.generate_report()
    
    def generate_report(self):
        """Generate comprehensive test report"""
        total_time = self.end_time - self.start_time
        successful_tests = [r for r in self.results if r['success']]
        failed_tests = [r for r in self.results if not r['success']]
        
        print("\n" + "=" * 60)
        print("📊 STRESS TEST REPORT")
        print("=" * 60)
        
        # Basic statistics
        print(f"Total Users Tested: {len(self.results)}")
        print(f"Successful Tests: {len(successful_tests)}")
        print(f"Failed Tests: {len(failed_tests)}")
        print(f"Success Rate: {len(successful_tests)/len(self.results)*100:.1f}%")
        print(f"Total Test Time: {total_time:.2f} seconds")
        print(f"Average Time per User: {total_time/len(self.results):.2f} seconds")
        
        if successful_tests:
            response_times = [r['response_time_ms'] for r in successful_tests]
            print(f"\n📈 RESPONSE TIME STATISTICS")
            print(f"Average Response Time: {statistics.mean(response_times):.1f}ms")
            print(f"Median Response Time: {statistics.median(response_times):.1f}ms")
            print(f"Min Response Time: {min(response_times):.1f}ms")
            print(f"Max Response Time: {max(response_times):.1f}ms")
            print(f"Standard Deviation: {statistics.stdev(response_times):.1f}ms")
        
        # Error analysis
        if failed_tests:
            print(f"\n❌ ERROR ANALYSIS")
            error_types = {}
            for error in failed_tests:
                error_msg = error['error']
                error_types[error_msg] = error_types.get(error_msg, 0) + 1
            
            for error, count in error_types.items():
                print(f"  {error}: {count} occurrences")
        
        # Data validation
        print(f"\n🔍 DATA VALIDATION")
        valid_responses = 0
        invalid_responses = 0
        
        for result in successful_tests:
            if result['data'] and 'age_months' in result['data']:
                valid_responses += 1
            else:
                invalid_responses += 1
        
        print(f"Valid Responses: {valid_responses}")
        print(f"Invalid Responses: {invalid_responses}")
        
        # Sample successful results
        if successful_tests:
            print(f"\n📋 SAMPLE SUCCESSFUL RESULTS")
            for i, result in enumerate(successful_tests[:3]):  # Show first 3 successful results
                print(f"\nUser {result['user_id']}:")
                if result['data']:
                    data = result['data']
                    print(f"  Age: {data.get('age_months', 'N/A')} months")
                    print(f"  BMI: {data.get('bmi', 'N/A')}")
                    print(f"  Weight-for-Age: {data.get('weight_for_age', {}).get('classification', 'N/A')}")
                    print(f"  Height-for-Age: {data.get('height_for_age', {}).get('classification', 'N/A')}")
                    print(f"  Weight-for-Height: {data.get('weight_for_height', {}).get('classification', 'N/A')}")
                    print(f"  BMI-for-Age: {data.get('bmi_for_age', {}).get('classification', 'N/A')}")
        
        # Performance assessment
        print(f"\n⚡ PERFORMANCE ASSESSMENT")
        if successful_tests:
            avg_response_time = statistics.mean([r['response_time_ms'] for r in successful_tests])
            if avg_response_time < 100:
                print("🟢 EXCELLENT - Average response time < 100ms")
            elif avg_response_time < 500:
                print("🟡 GOOD - Average response time < 500ms")
            elif avg_response_time < 1000:
                print("🟠 ACCEPTABLE - Average response time < 1000ms")
            else:
                print("🔴 POOR - Average response time > 1000ms")
        
        if len(failed_tests) == 0:
            print("🟢 PERFECT - No failed tests!")
        elif len(failed_tests) < len(self.results) * 0.05:  # Less than 5% failure rate
            print("🟡 GOOD - Low failure rate")
        elif len(failed_tests) < len(self.results) * 0.10:  # Less than 10% failure rate
            print("🟠 ACCEPTABLE - Moderate failure rate")
        else:
            print("🔴 POOR - High failure rate")
        
        print("\n" + "=" * 60)
        print("✅ Stress test completed!")
        print("=" * 60)

def main():
    """Main function to run the stress test"""
    print("WHO Growth Standards Stress Test")
    print("Testing PHP implementation with 50 simulated users")
    print()
    
    # Check if we can reach the API
    try:
        response = requests.get(f"{BASE_URL}/process_growth_standards.php", timeout=5)
        print(f"✅ API endpoint reachable at {BASE_URL}")
    except requests.exceptions.RequestException as e:
        print(f"❌ Cannot reach API endpoint: {e}")
        print("Please ensure your PHP server is running and the URL is correct.")
        return
    
    # Run stress test
    stress_test = WHOStressTest()
    stress_test.run_stress_test(50)

if __name__ == "__main__":
    main()
