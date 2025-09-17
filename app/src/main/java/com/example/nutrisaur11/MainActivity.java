package com.example.nutrisaur11;

import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.TextView;
import androidx.appcompat.app.AppCompatActivity;
import androidx.cardview.widget.CardView;
import android.widget.*;
import android.graphics.Color;
import android.app.AlertDialog;
import android.content.DialogInterface;
import android.text.InputType;
import java.util.*;
import android.database.Cursor;
import android.content.ContentValues;
import android.content.Intent;
import com.example.nutrisaur11.Constants;
import com.example.nutrisaur11.SignUpActivity;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import com.example.nutrisaur11.Event;
import com.example.nutrisaur11.EventAdapter;
import com.example.nutrisaur11.EventBackgroundService;
// Removed WebViewAPIClient - using direct HTTP requests
import java.util.Collections;
import java.util.Comparator;
import java.text.SimpleDateFormat;
import java.util.Date;
import android.widget.Toast;
import java.util.Arrays;
import android.util.Log;
import java.util.concurrent.CompletableFuture;
import com.google.firebase.messaging.FirebaseMessaging;
import android.content.BroadcastReceiver;
import android.content.IntentFilter;
import android.content.Context;
import androidx.core.app.NotificationCompat;
import androidx.core.app.NotificationManagerCompat;

public class MainActivity extends BaseActivity {

    private boolean isLoggedIn = false;
    private UserPreferencesDbHelper dbHelper;
    private FCMTokenManager fcmTokenManager;
    private boolean fcmInitialized = false; // Flag to prevent multiple FCM initializations

    // Add this at the top of MainActivity
    private static final String[] ALLERGENS = {"Peanuts", "Dairy", "Eggs", "Shellfish", "Gluten", "Soy", "Fish", "Tree nuts"};
    private static final String[] DIET_PREFS = {"Vegetarian", "Vegan", "Halal", "Kosher", "Pescatarian"};

    private RecyclerView eventsRecyclerView;
    private EventAdapter dashboardEventAdapter;
    private List<Event> dashboardEvents = new ArrayList<>();
    
    // Event checking with smart updates
    private long lastEventCheckTime = 0;
    private static final long MIN_EVENT_CHECK_INTERVAL = 30 * 1000; // 30 seconds minimum between checks
    private Set<Integer> lastKnownEventIds = new HashSet<>();
    
    // Broadcast receiver for real-time event updates
    private BroadcastReceiver eventRefreshReceiver;


    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        // ScreeningResultStore.init(this); // Removed
        dbHelper = new UserPreferencesDbHelper(this);
        
        // Check for daily reset before initializing
        DailyResetManager resetManager = new DailyResetManager(this);
        resetManager.checkAndResetDaily();
        
        // Initialize FCM Token Manager
        fcmTokenManager = new FCMTokenManager(this);
        
        // Start food preload service in background
        MainActivityHelper.startFoodPreloadService(this);
        
        // TEMPORARILY DISABLED: Initialize event checking and auto-refresh functionality
        // This was causing unwanted API calls every 3 seconds
        
        /*
        // Initialize event checking timer
        eventCheckHandler = new android.os.Handler();
        eventCheckRunnable = new Runnable() {
            @Override
            public void run() {
                // Start the EventBackgroundService to handle event checking in the background
                Intent eventServiceIntent = new Intent(MainActivity.this, EventBackgroundService.class);
                startService(eventServiceIntent);
                
                // Schedule next check
                eventCheckHandler.postDelayed(this, EVENT_CHECK_INTERVAL);
            }
        };
        */
        
        // Check login status using CommunityUserManager
        CommunityUserManager userManager = new CommunityUserManager(this);
        isLoggedIn = userManager.isLoggedIn();
        
        // Get current user email
        String currentEmail = userManager.getCurrentUserEmail();
        
        if (isLoggedIn) {
            setContentView(R.layout.activity_dashboard);
            
            // Set header title
            TextView pageTitle = findViewById(R.id.page_title);
            TextView pageSubtitle = findViewById(R.id.page_subtitle);
            if (pageTitle != null) {
                pageTitle.setText("DASHBOARD");
            }
            if (pageSubtitle != null) {
                pageSubtitle.setText("Your nutrition health overview");
            }
            
            // Update welcome greeting with user name
            updateWelcomeGreeting();
            
            // Setup UI components
            setupNavigation();
            // Initialize RecyclerView for events
            eventsRecyclerView = findViewById(R.id.events_recycler_view);
            
            // Setup SwipeRefreshLayout
            androidx.swiperefreshlayout.widget.SwipeRefreshLayout swipeRefreshLayout = findViewById(R.id.swipeRefreshLayout);
            if (swipeRefreshLayout != null) {
                swipeRefreshLayout.setOnRefreshListener(() -> {
                    fetchAndDisplayDashboardEvents();
                    swipeRefreshLayout.setRefreshing(false);
                });
            }
            
            dashboardEventAdapter = new EventAdapter(dashboardEvents, new EventAdapter.OnEventClickListener() {
                @Override
                public void onEventClick(Event event) {
                    // Optionally show event details from dashboard
                }
            });
            eventsRecyclerView.setLayoutManager(new LinearLayoutManager(this));
            eventsRecyclerView.setAdapter(dashboardEventAdapter);
            
            // Initialize FCM with user context if available
            initializeFirebaseMessaging();
            
            // Clear old calorie data to ensure fresh start
            clearOldCalorieData();
            
            // Load events immediately
            fetchAndDisplayDashboardEvents();
        } else {
            // User not logged in, launch LoginActivity
            Intent intent = new Intent(MainActivity.this, LoginActivity.class);
            startActivity(intent);
            overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
            finish(); // Close MainActivity since we're going to LoginActivity
        }
        
        // Setup broadcast receiver for real-time event updates
        setupEventRefreshReceiver();
        
        // Call this after session validation
        onSessionValidated();
    }
    
    @Override
    protected void initializeActivity() {
        // Additional initialization after session validation
        // This method is called automatically by BaseActivity
    }

    @Override
    protected void onResume() {
        super.onResume();
        // Smart event checking - only check if enough time has passed
        if (isLoggedIn) {
            long currentTime = System.currentTimeMillis();
            if (currentTime - lastEventCheckTime > MIN_EVENT_CHECK_INTERVAL) {
                checkForNewEvents();
            } else {
                // Just refresh the display with existing data
                fetchAndDisplayDashboardEvents();
            }
        }
    }
    
    @Override
    protected void onPause() {
        super.onPause();
        // Stop the background service to save resources
        Intent eventServiceIntent = new Intent(this, EventBackgroundService.class);
        stopService(eventServiceIntent);
    }
    
    @Override
    protected void onStop() {
        super.onStop();
        // Ensure background service is stopped when dashboard is no longer visible
        // This provides an additional safety net for resource optimization
        Intent eventServiceIntent = new Intent(this, EventBackgroundService.class);
        stopService(eventServiceIntent);
    }
    
    @Override
    protected void onDestroy() {
        super.onDestroy();
        // Clean up resources when activity is destroyed
        
        // Close database helper
        if (dbHelper != null) {
            dbHelper.close();
        }
        
        // Unregister broadcast receiver
        if (eventRefreshReceiver != null) {
            unregisterReceiver(eventRefreshReceiver);
        }
        
        // Stop background service
        Intent eventServiceIntent = new Intent(this, EventBackgroundService.class);
        stopService(eventServiceIntent);
    }


    private void setupNavigation() {
        // Dashboard navigation
        findViewById(R.id.nav_home).setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                Intent intent = new Intent(MainActivity.this, MainActivity.class);
                startActivity(intent);
                overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
                finish();
            }
        });

        findViewById(R.id.nav_food).setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                // Trigger a simple sync of user preferences before opening FoodActivity
                syncUserPreferencesToApi();
                
                Intent intent = new Intent(MainActivity.this, FoodActivity.class);
                startActivity(intent);
                overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
                finish();
            }
        });

        findViewById(R.id.nav_favorites).setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                Intent intent = new Intent(MainActivity.this, FavoritesActivity.class);
                startActivity(intent);
                overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
                finish();
            }
        });
        findViewById(R.id.nav_account).setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                Intent intent = new Intent(MainActivity.this, AccountActivity.class);
                startActivity(intent);
                overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
                finish();
            }
        });
        
        // Setup events functionality
        setupEvents();
        
        // Setup new action buttons
        setupActionButtons();
        
        // FCM token registration is now handled by FCMTokenManager with optimization
        // No need to call registerFCMTokenOnStartup() here
    }

    private void setupAccountSettings() {
        // Account settings setup (camera settings removed)



        // Data sync switch
        android.widget.Switch syncSwitch = findViewById(R.id.sync_switch);
        if (syncSwitch != null) {
            syncSwitch.setOnCheckedChangeListener(new android.widget.CompoundButton.OnCheckedChangeListener() {
                @Override
                public void onCheckedChanged(android.widget.CompoundButton buttonView, boolean isChecked) {
                    if (isChecked) {
                        android.widget.Toast.makeText(MainActivity.this, 
                            "Data sync enabled", android.widget.Toast.LENGTH_SHORT).show();
                    } else {
                        android.widget.Toast.makeText(MainActivity.this, 
                            "Data sync disabled", android.widget.Toast.LENGTH_SHORT).show();
                    }
                }
            });
        }

        // Privacy switch
        android.widget.Switch privacySwitch = findViewById(R.id.privacy_switch);
        if (privacySwitch != null) {
            privacySwitch.setOnCheckedChangeListener(new android.widget.CompoundButton.OnCheckedChangeListener() {
                @Override
                public void onCheckedChanged(android.widget.CompoundButton buttonView, boolean isChecked) {
                    if (isChecked) {
                        android.widget.Toast.makeText(MainActivity.this, 
                            "Data collection enabled", android.widget.Toast.LENGTH_SHORT).show();
                    } else {
                        android.widget.Toast.makeText(MainActivity.this, 
                            "Data collection disabled", android.widget.Toast.LENGTH_SHORT).show();
                    }
                }
            });
        }

        // Logout functionality
        View logoutSection = findViewById(R.id.logout_section);
        if (logoutSection != null) {
            logoutSection.setOnClickListener(new View.OnClickListener() {
                @Override
                public void onClick(View v) {
                    // Show confirmation dialog
                    new androidx.appcompat.app.AlertDialog.Builder(MainActivity.this)
                        .setTitle("Logout")
                        .setMessage("Are you sure you want to logout?")
                        .setPositiveButton("Yes", new android.content.DialogInterface.OnClickListener() {
                            @Override
                            public void onClick(android.content.DialogInterface dialog, int which) {
                                // Clear all user data
                                clearAllUserData();
                                // TODO: Fix LoginActivity reference
                                // Intent intent = new Intent(MainActivity.this, LoginActivity.class);
                                // startActivity(intent);
                                // overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
                                finish();
                            }
                        })
                        .setNegativeButton("Cancel", null)
                        .show();
                }
            });
        }
    }
    
    private void clearOldCalorieData() {
        try {
            // Clear any old calorie data to ensure fresh start
            CalorieTracker calorieTracker = new CalorieTracker(this);
            calorieTracker.clearDay();
        } catch (Exception e) {
            // Silent error handling
        }
    }
    
    private void clearAllUserData() {
        try {
            android.content.SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
            String currentUserEmail = prefs.getString("current_user_email", null);
            
            // Clear user-specific data first
            if (currentUserEmail != null) {
                AddedFoodManager.clearUserData(this, currentUserEmail);
                CalorieTracker.clearUserData(this, currentUserEmail);
                GeminiCacheManager.clearUserData(this, currentUserEmail);
                FavoritesManager.clearUserData(this, currentUserEmail);
            }
            
            // Clear all user data from main preferences
            android.content.SharedPreferences.Editor editor = prefs.edit();
            editor.clear();
            
            // Set basic logout state
            editor.putBoolean("is_logged_in", false);
            
            editor.apply();
            
        } catch (Exception e) {
            // Silent error handling
        }
    }

    
    private void setupEvents() {
        // Setup "See More" button for events
        Button seeMoreEvents = findViewById(R.id.see_more_events);
        if (seeMoreEvents != null) {
            seeMoreEvents.setOnClickListener(new View.OnClickListener() {
                @Override
                public void onClick(View v) {
                    // Use our optimized navigation method that stops the background service
                    navigateToEvents();
                    overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
                }
            });
        }
        
        // Load and display events in dashboard
        // loadDashboardEvents(); // This is now handled by fetchAndDisplayDashboardEvents()
    }
    
    // Removed WebViewAPIClient - using direct HTTP requests
    
    // Add a method to fetch joined events for the current user
    private void fetchAndDisplayDashboardEvents() {
        String userEmail = getCurrentUserEmail();
        if (userEmail == null) {
            return;
        }
        
        // Use direct HTTP request to fetch events
        new Thread(() -> {
            try {
                // Fetch all events first - use event.php directly
                java.net.URL url = new java.net.URL(Constants.API_BASE_URL + "event.php?action=get_events");
                java.net.HttpURLConnection conn = (java.net.HttpURLConnection) url.openConnection();
                conn.setRequestMethod("GET");
                conn.setRequestProperty("User-Agent", "NutrisaurApp/1.0 (Android)");
                conn.setRequestProperty("Accept", "text/plain, application/json");
                conn.setConnectTimeout(15000);
                conn.setReadTimeout(15000);
                
                int responseCode = conn.getResponseCode();
                
                if (responseCode == 200) {
                    java.io.BufferedReader reader = new java.io.BufferedReader(
                        new java.io.InputStreamReader(conn.getInputStream()));
                    StringBuilder response = new StringBuilder();
                    String line;
                    while ((line = reader.readLine()) != null) {
                        response.append(line);
                    }
                    reader.close();
                    
                    org.json.JSONObject jsonResponse = new org.json.JSONObject(response.toString());
                    if (jsonResponse.has("events")) {
                        org.json.JSONArray eventsArray = jsonResponse.getJSONArray("events");
                        
                        // Fetch joined events for this user
                        List<Integer> joinedIds = fetchUserJoinedEvents(userEmail);
                            
                        // Now updateDashboardEvents with joinedIds
                        updateDashboardEvents(eventsArray, joinedIds);
                    } else {
                        runOnUiThread(() -> {
                            showNoEventsMessage("No events data available");
                        });
                    }
                } else {
                    runOnUiThread(() -> {
                        showNoEventsMessage("Failed to load events");
                    });
                }
            } catch (Exception e) {
                runOnUiThread(() -> {
                    showNoEventsMessage("Error loading events");
                });
            }
        }).start();
    }
    
    private List<Integer> fetchUserJoinedEvents(String userEmail) {
        List<Integer> joinedIds = new ArrayList<>();
        try {
            org.json.JSONObject requestData = new org.json.JSONObject();
            requestData.put("action", "get_user_events");
            requestData.put("user_email", userEmail);
            
            java.net.URL url = new java.net.URL(Constants.UNIFIED_API_URL);
            java.net.HttpURLConnection conn = (java.net.HttpURLConnection) url.openConnection();
            conn.setRequestMethod("POST");
            conn.setRequestProperty("Content-Type", "application/json");
            conn.setDoOutput(true);
            conn.setConnectTimeout(10000);
            conn.setReadTimeout(10000);
            
            java.io.OutputStream os = conn.getOutputStream();
            os.write(requestData.toString().getBytes("UTF-8"));
            os.close();
            
            int responseCode = conn.getResponseCode();
            
            if (responseCode == 200) {
                java.io.BufferedReader reader = new java.io.BufferedReader(
                    new java.io.InputStreamReader(conn.getInputStream()));
                StringBuilder response = new StringBuilder();
                String line;
                while ((line = reader.readLine()) != null) {
                    response.append(line);
                }
                reader.close();
                
                org.json.JSONObject jsonResponse = new org.json.JSONObject(response.toString());
                if (jsonResponse.getBoolean("success")) {
                    org.json.JSONArray userEvents = jsonResponse.getJSONArray("user_events");
                    for (int i = 0; i < userEvents.length(); i++) {
                        org.json.JSONObject userEvent = userEvents.getJSONObject(i);
                        joinedIds.add(userEvent.getInt("id"));
                    }
                }
            }
        } catch (Exception e) {
            // Silent error handling
        }
        return joinedIds;
    }
    
    private void showNoEventsMessage(String message) {
        runOnUiThread(() -> {
            TextView noEventsMessage = findViewById(R.id.no_events_message);
            if (noEventsMessage != null) {
                noEventsMessage.setText(message);
                noEventsMessage.setVisibility(View.VISIBLE);
            }
        });
    }
    
    // Update updateDashboardEvents to accept joinedIds
    private void updateDashboardEvents(org.json.JSONArray eventsArray, List<Integer> joinedIds) {
        try {
            List<Event> allEvents = new ArrayList<>();
            List<Event> newEvents = new ArrayList<>();
            SimpleDateFormat format = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
            Date now = new Date();
            
            // Get existing event IDs to detect new events
            Set<Integer> existingEventIds = new HashSet<>();
            for (Event existingEvent : dashboardEvents) {
                existingEventIds.add(existingEvent.getProgramId());
            }
            
            // Update last known event IDs for new event detection
            Set<Integer> currentEventIds = new HashSet<>();
            
            for (int i = 0; i < eventsArray.length(); i++) {
                try {
                    org.json.JSONObject eventObj = eventsArray.getJSONObject(i);
                    
                    // Handle created_at field that might be boolean false or timestamp
                    long createdAt = 0;
                    try {
                        if (eventObj.get("created_at") instanceof Boolean) {
                            // If created_at is boolean false, use current timestamp
                            createdAt = System.currentTimeMillis();
                        } else {
                            createdAt = eventObj.getLong("created_at");
                        }
                    } catch (Exception e) {
                        // Fallback to current timestamp if parsing fails
                        createdAt = System.currentTimeMillis();
                    }
                    
                    Event event = new Event(
                        eventObj.getInt("id"),
                        eventObj.getString("title"),
                        eventObj.getString("type"),
                        eventObj.getString("description"),
                        eventObj.getString("date_time"),
                        eventObj.getString("location"),
                        eventObj.getString("organizer"),
                        createdAt
                    );
                    
                    // Track event ID for new event detection
                    currentEventIds.add(event.getProgramId());
                    
                    // Mark as joined if in joinedIds
                    if (joinedIds != null && joinedIds.contains(event.getProgramId())) {
                        event.setJoined(true);
                    }
                    
                    // Add all events for testing (remove date filtering temporarily)
                    try {
                        Date eventDate = format.parse(event.getDateTime());
                        
                        // Show all events regardless of date for testing
                        allEvents.add(event);
                        
                        // Check if this is a new event
                        if (!existingEventIds.contains(event.getProgramId())) {
                            newEvents.add(event);
                        }
                    } catch (Exception e) {
                        // Add event anyway if date parsing fails
                        allEvents.add(event);
                        if (!existingEventIds.contains(event.getProgramId())) {
                            newEvents.add(event);
                        }
                    }
                } catch (Exception e) {
                    // Silent error handling
                }
            }
            
            // Sort by soonest date/time
            Collections.sort(allEvents, new Comparator<Event>() {
                @Override
                public int compare(Event e1, Event e2) {
                    try {
                        Date d1 = format.parse(e1.getDateTime());
                        Date d2 = format.parse(e2.getDateTime());
                        return d1.compareTo(d2);
                    } catch (Exception e) {
                        return 0;
                    }
                }
            });
            
            // Take only the first 3
            List<Event> displayEvents = allEvents.subList(0, Math.min(3, allEvents.size()));
            
            runOnUiThread(() -> {
                dashboardEvents.clear();
                dashboardEvents.addAll(displayEvents);
                dashboardEventAdapter.notifyDataSetChanged();
                
                // Show/hide no events message
                TextView noEventsMessage = findViewById(R.id.no_events_message);
                if (noEventsMessage != null) {
                    if (displayEvents.isEmpty()) {
                        noEventsMessage.setText("No upcoming events found");
                        noEventsMessage.setVisibility(View.VISIBLE);
                    } else {
                        noEventsMessage.setVisibility(View.GONE);
                    }
                }
                
                // Update last known event IDs for new event detection
                lastKnownEventIds = currentEventIds;
            });
        } catch (Exception e) {
            runOnUiThread(() -> showNoEventsMessage("Error processing events"));
        }
    }
    

    
    private String getRelativeTime(String dateTime) {
        try {
            java.text.SimpleDateFormat format = new java.text.SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
            java.util.Date eventDate = format.parse(dateTime);
            java.util.Date now = new java.util.Date();
            
            long diffInMillis = eventDate.getTime() - now.getTime();
            long diffInDays = diffInMillis / (24 * 60 * 60 * 1000);
            
            if (diffInDays == 0) {
                return "Today";
            } else if (diffInDays == 1) {
                return "Tomorrow";
            } else if (diffInDays < 7) {
                return diffInDays + " days";
            } else {
                return formatDateTime(dateTime);
            }
        } catch (Exception e) {
            return dateTime;
        }
    }
    
    private String formatDateTime(String dateTime) {
        try {
            java.text.SimpleDateFormat inputFormat = new java.text.SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
            java.text.SimpleDateFormat outputFormat = new java.text.SimpleDateFormat("MMM dd • h:mm a");
            java.util.Date date = inputFormat.parse(dateTime);
            return outputFormat.format(date);
        } catch (Exception e) {
            return dateTime;
        }
    }
    
    private String getEventIcon(String type) {
        switch (type.toLowerCase()) {
            case "workshop": return "🍽️";
            case "seminar": return "📚";
            case "webinar": return "💻";
            case "demo": return "👨‍🍳";
            case "training": return "🏥";
            default: return "📅";
        }
    }
    
    // joinEvent method removed - join buttons are no longer displayed
    
    // Method to refresh dashboard events immediately
    private void refreshDashboardEvents() {
        fetchAndDisplayDashboardEvents();
    }

    private void showImageSourceDialog() {
        String[] options = {"View Food Recommendations", "Cancel"};
        new AlertDialog.Builder(this)
            .setTitle("AI Nutrition Assistant")
            .setItems(options, new DialogInterface.OnClickListener() {
                    @Override
                public void onClick(DialogInterface dialog, int which) {
                    switch (which) {
                        case 0: // View Food Recommendations
                            showFoodRecommendations();
                            break;
                        case 1: // Cancel
                            dialog.dismiss();
                            break;
                    }
                }
            })
            .show();
    }

    
    
    
    /**
     * Show food recommendations using existing system
     */
    private void showFoodRecommendations() {
        Intent intent = new Intent(this, FoodActivity.class);
        // Don't use AI override, use the existing sophisticated food system
        startActivity(intent);
        overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
    }





    private String getCurrentUserEmail() {
        CommunityUserManager userManager = new CommunityUserManager(this);
        return userManager.getCurrentUserEmail();
    }

    private boolean userHasDietPrefs() {
        String email = getCurrentUserEmail();
        if (email == null) return false;
        android.database.Cursor cursor = dbHelper.getReadableDatabase().rawQuery("SELECT * FROM " + UserPreferencesDbHelper.TABLE_NAME + " WHERE " + UserPreferencesDbHelper.COL_USER_EMAIL + "=?", new String[]{email});
        boolean exists = cursor.moveToFirst();
        cursor.close();
        return exists;
    }

    private void showDietPrefsDialog(Runnable onComplete) {
        FilterDialog filterDialog = new FilterDialog(this, new FilterDialog.OnFilterAppliedListener() {
            @Override
            public void onFilterApplied(List<String> allergies, List<String> dietPrefs, String avoidFoods) {
                if (onComplete != null) onComplete.run();
            }
        });
        filterDialog.show();
    }
    
    private void saveUserPreferences(List<String> allergies, List<String> dietPrefs, String avoidFoods, int riskScore) {
        String email = getCurrentUserEmail();
        if (email == null) return;
        
        android.content.ContentValues values = new android.content.ContentValues();
        values.put(UserPreferencesDbHelper.COL_USER_EMAIL, email);
        values.put(UserPreferencesDbHelper.COL_ALLERGIES, join(allergies));
        values.put(UserPreferencesDbHelper.COL_DIET_PREFS, join(dietPrefs));
        values.put(UserPreferencesDbHelper.COL_AVOID_FOODS, avoidFoods);
        values.put(UserPreferencesDbHelper.COL_RISK_SCORE, riskScore);
        
        // Use UPSERT (INSERT OR REPLACE) to preserve existing data
        dbHelper.getWritableDatabase().insertWithOnConflict(
            UserPreferencesDbHelper.TABLE_NAME, 
            null, 
            values, 
            android.database.sqlite.SQLiteDatabase.CONFLICT_REPLACE
        );
    }

    private String join(List<String> list) {
        return android.text.TextUtils.join(",", list);
    }

    // Simple method to sync user preferences to API when food tab is clicked
    private void syncUserPreferencesToApi() {
        String email = getCurrentUserEmail();
        if (email == null) return;
        
        // Get current user preferences from database
        android.database.Cursor cursor = dbHelper.getReadableDatabase().rawQuery(
            "SELECT * FROM " + UserPreferencesDbHelper.TABLE_NAME + " WHERE " + UserPreferencesDbHelper.COL_USER_EMAIL + "=?",
            new String[]{email}
        );
        
        if (cursor.moveToFirst()) {
            String allergies = cursor.getString(cursor.getColumnIndex(UserPreferencesDbHelper.COL_ALLERGIES));
            String dietPrefs = cursor.getString(cursor.getColumnIndex(UserPreferencesDbHelper.COL_DIET_PREFS));
            String avoidFoods = cursor.getString(cursor.getColumnIndex(UserPreferencesDbHelper.COL_AVOID_FOODS));
            int riskScore = cursor.getInt(cursor.getColumnIndex(UserPreferencesDbHelper.COL_RISK_SCORE));
            
            cursor.close();
            
            // Convert to lists
            List<String> allergiesList = allergies != null ? Arrays.asList(allergies.split(",")) : new ArrayList<>();
            List<String> dietPrefsList = dietPrefs != null ? Arrays.asList(dietPrefs.split(",")) : new ArrayList<>();
            
            // Sync to API in background
            new Thread(() -> {
                try {
                    okhttp3.OkHttpClient client = new okhttp3.OkHttpClient();
                    
                    org.json.JSONObject json = new org.json.JSONObject();
                    json.put("action", "save_preferences");
                    json.put("email", email);
                    json.put("allergies", new org.json.JSONArray(allergiesList));
                    json.put("diet_prefs", new org.json.JSONArray(dietPrefsList));
                    json.put("avoid_foods", avoidFoods != null ? avoidFoods : "");
                    json.put("risk_score", riskScore);
                    
                    okhttp3.RequestBody body = okhttp3.RequestBody.create(
                        json.toString(), 
                        okhttp3.MediaType.parse("application/json")
                    );
                    
                    okhttp3.Request request = new okhttp3.Request.Builder()
                        .url(Constants.UNIFIED_API_URL)
                        .post(body)
                        .build();
                    
                    try (okhttp3.Response response = client.newCall(request).execute()) {
                        if (response.isSuccessful()) {
                            android.util.Log.d("MainActivity", "Preferences synced to API successfully");
                        } else {
                            android.util.Log.e("MainActivity", "Failed to sync preferences: " + response.code());
                        }
                    }
                } catch (Exception e) {
                    android.util.Log.e("MainActivity", "Error syncing preferences: " + e.getMessage());
                }
            }).start();
        } else {
            cursor.close();
        }
    }

    // Add method to handle user deletion from web
    private void handleUserDeletion(String email) {
        android.util.Log.d("MainActivity", "handleUserDeletion called for email: " + email);
        
        new Thread(() -> {
            try {
                // Delete from local SQLite
                UserPreferencesDbHelper dbHelper = new UserPreferencesDbHelper(this);
                android.database.sqlite.SQLiteDatabase db = dbHelper.getWritableDatabase();
                
                // Delete user preferences
                db.delete(UserPreferencesDbHelper.TABLE_NAME, "user_email = ?", new String[]{email});
                
                // User profile data is now in preferences table, will be deleted above
                
                // Delete user
                db.delete(UserPreferencesDbHelper.TABLE_USERS, UserPreferencesDbHelper.COL_USER_EMAIL + " = ?", new String[]{email});
                
                android.util.Log.d("MainActivity", "User deleted from local SQLite: " + email);
                
                // Clear shared preferences if it's the current user
                String currentUser = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE).getString("current_user_email", null);
                if (email.equals(currentUser)) {
                    android.util.Log.d("MainActivity", "Clearing shared preferences for current user: " + email);
                    
                    getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE).edit()
                        .remove("current_user_email")
                        .remove("current_user_name")
                        .apply();
                    
                    runOnUiThread(() -> {
                        Toast.makeText(MainActivity.this, "Your account has been deleted", Toast.LENGTH_LONG).show();
                        // Redirect to login
                        // TODO: Fix LoginActivity reference
                        // Intent intent = new Intent(MainActivity.this, LoginActivity.class);
                        // intent.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TASK);
                        // startActivity(intent);
                    });
                }
                
            } catch (Exception e) {
                android.util.Log.e("MainActivity", "Error deleting user from local SQLite: " + e.getMessage());
            }
        }).start();
    }

    // Add method to check for user deletion from web
    private void checkUserDeletion() {
        new Thread(() -> {
            try {
                String currentUser = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE).getString("current_user_email", null);
                if (currentUser == null) return;
                
                android.util.Log.d("MainActivity", "Checking if user exists: " + currentUser);
                
                // Check if user still exists in web database
                okhttp3.OkHttpClient client = new okhttp3.OkHttpClient();
                
                okhttp3.Request request = new okhttp3.Request.Builder()
                                            .url(Constants.API_BASE_URL + "unified_api.php?type=usm")
                    .build();
                
                try (okhttp3.Response response = client.newCall(request).execute()) {
                    if (response.isSuccessful()) {
                        String responseBody = response.body().string();
                        android.util.Log.d("MainActivity", "USM API response: " + responseBody);
                        
                        org.json.JSONObject data = new org.json.JSONObject(responseBody);
                        
                        // Check if the response has users field
                        if (data.has("users")) {
                            org.json.JSONArray users = data.getJSONArray("users");
                            android.util.Log.d("MainActivity", "Found " + users.length() + " users in response");
                        
                        boolean userExists = false;
                        for (int i = 0; i < users.length(); i++) {
                            org.json.JSONObject user = users.getJSONObject(i);
                            String userEmail = user.getString("email");
                            android.util.Log.d("MainActivity", "Checking user " + i + ": " + userEmail);
                            if (currentUser.equals(userEmail)) {
                                userExists = true;
                                android.util.Log.d("MainActivity", "User found in response: " + currentUser);
                                break;
                            }
                        }
                        
                        if (!userExists) {
                            android.util.Log.d("MainActivity", "User NOT found in response, handling deletion: " + currentUser);
                            // User was deleted from web, handle locally
                            handleUserDeletion(currentUser);
                        } else {
                            android.util.Log.d("MainActivity", "User exists in response: " + currentUser);
                        }
                        } else {
                            android.util.Log.e("MainActivity", "USM API response missing 'users' field");
                        }
                    } else {
                        android.util.Log.e("MainActivity", "USM API request failed with code: " + response.code());
                    }
                }
            } catch (Exception e) {
                android.util.Log.e("MainActivity", "Error checking user deletion: " + e.getMessage());
            }
        }).start();
    }

    /**
     * Check for new events efficiently - only when needed
     */
    private void checkForNewEvents() {
        String userEmail = getCurrentUserEmail();
        if (userEmail == null) {
            android.util.Log.e("MainActivity", "User email is null, cannot check for new events");
            return;
        }
        
        android.util.Log.d("MainActivity", "Checking for new events for user: " + userEmail);
        lastEventCheckTime = System.currentTimeMillis();
        
        // Use direct HTTP request to fetch events
        new Thread(() -> {
            try {
                // Fetch all events first - use event.php directly
                java.net.URL url = new java.net.URL(Constants.API_BASE_URL + "event.php?action=get_events");
                java.net.HttpURLConnection conn = (java.net.HttpURLConnection) url.openConnection();
                conn.setRequestMethod("GET");
                conn.setRequestProperty("User-Agent", "NutrisaurApp/1.0 (Android)");
                conn.setRequestProperty("Accept", "text/plain, application/json");
                conn.setConnectTimeout(15000);
                conn.setReadTimeout(15000);
                
                int responseCode = conn.getResponseCode();
                android.util.Log.d("MainActivity", "Events API response code: " + responseCode);
                
                if (responseCode == 200) {
                    java.io.BufferedReader reader = new java.io.BufferedReader(
                        new java.io.InputStreamReader(conn.getInputStream()));
                    StringBuilder response = new StringBuilder();
                    String line;
                    while ((line = reader.readLine()) != null) {
                        response.append(line);
                    }
                    reader.close();
                    
                    android.util.Log.d("MainActivity", "Events API response: " + response.toString());
                    
                    org.json.JSONObject jsonResponse = new org.json.JSONObject(response.toString());
                    if (jsonResponse.has("events")) {
                        org.json.JSONArray eventsArray = jsonResponse.getJSONArray("events");
                        android.util.Log.d("MainActivity", "Found " + eventsArray.length() + " events");
                        
                        // Check for new events
                        Set<Integer> currentEventIds = new HashSet<>();
                        List<Event> newEvents = new ArrayList<>();
                        
                        for (int i = 0; i < eventsArray.length(); i++) {
                            try {
                                org.json.JSONObject eventObj = eventsArray.getJSONObject(i);
                                int eventId = eventObj.getInt("id");
                                currentEventIds.add(eventId);
                                
                                // Check if this is a new event
                                if (!lastKnownEventIds.contains(eventId)) {
                                    Event event = new Event(
                                        eventId,
                                        eventObj.getString("title"),
                                        eventObj.getString("type"),
                                        eventObj.getString("description"),
                                        eventObj.getString("date_time"),
                                        eventObj.getString("location"),
                                        eventObj.getString("organizer"),
                                        eventObj.getLong("created_at")
                                    );
                                    newEvents.add(event);
                                    android.util.Log.d("MainActivity", "New event detected: " + event.getTitle());
                                }
                            } catch (Exception e) {
                                android.util.Log.e("MainActivity", "Error processing event at index " + i + ": " + e.getMessage());
                            }
                        }
                        
                        // Update known event IDs
                        lastKnownEventIds = currentEventIds;
                        
                        // If there are new events, refresh the dashboard
                        if (!newEvents.isEmpty()) {
                            android.util.Log.d("MainActivity", "Found " + newEvents.size() + " new events, refreshing dashboard");
                            runOnUiThread(() -> {
                                // Show notification for new events
                                for (Event newEvent : newEvents) {
                                    android.widget.Toast.makeText(MainActivity.this, 
                                        "New event: " + newEvent.getTitle(), 
                                        android.widget.Toast.LENGTH_SHORT).show();
                                }
                                // Refresh dashboard
                                fetchAndDisplayDashboardEvents();
                            });
                        } else {
                            android.util.Log.d("MainActivity", "No new events found");
                            // Still refresh dashboard to ensure data is current
                            runOnUiThread(() -> fetchAndDisplayDashboardEvents());
                        }
                    } else {
                        android.util.Log.e("MainActivity", "API returned no events data");
                        runOnUiThread(() -> {
                            showNoEventsMessage("No events data available");
                        });
                    }
                } else {
                    android.util.Log.e("MainActivity", "Failed to fetch events, response code: " + responseCode);
                    runOnUiThread(() -> {
                        showNoEventsMessage("Failed to load events");
                    });
                }
            } catch (Exception e) {
                android.util.Log.e("MainActivity", "Error checking for new events: " + e.getMessage(), e);
                runOnUiThread(() -> {
                    showNoEventsMessage("Error loading events");
                });
            }
        }).start();
    }
    
    // Method to manually clear event tracking (for debugging)
    private void clearEventTracking() {
        android.content.SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
        android.content.SharedPreferences.Editor editor = prefs.edit();
        editor.remove("known_event_ids");
        editor.remove("started_event_ids");
        editor.apply();
        android.util.Log.d("MainActivity", "Event tracking cleared manually");
    }
    
    // Method to show current tracking status (for debugging)
    private void showTrackingStatus() {
        android.content.SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
        Set<String> knownIds = prefs.getStringSet("known_event_ids", new HashSet<>());
        Set<String> startedIds = prefs.getStringSet("started_event_ids", new HashSet<>());
        
        android.util.Log.d("MainActivity", "=== TRACKING STATUS ===");
        android.util.Log.d("MainActivity", "Known events: " + knownIds.size());
        android.util.Log.d("MainActivity", "Started events: " + startedIds.size());
        for (String id : knownIds) {
            android.util.Log.d("MainActivity", "Known: " + id);
        }
        for (String id : startedIds) {
            android.util.Log.d("MainActivity", "Started: " + id);
        }
        android.util.Log.d("MainActivity", "=====================");
    }
    
    // Event checking is now handled by EventBackgroundService
    // This method has been removed to prevent duplicate event checking
    
    private void setupEventRefreshReceiver() {
        eventRefreshReceiver = new BroadcastReceiver() {
            @Override
            public void onReceive(Context context, Intent intent) {
                if ("EVENT_REFRESH_NEEDED".equals(intent.getAction())) {
                    boolean hasNewEvent = intent.getBooleanExtra("new_event", false);
                    if (hasNewEvent) {
                        String eventTitle = intent.getStringExtra("event_title");
                        Log.d("MainActivity", "Received event refresh broadcast for: " + eventTitle);
                        
                        // Refresh dashboard events immediately
                        fetchAndDisplayDashboardEvents();
                        
                        // Show toast notification
                        if (eventTitle != null) {
                            Toast.makeText(MainActivity.this, "New event: " + eventTitle, Toast.LENGTH_SHORT).show();
                        }
                    }
                }
            }
        };
        
        // Register the receiver
        IntentFilter filter = new IntentFilter("EVENT_REFRESH_NEEDED");
        if (android.os.Build.VERSION.SDK_INT >= android.os.Build.VERSION_CODES.TIRAMISU) {
            registerReceiver(eventRefreshReceiver, filter, android.content.Context.RECEIVER_NOT_EXPORTED);
        } else {
            registerReceiver(eventRefreshReceiver, filter);
        }
    }
    
    /**
     * Get user barangay from database
     */
    private String getUserBarangayFromDatabase(String userEmail) {
        try {
            if (dbHelper != null) {
                android.database.sqlite.SQLiteDatabase db = dbHelper.getReadableDatabase();
                
                // Check if table exists first
                android.database.Cursor tableCheck = db.rawQuery(
                    "SELECT name FROM sqlite_master WHERE type='table' AND name=?", 
                    new String[]{UserPreferencesDbHelper.TABLE_NAME}
                );
                
                if (tableCheck == null || !tableCheck.moveToFirst()) {
                    Log.w("MainActivity", "Table " + UserPreferencesDbHelper.TABLE_NAME + " does not exist");
                    if (tableCheck != null) tableCheck.close();
                    return "";
                }
                tableCheck.close();
                
                // Check if barangay column exists
                android.database.Cursor columnCheck = db.rawQuery(
                    "PRAGMA table_info(" + UserPreferencesDbHelper.TABLE_NAME + ")", 
                    null
                );
                
                boolean hasBarangayColumn = false;
                if (columnCheck != null) {
                    while (columnCheck.moveToNext()) {
                        String columnName = columnCheck.getString(columnCheck.getColumnIndex("name"));
                        if (UserPreferencesDbHelper.COL_BARANGAY.equals(columnName)) {
                            hasBarangayColumn = true;
                            break;
                        }
                    }
                    columnCheck.close();
                }
                
                if (!hasBarangayColumn) {
                    Log.w("MainActivity", "Column " + UserPreferencesDbHelper.COL_BARANGAY + " does not exist in table " + UserPreferencesDbHelper.TABLE_NAME);
                    return "";
                }
                
                String[] columns = {UserPreferencesDbHelper.COL_BARANGAY};
                String selection = UserPreferencesDbHelper.COL_USER_EMAIL + " = ?";
                String[] selectionArgs = {userEmail};
                
                android.database.Cursor cursor = db.query(
                    UserPreferencesDbHelper.TABLE_NAME,
                    columns,
                    selection,
                    selectionArgs,
                    null, null, null
                );
                
                String barangay = "";
                if (cursor != null && cursor.moveToFirst()) {
                    barangay = cursor.getString(cursor.getColumnIndex(UserPreferencesDbHelper.COL_BARANGAY));
                    cursor.close();
                }
                
                Log.d("MainActivity", "Fetched barangay from database for " + userEmail + ": " + barangay);
                return barangay != null ? barangay : "";
            }
        } catch (Exception e) {
            Log.e("MainActivity", "Error fetching barangay from database: " + e.getMessage());
        }
        return "";
    }
    
    private void testFirebaseConnection() {
        // Test if Firebase is working
        FirebaseMessaging.getInstance().getToken()
            .addOnCompleteListener(task -> {
                if (task.isSuccessful()) {
                    String token = task.getResult();
                    Log.d("MainActivity", "Firebase test successful. Token: " + token.substring(0, 20) + "...");
                    
                    // Test notification permission
                    if (android.os.Build.VERSION.SDK_INT >= android.os.Build.VERSION_CODES.TIRAMISU) {
                        if (checkSelfPermission(android.Manifest.permission.POST_NOTIFICATIONS) == android.content.pm.PackageManager.PERMISSION_GRANTED) {
                            Log.d("MainActivity", "Notification permission granted");
                        } else {
                            Log.w("MainActivity", "Notification test failed", task.getException());
                        }
                    }
                    
                    // Don't automatically send FCM token - wait for screening completion
                    Log.d("MainActivity", "FCM token obtained but not sent to server yet. Will be sent after screening with barangay.");
                } else {
                    Log.e("MainActivity", "Firebase test failed", task.getException());
                }
            });
    }
    

    
    private void sendTokenToServer(String token) {
        // Send FCM token to community_users API for push notifications
        new Thread(() -> {
            try {
                // Get user barangay from database if available
                String userEmail = getCurrentUserEmail();
                String userBarangay = getUserBarangayFromDatabase(userEmail);
                
                okhttp3.OkHttpClient client = new okhttp3.OkHttpClient();
                
                // Use database update format for community_users table
                org.json.JSONObject data = new org.json.JSONObject();
                data.put("fcm_token", token);
                data.put("barangay", userBarangay);
                
                org.json.JSONObject requestData = new org.json.JSONObject();
                requestData.put("table", "community_users");
                requestData.put("data", data);
                requestData.put("where", "email = ?");
                requestData.put("params", new org.json.JSONArray().put(userEmail));
                
                okhttp3.RequestBody body = okhttp3.RequestBody.create(
                    requestData.toString(), 
                    okhttp3.MediaType.parse("application/json; charset=utf-8")
                );
                
                okhttp3.Request request = new okhttp3.Request.Builder()
                    .url(Constants.API_BASE_URL + "api/DatabaseAPI.php?action=update")
                    .post(body)
                    .addHeader("Content-Type", "application/json")
                    .build();
                
                try (okhttp3.Response response = client.newCall(request).execute()) {
                    if (response.isSuccessful()) {
                        Log.d("MainActivity", "FCM token registered with community_users API successfully");
                        // Store token locally to avoid re-sending
                        getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE)
                            .edit()
                            .putString("fcm_token_sent", token)
                            .apply();
                    } else {
                        Log.e("MainActivity", "Failed to register FCM token with community_users API. Response: " + response.code());
                    }
                }
            } catch (Exception e) {
                Log.e("MainActivity", "Error sending FCM token: " + e.getMessage());
            }
        }).start();
    }
    
    private void registerFCMTokenOnStartup() {
        // FCM tokens are now only registered after screening completion
        // This prevents sending tokens without barangay information
        Log.d("MainActivity", "FCM token registration on startup disabled. Tokens will be registered after screening.");
    }
    
    /**
     * Initialize Firebase Cloud Messaging
     * Only initializes FCM if user has completed screening and has barangay data
     */
    private void initializeFirebaseMessaging() {
        // Prevent multiple initializations
        if (fcmInitialized) {
            Log.d("MainActivity", "FCM already initialized, skipping duplicate initialization");
            return;
        }
        
        if (fcmTokenManager == null) {
            Log.w("MainActivity", "FCM Token Manager not initialized yet, skipping FCM setup");
            return;
        }
        
        try {
            // Check if user has completed screening and has barangay data
            String userEmail = getCurrentUserEmail();
            if (!userEmail.isEmpty()) {
                String userBarangay = getUserBarangayFromDatabase(userEmail);
                if (!userBarangay.isEmpty()) {
                    Log.d("MainActivity", "User has barangay data, initializing FCM with user context");
                    fcmTokenManager.initializeWithUser(userEmail);
                    fcmInitialized = true; // Mark as initialized
                    Log.d("MainActivity", "FCM initialization completed successfully");
                } else {
                    Log.d("MainActivity", "User has no barangay data, FCM initialization delayed until screening completion");
                }
            } else {
                Log.d("MainActivity", "No user email found, FCM initialization delayed");
            }
        } catch (Exception e) {
            Log.e("MainActivity", "Error initializing FCM: " + e.getMessage());
        }
    }
    


    // Database helper cleanup is handled in the main onDestroy method above
    
    // Method to handle navigation to other activities
    // This temporarily pauses the background service to save resources
    private void navigateToActivity(Class<?> activityClass) {
        // Stop the background service before navigating
        Intent eventServiceIntent = new Intent(this, EventBackgroundService.class);
        stopService(eventServiceIntent);
        
        // Navigate to the target activity
        Intent intent = new Intent(this, activityClass);
        startActivity(intent);
    }
    
    // Method to handle navigation to events activity
    private void navigateToEvents() {
        navigateToActivity(EventsActivity.class);
    }
    
    /**
     * Update welcome greeting with user's name
     */
    private void updateWelcomeGreeting() {
        TextView welcomeGreeting = findViewById(R.id.welcome_greeting);
        if (welcomeGreeting != null) {
            String userEmail = getCurrentUserEmail();
            if (userEmail != null && !userEmail.isEmpty()) {
                // Extract name from email (part before @)
                String userName = userEmail.split("@")[0];
                // Capitalize first letter
                userName = userName.substring(0, 1).toUpperCase() + userName.substring(1);
                welcomeGreeting.setText("Hi " + userName + "!");
            } else {
                welcomeGreeting.setText("Hi there!");
            }
        }
    }
    
    /**
     * Setup the new action buttons
     */
    private void setupActionButtons() {
        // Find personalized food button
        androidx.cardview.widget.CardView findFoodButton = findViewById(R.id.find_food_button);
        if (findFoodButton != null) {
            findFoodButton.setOnClickListener(new View.OnClickListener() {
                @Override
                public void onClick(View v) {
                    // Navigate to FoodActivity
                    Intent intent = new Intent(MainActivity.this, FoodActivity.class);
                    startActivity(intent);
                    overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
                    finish();
                }
            });
        }
        
        // Take picture button removed - camera functionality disabled
    }








}