package com.example.nutrisaur11.adapters;

import android.content.Context;
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

    public interface OnFoodClickListener {
        void onFoodClick(FoodRecommendation food);
    }

    public HorizontalFoodAdapter(Context context, List<FoodRecommendation> foodList) {
        this.context = context;
        this.foodList = foodList;
    }

    public void setOnFoodClickListener(OnFoodClickListener listener) {
        this.onFoodClickListener = listener;
    }

    @NonNull
    @Override
    public FoodViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(context).inflate(R.layout.item_food_horizontal, parent, false);
        return new FoodViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull FoodViewHolder holder, int position) {
        FoodRecommendation food = foodList.get(position);
        
        holder.foodName.setText(food.getFoodName());
        holder.calories.setText(food.getCalories() + " cal");
        holder.description.setText(food.getDescription());
        
        // Set default image or load from URL if available
        if (food.getImageUrl() != null && !food.getImageUrl().isEmpty()) {
            // TODO: Load image from URL using Glide or similar
            holder.foodImage.setImageResource(R.drawable.ic_food_placeholder);
        } else {
            holder.foodImage.setImageResource(R.drawable.ic_food_placeholder);
        }
        
        holder.itemView.setOnClickListener(v -> {
            if (onFoodClickListener != null) {
                onFoodClickListener.onFoodClick(food);
            }
        });
    }

    @Override
    public int getItemCount() {
        return foodList.size();
    }

    public void updateFoodList(List<FoodRecommendation> newFoodList) {
        this.foodList = newFoodList;
        notifyDataSetChanged();
    }

    public static class FoodViewHolder extends RecyclerView.ViewHolder {
        ImageView foodImage;
        TextView foodName;
        TextView calories;
        TextView description;

        public FoodViewHolder(@NonNull View itemView) {
            super(itemView);
            foodImage = itemView.findViewById(R.id.food_image);
            foodName = itemView.findViewById(R.id.food_name);
            calories = itemView.findViewById(R.id.food_calories);
            description = itemView.findViewById(R.id.food_description);
        }
    }
}
