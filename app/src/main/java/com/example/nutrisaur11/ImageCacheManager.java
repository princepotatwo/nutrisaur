package com.example.nutrisaur11;

import android.content.Context;
import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.util.LruCache;
import android.util.Log;
import okhttp3.*;
import java.io.IOException;
import java.io.InputStream;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class ImageCacheManager {
    private static final String TAG = "ImageCacheManager";
    private static ImageCacheManager instance;
    private final LruCache<String, Bitmap> memoryCache;
    private final OkHttpClient client;
    private final ExecutorService executor;
    
    private ImageCacheManager() {
        // For unlimited food recommendations, use count-based cache instead of size-based
        // Hold up to 50 images, remove oldest when full
        final int maxCacheCount = 50;
        
        Log.d(TAG, "Initializing ImageCacheManager - Max Images: " + maxCacheCount);
        
        memoryCache = new LruCache<String, Bitmap>(maxCacheCount) {
            @Override
            protected int sizeOf(String key, Bitmap bitmap) {
                // Return 1 for each item (count-based cache)
                return 1;
            }
            
            @Override
            protected void entryRemoved(boolean evicted, String key, Bitmap oldValue, Bitmap newValue) {
                if (evicted) {
                    Log.w(TAG, "Cache entry EVICTED (oldest): " + key + " (cache count: " + size() + "/" + maxSize() + ")");
                }
            }
        };
        
        this.client = new OkHttpClient();
        this.executor = Executors.newFixedThreadPool(3);
    }
    
    public static synchronized ImageCacheManager getInstance() {
        if (instance == null) {
            Log.d(TAG, "Creating new ImageCacheManager singleton instance");
            instance = new ImageCacheManager();
        } else {
            Log.d(TAG, "Returning existing ImageCacheManager singleton instance");
        }
        return instance;
    }
    
    public interface BitmapCallback {
        void onBitmapLoaded(Bitmap bitmap);
        void onError(String error);
    }
    
    public void loadImage(String imageUrl, BitmapCallback callback) {
        // Check if image is already in cache
        Bitmap cachedBitmap = memoryCache.get(imageUrl);
        if (cachedBitmap != null) {
            Log.d(TAG, "Image found in cache: " + imageUrl);
            callback.onBitmapLoaded(cachedBitmap);
            return;
        }
        
        // Load image from URL
        executor.execute(() -> {
            try {
                Log.d(TAG, "Loading image from URL: " + imageUrl);
                
                Request request = new Request.Builder()
                        .url(imageUrl)
                        .build();
                
                try (Response response = client.newCall(request).execute()) {
                    if (!response.isSuccessful()) {
                        Log.e(TAG, "Failed to load image: " + response.code() + " " + response.message());
                        callback.onError("Failed to load image: " + response.code());
                        return;
                    }
                    
                    InputStream inputStream = response.body().byteStream();
                    Bitmap bitmap = BitmapFactory.decodeStream(inputStream);
                    
                    if (bitmap != null) {
                        // Cache the bitmap
                        memoryCache.put(imageUrl, bitmap);
                        Log.d(TAG, "Image loaded and cached: " + imageUrl);
                        callback.onBitmapLoaded(bitmap);
                    } else {
                        Log.e(TAG, "Failed to decode bitmap from: " + imageUrl);
                        callback.onError("Failed to decode image");
                    }
                }
                
            } catch (Exception e) {
                Log.e(TAG, "Error loading image: " + imageUrl, e);
                callback.onError("Error loading image: " + e.getMessage());
            }
        });
    }
    
    public Bitmap getCachedImage(String cacheKey) {
        Bitmap cached = memoryCache.get(cacheKey);
        if (cached != null) {
            Log.d(TAG, "Cache HIT for key: " + cacheKey);
        } else {
            Log.d(TAG, "Cache MISS for key: " + cacheKey);
        }
        return cached;
    }
    
    public void putImageInCache(String key, Bitmap bitmap) {
        memoryCache.put(key, bitmap);
        Log.d(TAG, "Cached image with key: " + key);
    }
    
    public void clearCache() {
        memoryCache.evictAll();
        Log.d(TAG, "Image cache cleared");
    }
    
    public void logCacheStatus() {
        Log.d(TAG, "Cache Status - Images: " + memoryCache.size() + "/" + memoryCache.maxSize() + ", Hit Count: " + memoryCache.hitCount() + ", Miss Count: " + memoryCache.missCount());
    }
    
    public String getCacheStats() {
        return "Cache: " + memoryCache.size() + "/" + memoryCache.maxSize() + " images, " + 
               "Hit Rate: " + String.format("%.1f%%", (memoryCache.hitCount() * 100.0 / (memoryCache.hitCount() + memoryCache.missCount())));
    }
    
    public void shutdown() {
        if (executor != null && !executor.isShutdown()) {
            executor.shutdown();
        }
    }
}
