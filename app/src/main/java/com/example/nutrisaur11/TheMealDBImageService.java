package com.example.nutrisaur11;

import android.util.Log;
import okhttp3.*;
import org.json.JSONArray;
import org.json.JSONObject;
import java.io.IOException;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class TheMealDBImageService {
    private static final String TAG = "TheMealDBImageService";
    private static final String THEMEALDB_API_URL = "https://www.themealdb.com/api/json/v1/1";
    
    private final OkHttpClient client;
    private final ExecutorService executor;

    public TheMealDBImageService() {
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
                // Clean the food name for search
                String searchQuery = cleanFoodNameForSearch(foodName);
                
                // First try exact search
                String searchUrl = THEMEALDB_API_URL + "/search.php?s=" + searchQuery;
                Log.d(TAG, "Searching TheMealDB for: " + searchQuery);
                
                Request request = new Request.Builder()
                        .url(searchUrl)
                        .addHeader("User-Agent", "Nutrisaur11/1.0")
                        .build();

                try (Response response = client.newCall(request).execute()) {
                    if (!response.isSuccessful()) {
                        Log.e(TAG, "TheMealDB API request failed: " + response.code() + " " + response.message());
                        callback.onError("Failed to fetch image: " + response.code());
                        return;
                    }

                    String responseBody = response.body().string();
                    Log.d(TAG, "TheMealDB API response received for: " + foodName);

                    JSONObject jsonResponse = new JSONObject(responseBody);
                    JSONArray meals = jsonResponse.optJSONArray("meals");

                    if (meals != null && meals.length() > 0) {
                        // Get the first meal's image
                        JSONObject firstMeal = meals.getJSONObject(0);
                        String imageUrl = firstMeal.optString("strMealThumb", "");

                        if (!imageUrl.isEmpty()) {
                            Log.d(TAG, "Found TheMealDB image URL for " + foodName + ": " + imageUrl);
                            callback.onImageUrlReceived(imageUrl);
                        } else {
                            Log.w(TAG, "No image URL found in first result for: " + foodName);
                            // Try Filipino cuisine filter as fallback
                            tryFilipinoCuisineSearch(foodName, callback);
                        }
                    } else {
                        Log.w(TAG, "No meals found for: " + foodName);
                        // Try Filipino cuisine filter as fallback
                        tryFilipinoCuisineSearch(foodName, callback);
                    }
                }

            } catch (Exception e) {
                Log.e(TAG, "Error fetching TheMealDB image for " + foodName, e);
                callback.onError("Error: " + e.getMessage());
            }
        });
    }

    private void tryFilipinoCuisineSearch(String foodName, ImageCallback callback) {
        try {
            // Try to find a similar Filipino dish
            String searchUrl = THEMEALDB_API_URL + "/filter.php?a=Filipino";
            Log.d(TAG, "Trying Filipino cuisine search for: " + foodName);
            
            Request request = new Request.Builder()
                    .url(searchUrl)
                    .addHeader("User-Agent", "Nutrisaur11/1.0")
                    .build();

            try (Response response = client.newCall(request).execute()) {
                if (!response.isSuccessful()) {
                    callback.onError("Filipino cuisine search failed: " + response.code());
                    return;
                }

                String responseBody = response.body().string();
                JSONObject jsonResponse = new JSONObject(responseBody);
                JSONArray meals = jsonResponse.optJSONArray("meals");

                if (meals != null && meals.length() > 0) {
                    // Get a random Filipino dish image
                    int randomIndex = (int) (Math.random() * Math.min(meals.length(), 10));
                    JSONObject randomMeal = meals.getJSONObject(randomIndex);
                    String imageUrl = randomMeal.optString("strMealThumb", "");

                    if (!imageUrl.isEmpty()) {
                        Log.d(TAG, "Found Filipino cuisine fallback image for " + foodName + ": " + imageUrl);
                        callback.onImageUrlReceived(imageUrl);
                    } else {
                        callback.onError("No fallback image found");
                    }
                } else {
                    callback.onError("No Filipino dishes found");
                }
            }
        } catch (Exception e) {
            Log.e(TAG, "Error in Filipino cuisine search for " + foodName, e);
            callback.onError("Error in fallback search: " + e.getMessage());
        }
    }

    private String cleanFoodNameForSearch(String foodName) {
        if (foodName == null || foodName.trim().isEmpty()) {
            return "adobo";
        }
        
        // Clean the food name for search
        String cleaned = foodName.trim()
                .replaceAll("[^a-zA-Z0-9\\s]", "") // Remove special characters
                .replaceAll("\\s+", " ") // Replace multiple spaces with single space
                .trim();
        
        // If cleaned name is too short, use original
        if (cleaned.length() < 3) {
            cleaned = foodName.trim();
        }
        
        return cleaned;
    }

    public void getRandomFoodImage(ImageCallback callback) {
        executor.execute(() -> {
            try {
                // Get a random Filipino dish
                String searchUrl = THEMEALDB_API_URL + "/filter.php?a=Filipino";
                
                Request request = new Request.Builder()
                        .url(searchUrl)
                        .addHeader("User-Agent", "Nutrisaur11/1.0")
                        .build();

                try (Response response = client.newCall(request).execute()) {
                    if (!response.isSuccessful()) {
                        callback.onError("Failed to fetch random image: " + response.code());
                        return;
                    }

                    String responseBody = response.body().string();
                    JSONObject jsonResponse = new JSONObject(responseBody);
                    JSONArray meals = jsonResponse.optJSONArray("meals");

                    if (meals != null && meals.length() > 0) {
                        // Get a random Filipino dish
                        int randomIndex = (int) (Math.random() * meals.length());
                        JSONObject randomMeal = meals.getJSONObject(randomIndex);
                        String imageUrl = randomMeal.optString("strMealThumb", "");

                        if (!imageUrl.isEmpty()) {
                            Log.d(TAG, "Found random Filipino dish image: " + imageUrl);
                            callback.onImageUrlReceived(imageUrl);
                        } else {
                            callback.onError("No random image found");
                        }
                    } else {
                        callback.onError("No Filipino dishes available");
                    }
                }

            } catch (Exception e) {
                Log.e(TAG, "Error fetching random TheMealDB image", e);
                callback.onError("Error: " + e.getMessage());
            }
        });
    }

    public void shutdown() {
        executor.shutdown();
    }
}
