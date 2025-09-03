<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

function getFoodImages($query, $maxResults = 5) {
    $images = array();
    
    try {
        // Use Unsplash API (free tier, no API key needed for basic usage)
        $searchQuery = urlencode($query . ' food');
        $unsplashUrl = "https://api.unsplash.com/search/photos?query={$searchQuery}&per_page={$maxResults}&orientation=landscape";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $unsplashUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/json',
            'User-Agent: Nutrisaur-Food-App/1.0'
        ));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            
            if (isset($data['results']) && !empty($data['results'])) {
                foreach ($data['results'] as $photo) {
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
        
        // If Unsplash fails, try Pexels API
        if (empty($images)) {
            $pexelsUrl = "https://api.pexels.com/v1/search?query=" . urlencode($query . ' food') . "&per_page={$maxResults}";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $pexelsUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json',
                'User-Agent: Nutrisaur-Food-App/1.0'
            ));
            
            $pexelsResponse = curl_exec($ch);
            $pexelsHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($pexelsHttpCode === 200 && $pexelsResponse) {
                $pexelsData = json_decode($pexelsResponse, true);
                
                if (isset($pexelsData['photos']) && !empty($pexelsData['photos'])) {
                    foreach ($pexelsData['photos'] as $photo) {
                        if (isset($photo['src']['large'])) {
                            $images[] = array(
                                'title' => $query . ' food image',
                                'image_url' => $photo['src']['large'],
                                'source_url' => $photo['url'],
                                'query' => $query
                            );
                        }
                    }
                }
            }
        }
        
        // If both APIs fail, use a simple image service
        if (empty($images)) {
            $imageUrls = array(
                "https://source.unsplash.com/300x200/?{$query},food",
                "https://source.unsplash.com/300x200/?{$query},dish",
                "https://source.unsplash.com/300x200/?{$query},meal",
                "https://source.unsplash.com/300x200/?{$query},cuisine",
                "https://source.unsplash.com/300x200/?{$query},cooking"
            );
            
            foreach ($imageUrls as $url) {
                $images[] = array(
                    'title' => $query . ' food image',
                    'image_url' => $url,
                    'source_url' => $url,
                    'query' => $query
                );
            }
        }
        
    } catch (Exception $e) {
        error_log("Image API error: " . $e->getMessage());
    }
    
    return $images;
}

$query = isset($_GET['query']) ? $_GET['query'] : '';
$maxResults = intval(isset($_GET['max_results']) ? $_GET['max_results'] : 5);

if (empty($query)) {
    echo json_encode(array('success' => false, 'message' => 'Query parameter required'));
    exit;
}

$images = getFoodImages($query, $maxResults);

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
