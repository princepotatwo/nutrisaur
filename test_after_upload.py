#!/usr/bin/env python3
"""
Test After Upload
Quick test to verify when your PHP files are uploaded to nutrisaur11.epizy.com
"""

import requests
import time

def test_upload():
    """Test if your files are uploaded and working"""
    
    print("🧪 Testing After File Upload")
    print("=" * 50)
    print("Run this after uploading your PHP files to nutrisaur11.epizy.com")
    print("=" * 50)
    
    base_url = "http://nutrisaur11.epizy.com"
    
    # Test endpoints that should work after upload
    test_endpoints = [
        "/api.php?test=1",
        "/unified_api.php?test=1",
        "/index.php",
        "/sss/dash.php"
    ]
    
    working_endpoints = []
    
    for endpoint in test_endpoints:
        try:
            url = base_url + endpoint
            print(f"\n🔍 Testing: {url}")
            
            response = requests.get(url, timeout=10)
            
            if response.status_code == 200:
                content = response.text
                
                if "aes.js" in content:
                    print(f"  ❌ Still getting Cloudflare challenge")
                elif "404" in content or "Not Found" in content:
                    print(f"  ❌ File not found - not uploaded yet")
                else:
                    print(f"  ✅ SUCCESS! File is uploaded and working!")
                    print(f"  Content length: {len(content)}")
                    working_endpoints.append(endpoint)
                    
                    # Check if it's your actual API
                    if "api" in endpoint and ("success" in content or "API is working" in content):
                        print(f"  🎯 This is your actual API!")
                        
            else:
                print(f"  ❌ Status: {response.status_code}")
                
        except Exception as e:
            print(f"  ❌ Error: {e}")
    
    return working_endpoints

def main():
    """Main function"""
    print("🚀 File Upload Test for nutrisaur11.epizy.com")
    print("=" * 50)
    
    working = test_upload()
    
    print("\n" + "=" * 50)
    print("📊 UPLOAD TEST RESULTS")
    print("=" * 50)
    
    if working:
        print(f"✅ {len(working)} files are working!")
        print(f"\n🚀 Your app should now work!")
        print(f"   The URL {base_url} is ready!")
    else:
        print("❌ No files are working yet")
        print("\n💡 You need to:")
        print("   1. Upload your PHP files to nutrisaur11.epizy.com")
        print("   2. Run this test again")
        print("   3. Then rebuild your app")
    
    print("\n" + "=" * 50)

if __name__ == "__main__":
    main()
