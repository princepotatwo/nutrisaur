# FavoritesActivity.java Integration Guide

## Overview
This guide shows how to integrate CNN-based malnutrition detection into your existing `FavoritesActivity.java`. The integration uses the Roboflow Deteksi Stunting dataset for training and provides visual screening that complements your WHO growth standards assessment.

## 1. Add Dependencies to build.gradle (Module: app)

```gradle
dependencies {
    // Existing dependencies...
    
    // PyTorch Mobile for CNN inference
    implementation 'org.pytorch:pytorch_android:1.12.2'
    implementation 'org.pytorch:pytorch_android_torchvision:1.12.2'
    
    // Additional ML dependencies
    implementation 'org.tensorflow:tensorflow-lite:2.10.0'
}
```

## 2. Update AndroidManifest.xml

```xml
<!-- Add camera permission -->
<uses-permission android:name="android.permission.CAMERA" />
<uses-permission android:name="android.permission.READ_EXTERNAL_STORAGE" />

<!-- Add camera feature -->
<uses-feature android:name="android.hardware.camera" android:required="false" />
<uses-feature android:name="android.hardware.camera.autofocus" android:required="false" />
```

## 3. Copy Model File to Assets

1. Train the model using the Roboflow dataset (see ROBOFLOW_DATASET_GUIDE.md)
2. Copy the trained `malnutrition_model_android.pt` file to `app/src/main/assets/`
3. The model will be automatically copied to internal storage on first use

## 4. Update FavoritesActivity.java

### Add Imports
```java
// Add these imports to your existing FavoritesActivity.java
import com.example.nutrisaur11.ml.FavoritesActivityMalnutritionIntegration;
import com.example.nutrisaur11.ml.AndroidMalnutritionDetector;
```

### Add Class Variables
```java
public class FavoritesActivity extends BaseActivity {
    // ... existing variables ...
    
    // Malnutrition detection integration
    private FavoritesActivityMalnutritionIntegration malnutritionIntegration;
    
    // ... rest of existing code ...
}
```

### Update onCreate Method
```java
@Override
protected void onCreate(Bundle savedInstanceState) {
    super.onCreate(savedInstanceState);
    setContentView(R.layout.activity_favorites);
    
    // ... existing initialization code ...
    
    // Initialize malnutrition detection integration
    try {
        malnutritionIntegration = new FavoritesActivityMalnutritionIntegration(this);
        Log.d(TAG, "Malnutrition detection integration initialized");
    } catch (Exception e) {
        Log.e(TAG, "Failed to initialize malnutrition detection: " + e.getMessage());
        // Continue without malnutrition detection
    }
    
    // ... rest of existing onCreate code ...
}
```

### Add Permission Handling
```java
@Override
public void onRequestPermissionsResult(int requestCode, @NonNull String[] permissions, @NonNull int[] grantResults) {
    super.onRequestPermissionsResult(requestCode, permissions, grantResults);
    
    // Delegate to malnutrition integration
    if (malnutritionIntegration != null) {
        malnutritionIntegration.onRequestPermissionsResult(requestCode, permissions, grantResults);
    }
}
```

### Update onActivityResult Method
```java
@Override
protected void onActivityResult(int requestCode, int resultCode, Intent data) {
    super.onActivityResult(requestCode, resultCode, data);
    
    // Delegate to malnutrition integration first
    if (malnutritionIntegration != null) {
        malnutritionIntegration.onActivityResult(requestCode, resultCode, data);
    }
    
    // ... existing onActivityResult code for other activities ...
}
```

### Add Cleanup Method
```java
@Override
protected void onDestroy() {
    super.onDestroy();
    
    // Cleanup malnutrition detection resources
    if (malnutritionIntegration != null) {
        malnutritionIntegration.cleanup();
    }
    
    // ... existing cleanup code ...
}
```

### Update setupButtons Method
```java
private void setupButtons() {
    // Existing malnutrition detection button (camera functionality)
    findViewById(R.id.start_detection_button).setOnClickListener(v -> {
        // Use malnutrition integration instead of FullScreenCameraActivity
        if (malnutritionIntegration != null && malnutritionIntegration.isMalnutritionDetectionAvailable()) {
            // The integration will handle camera/gallery selection and analysis
            malnutritionIntegration.showImageSourceDialog();
        } else {
            // Fallback to full screen camera
            Intent intent = new Intent(FavoritesActivity.this, FullScreenCameraActivity.class);
            startActivity(intent);
            overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
        }
    });
    
    // ... existing navigation code ...
}
```

## 5. Update Layout File (activity_favorites.xml)

Add these UI components to your existing `activity_favorites.xml`:

```xml
<!-- Malnutrition Results Container - Add after your existing content -->
<androidx.cardview.widget.CardView
    android:id="@+id/malnutrition_results_container"
    android:layout_width="match_parent"
    android:layout_height="wrap_content"
    android:layout_margin="16dp"
    android:visibility="gone"
    app:cardCornerRadius="12dp"
    app:cardElevation="6dp"
    app:cardBackgroundColor="@color/card_background">

    <LinearLayout
        android:layout_width="match_parent"
        android:layout_height="wrap_content"
        android:orientation="vertical"
        android:padding="16dp">

        <!-- Header -->
        <LinearLayout
            android:layout_width="match_parent"
            android:layout_height="wrap_content"
            android:orientation="horizontal"
            android:gravity="center_vertical"
            android:layout_marginBottom="12dp">

            <TextView
                android:layout_width="0dp"
                android:layout_height="wrap_content"
                android:layout_weight="1"
                android:text="📊 ASSESSMENT RESULTS"
                android:textSize="16sp"
                android:textStyle="bold"
                android:textColor="@color/primary_text" />

            <ImageButton
                android:id="@+id/close_malnutrition_results"
                android:layout_width="32dp"
                android:layout_height="32dp"
                android:src="@drawable/ic_close"
                android:background="?attr/selectableItemBackgroundBorderless"
                android:contentDescription="Close results" />

        </LinearLayout>

        <!-- Image Preview -->
        <ImageView
            android:id="@+id/malnutrition_preview_image"
            android:layout_width="120dp"
            android:layout_height="120dp"
            android:layout_gravity="center_horizontal"
            android:layout_marginBottom="12dp"
            android:scaleType="centerCrop"
            android:background="@drawable/image_placeholder"
            android:contentDescription="Assessment image" />

        <!-- Status -->
        <TextView
            android:id="@+id/malnutrition_status_text"
            android:layout_width="match_parent"
            android:layout_height="wrap_content"
            android:text="Analyzing..."
            android:textSize="14sp"
            android:textStyle="bold"
            android:textColor="@color/primary_text"
            android:gravity="center"
            android:layout_marginBottom="4dp" />

        <!-- Confidence -->
        <TextView
            android:id="@+id/malnutrition_confidence_text"
            android:layout_width="match_parent"
            android:layout_height="wrap_content"
            android:text="Confidence: --"
            android:textSize="12sp"
            android:textColor="@color/secondary_text"
            android:gravity="center"
            android:layout_marginBottom="12dp" />

        <!-- Progress Bar -->
        <ProgressBar
            android:id="@+id/malnutrition_progress_bar"
            android:layout_width="match_parent"
            android:layout_height="wrap_content"
            android:layout_marginBottom="12dp"
            android:visibility="gone"
            style="?android:attr/progressBarStyleHorizontal" />

        <!-- Recommendations -->
        <TextView
            android:id="@+id/malnutrition_recommendations_text"
            android:layout_width="match_parent"
            android:layout_height="wrap_content"
            android:text=""
            android:textSize="12sp"
            android:textColor="@color/secondary_text"
            android:layout_marginBottom="12dp" />

        <!-- Action Buttons -->
        <LinearLayout
            android:layout_width="match_parent"
            android:layout_height="wrap_content"
            android:orientation="horizontal"
            android:gravity="center">

            <Button
                android:id="@+id/save_malnutrition_results"
                android:layout_width="0dp"
                android:layout_height="40dp"
                android:layout_weight="1"
                android:layout_marginEnd="8dp"
                android:text="💾 Save"
                android:textSize="12sp"
                android:background="@drawable/button_secondary_background" />

            <Button
                android:id="@+id/view_recommendations"
                android:layout_width="0dp"
                android:layout_height="40dp"
                android:layout_weight="1"
                android:layout_marginStart="8dp"
                android:text="📋 Recommendations"
                android:textSize="12sp"
                android:background="@drawable/button_primary_background" />

        </LinearLayout>

    </LinearLayout>

</androidx.cardview.widget.CardView>
```

## 6. Integration with WHO Standards

The malnutrition detection integrates seamlessly with your existing WHO growth standards:

1. **Visual Screening First**: Use CNN for initial assessment
2. **Anthropometric Validation**: Follow up with measurements
3. **Combined Assessment**: Use both for comprehensive evaluation
4. **Risk Stratification**: Prioritize cases requiring immediate attention

## 7. Testing and Validation

### Test Scenarios
1. **Camera Permission**: Test camera permission request flow
2. **Image Capture**: Test both camera and gallery image selection
3. **Model Loading**: Verify model loads correctly on app start
4. **Results Display**: Test results display and recommendations
5. **Data Persistence**: Verify results are saved and retrieved correctly
6. **Error Handling**: Test behavior when model fails to load

### Performance Considerations
- Model loading: ~2-3 seconds on first use
- Inference time: ~500ms-1s per image
- Memory usage: ~50-100MB for model
- Storage: ~25MB for model file

## 8. Privacy and Ethics

### Data Handling
- Images are processed locally on device
- No data sent to external servers
- Temporary files are automatically cleaned up
- Results stored locally with user consent

### Medical Disclaimer
- Add disclaimer that this is a screening tool
- Recommend professional medical assessment for high-risk cases
- Use as supplement to, not replacement for, clinical assessment

## 9. Deployment Checklist

- [ ] Model file in assets folder
- [ ] Dependencies added to build.gradle
- [ ] Permissions in AndroidManifest.xml
- [ ] UI components added to layout
- [ ] Integration code added to FavoritesActivity
- [ ] Error handling implemented
- [ ] Testing completed
- [ ] Privacy compliance verified

## 10. Quick Start Commands

```bash
# 1. Download Roboflow dataset (see ROBOFLOW_DATASET_GUIDE.md)
# 2. Train the model
cd /Users/jasminpingol/Downloads/thesis75/nutrisaur11
bash train_roboflow_model.sh

# 3. Copy model to Android assets
cp runs/malnutrition_cnn_roboflow/malnutrition_model_android.pt app/src/main/assets/

# 4. Build and test your Android app
```

This integration provides a powerful tool for malnutrition screening while maintaining the scientific rigor of your existing WHO growth standards implementation.
