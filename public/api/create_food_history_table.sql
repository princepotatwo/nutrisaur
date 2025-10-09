-- Create user_food_history table for nutrition management
-- This table stores all food entries added by community users for admin/BHW monitoring

CREATE TABLE IF NOT EXISTS user_food_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_email VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    date DATE NOT NULL,
    meal_category ENUM('Breakfast', 'Lunch', 'Dinner', 'Snacks') NOT NULL,
    food_name VARCHAR(255) NOT NULL,
    calories INT NOT NULL,
    serving_size VARCHAR(100) NOT NULL,
    protein DECIMAL(6,2) DEFAULT 0,
    carbs DECIMAL(6,2) DEFAULT 0,
    fat DECIMAL(6,2) DEFAULT 0,
    fiber DECIMAL(6,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_email) REFERENCES community_users(email) ON DELETE CASCADE,
    INDEX idx_user_date (user_email, date),
    INDEX idx_date (date),
    INDEX idx_calories (calories)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- This table enables:
-- 1. Admins to view all users' food history
-- 2. Track community nutrition patterns
-- 3. Identify high-risk cases (obesity, malnutrition)
-- 4. Generate nutrition reports and analytics

