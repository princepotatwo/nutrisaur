package com.example.nutrisaur11;

import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.util.Log;
import android.widget.ImageView;
import android.widget.ProgressBar;
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
     * Load food image using local drawable resources
     */
    public void loadFoodImage(String foodName, ImageView imageView, ProgressBar progressBar) {
        if (foodName == null || foodName.trim().isEmpty()) {
            Log.w(TAG, "Food name is null or empty");
            return;
        }
        
        // Load local image based on food name
        int drawableResource = getDrawableResourceForFood(foodName);
        imageView.setImageResource(drawableResource);
        Log.d(TAG, "Loaded local image for: " + foodName + " (resource: " + drawableResource + ")");
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
