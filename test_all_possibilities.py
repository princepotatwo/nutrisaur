#!/usr/bin/env python3
"""
Comprehensive Server Access Test
Test all possible ways to access your server and find what actually works
"""

import requests
import socket
import time

def test_domain_resolution():
    """Test if domains can be resolved"""
    
    print("🔍 Testing Domain Resolution")
    print("=" * 50)
    
    domains_to_test = [
        "nutrisaur.gt.tc",
        "nutrisaur.epizy.com", 
        "nutrisaur.free.nf",
        "nutrisaur.rf.gd",
        "nutrisaur.42web.io",
        "nutrisaur.000webhostapp.com",
        "nutrisaur.herokuapp.com"
    ]
    
    working_domains = []
    
    for domain in domains_to_test:
        try:
            ip = socket.gethostbyname(domain)
            print(f"  ✅ {domain} → {ip}")
            working_domains.append(domain)
        except socket.gaierror:
            print(f"  ❌ {domain} → Cannot resolve")
    
    return working_domains

def test_server_connectivity():
    """Test basic server connectivity"""
    
    print(f"\n🔍 Testing Server Connectivity")
    print("=" * 50)
    
    # Your server details from control panel
    server_ip = "185.27.134.121"
    username = "if0_39764899"
    
    test_urls = [
        f"http://{server_ip}/",
        f"http://{server_ip}/{username}/",
        f"http://{server_ip}/{username}/index.php",
        f"http://{server_ip}/{username}/api.php",
        f"http://{server_ip}/{username}/unified_api.php"
    ]
    
    working_urls = []
    
    for url in test_urls:
        try:
            print(f"\n🔍 Testing: {url}")
            
            # Try with different timeouts
            for timeout in [5, 10, 15]:
                try:
                    response = requests.get(url, timeout=timeout)
                    print(f"  ✅ Status: {response.status_code}")
                    print(f"  Response: {response.text[:200]}...")
                    
                    if response.status_code == 200:
                        working_urls.append(url)
                        break
                        
                except requests.exceptions.Timeout:
                    print(f"  ⏰ Timeout after {timeout}s")
                    continue
                except requests.exceptions.ConnectionError as e:
                    print(f"  ❌ Connection error: {e}")
                    break
                    
        except Exception as e:
            print(f"  ❌ Error: {e}")
    
    return working_urls

def test_infinityfree_subdomains():
    """Test common InfinityFree subdomain patterns"""
    
    print(f"\n🔍 Testing InfinityFree Subdomain Patterns")
    print("=" * 50)
    
    # Common InfinityFree patterns
    base_names = ["nutrisaur", "nutrisaur11", "nutrisaur-app", "nutrisaur-app11"]
    extensions = ["epizy.com", "free.nf", "rf.gd", "42web.io", "000webhostapp.com"]
    
    working_subdomains = []
    
    for base in base_names:
        for ext in extensions:
            subdomain = f"{base}.{ext}"
            try:
                print(f"\n🔍 Testing: {subdomain}")
                
                # Test basic connectivity
                ip = socket.gethostbyname(subdomain)
                print(f"  IP: {ip}")
                
                # Test HTTP access
                url = f"http://{subdomain}/"
                try:
                    response = requests.get(url, timeout=10)
                    print(f"  Status: {response.status_code}")
                    
                    if response.status_code == 200:
                        print(f"  ✅ SUCCESS: {subdomain}")
                        working_subdomains.append(subdomain)
                        
                        # Test if it has your files
                        test_urls = [
                            f"http://{subdomain}/api.php?test=1",
                            f"http://{subdomain}/unified_api.php?test=1",
                            f"http://{subdomain}/index.php"
                        ]
                        
                        for test_url in test_urls:
                            try:
                                test_response = requests.get(test_url, timeout=5)
                                if test_response.status_code == 200:
                                    print(f"    ✅ {test_url} works!")
                                    if "aes.js" not in test_response.text:
                                        print(f"    🎯 NO CLOUDFLARE: {test_url}")
                                        return [test_url]  # Found perfect solution!
                            except:
                                pass
                                
                except Exception as e:
                    print(f"  HTTP Error: {e}")
                    
            except socket.gaierror:
                print(f"  ❌ Cannot resolve")
    
    return working_subdomains

def test_alternative_hosting():
    """Test alternative hosting services"""
    
    print(f"\n🔍 Testing Alternative Hosting Services")
    print("=" * 50)
    
    # Test if common hosting services are accessible
    test_services = [
        "https://000webhost.com",
        "https://heroku.com", 
        "https://vercel.com",
        "https://netlify.com"
    ]
    
    for service in test_services:
        try:
            response = requests.get(service, timeout=10)
            print(f"  ✅ {service} - Status: {response.status_code}")
        except Exception as e:
            print(f"  ❌ {service} - Error: {e}")

def main():
    """Main test function"""
    print("🚀 Comprehensive Server Access Test")
    print("=" * 50)
    print("Testing all possible ways to access your server")
    print("=" * 50)
    
    # Test 1: Domain resolution
    working_domains = test_domain_resolution()
    
    # Test 2: Direct server access
    working_server_urls = test_server_connectivity()
    
    # Test 3: InfinityFree subdomains
    working_subdomains = test_infinityfree_subdomains()
    
    # Test 4: Alternative hosting
    test_alternative_hosting()
    
    print("\n" + "=" * 50)
    print("📊 COMPREHENSIVE TEST RESULTS")
    print("=" * 50)
    
    if working_domains:
        print(f"✅ Working domains ({len(working_domains)}):")
        for domain in working_domains:
            print(f"   - {domain}")
    
    if working_server_urls:
        print(f"✅ Working server URLs ({len(working_server_urls)}):")
        for url in working_server_urls:
            print(f"   - {url}")
    
    if working_subdomains:
        print(f"✅ Working subdomains ({len(working_subdomains)}):")
        for subdomain in working_subdomains:
            print(f"   - {subdomain}")
    
    if not any([working_domains, working_server_urls, working_subdomains]):
        print("❌ No working access methods found!")
        print("\n💡 RECOMMENDATIONS:")
        print("   1. Check your InfinityFree control panel")
        print("   2. Make sure your files are uploaded")
        print("   3. Consider switching to 000webhost")
        print("   4. Check if your account is suspended")
    
    print("\n" + "=" * 50)

if __name__ == "__main__":
    main()
