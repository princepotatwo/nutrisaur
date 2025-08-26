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
RUN echo '<?php header("Content-Type: application/json"); echo json_encode(["status" => "healthy", "timestamp" => date("Y-m-d H:i:s"), "port" => $_ENV["PORT"] ?? "8000"]); ?>' > public/health.php

# Expose port
EXPOSE 8000

# Start PHP server on fixed port 8000
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public", "-d", "display_errors=1", "-d", "error_reporting=E_ALL"]
