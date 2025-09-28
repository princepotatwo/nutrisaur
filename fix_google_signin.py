#!/usr/bin/env python3
"""
Google Sign-In Error Code 10 Fix Script
This script helps verify and fix Google Sign-In configuration issues
"""

import json
import sys
from datetime import datetime

def verify_google_services_config():
    """Verify google-services.json configuration"""
    print("🔍 Verifying google-services.json configuration...")
    
    try:
        with open('app/google-services.json', 'r') as f:
            config = json.load(f)
        
        # Extract key information
        project_number = config['project_info']['project_number']
        project_id = config['project_info']['project_id']
        
        print(f"✅ Project Number: {project_number}")
        print(f"✅ Project ID: {project_id}")
        
        # Check Android client configuration
        for client in config['client']:
            if 'android_client_info' in client['client_info']:
                package_name = client['client_info']['android_client_info']['package_name']
                print(f"✅ Package Name: {package_name}")
                
                # Check OAuth clients
                for oauth_client in client['oauth_client']:
                    if oauth_client['client_type'] == 1:  # Android client
                        client_id = oauth_client['client_id']
                        certificate_hash = oauth_client['android_info']['certificate_hash']
                        
                        print(f"✅ Android Client ID: {client_id}")
                        print(f"✅ Certificate Hash: {certificate_hash}")
                        
                        # Verify the certificate hash matches our debug keystore
                        expected_hash = "f7fa7bac3388cabac62dfd8a09ede29c3c508010"
                        if certificate_hash.lower() == expected_hash.lower():
                            print("✅ Certificate hash matches debug keystore")
                        else:
                            print(f"❌ Certificate hash mismatch!")
                            print(f"   Expected: {expected_hash}")
                            print(f"   Found:    {certificate_hash}")
                            return False
        
        return True
        
    except FileNotFoundError:
        print("❌ google-services.json not found!")
        return False
    except Exception as e:
        print(f"❌ Error reading google-services.json: {e}")
        return False

def check_android_manifest():
    """Check AndroidManifest.xml for required permissions"""
    print("\n🔍 Checking AndroidManifest.xml...")
    
    try:
        with open('app/src/main/AndroidManifest.xml', 'r') as f:
            manifest_content = f.read()
        
        required_permissions = [
            'android.permission.INTERNET',
            'android.permission.ACCESS_NETWORK_STATE'
        ]
        
        for permission in required_permissions:
            if permission in manifest_content:
                print(f"✅ Found permission: {permission}")
            else:
                print(f"❌ Missing permission: {permission}")
                return False
        
        return True
        
    except FileNotFoundError:
        print("❌ AndroidManifest.xml not found!")
        return False
    except Exception as e:
        print(f"❌ Error reading AndroidManifest.xml: {e}")
        return False

def check_build_gradle():
    """Check build.gradle.kts for Google Services plugin"""
    print("\n🔍 Checking build.gradle.kts...")
    
    try:
        with open('app/build.gradle.kts', 'r') as f:
            gradle_content = f.read()
        
        required_items = [
            'com.google.gms.google-services',
            'play-services-auth',
            'firebase-bom'
        ]
        
        for item in required_items:
            if item in gradle_content:
                print(f"✅ Found: {item}")
            else:
                print(f"❌ Missing: {item}")
                return False
        
        return True
        
    except FileNotFoundError:
        print("❌ build.gradle.kts not found!")
        return False
    except Exception as e:
        print(f"❌ Error reading build.gradle.kts: {e}")
        return False

def generate_google_console_instructions():
    """Generate instructions for Google Console configuration"""
    print("\n📋 Google Console Configuration Instructions:")
    print("=" * 60)
    
    print("""
1. Go to Google Cloud Console: https://console.cloud.google.com/
2. Select project: nutrisaur-ebf29
3. Go to APIs & Services > Credentials
4. Find your Android OAuth 2.0 client ID
5. Click Edit on the Android client
6. Verify/Update these settings:

   Package name: com.example.nutrisaur11
   SHA-1 certificate fingerprint: F7:FA:7B:AC:33:88:CA:BA:C6:2D:FD:8A:09:ED:E2:9C:3C:50:80:10
   
7. Save the changes
8. Wait 5-10 minutes for changes to propagate
9. Test the app again

IMPORTANT: Make sure there are no extra spaces or characters in the package name!
""")

def main():
    """Main fix function"""
    print("🔧 Google Sign-In Error Code 10 Fix Tool")
    print("=" * 50)
    print(f"Timestamp: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print("=" * 50)
    
    # Run checks
    checks = [
        ("Google Services Config", verify_google_services_config),
        ("Android Manifest", check_android_manifest),
        ("Build Gradle", check_build_gradle)
    ]
    
    results = {}
    
    for check_name, check_func in checks:
        print(f"\n{'='*20} {check_name} {'='*20}")
        try:
            result = check_func()
            results[check_name] = result
            status = "✅ PASSED" if result else "❌ FAILED"
            print(f"\n{status}: {check_name}")
        except Exception as e:
            print(f"❌ ERROR in {check_name}: {e}")
            results[check_name] = False
    
    # Summary
    print("\n" + "="*50)
    print("📊 CHECK SUMMARY")
    print("="*50)
    
    passed = sum(1 for result in results.values() if result)
    total = len(results)
    
    for check_name, result in results.items():
        status = "✅ PASSED" if result else "❌ FAILED"
        print(f"{status}: {check_name}")
    
    print(f"\nOverall: {passed}/{total} checks passed")
    
    if passed == total:
        print("\n🎉 All configuration checks passed!")
        print("The issue is likely in Google Console configuration.")
        generate_google_console_instructions()
    else:
        print("\n⚠️  Some configuration issues found.")
        print("Fix the failed checks above, then test again.")
    
    return passed == total

if __name__ == "__main__":
    success = main()
    sys.exit(0 if success else 1)
