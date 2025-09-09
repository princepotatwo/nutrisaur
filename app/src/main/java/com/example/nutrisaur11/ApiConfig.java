package com.example.nutrisaur11;

/**
 * API Configuration class
 * Centralized configuration for all API endpoints and keys
 */
public class ApiConfig {
    
    // Gemini API Configuration
    // To get a Gemini API key:
    // 1. Visit https://makersuite.google.com/app/apikey
    // 2. Create an account or sign in
    // 3. Generate a new API key
    // 4. Replace the placeholder below with your actual API key
    public static final String GEMINI_API_KEY = "YOUR_GEMINI_API_KEY_HERE";
    public static final String GEMINI_TEXT_API_URL = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" + GEMINI_API_KEY;
    public static final String GEMINI_IMAGE_API_URL = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent?key=" + GEMINI_API_KEY;
    
    // Groq API Configuration
    // To get a Groq API key:
    // 1. Visit https://console.groq.com/
    // 2. Create an account or sign in
    // 3. Navigate to API Keys section
    // 4. Generate a new API key
    // 5. Replace the placeholder below with your actual API key
    public static final String GROQ_API_KEY = "YOUR_GROQ_API_KEY_HERE";
    public static final String GROQ_API_URL = "https://api.groq.com/openai/v1/chat/completions";
    public static final String GROQ_MODEL = "llama-3.1-8b-instant";
    
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
    public static boolean GROQ_ENABLED = true; // Set to false if no Groq API key available
    
    /**
     * Check if Groq API is properly configured
     */
    public static boolean isGroqConfigured() {
        return GROQ_ENABLED && GROQ_API_KEY != null && 
               !GROQ_API_KEY.isEmpty() && 
               !GROQ_API_KEY.equals("gsk_1234567890abcdef");
    }
    
    /**
     * Get the primary API to use (Gemini first, then Groq if available)
     */
    public static String getPrimaryApi() {
        if (GEMINI_ENABLED) {
            return "gemini";
        } else if (isGroqConfigured()) {
            return "groq";
        } else {
            return "fallback";
        }
    }
}
