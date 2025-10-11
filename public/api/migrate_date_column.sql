-- Migration script to allow NULL values in date column for MHO recommended foods
-- This allows MHO recommended foods to be stored with date = NULL

ALTER TABLE user_food_history 
MODIFY COLUMN date DATE NULL;
