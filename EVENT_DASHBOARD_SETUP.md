# 🚀 Event-Driven Dashboard Setup Instructions

## 📋 **Step-by-Step Implementation**

### **1. Execute Database Table**
```sql
-- Run this SQL in your database
CREATE TABLE dashboard_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(100) NOT NULL,
    event_data JSON,
    barangay VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_type (event_type),
    INDEX idx_barangay (barangay),
    INDEX idx_created_at (created_at)
);
```

### **2. Add CSS to dash.php**
Add this line in the `<head>` section of your `dash.php`:
```html
<link rel="stylesheet" href="css/event_indicators.css">
```

### **3. Add JavaScript to dash.php**
Add these lines before the closing `</body>` tag in your `dash.php`:
```html
<script src="js/CommunityEventDashboardManager.js"></script>
<script src="js/dashboard_integration.js"></script>
```

### **4. Add HTML Indicators to dash.php**
Add this HTML in your dashboard header section:
```html
<!-- Community Event Status Indicators -->
<div class="community-event-status">
    <div id="community-connection" class="connection-indicator">
        <span class="status-dot"></span>
        <span class="status-text">Connecting...</span>
    </div>
    <div id="community-updates" class="update-indicator" style="display: none;">
        <span class="update-spinner"></span>
        <span>Updating...</span>
    </div>
</div>
```

### **5. Test the Implementation**

#### **Test Event Stream:**
```javascript
// Open browser console and run:
fetch('events_api.php?action=test_event')
  .then(response => response.json())
  .then(data => console.log('Test event result:', data));
```

#### **Test Event Publishing:**
```javascript
// Test publishing an event
fetch('events_api.php?action=test_event', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        event_type: 'screening_data_saved',
        event_data: {
            email: 'test@example.com',
            barangay: 'Bagumbayan',
            action: 'test'
        }
    })
});
```

### **6. Modify Your Save Functions**

Replace your existing `save_screening` calls with the event-enabled version:
```javascript
// Instead of:
fetch('api.php?action=save_screening', {...})

// Use:
fetch('api.php?action=save_screening_with_events', {...})
```

### **7. Verify Real-time Updates**

1. Open your dashboard
2. Look for the connection indicator (top-right)
3. Add new screening data
4. Watch the dashboard update automatically
5. Check browser console for event logs

## 🎯 **What You'll See**

### **Visual Indicators:**
- **Green dot**: Connected to event stream
- **Red dot**: Disconnected
- **Blue spinner**: Dashboard updating
- **Smooth animations**: No flickering

### **Real-time Updates:**
- New user registration → Total screened increases
- Screening data saved → Risk levels update
- Profile changes → Geographic charts update
- WHO standard changes → Classification charts update

## 🔧 **Troubleshooting**

### **If events don't work:**
1. Check browser console for errors
2. Verify database table exists
3. Test event API: `events_api.php?action=test_event`
4. Check file permissions

### **If dashboard flickers:**
1. Ensure CSS is loaded
2. Check for JavaScript errors
3. Verify event manager is initialized

## 📊 **Event Types**

The system handles these events:
- `new_user_registered` - New user added
- `screening_data_saved` - Screening data saved
- `screening_data_updated` - Screening data updated
- `user_profile_updated` - User profile changed
- `barangay_data_changed` - Geographic data changed
- `demographic_data_changed` - Demographics changed
- `physical_data_updated` - Weight/height changed

## 🚀 **Benefits**

✅ **Real-time updates** - Dashboard updates instantly
✅ **No flickering** - Smooth transitions
✅ **Targeted updates** - Only updates what changed
✅ **Reliable** - Automatic reconnection
✅ **Scalable** - Easy to add new events
✅ **Professional** - Enterprise-grade solution

## 🎉 **You're Done!**

Your dashboard now has real-time, event-driven updates that respond instantly to changes in your `community_users` table!
