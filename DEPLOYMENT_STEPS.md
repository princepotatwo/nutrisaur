# 🚀 Railway Deployment Steps for Food Image Scraper

## Step 1: Add PHP API File to Your Railway Project

You need to add the `food_image_scraper.php` file to your Railway deployment. Here's how:

### Option A: Add to Existing Railway Project
1. Go to your Railway dashboard
2. Navigate to your `nutrisaur-production` project
3. Add the file `public/api/food_image_scraper.php` to your project
4. Make sure Python 3.6+ is available on your Railway deployment

### Option B: Use Python API Server (Temporary Solution)
Since you're having issues with PHP, you can use the Python API server for now:

1. **Start the Python server on your computer:**
   ```bash
   python3 python_api_server.py
   ```

2. **Update FoodImageService.java to use localhost:**
   ```java
   private static final String API_BASE_URL = "http://10.0.2.2:8000/"; // For Android emulator
   // OR
   private static final String API_BASE_URL = "http://YOUR_COMPUTER_IP:8000/"; // For physical device
   ```

## Step 2: Test the API

### Test Railway Deployment:
```bash
curl "https://nutrisaur-production.up.railway.app/public/api/food_image_scraper.php?query=sinigang%20na%20baboy&max_results=3"
```

### Test Local Python Server:
```bash
curl "http://localhost:8000/public/api/food_image_scraper.php?query=sinigang%20na%20baboy&max_results=3"
```

## Step 3: Update Android App

The `FoodImageService.java` is already updated to use your Railway URL. If you want to use the local Python server instead, change the URL to:

```java
private static final String API_BASE_URL = "http://10.0.2.2:8000/"; // For emulator
// OR
private static final String API_BASE_URL = "http://YOUR_COMPUTER_IP:8000/"; // For device
```

## Step 4: Build and Test

1. Build the Android app:
   ```bash
   ./gradlew assembleDebug
   ```

2. Install on device/emulator:
   ```bash
   adb install app/build/outputs/apk/debug/app-debug.apk
   ```

3. Monitor logs:
   ```bash
   adb logcat | grep FoodImageService
   ```

## Current Status

✅ **Python Scraper**: Working
✅ **Python API Server**: Working (tested)
✅ **Android Integration**: Updated to use Railway URL
❌ **PHP API on Railway**: Needs to be deployed

## Quick Fix for Testing

If you want to test immediately without deploying to Railway:

1. **Start Python server:**
   ```bash
   python3 python_api_server.py
   ```

2. **Update FoodImageService.java:**
   ```java
   private static final String API_BASE_URL = "http://10.0.2.2:8000/";
   ```

3. **Rebuild and test the app**

This will work for testing purposes while you set up the Railway deployment.
