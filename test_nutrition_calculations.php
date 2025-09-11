<?php
/**
 * Test script to verify Gemini nutrition calculations for 5 users with different BMI categories
 * This script tests the accuracy of calorie and macronutrient calculations
 */

// Test users with different BMI categories
$testUsers = [
    [
        "name" => "Test User 1 - Underweight",
        "email" => "test1@example.com",
        "sex" => "Female",
        "birthday" => "1995-01-15",
        "age" => 29,
        "weight_kg" => 45.0,
        "height_cm" => 165.0,
        "bmi" => 16.5,
        "bmi_category" => "Underweight"
    ],
    [
        "name" => "Test User 2 - Normal Weight",
        "email" => "test2@example.com",
        "sex" => "Male",
        "birthday" => "1990-06-20",
        "age" => 34,
        "weight_kg" => 70.0,
        "height_cm" => 175.0,
        "bmi" => 22.9,
        "bmi_category" => "Normal weight"
    ],
    [
        "name" => "Test User 3 - Overweight",
        "email" => "test3@example.com",
        "sex" => "Female",
        "birthday" => "1988-03-10",
        "age" => 36,
        "weight_kg" => 75.0,
        "height_cm" => 160.0,
        "bmi" => 29.3,
        "bmi_category" => "Overweight"
    ],
    [
        "name" => "Test User 4 - Obese Class I",
        "email" => "test4@example.com",
        "sex" => "Male",
        "birthday" => "1985-11-25",
        "age" => 39,
        "weight_kg" => 95.0,
        "height_cm" => 170.0,
        "bmi" => 32.9,
        "bmi_category" => "Obese"
    ],
    [
        "name" => "Test User 5 - Severely Obese",
        "email" => "test5@example.com",
        "sex" => "Female",
        "birthday" => "1982-08-12",
        "age" => 42,
        "weight_kg" => 120.0,
        "height_cm" => 155.0,
        "bmi" => 50.0,
        "bmi_category" => "Obese"
    ]
];

// Gemini API configuration
$geminiApiKey = "AIzaSyAkX7Tpnsz-UnslwnmGytbnfc9XozoxtmU";
$geminiApiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent";

/**
 * Calculate BMR using Mifflin-St Jeor Equation
 */
function calculateBMR($weight, $height, $age, $gender) {
    if (strtolower($gender) === "male") {
        return (10 * $weight) + (6.25 * $height) - (5 * $age) + 5;
    } else {
        return (10 * $weight) + (6.25 * $height) - (5 * $age) - 161;
    }
}

/**
 * Calculate expected calories based on BMI category
 */
function calculateExpectedCalories($weight, $height, $age, $gender, $bmi) {
    $bmr = calculateBMR($weight, $height, $age, $gender);
    $activityFactor = 1.55; // Moderately Active
    $tdee = $bmr * $activityFactor;
    
    if ($bmi < 18.5) {
        // Underweight - add calories for weight gain
        return $tdee + 500;
    } elseif ($bmi >= 25 && $bmi < 30) {
        // Overweight - subtract calories for weight loss
        return $tdee - 500;
    } elseif ($bmi >= 30) {
        // Obese - subtract more calories for weight loss
        return max($tdee - 750, 1200); // Minimum 1200 calories
    } else {
        // Normal weight - maintain
        return $tdee;
    }
}

/**
 * Call Gemini API
 */
function callGeminiAPI($prompt, $apiKey, $apiUrl) {
    $data = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt]
                ]
            ]
        ]
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl . "?key=" . $apiKey);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $responseData = json_decode($response, true);
        if (isset($responseData["candidates"][0]["content"]["parts"][0]["text"])) {
            return $responseData["candidates"][0]["content"]["parts"][0]["text"];
        }
    }
    
    return null;
}

/**
 * Create nutrition prompt for Gemini
 */
function createNutritionPrompt($user) {
    $prompt = "You are a professional nutritionist. Analyze this user profile and provide personalized daily nutrition recommendations in JSON format.\n\n";
    
    $prompt .= "USER PROFILE:\n";
    $prompt .= "- Name: " . $user["name"] . "\n";
    $prompt .= "- Age: " . $user["age"] . " years\n";
    $prompt .= "- Gender: " . $user["sex"] . "\n";
    $prompt .= "- Weight: " . $user["weight_kg"] . " kg\n";
    $prompt .= "- Height: " . $user["height_cm"] . " cm\n";
    $prompt .= "- BMI: " . number_format($user["bmi"], 1) . " (" . $user["bmi_category"] . ")\n";
    $prompt .= "- Activity Level: Moderately Active\n";
    $prompt .= "- Health Goals: " . ($user["bmi"] < 18.5 ? "Weight gain" : ($user["bmi"] >= 25 ? "Weight loss" : "Maintain weight")) . "\n";
    
    // Calculate expected values
    $bmr = calculateBMR($user["weight_kg"], $user["height_cm"], $user["age"], $user["sex"]);
    $expectedCalories = calculateExpectedCalories($user["weight_kg"], $user["height_cm"], $user["age"], $user["sex"], $user["bmi"]);
    
    $prompt .= "\nNUTRITIONIST ANALYSIS REQUIRED:\n";
    $prompt .= "1. Calculate BMR using Mifflin-St Jeor Equation:\n";
    $prompt .= "   - " . $user["sex"] . ": BMR = 10 × " . $user["weight_kg"] . "kg + 6.25 × " . $user["height_cm"] . "cm - 5 × " . $user["age"] . " + " . (strtolower($user["sex"]) === "male" ? "5" : "-161") . " = " . number_format($bmr, 0) . " calories/day\n";
    $prompt .= "2. Apply activity factor (Moderately Active = 1.55) = " . number_format($bmr * 1.55, 0) . " calories\n";
    
    if ($user["bmi"] < 18.5) {
        $prompt .= "3. Add 500 calories for weight gain = " . number_format($bmr * 1.55 + 500, 0) . " calories/day\n";
    } elseif ($user["bmi"] >= 25 && $user["bmi"] < 30) {
        $prompt .= "3. Subtract 500 calories for weight loss = " . number_format($bmr * 1.55 - 500, 0) . " calories/day\n";
    } elseif ($user["bmi"] >= 30) {
        $prompt .= "3. Subtract 750 calories for weight loss = " . number_format(max($bmr * 1.55 - 750, 1200), 0) . " calories/day (minimum 1200)\n";
    } else {
        $prompt .= "3. Maintain current intake = " . number_format($bmr * 1.55, 0) . " calories/day\n";
    }
    
    $prompt .= "\nRESPOND IN THIS EXACT JSON FORMAT:\n";
    $prompt .= "{\n";
    $prompt .= "  \"totalCalories\": [EXACT_CALCULATED_VALUE],\n";
    $prompt .= "  \"caloriesLeft\": [SAME_AS_TOTAL_CALORIES],\n";
    $prompt .= "  \"caloriesEaten\": 0,\n";
    $prompt .= "  \"caloriesBurned\": 0,\n";
    $prompt .= "  \"macronutrients\": {\n";
    $prompt .= "    \"carbs\": 0,\n";
    $prompt .= "    \"protein\": 0,\n";
    $prompt .= "    \"fat\": 0,\n";
    $prompt .= "    \"carbsTarget\": [CALCULATE: (totalCalories - proteinCalories - fatCalories) / 4],\n";
    $prompt .= "    \"proteinTarget\": [CALCULATE: " . $user["weight_kg"] . "kg × 2.2g × 4 calories/g],\n";
    $prompt .= "    \"fatTarget\": [CALCULATE: totalCalories × 0.30 / 9 calories/g]\n";
    $prompt .= "  },\n";
    $prompt .= "  \"activity\": {\n";
    $prompt .= "    \"walkingCalories\": 0,\n";
    $prompt .= "    \"activityCalories\": 0,\n";
    $prompt .= "    \"totalBurned\": 0\n";
    $prompt .= "  },\n";
    $prompt .= "  \"mealDistribution\": {\n";
    $prompt .= "    \"breakfastCalories\": [CALCULATE: totalCalories × 0.25],\n";
    $prompt .= "    \"lunchCalories\": [CALCULATE: totalCalories × 0.35],\n";
    $prompt .= "    \"dinnerCalories\": [CALCULATE: totalCalories × 0.30],\n";
    $prompt .= "    \"snacksCalories\": [CALCULATE: totalCalories × 0.10],\n";
    $prompt .= "    \"breakfastEaten\": 0,\n";
    $prompt .= "    \"lunchEaten\": 0,\n";
    $prompt .= "    \"dinnerEaten\": 0,\n";
    $prompt .= "    \"snacksEaten\": 0,\n";
    $prompt .= "    \"breakfastRecommendation\": \"[MEAL_RECOMMENDATION]\",\n";
    $prompt .= "    \"lunchRecommendation\": \"[MEAL_RECOMMENDATION]\",\n";
    $prompt .= "    \"dinnerRecommendation\": \"[MEAL_RECOMMENDATION]\",\n";
    $prompt .= "    \"snacksRecommendation\": \"[SNACK_RECOMMENDATION]\"\n";
    $prompt .= "  },\n";
    $prompt .= "  \"recommendation\": \"[SPECIFIC_NUTRITIONAL_ADVICE]\",\n";
    $prompt .= "  \"healthStatus\": \"[BMI_STATUS_AND_WEIGHT_MANAGEMENT_RECOMMENDATIONS]\",\n";
    $prompt .= "  \"bmi\": " . number_format($user["bmi"], 1) . ",\n";
    $prompt .= "  \"bmiCategory\": \"" . $user["bmi_category"] . "\"\n";
    $prompt .= "}\n\n";
    
    $prompt .= "CRITICAL: Return ONLY raw JSON data. No markdown, no code blocks, no explanations. Just the JSON object.";
    
    return $prompt;
}

/**
 * Parse Gemini response
 */
function parseGeminiResponse($response) {
    // Clean up markdown code blocks if present
    $response = preg_replace("/```json\s*/", "", $response);
    $response = preg_replace("/```\s*$/", "", $response);
    $response = trim($response);
    
    return json_decode($response, true);
}

/**
 * Test a single user
 */
function testUser($user, $geminiApiKey, $geminiApiUrl) {
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "TESTING: " . $user["name"] . "\n";
    echo str_repeat("=", 80) . "\n";
    
    // Calculate expected values
    $expectedBmr = calculateBMR($user["weight_kg"], $user["height_cm"], $user["age"], $user["sex"]);
    $expectedCalories = calculateExpectedCalories($user["weight_kg"], $user["height_cm"], $user["age"], $user["sex"], $user["bmi"]);
    $expectedProtein = $user["weight_kg"] * 2.2; // 2.2g per kg
    $expectedFat = ($expectedCalories * 0.30) / 9; // 30% of calories from fat
    $expectedCarbs = ($expectedCalories - ($expectedProtein * 4) - ($expectedFat * 9)) / 4;
    
    echo "Expected Values:\n";
    echo "- BMI: " . number_format($user["bmi"], 1) . " (" . $user["bmi_category"] . ")\n";
    echo "- BMR: " . number_format($expectedBmr, 0) . " calories/day\n";
    echo "- Total Calories: " . number_format($expectedCalories, 0) . " calories/day\n";
    echo "- Protein Target: " . number_format($expectedProtein, 0) . "g\n";
    echo "- Fat Target: " . number_format($expectedFat, 0) . "g\n";
    echo "- Carbs Target: " . number_format($expectedCarbs, 0) . "g\n";
    
    // Create prompt and call Gemini
    $prompt = createNutritionPrompt($user);
    echo "\nCalling Gemini API...\n";
    
    $response = callGeminiAPI($prompt, $geminiApiKey, $geminiApiUrl);
    
    if ($response === null) {
        echo "❌ ERROR: Failed to get response from Gemini API\n";
        return false;
    }
    
    $data = parseGeminiResponse($response);
    
    if ($data === null) {
        echo "❌ ERROR: Failed to parse Gemini response\n";
        echo "Raw response: " . substr($response, 0, 200) . "...\n";
        return false;
    }
    
    // Analyze results
    echo "\nGemini Results:\n";
    echo "- Total Calories: " . ($data["totalCalories"] ?? "N/A") . "\n";
    echo "- Protein Target: " . ($data["macronutrients"]["proteinTarget"] ?? "N/A") . "g\n";
    echo "- Fat Target: " . ($data["macronutrients"]["fatTarget"] ?? "N/A") . "g\n";
    echo "- Carbs Target: " . ($data["macronutrients"]["carbsTarget"] ?? "N/A") . "g\n";
    echo "- Breakfast: " . ($data["mealDistribution"]["breakfastCalories"] ?? "N/A") . " cal\n";
    echo "- Lunch: " . ($data["mealDistribution"]["lunchCalories"] ?? "N/A") . " cal\n";
    echo "- Dinner: " . ($data["mealDistribution"]["dinnerCalories"] ?? "N/A") . " cal\n";
    echo "- Snacks: " . ($data["mealDistribution"]["snacksCalories"] ?? "N/A") . " cal\n";
    
    // Check accuracy
    $caloriesAccuracy = abs(($data["totalCalories"] ?? 0) - $expectedCalories) / $expectedCalories * 100;
    $proteinAccuracy = abs(($data["macronutrients"]["proteinTarget"] ?? 0) - $expectedProtein) / $expectedProtein * 100;
    $fatAccuracy = abs(($data["macronutrients"]["fatTarget"] ?? 0) - $expectedFat) / $expectedFat * 100;
    $carbsAccuracy = abs(($data["macronutrients"]["carbsTarget"] ?? 0) - $expectedCarbs) / $expectedCarbs * 100;
    
    echo "\nAccuracy Analysis:\n";
    echo "- Calories: " . number_format($caloriesAccuracy, 1) . "% deviation\n";
    echo "- Protein: " . number_format($proteinAccuracy, 1) . "% deviation\n";
    echo "- Fat: " . number_format($fatAccuracy, 1) . "% deviation\n";
    echo "- Carbs: " . number_format($carbsAccuracy, 1) . "% deviation\n";
    
    // Overall assessment
    $overallAccuracy = ($caloriesAccuracy + $proteinAccuracy + $fatAccuracy + $carbsAccuracy) / 4;
    
    if ($overallAccuracy < 10) {
        echo "✅ EXCELLENT: Very accurate calculations\n";
    } elseif ($overallAccuracy < 20) {
        echo "✅ GOOD: Acceptable accuracy\n";
    } elseif ($overallAccuracy < 30) {
        echo "⚠️  FAIR: Some inaccuracies\n";
    } else {
        echo "❌ POOR: Significant inaccuracies\n";
    }
    
    return [
        "user" => $user["name"],
        "bmi_category" => $user["bmi_category"],
        "expected_calories" => $expectedCalories,
        "actual_calories" => $data["totalCalories"] ?? 0,
        "calories_accuracy" => $caloriesAccuracy,
        "protein_accuracy" => $proteinAccuracy,
        "fat_accuracy" => $fatAccuracy,
        "carbs_accuracy" => $carbsAccuracy,
        "overall_accuracy" => $overallAccuracy
    ];
}

// Main test execution
echo "🧪 NUTRITION CALCULATION ACCURACY TEST\n";
echo "Testing Gemini API with 5 users across different BMI categories\n";
echo "Date: " . date("Y-m-d H:i:s") . "\n";

$results = [];

foreach ($testUsers as $user) {
    $result = testUser($user, $geminiApiKey, $geminiApiUrl);
    if ($result) {
        $results[] = $result;
    }
    
    // Add delay between API calls to avoid rate limiting
    sleep(2);
}

// Summary report
echo "\n" . str_repeat("=", 80) . "\n";
echo "SUMMARY REPORT\n";
echo str_repeat("=", 80) . "\n";

if (empty($results)) {
    echo "❌ No successful tests completed\n";
    exit(1);
}

$totalAccuracy = 0;
$categoryAccuracy = [];

foreach ($results as $result) {
    $totalAccuracy += $result["overall_accuracy"];
    
    if (!isset($categoryAccuracy[$result["bmi_category"]])) {
        $categoryAccuracy[$result["bmi_category"]] = [];
    }
    $categoryAccuracy[$result["bmi_category"]][] = $result["overall_accuracy"];
    
    echo sprintf("%-20s | %-15s | %6.1f%% accuracy\n", 
        $result["user"], 
        $result["bmi_category"], 
        $result["overall_accuracy"]
    );
}

$averageAccuracy = $totalAccuracy / count($results);
echo "\nOverall Average Accuracy: " . number_format($averageAccuracy, 1) . "%\n";

echo "\nAccuracy by BMI Category:\n";
foreach ($categoryAccuracy as $category => $accuracies) {
    $avg = array_sum($accuracies) / count($accuracies);
    echo "- " . $category . ": " . number_format($avg, 1) . "%\n";
}

if ($averageAccuracy >= 80) {
    echo "\n✅ OVERALL ASSESSMENT: EXCELLENT - Gemini is calculating nutrition accurately\n";
} elseif ($averageAccuracy >= 70) {
    echo "\n✅ OVERALL ASSESSMENT: GOOD - Gemini calculations are mostly accurate\n";
} elseif ($averageAccuracy >= 60) {
    echo "\n⚠️  OVERALL ASSESSMENT: FAIR - Some improvements needed\n";
} else {
    echo "\n❌ OVERALL ASSESSMENT: POOR - Significant accuracy issues detected\n";
}

echo "\nTest completed at: " . date("Y-m-d H:i:s") . "\n";
?>
