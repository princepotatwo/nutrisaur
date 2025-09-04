FROM php:8.1-cli

# Install Node.js and essential dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    curl \
    gnupg

# Install Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mysqli

# Set working directory
WORKDIR /var/www/html

# Copy files
COPY sss/ ./sss/
COPY public/ ./public/
COPY config.php ./config.php
COPY vendor/ ./vendor/
COPY package.json ./package.json
COPY email-service.js ./email-service.js

# Ensure config.php is accessible from both root and public directory
RUN cp config.php public/config.php

# Install Node.js dependencies
RUN npm install

# Create startup script that uses Railway's PORT
RUN echo '#!/bin/bash' > /start.sh && \
    echo 'set -e' >> /start.sh && \
    echo 'echo "Environment variables:"' >> /start.sh && \
    echo 'env | grep -E "(PORT|RAILWAY)" || echo "No PORT or RAILWAY variables found"' >> /start.sh && \
    echo 'PORT=${PORT:-8000}' >> /start.sh && \
    echo 'echo "Using port: $PORT"' >> /start.sh && \
    echo 'echo "Starting PHP server on port $PORT"' >> /start.sh && \
    echo 'php -S 0.0.0.0:$PORT -t public public/index.php' >> /start.sh && \
    chmod +x /start.sh

# Expose port
EXPOSE 8000

# Start using the script
CMD ["/bin/bash", "/start.sh"]
