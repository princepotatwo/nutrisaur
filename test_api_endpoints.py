#!/usr/bin/env python3
"""
API Endpoint Tester for Nutrisaur
Tests all possible InfinityFree server URLs to find which one works
"""

import requests
import time
from urllib.parse import urljoin

def test_endpoint(base_url, endpoint="api.php?test=1"):
    """Test a single endpoint and return the result"""
    full_url = urljoin(base_url, endpoint)
    
    try:
        print(f"Testing: {full_url}")
        
        # Set headers to mimic a mobile app
        headers = {
            'User-Agent': 'NutrisaurApp/1.0 (Android)',
            'Accept': 'application/json, text/plain, */*',
            'Content-Type': 'application/json',
            'Connection': 'keep-alive'
        }
        
        # Test GET request first
        response = requests.get(full_url, headers=headers, timeout=10)
        
        print(f"  Status: {response.status_code}")
        print(f"  Response: {response.text[:200]}...")
        
        if response.status_code == 200:
            print(f"  ✅ SUCCESS: {base_url}")
            return True, base_url
        else:
            print(f"  ❌ Failed with status {response.status_code}")
            return False, None
            
    except requests.exceptions.RequestException as e:
        print(f"  ❌ Error: {e}")
        return False, None
    except Exception as e:
        print(f"  ❌ Unexpected error: {e}")
        return False, None

def main():
    """Test all possible InfinityFree server URLs"""
    
    print("🚀 Testing Nutrisaur API Endpoints")
    print("=" * 50)
    
    # All possible InfinityFree server URLs
    test_urls = [
        "http://nutrisaur.epizy.com/",
        "http://nutrisaur.free.nf/", 
        "http://nutrisaur.rf.gd/",
        "http://nutrisaur.42web.io/",
        "http://185.27.134.121/if0_39764899/",
        "http://nutrisaur.gt.tc/"  # Your custom domain (might be blocked)
    ]
    
    working_urls = []
    
    for url in test_urls:
        print(f"\n🔍 Testing: {url}")
        success, working_url = test_endpoint(url)
        
        if success:
            working_urls.append(working_url)
        
        time.sleep(1)  # Small delay between tests
    
    print("\n" + "=" * 50)
    print("📊 RESULTS SUMMARY")
    print("=" * 50)
    
    if working_urls:
        print(f"✅ Working URLs ({len(working_urls)}):")
        for url in working_urls:
            print(f"   - {url}")
        
        print(f"\n🎯 RECOMMENDATION: Use this URL in your app:")
        print(f"   {working_urls[0]}")
        
        # Test the working URL with different endpoints
        print(f"\n🔧 Testing endpoints on working server:")
        working_base = working_urls[0]
        
        endpoints_to_test = [
            "api.php?test=1",
            "unified_api.php?test=1", 
            "api.php?endpoint=test",
            "unified_api.php?endpoint=test"
        ]
        
        for endpoint in endpoints_to_test:
            test_endpoint(working_base, endpoint)
            
    else:
        print("❌ No working URLs found!")
        print("   This might mean:")
        print("   - InfinityFree is blocking all requests")
        print("   - Your server is down")
        print("   - You need to upload your PHP files first")
    
    print("\n" + "=" * 50)

if __name__ == "__main__":
    main()
