# 🚀 NutriSaur Simple Notification System

## Overview
This is a simple push notification system for the NutriSaur project that allows administrators to send push notifications to all registered devices or specific FCM tokens.

## Features
- ✅ Send notifications to all active FCM tokens
- ✅ Send notifications to specific FCM tokens
- ✅ Real-time notification delivery status
- ✅ Notification logging and history
- ✅ Beautiful, responsive admin interface
- ✅ Integration with existing Firebase Admin SDK

## Quick Start

### 1. Access the Notification System
Navigate to: `sss/simple_notification_admin.php`
Or simply visit: `sss/` (index.php redirects to the notification page)

### 2. Send a Notification
1. **Title**: Enter the notification title (e.g., "🧪 Test Notification")
2. **Message**: Enter the notification body text
3. **Target**: Choose between:
   - **All Devices**: Send to all registered FCM tokens
   - **Specific Token**: Send to a specific FCM token (check the checkbox)
4. Click **"🚀 Send Notification"**

### 3. Monitor Results
- View real-time delivery status
- Check success/failure counts
- Review notification logs

## Technical Details

### Database Tables Used
- `fcm_tokens`: Stores device FCM tokens
- `notification_logs`: Logs all sent notifications

### Firebase Configuration
- Uses existing Firebase Admin SDK JSON file
- Automatically generates OAuth2 access tokens
- Sends notifications via FCM HTTP v1 API

### Security
- Requires admin authentication
- Session-based access control
- Input validation and sanitization

## FCM Token Management

### Viewing Tokens
The system automatically displays:
- Total active FCM tokens
- Recent notification history
- Delivery success/failure rates

### Token Storage
FCM tokens are stored in the `fcm_tokens` table with:
- Device information
- User email (if available)
- Barangay location
- Platform and app version
- Active status

## Troubleshooting

### Common Issues
1. **"No active FCM tokens found"**
   - Check if devices have registered FCM tokens
   - Verify database connection

2. **"Firebase Admin SDK file not found"**
   - Ensure `nutrisaur-ebf29-firebase-adminsdk-fbsvc-8dc50fb07f.json` exists
   - Check file permissions

3. **"Failed to generate access token"**
   - Verify Firebase service account credentials
   - Check internet connectivity for OAuth2

### Debug Information
- Check browser console for JavaScript errors
- Review PHP error logs
- Verify FCM token validity

## Integration

### Adding to Navigation
To add this to the main dashboard navigation, add this line to the navigation menu:

```html
<li><a href="simple_notification_admin"><span class="navbar-icon"></span><span>🚀 Simple Notifications</span></a></li>
```

### API Usage
The system can be extended to send notifications programmatically:

```php
// Example: Send notification via API
$data = [
    'action' => 'send_notification',
    'title' => 'Custom Title',
    'body' => 'Custom Message',
    'send_to_specific' => false
];

// POST to simple_notification_admin.php
```

## File Structure
```
sss/
├── simple_notification_admin.php    # Main notification interface
├── nutrisaur-ebf29-firebase-adminsdk-fbsvc-8dc50fb07f.json  # Firebase config
└── index.php                        # Redirects to notification page
```

## Support
For issues or questions:
1. Check the troubleshooting section above
2. Review PHP error logs
3. Verify Firebase configuration
4. Test with a simple notification first

---

**Note**: This system is designed to be simple and reliable. It uses the existing Firebase infrastructure and database schema, making it easy to maintain and extend.
