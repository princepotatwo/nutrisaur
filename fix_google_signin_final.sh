#!/bin/bash
# Google Sign-In Error Code 10 - Final Fix Script

echo "🔧 Google Sign-In Error Code 10 - Final Fix"
echo "=============================================="

# Step 1: Clean and rebuild the app
echo "📱 Step 1: Cleaning and rebuilding app..."
cd /Users/jasminpingol/Downloads/thesis75/nutrisaur11

echo "Cleaning project..."
./gradlew clean

echo "Removing old APK..."
rm -f app/build/outputs/apk/debug/app-debug.apk

echo "Building new APK..."
./gradlew assembleDebug

if [ $? -eq 0 ]; then
    echo "✅ App built successfully"
else
    echo "❌ Build failed"
    exit 1
fi

# Step 2: Uninstall and reinstall
echo ""
echo "📱 Step 2: Reinstalling app..."

echo "Uninstalling old version..."
adb uninstall com.example.nutrisaur11

echo "Installing new version..."
adb install app/build/outputs/apk/debug/app-debug.apk

if [ $? -eq 0 ]; then
    echo "✅ App installed successfully"
else
    echo "❌ Installation failed"
    exit 1
fi

# Step 3: Clear Google Play Services cache
echo ""
echo "📱 Step 3: Clearing Google Play Services cache..."

echo "Clearing Google Play Services data..."
adb shell pm clear com.google.android.gms

echo "Clearing Google Play Store data..."
adb shell pm clear com.android.vending

echo "Clearing app data..."
adb shell pm clear com.example.nutrisaur11

echo "✅ Caches cleared"

# Step 4: Restart device
echo ""
echo "📱 Step 4: Restarting device..."
echo "⚠️  IMPORTANT: Please restart your Android device now!"
echo "   This will clear all Google Play Services caches"
echo "   Wait for the device to fully boot before testing"

echo ""
echo "🎯 Next Steps:"
echo "1. Restart your Android device"
echo "2. Open the Nutrisaur app"
echo "3. Try Google Sign-In"
echo "4. Check if error code 10 is resolved"

echo ""
echo "📊 If still not working, try these additional steps:"
echo "- Go to Settings > Apps > Google Play Services > Clear Cache & Data"
echo "- Go to Settings > Accounts > Google > Remove and re-add your account"
echo "- Test with a different Google account"
echo "- Try on a different Android device"

echo ""
echo "✅ Fix script completed!"
