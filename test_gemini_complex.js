const https = require('https');

// Test with a more complex prompt similar to what the app sends
const apiKey = 'AIzaSyAR0YOJALZphmQaSbc5Ydzs5kZS6eCefJM';
const url = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=${apiKey}`;

const complexPrompt = `You are a PROFESSIONAL NUTRITIONIST and EXPERT CHEF specializing in Filipino and international cuisine. 
Your task is to analyze this person's nutritional screening data and provide personalized food recommendations.

FIRST: ACT AS A NUTRITIONIST - Analyze and classify this person's nutritional needs based on their screening answers:
SCREENING DATA: 
NUTRITIONAL SCREENING RESPONSES:
1. Location: CITY OF BALANGA, Central
2. Sex: Female
3. Age: 28 years old (born 1995-03-15)
4. Pregnancy Status: No
5. Physical Measurements:
   - Weight: 45 kg
   - Height: 155 cm
   - BMI: 18.7 (Underweight - nutritional intervention needed)
6. Health Assessment:
   - Physical Signs: Thin appearance, weakness
   - Nutritional Risk Score: 8/10 (High Risk - immediate intervention needed)
   - Income Level: Low
   - Food Allergies: None
   - Dietary Preferences: None
   - Foods to Avoid: None

NUTRITIONAL ASSESSMENT REQUIRED:
1. Classify their nutritional risk level (High Risk) based on screening responses
2. Identify specific nutritional deficiencies or concerns
3. Determine their primary nutritional needs (weight gain/weight loss/maintenance/malnutrition recovery)
4. Assess their dietary restrictions and health considerations
5. Evaluate their economic situation and food accessibility

USER PROFILE SUMMARY:
Age: 28 (Adult (18-50 years))
Sex: Female
BMI: 18.7
Location: Central
Health Conditions: Underweight - nutritional intervention needed
Budget Level: Low
Allergies: None
Diet Preferences: None
Pregnancy Status: Not Applicable

NOW: ACT AS A CHEF - Generate EXACTLY 8 food dishes for EACH of the 4 categories that address their specific nutritional needs:

CATEGORIES:
1. TRADITIONAL FILIPINO (8 dishes)
2. HEALTHY OPTIONS (8 dishes)  
3. INTERNATIONAL CUISINE (8 dishes)
4. BUDGET-FRIENDLY (8 dishes)

REQUIREMENTS:
- Each dish: food_name, calories (150-800), protein_g (5-40), fat_g (2-30), carbs_g (10-100)
- serving_size: "1 serving", diet_type: category name
- description: 1-2 sentences

Return ONLY valid JSON:
{"traditional":[{"food_name":"Name","calories":300,"protein_g":20,"fat_g":10,"carbs_g":25,"serving_size":"1 serving","diet_type":"Traditional Filipino","description":"Description"},...],"healthy":[...],"international":[...],"budget":[...]}`;

const data = JSON.stringify({
    generationConfig: {
        maxOutputTokens: 4000,
        temperature: 0.7,
        topP: 0.8,
        topK: 40
    },
    safetySettings: [{
        category: "HARM_CATEGORY_HARASSMENT",
        threshold: "BLOCK_MEDIUM_AND_ABOVE"
    }],
    contents: [{
        parts: [{
            text: complexPrompt
        }]
    }]
});

const options = {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Content-Length': data.length,
        'User-Agent': 'NutrisaurApp/1.0'
    }
};

console.log('Testing Gemini API with complex prompt...');
console.log('URL:', url);
console.log('Prompt length:', complexPrompt.length);

const req = https.request(url, options, (res) => {
    console.log(`\nResponse Status: ${res.statusCode}`);
    console.log('Response Headers:', res.headers);
    
    let responseData = '';
    res.on('data', (chunk) => {
        responseData += chunk;
    });
    
    res.on('end', () => {
        console.log('Response Text Length:', responseData.length);
        
        try {
            const jsonResponse = JSON.parse(responseData);
            console.log('JSON Response Keys:', Object.keys(jsonResponse));
            
            if (jsonResponse.candidates && jsonResponse.candidates.length > 0) {
                const content = jsonResponse.candidates[0].content;
                if (content && content.parts && content.parts.length > 0) {
                    const text = content.parts[0].text;
                    console.log('\nGenerated text length:', text.length);
                    console.log('First 200 chars:', text.substring(0, 200));
                    
                    // Check if it contains the expected JSON structure
                    if (text.includes('"traditional"') && text.includes('"healthy"')) {
                        console.log('✅ SUCCESS: Contains expected JSON structure');
                    } else {
                        console.log('❌ WARNING: Missing expected JSON structure');
                    }
                }
            }
            
            if (jsonResponse.usageMetadata) {
                console.log('Token usage:', jsonResponse.usageMetadata.totalTokenCount);
            }
        } catch (e) {
            console.log('Response is not valid JSON');
        }
    });
});

req.on('error', (e) => {
    console.error('Error:', e.message);
});

req.write(data);
req.end();
