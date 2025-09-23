package com.example.nutrisaur11;

import android.app.Activity;
import android.content.Intent;
import android.graphics.Bitmap;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.widget.ImageView;
import android.widget.ProgressBar;
import android.widget.TextView;
import androidx.appcompat.app.AppCompatActivity;
import com.google.android.material.button.MaterialButton;
import com.example.nutrisaur11.ml.TensorFlowLiteMalnutritionDetector.MalnutritionAnalysisResult;

public class AnalysisResultsActivity extends AppCompatActivity {
    private static final String TAG = "AnalysisResults";
    
    // Intent extras keys
    public static final String EXTRA_ANALYSIS_RESULT = "analysis_result";
    public static final String EXTRA_CAPTURED_IMAGE = "captured_image";
    public static final String EXTRA_CLASSIFICATION = "classification";
    public static final String EXTRA_CONFIDENCE = "confidence";
    public static final String EXTRA_RECOMMENDATION = "recommendation";
    
    // UI Components
    private ImageView analysisImage;
    private TextView analysisStatus;
    private TextView classificationResult;
    private ProgressBar confidenceProgress;
    private TextView confidencePercentage;
    private TextView recommendationText;
    private MaterialButton scanAgainButton;
    private MaterialButton backToFavoritesButton;
    
    // Data
    private MalnutritionAnalysisResult analysisResult;
    private Bitmap capturedImage;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_analysis_results);
        
        initializeViews();
        setupClickListeners();
        loadAnalysisData();
        displayResults();
    }
    
    private void initializeViews() {
        analysisImage = findViewById(R.id.analysis_image);
        analysisStatus = findViewById(R.id.analysis_status);
        classificationResult = findViewById(R.id.classification_result);
        confidenceProgress = findViewById(R.id.confidence_progress);
        confidencePercentage = findViewById(R.id.confidence_percentage);
        recommendationText = findViewById(R.id.recommendation_text);
        scanAgainButton = findViewById(R.id.scan_again_button);
        backToFavoritesButton = findViewById(R.id.back_to_favorites_button);
    }
    
    private void setupClickListeners() {
        findViewById(R.id.back_button).setOnClickListener(v -> finish());
        
        scanAgainButton.setOnClickListener(v -> {
            // Return to full screen camera for another scan
            Intent intent = new Intent(this, FullScreenCameraActivity.class);
            intent.addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP);
            startActivity(intent);
            finish();
        });
        
        backToFavoritesButton.setOnClickListener(v -> {
            // Return to FavoritesActivity
            Intent intent = new Intent(this, FavoritesActivity.class);
            intent.addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP);
            startActivity(intent);
            finish();
        });
    }
    
    private void loadAnalysisData() {
        Intent intent = getIntent();
        if (intent != null) {
            // Load analysis result data
            String classification = intent.getStringExtra(EXTRA_CLASSIFICATION);
            float confidence = intent.getFloatExtra(EXTRA_CONFIDENCE, 0.0f);
            String recommendation = intent.getStringExtra(EXTRA_RECOMMENDATION);
            
            // Create analysis result object with TensorFlow Lite format
            analysisResult = new MalnutritionAnalysisResult(true, classification, confidence, classification);
            
            // Load captured image if available
            capturedImage = intent.getParcelableExtra(EXTRA_CAPTURED_IMAGE);
            
            Log.d(TAG, "Loaded analysis data - Classification: " + classification + 
                      ", Confidence: " + confidence + "%, Recommendation: " + recommendation);
        } else {
            Log.e(TAG, "No intent data received");
            finish();
        }
    }
    
    private void displayResults() {
        if (analysisResult == null) {
            Log.e(TAG, "No analysis result to display");
            return;
        }
        
        // Display captured image
        if (capturedImage != null) {
            analysisImage.setImageBitmap(capturedImage);
        } else {
            // Set placeholder if no image
            analysisImage.setImageResource(R.drawable.ic_food_placeholder);
        }
        
        // Display classification result
        String className = analysisResult.className;
        String description = analysisResult.description;
        classificationResult.setText(description);
        
        // Set appropriate color based on classification
        int textColor = getClassificationColor(className);
        classificationResult.setTextColor(textColor);
        
        // Display confidence score
        float confidence = analysisResult.confidence;
        int confidencePercent = Math.round(confidence * 100);
        confidenceProgress.setProgress(confidencePercent);
        confidencePercentage.setText(confidencePercent + "%");
        
        // Set confidence progress bar color based on confidence level
        int progressColor = getConfidenceColor(confidence);
        confidenceProgress.setProgressTintList(android.content.res.ColorStateList.valueOf(progressColor));
        
        // Display recommendation based on classification
        String recommendation = getRecommendation(className, confidence);
        recommendationText.setText(recommendation);
        
        // Update analysis status
        analysisStatus.setText("Analysis Complete");
        
        Log.d(TAG, "Results displayed successfully");
    }
    
    private int getRiskLevelColor(String riskLevel) {
        if (riskLevel == null) return getResources().getColor(R.color.text_primary);
        
        String lowerRiskLevel = riskLevel.toLowerCase();
        if (lowerRiskLevel.contains("normal")) {
            return getResources().getColor(R.color.success_green);
        } else if (lowerRiskLevel.contains("low")) {
            return getResources().getColor(R.color.warning_orange);
        } else if (lowerRiskLevel.contains("moderate")) {
            return getResources().getColor(R.color.warning_orange);
        } else if (lowerRiskLevel.contains("high")) {
            return getResources().getColor(R.color.error_red);
        }
        
        return getResources().getColor(R.color.text_primary);
    }
    
    private static int getConfidencePercent(String confidenceText) {
        if (confidenceText == null) return 50;
        
        String lowerConfidence = confidenceText.toLowerCase();
        if (lowerConfidence.contains("high")) {
            return 85;
        } else if (lowerConfidence.contains("medium")) {
            return 65;
        } else if (lowerConfidence.contains("low")) {
            return 35;
        }
        
        return 50; // Default
    }
    
    private int getClassificationColor(String classification) {
        if (classification == null) return getResources().getColor(R.color.text_primary);
        
        String lowerClassification = classification.toLowerCase();
        if (lowerClassification.contains("normal")) {
            return getResources().getColor(R.color.success_green);
        } else if (lowerClassification.contains("stunting") || lowerClassification.contains("chronic")) {
            return getResources().getColor(R.color.warning_orange);
        } else if (lowerClassification.contains("severe") || lowerClassification.contains("acute")) {
            return getResources().getColor(R.color.error_red);
        } else {
            return getResources().getColor(R.color.primary_blue);
        }
    }
    
    private int getConfidenceColor(float confidence) {
        if (confidence >= 0.9f) {
            return getResources().getColor(R.color.success_green);
        } else if (confidence >= 0.7f) {
            return getResources().getColor(R.color.warning_orange);
        } else {
            return getResources().getColor(R.color.error_red);
        }
    }
    
    /**
     * Static method to create intent for starting this activity
     */
    public static Intent createIntent(Activity context, MalnutritionAnalysisResult result, Bitmap image) {
        Intent intent = new Intent(context, AnalysisResultsActivity.class);
        intent.putExtra(EXTRA_CLASSIFICATION, result.className);
        intent.putExtra(EXTRA_CONFIDENCE, result.confidence);
        intent.putExtra(EXTRA_RECOMMENDATION, result.description);
        if (image != null) {
            intent.putExtra(EXTRA_CAPTURED_IMAGE, image);
        }
        return intent;
    }
    
    private static String getRecommendation(String classification, float confidence) {
        if (classification.toLowerCase().contains("normal")) {
            return "✅ Continue maintaining healthy nutrition. Regular monitoring recommended.";
        } else if (classification.toLowerCase().contains("stunting") || classification.toLowerCase().contains("chronic")) {
            return "📏 Chronic malnutrition signs detected. Consider:\n• Long-term nutrition intervention\n• Growth monitoring\n• Address underlying causes\n• Professional medical consultation";
        } else if (classification.toLowerCase().contains("moderate") || classification.toLowerCase().contains("acute")) {
            return "⚠️ Moderate acute malnutrition detected. Consider:\n• Nutritional intervention\n• Dietary assessment\n• Growth monitoring\n• Professional consultation";
        } else {
            return "📋 Please consult with a healthcare professional for detailed assessment.";
        }
    }
    
    @Override
    public void onBackPressed() {
        super.onBackPressed();
        // Default back behavior - can be customized if needed
    }
}
