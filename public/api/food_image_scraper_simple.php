<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

function scrapeImages($query, $maxResults = 5) {
    $images = array();
    
    try {
        $searchUrl = "https://www.google.com/search?q=" . urlencode($query . " food") . "&tbm=isch";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $searchUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $html = curl_exec($ch);
        curl_close($ch);
        
        if ($html) {
            // Extract image URLs
            preg_match_all('/"ou":"(https:\/\/[^"]+\.(jpg|jpeg|png|webp))"/', $html, $matches);
            
            $foundUrls = array_unique($matches[1]);
            $count = 0;
            
            foreach ($foundUrls as $url) {
                if ($count >= $maxResults) break;
                
                $images[] = array(
                    'title' => $query . ' food image',
                    'image_url' => $url,
                    'source_url' => $url,
                    'query' => $query
                );
                $count++;
            }
        }
        
    } catch (Exception $e) {
        error_log("Scraping error: " . $e->getMessage());
    }
    
    return $images;
}

$query = isset($_GET['query']) ? $_GET['query'] : '';
$maxResults = intval(isset($_GET['max_results']) ? $_GET['max_results'] : 5);

if (empty($query)) {
    echo json_encode(array('success' => false, 'message' => 'Query parameter required'));
    exit;
}

$images = scrapeImages($query, $maxResults);

if (!empty($images)) {
    echo json_encode(array(
        'success' => true,
        'message' => 'Images found',
        'query' => $query,
        'count' => count($images),
        'images' => $images
    ));
} else {
    echo json_encode(array(
        'success' => false,
        'message' => 'No images found',
        'query' => $query
    ));
}
?>
