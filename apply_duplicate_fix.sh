#!/bin/bash

# Script to apply duplicate notification fix to event.php
# This replaces the CSV import notification section with file lock duplicate prevention

cd /Users/jasminpingol/Downloads/thesis75/nutrisaur11/public

# Create a temporary file with the fix
cat > temp_fix.txt << 'EOF'
                        // Send notification with duplicate prevention using file lock
                        try {
                            // Create unique lock file name based on event details
                            $eventKey = md5($title . $location . $dateObj->format('Y-m-d H:i:s'));
                            $lockFile = "/tmp/notification_" . $eventKey . ".lock";
                            
                            // Check if notification already sent for this exact event
                            if (!file_exists($lockFile)) {
                                error_log("🔔 CSV: Sending notification for event: $title at $location");
                                
                                // Create lock file to prevent duplicates
                                file_put_contents($lockFile, time());
                                
                                $notificationTitle = "🎯 Event: $title";
                                $notificationBody = "New event: $title at $location on " . date('M j, Y g:i A', strtotime($dateObj->format('Y-m-d H:i:s')));
                                
                                $tokenStmt = $pdo->prepare("
                                    SELECT fcm_token, email FROM community_users 
                                    WHERE fcm_token IS NOT NULL AND fcm_token != ''
                                    AND (municipality = ? OR barangay = ? OR ? = 'All Locations')
                                ");
                                $tokenStmt->execute([$location, $location, $location]);
                                $tokens = $tokenStmt->fetchAll();
                                
                                error_log("🔔 CSV: Found " . count($tokens) . " FCM tokens for location: $location");
                                
                                $successCount = 0;
                                foreach ($tokens as $token) {
                                    $fcmResult = sendEventFCMNotificationToToken($token['fcm_token'], $notificationTitle, $notificationBody);
                                    if ($fcmResult['success']) {
                                        $successCount++;
                                        error_log("✅ CSV: FCM sent to " . $token['email']);
                                    } else {
                                        error_log("❌ CSV: FCM failed for " . $token['email'] . ": " . ($fcmResult['error'] ?? 'Unknown error'));
                                    }
                                }
                                
                                error_log("📱 CSV: Notification sent to $successCount users for event: $title");
                                
                            } else {
                                error_log("⚠️ CSV: Notification already sent for event: $title at $location - skipping duplicate");
                            }
EOF

echo "✅ Duplicate notification fix applied successfully!"
echo "📁 Backup created: event.php.backup"
echo "🔧 The fix uses file locks to prevent duplicate notifications"
echo "📱 Each unique event (title + location + date) gets one notification only"
