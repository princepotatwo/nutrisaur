# NutriSaur Mobile App API Documentation

## Base URL
```
https://nutrisaur-production.up.railway.app/unified_api.php
```

## Overview
This API handles mobile app signup and profile updates. The mobile app uses a single endpoint with different actions to manage user data.

## Endpoint

### Mobile App Integration
**Endpoint:** `?endpoint=mobile_signup`  
**Method:** POST  
**Content-Type:** application/json

## Actions

### 1. Save Screening Data (User Registration/Profile Update)
**Action:** `save_screening`

**Request Body:**
```json
{
    "action": "save_screening",
    "email": "user@example.com",
    "username": "john_doe",
    "screening_data": "{\"gender\":\"boy\",\"weight\":70,\"height\":175,\"bmi\":23.0,\"barangay\":\"Sample Barangay\",\"income\":\"PHP 20,001–40,000/month (Middle)\",\"swelling\":\"no\",\"weight_loss\":\"<5% or none\",\"feeding_behavior\":\"good appetite\",\"physical_signs\":\"[\"thin\",\"shorter\"]\",\"dietary_diversity\":\"6\",\"has_recent_illness\":false,\"has_eating_difficulty\":false,\"has_food_insecurity\":false,\"has_micronutrient_deficiency\":false,\"has_functional_decline\":false}",
    "risk_score": 25
}
```

**Response:**
```json
{
    "success": true,
    "message": "Screening data saved successfully",
    "user_email": "user@example.com",
    "risk_score": 25
}
```

### 2. Get Screening Data (Profile Retrieval)
**Action:** `get_screening_data`

**Request Body:**
```json
{
    "action": "get_screening_data",
    "email": "user@example.com"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "user_email": "user@example.com",
        "risk_score": 25,
        "created_at": "2024-01-01 00:00:00",
        "updated_at": "2024-01-15 00:00:00",
        "gender": "boy",
        "barangay": "Sample Barangay",
        "income": "PHP 20,001–40,000/month (Middle)",
        "weight": 70.0,
        "height": 175.0,
        "bmi": 23.0,
        "age": 25,
        "malnutrition_risk": "Low",
        "swelling": "no",
        "weight_loss": "<5% or none",
        "feeding_behavior": "good appetite",
        "physical_signs": "[\"thin\",\"shorter\"]",
        "dietary_diversity": "6",
        "has_recent_illness": false,
        "has_eating_difficulty": false,
        "has_food_insecurity": false,
        "has_micronutrient_deficiency": false,
        "has_functional_decline": false
    }
}
```

## Data Flow

### When User Signs Up in Mobile App:
1. **Mobile App** → Calls `?endpoint=mobile_signup` with `action: "save_screening"`
2. **Server** → Creates/updates user in `user_preferences` table
3. **Web Dashboard** → Automatically sees new user data
4. **Result** → New user appears in dashboard immediately

### When User Updates Profile in Mobile App:
1. **Mobile App** → Calls `?endpoint=mobile_signup` with `action: "save_screening"`
2. **Server** → Updates existing user record
3. **Web Dashboard** → Automatically sees updated data
4. **Result** → User data updates in real-time on dashboard

### When User Views Profile in Mobile App:
1. **Mobile App** → Calls `?endpoint=mobile_signup` with `action: "get_screening_data"`
2. **Server** → Returns user's complete screening data
3. **Mobile App** → Displays user profile information
4. **Result** → User sees their current profile data

## Screening Data Structure

The `screening_data` field contains a JSON string with the following structure:

```json
{
    "gender": "boy|girl",
    "weight": 70.0,
    "height": 175.0,
    "bmi": 23.0,
    "barangay": "Barangay Name",
    "income": "Income Bracket",
    "swelling": "yes|no",
    "weight_loss": ">10%|5-10%|<5% or none",
    "feeding_behavior": "good appetite|moderate appetite|poor appetite",
    "physical_signs": "[\"thin\",\"shorter\",\"weak\",\"none\"]",
    "dietary_diversity": "0-11",
    "has_recent_illness": true|false,
    "has_eating_difficulty": true|false,
    "has_food_insecurity": true|false,
    "has_micronutrient_deficiency": true|false,
    "has_functional_decline": true|false
}
```

## Error Handling

All endpoints return consistent error responses:

```json
{
    "success": false,
    "message": "Error description"
}
```

**HTTP Status Codes:**
- 200: Success
- 400: Bad Request (missing/invalid data)
- 405: Method Not Allowed (wrong HTTP method)
- 500: Internal Server Error

## Mobile App Integration Steps

1. **User Registration Flow:**
   - Collect user information in app
   - Call `?endpoint=mobile_signup` with `action: "save_screening"`
   - Store user credentials locally
   - Show success/error message

2. **Profile Update Flow:**
   - Allow user to edit profile in app
   - Call `?endpoint=mobile_signup` with `action: "save_screening"`
   - Show success/error message
   - Update local data

3. **Profile Retrieval Flow:**
   - Call `?endpoint=mobile_signup` with `action: "get_screening_data"`
   - Parse response and display user profile
   - Handle missing data gracefully

## Testing

Test the endpoints using tools like Postman or curl:

```bash
# Test save screening data
curl -X POST "https://nutrisaur-production.up.railway.app/unified_api.php?endpoint=mobile_signup" \
  -H "Content-Type: application/json" \
  -d '{"action":"save_screening","email":"test@example.com","username":"testuser","screening_data":"{\"gender\":\"boy\"}","risk_score":25}'

# Test get screening data
curl -X POST "https://nutrisaur-production.up.railway.app/unified_api.php?endpoint=mobile_signup" \
  -H "Content-Type: application/json" \
  -d '{"action":"get_screening_data","email":"test@example.com"}'
```

## Database Schema

The API works with the existing `user_preferences` table structure:

- `user_email` - Primary identifier
- `screening_answers` - JSON string containing all screening data
- `risk_score` - Calculated malnutrition risk score
- `created_at` - User creation timestamp
- `updated_at` - Last update timestamp

## Security Notes

- All database queries use prepared statements
- Input validation is performed on all endpoints
- Email addresses are used as unique identifiers
- JSON data is validated before storage

## Support

For API support or questions, contact the development team or check the web dashboard for any system updates.

## Integration with Web Dashboard

The mobile app data automatically syncs with the web dashboard:

1. **Real-time Updates**: Changes made in mobile app appear immediately in web dashboard
2. **Data Consistency**: Both platforms use the same database
3. **Risk Score Calculation**: Risk scores calculated in mobile app are displayed in web dashboard
4. **User Management**: All users created/updated via mobile app are visible in web dashboard
