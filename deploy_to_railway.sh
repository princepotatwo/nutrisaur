#!/bin/bash

# Deploy Food Image Scraper to Railway
echo "🚀 Deploying Food Image Scraper to Railway..."

# Check if we're in the right directory
if [ ! -f "public/api/food_image_scraper.php" ]; then
    echo "❌ Error: food_image_scraper.php not found in public/api/"
    exit 1
fi

echo "✅ Found food_image_scraper.php"

# Test the current Railway deployment
echo "🔍 Testing current Railway deployment..."
curl -s "https://nutrisaur-production.up.railway.app/api/login.php" > /dev/null
if [ $? -eq 0 ]; then
    echo "✅ Railway deployment is accessible"
else
    echo "❌ Railway deployment not accessible"
    exit 1
fi

echo ""
echo "📋 Next Steps for Railway Deployment:"
echo ""
echo "1. Go to your Railway dashboard: https://railway.app/dashboard"
echo "2. Navigate to your 'nutrisaur-production' project"
echo "3. Add the file 'public/api/food_image_scraper.php' to your project"
echo "4. Make sure Python 3.6+ is available on your Railway deployment"
echo ""
echo "5. Test the API endpoint:"
echo "   curl 'https://nutrisaur-production.up.railway.app/public/api/food_image_scraper.php?query=sinigang%20na%20baboy&max_results=3'"
echo ""
echo "6. Update FoodImageService.java to use Railway URL (already done)"
echo ""
echo "🎯 Current Status:"
echo "✅ Python scraper: Working"
echo "✅ Android integration: Ready"
echo "✅ API endpoint: Created"
echo "❌ Railway deployment: Needs PHP file added"
echo ""
echo "💡 Alternative: Use Python API server for testing"
echo "   python3 python_api_server.py"
echo "   Then update FoodImageService.java to use: http://10.0.2.2:8000/"
