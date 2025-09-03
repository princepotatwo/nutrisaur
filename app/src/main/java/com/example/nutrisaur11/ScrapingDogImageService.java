package com.example.nutrisaur11;

import android.util.Log;
import okhttp3.*;
import org.json.JSONArray;
import org.json.JSONObject;
import java.io.IOException;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class ScrapingDogImageService {
    private static final String TAG = "ScrapingDogImageService";
    private static final String SCRAPING_DOG_API_URL = "https://api.scrapingdog.com/google-search";
    private static final String API_KEY = "68b76eebb481ff928530f195";
    
    private final OkHttpClient client;
    private final ExecutorService executor;
    
    public ScrapingDogImageService() {
        this.client = new OkHttpClient();
        this.executor = Executors.newFixedThreadPool(2);
    }
    
    public interface ImageCallback {
        void onImageUrlReceived(String imageUrl);
        void onError(String error);
    }
    
    /**
     * Get a food image using Scraping Dog Google Search API
     */
    public void getFoodImage(String foodName, ImageCallback callback) {
        executor.execute(() -> {
            try {
                // Build the search query for Filipino food images
                String query = foodName + " Filipino food recipe image";
                
                // Create the request URL with parameters
                HttpUrl url = HttpUrl.parse(SCRAPING_DOG_API_URL).newBuilder()
                        .addQueryParameter("api_key", API_KEY)
                        .addQueryParameter("q", query)
                        .addQueryParameter("gl", "ph") // Philippines
                        .addQueryParameter("hl", "en") // English
                        .addQueryParameter("num", "10") // Get 10 results
                        .addQueryParameter("tbm", "isch") // Image search
                        .build();
                
                Log.d(TAG, "Requesting Scraping Dog image for: " + foodName);
                
                Request request = new Request.Builder()
                        .url(url)
                        .addHeader("User-Agent", "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36")
                        .build();
                
                try (Response response = client.newCall(request).execute()) {
                    if (!response.isSuccessful()) {
                        Log.e(TAG, "Scraping Dog API request failed: " + response.code() + " " + response.message());
                        callback.onError("Failed to fetch image: " + response.code());
                        return;
                    }
                    
                    String responseBody = response.body().string();
                    Log.d(TAG, "Scraping Dog API response received for: " + foodName);
                    
                    // Check if response is HTML instead of JSON
                    if (responseBody.trim().startsWith("<!DOCTYPE") || responseBody.trim().startsWith("<html")) {
                        Log.e(TAG, "Scraping Dog API returned HTML instead of JSON for: " + foodName);
                        callback.onError("API returned HTML instead of JSON");
                        return;
                    }
                    
                    // Check if response is too short (likely an error page)
                    if (responseBody.length() < 50) {
                        Log.e(TAG, "Scraping Dog API returned very short response for: " + foodName);
                        callback.onError("API returned invalid response");
                        return;
                    }
                    
                    // Try to parse the JSON response
                    try {
                        JSONObject jsonResponse = new JSONObject(responseBody);
                        JSONArray results = jsonResponse.optJSONArray("results");
                        
                        if (results != null && results.length() > 0) {
                            // Get the first (best) image result
                            JSONObject firstImage = results.getJSONObject(0);
                            String imageUrl = firstImage.optString("image", "");
                            
                            if (!imageUrl.isEmpty()) {
                                Log.d(TAG, "Found Scraping Dog image URL for " + foodName + ": " + imageUrl);
                                callback.onImageUrlReceived(imageUrl);
                            } else {
                                Log.w(TAG, "No image URL found in first result for: " + foodName);
                                callback.onError("No image URL found");
                            }
                        } else {
                            Log.w(TAG, "No images found for: " + foodName);
                            callback.onError("No images found");
                        }
                    } catch (Exception e) {
                        Log.e(TAG, "Failed to parse JSON response for " + foodName + ": " + e.getMessage());
                        Log.d(TAG, "Response body: " + responseBody.substring(0, Math.min(200, responseBody.length())));
                        callback.onError("Failed to parse JSON response");
                    }
                }
                
            } catch (Exception e) {
                Log.e(TAG, "Error fetching Scraping Dog image for " + foodName, e);
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
                HttpUrl url = HttpUrl.parse(SCRAPING_DOG_API_URL).newBuilder()
                        .addQueryParameter("api_key", API_KEY)
                        .addQueryParameter("q", query)
                        .addQueryParameter("gl", "ph")
                        .addQueryParameter("hl", "en")
                        .addQueryParameter("num", "5")
                        .addQueryParameter("tbm", "isch")
                        .build();
                
                Request request = new Request.Builder()
                        .url(url)
                        .addHeader("User-Agent", "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36")
                        .build();
                
                try (Response response = client.newCall(request).execute()) {
                    if (!response.isSuccessful()) {
                        Log.e(TAG, "Scraping Dog random image request failed: " + response.code());
                        callback.onError("Failed to fetch random image: " + response.code());
                        return;
                    }
                    
                    String responseBody = response.body().string();
                    
                    // Check if response is HTML instead of JSON
                    if (responseBody.trim().startsWith("<!DOCTYPE") || responseBody.trim().startsWith("<html")) {
                        Log.e(TAG, "Scraping Dog API returned HTML instead of JSON for random image: " + foodName);
                        callback.onError("API returned HTML instead of JSON");
                        return;
                    }
                    
                    try {
                        JSONObject jsonResponse = new JSONObject(responseBody);
                        JSONArray results = jsonResponse.optJSONArray("results");
                        
                        if (results != null && results.length() > 0) {
                            // Get a random image from the results
                            int randomImageIndex = (int) (System.currentTimeMillis() % results.length());
                            JSONObject randomImage = results.getJSONObject(randomImageIndex);
                            String imageUrl = randomImage.optString("image", "");
                            
                            if (!imageUrl.isEmpty()) {
                                Log.d(TAG, "Found random Scraping Dog image URL for " + foodName + ": " + imageUrl);
                                callback.onImageUrlReceived(imageUrl);
                            } else {
                                Log.w(TAG, "No random image URL found for: " + foodName);
                                callback.onError("No random image URL found");
                            }
                        } else {
                            Log.w(TAG, "No random images found for: " + foodName);
                            callback.onError("No random images found");
                        }
                    } catch (Exception e) {
                        Log.e(TAG, "Failed to parse JSON response for random image " + foodName + ": " + e.getMessage());
                        callback.onError("Failed to parse JSON response");
                    }
                }
                
            } catch (Exception e) {
                Log.e(TAG, "Error fetching random Scraping Dog image for " + foodName, e);
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
