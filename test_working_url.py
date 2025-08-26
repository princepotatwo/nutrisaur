#!/usr/bin/env python3
"""
Test Working URL
Test if nutrisaur11.epizy.com has your PHP files and no Cloudflare
"""

import requests

def test_working_url():
    """Test the working URL to see what's actually there"""
    
    print("🔍 Testing Working URL: nutrisaur11.epizy.com")
    print("=" * 50)
    
    base_url = "http://nutrisaur11.epizy.com"
    
    # Test different endpoints
    test_endpoints = [
        "/",
        "/index.php",
        "/api.php?test=1",
        "/unified_api.php?test=1",
        "/config.php",
        "/sss/",
        "/sss/dash.php"
    ]
    
    working_endpoints = []
    
    for endpoint in test_endpoints:
        try:
            url = base_url + endpoint
            print(f"\n🔍 Testing: {url}")
            
            headers = {
                'User-Agent': 'NutrisaurApp/1.0 (Android)',
                'Accept': 'text/plain, application/json'
            }
            
            response = requests.get(url, headers=headers, timeout=15)
            
            print(f"  Status: {response.status_code}")
            print(f"  Content-Type: {response.headers.get('Content-Type', 'Unknown')}")
            
            if response.status_code == 200:
                content = response.text
                
                # Check for Cloudflare
                if "aes.js" in content:
                    print(f"  ❌ Cloudflare JavaScript challenge detected!")
                elif "404" in content or "Not Found" in content:
                    print(f"  ❌ File not found")
                else:
                    print(f"  ✅ SUCCESS! Content length: {len(content)}")
                    print(f"  Preview: {content[:200]}...")
                    working_endpoints.append(endpoint)
                    
                    # Check if it's your actual API
                    if "api" in endpoint and ("success" in content or "API is working" in content):
                        print(f"  🎯 This looks like your actual API!")
                        
            else:
                print(f"  ❌ Failed with status {response.status_code}")
                
        except Exception as e:
            print(f"  ❌ Error: {e}")
    
    return working_endpoints, base_url

def test_mobile_app_simulation():
    """Test if the working URL works with mobile app requests"""
    
    print(f"\n📱 Testing Mobile App Simulation")
    print("=" * 50)
    
    base_url = "http://nutrisaur11.epizy.com"
    
    # Test POST request like your app does
    try:
        url = f"{base_url}/api.php"
        headers = {
            'User-Agent': 'NutrisaurApp/1.0 (Android)',
            'Content-Type': 'application/json',
            'Accept': 'text/plain, application/json'
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
                print(f"  ✅ SUCCESS! No Cloudflare blocking!")
                return True
        else:
            print(f"  ❌ Failed with status {response.status_code}")
            return False
            
    except Exception as e:
        print(f"  ❌ Error: {e}")
        return False

def main():
    """Main test function"""
    print("🚀 Testing Working URL: nutrisaur11.epizy.com")
    print("=" * 50)
    print("This URL was found to be accessible - let's see what's there!")
    print("=" * 50)
    
    # Test 1: Check what endpoints work
    working_endpoints, base_url = test_working_url()
    
    # Test 2: Test mobile app simulation
    mobile_works = test_mobile_app_simulation()
    
    print("\n" + "=" * 50)
    print("📊 WORKING URL TEST RESULTS")
    print("=" * 50)
    
    if working_endpoints:
        print(f"✅ Working endpoints ({len(working_endpoints)}):")
        for endpoint in working_endpoints:
            print(f"   - {endpoint}")
        
        print(f"\n🎯 Base URL: {base_url}")
        
        if mobile_works:
            print(f"\n🚀 PERFECT! This URL should work with your app!")
            print(f"   Update your Constants.java to use:")
            print(f"   {base_url}")
        else:
            print(f"\n⚠️ URL works but still has Cloudflare issues")
            
    else:
        print("❌ No working endpoints found")
        print("   This URL might not have your files uploaded")
    
    print("\n" + "=" * 50)

if __name__ == "__main__":
    main()
