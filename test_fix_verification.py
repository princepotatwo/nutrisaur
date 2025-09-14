#!/usr/bin/env python3
"""
Comprehensive test to verify the previous button fix.
This script will monitor the app and provide detailed analysis.
"""

import subprocess
import time
import re
from datetime import datetime

def clear_logs():
    """Clear existing logs"""
    print("🧹 Clearing existing logs...")
    subprocess.run(['/Users/jasminpingol/Library/Android/sdk/platform-tools/adb', 'logcat', '-c'])

def monitor_nutritional_screening():
    """Monitor the nutritional screening activity specifically"""
    print("🔍 Monitoring NutritionalScreeningActivity...")
    print("📱 Please follow these steps:")
    print("   1. Navigate to nutritional screening")
    print("   2. Go through questions until you reach weight/height")
    print("   3. Enter text in weight or height field")
    print("   4. Click previous button")
    print("   5. Verify it works with ONE click")
    print("\n⏳ Monitoring for 120 seconds...")
    print("=" * 70)
    
    try:
        # Monitor only NutritionalScreening logs
        process = subprocess.Popen([
            '/Users/jasminpingol/Library/Android/sdk/platform-tools/adb', 
            'logcat', 
            '-s', 
            'NutritionalScreening:*'
        ], stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
        
        start_time = time.time()
        events = []
        previous_clicks = []
        weight_height_events = []
        
        while time.time() - start_time < 120:  # Monitor for 2 minutes
            line = process.stdout.readline()
            if line:
                timestamp = datetime.now().strftime("%H:%M:%S")
                print(f"[{timestamp}] {line.strip()}")
                
                # Track events
                event = {
                    'timestamp': timestamp,
                    'message': line.strip()
                }
                events.append(event)
                
                # Track previous button clicks
                if "previous" in line.lower() and "click" in line.lower():
                    previous_clicks.append(event)
                    print(f"🔘 PREVIOUS BUTTON EVENT: {line.strip()}")
                
                # Track weight/height navigation
                if "weight" in line.lower() or "height" in line.lower():
                    weight_height_events.append(event)
                    print(f"⚖️ WEIGHT/HEIGHT EVENT: {line.strip()}")
                
                # Track question navigation
                if "showQuestion" in line:
                    print(f"📋 QUESTION NAVIGATION: {line.strip()}")
        
        process.terminate()
        
        # Analysis
        print("\n" + "=" * 70)
        print("📊 ANALYSIS RESULTS:")
        print(f"   Total events captured: {len(events)}")
        print(f"   Previous button events: {len(previous_clicks)}")
        print(f"   Weight/Height events: {len(weight_height_events)}")
        
        if previous_clicks:
            print("\n🔘 PREVIOUS BUTTON CLICK ANALYSIS:")
            for i, click in enumerate(previous_clicks, 1):
                print(f"   Click {i}: {click['timestamp']} - {click['message']}")
            
            # Check for double-click pattern
            if len(previous_clicks) >= 2:
                time_diff = None
                for i in range(1, len(previous_clicks)):
                    # Simple time comparison (this is a basic check)
                    print(f"   ⚠️  Multiple clicks detected - potential double-click issue")
            else:
                print("   ✅ Single click pattern detected")
        
        # Check for weight/height navigation
        if weight_height_events:
            print("\n⚖️ WEIGHT/HEIGHT NAVIGATION:")
            for event in weight_height_events:
                print(f"   {event['timestamp']}: {event['message']}")
        
        return len(previous_clicks), len(weight_height_events)
        
    except Exception as e:
        print(f"❌ Error during monitoring: {e}")
        return 0, 0

def main():
    print("🚀 NUTRITIONAL SCREENING PREVIOUS BUTTON TEST")
    print("=" * 50)
    
    # Clear logs first
    clear_logs()
    
    # Monitor the app
    click_count, weight_height_count = monitor_nutritional_screening()
    
    print("\n" + "=" * 70)
    print("🎯 FINAL VERDICT:")
    
    if click_count == 0:
        print("❌ No previous button activity detected")
        print("   - Make sure you tested the previous button")
        print("   - Check if the app is running correctly")
    elif click_count == 1:
        print("🎉 SUCCESS: Previous button works with single click!")
        print("   - The double-click issue has been fixed")
    else:
        print("⚠️  WARNING: Multiple clicks detected")
        print(f"   - {click_count} previous button events found")
        print("   - This may indicate the issue still exists")
    
    if weight_height_count > 0:
        print("✅ Weight/Height navigation detected")
    else:
        print("ℹ️  No weight/height navigation detected")

if __name__ == "__main__":
    main()
