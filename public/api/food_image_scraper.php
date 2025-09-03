<?php
/**
 * Food Image Scraper API using enhanced serping approach - Google SERP scraping
 * Exactly 10 images, no unlimited cards
 * Based on: https://github.com/serping/express-scraper/blob/main/app/api/v1/google/serp.ts
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

// Function to scrape Google Images using enhanced serping approach
function scrapeGoogleImagesSerping($foodQuery) {
    $images = array();
    
    try {
        // Multiple search strategies for better results
        $searchStrategies = array(
            // Strategy 1: Basic Google Images search
            "https://www.google.com/search?q=" . urlencode($foodQuery . ' food') . "&tbm=isch&hl=en&tbs=isz:l",
            // Strategy 2: With photo filter
            "https://www.google.com/search?q=" . urlencode($foodQuery . ' food') . "&tbm=isch&hl=en&tbs=isz:l,itp:photo",
            // Strategy 3: Without food keyword
            "https://www.google.com/search?q=" . urlencode($foodQuery) . "&tbm=isch&hl=en&tbs=isz:l",
            // Strategy 4: With recipe keyword
            "https://www.google.com/search?q=" . urlencode($foodQuery . ' recipe') . "&tbm=isch&hl=en&tbs=isz:l"
        );
        
        $userAgents = array(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        );
        
        $patterns = array(
            // Google Images JSON data patterns
            '/"ou":"(https:\/\/[^"]+\.(jpg|jpeg|png|webp))"/',
            '/"url":"(https:\/\/[^"]+\.(jpg|jpeg|png|webp))"/',
            '/\["(https:\/\/[^"]+\.(jpg|jpeg|png|webp))"/',
            // Direct image URLs
            '/data-src="(https:\/\/[^"]+\.(jpg|jpeg|png|webp))"/',
            '/src="(https:\/\/[^"]+\.(jpg|jpeg|png|webp))"/',
            // Google Images specific patterns
            '/"https:\/\/[^"]+\.(jpg|jpeg|png|webp)"/',
            '/https:\/\/[^"]+\.(jpg|jpeg|png|webp)/',
            // Additional patterns for better coverage
            '/"https:\/\/[^"]+\.(jpg|jpeg|png|webp)\?[^"]*"/',
            '/https:\/\/[^"]+\.(jpg|jpeg|png|webp)\?[^"]*/'
        );
        
        $foundUrls = array();
        
        foreach ($searchStrategies as $strategyIndex => $searchUrl) {
            if (count($images) >= 10) break; // Stop if we have enough images
            
            foreach ($userAgents as $userAgent) {
                if (count($images) >= 10) break;
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $searchUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
                    'Accept-Language: en-US,en;q=0.9',
                    'Accept-Encoding: gzip, deflate, br',
                    'Connection: keep-alive',
                    'Upgrade-Insecure-Requests: 1',
                    'Sec-Fetch-Dest: document',
                    'Sec-Fetch-Mode: navigate',
                    'Sec-Fetch-Site: none',
                    'Cache-Control: max-age=0',
                    'DNT: 1'
                ));
                
                $html = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode === 200 && $html) {
                    // Extract image URLs using patterns
                    foreach ($patterns as $pattern) {
                        preg_match_all($pattern, $html, $matches);
                        if (!empty($matches[1])) {
                            $foundUrls = array_merge($foundUrls, $matches[1]);
                        } elseif (!empty($matches[0])) {
                            $foundUrls = array_merge($foundUrls, $matches[0]);
                        }
                    }
                    
                    // Remove duplicates and validate URLs
                    $foundUrls = array_unique($foundUrls);
                    
                    foreach ($foundUrls as $url) {
                        if (count($images) >= 10) break;
                        
                        // Clean up URL (remove quotes if present)
                        $url = trim($url, '"');
                        
                        // Validate URL and ensure it's an image
                        if (filter_var($url, FILTER_VALIDATE_URL) && 
                            preg_match('/\.(jpg|jpeg|png|webp)$/i', $url)) {
                            
                            $images[] = array(
                                'title' => $foodQuery . ' food image',
                                'image_url' => $url,
                                'source_url' => $url,
                                'query' => $foodQuery
                            );
                        }
                    }
                    
                    // If we found images, break out of user agent loop
                    if (!empty($images)) {
                        break;
                    }
                }
                
                // Small delay between requests
                usleep(100000); // 0.1 second
            }
        }
        
        // If still no images, use high-quality fallback
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
        error_log("Error scraping Google Images (serping): " . $e->getMessage());
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
    
    // Scrape Google Images using enhanced serping approach
    $images = scrapeGoogleImagesSerping($foodQuery);
    
    if (!empty($images)) {
        // Limit to exactly 10 images
        $images = array_slice($images, 0, 10);
        
        // Determine source based on image URLs
        $source = 'google_serp_scraping';
        $hasGoogleImages = false;
        foreach ($images as $image) {
            if (strpos($image['image_url'], 'unsplash.com') === false) {
                $hasGoogleImages = true;
                break;
            }
        }
        if (!$hasGoogleImages) {
            $source = 'unsplash_fallback';
        }
        
        echo json_encode(array(
            'success' => true,
            'message' => 'Images retrieved successfully using enhanced Google SERP scraping',
            'query' => $foodQuery,
            'count' => count($images),
            'images' => $images,
            'source' => $source
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
