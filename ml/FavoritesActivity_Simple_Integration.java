package com.example.nutrisaur11;

import android.Manifest;
import android.app.AlertDialog;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.graphics.Bitmap;
import android.net.Uri;
import android.os.Bundle;
import android.provider.MediaStore;
import android.util.Log;
import android.widget.TextView;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.app.ActivityCompat;
import androidx.core.content.ContextCompat;
import androidx.core.widget.NestedScrollView;

// Add these imports for malnutrition detection
import com.example.nutrisaur11.ml.SimpleMalnutritionDetector;
import com.example.nutrisaur11.ml.SimpleMalnutritionDetector.MalnutritionAnalysisResult;

import java.io.IOException;

/**
 * Enhanced FavoritesActivity with CNN malnutrition detection
 * Integrates with existing camera functionality
 */
public class FavoritesActivity extends BaseActivity {
    private static final String TAG = "FavoritesActivity";
    private static final int REQUEST_CAMERA_PERMISSION = 1001;
    private static final int REQUEST_CAMERA_CAPTURE = 1002;
    private static final int REQUEST_IMAGE_PICK = 1003;
    
    // Malnutrition detection integration
    private SimpleMalnutritionDetector malnutritionDetector;
    private boolean malnutritionDetectionEnabled = false;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_favorites);
        
        // Disable scrolling on NestedScrollView
        NestedScrollView nestedScrollView = findViewById(R.id.nested_scroll_view);
        if (nestedScrollView != null) {
            nestedScrollView.setNestedScrollingEnabled(false);
        }
        
        // Set header title
        TextView pageTitle = findViewById(R.id.page_title);
        TextView pageSubtitle = findViewById(R.id.page_subtitle);
        if (pageTitle != null) {
            pageTitle.setText("DETECT SIGNS OF MALNUTRITION");
        }
        if (pageSubtitle != null) {
            pageSubtitle.setText("Take photo to analyze nutrition with AI");
        }
        
        // Initialize malnutrition detection
        initializeMalnutritionDetection();
        
        setupButtons();
        
        // Call this after session validation
        onSessionValidated();
    }
    
    @Override
    protected void initializeActivity() {
        // Additional initialization after session validation
        // This method is called automatically by BaseActivity
    }
    
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
    
    private void setupFavoritesRecyclerView() {
        // RecyclerView functionality removed - now using malnutrition detection UI
        android.util.Log.d("FavoritesActivity", "Malnutrition detection UI active");
    }
    
    private void loadFavorites() {
        // Favorites functionality removed - now using malnutrition detection UI
        android.util.Log.d("FavoritesActivity", "Malnutrition detection UI active - favorites loading disabled");
    }
    
    private void hideStaticCards() {
        // Static cards functionality removed - now using malnutrition detection UI
        android.util.Log.d("FavoritesActivity", "Malnutrition detection UI active - static cards disabled");
    }
    
    private void updateEmptyState() {
        // Empty state functionality removed - now using malnutrition detection UI
        android.util.Log.d("FavoritesActivity", "Malnutrition detection UI active - empty state disabled");
    }
    
    private String getCurrentUserEmail() {
        android.content.SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
        String email = prefs.getString("current_user_email", "");
        android.util.Log.d("FavoritesActivity", "Retrieved user email: " + email);
        return email;
    }
    
    private void setupButtons() {
        // Enhanced malnutrition detection button with AI analysis
        findViewById(R.id.start_detection_button).setOnClickListener(v -> {
            if (malnutritionDetectionEnabled) {
                showImageSourceDialog();
            } else {
                // Fallback to existing camera functionality
                launchFullScreenCamera();
            }
        });
        
        // Setup bottom navigation bar (unchanged)
        findViewById(R.id.nav_home).setOnClickListener(v -> {
            Intent intent = new Intent(FavoritesActivity.this, MainActivity.class);
            startActivity(intent);
            overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
            finish();
        });
        
        findViewById(R.id.nav_food).setOnClickListener(v -> {
            Intent intent = new Intent(FavoritesActivity.this, FoodActivity.class);
            startActivity(intent);
            overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
            finish();
        });
        
        findViewById(R.id.nav_favorites).setOnClickListener(v -> {
            // Already in FavoritesActivity, do nothing
        });
        
        findViewById(R.id.nav_account).setOnClickListener(v -> {
            Intent intent = new Intent(FavoritesActivity.this, AccountActivity.class);
            startActivity(intent);
            overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
            finish();
        });
    }
    
    /**
     * Show dialog to choose image source (camera or gallery)
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
}
