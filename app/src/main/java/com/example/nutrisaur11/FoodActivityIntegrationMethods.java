package com.example.nutrisaur11;

import android.util.Log;
import java.util.Map;

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
                                                     String primaryCondition, Map<String, String> questionnaireAnswers) {
        
        StringBuilder prompt = new StringBuilder();
        
        // Smart AI nutritionist that adapts to all patient types and ages
        prompt.append("You are now a licensed nutritionist. Your role is to assess nutritional status and provide evidence-based food recommendations for any classification such as normal weight, underweight, overweight, and obese. Always explain the reasoning in simple words and suggest practical meal ideas that fit the classification. Consider balanced nutrition, portion sizes, and local food availability. Your goal is to help people understand what to eat, what to limit, and how to form healthy eating habits without being too strict. Provide clear meal suggestions for breakfast, lunch, dinner, and snacks.\n\n");
        
        prompt.append("PATIENT PROFILE:\n");
        prompt.append("Age: ").append(userAge != null ? userAge : "25").append(" years old | Sex: ").append(userSex != null ? userSex : "Not specified").append(" | BMI: ").append(userBMI != null ? userBMI : "22.5").append(" (").append(userBMICategory != null ? userBMICategory : "Normal").append(")\n");
        prompt.append("Weight: ").append(userWeight != null ? userWeight + " kg" : "Not specified").append(" | Height: ").append(userHeight != null ? userHeight + " cm" : "Not specified").append(" | Risk: ").append(userNutritionalRisk != null ? userNutritionalRisk : "Low").append("\n");
        prompt.append("Location: ").append(userMunicipality != null ? userMunicipality : "Not specified").append(" | Budget: ").append(userBudgetLevel != null ? userBudgetLevel : "Low").append("\n");
        prompt.append("Health Conditions: ").append(userHealthConditions != null ? userHealthConditions : "None").append("\n");
        prompt.append("Notes: ").append(userNotes != null ? userNotes : "No notes available").append("\n\n");
        
        prompt.append("PREFERENCES:\n");
        prompt.append("Diet: ").append(getQuestionnaireAnswer(questionnaireAnswers, "question_0")).append(" | Allergies: ").append(getQuestionnaireAnswer(questionnaireAnswers, "question_1")).append(" | Craving: ").append(getQuestionnaireAnswer(questionnaireAnswers, "question_2")).append("\n");
        prompt.append("Meal Type: ").append(getQuestionnaireAnswer(questionnaireAnswers, "question_3")).append(" | Planning: ").append(getQuestionnaireAnswer(questionnaireAnswers, "question_4")).append(" | Budget: ").append(getQuestionnaireAnswer(questionnaireAnswers, "question_5")).append("\n\n");
        
        // Add specific guidance based on BMI category
        if (userBMICategory != null) {
            if (userBMICategory.toLowerCase().contains("obese")) {
                prompt.append("CRITICAL: This patient is OBESE and needs immediate weight loss. As their nutritionist, you MUST recommend foods that create a calorie deficit. AVOID high-calorie, high-fat, high-sugar foods like Tapsilog, Champorado, Crispy Pata, Lechon Kawali, fried foods, and rice-heavy dishes. Focus on lean proteins, vegetables, and low-calorie options.\n\n");
            } else if (userBMICategory.toLowerCase().contains("overweight")) {
                prompt.append("IMPORTANT: This patient is OVERWEIGHT and needs moderate weight management. Focus on portion control and nutrient-dense foods. Limit high-calorie and fried foods.\n\n");
            } else if (userBMICategory.toLowerCase().contains("underweight")) {
                prompt.append("IMPORTANT: This patient is UNDERWEIGHT and needs nutritious weight gain. Focus on calorie-dense, nutrient-rich foods that promote healthy weight gain.\n\n");
                } else {
                prompt.append("IMPORTANT: This patient has a normal BMI. Focus on maintaining optimal health with balanced nutrition.\n\n");
            }
        }
        
        prompt.append("YOUR TASK: As a professional nutritionist, analyze this patient's complete profile (age, BMI, health status, preferences) and recommend 8 foods for each category (breakfast, lunch, dinner, snacks) that are specifically tailored to their individual needs. Consider their age-appropriate nutritional requirements and health goals.\n\n");
        
        prompt.append("Return JSON with 8 foods per category: breakfast, lunch, dinner, snacks.\n");
        prompt.append("Each food needs: food_name, calories, protein_g, fat_g, carbs_g, serving_size, diet_type, description.\n");
        prompt.append("IMPORTANT: diet_type must be exactly 'Breakfast', 'Lunch', 'Dinner', or 'Snacks' (not 'Balanced' or other values).\n");
        prompt.append("JSON structure must be: {\"breakfast\":[...], \"lunch\":[...], \"dinner\":[...], \"snacks\":[...]}\n");
        
        Log.d(TAG, "=== AI NUTRITIONIST PROMPT DEBUG ===");
        Log.d(TAG, "User Weight: " + userWeight + " kg");
        Log.d(TAG, "User Height: " + userHeight + " cm");
        Log.d(TAG, "User BMI: " + userBMI);
        Log.d(TAG, "User BMI Category: " + userBMICategory);
        Log.d(TAG, "Health Conditions: " + userHealthConditions);
        Log.d(TAG, "=== PROMPT PREVIEW (first 1500 chars) ===");
        Log.d(TAG, prompt.toString().substring(0, Math.min(1500, prompt.length())));
        Log.d(TAG, "=== END OF PROMPT PREVIEW ===");
        
        return prompt.toString();
    }
    
    private static String getQuestionnaireAnswer(Map<String, String> questionnaireAnswers, String questionKey) {
        if (questionnaireAnswers != null && questionnaireAnswers.containsKey(questionKey)) {
            String answer = questionnaireAnswers.get(questionKey);
            return answer != null && !answer.isEmpty() ? answer : "Not specified";
        }
        return "Not specified";
    }
    
}
