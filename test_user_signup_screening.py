#!/usr/bin/env python3
"""
Test script to simulate new user sign up and screening process
and verify FCM token registration
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
        'password': 'testpassword123',
        'municipality': 'CITY OF BALANGA',
        'barangay': 'Bangkal',
        'sex': 'Female',
        'birthday': '1990-01-01',
        'is_pregnant': 'No',
        'weight': '60',
        'height': '165'
    }

def test_user_signup(user_data):
    """Test user sign up process"""
    print(f"🔐 Testing user sign up for: {user_data['email']}")
    
    # Step 1: Register user with basic info first
    register_data = {
        'action': 'register',
        'username': user_data['name'],
        'email': user_data['email'],
        'password': user_data['password']
    }
    
    try:
        response = requests.post(DATABASE_API_URL, data=register_data, timeout=30)
        print(f"   Sign up response: {response.status_code}")
        
        if response.status_code == 200:
            result = response.json()
            print(f"   Sign up result: {result}")
            return result.get('success', False)
        else:
            print(f"   Sign up failed: {response.text}")
            return False
            
    except Exception as e:
        print(f"   Sign up error: {e}")
        return False

def test_screening_completion(user_data):
    """Test screening completion process"""
    print(f"📋 Testing screening completion for: {user_data['email']}")
    
    # Step 2: Complete screening using save_screening API
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

def check_fcm_token_registration(user_email):
    """Check if FCM token was registered for the user"""
    print(f"🔔 Checking FCM token registration for: {user_email}")
    
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
                    print(f"   ✅ FCM token found: {fcm_token[:50]}...")
                    return True
                else:
                    print(f"   ❌ No FCM token found for user")
                    return False
            else:
                print(f"   ❌ User not found in database")
                return False
        else:
            print(f"   ❌ Database query failed: {response.text}")
            return False
            
    except Exception as e:
        print(f"   ❌ Database query error: {e}")
        return False

def test_notification_sending(user_email):
    """Test sending notification to the user"""
    print(f"📱 Testing notification sending to: {user_email}")
    
    notification_data = {
        'action': 'send_notification',
        'notification_data': json.dumps({
            'title': '🧪 Test Notification',
            'body': f'Testing FCM for user {user_email}',
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
        'action': 'delete',
        'table': 'community_users',
        'where': 'email = ?',
        'params': json.dumps([user_email])
    }
    
    try:
        response = requests.post(DATABASE_API_URL, data=delete_data, timeout=30)
        if response.status_code == 200:
            result = response.json()
            print(f"   Cleanup result: {result}")
        else:
            print(f"   Cleanup failed: {response.text}")
    except Exception as e:
        print(f"   Cleanup error: {e}")

def main():
    """Main test function"""
    print("🚀 Starting FCM Token Registration Test")
    print("=" * 50)
    
    # Generate test user
    user_data = generate_test_user()
    print(f"👤 Test User: {user_data['email']}")
    print(f"📍 Location: {user_data['municipality']}, {user_data['barangay']}")
    print()
    
    # Step 1: Test user sign up
    signup_success = test_user_signup(user_data)
    print()
    
    if not signup_success:
        print("❌ Sign up failed, stopping test")
        return
    
    # Step 2: Test screening completion
    screening_success = test_screening_completion(user_data)
    print()
    
    if not screening_success:
        print("❌ Screening failed, stopping test")
        cleanup_test_user(user_data['email'])
        return
    
    # Step 3: Check FCM token registration
    fcm_registered = check_fcm_token_registration(user_data['email'])
    print()
    
    if fcm_registered:
        # Step 4: Test notification sending
        notification_success = test_notification_sending(user_data['email'])
        print()
        
        if notification_success:
            print("🎉 SUCCESS: Complete FCM flow working!")
            print("   ✅ User sign up successful")
            print("   ✅ Screening completion successful") 
            print("   ✅ FCM token registered")
            print("   ✅ Notification sending successful")
        else:
            print("⚠️  PARTIAL SUCCESS: FCM token registered but notification failed")
    else:
        print("❌ FAILURE: FCM token not registered after screening")
        print("   This indicates the Android app FCM registration is not working")
    
    # Cleanup
    print()
    cleanup_test_user(user_data['email'])
    print("✅ Test completed and cleaned up")

if __name__ == "__main__":
    main()
