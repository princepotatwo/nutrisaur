package com.example.nutrisaur11;

import android.app.Activity;
import android.content.Intent;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.widget.Button;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import org.json.JSONObject;
import org.json.JSONArray;
import org.json.JSONException;
import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;

public class FoodDetailsActivity extends Activity {
    private static final String TAG = "FoodDetailsActivity";
    private static final String GEMINI_API_URL = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent";
    private static final String API_KEY = "AIzaSyAkX7Tpnsz-UnslwnmGytbnfc9XozoxtmU";

    private FoodItem foodItem;
    private FavoritesManager favoritesManager;
    private AddedFoodManager addedFoodManager;
    private CalorieTracker calorieTracker;
    private ExecutorService executorService;
    private FoodImageService foodImageService;
    private String currentMealCategory;
    private int maxCalories = 500;

    // UI Elements
    private ImageView backButton;
    private ImageView foodImage;
    private TextView foodName;
    private TextView foodCalories;
    private TextView foodServing;
    private TextView descriptionText;
    private TextView ingredientsText;
    private TextView nutritionFactsText;
    private Button addToMealButton;
    private Button addToFavoritesButton;
    private Button findAlternativesButton;
    private LinearLayout loadingLayout;
    private LinearLayout contentLayout;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_food_details);

        // Get food item from intent
        foodItem = (FoodItem) getIntent().getSerializableExtra("food_item");
        if (foodItem == null) {
            Toast.makeText(this, "Food item not found", Toast.LENGTH_SHORT).show();
            finish();
            return;
        }

        // Get meal category and max calories from intent
        currentMealCategory = getIntent().getStringExtra("meal_category");
        maxCalories = getIntent().getIntExtra("max_calories", 500);

        // Initialize managers and services
        favoritesManager = new FavoritesManager(this);
        addedFoodManager = new AddedFoodManager(this);
        calorieTracker = new CalorieTracker(this);
        executorService = Executors.newSingleThreadExecutor();
        foodImageService = new FoodImageService();

        // Initialize UI elements
        backButton = findViewById(R.id.back_button);
        foodImage = findViewById(R.id.food_image);
        foodName = findViewById(R.id.food_name);
        foodCalories = findViewById(R.id.food_calories);
        foodServing = findViewById(R.id.food_serving);
        descriptionText = findViewById(R.id.description_text);
        ingredientsText = findViewById(R.id.ingredients_text);
        nutritionFactsText = findViewById(R.id.nutrition_facts_text);
        addToMealButton = findViewById(R.id.add_to_meal_button);
        addToFavoritesButton = findViewById(R.id.add_to_favorites_button);
        findAlternativesButton = findViewById(R.id.find_alternatives_button);
        loadingLayout = findViewById(R.id.loading_layout);
        contentLayout = findViewById(R.id.content_layout);

        // Set basic food data
        foodName.setText(foodItem.getName());
        foodCalories.setText(foodItem.getCalories() + " kcal");
        foodServing.setText(foodItem.getServingSizeGrams() + foodItem.getUnit());
        
        // Load food image using FoodImageService
        loadFoodImage();

        // Show loading state and fetch details
        showLoadingState();
        fetchFoodDetailsFromGemini();

        // Set click listeners
        backButton.setOnClickListener(v -> finish());
        
        addToMealButton.setOnClickListener(v -> {
            addFoodToMeal();
        });

        // Favorites button logic
        updateFavoriteButton();
        addToFavoritesButton.setOnClickListener(v -> toggleFavorite());
        
        // Update add to meal button state
        updateAddToMealButton();
        
        // Find Alternatives button
        findAlternativesButton.setOnClickListener(v -> openAlternativesActivity());
    }

    private void toggleFavorite() {
        if (isFoodFavorite()) {
            removeFromFavorites();
            Toast.makeText(this, "Removed from favorites", Toast.LENGTH_SHORT).show();
        } else {
            addToFavorites();
            Toast.makeText(this, "Added to favorites", Toast.LENGTH_SHORT).show();
        }
        updateFavoriteButton();
    }

    private boolean isFoodFavorite() {
        return favoritesManager.isFavorite(foodItem);
    }

    private void addToFavorites() {
        favoritesManager.addToFavorites(foodItem);
    }

    private void removeFromFavorites() {
        favoritesManager.removeFromFavorites(foodItem);
    }

    private void updateFavoriteButton() {
        boolean isFavorite = isFoodFavorite();
        if (isFavorite) {
            addToFavoritesButton.setText("Remove from Favorites");
        } else {
            addToFavoritesButton.setText("Add to Favorites");
        }
        // Button styling is now handled by the selector drawable
    }

    @Override
    protected void onResume() {
        super.onResume();
        // Update button states when returning to this activity
        updateFavoriteButton();
        updateAddToMealButton();
    }

    @Override
    protected void onDestroy() {
        super.onDestroy();
        if (executorService != null) {
            executorService.shutdown();
        }
    }

    private void showLoadingState() {
        loadingLayout.setVisibility(View.VISIBLE);
        contentLayout.setVisibility(View.GONE);
    }

    private void showContentState() {
        loadingLayout.setVisibility(View.GONE);
        contentLayout.setVisibility(View.VISIBLE);
    }

    private void loadFoodImage() {
        if (foodImageService != null && foodItem != null) {
            // Set a placeholder first
            foodImage.setImageResource(R.drawable.veg);
            
            // Load the appropriate image using FoodImageService
            foodImageService.loadFoodImage(foodItem.getName(), foodImage, null);
        }
    }

    private void fetchFoodDetailsFromGemini() {
        executorService.execute(() -> {
            try {
                String prompt = createFoodDetailsPrompt();
                String response = callGeminiAPI(prompt);
                FoodDetails details = parseFoodDetailsResponse(response);
                
                runOnUiThread(() -> {
                    if (details != null) {
                        updateFoodDetails(details);
                    } else {
                        showFallbackDetails();
                    }
                    showContentState();
                });
                
            } catch (Exception e) {
                Log.e(TAG, "Error fetching food details: " + e.getMessage());
                runOnUiThread(() -> {
                    showFallbackDetails();
                    showContentState();
                });
            }
        });
    }

    private String createFoodDetailsPrompt() {
        StringBuilder prompt = new StringBuilder();
        prompt.append("You are a professional nutritionist. Provide detailed information about this food item.\n\n");
        prompt.append("FOOD ITEM: ").append(foodItem.getName()).append("\n");
        prompt.append("CALORIES: ").append(foodItem.getCalories()).append(" kcal per ").append(foodItem.getServingSizeGrams()).append(foodItem.getUnit()).append("\n\n");
        
        prompt.append("Please provide:\n");
        prompt.append("1. A brief, informative description (2-3 sentences)\n");
        prompt.append("2. Common ingredients (if it's a dish) or nutritional components\n");
        prompt.append("3. Detailed nutrition facts per serving\n\n");
        
        prompt.append("RESPOND IN THIS EXACT JSON FORMAT:\n");
        prompt.append("{\n");
        prompt.append("  \"description\": \"Brief description of the food item\",\n");
        prompt.append("  \"ingredients\": \"List of main ingredients or components\",\n");
        prompt.append("  \"nutrition_facts\": \"Detailed nutrition information per serving\"\n");
        prompt.append("}\n");
        
        return prompt.toString();
    }

    private String callGeminiAPI(String prompt) throws Exception {
        URL url = new URL(GEMINI_API_URL + "?key=" + API_KEY);
        HttpURLConnection conn = (HttpURLConnection) url.openConnection();
        conn.setRequestMethod("POST");
        conn.setRequestProperty("Content-Type", "application/json");
        conn.setDoOutput(true);
        
        // Create request body
        JSONObject requestBody = new JSONObject();
        JSONArray contents = new JSONArray();
        JSONObject content = new JSONObject();
        JSONArray parts = new JSONArray();
        JSONObject part = new JSONObject();
        part.put("text", prompt);
        parts.put(part);
        content.put("parts", parts);
        contents.put(content);
        requestBody.put("contents", contents);
        
        // Add generation config
        JSONObject generationConfig = new JSONObject();
        generationConfig.put("temperature", 0.7);
        generationConfig.put("maxOutputTokens", 1024);
        requestBody.put("generationConfig", generationConfig);
        
        // Send request
        OutputStream os = conn.getOutputStream();
        os.write(requestBody.toString().getBytes());
        os.flush();
        os.close();
        
        // Read response
        int responseCode = conn.getResponseCode();
        BufferedReader reader;
        if (responseCode >= 200 && responseCode < 300) {
            reader = new BufferedReader(new InputStreamReader(conn.getInputStream()));
        } else {
            reader = new BufferedReader(new InputStreamReader(conn.getErrorStream()));
        }
        
        StringBuilder response = new StringBuilder();
        String line;
        while ((line = reader.readLine()) != null) {
            response.append(line);
        }
        reader.close();
        
        Log.d(TAG, "Gemini API Response Code: " + responseCode);
        Log.d(TAG, "Gemini API Response: " + response.toString());
        
        if (responseCode != 200) {
            throw new Exception("Gemini API Error " + responseCode + ": " + response.toString());
        }
        
        return response.toString();
    }

    private FoodDetails parseFoodDetailsResponse(String response) {
        try {
            JSONObject jsonResponse = new JSONObject(response);
            JSONArray candidates = jsonResponse.getJSONArray("candidates");
            JSONObject candidate = candidates.getJSONObject(0);
            JSONObject content = candidate.getJSONObject("content");
            JSONArray parts = content.getJSONArray("parts");
            JSONObject part = parts.getJSONObject(0);
            String text = part.getString("text");
            
            // Extract JSON from the text response
            String jsonString = text;
            if (text.contains("```json")) {
                int start = text.indexOf("```json") + 7;
                int end = text.indexOf("```", start);
                if (end > start) {
                    jsonString = text.substring(start, end).trim();
                }
            } else {
                int jsonStart = text.indexOf("{");
                int jsonEnd = text.lastIndexOf("}") + 1;
                if (jsonStart != -1 && jsonEnd > jsonStart) {
                    jsonString = text.substring(jsonStart, jsonEnd);
                }
            }
            
            if (!jsonString.isEmpty()) {
                JSONObject foodData = new JSONObject(jsonString);
                return new FoodDetails(
                    foodData.optString("description", ""),
                    foodData.optString("ingredients", ""),
                    foodData.optString("nutrition_facts", "")
                );
            }
            
        } catch (Exception e) {
            Log.e(TAG, "Error parsing food details response: " + e.getMessage());
        }
        
        return null;
    }

    private void updateFoodDetails(FoodDetails details) {
        descriptionText.setText(details.getDescription());
        ingredientsText.setText(details.getIngredients());
        nutritionFactsText.setText(details.getNutritionFacts());
    }

    private void showFallbackDetails() {
        descriptionText.setText("A delicious and healthy option for your meal.");
        ingredientsText.setText("Ingredients: " + foodItem.getName() + ", water, spices.");
        nutritionFactsText.setText("Nutrition Facts (per serving):\n" +
                "Calories: " + foodItem.getCalories() + " kcal\n" +
                "Protein: 20g\n" +
                "Carbs: 30g\n" +
                "Fat: 10g\n" +
                "Fiber: 5g\n" +
                "Sodium: 100mg\n" +
                "Sugar: 5g");
    }

    // Inner class for food details
    private static class FoodDetails {
        private String description;
        private String ingredients;
        private String nutritionFacts;

        public FoodDetails(String description, String ingredients, String nutritionFacts) {
            this.description = description;
            this.ingredients = ingredients;
            this.nutritionFacts = nutritionFacts;
        }

        public String getDescription() { return description; }
        public String getIngredients() { return ingredients; }
        public String getNutritionFacts() { return nutritionFacts; }
    }
    
    private void openAlternativesActivity() {
        Intent intent = new Intent(this, FoodAlternativesActivity.class);
        intent.putExtra("food_item", foodItem);
        // Pass the current meal category if available
        if (currentMealCategory != null) {
            intent.putExtra("meal_category", currentMealCategory);
        }
        startActivity(intent);
    }
    
    /**
     * Add food to meal with proper meal category assignment and calorie tracking
     */
    private void addFoodToMeal() {
        try {
            // Set meal category on food item if not already set
            if (currentMealCategory != null && (foodItem.getMealCategory() == null || foodItem.getMealCategory().isEmpty())) {
                foodItem.setMealCategory(currentMealCategory);
            }
            
            // Add to added foods manager
            addedFoodManager.addToAddedFoods(foodItem);
            
            // Add to calorie tracking
            if (currentMealCategory != null) {
                calorieTracker.addFoodToMeal(currentMealCategory, foodItem, maxCalories);
            }
            
            // Update button state
            updateAddToMealButton();
            
            // Show success message
            Toast.makeText(this, "Added " + foodItem.getName() + " to " + 
                          (currentMealCategory != null ? currentMealCategory : "meal") + "!", 
                          Toast.LENGTH_SHORT).show();
            
            Log.d(TAG, "Successfully added " + foodItem.getName() + " to " + currentMealCategory);
            
        } catch (Exception e) {
            Log.e(TAG, "Error adding food to meal: " + e.getMessage());
            Toast.makeText(this, "Error adding food to meal", Toast.LENGTH_SHORT).show();
        }
    }
    
    /**
     * Remove food from meal
     */
    private void removeFoodFromMeal() {
        try {
            // Remove from added foods manager
            addedFoodManager.removeFromAddedFoods(foodItem);
            
            // Remove from calorie tracking
            if (currentMealCategory != null) {
                calorieTracker.removeFoodFromMeal(currentMealCategory, foodItem);
            }
            
            // Update button state
            updateAddToMealButton();
            
            // Show success message
            Toast.makeText(this, "Removed " + foodItem.getName() + " from " + 
                          (currentMealCategory != null ? currentMealCategory : "meal"), 
                          Toast.LENGTH_SHORT).show();
            
            Log.d(TAG, "Successfully removed " + foodItem.getName() + " from " + currentMealCategory);
            
        } catch (Exception e) {
            Log.e(TAG, "Error removing food from meal: " + e.getMessage());
            Toast.makeText(this, "Error removing food from meal", Toast.LENGTH_SHORT).show();
        }
    }
    
    /**
     * Update the add to meal button state based on whether food is already added
     */
    private void updateAddToMealButton() {
        if (addToMealButton == null) return;
        
        boolean isAdded = addedFoodManager.isAdded(foodItem);
        
        if (isAdded) {
            addToMealButton.setText("Remove from Meal");
            addToMealButton.setBackgroundResource(R.drawable.button_blue_selector);
            addToMealButton.setOnClickListener(v -> removeFoodFromMeal());
        } else {
            addToMealButton.setText("Add to Meal");
            addToMealButton.setBackgroundResource(R.drawable.button_green_selector);
            addToMealButton.setOnClickListener(v -> addFoodToMeal());
        }
    }
}
