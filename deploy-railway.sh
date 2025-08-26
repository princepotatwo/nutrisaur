#!/bin/bash

# Nutrisaur Railway Deployment Script
echo "🚀 Preparing Nutrisaur for Railway deployment..."

# Create public directory if it doesn't exist
if [ ! -d "public" ]; then
    echo "📁 Creating public directory..."
    mkdir -p public
fi

# Copy PHP files to public directory
echo "📋 Copying PHP files to public directory..."
cp -r sss/* public/ 2>/dev/null || echo "⚠️  sss directory not found"
cp -r thesis355/* public/ 2>/dev/null || echo "⚠️  thesis355 directory not found"

# Create health check file
echo "🏥 Creating health check endpoint..."
cat > public/health.php << 'EOF'
<?php
header('Content-Type: application/json');
echo json_encode([
    'status' => 'healthy',
    'timestamp' => date('Y-m-d H:i:s'),
    'version' => '1.0.0',
    'message' => 'Nutrisaur is running successfully!'
]);
?>
EOF

# Create main index file if it doesn't exist
if [ ! -f "public/index.php" ]; then
    echo "🏠 Creating main index file..."
    cat > public/index.php << 'EOF'
<?php
// Main entry point for Nutrisaur Web Application
session_start();

// Set headers for CORS and security
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: text/html; charset=UTF-8');

// Check if it's a preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Simple routing
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = trim($path, '/');

// Route to appropriate file
switch ($path) {
    case '':
    case 'index':
        if (file_exists('settings_verified_mho.php')) {
            include 'settings_verified_mho.php';
        } else {
            echo '<h1>Welcome to Nutrisaur</h1>';
            echo '<p>Malnutrition Assessment System</p>';
            echo '<p><a href="/health">Health Check</a></p>';
        }
        break;
    case 'health':
        include 'health.php';
        break;
    default:
        // Try to find the file
        if (file_exists("$path.php")) {
            include "$path.php";
        } else {
            http_response_code(404);
            echo '<h1>404 - Page Not Found</h1>';
            echo '<p>The requested page could not be found.</p>';
            echo '<p><a href="/">Go to Home</a></p>';
        }
        break;
}
?>
EOF
fi

# Set proper permissions
echo "🔐 Setting file permissions..."
chmod 755 public/
chmod 644 public/*.php 2>/dev/null || echo "⚠️  No PHP files found"

echo "✅ Railway deployment preparation complete!"
echo ""
echo "📋 Next steps:"
echo "1. Commit and push these changes to your GitHub repository"
echo "2. In Railway dashboard, connect your repository"
echo "3. Railway will automatically detect the configuration and deploy"
echo "4. Check the deployment logs for any issues"
echo ""
echo "🔗 Your app will be available at the Railway-provided URL"
echo "🏥 Health check: /health endpoint"
