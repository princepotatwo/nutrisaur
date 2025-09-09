package com.example.nutrisaur11;

import android.content.Context;
import android.util.Log;
import java.util.*;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import okhttp3.*;
import org.json.JSONObject;
import org.json.JSONArray;
import org.json.JSONException;
import java.io.IOException;

/**
 * Food Details Manager
 * Handles detailed food information including nutrients and ingredients
 */
public class FoodDetailsManager {
    private static final String TAG = "FoodDetailsManager";
    
    // Gemini API configuration
    private static final String GEMINI_API_KEY = "AIzaSyAR0YOJALZphmQaSbc5Ydzs5kZS6eCefJM";
    private static final String GEMINI_TEXT_API_URL = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" + GEMINI_API_KEY;
    
    private Context context;
    private ExecutorService executorService;
    
    public interface FoodDetailsCallback {
        void onFoodDetailsFound(FoodDetails foodDetails);
        void onError(String error);
    }
    
    public FoodDetailsManager(Context context) {
        this.context = context;
        this.executorService = Executors.newFixedThreadPool(2);
    }
    
    /**
     * Get detailed food information including nutrients and ingredients
     */
    public void getFoodDetails(FoodRecommendation food, FoodDetailsCallback callback) {
        executorService.execute(() -> {
            try {
                Log.d(TAG, "Getting detailed information for: " + food.getFoodName());
                
                // Build food details prompt
                String detailsPrompt = buildFoodDetailsPrompt(food);
                
                // Call Gemini API for food details
                FoodDetails foodDetails = callGeminiForFoodDetails(detailsPrompt, food);
                
                if (foodDetails != null) {
                    Log.d(TAG, "Found detailed information for: " + food.getFoodName());
                    callback.onFoodDetailsFound(foodDetails);
                } else {
                    Log.w(TAG, "No detailed information found, using fallback");
                    FoodDetails fallbackDetails = getFallbackFoodDetails(food);
                    callback.onFoodDetailsFound(fallbackDetails);
                }
                
            } catch (Exception e) {
                Log.e(TAG, "Error getting food details: " + e.getMessage());
                callback.onError("Failed to get food details: " + e.getMessage());
            }
        });
    }
    
    private String buildFoodDetailsPrompt(FoodRecommendation food) {
        StringBuilder prompt = new StringBuilder();
        prompt.append("You are an expert nutritionist and chef specializing in Filipino cuisine. ");
        prompt.append("Provide detailed nutritional and ingredient information for the following dish:\n\n");
        
        prompt.append("FOOD TO ANALYZE:\n");
        prompt.append("Name: ").append(food.getFoodName()).append("\n");
        prompt.append("Calories: ").append(food.getCalories()).append(" kcal\n");
        prompt.append("Protein: ").append(food.getProtein()).append("g\n");
        prompt.append("Fat: ").append(food.getFat()).append("g\n");
        prompt.append("Carbs: ").append(food.getCarbs()).append("g\n");
        prompt.append("Description: ").append(food.getDescription()).append("\n\n");
        
        prompt.append("REQUIREMENTS:\n");
        prompt.append("1. Provide COMPLETE nutritional breakdown for 1 serving\n");
        prompt.append("2. List ALL main ingredients with amounts\n");
        prompt.append("3. Include cooking method and preparation time\n");
        prompt.append("4. Identify allergens and dietary tags\n");
        prompt.append("5. Mention health benefits\n");
        prompt.append("6. Provide storage and reheating instructions\n");
        prompt.append("7. Use accurate, realistic data\n");
        prompt.append("8. Focus on Filipino/Asian cooking methods\n\n");
        
        prompt.append("NUTRITIONAL DATA REQUIREMENTS:\n");
        prompt.append("- All values should be for 1 serving\n");
        prompt.append("- Use realistic serving sizes (1 cup, 1 plate, 1 bowl, etc.)\n");
        prompt.append("- Include macronutrients: calories, protein, fat, carbs, fiber, sugar\n");
        prompt.append("- Include micronutrients: vitamins A, C, D, E, K, B-complex\n");
        prompt.append("- Include minerals: calcium, iron, magnesium, phosphorus, potassium, zinc\n");
        prompt.append("- Include fat breakdown: saturated, monounsaturated, polyunsaturated, omega-3, omega-6\n");
        prompt.append("- Include sodium and cholesterol levels\n\n");
        
        prompt.append("INGREDIENT REQUIREMENTS:\n");
        prompt.append("- List main ingredients with specific amounts\n");
        prompt.append("- Include preparation method (chopped, diced, minced, etc.)\n");
        prompt.append("- Mark optional ingredients\n");
        prompt.append("- Use common Filipino/Asian ingredients\n");
        prompt.append("- Include seasonings and spices\n\n");
        
        prompt.append("COOKING INFORMATION:\n");
        prompt.append("- Specify cooking method (adobo, sinigang, stir-fry, grill, etc.)\n");
        prompt.append("- Provide prep time and cook time in minutes\n");
        prompt.append("- Indicate difficulty level (Easy, Medium, Hard)\n");
        prompt.append("- Specify number of servings\n\n");
        
        prompt.append("ALLERGEN AND DIETARY INFORMATION:\n");
        prompt.append("- List common allergens (gluten, dairy, nuts, soy, etc.)\n");
        prompt.append("- Identify dietary tags (vegetarian, vegan, gluten-free, etc.)\n");
        prompt.append("- Mention if suitable for specific diets\n\n");
        
        prompt.append("Return ONLY valid JSON with this exact structure:\n");
        prompt.append("{\n");
        prompt.append("  \"food_name\": \"[DISH NAME]\",\n");
        prompt.append("  \"description\": \"[DETAILED DESCRIPTION]\",\n");
        prompt.append("  \"serving_size\": \"[SERVING SIZE]\",\n");
        prompt.append("  \"cooking_method\": \"[COOKING METHOD]\",\n");
        prompt.append("  \"cuisine\": \"[CUISINE TYPE]\",\n");
        prompt.append("  \"difficulty\": \"[EASY/MEDIUM/HARD]\",\n");
        prompt.append("  \"prep_time\": [MINUTES],\n");
        prompt.append("  \"cook_time\": [MINUTES],\n");
        prompt.append("  \"total_time\": [MINUTES],\n");
        prompt.append("  \"servings\": [NUMBER],\n");
        prompt.append("  \"nutrition\": {\n");
        prompt.append("    \"calories\": [NUMBER],\n");
        prompt.append("    \"protein\": [NUMBER],\n");
        prompt.append("    \"fat\": [NUMBER],\n");
        prompt.append("    \"carbs\": [NUMBER],\n");
        prompt.append("    \"fiber\": [NUMBER],\n");
        prompt.append("    \"sugar\": [NUMBER],\n");
        prompt.append("    \"sodium\": [NUMBER],\n");
        prompt.append("    \"cholesterol\": [NUMBER],\n");
        prompt.append("    \"saturated_fat\": [NUMBER],\n");
        prompt.append("    \"trans_fat\": [NUMBER],\n");
        prompt.append("    \"monounsaturated_fat\": [NUMBER],\n");
        prompt.append("    \"polyunsaturated_fat\": [NUMBER],\n");
        prompt.append("    \"omega3\": [NUMBER],\n");
        prompt.append("    \"omega6\": [NUMBER],\n");
        prompt.append("    \"vitamin_a\": [NUMBER],\n");
        prompt.append("    \"vitamin_c\": [NUMBER],\n");
        prompt.append("    \"vitamin_d\": [NUMBER],\n");
        prompt.append("    \"vitamin_e\": [NUMBER],\n");
        prompt.append("    \"vitamin_k\": [NUMBER],\n");
        prompt.append("    \"thiamine\": [NUMBER],\n");
        prompt.append("    \"riboflavin\": [NUMBER],\n");
        prompt.append("    \"niacin\": [NUMBER],\n");
        prompt.append("    \"vitamin_b6\": [NUMBER],\n");
        prompt.append("    \"folate\": [NUMBER],\n");
        prompt.append("    \"vitamin_b12\": [NUMBER],\n");
        prompt.append("    \"biotin\": [NUMBER],\n");
        prompt.append("    \"pantothenic_acid\": [NUMBER],\n");
        prompt.append("    \"calcium\": [NUMBER],\n");
        prompt.append("    \"iron\": [NUMBER],\n");
        prompt.append("    \"magnesium\": [NUMBER],\n");
        prompt.append("    \"phosphorus\": [NUMBER],\n");
        prompt.append("    \"potassium\": [NUMBER],\n");
        prompt.append("    \"zinc\": [NUMBER],\n");
        prompt.append("    \"copper\": [NUMBER],\n");
        prompt.append("    \"manganese\": [NUMBER],\n");
        prompt.append("    \"selenium\": [NUMBER],\n");
        prompt.append("    \"iodine\": [NUMBER]\n");
        prompt.append("  },\n");
        prompt.append("  \"ingredients\": [\n");
        prompt.append("    {\n");
        prompt.append("      \"name\": \"[INGREDIENT NAME]\",\n");
        prompt.append("      \"amount\": \"[AMOUNT]\",\n");
        prompt.append("      \"unit\": \"[UNIT]\",\n");
        prompt.append("      \"preparation\": \"[PREPARATION METHOD]\",\n");
        prompt.append("      \"notes\": \"[NOTES]\",\n");
        prompt.append("      \"is_optional\": [true/false]\n");
        prompt.append("    }\n");
        prompt.append("  ],\n");
        prompt.append("  \"allergens\": [\"[ALLERGEN1]\", \"[ALLERGEN2]\"],\n");
        prompt.append("  \"dietary_tags\": [\"[TAG1]\", \"[TAG2]\"],\n");
        prompt.append("  \"health_benefits\": [\"[BENEFIT1]\", \"[BENEFIT2]\"],\n");
        prompt.append("  \"storage_instructions\": \"[STORAGE INSTRUCTIONS]\",\n");
        prompt.append("  \"reheating_instructions\": \"[REHEATING INSTRUCTIONS]\"\n");
        prompt.append("}");
        
        return prompt.toString();
    }
    
    private FoodDetails callGeminiForFoodDetails(String prompt, FoodRecommendation food) {
        try {
            // Create JSON request
            JSONObject requestBody = new JSONObject();
            JSONArray contents = new JSONArray();
            JSONObject content = new JSONObject();
            JSONArray parts = new JSONArray();
            JSONObject part = new JSONObject();
            part.put("text", prompt);
            parts.put(part);
            content.put("parts", parts);
            contents.put(content);
            requestBody.put("contents", contents);
            
            // Make API call
            OkHttpClient client = new OkHttpClient();
            RequestBody body = RequestBody.create(
                requestBody.toString(), 
                okhttp3.MediaType.parse("application/json")
            );
            
            Request request = new Request.Builder()
                .url(GEMINI_TEXT_API_URL)
                .post(body)
                .build();
                
            try (Response response = client.newCall(request).execute()) {
                if (response.isSuccessful() && response.body() != null) {
                    String responseText = response.body().string();
                    Log.d(TAG, "Gemini food details response: " + responseText);
                    
                    return parseFoodDetailsResponse(responseText, food);
                }
            }
        } catch (Exception e) {
            Log.e(TAG, "Error calling Gemini for food details: " + e.getMessage());
        }
        
        return null;
    }
    
    private FoodDetails parseFoodDetailsResponse(String responseText, FoodRecommendation food) {
        try {
            // Parse the Gemini response structure
            JSONObject geminiResponse = new JSONObject(responseText);
            JSONArray candidates = geminiResponse.getJSONArray("candidates");
            
            if (candidates.length() > 0) {
                JSONObject candidate = candidates.getJSONObject(0);
                JSONObject content = candidate.getJSONObject("content");
                JSONArray parts = content.getJSONArray("parts");
                
                for (int i = 0; i < parts.length(); i++) {
                    JSONObject part = parts.getJSONObject(i);
                    if (part.has("text")) {
                        String textContent = part.getString("text");
                        Log.d(TAG, "Extracted food details text: " + textContent);
                        
                        // Extract JSON object from the text content
                        int objectStart = textContent.indexOf("{");
                        int objectEnd = textContent.lastIndexOf("}") + 1;
                        
                        if (objectStart >= 0 && objectEnd > objectStart) {
                            String jsonObjectString = textContent.substring(objectStart, objectEnd);
                            Log.d(TAG, "Extracted food details JSON: " + jsonObjectString);
                            
                            return parseFoodDetailsFromJson(jsonObjectString, food);
                        }
                    }
                }
            }
        } catch (JSONException e) {
            Log.e(TAG, "Error parsing food details JSON: " + e.getMessage());
        }
        
        return null;
    }
    
    private FoodDetails parseFoodDetailsFromJson(String jsonString, FoodRecommendation food) {
        try {
            JSONObject foodDetailsJson = new JSONObject(jsonString);
            FoodDetails foodDetails = new FoodDetails();
            
            // Basic information
            foodDetails.setFoodName(foodDetailsJson.optString("food_name", food.getFoodName()));
            foodDetails.setDescription(foodDetailsJson.optString("description", food.getDescription()));
            foodDetails.setServingSize(foodDetailsJson.optString("serving_size", "1 serving"));
            foodDetails.setCookingMethod(foodDetailsJson.optString("cooking_method", "Not specified"));
            foodDetails.setCuisine(foodDetailsJson.optString("cuisine", "Filipino"));
            foodDetails.setDifficulty(foodDetailsJson.optString("difficulty", "Medium"));
            foodDetails.setPrepTime(foodDetailsJson.optInt("prep_time", 15));
            foodDetails.setCookTime(foodDetailsJson.optInt("cook_time", 30));
            foodDetails.setTotalTime(foodDetailsJson.optInt("total_time", 45));
            foodDetails.setServings(foodDetailsJson.optInt("servings", 4));
            
            // Parse nutrition information
            JSONObject nutrition = foodDetailsJson.optJSONObject("nutrition");
            if (nutrition != null) {
                foodDetails.setCalories(nutrition.optInt("calories", food.getCalories()));
                foodDetails.setProtein(nutrition.optDouble("protein", food.getProtein()));
                foodDetails.setFat(nutrition.optDouble("fat", food.getFat()));
                foodDetails.setCarbs(nutrition.optDouble("carbs", food.getCarbs()));
                foodDetails.setFiber(nutrition.optDouble("fiber", 0.0));
                foodDetails.setSugar(nutrition.optDouble("sugar", 0.0));
                foodDetails.setSodium(nutrition.optDouble("sodium", 0.0));
                foodDetails.setCholesterol(nutrition.optDouble("cholesterol", 0.0));
                foodDetails.setSaturatedFat(nutrition.optDouble("saturated_fat", 0.0));
                foodDetails.setTransFat(nutrition.optDouble("trans_fat", 0.0));
                foodDetails.setMonounsaturatedFat(nutrition.optDouble("monounsaturated_fat", 0.0));
                foodDetails.setPolyunsaturatedFat(nutrition.optDouble("polyunsaturated_fat", 0.0));
                foodDetails.setOmega3(nutrition.optDouble("omega3", 0.0));
                foodDetails.setOmega6(nutrition.optDouble("omega6", 0.0));
                
                // Vitamins
                foodDetails.setVitaminA(nutrition.optDouble("vitamin_a", 0.0));
                foodDetails.setVitaminC(nutrition.optDouble("vitamin_c", 0.0));
                foodDetails.setVitaminD(nutrition.optDouble("vitamin_d", 0.0));
                foodDetails.setVitaminE(nutrition.optDouble("vitamin_e", 0.0));
                foodDetails.setVitaminK(nutrition.optDouble("vitamin_k", 0.0));
                foodDetails.setThiamine(nutrition.optDouble("thiamine", 0.0));
                foodDetails.setRiboflavin(nutrition.optDouble("riboflavin", 0.0));
                foodDetails.setNiacin(nutrition.optDouble("niacin", 0.0));
                foodDetails.setVitaminB6(nutrition.optDouble("vitamin_b6", 0.0));
                foodDetails.setFolate(nutrition.optDouble("folate", 0.0));
                foodDetails.setVitaminB12(nutrition.optDouble("vitamin_b12", 0.0));
                foodDetails.setBiotin(nutrition.optDouble("biotin", 0.0));
                foodDetails.setPantothenicAcid(nutrition.optDouble("pantothenic_acid", 0.0));
                
                // Minerals
                foodDetails.setCalcium(nutrition.optDouble("calcium", 0.0));
                foodDetails.setIron(nutrition.optDouble("iron", 0.0));
                foodDetails.setMagnesium(nutrition.optDouble("magnesium", 0.0));
                foodDetails.setPhosphorus(nutrition.optDouble("phosphorus", 0.0));
                foodDetails.setPotassium(nutrition.optDouble("potassium", 0.0));
                foodDetails.setZinc(nutrition.optDouble("zinc", 0.0));
                foodDetails.setCopper(nutrition.optDouble("copper", 0.0));
                foodDetails.setManganese(nutrition.optDouble("manganese", 0.0));
                foodDetails.setSelenium(nutrition.optDouble("selenium", 0.0));
                foodDetails.setIodine(nutrition.optDouble("iodine", 0.0));
            }
            
            // Parse ingredients
            JSONArray ingredients = foodDetailsJson.optJSONArray("ingredients");
            if (ingredients != null) {
                List<FoodDetails.Ingredient> ingredientList = new ArrayList<>();
                for (int i = 0; i < ingredients.length(); i++) {
                    JSONObject ingredientJson = ingredients.getJSONObject(i);
                    FoodDetails.Ingredient ingredient = new FoodDetails.Ingredient();
                    ingredient.setName(ingredientJson.optString("name", ""));
                    ingredient.setAmount(ingredientJson.optString("amount", ""));
                    ingredient.setUnit(ingredientJson.optString("unit", ""));
                    ingredient.setPreparation(ingredientJson.optString("preparation", ""));
                    ingredient.setNotes(ingredientJson.optString("notes", ""));
                    ingredient.setOptional(ingredientJson.optBoolean("is_optional", false));
                    ingredientList.add(ingredient);
                }
                foodDetails.setIngredients(ingredientList);
            }
            
            // Parse allergens
            JSONArray allergens = foodDetailsJson.optJSONArray("allergens");
            if (allergens != null) {
                List<String> allergenList = new ArrayList<>();
                for (int i = 0; i < allergens.length(); i++) {
                    allergenList.add(allergens.getString(i));
                }
                foodDetails.setAllergens(allergenList);
            }
            
            // Parse dietary tags
            JSONArray dietaryTags = foodDetailsJson.optJSONArray("dietary_tags");
            if (dietaryTags != null) {
                List<String> tagList = new ArrayList<>();
                for (int i = 0; i < dietaryTags.length(); i++) {
                    tagList.add(dietaryTags.getString(i));
                }
                foodDetails.setDietaryTags(tagList);
            }
            
            // Parse health benefits
            JSONArray healthBenefits = foodDetailsJson.optJSONArray("health_benefits");
            if (healthBenefits != null) {
                List<String> benefitList = new ArrayList<>();
                for (int i = 0; i < healthBenefits.length(); i++) {
                    benefitList.add(healthBenefits.getString(i));
                }
                foodDetails.setHealthBenefits(benefitList);
            }
            
            // Parse storage and reheating instructions
            foodDetails.setStorageInstructions(foodDetailsJson.optString("storage_instructions", "Store in refrigerator for up to 3 days"));
            foodDetails.setReheatingInstructions(foodDetailsJson.optString("reheating_instructions", "Reheat in microwave or on stovetop until hot"));
            
            Log.d(TAG, "Successfully parsed food details for: " + foodDetails.getFoodName());
            return foodDetails;
            
        } catch (JSONException e) {
            Log.e(TAG, "Error parsing food details from JSON: " + e.getMessage());
        }
        
        return null;
    }
    
    private FoodDetails getFallbackFoodDetails(FoodRecommendation food) {
        FoodDetails fallbackDetails = new FoodDetails();
        
        // Set basic information
        fallbackDetails.setFoodName(food.getFoodName());
        fallbackDetails.setDescription(food.getDescription());
        fallbackDetails.setServingSize("1 serving");
        fallbackDetails.setCookingMethod("Traditional Filipino");
        fallbackDetails.setCuisine("Filipino");
        fallbackDetails.setDifficulty("Medium");
        fallbackDetails.setPrepTime(15);
        fallbackDetails.setCookTime(30);
        fallbackDetails.setTotalTime(45);
        fallbackDetails.setServings(4);
        
        // Set basic nutrition
        fallbackDetails.setCalories(food.getCalories());
        fallbackDetails.setProtein(food.getProtein());
        fallbackDetails.setFat(food.getFat());
        fallbackDetails.setCarbs(food.getCarbs());
        
        // Set basic ingredients
        List<FoodDetails.Ingredient> ingredients = new ArrayList<>();
        ingredients.add(new FoodDetails.Ingredient("Main protein", "200g", "grams"));
        ingredients.add(new FoodDetails.Ingredient("Vegetables", "1 cup", "chopped"));
        ingredients.add(new FoodDetails.Ingredient("Seasonings", "To taste", ""));
        fallbackDetails.setIngredients(ingredients);
        
        // Set basic dietary tags
        List<String> dietaryTags = new ArrayList<>();
        dietaryTags.add("Traditional Filipino");
        fallbackDetails.setDietaryTags(dietaryTags);
        
        // Set basic health benefits
        List<String> healthBenefits = new ArrayList<>();
        healthBenefits.add("Good source of protein");
        healthBenefits.add("Contains essential vitamins and minerals");
        fallbackDetails.setHealthBenefits(healthBenefits);
        
        fallbackDetails.setStorageInstructions("Store in refrigerator for up to 3 days");
        fallbackDetails.setReheatingInstructions("Reheat in microwave or on stovetop until hot");
        
        Log.d(TAG, "Created fallback food details for: " + food.getFoodName());
        return fallbackDetails;
    }
    
    public void shutdown() {
        if (executorService != null) {
            executorService.shutdown();
        }
    }
}
