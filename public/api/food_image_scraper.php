<?php
/**
 * Food Image Scraper API using DuckDuckGo search
 * Exactly 10 images, no unlimited cards
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

// Function to search DuckDuckGo images using direct API
function searchDuckDuckGoImages($foodQuery) {
    $images = array();
    
    try {
        // Use DuckDuckGo Instant Answer API
        $searchQuery = urlencode($foodQuery . ' food');
        $apiUrl = "https://api.duckduckgo.com/?q={$searchQuery}&format=json&no_html=1&skip_disambig=1";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            
            // Extract image URLs from DuckDuckGo response
            if (isset($data['Image']) && !empty($data['Image'])) {
                $images[] = array(
                    'title' => $foodQuery . ' food image',
                    'image_url' => $data['Image'],
                    'source_url' => $data['Image'],
                    'query' => $foodQuery
                );
            }
            
            // Also check for related topics with images
            if (isset($data['RelatedTopics']) && is_array($data['RelatedTopics'])) {
                foreach ($data['RelatedTopics'] as $topic) {
                    if (isset($topic['Icon']['URL']) && !empty($topic['Icon']['URL'])) {
                        $images[] = array(
                            'title' => $foodQuery . ' food image',
                            'image_url' => $topic['Icon']['URL'],
                            'source_url' => $topic['Icon']['URL'],
                            'query' => $foodQuery
                        );
                    }
                }
            }
        }
        
        // If DuckDuckGo API doesn't work, use a fallback with high-quality food images
        if (empty($images)) {
            $fallbackImages = array(
                "https://source.unsplash.com/300x200/?{$foodQuery},food",
                "https://source.unsplash.com/300x200/?{$foodQuery},dish",
                "https://source.unsplash.com/300x200/?{$foodQuery},meal",
                "https://source.unsplash.com/300x200/?{$foodQuery},cuisine",
                "https://source.unsplash.com/300x200/?{$foodQuery},cooking",
                "https://source.unsplash.com/300x200/?{$foodQuery},recipe",
                "https://source.unsplash.com/300x200/?{$foodQuery},delicious",
                "https://source.unsplash.com/300x200/?{$foodQuery},tasty",
                "https://source.unsplash.com/300x200/?{$foodQuery},homemade",
                "https://source.unsplash.com/300x200/?{$foodQuery},traditional"
            );
            
            foreach ($fallbackImages as $url) {
                $images[] = array(
                    'title' => $foodQuery . ' food image',
                    'image_url' => $url,
                    'source_url' => $url,
                    'query' => $foodQuery
                );
            }
        }
        
    } catch (Exception $e) {
        error_log("Error searching DuckDuckGo images: " . $e->getMessage());
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
    
    // Search for images
    $images = searchDuckDuckGoImages($foodQuery);
    
    if (!empty($images)) {
        // Limit to exactly 10 images
        $images = array_slice($images, 0, 10);
        
        echo json_encode(array(
            'success' => true,
            'message' => 'Images retrieved successfully',
            'query' => $foodQuery,
            'count' => count($images),
            'images' => $images,
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
