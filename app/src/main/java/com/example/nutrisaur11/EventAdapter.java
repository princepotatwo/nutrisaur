package com.example.nutrisaur11;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.Button;
import android.widget.LinearLayout;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import java.util.List;

public class EventAdapter extends RecyclerView.Adapter<EventAdapter.EventViewHolder> {
    
    private List<Event> events;
    private OnEventClickListener listener;
    
    public interface OnEventClickListener {
        void onJoinEvent(Event event, int position);
        void onEventClick(Event event);
    }
    
    public EventAdapter(List<Event> events, OnEventClickListener listener) {
        this.events = events;
        this.listener = listener;
    }
    
    @NonNull
    @Override
    public EventViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext())
                .inflate(R.layout.event_item, parent, false);
        return new EventViewHolder(view);
    }
    
    @Override
    public void onBindViewHolder(@NonNull EventViewHolder holder, int position) {
        Event event = events.get(position);
        holder.bind(event);
    }
    
    @Override
    public int getItemCount() {
        return events.size();
    }
    
    public void updateEvents(List<Event> newEvents) {
        this.events = newEvents;
        notifyDataSetChanged();
    }
    
    class EventViewHolder extends RecyclerView.ViewHolder {
        private LinearLayout eventIcon;
        private TextView eventTitle;
        private TextView eventDetails;
        private Button joinButton;
        
        public EventViewHolder(@NonNull View itemView) {
            super(itemView);
            eventIcon = itemView.findViewById(R.id.event_icon);
            eventTitle = itemView.findViewById(R.id.event_title);
            eventDetails = itemView.findViewById(R.id.event_details);
            joinButton = itemView.findViewById(R.id.join_button);
        }
        
        public void bind(Event event) {
            eventTitle.setText(event.getTitle());
            eventDetails.setText(event.getRelativeTime() + " • " + event.getFormattedDate() + " • " + event.getLocation());
            
            // Set event icon
            TextView iconText = eventIcon.findViewById(R.id.icon_text);
            iconText.setText(event.getEventIcon());
            
            // Set event color
            eventIcon.setBackgroundTintList(android.content.res.ColorStateList.valueOf(event.getEventColor()));
            
            // Check event status and set appropriate button state
            long currentTime = System.currentTimeMillis();
            long eventTime = 0;
            long eventEndTime = 0;
            
            try {
                java.text.SimpleDateFormat format = new java.text.SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
                java.util.Date eventDate = format.parse(event.getDateTime());
                eventTime = eventDate.getTime();
                // Events finish immediately when current time passes event time
                eventEndTime = eventTime;
            } catch (Exception e) {
                e.printStackTrace();
            }
            
            // Determine event status with more lenient logic
            boolean isPastEvent = event.isPastEvent();
            boolean isEventStarted = currentTime >= eventTime && currentTime < eventEndTime;
            boolean isEventFinished = currentTime >= eventTime; // Events finish immediately when current time passes event time
            
            // Calculate days difference for more lenient display
            long diffInMillis = eventTime - currentTime;
            long diffInDays = diffInMillis / (24 * 60 * 60 * 1000);
            
            if (diffInDays < -7) {
                // Very old event (more than 7 days ago) - GRAY and disabled
                joinButton.setText("Finished");
                joinButton.setEnabled(false);
                joinButton.setSelected(false);
                itemView.setAlpha(0.3f);
                joinButton.setBackgroundResource(R.drawable.outline_gray_button);
                itemView.setBackgroundResource(R.drawable.event_card_gray);
            } else if (isEventFinished && diffInDays >= -7) {
                // Recent past event (within 7 days) - Show but disabled
                joinButton.setText("Finished");
                joinButton.setEnabled(false);
                joinButton.setSelected(event.isJoined());
                itemView.setAlpha(0.7f);
                joinButton.setBackgroundResource(R.drawable.outline_gray_button);
                itemView.setBackgroundResource(R.drawable.event_card_white);
            } else if (isEventStarted) {
                // Event is currently happening - WHITE
                joinButton.setText("Join Now");
                joinButton.setEnabled(true);
                joinButton.setSelected(false);
                itemView.setAlpha(1.0f);
                joinButton.setBackgroundResource(R.drawable.filled_green_button);
                itemView.setBackgroundResource(R.drawable.event_card_white);
            } else {
                // Upcoming event - WHITE
                itemView.setAlpha(1.0f);
                joinButton.setText(event.isJoined() ? "Unjoin" : "Join");
                joinButton.setEnabled(true);
                joinButton.setSelected(event.isJoined());
                joinButton.setBackgroundResource(event.isJoined() ? R.drawable.filled_green_button : R.drawable.outline_green_button);
                itemView.setBackgroundResource(R.drawable.event_card_white);
            }
            
            // Ensure button is always visible and properly styled
            joinButton.setVisibility(View.VISIBLE);
            joinButton.setClickable(true);
            joinButton.setFocusable(true);
            
            // Force text to be visible and set properly
            if (isEventFinished || isPastEvent) {
                joinButton.setTextColor(android.graphics.Color.parseColor("#9CA3AF")); // Gray for finished/past
            } else if (isEventStarted) {
                joinButton.setTextColor(android.graphics.Color.WHITE); // White for active events
            } else {
                joinButton.setTextColor(event.isJoined() ? android.graphics.Color.WHITE : android.graphics.Color.parseColor("#10B981")); // White for joined, green for unjoined
            }
            joinButton.setTypeface(android.graphics.Typeface.DEFAULT_BOLD);
            
            // Debug: Log the button text and status
            android.util.Log.d("EventAdapter", "Event: " + event.getTitle() + 
                " | Button: " + joinButton.getText() + 
                " | Status: " + (isEventFinished ? "Finished" : isEventStarted ? "Started" : isPastEvent ? "Past" : "Upcoming") +
                " | EventTime: " + eventTime + 
                " | CurrentTime: " + currentTime + 
                " | EventEndTime: " + eventEndTime +
                " | IsStarted: " + isEventStarted +
                " | IsFinished: " + isEventFinished);
            
            // Set click listeners
            joinButton.setOnClickListener(v -> {
                if (listener != null && !isEventFinished && !isPastEvent) {
                    listener.onJoinEvent(event, getAdapterPosition());
                }
            });
            
            itemView.setOnClickListener(v -> {
                if (listener != null) {
                    listener.onEventClick(event);
                }
            });
        }
    }
} 