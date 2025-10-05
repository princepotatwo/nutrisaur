-- Create events table for community_users events
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

-- Insert some sample events for testing
INSERT INTO dashboard_events (event_type, event_data, barangay, created_at) VALUES
('screening_data_saved', '{"email": "test@example.com", "barangay": "Bagumbayan", "action": "saved"}', 'Bagumbayan', NOW()),
('new_user_registered', '{"email": "user@example.com", "barangay": "Poblacion", "action": "registered"}', 'Poblacion', NOW());
