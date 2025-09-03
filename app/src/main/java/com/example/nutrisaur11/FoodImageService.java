package com.example.nutrisaur11;

import android.content.Context;
import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.util.Log;
import android.widget.ImageView;
import android.widget.ProgressBar;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.io.IOException;
import java.io.InputStream;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

import okhttp3.Call;
import okhttp3.Callback;
import okhttp3.MediaType;
import okhttp3.OkHttpClient;
import okhttp3.Request;
import okhttp3.RequestBody;
import okhttp3.Response;

public class FoodImageService {
    private static final String TAG = "FoodImageService";
    // Use your existing Railway deployment URL from Constants.java
    private static final String API_BASE_URL = "https://nutrisaur-production.up.railway.app/"; // Production Railway deployment
    private static final String IMAGE_SCRAPER_ENDPOINT = "api/food_image_scraper_simple.php";
    
    private final OkHttpClient client;
    private final ExecutorService executor;
    private final ImageCacheManager imageCacheManager;
    
    public FoodImageService() {
        this.client = new OkHttpClient();
        this.executor = Executors.newFixedThreadPool(3);
        this.imageCacheManager = ImageCacheManager.getInstance();
    }
    
    public interface ImageLoadCallback {
        void onImageLoaded(Bitmap bitmap);
        void onError(String error);
    }
    
    public interface ImageUrlsCallback {
        void onUrlsReceived(String[] imageUrls);
        void onError(String error);
    }
    
    /**
     * Load food image using Google Images scraper API
     */
    public void loadFoodImage(String foodName, ImageView imageView, ProgressBar progressBar) {
        if (foodName == null || foodName.trim().isEmpty()) {
            Log.w(TAG, "Food name is null or empty");
            return;
        }
        
        // Check cache first
        String cacheKey = "food_" + foodName.toLowerCase().replaceAll("\\s+", "_");
        Bitmap cachedBitmap = imageCacheManager.getCachedImage(cacheKey);
        
        if (cachedBitmap != null) {
            Log.d(TAG, "Using cached image for: " + foodName);
            imageView.setImageBitmap(cachedBitmap);
            progressBar.setVisibility(android.view.View.GONE);
            return;
        }
        
        // Show loading state
        progressBar.setVisibility(android.view.View.VISIBLE);
        imageView.setImageResource(R.drawable.default_food_image);
        
        // Get image URLs from scraper API
        getFoodImageUrls(foodName, new ImageUrlsCallback() {
            @Override
            public void onUrlsReceived(String[] imageUrls) {
                if (imageUrls.length > 0) {
                    // Load the first image
                    loadImageFromUrl(imageUrls[0], new ImageLoadCallback() {
                        @Override
                        public void onImageLoaded(Bitmap bitmap) {
                            // Cache the image
                            imageCacheManager.putImageInCache(cacheKey, bitmap);
                            
                            // Update UI on main thread
                            imageView.post(() -> {
                                imageView.setImageBitmap(bitmap);
                                progressBar.setVisibility(android.view.View.GONE);
                                Log.d(TAG, "Image loaded successfully for: " + foodName);
                            });
                        }
                        
                        @Override
                        public void onError(String error) {
                            Log.e(TAG, "Error loading image for " + foodName + ": " + error);
                            imageView.post(() -> {
                                imageView.setImageResource(R.drawable.ic_food_simple);
                                progressBar.setVisibility(android.view.View.GONE);
                            });
                        }
                    });
                } else {
                    Log.w(TAG, "No image URLs received for: " + foodName);
                    imageView.post(() -> {
                        imageView.setImageResource(R.drawable.ic_food_simple);
                        progressBar.setVisibility(android.view.View.GONE);
                    });
                }
            }
            
            @Override
            public void onError(String error) {
                Log.e(TAG, "Error getting image URLs for " + foodName + ": " + error);
                imageView.post(() -> {
                    imageView.setImageResource(R.drawable.ic_food_simple);
                    progressBar.setVisibility(android.view.View.GONE);
                });
            }
        });
    }
    
    /**
     * Get food image URLs from the scraper API
     */
    private void getFoodImageUrls(String foodName, ImageUrlsCallback callback) {
        executor.execute(() -> {
            try {
                // Build the API URL
                String apiUrl = API_BASE_URL + IMAGE_SCRAPER_ENDPOINT + "?query=" + 
                               java.net.URLEncoder.encode(foodName, "UTF-8") + "&max_results=3";
                
                Log.d(TAG, "Requesting image URLs from: " + apiUrl);
                
                Request request = new Request.Builder()
                        .url(apiUrl)
                        .get()
                        .build();
                
                try (Response response = client.newCall(request).execute()) {
                    if (!response.isSuccessful()) {
                        callback.onError("API request failed: " + response.code());
                        return;
                    }
                    
                    String responseBody = response.body().string();
                    JSONObject jsonResponse = new JSONObject(responseBody);
                    
                    if (jsonResponse.getBoolean("success")) {
                        JSONArray imagesArray = jsonResponse.getJSONArray("images");
                        String[] imageUrls = new String[imagesArray.length()];
                        
                        for (int i = 0; i < imagesArray.length(); i++) {
                            JSONObject imageObj = imagesArray.getJSONObject(i);
                            imageUrls[i] = imageObj.getString("image_url");
                        }
                        
                        callback.onUrlsReceived(imageUrls);
                    } else {
                        String errorMessage = jsonResponse.optString("message", "Unknown error");
                        callback.onError("API error: " + errorMessage);
                    }
                }
                
            } catch (Exception e) {
                Log.e(TAG, "Error getting image URLs", e);
                callback.onError("Network error: " + e.getMessage());
            }
        });
    }
    
    /**
     * Load image from URL
     */
    private void loadImageFromUrl(String imageUrl, ImageLoadCallback callback) {
        executor.execute(() -> {
            try {
                Log.d(TAG, "Loading image from URL: " + imageUrl);
                
                Request request = new Request.Builder()
                        .url(imageUrl)
                        .build();
                
                try (Response response = client.newCall(request).execute()) {
                    if (!response.isSuccessful()) {
                        callback.onError("Failed to load image: " + response.code());
                        return;
                    }
                    
                    InputStream inputStream = response.body().byteStream();
                    Bitmap bitmap = BitmapFactory.decodeStream(inputStream);
                    
                    if (bitmap != null) {
                        callback.onImageLoaded(bitmap);
                    } else {
                        callback.onError("Failed to decode image");
                    }
                }
                
            } catch (Exception e) {
                Log.e(TAG, "Error loading image from URL", e);
                callback.onError("Error loading image: " + e.getMessage());
            }
        });
    }
    
    /**
     * Preload food images in background
     */
    public void preloadFoodImages(String[] foodNames) {
        for (String foodName : foodNames) {
            if (foodName != null && !foodName.trim().isEmpty()) {
                String cacheKey = "food_" + foodName.toLowerCase().replaceAll("\\s+", "_");
                
                // Only preload if not already cached
                if (imageCacheManager.getCachedImage(cacheKey) == null) {
                    getFoodImageUrls(foodName, new ImageUrlsCallback() {
                        @Override
                        public void onUrlsReceived(String[] imageUrls) {
                            if (imageUrls.length > 0) {
                                loadImageFromUrl(imageUrls[0], new ImageLoadCallback() {
                                    @Override
                                    public void onImageLoaded(Bitmap bitmap) {
                                        imageCacheManager.putImageInCache(cacheKey, bitmap);
                                        Log.d(TAG, "Preloaded image for: " + foodName);
                                    }
                                    
                                    @Override
                                    public void onError(String error) {
                                        Log.w(TAG, "Failed to preload image for " + foodName + ": " + error);
                                    }
                                });
                            }
                        }
                        
                        @Override
                        public void onError(String error) {
                            Log.w(TAG, "Failed to get URLs for preloading " + foodName + ": " + error);
                        }
                    });
                }
            }
        }
    }
    
    /**
     * Clear all cached food images
     */
    public void clearCache() {
        imageCacheManager.clearCache();
        Log.d(TAG, "Food image cache cleared");
    }
    
    /**
     * Get cache statistics
     */
    public String getCacheStats() {
        return imageCacheManager.getCacheStats();
    }
    
    /**
     * Shutdown the service
     */
    public void shutdown() {
        if (executor != null && !executor.isShutdown()) {
            executor.shutdown();
        }
    }
}
