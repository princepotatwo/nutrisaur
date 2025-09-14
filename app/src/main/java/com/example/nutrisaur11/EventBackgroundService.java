package com.example.nutrisaur11;

import android.app.Service;
import android.content.Intent;
import android.os.IBinder;
import android.os.Handler;
import android.os.Looper;
import android.content.SharedPreferences;
import java.util.Set;
import java.util.HashSet;
import java.util.List;
import java.util.ArrayList;
import java.util.concurrent.Executors;
import java.util.concurrent.ScheduledExecutorService;
import java.util.concurrent.TimeUnit;
import java.util.concurrent.CompletableFuture;
import com.example.nutrisaur11.Event;
import com.example.nutrisaur11.Constants;
// Removed WebViewAPIClient - using direct HTTP requests

import android.content.Context;

public class EventBackgroundService extends Service {
    private static final long CHECK_INTERVAL = 3 * 1000; // 3 seconds (real-time event detection)
    private ScheduledExecutorService scheduler;
    private Handler mainHandler;
    
    @Override
    public void onCreate() {
        super.onCreate();
        mainHandler = new Handler(Looper.getMainLooper());
        scheduler = Executors.newScheduledThreadPool(1);
    }
    
    @Override
    public int onStartCommand(Intent intent, int flags, int startId) {
        android.util.Log.d("EventBackgroundService", "EventBackgroundService started with startId: " + startId);
        
        // Check if this is a foreground service request or if we should run in background
        boolean isForeground = intent != null && intent.getBooleanExtra("foreground", false);
        
        if (isForeground) {
            // Start periodic checking only when dashboard is visible
            startPeriodicChecking();
            return START_STICKY; // Restart service if killed
        } else {
            // If not foreground, just do a single check and stop
            // This is for when the app is in background but we still want to check for critical events
            checkForNewEvents();
            // Schedule self-destruction after a single check
            new Handler().postDelayed(() -> {
                android.util.Log.d("EventBackgroundService", "Background check completed, stopping service");
                stopSelf();
            }, 10000); // Give 10 seconds for the check to complete
            return START_NOT_STICKY;
        }
    }
    
    private void startPeriodicChecking() {
        android.util.Log.d("EventBackgroundService", "Starting periodic event checking every " + CHECK_INTERVAL + "ms");
        scheduler.scheduleAtFixedRate(new Runnable() {
            @Override
            public void run() {
                android.util.Log.d("EventBackgroundService", "Running scheduled event check...");
                checkForNewEvents();
            }
        }, 0, CHECK_INTERVAL, TimeUnit.MILLISECONDS);
    }
    
    // Removed WebViewAPIClient - using direct HTTP requests
    
    private void checkForNewEvents() {
        // Use direct HTTP request to check for events
        new Thread(() -> {
            try {
                android.util.Log.d("EventBackgroundService", "Starting event check with direct HTTP...");
                
                // Make direct HTTP request to check for events
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
                    
                    android.util.Log.d("EventBackgroundService", "HTTP API response: " + response.toString());
                    
                    org.json.JSONObject jsonResponse = new org.json.JSONObject(response.toString());
                    
                    // Check if events array exists
                    if (jsonResponse.has("events")) {
                        org.json.JSONArray eventsArray = jsonResponse.getJSONArray("events");
                            android.util.Log.d("EventBackgroundService", "Found " + eventsArray.length() + " events");
                            
                            // Get existing event IDs from SharedPreferences
                            SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
                            Set<String> existingEventIds = prefs.getStringSet("known_event_ids", new HashSet<>());
                            Set<String> newEventIds = new HashSet<>();
                            
                            android.util.Log.d("EventBackgroundService", "Currently tracking " + existingEventIds.size() + " events");
                            
                            // Check for new events and event start notifications
                            for (int i = 0; i < eventsArray.length(); i++) {
                                org.json.JSONObject eventObj = eventsArray.getJSONObject(i);
                                String eventId = String.valueOf(eventObj.getInt("id"));
                                
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
                                
                                // Check if this is a truly new event (FIXED: Match MainActivity logic)
                                if (!existingEventIds.contains(eventId)) {
                                    android.util.Log.d("EventBackgroundService", "NEW EVENT DETECTED: " + event.getTitle() + " (ID: " + eventId + ")");
                                    
                                    // Check if we already notified about this event (prevent duplicate notifications)
                                    android.content.SharedPreferences notifyPrefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
                                    Set<String> notifiedEventIds = notifyPrefs.getStringSet("notified_event_ids", new HashSet<>());
                                    
                                    if (!notifiedEventIds.contains(eventId)) {
                                        // Show notification for new event on main thread
                                        mainHandler.post(() -> {
                                            android.util.Log.d("EventBackgroundService", "New event detected: " + event.getTitle());
                                            // Notify MainActivity to refresh dashboard
                                            Intent refreshIntent = new Intent("EVENT_REFRESH_NEEDED");
                                            refreshIntent.putExtra("new_event", true);
                                            refreshIntent.putExtra("event_title", event.getTitle());
                                            sendBroadcast(refreshIntent);
                                            // Firebase Cloud Messaging will handle notifications automatically
                                        });
                                        
                                        // Mark this event as notified
                                        Set<String> updatedNotifiedIds = new HashSet<>(notifiedEventIds);
                                        updatedNotifiedIds.add(eventId);
                                        android.content.SharedPreferences.Editor editor = notifyPrefs.edit();
                                        editor.putStringSet("notified_event_ids", updatedNotifiedIds);
                                        editor.apply();
                                        
                                        android.util.Log.d("EventBackgroundService", "New event notification sent: " + event.getTitle() + " (ID: " + eventId + ")");
                                    } else {
                                        android.util.Log.d("EventBackgroundService", "Event already notified about: " + event.getTitle() + " (ID: " + eventId + ")");
                                    }
                                    
                                    newEventIds.add(eventId);
                                } else {
                                    // Event already known, just add to tracking
                                    newEventIds.add(eventId);
                                    android.util.Log.d("EventBackgroundService", "Known event tracked: " + event.getTitle() + " (ID: " + eventId + ")");
                                }
                                
                                // Check if event is starting now (within 2 minutes of start time - more restrictive)
                                try {
                                    java.text.SimpleDateFormat format = new java.text.SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
                                    java.util.Date eventDate = format.parse(event.getDateTime());
                                    long eventTime = eventDate.getTime();
                                    long currentTime = System.currentTimeMillis();
                                    long timeDiff = eventTime - currentTime;
                                    
                                    // If event is starting within 2 minutes and not more than 2 minutes ago (more restrictive)
                                    if (timeDiff >= -120000 && timeDiff <= 120000) { // ±2 minutes
                                        // Check if we already notified about this event starting
                                        android.content.SharedPreferences startPrefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
                                        Set<String> startedEventIds = startPrefs.getStringSet("started_event_ids", new HashSet<>());
                                        
                                        if (!startedEventIds.contains(eventId)) {
                                            // Show event start notification
                                            mainHandler.post(() -> {
                                                android.util.Log.d("EventBackgroundService", "New event detected: " + event.getTitle());
                                                // Firebase Cloud Messaging will handle notifications automatically
                                            });
                                            
                                            // Mark this event as notified (FIXED: Create new Set)
                                            Set<String> updatedStartedIds = new HashSet<>(startedEventIds);
                                            updatedStartedIds.add(eventId);
                                            android.content.SharedPreferences.Editor editor = startPrefs.edit();
                                            editor.putStringSet("started_event_ids", updatedStartedIds);
                                            editor.apply();
                                        }
                                    }
                                } catch (Exception e) {
                                    e.printStackTrace();
                                }
                            }
                            
                            // Update known event IDs (FIXED: Create new Set to avoid SharedPreferences bug)
                            if (!newEventIds.isEmpty()) {
                                // Create a new Set instead of modifying the existing one
                                Set<String> updatedEventIds = new HashSet<>(existingEventIds);
                                updatedEventIds.addAll(newEventIds);
                                
                                // Limit the size of tracked events to prevent memory issues (keep last 1000)
                                if (updatedEventIds.size() > 1000) {
                                    // Convert to list, take last 1000, convert back to set
                                    List<String> eventList = new ArrayList<>(updatedEventIds);
                                    updatedEventIds = new HashSet<>(eventList.subList(eventList.size() - 1000, eventList.size()));
                                }
                                
                                android.content.SharedPreferences.Editor editor = prefs.edit();
                                editor.putStringSet("known_event_ids", updatedEventIds);
                                editor.apply();
                                
                                // Debug: Log the update
                                android.util.Log.d("EventBackgroundService", "Updated tracking: " + existingEventIds.size() + " -> " + updatedEventIds.size() + " events");
                            } else {
                                android.util.Log.d("EventBackgroundService", "No new events to track");
                            }
                    } else {
                        android.util.Log.e("EventBackgroundService", "API response missing events array");
                    }
                } else {
                    android.util.Log.e("EventBackgroundService", "Failed to fetch events, response code: " + responseCode);
                }
            } catch (Exception e) {
                android.util.Log.e("EventBackgroundService", "Error checking for events: " + e.getMessage(), e);
            }
        }).start();
    }
    
    @Override
    public IBinder onBind(Intent intent) {
        return null;
    }
    
    @Override
    public void onDestroy() {
        super.onDestroy();
        android.util.Log.d("EventBackgroundService", "EventBackgroundService being destroyed, shutting down scheduler");
        if (scheduler != null) {
            scheduler.shutdown();
        }
    }
    
    // Method to manually clear tracking data for testing
    public static void clearTrackingData(Context context) {
        android.util.Log.d("EventBackgroundService", "Clearing tracking data for testing...");
        android.content.SharedPreferences prefs = context.getSharedPreferences("nutrisaur_prefs", android.content.Context.MODE_PRIVATE);
        android.content.SharedPreferences.Editor editor = prefs.edit();
        editor.remove("known_event_ids");
        editor.remove("started_event_ids");
        editor.remove("notified_event_ids"); // Also clear notification tracking
        editor.apply();
        android.util.Log.d("EventBackgroundService", "Tracking data cleared (including notification tracking)");
    }
    
    // Method to manually trigger event checking for testing
    public static void triggerEventCheck(Context context) {
        android.util.Log.d("EventBackgroundService", "Manually triggering event check...");
        android.content.Intent eventServiceIntent = new android.content.Intent(context, EventBackgroundService.class);
        context.startService(eventServiceIntent);
    }
} 