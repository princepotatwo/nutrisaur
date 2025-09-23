package com.example.nutrisaur11.ml;

import android.content.Context;
import android.graphics.Bitmap;
import android.util.Log;

import org.tensorflow.lite.Interpreter;
import org.tensorflow.lite.support.common.FileUtil;
import org.tensorflow.lite.support.common.ops.NormalizeOp;
import org.tensorflow.lite.support.image.ImageProcessor;
import org.tensorflow.lite.support.image.TensorImage;
import org.tensorflow.lite.support.image.ops.ResizeOp;

import java.io.File;
import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.nio.ByteBuffer;
import java.nio.ByteOrder;
import java.nio.MappedByteBuffer;
import java.nio.channels.FileChannel;
import java.util.Arrays;

/**
 * TensorFlow Lite Malnutrition Detection
 * Uses CNN model for analyzing malnutrition signs in images
 * Classes: moderate_acute_malnutrition, normal, stunting
 */
public class TensorFlowLiteMalnutritionDetector {
    private static final String TAG = "TFLiteMalnutritionDetector";
    
    // Model configuration
    private static final String MODEL_FILE = "malnutrition_model.tflite";
    private static final int INPUT_SIZE = 224;
    private static final int NUM_CLASSES = 3;
    
    // Class names matching our model
    private static final String[] CLASS_NAMES = {
        "moderate_acute_malnutrition", // Index 0
        "normal",                      // Index 1
        "stunting"                     // Index 2
    };
    
    private Interpreter tflite;
    private Context context;
    private boolean isInitialized = false;
    
    public TensorFlowLiteMalnutritionDetector(Context context) {
        this.context = context;
        initializeModel();
    }
    
    /**
     * Initialize TensorFlow Lite model
     */
    private void initializeModel() {
        Log.d(TAG, "🔧 Starting TensorFlow Lite model initialization...");
        
        try {
            // Step 1: Load model from assets
            Log.d(TAG, "📂 Loading model from assets...");
            MappedByteBuffer modelBuffer = loadModelFile();
            Log.d(TAG, "✅ Model loaded from assets");
            Log.d(TAG, "📊 Model size: " + modelBuffer.capacity() + " bytes");
            
            // Step 2: Create interpreter
            Log.d(TAG, "🤖 Creating TensorFlow Lite interpreter...");
            Interpreter.Options options = new Interpreter.Options();
            options.setNumThreads(4); // Use 4 threads for better performance
            tflite = new Interpreter(modelBuffer, options);
            Log.d(TAG, "✅ TensorFlow Lite interpreter created");
            
            // Step 3: Get model info
            Log.d(TAG, "📊 Model input details:");
            Log.d(TAG, "   Shape: " + Arrays.toString(tflite.getInputTensor(0).shape()));
            Log.d(TAG, "   Data type: " + tflite.getInputTensor(0).dataType());
            
            Log.d(TAG, "📊 Model output details:");
            Log.d(TAG, "   Shape: " + Arrays.toString(tflite.getOutputTensor(0).shape()));
            Log.d(TAG, "   Data type: " + tflite.getOutputTensor(0).dataType());
            
            // Step 4: Test model with dummy input
            Log.d(TAG, "🧪 Testing model with dummy input...");
            float[][][][] dummyInput = new float[1][INPUT_SIZE][INPUT_SIZE][3];
            float[][] dummyOutput = new float[1][NUM_CLASSES];
            
            tflite.run(dummyInput, dummyOutput);
            Log.d(TAG, "✅ Model test successful");
            Log.d(TAG, "📊 Test output: " + Arrays.toString(dummyOutput[0]));
            
            isInitialized = true;
            Log.d(TAG, "🎯 TensorFlow Lite model initialization COMPLETE");
            
        } catch (Exception e) {
            Log.e(TAG, "❌ Model initialization FAILED");
            Log.e(TAG, "🔍 Error type: " + e.getClass().getSimpleName());
            Log.e(TAG, "🔍 Error message: " + e.getMessage());
            Log.e(TAG, "🔍 Stack trace:");
            for (StackTraceElement element : e.getStackTrace()) {
                Log.e(TAG, "   " + element.toString());
            }
            
            isInitialized = false;
            tflite = null;
        }
    }
    
    /**
     * Load model file from assets
     */
    private MappedByteBuffer loadModelFile() throws IOException {
        // Copy model from assets to internal storage
        File modelFile = new File(context.getFilesDir(), MODEL_FILE);
        
        if (!modelFile.exists() || modelFile.length() == 0) {
            Log.d(TAG, "📂 Copying model from assets to internal storage...");
            try (InputStream is = context.getAssets().open(MODEL_FILE)) {
                try (FileOutputStream fos = new FileOutputStream(modelFile)) {
                    byte[] buffer = new byte[8192];
                    int bytesRead;
                    while ((bytesRead = is.read(buffer)) != -1) {
                        fos.write(buffer, 0, bytesRead);
                    }
                }
            }
            Log.d(TAG, "✅ Model copied successfully");
        } else {
            Log.d(TAG, "✅ Model already exists in internal storage");
        }
        
        // Load model as MappedByteBuffer
        try (FileInputStream fis = new FileInputStream(modelFile)) {
            FileChannel fileChannel = fis.getChannel();
            long startOffset = 0;
            long declaredLength = fileChannel.size();
            return fileChannel.map(FileChannel.MapMode.READ_ONLY, startOffset, declaredLength);
        }
    }
    
    /**
     * Analyze image for malnutrition signs
     */
    public MalnutritionAnalysisResult analyzeImage(Bitmap bitmap) {
        Log.d(TAG, "🔍 Starting TensorFlow Lite analysis...");
        Log.d(TAG, "📊 Model initialized: " + isInitialized);
        Log.d(TAG, "📊 Input bitmap: " + (bitmap != null ? bitmap.getWidth() + "x" + bitmap.getHeight() : "NULL"));
        
        if (!isInitialized || tflite == null) {
            Log.e(TAG, "❌ TensorFlow Lite model not available");
            return new MalnutritionAnalysisResult(false, "TensorFlow Lite model not available. Please ensure the model is properly installed.", 0.0f, "error");
        }
        
        try {
            // Step 1: Preprocess image
            Log.d(TAG, "🖼️ Step 1: Preprocessing image...");
            Bitmap resizedBitmap = Bitmap.createScaledBitmap(bitmap, INPUT_SIZE, INPUT_SIZE, true);
            Log.d(TAG, "📊 Resized bitmap: " + resizedBitmap.getWidth() + "x" + resizedBitmap.getHeight());
            
            // Convert bitmap to float array
            float[][][][] inputArray = new float[1][INPUT_SIZE][INPUT_SIZE][3];
            convertBitmapToFloatArray(resizedBitmap, inputArray[0]);
            Log.d(TAG, "✅ Image preprocessing complete");
            
            // Step 2: Run inference
            Log.d(TAG, "🤖 Step 2: Running TensorFlow Lite inference...");
            float[][] outputArray = new float[1][NUM_CLASSES];
            tflite.run(inputArray, outputArray);
            Log.d(TAG, "✅ TensorFlow Lite inference complete");
            
            // Step 3: Process results
            Log.d(TAG, "📊 Step 3: Processing results...");
            float[] scores = outputArray[0];
            Log.d(TAG, "📊 Raw scores: " + Arrays.toString(scores));
            
            // Find predicted class
            int predictedClass = 0;
            float maxScore = scores[0];
            for (int i = 1; i < scores.length; i++) {
                if (scores[i] > maxScore) {
                    maxScore = scores[i];
                    predictedClass = i;
                }
            }
            
            String className = CLASS_NAMES[predictedClass];
            float confidence = maxScore;
            
            Log.d(TAG, "🎯 Predicted class: " + predictedClass + " (" + className + ")");
            Log.d(TAG, "📊 Confidence: " + confidence + " (" + (confidence * 100) + "%)");
            Log.d(TAG, "✅ TensorFlow Lite Analysis Complete: " + className + " (" + String.format("%.2f%%", confidence * 100) + " confidence)");
            
            return new MalnutritionAnalysisResult(true, getDescription(className, confidence), confidence, className);
            
        } catch (Exception e) {
            Log.e(TAG, "❌ Error during TensorFlow Lite analysis");
            Log.e(TAG, "🔍 Error type: " + e.getClass().getSimpleName());
            Log.e(TAG, "🔍 Error message: " + e.getMessage());
            Log.e(TAG, "🔍 Stack trace:");
            for (StackTraceElement element : e.getStackTrace()) {
                Log.e(TAG, "   " + element.toString());
            }
            return new MalnutritionAnalysisResult(false, "TensorFlow Lite analysis failed: " + e.getMessage(), 0.0f, "error");
        }
    }
    
    /**
     * Convert bitmap to float array for TensorFlow Lite
     */
    private void convertBitmapToFloatArray(Bitmap bitmap, float[][][] array) {
        int width = bitmap.getWidth();
        int height = bitmap.getHeight();
        
        // Get pixel data
        int[] pixels = new int[width * height];
        bitmap.getPixels(pixels, 0, width, 0, 0, width, height);
        
        // Convert to float array (normalize to 0-1 range)
        for (int y = 0; y < height; y++) {
            for (int x = 0; x < width; x++) {
                int pixel = pixels[y * width + x];
                
                // Extract RGB values
                int r = (pixel >> 16) & 0xFF;
                int g = (pixel >> 8) & 0xFF;
                int b = pixel & 0xFF;
                
                // Normalize to 0-1 range (assuming model expects this)
                array[y][x][0] = r / 255.0f; // Red
                array[y][x][1] = g / 255.0f; // Green
                array[y][x][2] = b / 255.0f; // Blue
            }
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
