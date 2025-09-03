package com.example.nutrisaur11;

import android.util.Log;
import okhttp3.*;
import org.json.JSONArray;
import org.json.JSONObject;
import java.io.IOException;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class SpoonacularImageService {
    private static final String TAG = "SpoonacularImageService";
    private static final String SPOONACULAR_API_URL = "https://api.spoonacular.com/recipes";
    private static final String API_KEY = "be0fe391f9e74971b21563c0e9491f84";
    
    private final OkHttpClient client;
    private final ExecutorService executor;

    public SpoonacularImageService() {
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
                
                // Search for recipes
                String searchUrl = SPOONACULAR_API_URL + "/search?query=" + searchQuery + 
                                 "&apiKey=" + API_KEY + "&number=5&addRecipeInformation=true";
                Log.d(TAG, "Searching Spoonacular for: " + searchQuery);
                
                Request request = new Request.Builder()
                        .url(searchUrl)
                        .addHeader("User-Agent", "Nutrisaur11/1.0")
                        .build();

                try (Response response = client.newCall(request).execute()) {
                    if (!response.isSuccessful()) {
                        Log.e(TAG, "Spoonacular API request failed: " + response.code() + " " + response.message());
                        callback.onError("Failed to fetch image: " + response.code());
                        return;
                    }

                    String responseBody = response.body().string();
                    Log.d(TAG, "Spoonacular API response received for: " + foodName);

                    JSONObject jsonResponse = new JSONObject(responseBody);
                    JSONArray results = jsonResponse.optJSONArray("results");
                    String baseUri = jsonResponse.optString("baseUri", "");

                    if (results != null && results.length() > 0) {
                        // Get the first recipe's image
                        JSONObject firstRecipe = results.getJSONObject(0);
                        String imageFileName = firstRecipe.optString("image", "");

                        if (!imageFileName.isEmpty() && !baseUri.isEmpty()) {
                            String fullImageUrl = baseUri + imageFileName;
                            Log.d(TAG, "Found Spoonacular image URL for " + foodName + ": " + fullImageUrl);
                            callback.onImageUrlReceived(fullImageUrl);
                        } else {
                            Log.w(TAG, "No image URL found in first result for: " + foodName);
                            // Try random recipe search as fallback
                            tryRandomRecipeSearch(callback);
                        }
                    } else {
                        Log.w(TAG, "No recipes found for: " + foodName);
                        // Try random recipe search as fallback
                        tryRandomRecipeSearch(callback);
                    }
                }

            } catch (Exception e) {
                Log.e(TAG, "Error fetching Spoonacular image for " + foodName, e);
                callback.onError("Error: " + e.getMessage());
            }
        });
    }

    private void tryRandomRecipeSearch(ImageCallback callback) {
        try {
            // Try to get a random recipe
            String searchUrl = SPOONACULAR_API_URL + "/random?apiKey=" + API_KEY + "&number=1";
            Log.d(TAG, "Trying random recipe search");
            
            Request request = new Request.Builder()
                    .url(searchUrl)
                    .addHeader("User-Agent", "Nutrisaur11/1.0")
                    .build();

            try (Response response = client.newCall(request).execute()) {
                if (!response.isSuccessful()) {
                    callback.onError("Random recipe search failed: " + response.code());
                    return;
                }

                String responseBody = response.body().string();
                JSONObject jsonResponse = new JSONObject(responseBody);
                JSONArray recipes = jsonResponse.optJSONArray("recipes");

                if (recipes != null && recipes.length() > 0) {
                    JSONObject randomRecipe = recipes.getJSONObject(0);
                    String imageUrl = randomRecipe.optString("image", "");

                    if (!imageUrl.isEmpty()) {
                        Log.d(TAG, "Found random recipe fallback image: " + imageUrl);
                        callback.onImageUrlReceived(imageUrl);
                    } else {
                        callback.onError("No fallback image found");
                    }
                } else {
                    callback.onError("No random recipes available");
                }
            }
        } catch (Exception e) {
            Log.e(TAG, "Error in random recipe search", e);
            callback.onError("Error in fallback search: " + e.getMessage());
        }
    }

    private String cleanFoodNameForSearch(String foodName) {
        if (foodName == null || foodName.trim().isEmpty()) {
            return "chicken";
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
                // Get a random recipe
                String searchUrl = SPOONACULAR_API_URL + "/random?apiKey=" + API_KEY + "&number=1";
                
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
                    JSONArray recipes = jsonResponse.optJSONArray("recipes");

                    if (recipes != null && recipes.length() > 0) {
                        JSONObject randomRecipe = recipes.getJSONObject(0);
                        String imageUrl = randomRecipe.optString("image", "");

                        if (!imageUrl.isEmpty()) {
                            Log.d(TAG, "Found random recipe image: " + imageUrl);
                            callback.onImageUrlReceived(imageUrl);
                        } else {
                            callback.onError("No random image found");
                        }
                    } else {
                        callback.onError("No recipes available");
                    }
                }

            } catch (Exception e) {
                Log.e(TAG, "Error fetching random Spoonacular image", e);
                callback.onError("Error: " + e.getMessage());
            }
        });
    }

    public void shutdown() {
        executor.shutdown();
    }
}
