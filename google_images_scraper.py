#!/usr/bin/env python3
"""
Google Images Scraper for Nutrisaur Food Recommendations
Based on oxylabs/how-to-scrape-google-images
"""

import requests
import pandas as pd
import json
import sys
import os
from urllib.parse import quote_plus
import time
from typing import List, Dict, Optional

class GoogleImagesScraper:
    def __init__(self):
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        })
        
    def search_images(self, query: str, max_results: int = 10) -> List[Dict]:
        """
        Search Google Images for a given query
        Returns a list of image data dictionaries
        """
        try:
            # Format query for Google Images search
            search_query = quote_plus(query)
            url = f"https://www.google.com/search?q={search_query}&tbm=isch"
            
            print(f"Searching for: {query}")
            
            response = self.session.get(url, timeout=10)
            response.raise_for_status()
            
            # Extract image URLs from the response
            # This is a simplified approach - in production you might want to use a more robust parser
            content = response.text
            
            # Look for image URLs in the response
            image_data = self._extract_image_urls(content, query, max_results)
            
            return image_data
            
        except Exception as e:
            print(f"Error searching images for '{query}': {str(e)}")
            return []
    
    def _extract_image_urls(self, content: str, query: str, max_results: int) -> List[Dict]:
        """
        Extract image URLs from Google Images search response
        This is a simplified implementation - you might want to use a more robust HTML parser
        """
        image_data = []
        
        # Simple regex to find image URLs (this is a basic approach)
        import re
        
        # Look for common image URL patterns
        url_patterns = [
            r'https://[^"\s]+\.(?:jpg|jpeg|png|gif|webp)',
            r'https://[^"\s]+\.(?:jpg|jpeg|png|gif|webp)\?[^"\s]*',
        ]
        
        for pattern in url_patterns:
            matches = re.findall(pattern, content, re.IGNORECASE)
            for url in matches:
                if len(image_data) >= max_results:
                    break
                    
                # Clean the URL
                url = url.strip('"\'')
                
                # Skip if already added
                if any(img['image_url'] == url for img in image_data):
                    continue
                
                image_data.append({
                    'title': f"{query} image",
                    'image_url': url,
                    'source_url': url,
                    'query': query
                })
        
        # If no images found, return fallback data
        if not image_data:
            image_data = self._get_fallback_images(query)
        
        return image_data[:max_results]
    
    def _get_fallback_images(self, query: str) -> List[Dict]:
        """
        Return fallback image data when scraping fails
        """
        # Use a food image placeholder service
        fallback_urls = [
            f"https://via.placeholder.com/300x200/FF6B6B/FFFFFF?text={quote_plus(query)}",
            f"https://via.placeholder.com/300x200/4ECDC4/FFFFFF?text={quote_plus(query)}",
            f"https://via.placeholder.com/300x200/45B7D1/FFFFFF?text={quote_plus(query)}"
        ]
        
        return [
            {
                'title': f"{query} image",
                'image_url': url,
                'source_url': url,
                'query': query
            }
            for url in fallback_urls
        ]
    
    def save_to_csv(self, image_data: List[Dict], filename: str = "food_images.csv"):
        """Save image data to CSV file"""
        if image_data:
            df = pd.DataFrame(image_data)
            df.to_csv(filename, index=False)
            print(f"Saved {len(image_data)} images to {filename}")
        else:
            print("No image data to save")

def main():
    """Main function for command line usage"""
    if len(sys.argv) < 2:
        print("Usage: python google_images_scraper.py <food_query> [max_results]")
        print("Example: python google_images_scraper.py 'sinigang na baboy' 5")
        sys.exit(1)
    
    query = sys.argv[1]
    max_results = int(sys.argv[2]) if len(sys.argv) > 2 else 5
    
    scraper = GoogleImagesScraper()
    image_data = scraper.search_images(query, max_results)
    
    if image_data:
        # Save to CSV
        scraper.save_to_csv(image_data, f"{query.replace(' ', '_')}_images.csv")
        
        # Print results
        print(f"\nFound {len(image_data)} images for '{query}':")
        for i, img in enumerate(image_data, 1):
            print(f"{i}. {img['title']} - {img['image_url']}")
    else:
        print(f"No images found for '{query}'")

if __name__ == "__main__":
    main()
