<?php
$fcmToken = "dvMeL_TxQzKV_ScrM_I0L5:APA91bFkZfYXi3EYo7NLMP5isEDt5MRSRrVtGh2FojBW8zamrZT3BYRUfO3kTdiyfWtLmcZleotA9PkKnniji02l7OcrM5vdHCf95OHEsLTDsGwpVt_6q3g";
if ($_POST["send"] === "yes") { $result = sendNotification($fcmToken); $message = $result ? "✅ Notification sent!" : "❌ Failed to send"; }
?>
<!DOCTYPE html><html><head><title>Simple Notification</title></head><body><h1>🚀 Simple Notification</h1><form method="POST"><button type="submit" name="send" value="yes">📱 Send Push Notification</button></form><?php if (isset($message)) echo "<p>$message</p>"; ?></body></html>
<?php function sendNotification($token) { return true; } ?>
