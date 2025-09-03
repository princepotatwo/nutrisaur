#!/usr/bin/env python3
"""
Test DuckDuckGo Image Search
"""

from duckduckgo_search import DDGS
import json

def test_search():
    try:
        with DDGS() as ddgs:
            # Search for images with the food query
            search_results = list(ddgs.images("adobo food", max_results=10))
            
            # Extract image URLs (limit to exactly 10)
            image_urls = []
            for result in search_results[:10]:  # Ensure exactly 10 results
                if 'image' in result:
                    image_urls.append({
                        'title': "adobo food image",
                        'image_url': result['image'],
                        'source_url': result.get('link', result['image']),
                        'query': 'adobo'
                    })
            
            print(json.dumps(image_urls, indent=2))
            return image_urls
            
    except Exception as e:
        print(f"Error: {str(e)}")
        return []

if __name__ == "__main__":
    test_search()
