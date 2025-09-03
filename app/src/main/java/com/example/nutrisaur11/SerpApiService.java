package com.example.nutrisaur11;

import android.util.Log;
import okhttp3.*;
import org.json.JSONArray;
import org.json.JSONObject;
import java.io.IOException;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class SerpApiService {
    private static final String TAG = "SerpApiService";
    private static final String SERPAPI_BASE_URL = "https://serpapi.com/search.json";
    private static final String API_KEY = "d3364bf32ff64536f7bedcdecc9f1f524f3aa4a8b37c55f1cd3802b6f08bb83d";
    
    private final OkHttpClient client;
    private final ExecutorService executor;
    
    public SerpApiService() {
        this.client = new OkHttpClient();
        this.executor = Executors.newFixedThreadPool(2);
    }
    
    public interface ImageCallback {
        void onImageUrlReceived(String imageUrl);
        void onError(String error);
    }
    
    public void getFoodImage(String foodName, ImageCallback callback) {
        executor.execute(() -> {
            try {
                // Build the search query - focus on Filipino food images
                String query = foodName + " Filipino food recipe";
                
                // Create the request URL
                HttpUrl url = HttpUrl.parse(SERPAPI_BASE_URL).newBuilder()
                        .addQueryParameter("engine", "google_images")
                        .addQueryParameter("q", query)
                        .addQueryParameter("api_key", API_KEY)
                        .addQueryParameter("num", "5") // Get top 5 results
                        .addQueryParameter("safe", "active") // Safe search
                        .addQueryParameter("tbm", "isch") // Image search
                        .build();
                Log.d(TAG, "Requesting image for: " + foodName + " URL: " + url);
                
                Request request = new Request.Builder()
                        .url(url)
                        .build();
                
                try (Response response = client.newCall(request).execute()) {
                    if (!response.isSuccessful()) {
                        Log.e(TAG, "SerpApi request failed: " + response.code() + " " + response.message());
                        callback.onError("Failed to fetch image: " + response.code());
                        return;
                    }
                    
                    String responseBody = response.body().string();
                    Log.d(TAG, "SerpApi response received for: " + foodName);
                    
                    // Parse the JSON response
                    JSONObject jsonResponse = new JSONObject(responseBody);
                    JSONArray imagesResults = jsonResponse.optJSONArray("images_results");
                    
                    if (imagesResults != null && imagesResults.length() > 0) {
                        // Get the first (best) image result
                        JSONObject firstImage = imagesResults.getJSONObject(0);
                        String imageUrl = firstImage.optString("original", "");
                        
                        if (!imageUrl.isEmpty()) {
                            Log.d(TAG, "Found image URL for " + foodName + ": " + imageUrl);
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
                Log.e(TAG, "Error fetching image for " + foodName, e);
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
