#!/usr/bin/env python3
"""
Test script for the NEW Google OAuth functionality in DatabaseAPI.php
This script tests the updated Google OAuth implementation
"""

import requests
import json
import sys
from datetime import datetime

# Configuration
API_BASE_URL = "https://nutrisaur-production.up.railway.app"
DATABASE_API_URL = f"{API_BASE_URL}/api/DatabaseAPI.php"

def test_google_oauth_in_database_api():
    """Test Google OAuth functionality in the main DatabaseAPI.php"""
    print("🔍 Testing Google OAuth in DatabaseAPI.php...")
    
    # Test data for Google OAuth
    test_data = {
        "action": "google_signin",
        "id_token": "test_token_12345",  # This would be a real Google ID token in production
        "email": "test@example.com",
        "name": "Test User",
        "profile_picture": "https://example.com/profile.jpg"
    }
    
    try:
        response = requests.post(
            DATABASE_API_URL,
            json=test_data,
            headers={
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            timeout=30
        )
        
        print(f"Status Code: {response.status_code}")
        print(f"Response Headers: {dict(response.headers)}")
        
        if response.status_code == 200:
            try:
                response_data = response.json()
                print(f"Response Data: {json.dumps(response_data, indent=2)}")
                
                if response_data.get('success'):
                    print("✅ Google OAuth in DatabaseAPI.php is working")
                    return True
                else:
                    print(f"⚠️  Google OAuth returned error: {response_data.get('error', 'Unknown error')}")
                    return False
            except json.JSONDecodeError:
                print(f"Response Text: {response.text}")
                return False
        else:
            print(f"Error Response: {response.text}")
            return False
            
    except requests.exceptions.RequestException as e:
        print(f"❌ Request failed: {e}")
        return False

def test_google_oauth_with_realistic_token():
    """Test Google OAuth with a more realistic token structure"""
    print("\n🔍 Testing Google OAuth with realistic token...")
    
    # This simulates what the Android app would send
    test_data = {
        "action": "google_signin",
        "id_token": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJhY2NvdW50cy5nb29nbGUuY29tIiwiYXVkIjoiNDM1Mzc5MDM3NDctMm5kOW10bW1pOTcydWNvaXJobzJzdGhrcWx1OG1jdDZiLmFwcHMuZ29vZ2xldXNlcmNvbnRlbnQuY29tIiwiZXhwIjoxNzM4MjQ4MDAwLCJpYXQiOjE3MzgyNDQ0MDAsInN1YiI6IjEyMzQ1Njc4OTAifQ.test_signature",  # Mock JWT token
        "email": "testuser@gmail.com",
        "name": "Test User",
        "profile_picture": "https://lh3.googleusercontent.com/a/test-profile-picture"
    }
    
    try:
        response = requests.post(
            DATABASE_API_URL,
            json=test_data,
            headers={
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            timeout=30
        )
        
        print(f"Status Code: {response.status_code}")
        
        if response.status_code == 200:
            try:
                response_data = response.json()
                print(f"Response Data: {json.dumps(response_data, indent=2)}")
                
                # Check if the response indicates success or specific error
                if response_data.get('success'):
                    print("✅ Google OAuth with realistic token is working")
                    return True
                else:
                    print(f"⚠️  Google OAuth returned error: {response_data.get('error', 'Unknown error')}")
                    return False
            except json.JSONDecodeError:
                print(f"Response Text: {response.text}")
                return False
        else:
            print(f"Error Response: {response.text}")
            return False
            
    except requests.exceptions.RequestException as e:
        print(f"❌ Request failed: {e}")
        return False

def test_cors_headers_for_database_api():
    """Test CORS headers for the main DatabaseAPI.php"""
    print("\n🔍 Testing CORS headers for DatabaseAPI.php...")
    
    try:
        # Test OPTIONS request (preflight)
        response = requests.options(
            DATABASE_API_URL,
            headers={
                'Origin': 'https://nutrisaur-production.up.railway.app',
                'Access-Control-Request-Method': 'POST',
                'Access-Control-Request-Headers': 'Content-Type'
            },
            timeout=10
        )
        
        print(f"OPTIONS Status Code: {response.status_code}")
        print(f"CORS Headers: {dict(response.headers)}")
        
        # Check for required CORS headers
        cors_headers = {
            'Access-Control-Allow-Origin': response.headers.get('Access-Control-Allow-Origin'),
            'Access-Control-Allow-Methods': response.headers.get('Access-Control-Allow-Methods'),
            'Access-Control-Allow-Headers': response.headers.get('Access-Control-Allow-Headers')
        }
        
        print(f"CORS Configuration: {json.dumps(cors_headers, indent=2)}")
        
        # The DatabaseAPI.php should handle CORS properly
        if response.status_code == 200:
            print("✅ CORS is working for DatabaseAPI.php")
            return True
        else:
            print("⚠️  CORS configuration may need adjustment")
            return False
            
    except requests.exceptions.RequestException as e:
        print(f"❌ CORS test failed: {e}")
        return False

def test_android_app_compatibility():
    """Test if the implementation is compatible with Android app expectations"""
    print("\n🔍 Testing Android app compatibility...")
    
    # Test the exact request format that the Android app would send
    test_data = {
        "action": "google_signin",
        "id_token": "mock_google_id_token",
        "email": "android.test@gmail.com",
        "name": "Android Test User",
        "profile_picture": "https://lh3.googleusercontent.com/a/android-test"
    }
    
    try:
        # Use the same URL format that the Android app uses
        url = f"{DATABASE_API_URL}?action=google_signin"
        
        response = requests.post(
            url,
            json=test_data,
            headers={
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            timeout=30
        )
        
        print(f"Android-compatible request status: {response.status_code}")
        
        if response.status_code == 200:
            try:
                response_data = response.json()
                print(f"Android-compatible response: {json.dumps(response_data, indent=2)}")
                
                # Check if the response format matches what the Android app expects
                if 'success' in response_data and 'message' in response_data:
                    print("✅ Response format is compatible with Android app")
                    return True
                else:
                    print("⚠️  Response format may not be compatible with Android app")
                    return False
            except json.JSONDecodeError:
                print(f"Response Text: {response.text}")
                return False
        else:
            print(f"Error Response: {response.text}")
            return False
            
    except requests.exceptions.RequestException as e:
        print(f"❌ Android compatibility test failed: {e}")
        return False

def main():
    """Main test function"""
    print("🚀 Testing NEW Google OAuth Implementation in DatabaseAPI.php")
    print("=" * 70)
    print(f"Timestamp: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print(f"Testing endpoint: {DATABASE_API_URL}")
    print("=" * 70)
    
    # Run tests
    tests = [
        ("Google OAuth in DatabaseAPI", test_google_oauth_in_database_api),
        ("Google OAuth with Realistic Token", test_google_oauth_with_realistic_token),
        ("CORS Headers for DatabaseAPI", test_cors_headers_for_database_api),
        ("Android App Compatibility", test_android_app_compatibility)
    ]
    
    results = {}
    
    for test_name, test_func in tests:
        print(f"\n{'='*20} {test_name} {'='*20}")
        try:
            result = test_func()
            results[test_name] = result
            status = "✅ PASSED" if result else "❌ FAILED"
            print(f"\n{status}: {test_name}")
        except Exception as e:
            print(f"❌ ERROR in {test_name}: {e}")
            results[test_name] = False
    
    # Summary
    print("\n" + "="*70)
    print("📊 TEST SUMMARY")
    print("="*70)
    
    passed = sum(1 for result in results.values() if result)
    total = len(results)
    
    for test_name, result in results.items():
        status = "✅ PASSED" if result else "❌ FAILED"
        print(f"{status}: {test_name}")
    
    print(f"\nOverall: {passed}/{total} tests passed")
    
    if passed == total:
        print("🎉 All tests passed! Google OAuth should be working with the new implementation.")
        print("\n💡 NEXT STEPS:")
        print("1. Deploy the updated DatabaseAPI.php to the server")
        print("2. Test the Android app with the new implementation")
        print("3. Verify Google Sign-In works end-to-end")
    else:
        print("⚠️  Some tests failed. Check the issues above.")
        
    return passed == total

if __name__ == "__main__":
    success = main()
    sys.exit(0 if success else 1)
