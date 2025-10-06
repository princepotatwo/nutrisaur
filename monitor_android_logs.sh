#!/bin/bash
# Monitor Android logs for forgot password testing

echo "📱 Android Log Monitor for Forgot Password Testing"
echo "=================================================="
echo ""
echo "This script will monitor Android logs for forgot password related messages."
echo "Make sure your Android device is connected and USB debugging is enabled."
echo ""
echo "Press Ctrl+C to stop monitoring"
echo ""

# Check if adb is available
if ! command -v adb &> /dev/null; then
    echo "❌ ADB not found. Please install Android SDK or add adb to PATH"
    echo ""
    echo "Alternative: You can monitor logs manually in Android Studio:"
    echo "1. Open Android Studio"
    echo "2. Go to View > Tool Windows > Logcat"
    echo "3. Filter by 'LoginActivity' or 'CommunityUserManager'"
    echo "4. Test forgot password in your app"
    exit 1
fi

# Check if device is connected
if ! adb devices | grep -q "device$"; then
    echo "❌ No Android device connected"
    echo "Please connect your device and enable USB debugging"
    exit 1
fi

echo "✅ Android device connected"
echo "🔍 Monitoring logs for forgot password activity..."
echo ""

# Monitor logs with relevant filters
adb logcat -c  # Clear existing logs
adb logcat | grep -E "(LoginActivity|CommunityUserManager|DatabaseAPI|forgot|password|reset|error|Error|ERROR)" --line-buffered
