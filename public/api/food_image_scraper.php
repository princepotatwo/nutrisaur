<?php
/**
 * Food Image Scraper API
 * Scrapes Google Images for food photos
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

// Function to scrape Google Images using PHP
function scrapeGoogleImages($query, $maxResults = 5) {
    $images = array();
    
    try {
        // Create search URL
        $searchQuery = urlencode($query . ' food');
        $searchUrl = "https://www.google.com/search?q={$searchQuery}&tbm=isch&hl=en";
        
        // Set up cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $searchUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.5',
            'Accept-Encoding: gzip, deflate',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1'
        ));
        
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || empty($html)) {
            throw new Exception("Failed to fetch Google Images page. HTTP Code: $httpCode");
        }
        
        // Extract image URLs using regex
        $pattern = '/\["(https:\/\/[^"]+\.(?:jpg|jpeg|png|webp))"/';
        preg_match_all($pattern, $html, $matches);
        
        if (empty($matches[1])) {
            // Try alternative pattern
            $pattern = '/"ou":"(https:\/\/[^"]+\.(?:jpg|jpeg|png|webp))"/';
            preg_match_all($pattern, $html, $matches);
        }
        
        if (empty($matches[1])) {
            // Try another pattern for Google Images
            $pattern = '/"url":"(https:\/\/[^"]+\.(?:jpg|jpeg|png|webp))"/';
            preg_match_all($pattern, $html, $matches);
        }
        
        // Process found images
        $foundUrls = array_unique($matches[1]);
        $count = 0;
        
        foreach ($foundUrls as $url) {
            if ($count >= $maxResults) break;
            
            // Validate URL
            if (filter_var($url, FILTER_VALIDATE_URL) && 
                preg_match('/\.(jpg|jpeg|png|webp)$/i', $url)) {
                
                $images[] = array(
                    'title' => $query . ' food image',
                    'image_url' => $url,
                    'source_url' => $url,
                    'query' => $query
                );
                $count++;
            }
        }
        
        // If no images found, try a different approach
        if (empty($images)) {
            // Use Unsplash API as backup
            $unsplashUrl = "https://api.unsplash.com/search/photos?query=" . urlencode($query . ' food') . "&per_page=" . $maxResults . "&client_id=YOUR_UNSPLASH_ACCESS_KEY";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $unsplashUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $unsplashResponse = curl_exec($ch);
            curl_close($ch);
            
            if ($unsplashResponse) {
                $unsplashData = json_decode($unsplashResponse, true);
                if (isset($unsplashData['results'])) {
                    foreach ($unsplashData['results'] as $photo) {
                        if (isset($photo['urls']['regular'])) {
                            $images[] = array(
                                'title' => $query . ' food image',
                                'image_url' => $photo['urls']['regular'],
                                'source_url' => $photo['links']['html'],
                                'query' => $query
                            );
                        }
                    }
                }
            }
        }
        
    } catch (Exception $e) {
        error_log("Error scraping Google Images: " . $e->getMessage());
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
    
    // Scrape Google Images
    $images = scrapeGoogleImages($foodQuery, $maxResults);
    
    if (!empty($images)) {
        echo json_encode(array(
            'success' => true,
            'message' => 'Images retrieved successfully',
            'query' => $foodQuery,
            'count' => count($images),
            'images' => $images
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
