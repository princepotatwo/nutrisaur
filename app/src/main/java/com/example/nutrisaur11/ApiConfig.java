package com.example.nutrisaur11;

public class ApiConfig {
    // Gemini API Configuration
    public static final String GEMINI_API_KEY = "AIzaSyAR0YOJALZphmQaSbc5Ydzs5kZS6eCefJM";
    public static final String GEMINI_TEXT_API_URL = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" + GEMINI_API_KEY;
    
    // Groq API Configuration (disabled for now)
    public static final String GROQ_API_KEY = "YOUR_GROQ_API_KEY_HERE";
    public static final String GROQ_API_URL = "https://api.groq.com/openai/v1/chat/completions";
    public static boolean GROQ_ENABLED = false; // Set to false if no Groq API key available
    
    // Database API Configuration
    public static final String DATABASE_API_URL = "https://nutrisaur-production.up.railway.app/api/DatabaseAPI.php";
    
    // Image API Configuration
    public static final String UNSPLASH_ACCESS_KEY = "YOUR_UNSPLASH_ACCESS_KEY";
    public static final String PEXELS_API_KEY = "YOUR_PEXELS_API_KEY";
    public static final String SPOONACULAR_API_KEY = "YOUR_SPOONACULAR_API_KEY";
    
    // Rate limiting
    public static final int MAX_REQUESTS_PER_MINUTE = 60;
    public static final int REQUEST_TIMEOUT_MS = 30000;
    
    // Cache settings
    public static final int CACHE_DURATION_HOURS = 24;
    public static final int MAX_CACHE_SIZE_MB = 100;
    
    // Gemini API specific settings
    public static final int MAX_RETRY_ATTEMPTS = 3;
    public static final int MAX_PROMPT_LENGTH = 10000;
    public static final int MAX_TOKENS = 4096;
    public static final int INITIAL_RETRY_DELAY_MS = 1000;
    public static final int MAX_RETRY_DELAY_MS = 10000;
    public static final int CONNECT_TIMEOUT = 30;
    public static final int READ_TIMEOUT = 60;
    public static final int WRITE_TIMEOUT = 60;
    public static final String GEMINI_IMAGE_API_URL = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" + GEMINI_API_KEY;
}
