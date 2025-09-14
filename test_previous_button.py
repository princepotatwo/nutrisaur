#!/usr/bin/env python3
"""
Test script to verify the previous button functionality in NutritionalScreeningActivity.
This script monitors the app logs to check if the previous button works correctly.
"""

import subprocess
import time
import re

def monitor_app_logs():
    """Monitor the app logs for previous button behavior"""
    print("🔍 Starting app log monitoring...")
    print("📱 Please test the previous button functionality:")
    print("   1. Open the app and go to nutritional screening")
    print("   2. Navigate to weight or height question")
    print("   3. Enter some text")
    print("   4. Click the previous button")
    print("   5. Check if it works with just ONE click")
    print("\n⏳ Monitoring logs for 60 seconds...")
    print("=" * 60)
    
    # Start monitoring logs
    try:
        process = subprocess.Popen([
            '/Users/jasminpingol/Library/Android/sdk/platform-tools/adb', 
            'logcat', 
            '-s', 
            'NutritionalScreening:*'
        ], stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
        
        start_time = time.time()
        previous_button_clicks = 0
        weight_height_navigation = False
        
        while time.time() - start_time < 60:  # Monitor for 60 seconds
            line = process.stdout.readline()
            if line:
                print(f"📋 {line.strip()}")
                
                # Count previous button clicks
                if "Previous button" in line and "clicked" in line.lower():
                    previous_button_clicks += 1
                    print(f"🔘 Previous button clicked! (Total: {previous_button_clicks})")
                
                # Check for weight/height navigation
                if "showWeightQuestion" in line or "showHeightQuestion" in line:
                    weight_height_navigation = True
                    print("⚖️ Weight/Height question detected!")
                
                # Check for successful navigation
                if "showQuestion" in line and "sequenceIndex" in line:
                    print("✅ Question navigation detected!")
        
        process.terminate()
        
        print("\n" + "=" * 60)
        print("📊 TEST RESULTS:")
        print(f"   Previous button clicks detected: {previous_button_clicks}")
        print(f"   Weight/Height navigation: {'Yes' if weight_height_navigation else 'No'}")
        
        if previous_button_clicks > 0:
            print("✅ Previous button is working!")
            if previous_button_clicks == 1:
                print("🎉 SUCCESS: Previous button works with single click!")
            else:
                print("⚠️  WARNING: Multiple clicks detected - may still have issues")
        else:
            print("❌ No previous button activity detected")
            
    except Exception as e:
        print(f"❌ Error monitoring logs: {e}")

if __name__ == "__main__":
    monitor_app_logs()
