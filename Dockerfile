FROM php:8.1-apache

# Install only essential dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mysqli

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy files
COPY sss/ ./sss/
COPY public/ ./public/
COPY config.php ./config.php
COPY email_config.php ./email_config.php
COPY vendor/ ./vendor/

# Ensure config files are accessible from both root and public directory
RUN cp config.php public/config.php
RUN cp email_config.php public/email_config.php

# Configure Apache to use the public directory
RUN echo '<VirtualHost *:80>' > /etc/apache2/sites-available/000-default.conf && \
    echo '    DocumentRoot /var/www/html/public' >> /etc/apache2/sites-available/000-default.conf && \
    echo '    <Directory /var/www/html/public>' >> /etc/apache2/sites-available/000-default.conf && \
    echo '        AllowOverride All' >> /etc/apache2/sites-available/000-default.conf && \
    echo '        Require all granted' >> /etc/apache2/sites-available/000-default.conf && \
    echo '    </Directory>' >> /etc/apache2/sites-available/000-default.conf && \
    echo '</VirtualHost>' >> /etc/apache2/sites-available/000-default.conf

# Create startup script that uses Railway's PORT
RUN echo '#!/bin/bash' > /start.sh && \
    echo 'set -e' >> /start.sh && \
    echo 'echo "Environment variables:"' >> /start.sh && \
    echo 'env | grep -E "(PORT|RAILWAY)" || echo "No PORT or RAILWAY variables found"' >> /start.sh && \
    echo 'PORT=${PORT:-80}' >> /start.sh && \
    echo 'echo "Using port: $PORT"' >> /start.sh && \
    echo 'echo "Starting Apache on port $PORT"' >> /start.sh && \
    echo 'sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf' >> /start.sh && \
    echo 'apache2-foreground' >> /start.sh && \
    chmod +x /start.sh

# Expose port
EXPOSE 80

# Start using the script
CMD ["/bin/bash", "/start.sh"]
