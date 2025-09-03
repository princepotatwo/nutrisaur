#!/usr/bin/env python3
"""
Test script that simulates the PHP API functionality
"""

import json
import sys
import os
from urllib.parse import parse_qs, urlparse
from google_images_scraper_advanced import AdvancedGoogleImagesScraper

def simulate_php_api():
    """Simulate the PHP API endpoint"""
    
    print("=== PHP API Simulation ===")
    print("This simulates what the PHP API would do")
    print()
    
    # Test cases
    test_cases = [
        {
            'method': 'GET',
            'query': 'sinigang na baboy',
            'max_results': 3
        },
        {
            'method': 'POST',
            'query': 'adobo',
            'max_results': 2
        },
        {
            'method': 'GET',
            'query': 'tinola',
            'max_results': 1
        }
    ]
    
    scraper = AdvancedGoogleImagesScraper()
    
    for i, test_case in enumerate(test_cases, 1):
        print(f"Test Case {i}: {test_case['method']} request")
        print(f"Query: '{test_case['query']}'")
        print(f"Max Results: {test_case['max_results']}")
        print("-" * 50)
        
        try:
            # Simulate the PHP API logic
            query = test_case['query']
            max_results = test_case['max_results']
            
            # Validate input (like PHP would)
            if not query or len(query) < 2:
                response = {
                    'success': False,
                    'message': 'Invalid food query. Must be 2-100 characters long.',
                    'query': query
                }
            else:
                # Call the Python scraper (like PHP would)
                image_data = scraper.search_images(query, max_results)
                
                if image_data and len(image_data) > 0:
                    response = {
                        'success': True,
                        'message': 'Images retrieved successfully',
                        'query': query,
                        'count': len(image_data),
                        'images': image_data
                    }
                else:
                    # Fallback response (like PHP would)
                    fallback_images = [
                        {
                            'title': f"{query} image",
                            'image_url': f"https://via.placeholder.com/300x200/FF6B6B/FFFFFF?text={query.replace(' ', '+')}",
                            'source_url': f"https://via.placeholder.com/300x200/FF6B6B/FFFFFF?text={query.replace(' ', '+')}",
                            'query': query
                        }
                    ]
                    
                    response = {
                        'success': True,
                        'message': 'Using fallback images (scraper unavailable)',
                        'query': query,
                        'count': len(fallback_images),
                        'images': fallback_images,
                        'fallback': True
                    }
            
            # Print the response (like PHP would output)
            print("Response:")
            print(json.dumps(response, indent=2))
            
        except Exception as e:
            response = {
                'success': False,
                'message': 'Internal server error',
                'error': str(e)
            }
            print("Response:")
            print(json.dumps(response, indent=2))
        
        print()
        print("=" * 60)
        print()

def test_curl_simulation():
    """Simulate curl requests to the API"""
    
    print("=== Curl Request Simulation ===")
    print()
    
    # Simulate GET request
    print("Simulating: curl 'http://localhost:8000/public/api/food_image_scraper.php?query=sinigang%20na%20baboy&max_results=3'")
    print()
    
    # Parse the simulated URL
    simulated_url = "http://localhost:8000/public/api/food_image_scraper.php?query=sinigang%20na%20baboy&max_results=3"
    parsed_url = urlparse(simulated_url)
    query_params = parse_qs(parsed_url.query)
    
    query = query_params.get('query', [''])[0]
    max_results = int(query_params.get('max_results', ['5'])[0])
    
    print(f"Parsed Query: {query}")
    print(f"Parsed Max Results: {max_results}")
    print()
    
    # Process the request
    scraper = AdvancedGoogleImagesScraper()
    image_data = scraper.search_images(query, max_results)
    
    response = {
        'success': True,
        'message': 'Images retrieved successfully',
        'query': query,
        'count': len(image_data),
        'images': image_data
    }
    
    print("Response:")
    print(json.dumps(response, indent=2))

if __name__ == "__main__":
    print("PHP API Testing Simulation")
    print("=" * 50)
    print()
    
    simulate_php_api()
    print()
    test_curl_simulation()
    
    print()
    print("=== Testing Complete ===")
    print("This simulation shows what the PHP API would do.")
    print("To test the actual PHP API, install PHP and run:")
    print("php -S localhost:8000")
    print("curl 'http://localhost:8000/public/api/food_image_scraper.php?query=sinigang%20na%20baboy'")
