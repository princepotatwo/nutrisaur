-- Create notification_logs table if it doesn't exist
-- This table stores notification sending attempts and results

CREATE TABLE IF NOT EXISTS notification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    notification_type VARCHAR(255) NOT NULL,
    target_type VARCHAR(50) NOT NULL,
    target_value TEXT,
    tokens_sent INT DEFAULT 1,
    success BOOLEAN DEFAULT FALSE,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_id (event_id),
    INDEX idx_created_at (created_at),
    INDEX idx_success (success)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
