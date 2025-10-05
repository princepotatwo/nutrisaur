<?php
// Test the events system
require_once 'public/config.php';
require_once 'public/api/DatabaseAPI.php';

echo "🧪 Testing Events System...\n";

try {
    $dbAPI = DatabaseAPI::getInstance();
    $pdo = $dbAPI->getPDO();
    
    if (!$pdo) {
        echo "❌ Database connection failed\n";
        exit;
    }
    
    echo "✅ Database connected\n";
    
    // Check if dashboard_events table exists
    $query = "SHOW TABLES LIKE 'dashboard_events'";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $table = $stmt->fetch();
    
    if ($table) {
        echo "✅ dashboard_events table exists\n";
        
        // Test inserting an event
        $testData = [
            'email' => 'test@example.com',
            'barangay' => 'Bagumbayan',
            'action' => 'test',
            'timestamp' => time()
        ];
        
        $insertQuery = "INSERT INTO dashboard_events (event_type, event_data, barangay, created_at) 
                        VALUES (?, ?, ?, NOW())";
        $stmt = $pdo->prepare($insertQuery);
        $result = $stmt->execute([
            'test_event',
            json_encode($testData),
            'Bagumbayan'
        ]);
        
        if ($result) {
            echo "✅ Test event inserted successfully\n";
        } else {
            echo "❌ Failed to insert test event\n";
        }
        
    } else {
        echo "❌ dashboard_events table does not exist\n";
        echo "📋 Please run the SQL to create the table:\n";
        echo "CREATE TABLE dashboard_events (\n";
        echo "    id INT AUTO_INCREMENT PRIMARY KEY,\n";
        echo "    event_type VARCHAR(100) NOT NULL,\n";
        echo "    event_data JSON,\n";
        echo "    barangay VARCHAR(100),\n";
        echo "    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n";
        echo "    INDEX idx_event_type (event_type),\n";
        echo "    INDEX idx_barangay (barangay),\n";
        echo "    INDEX idx_created_at (created_at)\n";
        echo ");\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
