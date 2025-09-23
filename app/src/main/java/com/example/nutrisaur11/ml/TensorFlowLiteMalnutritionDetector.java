package com.example.nutrisaur11.ml;

import android.content.Context;
import android.graphics.Bitmap;
import android.util.Log;
import org.tensorflow.lite.Interpreter;
import java.io.FileInputStream;
import java.io.IOException;
import java.nio.MappedByteBuffer;
import java.nio.channels.FileChannel;
import java.nio.ByteBuffer;
import java.nio.ByteOrder;
import java.util.Arrays;

/**
 * TensorFlow Lite Malnutrition Detector
 * Uses the trained .tflite model for malnutrition detection
 */
public class TensorFlowLiteMalnutritionDetector {
    private static final String TAG = "TFLiteMalnutritionDetector";
    private static final String MODEL_FILENAME = "malnutrition_model.tflite";
    
    // Model input/output specifications
    private static final int INPUT_SIZE = 224;
    private static final int NUM_CLASSES = 3;
    private static final float IMAGE_MEAN = 127.5f;
    private static final float IMAGE_STD = 127.5f;
    
    // Class indices
    public static final int MODERATE_ACUTE_MALNUTRITION = 0; // Malnutrition
    public static final int NORMAL = 1;                       // Healthy
    public static final int STUNTING = 2;                     // Stunting
    
    private Interpreter tflite;
    private Context context;
    private boolean isInitialized = false;
    
    public TensorFlowLiteMalnutritionDetector(Context context) {
        this.context = context;
        initializeModel();
    }
    
    /**
     * Initialize the TensorFlow Lite model
     */
    private void initializeModel() {
        try {
            tflite = new Interpreter(loadModelFile());
            isInitialized = true;
            Log.d(TAG, "TensorFlow Lite malnutrition detection model loaded successfully");
        } catch (Exception e) {
            Log.e(TAG, "Error loading TensorFlow Lite model: " + e.getMessage());
            isInitialized = false;
        }
    }
    
    /**
     * Load model file from assets
     */
    private MappedByteBuffer loadModelFile() throws IOException {
        FileInputStream inputStream = new FileInputStream(context.getAssets().openFd(MODEL_FILENAME).getFileDescriptor());
        FileChannel fileChannel = inputStream.getChannel();
        long startOffset = context.getAssets().openFd(MODEL_FILENAME).getStartOffset();
        long declaredLength = context.getAssets().openFd(MODEL_FILENAME).getDeclaredLength();
        return fileChannel.map(FileChannel.MapMode.READ_ONLY, startOffset, declaredLength);
    }
    
    /**
     * Analyze image for malnutrition signs
     * @param bitmap Input image
     * @return Analysis result
     */
    public MalnutritionAnalysisResult analyzeImage(Bitmap bitmap) {
        if (!isInitialized || tflite == null) {
            return new MalnutritionAnalysisResult(false, "Model not available", 0.0f, "normal");
        }
        
        try {
            // Preprocess image
            ByteBuffer inputBuffer = preprocessImage(bitmap);
            
            // Prepare output array
            float[][] outputArray = new float[1][NUM_CLASSES];
            
            // Run inference
            tflite.run(inputBuffer, outputArray);
            
            // Get predictions
            float[] scores = outputArray[0];
            int predictedClass = getPredictedClass(scores);
            float confidence = scores[predictedClass];
            String className = getClassName(predictedClass);
            
            Log.d(TAG, String.format("Analysis: %s (%.2f%% confidence)", className, confidence * 100));
            Log.d(TAG, "Raw scores: " + Arrays.toString(scores));
            
            return new MalnutritionAnalysisResult(true, getDescription(className, confidence), confidence, className);
            
        } catch (Exception e) {
            Log.e(TAG, "Error during analysis: " + e.getMessage());
            return new MalnutritionAnalysisResult(false, "Analysis failed: " + e.getMessage(), 0.0f, "error");
        }
    }
    
    /**
     * Preprocess image for TensorFlow Lite model
     */
    private ByteBuffer preprocessImage(Bitmap bitmap) {
        // Resize bitmap to model input size
        Bitmap resizedBitmap = Bitmap.createScaledBitmap(bitmap, INPUT_SIZE, INPUT_SIZE, true);
        
        // Create ByteBuffer for input
        ByteBuffer inputBuffer = ByteBuffer.allocateDirect(4 * INPUT_SIZE * INPUT_SIZE * 3);
        inputBuffer.order(ByteOrder.nativeOrder());
        
        // Convert bitmap to ByteBuffer with normalization
        int[] pixels = new int[INPUT_SIZE * INPUT_SIZE];
        resizedBitmap.getPixels(pixels, 0, INPUT_SIZE, 0, 0, INPUT_SIZE, INPUT_SIZE);
        
        for (int pixel : pixels) {
            // Extract RGB values and normalize to [-1, 1]
            float r = ((pixel >> 16) & 0xFF) / 255.0f;
            float g = ((pixel >> 8) & 0xFF) / 255.0f;
            float b = (pixel & 0xFF) / 255.0f;
            
            // Normalize to [-1, 1] range
            r = (r - 0.5f) / 0.5f;
            g = (g - 0.5f) / 0.5f;
            b = (b - 0.5f) / 0.5f;
            
            inputBuffer.putFloat(r);
            inputBuffer.putFloat(g);
            inputBuffer.putFloat(b);
        }
        
        return inputBuffer;
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
     * Get class name from index
     */
    private String getClassName(int classIndex) {
        switch (classIndex) {
            case MODERATE_ACUTE_MALNUTRITION:
                return "moderate_acute_malnutrition";
            case NORMAL:
                return "normal";
            case STUNTING:
                return "stunting";
            default:
                return "unknown";
        }
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
        return isInitialized && tflite != null;
    }
    
    /**
     * Clean up resources
     */
    public void cleanup() {
        if (tflite != null) {
            tflite.close();
            tflite = null;
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