package com.example.nutrisaur11;

import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.widget.LinearLayout;
import android.widget.TextView;
import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import java.util.List;
import java.util.ArrayList;

public class FavoritesActivity extends AppCompatActivity {
    private FavoritesManager favoritesManager;
    private RecyclerView favoritesRecyclerView;
    private FavoriteAdapter favoritesAdapter;
    private List<DishData.Dish> favoritesList = new ArrayList<>();

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_favorites);
        
        // Initialize favorites manager
        favoritesManager = new FavoritesManager(this);
        
        // Setup RecyclerView for favorites
        setupFavoritesRecyclerView();
        
        // Load favorites
        loadFavorites();
        
        setupButtons();
    }
    
    private void setupFavoritesRecyclerView() {
        favoritesRecyclerView = findViewById(R.id.favorites_recycler_view);
        if (favoritesRecyclerView == null) {
            android.util.Log.e("FavoritesActivity", "RecyclerView not found in layout!");
            return;
        }
        
        android.util.Log.d("FavoritesActivity", "Setting up RecyclerView");
        favoritesRecyclerView.setLayoutManager(new LinearLayoutManager(this));
        favoritesAdapter = new FavoriteAdapter(favoritesList);
        favoritesRecyclerView.setAdapter(favoritesAdapter);
        
        // Show RecyclerView and hide static cards
        favoritesRecyclerView.setVisibility(View.VISIBLE);
        hideStaticCards();
    }
    
    private void loadFavorites() {
        String userEmail = getCurrentUserEmail();
        android.util.Log.d("FavoritesActivity", "Loading favorites for user: " + userEmail);
        
        if (userEmail.isEmpty()) {
            android.widget.Toast.makeText(this, "Please log in to view favorites", android.widget.Toast.LENGTH_SHORT).show();
            android.util.Log.d("FavoritesActivity", "User email is empty");
            return;
        }
        
        favoritesList.clear();
        List<DishData.Dish> userFavorites = favoritesManager.getFavorites(userEmail);
        favoritesList.addAll(userFavorites);
        
        android.util.Log.d("FavoritesActivity", "Loaded " + userFavorites.size() + " favorites for user: " + userEmail);
        
        if (favoritesAdapter != null) {
            favoritesAdapter.notifyDataSetChanged();
        }
        
        // Update empty state
        updateEmptyState();
        
        // Hide static cards if we have a working RecyclerView
        hideStaticCards();
    }
    
    private void hideStaticCards() {
        // Hide the static cards container
        View staticCardsContainer = findViewById(R.id.static_favorites_container);
        if (staticCardsContainer != null) {
            staticCardsContainer.setVisibility(View.GONE);
            android.util.Log.d("FavoritesActivity", "Hidden static cards container");
        }
    }
    
    private void updateEmptyState() {
        TextView emptyText = findViewById(R.id.empty_favorites_text);
        if (emptyText != null) {
            if (favoritesList.isEmpty()) {
                emptyText.setVisibility(View.VISIBLE);
                emptyText.setText("No favorites yet!\nTap the heart icon on any food to add it to favorites.");
            } else {
                emptyText.setVisibility(View.GONE);
            }
        }
    }
    
    private String getCurrentUserEmail() {
        android.content.SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
        String email = prefs.getString("current_user_email", "");
        android.util.Log.d("FavoritesActivity", "Retrieved user email: " + email);
        return email;
    }
    
    private void setupButtons() {
        // Setup bottom navigation bar
        findViewById(R.id.nav_home).setOnClickListener(v -> {
            Intent intent = new Intent(FavoritesActivity.this, MainActivity.class);
            startActivity(intent);
            overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
            finish();
        });
        
        findViewById(R.id.nav_food).setOnClickListener(v -> {
            Intent intent = new Intent(FavoritesActivity.this, FoodActivity.class);
            startActivity(intent);
            overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
            finish();
        });
        
        findViewById(R.id.nav_favorites).setOnClickListener(v -> {
            // Already in FavoritesActivity, do nothing
        });
        
        findViewById(R.id.nav_account).setOnClickListener(v -> {
            Intent intent = new Intent(FavoritesActivity.this, AccountActivity.class);
            startActivity(intent);
            overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
            finish();
        });
    }
    
    @Override
    protected void onResume() {
        super.onResume();
        // Reload favorites when returning to the activity
        loadFavorites();
    }
    
    @Override
    protected void onDestroy() {
        super.onDestroy();
        if (favoritesManager != null) {
            favoritesManager.close();
        }
    }
} 