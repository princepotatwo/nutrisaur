package com.example.nutrisaur11;

import android.content.Context;
import android.graphics.Bitmap;
import android.util.Base64;
import android.util.Log;
import org.json.JSONArray;
import org.json.JSONObject;
import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.io.ByteArrayOutputStream;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class GeminiImageAnalysisService {
    private static final String TAG = "GeminiImageAnalysis";
    private static final String GEMINI_API_URL = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" + ApiConfig.GEMINI_API_KEY;
    
    private final ExecutorService executor = Executors.newSingleThreadExecutor();
    private Context context;
    
    public GeminiImageAnalysisService(Context context) {
        this.context = context;
    }
    
    public interface MalnutritionAnalysisCallback {
        void onSuccess(MalnutritionAnalysisResult result);
        void onError(String error);
    }
    
    public static class MalnutritionAnalysisResult {
        private String riskLevel;
        private String confidence;
        private String analysis;
        private String recommendations;
        private boolean hasMalnutritionSigns;
        private String specificSigns;
        
        public MalnutritionAnalysisResult(String riskLevel, String confidence, String analysis, 
                                       String recommendations, boolean hasMalnutritionSigns, String specificSigns) {
            this.riskLevel = riskLevel;
            this.confidence = confidence;
            this.analysis = analysis;
            this.recommendations = recommendations;
            this.hasMalnutritionSigns = hasMalnutritionSigns;
            this.specificSigns = specificSigns;
        }
        
        // Getters
        public String getRiskLevel() { return riskLevel; }
        public String getConfidence() { return confidence; }
        public String getAnalysis() { return analysis; }
        public String getRecommendations() { return recommendations; }
        public boolean hasMalnutritionSigns() { return hasMalnutritionSigns; }
        public String getSpecificSigns() { return specificSigns; }
    }
    
    public void analyzeImageForMalnutrition(Bitmap bitmap, MalnutritionAnalysisCallback callback) {
        executor.execute(() -> {
            try {
                Log.d(TAG, "Starting Gemini image analysis...");
                
                // Convert bitmap to base64
                String base64Image = bitmapToBase64(bitmap);
                Log.d(TAG, "Image converted to base64, size: " + base64Image.length() + " characters");
                
                // Create the API request
                JSONObject requestBody = createMalnutritionAnalysisRequest(base64Image);
                Log.d(TAG, "API request created");
                
                // Make API call with retry logic
                String response = makeApiCallWithRetry(requestBody);
                Log.d(TAG, "API call successful, response length: " + response.length());
                
                // Parse response
                MalnutritionAnalysisResult result = parseMalnutritionResponse(response);
                Log.d(TAG, "Response parsed successfully - Risk Level: " + result.getRiskLevel());
                
                // Return result on main thread
                callback.onSuccess(result);
                
            } catch (Exception e) {
                Log.e(TAG, "Error analyzing image: " + e.getMessage(), e);
                
                // Create fallback result
                MalnutritionAnalysisResult fallbackResult = createFallbackResult(e.getMessage());
                callback.onSuccess(fallbackResult);
            }
        });
    }
    
    private String bitmapToBase64(Bitmap bitmap) {
        ByteArrayOutputStream byteArrayOutputStream = new ByteArrayOutputStream();
        bitmap.compress(Bitmap.CompressFormat.JPEG, 90, byteArrayOutputStream);
        byte[] byteArray = byteArrayOutputStream.toByteArray();
        return Base64.encodeToString(byteArray, Base64.NO_WRAP);
    }
    
    private JSONObject createMalnutritionAnalysisRequest(String base64Image) throws Exception {
        JSONObject requestBody = new JSONObject();
        JSONArray contents = new JSONArray();
        JSONObject content = new JSONObject();
        JSONArray parts = new JSONArray();
        
        // Add text prompt
        String prompt = createMalnutritionAnalysisPrompt();
        parts.put(new JSONObject().put("text", prompt));
        
        // Add image data
        JSONObject imageData = new JSONObject();
        imageData.put("mime_type", "image/jpeg");
        imageData.put("data", base64Image);
        
        JSONObject inlineData = new JSONObject();
        inlineData.put("inline_data", imageData);
        parts.put(inlineData);
        
        content.put("parts", parts);
        contents.put(content);
        requestBody.put("contents", contents);
        
        // Add generation config for structured output
        JSONObject generationConfig = new JSONObject();
        generationConfig.put("temperature", 0.1); // Low temperature for consistent analysis
        generationConfig.put("maxOutputTokens", 1000);
        requestBody.put("generationConfig", generationConfig);
        
        return requestBody;
    }
    
    private String createMalnutritionAnalysisPrompt() {
        return "You are a medical AI assistant specializing in malnutrition detection. " +
               "Analyze this image for signs of malnutrition in children or adults. " +
               "Look for specific visual indicators including:\n\n" +
               "1. **Facial features**: Sunken eyes, hollow cheeks, thin face\n" +
               "2. **Body appearance**: Visible ribs, protruding bones, wasted appearance\n" +
               "3. **Skin condition**: Dry, pale, or loose skin\n" +
               "4. **Hair condition**: Thin, brittle, or discolored hair\n" +
               "5. **Overall body proportions**: Severe thinness, muscle wasting\n\n" +
               "**IMPORTANT**: Only identify malnutrition if there are CLEAR, OBVIOUS signs. " +
               "If the person appears normal or healthy, say so clearly.\n\n" +
               "Respond with a JSON object in this exact format:\n" +
               "{\n" +
               "  \"risk_level\": \"NORMAL\" or \"LOW\" or \"MODERATE\" or \"HIGH\",\n" +
               "  \"confidence\": \"High\" or \"Medium\" or \"Low\",\n" +
               "  \"has_malnutrition_signs\": true or false,\n" +
               "  \"specific_signs\": \"List specific signs found, or 'No obvious signs of malnutrition' if normal\",\n" +
               "  \"analysis\": \"Detailed analysis of what you observe in the image\",\n" +
               "  \"recommendations\": \"Specific recommendations based on findings\"\n" +
               "}\n\n" +
               "Be conservative - only flag malnutrition if signs are clearly visible and concerning.";
    }
    
    private String makeApiCallWithRetry(JSONObject requestBody) throws Exception {
        int maxRetries = 3;
        Exception lastException = null;
        
        for (int attempt = 1; attempt <= maxRetries; attempt++) {
            try {
                Log.d(TAG, "API call attempt " + attempt + "/" + maxRetries);
                return makeApiCall(requestBody);
            } catch (Exception e) {
                lastException = e;
                Log.w(TAG, "API call attempt " + attempt + " failed: " + e.getMessage());
                
                if (attempt < maxRetries) {
                    // Wait before retry (exponential backoff)
                    long delay = 1000 * attempt; // 1s, 2s, 3s
                    Thread.sleep(delay);
                }
            }
        }
        
        throw new Exception("API call failed after " + maxRetries + " attempts. Last error: " + 
                          (lastException != null ? lastException.getMessage() : "Unknown error"));
    }
    
    private String makeApiCall(JSONObject requestBody) throws Exception {
        URL url = new URL(GEMINI_API_URL);
        HttpURLConnection connection = (HttpURLConnection) url.openConnection();
        
        connection.setRequestMethod("POST");
        connection.setRequestProperty("Content-Type", "application/json");
        connection.setConnectTimeout(30000);
        connection.setReadTimeout(60000);
        connection.setDoOutput(true);
        
        // Send request
        try (OutputStream os = connection.getOutputStream()) {
            byte[] input = requestBody.toString().getBytes("utf-8");
            os.write(input, 0, input.length);
        }
        
        // Read response
        int responseCode = connection.getResponseCode();
        BufferedReader reader;
        
        if (responseCode >= 200 && responseCode < 300) {
            reader = new BufferedReader(new InputStreamReader(connection.getInputStream()));
        } else {
            reader = new BufferedReader(new InputStreamReader(connection.getErrorStream()));
        }
        
        StringBuilder response = new StringBuilder();
        String line;
        while ((line = reader.readLine()) != null) {
            response.append(line);
        }
        reader.close();
        
        if (responseCode >= 200 && responseCode < 300) {
            Log.d(TAG, "API call successful");
            return response.toString();
        } else {
            Log.e(TAG, "API call failed with code: " + responseCode);
            Log.e(TAG, "Response: " + response.toString());
            throw new Exception("API call failed with code: " + responseCode);
        }
    }
    
    private MalnutritionAnalysisResult parseMalnutritionResponse(String response) throws Exception {
        Log.d(TAG, "Parsing response: " + response);
        
        try {
            JSONObject jsonResponse = new JSONObject(response);
            JSONArray candidates = jsonResponse.getJSONArray("candidates");
            JSONObject candidate = candidates.getJSONObject(0);
            JSONObject content = candidate.getJSONObject("content");
            JSONArray parts = content.getJSONArray("parts");
            String text = parts.getJSONObject(0).getString("text");
            
            Log.d(TAG, "Extracted text: " + text);
            
            // Extract JSON from the response text
            String jsonText = extractJsonFromText(text);
            JSONObject analysisJson = new JSONObject(jsonText);
            
            String riskLevel = analysisJson.getString("risk_level");
            String confidence = analysisJson.getString("confidence");
            boolean hasMalnutritionSigns = analysisJson.getBoolean("has_malnutrition_signs");
            String specificSigns = analysisJson.getString("specific_signs");
            String analysis = analysisJson.getString("analysis");
            String recommendations = analysisJson.getString("recommendations");
            
            return new MalnutritionAnalysisResult(riskLevel, confidence, analysis, 
                                               recommendations, hasMalnutritionSigns, specificSigns);
            
        } catch (Exception e) {
            Log.e(TAG, "Error parsing response: " + e.getMessage());
            Log.e(TAG, "Response was: " + response);
            
            // Fallback: create a safe default result
            return new MalnutritionAnalysisResult(
                "NORMAL", 
                "Low", 
                "Unable to analyze image properly. Please try again with a clearer photo.", 
                "Consult a healthcare provider for proper assessment.", 
                false, 
                "Analysis failed - please retake photo"
            );
        }
    }
    
    private String extractJsonFromText(String text) {
        // Find JSON object in the response text
        int startIndex = text.indexOf("{");
        int endIndex = text.lastIndexOf("}");
        
        if (startIndex != -1 && endIndex != -1 && endIndex > startIndex) {
            return text.substring(startIndex, endIndex + 1);
        }
        
        // If no JSON found, return the whole text
        return text;
    }
    
    private MalnutritionAnalysisResult createFallbackResult(String errorMessage) {
        Log.w(TAG, "Creating fallback result due to error: " + errorMessage);
        
        return new MalnutritionAnalysisResult(
            "NORMAL", // Safe default - no malnutrition detected
            "Low", // Low confidence due to analysis failure
            "Unable to complete AI analysis. Please ensure you have a stable internet connection and try again. " +
            "For accurate assessment, consult a healthcare provider.",
            "Please retake the photo with better lighting and ensure your face is clearly visible. " +
            "If the issue persists, consult a healthcare provider for proper malnutrition screening.",
            false, // No malnutrition signs detected (safe default)
            "Analysis failed - please retake photo"
        );
    }
    
    public void cleanup() {
        if (executor != null && !executor.isShutdown()) {
            executor.shutdown();
        }
    }
}
