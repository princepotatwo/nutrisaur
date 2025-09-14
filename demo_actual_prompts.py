#!/usr/bin/env python3
"""
Demo showing ACTUAL prompts that are sent to AI after food preferences questionnaire
Shows the real implementation and how it affects food recommendations
"""

def demo_actual_prompt_flow():
    """Show the actual prompt flow in the app"""
    
    print("🤖 ACTUAL AI PROMPT FLOW AFTER FOOD PREFERENCES")
    print("=" * 80)
    
    # Simulate user completing food preferences questionnaire
    print("\n1️⃣ USER COMPLETES FOOD PREFERENCES QUESTIONNAIRE")
    print("-" * 60)
    
    user_answers = {
        "question_0": "VEGETARIAN",  # Food Identity
        "question_1": "MILK,EGGS",   # Allergies  
        "question_2": "SWEET,CRUNCHY",  # Cravings
        "question_3": "GRILLED,STEAMED",  # Cooking Methods
        "question_4": "₱200-300 per day"  # Budget
    }
    
    print("User answers:")
    for key, value in user_answers.items():
        print(f"  {key}: {value}")
    
    # Simulate the combineAllAnswers() method
    combined_preferences = "Food Identity: VEGETARIAN. Food Allergies: MILK, EGGS. Food Cravings: SWEET, CRUNCHY. Preferred Cooking Methods: GRILLED, STEAMED. Daily Food Budget: ₱200-300 per day."
    
    print(f"\nCombined preferences string:")
    print(f"  '{combined_preferences}'")
    
    # Show what gets saved to SharedPreferences
    print("\n2️⃣ SAVED TO SHAREDPREFERENCES")
    print("-" * 60)
    print("Keys saved:")
    print("  user@example.com_question_0: VEGETARIAN")
    print("  user@example.com_question_1: MILK,EGGS")
    print("  user@example.com_question_2: SWEET,CRUNCHY")
    print("  user@example.com_question_3: GRILLED,STEAMED")
    print("  user@example.com_question_4: ₱200-300 per day")
    print("  user@example.com_food_preferences_combined: [combined string]")
    
    # Show cache clearing
    print("\n3️⃣ CACHE CLEARING")
    print("-" * 60)
    print("Cache keys cleared:")
    cache_keys = [
        "user@example.com_last_food_recommendation_time",
        "user@example.com_last_breakfast_recommendation_time",
        "user@example.com_last_lunch_recommendation_time", 
        "user@example.com_last_dinner_recommendation_time",
        "user@example.com_last_snack_recommendation_time",
        "user@example.com_cached_food_recommendations",
        "user@example.com_cached_breakfast_foods",
        "user@example.com_cached_lunch_foods",
        "user@example.com_cached_dinner_foods",
        "user@example.com_cached_snack_foods"
    ]
    for key in cache_keys:
        print(f"  ❌ {key}")

def demo_actual_ai_prompts():
    """Show the actual AI prompts that are generated"""
    
    print("\n\n4️⃣ ACTUAL AI PROMPTS GENERATED")
    print("=" * 80)
    
    # Simulate user profile
    user_profile = {
        "age": "25",
        "sex": "Female", 
        "weight": "60",
        "height": "165",
        "bmi": "22.1",
        "bmi_category": "Normal",
        "municipality": "Manila",
        "health_conditions": "None"
    }
    
    # Simulate the combined preferences
    combined_preferences = "Food Identity: VEGETARIAN. Food Allergies: MILK, EGGS. Food Cravings: SWEET, CRUNCHY. Preferred Cooking Methods: GRILLED, STEAMED. Daily Food Budget: ₱200-300 per day."
    
    print("\n🍽️ FOOD RECOMMENDATION PROMPT")
    print("-" * 60)
    
    # This is the ACTUAL prompt that gets sent to AI
    actual_prompt = f"""You are now a licensed nutritionist. Your role is to assess nutritional status and provide evidence-based food recommendations for any classification such as normal weight, underweight, overweight, and obese. Always explain the reasoning in simple words and suggest practical meal ideas that fit the classification. Consider balanced nutrition, portion sizes, and local food availability. Your goal is to help people understand what to eat, what to limit, and how to form healthy eating habits without being too strict. Provide clear meal suggestions for breakfast, lunch, dinner, and snacks.

PATIENT PROFILE:
Age: {user_profile['age']} years old | Sex: {user_profile['sex']} | BMI: {user_profile['bmi']} ({user_profile['bmi_category']})
Weight: {user_profile['weight']} kg | Height: {user_profile['height']} cm | Risk: Low
Location: {user_profile['municipality']} | Budget: Low
Health Conditions: {user_profile['health_conditions']}
Notes: No notes available

PREFERENCES:
Food Preferences: {combined_preferences}

IMPORTANT: This patient has a normal BMI. Focus on maintaining optimal health with balanced nutrition.

YOUR TASK: As a professional nutritionist, analyze this patient's complete profile (age, BMI, health status, preferences) and recommend 8 foods for each category (breakfast, lunch, dinner, snacks) that are specifically tailored to their individual needs. Consider their age-appropriate nutritional requirements and health goals.

Return JSON with 8 foods per category: breakfast, lunch, dinner, snacks.
Each food needs: food_name, calories, protein_g, fat_g, carbs_g, serving_size, diet_type, description.
IMPORTANT: diet_type must be exactly 'Breakfast', 'Lunch', 'Dinner', or 'Snacks' (not 'Balanced' or other values).
JSON structure must be: {{"breakfast":[...], "lunch":[...], "dinner":[...], "snacks":[...]}}"""
    
    print(actual_prompt)
    
    print("\n🎯 KEY DIFFERENCES FROM OLD SYSTEM:")
    print("• OLD: Generic preferences like 'Diet: VEGETARIAN | Allergies: MILK | Craving: SWEET'")
    print("• NEW: Comprehensive string with all details: 'Food Identity: VEGETARIAN. Food Allergies: MILK, EGGS. Food Cravings: SWEET, CRUNCHY. Preferred Cooking Methods: GRILLED, STEAMED. Daily Food Budget: ₱200-300 per day.'")
    print("• OLD: 6 separate preference lines")
    print("• NEW: 1 comprehensive preferences line")
    print("• OLD: No budget information")
    print("• NEW: Specific PHP peso budget ranges")

def demo_food_substitution_prompts():
    """Show how food substitution prompts are affected"""
    
    print("\n\n🔄 FOOD SUBSTITUTION PROMPTS")
    print("=" * 80)
    
    original_food = "Scrambled Eggs with Toast"
    combined_preferences = "Food Identity: VEGETARIAN. Food Allergies: MILK, EGGS. Food Cravings: SWEET, CRUNCHY. Preferred Cooking Methods: GRILLED, STEAMED. Daily Food Budget: ₱200-300 per day."
    
    print(f"\nOriginal Food: {original_food}")
    print("\nACTUAL SUBSTITUTION PROMPT:")
    print("-" * 60)
    
    substitution_prompt = f"""You are a professional Filipino nutritionist.
Given an ORIGINAL DISH, provide 3 healthier substitutions.

RULES:
- Provide exactly 3 alternatives per dish.
- Substitution logic:
  1. Healthier version of the same dish (improved nutrition).
  2. Different dish with similar nutrition profile.
  3. Different dish with similar taste profile.
- Adapt strictly to user BMI and health profile.
- Calories must be within ±10% of original (preferably lower).
- Protein: equal or higher than original.
- Fat: lower than original, prioritize healthy fats.
- Carbs: equal or lower, prefer complex carbs.
- Serving size: always "1 serving".

INPUT:
ORIGINAL DISH:
Name: {original_food}
Calories: 350 kcal
Protein: 15g | Fat: 12g | Carbs: 45g

USER PROFILE:
BMI: 22.1
Allergies: MILK, EGGS
Diet Preferences: VEGETARIAN
Food Preferences: {combined_preferences}

OUTPUT:
Return ONLY a valid JSON array with 3 items:
[
  {{
    "food_name": "[Filipino Food Name Only]",
    "calories": <number>,
    "protein_g": <number>,
    "fat_g": <number>,
    "carbs_g": <number>,
    "serving_size": "1 serving",
    "diet_type": "[Type]",
    "description": "[Nutritional improvements and benefits]"
  }},
  ...
]"""
    
    print(substitution_prompt)
    
    print("\n🎯 IMPACT ON SUBSTITUTIONS:")
    print("• AI will avoid egg-based alternatives (user is vegetarian)")
    print("• AI will avoid milk-based alternatives (user has milk allergy)")
    print("• AI will focus on sweet and crunchy alternatives")
    print("• AI will suggest grilled/steamed preparations")
    print("• AI will consider ₱200-300 budget constraints")

def demo_real_ai_responses():
    """Show what AI responses would look like"""
    
    print("\n\n🤖 EXPECTED AI RESPONSES")
    print("=" * 80)
    
    print("\n🍳 BREAKFAST RECOMMENDATIONS (with preferences):")
    print("-" * 60)
    
    breakfast_recommendations = [
        {
            "food_name": "Grilled Sweet Potato with Coconut Yogurt",
            "calories": 280,
            "protein_g": 8,
            "fat_g": 6,
            "carbs_g": 52,
            "description": "Perfect for vegetarian diet, sweet cravings, and grilled cooking preference"
        },
        {
            "food_name": "Steamed Oatmeal with Crunchy Nuts",
            "calories": 320,
            "protein_g": 12,
            "fat_g": 8,
            "carbs_g": 58,
            "description": "Meets sweet and crunchy cravings, steamed preparation, no milk/eggs"
        },
        {
            "food_name": "Grilled Banana with Honey and Granola",
            "calories": 250,
            "protein_g": 6,
            "fat_g": 4,
            "carbs_g": 48,
            "description": "Sweet and crunchy, grilled method, vegetarian-friendly, budget-conscious"
        }
    ]
    
    for i, food in enumerate(breakfast_recommendations, 1):
        print(f"{i}. {food['food_name']}")
        print(f"   Calories: {food['calories']} | Protein: {food['protein_g']}g | Fat: {food['fat_g']}g | Carbs: {food['carbs_g']}g")
        print(f"   Description: {food['description']}")
        print()
    
    print("\n🔄 FOOD SUBSTITUTIONS (for Scrambled Eggs with Toast):")
    print("-" * 60)
    
    substitutions = [
        {
            "food_name": "Tofu Scramble with Grilled Toast",
            "calories": 320,
            "protein_g": 18,
            "fat_g": 8,
            "carbs_g": 45,
            "description": "Vegetarian alternative, grilled preparation, sweet and crunchy elements"
        },
        {
            "food_name": "Grilled Sweet Potato Hash",
            "calories": 280,
            "protein_g": 6,
            "fat_g": 4,
            "carbs_g": 52,
            "description": "Sweet and crunchy, grilled method, no eggs or milk, budget-friendly"
        },
        {
            "food_name": "Steamed Oatmeal with Crunchy Toppings",
            "calories": 300,
            "protein_g": 10,
            "fat_g": 6,
            "carbs_g": 50,
            "description": "Meets sweet/crunchy cravings, steamed preparation, vegetarian-safe"
        }
    ]
    
    for i, food in enumerate(substitutions, 1):
        print(f"{i}. {food['food_name']}")
        print(f"   Calories: {food['calories']} | Protein: {food['protein_g']}g | Fat: {food['fat_g']}g | Carbs: {food['carbs_g']}g")
        print(f"   Description: {food['description']}")
        print()

if __name__ == "__main__":
    demo_actual_prompt_flow()
    demo_actual_ai_prompts()
    demo_food_substitution_prompts()
    demo_real_ai_responses()
    
    print("\n\n🎉 SUMMARY")
    print("=" * 80)
    print("The food preferences questionnaire now provides:")
    print("✅ Comprehensive, detailed preference strings")
    print("✅ Specific dietary restrictions and allergies")
    print("✅ Taste preferences (sweet, crunchy, etc.)")
    print("✅ Cooking method preferences")
    print("✅ Budget constraints in PHP pesos")
    print("✅ Automatic cache clearing for fresh recommendations")
    print("✅ Integration with both food recommendations AND substitutions")
    print("\nThis makes AI responses much more personalized and relevant! 🚀")
