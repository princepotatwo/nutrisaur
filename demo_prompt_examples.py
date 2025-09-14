#!/usr/bin/env python3
"""
Demo script showing how food preferences affect AI prompts
Shows before/after examples of food recommendations and substitutions
"""

def demo_food_preferences_impact():
    """Demonstrate how food preferences affect AI prompts"""
    
    print("🍽️ FOOD PREFERENCES IMPACT ON AI PROMPTS")
    print("=" * 80)
    
    # Example 1: User completes food preferences questionnaire
    print("\n📝 EXAMPLE 1: User completes food preferences questionnaire")
    print("-" * 60)
    
    user_preferences = {
        "question_0": "VEGETARIAN",  # Food Identity
        "question_1": "MILK,EGGS",   # Allergies
        "question_2": "SWEET,CRUNCHY",  # Cravings
        "question_3": "GRILLED,STEAMED",  # Cooking Methods
        "question_4": "₱200-300 per day"  # Budget
    }
    
    # Simulate the combineAllAnswers method
    combined_preferences = "Food Identity: VEGETARIAN. Food Allergies: MILK, EGGS. Food Cravings: SWEET, CRUNCHY. Preferred Cooking Methods: GRILLED, STEAMED. Daily Food Budget: ₱200-300 per day."
    
    print("User's Food Preferences:")
    for key, value in user_preferences.items():
        print(f"  {key}: {value}")
    
    print(f"\nCombined Preferences String:")
    print(f"  '{combined_preferences}'")
    
    # Example 2: Food Recommendation Prompt
    print("\n🤖 EXAMPLE 2: Food Recommendation Prompt")
    print("-" * 60)
    
    # BEFORE: Without food preferences
    prompt_before = """
    User Profile: 25-year-old female, 60kg, 165cm, BMI: 22.1
    Nutrition Goals: Weight maintenance
    Daily Calorie Target: 2000 calories
    Current Meal: Breakfast
    Request: Recommend healthy breakfast options
    """
    
    # AFTER: With food preferences
    prompt_after = """
    User Profile: 25-year-old female, 60kg, 165cm, BMI: 22.1
    Nutrition Goals: Weight maintenance
    Daily Calorie Target: 2000 calories
    Current Meal: Breakfast
    Request: Recommend healthy breakfast options
    
    Food Preferences: Food Identity: VEGETARIAN. Food Allergies: MILK, EGGS. Food Cravings: SWEET, CRUNCHY. Preferred Cooking Methods: GRILLED, STEAMED. Daily Food Budget: ₱200-300 per day.
    """
    
    print("BEFORE (without preferences):")
    print(prompt_before.strip())
    
    print("\nAFTER (with preferences):")
    print(prompt_after.strip())
    
    print("\n🎯 IMPACT:")
    print("• AI will avoid milk and egg-based foods")
    print("• AI will focus on vegetarian options")
    print("• AI will suggest sweet and crunchy foods")
    print("• AI will recommend grilled/steamed cooking methods")
    print("• AI will consider ₱200-300 daily budget")
    
    # Example 3: Food Substitution Prompt
    print("\n🔄 EXAMPLE 3: Food Substitution Prompt")
    print("-" * 60)
    
    original_food = "Scrambled Eggs with Toast"
    
    # BEFORE: Without food preferences
    sub_prompt_before = f"""
    User Profile: 25-year-old female, 60kg, 165cm, BMI: 22.1
    Original Food: {original_food}
    Request: Suggest healthy alternatives
    """
    
    # AFTER: With food preferences
    sub_prompt_after = f"""
    User Profile: 25-year-old female, 60kg, 165cm, BMI: 22.1
    Original Food: {original_food}
    Request: Suggest healthy alternatives
    
    Food Preferences: Food Identity: VEGETARIAN. Food Allergies: MILK, EGGS. Food Cravings: SWEET, CRUNCHY. Preferred Cooking Methods: GRILLED, STEAMED. Daily Food Budget: ₱200-300 per day.
    """
    
    print(f"Original Food: {original_food}")
    print("\nBEFORE (without preferences):")
    print(sub_prompt_before.strip())
    
    print("\nAFTER (with preferences):")
    print(sub_prompt_after.strip())
    
    print("\n🎯 IMPACT:")
    print("• AI will avoid egg-based substitutions")
    print("• AI will suggest vegetarian alternatives")
    print("• AI will focus on sweet/crunchy options")
    print("• AI will recommend grilled/steamed preparations")
    print("• AI will consider budget-friendly options")
    
    # Example 4: Different User Preferences
    print("\n👤 EXAMPLE 4: Different User Preferences")
    print("-" * 60)
    
    different_preferences = {
        "question_0": "STANDARD EATER",
        "question_1": "PEANUTS,SHELLFISH",  # Different allergies
        "question_2": "SPICY,UMAMI",  # Different cravings
        "question_3": "FRIED,STIR-FRIED",  # Different cooking methods
        "question_4": "₱100-200 per day"  # Different budget
    }
    
    different_combined = "Food Identity: STANDARD EATER. Food Allergies: PEANUTS, SHELLFISH. Food Cravings: SPICY, UMAMI. Preferred Cooking Methods: FRIED, STIR-FRIED. Daily Food Budget: ₱100-200 per day."
    
    print("Different User's Preferences:")
    for key, value in different_preferences.items():
        print(f"  {key}: {value}")
    
    print(f"\nCombined: '{different_combined}'")
    
    print("\n🎯 IMPACT ON SAME BREAKFAST REQUEST:")
    print("• AI will avoid peanuts and shellfish")
    print("• AI will suggest spicy and umami flavors")
    print("• AI will recommend fried/stir-fried preparations")
    print("• AI will focus on budget-friendly ₱100-200 options")
    print("• AI can include meat and dairy (standard eater)")
    
    # Example 5: Cache Clearing Impact
    print("\n🗑️ EXAMPLE 5: Cache Clearing Impact")
    print("-" * 60)
    
    print("When user completes food preferences questionnaire:")
    print("1. ✅ Old food recommendations are cleared from cache")
    print("2. ✅ New preferences are saved to SharedPreferences")
    print("3. ✅ Next food recommendation request will:")
    print("   - Use fresh AI prompts with new preferences")
    print("   - Generate completely new recommendations")
    print("   - Not use any cached old recommendations")
    
    print("\nCache Keys Cleared:")
    cache_keys = [
        "user_email_last_food_recommendation_time",
        "user_email_last_breakfast_recommendation_time", 
        "user_email_last_lunch_recommendation_time",
        "user_email_last_dinner_recommendation_time",
        "user_email_last_snack_recommendation_time",
        "user_email_cached_food_recommendations",
        "user_email_cached_breakfast_foods",
        "user_email_cached_lunch_foods", 
        "user_email_cached_dinner_foods",
        "user_email_cached_snack_foods"
    ]
    
    for key in cache_keys:
        print(f"  • {key}")

def demo_real_world_scenarios():
    """Show real-world scenarios of how preferences affect recommendations"""
    
    print("\n\n🌍 REAL-WORLD SCENARIOS")
    print("=" * 80)
    
    scenarios = [
        {
            "user": "Vegetarian with milk allergy, sweet cravings, ₱200-300 budget",
            "preferences": "Food Identity: VEGETARIAN. Food Allergies: MILK. Food Cravings: SWEET. Preferred Cooking Methods: GRILLED, STEAMED. Daily Food Budget: ₱200-300 per day.",
            "breakfast_request": "Recommend healthy breakfast",
            "ai_response": "• Grilled sweet potato with coconut yogurt\n• Steamed oatmeal with fruits and nuts\n• Grilled banana with honey and granola\n• Sweet smoothie bowl with grilled fruits"
        },
        {
            "user": "Standard eater, no allergies, spicy cravings, ₱100-200 budget", 
            "preferences": "Food Identity: STANDARD EATER. Food Allergies: None. Food Cravings: SPICY. Preferred Cooking Methods: FRIED, STIR-FRIED. Daily Food Budget: ₱100-200 per day.",
            "breakfast_request": "Recommend healthy breakfast",
            "ai_response": "• Spicy fried rice with vegetables\n• Stir-fried eggs with chili and onions\n• Spicy breakfast burrito\n• Fried spicy tofu scramble"
        },
        {
            "user": "Vegan, no allergies, crunchy cravings, ₱300-500 budget",
            "preferences": "Food Identity: VEGAN. Food Allergies: None. Food Cravings: CRUNCHY. Preferred Cooking Methods: BAKED, RAW. Daily Food Budget: ₱300-500 per day.",
            "breakfast_request": "Recommend healthy breakfast", 
            "ai_response": "• Crunchy granola with plant milk\n• Baked sweet potato chips with avocado\n• Raw crunchy vegetable smoothie bowl\n• Baked crispy chickpea scramble"
        }
    ]
    
    for i, scenario in enumerate(scenarios, 1):
        print(f"\n📋 SCENARIO {i}: {scenario['user']}")
        print("-" * 60)
        print(f"Preferences: {scenario['preferences']}")
        print(f"Request: {scenario['breakfast_request']}")
        print(f"AI Response:")
        for item in scenario['ai_response'].split('\n'):
            print(f"  {item}")

if __name__ == "__main__":
    demo_food_preferences_impact()
    demo_real_world_scenarios()
    
    print("\n\n🎉 SUMMARY")
    print("=" * 80)
    print("The food preferences questionnaire now:")
    print("✅ Provides specific, personalized AI prompts")
    print("✅ Considers dietary restrictions and allergies") 
    print("✅ Matches user's taste preferences")
    print("✅ Suggests appropriate cooking methods")
    print("✅ Respects budget constraints")
    print("✅ Clears old recommendations for fresh results")
    print("\nThis makes food recommendations much more relevant and useful! 🚀")
