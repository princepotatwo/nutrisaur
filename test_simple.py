#!/usr/bin/env python3
import requests
import json

# Test the API endpoint
url = 'https://nutrisaur-production.up.railway.app/api/DatabaseAPI.php?action=register_community_user'

data = {
    'name': 'Test User',
    'email': 'test@example.com',
    'password': 'testpassword123',
    'municipality': 'Test Municipality',
    'barangay': 'Test Barangay',
    'sex': 'Male',
    'birthday': '1990-01-01',
    'is_pregnant': 'No',
    'weight': '70',
    'height': '175'
}

headers = {
    'Content-Type': 'application/json'
}

try:
    print("Testing API endpoint...")
    print(f"URL: {url}")
    print(f"Data: {json.dumps(data, indent=2)}")
    
    response = requests.post(url, json=data, headers=headers, timeout=30)
    
    print(f"\nResponse Status: {response.status_code}")
    print(f"Response Headers: {dict(response.headers)}")
    print(f"Response Text: {response.text}")
    
    if response.status_code == 200:
        try:
            json_response = response.json()
            print(f"JSON Response: {json.dumps(json_response, indent=2)}")
        except:
            print("Response is not valid JSON")
    else:
        print(f"Error: HTTP {response.status_code}")
        
except Exception as e:
    print(f"Error: {e}")
