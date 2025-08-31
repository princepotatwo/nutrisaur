import json
import sys

try:
    with open('jwt_response.json', 'r') as f:
        data = json.load(f)
    
    response_preview = data['oauth_exchange']['response_preview']
    if '"access_token":"' in response_preview:
        access_token = response_preview.split('"access_token":"')[1].split('",')[0]
        print(f"Access Token: {access_token[:50]}...")
        
        # Test FCM endpoint manually
        import requests
        
        project_id = "nutrisaur-ebf29"
        fcm_url = f"https://fcm.googleapis.com/v1/projects/{project_id}/messages:send"
        
        headers = {
            'Authorization': f'Bearer {access_token}',
            'Content-Type': 'application/json'
        }
        
        # Test payload
        test_payload = {
            "message": {
                "token": "test_token",
                "notification": {
                    "title": "Test",
                    "body": "Test message"
                }
            }
        }
        
        print(f"Testing FCM endpoint: {fcm_url}")
        response = requests.post(fcm_url, headers=headers, json=test_payload)
        
        print(f"HTTP Status: {response.status_code}")
        print(f"Response: {response.text[:200]}")
        
    else:
        print("No access token found in response")
        
except Exception as e:
    print(f"Error: {e}")
