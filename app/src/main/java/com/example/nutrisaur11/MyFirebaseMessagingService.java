package com.example.nutrisaur11;

import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.content.Context;
import android.content.Intent;
import android.media.RingtoneManager;
import android.net.Uri;
import android.os.Build;
import android.util.Log;

import androidx.annotation.NonNull;
import androidx.core.app.NotificationCompat;

import com.google.firebase.messaging.FirebaseMessagingService;
import com.google.firebase.messaging.RemoteMessage;

public class MyFirebaseMessagingService extends FirebaseMessagingService {
    private static final String TAG = "MyFirebaseMsgService";
    private static final String CHANNEL_ID = "nutrisaur_events";
    private static final String CHANNEL_NAME = "Event Notifications";
    private static final String CHANNEL_DESCRIPTION = "Notifications for new nutrition events";
    
    private FCMTokenManager tokenManager;
    private long lastTokenRefreshTime = 0;
    private static final long MIN_TOKEN_REFRESH_INTERVAL = 60000; // 1 minute minimum between token refreshes

    @Override
    public void onCreate() {
        super.onCreate();
        tokenManager = new FCMTokenManager(this);
    }

    @Override
    public void onMessageReceived(@NonNull RemoteMessage remoteMessage) {
        Log.d(TAG, "From: " + remoteMessage.getFrom());

        if (remoteMessage.getData().size() > 0) {
            Log.d(TAG, "Message data payload: " + remoteMessage.getData());
            
            // Handle different notification types
            String notificationType = remoteMessage.getData().get("notification_type");
            if (notificationType != null) {
                switch (notificationType) {
                    case "new_event":
                        sendEventNotification(remoteMessage, "New Event");
                        break;
                    case "event_updated":
                        sendEventNotification(remoteMessage, "Event Updated");
                        break;
                    case "imported_event":
                        sendEventNotification(remoteMessage, "Event Imported");
                        break;
                    case "general":
                        sendGeneralNotification(remoteMessage);
                        break;
                    default:
                        sendEventNotification(remoteMessage, "Notification");
                        break;
                }
            } else {
                // Default to event notification if no type specified
                sendEventNotification(remoteMessage, "Notification");
            }
        }

        if (remoteMessage.getNotification() != null) {
            Log.d(TAG, "Message Notification Body: " + remoteMessage.getNotification().getBody());
            sendEventNotification(remoteMessage, "Notification");
        }
    }

    @Override
    public void onNewToken(@NonNull String token) {
        long currentTime = System.currentTimeMillis();
        Log.d(TAG, "Refreshed token: " + token);
        
        // Rate limiting: don't refresh tokens more than once per minute
        if (currentTime - lastTokenRefreshTime < MIN_TOKEN_REFRESH_INTERVAL) {
            Log.w(TAG, "Token refresh rate limited - skipping (last refresh was " + 
                  (currentTime - lastTokenRefreshTime) + "ms ago)");
            return;
        }
        
        // Use token manager to handle registration intelligently
        if (tokenManager != null) {
            // Only force refresh if the token manager thinks it's necessary
            if (tokenManager.isTokenRegistrationNeeded()) {
                Log.d(TAG, "Token registration needed, forcing refresh");
                tokenManager.forceTokenRefresh();
                lastTokenRefreshTime = currentTime;
            } else {
                Log.d(TAG, "Token registration not needed, skipping refresh");
            }
        } else {
            // Fallback to direct registration
            sendRegistrationToServer(token);
            lastTokenRefreshTime = currentTime;
        }
    }

    private void sendEventNotification(RemoteMessage remoteMessage, String defaultTitle) {
        String title = remoteMessage.getNotification() != null ? 
            remoteMessage.getNotification().getTitle() : 
            remoteMessage.getData().get("event_title");
            
        if (title == null || title.isEmpty()) {
            title = defaultTitle;
        }
        
        String body = remoteMessage.getNotification() != null ? 
            remoteMessage.getNotification().getBody() : 
            remoteMessage.getData().get("event_description");
            
        if (body == null || body.isEmpty()) {
            body = "New nutrition event available!";
        }

        // Get event details for rich notification
        String eventLocation = remoteMessage.getData().get("event_location");
        String eventDate = remoteMessage.getData().get("event_date");
        String eventType = remoteMessage.getData().get("event_type");

        Intent intent = new Intent(this, EventsActivity.class);
        intent.addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP);
        
        // Add event data to intent
        if (remoteMessage.getData().containsKey("event_id")) {
            intent.putExtra("event_id", remoteMessage.getData().get("event_id"));
        }
        
        PendingIntent pendingIntent = PendingIntent.getActivity(this, 0, intent,
            PendingIntent.FLAG_ONE_SHOT | PendingIntent.FLAG_IMMUTABLE);

        Uri defaultSoundUri = RingtoneManager.getDefaultUri(RingtoneManager.TYPE_NOTIFICATION);
        
        NotificationCompat.Builder notificationBuilder =
            new NotificationCompat.Builder(this, CHANNEL_ID)
                .setSmallIcon(R.drawable.ic_notification)
                .setContentTitle(title)
                .setContentText(body)
                .setAutoCancel(true)
                .setSound(defaultSoundUri)
                .setContentIntent(pendingIntent)
                .setPriority(NotificationCompat.PRIORITY_HIGH)
                .setStyle(new NotificationCompat.BigTextStyle().bigText(body));

        // Add rich notification content if available
        if (eventLocation != null && !eventLocation.isEmpty()) {
            notificationBuilder.setContentText(body + "\n📍 " + eventLocation);
        }
        
        if (eventDate != null && !eventDate.isEmpty()) {
            notificationBuilder.setContentText(body + "\n📅 " + eventDate);
        }

        NotificationManager notificationManager =
            (NotificationManager) getSystemService(Context.NOTIFICATION_SERVICE);

        createNotificationChannel(notificationManager);

        // Generate unique notification ID based on event ID or timestamp
        int notificationId = 0;
        if (remoteMessage.getData().containsKey("event_id")) {
            try {
                notificationId = Integer.parseInt(remoteMessage.getData().get("event_id"));
            } catch (NumberFormatException e) {
                notificationId = (int) System.currentTimeMillis();
            }
        } else {
            notificationId = (int) System.currentTimeMillis();
        }

        notificationManager.notify(notificationId, notificationBuilder.build());
        
        Log.d(TAG, "Event notification sent: " + title);
    }

    private void sendGeneralNotification(RemoteMessage remoteMessage) {
        String title = remoteMessage.getNotification() != null ? 
            remoteMessage.getNotification().getTitle() : 
            remoteMessage.getData().get("title");
            
        String body = remoteMessage.getNotification() != null ? 
            remoteMessage.getNotification().getBody() : 
            remoteMessage.getData().get("body");

        Intent intent = new Intent(this, MainActivity.class);
        intent.addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP);
        
        PendingIntent pendingIntent = PendingIntent.getActivity(this, 0, intent,
            PendingIntent.FLAG_ONE_SHOT | PendingIntent.FLAG_IMMUTABLE);

        Uri defaultSoundUri = RingtoneManager.getDefaultUri(RingtoneManager.TYPE_NOTIFICATION);
        
        NotificationCompat.Builder notificationBuilder =
            new NotificationCompat.Builder(this, CHANNEL_ID)
                .setSmallIcon(R.drawable.ic_notification)
                .setContentTitle(title)
                .setContentText(body)
                .setAutoCancel(true)
                .setSound(defaultSoundUri)
                .setContentIntent(pendingIntent)
                .setPriority(NotificationCompat.PRIORITY_DEFAULT);

        NotificationManager notificationManager =
            (NotificationManager) getSystemService(Context.NOTIFICATION_SERVICE);

        createNotificationChannel(notificationManager);

        int notificationId = (int) System.currentTimeMillis();
        notificationManager.notify(notificationId, notificationBuilder.build());
        
        Log.d(TAG, "General notification sent: " + title);
    }

    private void createNotificationChannel(NotificationManager notificationManager) {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            NotificationChannel channel = new NotificationChannel(
                CHANNEL_ID,
                CHANNEL_NAME,
                NotificationManager.IMPORTANCE_HIGH
            );
            channel.setDescription(CHANNEL_DESCRIPTION);
            channel.enableVibration(true);
            channel.enableLights(true);
            channel.setShowBadge(true);
            notificationManager.createNotificationChannel(channel);
        }
    }

    private void sendRegistrationToServer(String token) {
        // Fallback method - this should not be called if token manager is working
        Log.d(TAG, "Fallback: Token should be sent to server: " + token);
        
        // You can implement direct server communication here if needed
        // But it's better to use the FCMTokenManager
    }
}
