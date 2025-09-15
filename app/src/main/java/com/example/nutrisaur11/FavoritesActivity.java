package com.example.nutrisaur11;

import android.content.Intent;
import android.os.Bundle;
import android.widget.TextView;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.widget.NestedScrollView;

public class FavoritesActivity extends BaseActivity {
    // Malnutrition detection activity - favorites functionality removed

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_favorites);
        
        // Disable scrolling on NestedScrollView
        NestedScrollView nestedScrollView = findViewById(R.id.nested_scroll_view);
        if (nestedScrollView != null) {
            nestedScrollView.setNestedScrollingEnabled(false);
        }
        
        // Set header title
        TextView pageTitle = findViewById(R.id.page_title);
        TextView pageSubtitle = findViewById(R.id.page_subtitle);
        if (pageTitle != null) {
            pageTitle.setText("DETECT SIGNS OF MALNUTRITION");
        }
        if (pageSubtitle != null) {
            pageSubtitle.setText("Take photo to analyze nutrition");
        }
        
        setupButtons();
        
        // Call this after session validation
        onSessionValidated();
    }
    
    @Override
    protected void initializeActivity() {
        // Additional initialization after session validation
        // This method is called automatically by BaseActivity
    }
    
    
    private void setupFavoritesRecyclerView() {
        // RecyclerView functionality removed - now using malnutrition detection UI
        android.util.Log.d("FavoritesActivity", "Malnutrition detection UI active");
    }
    
    private void loadFavorites() {
        // Favorites functionality removed - now using malnutrition detection UI
        android.util.Log.d("FavoritesActivity", "Malnutrition detection UI active - favorites loading disabled");
    }
    
    private void hideStaticCards() {
        // Static cards functionality removed - now using malnutrition detection UI
        android.util.Log.d("FavoritesActivity", "Malnutrition detection UI active - static cards disabled");
    }
    
    private void updateEmptyState() {
        // Empty state functionality removed - now using malnutrition detection UI
        android.util.Log.d("FavoritesActivity", "Malnutrition detection UI active - empty state disabled");
    }
    
    private String getCurrentUserEmail() {
        android.content.SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
        String email = prefs.getString("current_user_email", "");
        android.util.Log.d("FavoritesActivity", "Retrieved user email: " + email);
        return email;
    }
    
    private void setupButtons() {
        // Setup malnutrition detection button (camera functionality removed)
        findViewById(R.id.start_detection_button).setOnClickListener(v -> {
            // Launch full screen camera activity
            Intent intent = new Intent(FavoritesActivity.this, FullScreenCameraActivity.class);
            startActivity(intent);
            overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
        });
        
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
    
} 