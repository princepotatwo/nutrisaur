package com.example.nutrisaur11;

import android.util.Log;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class FallbackImageService {
    private static final String TAG = "FallbackImageService";
    private final ExecutorService executor;
    
    public FallbackImageService() {
        this.executor = Executors.newFixedThreadPool(2);
    }
    
    public interface ImageCallback {
        void onImageUrlReceived(String imageUrl);
        void onError(String error);
    }
    
    /**
     * Get a fallback food image using a simple placeholder service
     */
    public void getFoodImage(String foodName, ImageCallback callback) {
        executor.execute(() -> {
            try {
                // Use Lorem Picsum with food name as seed for consistent images
                String seed = foodName.toLowerCase().replaceAll("[^a-z0-9]", "");
                if (seed.isEmpty()) {
                    seed = "food";
                }
                
                // Generate a consistent image URL based on food name
                String imageUrl = "https://picsum.photos/seed/" + seed + "/400/300";
                
                Log.d(TAG, "Generated fallback image URL for " + foodName + ": " + imageUrl);
                callback.onImageUrlReceived(imageUrl);
                
            } catch (Exception e) {
                Log.e(TAG, "Error generating fallback image for " + foodName, e);
                callback.onError("Error: " + e.getMessage());
            }
        });
    }
    
    /**
     * Get a random fallback food image
     */
    public void getRandomFoodImage(String foodName, ImageCallback callback) {
        executor.execute(() -> {
            try {
                // Use timestamp for random images
                long timestamp = System.currentTimeMillis();
                String imageUrl = "https://picsum.photos/400/300?random=" + timestamp;
                
                Log.d(TAG, "Generated random fallback image URL for " + foodName + ": " + imageUrl);
                callback.onImageUrlReceived(imageUrl);
                
            } catch (Exception e) {
                Log.e(TAG, "Error generating random fallback image for " + foodName, e);
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
