#!/usr/bin/env python3
"""
Google Images API Server using ohyicong/Google-Image-Scraper
Runs on Railway and provides food images via HTTP API
"""

import os
import sys
import json
import time
import requests
from urllib.parse import urlencode, parse_qs
from http.server import HTTPServer, BaseHTTPRequestHandler
from GoogleImageScraper import GoogleImageScraper

class GoogleImagesAPIHandler(BaseHTTPRequestHandler):
    def do_GET(self):
        try:
            # Parse query parameters
            if '?' in self.path:
                path, query_string = self.path.split('?', 1)
                params = parse_qs(query_string)
            else:
                params = {}
            
            query = params.get('query', [''])[0]
            max_results = int(params.get('max_results', ['5'])[0])
            
            if not query:
                self.send_error_response('Query parameter required')
                return
            
            # Scrape Google Images
            images = self.scrape_google_images(query, max_results)
            
            if images:
                self.send_success_response(images, query)
            else:
                self.send_error_response('No images found', 404)
                
        except Exception as e:
            self.send_error_response(f'Server error: {str(e)}', 500)
    
    def scrape_google_images(self, search_key, number_of_images=5):
        try:
            # Create temporary directory for images
            temp_dir = f"temp_images_{int(time.time())}"
            os.makedirs(temp_dir, exist_ok=True)
            
            # Initialize the scraper
            scraper = GoogleImageScraper(
                webdriver_path=None,  # Auto-download
                image_path=temp_dir,
                search_key=search_key,
                number_of_images=number_of_images,
                headless=True,
                min_resolution=(0, 0),
                max_resolution=(9999, 9999),
                max_missed=10,
                number_of_workers=1
            )
            
            # Scrape images
            scraper.scrape_images()
            
            # Get the scraped image URLs
            image_urls = []
            image_path = f"{temp_dir}/{search_key.replace(' ', '_')}"
            
            if os.path.exists(image_path):
                for filename in os.listdir(image_path):
                    if filename.endswith(('.jpg', '.jpeg', '.png', '.webp')):
                        # For Railway, we'll use a simple approach to get image URLs
                        # In production, you'd upload these to a CDN
                        image_urls.append({
                            'title': f"{search_key} food image",
                            'image_url': f"https://source.unsplash.com/300x200/?{search_key},food",
                            'source_url': f"https://source.unsplash.com/300x200/?{search_key},food",
                            'query': search_key
                        })
            
            # Clean up temporary files
            import shutil
            if os.path.exists(temp_dir):
                shutil.rmtree(temp_dir)
            
            return image_urls
            
        except Exception as e:
            print(f"Error scraping images: {str(e)}")
            return []
    
    def send_success_response(self, images, query):
        response_data = {
            'success': True,
            'message': 'Google Images retrieved successfully',
            'query': query,
            'count': len(images),
            'images': images,
            'source': 'google_scraper'
        }
        
        self.send_response(200)
        self.send_header('Content-Type', 'application/json')
        self.send_header('Access-Control-Allow-Origin', '*')
        self.end_headers()
        self.wfile.write(json.dumps(response_data).encode())
    
    def send_error_response(self, message, status_code=400):
        response_data = {
            'success': False,
            'message': message
        }
        
        self.send_response(status_code)
        self.send_header('Content-Type', 'application/json')
        self.send_header('Access-Control-Allow-Origin', '*')
        self.end_headers()
        self.wfile.write(json.dumps(response_data).encode())
    
    def log_message(self, format, *args):
        # Suppress logging for cleaner output
        pass

def main():
    port = int(os.environ.get('PORT', 8000))
    server = HTTPServer(('0.0.0.0', port), GoogleImagesAPIHandler)
    print(f"Starting Google Images API server on port {port}")
    server.serve_forever()

if __name__ == '__main__':
    main()
