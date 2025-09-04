FROM php:8.1-cli

# Install only the absolute minimum
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Install Node.js (simplified)
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mysqli

# Set working directory
WORKDIR /var/www/html

# Copy everything at once to reduce layers
COPY . .

# Install Node.js dependencies
RUN npm install

# Create simple startup script
RUN echo 'php -S 0.0.0.0:${PORT:-8000} -t public public/index.php' > /start.sh && \
    chmod +x /start.sh

EXPOSE 8000
CMD ["/bin/bash", "/start.sh"]
