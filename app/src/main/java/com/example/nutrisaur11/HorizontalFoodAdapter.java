package com.example.nutrisaur11;

import android.content.Context;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import androidx.cardview.widget.CardView;
import android.util.Log;
import java.util.List;

public class HorizontalFoodAdapter extends RecyclerView.Adapter<HorizontalFoodAdapter.ViewHolder> {
    private static final String TAG = "HorizontalFoodAdapter";
    private List<FoodRecommendation> foods;
    private Context context;
    private FoodImageService foodImageService;
    private OnFoodClickListener listener;

    public interface OnFoodClickListener {
        void onFoodClick(FoodRecommendation food);
    }

    public HorizontalFoodAdapter(List<FoodRecommendation> foods, Context context, OnFoodClickListener listener) {
        this.foods = foods;
        this.context = context;
        this.listener = listener;
        this.foodImageService = new FoodImageService();
        Log.d(TAG, "HorizontalFoodAdapter created with " + foods.size() + " foods");
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(context).inflate(R.layout.item_food_horizontal, parent, false);
        return new ViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {
        FoodRecommendation food = foods.get(position);
        
        Log.d(TAG, "Binding food at position " + position + ": " + food.getFoodName());
        
        // Set food name (shortened for horizontal layout)
        String shortName = shortenFoodName(food.getFoodName());
        holder.foodName.setText(shortName);
        
        // Set calories
        holder.calories.setText(food.getCalories() + " cal");
        
        // Load image
        foodImageService.loadFoodImage(food.getFoodName(), holder.foodImage, null);
        
        // Set click listener
        holder.foodCard.setOnClickListener(v -> {
            if (listener != null) {
                listener.onFoodClick(food);
            }
        });
    }

    @Override
    public int getItemCount() {
        return foods.size();
    }

    private String shortenFoodName(String foodName) {
        if (foodName == null || foodName.trim().isEmpty()) {
            return "Food";
        }
        
        String cleaned = foodName.trim();
        
        // Remove parentheses and brackets
        cleaned = cleaned.replaceAll("\\([^)]*\\)", "");
        cleaned = cleaned.replaceAll("\\[[^]]*\\]", "");
        cleaned = cleaned.replaceAll("\\s+", " ");
        
        // Remove unnecessary words
        String[] wordsToRemove = {
            "Recipe", "Filipino", "Food", "Dish"
        };
        
        for (String word : wordsToRemove) {
            cleaned = cleaned.replaceAll("\\b" + word + "\\b", "");
        }
        
        // Clean up extra spaces
        cleaned = cleaned.replaceAll("\\s+", " ").trim();
        
        // Limit to 15 characters for horizontal layout
        if (cleaned.length() > 15) {
            cleaned = cleaned.substring(0, 12) + "...";
        }
        
        // If name becomes too short, use a fallback
        if (cleaned.length() < 2) {
            cleaned = "Food";
        }
        
        return cleaned;
    }

    public static class ViewHolder extends RecyclerView.ViewHolder {
        CardView foodCard;
        ImageView foodImage;
        TextView foodName;
        TextView calories;

        public ViewHolder(@NonNull View itemView) {
            super(itemView);
            foodCard = itemView.findViewById(R.id.food_card);
            foodImage = itemView.findViewById(R.id.food_image);
            foodName = itemView.findViewById(R.id.food_name);
            calories = itemView.findViewById(R.id.calories);
        }
    }
}
