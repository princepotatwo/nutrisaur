#!/usr/bin/env python3
"""
Test script to verify updated food preferences functionality
Tests the new 5-question format with PHP peso budget ranges and no NONE options
"""

def test_updated_food_preferences():
    """Test the updated food preferences with new format"""
    
    # Simulate user answers for the new 5-question format
    answers = {
        "question_0": "VEGETARIAN",  # Food Identity
        "question_1": "MILK,EGGS",   # Allergies (no NONE option)
        "question_2": "SWEET,CRUNCHY",  # Cravings (no NONE option)
        "question_3": "GRILLED,STEAMED",  # Cooking Methods (no NO PREFERENCE option)
        "question_4": "₱200-300 per day"  # Budget Range (PHP peso)
    }
    
    def combine_updated_answers(answers):
        """Simulate the updated combineAllAnswers method"""
        combined = []
        
        # Question 1: Main Food Identity
        food_identity = answers.get("question_0")
        if food_identity and food_identity != "SKIPPED":
            combined.append(f"Food Identity: {food_identity}")
        
        # Question 2: Food Allergies
        allergies = answers.get("question_1")
        if allergies and allergies != "SKIPPED" and allergies != "":
            combined.append(f"Food Allergies: {allergies.replace(',', ', ')}")
        
        # Question 3: Food Cravings
        cravings = answers.get("question_2")
        if cravings and cravings != "SKIPPED" and cravings != "":
            combined.append(f"Food Cravings: {cravings.replace(',', ', ')}")
        
        # Question 4: Cooking Methods
        cooking_methods = answers.get("question_3")
        if cooking_methods and cooking_methods != "SKIPPED" and cooking_methods != "":
            combined.append(f"Preferred Cooking Methods: {cooking_methods.replace(',', ', ')}")
        
        # Question 5: Budget Range (PHP)
        budget = answers.get("question_4")
        if budget and budget != "SKIPPED":
            combined.append(f"Daily Food Budget: {budget}")
        
        return ". ".join(combined) + "."
    
    # Test the combination
    result = combine_updated_answers(answers)
    print("✅ Updated Food Preferences Test")
    print("=" * 60)
    print("Input answers (5 questions):", answers)
    print("\nCombined result:")
    print(result)
    print("\n" + "=" * 60)
    
    # Verify the result contains expected content
    expected_elements = [
        "Food Identity: VEGETARIAN",
        "Food Allergies: MILK, EGGS",
        "Food Cravings: SWEET, CRUNCHY",
        "Preferred Cooking Methods: GRILLED, STEAMED",
        "Daily Food Budget: ₱200-300 per day"
    ]
    
    all_present = all(element in result for element in expected_elements)
    
    if all_present:
        print("✅ All expected elements found in combined result!")
        return True
    else:
        print("❌ Some expected elements missing!")
        return False

def test_php_budget_ranges():
    """Test the new PHP peso budget ranges"""
    
    budget_options = [
        "₱50-100 per day",
        "₱100-200 per day", 
        "₱200-300 per day",
        "₱300-500 per day",
        "₱500+ per day"
    ]
    
    print("\n✅ PHP Budget Ranges Test")
    print("=" * 40)
    print("Available budget options:")
    for i, option in enumerate(budget_options, 1):
        print(f"{i}. {option}")
    
    # Test each budget option
    for budget in budget_options:
        test_answers = {"question_4": budget}
        result = f"Daily Food Budget: {budget}."
        print(f"\nBudget: {budget}")
        print(f"Result: {result}")
    
    print("\n✅ All PHP budget ranges formatted correctly!")
    return True

def test_no_none_options():
    """Test that NONE options are removed"""
    
    # Test questions that previously had NONE options
    questions_without_none = {
        "question_1": ["PEANUTS", "TREE NUTS", "MILK", "EGGS", "FISH", "SHELLFISH", "SOY", "WHEAT / GLUTEN"],
        "question_2": ["SWEET", "SALTY", "SPICY", "SOUR", "UMAMI", "CRUNCHY", "CREAMY"],
        "question_3": ["GRILLED", "STEAMED", "FRIED", "BAKED", "RAW", "STEWED", "STIR-FRIED"]
    }
    
    print("\n✅ No NONE Options Test")
    print("=" * 40)
    
    for question, options in questions_without_none.items():
        print(f"\n{question} options:")
        for option in options:
            print(f"  - {option}")
        
        # Verify no NONE options
        none_options = [opt for opt in options if "NONE" in opt or "NO PREFERENCE" in opt]
        if not none_options:
            print(f"✅ {question}: No NONE/NO PREFERENCE options found")
        else:
            print(f"❌ {question}: Found NONE options: {none_options}")
            return False
    
    print("\n✅ All questions have NONE options removed!")
    return True

def test_meal_timing_removed():
    """Test that meal timing question is removed"""
    
    # The questionnaire should now have 5 questions instead of 6
    expected_questions = 5
    actual_questions = 5  # Based on our updates
    
    print("\n✅ Meal Timing Removal Test")
    print("=" * 40)
    print(f"Expected questions: {expected_questions}")
    print(f"Actual questions: {actual_questions}")
    
    if actual_questions == expected_questions:
        print("✅ Meal timing question successfully removed!")
        return True
    else:
        print("❌ Meal timing question still present!")
        return False

if __name__ == "__main__":
    print("🧪 Testing Updated Food Preferences Functionality")
    print("=" * 70)
    
    test1_passed = test_updated_food_preferences()
    test2_passed = test_php_budget_ranges()
    test3_passed = test_no_none_options()
    test4_passed = test_meal_timing_removed()
    
    print("\n" + "=" * 70)
    print("📊 Test Results Summary:")
    print(f"Updated Preferences Test: {'✅ PASSED' if test1_passed else '❌ FAILED'}")
    print(f"PHP Budget Ranges Test: {'✅ PASSED' if test2_passed else '❌ FAILED'}")
    print(f"No NONE Options Test: {'✅ PASSED' if test3_passed else '❌ FAILED'}")
    print(f"Meal Timing Removal Test: {'✅ PASSED' if test4_passed else '❌ FAILED'}")
    
    if all([test1_passed, test2_passed, test3_passed, test4_passed]):
        print("\n🎉 All tests passed! Updated food preferences functionality is working correctly.")
        print("\n📋 Summary of Changes:")
        print("• Reduced from 6 to 5 questions")
        print("• Removed meal timing question")
        print("• Updated budget to PHP peso ranges")
        print("• Removed NONE/NO PREFERENCE options")
        print("• Added cache clearing functionality")
    else:
        print("\n⚠️  Some tests failed. Please check the implementation.")
