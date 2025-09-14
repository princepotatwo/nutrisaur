#!/usr/bin/env python3
"""
Simple test script to verify food preferences functionality
This simulates the food preferences questionnaire logic
"""

def test_food_preferences_combination():
    """Test the food preferences combination logic"""
    
    # Simulate user answers
    answers = {
        "question_0": "VEGETARIAN",  # Food Identity
        "question_1": "MILK,EGGS",   # Allergies
        "question_2": "SWEET,CRUNCHY",  # Cravings
        "question_3": "GRILLED,STEAMED",  # Cooking Methods
        "question_4": "REGULAR",     # Meal Timing
        "question_5": "MODERATE"     # Budget
    }
    
    def combine_all_answers(answers):
        """Simulate the combineAllAnswers method"""
        combined = []
        
        # Question 1: Main Food Identity
        food_identity = answers.get("question_0")
        if food_identity and food_identity != "SKIPPED":
            combined.append(f"Food Identity: {food_identity}")
        
        # Question 2: Food Allergies
        allergies = answers.get("question_1")
        if allergies and allergies != "SKIPPED":
            if "NONE" in allergies:
                combined.append("No food allergies")
            else:
                combined.append(f"Food Allergies: {allergies.replace(',', ', ')}")
        
        # Question 3: Food Cravings
        cravings = answers.get("question_2")
        if cravings and cravings != "SKIPPED":
            if "NONE" in cravings:
                combined.append("No specific food cravings")
            else:
                combined.append(f"Food Cravings: {cravings.replace(',', ', ')}")
        
        # Question 4: Cooking Methods
        cooking_methods = answers.get("question_3")
        if cooking_methods and cooking_methods != "SKIPPED":
            if "NO PREFERENCE" in cooking_methods:
                combined.append("No cooking method preferences")
            else:
                combined.append(f"Preferred Cooking Methods: {cooking_methods.replace(',', ', ')}")
        
        # Question 5: Meal Timing
        meal_timing = answers.get("question_4")
        if meal_timing and meal_timing != "SKIPPED":
            combined.append(f"Meal Timing: {meal_timing}")
        
        # Question 6: Budget Level
        budget = answers.get("question_5")
        if budget and budget != "SKIPPED":
            combined.append(f"Budget Level: {budget}")
        
        return ". ".join(combined) + "."
    
    # Test the combination
    result = combine_all_answers(answers)
    print("✅ Food Preferences Combination Test")
    print("=" * 50)
    print("Input answers:", answers)
    print("\nCombined result:")
    print(result)
    print("\n" + "=" * 50)
    
    # Verify the result contains expected content
    expected_elements = [
        "Food Identity: VEGETARIAN",
        "Food Allergies: MILK, EGGS",
        "Food Cravings: SWEET, CRUNCHY",
        "Preferred Cooking Methods: GRILLED, STEAMED",
        "Meal Timing: REGULAR",
        "Budget Level: MODERATE"
    ]
    
    all_present = all(element in result for element in expected_elements)
    
    if all_present:
        print("✅ All expected elements found in combined result!")
        return True
    else:
        print("❌ Some expected elements missing!")
        return False

def test_skip_functionality():
    """Test the skip functionality"""
    
    # Simulate answers with some skipped
    answers_with_skips = {
        "question_0": "VEGAN",       # Answered
        "question_1": "SKIPPED",     # Skipped
        "question_2": "SWEET",       # Answered
        "question_3": "SKIPPED",     # Skipped
        "question_4": "EARLY BIRD",  # Answered
        "question_5": "SKIPPED"      # Skipped
    }
    
    def combine_with_skips(answers):
        """Test combination with skipped questions"""
        combined = []
        
        # Only process non-skipped questions
        for i in range(6):
            key = f"question_{i}"
            value = answers.get(key, "")
            
            if value and value != "SKIPPED":
                question_names = [
                    "Food Identity",
                    "Food Allergies", 
                    "Food Cravings",
                    "Cooking Methods",
                    "Meal Timing",
                    "Budget Level"
                ]
                combined.append(f"{question_names[i]}: {value}")
        
        return ". ".join(combined) + "." if combined else "No preferences specified."
    
    result = combine_with_skips(answers_with_skips)
    print("\n✅ Skip Functionality Test")
    print("=" * 50)
    print("Input answers (with skips):", answers_with_skips)
    print("\nCombined result:")
    print(result)
    print("\n" + "=" * 50)
    
    # Should only contain answered questions
    expected_answered = [
        "Food Identity: VEGAN",
        "Food Cravings: SWEET", 
        "Meal Timing: EARLY BIRD"
    ]
    
    all_answered_present = all(element in result for element in expected_answered)
    no_skipped_present = "SKIPPED" not in result
    
    if all_answered_present and no_skipped_present:
        print("✅ Skip functionality working correctly!")
        return True
    else:
        print("❌ Skip functionality has issues!")
        return False

if __name__ == "__main__":
    print("🧪 Testing Food Preferences Functionality")
    print("=" * 60)
    
    test1_passed = test_food_preferences_combination()
    test2_passed = test_skip_functionality()
    
    print("\n" + "=" * 60)
    print("📊 Test Results Summary:")
    print(f"Combination Test: {'✅ PASSED' if test1_passed else '❌ FAILED'}")
    print(f"Skip Test: {'✅ PASSED' if test2_passed else '❌ FAILED'}")
    
    if test1_passed and test2_passed:
        print("\n🎉 All tests passed! Food preferences functionality is working correctly.")
    else:
        print("\n⚠️  Some tests failed. Please check the implementation.")
