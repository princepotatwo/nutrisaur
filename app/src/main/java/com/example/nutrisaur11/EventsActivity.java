package com.example.nutrisaur11;

import android.os.Bundle;
import android.view.View;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import org.json.JSONArray;
import org.json.JSONObject;
import java.util.ArrayList;
import java.util.List;
import com.example.nutrisaur11.Constants;

public class EventsActivity extends AppCompatActivity implements EventAdapter.OnEventClickListener {
    
    private RecyclerView recyclerView;
    private EventAdapter adapter;
    private List<Event> events;
    private ProgressBar loadingIndicator;
    private TextView noEventsMessage;
    private String currentUserEmail;
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_events);
        
        // Initialize views
        recyclerView = findViewById(R.id.events_recycler_view);
        loadingIndicator = findViewById(R.id.events_loading);
        noEventsMessage = findViewById(R.id.no_events_message);
        
        // Get current user email
        android.content.SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
        currentUserEmail = prefs.getString("user_email", "");
        
        // Setup RecyclerView
        events = new ArrayList<>();
        adapter = new EventAdapter(events, this);
        recyclerView.setLayoutManager(new LinearLayoutManager(this));
        recyclerView.setAdapter(adapter);
        
        // Setup back button
        findViewById(R.id.back_button).setOnClickListener(v -> finish());
        
        // Load events
        loadEvents();
    }
    
    private void loadEvents() {
        showLoading(true);
        new Thread(() -> {
            try {
                // Use event.php directly to get events
                String apiUrl = Constants.API_BASE_URL + "event.php?action=get_events";
                java.net.URL url = new java.net.URL(apiUrl);
                java.net.HttpURLConnection conn = (java.net.HttpURLConnection) url.openConnection();
                conn.setRequestMethod("GET");
                conn.setRequestProperty("User-Agent", "NutrisaurApp/1.0 (Android)");
                conn.setRequestProperty("Accept", "text/plain, application/json");
                conn.setConnectTimeout(15000);
                conn.setReadTimeout(15000);
                
                int responseCode = conn.getResponseCode();
                android.util.Log.d("EventsActivity", "Events API response code: " + responseCode);
                
                if (responseCode == 200) {
                    java.io.BufferedReader reader = new java.io.BufferedReader(
                        new java.io.InputStreamReader(conn.getInputStream()));
                    StringBuilder response = new StringBuilder();
                    String line;
                    while ((line = reader.readLine()) != null) {
                        response.append(line);
                    }
                    reader.close();
                    
                    android.util.Log.d("EventsActivity", "Events API response: " + response.toString());
                    
                    // Parse the response - event.php returns events in a JSON object with events array
                    org.json.JSONObject jsonResponse = new org.json.JSONObject(response.toString());
                    org.json.JSONArray eventsArray = jsonResponse.getJSONArray("events");
                    
                    // Fetch joined events for this user using event.php
                    List<Integer> joinedIds = getJoinedEventIds();
                    
                    // Process events
                    List<Event> newEvents = new ArrayList<>();
                    java.text.SimpleDateFormat format = new java.text.SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
                    java.util.Date now = new java.util.Date();
                    
                    for (int i = 0; i < eventsArray.length(); i++) {
                        org.json.JSONObject eventObj = eventsArray.getJSONObject(i);
                        
                        // Handle created_at field that might be boolean false or timestamp
                        long createdAt = 0;
                        try {
                            if (eventObj.get("created_at") instanceof Boolean) {
                                createdAt = System.currentTimeMillis();
                            } else {
                                createdAt = eventObj.getLong("created_at");
                            }
                        } catch (Exception e) {
                            createdAt = System.currentTimeMillis();
                        }
                        
                        Event event = new Event(
                            eventObj.getInt("program_id"), // Use program_id from database
                            eventObj.getString("title"),
                            eventObj.getString("type"),
                            eventObj.getString("description"),
                            eventObj.getString("date_time"),
                            eventObj.getString("location"),
                            eventObj.getString("organizer"),
                            createdAt
                        );
                        
                        if (joinedIds.contains(event.getProgramId())) {
                            event.setJoined(true);
                        }
                        
                        newEvents.add(event);
                        android.util.Log.d("EventsActivity", "Added event: " + event.getTitle());
                    }
                    
                    // Sort: upcoming first, past at bottom
                    java.util.Collections.sort(newEvents, (e1, e2) -> {
                        try {
                            java.text.SimpleDateFormat f = new java.text.SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
                            java.util.Date d1 = f.parse(e1.getDateTime());
                            java.util.Date d2 = f.parse(e2.getDateTime());
                            return d1.compareTo(d2);
                        } catch (Exception ex) { return 0; }
                    });
                    
                    runOnUiThread(() -> {
                        events.clear();
                        events.addAll(newEvents);
                        adapter.notifyDataSetChanged();
                        showLoading(false);
                        updateEmptyState();
                    });
                } else {
                    runOnUiThread(() -> {
                        showLoading(false);
                        Toast.makeText(EventsActivity.this,
                            "Error loading events. Response code: " + responseCode,
                            Toast.LENGTH_SHORT).show();
                    });
                }
            } catch (Exception e) {
                android.util.Log.e("EventsActivity", "Error loading events: " + e.getMessage());
                runOnUiThread(() -> {
                    showLoading(false);
                    Toast.makeText(EventsActivity.this,
                        "Error loading events: " + e.getMessage(),
                        Toast.LENGTH_SHORT).show();
                });
            }
        }).start();
    }
    
    private List<Integer> getJoinedEventIds() {
        List<Integer> joinedIds = new ArrayList<>();
        try {
            // Use event.php to get user's joined events
            String apiUrl = Constants.API_BASE_URL + "event.php";
            org.json.JSONObject requestData = new org.json.JSONObject();
            requestData.put("action", "get_user_events");
            requestData.put("user_email", currentUserEmail);
            
            java.net.URL url = new java.net.URL(apiUrl);
            java.net.HttpURLConnection conn = (java.net.HttpURLConnection) url.openConnection();
            conn.setRequestMethod("POST");
            conn.setRequestProperty("Content-Type", "application/json");
            conn.setDoOutput(true);
            conn.setConnectTimeout(15000);
            conn.setReadTimeout(15000);
            
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
                if (jsonResponse.has("success") && jsonResponse.getBoolean("success")) {
                    org.json.JSONArray userEvents = jsonResponse.getJSONArray("user_events");
                    for (int i = 0; i < userEvents.length(); i++) {
                        org.json.JSONObject userEvent = userEvents.getJSONObject(i);
                        joinedIds.add(userEvent.getInt("program_id"));
                    }
                }
            }
        } catch (Exception e) {
            android.util.Log.e("EventsActivity", "Error getting joined events: " + e.getMessage());
        }
        return joinedIds;
    }
    
    private void showLoading(boolean show) {
        loadingIndicator.setVisibility(show ? View.VISIBLE : View.GONE);
        recyclerView.setVisibility(show ? View.GONE : View.VISIBLE);
    }
    
    private void updateEmptyState() {
        if (events.isEmpty()) {
            noEventsMessage.setVisibility(View.VISIBLE);
            recyclerView.setVisibility(View.GONE);
        } else {
            noEventsMessage.setVisibility(View.GONE);
            recyclerView.setVisibility(View.VISIBLE);
        }
    }
    
    // onJoinEvent method removed - join buttons are no longer displayed
    
    @Override
    public void onEventClick(Event event) {
        // Show event details dialog
        showEventDetails(event);
    }
    
    private void joinEvent(Event event, int position) {
        new Thread(() -> {
            try {
                // Use event.php for join/leave operations
                String apiUrl = Constants.API_BASE_URL + "event.php";
                org.json.JSONObject requestData = new org.json.JSONObject();
                if (event.isJoined()) {
                    requestData.put("action", "leave_event");
                } else {
                    requestData.put("action", "join_event");
                }
                requestData.put("program_id", event.getProgramId());
                requestData.put("user_email", currentUserEmail);
                
                java.net.URL url = new java.net.URL(apiUrl);
                java.net.HttpURLConnection conn = (java.net.HttpURLConnection) url.openConnection();
                conn.setRequestMethod("POST");
                conn.setRequestProperty("Content-Type", "application/json");
                conn.setDoOutput(true);
                conn.setConnectTimeout(15000);
                conn.setReadTimeout(15000);
                
                java.io.OutputStream os = conn.getOutputStream();
                os.write(requestData.toString().getBytes("UTF-8"));
                os.close();
                
                int responseCode = conn.getResponseCode();
                android.util.Log.d("EventsActivity", "Join/Leave API response code: " + responseCode);
                
                if (responseCode == 200) {
                    java.io.BufferedReader reader = new java.io.BufferedReader(
                        new java.io.InputStreamReader(conn.getInputStream()));
                    StringBuilder response = new StringBuilder();
                    String line;
                    while ((line = reader.readLine()) != null) {
                        response.append(line);
                    }
                    reader.close();
                    
                    android.util.Log.d("EventsActivity", "Join/Leave API response: " + response.toString());
                    
                    org.json.JSONObject jsonResponse = new org.json.JSONObject(response.toString());
                    if (jsonResponse.has("success") && jsonResponse.getBoolean("success")) {
                        runOnUiThread(() -> {
                            event.setJoined(!event.isJoined());
                            adapter.notifyItemChanged(position);
                            String action = event.isJoined() ? "joined" : "left";
                            Toast.makeText(EventsActivity.this,
                                "Successfully " + action + " event: " + event.getTitle(),
                                Toast.LENGTH_SHORT).show();
                        });
                    } else {
                        runOnUiThread(() -> {
                            try {
                                String errorMsg = jsonResponse.has("error") ? 
                                    jsonResponse.getString("error") : "Unknown error occurred";
                                Toast.makeText(EventsActivity.this, errorMsg, Toast.LENGTH_SHORT).show();
                            } catch (org.json.JSONException e) {
                                Toast.makeText(EventsActivity.this,
                                    "Error joining/leaving event",
                                    Toast.LENGTH_SHORT).show();
                            }
                        });
                    }
                } else {
                    runOnUiThread(() -> {
                        Toast.makeText(EventsActivity.this,
                            "Error joining/leaving event. Response code: " + responseCode,
                            Toast.LENGTH_SHORT).show();
                    });
                }
            } catch (Exception e) {
                android.util.Log.e("EventsActivity", "Error in join/leave event: " + e.getMessage());
                runOnUiThread(() -> {
                    Toast.makeText(EventsActivity.this,
                        "Error: " + e.getMessage(),
                        Toast.LENGTH_SHORT).show();
                });
            }
        }).start();
    }
    
    private void showEventDetails(Event event) {
        android.app.AlertDialog.Builder builder = new android.app.AlertDialog.Builder(this);
        builder.setTitle(event.getTitle());
        
        String details = "Type: " + event.getType() + "\n" +
                        "Date: " + event.getFormattedDate() + "\n" +
                        "Location: " + event.getLocation() + "\n" +
                        "Organizer: " + event.getOrganizer() + "\n\n" +
                        "Description:\n" + event.getDescription();
        
        builder.setMessage(details);
        builder.setPositiveButton("Close", null);
        
        // Join functionality removed - events are display-only
        builder.show();
    }
} 