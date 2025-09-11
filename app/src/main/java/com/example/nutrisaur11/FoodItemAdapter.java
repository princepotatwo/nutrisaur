package com.example.nutrisaur11;

import android.content.Context;
import android.content.Intent;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.BaseAdapter;
import android.widget.ImageView;
import android.widget.TextView;
import java.util.List;

public class FoodItemAdapter extends BaseAdapter {
    private Context context;
    private List<FoodItem> foodItems;
    private LayoutInflater inflater;
    private AddedFoodManager addedFoodManager;
    private CalorieTracker calorieTracker;
    private String currentMealCategory;
    private boolean hideAddButton = false; // Flag to hide add/remove buttons
    
    public FoodItemAdapter(Context context, List<FoodItem> foodItems) {
        this.context = context;
        this.foodItems = foodItems;
        this.inflater = LayoutInflater.from(context);
        this.addedFoodManager = new AddedFoodManager(context);
        this.calorieTracker = new CalorieTracker(context);
    }
    
    public void setMealCategory(String mealCategory) {
        this.currentMealCategory = mealCategory;
    }
    
    public void setHideAddButton(boolean hide) {
        this.hideAddButton = hide;
    }
    
    @Override
    public int getCount() {
        return foodItems.size();
    }
    
    @Override
    public Object getItem(int position) {
        return foodItems.get(position);
    }
    
    @Override
    public long getItemId(int position) {
        return position;
    }
    
    @Override
    public View getView(int position, View convertView, ViewGroup parent) {
        ViewHolder holder;
        
        if (convertView == null) {
            convertView = inflater.inflate(R.layout.item_food, parent, false);
            holder = new ViewHolder();
            holder.foodEmoji = convertView.findViewById(R.id.food_emoji);
            holder.foodName = convertView.findViewById(R.id.food_name);
            holder.foodCalories = convertView.findViewById(R.id.food_calories);
            holder.minusButton = convertView.findViewById(R.id.minus_button);
            holder.plusButton = convertView.findViewById(R.id.plus_button);
            convertView.setTag(holder);
        } else {
            holder = (ViewHolder) convertView.getTag();
        }
        
        FoodItem foodItem = foodItems.get(position);
        
        // Set food emoji based on food name
        holder.foodEmoji.setText(getFoodEmoji(foodItem.getName()));
        
        // Set food name
        holder.foodName.setText(foodItem.getName());
        
        // Set calories and weight
        holder.foodCalories.setText(foodItem.getCaloriesText());
        
        // Show/hide buttons based on flag
        if (hideAddButton) {
            holder.minusButton.setVisibility(View.GONE);
            holder.plusButton.setVisibility(View.GONE);
        } else {
            holder.minusButton.setVisibility(View.VISIBLE);
            holder.plusButton.setVisibility(View.VISIBLE);
            
            // Update button states based on whether food is added
            boolean isAdded = addedFoodManager.isAdded(foodItem);
            updateButtonStates(holder, isAdded);
            
            // Set click listeners
            holder.minusButton.setOnClickListener(v -> {
                onMinusButtonClicked(foodItem, holder);
            });
            
            holder.plusButton.setOnClickListener(v -> {
                onPlusButtonClicked(foodItem, holder);
            });
        }
        
        // Set click listener for entire food item (except buttons)
        convertView.setOnClickListener(v -> {
            onFoodItemClicked(foodItem);
        });
        
        return convertView;
    }
    
    private String getFoodEmoji(String foodName) {
        String name = foodName.toLowerCase();
        
        // Breakfast foods
        if (name.contains("oatmeal") || name.contains("oats")) return "🥣";
        if (name.contains("yogurt") || name.contains("greek")) return "🥛";
        if (name.contains("egg") || name.contains("scrambled")) return "🍳";
        if (name.contains("toast") || name.contains("bread")) return "🍞";
        if (name.contains("banana")) return "🍌";
        if (name.contains("almond") || name.contains("nut")) return "🥜";
        if (name.contains("smoothie") || name.contains("bowl")) return "🥤";
        if (name.contains("avocado")) return "🥑";
        
        // Lunch/Dinner foods
        if (name.contains("chicken") || name.contains("poultry")) return "🍗";
        if (name.contains("salmon") || name.contains("fish")) return "🐟";
        if (name.contains("beef") || name.contains("steak") || name.contains("meat")) return "🥩";
        if (name.contains("pork") || name.contains("bacon")) return "🥓";
        if (name.contains("turkey")) return "🦃";
        if (name.contains("quinoa")) return "🌾";
        if (name.contains("rice")) return "🍚";
        if (name.contains("pasta") || name.contains("noodle")) return "🍝";
        if (name.contains("pizza")) return "🍕";
        if (name.contains("burger") || name.contains("sandwich")) return "🍔";
        if (name.contains("salad") || name.contains("lettuce")) return "🥗";
        if (name.contains("soup")) return "🍲";
        if (name.contains("curry")) return "🍛";
        
        // Vegetables
        if (name.contains("broccoli")) return "🥦";
        if (name.contains("carrot")) return "🥕";
        if (name.contains("tomato")) return "🍅";
        if (name.contains("onion")) return "🧅";
        if (name.contains("pepper") || name.contains("bell")) return "🫑";
        if (name.contains("mushroom")) return "🍄";
        if (name.contains("potato") || name.contains("sweet potato")) return "🥔";
        if (name.contains("corn")) return "🌽";
        if (name.contains("cucumber")) return "🥒";
        if (name.contains("spinach") || name.contains("leafy")) return "🥬";
        
        // Fruits
        if (name.contains("apple")) return "🍎";
        if (name.contains("orange")) return "🍊";
        if (name.contains("grape")) return "🍇";
        if (name.contains("strawberry") || name.contains("berry")) return "🍓";
        if (name.contains("cherry")) return "🍒";
        if (name.contains("peach")) return "🍑";
        if (name.contains("pineapple")) return "🍍";
        if (name.contains("watermelon")) return "🍉";
        if (name.contains("lemon") || name.contains("lime")) return "🍋";
        
        // Snacks
        if (name.contains("popcorn")) return "🍿";
        if (name.contains("chips") || name.contains("crisp")) return "🍟";
        if (name.contains("cookie") || name.contains("biscuit")) return "🍪";
        if (name.contains("cake") || name.contains("dessert")) return "🍰";
        if (name.contains("chocolate")) return "🍫";
        if (name.contains("candy") || name.contains("sweet")) return "🍬";
        if (name.contains("ice cream")) return "🍦";
        
        // Dairy
        if (name.contains("cheese")) return "🧀";
        if (name.contains("milk")) return "🥛";
        if (name.contains("butter")) return "🧈";
        
        // Grains
        if (name.contains("wheat") || name.contains("grain")) return "🌾";
        if (name.contains("cereal")) return "🥣";
        
        // Default food emoji
        return "🍽️";
    }
    
    private void updateButtonStates(ViewHolder holder, boolean isAdded) {
        if (isAdded) {
            holder.minusButton.setVisibility(View.VISIBLE);
            holder.plusButton.setVisibility(View.GONE);
        } else {
            holder.minusButton.setVisibility(View.GONE);
            holder.plusButton.setVisibility(View.VISIBLE);
        }
    }
    
    private void onMinusButtonClicked(FoodItem foodItem, ViewHolder holder) {
        // Remove from added foods
        addedFoodManager.removeFromAddedFoods(foodItem);
        
        // Remove from calorie tracking
        if (currentMealCategory != null) {
            calorieTracker.removeFoodFromMeal(currentMealCategory, foodItem);
        }
        
        updateButtonStates(holder, false);
        
        // Notify parent activity about calorie change
        notifyCalorieChange();
    }
    
    private void onPlusButtonClicked(FoodItem foodItem, ViewHolder holder) {
        // Add to added foods
        addedFoodManager.addToAddedFoods(foodItem);
        
        // Add to calorie tracking
        if (currentMealCategory != null) {
            // Get max calories from the current meal (this should be passed from the activity)
            int maxCalories = 500; // Default, should be passed from activity
            calorieTracker.addFoodToMeal(currentMealCategory, foodItem, maxCalories);
        }
        
        updateButtonStates(holder, true);
        
        // Notify parent activity about calorie change
        notifyCalorieChange();
    }
    
    private void notifyCalorieChange() {
        // This will be implemented to notify the parent activity
        // For now, we'll just log the change
        if (currentMealCategory != null) {
            CalorieTracker.MealCalories mealCalories = calorieTracker.getMealCalories(currentMealCategory);
            if (mealCalories != null) {
                android.util.Log.d("FoodItemAdapter", "Calories updated for " + currentMealCategory + ": " + mealCalories.getCalorieText());
            }
        }
    }

    private void onFoodItemClicked(FoodItem foodItem) {
        // Open full screen details activity
        Intent intent = new Intent(context, FoodDetailsActivity.class);
        intent.putExtra("food_item", foodItem);
        context.startActivity(intent);
    }
    
    private static class ViewHolder {
        TextView foodEmoji;
        TextView foodName;
        TextView foodCalories;
        ImageView minusButton;
        ImageView plusButton;
    }
}
