FROM php:8.1-apache

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

# Enable Apache mod_rewrite
RUN a2enmod rewrite

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
RUN echo '<?php header("Content-Type: application/json"); echo json_encode(["status" => "healthy", "timestamp" => date("Y-m-d H:i:s"), "port" => $_ENV["PORT"] ?? "80"]); ?>' > public/health.php

# Set Apache document root to public directory
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
