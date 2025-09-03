<?php
/**
 * Food Image Scraper API using christian-byrne/google-image-comyui-node approach
 * Uses DuckDuckGo search and limits to exactly 10 images
 * https://github.com/christian-byrne/google-image-comyui-node
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

// Function to call the christian-byrne Google Image scraper
function callDuckDuckGoScraper($foodQuery) {
    try {
        // Path to the Python script
        $pythonScript = __DIR__ . '/../../node.py';
        
        // Check if Python script exists
        if (!file_exists($pythonScript)) {
            throw new Exception("DuckDuckGo scraper script not found: $pythonScript");
        }
        
        // Create a temporary Python script for this specific query
        $tempScript = __DIR__ . '/../../temp_duckduckgo_' . time() . '.py';
        
        // Create the Python script content using christian-byrne approach
        $pythonCode = <<<PYTHON
import sys
import os
sys.path.append(os.path.dirname(os.path.abspath(__file__)))

from duckduckgo_search import DDGS
import json

def search_images(search_key):
    try:
        # Use DuckDuckGo search like christian-byrne approach
        with DDGS() as ddgs:
            # Search for images with the food query
            search_results = list(ddgs.images(search_key + " food", max_results=10))
            
            # Extract image URLs (limit to exactly 10)
            image_urls = []
            for result in search_results[:10]:  # Ensure exactly 10 results
                if 'image' in result:
                    image_urls.append({
                        'title': f"{search_key} food image",
                        'image_url': result['image'],
                        'source_url': result.get('link', result['image']),
                        'query': search_key
                    })
            
            return image_urls
            
    except Exception as e:
        print(f"Error: {str(e)}")
        return []

if __name__ == "__main__":
    search_key = sys.argv[1] if len(sys.argv) > 1 else "food"
    results = search_images(search_key)
    print(json.dumps(results))
PYTHON;
        
        // Write the temporary script
        file_put_contents($tempScript, $pythonCode);
        
        // Escape the query for shell execution
        $escapedQuery = escapeshellarg($foodQuery);
        
        // Build the command
        $command = "python3 $tempScript $escapedQuery 2>&1";
        
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
        error_log("Error calling DuckDuckGo scraper: " . $e->getMessage());
        return null;
    }
}

// Main API logic
try {
    // Get request method
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        // Handle GET request
        $foodQuery = isset($_GET['query']) ? $_GET['query'] : '';
        $maxResults = intval(isset($_GET['max_results']) ? $_GET['max_results'] : 10);
        
    } elseif ($method === 'POST') {
        // Handle POST request
        $input = json_decode(file_get_contents('php://input'), true);
        $foodQuery = isset($input['query']) ? $input['query'] : '';
        $maxResults = intval(isset($input['max_results']) ? $input['max_results'] : 10);
        
    } else {
        http_response_code(405);
        echo json_encode(array(
            'success' => false,
            'message' => 'Method not allowed. Use GET or POST.',
            'usage' => array(
                'GET' => '?query=food_name&max_results=10',
                'POST' => '{"query": "food_name", "max_results": 10}'
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
    
    // Force max_results to be exactly 10 (as requested)
    $maxResults = 10;
    
    // Log the request
    error_log("Food image scraper request: query='$foodQuery', max_results=$maxResults");
    
    // Call the DuckDuckGo scraper
    $imageData = callDuckDuckGoScraper($foodQuery);
    
    if ($imageData && !empty($imageData)) {
        // Limit to exactly 10 images
        $imageData = array_slice($imageData, 0, 10);
        
        echo json_encode(array(
            'success' => true,
            'message' => 'Images retrieved successfully using DuckDuckGo search',
            'query' => $foodQuery,
            'count' => count($imageData),
            'images' => $imageData,
            'source' => 'duckduckgo_search'
        ));
        
    } else {
        http_response_code(404);
        echo json_encode(array(
            'success' => false,
            'message' => 'No images found for the query',
            'query' => $foodQuery
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
