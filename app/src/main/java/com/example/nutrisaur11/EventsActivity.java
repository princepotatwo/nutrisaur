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
                // Fetch all events
                String apiUrl = Constants.UNIFIED_API_URL + "?type=events";
                java.net.URL url = new java.net.URL(apiUrl);
                java.net.HttpURLConnection conn = (java.net.HttpURLConnection) url.openConnection();
                conn.setRequestMethod("GET");
                conn.setRequestProperty("User-Agent", "Mozilla/5.0 (Android) Nutrisaur-App/1.0");
                conn.setRequestProperty("Accept", "application/json, text/plain, */*");
                conn.setRequestProperty("Accept-Language", "en-US,en;q=0.9");
                conn.setRequestProperty("Accept-Encoding", "gzip, deflate, br");
                conn.setRequestProperty("Connection", "keep-alive");
                conn.setRequestProperty("Cache-Control", "no-cache");
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
                        org.json.JSONObject requestData = new org.json.JSONObject();
                        requestData.put("action", "get_user_events");
                        requestData.put("user_email", currentUserEmail);
                        java.net.URL url2 = new java.net.URL(Constants.UNIFIED_API_URL);
                        java.net.HttpURLConnection conn2 = (java.net.HttpURLConnection) url2.openConnection();
                        conn2.setRequestMethod("POST");
                        conn2.setRequestProperty("Content-Type", "application/json");
                        conn2.setDoOutput(true);
                        java.io.OutputStream os = conn2.getOutputStream();
                        os.write(requestData.toString().getBytes("UTF-8"));
                        os.close();
                        int responseCode2 = conn2.getResponseCode();
                        List<Integer> joinedIds = new ArrayList<>();
                        if (responseCode2 == 200) {
                            java.io.BufferedReader reader2 = new java.io.BufferedReader(
                                new java.io.InputStreamReader(conn2.getInputStream()));
                            StringBuilder response2 = new StringBuilder();
                            String line2;
                            while ((line2 = reader2.readLine()) != null) {
                                response2.append(line2);
                            }
                            reader2.close();
                            org.json.JSONObject jsonResponse2 = new org.json.JSONObject(response2.toString());
                            if (jsonResponse2.has("success") && jsonResponse2.getBoolean("success")) {
                                org.json.JSONArray userEvents = jsonResponse2.getJSONArray("user_events");
                                for (int i = 0; i < userEvents.length(); i++) {
                                    org.json.JSONObject userEvent = userEvents.getJSONObject(i);
                                    joinedIds.add(userEvent.getInt("id"));
                                }
                            }
                        }
                        // Now update events list with joinedIds and sort
                        List<Event> newEvents = new ArrayList<>();
                        java.text.SimpleDateFormat format = new java.text.SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
                        java.util.Date now = new java.util.Date();
                        for (int i = 0; i < eventsArray.length(); i++) {
                            org.json.JSONObject eventObj = eventsArray.getJSONObject(i);
                            Event event = new Event(
                                eventObj.getInt("id"),
                                eventObj.getString("title"),
                                eventObj.getString("type"),
                                eventObj.getString("description"),
                                eventObj.getString("date_time"),
                                eventObj.getString("location"),
                                eventObj.getString("organizer"),
                                eventObj.getLong("created_at")
                            );
                            if (joinedIds.contains(event.getProgramId())) {
                                event.setJoined(true);
                            }
                            newEvents.add(event);
                        }
                        // Sort: upcoming first, past at bottom
                        java.util.Collections.sort(newEvents, (e1, e2) -> {
                            boolean past1 = e1.isPastEvent();
                            boolean past2 = e2.isPastEvent();
                            if (past1 != past2) return past1 ? 1 : -1;
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
                    }
                }
            } catch (Exception e) {
                runOnUiThread(() -> {
                    showLoading(false);
                    Toast.makeText(EventsActivity.this,
                        "Error loading events: " + e.getMessage(),
                        Toast.LENGTH_SHORT).show();
                });
            }
        }).start();
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
    
    @Override
    public void onJoinEvent(Event event, int position) {
        // Join event via API
        joinEvent(event, position);
    }
    
    @Override
    public void onEventClick(Event event) {
        // Show event details dialog
        showEventDetails(event);
    }
    
    private void joinEvent(Event event, int position) {
        new Thread(() -> {
            try {
                String apiUrl = Constants.UNIFIED_API_URL;
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
                        runOnUiThread(() -> {
                            event.setJoined(!event.isJoined());
                            adapter.notifyItemChanged(position);
                        });
                    } else {
                        runOnUiThread(() -> {
                            try {
                                Toast.makeText(EventsActivity.this,
                                    jsonResponse.getString("error"),
                                    Toast.LENGTH_SHORT).show();
                            } catch (org.json.JSONException e) {
                                Toast.makeText(EventsActivity.this,
                                    "Error joining/unjoining event",
                                    Toast.LENGTH_SHORT).show();
                            }
                        });
                    }
                } else {
                    runOnUiThread(() -> {
                        Toast.makeText(EventsActivity.this,
                            "Error joining/unjoining event. Please try again.",
                            Toast.LENGTH_SHORT).show();
                    });
                }
            } catch (Exception e) {
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
        
        if (!event.isJoined()) {
            builder.setNegativeButton("Join Event", (dialog, which) -> {
                onJoinEvent(event, events.indexOf(event));
            });
        }
        
        builder.show();
    }
} 