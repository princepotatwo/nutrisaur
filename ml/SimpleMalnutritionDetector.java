package com.example.nutrisaur11.ml;

import android.content.Context;
import android.graphics.Bitmap;
import android.util.Log;
import android.widget.Toast;

import org.pytorch.IValue;
import org.pytorch.Module;
import org.pytorch.Tensor;
import org.pytorch.torchvision.TensorImageUtils;

import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.util.Arrays;
import java.util.List;

/**
 * Simple Malnutrition Detector for FavoritesActivity
 * Works with existing camera functionality
 */
public class SimpleMalnutritionDetector {
    private static final String TAG = "SimpleMalnutritionDetector";
    
    // Class indices matching Roboflow dataset
    public static final int NORMAL = 0;           // Healthy
    public static final int MODERATE_MALNUTRITION = 1; // MalNutrisi  
    public static final int STUNTING = 2;         // Stunting
    
    private Module model;
    private List<String> classNames;
    private Context context;
    private boolean isInitialized = false;
    
    public SimpleMalnutritionDetector(Context context) {
        this.context = context;
        initializeModel();
    }
    
    /**
     * Initialize the PyTorch model
     */
    private void initializeModel() {
        try {
            // Load model from assets
            model = Module.load(assetFilePath("malnutrition_model_android.pt"));
            
            // Initialize class names (matching Roboflow dataset)
            classNames = Arrays.asList(
                "normal",                    // Healthy
                "moderate_malnutrition",     // MalNutrisi
                "stunting"                   // Stunting
            );
            
            isInitialized = true;
            Log.d(TAG, "Malnutrition detection model loaded successfully");
            
        } catch (Exception e) {
            Log.e(TAG, "Error loading malnutrition detection model: " + e.getMessage());
            isInitialized = false;
        }
    }
    
    /**
     * Analyze image for malnutrition signs
     * @param bitmap Input image
     * @return Analysis result
     */
    public MalnutritionAnalysisResult analyzeImage(Bitmap bitmap) {
        if (!isInitialized || model == null) {
            return new MalnutritionAnalysisResult(false, "Model not available", 0.0f, "normal");
        }
        
        try {
            // Preprocess image
            Bitmap resizedBitmap = Bitmap.createScaledBitmap(bitmap, 224, 224, true);
            Tensor inputTensor = TensorImageUtils.bitmapToFloat32Tensor(
                resizedBitmap,
                TensorImageUtils.TORCHVISION_NORM_MEAN_RGB,
                TensorImageUtils.TORCHVISION_NORM_STD_RGB
            );
            
            // Run inference
            IValue inputs = IValue.from(inputTensor);
            Tensor outputs = model.forward(inputs).toTensor();
            
            // Get predictions
            float[] scores = outputs.getDataAsFloatArray();
            int predictedClass = getPredictedClass(scores);
            float confidence = scores[predictedClass];
            String className = classNames.get(predictedClass);
            
            Log.d(TAG, String.format("Analysis: %s (%.2f%% confidence)", className, confidence * 100));
            
            return new MalnutritionAnalysisResult(true, getDescription(className, confidence), confidence, className);
            
        } catch (Exception e) {
            Log.e(TAG, "Error during analysis: " + e.getMessage());
            return new MalnutritionAnalysisResult(false, "Analysis failed: " + e.getMessage(), 0.0f, "error");
        }
    }
    
    /**
     * Get predicted class from scores
     */
    private int getPredictedClass(float[] scores) {
        int maxIndex = 0;
        for (int i = 1; i < scores.length; i++) {
            if (scores[i] > scores[maxIndex]) {
                maxIndex = i;
            }
        }
        return maxIndex;
    }
    
    /**
     * Get human-readable description
     */
    private String getDescription(String className, float confidence) {
        String baseDescription;
        
        switch (className) {
            case "normal":
                baseDescription = "Normal nutritional status detected";
                break;
            case "moderate_malnutrition":
                baseDescription = "Signs of moderate malnutrition detected";
                break;
            case "stunting":
                baseDescription = "Signs of stunting (chronic malnutrition) detected";
                break;
            default:
                baseDescription = "Unknown classification";
        }
        
        // Add confidence level
        if (confidence >= 0.8) {
            return baseDescription + " (High confidence)";
        } else if (confidence >= 0.6) {
            return baseDescription + " (Medium confidence)";
        } else {
            return baseDescription + " (Low confidence - recommend professional assessment)";
        }
    }
    
    /**
     * Get recommendations based on analysis
     */
    public String getRecommendations(String className, float confidence) {
        if (confidence < 0.6) {
            return "Low confidence result. Please consult with a healthcare professional for accurate assessment.";
        }
        
        switch (className) {
            case "normal":
                return "✅ Continue maintaining healthy nutrition. Regular monitoring recommended.";
            case "moderate_malnutrition":
                return "⚠️ Moderate malnutrition signs detected. Consider:\n• Nutrition counseling\n• Dietary assessment\n• Regular monitoring\n• Professional consultation";
            case "stunting":
                return "📏 Chronic malnutrition signs detected. Consider:\n• Long-term nutrition intervention\n• Growth monitoring\n• Address underlying causes\n• Professional medical consultation";
            default:
                return "Please consult with a healthcare professional for proper assessment.";
        }
    }
    
    /**
     * Check if model is available
     */
    public boolean isAvailable() {
        return isInitialized && model != null;
    }
    
    /**
     * Copy model file from assets to internal storage
     */
    private String assetFilePath(String filename) {
        File file = new File(context.getFilesDir(), filename);
        if (file.exists() && file.length() > 0) {
            return file.getAbsolutePath();
        }
        
        try (InputStream is = context.getAssets().open(filename)) {
            try (FileOutputStream fos = new FileOutputStream(file)) {
                byte[] buffer = new byte[is.available()];
                is.read(buffer);
                fos.write(buffer);
            }
        } catch (IOException e) {
            Log.e(TAG, "Error copying model file: " + e.getMessage());
            throw new RuntimeException("Failed to copy model file", e);
        }
        
        return file.getAbsolutePath();
    }
    
    /**
     * Clean up resources
     */
    public void cleanup() {
        if (model != null) {
            model.destroy();
            model = null;
        }
        isInitialized = false;
    }
    
    /**
     * Result class for malnutrition analysis
     */
    public static class MalnutritionAnalysisResult {
        public final boolean success;
        public final String description;
        public final float confidence;
        public final String className;
        
        public MalnutritionAnalysisResult(boolean success, String description, float confidence, String className) {
            this.success = success;
            this.description = description;
            this.confidence = confidence;
            this.className = className;
        }
        
        public boolean isHighConfidence() {
            return confidence >= 0.8;
        }
        
        public boolean isMediumConfidence() {
            return confidence >= 0.6 && confidence < 0.8;
        }
        
        public boolean isLowConfidence() {
            return confidence < 0.6;
        }
        
        public boolean requiresAttention() {
            return !className.equals("normal") && confidence >= 0.6;
        }
        
        @Override
        public String toString() {
            return String.format("MalnutritionAnalysisResult{success=%s, description='%s', confidence=%.2f, className='%s'}", 
                success, description, confidence, className);
        }
    }
}
