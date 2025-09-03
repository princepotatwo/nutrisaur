package com.example.nutrisaur11;

import android.util.Log;
import okhttp3.*;
import org.json.JSONArray;
import org.json.JSONObject;
import java.io.IOException;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class UnsplashImageService {
    private static final String TAG = "UnsplashImageService";
    private static final String UNSPLASH_API_URL = "https://api.unsplash.com/search/photos";
    private static final String ACCESS_KEY = "YOUR_UNSPLASH_ACCESS_KEY"; // You'll need to get this from Unsplash
    
    private final OkHttpClient client;
    private final ExecutorService executor;
    
    public UnsplashImageService() {
        this.client = new OkHttpClient();
        this.executor = Executors.newFixedThreadPool(2);
    }
    
    public interface ImageCallback {
        void onImageUrlReceived(String imageUrl);
        void onError(String error);
    }
    
    /**
     * Get a food image using Unsplash API
     */
    public void getFoodImage(String foodName, ImageCallback callback) {
        executor.execute(() -> {
            try {
                // Build the search query for Filipino food
                String query = foodName + " Filipino food";
                
                // Create the request URL
                HttpUrl url = HttpUrl.parse(UNSPLASH_API_URL).newBuilder()
                        .addQueryParameter("query", query)
                        .addQueryParameter("per_page", "5")
                        .addQueryParameter("orientation", "landscape")
                        .build();
                
                Request request = new Request.Builder()
                        .url(url)
                        .addHeader("Authorization", "Client-ID " + ACCESS_KEY)
                        .build();
                
                try (Response response = client.newCall(request).execute()) {
                    if (!response.isSuccessful()) {
                        Log.e(TAG, "Unsplash API request failed: " + response.code() + " " + response.message());
                        callback.onError("Failed to fetch image: " + response.code());
                        return;
                    }
                    
                    String responseBody = response.body().string();
                    Log.d(TAG, "Unsplash API response received for: " + foodName);
                    
                    // Parse the JSON response
                    JSONObject jsonResponse = new JSONObject(responseBody);
                    JSONArray results = jsonResponse.optJSONArray("results");
                    
                    if (results != null && results.length() > 0) {
                        // Get the first (best) image result
                        JSONObject firstImage = results.getJSONObject(0);
                        JSONObject urls = firstImage.getJSONObject("urls");
                        String imageUrl = urls.optString("regular", ""); // Use 'regular' size (1080px width)
                        
                        if (!imageUrl.isEmpty()) {
                            Log.d(TAG, "Found Unsplash image URL for " + foodName + ": " + imageUrl);
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
                Log.e(TAG, "Error fetching Unsplash image for " + foodName, e);
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
