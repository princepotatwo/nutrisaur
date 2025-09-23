# Simple CNN Integration for FavoritesActivity.java

## Overview
This guide shows how to add CNN malnutrition detection to your existing `FavoritesActivity.java` with minimal changes. Your existing camera functionality will be enhanced with AI analysis.

## 🎯 What You'll Get

- **Keep existing camera**: Your `FullScreenCameraActivity` remains unchanged
- **Add AI analysis**: New camera/gallery options with CNN analysis
- **Three options**: Take Photo (AI), Choose from Gallery (AI), Full Screen Camera (original)
- **Minimal changes**: Only add imports and modify `setupButtons()` method

## 📋 Step-by-Step Integration

### Step 1: Add Dependencies to build.gradle (Module: app)

```gradle
dependencies {
    // Existing dependencies...
    
    // PyTorch Mobile for CNN inference
    implementation 'org.pytorch:pytorch_android:1.12.2'
    implementation 'org.pytorch:pytorch_android_torchvision:1.12.2'
}
```

### Step 2: Update AndroidManifest.xml

```xml
<!-- Add camera permission -->
<uses-permission android:name="android.permission.CAMERA" />
<uses-permission android:name="android.permission.READ_EXTERNAL_STORAGE" />

<!-- Add camera feature -->
<uses-feature android:name="android.hardware.camera" android:required="false" />
```

### Step 3: Copy Model File to Assets

1. Train the model using Roboflow dataset (see ROBOFLOW_DATASET_GUIDE.md)
2. Copy `malnutrition_model_android.pt` to `app/src/main/assets/`

### Step 4: Modify Your FavoritesActivity.java

#### Add Imports (at the top)
```java
// Add these imports to your existing FavoritesActivity.java
import android.Manifest;
import android.app.AlertDialog;
import android.content.pm.PackageManager;
import android.graphics.Bitmap;
import android.net.Uri;
import android.provider.MediaStore;
import android.widget.Toast;
import androidx.core.app.ActivityCompat;
import androidx.core.content.ContextCompat;

// Malnutrition detection imports
import com.example.nutrisaur11.ml.SimpleMalnutritionDetector;
import com.example.nutrisaur11.ml.SimpleMalnutritionDetector.MalnutritionAnalysisResult;

import java.io.IOException;
```

#### Add Class Variables (after line 10)
```java
public class FavoritesActivity extends BaseActivity {
    private static final String TAG = "FavoritesActivity";
    private static final int REQUEST_CAMERA_PERMISSION = 1001;
    private static final int REQUEST_CAMERA_CAPTURE = 1002;
    private static final int REQUEST_IMAGE_PICK = 1003;
    
    // Malnutrition detection integration
    private SimpleMalnutritionDetector malnutritionDetector;
    private boolean malnutritionDetectionEnabled = false;
    
    // ... rest of existing code ...
}
```

#### Modify onCreate Method (add after line 32)
```java
@Override
protected void onCreate(Bundle savedInstanceState) {
    super.onCreate(savedInstanceState);
    setContentView(R.layout.activity_favorites);
    
    // ... existing code ...
    
    // Initialize malnutrition detection
    initializeMalnutritionDetection();
    
    setupButtons();
    
    // ... rest of existing code ...
}
```

#### Add New Methods (add these new methods)
```java
/**
 * Initialize malnutrition detection
 */
private void initializeMalnutritionDetection() {
    try {
        malnutritionDetector = new SimpleMalnutritionDetector(this);
        malnutritionDetectionEnabled = malnutritionDetector.isAvailable();
        
        if (malnutritionDetectionEnabled) {
            Log.d(TAG, "Malnutrition detection initialized successfully");
            Toast.makeText(this, "AI malnutrition detection ready", Toast.LENGTH_SHORT).show();
        } else {
            Log.w(TAG, "Malnutrition detection not available");
            Toast.makeText(this, "Using camera without AI analysis", Toast.LENGTH_SHORT).show();
        }
    } catch (Exception e) {
        Log.e(TAG, "Failed to initialize malnutrition detection: " + e.getMessage());
        malnutritionDetectionEnabled = false;
    }
}

/**
 * Show dialog to choose image source
 */
private void showImageSourceDialog() {
    AlertDialog.Builder builder = new AlertDialog.Builder(this);
    builder.setTitle("🔍 AI Malnutrition Analysis")
           .setMessage("Choose how to capture the image for AI analysis:")
           .setPositiveButton("📷 Take Photo", (dialog, which) -> requestCameraPermission())
           .setNegativeButton("🖼️ Choose from Gallery", (dialog, which) -> openImagePicker())
           .setNeutralButton("📱 Use Full Screen Camera", (dialog, which) -> launchFullScreenCamera())
           .show();
}

/**
 * Request camera permission
 */
private void requestCameraPermission() {
    if (ContextCompat.checkSelfPermission(this, Manifest.permission.CAMERA) 
            != PackageManager.PERMISSION_GRANTED) {
        ActivityCompat.requestPermissions(this, 
            new String[]{Manifest.permission.CAMERA}, REQUEST_CAMERA_PERMISSION);
    } else {
        openCamera();
    }
}

/**
 * Handle permission request result
 */
@Override
public void onRequestPermissionsResult(int requestCode, String[] permissions, int[] grantResults) {
    super.onRequestPermissionsResult(requestCode, permissions, grantResults);
    
    if (requestCode == REQUEST_CAMERA_PERMISSION) {
        if (grantResults.length > 0 && grantResults[0] == PackageManager.PERMISSION_GRANTED) {
            openCamera();
        } else {
            Toast.makeText(this, "Camera permission required for malnutrition analysis", Toast.LENGTH_LONG).show();
        }
    }
}

/**
 * Open camera to capture image
 */
private void openCamera() {
    Intent cameraIntent = new Intent(MediaStore.ACTION_IMAGE_CAPTURE);
    if (cameraIntent.resolveActivity(getPackageManager()) != null) {
        startActivityForResult(cameraIntent, REQUEST_CAMERA_CAPTURE);
    } else {
        Toast.makeText(this, "Camera not available", Toast.LENGTH_SHORT).show();
    }
}

/**
 * Open image picker to select from gallery
 */
private void openImagePicker() {
    Intent intent = new Intent(Intent.ACTION_PICK, MediaStore.Images.Media.EXTERNAL_CONTENT_URI);
    intent.setType("image/*");
    startActivityForResult(intent, REQUEST_IMAGE_PICK);
}

/**
 * Launch full screen camera (existing functionality)
 */
private void launchFullScreenCamera() {
    Intent intent = new Intent(FavoritesActivity.this, FullScreenCameraActivity.class);
    startActivity(intent);
    overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
}

/**
 * Handle activity result from camera or image picker
 */
@Override
protected void onActivityResult(int requestCode, int resultCode, Intent data) {
    super.onActivityResult(requestCode, resultCode, data);
    
    if (resultCode != RESULT_OK) return;
    
    switch (requestCode) {
        case REQUEST_CAMERA_CAPTURE:
            if (data != null && data.getExtras() != null) {
                Bitmap imageBitmap = (Bitmap) data.getExtras().get("data");
                if (imageBitmap != null && malnutritionDetectionEnabled) {
                    analyzeImage(imageBitmap);
                }
            }
            break;
            
        case REQUEST_IMAGE_PICK:
            if (data != null && data.getData() != null) {
                Uri imageUri = data.getData();
                try {
                    Bitmap imageBitmap = MediaStore.Images.Media.getBitmap(
                        getContentResolver(), imageUri);
                    if (imageBitmap != null && malnutritionDetectionEnabled) {
                        analyzeImage(imageBitmap);
                    }
                } catch (IOException e) {
                    Log.e(TAG, "Error loading image from gallery: " + e.getMessage());
                    Toast.makeText(this, "Error loading image", Toast.LENGTH_SHORT).show();
                }
            }
            break;
    }
}

/**
 * Analyze image for malnutrition signs
 */
private void analyzeImage(Bitmap bitmap) {
    if (malnutritionDetector == null || !malnutritionDetectionEnabled) {
        Toast.makeText(this, "AI analysis not available", Toast.LENGTH_SHORT).show();
        return;
    }
    
    // Show loading toast
    Toast.makeText(this, "🔍 Analyzing image...", Toast.LENGTH_SHORT).show();
    
    // Run analysis in background thread
    new Thread(() -> {
        try {
            MalnutritionAnalysisResult result = malnutritionDetector.analyzeImage(bitmap);
            
            // Update UI on main thread
            runOnUiThread(() -> {
                showAnalysisResults(result);
            });
            
        } catch (Exception e) {
            Log.e(TAG, "Error during analysis: " + e.getMessage());
            runOnUiThread(() -> {
                Toast.makeText(this, "Analysis failed: " + e.getMessage(), Toast.LENGTH_LONG).show();
            });
        }
    }).start();
}

/**
 * Show analysis results
 */
private void showAnalysisResults(MalnutritionAnalysisResult result) {
    if (!result.success) {
        Toast.makeText(this, result.description, Toast.LENGTH_LONG).show();
        return;
    }
    
    // Show results dialog
    AlertDialog.Builder builder = new AlertDialog.Builder(this);
    builder.setTitle("📊 AI Analysis Results")
           .setMessage(String.format(
               "Analysis: %s\n\nConfidence: %.1f%%\n\n%s",
               result.description,
               result.confidence * 100,
               malnutritionDetector.getRecommendations(result.className, result.confidence)
           ))
           .setPositiveButton("💾 Save Results", (dialog, which) -> saveAnalysisResults(result))
           .setNegativeButton("📋 View Details", (dialog, which) -> showDetailedResults(result))
           .setNeutralButton("OK", null)
           .show();
    
    // Show immediate toast for critical cases
    if (result.requiresAttention()) {
        Toast.makeText(this, "⚠️ " + result.description, Toast.LENGTH_LONG).show();
    }
}

/**
 * Show detailed results
 */
private void showDetailedResults(MalnutritionAnalysisResult result) {
    AlertDialog.Builder builder = new AlertDialog.Builder(this);
    builder.setTitle("📋 Detailed Analysis")
           .setMessage(String.format(
               "Class: %s\nConfidence: %.1f%%\n\nDescription: %s\n\nRecommendations:\n%s",
               result.className,
               result.confidence * 100,
               result.description,
               malnutritionDetector.getRecommendations(result.className, result.confidence)
           ))
           .setPositiveButton("OK", null)
           .show();
}

/**
 * Save analysis results
 */
private void saveAnalysisResults(MalnutritionAnalysisResult result) {
    android.content.SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
    prefs.edit()
         .putString("malnutrition_class", result.className)
         .putFloat("malnutrition_confidence", result.confidence)
         .putString("malnutrition_description", result.description)
         .putLong("malnutrition_timestamp", System.currentTimeMillis())
         .apply();
    
    Toast.makeText(this, "✅ Analysis results saved", Toast.LENGTH_SHORT).show();
    Log.d(TAG, "Analysis results saved: " + result.className);
}

/**
 * Clean up resources
 */
@Override
protected void onDestroy() {
    super.onDestroy();
    
    if (malnutritionDetector != null) {
        malnutritionDetector.cleanup();
    }
}
```

#### Modify setupButtons Method (replace the existing one)
```java
private void setupButtons() {
    // Enhanced malnutrition detection button with AI analysis
    findViewById(R.id.start_detection_button).setOnClickListener(v -> {
        if (malnutritionDetectionEnabled) {
            showImageSourceDialog(); // Shows 3 options: Take Photo (AI), Gallery (AI), Full Screen Camera
        } else {
            // Fallback to existing camera functionality
            launchFullScreenCamera();
        }
    });
    
    // ... rest of existing navigation code unchanged ...
}
```

## 🎉 What You Get

### User Experience:
1. **Tap "Start Detection"** → Shows dialog with 3 options:
   - 📷 **Take Photo** (with AI analysis)
   - 🖼️ **Choose from Gallery** (with AI analysis)  
   - 📱 **Use Full Screen Camera** (your existing functionality)

2. **AI Analysis Results**:
   - Normal: "Normal nutritional status detected"
   - Moderate Malnutrition: "Signs of moderate malnutrition detected"
   - Stunting: "Signs of stunting (chronic malnutrition) detected"

3. **Recommendations**: Tailored advice based on WHO standards

### Technical Benefits:
- ✅ **Keep existing code**: Your `FullScreenCameraActivity` unchanged
- ✅ **Minimal integration**: Only modify `FavoritesActivity.java`
- ✅ **Graceful fallback**: Works even if AI model fails to load
- ✅ **Background processing**: AI analysis doesn't block UI
- ✅ **Results saved**: Analysis stored in SharedPreferences

## 🚀 Quick Test

1. **Build and run** your app
2. **Go to Favorites tab** (malnutrition detection)
3. **Tap "Start Detection"**
4. **Choose "Take Photo"** or "Choose from Gallery"
5. **See AI analysis results**

## 📱 Expected Results

- **Normal**: "Normal nutritional status detected (High confidence)"
- **Malnutrition**: "Signs of moderate malnutrition detected (Medium confidence)"
- **Stunting**: "Signs of stunting detected (High confidence)"

This integration gives you the best of both worlds: your existing camera functionality plus new AI-powered malnutrition detection!
