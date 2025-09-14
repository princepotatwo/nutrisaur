package com.example.nutrisaur11;

import android.app.Dialog;
import android.content.Context;
import android.os.Bundle;
import android.view.View;
import android.view.Window;
import android.widget.Button;
import android.widget.ImageView;
import android.widget.TextView;
import android.widget.Toast;
import androidx.annotation.NonNull;

public class FoodDetailsDialog extends Dialog {
    private static final String TAG = "FoodDetailsDialog";
    
    private FoodItem foodItem;
    private Context context;
    private FavoritesManager favoritesManager;
    
    // UI Elements
    private ImageView closeButton;
    private ImageView foodImage;
    private TextView foodName;
    private TextView caloriesText;
    private TextView servingSizeText;
    private TextView descriptionText;
    private TextView ingredientsText;
    private TextView nutritionFactsText;
    private Button addToMealButton;
    private Button addToFavoritesButton;
    
    public FoodDetailsDialog(@NonNull Context context, FoodItem foodItem) {
        super(context);
        this.context = context;
        this.foodItem = foodItem;
        this.favoritesManager = new FavoritesManager(context);
    }
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        requestWindowFeature(Window.FEATURE_NO_TITLE);
        setContentView(R.layout.dialog_food_details);
        
        initializeViews();
        setupClickListeners();
        populateFoodDetails();
    }
    
    private void initializeViews() {
        closeButton = findViewById(R.id.close_button);
        foodImage = findViewById(R.id.food_image);
        foodName = findViewById(R.id.food_name);
        caloriesText = findViewById(R.id.calories_text);
        servingSizeText = findViewById(R.id.serving_size_text);
        descriptionText = findViewById(R.id.description_text);
        ingredientsText = findViewById(R.id.ingredients_text);
        nutritionFactsText = findViewById(R.id.nutrition_facts_text);
        addToMealButton = findViewById(R.id.add_to_meal_button);
        addToFavoritesButton = findViewById(R.id.add_to_favorites_button);
    }
    
    private void setupClickListeners() {
        closeButton.setOnClickListener(v -> {
            // Trigger calorie calculation when closing dialog
            triggerCalorieCalculation();
            dismiss();
        });
        
        addToMealButton.setOnClickListener(v -> {
            addToMeal();
        });
        
        addToFavoritesButton.setOnClickListener(v -> {
            toggleFavorite();
        });
    }
    
    private void populateFoodDetails() {
        if (foodItem == null) return;
        
        // Set basic food information
        foodName.setText(foodItem.getName());
        caloriesText.setText(foodItem.getCalories() + " kcal");
        servingSizeText.setText(foodItem.getWeight() + " " + foodItem.getUnit());
        
        // Set description based on food type
        String description = getFoodDescription(foodItem.getName());
        descriptionText.setText(description);
        
        // Set ingredients
        String ingredients = getFoodIngredients(foodItem.getName());
        ingredientsText.setText(ingredients);
        
        // Set nutrition facts
        String nutritionFacts = getNutritionFacts(foodItem);
        nutritionFactsText.setText(nutritionFacts);
        
        // Set favorite button state
        updateFavoriteButton();
    }
    
    private String getFoodDescription(String foodName) {
        // Generate descriptions based on food type
        if (foodName.toLowerCase().contains("chicken")) {
            return "Lean protein source rich in essential amino acids. Perfect for muscle building and weight management. Low in saturated fat and high in protein.";
        } else if (foodName.toLowerCase().contains("salmon")) {
            return "Excellent source of omega-3 fatty acids and high-quality protein. Supports heart health and brain function. Rich in vitamin D and B12.";
        } else if (foodName.toLowerCase().contains("oatmeal")) {
            return "Whole grain breakfast option high in fiber and complex carbohydrates. Helps maintain stable blood sugar levels and provides sustained energy.";
        } else if (foodName.toLowerCase().contains("yogurt")) {
            return "Probiotic-rich dairy product that supports gut health. High in protein and calcium. Choose Greek yogurt for extra protein content.";
        } else if (foodName.toLowerCase().contains("rice")) {
            return "Staple carbohydrate source providing energy. Brown rice offers more fiber and nutrients compared to white rice.";
        } else if (foodName.toLowerCase().contains("vegetables")) {
            return "Nutrient-dense foods rich in vitamins, minerals, and antioxidants. Low in calories and high in fiber. Essential for overall health.";
        } else if (foodName.toLowerCase().contains("quinoa")) {
            return "Complete protein source containing all essential amino acids. High in fiber, iron, and magnesium. Gluten-free superfood.";
        } else if (foodName.toLowerCase().contains("eggs")) {
            return "Complete protein source with all essential amino acids. Rich in choline, vitamin D, and B12. Versatile and nutrient-dense.";
        } else if (foodName.toLowerCase().contains("nuts")) {
            return "Healthy fat source rich in omega-3 fatty acids, vitamin E, and magnesium. Good for heart health and brain function.";
        } else if (foodName.toLowerCase().contains("fruit")) {
            return "Natural source of vitamins, minerals, and antioxidants. High in fiber and water content. Supports immune system and digestion.";
        } else {
            return "Nutritious food option that fits well into a balanced diet. Provides essential nutrients for optimal health and wellness.";
        }
    }
    
    private String getFoodIngredients(String foodName) {
        // Generate ingredients based on food type
        if (foodName.toLowerCase().contains("chicken")) {
            return "• Chicken breast\n• Olive oil\n• Salt\n• Black pepper\n• Herbs (optional)";
        } else if (foodName.toLowerCase().contains("salmon")) {
            return "• Salmon fillet\n• Lemon\n• Dill\n• Salt\n• Black pepper\n• Olive oil";
        } else if (foodName.toLowerCase().contains("oatmeal")) {
            return "• Rolled oats\n• Water or milk\n• Berries (optional)\n• Honey (optional)\n• Nuts (optional)";
        } else if (foodName.toLowerCase().contains("yogurt")) {
            return "• Milk\n• Live cultures\n• Natural flavoring\n• No artificial additives";
        } else if (foodName.toLowerCase().contains("rice")) {
            return "• Rice grains\n• Water\n• Salt (optional)";
        } else if (foodName.toLowerCase().contains("vegetables")) {
            return "• Mixed vegetables\n• Olive oil\n• Salt\n• Herbs\n• Spices";
        } else if (foodName.toLowerCase().contains("quinoa")) {
            return "• Quinoa grains\n• Water\n• Salt\n• Olive oil (optional)";
        } else if (foodName.toLowerCase().contains("eggs")) {
            return "• Fresh eggs\n• Butter or oil\n• Salt\n• Black pepper";
        } else if (foodName.toLowerCase().contains("nuts")) {
            return "• Mixed nuts\n• No added salt\n• No artificial flavors";
        } else if (foodName.toLowerCase().contains("fruit")) {
            return "• Fresh fruit\n• No added sugar\n• No preservatives";
        } else {
            return "• Natural ingredients\n• No artificial additives\n• Fresh and wholesome";
        }
    }
    
    private String getNutritionFacts(FoodItem foodItem) {
        // Calculate approximate nutrition facts based on calories
        int calories = foodItem.getCalories();
        int protein = (int) (calories * 0.25 / 4); // 25% protein, 4 cal/g
        int carbs = (int) (calories * 0.45 / 4);   // 45% carbs, 4 cal/g
        int fat = (int) (calories * 0.30 / 9);     // 30% fat, 9 cal/g
        int fiber = (int) (calories * 0.1 / 2);    // Approximate fiber
        
        return String.format(
            "Nutrition Facts (per %d %s):\n\n" +
            "Calories: %d kcal\n" +
            "Protein: %d g\n" +
            "Carbohydrates: %d g\n" +
            "Fat: %d g\n" +
            "Fiber: %d g\n" +
            "Sodium: %d mg\n" +
            "Sugar: %d g",
            foodItem.getWeight(),
            foodItem.getUnit(),
            calories,
            protein,
            carbs,
            fat,
            fiber,
            (int) (calories * 0.02), // Approximate sodium
            (int) (calories * 0.15)  // Approximate sugar
        );
    }
    
    private void addToMeal() {
        // Add to added foods
        AddedFoodManager addedFoodManager = new AddedFoodManager(context);
        addedFoodManager.addToAddedFoods(foodItem);
        
        Toast.makeText(context, "Added " + foodItem.getName() + " to meal", Toast.LENGTH_SHORT).show();
        dismiss();
    }
    
    private void triggerCalorieCalculation() {
        // Notify parent activity to refresh calorie data
        if (context instanceof FoodActivity) {
            ((FoodActivity) context).refreshCalorieData();
        } else if (context instanceof FoodLoggingActivity) {
            ((FoodLoggingActivity) context).refreshCalorieData();
        }
    }
    
    private void toggleFavorite() {
        // TODO: Implement favorite toggle functionality
        boolean isFavorite = isFoodFavorite();
        if (isFavorite) {
            removeFromFavorites();
            Toast.makeText(context, "Removed from favorites", Toast.LENGTH_SHORT).show();
        } else {
            addToFavorites();
            Toast.makeText(context, "Added to favorites", Toast.LENGTH_SHORT).show();
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
            addToFavoritesButton.setBackgroundResource(R.drawable.button_secondary_background);
        } else {
            addToFavoritesButton.setText("Add to Favorites");
            addToFavoritesButton.setBackgroundResource(R.drawable.button_primary_background);
        }
    }
}
