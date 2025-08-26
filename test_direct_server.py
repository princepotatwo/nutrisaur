#!/usr/bin/env python3
"""
Direct Server Access Test
Try to access your server directly via IP to bypass Cloudflare
"""

import requests
import socket

def test_direct_server_access():
    """Test direct server access via IP"""
    
    print("🔍 Testing Direct Server Access")
    print("=" * 50)
    
    # Your server details from control panel
    server_ip = "185.27.134.121"
    username = "if0_39764899"
    
    # Try different direct access methods
    test_urls = [
        f"http://{server_ip}/{username}/",
        f"http://{server_ip}/{username}/api.php?test=1",
        f"http://{server_ip}/{username}/unified_api.php?test=1",
        f"http://{server_ip}/",
        f"http://{server_ip}/api.php?test=1"
    ]
    
    working_urls = []
    
    for url in test_urls:
        try:
            print(f"\n🔍 Testing: {url}")
            
            headers = {
                'User-Agent': 'NutrisaurApp/1.0 (Android)',
                'Accept': 'text/plain, application/json'
            }
            
            response = requests.get(url, headers=headers, timeout=10)
            
            print(f"  Status: {response.status_code}")
            print(f"  Response: {response.text[:200]}...")
            
            if response.status_code == 200:
                if "aes.js" not in response.text:
                    print(f"  ✅ SUCCESS! Direct access works: {url}")
                    working_urls.append(url)
                else:
                    print(f"  ❌ Still getting Cloudflare challenge")
            else:
                print(f"  ❌ Failed with status {response.status_code}")
                
        except Exception as e:
            print(f"  ❌ Error: {e}")
    
    return working_urls

def test_dns_resolution():
    """Test if we can resolve the domain directly"""
    
    print(f"\n🔍 Testing DNS Resolution")
    print("=" * 50)
    
    domains = [
        "nutrisaur.gt.tc",
        "nutrisaur.epizy.com",
        "nutrisaur.free.nf",
        "nutrisaur.rf.gd"
    ]
    
    for domain in domains:
        try:
            ip = socket.gethostbyname(domain)
            print(f"  {domain} → {ip}")
        except socket.gaierror:
            print(f"  {domain} → Cannot resolve")

def main():
    """Main test function"""
    print("🚀 Direct Server Access Test")
    print("=" * 50)
    print("Trying to bypass Cloudflare by accessing your server directly")
    print("=" * 50)
    
    # Test DNS resolution first
    test_dns_resolution()
    
    # Test direct server access
    working_urls = test_direct_server_access()
    
    print("\n" + "=" * 50)
    print("📊 DIRECT ACCESS RESULTS")
    print("=" * 50)
    
    if working_urls:
        print(f"✅ Found {len(working_urls)} working direct access URLs:")
        for url in working_urls:
            print(f"   - {url}")
        
        print(f"\n🎯 SOLUTION: Update your app to use:")
        print(f"   {working_urls[0]}")
        print(f"\n   This bypasses Cloudflare completely!")
        
    else:
        print("❌ No direct access URLs worked")
        print("\n💡 NEXT STEPS:")
        print("   1. Disable Cloudflare in your InfinityFree panel")
        print("   2. Or switch to a different hosting service")
        print("   3. Or implement a proxy solution")
    
    print("\n" + "=" * 50)

if __name__ == "__main__":
    main()
