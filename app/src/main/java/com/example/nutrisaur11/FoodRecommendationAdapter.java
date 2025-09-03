package com.example.nutrisaur11;

import android.content.Context;
import android.graphics.Bitmap;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;
import android.widget.Button;
import android.widget.ProgressBar;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import androidx.cardview.widget.CardView;
import android.util.Log;
import java.util.List;
import java.util.Set;
import java.util.HashSet;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class FoodRecommendationAdapter extends RecyclerView.Adapter<FoodRecommendationAdapter.ViewHolder> {
    private static final String TAG = "FoodRecommendationAdapter";
    private List<FoodRecommendation> recommendations;
    private Context context;
    private ExecutorService executorService;
    private ImageCacheManager imageCacheManager;
    private FoodImageService foodImageService;
    private Set<String> pendingRequests = new HashSet<>();
    

    public FoodRecommendationAdapter(List<FoodRecommendation> recommendations, Context context) {
        this.recommendations = recommendations;
        this.context = context;
        this.executorService = Executors.newFixedThreadPool(2);
        this.imageCacheManager = ImageCacheManager.getInstance();
        this.foodImageService = new FoodImageService();
        Log.d(TAG, "FoodRecommendationAdapter created - Cache status:");
        imageCacheManager.logCacheStatus();
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(context).inflate(R.layout.item_food_recommendation, parent, false);
        return new ViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {
        FoodRecommendation recommendation = recommendations.get(position);
        
        Log.d(TAG, "Binding recommendation at position " + position + ": " + recommendation.getFoodName());
        
        // Set food information with cleaned names
        String cleanedFoodName = cleanFoodName(recommendation.getFoodName());
        holder.foodNameText.setText(cleanedFoodName);
        holder.caloriesText.setText(recommendation.getCalories() + " cal");
        holder.proteinText.setText(recommendation.getProtein() + " g");
        holder.fatText.setText(recommendation.getFat() + " g");
        holder.carbsText.setText(recommendation.getCarbs() + " g");
        holder.descriptionText.setText(recommendation.getDescription());
        
        Log.d(TAG, "Set text values - Name: '" + recommendation.getFoodName() + 
                  "', Calories: " + recommendation.getCalories() + 
                  ", Protein: " + recommendation.getProtein() +
                  ", Description: '" + recommendation.getDescription() + "'");
        
        // Handle image loading using FoodImageService
        if (recommendation.getImageUrl() != null && !recommendation.getImageUrl().isEmpty()) {
            // Use the image URL from the recommendation
            foodImageService.loadFoodImage(recommendation.getImageUrl(), holder.foodImage, holder.progressBar);
        } else {
            // Load image using food name
            foodImageService.loadFoodImage(recommendation.getFoodName(), holder.foodImage, holder.progressBar);
        }
        
        // Set up smart substitution button
        holder.smartSubstitutionButton.setOnClickListener(v -> {
            Log.d(TAG, "Smart substitution clicked for: " + recommendation.getFoodName());
            // TODO: Implement smart substitution functionality
        });
        
        // Set up view ingredients button
        holder.viewIngredientsButton.setOnClickListener(v -> {
            Log.d(TAG, "View ingredients clicked for: " + recommendation.getFoodName());
            // TODO: Implement view ingredients functionality
        });
    }

    @Override
    public int getItemCount() {
        return recommendations.size();
    }


    
    // Method to clean and shorten food names
    private String cleanFoodName(String foodName) {
        if (foodName == null || foodName.trim().isEmpty()) {
            return "Filipino Dish";
        }
        
        String cleaned = foodName.trim();
        
        // Only remove parentheses and brackets, keep the main food name
        cleaned = cleaned.replaceAll("\\([^)]*\\)", ""); // Remove parentheses and content
        cleaned = cleaned.replaceAll("\\[[^]]*\\]", ""); // Remove brackets and content
        cleaned = cleaned.replaceAll("\\s+", " "); // Replace multiple spaces with single space
        
        // Only remove very specific unnecessary words, keep important food words
        String[] wordsToRemove = {
            "Recipe", "Filipino", "Food", "Dish"
        };
        
        for (String word : wordsToRemove) {
            cleaned = cleaned.replaceAll("\\b" + word + "\\b", "");
        }
        
        // Clean up extra spaces
        cleaned = cleaned.replaceAll("\\s+", " ").trim();
        
        // Limit to 30 characters (increased from 25)
        if (cleaned.length() > 30) {
            cleaned = cleaned.substring(0, 27) + "...";
        }
        
        // If name becomes too short or empty, use a fallback
        if (cleaned.length() < 3) {
            cleaned = "Filipino Dish";
        }
        
        return cleaned;
    }
    

    public static class ViewHolder extends RecyclerView.ViewHolder {
        CardView recommendationCard;
        ImageView foodImage;
        TextView foodNameText;
        TextView caloriesText;
        TextView proteinText;
        TextView fatText;
        TextView carbsText;
        TextView descriptionText;
        Button smartSubstitutionButton;
        Button viewIngredientsButton;
        ProgressBar progressBar;

        public ViewHolder(@NonNull View itemView) {
            super(itemView);
            recommendationCard = itemView.findViewById(R.id.recommendation_card);
            foodImage = itemView.findViewById(R.id.food_image);
            foodNameText = itemView.findViewById(R.id.food_name_text);
            caloriesText = itemView.findViewById(R.id.calories_text);
            proteinText = itemView.findViewById(R.id.protein_text);
            fatText = itemView.findViewById(R.id.fat_text);
            carbsText = itemView.findViewById(R.id.carbs_text);
            descriptionText = itemView.findViewById(R.id.description_text);
            smartSubstitutionButton = itemView.findViewById(R.id.smart_substitution_button);
            viewIngredientsButton = itemView.findViewById(R.id.view_ingredients_button);
            progressBar = itemView.findViewById(R.id.progress_bar);
        }
    }
}
