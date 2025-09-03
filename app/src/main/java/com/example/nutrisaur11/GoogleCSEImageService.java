package com.example.nutrisaur11;

import android.util.Log;
import okhttp3.*;
import org.json.JSONArray;
import org.json.JSONObject;
import java.io.IOException;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class GoogleCSEImageService {
    private static final String TAG = "GoogleCSEImageService";
    private static final String GOOGLE_CSE_API_URL = "https://www.googleapis.com/customsearch/v1";
    private static final String API_KEY = "AIzaSyDrTtJm1HQPmmgK2Qqo0gZlmoN5WCd8-g8";
    private static final String SEARCH_ENGINE_ID = "0234698bd81ee4fb9"; // Your CSE ID from the script
    
    private final OkHttpClient client;
    private final ExecutorService executor;
    
    public GoogleCSEImageService() {
        this.client = new OkHttpClient();
        this.executor = Executors.newFixedThreadPool(2);
    }
    
    public interface ImageCallback {
        void onImageUrlReceived(String imageUrl);
        void onError(String error);
    }
    
    /**
     * Get a food image using Google Custom Search Engine API
     */
    public void getFoodImage(String foodName, ImageCallback callback) {
        executor.execute(() -> {
            try {
                // Build the search query for Filipino food images
                String query = foodName + " Filipino food recipe image";
                
                // Create the request URL
                HttpUrl url = HttpUrl.parse(GOOGLE_CSE_API_URL).newBuilder()
                        .addQueryParameter("key", API_KEY)
                        .addQueryParameter("cx", SEARCH_ENGINE_ID)
                        .addQueryParameter("q", query)
                        .addQueryParameter("searchType", "image")
                        .addQueryParameter("num", "5") // Get top 5 results
                        .addQueryParameter("safe", "active") // Safe search
                        .addQueryParameter("imgSize", "medium") // Medium size images
                        .addQueryParameter("imgType", "photo") // Only photos
                        .build();
                
                Log.d(TAG, "Requesting Google CSE image for: " + foodName + " URL: " + url);
                
                Request request = new Request.Builder()
                        .url(url)
                        .build();
                
                try (Response response = client.newCall(request).execute()) {
                    if (!response.isSuccessful()) {
                        Log.e(TAG, "Google CSE API request failed: " + response.code() + " " + response.message());
                        callback.onError("Failed to fetch image: " + response.code());
                        return;
                    }
                    
                    String responseBody = response.body().string();
                    Log.d(TAG, "Google CSE API response received for: " + foodName);
                    
                    // Parse the JSON response
                    JSONObject jsonResponse = new JSONObject(responseBody);
                    JSONArray items = jsonResponse.optJSONArray("items");
                    
                    if (items != null && items.length() > 0) {
                        // Get the first (best) image result
                        JSONObject firstImage = items.getJSONObject(0);
                        String imageUrl = firstImage.optString("link", "");
                        
                        if (!imageUrl.isEmpty()) {
                            Log.d(TAG, "Found Google CSE image URL for " + foodName + ": " + imageUrl);
                            callback.onImageUrlReceived(imageUrl);
                        } else {
                            Log.w(TAG, "No image URL found in first result for: " + foodName);
                            callback.onError("No image URL found");
                        }
                    } else {
                        Log.w(TAG, "No images found for: " + foodName);
                        callback.onError("No images found");
                    }
                }
                
            } catch (Exception e) {
                Log.e(TAG, "Error fetching Google CSE image for " + foodName, e);
                callback.onError("Error: " + e.getMessage());
            }
        });
    }
    
    /**
     * Get a random food image with different search terms
     */
    public void getRandomFoodImage(String foodName, ImageCallback callback) {
        executor.execute(() -> {
            try {
                // Add random terms to get variety
                String[] searchTerms = {
                    "Filipino food",
                    "Asian cuisine", 
                    "Traditional dish",
                    "Home cooking",
                    "Restaurant food"
                };
                
                int randomIndex = (int) (System.currentTimeMillis() % searchTerms.length);
                String query = foodName + " " + searchTerms[randomIndex] + " image";
                
                // Create the request URL
                HttpUrl url = HttpUrl.parse(GOOGLE_CSE_API_URL).newBuilder()
                        .addQueryParameter("key", API_KEY)
                        .addQueryParameter("cx", SEARCH_ENGINE_ID)
                        .addQueryParameter("q", query)
                        .addQueryParameter("searchType", "image")
                        .addQueryParameter("num", "3") // Get top 3 results
                        .addQueryParameter("safe", "active")
                        .addQueryParameter("imgSize", "medium")
                        .addQueryParameter("imgType", "photo")
                        .build();
                
                Request request = new Request.Builder()
                        .url(url)
                        .build();
                
                try (Response response = client.newCall(request).execute()) {
                    if (!response.isSuccessful()) {
                        Log.e(TAG, "Google CSE random image request failed: " + response.code());
                        callback.onError("Failed to fetch random image: " + response.code());
                        return;
                    }
                    
                    String responseBody = response.body().string();
                    JSONObject jsonResponse = new JSONObject(responseBody);
                    JSONArray items = jsonResponse.optJSONArray("items");
                    
                    if (items != null && items.length() > 0) {
                        // Get a random image from the results
                        int randomImageIndex = (int) (System.currentTimeMillis() % items.length());
                        JSONObject randomImage = items.getJSONObject(randomImageIndex);
                        String imageUrl = randomImage.optString("link", "");
                        
                        if (!imageUrl.isEmpty()) {
                            Log.d(TAG, "Found random Google CSE image URL for " + foodName + ": " + imageUrl);
                            callback.onImageUrlReceived(imageUrl);
                        } else {
                            Log.w(TAG, "No random image URL found for: " + foodName);
                            callback.onError("No random image URL found");
                        }
                    } else {
                        Log.w(TAG, "No random images found for: " + foodName);
                        callback.onError("No random images found");
                    }
                }
                
            } catch (Exception e) {
                Log.e(TAG, "Error fetching random Google CSE image for " + foodName, e);
                callback.onError("Error: " + e.getMessage());
            }
        });
    }
    
    public void shutdown() {
        if (executor != null && !executor.isShutdown()) {
            executor.shutdown();
        }
    }
}
