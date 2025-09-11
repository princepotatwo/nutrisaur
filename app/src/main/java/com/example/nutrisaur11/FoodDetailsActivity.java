package com.example.nutrisaur11;

import android.app.Activity;
import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.ImageView;
import android.widget.TextView;
import android.widget.Toast;

public class FoodDetailsActivity extends Activity {
    private static final String TAG = "FoodDetailsActivity";

    private FoodItem foodItem;
    private FavoritesManager favoritesManager;
    private AddedFoodManager addedFoodManager;

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
    private Button findAlternativeButton;

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

        // Initialize managers
        favoritesManager = new FavoritesManager(this);
        addedFoodManager = new AddedFoodManager(this);

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
        findAlternativeButton = findViewById(R.id.find_alternative_button);

        // Set data
        foodName.setText(foodItem.getName());
        foodCalories.setText(foodItem.getCalories() + " kcal");
        foodServing.setText(foodItem.getServingSizeGrams() + foodItem.getUnit());
        // Set default veg image
        foodImage.setImageResource(R.drawable.veg);

        // Populate with mock details for now
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

        // Set click listeners
        backButton.setOnClickListener(v -> finish());
        
        findAlternativeButton.setOnClickListener(v -> {
            // TODO: Implement find alternative functionality
            Toast.makeText(this, "Find Alternative feature coming soon!", Toast.LENGTH_SHORT).show();
        });
        
        addToMealButton.setOnClickListener(v -> {
            // Only add to meal, no remove functionality
            addedFoodManager.addToAddedFoods(foodItem);
            Toast.makeText(this, "Added " + foodItem.getName() + " to meal!", Toast.LENGTH_SHORT).show();
        });

        // Favorites button logic
        updateFavoriteButton();
        addToFavoritesButton.setOnClickListener(v -> toggleFavorite());
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
    }
}
