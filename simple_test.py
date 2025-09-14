#!/usr/bin/env python3
"""
Simple test to verify the previous button fix works.
"""

import subprocess
import time

def test_previous_button():
    print("🧪 TESTING PREVIOUS BUTTON FIX")
    print("=" * 40)
    
    # Clear logs
    subprocess.run(['/Users/jasminpingol/Library/Android/sdk/platform-tools/adb', 'logcat', '-c'])
    
    print("📱 Instructions:")
    print("1. Open the app")
    print("2. Go to nutritional screening")
    print("3. Navigate to weight or height question")
    print("4. Enter some text (e.g., '70' for weight)")
    print("5. Click the previous button ONCE")
    print("6. Check if it goes back properly")
    print("\n⏳ Monitoring for 30 seconds...")
    print("-" * 40)
    
    try:
        # Monitor logs
        process = subprocess.Popen([
            '/Users/jasminpingol/Library/Android/sdk/platform-tools/adb', 
            'logcat', 
            '-s', 
            'NutritionalScreening:*'
        ], stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
        
        start_time = time.time()
        events = []
        
        while time.time() - start_time < 30:
            line = process.stdout.readline()
            if line:
                print(f"📋 {line.strip()}")
                events.append(line.strip())
        
        process.terminate()
        
        print("\n" + "=" * 40)
        print("📊 RESULTS:")
        print(f"Total events: {len(events)}")
        
        # Look for specific patterns
        previous_events = [e for e in events if 'previous' in e.lower()]
        weight_height_events = [e for e in events if 'weight' in e.lower() or 'height' in e.lower()]
        
        print(f"Previous button events: {len(previous_events)}")
        print(f"Weight/Height events: {len(weight_height_events)}")
        
        if previous_events:
            print("\n🔘 Previous button activity detected:")
            for event in previous_events:
                print(f"   - {event}")
        
        if weight_height_events:
            print("\n⚖️ Weight/Height activity detected:")
            for event in weight_height_events:
                print(f"   - {event}")
        
        print("\n✅ Test completed!")
        print("If the previous button worked with one click, the fix is successful!")
        
    except Exception as e:
        print(f"❌ Error: {e}")

if __name__ == "__main__":
    test_previous_button()
