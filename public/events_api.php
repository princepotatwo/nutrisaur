<?php
// events_api.php - Event publishing and streaming for community_users table
require_once 'config.php';
require_once 'api/DatabaseAPI.php';

// Event publishing functions
function publishCommunityEvent($eventType, $userData, $barangay = '') {
    global $pdo;
    
    try {
        $query = "INSERT INTO dashboard_events (event_type, event_data, barangay, created_at) 
                  VALUES (?, ?, ?, NOW())";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            $eventType,
            json_encode($userData),
            $barangay
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Error publishing community event: " . $e->getMessage());
        return false;
    }
}

// Event stream endpoint
if ($_GET['action'] === 'event_stream') {
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('Access-Control-Allow-Origin: *');
    
    $lastEventId = $_GET['last_event_id'] ?? 0;
    $barangay = $_GET['barangay'] ?? '';
    
    while (true) {
        // Check for new events
        $query = "SELECT * FROM dashboard_events 
                  WHERE id > ? 
                  AND (barangay = ? OR barangay = '' OR ? = '')
                  ORDER BY created_at ASC 
                  LIMIT 10";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$lastEventId, $barangay, $barangay]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($events as $event) {
            $eventData = [
                'id' => $event['id'],
                'type' => $event['event_type'],
                'timestamp' => $event['created_at'],
                'data' => json_decode($event['event_data'], true)
            ];
            
            echo "id: {$event['id']}\n";
            echo "event: {$event['event_type']}\n";
            echo "data: " . json_encode($eventData) . "\n\n";
            
            $lastEventId = $event['id'];
        }
        
        ob_flush();
        flush();
        sleep(2);
    }
}

// Test endpoint to publish a sample event
if ($_GET['action'] === 'test_event') {
    $testData = [
        'email' => 'test@example.com',
        'name' => 'Test User',
        'barangay' => 'Bagumbayan',
        'municipality' => 'BALANGA',
        'action' => 'test',
        'timestamp' => time()
    ];
    
    $result = publishCommunityEvent('screening_data_saved', $testData, 'Bagumbayan');
    
    echo json_encode([
        'success' => $result,
        'message' => $result ? 'Test event published' : 'Failed to publish event'
    ]);
}

// Get recent events for debugging
if ($_GET['action'] === 'get_events') {
    $limit = $_GET['limit'] ?? 10;
    $query = "SELECT * FROM dashboard_events ORDER BY created_at DESC LIMIT ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$limit]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'events' => $events
    ]);
}
?>
