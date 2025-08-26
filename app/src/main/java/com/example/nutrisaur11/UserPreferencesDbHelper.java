package com.example.nutrisaur11;

import android.content.Context;
import android.database.sqlite.SQLiteDatabase;
import android.database.sqlite.SQLiteOpenHelper;

public class UserPreferencesDbHelper extends SQLiteOpenHelper {
    public static final String DATABASE_NAME = "userprefs.db";
    public static final int DATABASE_VERSION = 17; // Force upgrade to add missing columns
    public static final String TABLE_NAME = "preferences";
    public static final String COL_ID = "id";
    public static final String COL_ALLERGIES = "allergies";
    public static final String COL_DIET_PREFS = "diet_prefs";
    public static final String COL_AVOID_FOODS = "avoid_foods";
    public static final String COL_USER_EMAIL = "user_email";
    public static final String COL_RISK_SCORE = "risk_score";
    public static final String COL_SCREENING_ANSWERS = "screening_answers"; // Add screening_answers column
    public static final String TABLE_FOOD_RECS = "food_recommendations";

    
    // Favorites table
    public static final String TABLE_FAVORITES = "favorites";
    public static final String COL_FAVORITE_ID = "id";
    public static final String COL_FAVORITE_USER_EMAIL = "user_email";
    public static final String COL_FAVORITE_DISH_NAME = "dish_name";
    public static final String COL_FAVORITE_DISH_EMOJI = "dish_emoji";
    public static final String COL_FAVORITE_DISH_DESC = "dish_desc";
    public static final String COL_FAVORITE_DISH_TAGS = "dish_tags";
    public static final String COL_FAVORITE_ADDED_AT = "added_at";
    
    // Substitutions table
    public static final String TABLE_SUBSTITUTIONS = "substitutions";
    public static final String COL_SUB_ID = "id";
    public static final String COL_SUB_USER_EMAIL = "user_email";
    public static final String COL_SUB_ORIGINAL_NAME = "original_name";
    public static final String COL_SUB_CHOSEN_NAME = "chosen_name";
    public static final String COL_SUB_CHOSEN_EMOJI = "chosen_emoji";
    public static final String COL_SUB_CHOSEN_DESC = "chosen_desc";
    public static final String COL_SUB_CHOSEN_TAGS = "chosen_tags";
    public static final String COL_SUB_REASON_TAG = "reason_tag"; // similarity reason/tag
    public static final String COL_SUB_TIMESTAMP = "timestamp";
    
    // Users table for SignUpActivity
    public static final String TABLE_USERS = "users";
    public static final String COL_USERNAME = "username";
    public static final String COL_PASSWORD = "password";
    public static final String COL_CREATED_AT = "created_at";
    
    // User profile columns are now integrated into the main preferences table
    // No separate user_profile table needed - all data stored in main table
    public static final String COL_USER_NAME = "name";
    public static final String COL_USER_AGE = "age";
    public static final String COL_USER_HEIGHT = "height";
    public static final String COL_USER_WEIGHT = "weight";
    public static final String COL_USER_BMI = "bmi";
    public static final String COL_USER_GENDER = "gender";
    public static final String COL_USER_GOAL = "goal";
    public static final String COL_USER_BIRTHDAY = "birthday";
    public static final String COL_BARANGAY = "barangay";
    public static final String COL_INCOME = "income";

    // Screening assessment columns
    public static final String COL_GENDER = "gender";
    public static final String COL_SWELLING = "swelling";
    public static final String COL_WEIGHT_LOSS = "weight_loss";
    public static final String COL_FEEDING_BEHAVIOR = "feeding_behavior";
    public static final String COL_FEEDING = "feeding"; // Add alias for EditProfileDialog
    public static final String COL_PHYSICAL_SIGNS = "physical_signs";
    public static final String COL_DIETARY_DIVERSITY = "dietary_diversity"; // Add constant for consistency
    
    // Food recommendations table columns
    public static final String COL_FOOD_RECS_ID = "id";
    public static final String COL_FOOD_RECS_USER_EMAIL = "user_email";
    public static final String COL_FOOD_RECS_NAME = "name";
    public static final String COL_FOOD_RECS_EMOJI = "emoji";
    public static final String COL_FOOD_RECS_DESC = "desc";
    public static final String COL_FOOD_RECS_TIMESTAMP = "timestamp";

    private static final String SQL_CREATE =
        "CREATE TABLE " + TABLE_NAME + " (" +
        COL_ID + " INTEGER PRIMARY KEY AUTOINCREMENT, " +
        COL_USER_EMAIL + " TEXT UNIQUE, " + // Make user_email unique
        COL_ALLERGIES + " TEXT, " +
        COL_DIET_PREFS + " TEXT, " +
        COL_AVOID_FOODS + " TEXT, " +
        COL_RISK_SCORE + " INTEGER, " +
        COL_SCREENING_ANSWERS + " TEXT, " + // Add screening_answers column
        COL_GENDER + " TEXT, " +
        COL_SWELLING + " TEXT, " +
        COL_WEIGHT_LOSS + " TEXT, " +
        COL_FEEDING_BEHAVIOR + " TEXT, " +
        COL_FEEDING + " TEXT, " + // Add feeding column as alias
        COL_PHYSICAL_SIGNS + " TEXT, " +
        "dietary_diversity TEXT, " +
        COL_BARANGAY + " TEXT, " +
        COL_INCOME + " TEXT, " +
        // User profile columns integrated into main table (matching web database structure)
        COL_USER_NAME + " TEXT, " +
        COL_USER_AGE + " INTEGER, " +
        COL_USER_HEIGHT + " REAL, " +
        COL_USER_WEIGHT + " REAL, " +
        COL_USER_BMI + " REAL, " +
        COL_USER_GOAL + " TEXT, " +
        COL_USER_BIRTHDAY + " TEXT, " +
        // Timestamps to match web database
        "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, " +
        "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP" +
        ")";

    private static final String SQL_CREATE_USERS =
        "CREATE TABLE " + TABLE_USERS + " (" +
        COL_ID + " INTEGER PRIMARY KEY AUTOINCREMENT, " +
        COL_USERNAME + " TEXT, " +
        COL_USER_EMAIL + " TEXT UNIQUE, " +
        COL_PASSWORD + " TEXT, " +
        COL_CREATED_AT + " TEXT)";

    private static final String SQL_CREATE_FOOD_RECS =
        "CREATE TABLE IF NOT EXISTS " + TABLE_FOOD_RECS + " (" +
        COL_FOOD_RECS_ID + " INTEGER PRIMARY KEY AUTOINCREMENT, " +
        COL_FOOD_RECS_USER_EMAIL + " TEXT, " +
        COL_FOOD_RECS_NAME + " TEXT, " +
        COL_FOOD_RECS_EMOJI + " TEXT, " +
        COL_FOOD_RECS_DESC + " TEXT, " +
        COL_FOOD_RECS_TIMESTAMP + " INTEGER)";
        
    private static final String SQL_CREATE_FAVORITES =
        "CREATE TABLE IF NOT EXISTS " + TABLE_FAVORITES + " (" +
        COL_FAVORITE_ID + " INTEGER PRIMARY KEY AUTOINCREMENT, " +
        COL_FAVORITE_USER_EMAIL + " TEXT, " +
        COL_FAVORITE_DISH_NAME + " TEXT, " +
        COL_FAVORITE_DISH_EMOJI + " TEXT, " +
        COL_FAVORITE_DISH_DESC + " TEXT, " +
        COL_FAVORITE_DISH_TAGS + " TEXT, " +
        COL_FAVORITE_ADDED_AT + " TIMESTAMP DEFAULT CURRENT_TIMESTAMP, " +
        "UNIQUE(" + COL_FAVORITE_USER_EMAIL + ", " + COL_FAVORITE_DISH_NAME + "))";
    
    private static final String SQL_CREATE_SUBSTITUTIONS =
        "CREATE TABLE IF NOT EXISTS " + TABLE_SUBSTITUTIONS + " (" +
        COL_SUB_ID + " INTEGER PRIMARY KEY AUTOINCREMENT, " +
        COL_SUB_USER_EMAIL + " TEXT, " +
        COL_SUB_ORIGINAL_NAME + " TEXT, " +
        COL_SUB_CHOSEN_NAME + " TEXT, " +
        COL_SUB_CHOSEN_EMOJI + " TEXT, " +
        COL_SUB_CHOSEN_DESC + " TEXT, " +
        COL_SUB_CHOSEN_TAGS + " TEXT, " +
        COL_SUB_REASON_TAG + " TEXT, " +
        COL_SUB_TIMESTAMP + " INTEGER)";
        
    // User profile table removed - all data now stored in main preferences table

    public UserPreferencesDbHelper(Context context) {
        super(context, DATABASE_NAME, null, DATABASE_VERSION);
    }

    @Override
    public void onCreate(SQLiteDatabase db) {
        db.execSQL(SQL_CREATE);
        db.execSQL(SQL_CREATE_USERS);
        db.execSQL(SQL_CREATE_FOOD_RECS);
        db.execSQL(SQL_CREATE_FAVORITES);
        db.execSQL(SQL_CREATE_SUBSTITUTIONS);
        // User profile data is now integrated into main preferences table
    }

    @Override
    public void onUpgrade(SQLiteDatabase db, int oldVersion, int newVersion) {
        if (oldVersion < 5) {
            // Previously created user_profile table, now integrated into main table
            // Add user profile columns to main table if they don't exist
            try {
                db.execSQL("ALTER TABLE " + TABLE_NAME + " ADD COLUMN " + COL_USER_NAME + " TEXT");
                db.execSQL("ALTER TABLE " + TABLE_NAME + " ADD COLUMN " + COL_USER_AGE + " INTEGER");
                db.execSQL("ALTER TABLE " + TABLE_NAME + " ADD COLUMN " + COL_USER_HEIGHT + " REAL");
                db.execSQL("ALTER TABLE " + TABLE_NAME + " ADD COLUMN " + COL_USER_WEIGHT + " REAL");
                db.execSQL("ALTER TABLE " + TABLE_NAME + " ADD COLUMN " + COL_USER_BMI + " REAL");
                db.execSQL("ALTER TABLE " + TABLE_NAME + " ADD COLUMN " + COL_USER_GOAL + " TEXT");
                db.execSQL("ALTER TABLE " + TABLE_NAME + " ADD COLUMN " + COL_USER_BIRTHDAY + " TEXT");
            } catch (Exception e) {
                // Columns might already exist
            }
        }
        if (oldVersion < 6) {
            db.execSQL("ALTER TABLE " + TABLE_NAME + " ADD COLUMN " + COL_GENDER + " TEXT");
            db.execSQL("ALTER TABLE " + TABLE_NAME + " ADD COLUMN " + COL_SWELLING + " TEXT");
            db.execSQL("ALTER TABLE " + TABLE_NAME + " ADD COLUMN " + COL_WEIGHT_LOSS + " TEXT");
            db.execSQL("ALTER TABLE " + TABLE_NAME + " ADD COLUMN " + COL_FEEDING_BEHAVIOR + " TEXT");
            db.execSQL("ALTER TABLE " + TABLE_NAME + " ADD COLUMN " + COL_PHYSICAL_SIGNS + " TEXT");
        }
        if (oldVersion < 7) {
            // Add dietary_diversity column if not present
            db.execSQL("ALTER TABLE " + TABLE_NAME + " ADD COLUMN dietary_diversity TEXT");
            // Add unique constraint to user_email (requires table rebuild)
            db.execSQL("CREATE TABLE IF NOT EXISTS preferences_new (" +
                COL_ID + " INTEGER PRIMARY KEY AUTOINCREMENT, " +
                COL_USER_EMAIL + " TEXT UNIQUE, " +
                COL_ALLERGIES + " TEXT, " +
                COL_DIET_PREFS + " TEXT, " +
                COL_AVOID_FOODS + " TEXT, " +
                COL_RISK_SCORE + " INTEGER, " +
                COL_GENDER + " TEXT, " +
                COL_SWELLING + " TEXT, " +
                COL_WEIGHT_LOSS + " TEXT, " +
                COL_FEEDING_BEHAVIOR + " TEXT, " +
                COL_PHYSICAL_SIGNS + " TEXT, " +
                "dietary_diversity TEXT)");
            db.execSQL("INSERT OR REPLACE INTO preferences_new SELECT * FROM " + TABLE_NAME + ";");
            db.execSQL("DROP TABLE " + TABLE_NAME + ";");
            db.execSQL("ALTER TABLE preferences_new RENAME TO " + TABLE_NAME + ";");
        }
        if (oldVersion < 8) {
            // Birthday column is now part of main preferences table
            // Already added in version 5 upgrade above
        }
        if (oldVersion < 17) {
            // Recreate food recommendations table with proper structure
            db.execSQL("DROP TABLE IF EXISTS " + TABLE_FOOD_RECS);
            db.execSQL(SQL_CREATE_FOOD_RECS);
        }
        if (oldVersion < 9) {
            // Create users table for SignUpActivity
            db.execSQL(SQL_CREATE_USERS);
            // Add feeding column as alias for EditProfileDialog
            db.execSQL("ALTER TABLE " + TABLE_NAME + " ADD COLUMN " + COL_FEEDING + " TEXT");
            // Copy data from feeding_behavior to feeding
            db.execSQL("UPDATE " + TABLE_NAME + " SET " + COL_FEEDING + " = " + COL_FEEDING_BEHAVIOR + " WHERE " + COL_FEEDING_BEHAVIOR + " IS NOT NULL");
        }
        if (oldVersion < 10) {
            // Add screening_answers column for JSON storage
            db.execSQL("ALTER TABLE " + TABLE_NAME + " ADD COLUMN " + COL_SCREENING_ANSWERS + " TEXT");
        }
        if (oldVersion < 11) {
            // Future version 11 upgrades can be added here
            // For now, just ensure compatibility
        }
        if (oldVersion < 12) {
            db.execSQL("ALTER TABLE " + TABLE_NAME + " ADD COLUMN " + COL_BARANGAY + " TEXT;");
            db.execSQL("ALTER TABLE " + TABLE_NAME + " ADD COLUMN " + COL_INCOME + " TEXT;");
        }
        if (oldVersion < 13) {
            // Add timestamp columns to match web database structure
            try {
                db.execSQL("ALTER TABLE " + TABLE_NAME + " ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;");
                db.execSQL("ALTER TABLE " + TABLE_NAME + " ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;");
            } catch (Exception e) {
                // Columns might already exist
                android.util.Log.w("UserPreferencesDbHelper", "Timestamp columns may already exist");
            }
        }
        if (oldVersion < 14) {
            // Create favorites table
            db.execSQL(SQL_CREATE_FAVORITES);
        }
        if (oldVersion < 15) {
            // Create substitutions table
            db.execSQL(SQL_CREATE_SUBSTITUTIONS);
        }
        if (oldVersion < 16) {
            // Comprehensive upgrade to ensure all columns exist
            // Add any missing columns that might not have been added in previous upgrades
            try {
                // User profile columns
                db.execSQL("ALTER TABLE " + TABLE_NAME + " ADD COLUMN " + COL_USER_NAME + " TEXT");
            } catch (Exception e) {
                // Column might already exist
            }
            try {
                db.execSQL("ALTER TABLE " + TABLE_NAME + " ADD COLUMN " + COL_USER_AGE + " INTEGER");
            } catch (Exception e) {
                // Column might already exist
            }
            try {
                db.execSQL("ALTER TABLE " + TABLE_NAME + " ADD COLUMN " + COL_USER_HEIGHT + " REAL");
            } catch (Exception e) {
                // Column might already exist
            }
            try {
                db.execSQL("ALTER TABLE " + TABLE_NAME + " ADD COLUMN " + COL_USER_WEIGHT + " REAL");
            } catch (Exception e) {
                // Column might already exist
            }
            try {
                db.execSQL("ALTER TABLE " + TABLE_NAME + " ADD COLUMN " + COL_USER_BMI + " REAL");
            } catch (Exception e) {
                // Column might already exist
            }
            try {
                db.execSQL("ALTER TABLE " + TABLE_NAME + " ADD COLUMN " + COL_USER_GOAL + " TEXT");
            } catch (Exception e) {
                // Column might already exist
            }
            try {
                db.execSQL("ALTER TABLE " + TABLE_NAME + " ADD COLUMN " + COL_USER_BIRTHDAY + " TEXT");
            } catch (Exception e) {
                // Column might already exist
            }
            try {
                db.execSQL("ALTER TABLE " + TABLE_NAME + " ADD COLUMN " + COL_BARANGAY + " TEXT");
            } catch (Exception e) {
                // Column might already exist
            }
            try {
                db.execSQL("ALTER TABLE " + TABLE_NAME + " ADD COLUMN " + COL_INCOME + " TEXT");
            } catch (Exception e) {
                // Column might already exist
            }
            try {
                db.execSQL("ALTER TABLE " + TABLE_NAME + " ADD COLUMN " + COL_SCREENING_ANSWERS + " TEXT");
            } catch (Exception e) {
                // Column might already exist
            }
            try {
                db.execSQL("ALTER TABLE " + TABLE_NAME + " ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
            } catch (Exception e) {
                // Column might already exist
            }
            try {
                db.execSQL("ALTER TABLE " + TABLE_NAME + " ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
            } catch (Exception e) {
                // Column might already exist
            }
            
            android.util.Log.d("UserPreferencesDbHelper", "Version 16 upgrade completed - all columns ensured");
        }
    }

    @Override
    public void onDowngrade(SQLiteDatabase db, int oldVersion, int newVersion) {
        // Handle database downgrade gracefully
        // Instead of throwing an exception, we'll recreate the database
        // This is a destructive operation but prevents crashes
        android.util.Log.w("UserPreferencesDbHelper", "Database downgrade detected from " + oldVersion + " to " + newVersion + ". Recreating database.");
        
        // Drop all tables and recreate them
        db.execSQL("DROP TABLE IF EXISTS " + TABLE_NAME);
        db.execSQL("DROP TABLE IF EXISTS " + TABLE_USERS);
        db.execSQL("DROP TABLE IF EXISTS " + TABLE_FOOD_RECS);
        // user_profile table no longer exists
        
        // Recreate all tables
        onCreate(db);
    }
} 