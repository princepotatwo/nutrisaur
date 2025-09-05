# Database Migration Summary

## Problem
The Android app was using a local SQLite database with a table called "preferences" that had a different structure than the web database's "user_preferences" table. This caused column mismatch errors when the app tried to access columns that didn't exist in the local database.

## Solution
Updated the Android app's database structure to match the web database's `user_preferences` table exactly.

## Changes Made

### 1. UserPreferencesDbHelper.java
- **Updated table name**: Changed from "preferences" to "user_preferences"
- **Updated database version**: Incremented to version 18 to force migration
- **Updated table structure**: Modified SQL_CREATE to match the web database schema exactly
- **Added missing column constants**: Added constants for all columns in the user_preferences table
- **Enhanced migration logic**: Added comprehensive migration in onUpgrade() method to:
  - Create new table with correct structure
  - Migrate existing data from old table
  - Handle missing columns gracefully

### 2. FoodActivity.java
- **Added safe column access methods**: 
  - `getStringFromCursor()` - safely gets string values
  - `getDoubleFromCursor()` - safely gets double values  
  - `getIntFromCursor()` - safely gets int values
- **Updated column access**: Replaced direct column access with safe methods
- **Enhanced error handling**: Added try-catch blocks to handle missing columns gracefully

## Database Schema Changes

### Old Structure (preferences table)
```sql
CREATE TABLE preferences (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_email TEXT UNIQUE,
    allergies TEXT,
    diet_prefs TEXT,
    avoid_foods TEXT,
    risk_score INTEGER,
    screening_answers TEXT,
    gender TEXT,
    swelling TEXT,
    weight_loss TEXT,
    feeding_behavior TEXT,
    physical_signs TEXT,
    dietary_diversity TEXT,
    barangay TEXT,
    income TEXT,
    name TEXT,
    age INTEGER,
    height REAL,
    weight REAL,
    bmi REAL,
    goal TEXT,
    birthday TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
```

### New Structure (user_preferences table)
```sql
CREATE TABLE user_preferences (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_email TEXT UNIQUE,
    username TEXT,
    name TEXT,
    birthday TEXT,
    age INTEGER,
    gender TEXT,
    height REAL,
    weight REAL,
    bmi REAL,
    muac REAL,
    swelling TEXT,
    weight_loss TEXT,
    dietary_diversity INTEGER,
    feeding_behavior TEXT,
    physical_thin INTEGER DEFAULT 0,
    physical_shorter INTEGER DEFAULT 0,
    physical_weak INTEGER DEFAULT 0,
    physical_none INTEGER DEFAULT 0,
    physical_signs TEXT,
    has_recent_illness INTEGER DEFAULT 0,
    has_eating_difficulty INTEGER DEFAULT 0,
    has_food_insecurity INTEGER DEFAULT 0,
    has_micronutrient_deficiency INTEGER DEFAULT 0,
    has_functional_decline INTEGER DEFAULT 0,
    goal TEXT,
    risk_score INTEGER,
    screening_answers TEXT,
    allergies TEXT,
    diet_prefs TEXT,
    avoid_foods TEXT,
    barangay TEXT,
    income TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
```

## Key Improvements

1. **Column Compatibility**: All columns now match the web database structure
2. **Data Type Consistency**: Fixed data types (e.g., dietary_diversity is now INTEGER)
3. **Missing Columns Added**: Added all missing columns from the web database
4. **Safe Access**: Added safe column access methods to prevent crashes
5. **Migration Support**: Automatic migration from old structure to new structure
6. **Error Handling**: Graceful handling of missing columns during transition

## Testing

A test script (`test_database_migration.java`) was created to verify:
- Table creation with correct structure
- Data insertion and retrieval
- Column compatibility
- Migration process

## Result

The Android app now uses a database structure that exactly matches the web database's `user_preferences` table, eliminating column mismatch errors and ensuring data consistency across the platform.
