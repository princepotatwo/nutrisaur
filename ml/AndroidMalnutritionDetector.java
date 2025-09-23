package com.example.nutrisaur11.ml;

import android.content.Context;
import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.util.Log;
import androidx.annotation.NonNull;

import org.pytorch.IValue;
import org.pytorch.Module;
import org.pytorch.Tensor;
import org.pytorch.torchvision.TensorImageUtils;

import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.List;
import java.util.Map;

/**
 * Malnutrition Detection using PyTorch Mobile
 * Integrates with WHO growth standards for comprehensive assessment
 * Trained on Roboflow Deteksi Stunting dataset
 */
public class AndroidMalnutritionDetector {
    private static final String TAG = "MalnutritionDetector";
    
    // Malnutrition severity levels (matching Roboflow dataset)
    public static final int SEVERE_ACUTE_MALNUTRITION = 0;  // SAM
    public static final int MODERATE_ACUTE_MALNUTRITION = 1; // MAM (from MalNutrisi)
    public static final int MILD_MALNUTRITION = 2;
    public static final int STUNTING = 3;  // From Roboflow Stunting class
    public static final int NORMAL = 4;    // From Roboflow Healthy class
    public static final int OVERWEIGHT = 5;
    public static final int OBESITY = 6;
    
    private Module model;
    private List<String> classNames;
    private int imageSize;
    private Context context;
    
    // WHO severity thresholds
    private static final double[] WHO_SEVERITY_THRESHOLDS = {
        0.85,  // SAM - High confidence threshold
        0.75,  // MAM - High confidence threshold  
        0.65,  // Mild - Medium confidence threshold
        0.70,  // Stunting - Medium confidence threshold
        0.80,  // Normal - High confidence threshold
        0.75,  // Overweight - High confidence threshold
        0.85   // Obesity - High confidence threshold
    };
    
    public AndroidMalnutritionDetector(Context context) {
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
            
            // Initialize class names (matching Roboflow dataset + additional classes)
            classNames = Arrays.asList(
                "severe_acute_malnutrition",
                "moderate_acute_malnutrition", // From MalNutrisi
                "mild_malnutrition",
                "stunting",  // From Roboflow Stunting class
                "normal",    // From Roboflow Healthy class
                "overweight",
                "obesity"
            );
            
            imageSize = 224; // Standard input size
            Log.d(TAG, "Malnutrition detection model loaded successfully");
            
        } catch (Exception e) {
            Log.e(TAG, "Error loading malnutrition detection model: " + e.getMessage());
            throw new RuntimeException("Failed to load malnutrition detection model", e);
        }
    }
    
    /**
     * Detect malnutrition from image
     * @param imagePath Path to the image file
     * @return MalnutritionResult containing prediction and confidence
     */
    public MalnutritionResult detectMalnutrition(String imagePath) {
        try {
            // Load and preprocess image
            Bitmap bitmap = BitmapFactory.decodeFile(imagePath);
            if (bitmap == null) {
                throw new IllegalArgumentException("Could not load image from: " + imagePath);
            }
            
            return detectMalnutrition(bitmap);
            
        } catch (Exception e) {
            Log.e(TAG, "Error detecting malnutrition: " + e.getMessage());
            return new MalnutritionResult(-1, 0.0f, "Error", "Failed to process image");
        }
    }
    
    /**
     * Detect malnutrition from bitmap
     * @param bitmap Input image bitmap
     * @return MalnutritionResult containing prediction and confidence
     */
    public MalnutritionResult detectMalnutrition(Bitmap bitmap) {
        try {
            // Preprocess image
            Bitmap resizedBitmap = Bitmap.createScaledBitmap(bitmap, imageSize, imageSize, true);
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
            
            // Get class name and description
            String className = classNames.get(predictedClass);
            String description = getMalnutritionDescription(predictedClass, confidence);
            
            Log.d(TAG, String.format("Prediction: %s (%.2f%% confidence)", className, confidence * 100));
            
            return new MalnutritionResult(predictedClass, confidence, className, description);
            
        } catch (Exception e) {
            Log.e(TAG, "Error during malnutrition detection: " + e.getMessage());
            return new MalnutritionResult(-1, 0.0f, "Error", "Detection failed: " + e.getMessage());
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
     * Get human-readable malnutrition description
     */
    private String getMalnutritionDescription(int classIndex, float confidence) {
        String baseDescription;
        
        switch (classIndex) {
            case SEVERE_ACUTE_MALNUTRITION:
                baseDescription = "Severe Acute Malnutrition (SAM) detected";
                break;
            case MODERATE_ACUTE_MALNUTRITION:
                baseDescription = "Moderate Acute Malnutrition (MAM) detected - from MalNutrisi class";
                break;
            case MILD_MALNUTRITION:
                baseDescription = "Mild malnutrition detected";
                break;
            case STUNTING:
                baseDescription = "Stunting (chronic malnutrition) detected - from Roboflow dataset";
                break;
            case NORMAL:
                baseDescription = "Normal nutritional status - from Healthy class";
                break;
            case OVERWEIGHT:
                baseDescription = "Overweight detected";
                break;
            case OBESITY:
                baseDescription = "Obesity detected";
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
            return baseDescription + " (Low confidence - recommend additional assessment)";
        }
    }
    
    /**
     * Get WHO severity level based on prediction
     */
    public String getWHOSeverityLevel(int classIndex, float confidence) {
        if (confidence < WHO_SEVERITY_THRESHOLDS[classIndex]) {
            return "UNCERTAIN"; // Below confidence threshold
        }
        
        switch (classIndex) {
            case SEVERE_ACUTE_MALNUTRITION:
                return "SEVERE"; // Immediate medical attention required
            case MODERATE_ACUTE_MALNUTRITION:
            case OBESITY:
                return "MODERATE"; // Requires intervention
            case MILD_MALNUTRITION:
            case OVERWEIGHT:
                return "MILD"; // Monitor and provide guidance
            case STUNTING:
                return "CHRONIC"; // Long-term intervention needed
            case NORMAL:
                return "NORMAL"; // Healthy status
            default:
                return "UNKNOWN";
        }
    }
    
    /**
     * Get recommendations based on malnutrition detection
     */
    public List<String> getRecommendations(int classIndex, float confidence) {
        List<String> recommendations = new ArrayList<>();
        
        if (confidence < 0.6) {
            recommendations.add("Low confidence prediction - recommend anthropometric measurements");
            recommendations.add("Use WHO growth standards for accurate assessment");
            return recommendations;
        }
        
        switch (classIndex) {
            case SEVERE_ACUTE_MALNUTRITION:
                recommendations.add("🚨 IMMEDIATE MEDICAL ATTENTION REQUIRED");
                recommendations.add("Start therapeutic feeding program immediately");
                recommendations.add("Refer to specialized nutrition center");
                recommendations.add("Monitor for complications");
                recommendations.add("Measure MUAC and weight-for-height z-score");
                break;
                
            case MODERATE_ACUTE_MALNUTRITION:
                recommendations.add("⚠️ Moderate malnutrition requires intervention");
                recommendations.add("Start supplementary feeding program");
                recommendations.add("Monitor weight and height regularly");
                recommendations.add("Provide nutrition education");
                recommendations.add("Measure anthropometric indicators");
                break;
                
            case MILD_MALNUTRITION:
                recommendations.add("📊 Monitor nutritional status closely");
                recommendations.add("Provide balanced nutrition guidance");
                recommendations.add("Encourage diverse food intake");
                recommendations.add("Regular follow-up assessments");
                break;
                
            case STUNTING:
                recommendations.add("📏 Chronic malnutrition - long-term intervention needed");
                recommendations.add("Focus on catch-up growth nutrition");
                recommendations.add("Address underlying causes");
                recommendations.add("Regular height monitoring");
                recommendations.add("Age-appropriate nutrition support");
                break;
                
            case NORMAL:
                recommendations.add("✅ Maintain healthy nutritional status");
                recommendations.add("Continue balanced diet");
                recommendations.add("Regular health monitoring");
                recommendations.add("Prevent malnutrition through good nutrition");
                break;
                
            case OVERWEIGHT:
                recommendations.add("⚖️ Address overweight through healthy lifestyle");
                recommendations.add("Balance calorie intake and expenditure");
                recommendations.add("Increase physical activity");
                recommendations.add("Focus on nutrient-dense foods");
                recommendations.add("Regular BMI monitoring");
                break;
                
            case OBESITY:
                recommendations.add("🚨 Obesity requires comprehensive intervention");
                recommendations.add("Medical supervision recommended");
                recommendations.add("Structured weight management program");
                recommendations.add("Lifestyle modification support");
                recommendations.add("Regular health monitoring");
                break;
        }
        
        return recommendations;
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
    }
    
    /**
     * Result class for malnutrition detection
     */
    public static class MalnutritionResult {
        public final int classIndex;
        public final float confidence;
        public final String className;
        public final String description;
        
        public MalnutritionResult(int classIndex, float confidence, String className, String description) {
            this.classIndex = classIndex;
            this.confidence = confidence;
            this.className = className;
            this.description = description;
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
        
        public boolean requiresImmediateAttention() {
            return classIndex == SEVERE_ACUTE_MALNUTRITION && confidence >= 0.8;
        }
        
        public boolean requiresIntervention() {
            return (classIndex == MODERATE_ACUTE_MALNUTRITION || classIndex == OBESITY) && confidence >= 0.7;
        }
        
        @Override
        public String toString() {
            return String.format("MalnutritionResult{classIndex=%d, confidence=%.2f, className='%s', description='%s'}", 
                classIndex, confidence, className, description);
        }
    }
}
