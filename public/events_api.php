<?php
// events_api.php - Fixed event streaming for community_users table
require_once 'config.php';
require_once 'api/DatabaseAPI.php';

// Event publishing functions
function publishCommunityEvent($eventType, $userData, $barangay = '') {
    try {
        $pdo = DatabaseAPI::getInstance()->getPDO();
        if (!$pdo) {
            return false;
        }
        
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
if (isset($_GET['action']) && $_GET['action'] === 'event_stream') {
    // Set proper headers for Server-Sent Events
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Cache-Control');
    
    // Disable output buffering
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    $lastEventId = $_GET['last_event_id'] ?? 0;
    $barangay = $_GET['barangay'] ?? '';
    
    // Send initial connection event
    echo "data: " . json_encode(['type' => 'connected', 'message' => 'Event stream connected']) . "\n\n";
    
    while (true) {
        try {
            $pdo = DatabaseAPI::getInstance()->getPDO();
            if (!$pdo) {
                echo "data: " . json_encode(['type' => 'error', 'message' => 'Database connection failed']) . "\n\n";
                break;
            }
            
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
            
            // Send heartbeat every 30 seconds
            echo "data: " . json_encode(['type' => 'heartbeat', 'timestamp' => time()]) . "\n\n";
            
        } catch (Exception $e) {
            echo "data: " . json_encode(['type' => 'error', 'message' => $e->getMessage()]) . "\n\n";
            break;
        }
        
        // Flush output
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
        
        // Wait 2 seconds before next check
        sleep(2);
    }
    
    exit;
}

// Test endpoint to publish a sample event
if (isset($_GET['action']) && $_GET['action'] === 'test_event') {
    header('Content-Type: application/json');
    
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
    exit;
}

// Get recent events for debugging
if (isset($_GET['action']) && $_GET['action'] === 'get_events') {
    header('Content-Type: application/json');
    
    try {
        $pdo = DatabaseAPI::getInstance()->getPDO();
        $limit = $_GET['limit'] ?? 10;
        $query = "SELECT * FROM dashboard_events ORDER BY created_at DESC LIMIT ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$limit]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'events' => $events
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// Default response
header('Content-Type: application/json');
echo json_encode(['error' => 'Invalid action']);
?>
