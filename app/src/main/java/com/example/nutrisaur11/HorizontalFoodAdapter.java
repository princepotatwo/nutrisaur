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
    private static final int VIEW_TYPE_LOADING = 0;
    private static final int VIEW_TYPE_FOOD = 1;
    
    private List<FoodRecommendation> foods;
    private Context context;
    private FoodImageService foodImageService;
    private OnFoodClickListener listener;
    private boolean isLoading = false;

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
    
    public void setLoading(boolean loading) {
        this.isLoading = loading;
        notifyDataSetChanged();
        Log.d(TAG, "setLoading called with: " + loading);
    }
    
    public boolean isLoading() {
        return isLoading;
    }

    @Override
    public int getItemViewType(int position) {
        return isLoading ? VIEW_TYPE_LOADING : VIEW_TYPE_FOOD;
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        if (viewType == VIEW_TYPE_LOADING) {
            View view = LayoutInflater.from(context).inflate(R.layout.item_food_loading, parent, false);
            return new ViewHolder(view, viewType);
        } else {
            View view = LayoutInflater.from(context).inflate(R.layout.item_food_horizontal, parent, false);
            return new ViewHolder(view, viewType);
        }
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {
        if (holder.viewType == VIEW_TYPE_LOADING) {
            // Loading state - no binding needed
            Log.d(TAG, "Binding loading card at position " + position);
            return;
        }
        
        FoodRecommendation food = foods.get(position);
        
        Log.d(TAG, "Binding food at position " + position + ": " + food.getFoodName());
        
        // Set food name (shortened for horizontal layout)
        String shortName = shortenFoodName(food.getFoodName());
        holder.foodName.setText(shortName);
        
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
        if (isLoading) {
            return 4; // Show 4 loading cards
        }
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
        int viewType;

        public ViewHolder(@NonNull View itemView, int viewType) {
            super(itemView);
            this.viewType = viewType;
            
            if (viewType == VIEW_TYPE_FOOD) {
                foodCard = itemView.findViewById(R.id.food_card);
                foodImage = itemView.findViewById(R.id.food_image);
                foodName = itemView.findViewById(R.id.food_name);
            }
            // For loading view, no specific views needed
        }
    }
}
