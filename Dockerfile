FROM php:8.1-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Install PHP extensions (json is built into PHP 8.1)
RUN docker-php-ext-install pdo_mysql mysqli

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY sss/ ./sss/
COPY public/ ./public/

# Create public directory if it doesn't exist
RUN mkdir -p public

# Copy PHP files to public directory
RUN cp -r sss/* public/ 2>/dev/null || true

# Create health check endpoint
RUN echo '<?php header("Content-Type: application/json"); echo json_encode(["status" => "healthy", "timestamp" => date("Y-m-d H:i:s"), "port" => $_ENV["PORT"] ?? "unknown"]); ?>' > public/health.php

# Create startup script
RUN echo '#!/bin/bash\nPORT=${PORT:-8000}\necho "Starting PHP server on port $PORT"\nphp -S 0.0.0.0:$PORT -t public -d display_errors=1 -d error_reporting=E_ALL' > /start.sh && chmod +x /start.sh

# Expose port
EXPOSE 8000

# Start using the script
CMD ["/start.sh"]
