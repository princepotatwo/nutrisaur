#!/usr/bin/env python3
"""
Test Enhanced API
Test the enhanced API with CORS headers and API key protection
"""

import requests
import json

def test_enhanced_api():
    """Test the enhanced API with proper headers"""
    
    print("🧪 Testing Enhanced API with CORS + API Key")
    print("=" * 50)
    
    base_url = "http://nutrisaur.gt.tc"
    
    # Test 1: Test endpoint (no API key required)
    print(f"\n🔍 Test 1: Test Endpoint (No API Key)")
    try:
        url = f"{base_url}/api.php?test=1"
        headers = {
            'User-Agent': 'NutrisaurApp/2.0 (Android)',
            'Accept': 'application/json, text/plain, */*',
            'Origin': 'https://nutrisaur.gt.tc'
        }
        
        response = requests.get(url, headers=headers, timeout=15)
        print(f"  URL: {url}")
        print(f"  Status: {response.status_code}")
        print(f"  Headers: {dict(response.headers)}")
        
        if response.status_code == 200:
            content = response.text
            if "aes.js" in content:
                print(f"  ❌ Still getting Cloudflare challenge")
                return False
            else:
                print(f"  ✅ SUCCESS! No Cloudflare blocking!")
                print(f"  Response: {content[:300]}...")
        else:
            print(f"  ❌ Failed with status {response.status_code}")
            return False
            
    except Exception as e:
        print(f"  ❌ Error: {e}")
        return False
    
    # Test 2: Test with API key
    print(f"\n🔍 Test 2: Test with API Key")
    try:
        url = f"{base_url}/api.php?test=1"
        headers = {
            'User-Agent': 'NutrisaurApp/2.0 (Android; API-Key: nutrisaur2024)',
            'Accept': 'application/json, text/plain, */*',
            'X-API-Key': 'nutrisaur2024',
            'Origin': 'https://nutrisaur.gt.tc',
            'Cache-Control': 'no-cache'
        }
        
        response = requests.get(url, headers=headers, timeout=15)
        print(f"  URL: {url}")
        print(f"  Status: {response.status_code}")
        
        if response.status_code == 200:
            content = response.text
            if "aes.js" in content:
                print(f"  ❌ Still getting Cloudflare challenge")
                return False
            else:
                print(f"  ✅ SUCCESS! API key working!")
                print(f"  Response: {content[:300]}...")
        else:
            print(f"  ❌ Failed with status {response.status_code}")
            return False
            
    except Exception as e:
        print(f"  ❌ Error: {e}")
        return False
    
    # Test 3: Test POST request (like your app does)
    print(f"\n🔍 Test 3: POST Request (Like Your App)")
    try:
        url = f"{base_url}/api.php"
        headers = {
            'User-Agent': 'NutrisaurApp/2.0 (Android; API-Key: nutrisaur2024)',
            'Content-Type': 'application/json; charset=utf-8',
            'Accept': 'application/json, text/plain, */*',
            'X-API-Key': 'nutrisaur2024',
            'Origin': 'https://nutrisaur.gt.tc',
            'Cache-Control': 'no-cache'
        }
        
        # Simulate screening data
        test_data = {
            "action": "test_screening",
            "test": True,
            "timestamp": "2024-01-01 12:00:00"
        }
        
        response = requests.post(url, headers=headers, json=test_data, timeout=15)
        
        print(f"  POST to: {url}")
        print(f"  Status: {response.status_code}")
        print(f"  Response: {response.text[:300]}...")
        
        if response.status_code == 200:
            if "aes.js" in response.text:
                print(f"  ❌ Still getting Cloudflare challenge")
                return False
            else:
                print(f"  ✅ SUCCESS! POST request working!")
                return True
        else:
            print(f"  ❌ Failed with status {response.status_code}")
            return False
            
    except Exception as e:
        print(f"  ❌ Error: {e}")
        return False

def main():
    """Main test function"""
    print("🚀 Testing Enhanced API (CORS + API Key)")
    print("=" * 50)
    print("This tests if the enhanced CORS headers and API key bypass Cloudflare")
    print("=" * 50)
    
    success = test_enhanced_api()
    
    print("\n" + "=" * 50)
    print("📊 ENHANCED API TEST RESULTS")
    print("=" * 50)
    
    if success:
        print("✅ SUCCESS! Enhanced API is working!")
        print("   CORS headers + API key bypassed Cloudflare!")
        print("\n🚀 Your app should now work!")
        print("   Rebuild your app with the updated Constants.java")
        
    else:
        print("❌ Enhanced API still has issues")
        print("\n💡 NEXT STEPS:")
        print("   1. Upload the enhanced api.php to your server")
        print("   2. Make sure CORS headers are working")
        print("   3. Test again with this script")
        print("   4. If still blocked, we need a different approach")
    
    print("\n" + "=" * 50)

if __name__ == "__main__":
    main()
