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
            
            // Set event card styling (join button removed)
            long currentTime = System.currentTimeMillis();
            long eventTime = 0;
            
            try {
                java.text.SimpleDateFormat format = new java.text.SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
                java.util.Date eventDate = format.parse(event.getDateTime());
                eventTime = eventDate.getTime();
            } catch (Exception e) {
                e.printStackTrace();
            }
            
            // Calculate days difference for display
            long diffInMillis = eventTime - currentTime;
            long diffInDays = diffInMillis / (24 * 60 * 60 * 1000);
            
            if (diffInDays < -7) {
                // Very old event (more than 7 days ago) - GRAY
                itemView.setAlpha(0.3f);
                itemView.setBackgroundResource(R.drawable.event_card_gray);
            } else if (diffInDays < 0) {
                // Recent past event (within 7 days) - Show but dimmed
                itemView.setAlpha(0.7f);
                itemView.setBackgroundResource(R.drawable.event_card_white);
            } else {
                // Upcoming event - Normal
                itemView.setAlpha(1.0f);
                itemView.setBackgroundResource(R.drawable.event_card_white);
            }
            
            // Hide join button as requested
            joinButton.setVisibility(View.GONE);
            joinButton.setClickable(false);
            joinButton.setFocusable(false);
            
            // Join button removed - no click listener needed
            
            itemView.setOnClickListener(v -> {
                if (listener != null) {
                    listener.onEventClick(event);
                }
            });
        }
    }
} 