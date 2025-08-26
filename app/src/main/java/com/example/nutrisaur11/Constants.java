package com.example.nutrisaur11;

public class Constants {
    // Production server URL - Your working InfinityFree domain
    public static final String API_BASE_URL = "http://nutrisaur.gt.tc/";
    
    // API Keys for bypassing Cloudflare
    public static final String API_KEY = "nutrisaur2024";
    public static final String BACKUP_API_KEY = "mobile_app_key";
    
    // Use the main API endpoint
    public static final String UNIFIED_API_URL = API_BASE_URL + "api.php";
    
    // Backup API endpoint
    public static final String BACKUP_API_URL = API_BASE_URL + "unified_api.php";
    
    // Enhanced headers to bypass Cloudflare
    public static final String USER_AGENT = "NutrisaurApp/2.0 (Android; API-Key: " + API_KEY + ")";
    
    // IMPORTANT: This approach uses CORS headers + API keys to bypass Cloudflare
    // Your PHP files now have enhanced CORS and API key protection
} 