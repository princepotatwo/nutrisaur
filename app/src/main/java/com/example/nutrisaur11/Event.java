package com.example.nutrisaur11;

import java.util.Date;
import android.graphics.Color;

public class Event {
    private int programId;
    private String title;
    private String type;
    private String description;
    private String dateTime;
    private String location;
    private String organizer;
    private long createdAt;
    private boolean isJoined;

    public Event() {
        // Default constructor
    }

    public Event(int programId, String title, String type, String description, 
                 String dateTime, String location, String organizer, long createdAt) {
        this.programId = programId;
        this.title = title;
        this.type = type;
        this.description = description;
        this.dateTime = dateTime;
        this.location = location;
        this.organizer = organizer;
        this.createdAt = createdAt;
        this.isJoined = false;
    }

    // Getters and Setters
    public int getProgramId() {
        return programId;
    }

    public void setProgramId(int programId) {
        this.programId = programId;
    }

    public String getTitle() {
        return title;
    }

    public void setTitle(String title) {
        this.title = title;
    }

    public String getType() {
        return type;
    }

    public void setType(String type) {
        this.type = type;
    }

    public String getDescription() {
        return description;
    }

    public void setDescription(String description) {
        this.description = description;
    }

    public String getDateTime() {
        return dateTime;
    }

    public void setDateTime(String dateTime) {
        this.dateTime = dateTime;
    }

    public String getLocation() {
        return location;
    }

    public void setLocation(String location) {
        this.location = location;
    }

    public String getOrganizer() {
        return organizer;
    }

    public void setOrganizer(String organizer) {
        this.organizer = organizer;
    }

    public long getCreatedAt() {
        return createdAt;
    }

    public void setCreatedAt(long createdAt) {
        this.createdAt = createdAt;
    }

    public boolean isJoined() {
        return isJoined;
    }

    public void setJoined(boolean joined) {
        isJoined = joined;
    }

    // Helper method to get formatted date
    public String getFormattedDate() {
        try {
            // Parse the date string and format it
            java.text.SimpleDateFormat inputFormat = new java.text.SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
            java.text.SimpleDateFormat outputFormat = new java.text.SimpleDateFormat("MMM dd • h:mm a");
            Date date = inputFormat.parse(dateTime);
            return outputFormat.format(date);
        } catch (Exception e) {
            return dateTime; // Return original if parsing fails
        }
    }

    // Helper method to get relative time (Today, Tomorrow, etc.)
    public String getRelativeTime() {
        try {
            java.text.SimpleDateFormat format = new java.text.SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
            Date eventDate = format.parse(dateTime);
            Date now = new Date();
            
            long diffInMillis = eventDate.getTime() - now.getTime();
            long diffInDays = diffInMillis / (24 * 60 * 60 * 1000);
            
            if (diffInDays == 0) {
                return "Today";
            } else if (diffInDays == 1) {
                return "Tomorrow";
            } else if (diffInDays < 7) {
                return diffInDays + " days";
            } else {
                return getFormattedDate();
            }
        } catch (Exception e) {
            return dateTime;
        }
    }

    // Helper method to get event icon based on type
    public String getEventIcon() {
        switch (type.toLowerCase()) {
            case "workshop":
                return "🍽️";
            case "seminar":
                return "📚";
            case "webinar":
                return "💻";
            case "demo":
                return "👨‍🍳";
            case "training":
                return "🏥";
            default:
                return "📅";
        }
    }

    // Helper method to get event color based on type
    public int getEventColor() {
        switch (type.toLowerCase()) {
            case "workshop":
                return Color.parseColor("#10B981"); // Green
            case "seminar":
                return Color.parseColor("#3B82F6"); // Blue
            case "webinar":
                return Color.parseColor("#8B5CF6"); // Purple
            case "demo":
                return Color.parseColor("#F59E0B"); // Orange
            case "training":
                return Color.parseColor("#EF4444"); // Red
            default:
                return Color.parseColor("#6B7280"); // Gray
        }
    }

    public boolean isPastEvent() {
        try {
            java.text.SimpleDateFormat format = new java.text.SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
            Date eventDate = format.parse(dateTime);
            Date now = new Date();
            return eventDate.before(now);
        } catch (Exception e) {
            return false;
        }
    }
} 