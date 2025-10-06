#!/bin/bash
# Enhanced Android Log Monitor for Forgot Password Debugging

echo "🔍 Enhanced Forgot Password Log Monitor"
echo "======================================"
echo ""
echo "This script will monitor Android logs specifically for forgot password debugging."
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
    echo "3. Filter by 'LoginActivity' and look for 'FORGOT PASSWORD DEBUG'"
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
echo "📱 Now test the forgot password functionality in your app:"
echo "   1. Go to login screen"
echo "   2. Tap 'Forgot Password'"
echo "   3. Enter email: kevinpingol123@gmail.com"
echo "   4. Watch the logs below for detailed debugging info"
echo ""

# Clear existing logs
adb logcat -c

# Monitor logs with specific filters for forgot password debugging
adb logcat | grep -E "(LoginActivity.*FORGOT|LoginActivity.*VERIFY|LoginActivity.*UPDATE|LoginActivity.*Error|LoginActivity.*Exception|LoginActivity.*API|LoginActivity.*Response|LoginActivity.*HTTP)" --line-buffered | while read line; do
    # Color code the output
    if echo "$line" | grep -q "ERROR\|Exception\|Error"; then
        echo -e "\033[31m$line\033[0m"  # Red for errors
    elif echo "$line" | grep -q "WARN\|Warning"; then
        echo -e "\033[33m$line\033[0m"  # Yellow for warnings
    elif echo "$line" | grep -q "DEBUG.*START\|DEBUG.*END"; then
        echo -e "\033[36m$line\033[0m"  # Cyan for debug markers
    elif echo "$line" | grep -q "Success\|success"; then
        echo -e "\033[32m$line\033[0m"  # Green for success
    else
        echo -e "\033[37m$line\033[0m"  # White for normal logs
    fi
done
