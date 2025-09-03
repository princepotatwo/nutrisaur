<?php
/**
 * Food Image Scraper API
 * Calls Python Google Images scraper to get food images for recommendations
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

// Function to call Python scraper
function callPythonScraper($foodQuery, $maxResults = 5) {
    try {
        // Path to the Python script
        $pythonScript = __DIR__ . '/../../google_images_scraper_advanced.py';
        
        // Check if Python script exists
        if (!file_exists($pythonScript)) {
            throw new Exception("Python scraper script not found: $pythonScript");
        }
        
        // Escape the query for shell execution
        $escapedQuery = escapeshellarg($foodQuery);
        $escapedMaxResults = escapeshellarg($maxResults);
        
        // Build the command
        $command = "python3 $pythonScript $escapedQuery $escapedMaxResults 2>&1";
        
        // Execute the command
        $output = shell_exec($command);
        $returnCode = $? ?? 0;
        
        if ($returnCode !== 0) {
            throw new Exception("Python script failed with return code: $returnCode. Output: $output");
        }
        
        // Look for the generated JSON file
        $safeQuery = str_replace([' ', '/'], ['_', '_'], $foodQuery);
        $jsonFile = __DIR__ . "/../../{$safeQuery}_images.json";
        
        if (!file_exists($jsonFile)) {
            throw new Exception("JSON output file not found: $jsonFile");
        }
        
        // Read and parse the JSON file
        $jsonContent = file_get_contents($jsonFile);
        if ($jsonContent === false) {
            throw new Exception("Failed to read JSON file: $jsonFile");
        }
        
        $imageData = json_decode($jsonContent, true);
        if ($imageData === null) {
            throw new Exception("Failed to parse JSON: " . json_last_error_msg());
        }
        
        // Clean up the temporary file
        unlink($jsonFile);
        
        return $imageData;
        
    } catch (Exception $e) {
        error_log("Error calling Python scraper: " . $e->getMessage());
        return null;
    }
}

// Function to get fallback images
function getFallbackImages($foodQuery) {
    $fallbackUrls = [
        "https://via.placeholder.com/300x200/FF6B6B/FFFFFF?text=" . urlencode($foodQuery),
        "https://via.placeholder.com/300x200/4ECDC4/FFFFFF?text=" . urlencode($foodQuery),
        "https://via.placeholder.com/300x200/45B7D1/FFFFFF?text=" . urlencode($foodQuery),
        "https://via.placeholder.com/300x200/96CEB4/FFFFFF?text=" . urlencode($foodQuery),
        "https://via.placeholder.com/300x200/FFEAA7/000000?text=" . urlencode($foodQuery)
    ];
    
    $fallbackImages = [];
    foreach ($fallbackUrls as $url) {
        $fallbackImages[] = [
            'title' => "$foodQuery image",
            'image_url' => $url,
            'source_url' => $url,
            'query' => $foodQuery
        ];
    }
    
    return $fallbackImages;
}

// Main API logic
try {
    // Get request method
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        // Handle GET request
        $foodQuery = $_GET['query'] ?? '';
        $maxResults = intval($_GET['max_results'] ?? 5);
        
    } elseif ($method === 'POST') {
        // Handle POST request
        $input = json_decode(file_get_contents('php://input'), true);
        $foodQuery = $input['query'] ?? '';
        $maxResults = intval($input['max_results'] ?? 5);
        
    } else {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed. Use GET or POST.',
            'usage' => [
                'GET' => '?query=food_name&max_results=5',
                'POST' => '{"query": "food_name", "max_results": 5}'
            ]
        ]);
        exit;
    }
    
    // Validate input
    if (!validateFoodQuery($foodQuery)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid food query. Must be 2-100 characters long.',
            'query' => $foodQuery
        ]);
        exit;
    }
    
    // Sanitize input
    $foodQuery = sanitizeInput($foodQuery);
    $maxResults = max(1, min(20, $maxResults)); // Limit between 1 and 20
    
    // Log the request
    error_log("Food image scraper request: query='$foodQuery', max_results=$maxResults");
    
    // Call Python scraper
    $imageData = callPythonScraper($foodQuery, $maxResults);
    
    if ($imageData && !empty($imageData)) {
        // Success - return scraped images
        echo json_encode([
            'success' => true,
            'message' => 'Images retrieved successfully',
            'query' => $foodQuery,
            'count' => count($imageData),
            'images' => $imageData
        ]);
        
    } else {
        // Fallback to placeholder images
        $fallbackImages = getFallbackImages($foodQuery);
        
        echo json_encode([
            'success' => true,
            'message' => 'Using fallback images (scraper unavailable)',
            'query' => $foodQuery,
            'count' => count($fallbackImages),
            'images' => $fallbackImages,
            'fallback' => true
        ]);
    }
    
} catch (Exception $e) {
    error_log("Food image scraper error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $e->getMessage()
    ]);
}
?>
