<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

function scrapeImages($query, $maxResults = 5) {
    $images = array();
    
    try {
        $searchUrl = "https://www.google.com/search?q=" . urlencode($query . " food") . "&tbm=isch&hl=en";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $searchUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
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
            throw new Exception("Failed to fetch page. HTTP Code: $httpCode");
        }
        
        // Try multiple patterns to extract image URLs
        $patterns = array(
            '/"ou":"(https:\/\/[^"]+\.(jpg|jpeg|png|webp))"/',
            '/"url":"(https:\/\/[^"]+\.(jpg|jpeg|png|webp))"/',
            '/\["(https:\/\/[^"]+\.(jpg|jpeg|png|webp))"/',
            '/src="(https:\/\/[^"]+\.(jpg|jpeg|png|webp))"/',
            '/data-src="(https:\/\/[^"]+\.(jpg|jpeg|png|webp))"/'
        );
        
        $foundUrls = array();
        
        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $html, $matches);
            if (!empty($matches[1])) {
                $foundUrls = array_merge($foundUrls, $matches[1]);
            }
        }
        
        $foundUrls = array_unique($foundUrls);
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
        
        // If still no images, try using a different search approach
        if (empty($images)) {
            // Use a different search URL
            $altSearchUrl = "https://www.google.com/search?q=" . urlencode($query) . "&tbm=isch&hl=en&tbs=isz:l";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $altSearchUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $altHtml = curl_exec($ch);
            curl_close($ch);
            
            if ($altHtml) {
                foreach ($patterns as $pattern) {
                    preg_match_all($pattern, $altHtml, $matches);
                    if (!empty($matches[1])) {
                        $foundUrls = array_merge($foundUrls, $matches[1]);
                    }
                }
                
                $foundUrls = array_unique($foundUrls);
                $count = 0;
                
                foreach ($foundUrls as $url) {
                    if ($count >= $maxResults) break;
                    
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
