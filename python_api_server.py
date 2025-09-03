#!/usr/bin/env python3
"""
Python HTTP Server that replaces the PHP API for testing
"""

import http.server
import socketserver
import json
import urllib.parse
import sys
import os
from google_images_scraper_advanced import AdvancedGoogleImagesScraper

class FoodImageAPIHandler(http.server.BaseHTTPRequestHandler):
    def do_GET(self):
        """Handle GET requests"""
        try:
            # Parse the URL
            parsed_url = urllib.parse.urlparse(self.path)
            
            if parsed_url.path == '/public/api/food_image_scraper.php':
                # Extract query parameters
                query_params = urllib.parse.parse_qs(parsed_url.query)
                
                food_query = query_params.get('query', [''])[0]
                max_results = int(query_params.get('max_results', ['5'])[0])
                
                # Process the request
                response_data = self.process_image_request(food_query, max_results)
                
                # Send response
                self.send_response(200)
                self.send_header('Content-Type', 'application/json')
                self.send_header('Access-Control-Allow-Origin', '*')
                self.send_header('Access-Control-Allow-Methods', 'GET, POST')
                self.send_header('Access-Control-Allow-Headers', 'Content-Type')
                self.end_headers()
                
                self.wfile.write(json.dumps(response_data, indent=2).encode('utf-8'))
                
            else:
                # Return 404 for other paths
                self.send_response(404)
                self.send_header('Content-Type', 'text/plain')
                self.end_headers()
                self.wfile.write(b'Not Found')
                
        except Exception as e:
            # Return error response
            error_response = {
                'success': False,
                'message': 'Internal server error',
                'error': str(e)
            }
            
            self.send_response(500)
            self.send_header('Content-Type', 'application/json')
            self.end_headers()
            self.wfile.write(json.dumps(error_response, indent=2).encode('utf-8'))
    
    def do_POST(self):
        """Handle POST requests"""
        try:
            if self.path == '/public/api/food_image_scraper.php':
                # Read POST data
                content_length = int(self.headers.get('Content-Length', 0))
                post_data = self.rfile.read(content_length).decode('utf-8')
                
                # Parse JSON data
                try:
                    json_data = json.loads(post_data)
                    food_query = json_data.get('query', '')
                    max_results = int(json_data.get('max_results', 5))
                except json.JSONDecodeError:
                    food_query = ''
                    max_results = 5
                
                # Process the request
                response_data = self.process_image_request(food_query, max_results)
                
                # Send response
                self.send_response(200)
                self.send_header('Content-Type', 'application/json')
                self.send_header('Access-Control-Allow-Origin', '*')
                self.send_header('Access-Control-Allow-Methods', 'GET, POST')
                self.send_header('Access-Control-Allow-Headers', 'Content-Type')
                self.end_headers()
                
                self.wfile.write(json.dumps(response_data, indent=2).encode('utf-8'))
                
            else:
                self.send_response(404)
                self.send_header('Content-Type', 'text/plain')
                self.end_headers()
                self.wfile.write(b'Not Found')
                
        except Exception as e:
            error_response = {
                'success': False,
                'message': 'Internal server error',
                'error': str(e)
            }
            
            self.send_response(500)
            self.send_header('Content-Type', 'application/json')
            self.end_headers()
            self.wfile.write(json.dumps(error_response, indent=2).encode('utf-8'))
    
    def do_OPTIONS(self):
        """Handle CORS preflight requests"""
        self.send_response(200)
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        self.send_header('Access-Control-Allow-Headers', 'Content-Type')
        self.end_headers()
    
    def process_image_request(self, food_query, max_results):
        """Process the image request using the scraper"""
        
        # Validate input
        if not food_query or len(food_query) < 2:
            return {
                'success': False,
                'message': 'Invalid food query. Must be 2-100 characters long.',
                'query': food_query
            }
        
        # Sanitize input
        food_query = food_query.strip()[:100]  # Limit to 100 characters
        max_results = max(1, min(20, max_results))  # Limit between 1 and 20
        
        try:
            # Use the scraper
            scraper = AdvancedGoogleImagesScraper()
            image_data = scraper.search_images(food_query, max_results)
            
            if image_data and len(image_data) > 0:
                return {
                    'success': True,
                    'message': 'Images retrieved successfully',
                    'query': food_query,
                    'count': len(image_data),
                    'images': image_data
                }
            else:
                # Fallback to placeholder images
                fallback_images = [
                    {
                        'title': f"{food_query} image",
                        'image_url': f"https://via.placeholder.com/300x200/FF6B6B/FFFFFF?text={food_query.replace(' ', '+')}",
                        'source_url': f"https://via.placeholder.com/300x200/FF6B6B/FFFFFF?text={food_query.replace(' ', '+')}",
                        'query': food_query
                    }
                ]
                
                return {
                    'success': True,
                    'message': 'Using fallback images (scraper unavailable)',
                    'query': food_query,
                    'count': len(fallback_images),
                    'images': fallback_images,
                    'fallback': True
                }
                
        except Exception as e:
            return {
                'success': False,
                'message': 'Error processing request',
                'error': str(e)
            }

def start_server(port=8000):
    """Start the HTTP server"""
    handler = FoodImageAPIHandler
    
    with socketserver.TCPServer(("", port), handler) as httpd:
        print(f"🚀 Python API Server started on port {port}")
        print(f"📡 API Endpoint: http://localhost:{port}/public/api/food_image_scraper.php")
        print()
        print("📋 Test Commands:")
        print(f"curl 'http://localhost:{port}/public/api/food_image_scraper.php?query=sinigang%20na%20baboy&max_results=3'")
        print(f"curl -X POST http://localhost:{port}/public/api/food_image_scraper.php -H 'Content-Type: application/json' -d '{{\"query\": \"adobo\", \"max_results\": 2}}'")
        print()
        print("🛑 Press Ctrl+C to stop the server")
        print("=" * 60)
        
        try:
            httpd.serve_forever()
        except KeyboardInterrupt:
            print("\n🛑 Server stopped")

if __name__ == "__main__":
    port = 8000
    if len(sys.argv) > 1:
        try:
            port = int(sys.argv[1])
        except ValueError:
            print("Invalid port number. Using default port 8000.")
    
    start_server(port)
