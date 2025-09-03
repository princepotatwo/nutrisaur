package com.example.nutrisaur11;

import java.util.Arrays;
import java.util.List;

public class PersonalizationQuestion {
    private String title;
    private List<Choice> choices;
    private boolean isMultipleChoice;
    private int colorResId;
    
    public PersonalizationQuestion(String title, List<Choice> choices, boolean isMultipleChoice, int colorResId) {
        this.title = title;
        this.choices = choices;
        this.isMultipleChoice = isMultipleChoice;
        this.colorResId = colorResId;
    }
    
    public String getTitle() { return title; }
    public List<Choice> getChoices() { return choices; }
    public boolean isMultipleChoice() { return isMultipleChoice; }
    public int getColorResId() { return colorResId; }
    
    public static List<PersonalizationQuestion> getQuestions() {
        return Arrays.asList(
            // Question 1: Main Food Identity
            new PersonalizationQuestion(
                "Main Food Identity",
                Arrays.asList(
                    new Choice("HALAL", "only halal foods, no pork, no alcohol", R.color.purple_band),
                    new Choice("KOSHER", "only kosher foods, no pork, no shellfish, kosher prep", R.color.blue_band),
                    new Choice("VEGETARIAN", "no meat, but may eat dairy/eggs", R.color.orange_band),
                    new Choice("VEGAN", "no animal products at all", R.color.pink_band),
                    new Choice("PESCATARIAN", "fish allowed, no other meat", R.color.light_blue_band),
                    new Choice("STANDARD EATER", "no special rules", R.color.green_band),
                    new Choice("SKIP", "", R.color.gray_band)
                ),
                false,
                R.color.purple_band
            ),
            
            // Question 2: Food Allergies
            new PersonalizationQuestion(
                "Food Allergies / Intolerances",
                Arrays.asList(
                    new Choice("PEANUTS", "", R.color.red_band),
                    new Choice("TREE NUTS", "", R.color.orange_band),
                    new Choice("MILK", "", R.color.blue_band),
                    new Choice("EGGS", "", R.color.yellow_band),
                    new Choice("FISH", "", R.color.cyan_band),
                    new Choice("SHELLFISH", "", R.color.purple_band),
                    new Choice("SOY", "", R.color.green_band),
                    new Choice("WHEAT / GLUTEN", "", R.color.brown_band),
                    new Choice("SKIP", "", R.color.gray_band)
                ),
                true,
                R.color.blue_band
            ),
            
            // Question 3: Current Craving
            new PersonalizationQuestion(
                "Current Craving",
                Arrays.asList(
                    new Choice("COMFORT FOOD", "", R.color.purple_band),
                    new Choice("LIGHT & FRESH", "", R.color.light_blue_band),
                    new Choice("SPICY & BOLD", "", R.color.red_band),
                    new Choice("SWEET TREATS", "", R.color.pink_band),
                    new Choice("SAVORY DISHES", "", R.color.orange_band),
                    new Choice("SOMETHING NEW", "", R.color.green_band),
                    new Choice("TRADITIONAL FILIPINO", "", R.color.brown_band),
                    new Choice("QUICK & EASY", "", R.color.blue_band),
                    new Choice("SOMETHING FANCY", "", R.color.purple_band),
                    new Choice("STREET FOOD", "", R.color.orange_band),
                    new Choice("HOME-COOKED STYLE", "", R.color.green_band),
                    new Choice("SKIP", "", R.color.gray_band)
                ),
                false,
                R.color.orange_band
            ),
            
            // Question 4: Meal Type
            new PersonalizationQuestion(
                "Meal Type",
                Arrays.asList(
                    new Choice("BREAKFAST", "", R.color.orange_band),
                    new Choice("LUNCH", "", R.color.blue_band),
                    new Choice("DINNER", "", R.color.purple_band),
                    new Choice("SNACK", "", R.color.green_band),
                    new Choice("DESSERT", "", R.color.pink_band),
                    new Choice("ANY MEAL", "", R.color.gray_band),
                    new Choice("LIGHT MEAL", "", R.color.light_blue_band),
                    new Choice("HEAVY MEAL", "", R.color.brown_band),
                    new Choice("BALANCED MEAL", "", R.color.green_band),
                    new Choice("QUICK BITE", "", R.color.blue_band),
                    new Choice("SKIP", "", R.color.gray_band)
                ),
                false,
                R.color.green_band
            ),
            
            // Question 5: Planning Preference
            new PersonalizationQuestion(
                "Planning Preference",
                Arrays.asList(
                    new Choice("JUST FIND ME A DISH TO EAT", "", R.color.blue_band),
                    new Choice("MEAL PLAN FOR TODAY", "", R.color.green_band),
                    new Choice("WEEKLY MEAL PLAN", "", R.color.purple_band),
                    new Choice("MONTHLY MEAL PLAN", "", R.color.orange_band),
                    new Choice("I WANT TO EXPLORE NEW FOODS", "", R.color.pink_band),
                    new Choice("I WANT TO TRY FILIPINO FOOD", "", R.color.brown_band),
                    new Choice("I WANT TO DISCOVER REGIONAL DISHES", "", R.color.red_band),
                    new Choice("I WANT TO TRY FUSION FOOD", "", R.color.cyan_band),
                    new Choice("SKIP", "", R.color.gray_band)
                ),
                false,
                R.color.purple_band
            ),
            
            // Question 6: Budget Range
            new PersonalizationQuestion(
                "Budget Range",
                Arrays.asList(
                    new Choice("UNDER ₱50", "", R.color.green_band),
                    new Choice("₱50-₱100", "", R.color.light_blue_band),
                    new Choice("₱100-₱200", "", R.color.blue_band),
                    new Choice("₱200-₱500", "", R.color.orange_band),
                    new Choice("₱500-₱1000", "", R.color.purple_band),
                    new Choice("OVER ₱1000", "", R.color.pink_band),
                    new Choice("I WANT TO SAVE MONEY", "", R.color.green_band),
                    new Choice("BUDGET IS FLEXIBLE", "", R.color.gray_band),
                    new Choice("I WANT PREMIUM FOOD", "", R.color.purple_band),
                    new Choice("SKIP", "", R.color.gray_band)
                ),
                false,
                R.color.green_band
            )
        );
    }
}
