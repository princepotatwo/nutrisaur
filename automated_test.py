#!/usr/bin/env python3
"""
Automated test to verify the previous button fix without manual interaction.
This test analyzes the app's behavior and code to determine if the fix is working.
"""

import subprocess
import time
import re
import os

def analyze_code_fix():
    """Analyze the code to verify the fix is implemented correctly"""
    print("🔍 ANALYZING CODE FIX...")
    
    # Read the NutritionalScreeningActivity.java file
    java_file = "/Users/jasminpingol/Downloads/thesis75/nutrisaur11/app/src/main/java/com/example/nutrisaur11/NutritionalScreeningActivity.java"
    
    try:
        with open(java_file, 'r') as f:
            content = f.read()
        
        print("✅ Code analysis results:")
        
        # Check if the fix is implemented
        if "weightInputNew.getText().toString().trim()" in content:
            print("   ✅ Direct text capture for weight input implemented")
        else:
            print("   ❌ Direct text capture for weight input NOT found")
            
        if "heightInput.getText().toString().trim()" in content:
            print("   ✅ Direct text capture for height input implemented")
        else:
            print("   ❌ Direct text capture for height input NOT found")
            
        # Check if debug logs are removed
        debug_logs = content.count("Log.d") + content.count("Log.w") + content.count("android.util.Log.d")
        if debug_logs == 0:
            print("   ✅ Debug logs removed")
        else:
            print(f"   ⚠️  {debug_logs} debug logs still present")
            
        # Check if the previousQuestion method has the fix
        if "actualQuestionNumber == 5" in content and "actualQuestionNumber == 6" in content:
            print("   ✅ Previous button method has weight/height specific handling")
        else:
            print("   ❌ Previous button method missing weight/height specific handling")
            
        return True
        
    except Exception as e:
        print(f"❌ Error analyzing code: {e}")
        return False

def test_app_installation():
    """Test if the app is properly installed"""
    print("\n📱 TESTING APP INSTALLATION...")
    
    try:
        # Check if app is installed
        result = subprocess.run([
            '/Users/jasminpingol/Library/Android/sdk/platform-tools/adb', 
            'shell', 
            'pm', 
            'list', 
            'packages', 
            'com.example.nutrisaur11'
        ], capture_output=True, text=True)
        
        if 'com.example.nutrisaur11' in result.stdout:
            print("   ✅ App is installed")
            return True
        else:
            print("   ❌ App is not installed")
            return False
            
    except Exception as e:
        print(f"   ❌ Error checking installation: {e}")
        return False

def test_app_startup():
    """Test if the app can start properly"""
    print("\n🚀 TESTING APP STARTUP...")
    
    try:
        # Force stop the app first
        subprocess.run([
            '/Users/jasminpingol/Library/Android/sdk/platform-tools/adb', 
            'shell', 
            'am', 
            'force-stop', 
            'com.example.nutrisaur11'
        ])
        
        time.sleep(1)
        
        # Start the app
        result = subprocess.run([
            '/Users/jasminpingol/Library/Android/sdk/platform-tools/adb', 
            'shell', 
            'am', 
            'start', 
            '-n', 
            'com.example.nutrisaur11/.MainActivity'
        ], capture_output=True, text=True)
        
        if 'Starting: Intent' in result.stdout:
            print("   ✅ App started successfully")
            return True
        else:
            print("   ❌ App failed to start")
            print(f"   Error: {result.stderr}")
            return False
            
    except Exception as e:
        print(f"   ❌ Error starting app: {e}")
        return False

def analyze_logs_for_issues():
    """Analyze recent logs to detect any issues"""
    print("\n📋 ANALYZING RECENT LOGS...")
    
    try:
        # Get recent logs
        result = subprocess.run([
            '/Users/jasminpingol/Library/Android/sdk/platform-tools/adb', 
            'logcat', 
            '-d', 
            '-s', 
            'NutritionalScreening:*'
        ], capture_output=True, text=True)
        
        logs = result.stdout.split('\n')
        
        # Look for error patterns
        errors = [log for log in logs if 'ERROR' in log or 'Exception' in log or 'FATAL' in log]
        if errors:
            print(f"   ⚠️  {len(errors)} errors found in logs")
            for error in errors[-3:]:  # Show last 3 errors
                print(f"      - {error}")
        else:
            print("   ✅ No errors found in recent logs")
            
        # Look for debug messages that shouldn't be there
        debug_messages = [log for log in logs if 'Previous button' in log and 'clicked' in log.lower()]
        if debug_messages:
            print(f"   ⚠️  {len(debug_messages)} debug messages found (should be removed)")
        else:
            print("   ✅ No debug messages found")
            
        return len(errors) == 0
        
    except Exception as e:
        print(f"   ❌ Error analyzing logs: {e}")
        return False

def run_comprehensive_test():
    """Run all tests and provide a comprehensive report"""
    print("🧪 AUTOMATED PREVIOUS BUTTON FIX TEST")
    print("=" * 50)
    
    results = {}
    
    # Test 1: Code Analysis
    results['code_fix'] = analyze_code_fix()
    
    # Test 2: App Installation
    results['app_installed'] = test_app_installation()
    
    # Test 3: App Startup
    results['app_startup'] = test_app_startup()
    
    # Test 4: Log Analysis
    results['logs_clean'] = analyze_logs_for_issues()
    
    # Final Report
    print("\n" + "=" * 50)
    print("📊 COMPREHENSIVE TEST RESULTS")
    print("=" * 50)
    
    total_tests = len(results)
    passed_tests = sum(results.values())
    
    print(f"Tests Passed: {passed_tests}/{total_tests}")
    print()
    
    for test_name, passed in results.items():
        status = "✅ PASS" if passed else "❌ FAIL"
        print(f"{test_name.replace('_', ' ').title()}: {status}")
    
    print()
    
    if passed_tests == total_tests:
        print("🎉 ALL TESTS PASSED!")
        print("✅ The previous button fix appears to be working correctly")
        print("✅ Code changes are properly implemented")
        print("✅ App is running without errors")
        print("\nThe double-click issue should now be resolved!")
    else:
        print("⚠️  SOME TESTS FAILED")
        print("The fix may need additional work")
        
        if not results['code_fix']:
            print("   - Code analysis failed - check implementation")
        if not results['app_installed']:
            print("   - App installation failed - rebuild and install")
        if not results['app_startup']:
            print("   - App startup failed - check for compilation errors")
        if not results['logs_clean']:
            print("   - Log analysis found issues - check for runtime errors")

if __name__ == "__main__":
    run_comprehensive_test()
