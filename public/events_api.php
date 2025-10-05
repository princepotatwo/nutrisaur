<?php
// events_api.php - Working Server-Sent Events for dashboard
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if this is an event stream request
if (isset($_GET['action']) && $_GET['action'] === 'event_stream') {
    // Set proper headers for Server-Sent Events
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Cache-Control');
    
    // Disable any output buffering
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Send initial connection event
    echo "data: " . json_encode(['type' => 'connected', 'message' => 'Event stream connected']) . "\n\n";
    
    // Flush immediately
    if (ob_get_level()) {
        ob_flush();
    }
    flush();
    
    $lastEventId = intval($_GET['last_event_id'] ?? 0);
    $barangay = $_GET['barangay'] ?? '';
    
    // Simple event loop
    $counter = 0;
    while ($counter < 100) { // Limit to prevent infinite loops
        try {
            // Send heartbeat every 10 seconds
            if ($counter % 5 === 0) {
                echo "data: " . json_encode([
                    'type' => 'heartbeat', 
                    'timestamp' => time(),
                    'counter' => $counter
                ]) . "\n\n";
            }
            
            // Flush output
            if (ob_get_level()) {
                ob_flush();
            }
            flush();
            
        } catch (Exception $e) {
            echo "data: " . json_encode(['type' => 'error', 'message' => $e->getMessage()]) . "\n\n";
            break;
        }
        
        $counter++;
        sleep(2); // Wait 2 seconds
    }
    
    exit;
}

// Test endpoint
if (isset($_GET['action']) && $_GET['action'] === 'test_event') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Test event endpoint working',
        'timestamp' => time()
    ]);
    exit;
}

// Default response
header('Content-Type: application/json');
echo json_encode(['error' => 'Invalid action']);
?>
