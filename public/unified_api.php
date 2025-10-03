<?php
/**
 * Unified API Endpoint
 * Handles all API requests with type and endpoint parameters
 * Routes to appropriate DatabaseAPI functions
 */

// Set timezone
date_default_timezone_set('Asia/Manila');

// Include DatabaseAPI
require_once __DIR__ . '/api/DatabaseAPI.php';

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request parameters
$type = $_GET['type'] ?? $_POST['type'] ?? '';
$endpoint = $_GET['endpoint'] ?? $_POST['endpoint'] ?? '';

// Log the request for debugging
error_log("Unified API called - Type: $type, Endpoint: $endpoint, Method: " . $_SERVER['REQUEST_METHOD']);

try {
    $db = DatabaseAPI::getInstance();
    
    // Route based on type parameter
    switch ($type) {
        case 'dashboard':
            handleDashboardRequest($db);
            break;
            
        case 'usm':
            handleUSMRequest($db);
            break;
            
        case 'community_metrics':
            handleCommunityMetricsRequest($db);
            break;
            
        case 'risk_distribution':
            handleRiskDistributionRequest($db);
            break;
            
        case 'geographic_distribution':
            handleGeographicDistributionRequest($db);
            break;
            
        case 'critical_alerts':
            handleCriticalAlertsRequest($db);
            break;
            
        case 'intelligent_programs':
            handleIntelligentProgramsRequest($db);
            break;
            
        case 'realtime_stream':
            handleRealtimeStreamRequest($db);
            break;
            
        default:
            // Route based on endpoint parameter
            switch ($endpoint) {
                case 'create_event':
                    handleCreateEventRequest($db);
                    break;
                    
                case 'mobile_signup':
                    handleMobileSignupRequest($db);
                    break;
                    
                default:
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid type or endpoint parameter',
                        'available_types' => ['dashboard', 'usm', 'community_metrics', 'risk_distribution', 'geographic_distribution', 'critical_alerts', 'intelligent_programs'],
                        'available_endpoints' => ['create_event', 'mobile_signup']
                    ]);
                    break;
            }
            break;
    }
    
} catch (Exception $e) {
    error_log("Unified API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

// Dashboard data handler
function handleDashboardRequest($db) {
    try {
        // Get basic dashboard statistics
        $pdo = $db->getPDO();
        
        // Total users
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM community_users");
        $totalUsers = $stmt->fetchColumn();
        
        // Users with FCM tokens
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM community_users WHERE fcm_token IS NOT NULL AND fcm_token != ''");
        $activeTokens = $stmt->fetchColumn();
        
        // Recent events
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM programs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $recentEvents = $stmt->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'total_users' => $totalUsers,
                'active_tokens' => $activeTokens,
                'recent_events' => $recentEvents,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching dashboard data: ' . $e->getMessage()
        ]);
    }
}

// User Screening Module handler
function handleUSMRequest($db) {
    try {
        $pdo = $db->getPDO();
        
        // Get screening data
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total_screenings,
                AVG(risk_score) as avg_risk_score,
                COUNT(CASE WHEN risk_score >= 70 THEN 1 END) as high_risk_count
            FROM community_users 
            WHERE screening_date IS NOT NULL
        ");
        $screeningData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $screeningData
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching USM data: ' . $e->getMessage()
        ]);
    }
}

// Community metrics handler
function handleCommunityMetricsRequest($db) {
    try {
        $pdo = $db->getPDO();
        $barangay = $_GET['barangay'] ?? $_POST['barangay'] ?? '';
        
        $sql = "SELECT 
                    barangay,
                    COUNT(*) as user_count,
                    AVG(risk_score) as avg_risk_score,
                    COUNT(CASE WHEN risk_score >= 70 THEN 1 END) as high_risk_count
                FROM community_users 
                WHERE screening_date IS NOT NULL";
        
        $params = [];
        if (!empty($barangay) && $barangay !== 'all') {
            $sql .= " AND barangay = ?";
            $params[] = $barangay;
        }
        
        $sql .= " GROUP BY barangay ORDER BY user_count DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $metrics = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $metrics
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching community metrics: ' . $e->getMessage()
        ]);
    }
}

// Risk distribution handler
function handleRiskDistributionRequest($db) {
    try {
        $pdo = $db->getPDO();
        $barangay = $_GET['barangay'] ?? $_POST['barangay'] ?? '';
        
        $sql = "SELECT 
                    CASE 
                        WHEN risk_score < 30 THEN 'Low Risk'
                        WHEN risk_score < 70 THEN 'Medium Risk'
                        ELSE 'High Risk'
                    END as risk_category,
                    COUNT(*) as count
                FROM community_users 
                WHERE screening_date IS NOT NULL";
        
        $params = [];
        if (!empty($barangay) && $barangay !== 'all') {
            $sql .= " AND barangay = ?";
            $params[] = $barangay;
        }
        
        $sql .= " GROUP BY risk_category";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $distribution
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching risk distribution: ' . $e->getMessage()
        ]);
    }
}

// Geographic distribution handler
function handleGeographicDistributionRequest($db) {
    try {
        $pdo = $db->getPDO();
        
        $stmt = $pdo->query("
            SELECT 
                municipality,
                barangay,
                COUNT(*) as user_count,
                AVG(risk_score) as avg_risk_score
            FROM community_users 
            WHERE screening_date IS NOT NULL
            GROUP BY municipality, barangay
            ORDER BY user_count DESC
        ");
        $distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $distribution
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching geographic distribution: ' . $e->getMessage()
        ]);
    }
}

// Critical alerts handler
function handleCriticalAlertsRequest($db) {
    try {
        $pdo = $db->getPDO();
        
        $stmt = $pdo->query("
            SELECT 
                email,
                name,
                barangay,
                risk_score,
                screening_date
            FROM community_users 
            WHERE risk_score >= 70 
            AND screening_date IS NOT NULL
            ORDER BY risk_score DESC, screening_date DESC
            LIMIT 50
        ");
        $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $alerts
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching critical alerts: ' . $e->getMessage()
        ]);
    }
}

// Intelligent programs handler
function handleIntelligentProgramsRequest($db) {
    try {
        $pdo = $db->getPDO();
        
        $stmt = $pdo->query("
            SELECT 
                program_id,
                title,
                type,
                description,
                location,
                organizer,
                date_time,
                created_at
            FROM programs 
            ORDER BY created_at DESC
            LIMIT 20
        ");
        $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $programs
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching intelligent programs: ' . $e->getMessage()
        ]);
    }
}

// Create event handler
function handleCreateEventRequest($db) {
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'POST method required']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        
        $title = $input['title'] ?? '';
        $type = $input['type'] ?? '';
        $description = $input['description'] ?? '';
        $date_time = $input['date_time'] ?? '';
        $location = $input['location'] ?? '';
        $organizer = $input['organizer'] ?? '';
        
        if (empty($title) || empty($type) || empty($description) || empty($date_time) || empty($organizer)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }
        
        $pdo = $db->getPDO();
        $stmt = $pdo->prepare("
            INSERT INTO programs (title, type, description, date_time, location, organizer, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $result = $stmt->execute([$title, $type, $description, $date_time, $location, $organizer]);
        
        if ($result) {
            $eventId = $pdo->lastInsertId();
            
            // Add lock file mechanism for manual event creation
            $eventKey = md5($title . $location . date('Y-m-d H:i:s', strtotime($date_time)));
            $lockFile = "/tmp/notification_" . $eventKey . ".lock";
            
            // Check if notification already sent for this exact event
            if (!file_exists($lockFile)) {
                // Create lock file to prevent duplicates
                file_put_contents($lockFile, time());
                error_log("🔔 Unified API: Created lock file for event: $title at $location");
            } else {
                error_log("⚠️ Unified API: Notification already sent for event: $title at $location - skipping duplicate");
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Event created successfully',
                'event_id' => $eventId
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create event']);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error creating event: ' . $e->getMessage()
        ]);
    }
}

// Mobile signup handler
function handleMobileSignupRequest($db) {
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'POST method required']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'save_screening':
                // Handle screening data save
                $email = $input['email'] ?? '';
                $username = $input['username'] ?? '';
                $screening_data = $input['screening_data'] ?? '';
                $risk_score = $input['risk_score'] ?? 0;
                
                if (empty($email) || empty($screening_data)) {
                    echo json_encode(['success' => false, 'message' => 'Email and screening data required']);
                    return;
                }
                
                $pdo = $db->getPDO();
                $stmt = $pdo->prepare("
                    INSERT INTO community_users (email, name, screening_date) 
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                    name = VALUES(name),
                    screening_date = NOW()
                ");
                
                $result = $stmt->execute([$email, $username]);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Screening data saved successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to save screening data']);
                }
                break;
                
            case 'get_screening_data':
                // Handle screening data retrieval
                $email = $input['email'] ?? '';
                
                if (empty($email)) {
                    echo json_encode(['success' => false, 'message' => 'Email required']);
                    return;
                }
                
                $pdo = $db->getPDO();
                $stmt = $pdo->prepare("SELECT * FROM community_users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    echo json_encode([
                        'success' => true,
                        'data' => $user
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'User not found']);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                break;
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error in mobile signup: ' . $e->getMessage()
        ]);
    }
}

// Server-Sent Events handler for real-time dashboard updates
function handleRealtimeStreamRequest($db) {
    try {
        // Set headers for SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Cache-Control');
        
        // Get parameters
        $barangay = $_GET['barangay'] ?? '';
        $lastCheckTime = time();
        
        // Keep connection alive and check for changes
        while (true) {
            // Check if client disconnected
            if (connection_aborted()) {
                break;
            }
            
            // Check for new screening data
            $pdo = $db->getPDO();
            $query = "SELECT COUNT(*) as new_screenings 
                      FROM screening_responses 
                      WHERE (barangay = ? OR ? = '') 
                      AND created_at > ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$barangay, $barangay, date('Y-m-d H:i:s', $lastCheckTime)]);
            $result = $stmt->fetch();
            
            // Check for new programs
            $query2 = "SELECT COUNT(*) as new_programs 
                       FROM programs 
                       WHERE (target_location = ? OR ? = '') 
                       AND created_at > ?";
            $stmt2 = $pdo->prepare($query2);
            $stmt2->execute([$barangay, $barangay, date('Y-m-d H:i:s', $lastCheckTime)]);
            $result2 = $stmt2->fetch();
            
            // Check for new critical alerts
            $query3 = "SELECT COUNT(*) as new_alerts 
                       FROM screening_responses 
                       WHERE (barangay = ? OR ? = '') 
                       AND created_at > ? 
                       AND (bmi_category = 'Severely Underweight' OR bmi_category = 'Severely Overweight')";
            $stmt3 = $pdo->prepare($query3);
            $stmt3->execute([$barangay, $barangay, date('Y-m-d H:i:s', $lastCheckTime)]);
            $result3 = $stmt3->fetch();
            
            $hasChanges = $result['new_screenings'] > 0 || $result2['new_programs'] > 0 || $result3['new_alerts'] > 0;
            
            if ($hasChanges) {
                // Send update signal to client
                $updateData = [
                    'timestamp' => time(),
                    'barangay' => $barangay,
                    'new_screenings' => $result['new_screenings'],
                    'new_programs' => $result2['new_programs'],
                    'new_alerts' => $result3['new_alerts'],
                    'message' => 'Dashboard data updated'
                ];
                
                echo "data: " . json_encode($updateData) . "\n\n";
                ob_flush();
                flush();
                
                $lastCheckTime = time();
            } else {
                // Send heartbeat to keep connection alive
                echo "data: " . json_encode(['heartbeat' => true, 'timestamp' => time()]) . "\n\n";
                ob_flush();
                flush();
            }
            
            // Wait 3 seconds before next check
            sleep(3);
        }
        
    } catch (Exception $e) {
        error_log("SSE Error: " . $e->getMessage());
        echo "data: " . json_encode(['error' => 'Connection error']) . "\n\n";
        ob_flush();
        flush();
    }
}
?>
