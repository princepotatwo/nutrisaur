package com.example.nutrisaur11.ml;

import android.content.Context;
import android.graphics.Bitmap;
import android.util.Log;
import android.widget.Toast;

import com.example.nutrisaur11.ml.TensorFlowLiteMalnutritionDetector;

import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.util.Arrays;
import java.util.List;

/**
 * Simple 3-Class Malnutrition Detector for FavoritesActivity
 * Works with: normal, moderate_acute_malnutrition, stunting
 * Trained on real Roboflow dataset with 93.71% accuracy
 */
public class SimpleMalnutritionDetector {
    private static final String TAG = "SimpleMalnutritionDetector";
    
    // Class indices matching our 3-class model
    public static final int MODERATE_ACUTE_MALNUTRITION = 0; // MalNutrisi
    public static final int NORMAL = 1;                       // Healthy
    public static final int STUNTING = 2;                     // Stunting
    
    private TensorFlowLiteMalnutritionDetector tfliteDetector;
    private List<String> classNames;
    private Context context;
    private boolean isInitialized = false;
    
    public SimpleMalnutritionDetector(Context context) {
        this.context = context;
        initializeModel();
    }
    
    /**
     * Initialize the TensorFlow Lite model
     */
    private void initializeModel() {
        Log.d(TAG, "🔧 Starting TensorFlow Lite model initialization...");
        Log.d(TAG, "📱 Context: " + (context != null ? "Available" : "NULL"));
        
        try {
            // Initialize TensorFlow Lite detector
            Log.d(TAG, "🤖 Creating TensorFlow Lite detector...");
            tfliteDetector = new TensorFlowLiteMalnutritionDetector(context);
            
            // Check if initialization was successful
            if (tfliteDetector.isAvailable()) {
                isInitialized = true;
                Log.d(TAG, "🎯 TensorFlow Lite model initialization COMPLETE - CNN ready for analysis");
            } else {
                throw new RuntimeException("TensorFlow Lite detector initialization failed");
            }
            
        } catch (Exception e) {
            Log.e(TAG, "❌ TensorFlow Lite model initialization FAILED");
            Log.e(TAG, "🔍 Error type: " + e.getClass().getSimpleName());
            Log.e(TAG, "🔍 Error message: " + e.getMessage());
            Log.e(TAG, "🔍 Stack trace:");
            for (StackTraceElement element : e.getStackTrace()) {
                Log.e(TAG, "   " + element.toString());
            }
            
            // Set initialized to false - CNN model is required
            isInitialized = false;
            tfliteDetector = null;
            
            Log.e(TAG, "🚫 TensorFlow Lite model is required for analysis - no fallback available");
        }
    }
    
    /**
     * Analyze image for malnutrition signs using CNN
     * @param bitmap Input image
     * @return Analysis result
     */
    public MalnutritionAnalysisResult analyzeImage(Bitmap bitmap) {
        Log.d(TAG, "🔍 Starting TensorFlow Lite CNN analysis...");
        Log.d(TAG, "📊 Model initialized: " + isInitialized);
        Log.d(TAG, "📊 TFLite detector: " + (tfliteDetector != null ? "Available" : "NULL"));
        Log.d(TAG, "📊 Input bitmap: " + (bitmap != null ? bitmap.getWidth() + "x" + bitmap.getHeight() : "NULL"));
        
        if (!isInitialized || tfliteDetector == null) {
            Log.e(TAG, "❌ TensorFlow Lite model not available - cannot perform analysis");
            return new MalnutritionAnalysisResult(false, "TensorFlow Lite model not available. Please ensure the model is properly installed.", 0.0f, "error");
        }
        
        // Delegate to TensorFlow Lite detector
        TensorFlowLiteMalnutritionDetector.MalnutritionAnalysisResult tfliteResult = tfliteDetector.analyzeImage(bitmap);
        
        // Convert to our result type
        return new MalnutritionAnalysisResult(
            tfliteResult.success,
            tfliteResult.description,
            tfliteResult.confidence,
            tfliteResult.className
        );
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
            case "moderate_acute_malnutrition":
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
            case "moderate_acute_malnutrition":
                return "⚠️ Moderate malnutrition signs detected. Consider:\n• Nutrition counseling\n• Dietary assessment\n• Regular monitoring\n• Professional consultation";
            case "stunting":
                return "📏 Chronic malnutrition signs detected. Consider:\n• Long-term nutrition intervention\n• Growth monitoring\n• Address underlying causes\n• Professional medical consultation";
            default:
                return "Please consult with a healthcare professional for proper assessment.";
        }
    }
    
    /**
     * Get WHO severity level
     */
    public String getWHOSeverityLevel(String className, float confidence) {
        if (confidence < 0.6) {
            return "UNCERTAIN";
        }
        
        switch (className) {
            case "normal":
                return "NORMAL";
            case "moderate_acute_malnutrition":
                return "MODERATE";
            case "stunting":
                return "CHRONIC";
            default:
                return "UNKNOWN";
        }
    }
    
    /**
     * Check if model is available
     */
    public boolean isAvailable() {
        return isInitialized && tfliteDetector != null;
    }
    
    
    /**
     * Clean up resources
     */
    public void cleanup() {
        if (tfliteDetector != null) {
            tfliteDetector.cleanup();
            tfliteDetector = null;
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
        
        public boolean requiresImmediateAttention() {
            return className.equals("moderate_acute_malnutrition") && confidence >= 0.8;
        }
        
        @Override
        public String toString() {
            return String.format("MalnutritionAnalysisResult{success=%s, description='%s', confidence=%.2f, className='%s'}", 
                success, description, confidence, className);
        }
    }
}
