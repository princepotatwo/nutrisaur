<?php
/**
 * Food Image Scraper API - Default Images Implementation
 * Exactly 10 images, no unlimited cards
 * Uses default food images from drawable folder
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

// Function to get default food images
function getDefaultFoodImages($foodQuery) {
    // List of available default food images
    $defaultImages = array(
        'adobo' => 'adobo.jpg',
        'sinigang' => 'sinigang_na_baboy.jpg',
        'lechon' => 'lechon.jpg',
        'pancit' => 'pancit_sotanghon.jpg',
        'tinola' => 'tinola.jpg',
        'tortang' => 'tortang_talong.jpg',
        'chicharon' => 'chicharon.jpg',
        'suman' => 'suman_sa_latik.jpg',
        'turon' => 'turon.jpg',
        'bibingka' => 'ube_bibingka.jpg',
        'halaya' => 'ube_halaya.jpg',
        'empanada' => 'vigan_empanada.jpg',
        'ukoy' => 'ukoy.jpg',
        'tupig' => 'tupig.jpg',
        'tokneneng' => 'tokneneng.jpg',
        'tocilog' => 'tocilog.jpg',
        'bangus' => 'tinolang_bangus.jpg',
        'tinapa' => 'tinapa.jpg',
        'pork' => 'sweet_sour_pork.jpg',
        'fish' => 'sweet_and_sour_fish.jpg',
        'milk' => 'soya_milk.jpg',
        'sorbetes' => 'sorbetes.jpg',
        'squid' => 'squid_balls.png',
        'default' => 'default_img.png'
    );
    
    $images = array();
    $baseUrl = 'https://nutrisaur-production.up.railway.app/app/src/main/res/drawable/';
    
    // Try to find matching food image
    $foundMatch = false;
    foreach ($defaultImages as $foodName => $imageFile) {
        if (stripos($foodQuery, $foodName) !== false) {
            $images[] = array(
                'title' => $foodQuery . ' food image',
                'image_url' => $baseUrl . $imageFile,
                'source_url' => $baseUrl . $imageFile,
                'query' => $foodQuery
            );
            $foundMatch = true;
            break;
        }
    }
    
    // If no match found, use default image
    if (!$foundMatch) {
        $images[] = array(
            'title' => $foodQuery . ' food image',
            'image_url' => $baseUrl . 'default_img.png',
            'source_url' => $baseUrl . 'default_img.png',
            'query' => $foodQuery
        );
    }
    
    // Repeat the same image 10 times to get exactly 10 images
    $selectedImage = $images[0];
    $images = array();
    for ($i = 0; $i < 10; $i++) {
        $images[] = $selectedImage;
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
    
    // Get default food images
    $images = getDefaultFoodImages($foodQuery);
    
    echo json_encode(array(
        'success' => true,
        'message' => 'Default food images retrieved successfully',
        'query' => $foodQuery,
        'count' => count($images),
        'images' => $images,
        'source' => 'default_images'
    ));
    
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
