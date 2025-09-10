package com.example.nutrisaur11.adapters;

import android.content.Context;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.example.nutrisaur11.R;
import com.example.nutrisaur11.FoodRecommendation;
import java.util.List;

public class HorizontalFoodAdapter extends RecyclerView.Adapter<HorizontalFoodAdapter.FoodViewHolder> {
    private List<FoodRecommendation> foodList;
    private Context context;
    private OnFoodClickListener onFoodClickListener;
    private boolean isLoading = false;

    public interface OnFoodClickListener {
        void onFoodClick(FoodRecommendation food);
        void onFoodLongClick(FoodRecommendation food);
    }

    public HorizontalFoodAdapter(List<FoodRecommendation> foodList, Context context, OnFoodClickListener listener) {
        this.foodList = foodList;
        this.context = context;
        this.onFoodClickListener = listener;
    }

    public void setOnFoodClickListener(OnFoodClickListener listener) {
        this.onFoodClickListener = listener;
    }

    public void setLoading(boolean loading) {
        this.isLoading = loading;
        notifyDataSetChanged();
    }

    @NonNull
    @Override
    public FoodViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(context).inflate(R.layout.item_food_horizontal, parent, false);
        return new FoodViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull FoodViewHolder holder, int position) {
        if (isLoading) {
            holder.foodName.setText("Loading...");
            holder.foodImage.setImageResource(R.drawable.steamed_riced);
            return;
        }
        
        FoodRecommendation food = foodList.get(position);
        
        holder.foodName.setText(food.getFoodName());
        
        // Set image using local drawable resources
        if (food.getImageUrl() != null && !food.getImageUrl().isEmpty()) {
            Log.d("FoodAdapter", "Food: " + food.getFoodName() + " using image: " + food.getImageUrl());
            
            // Get drawable resource ID from string name
            int imageResourceId = context.getResources().getIdentifier(
                food.getImageUrl(), 
                "drawable", 
                context.getPackageName()
            );
            
            if (imageResourceId != 0) {
                // Image found, use it
                holder.foodImage.setImageResource(imageResourceId);
                Log.d("FoodAdapter", "Successfully loaded image: " + food.getImageUrl());
            } else {
                // Image not found, use default food image
                holder.foodImage.setImageResource(R.drawable.steamed_riced);
                Log.w("FoodAdapter", "Image not found: " + food.getImageUrl() + ", using default food image");
            }
        } else {
            // No image specified, use default food image
            holder.foodImage.setImageResource(R.drawable.steamed_riced);
        }
        
        holder.itemView.setOnClickListener(v -> {
            if (onFoodClickListener != null) {
                onFoodClickListener.onFoodClick(food);
            }
        });
        
        holder.itemView.setOnLongClickListener(v -> {
            if (onFoodClickListener != null) {
                onFoodClickListener.onFoodLongClick(food);
                return true; // Consume the long click event
            }
            return false;
        });
    }

    @Override
    public int getItemCount() {
        if (isLoading) {
            return 8; // Show 8 loading cards
        }
        return foodList.size();
    }

    public void updateFoodList(List<FoodRecommendation> newFoodList) {
        this.foodList = newFoodList;
        notifyDataSetChanged();
    }

    public static class FoodViewHolder extends RecyclerView.ViewHolder {
        ImageView foodImage;
        TextView foodName;

        public FoodViewHolder(@NonNull View itemView) {
            super(itemView);
            foodImage = itemView.findViewById(R.id.food_image);
            foodName = itemView.findViewById(R.id.food_name);
        }
    }
}
