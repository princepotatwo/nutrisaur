#!/usr/bin/env python3
"""
Check DNS Settings
Check if Cloudflare is configured in DNS for nutrisaur.gt.tc
"""

import socket
import requests
import dns.resolver

def check_dns_settings():
    """Check DNS settings for the domain"""
    
    print("🔍 Checking DNS Settings for nutrisaur.gt.tc")
    print("=" * 50)
    
    domain = "nutrisaur.gt.tc"
    
    # Check 1: Basic DNS resolution
    print(f"\n🔍 Check 1: Basic DNS Resolution")
    try:
        ip = socket.gethostbyname(domain)
        print(f"  Domain resolves to: {ip}")
    except Exception as e:
        print(f"  ❌ DNS resolution failed: {e}")
        return
    
    # Check 2: Check nameservers
    print(f"\n🔍 Check 2: Nameservers")
    try:
        # Try to get nameservers
        nameservers = dns.resolver.resolve(domain, 'NS')
        print(f"  Nameservers:")
        for ns in nameservers:
            ns_str = str(ns)
            print(f"    - {ns_str}")
            
            # Check if it's Cloudflare
            if 'cloudflare' in ns_str.lower():
                print(f"      ❌ CLOUDFLARE detected!")
            elif 'infinityfree' in ns_str.lower():
                print(f"      ✅ InfinityFree nameserver")
            else:
                print(f"      ? Unknown nameserver")
                
    except Exception as e:
        print(f"  ❌ Could not get nameservers: {e}")
        print(f"  💡 This might mean Cloudflare is blocking DNS queries")
    
    # Check 3: Check HTTP headers for Cloudflare
    print(f"\n🔍 Check 3: HTTP Headers")
    try:
        response = requests.get(f"http://{domain}", timeout=10)
        headers = dict(response.headers)
        
        print(f"  Server: {headers.get('Server', 'Unknown')}")
        print(f"  CF-Ray: {headers.get('CF-Ray', 'Not present')}")
        print(f"  CF-Cache-Status: {headers.get('CF-Cache-Status', 'Not present')}")
        
        if 'CF-Ray' in headers or 'CF-Cache-Status' in headers:
            print(f"  ❌ CLOUDFLARE headers detected!")
        else:
            print(f"  ✅ No Cloudflare headers detected")
            
    except Exception as e:
        print(f"  ❌ HTTP check failed: {e}")
    
    # Check 4: Check if we can access InfinityFree directly
    print(f"\n🔍 Check 4: InfinityFree Direct Access")
    try:
        # Try to access your account directly
        test_url = f"http://{ip}/if0_39764899/"
        response = requests.get(test_url, timeout=10)
        print(f"  Direct IP access: {test_url}")
        print(f"  Status: {response.status_code}")
        
        if response.status_code == 200:
            print(f"  ✅ Direct access works!")
        else:
            print(f"  ❌ Direct access failed")
            
    except Exception as e:
        print(f"  ❌ Direct access error: {e}")

def main():
    """Main function"""
    print("🚀 DNS Settings Check for nutrisaur.gt.tc")
    print("=" * 50)
    print("This will help identify where Cloudflare is configured")
    print("=" * 50)
    
    check_dns_settings()
    
    print("\n" + "=" * 50)
    print("📊 DNS ANALYSIS RESULTS")
    print("=" * 50)
    print("💡 If Cloudflare is detected in nameservers:")
    print("   1. You need to change nameservers in InfinityFree")
    print("   2. Or contact InfinityFree support to disable Cloudflare")
    print("   3. Or switch to a different hosting service")
    print("\n💡 If no Cloudflare detected:")
    print("   1. The blocking might be at the server level")
    print("   2. Check InfinityFree control panel more carefully")
    print("   3. Contact InfinityFree support")
    
    print("\n" + "=" * 50)

if __name__ == "__main__":
    main()
