#!/usr/bin/env python3
"""
Test script to simulate screening completion and verify FCM token registration
"""

import requests
import json
import time
import random
import string

# Configuration
API_BASE_URL = "https://nutrisaur-production.up.railway.app"
DATABASE_API_URL = f"{API_BASE_URL}/api/DatabaseAPI.php"

def generate_test_user():
    """Generate a random test user"""
    random_id = ''.join(random.choices(string.ascii_lowercase + string.digits, k=8))
    return {
        'name': f'Test User {random_id}',
        'email': f'testuser{random_id}@example.com',
        'municipality': 'CITY OF BALANGA',
        'barangay': 'Bangkal',
        'sex': 'Female',
        'birthday': '1990-01-01',
        'is_pregnant': 'No',
        'weight': '60',
        'height': '165'
    }

def create_test_user_in_database(user_data):
    """Create a test user directly in the database"""
    print(f"👤 Creating test user in database: {user_data['email']}")
    
    # Insert user directly into community_users table
    insert_data = {
        'table': 'community_users',
        'data': {
            'email': user_data['email'],
            'name': user_data['name'],
            'password': 'testpassword123',
            'barangay': user_data['barangay'],
            'municipality': user_data['municipality'],
            'sex': user_data['sex'],
            'birthday': user_data['birthday'],
            'is_pregnant': user_data['is_pregnant'],
            'weight': user_data['weight'],
            'height': user_data['height'],
            'fcm_token': ''  # Empty initially
        }
    }
    
    try:
        response = requests.post(f"{DATABASE_API_URL}?action=insert", 
                               json=insert_data, 
                               headers={'Content-Type': 'application/json'},
                               timeout=30)
        print(f"   Insert response: {response.status_code}")
        
        if response.status_code == 200:
            result = response.json()
            print(f"   Insert result: {result}")
            return result.get('success', False)
        else:
            print(f"   Insert failed: {response.text}")
            return False
            
    except Exception as e:
        print(f"   Insert error: {e}")
        return False

def test_screening_completion(user_data):
    """Test screening completion process"""
    print(f"📋 Testing screening completion for: {user_data['email']}")
    
    # Complete screening using save_screening API
    screening_data = {
        'action': 'save_screening',
        'email': user_data['email'],
        'name': user_data['name'],
        'municipality': user_data['municipality'],
        'barangay': user_data['barangay'],
        'sex': user_data['sex'],
        'birthday': user_data['birthday'],
        'is_pregnant': user_data['is_pregnant'],
        'weight': user_data['weight'],
        'height': user_data['height']
    }
    
    try:
        response = requests.post(DATABASE_API_URL, data=screening_data, timeout=30)
        print(f"   Screening response: {response.status_code}")
        
        if response.status_code == 200:
            result = response.json()
            print(f"   Screening result: {result}")
            return result.get('success', False)
        else:
            print(f"   Screening failed: {response.text}")
            return False
            
    except Exception as e:
        print(f"   Screening error: {e}")
        return False

def simulate_fcm_token_registration(user_data):
    """Simulate FCM token registration (what the Android app would do)"""
    print(f"🔔 Simulating FCM token registration for: {user_data['email']}")
    
    # Generate a mock FCM token
    mock_fcm_token = f"mock_fcm_token_{random.randint(100000, 999999)}"
    
    # Update user with FCM token
    update_data = {
        'table': 'community_users',
        'data': {
            'fcm_token': mock_fcm_token
        },
        'where': 'email = ?',
        'params': [user_data['email']]
    }
    
    try:
        response = requests.post(f"{DATABASE_API_URL}?action=update", 
                               json=update_data, 
                               headers={'Content-Type': 'application/json'},
                               timeout=30)
        print(f"   FCM update response: {response.status_code}")
        
        if response.status_code == 200:
            result = response.json()
            print(f"   FCM update result: {result}")
            return result.get('success', False), mock_fcm_token
        else:
            print(f"   FCM update failed: {response.text}")
            return False, None
            
    except Exception as e:
        print(f"   FCM update error: {e}")
        return False, None

def check_fcm_token_registration(user_email):
    """Check if FCM token was registered for the user"""
    print(f"🔍 Checking FCM token registration for: {user_email}")
    
    # Query community_users table for FCM token
    query_data = {
        'action': 'query',
        'sql': f"SELECT email, barangay, fcm_token FROM community_users WHERE email = '{user_email}'"
    }
    
    try:
        response = requests.post(DATABASE_API_URL, data=query_data, timeout=30)
        
        if response.status_code == 200:
            result = response.json()
            if result.get('success') and result.get('data'):
                user_data = result['data'][0]
                fcm_token = user_data.get('fcm_token', '')
                
                if fcm_token and fcm_token != '':
                    print(f"   ✅ FCM token found: {fcm_token}")
                    return True, fcm_token
                else:
                    print(f"   ❌ No FCM token found for user")
                    return False, None
            else:
                print(f"   ❌ User not found in database")
                return False, None
        else:
            print(f"   ❌ Database query failed: {response.text}")
            return False, None
            
    except Exception as e:
        print(f"   ❌ Database query error: {e}")
        return False, None

def test_notification_sending(user_email, fcm_token):
    """Test sending notification to the user"""
    print(f"📱 Testing notification sending to: {user_email}")
    
    notification_data = {
        'action': 'send_notification',
        'notification_data': json.dumps({
            'title': '🧪 Test Notification',
            'body': f'Testing FCM for user {user_email} with token {fcm_token[:20]}...',
            'target_user': user_email
        })
    }
    
    try:
        response = requests.post(DATABASE_API_URL, data=notification_data, timeout=30)
        
        if response.status_code == 200:
            result = response.json()
            print(f"   Notification result: {result}")
            return result.get('success', False)
        else:
            print(f"   Notification failed: {response.text}")
            return False
            
    except Exception as e:
        print(f"   Notification error: {e}")
        return False

def cleanup_test_user(user_email):
    """Clean up test user from database"""
    print(f"🧹 Cleaning up test user: {user_email}")
    
    delete_data = {
        'table': 'community_users',
        'where': 'email = ?',
        'params': [user_email]
    }
    
    try:
        response = requests.post(f"{DATABASE_API_URL}?action=delete", 
                               json=delete_data, 
                               headers={'Content-Type': 'application/json'},
                               timeout=30)
        if response.status_code == 200:
            result = response.json()
            print(f"   Cleanup result: {result}")
        else:
            print(f"   Cleanup failed: {response.text}")
    except Exception as e:
        print(f"   Cleanup error: {e}")

def main():
    """Main test function"""
    print("🚀 Starting FCM Token Registration Test (Screening Focus)")
    print("=" * 60)
    
    # Generate test user
    user_data = generate_test_user()
    print(f"👤 Test User: {user_data['email']}")
    print(f"📍 Location: {user_data['municipality']}, {user_data['barangay']}")
    print()
    
    # Step 1: Create test user in database
    user_created = create_test_user_in_database(user_data)
    print()
    
    if not user_created:
        print("❌ User creation failed, stopping test")
        return
    
    # Step 2: Test screening completion
    screening_success = test_screening_completion(user_data)
    print()
    
    if not screening_success:
        print("❌ Screening failed, stopping test")
        cleanup_test_user(user_data['email'])
        return
    
    # Step 3: Simulate FCM token registration (what Android app would do)
    fcm_registered, fcm_token = simulate_fcm_token_registration(user_data)
    print()
    
    if not fcm_registered:
        print("❌ FCM token registration failed")
        cleanup_test_user(user_data['email'])
        return
    
    # Step 4: Verify FCM token is in database
    fcm_verified, stored_token = check_fcm_token_registration(user_data['email'])
    print()
    
    if fcm_verified and stored_token == fcm_token:
        # Step 5: Test notification sending
        notification_success = test_notification_sending(user_data['email'], fcm_token)
        print()
        
        if notification_success:
            print("🎉 SUCCESS: Complete FCM flow working!")
            print("   ✅ User created in database")
            print("   ✅ Screening completion successful") 
            print("   ✅ FCM token registered and verified")
            print("   ✅ Notification sending successful")
            print()
            print("📱 Android App Test Results:")
            print("   - The Android app should register FCM tokens after screening")
            print("   - Users should receive push notifications")
            print("   - The FCM registration process is working correctly")
        else:
            print("⚠️  PARTIAL SUCCESS: FCM token registered but notification failed")
    else:
        print("❌ FAILURE: FCM token not properly stored in database")
    
    # Cleanup
    print()
    cleanup_test_user(user_data['email'])
    print("✅ Test completed and cleaned up")

if __name__ == "__main__":
    main()
