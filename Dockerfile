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

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mysqli json

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
RUN echo '<?php echo json_encode(["status" => "healthy", "timestamp" => date("Y-m-d H:i:s")]); ?>' > public/health.php

# Expose port (Railway will set the actual port)
EXPOSE 8000

# Start PHP development server using environment variable for port
CMD php -S 0.0.0.0:${PORT:-8000} -t public
