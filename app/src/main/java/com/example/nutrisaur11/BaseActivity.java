package com.example.nutrisaur11;

import android.os.Bundle;
import androidx.appcompat.app.AppCompatActivity;

/**
 * Base activity that provides automatic session validation
 * All activities that require authentication should extend this class
 */
public abstract class BaseActivity extends AppCompatActivity {
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        
        // Record user interaction (opening activity)
        SessionManager.getInstance(this).recordUserInteraction();
        
        // Validate session for all activities that extend this
        if (!SessionManager.getInstance(this).validateSession(this)) {
            return; // Will redirect to login
        }
    }
    
    @Override
    protected void onResume() {
        super.onResume();
        
        // Record user interaction (returning to activity)
        SessionManager.getInstance(this).recordUserInteraction();
        
        // INTERACTION-BASED VALIDATION: Only validate when user is actively using the app
        if (!SessionManager.getInstance(this).validateSession(this)) {
            return; // Will redirect to login
        }
    }
    
    @Override
    protected void onPause() {
        super.onPause();
        
        // Mark user as idle when activity goes to background
        SessionManager.getInstance(this).markUserAsIdle();
    }
    
    @Override
    protected void onStop() {
        super.onStop();
        
        // Mark user as idle when activity stops
        SessionManager.getInstance(this).markUserAsIdle();
    }
    
    /**
     * Override this method to perform additional setup after session validation
     */
    protected abstract void initializeActivity();
    
    /**
     * Call this method when user interacts with the app (button clicks, navigation, etc.)
     * This helps optimize battery usage by only checking session when user is active
     */
    protected void onUserInteraction() {
        SessionManager.getInstance(this).recordUserInteraction();
    }
    
    /**
     * Call this method at the end of onCreate after session validation
     */
    protected void onSessionValidated() {
        initializeActivity();
    }
}
