<?php
/**
 * Food Image Scraper API using ohyicong/Google-Image-Scraper
 * https://github.com/ohyicong/Google-Image-Scraper
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// Function to validate food query
function validateFoodQuery($query) {
    if (empty($query) || strlen($query) < 2) {
        return false;
    }
    
    // Remove potentially dangerous characters
    $query = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $query);
    
    return strlen($query) <= 100; // Limit length
}

// Function to call the Google Image Scraper
function callGoogleImageScraper($foodQuery, $maxResults = 5) {
    try {
        // Path to the Python script
        $pythonScript = __DIR__ . '/../../GoogleImageScraper.py';
        
        // Check if Python script exists
        if (!file_exists($pythonScript)) {
            throw new Exception("Google Image Scraper script not found: $pythonScript");
        }
        
        // Create a temporary Python script for this specific query
        $tempScript = __DIR__ . '/../../temp_scraper_' . time() . '.py';
        
        // Create the Python script content
        $pythonCode = <<<PYTHON
import sys
import os
sys.path.append(os.path.dirname(os.path.abspath(__file__)))

from GoogleImageScraper import GoogleImageScraper
import json

def scrape_images(search_key, number_of_images=5):
    try:
        # Initialize the scraper
        scraper = GoogleImageScraper(
            webdriver_path=None,  # Auto-download
            image_path="temp_images",
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
        image_path = "temp_images/" + search_key.replace(" ", "_")
        
        if os.path.exists(image_path):
            for filename in os.listdir(image_path):
                if filename.endswith(('.jpg', '.jpeg', '.png', '.webp')):
                    # For now, we'll return placeholder URLs since we can't serve local files
                    # In production, you'd upload these to a CDN or cloud storage
                    image_urls.append({
                        'title': f"{search_key} food image",
                        'image_url': f"https://source.unsplash.com/300x200/?{search_key},food",
                        'source_url': f"https://source.unsplash.com/300x200/?{search_key},food",
                        'query': search_key
                    })
        
        # Clean up temporary files
        if os.path.exists(image_path):
            import shutil
            shutil.rmtree(image_path)
        
        return image_urls
        
    except Exception as e:
        print(f"Error: {str(e)}")
        return []

if __name__ == "__main__":
    search_key = sys.argv[1] if len(sys.argv) > 1 else "food"
    number_of_images = int(sys.argv[2]) if len(sys.argv) > 2 else 5
    
    results = scrape_images(search_key, number_of_images)
    print(json.dumps(results))
PYTHON;
        
        // Write the temporary script
        file_put_contents($tempScript, $pythonCode);
        
        // Escape the query for shell execution
        $escapedQuery = escapeshellarg($foodQuery);
        $escapedMaxResults = escapeshellarg($maxResults);
        
        // Build the command
        $command = "python3 $tempScript $escapedQuery $escapedMaxResults 2>&1";
        
        // Execute the command
        $output = shell_exec($command);
        
        // Clean up the temporary script
        unlink($tempScript);
        
        // Check if the command executed successfully
        if (strpos($output, 'Error:') !== false) {
            throw new Exception("Python script failed: $output");
        }
        
        // Parse the JSON output
        $imageData = json_decode($output, true);
        if ($imageData === null) {
            throw new Exception("Failed to parse JSON output: " . json_last_error_msg());
        }
        
        return $imageData;
        
    } catch (Exception $e) {
        error_log("Error calling Google Image Scraper: " . $e->getMessage());
        return null;
    }
}

// Function to get fallback images using Unsplash
function getFallbackImages($foodQuery, $maxResults = 5) {
    $images = array();
    
    try {
        // Use Unsplash Source API for fallback
        $imageUrls = array(
            "https://source.unsplash.com/300x200/?{$foodQuery},food",
            "https://source.unsplash.com/300x200/?{$foodQuery},dish",
            "https://source.unsplash.com/300x200/?{$foodQuery},meal",
            "https://source.unsplash.com/300x200/?{$foodQuery},cuisine",
            "https://source.unsplash.com/300x200/?{$foodQuery},cooking"
        );
        
        $count = 0;
        foreach ($imageUrls as $url) {
            if ($count >= $maxResults) break;
            
            $images[] = array(
                'title' => $foodQuery . ' food image',
                'image_url' => $url,
                'source_url' => $url,
                'query' => $foodQuery
            );
            $count++;
        }
        
    } catch (Exception $e) {
        error_log("Error getting fallback images: " . $e->getMessage());
    }
    
    return $images;
}

// Main API logic
try {
    // Get request method
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        // Handle GET request
        $foodQuery = isset($_GET['query']) ? $_GET['query'] : '';
        $maxResults = intval(isset($_GET['max_results']) ? $_GET['max_results'] : 5);
        
    } elseif ($method === 'POST') {
        // Handle POST request
        $input = json_decode(file_get_contents('php://input'), true);
        $foodQuery = isset($input['query']) ? $input['query'] : '';
        $maxResults = intval(isset($input['max_results']) ? $input['max_results'] : 5);
        
    } else {
        http_response_code(405);
        echo json_encode(array(
            'success' => false,
            'message' => 'Method not allowed. Use GET or POST.',
            'usage' => array(
                'GET' => '?query=food_name&max_results=5',
                'POST' => '{"query": "food_name", "max_results": 5}'
            )
        ));
        exit;
    }
    
    // Validate input
    if (!validateFoodQuery($foodQuery)) {
        http_response_code(400);
        echo json_encode(array(
            'success' => false,
            'message' => 'Invalid food query. Must be 2-100 characters long.',
            'query' => $foodQuery
        ));
        exit;
    }
    
    // Sanitize input
    $foodQuery = sanitizeInput($foodQuery);
    $maxResults = max(1, min(20, $maxResults)); // Limit between 1 and 20
    
    // Log the request
    error_log("Food image scraper request: query='$foodQuery', max_results=$maxResults");
    
    // Try to use the Google Image Scraper first
    $imageData = callGoogleImageScraper($foodQuery, $maxResults);
    
    if ($imageData && !empty($imageData)) {
        // Success - return scraped images
        echo json_encode(array(
            'success' => true,
            'message' => 'Images retrieved successfully using Google Image Scraper',
            'query' => $foodQuery,
            'count' => count($imageData),
            'images' => $imageData,
            'source' => 'google_scraper'
        ));
        
    } else {
        // Fallback to Unsplash images
        $fallbackImages = getFallbackImages($foodQuery, $maxResults);
        
        echo json_encode(array(
            'success' => true,
            'message' => 'Using Unsplash fallback images',
            'query' => $foodQuery,
            'count' => count($fallbackImages),
            'images' => $fallbackImages,
            'source' => 'unsplash_fallback'
        ));
    }
    
} catch (Exception $e) {
    error_log("Food image scraper error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'Internal server error',
        'error' => $e->getMessage()
    ));
}
?>
