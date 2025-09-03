package com.example.nutrisaur11;

import android.util.Log;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class PicsumImageService {
    private static final String TAG = "PicsumImageService";
    private static final String PICSUM_BASE_URL = "https://picsum.photos";
    
    private final ExecutorService executor;
    
    public PicsumImageService() {
        this.executor = Executors.newFixedThreadPool(2);
    }
    
    public interface ImageCallback {
        void onImageUrlReceived(String imageUrl);
        void onError(String error);
    }
    
    /**
     * Get a food image using Lorem Picsum API
     * Uses the food name as a seed to get consistent images for the same food
     */
    public void getFoodImage(String foodName, ImageCallback callback) {
        executor.execute(() -> {
            try {
                // Create a seed from the food name to get consistent images
                String seed = foodName.toLowerCase().replaceAll("[^a-z0-9]", "");
                if (seed.isEmpty()) {
                    seed = "food";
                }
                
                // Generate image URL with specific dimensions for food cards
                // Using 400x300 for good aspect ratio for food images
                String imageUrl = PICSUM_BASE_URL + "/seed/" + seed + "/400/300";
                
                Log.d(TAG, "Generated Picsum image URL for " + foodName + ": " + imageUrl);
                callback.onImageUrlReceived(imageUrl);
                
            } catch (Exception e) {
                Log.e(TAG, "Error generating Picsum image URL for " + foodName, e);
                callback.onError("Error: " + e.getMessage());
            }
        });
    }
    
    /**
     * Get a random food image (for variety)
     */
    public void getRandomFoodImage(String foodName, ImageCallback callback) {
        executor.execute(() -> {
            try {
                // Generate random image URL with food-appropriate dimensions
                String imageUrl = PICSUM_BASE_URL + "/400/300?random=" + System.currentTimeMillis();
                
                Log.d(TAG, "Generated random Picsum image URL for " + foodName + ": " + imageUrl);
                callback.onImageUrlReceived(imageUrl);
                
            } catch (Exception e) {
                Log.e(TAG, "Error generating random Picsum image URL for " + foodName, e);
                callback.onError("Error: " + e.getMessage());
            }
        });
    }
    
    /**
     * Get a specific food image by ID (for consistent results)
     */
    public void getFoodImageById(String foodName, int imageId, ImageCallback callback) {
        executor.execute(() -> {
            try {
                // Generate image URL with specific ID
                String imageUrl = PICSUM_BASE_URL + "/id/" + imageId + "/400/300";
                
                Log.d(TAG, "Generated Picsum image URL with ID " + imageId + " for " + foodName + ": " + imageUrl);
                callback.onImageUrlReceived(imageUrl);
                
            } catch (Exception e) {
                Log.e(TAG, "Error generating Picsum image URL with ID for " + foodName, e);
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
