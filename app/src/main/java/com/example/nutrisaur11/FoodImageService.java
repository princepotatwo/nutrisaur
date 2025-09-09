package com.example.nutrisaur11;

import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.util.Log;
import android.widget.ImageView;
import android.widget.ProgressBar;
import android.content.res.Resources;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

/**
 * Simple Food Image Service - Uses local drawable resources only
 * No API calls, no scraping, no network requests, no caching
 */
public class FoodImageService {
    private static final String TAG = "FoodImageService";
    private final ExecutorService executor;

    public FoodImageService() {
        this.executor = Executors.newFixedThreadPool(2);
        Log.d(TAG, "FoodImageService initialized - Local images only");
    }

    /**
     * Load food image using local drawable resources with proper scaling
     */
    public void loadFoodImage(String foodName, ImageView imageView, ProgressBar progressBar) {
        if (foodName == null || foodName.trim().isEmpty()) {
            Log.w(TAG, "Food name is null or empty");
            return;
        }
        
        // Load local image based on food name with proper scaling
        int drawableResource = getDrawableResourceForFood(foodName);
        
        // Load and scale the bitmap properly to prevent OOM
        executor.execute(() -> {
            try {
                Bitmap scaledBitmap = loadScaledBitmap(imageView.getContext().getResources(), drawableResource, imageView);
                if (scaledBitmap != null) {
                    imageView.post(() -> {
                        imageView.setImageBitmap(scaledBitmap);
                        if (progressBar != null) {
                            progressBar.setVisibility(android.view.View.GONE);
                        }
                    });
                } else {
                    // Fallback to setImageResource if scaling fails
                    imageView.post(() -> {
                        imageView.setImageResource(drawableResource);
                        if (progressBar != null) {
                            progressBar.setVisibility(android.view.View.GONE);
                        }
                    });
                }
                Log.d(TAG, "Loaded local image for: " + foodName + " (resource: " + drawableResource + ")");
            } catch (Exception e) {
                Log.e(TAG, "Error loading image for " + foodName + ": " + e.getMessage());
                imageView.post(() -> {
                    imageView.setImageResource(drawableResource);
                    if (progressBar != null) {
                        progressBar.setVisibility(android.view.View.GONE);
                    }
                });
            }
        });
    }

    /**
     * Get drawable resource ID based on food name
     */
    private int getDrawableResourceForFood(String foodName) {
        String lowerFoodName = foodName.toLowerCase();
        
        // Map food names to drawable resources
        if (lowerFoodName.contains("adobo")) {
            return R.drawable.adobo;
        } else if (lowerFoodName.contains("sinigang")) {
            return R.drawable.sinigang_na_baboy;
        } else if (lowerFoodName.contains("lechon")) {
            return R.drawable.lechon;
        } else if (lowerFoodName.contains("pancit")) {
            return R.drawable.pancit_sotanghon;
        } else if (lowerFoodName.contains("tinola")) {
            return R.drawable.tinola;
        } else if (lowerFoodName.contains("tortang")) {
            return R.drawable.tortang_talong;
        } else if (lowerFoodName.contains("chicharon")) {
            return R.drawable.chicharon;
        } else if (lowerFoodName.contains("suman")) {
            return R.drawable.suman_sa_latik;
        } else if (lowerFoodName.contains("turon")) {
            return R.drawable.turon;
        } else if (lowerFoodName.contains("bibingka")) {
            return R.drawable.ube_bibingka;
        } else if (lowerFoodName.contains("halaya")) {
            return R.drawable.ube_halaya;
        } else if (lowerFoodName.contains("empanada")) {
            return R.drawable.vigan_empanada;
        } else if (lowerFoodName.contains("ukoy")) {
            return R.drawable.ukoy;
        } else if (lowerFoodName.contains("tupig")) {
            return R.drawable.tupig;
        } else if (lowerFoodName.contains("tokneneng")) {
            return R.drawable.tokneneng;
        } else if (lowerFoodName.contains("tocilog")) {
            return R.drawable.tocilog;
        } else if (lowerFoodName.contains("bangus")) {
            return R.drawable.tinolang_bangus;
        } else if (lowerFoodName.contains("tinapa")) {
            return R.drawable.tinapa;
        } else if (lowerFoodName.contains("pork")) {
            return R.drawable.sweet_sour_pork;
        } else if (lowerFoodName.contains("fish")) {
            return R.drawable.sweet_and_sour_fish;
        } else if (lowerFoodName.contains("milk")) {
            return R.drawable.soya_milk;
        } else if (lowerFoodName.contains("sorbetes")) {
            return R.drawable.sorbetes;
        } else if (lowerFoodName.contains("squid")) {
            return R.drawable.squid_balls;
        } else {
            // Default image for unmatched foods
            return R.drawable.default_img;
        }
    }
    
    /**
     * Load and scale bitmap to prevent OOM crashes
     */
    private Bitmap loadScaledBitmap(Resources resources, int drawableResource, ImageView imageView) {
        try {
            // Get the target size from the ImageView
            int targetWidth = imageView.getWidth();
            int targetHeight = imageView.getHeight();
            
            // If ImageView doesn't have dimensions yet, use reasonable defaults
            if (targetWidth <= 0 || targetHeight <= 0) {
                targetWidth = 300; // Default width
                targetHeight = 200; // Default height
            }
            
            // First, get the image dimensions without loading the full bitmap
            BitmapFactory.Options options = new BitmapFactory.Options();
            options.inJustDecodeBounds = true;
            BitmapFactory.decodeResource(resources, drawableResource, options);
            
            int imageWidth = options.outWidth;
            int imageHeight = options.outHeight;
            
            // Calculate the sample size to scale down the image
            int sampleSize = calculateInSampleSize(imageWidth, imageHeight, targetWidth, targetHeight);
            
            // Now load the bitmap with the calculated sample size
            options.inJustDecodeBounds = false;
            options.inSampleSize = sampleSize;
            options.inPreferredConfig = Bitmap.Config.RGB_565; // Use less memory
            options.inDither = false;
            options.inPurgeable = true;
            options.inInputShareable = true;
            
            Bitmap bitmap = BitmapFactory.decodeResource(resources, drawableResource, options);
            
            if (bitmap != null) {
                Log.d(TAG, "Scaled bitmap: " + bitmap.getWidth() + "x" + bitmap.getHeight() + 
                      " (sample size: " + sampleSize + ")");
            }
            
            return bitmap;
        } catch (OutOfMemoryError e) {
            Log.e(TAG, "OutOfMemoryError loading bitmap: " + e.getMessage());
            return null;
        } catch (Exception e) {
            Log.e(TAG, "Error loading scaled bitmap: " + e.getMessage());
            return null;
        }
    }
    
    /**
     * Calculate the sample size for bitmap scaling
     */
    private int calculateInSampleSize(int imageWidth, int imageHeight, int targetWidth, int targetHeight) {
        int sampleSize = 1;
        
        if (imageHeight > targetHeight || imageWidth > targetWidth) {
            final int halfHeight = imageHeight / 2;
            final int halfWidth = imageWidth / 2;
            
            // Calculate the largest sample size that is a power of 2 and keeps both
            // height and width larger than the requested height and width.
            while ((halfHeight / sampleSize) >= targetHeight && (halfWidth / sampleSize) >= targetWidth) {
                sampleSize *= 2;
            }
        }
        
        // Ensure sample size is at least 1 and not too large
        return Math.max(1, Math.min(sampleSize, 8));
    }

    /**
     * Preload food images (no-op for local images)
     */
    public void preloadFoodImages(String[] foodNames) {
        // No preloading needed for local images
        Log.d(TAG, "Preload requested for local images - no action needed");
    }

    /**
     * Shutdown executor
     */
    public void shutdown() {
        executor.shutdown();
    }
}
