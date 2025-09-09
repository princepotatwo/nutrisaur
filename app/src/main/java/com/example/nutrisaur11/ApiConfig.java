package com.example.nutrisaur11;

/**
 * API Configuration class
 * Centralized configuration for all API endpoints and keys
 */
public class ApiConfig {
    
    // Gemini API Configuration
    public static final String GEMINI_API_KEY = "AIzaSyAR0YOJALZphmQaSbc5Ydzs5kZS6eCefJM";
    public static final String GEMINI_TEXT_API_URL = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" + GEMINI_API_KEY;
    public static final String GEMINI_IMAGE_API_URL = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent?key=" + GEMINI_API_KEY;
    
    // Grok API Configuration (xAI)
    // To get a Grok API key:
    // 1. Visit https://console.x.ai/
    // 2. Create an account or sign in
    // 3. Navigate to API section
    // 4. Generate a new API key
    // 5. Replace the placeholder below with your actual API key
    public static final String GROK_API_KEY = "xai-1234567890abcdef"; // Replace with actual Grok API key
    public static final String GROK_API_URL = "https://api.x.ai/v1/chat/completions";
    public static final String GROK_MODEL = "grok-beta";
    
    // Timeout settings (in seconds)
    public static final int CONNECT_TIMEOUT = 30;
    public static final int READ_TIMEOUT = 120; // Increased to 2 minutes
    public static final int WRITE_TIMEOUT = 30;
    
    // Retry settings with exponential backoff
    public static final int MAX_RETRY_ATTEMPTS = 3;
    public static final long INITIAL_RETRY_DELAY_MS = 2000; // 2 seconds
    public static final long MAX_RETRY_DELAY_MS = 10000; // 10 seconds
    
    // Request optimization
    public static final int MAX_PROMPT_LENGTH = 8000; // Limit prompt length
    public static final int MAX_TOKENS = 4000; // Limit response tokens
    
    // API Status
    public static boolean GEMINI_ENABLED = true;
    public static boolean GROK_ENABLED = true; // Set to false if no Grok API key available
    
    /**
     * Check if Grok API is properly configured
     */
    public static boolean isGrokConfigured() {
        return GROK_ENABLED && GROK_API_KEY != null && 
               !GROK_API_KEY.isEmpty() && 
               !GROK_API_KEY.equals("xai-1234567890abcdef");
    }
    
    /**
     * Get the primary API to use (Gemini first, then Grok if available)
     */
    public static String getPrimaryApi() {
        if (GEMINI_ENABLED) {
            return "gemini";
        } else if (isGrokConfigured()) {
            return "grok";
        } else {
            return "fallback";
        }
    }
}
