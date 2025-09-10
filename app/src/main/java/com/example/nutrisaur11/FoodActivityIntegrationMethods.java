package com.example.nutrisaur11;

import android.util.Log;

public class FoodActivityIntegrationMethods {
    private static final String TAG = "FoodActivityIntegrationMethods";
    
    public static String buildConditionSpecificPrompt(String userAge, String userSex, String userBMI, 
                                                     String userHeight, String userWeight,
                                                     String userBMICategory, String userMUAC, 
                                                     String userMUACCategory, String userNutritionalRisk,
                                                     String userHealthConditions, String userActivityLevel, 
                                                     String userBudgetLevel, String userDietaryRestrictions, 
                                                     String userAllergies, String userDietPrefs, 
                                                     String userAvoidFoods, String userRiskScore, 
                                                     String userBarangay, String userIncome, 
                                                     String userPregnancyStatus, String userMunicipality, 
                                                     String userScreeningDate, String userNotes,
                                                     String primaryCondition) {
        
        StringBuilder prompt = new StringBuilder();
        
        // Simple, intelligent prompt that lets the AI be the expert
        prompt.append("You are a PROFESSIONAL NUTRITIONIST and EXPERT CHEF specializing in Filipino cuisine and malnutrition recovery. ");
        prompt.append("Analyze this person's complete nutritional profile and provide personalized food recommendations. ");
        prompt.append("Generate EXACTLY 8 food dishes for EACH of the 4 meal categories (breakfast, lunch, dinner, snacks).\n\n");
        
        // Present all the data and let AI analyze it
        prompt.append("PATIENT NUTRITIONAL PROFILE:\n");
        prompt.append("Age: ").append(userAge != null ? userAge : "25").append(" years old\n");
        prompt.append("Sex: ").append(userSex != null ? userSex : "Not specified").append("\n");
        prompt.append("Height: ").append(userHeight != null ? userHeight + " cm" : "Not specified").append("\n");
        prompt.append("Weight: ").append(userWeight != null ? userWeight + " kg" : "Not specified").append("\n");
        prompt.append("BMI: ").append(userBMI != null ? userBMI : "22.5").append(" (").append(userBMICategory != null ? userBMICategory : "Normal").append(")\n");
        prompt.append("MUAC: ").append(userMUAC != null ? userMUAC + " cm" : "Not specified").append(" (").append(userMUACCategory != null ? userMUACCategory : "Normal").append(")\n");
        prompt.append("Nutritional Risk Level: ").append(userNutritionalRisk != null ? userNutritionalRisk : "Low").append("\n");
        prompt.append("Pregnancy Status: ").append(userPregnancyStatus != null ? userPregnancyStatus : "Not Applicable").append("\n");
        prompt.append("Health Conditions: ").append(userHealthConditions != null ? userHealthConditions : "None").append("\n");
        prompt.append("Activity Level: ").append(userActivityLevel != null ? userActivityLevel : "Moderate").append("\n");
        prompt.append("Budget Level: ").append(userBudgetLevel != null ? userBudgetLevel : "Low").append("\n");
        prompt.append("Dietary Restrictions: ").append(userDietaryRestrictions != null ? userDietaryRestrictions : "None").append("\n");
        prompt.append("Allergies: ").append(userAllergies != null ? userAllergies : "None").append("\n");
        prompt.append("Diet Preferences: ").append(userDietPrefs != null ? userDietPrefs : "None").append("\n");
        prompt.append("Foods to Avoid: ").append(userAvoidFoods != null ? userAvoidFoods : "None").append("\n");
        prompt.append("Location: ").append(userMunicipality != null ? userMunicipality : "Not specified").append(", ").append(userBarangay != null ? userBarangay : "Not specified").append("\n");
        prompt.append("Income Level: ").append(userIncome != null ? userIncome : "Low").append("\n");
        prompt.append("Screening Notes: ").append(userNotes != null ? userNotes : "None").append("\n");
        
        // Let the AI be the expert
        prompt.append("\nAs a professional nutritionist, analyze this profile and provide appropriate recommendations:\n");
        prompt.append("- Consider age-specific nutritional needs\n");
        prompt.append("- Address BMI category and weight management goals\n");
        prompt.append("- Account for nutritional risk level and health conditions\n");
        prompt.append("- Respect dietary restrictions, allergies, and preferences\n");
        prompt.append("- Consider budget and local food availability\n");
        prompt.append("- Provide Filipino cuisine options when appropriate\n");
        prompt.append("- Ensure foods are appropriate for the person's condition\n\n");
        
        // Simple response format
        prompt.append("RESPONSE FORMAT:\n");
        prompt.append("Return a JSON object with exactly 8 foods for each category (breakfast, lunch, dinner, snacks). ");
        prompt.append("Each food must include: food_name, calories, protein_g, fat_g, carbs_g, serving_size, diet_type, description.\n");
        
        Log.d(TAG, "=== SIMPLIFIED AI NUTRITIONIST PROMPT (first 1000 chars) ===");
        Log.d(TAG, prompt.toString().substring(0, Math.min(1000, prompt.length())));
        Log.d(TAG, "=== END OF PROMPT PREVIEW ===");
        
        return prompt.toString();
    }
    
}
