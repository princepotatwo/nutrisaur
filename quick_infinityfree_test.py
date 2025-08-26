#!/usr/bin/env python3
"""
Quick InfinityFree Test
Try different ways to access your server
"""

import requests
import socket

def quick_test():
    """Quick test of different access methods"""
    
    print("🚀 Quick InfinityFree Access Test")
    print("=" * 50)
    
    # Your server details
    server_ip = "185.27.134.121"
    username = "if0_39764899"
    
    print(f"Server IP: {server_ip}")
    print(f"Username: {username}")
    print("=" * 50)
    
    # Test 1: Try different ports
    print(f"\n🔍 Test 1: Different Ports")
    ports = [80, 8080, 443, 21, 22]
    
    for port in ports:
        try:
            sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            sock.settimeout(5)
            result = sock.connect_ex((server_ip, port))
            if result == 0:
                print(f"  ✅ Port {port} is open")
            else:
                print(f"  ❌ Port {port} is closed")
            sock.close()
        except Exception as e:
            print(f"  ❌ Port {port} error: {e}")
    
    # Test 2: Try different URL patterns
    print(f"\n🔍 Test 2: Different URL Patterns")
    url_patterns = [
        f"http://{server_ip}/",
        f"http://{server_ip}/{username}/",
        f"http://{server_ip}/{username}/index.php",
        f"http://{server_ip}/{username}/api.php",
        f"https://{server_ip}/",
        f"https://{server_ip}/{username}/"
    ]
    
    for url in url_patterns:
        try:
            print(f"\n  Testing: {url}")
            response = requests.get(url, timeout=10)
            print(f"    Status: {response.status_code}")
            
            if response.status_code == 200:
                content = response.text
                if "aes.js" in content:
                    print(f"    ❌ Cloudflare challenge")
                else:
                    print(f"    ✅ SUCCESS! No Cloudflare!")
                    print(f"    Content length: {len(content)}")
                    return url
                    
        except Exception as e:
            print(f"    ❌ Error: {e}")
    
    return None

def main():
    """Main function"""
    print("🚀 Quick InfinityFree Access Test")
    print("=" * 50)
    print("Testing different ways to access your server")
    print("=" * 50)
    
    working_url = quick_test()
    
    print("\n" + "=" * 50)
    print("📊 QUICK TEST RESULTS")
    print("=" * 50)
    
    if working_url:
        print(f"✅ SUCCESS! Found working URL: {working_url}")
        print(f"\n🚀 Use this URL in your app!")
        print(f"   Update Constants.java with: {working_url}")
        
    else:
        print("❌ No working access method found")
        print("\n💡 RECOMMENDATIONS:")
        print("   1. Contact InfinityFree support to disable Cloudflare")
        print("   2. Switch to 000webhost (free, no Cloudflare)")
        print("   3. Use local development with ngrok")
        print("   4. Cloudflare is blocking everything")
    
    print("\n" + "=" * 50)

if __name__ == "__main__":
    main()
