package com.example.nutrisaur11;

import android.app.Service;
import android.content.Intent;
import android.os.IBinder;
import android.util.Log;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import java.util.List;
import java.util.ArrayList;
import java.util.Set;
import java.util.HashSet;

/**
 * Background service to preload food recommendations when the app starts
 * This ensures food cards are ready immediately when user opens FoodActivity
 */
public class FoodPreloadService extends Service {
    private static final String TAG = "FoodPreloadService";
    private ExecutorService executorService;
    private static List<FoodRecommendation> preloadedRecommendations = new ArrayList<>();
    private static Set<String> preloadedFoodNames = new HashSet<>();
    private static boolean isPreloaded = false;
    private static boolean isPreloading = false;

    @Override
    public void onCreate() {
        super.onCreate();
        Log.d(TAG, "FoodPreloadService created");
        executorService = Executors.newFixedThreadPool(1);
    }

    @Override
    public int onStartCommand(Intent intent, int flags, int startId) {
        Log.d(TAG, "FoodPreloadService started");
        
        if (!isPreloaded && !isPreloading) {
            isPreloading = true;
            preloadFoodData();
        } else if (isPreloaded) {
            Log.d(TAG, "Food data already preloaded, stopping service");
            stopSelf();
        }
        
        return START_STICKY;
    }

    private void preloadFoodData() {
        executorService.execute(() -> {
            try {
                Log.d(TAG, "Starting background food preload");
                
                // Since we removed the old food recommendation system,
                // we'll create empty recommendations for now
                List<FoodRecommendation> newRecommendations = new ArrayList<>();
                
                if (newRecommendations != null && !newRecommendations.isEmpty()) {
                    synchronized (preloadedRecommendations) {
                        preloadedRecommendations.clear();
                        preloadedFoodNames.clear();
                        
                        for (FoodRecommendation recommendation : newRecommendations) {
                            preloadedRecommendations.add(recommendation);
                            preloadedFoodNames.add(recommendation.getFoodName().toLowerCase());
                        }
                        
                        isPreloaded = true;
                        isPreloading = false;
                        
                        Log.d(TAG, "Successfully preloaded " + newRecommendations.size() + " food recommendations");
                    }
                } else {
                    Log.e(TAG, "Failed to preload food data");
                    isPreloading = false;
                }
                
                // Stop the service after preloading
                stopSelf();
                
            } catch (Exception e) {
                Log.e(TAG, "Error preloading food data: " + e.getMessage());
                isPreloading = false;
                stopSelf();
            }
        });
    }

    public static List<FoodRecommendation> getPreloadedRecommendations() {
        synchronized (preloadedRecommendations) {
            return new ArrayList<>(preloadedRecommendations);
        }
    }

    public static Set<String> getPreloadedFoodNames() {
        synchronized (preloadedFoodNames) {
            return new HashSet<>(preloadedFoodNames);
        }
    }

    public static boolean isPreloaded() {
        return isPreloaded;
    }

    public static void clearPreloadedData() {
        synchronized (preloadedRecommendations) {
            preloadedRecommendations.clear();
            preloadedFoodNames.clear();
            isPreloaded = false;
            isPreloading = false;
        }
    }

    @Override
    public void onDestroy() {
        super.onDestroy();
        Log.d(TAG, "FoodPreloadService destroyed");
        if (executorService != null) {
            executorService.shutdown();
        }
    }

    @Override
    public IBinder onBind(Intent intent) {
        return null;
    }
}
