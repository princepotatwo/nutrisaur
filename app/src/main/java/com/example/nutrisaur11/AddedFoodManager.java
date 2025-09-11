package com.example.nutrisaur11;

import android.content.Context;
import android.content.SharedPreferences;
import com.google.gson.Gson;
import com.google.gson.reflect.TypeToken;
import java.lang.reflect.Type;
import java.util.ArrayList;
import java.util.List;

public class AddedFoodManager {
    private static final String PREFS_NAME = "added_food_prefs";
    private static final String KEY_ADDED_FOODS = "added_food_items";

    private SharedPreferences sharedPreferences;
    private Gson gson;

    public AddedFoodManager(Context context) {
        sharedPreferences = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE);
        gson = new Gson();
    }

    public void addToAddedFoods(FoodItem foodItem) {
        List<FoodItem> addedFoods = getAddedFoods();
        if (!isAdded(foodItem)) {
            addedFoods.add(foodItem);
            saveAddedFoods(addedFoods);
        }
    }

    public void removeFromAddedFoods(FoodItem foodItem) {
        List<FoodItem> addedFoods = getAddedFoods();
        // Remove by name for simplicity, assuming names are unique enough for added foods
        addedFoods.removeIf(item -> item.getName().equals(foodItem.getName()));
        saveAddedFoods(addedFoods);
    }

    public boolean isAdded(FoodItem foodItem) {
        List<FoodItem> addedFoods = getAddedFoods();
        return addedFoods.stream().anyMatch(item -> item.getName().equals(foodItem.getName()));
    }

    public List<FoodItem> getAddedFoods() {
        String json = sharedPreferences.getString(KEY_ADDED_FOODS, null);
        if (json == null) {
            return new ArrayList<>();
        }
        Type type = new TypeToken<List<FoodItem>>() {}.getType();
        return gson.fromJson(json, type);
    }

    private void saveAddedFoods(List<FoodItem> addedFoods) {
        String json = gson.toJson(addedFoods);
        sharedPreferences.edit().putString(KEY_ADDED_FOODS, json).apply();
    }
}
