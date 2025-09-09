const https = require('https');

// Test the Gemini API directly to see if it's working
const apiKey = 'AIzaSyAR0YOJALZphmQaSbc5Ydzs5kZS6eCefJM';
const url = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=${apiKey}`;

const testPrompt = `You are a PROFESSIONAL NUTRITIONIST. Generate EXACTLY 2 food dishes for malnutrition recovery:

USER PROFILE:
Age: 25
Sex: Female
BMI: 18.5
Health: Underweight
Budget: Low
Allergies: None
Diet: None
Pregnancy: No

Return ONLY valid JSON:
{"traditional":[{"food_name":"Chicken Adobo","calories":350,"protein_g":25,"fat_g":15,"carbs_g":20,"serving_size":"1 serving","diet_type":"Traditional Filipino","description":"Classic Filipino dish with tender chicken in savory sauce"}]}`;

const data = JSON.stringify({
    generationConfig: {
        maxOutputTokens: 1000,
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
            text: testPrompt
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

console.log('Testing Gemini API with optimized settings...');
console.log('URL:', url);
console.log('Prompt length:', testPrompt.length);

const req = https.request(url, options, (res) => {
    console.log(`\nResponse Status: ${res.statusCode}`);
    console.log('Response Headers:', res.headers);
    
    let responseData = '';
    res.on('data', (chunk) => {
        responseData += chunk;
    });
    
    res.on('end', () => {
        console.log('Response Text:', responseData);
        
        try {
            const jsonResponse = JSON.parse(responseData);
            console.log('JSON Response:', JSON.stringify(jsonResponse, null, 2));
            
            if (jsonResponse.candidates && jsonResponse.candidates.length > 0) {
                const content = jsonResponse.candidates[0].content;
                if (content && content.parts && content.parts.length > 0) {
                    const text = content.parts[0].text;
                    console.log('\nGenerated text:', text);
                }
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
