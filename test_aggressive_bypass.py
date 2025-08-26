#!/usr/bin/env python3
"""
Aggressive Cloudflare Bypass Test
Try more aggressive techniques to bypass Cloudflare
"""

import requests
import time
import random

def test_aggressive_bypass():
    """Test aggressive bypass techniques"""
    
    print("🚀 Aggressive Cloudflare Bypass Test")
    print("=" * 50)
    
    base_url = "http://nutrisaur.gt.tc"
    
    # Test different aggressive approaches
    bypass_attempts = [
        # Attempt 1: Browser-like headers
        {
            'name': 'Browser-like Headers',
            'headers': {
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language': 'en-US,en;q=0.5',
                'Accept-Encoding': 'gzip, deflate',
                'DNT': '1',
                'Connection': 'keep-alive',
                'Upgrade-Insecure-Requests': '1',
                'Sec-Fetch-Dest': 'document',
                'Sec-Fetch-Mode': 'navigate',
                'Sec-Fetch-Site': 'none',
                'Cache-Control': 'max-age=0'
            }
        },
        
        # Attempt 2: Mobile app with different approach
        {
            'name': 'Mobile App Alternative',
            'headers': {
                'User-Agent': 'NutrisaurApp/2.0 (Android; Build/123; API-Key: nutrisaur2024)',
                'Accept': 'application/json, text/plain, */*',
                'X-API-Key': 'nutrisaur2024',
                'X-Requested-With': 'XMLHttpRequest',
                'X-Forwarded-For': '192.168.1.1',
                'X-Real-IP': '192.168.1.1',
                'Referer': 'https://nutrisaur.gt.tc/',
                'Origin': 'https://nutrisaur.gt.tc'
            }
        },
        
        # Attempt 3: API client headers
        {
            'name': 'API Client Headers',
            'headers': {
                'User-Agent': 'Nutrisaur-API-Client/1.0',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'Authorization': 'Bearer nutrisaur2024',
                'X-Client-Version': '2.0',
                'X-Platform': 'Android',
                'X-Device-ID': 'test-device-123'
            }
        },
        
        # Attempt 4: Minimal headers
        {
            'name': 'Minimal Headers',
            'headers': {
                'User-Agent': 'NutrisaurApp/2.0'
            }
        }
    ]
    
    for i, attempt in enumerate(bypass_attempts, 1):
        print(f"\n🔍 Attempt {i}: {attempt['name']}")
        print("-" * 40)
        
        try:
            # Test with GET first
            url = f"{base_url}/api.php?test=1"
            response = requests.get(url, headers=attempt['headers'], timeout=15)
            
            print(f"  GET Status: {response.status_code}")
            
            if response.status_code == 200:
                content = response.text
                if "aes.js" in content:
                    print(f"  ❌ Still getting Cloudflare challenge")
                else:
                    print(f"  ✅ SUCCESS! Bypassed Cloudflare!")
                    print(f"  Response: {content[:200]}...")
                    return True, attempt['name']
            else:
                print(f"  ❌ Failed with status {response.status_code}")
                
        except Exception as e:
            print(f"  ❌ Error: {e}")
        
        # Small delay between attempts
        time.sleep(1)
    
    return False, None

def test_different_endpoints():
    """Test different endpoints that might work"""
    
    print(f"\n🔍 Testing Different Endpoints")
    print("=" * 50)
    
    base_url = "http://nutrisaur.gt.tc"
    
    # Test different endpoints
    endpoints = [
        "/",
        "/index.php",
        "/api.php",
        "/unified_api.php",
        "/sss/",
        "/sss/dash.php"
    ]
    
    for endpoint in endpoints:
        try:
            url = base_url + endpoint
            print(f"\n  Testing: {url}")
            
            headers = {
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
            }
            
            response = requests.get(url, headers=headers, timeout=10)
            print(f"    Status: {response.status_code}")
            
            if response.status_code == 200:
                content = response.text
                if "aes.js" in content:
                    print(f"    ❌ Cloudflare challenge")
                else:
                    print(f"    ✅ No Cloudflare! Content length: {len(content)}")
                    
        except Exception as e:
            print(f"    ❌ Error: {e}")

def main():
    """Main test function"""
    print("🚀 Aggressive Cloudflare Bypass Test")
    print("=" * 50)
    print("Trying more aggressive techniques to bypass Cloudflare")
    print("=" * 50)
    
    # Test aggressive bypass
    success, method = test_aggressive_bypass()
    
    # Test different endpoints
    test_different_endpoints()
    
    print("\n" + "=" * 50)
    print("📊 AGGRESSIVE BYPASS RESULTS")
    print("=" * 50)
    
    if success:
        print(f"✅ SUCCESS! Bypassed Cloudflare with: {method}")
        print(f"\n🚀 Your app should now work!")
        print(f"   Use these headers in your app:")
        print(f"   {method}")
        
    else:
        print("❌ All aggressive bypass attempts failed")
        print("\n💡 FINAL RECOMMENDATIONS:")
        print("   1. DISABLE CLOUDFLARE in InfinityFree control panel")
        print("   2. Or switch to a different hosting service")
        print("   3. Or use local development with ngrok")
        print("   4. Cloudflare is too aggressive for mobile apps")
    
    print("\n" + "=" * 50)

if __name__ == "__main__":
    main()
