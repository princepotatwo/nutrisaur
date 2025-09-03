package com.example.nutrisaur11;

import android.content.Context;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.Button;
import android.widget.ImageView;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import java.util.List;
import com.example.nutrisaur11.FoodImageHelper;

public class FoodCardAdapter extends RecyclerView.Adapter<FoodCardAdapter.FoodCardViewHolder> {
    private final List<DishData.Dish> foodList;
    private final OnSubstituteClickListener substituteClickListener;
    private final OnInfoClickListener infoClickListener;
    private final FavoritesManager favoritesManager;
    private String userEmail;

    public interface OnSubstituteClickListener {
        void onSubstituteClick(DishData.Dish dish);
    }

    public interface OnInfoClickListener {
        void onInfoClick(DishData.Dish dish);
    }

    public FoodCardAdapter(List<DishData.Dish> foodList, OnSubstituteClickListener substituteListener, OnInfoClickListener infoListener, Context context, String userEmail) {
        this.foodList = foodList;
        this.substituteClickListener = substituteListener;
        this.infoClickListener = infoListener;
        
        // Safety check for context
        if (context == null) {
            throw new IllegalArgumentException("Context cannot be null for FoodCardAdapter");
        }
        
        this.favoritesManager = new FavoritesManager(context);
        
        // Set user email, with fallback to SharedPreferences if empty
        if (userEmail != null && !userEmail.isEmpty()) {
            this.userEmail = userEmail;
        } else {
            try {
                android.content.SharedPreferences prefs = context.getSharedPreferences("nutrisaur_prefs", Context.MODE_PRIVATE);
                this.userEmail = prefs.getString("current_user_email", "");
                android.util.Log.d("FoodCardAdapter", "Retrieved user email from SharedPreferences: " + this.userEmail);
            } catch (Exception e) {
                android.util.Log.e("FoodCardAdapter", "Error accessing SharedPreferences: " + e.getMessage());
                this.userEmail = "";
            }
        }
    }

    @NonNull
    @Override
    public FoodCardViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.food_card, parent, false);
        return new FoodCardViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull FoodCardViewHolder holder, int position) {
        DishData.Dish dish = foodList.get(position);
        
        // Set text immediately (fast operation)
        holder.name.setText(dish.name);
        
        // Set a placeholder image first (fast operation)
        holder.foodImage.setImageResource(R.drawable.ic_food_simple);
        
        // Load food image asynchronously to prevent scrolling lag
        loadImageAsync(holder, dish.name);
        
        // Set up substitute button click listener with null safety
        if (substituteClickListener != null) {
            holder.substituteBtn.setOnClickListener(v -> substituteClickListener.onSubstituteClick(dish));
        } else {
            holder.substituteBtn.setVisibility(View.GONE); // Hide button if no listener
        }
        
        // Check if this dish is already in favorites
        holder.isFavorited = favoritesManager.isFavorite(userEmail, dish.name);
        holder.favoriteIcon.setImageResource(holder.isFavorited ? R.drawable.ic_heart_filled : R.drawable.ic_heart_outline);
        
        // Set up favorite icon click listener with proper favorite functionality
        holder.favoriteIcon.setOnClickListener(v -> {
            holder.isFavorited = !holder.isFavorited;
            if (holder.isFavorited) {
                holder.favoriteIcon.setImageResource(R.drawable.ic_heart_filled);
                saveToFavorites(dish);
            } else {
                holder.favoriteIcon.setImageResource(R.drawable.ic_heart_outline);
                removeFromFavorites(dish);
            }
        });
        
        // Set up info icon click listener
        holder.infoIcon.setOnClickListener(v -> {
            if (infoClickListener != null) {
                infoClickListener.onInfoClick(dish);
            }
        });
    }
    
    /**
     * Load image asynchronously to prevent scrolling lag
     */
    private void loadImageAsync(FoodCardViewHolder holder, String dishName) {
        // Use a background thread for image loading
        new Thread(() -> {
            try {
                // Get image resource ID in background
                int imageResourceId = FoodImageHelper.getImageResourceId(holder.itemView.getContext(), dishName);
                
                // Update UI on main thread
                holder.itemView.post(() -> {
                    try {
                        holder.foodImage.setImageResource(imageResourceId);
                    } catch (Exception e) {
                        android.util.Log.w("FoodCardAdapter", "Error setting image for " + dishName + ": " + e.getMessage());
                        // Fallback to default icon
                        holder.foodImage.setImageResource(R.drawable.ic_food_simple);
                    }
                });
            } catch (Exception e) {
                android.util.Log.e("FoodCardAdapter", "Error loading image for " + dishName + ": " + e.getMessage());
                // Fallback to default icon on main thread
                holder.itemView.post(() -> {
                    holder.foodImage.setImageResource(R.drawable.ic_food_simple);
                });
            }
        }).start();
    }

    @Override
    public int getItemCount() {
        return foodList.size();
    }
    
    private void saveToFavorites(DishData.Dish dish) {
        favoritesManager.addToFavorites(userEmail, dish);
    }
    
    private void removeFromFavorites(DishData.Dish dish) {
        favoritesManager.removeFromFavorites(userEmail, dish.name);
    }

    static class FoodCardViewHolder extends RecyclerView.ViewHolder {
        ImageView foodImage;
        TextView name;
        Button substituteBtn;
        ImageView favoriteIcon, infoIcon;
        boolean isFavorited = false;
        
        FoodCardViewHolder(@NonNull View itemView) {
            super(itemView);
            foodImage = itemView.findViewById(R.id.food_image);
            name = itemView.findViewById(R.id.food_name);
            substituteBtn = itemView.findViewById(R.id.btn_substitute);
            favoriteIcon = itemView.findViewById(R.id.favorite_icon);
            infoIcon = itemView.findViewById(R.id.info_icon);
        }
    }
} 