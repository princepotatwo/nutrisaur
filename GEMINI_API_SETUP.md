# Gemini API Setup Instructions

## How to Get Your Gemini API Key

1. Go to [Google AI Studio](https://ai.google.dev/)
2. Sign in with your Google account
3. Click on "Get API Key" or "Create API Key"
4. Create a new project or select an existing one
5. Copy the generated API key

## How to Update the API Key in Your App

1. Open `app/src/main/java/com/example/nutrisaur11/GeminiService.java`
2. Find line 19: `private static final String API_KEY = "YOUR_GEMINI_API_KEY_HERE";`
3. Replace `YOUR_GEMINI_API_KEY_HERE` with your actual API key from Google AI Studio
4. Save the file and rebuild your app

## Testing

Once you've added the API key:
- The app will now use Gemini AI for personalized food recommendations
- No fallback data will be shown - you'll see either Gemini results or empty results
- Check the logs for "Gemini API Response" to see if the API is working

## Example API Key Format
Your API key should look like: `AIzaSyB...` (starts with "AIzaSy")

## Troubleshooting
- If you see "API key not valid" error, double-check that you copied the key correctly
- Make sure there are no extra spaces or characters
- Ensure your Google account has access to the Gemini API
