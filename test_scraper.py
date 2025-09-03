#!/usr/bin/env python3
"""
Test script for Google Images Scraper
"""

import sys
import os

# Add the current directory to the path so we can import our scraper
sys.path.append(os.path.dirname(os.path.abspath(__file__)))

try:
    from google_images_scraper_advanced import AdvancedGoogleImagesScraper
    print("✓ Successfully imported AdvancedGoogleImagesScraper")
except ImportError as e:
    print(f"✗ Failed to import AdvancedGoogleImagesScraper: {e}")
    sys.exit(1)

def test_scraper():
    """Test the scraper with a simple food query"""
    
    print("\n=== Testing Google Images Scraper ===\n")
    
    # Test queries
    test_queries = [
        "sinigang na baboy",
        "adobo",
        "tinola",
        "kare-kare"
    ]
    
    scraper = AdvancedGoogleImagesScraper()
    
    for query in test_queries:
        print(f"Testing query: '{query}'")
        try:
            image_data = scraper.search_images(query, max_results=3)
            
            if image_data:
                print(f"✓ Found {len(image_data)} images for '{query}'")
                for i, img in enumerate(image_data, 1):
                    print(f"  {i}. {img['title']} - {img['image_url'][:50]}...")
            else:
                print(f"✗ No images found for '{query}'")
                
        except Exception as e:
            print(f"✗ Error testing '{query}': {e}")
        
        print()  # Empty line for readability
    
    print("=== Test completed ===")

if __name__ == "__main__":
    test_scraper()
