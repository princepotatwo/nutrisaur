package com.example.nutrisaur11;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import java.util.List;

public class FavoriteAdapter extends RecyclerView.Adapter<FavoriteAdapter.FavoriteViewHolder> {
    private final List<DishData.Dish> favoritesList;

    public FavoriteAdapter(List<DishData.Dish> favoritesList) {
        this.favoritesList = favoritesList;
    }

    @NonNull
    @Override
    public FavoriteViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.favorite_item, parent, false);
        return new FavoriteViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull FavoriteViewHolder holder, int position) {
        DishData.Dish dish = favoritesList.get(position);
        holder.emoji.setText(dish.emoji);
        holder.name.setText(dish.name);
        holder.description.setText(dish.desc);
    }

    @Override
    public int getItemCount() {
        return favoritesList.size();
    }

    static class FavoriteViewHolder extends RecyclerView.ViewHolder {
        TextView emoji, name, description;
        
        FavoriteViewHolder(@NonNull View itemView) {
            super(itemView);
            emoji = itemView.findViewById(R.id.favorite_emoji);
            name = itemView.findViewById(R.id.favorite_name);
            description = itemView.findViewById(R.id.favorite_desc);
        }
    }
}
