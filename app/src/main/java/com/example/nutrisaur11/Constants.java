package com.example.nutrisaur11;

public class Constants {
    // Production server URL - Railway deployment (no more Cloudflare blocking!)
    public static final String API_BASE_URL = "https://nutrisaur-production.up.railway.app/";
    
    // API Keys (kept for compatibility, but not needed for Railway)
    public static final String API_KEY = "nutrisaur2024";
    public static final String BACKUP_API_KEY = "mobile_app_key";
    
    // Use the unified API endpoint (Railway)
    public static final String UNIFIED_API_URL = API_BASE_URL + "unified_api.php";
    
    // Backup API endpoint (Railway)
    public static final String BACKUP_API_URL = API_BASE_URL + "unified_api.php";
    
    // User agent for mobile app identification
    public static final String USER_AGENT = "NutrisaurApp/2.0 (Android; Railway)";
    
    // IMPORTANT: Railway deployment - no more Cloudflare blocking issues!
    // All API endpoints are now accessible without bypassing
} 