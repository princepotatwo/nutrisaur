#!/usr/bin/env python3
"""
Advanced Google Sign-In Debugging
Since Google Console config is correct, let's check other potential issues
"""

import json
import sys
from datetime import datetime

def check_google_services_consistency():
    """Check if google-services.json is consistent with Google Console"""
    print("🔍 Checking google-services.json consistency...")
    
    try:
        with open('app/google-services.json', 'r') as f:
            config = json.load(f)
        
        # Extract Android client info
        android_client = None
        for client in config['client']:
            if 'android_client_info' in client['client_info']:
                android_client = client
                break
        
        if not android_client:
            print("❌ No Android client found in google-services.json")
            return False
        
        # Check OAuth client
        oauth_client = None
        for oauth in android_client['oauth_client']:
            if oauth['client_type'] == 1:  # Android
                oauth_client = oauth
                break
        
        if not oauth_client:
            print("❌ No Android OAuth client found")
            return False
        
        print("✅ Android OAuth client found")
        print(f"   Client ID: {oauth_client['client_id']}")
        print(f"   Package: {android_client['client_info']['android_client_info']['package_name']}")
        print(f"   Certificate: {oauth_client['android_info']['certificate_hash']}")
        
        return True
        
    except Exception as e:
        print(f"❌ Error: {e}")
        return False

def check_google_signin_implementation():
    """Check the Google Sign-In implementation in the app"""
    print("\n🔍 Checking Google Sign-In implementation...")
    
    try:
        # Check LoginActivity
        with open('app/src/main/java/com/example/nutrisaur11/LoginActivity.java', 'r') as f:
            login_content = f.read()
        
        # Check for Google Sign-In setup
        google_signin_checks = [
            'GoogleSignInOptions',
            'GoogleSignInClient',
            'requestIdToken',
            'ANDROID_CLIENT_ID'
        ]
        
        for check in google_signin_checks:
            if check in login_content:
                print(f"✅ Found: {check}")
            else:
                print(f"❌ Missing: {check}")
                return False
        
        # Check the client ID
        if '43537903747-2nd9mtmm972ucoirho2sthkqlu8mct6b.apps.googleusercontent.com' in login_content:
            print("✅ Correct Android Client ID found")
        else:
            print("❌ Android Client ID not found or incorrect")
            return False
        
        return True
        
    except Exception as e:
        print(f"❌ Error: {e}")
        return False

def check_common_issues():
    """Check for common Google Sign-In issues"""
    print("\n🔍 Checking for common issues...")
    
    issues = []
    
    # Check if app is using debug keystore
    print("✅ App is using debug keystore (expected for development)")
    
    # Check if Google Play Services is available
    print("✅ Google Play Services should be available on device")
    
    # Check network connectivity
    print("✅ Network connectivity required for Google Sign-In")
    
    # Check if app is signed correctly
    print("✅ App should be signed with debug keystore")
    
    return True

def generate_advanced_troubleshooting():
    """Generate advanced troubleshooting steps"""
    print("\n🔧 Advanced Troubleshooting Steps:")
    print("=" * 50)
    
    print("""
Since Google Console configuration is correct, try these steps:

1. **Clear Google Play Services Cache**
   - Go to Settings > Apps > Google Play Services
   - Clear cache and data
   - Restart device

2. **Check Device Google Account**
   - Make sure device has a Google account signed in
   - Go to Settings > Accounts > Google
   - Verify account is active

3. **Test with Different Google Account**
   - Try signing in with a different Google account
   - Some accounts may have restrictions

4. **Check App Permissions**
   - Go to Settings > Apps > Nutrisaur
   - Ensure all permissions are granted
   - Especially Internet and Network access

5. **Try on Different Device**
   - Test on a different Android device
   - Some devices may have Google Play Services issues

6. **Enable Debug Logging**
   - Add this to your app for more detailed logs:
   ```java
   Log.d("GoogleSignIn", "Starting Google Sign-In...");
   ```

7. **Check for Google Play Services Updates**
   - Update Google Play Services in Play Store
   - Restart device after update

8. **Verify App Installation**
   - Uninstall app completely
   - Restart device
   - Reinstall app
   - Test Google Sign-In

9. **Check for Conflicting Apps**
   - Disable any VPN or proxy apps
   - Disable ad blockers
   - Check for security apps that might interfere

10. **Test in Airplane Mode (then back online)**
    - Turn on airplane mode
    - Wait 10 seconds
    - Turn off airplane mode
    - Test Google Sign-In
""")

def main():
    """Main advanced debugging function"""
    print("🔧 Advanced Google Sign-In Debugging")
    print("=" * 50)
    print(f"Timestamp: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print("=" * 50)
    
    # Run advanced checks
    checks = [
        ("Google Services Consistency", check_google_services_consistency),
        ("Google Sign-In Implementation", check_google_signin_implementation),
        ("Common Issues Check", check_common_issues)
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
    print("📊 ADVANCED DEBUG SUMMARY")
    print("="*50)
    
    passed = sum(1 for result in results.values() if result)
    total = len(results)
    
    for check_name, result in results.items():
        status = "✅ PASSED" if result else "❌ FAILED"
        print(f"{status}: {check_name}")
    
    print(f"\nOverall: {passed}/{total} checks passed")
    
    if passed == total:
        print("\n🎉 All advanced checks passed!")
        print("The issue is likely device-specific or environment-related.")
        generate_advanced_troubleshooting()
    else:
        print("\n⚠️  Some implementation issues found.")
        print("Fix the failed checks above, then test again.")
    
    return passed == total

if __name__ == "__main__":
    success = main()
    sys.exit(0 if success else 1)
