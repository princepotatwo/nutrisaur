package com.example.nutrisaur11;

import android.content.Intent;
import android.os.Bundle;
import androidx.appcompat.app.AppCompatActivity;

public class FoodPreferencesActivity extends AppCompatActivity {

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        
        // Instead of creating a new activity, just start PersonalizationActivity
        // with a flag to indicate it's for editing preferences
        Intent intent = new Intent(this, PersonalizationActivity.class);
        intent.putExtra("is_editing_preferences", true);
        startActivity(intent);
        finish();
    }
}
