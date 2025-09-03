#!/usr/bin/env python3
"""
Advanced Google Images Scraper for Nutrisaur Food Recommendations
Based on oxylabs/how-to-scrape-google-images with improved parsing
"""

import requests
import pandas as pd
import json
import sys
import os
from urllib.parse import quote_plus, unquote
import time
from typing import List, Dict, Optional
import re
from bs4 import BeautifulSoup
import base64

class AdvancedGoogleImagesScraper:
    def __init__(self):
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language': 'en-US,en;q=0.5',
            'Accept-Encoding': 'gzip, deflate',
            'Connection': 'keep-alive',
            'Upgrade-Insecure-Requests': '1',
        })
        
    def search_images(self, query: str, max_results: int = 10) -> List[Dict]:
        """
        Search Google Images for a given query using advanced parsing
        Returns a list of image data dictionaries
        """
        try:
            # Format query for Google Images search
            search_query = quote_plus(query)
            url = f"https://www.google.com/search?q={search_query}&tbm=isch&hl=en"
            
            print(f"Searching for: {query}")
            
            response = self.session.get(url, timeout=15)
            response.raise_for_status()
            
            # Parse the HTML content
            soup = BeautifulSoup(response.content, 'html.parser')
            
            # Extract image data using multiple methods
            image_data = self._extract_images_advanced(soup, query, max_results)
            
            return image_data
            
        except Exception as e:
            print(f"Error searching images for '{query}': {str(e)}")
            return self._get_fallback_images(query, max_results)
    
    def _extract_images_advanced(self, soup: BeautifulSoup, query: str, max_results: int) -> List[Dict]:
        """
        Extract image URLs using multiple advanced methods
        """
        image_data = []
        
        # Method 1: Extract from script tags containing image data
        scripts = soup.find_all('script')
        for script in scripts:
            if script.string and 'AF_initDataCallback' in script.string:
                # Look for image data in the script
                image_urls = self._extract_from_script(script.string, query)
                for url in image_urls:
                    if len(image_data) >= max_results:
                        break
                    if url not in [img['image_url'] for img in image_data]:
                        image_data.append({
                            'title': f"{query} image",
                            'image_url': url,
                            'source_url': url,
                            'query': query
                        })
        
        # Method 2: Extract from img tags
        if len(image_data) < max_results:
            img_tags = soup.find_all('img')
            for img in img_tags:
                if len(image_data) >= max_results:
                    break
                    
                src = img.get('src') or img.get('data-src')
                if src and self._is_valid_image_url(src):
                    if src not in [img['image_url'] for img in image_data]:
                        image_data.append({
                            'title': img.get('alt', f"{query} image"),
                            'image_url': src,
                            'source_url': src,
                            'query': query
                        })
        
        # Method 3: Extract from data attributes
        if len(image_data) < max_results:
            divs = soup.find_all('div', {'data-ved': True})
            for div in divs:
                if len(image_data) >= max_results:
                    break
                    
                # Look for image data in various attributes
                for attr in ['data-thumbnail-url', 'data-src', 'data-thumbnail']:
                    url = div.get(attr)
                    if url and self._is_valid_image_url(url):
                        if url not in [img['image_url'] for img in image_data]:
                            image_data.append({
                                'title': f"{query} image",
                                'image_url': url,
                                'source_url': url,
                                'query': query
                            })
                            break
        
        # Method 4: Extract from JSON-like strings in the page
        if len(image_data) < max_results:
            page_text = str(soup)
            json_patterns = [
                r'"ou":"([^"]+\.(?:jpg|jpeg|png|gif|webp)[^"]*)"',
                r'"url":"([^"]+\.(?:jpg|jpeg|png|gif|webp)[^"]*)"',
                r'"src":"([^"]+\.(?:jpg|jpeg|png|gif|webp)[^"]*)"',
            ]
            
            for pattern in json_patterns:
                matches = re.findall(pattern, page_text, re.IGNORECASE)
                for url in matches:
                    if len(image_data) >= max_results:
                        break
                    
                    # Clean and decode URL
                    url = url.replace('\\u003d', '=').replace('\\u0026', '&')
                    url = unquote(url)
                    
                    if self._is_valid_image_url(url) and url not in [img['image_url'] for img in image_data]:
                        image_data.append({
                            'title': f"{query} image",
                            'image_url': url,
                            'source_url': url,
                            'query': query
                        })
        
        # If still not enough images, add fallbacks
        if len(image_data) < max_results:
            fallbacks = self._get_fallback_images(query, max_results - len(image_data))
            image_data.extend(fallbacks)
        
        return image_data[:max_results]
    
    def _extract_from_script(self, script_content: str, query: str) -> List[str]:
        """Extract image URLs from script content"""
        urls = []
        
        # Look for various patterns in script content
        patterns = [
            r'https://[^"\s]+\.(?:jpg|jpeg|png|gif|webp)(?:\?[^"\s]*)?',
            r'http://[^"\s]+\.(?:jpg|jpeg|png|gif|webp)(?:\?[^"\s]*)?',
        ]
        
        for pattern in patterns:
            matches = re.findall(pattern, script_content, re.IGNORECASE)
            for url in matches:
                url = url.strip('"\'')
                if self._is_valid_image_url(url):
                    urls.append(url)
        
        return urls
    
    def _is_valid_image_url(self, url: str) -> bool:
        """Check if URL is a valid image URL"""
        if not url or not isinstance(url, str):
            return False
        
        # Must be HTTP/HTTPS
        if not url.startswith(('http://', 'https://')):
            return False
        
        # Must have image extension
        image_extensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.bmp']
        if not any(ext in url.lower() for ext in image_extensions):
            return False
        
        # Skip data URLs and very short URLs
        if url.startswith('data:') or len(url) < 20:
            return False
        
        return True
    
    def _get_fallback_images(self, query: str, count: int = 3) -> List[Dict]:
        """
        Return fallback image data when scraping fails
        """
        # Use food image placeholder services
        fallback_urls = [
            f"https://via.placeholder.com/300x200/FF6B6B/FFFFFF?text={quote_plus(query)}",
            f"https://via.placeholder.com/300x200/4ECDC4/FFFFFF?text={quote_plus(query)}",
            f"https://via.placeholder.com/300x200/45B7D1/FFFFFF?text={quote_plus(query)}",
            f"https://via.placeholder.com/300x200/96CEB4/FFFFFF?text={quote_plus(query)}",
            f"https://via.placeholder.com/300x200/FFEAA7/000000?text={quote_plus(query)}"
        ]
        
        return [
            {
                'title': f"{query} image",
                'image_url': url,
                'source_url': url,
                'query': query
            }
            for url in fallback_urls[:count]
        ]
    
    def save_to_csv(self, image_data: List[Dict], filename: str = "food_images.csv"):
        """Save image data to CSV file"""
        if image_data:
            df = pd.DataFrame(image_data)
            df.to_csv(filename, index=False)
            print(f"Saved {len(image_data)} images to {filename}")
        else:
            print("No image data to save")
    
    def save_to_json(self, image_data: List[Dict], filename: str = "food_images.json"):
        """Save image data to JSON file"""
        if image_data:
            with open(filename, 'w', encoding='utf-8') as f:
                json.dump(image_data, f, indent=2, ensure_ascii=False)
            print(f"Saved {len(image_data)} images to {filename}")
        else:
            print("No image data to save")

def main():
    """Main function for command line usage"""
    if len(sys.argv) < 2:
        print("Usage: python google_images_scraper_advanced.py <food_query> [max_results]")
        print("Example: python google_images_scraper_advanced.py 'sinigang na baboy' 5")
        sys.exit(1)
    
    query = sys.argv[1]
    max_results = int(sys.argv[2]) if len(sys.argv) > 2 else 5
    
    scraper = AdvancedGoogleImagesScraper()
    image_data = scraper.search_images(query, max_results)
    
    if image_data:
        # Save to both CSV and JSON
        safe_query = query.replace(' ', '_').replace('/', '_')
        scraper.save_to_csv(image_data, f"{safe_query}_images.csv")
        scraper.save_to_json(image_data, f"{safe_query}_images.json")
        
        # Print results
        print(f"\nFound {len(image_data)} images for '{query}':")
        for i, img in enumerate(image_data, 1):
            print(f"{i}. {img['title']} - {img['image_url']}")
    else:
        print(f"No images found for '{query}'")

if __name__ == "__main__":
    main()
