# Google Images Scraper for Nutrisaur Food Recommendations

This implementation integrates Google Images scraping functionality into the Nutrisaur app's personalized food recommendation system, based on the [oxylabs/how-to-scrape-google-images](https://github.com/oxylabs/how-to-scrape-google-images) repository.

## Overview

The system consists of:
1. **Python Google Images Scraper** - Scrapes food images from Google Images
2. **PHP API Endpoint** - Bridges the Python scraper with the Android app
3. **Android FoodImageService** - Handles image loading in the Android app
4. **Updated FoodRecommendation Model** - Now includes image URL support

## Files Created/Modified

### New Files:
- `google_images_scraper.py` - Basic Google Images scraper
- `google_images_scraper_advanced.py` - Advanced scraper with better parsing
- `public/api/food_image_scraper.php` - PHP API endpoint
- `app/src/main/java/com/example/nutrisaur11/FoodImageService.java` - Android image service
- `requirements.txt` - Python dependencies
- `test_scraper.py` - Test script for the scraper

### Modified Files:
- `app/src/main/java/com/example/nutrisaur11/FoodRecommendation.java` - Added imageUrl field
- `app/src/main/java/com/example/nutrisaur11/FoodRecommendationAdapter.java` - Updated to use FoodImageService
- `app/src/main/java/com/example/nutrisaur11/FoodRecommendationActivity.java` - Updated to handle image URLs

## Setup Instructions

### 1. Install Python Dependencies

```bash
pip install -r requirements.txt
```

Required packages:
- `requests>=2.25.1`
- `pandas>=1.3.0`
- `beautifulsoup4>=4.9.3`
- `lxml>=4.6.3`
- `urllib3>=1.26.0`

### 2. Test the Python Scraper

```bash
# Test the basic scraper
python3 google_images_scraper.py "sinigang na baboy" 3

# Test the advanced scraper
python3 google_images_scraper_advanced.py "adobo" 5

# Run the test script
python3 test_scraper.py
```

### 3. Configure the PHP API

Update the API base URL in `FoodImageService.java`:

```java
private static final String API_BASE_URL = "https://your-domain.com/public/api/";
```

Replace `your-domain.com` with your actual domain where the PHP API is hosted.

### 4. Test the PHP API

```bash
# Test with GET request
curl "https://your-domain.com/public/api/food_image_scraper.php?query=sinigang%20na%20baboy&max_results=3"

# Test with POST request
curl -X POST "https://your-domain.com/public/api/food_image_scraper.php" \
  -H "Content-Type: application/json" \
  -d '{"query": "adobo", "max_results": 3}'
```

## How It Works

### 1. Food Recommendation Generation
When the Android app generates a food recommendation:
- The Gemini API creates a recommendation with food details
- Optionally includes an `image_url` field in the JSON response
- The `FoodRecommendation` model stores this image URL

### 2. Image Loading Process
When displaying a food recommendation:

1. **Check Cache**: First checks if the image is already cached
2. **Use Image URL**: If the recommendation has an image URL, uses it directly
3. **Scrape Images**: If no image URL, calls the PHP API to scrape Google Images
4. **Load Image**: Downloads and displays the image
5. **Cache**: Stores the image in memory cache for future use

### 3. Progressive Loading
- Preloads images for the next 2 recommendations
- Uses background threads to avoid blocking the UI
- Implements timeout and fallback mechanisms

## API Endpoints

### GET /public/api/food_image_scraper.php
```
Parameters:
- query: Food name to search for (required)
- max_results: Number of images to return (optional, default: 5)

Example:
GET /public/api/food_image_scraper.php?query=sinigang%20na%20baboy&max_results=3
```

### POST /public/api/food_image_scraper.php
```json
{
  "query": "sinigang na baboy",
  "max_results": 3
}
```

### Response Format
```json
{
  "success": true,
  "message": "Images retrieved successfully",
  "query": "sinigang na baboy",
  "count": 3,
  "images": [
    {
      "title": "sinigang na baboy image",
      "image_url": "https://example.com/image1.jpg",
      "source_url": "https://example.com/image1.jpg",
      "query": "sinigang na baboy"
    }
  ]
}
```

## Features

### ✅ Implemented
- Google Images scraping using multiple extraction methods
- PHP API endpoint with input validation and sanitization
- Android FoodImageService with caching and error handling
- Progressive image loading for smooth UX
- Fallback to placeholder images when scraping fails
- Memory-efficient caching system
- Background image preloading

### 🔄 Fallback System
If the Google Images scraper fails:
1. Returns placeholder images with food names
2. Uses default food icons in the Android app
3. Logs errors for debugging
4. Continues app functionality without interruption

### 🚀 Performance Optimizations
- Memory-based LRU cache for images
- Background thread pool for image loading
- Progressive loading (current + next 2 images)
- Request deduplication to avoid duplicate API calls
- Timeout handling to prevent stuck loading states

## Troubleshooting

### Common Issues

1. **Python scraper not working**
   - Check if all dependencies are installed
   - Verify Python 3.6+ is being used
   - Check internet connectivity

2. **PHP API errors**
   - Ensure Python script path is correct in PHP
   - Check file permissions for script execution
   - Verify PHP has shell_exec enabled

3. **Android app not loading images**
   - Check API base URL configuration
   - Verify network connectivity
   - Check Android logs for error messages

### Debugging

1. **Test Python scraper directly**:
   ```bash
   python3 google_images_scraper_advanced.py "test food" 1
   ```

2. **Check PHP API logs**:
   ```bash
   tail -f /var/log/apache2/error.log
   ```

3. **Monitor Android logs**:
   ```bash
   adb logcat | grep FoodImageService
   ```

## Security Considerations

- Input validation and sanitization in PHP API
- URL encoding for food queries
- Rate limiting (implemented in PHP)
- Error handling without exposing sensitive information
- Secure image URL validation

## Future Enhancements

1. **Image Quality Filtering**: Filter images by size and quality
2. **Multiple Image Sources**: Integrate with other image APIs
3. **Image Categorization**: Categorize images by food type
4. **Offline Support**: Cache images for offline viewing
5. **Image Compression**: Optimize image sizes for mobile

## License

This implementation is based on the [oxylabs/how-to-scrape-google-images](https://github.com/oxylabs/how-to-scrape-google-images) repository and adapted for the Nutrisaur app's specific needs.

## Support

For issues or questions:
1. Check the troubleshooting section above
2. Review Android logs for error messages
3. Test individual components separately
4. Verify all dependencies are properly installed
