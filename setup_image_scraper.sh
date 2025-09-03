#!/bin/bash

# Setup script for Google Images Scraper
# Based on oxylabs/how-to-scrape-google-images

echo "=== Google Images Scraper Setup for Nutrisaur ==="
echo ""

# Check if Python 3 is installed
if ! command -v python3 &> /dev/null; then
    echo "❌ Python 3 is not installed. Please install Python 3.6+ first."
    exit 1
fi

echo "✅ Python 3 found: $(python3 --version)"

# Install Python dependencies
echo ""
echo "Installing Python dependencies..."
pip3 install -r requirements.txt

if [ $? -eq 0 ]; then
    echo "✅ Python dependencies installed successfully"
else
    echo "❌ Failed to install Python dependencies"
    exit 1
fi

# Test the scraper
echo ""
echo "Testing the scraper..."
python3 test_scraper.py

if [ $? -eq 0 ]; then
    echo "✅ Scraper test completed successfully"
else
    echo "❌ Scraper test failed"
    exit 1
fi

# Check if PHP is available
if command -v php &> /dev/null; then
    echo ""
    echo "✅ PHP found: $(php --version | head -n 1)"
    echo ""
    echo "To test the PHP API endpoint:"
    echo "1. Start a local PHP server: php -S localhost:8000"
    echo "2. Test with: curl 'http://localhost:8000/public/api/food_image_scraper.php?query=sinigang%20na%20baboy'"
else
    echo ""
    echo "⚠️  PHP not found. The PHP API endpoint won't work without PHP."
    echo "You can still use the Python scraper directly."
fi

echo ""
echo "=== Setup Complete ==="
echo ""
echo "Next steps:"
echo "1. Update the API_BASE_URL in FoodImageService.java with your actual domain"
echo "2. Deploy the PHP API endpoint to your web server"
echo "3. Build and test the Android app"
echo ""
echo "For more information, see GOOGLE_IMAGES_SCRAPER_README.md"
