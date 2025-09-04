FROM php:8.1-cli

# Install essential dependencies only
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    curl \
    gnupg \
    && rm -rf /var/lib/apt/lists/*

# Install Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mysqli

# Set working directory
WORKDIR /var/www/html

# Copy files in order of least to most likely to change
COPY config.php ./config.php
COPY email_config.php ./email_config.php
COPY package.json ./package.json
COPY email-service-simple.js ./email-service-simple.js
COPY vendor/ ./vendor/
COPY public/ ./public/
COPY sss/ ./sss/

# Ensure config files are accessible from public directory
RUN cp config.php public/config.php && \
    cp email_config.php public/email_config.php

# Install Node.js dependencies
RUN npm install

# Create startup script
RUN echo '#!/bin/bash' > /start.sh && \
    echo 'PORT=${PORT:-8000}' >> /start.sh && \
    echo 'php -S 0.0.0.0:$PORT -t public public/index.php' >> /start.sh && \
    chmod +x /start.sh

# Expose port
EXPOSE 8000

# Start using the script
CMD ["/bin/bash", "/start.sh"]
