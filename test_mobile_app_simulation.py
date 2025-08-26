#!/usr/bin/env python3
"""
Mobile App Simulation Test for Nutrisaur
Simulates exactly what your Android app does to bypass Cloudflare
"""

import requests
import json
import time

def test_mobile_app_request():
    """Test the API exactly like your Android app does"""
    
    print("📱 Testing Mobile App Simulation")
    print("=" * 50)
    
    base_url = "http://nutrisaur.gt.tc"
    
    # Test 1: Simple GET request (like your app's event fetching)
    print(f"\n🔍 Test 1: GET Request (Events)")
    try:
        url = f"{base_url}/api.php?endpoint=events"
        headers = {
            'User-Agent': 'NutrisaurApp/1.0 (Android)',
            'Accept': 'text/plain, application/json',
            'Accept-Language': 'en-US,en;q=0.9',
            'Accept-Encoding': 'gzip, deflate, br',
            'Connection': 'keep-alive',
            'Cache-Control': 'no-cache'
        }
        
        response = requests.get(url, headers=headers, timeout=15)
        print(f"  URL: {url}")
        print(f"  Status: {response.status_code}")
        print(f"  Headers: {dict(response.headers)}")
        print(f"  Response: {response.text[:300]}...")
        
        if response.status_code == 200:
            if "aes.js" in response.text:
                print("  ❌ Cloudflare JavaScript challenge detected!")
                print("  💡 This is why your app can't connect")
            else:
                print("  ✅ Success! Real API response")
        else:
            print(f"  ❌ Failed with status {response.status_code}")
            
    except Exception as e:
        print(f"  ❌ Error: {e}")
    
    # Test 2: POST request (like your app's screening data)
    print(f"\n🔍 Test 2: POST Request (Screening Data)")
    try:
        url = f"{base_url}/api.php"
        headers = {
            'User-Agent': 'NutrisaurApp/1.0 (Android)',
            'Content-Type': 'application/json',
            'Accept': 'text/plain, application/json',
            'Connection': 'keep-alive'
        }
        
        # Simulate screening data like your app sends
        test_data = {
            "action": "test_screening",
            "test": True,
            "timestamp": "2024-01-01 12:00:00"
        }
        
        response = requests.post(url, headers=headers, json=test_data, timeout=15)
        print(f"  URL: {url}")
        print(f"  Status: {response.status_code}")
        print(f"  Response: {response.text[:300]}...")
        
        if response.status_code == 200:
            if "aes.js" in response.text:
                print("  ❌ Cloudflare JavaScript challenge detected!")
            else:
                print("  ✅ Success! Real API response")
        else:
            print(f"  ❌ Failed with status {response.status_code}")
            
    except Exception as e:
        print(f"  ❌ Error: {e}")
    
    # Test 3: Try to bypass Cloudflare with different approaches
    print(f"\n🔍 Test 3: Cloudflare Bypass Attempts")
    
    bypass_attempts = [
        # Try different User-Agent strings
        {'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'},
        {'User-Agent': 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_7_1 like Mac OS X)'},
        {'User-Agent': 'Mozilla/5.0 (Linux; Android 10; SM-G975F)'},
        
        # Try different Accept headers
        {'Accept': 'application/json, text/plain, */*'},
        {'Accept': '*/*'},
        
        # Try with referer
        {'Referer': 'https://nutrisaur.gt.tc/'},
        
        # Try with different content type
        {'Content-Type': 'application/x-www-form-urlencoded'},
    ]
    
    for i, attempt in enumerate(bypass_attempts, 1):
        try:
            print(f"  Attempt {i}: {list(attempt.keys())[0]}")
            
            test_headers = {
                'User-Agent': 'NutrisaurApp/1.0 (Android)',
                'Accept': 'text/plain, application/json'
            }
            test_headers.update(attempt)
            
            response = requests.get(f"{base_url}/api.php?test=1", headers=test_headers, timeout=10)
            
            if response.status_code == 200 and "aes.js" not in response.text:
                print(f"    ✅ SUCCESS! Bypassed Cloudflare with: {list(attempt.keys())[0]}")
                print(f"    Response: {response.text[:200]}...")
                return True
            else:
                print(f"    ❌ Still getting Cloudflare challenge")
                
        except Exception as e:
            print(f"    ❌ Error: {e}")
        
        time.sleep(1)
    
    return False

def main():
    """Main test function"""
    print("🚀 Nutrisaur Mobile App API Test")
    print("=" * 50)
    print("This simulates exactly what your Android app does")
    print("to help identify why Cloudflare is blocking it")
    print("=" * 50)
    
    success = test_mobile_app_request()
    
    print("\n" + "=" * 50)
    print("📊 ANALYSIS RESULTS")
    print("=" * 50)
    
    if success:
        print("✅ Found a way to bypass Cloudflare!")
        print("   Update your app with the working headers")
    else:
        print("❌ All bypass attempts failed")
        print("\n💡 RECOMMENDATIONS:")
        print("   1. Your domain nutrisaur.gt.tc IS working")
        print("   2. Cloudflare is blocking mobile apps with JS challenges")
        print("   3. You need to either:")
        print("      - Disable Cloudflare on your domain")
        print("      - Use a different hosting service")
        print("      - Implement a proxy solution")
        print("      - Buy a premium Cloudflare plan")
    
    print("\n" + "=" * 50)

if __name__ == "__main__":
    main()
