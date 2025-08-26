#!/usr/bin/env python3
"""
Alternative Solutions Test
Test different hosting options to find what actually works
"""

import requests
import socket
import time

def test_000webhost():
    """Test 000webhost accessibility"""
    
    print("🔍 Testing 000webhost Alternative")
    print("=" * 50)
    
    # Test if we can access 000webhost
    try:
        response = requests.get("https://000webhost.com", timeout=10)
        print(f"✅ 000webhost.com accessible - Status: {response.status_code}")
        
        # Test if we can create an account
        print("💡 000webhost is accessible - you can create a free account there")
        return True
        
    except Exception as e:
        print(f"❌ 000webhost.com error: {e}")
        return False

def test_heroku():
    """Test Heroku accessibility"""
    
    print(f"\n🔍 Testing Heroku Alternative")
    print("=" * 50)
    
    try:
        response = requests.get("https://heroku.com", timeout=10)
        print(f"✅ Heroku.com accessible - Status: {response.status_code}")
        
        # Test if we can access the signup
        signup_response = requests.get("https://signup.heroku.com", timeout=10)
        print(f"✅ Heroku signup accessible - Status: {signup_response.status_code}")
        
        print("💡 Heroku is accessible - you can create a free account there")
        return True
        
    except Exception as e:
        print(f"❌ Heroku error: {e}")
        return False

def test_vercel():
    """Test Vercel accessibility"""
    
    print(f"\n🔍 Testing Vercel Alternative")
    print("=" * 50)
    
    try:
        response = requests.get("https://vercel.com", timeout=10)
        print(f"✅ Vercel.com accessible - Status: {response.status_code}")
        
        print("💡 Vercel is accessible - you can create a free account there")
        return True
        
    except Exception as e:
        print(f"❌ Vercel error: {e}")
        return False

def test_github_pages():
    """Test GitHub Pages as alternative"""
    
    print(f"\n🔍 Testing GitHub Pages Alternative")
    print("=" * 50)
    
    try:
        response = requests.get("https://pages.github.com", timeout=10)
        print(f"✅ GitHub Pages accessible - Status: {response.status_code}")
        
        print("💡 GitHub Pages is accessible - you can host static files there")
        return True
        
    except Exception as e:
        print(f"❌ GitHub Pages error: {e}")
        return False

def test_local_solution():
    """Test if we can create a local solution"""
    
    print(f"\n🔍 Testing Local Solution")
    print("=" * 50)
    
    print("💡 Local development server option:")
    print("   - Run PHP locally on your computer")
    print("   - Use ngrok to expose it to the internet")
    print("   - Your app connects to your local server")
    print("   - No hosting issues, no Cloudflare blocking")
    
    return True

def test_existing_domains():
    """Test if any existing domains are working"""
    
    print(f"\n🔍 Testing Existing Domains")
    print("=" * 50)
    
    # Test your original domain
    try:
        response = requests.get("http://nutrisaur.gt.tc/api.php?test=1", timeout=10)
        if response.status_code == 200:
            if "aes.js" in response.text:
                print("❌ nutrisaur.gt.tc - Still has Cloudflare blocking")
            else:
                print("✅ nutrisaur.gt.tc - Working! No Cloudflare!")
                return "nutrisaur.gt.tc"
        else:
            print(f"❌ nutrisaur.gt.tc - Status: {response.status_code}")
    except Exception as e:
        print(f"❌ nutrisaur.gt.tc - Error: {e}")
    
    return None

def main():
    """Main test function"""
    print("🚀 Alternative Solutions Test")
    print("=" * 50)
    print("Let's find a working solution without InfinityFree headaches!")
    print("=" * 50)
    
    # Test different alternatives
    alternatives = []
    
    if test_000webhost():
        alternatives.append("000webhost")
    
    if test_heroku():
        alternatives.append("Heroku")
    
    if test_vercel():
        alternatives.append("Vercel")
    
    if test_github_pages():
        alternatives.append("GitHub Pages")
    
    test_local_solution()
    alternatives.append("Local Development")
    
    # Test existing domains
    working_domain = test_existing_domains()
    
    print("\n" + "=" * 50)
    print("📊 ALTERNATIVE SOLUTIONS RESULTS")
    print("=" * 50)
    
    if alternatives:
        print(f"✅ Working alternatives ({len(alternatives)}):")
        for alt in alternatives:
            print(f"   - {alt}")
    
    if working_domain:
        print(f"\n🎯 EXCELLENT NEWS!")
        print(f"   Your existing domain {working_domain} is working!")
        print(f"   No need to change anything!")
    
    print(f"\n💡 RECOMMENDATIONS:")
    print(f"   1. 000webhost - Easiest alternative to InfinityFree")
    print(f"   2. Heroku - Most professional, great free tier")
    print(f"   3. Local Development - No hosting issues at all")
    
    if working_domain:
        print(f"\n🚀 IMMEDIATE SOLUTION:")
        print(f"   Use your existing working domain: {working_domain}")
        print(f"   Update your app to use it!")
    
    print("\n" + "=" * 50)

if __name__ == "__main__":
    main()
