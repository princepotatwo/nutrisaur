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

public class FoodSubstitutionAdapter extends RecyclerView.Adapter<FoodSubstitutionAdapter.ViewHolder> {
    private static final String TAG = "FoodSubstitutionAdapter";
    
    private List<FoodRecommendation> substitutions;
    private Context context;
    private FoodImageService foodImageService;
    private OnSubstitutionClickListener listener;
    private String substitutionReason;

    public interface OnSubstitutionClickListener {
        void onSubstitutionClick(FoodRecommendation substitution);
    }

    public FoodSubstitutionAdapter(List<FoodRecommendation> substitutions, Context context, 
                                 OnSubstitutionClickListener listener, String substitutionReason) {
        this.substitutions = substitutions;
        this.context = context;
        this.listener = listener;
        this.substitutionReason = substitutionReason;
        this.foodImageService = new FoodImageService();
        Log.d(TAG, "FoodSubstitutionAdapter created with " + substitutions.size() + " substitutions");
    }
    
    public void updateSubstitutions(List<FoodRecommendation> newSubstitutions, String reason) {
        this.substitutions = newSubstitutions;
        this.substitutionReason = reason;
        notifyDataSetChanged();
        Log.d(TAG, "Updated substitutions: " + newSubstitutions.size() + " items, reason: " + reason);
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(context).inflate(R.layout.item_food_substitution, parent, false);
        return new ViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {
        FoodRecommendation substitution = substitutions.get(position);
        
        Log.d(TAG, "Binding substitution at position " + position + ": " + substitution.getFoodName());
        
        // Set food name
        String shortName = shortenFoodName(substitution.getFoodName());
        holder.substitutionName.setText(shortName);
        
        // Set substitution reason
        if (substitutionReason != null && !substitutionReason.isEmpty()) {
            holder.substitutionReason.setText(getShortReason(substitutionReason));
        } else {
            holder.substitutionReason.setText("Alternative");
        }
        
        // Load image
        foodImageService.loadFoodImage(substitution.getFoodName(), holder.substitutionImage, null);
        
        // Set click listener
        holder.substitutionCard.setOnClickListener(v -> {
            if (listener != null) {
                listener.onSubstitutionClick(substitution);
            }
        });
    }

    @Override
    public int getItemCount() {
        return substitutions.size();
    }

    private String shortenFoodName(String foodName) {
        if (foodName == null || foodName.trim().isEmpty()) {
            return "Alternative";
        }
        
        String cleaned = foodName.trim();
        
        // Remove parentheses and brackets
        cleaned = cleaned.replaceAll("\\([^)]*\\)", "");
        cleaned = cleaned.replaceAll("\\[[^]]*\\]", "");
        cleaned = cleaned.replaceAll("\\s+", " ");
        
        // Remove unnecessary words
        String[] wordsToRemove = {
            "Recipe", "Filipino", "Food", "Dish", "Alternative", "Substitution"
        };
        
        for (String word : wordsToRemove) {
            cleaned = cleaned.replaceAll("\\b" + word + "\\b", "");
        }
        
        // Clean up extra spaces
        cleaned = cleaned.replaceAll("\\s+", " ").trim();
        
        // Split into words for two-line display
        String[] words = cleaned.split("\\s+");
        
        if (words.length >= 2) {
            // For two or more words, split them for two-line display
            StringBuilder result = new StringBuilder();
            int midPoint = (words.length + 1) / 2; // Split roughly in the middle
            
            // First line
            for (int i = 0; i < midPoint; i++) {
                if (i > 0) result.append(" ");
                result.append(words[i]);
            }
            
            result.append("\n"); // Line break
            
            // Second line
            for (int i = midPoint; i < words.length; i++) {
                if (i > midPoint) result.append(" ");
                result.append(words[i]);
            }
            
            return result.toString();
        } else {
            // Single word - keep as is but limit length
            if (cleaned.length() > 20) {
                cleaned = cleaned.substring(0, 17) + "...";
            }
            return cleaned;
        }
    }
    
    private String getShortReason(String reason) {
        if (reason == null || reason.isEmpty()) {
            return "Alternative";
        }
        
        String shortReason = reason.toLowerCase();
        
        if (shortReason.contains("budget")) {
            return "Budget-friendly";
        } else if (shortReason.contains("health")) {
            return "Healthier option";
        } else if (shortReason.contains("allergy")) {
            return "Allergy-safe";
        } else if (shortReason.contains("diet")) {
            return "Diet-friendly";
        } else if (shortReason.contains("pregnancy")) {
            return "Pregnancy-safe";
        } else if (shortReason.contains("availability")) {
            return "Available locally";
        } else {
            return "Alternative";
        }
    }

    public static class ViewHolder extends RecyclerView.ViewHolder {
        CardView substitutionCard;
        ImageView substitutionImage;
        TextView substitutionName;
        TextView substitutionReason;
        TextView substitutionBadge;

        public ViewHolder(@NonNull View itemView) {
            super(itemView);
            substitutionCard = itemView.findViewById(R.id.substitution_card);
            substitutionImage = itemView.findViewById(R.id.substitution_image);
            substitutionName = itemView.findViewById(R.id.substitution_name);
            substitutionReason = itemView.findViewById(R.id.substitution_reason);
            substitutionBadge = itemView.findViewById(R.id.substitution_badge);
        }
    }
}
