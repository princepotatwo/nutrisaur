-- Create screening_history table for progress tracking
-- This table stores all screening assessments to track user progress over time

CREATE TABLE IF NOT EXISTS screening_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_email VARCHAR(255) NOT NULL,
    screening_date DATETIME NOT NULL,
    weight DECIMAL(5,2) NULL,
    height DECIMAL(5,2) NULL,
    bmi DECIMAL(4,2) NULL,
    age_months INT NULL,
    sex ENUM('Male', 'Female') NULL,
    classification_type VARCHAR(50) NULL COMMENT 'bmi-for-age, weight-for-age, height-for-age, weight-for-height',
    classification VARCHAR(100) NULL COMMENT 'Normal, Underweight, Overweight, etc.',
    z_score DECIMAL(6,3) NULL,
    nutritional_risk VARCHAR(20) NULL COMMENT 'Low, Medium, High',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes for performance
    INDEX idx_user_email (user_email),
    INDEX idx_screening_date (screening_date),
    INDEX idx_user_date (user_email, screening_date),
    INDEX idx_classification (classification_type, classification),
    
    -- Foreign key constraint
    FOREIGN KEY (user_email) REFERENCES community_users(email) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add table comment
ALTER TABLE screening_history COMMENT = 'Historical screening data for progress tracking and analytics';
