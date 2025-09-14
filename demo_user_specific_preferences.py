#!/usr/bin/env python3
"""
Demo showing how food preferences are user-specific in SharedPreferences
Shows that each logged-in user has their own separate preferences
"""

def demo_user_specific_saving():
    """Demonstrate how preferences are saved per user"""
    
    print("👤 USER-SPECIFIC FOOD PREFERENCES IN SHAREDPREFERENCES")
    print("=" * 80)
    
    print("\n1️⃣ HOW USER-SPECIFIC KEYS WORK")
    print("-" * 60)
    
    # Simulate two different users
    user1_email = "john@example.com"
    user2_email = "maria@example.com"
    
    print("When User 1 (john@example.com) completes food preferences:")
    user1_preferences = {
        "question_0": "VEGETARIAN",
        "question_1": "MILK,EGGS", 
        "question_2": "SWEET,CRUNCHY",
        "question_3": "GRILLED,STEAMED",
        "question_4": "₱200-300 per day"
    }
    
    print("Keys saved to SharedPreferences:")
    for key, value in user1_preferences.items():
        shared_pref_key = f"{user1_email}_{key}"
        print(f"  {shared_pref_key}: {value}")
    
    combined1 = "Food Identity: VEGETARIAN. Food Allergies: MILK, EGGS. Food Cravings: SWEET, CRUNCHY. Preferred Cooking Methods: GRILLED, STEAMED. Daily Food Budget: ₱200-300 per day."
    print(f"  {user1_email}_food_preferences_combined: {combined1}")
    
    print(f"\nWhen User 2 (maria@example.com) completes food preferences:")
    user2_preferences = {
        "question_0": "STANDARD EATER",
        "question_1": "PEANUTS,SHELLFISH",
        "question_2": "SPICY,UMAMI", 
        "question_3": "FRIED,STIR-FRIED",
        "question_4": "₱100-200 per day"
    }
    
    print("Keys saved to SharedPreferences:")
    for key, value in user2_preferences.items():
        shared_pref_key = f"{user2_email}_{key}"
        print(f"  {shared_pref_key}: {value}")
    
    combined2 = "Food Identity: STANDARD EATER. Food Allergies: PEANUTS, SHELLFISH. Food Cravings: SPICY, UMAMI. Preferred Cooking Methods: FRIED, STIR-FRIED. Daily Food Budget: ₱100-200 per day."
    print(f"  {user2_email}_food_preferences_combined: {combined2}")

def demo_sharedpreferences_structure():
    """Show the actual SharedPreferences structure"""
    
    print("\n\n2️⃣ SHAREDPREFERENCES STRUCTURE")
    print("=" * 80)
    
    print("File: nutrisaur_prefs")
    print("Content:")
    print("=" * 40)
    
    # Simulate what's actually stored
    shared_prefs_content = {
        # User 1 preferences
        "john@example.com_question_0": "VEGETARIAN",
        "john@example.com_question_1": "MILK,EGGS",
        "john@example.com_question_2": "SWEET,CRUNCHY", 
        "john@example.com_question_3": "GRILLED,STEAMED",
        "john@example.com_question_4": "₱200-300 per day",
        "john@example.com_food_preferences_combined": "Food Identity: VEGETARIAN. Food Allergies: MILK, EGGS. Food Cravings: SWEET, CRUNCHY. Preferred Cooking Methods: GRILLED, STEAMED. Daily Food Budget: ₱200-300 per day.",
        "john@example.com_preferences_updated": "1694567890123",
        
        # User 2 preferences  
        "maria@example.com_question_0": "STANDARD EATER",
        "maria@example.com_question_1": "PEANUTS,SHELLFISH",
        "maria@example.com_question_2": "SPICY,UMAMI",
        "maria@example.com_question_3": "FRIED,STIR-FRIED", 
        "maria@example.com_question_4": "₱100-200 per day",
        "maria@example.com_food_preferences_combined": "Food Identity: STANDARD EATER. Food Allergies: PEANUTS, SHELLFISH. Food Cravings: SPICY, UMAMI. Preferred Cooking Methods: FRIED, STIR-FRIED. Daily Food Budget: ₱100-200 per day.",
        "maria@example.com_preferences_updated": "1694567890456",
        
        # Other app data
        "current_user_email": "john@example.com",  # Currently logged in user
        "app_version": "1.0.0"
    }
    
    for key, value in shared_prefs_content.items():
        print(f"{key} = {value}")

def demo_loading_user_preferences():
    """Show how preferences are loaded for specific users"""
    
    print("\n\n3️⃣ LOADING USER-SPECIFIC PREFERENCES")
    print("=" * 80)
    
    print("When app loads food recommendations:")
    print("1. Get current logged-in user email: 'john@example.com'")
    print("2. Load preferences using user-specific keys:")
    print()
    
    current_user = "john@example.com"
    print(f"Loading preferences for: {current_user}")
    print("-" * 40)
    
    # Simulate the loading process
    user_keys = [
        f"{current_user}_question_0",
        f"{current_user}_question_1", 
        f"{current_user}_question_2",
        f"{current_user}_question_3",
        f"{current_user}_question_4",
        f"{current_user}_food_preferences_combined"
    ]
    
    for key in user_keys:
        print(f"Loading: {key}")
    
    print(f"\nResult: Only {current_user}'s preferences are loaded")
    print("Maria's preferences are completely ignored!")

def demo_user_isolation():
    """Show how users are completely isolated"""
    
    print("\n\n4️⃣ USER ISOLATION DEMONSTRATION")
    print("=" * 80)
    
    print("Scenario: Two users on the same device")
    print("-" * 40)
    
    print("👤 User 1 (john@example.com) logs in:")
    print("  • Completes food preferences questionnaire")
    print("  • Sets: VEGETARIAN, MILK/EGG allergies, sweet/crunchy cravings")
    print("  • Gets AI recommendations: Grilled sweet potato, steamed oatmeal")
    print()
    
    print("👤 User 2 (maria@example.com) logs in:")
    print("  • Completes food preferences questionnaire") 
    print("  • Sets: STANDARD EATER, PEANUT/SHELLFISH allergies, spicy/umami cravings")
    print("  • Gets AI recommendations: Spicy fried rice, stir-fried dishes")
    print()
    
    print("🔄 User 1 logs back in:")
    print("  • AI still recommends: Grilled sweet potato, steamed oatmeal")
    print("  • Maria's preferences are NOT used")
    print("  • Each user's preferences are completely separate")
    print()
    
    print("✅ KEY POINTS:")
    print("  • Each user has their own preference keys")
    print("  • Keys include the user's email address")
    print("  • Only the logged-in user's preferences are loaded")
    print("  • Users cannot see each other's preferences")
    print("  • Switching users loads different preferences")

def demo_code_implementation():
    """Show the actual code implementation"""
    
    print("\n\n5️⃣ CODE IMPLEMENTATION")
    print("=" * 80)
    
    print("Saving (FoodPreferencesActivity.java):")
    print("-" * 40)
    print("""
    // Get current user email
    String userEmail = prefs.getString("current_user_email", "");
    
    // Save with user-specific keys
    for (Map.Entry<String, String> entry : answers.entrySet()) {
        String key = userEmail + "_" + entry.getKey();  // john@example.com_question_0
        editor.putString(key, entry.getValue());
    }
    
    // Save combined preferences
    editor.putString(userEmail + "_food_preferences_combined", combinedPreferences);
    """)
    
    print("\nLoading (FoodActivityIntegration.java):")
    print("-" * 40)
    print("""
    // Get current user email
    String userEmail = prefs.getString("current_user_email", "");
    
    // Load user-specific preferences
    for (int i = 0; i < 5; i++) {
        String key = userEmail + "_question_" + i;  // john@example.com_question_0
        String value = prefs.getString(key, "");
        if (!value.isEmpty()) {
            answers.put("question_" + i, value);
        }
    }
    
    // Load combined preferences
    String combinedPreferences = prefs.getString(userEmail + "_food_preferences_combined", "");
    """)

if __name__ == "__main__":
    demo_user_specific_saving()
    demo_sharedpreferences_structure()
    demo_loading_user_preferences()
    demo_user_isolation()
    demo_code_implementation()
    
    print("\n\n🎉 SUMMARY")
    print("=" * 80)
    print("✅ Food preferences are saved per user using their email address")
    print("✅ Each user has completely separate preference storage")
    print("✅ Only the currently logged-in user's preferences are used")
    print("✅ Users cannot access each other's preferences")
    print("✅ Switching users loads different preferences automatically")
    print("\nThis ensures complete privacy and personalization! 🔒")
